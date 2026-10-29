<?php

namespace App\Http\Controllers;

use App\Models\Lhp;
use App\Models\Opd;
use App\Models\Pegawai;
use App\Models\Pemeriksaan;
use App\Models\TahunAnggaran;
use App\Services\AuditLogger;
use App\Services\PemeriksaanService;
use App\Http\Requests\StorePemeriksaanRequest;
use App\Http\Requests\UpdatePemeriksaanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PemeriksaanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Pemeriksaan::forUser($user)
            ->active()
            ->where('jenis_pengajuan', Pemeriksaan::JENIS_PENGAJUAN_SPT_DAN_LHP)
            ->with([
                'opd', 
                'timPemeriksas.pegawai', 
                'lhps' => function($q) use ($user) { $q->forUser($user); }, 
                'temuans' => function($q) use ($user) { $q->forUser($user)->active(); }, 
                // creator.pegawai wajib di-eager load: accessor User::official_name
                // membaca relasi pegawai, sehingga tanpa ini terjadi lazy loading.
                'creator.pegawai',
            ])
            ->withCount('temuans')
            ->withSum('temuans', 'nilai_kerugian');

        // Filter: Sumber Pemeriksaan
        if ($request->filled('sumber')) {
            $query->where('sumber_pemeriksaan', $request->sumber);
        }

        // Filter: OPD
        if ($request->filled('opd_id')) {
            $query->where('opd_id', $request->opd_id);
        }

        // Filter: Tahun
        if ($request->filled('tahun')) {
            $query->where('tahun', $request->tahun);
        }

        // Filter: Status Pengajuan
        if ($request->filled('status_pengajuan')) {
            if ($request->status_pengajuan === 'PERLU REVISI') {
                $query->whereIn('status_pengajuan', ['PERLU REVISI', 'DITOLAK']);
            } else {
                $query->where('status_pengajuan', $request->status_pengajuan);
            }
        }

        // Filter: Status Penyelesaian
        if ($request->filled('penyelesaian')) {
            $resolvedStatuses = ['Sesuai', 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti'];

            if ($request->penyelesaian === 'tuntas') {
                // Semua temuan sudah resolved DAN harus punya minimal 1 temuan
                $query->whereHas('temuans', function ($q) {
                    $q->where('is_archived', false);
                })->whereDoesntHave('temuans', function ($q) use ($resolvedStatuses) {
                    $q->where('is_archived', false)
                      ->whereNotIn('status_penyelesaian', $resolvedStatuses);
                });
            } elseif ($request->penyelesaian === 'belum_tuntas') {
                // Masih ada temuan yang belum resolved
                $query->whereHas('temuans', function ($q) use ($resolvedStatuses) {
                    $q->where('is_archived', false)
                      ->whereNotIn('status_penyelesaian', $resolvedStatuses);
                });
            } elseif ($request->penyelesaian === 'belum_ada_temuan') {
                // Belum punya temuan sama sekali
                $query->whereDoesntHave('temuans', function ($q) {
                    $q->where('is_archived', false);
                });
            }
        }

        // Search: Judul, Nomor SPT
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('nomor_spt', 'like', "%{$search}%")
                    ->orWhereHas('opd', function ($oq) use ($search) {
                        $oq->where('nama', 'like', "%{$search}%")
                            ->orWhere('kode', 'like', "%{$search}%");
                    });
            });
        }

        // Hitung Ringkasan Statistik Status Penyelesaian untuk Scope Pengguna
        $resolvedStatuses = ['Sesuai', 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti'];
        $baseStatsQuery = Pemeriksaan::forUser($user)
            ->active()
            ->where('jenis_pengajuan', Pemeriksaan::JENIS_PENGAJUAN_SPT_DAN_LHP);
        if ($request->filled('sumber')) {
            $baseStatsQuery->where('sumber_pemeriksaan', $request->sumber);
        }
        if ($request->filled('opd_id')) {
            $baseStatsQuery->where('opd_id', $request->opd_id);
        }
        if ($request->filled('tahun')) {
            $baseStatsQuery->where('tahun', $request->tahun);
        }
        if ($request->filled('search')) {
            $searchKeyword = $request->search;
            $baseStatsQuery->where(function ($q) use ($searchKeyword) {
                $q->where('judul', 'like', "%{$searchKeyword}%")
                    ->orWhere('nomor_spt', 'like', "%{$searchKeyword}%")
                    ->orWhereHas('opd', function ($oq) use ($searchKeyword) {
                        $oq->where('nama', 'like', "%{$searchKeyword}%")
                            ->orWhere('kode', 'like', "%{$searchKeyword}%");
                    });
            });
        }

        $totalPemeriksaan = (clone $baseStatsQuery)->count();
        $totalTuntas = (clone $baseStatsQuery)->whereHas('temuans', function ($q) {
            $q->where('is_archived', false);
        })->whereDoesntHave('temuans', function ($q) use ($resolvedStatuses) {
            $q->where('is_archived', false)
              ->whereNotIn('status_penyelesaian', $resolvedStatuses);
        })->count();

        $totalBelumTuntas = (clone $baseStatsQuery)->whereHas('temuans', function ($q) use ($resolvedStatuses) {
            $q->where('is_archived', false)
              ->whereNotIn('status_penyelesaian', $resolvedStatuses);
        })->count();

        $totalBelumAdaTemuan = (clone $baseStatsQuery)->whereDoesntHave('temuans', function ($q) {
            $q->where('is_archived', false);
        })->count();

        $pemeriksaans = $query->latest()->paginate(15)->withQueryString();

        $opds = Opd::active()->orderBy('nama')->get();
        $years = TahunAnggaran::allYears();
        $sumberList = ['Inspektorat'];
        $opdId = $request->input('opd_id');
        $sumber = $request->input('sumber');
        $tahun = $request->input('tahun');
        $status = $request->input('status') ?? $request->input('status_pengajuan');
        $statusPengajuan = $status;
        $search = $request->input('search');
        $penyelesaian = $request->input('penyelesaian');

        return view('pemeriksaan.index', compact(
            'pemeriksaans',
            'opds',
            'years',
            'sumberList',
            'opdId',
            'sumber',
            'tahun',
            'status',
            'statusPengajuan',
            'search',
            'penyelesaian',
            'totalPemeriksaan',
            'totalTuntas',
            'totalBelumTuntas',
            'totalBelumAdaTemuan'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $user = $request->user();

        if ($user->isPemantau() || $user->isOpd()) {
            abort(403, 'Akses ditolak: Hanya Admin Sumber dan Super Admin yang dapat menginput pemeriksaan.');
        }

        // Admin Sumber BPK, BPKP, ITJEN langsung diarahkan ke form LHP & Tindak Lanjut (tanpa SPT)
        if (! $user->canManageSpt()) {
            return redirect()->route('lhp.create');
        }

        $pegawaiInspektur = Pegawai::inspekturDaerah()->first();
        $pegawaiQuery = Pegawai::active();
        if ($pegawaiInspektur) {
            $pegawaiQuery->where('id', '!=', $pegawaiInspektur->id);
        }
        $pegawais = $pegawaiQuery->orderBy('nama')->get();
        $opds = Opd::active()->orderBy('nama')->get();
        $years = TahunAnggaran::activeYears();
        $currentYear = TahunAnggaran::currentYear();
        $sumberList = ['Inspektorat'];

        $lockedSumber = $user->isAdminSumber() ? $user->getSumberPemeriksaan() : null;

        return view('pemeriksaan.create', compact('opds', 'pegawais', 'pegawaiInspektur', 'years', 'currentYear', 'sumberList', 'lockedSumber'));
    }

    /**
     * Keep old wizard URLs working by sending users to the classic form.
     */
    public function createWizard()
    {
        return redirect()->route('pemeriksaan.create');
    }

    /**
     * Store a newly created resource in storage (including unified Multi-LHP and Multi-Tindak Lanjut).
     */
        public function store(StorePemeriksaanRequest $request, PemeriksaanService $service)
    {
        $validated = $request->validated();
        
        $pemeriksaan = $service->createPemeriksaan($validated, $request->allFiles(), $request->user());

        $countLhp = $pemeriksaan->lhp_count_for_message ?? 0;
        $msg = "Data Pemeriksaan & SPT berhasil disimpan sebagai DRAFT" . ($countLhp > 0 ? " beserta {$countLhp} LHP." : ".");

        return redirect()->route('pemeriksaan.show', $pemeriksaan)->with('success', $msg);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Pemeriksaan $pemeriksaan)
    {
        $user = $request->user();

        // Ensure user is authorized to view this pemeriksaan
        if (! $pemeriksaan->forUser($user)->where('id', $pemeriksaan->id)->exists()) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat data pemeriksaan ini.');
        }

        if ($user->isOpd()) {
            $pemeriksaan->load([
                'lhps' => function ($query) use ($user) {
                    $query->forUser($user);
                },
                'lhps.opds',
                'lhps.temuans' => function ($query) use ($user) {
                    $query->forUser($user)
                        ->active()
                        ->where('opd_id', $user->opd_id);
                },
                'lhps.temuans.tindakLanjuts',
                'creator.pegawai',
            ]);

            $pemeriksaan->lhps->each(function (Lhp $lhp) use ($user): void {
                $lhp->setRelation('opds', $lhp->opds->where('id', $user->opd_id)->values());
            });

            return view('pemeriksaan.show-opd', [
                'pemeriksaan' => $pemeriksaan,
                'focusedLhp' => null,
            ]);
        }

        // Load the extended examination data only for roles that manage or review it.
        $pemeriksaan->load([
            'opd',
            'timPemeriksas.pegawai',
            'lhps' => function ($q) use ($user) {
                $q->forUser($user);
            },
            'lhps.opds',
            'lhps.temuans' => function ($q) use ($user) {
                $q->forUser($user)->active();
            },
            'lhps.temuans.opd',
            'lhps.temuans.creator.pegawai',
            'lhps.temuans.rekomendasis',
            'lhps.temuans.tindakLanjuts.creator.pegawai',
            'lhps.temuans.tindakLanjuts.verifier.pegawai',
            'temuans' => function ($q) use ($user) {
                $q->forUser($user)->active();
            },
            'temuans.opd',
            'temuans.creator.pegawai',
            'temuans.rekomendasis',
            'temuans.tindakLanjuts.creator.pegawai',
            'temuans.tindakLanjuts.verifier.pegawai',
            'creator.pegawai',
            'creator.role',
            'approver.pegawai',
        ]);

        $assignedPegawaiIds = $pemeriksaan->timPemeriksas->pluck('pegawai_id')->toArray();
        $pegawaiInspekturId = Pegawai::inspekturDaerah()->value('id');
        if ($pegawaiInspekturId) {
            $assignedPegawaiIds[] = $pegawaiInspekturId;
        }
        $availablePegawais = Pegawai::active()->whereNotIn('id', $assignedPegawaiIds)->orderBy('nama')->get();
        $allOpds = Opd::active()->orderBy('nama')->get();

        return view('pemeriksaan.show', compact('pemeriksaan', 'availablePegawais', 'allOpds'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Pemeriksaan $pemeriksaan)
    {
        $user = $request->user();

        if ($pemeriksaan->is_lhp_only_submission) {
            return redirect()->route('pemeriksaan.show', $pemeriksaan)
                ->with('info', 'Pengajuan ini hanya memuat LHP dan tidak memiliki data SPT untuk diedit.');
        }

        if (! $user->canManageSpt()) {
            abort(403, 'Surat Perintah Tugas (SPT) hanya dapat diedit oleh Admin Inspektorat.');
        }

        if ($user->isAdminSumber() && $pemeriksaan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
            abort(403, 'Anda hanya dapat mengedit pemeriksaan dari sumber ' . $user->getSumberPemeriksaan());
        }


        $pegawaiInspektur = Pegawai::inspekturDaerah()->first();
        $pegawaiQuery = Pegawai::active();
        if ($pegawaiInspektur) {
            $pegawaiQuery->where('id', '!=', $pegawaiInspektur->id);
        }
        $pegawais = $pegawaiQuery->orderBy('nama')->get();
        $allOpds = Opd::active()->orderBy('nama')->get();
        $years = TahunAnggaran::activeYears()->prepend($pemeriksaan->tahun)->unique()->sortDesc()->values();
        $sumberList = ['Inspektorat'];
        $lockedSumber = $user->isAdminSumber() ? $user->getSumberPemeriksaan() : null;

        // Semua relasi yang dipakai view form edit harus dimuat lebih dulu.
        // Daftar ini mengikuti kebutuhan resources/views/pemeriksaan/edit.blade.php.
        $pemeriksaan->load([
            'opd',
            'timPemeriksas.pegawai',
            'lhps.opds',
            'lhps.temuans' => function ($q) {
                $q->active();
            },
            'lhps.temuans.opd',
            'lhps.temuans.rekomendasis',
            'lhps.temuans.tindakLanjuts.creator.pegawai',
            'lhps.temuans.tindakLanjuts.verifier.pegawai',
            'temuans' => function ($q) {
                $q->active();
            },
            'temuans.opd',
            'temuans.rekomendasis',
            'temuans.tindakLanjuts.creator.pegawai',
            'temuans.tindakLanjuts.verifier.pegawai',
        ]);

        return view('pemeriksaan.edit', compact('pemeriksaan', 'allOpds', 'pegawais', 'pegawaiInspektur', 'years', 'sumberList', 'lockedSumber'));
    }

    /**
     * Update the specified resource in storage (including unified LHP).
     */
        public function update(UpdatePemeriksaanRequest $request, Pemeriksaan $pemeriksaan, PemeriksaanService $service)
    {
        $validated = $request->validated();
        
        $service->updatePemeriksaan($pemeriksaan, $validated, $request->allFiles(), $request->user());

        return redirect()->route('pemeriksaan.show', $pemeriksaan)
            ->with('success', 'Data SPT & LHP berhasil diperbarui.');
    }

    /**
     * Download or view SPT document.
     */
    public function downloadSpt(Request $request, Pemeriksaan $pemeriksaan)
    {
        $user = $request->user();

        if (! $pemeriksaan->forUser($user)->where('id', $pemeriksaan->id)->exists()) {
            abort(403, 'Akses ditolak.');
        }

        if (! $pemeriksaan->dokumen_spt) {
            return back()->with('error', 'Dokumen SPT tidak ditemukan.');
        }

        $disk = Storage::disk('local')->exists($pemeriksaan->dokumen_spt) ? 'local' : (Storage::disk('public')->exists($pemeriksaan->dokumen_spt) ? 'public' : null);

        if (! $disk) {
            return back()->with('error', 'Dokumen SPT tidak ditemukan di penyimpanan server.');
        }

        AuditLogger::log('DOWNLOAD', 'SPT', "Mengunduh berkas SPT (Pemeriksaan ID: {$pemeriksaan->id}, SPT: {$pemeriksaan->nomor_spt}).");

        $downloadFilename = str_replace(['/', '\\'], '_', $pemeriksaan->nama_dokumen_spt_asli ?? ('SPT_' . str_replace(['/', '\\'], '_', $pemeriksaan->nomor_spt) . '.pdf'));

        return Storage::disk($disk)->download(
            $pemeriksaan->dokumen_spt,
            $downloadFilename
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Pemeriksaan $pemeriksaan)
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && ! ($user->isAdminSumber() && $pemeriksaan->sumber_pemeriksaan === $user->getSumberPemeriksaan())) {
            abort(403, 'Anda tidak memiliki wewenang untuk menghapus data pemeriksaan ini.');
        }

        AuditLogger::log('DELETE', 'PEMERIKSAAN', "Menghapus Pemeriksaan (ID: {$pemeriksaan->id}, SPT: {$pemeriksaan->nomor_spt}). Data dialihkan ke arsip pemulihan.");

        // Cascade soft delete to child temuans
        $pemeriksaan->temuans()->delete();

        $pemeriksaan->delete();

        return redirect()->route('pemeriksaan.index')->with('success', 'Data Pemeriksaan berhasil dihapus dan dipindahkan ke Arsip & Pemulihan.');
    }

    /**
     * Archive a fully-resolved pemeriksaan (hide from active list but keep data in Laporan/Rekapan).
     */
    public function archive(Request $request, Pemeriksaan $pemeriksaan)
    {
        $user = $request->user();

        // Hanya Admin Sumber (sesuai sumber) dan Super Admin yang bisa mengarsipkan
        if (! $user->isSuperAdmin() && ! $user->isAdminSumber()) {
            abort(403, 'Anda tidak memiliki wewenang untuk mengarsipkan data pemeriksaan ini.');
        }

        // Admin Sumber hanya bisa arsipkan sumber miliknya
        if ($user->isAdminSumber() && $pemeriksaan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
            abort(403, 'Anda hanya dapat mengarsipkan pemeriksaan dari sumber ' . $user->getSumberPemeriksaan());
        }

        // Pastikan semua temuan sudah selesai ditindaklanjuti
        if (! $pemeriksaan->isFullyResolved()) {
            return back()->with('error', 'Pemeriksaan ini belum dapat diarsipkan karena masih ada temuan yang belum selesai ditindaklanjuti.');
        }

        $pemeriksaan->update(['is_archived' => true]);

        AuditLogger::log(
            'ARCHIVE',
            'PEMERIKSAAN',
            "Mengarsipkan Pemeriksaan (ID: {$pemeriksaan->id}, SPT: {$pemeriksaan->nomor_spt}). Data temuan tetap tersimpan di Laporan Rekapan."
        );

        return redirect()->route('pemeriksaan.index')->with('success', 'Data Pemeriksaan berhasil diarsipkan. Data temuan dan tindak lanjut tetap tersimpan di Laporan Rekapan.');
    }
}
