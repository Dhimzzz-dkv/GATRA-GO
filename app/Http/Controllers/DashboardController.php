<?php

namespace App\Http\Controllers;

use App\Models\Opd;
use App\Models\Pemeriksaan;
use App\Models\Temuan;
use App\Models\TindakLanjut;
use App\Models\TahunAnggaran;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the enhanced analytical dashboard based on user role and dynamic filters.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user->role;

        $opdId = $request->input('opd_id');
        $sumber = $request->input('sumber');
        $tahun = $request->input('tahun');

        $submissionTypes = $user->isAdminSumber() && ! $user->canManageSpt()
            ? [Pemeriksaan::JENIS_PENGAJUAN_LHP_ONLY]
            : ($user->isSuperAdmin() || $user->isPemantau() || $user->isOpd()
                ? [Pemeriksaan::JENIS_PENGAJUAN_SPT_DAN_LHP, Pemeriksaan::JENIS_PENGAJUAN_LHP_ONLY]
                : [Pemeriksaan::JENIS_PENGAJUAN_SPT_DAN_LHP]);

        // Scoped base queries
        $pemeriksaanQuery = Pemeriksaan::forUser($user)->active()
            ->whereIn('jenis_pengajuan', $submissionTypes)
            ->when($opdId, fn ($q) => $q->where('opd_id', $opdId))
            ->when($sumber, fn ($q) => $q->where('sumber_pemeriksaan', $sumber))
            ->when($tahun, fn ($q) => $q->where('tahun', $tahun));

        $temuanQuery = Temuan::forUser($user)->active()
            ->when($opdId, fn ($q) => $q->where('opd_id', $opdId))
            ->when($sumber, fn ($q) => $q->where('sumber_pemeriksaan', $sumber))
            ->when($tahun, fn ($q) => $q->where('tahun', $tahun));

        $tindakLanjutQuery = TindakLanjut::forUser($user)
            ->when($opdId, fn ($q) => $q->where('opd_id', $opdId))
            ->when($sumber || $tahun, function ($q) use ($sumber, $tahun) {
                $q->whereHas('temuan', function ($tq) use ($sumber, $tahun) {
                    $tq->when($sumber, fn ($sq) => $sq->where('sumber_pemeriksaan', $sumber))
                       ->when($tahun, fn ($ty) => $ty->where('tahun', $tahun));
                });
            });

        // Basic counts
        $totalOpd = $opdId ? 1 : Opd::active()->count();
        $totalPemeriksaan = (clone $pemeriksaanQuery)->count();
        $totalTemuan = (clone $temuanQuery)->count();
        $totalNilaiKerugian = (clone $temuanQuery)->where('ada_unsur_kerugian', true)->sum('nilai_kerugian');

        // Verified recovered amounts for both state and regional losses.
        $totalDisetor = (clone $tindakLanjutQuery)->where('status_verifikasi', 'SESUAI')->sum('jumlah_setor');
        $totalSisaKerugian = max(0, $totalNilaiKerugian - $totalDisetor);
        $persenPemulihan = $totalNilaiKerugian > 0 ? round(($totalDisetor / $totalNilaiKerugian) * 100, 1) : 0;

        // 1. Distribution per 4 Sumber (PRD Seksi M)
        $temuanPerSumber = [
            'BPK' => (clone $temuanQuery)->where('sumber_pemeriksaan', 'BPK')->count(),
            'BPKP' => (clone $temuanQuery)->where('sumber_pemeriksaan', 'BPKP')->count(),
            'ITJEN' => (clone $temuanQuery)->where('sumber_pemeriksaan', 'ITJEN')->count(),
            'Inspektorat' => (clone $temuanQuery)->where('sumber_pemeriksaan', 'Inspektorat')->count(),
        ];

        // 2. Distribution per 5 Status Penyelesaian
        $statusPenyelesaianCounts = [
            'Belum Ditindaklanjuti' => (clone $temuanQuery)->where('status_penyelesaian', 'Belum Ditindaklanjuti')->count(),
            'Menunggu Verifikasi' => (clone $temuanQuery)->where('status_penyelesaian', 'Menunggu Verifikasi')->count(),
            'Belum Sesuai' => (clone $temuanQuery)->where('status_penyelesaian', 'Belum Sesuai')->count(),
            'Sesuai' => (clone $temuanQuery)->where('status_penyelesaian', 'Sesuai')->count(),
            'Tidak Dapat Ditindaklanjuti' => (clone $temuanQuery)->where('status_penyelesaian', 'like', '%Tidak Dapat Ditindaklanjuti%')->count(),
        ];

        // 3. Distribution per Status Pengajuan Administratif (PRD Seksi M & K)
        $statusPengajuanCounts = [
            'Draft' => (clone $temuanQuery)->where('status_pengajuan', 'DRAFT')->count(),
            'Menunggu Persetujuan' => (clone $temuanQuery)->where('status_pengajuan', 'MENUNGGU PERSETUJUAN')->count(),
            'Disetujui' => (clone $temuanQuery)->where('status_pengajuan', 'DISETUJUI')->count(),
            'Perlu Revisi' => (clone $temuanQuery)->whereIn('status_pengajuan', ['PERLU REVISI', 'DITOLAK'])->count(),
        ];

        // Top 5 OPD dengan temuan terbanyak & total kerugian (Filtered by sumber & tahun if selected)
        $topOpds = Opd::active()
            ->when($opdId, fn ($q) => $q->where('id', $opdId))
            ->withCount(['temuans' => function ($q) use ($user, $sumber, $tahun) {
                $q->forUser($user)->active()
                    ->when($sumber, fn ($sq) => $sq->where('sumber_pemeriksaan', $sumber))
                    ->when($tahun, fn ($ty) => $ty->where('tahun', $tahun));
            }])
            ->withSum(['temuans' => function ($q) use ($user, $sumber, $tahun) {
                $q->forUser($user)->active()->where('ada_unsur_kerugian', true)
                    ->when($sumber, fn ($sq) => $sq->where('sumber_pemeriksaan', $sumber))
                    ->when($tahun, fn ($ty) => $ty->where('tahun', $tahun));
            }], 'nilai_kerugian')
            ->orderBy('temuans_count', 'desc')
            ->orderBy('temuans_sum_nilai_kerugian', 'desc')
            ->take(5)
            ->get();

        // Action Items based on Role (Fokus pada Verifikasi Tindak Lanjut & Kasda)
        $actionItems = [
            'pendingPemeriksaan' => 0,
            'pendingTemuan' => 0,
            'pendingTindakLanjut' => TindakLanjut::where('status_verifikasi', 'MENUNGGU VERIFIKASI')->count(),
            'draftTemuan' => 0,
            'revisiTemuan' => 0,
            'belumDitindaklanjutiOpd' => (clone $temuanQuery)->where('status_penyelesaian', 'Belum Ditindaklanjuti')->count(),
            'belumSesuaiOpd' => (clone $temuanQuery)->where('status_penyelesaian', 'Belum Sesuai')->count(),
        ];

        // Recent findings
        $recentTemuans = (clone $temuanQuery)->with('opd')->orderBy('created_at', 'desc')->take(5)->get();

        // Master lists for filters
        $opds = Opd::active()->orderBy('nama')->get();
        $sumberList = ['BPK', 'BPKP', 'ITJEN', 'Inspektorat'];
        $years = TahunAnggaran::allYears();

        return view('dashboard', compact(
            'user',
            'role',
            'opdId',
            'sumber',
            'tahun',
            'opds',
            'sumberList',
            'years',
            'totalOpd',
            'totalPemeriksaan',
            'totalTemuan',
            'totalNilaiKerugian',
            'totalDisetor',
            'totalSisaKerugian',
            'persenPemulihan',
            'temuanPerSumber',
            'statusPenyelesaianCounts',
            'statusPengajuanCounts',
            'topOpds',
            'actionItems',
            'recentTemuans'
        ));
    }
}
