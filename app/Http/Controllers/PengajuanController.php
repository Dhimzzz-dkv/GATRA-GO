<?php

namespace App\Http\Controllers;

use App\Models\Pemeriksaan;
use App\Models\Temuan;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PengajuanController extends Controller
{
    /**
     * Display the approval queue and submission management hub.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->isSuperAdmin()) {
            abort(403, 'Akses ditolak. Halaman Pengajuan & Persetujuan hanya untuk Super Administrator.');
        }

        $status = $request->input('status', 'MENUNGGU PERSETUJUAN');
        $sumber = $request->input('sumber');

        // Scoped query for Pemeriksaan
        $pemeriksaanQuery = Pemeriksaan::forUser($user)
            ->active()
            ->where(function ($query) {
                $query->where('sumber_pemeriksaan', 'Inspektorat')
                    ->orWhereHas('lhps');
            })
            ->with(['opd', 'creator', 'lhps']);

        // Count pending badges
        $pendingPemeriksaanCount = (clone $pemeriksaanQuery)->where('status_pengajuan', 'MENUNGGU PERSETUJUAN')->count();
        $revisiPemeriksaanCount = (clone $pemeriksaanQuery)->whereIn('status_pengajuan', ['PERLU REVISI', 'DITOLAK'])->count();

        // Apply filters
        if ($status === 'PERLU REVISI') {
            $pemeriksaanQuery->whereIn('status_pengajuan', ['PERLU REVISI', 'DITOLAK']);
        } elseif ($status) {
            $pemeriksaanQuery->where('status_pengajuan', $status);
        }

        if ($sumber) {
            $pemeriksaanQuery->where('sumber_pemeriksaan', $sumber);
        }

        $pemeriksaans = $pemeriksaanQuery->orderBy('updated_at', 'desc')->paginate(10, ['*'], 'pemeriksaan_page')->withQueryString();

        $sumberList = ['BPK', 'BPKP', 'ITJEN', 'Inspektorat'];

        return view('pengajuan.index', compact(
            'status',
            'sumber',
            'pemeriksaans',
            'pendingPemeriksaanCount',
            'revisiPemeriksaanCount',
            'sumberList'
        ));
    }

    /**
     * Show source administrators the approval status of their submitted LHPs.
     */
    public function mine(Request $request): View
    {
        $user = $request->user();

        if (! $user->isAdminSumber() || $user->canManageSpt()) {
            abort(403, 'Halaman ini hanya tersedia untuk Admin Sumber non-Inspektorat.');
        }

        $source = $user->getSumberPemeriksaan();

        if (! $source) {
            abort(403, 'Akun Anda belum terhubung dengan sumber pemeriksaan.');
        }

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(['MENUNGGU PERSETUJUAN', 'DISETUJUI', 'PERLU REVISI', 'DRAFT'])],
            'tahun' => ['nullable', 'integer', 'digits:4'],
        ]);

        $status = $validated['status'] ?? '';
        $tahun = $validated['tahun'] ?? '';

        $baseQuery = Pemeriksaan::query()
            ->forUser($user)
            ->active()
            ->where('jenis_pengajuan', Pemeriksaan::JENIS_PENGAJUAN_LHP_ONLY)
            ->where('sumber_pemeriksaan', $source);

        $statusCounts = [
            'MENUNGGU PERSETUJUAN' => (clone $baseQuery)->where('status_pengajuan', 'MENUNGGU PERSETUJUAN')->count(),
            'DISETUJUI' => (clone $baseQuery)->where('status_pengajuan', 'DISETUJUI')->count(),
            'PERLU REVISI' => (clone $baseQuery)->whereIn('status_pengajuan', ['PERLU REVISI', 'DITOLAK'])->count(),
        ];

        $years = (clone $baseQuery)
            ->select('tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        $pemeriksaanQuery = (clone $baseQuery)
            ->with(['lhps.opds'])
            ->withCount('lhps');

        if ($status === 'PERLU REVISI') {
            $pemeriksaanQuery->whereIn('status_pengajuan', ['PERLU REVISI', 'DITOLAK']);
        } elseif ($status !== '') {
            $pemeriksaanQuery->where('status_pengajuan', $status);
        }

        if ($tahun !== '') {
            $pemeriksaanQuery->where('tahun', $tahun);
        }

        $pemeriksaans = $pemeriksaanQuery
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        return view('pengajuan.mine', compact('source', 'status', 'statusCounts', 'tahun', 'years', 'pemeriksaans'));
    }

    // ==========================================
    // PEMERIKSAAN SUBMISSION & APPROVAL METHODS
    // ==========================================

    /**
     * Submit Pemeriksaan for approval.
     */
    public function submitPemeriksaan(Request $request, Pemeriksaan $pemeriksaan)
    {
        $user = $request->user();
        $targetLabel = $this->approvalTargetLabel($pemeriksaan);

        // Hanya Super Admin atau Admin Sumber yang sah yang dapat mengajukan
        if (! $user->isSuperAdmin() && ! $user->isAdminSumber()) {
            abort(403, 'Akses ditolak: Hanya Admin Sumber atau Super Admin yang dapat mengajukan pemeriksaan.');
        }

        if ($user->isAdminSumber() && $pemeriksaan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
            abort(403, 'Akses ditolak: Anda hanya dapat mengajukan pemeriksaan dari sumber ' . $user->getSumberPemeriksaan());
        }

        if (! in_array($pemeriksaan->status_pengajuan, ['DRAFT', 'PERLU REVISI', 'DITOLAK'])) {
            return back()->with('error', ucfirst($targetLabel) . ' tidak dapat diajukan (status saat ini: ' . $pemeriksaan->status_pengajuan . ').');
        }

        if ($pemeriksaan->is_lhp_only_submission && ! $pemeriksaan->lhps()->exists()) {
            return back()->with('error', 'Belum ada LHP yang dapat diajukan. Simpan minimal satu LHP terlebih dahulu.');
        }

        $oldAttributes = $pemeriksaan->getAttributes();

        // Transaksi: status pemeriksaan dan seluruh temuannya harus berubah
        // bersama. Bila hanya pemeriksaan yang ter-update, temuan tetap
        // MENUNGGU PERSETUJUAN sehingga OPD tidak dapat menindaklanjutinya.
        DB::transaction(function () use ($pemeriksaan) {
            $pemeriksaan->update([
                'status_pengajuan' => 'MENUNGGU PERSETUJUAN',
            ]);

            $pemeriksaan->temuans()->update([
                'status_pengajuan' => 'MENUNGGU PERSETUJUAN',
            ]);
        }, 3);

        AuditLogger::logModelChange('SUBMIT', $pemeriksaan, 'PEMERIKSAAN', "Mengajukan {$targetLabel} (ID: {$pemeriksaan->id}) untuk verifikasi Super Admin.", $oldAttributes);

        return back()->with('success', ucfirst($targetLabel) . ' berhasil diajukan untuk diverifikasi.');
    }

    /**
     * Cancel Pemeriksaan submission.
     */
    public function cancelPemeriksaan(Request $request, Pemeriksaan $pemeriksaan)
    {
        $user = $request->user();
        $targetLabel = $this->approvalTargetLabel($pemeriksaan);

        // Hanya Super Admin atau Admin Sumber yang sah yang dapat membatalkan pengajuan
        if (! $user->isSuperAdmin() && ! $user->isAdminSumber()) {
            abort(403, 'Akses ditolak: Hanya Admin Sumber atau Super Admin yang dapat membatalkan pengajuan pemeriksaan.');
        }

        if ($user->isAdminSumber() && $pemeriksaan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
            abort(403, 'Akses ditolak: Anda hanya dapat membatalkan pengajuan pemeriksaan dari sumber ' . $user->getSumberPemeriksaan());
        }

        if ($pemeriksaan->status_pengajuan !== 'MENUNGGU PERSETUJUAN') {
            return back()->with('error', 'Pengajuan tidak dapat dibatalkan.');
        }

        $oldAttributes = $pemeriksaan->getAttributes();

        DB::transaction(function () use ($pemeriksaan) {
            $pemeriksaan->update([
                'status_pengajuan' => 'DRAFT',
            ]);

            $pemeriksaan->temuans()->update([
                'status_pengajuan' => 'DRAFT',
            ]);
        }, 3);

        AuditLogger::logModelChange('CANCEL_SUBMIT', $pemeriksaan, 'PEMERIKSAAN', "Membatalkan pengajuan {$targetLabel} (ID: {$pemeriksaan->id}) dan mengembalikan ke DRAFT.", $oldAttributes);

        return back()->with('success', 'Pengajuan ' . $targetLabel . ' berhasil dibatalkan dan dikembalikan ke DRAFT.');
    }

    /**
     * Super Admin approves Pemeriksaan.
     */
    public function approvePemeriksaan(Request $request, Pemeriksaan $pemeriksaan)
    {
        $user = $request->user();
        $targetLabel = $this->approvalTargetLabel($pemeriksaan);

        if (! $user->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang berhak menyetujui pengajuan.');
        }

        if ($pemeriksaan->status_pengajuan !== 'MENUNGGU PERSETUJUAN') {
            abort(403, 'Data berstatus DRAFT atau belum diajukan tidak dapat diproses oleh Super Admin.');
        }

        $oldAttributes = $pemeriksaan->getAttributes();

        DB::transaction(function () use ($pemeriksaan, $user) {
            $pemeriksaan->update([
                'status_pengajuan' => 'DISETUJUI',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'catatan_persetujuan' => null,
            ]);

            $pemeriksaan->temuans()->update([
                'status_pengajuan' => 'DISETUJUI',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'catatan_revisi' => null,
            ]);
        }, 3);

        AuditLogger::logModelChange('APPROVE', $pemeriksaan, 'PEMERIKSAAN', "Super Admin menyetujui {$targetLabel} (ID: {$pemeriksaan->id}).", $oldAttributes);

        return back()->with('success', ucfirst($targetLabel) . ' berhasil DISETUJUI.');
    }

    /**
     * Super Admin requests revision for Pemeriksaan.
     */
    public function revisePemeriksaan(Request $request, Pemeriksaan $pemeriksaan)
    {
        $user = $request->user();
        $targetLabel = $this->approvalTargetLabel($pemeriksaan);

        if (! $user->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang berhak meminta revisi pengajuan.');
        }

        if ($pemeriksaan->status_pengajuan !== 'MENUNGGU PERSETUJUAN') {
            abort(403, 'Data berstatus DRAFT atau belum diajukan tidak dapat diproses oleh Super Admin.');
        }

        $request->validate([
            'catatan_revisi' => ['required', 'string', 'min:5'],
        ], [
            'catatan_revisi.required' => 'Catatan revisi wajib diisi untuk menginstruksikan perbaikan.',
        ]);

        $oldAttributes = $pemeriksaan->getAttributes();

        DB::transaction(function () use ($pemeriksaan, $request) {
            $pemeriksaan->update([
                'status_pengajuan' => 'PERLU REVISI',
                'catatan_persetujuan' => $request->catatan_revisi,
            ]);

            $pemeriksaan->temuans()->update([
                'status_pengajuan' => 'PERLU REVISI',
                'catatan_revisi' => $request->catatan_revisi,
            ]);
        }, 3);

        AuditLogger::logModelChange('REVISE', $pemeriksaan, 'PEMERIKSAAN', "Super Admin meminta revisi {$targetLabel} (ID: {$pemeriksaan->id}) dengan catatan: {$request->catatan_revisi}.", $oldAttributes);

        return back()->with('success', ucfirst($targetLabel) . ' telah dikembalikan untuk PERLU REVISI dengan catatan.');
    }

    private function approvalTargetLabel(Pemeriksaan $pemeriksaan): string
    {
        if (! $pemeriksaan->is_lhp_only_submission) {
            return 'Pemeriksaan ' . $pemeriksaan->nomor_spt;
        }

        $lhpNumbers = $pemeriksaan->lhps()
            ->orderBy('id')
            ->pluck('nomor_lhp')
            ->filter()
            ->implode(', ');

        return 'LHP ' . ($lhpNumbers !== '' ? $lhpNumbers : 'sumber ' . $pemeriksaan->sumber_pemeriksaan . ' TA ' . $pemeriksaan->tahun);
    }

}
