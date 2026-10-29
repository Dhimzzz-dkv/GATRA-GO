<?php

namespace App\Http\Controllers;

use App\Models\Opd;
use App\Models\Pemeriksaan;
use App\Models\Temuan;
use App\Models\TahunAnggaran;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller
{
    /**
     * Display report center with executive matrix and detailed finding lists.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $tab = $request->input('tab', 'rekap'); // 'rekap' or 'rincian'
        $jenis = $request->input('jenis', 'kerugian');
        if ($jenis !== 'administrasi') {
            $jenis = 'kerugian';
        }

        $sumber = $request->input('sumber');
        $opdId = $request->input('opd_id');
        $tahun = $request->input('tahun');
        $pemeriksaanId = $request->input('pemeriksaan_id');
        $statusPenyelesaian = $request->input('status_penyelesaian');

        // Scoped Temuan Query
        $temuanQuery = Temuan::with([
            'opd',
            'lhp',
            // pemeriksaan.opd dipakai untuk menampilkan instansi target pada
            // matriks rekapitulasi; tanpa ini terjadi lazy loading.
            'pemeriksaan.opd',
            'pemeriksaan.lhps' => function ($q) use ($user) {
                $q->forUser($user);
            },
            'rekomendasis.tindakLanjuts.creator.pegawai',
            'rekomendasis.creator.pegawai',
            'creator.pegawai',
            'tindakLanjuts' => function ($tq) {
                $tq->where('status_verifikasi', 'SESUAI');
            }
        ])
            ->forUser($user)
            ->active()
            ->when($jenis === 'kerugian', fn ($q) => $q->where('ada_unsur_kerugian', true))
            ->when($jenis === 'administrasi', fn ($q) => $q->where('ada_unsur_administrasi', true))
            ->when($sumber, fn ($q) => $q->where('sumber_pemeriksaan', $sumber))
            ->when($opdId, fn ($q) => $q->where('opd_id', $opdId))
            ->when($jenis === 'kerugian' && $tahun, fn ($q) => $q->where('tahun', $tahun))
            ->when($jenis === 'administrasi' && $pemeriksaanId, fn ($q) => $q->where('pemeriksaan_id', $pemeriksaanId))
            ->when($statusPenyelesaian, fn ($q) => $q->where('status_penyelesaian', $statusPenyelesaian));

        // Aggregate Totals
        $allTemuans = (clone $temuanQuery)->get();
        $totalTemuan = $allTemuans->count();

        // Cards & Total Kerugian (Breakdown Daerah & Negara)
        $totalKerugianDaerah = $allTemuans->where('ada_unsur_kerugian', true)->where('kategori_kerugian', 'Kerugian Daerah')->sum('nilai_kerugian');
        $totalKerugianNegara = $allTemuans->where('ada_unsur_kerugian', true)->where('kategori_kerugian', 'Kerugian Negara')->sum('nilai_kerugian');
        $totalKerugian = $totalKerugianDaerah + $totalKerugianNegara;

        $totalSetoranDaerah = $allTemuans->where('kategori_kerugian', 'Kerugian Daerah')->sum('total_disetor');
        $totalSetoranNegara = $allTemuans->where('kategori_kerugian', 'Kerugian Negara')->sum('total_disetor');
        $totalSetoran = $totalSetoranDaerah + $totalSetoranNegara;

        $totalSisaDaerah = max(0, $totalKerugianDaerah - $totalSetoranDaerah);
        $totalSisaNegara = max(0, $totalKerugianNegara - $totalSetoranNegara);
        $totalSisaKerugian = max(0, $totalKerugian - $totalSetoran);

        $totalPersenSelesai = $totalKerugian > 0 ? round(($totalSetoran / $totalKerugian) * 100, 1) : ($totalTemuan > 0 ? round(($allTemuans->where('status_penyelesaian', 'Sesuai')->count() / $totalTemuan) * 100, 1) : 0);

        // Total Status Penyelesaian
        $totalBelumTl = $allTemuans->where('status_penyelesaian', 'Belum Ditindaklanjuti')->count();
        $totalBelumSesuai = $allTemuans->where('status_penyelesaian', 'Belum Sesuai')->count();
        $totalSesuai = $allTemuans->where('status_penyelesaian', 'Sesuai')->count();
        $totalTidakDapatTl = $allTemuans->filter(fn ($t) => str_contains($t->status_penyelesaian, 'Tidak Dapat Ditindaklanjuti'))->count();

        // Cards Administrasi
        $totalPemeriksaan = $allTemuans->pluck('pemeriksaan_id')->filter()->unique()->count();
        $allRekomendasis = $allTemuans->flatMap->rekomendasis;
        $totalButirRekomendasi = $allRekomendasis->count();
        $completedRekomendasiCount = $allRekomendasis->filter(fn ($r) => in_array($r->status_penyelesaian, ['Sesuai', 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti']))->count();
        $persenRekomendasiSelesai = $totalButirRekomendasi > 0 ? round(($completedRekomendasiCount / $totalButirRekomendasi) * 100, 1) : 0;

        // Tab 1: Executive Matrix per OPD (Kerugian)
        $opdMatrix = Opd::active()
            ->when($opdId, fn ($q) => $q->where('id', $opdId))
            ->orderBy('nama')
            ->get()
            ->map(function ($opd) use ($allTemuans) {
                $opdTemuans = $allTemuans->where('opd_id', $opd->id);
                $count = $opdTemuans->count();

                $kerugianDaerah = $opdTemuans->where('ada_unsur_kerugian', true)->where('kategori_kerugian', 'Kerugian Daerah')->sum('nilai_kerugian');
                $kerugianNegara = $opdTemuans->where('ada_unsur_kerugian', true)->where('kategori_kerugian', 'Kerugian Negara')->sum('nilai_kerugian');
                $disetorDaerah = $opdTemuans->where('kategori_kerugian', 'Kerugian Daerah')->sum('total_disetor');
                $disetorNegara = $opdTemuans->where('kategori_kerugian', 'Kerugian Negara')->sum('total_disetor');

                $sisaDaerah = max(0, $kerugianDaerah - $disetorDaerah);
                $sisaNegara = max(0, $kerugianNegara - $disetorNegara);

                $kerugianTotal = $kerugianDaerah + $kerugianNegara;
                $disetorTotal = $disetorDaerah + $disetorNegara;
                $sisaTotal = max(0, $kerugianTotal - $disetorTotal);

                return [
                    'opd' => $opd,
                    'total_temuan' => $count,
                    'bpk_count' => $opdTemuans->where('sumber_pemeriksaan', 'BPK')->count(),
                    'bpkp_count' => $opdTemuans->where('sumber_pemeriksaan', 'BPKP')->count(),
                    'itjen_count' => $opdTemuans->where('sumber_pemeriksaan', 'ITJEN')->count(),
                    'inspektorat_count' => $opdTemuans->where('sumber_pemeriksaan', 'Inspektorat')->count(),
                    'belum_tl' => $opdTemuans->where('status_penyelesaian', 'Belum Ditindaklanjuti')->count(),
                    'belum_sesuai' => $opdTemuans->where('status_penyelesaian', 'Belum Sesuai')->count(),
                    'sesuai' => $opdTemuans->where('status_penyelesaian', 'Sesuai')->count(),
                    'tidak_dapat_tl' => $opdTemuans->filter(fn ($t) => str_contains($t->status_penyelesaian, 'Tidak Dapat Ditindaklanjuti'))->count(),
                    'nilai_kerugian_daerah' => $kerugianDaerah,
                    'total_disetor_daerah' => $disetorDaerah,
                    'sisa_kerugian_daerah' => $sisaDaerah,
                    'nilai_kerugian_negara' => $kerugianNegara,
                    'total_disetor_negara' => $disetorNegara,
                    'sisa_kerugian_negara' => $sisaNegara,
                    'nilai_kerugian' => $kerugianTotal,
                    'total_disetor' => $disetorTotal,
                    'sisa_kerugian' => $sisaTotal,
                    'persen_selesai' => $kerugianTotal > 0 ? round(($disetorTotal / $kerugianTotal) * 100, 1) : ($count > 0 && $opdTemuans->where('status_penyelesaian', 'Sesuai')->count() === $count ? 100 : 0),
                ];
            })
            ->filter(fn ($row) => $row['total_temuan'] > 0 || !empty($opdId));

        // Tab 1: Executive Matrix per Pemeriksaan (Administrasi)
        $pemeriksaanMatrix = $allTemuans->groupBy('pemeriksaan_id')->map(function ($temuans, $pId) {
            $pemeriksaan = $temuans->first()->pemeriksaan;
            $allReks = $temuans->flatMap->rekomendasis;
            $totalReks = $allReks->count();
            $completedReks = $allReks->filter(fn ($r) => in_array($r->status_penyelesaian, ['Sesuai', 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti']))->count();
            $persen = $totalReks > 0 ? round(($completedReks / $totalReks) * 100, 1) : 0;

            return [
                'pemeriksaan' => $pemeriksaan,
                'temuans' => $temuans,
                'total_temuan' => $temuans->count(),
                'total_rekomendasi' => $totalReks,
                'completed_rekomendasi' => $completedReks,
                'persen_selesai' => $persen,
            ];
        })->values();

        // Tab 2: Detailed Findings List
        $rincianTemuans = $temuanQuery->orderBy('tahun', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();

        $opds = Opd::active()->orderBy('nama')->get();
        $years = TahunAnggaran::allYears();
        $sumberList = ['BPK', 'BPKP', 'ITJEN', 'Inspektorat'];

        $pemeriksaanList = Pemeriksaan::forUser($user)
            ->with(['opd', 'lhps'])
            ->whereHas('temuans', fn ($q) => $q->where('ada_unsur_administrasi', true))
            ->when($sumber, fn ($q) => $q->where('sumber_pemeriksaan', $sumber))
            ->when($opdId, fn ($q) => $q->where(function ($query) use ($opdId) {
                $query->where('opd_id', $opdId)
                    ->orWhereHas('temuans', fn ($temuanQuery) => $temuanQuery->where('opd_id', $opdId));
            }))
            ->orderBy('tahun', 'desc')
            ->orderBy('nomor_spt')
            ->get();

        return view('laporan.index', compact(
            'tab',
            'jenis',
            'sumber',
            'opdId',
            'tahun',
            'pemeriksaanId',
            'statusPenyelesaian',
            'opdMatrix',
            'pemeriksaanMatrix',
            'rincianTemuans',
            'totalTemuan',
            'totalBelumTl',
            'totalBelumSesuai',
            'totalSesuai',
            'totalTidakDapatTl',
            'totalKerugianDaerah',
            'totalKerugianNegara',
            'totalKerugian',
            'totalSetoranDaerah',
            'totalSetoranNegara',
            'totalSetoran',
            'totalSisaDaerah',
            'totalSisaNegara',
            'totalSisaKerugian',
            'totalPersenSelesai',
            'totalPemeriksaan',
            'totalButirRekomendasi',
            'completedRekomendasiCount',
            'persenRekomendasiSelesai',
            'opds',
            'years',
            'sumberList',
            'pemeriksaanList'
        ));
    }

    /**
     * Official printable report view with Gorontalo Provincial Government letterhead.
     */
    public function cetak(Request $request)
    {
        $user = $request->user();
        $tab = $request->input('tab', 'rekap');
        $jenis = $request->input('jenis', 'kerugian');
        if ($jenis !== 'administrasi') {
            $jenis = 'kerugian';
        }

        $sumber = $request->input('sumber');
        $opdId = $request->input('opd_id');
        $tahun = $request->input('tahun');
        $pemeriksaanId = $request->input('pemeriksaan_id');
        $statusPenyelesaian = $request->input('status_penyelesaian');

        $temuanQuery = Temuan::with([
            'opd',
            'lhp',
            'pemeriksaan.opd',
            'pemeriksaan.lhps',
            'rekomendasis.tindakLanjuts.creator.pegawai',
            'rekomendasis.creator.pegawai',
            'creator.pegawai',
            'tindakLanjuts' => function ($tq) {
                $tq->where('status_verifikasi', 'SESUAI');
            }
        ])
            ->forUser($user)
            ->active()
            ->when($jenis === 'kerugian', fn ($q) => $q->where('ada_unsur_kerugian', true))
            ->when($jenis === 'administrasi', fn ($q) => $q->where('ada_unsur_administrasi', true))
            ->when($sumber, fn ($q) => $q->where('sumber_pemeriksaan', $sumber))
            ->when($opdId, fn ($q) => $q->where('opd_id', $opdId))
            ->when($jenis === 'kerugian' && $tahun, fn ($q) => $q->where('tahun', $tahun))
            ->when($jenis === 'administrasi' && $pemeriksaanId, fn ($q) => $q->where('pemeriksaan_id', $pemeriksaanId))
            ->when($statusPenyelesaian, fn ($q) => $q->where('status_penyelesaian', $statusPenyelesaian));

        $allTemuans = $temuanQuery->orderBy('tahun', 'desc')->get();
        $totalTemuan = $allTemuans->count();

        // Summary figures
        $totalKerugianDaerah = $allTemuans->where('ada_unsur_kerugian', true)->where('kategori_kerugian', 'Kerugian Daerah')->sum('nilai_kerugian');
        $totalKerugianNegara = $allTemuans->where('ada_unsur_kerugian', true)->where('kategori_kerugian', 'Kerugian Negara')->sum('nilai_kerugian');
        $totalKerugian = $totalKerugianDaerah + $totalKerugianNegara;

        $totalSetoranDaerah = $allTemuans->where('kategori_kerugian', 'Kerugian Daerah')->sum('total_disetor');
        $totalSetoranNegara = $allTemuans->where('kategori_kerugian', 'Kerugian Negara')->sum('total_disetor');
        $totalSetoran = $totalSetoranDaerah + $totalSetoranNegara;
        
        $totalSisaDaerah = max(0, $totalKerugianDaerah - $totalSetoranDaerah);
        $totalSisaNegara = max(0, $totalKerugianNegara - $totalSetoranNegara);
        $totalSisaKerugian = max(0, $totalKerugian - $totalSetoran);
        
        $totalPersenSelesai = $totalKerugian > 0 ? round(($totalSetoran / $totalKerugian) * 100, 1) : ($totalTemuan > 0 ? round(($allTemuans->where('status_penyelesaian', 'Sesuai')->count() / $totalTemuan) * 100, 1) : 0);

        // Total Status Penyelesaian
        $totalBelumTl = $allTemuans->where('status_penyelesaian', 'Belum Ditindaklanjuti')->count();
        $totalBelumSesuai = $allTemuans->where('status_penyelesaian', 'Belum Sesuai')->count();
        $totalSesuai = $allTemuans->where('status_penyelesaian', 'Sesuai')->count();
        $totalTidakDapatTl = $allTemuans->filter(fn ($t) => str_contains($t->status_penyelesaian, 'Tidak Dapat Ditindaklanjuti'))->count();

        $totalPemeriksaan = $allTemuans->pluck('pemeriksaan_id')->filter()->unique()->count();
        $allReks = $allTemuans->flatMap->rekomendasis;
        $totalButirRekomendasi = $allReks->count();
        $completedRekomendasiCount = $allReks->filter(fn ($r) => in_array($r->status_penyelesaian, ['Sesuai', 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti']))->count();
        $persenRekomendasiSelesai = $totalButirRekomendasi > 0 ? round(($completedRekomendasiCount / $totalButirRekomendasi) * 100, 1) : 0;

        $selectedOpd = $opdId ? Opd::find($opdId) : null;
        $selectedPemeriksaan = $pemeriksaanId ? Pemeriksaan::with('lhps')->find($pemeriksaanId) : null;

        $opdMatrix = Opd::active()
            ->when($opdId, fn ($q) => $q->where('id', $opdId))
            ->orderBy('nama')
            ->get()
            ->map(function ($opd) use ($allTemuans) {
                $opdTemuans = $allTemuans->where('opd_id', $opd->id);
                $count = $opdTemuans->count();
                $kerugianDaerah = $opdTemuans->where('ada_unsur_kerugian', true)->where('kategori_kerugian', 'Kerugian Daerah')->sum('nilai_kerugian');
                $kerugianNegara = $opdTemuans->where('ada_unsur_kerugian', true)->where('kategori_kerugian', 'Kerugian Negara')->sum('nilai_kerugian');
                $disetorDaerah = $opdTemuans->where('kategori_kerugian', 'Kerugian Daerah')->sum('total_disetor');
                $disetorNegara = $opdTemuans->where('kategori_kerugian', 'Kerugian Negara')->sum('total_disetor');

                $kerugianTotal = $kerugianDaerah + $kerugianNegara;
                $disetorTotal = $disetorDaerah + $disetorNegara;

                return [
                    'opd' => $opd,
                    'total_temuan' => $count,
                    'bpk_count' => $opdTemuans->where('sumber_pemeriksaan', 'BPK')->count(),
                    'bpkp_count' => $opdTemuans->where('sumber_pemeriksaan', 'BPKP')->count(),
                    'itjen_count' => $opdTemuans->where('sumber_pemeriksaan', 'ITJEN')->count(),
                    'inspektorat_count' => $opdTemuans->where('sumber_pemeriksaan', 'Inspektorat')->count(),
                    'belum_tl' => $opdTemuans->where('status_penyelesaian', 'Belum Ditindaklanjuti')->count(),
                    'belum_sesuai' => $opdTemuans->where('status_penyelesaian', 'Belum Sesuai')->count(),
                    'sesuai' => $opdTemuans->where('status_penyelesaian', 'Sesuai')->count(),
                    'tidak_dapat_tl' => $opdTemuans->filter(fn ($t) => str_contains($t->status_penyelesaian, 'Tidak Dapat Ditindaklanjuti'))->count(),
                    'nilai_kerugian_daerah' => $kerugianDaerah,
                    'nilai_kerugian_negara' => $kerugianNegara,
                    'total_disetor_daerah' => $disetorDaerah,
                    'total_disetor_negara' => $disetorNegara,
                    'sisa_kerugian_daerah' => max(0, $kerugianDaerah - $disetorDaerah),
                    'sisa_kerugian_negara' => max(0, $kerugianNegara - $disetorNegara),
                    'nilai_kerugian' => $kerugianTotal,
                    'total_disetor' => $disetorTotal,
                    'sisa_kerugian' => max(0, $kerugianTotal - $disetorTotal),
                    'persen_selesai' => $kerugianTotal > 0 ? round(($disetorTotal / $kerugianTotal) * 100, 1) : ($count > 0 && $opdTemuans->where('status_penyelesaian', 'Sesuai')->count() === $count ? 100 : 0),
                ];
            })
            ->filter(fn ($row) => $row['total_temuan'] > 0 || !empty($opdId));

        $pemeriksaanMatrix = $allTemuans->groupBy('pemeriksaan_id')->map(function ($temuans, $pId) {
            $pemeriksaan = $temuans->first()->pemeriksaan;
            $reks = $temuans->flatMap->rekomendasis;
            $tot = $reks->count();
            $comp = $reks->filter(fn ($r) => in_array($r->status_penyelesaian, ['Sesuai', 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti']))->count();
            $prc = $tot > 0 ? round(($comp / $tot) * 100, 1) : 0;

            return [
                'pemeriksaan' => $pemeriksaan,
                'temuans' => $temuans,
                'total_temuan' => $temuans->count(),
                'total_rekomendasi' => $tot,
                'completed_rekomendasi' => $comp,
                'persen_selesai' => $prc,
            ];
        })->values();

        return view('laporan.cetak', compact(
            'tab',
            'jenis',
            'sumber',
            'selectedOpd',
            'selectedPemeriksaan',
            'tahun',
            'pemeriksaanId',
            'statusPenyelesaian',
            'allTemuans',
            'opdMatrix',
            'pemeriksaanMatrix',
            'totalTemuan',
            'totalBelumTl',
            'totalBelumSesuai',
            'totalSesuai',
            'totalTidakDapatTl',
            'totalKerugianDaerah',
            'totalKerugianNegara',
            'totalKerugian',
            'totalSetoranDaerah',
            'totalSetoranNegara',
            'totalSetoran',
            'totalSisaDaerah',
            'totalSisaNegara',
            'totalSisaKerugian',
            'totalPersenSelesai',
            'totalPemeriksaan',
            'totalButirRekomendasi',
            'completedRekomendasiCount',
            'persenRekomendasiSelesai'
        ));
    }

    /**
     * Export report data as a genuine, professionally styled native Excel spreadsheet (.xlsx).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $user = $request->user();
        $tab = $request->input('tab', 'rekap');
        $jenis = $request->input('jenis', 'kerugian');
        if ($jenis !== 'administrasi') {
            $jenis = 'kerugian';
        }

        $sumber = $request->input('sumber');
        $opdId = $request->input('opd_id');
        $tahun = $request->input('tahun');
        $pemeriksaanId = $request->input('pemeriksaan_id');
        $statusPenyelesaian = $request->input('status_penyelesaian');

        $temuanQuery = Temuan::with([
            'opd',
            'lhp',
            'pemeriksaan.opd',
            'pemeriksaan.lhps',
            'rekomendasis.tindakLanjuts.creator.pegawai',
            'rekomendasis.creator.pegawai',
            'creator.pegawai',
            'tindakLanjuts' => function ($tq) {
                $tq->where('status_verifikasi', 'SESUAI');
            }
        ])
            ->forUser($user)
            ->active()
            ->when($jenis === 'kerugian', fn ($q) => $q->where('ada_unsur_kerugian', true))
            ->when($jenis === 'administrasi', fn ($q) => $q->where('ada_unsur_administrasi', true))
            ->when($sumber, fn ($q) => $q->where('sumber_pemeriksaan', $sumber))
            ->when($opdId, fn ($q) => $q->where('opd_id', $opdId))
            ->when($jenis === 'kerugian' && $tahun, fn ($q) => $q->where('tahun', $tahun))
            ->when($jenis === 'administrasi' && $pemeriksaanId, fn ($q) => $q->where('pemeriksaan_id', $pemeriksaanId))
            ->when($statusPenyelesaian, fn ($q) => $q->where('status_penyelesaian', $statusPenyelesaian));

        $allTemuans = $temuanQuery->orderBy('tahun', 'desc')->get();
        $totalTemuan = $allTemuans->count();
        $totalKerugian = $allTemuans->where('ada_unsur_kerugian', true)->sum('nilai_kerugian');
        $totalSetoran = $allTemuans->sum('total_disetor');
        $totalSisaKerugian = max(0, $totalKerugian - $totalSetoran);

        $selectedOpd = $opdId ? Opd::find($opdId) : null;
        $selectedPemeriksaan = $pemeriksaanId ? Pemeriksaan::with('lhps')->find($pemeriksaanId) : null;

        // Log audit trail
        \App\Services\AuditLogger::log(
            'EXPORT',
            'LAPORAN',
            "Mengekspor Laporan ({$jenis}, {$tab}) ke format Excel (.xlsx). Filter: Sumber={$sumber}, OPD=" . ($selectedOpd?->nama ?? 'Semua') . ", Tahun={$tahun}."
        );

        $filename = 'Laporan_GATRA-GO_' . ($jenis === 'administrasi' ? 'Administrasi_' : 'Kerugian_') . ($tab === 'rekap' ? 'Matriks_' : 'Rincian_') . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($tab, $jenis, $sumber, $selectedOpd, $selectedPemeriksaan, $tahun, $statusPenyelesaian, $allTemuans, $opdId, $totalTemuan, $totalKerugian, $totalSetoran, $totalSisaKerugian) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle($tab === 'rekap' ? ($jenis === 'administrasi' ? 'Matriks Pemeriksaan' : 'Matriks Rekapitulasi OPD') : 'Rincian Temuan');
            $sheet->setShowGridLines(true);

            // Print Page Setup (Landscape, A4, Fit All Columns to 1 Page Wide, Center Horizontally)
            $sheet->getPageSetup()
                ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
                ->setFitToPage(true)
                ->setFitToWidth(1)
                ->setFitToHeight(0)
                ->setHorizontalCentered(true);

            $sheet->getPageMargins()
                ->setTop(0.4)
                ->setRight(0.3)
                ->setBottom(0.4)
                ->setLeft(0.3);

            if ($jenis === 'kerugian') {
                if ($tab === 'rekap') {
                    $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(6, 7);
                } else {
                    $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(6, 6);
                }
            } else {
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(6, 6);
            }

            // Filter metadata string
            $filterInfo = [];
            $filterInfo[] = "Jenis: " . ($jenis === 'administrasi' ? 'Administrasi' : 'Kerugian Daerah');
            if ($sumber) $filterInfo[] = "Sumber: {$sumber}";
            if ($selectedOpd) $filterInfo[] = "OPD: {$selectedOpd->nama}";
            if ($selectedPemeriksaan) {
                $submissionNumber = $selectedPemeriksaan->is_lhp_only_submission
                    ? $selectedPemeriksaan->lhps->pluck('nomor_lhp')->filter()->join(', ')
                    : $selectedPemeriksaan->nomor_spt;
                $filterInfo[] = ($selectedPemeriksaan->is_lhp_only_submission ? 'LHP: ' : 'SPT: ') . ($submissionNumber ?: 'Pengajuan LHP');
            }
            if ($tahun) $filterInfo[] = "Tahun: {$tahun}";
            if ($statusPenyelesaian) $filterInfo[] = "Status: {$statusPenyelesaian}";
            $filterText = !empty($filterInfo) ? implode(' | ', $filterInfo) : 'Semua Data';

            // Kop Laporan Styles
            $sheet->setCellValue('A1', 'PEMERINTAH PROVINSI GORONTALO');
            $sheet->setCellValue('A2', 'INSPEKTORAT DAERAH');
            $sheet->setCellValue('A3', 'Jln. Jenderal Sudirman No. 79 Telp. (0435) 852486 Fax (0435) 852485');
            $sheet->setCellValue('A4', 'Website : http://inspektorat.gorontaloprov.go.id   email : itda@gorontaloprov.go.id');
            $reportTitle = $jenis === 'administrasi'
                ? ($tab === 'rekap' ? 'MATRIKS REKAPITULASI PENGAWASAN ADMINISTRASI' : 'LAPORAN RINCIAN TEMUAN ADMINISTRASI')
                : ($tab === 'rekap' ? 'MATRIKS REKAPITULASI PENGAWASAN OPD' : 'LAPORAN RINCIAN TEMUAN HASIL PEMERIKSAAN');
            $exportMetadata = 'Filter: ' . $filterText . ' | Diekspor pada: ' . date('d/m/Y H:i:s') . ' oleh: ' . (auth()->user()->name ?? 'User');

            $lastColumn = $jenis === 'kerugian'
                ? ($tab === 'rekap' ? 'N' : 'M')
                : ($tab === 'rekap' ? 'K' : 'H');

            foreach ([1, 2, 3, 4] as $headerRow) {
                $sheet->mergeCells("A{$headerRow}:{$lastColumn}{$headerRow}");
            }

            $sheet->getStyle("A1:{$lastColumn}4")->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

            $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray([
                'borders' => [
                    'bottom' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                        'color' => ['argb' => 'FF1F2937'],
                    ],
                ],
            ]);

            $sheet->getRowDimension(1)->setRowHeight(22);
            $sheet->getRowDimension(2)->setRowHeight(26);
            $sheet->getRowDimension(3)->setRowHeight(16);
            $sheet->getRowDimension(4)->setRowHeight(16);

            $kopFont = ['name' => 'Times New Roman', 'color' => ['argb' => 'FF000000']];
            $sheet->getStyle('A1')->getFont()->applyFromArray($kopFont + ['bold' => true, 'size' => 12]);
            $sheet->getStyle('A2')->getFont()->applyFromArray($kopFont + ['bold' => true, 'size' => 14]);
            $sheet->getStyle('A3:A4')->getFont()->applyFromArray($kopFont + ['size' => 8]);

            $borderThin = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FFCBD5E1'],
                    ],
                ],
            ];

            $headerPrimaryStyle = [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A8A']],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FF475569']]],
            ];

            $headerSubStyle = [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2563EB']],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FF475569']]],
            ];

            if ($jenis === 'kerugian') {
                if ($tab === 'rekap') {
                    // Table Headers (Rows 6 & 7)
                    $sheet->mergeCells('A6:A7')->setCellValue('A6', 'No');
                    $sheet->mergeCells('B6:B7')->setCellValue('B6', 'Nama OPD / Instansi');
                    $sheet->mergeCells('C6:F6')->setCellValue('C6', 'Sumber Pemeriksaan');
                    $sheet->setCellValue('C7', 'BPK');
                    $sheet->setCellValue('D7', 'BPKP');
                    $sheet->setCellValue('E7', 'ITJEN');
                    $sheet->setCellValue('F7', 'INSP');
                    $sheet->mergeCells('G6:G7')->setCellValue('G6', 'Total Temuan');
                    $sheet->mergeCells('H6:J6')->setCellValue('H6', 'Status Tindak Lanjut');
                    $sheet->setCellValue('H7', 'Belum TL');
                    $sheet->setCellValue('I7', 'Belum Sesuai');
                    $sheet->setCellValue('J7', 'Sesuai');
                    $sheet->mergeCells('K6:K7')->setCellValue('K6', 'Nilai Kerugian (Rp)');
                    $sheet->mergeCells('L6:L7')->setCellValue('L6', 'Telah Disetor (Rp)');
                    $sheet->mergeCells('M6:M7')->setCellValue('M6', 'Sisa Kerugian (Rp)');
                    $sheet->mergeCells('N6:N7')->setCellValue('N6', '% Selesai');

                    $sheet->getStyle('A6:N6')->applyFromArray($headerPrimaryStyle);
                    $sheet->getStyle('A7:N7')->applyFromArray($headerPrimaryStyle);
                    $sheet->getStyle('C7:F7')->applyFromArray($headerSubStyle);
                    $sheet->getStyle('H7:J7')->applyFromArray($headerSubStyle);
                    $sheet->getRowDimension(6)->setRowHeight(24);
                    $sheet->getRowDimension(7)->setRowHeight(20);

                    // Freeze Header Panes
                    $sheet->freezePane('A8');

                    $opdMatrix = Opd::active()
                        ->when($opdId, fn ($q) => $q->where('id', $opdId))
                        ->orderBy('nama')
                        ->get()
                        ->map(function ($opd) use ($allTemuans) {
                            $opdTemuans = $allTemuans->where('opd_id', $opd->id);
                            $count = $opdTemuans->count();
                            $kerugian = $opdTemuans->where('ada_unsur_kerugian', true)->sum('nilai_kerugian');
                            $disetor = $opdTemuans->sum('total_disetor');

                            return [
                                'opd' => $opd,
                                'total_temuan' => $count,
                                'bpk_count' => $opdTemuans->where('sumber_pemeriksaan', 'BPK')->count(),
                                'bpkp_count' => $opdTemuans->where('sumber_pemeriksaan', 'BPKP')->count(),
                                'itjen_count' => $opdTemuans->where('sumber_pemeriksaan', 'ITJEN')->count(),
                                'inspektorat_count' => $opdTemuans->where('sumber_pemeriksaan', 'Inspektorat')->count(),
                                'belum_tl' => $opdTemuans->where('status_penyelesaian', 'Belum Ditindaklanjuti')->count(),
                                'belum_sesuai' => $opdTemuans->where('status_penyelesaian', 'Belum Sesuai')->count(),
                                'sesuai' => $opdTemuans->where('status_penyelesaian', 'Sesuai')->count(),
                                'tidak_dapat_tl' => $opdTemuans->filter(fn ($t) => str_contains($t->status_penyelesaian, 'Tidak Dapat Ditindaklanjuti'))->count(),
                                'nilai_kerugian' => $kerugian,
                                'total_disetor' => $disetor,
                                'sisa_kerugian' => max(0, $kerugian - $disetor),
                                'persen_selesai' => $kerugian > 0 ? round(($disetor / $kerugian) * 100, 1) : ($count > 0 && $opdTemuans->where('status_penyelesaian', 'Sesuai')->count() === $count ? 100 : 0),
                            ];
                        })
                        ->filter(fn ($row) => $row['total_temuan'] > 0 || !empty($opdId));

                    $currentRow = 8;
                    foreach ($opdMatrix as $idx => $row) {
                        $sheet->setCellValue("A{$currentRow}", $idx + 1);
                        $sheet->setCellValue("B{$currentRow}", $row['opd']->nama . ' (' . $row['opd']->kode . ')');
                        $sheet->setCellValue("C{$currentRow}", $row['bpk_count']);
                        $sheet->setCellValue("D{$currentRow}", $row['bpkp_count']);
                        $sheet->setCellValue("E{$currentRow}", $row['itjen_count']);
                        $sheet->setCellValue("F{$currentRow}", $row['inspektorat_count']);
                        $sheet->setCellValue("G{$currentRow}", $row['total_temuan']);
                        $sheet->setCellValue("H{$currentRow}", $row['belum_tl']);
                        $sheet->setCellValue("I{$currentRow}", $row['belum_sesuai']);
                        $sheet->setCellValue("J{$currentRow}", $row['sesuai']);
                        $sheet->setCellValue("K{$currentRow}", $row['nilai_kerugian']);
                        $sheet->setCellValue("L{$currentRow}", $row['total_disetor']);
                        $sheet->setCellValue("M{$currentRow}", $row['sisa_kerugian']);
                        $sheet->setCellValue("N{$currentRow}", $row['persen_selesai'] / 100);

                        $sheet->getStyle("A{$currentRow}:N{$currentRow}")->applyFromArray($borderThin);
                        $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("C{$currentRow}:J{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("G{$currentRow}")->getFont()->setBold(true);
                        $sheet->getStyle("K{$currentRow}:M{$currentRow}")->getNumberFormat()->setFormatCode('#,##0');
                        $sheet->getStyle("N{$currentRow}")->getNumberFormat()->setFormatCode('0.0%');
                        $sheet->getStyle("N{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                        if ($idx % 2 === 1) {
                            $sheet->getStyle("A{$currentRow}:N{$currentRow}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
                        }

                        // Highlight badges
                        if ($row['belum_tl'] > 0) {
                            $sheet->getStyle("H{$currentRow}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEE2E2');
                            $sheet->getStyle("H{$currentRow}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF991B1B'));
                        }
                        if ($row['belum_sesuai'] > 0) {
                            $sheet->getStyle("I{$currentRow}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEF3C7');
                            $sheet->getStyle("I{$currentRow}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF92400E'));
                        }
                        if ($row['sesuai'] > 0) {
                            $sheet->getStyle("J{$currentRow}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFDCFCE7');
                            $sheet->getStyle("J{$currentRow}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF166534'));
                        }

                        $currentRow++;
                    }

                    // Total Summary Row
                    $startDataRow = 8;
                    $endDataRow = max(8, $currentRow - 1);

                    $sheet->mergeCells("A{$currentRow}:B{$currentRow}")->setCellValue("A{$currentRow}", 'TOTAL KESELURUHAN');
                    $sheet->setCellValue("C{$currentRow}", "=SUM(C{$startDataRow}:C{$endDataRow})");
                    $sheet->setCellValue("D{$currentRow}", "=SUM(D{$startDataRow}:D{$endDataRow})");
                    $sheet->setCellValue("E{$currentRow}", "=SUM(E{$startDataRow}:E{$endDataRow})");
                    $sheet->setCellValue("F{$currentRow}", "=SUM(F{$startDataRow}:F{$endDataRow})");
                    $sheet->setCellValue("G{$currentRow}", "=SUM(G{$startDataRow}:G{$endDataRow})");
                    $sheet->setCellValue("H{$currentRow}", "=SUM(H{$startDataRow}:H{$endDataRow})");
                    $sheet->setCellValue("I{$currentRow}", "=SUM(I{$startDataRow}:I{$endDataRow})");
                    $sheet->setCellValue("J{$currentRow}", "=SUM(J{$startDataRow}:J{$endDataRow})");
                    $sheet->setCellValue("K{$currentRow}", "=SUM(K{$startDataRow}:K{$endDataRow})");
                    $sheet->setCellValue("L{$currentRow}", "=SUM(L{$startDataRow}:L{$endDataRow})");
                    $sheet->setCellValue("M{$currentRow}", "=SUM(M{$startDataRow}:M{$endDataRow})");
                    $sheet->setCellValue("N{$currentRow}", "=IF(K{$currentRow}>0, L{$currentRow}/K{$currentRow}, IF(G{$currentRow}>0, J{$currentRow}/G{$currentRow}, 0))");

                    $totalRowStyle = [
                        'font' => ['bold' => true, 'size' => 10],
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF1F5F9']],
                        'borders' => [
                            'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE, 'color' => ['argb' => 'FF1E3A8A']],
                            'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE, 'color' => ['argb' => 'FF1E3A8A']],
                            'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']],
                        ],
                    ];
                    $sheet->getStyle("A{$currentRow}:N{$currentRow}")->applyFromArray($totalRowStyle);
                    $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$currentRow}:J{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("K{$currentRow}:M{$currentRow}")->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle("N{$currentRow}")->getNumberFormat()->setFormatCode('0.0%');
                    $sheet->getRowDimension($currentRow)->setRowHeight(22);

                    $sheet->getColumnDimension('A')->setWidth(5);
                    $sheet->getColumnDimension('B')->setWidth(34);
                    $sheet->getColumnDimension('C')->setWidth(8);
                    $sheet->getColumnDimension('D')->setWidth(8);
                    $sheet->getColumnDimension('E')->setWidth(8);
                    $sheet->getColumnDimension('F')->setWidth(8);
                    $sheet->getColumnDimension('G')->setWidth(11);
                    $sheet->getColumnDimension('H')->setWidth(11);
                    $sheet->getColumnDimension('I')->setWidth(12);
                    $sheet->getColumnDimension('J')->setWidth(10);
                    $sheet->getColumnDimension('K')->setWidth(18);
                    $sheet->getColumnDimension('L')->setWidth(18);
                    $sheet->getColumnDimension('M')->setWidth(18);
                    $sheet->getColumnDimension('N')->setWidth(11);
                    $sheet->getStyle('B8:B' . $currentRow)->getAlignment()->setWrapText(true);

                } else {
                    // Tab Rincian Temuan Kerugian
                    $headers = [
                        'A6' => 'No',
                        'B6' => 'Sumber',
                        'C6' => 'Tahun',
                        'D6' => 'Nama OPD Target',
                        'E6' => 'Judul Temuan',
                        'F6' => 'Uraian Temuan',
                        'G6' => 'Rekomendasi',
                        'H6' => 'Unsur Adm',
                        'I6' => 'Nilai Kerugian (Rp)',
                        'J6' => 'Total Disetor (Rp)',
                        'K6' => 'Sisa Kerugian (Rp)',
                        'L6' => 'Status Pengajuan',
                        'M6' => 'Status Penyelesaian',
                    ];

                    foreach ($headers as $cell => $val) {
                        $sheet->setCellValue($cell, $val);
                    }

                    $sheet->getStyle('A6:M6')->applyFromArray($headerPrimaryStyle);
                    $sheet->getRowDimension(6)->setRowHeight(26);
                    $sheet->freezePane('A7');

                    $currentRow = 7;
                    foreach ($allTemuans as $idx => $t) {
                        $sheet->setCellValue("A{$currentRow}", $idx + 1);
                        $sheet->setCellValue("B{$currentRow}", $t->sumber_pemeriksaan);
                        $sheet->setCellValue("C{$currentRow}", $t->tahun);
                        $sheet->setCellValue("D{$currentRow}", $t->opd->nama ?? '-');
                        $sheet->setCellValue("E{$currentRow}", $t->judul_temuan);
                        $sheet->setCellValue("F{$currentRow}", strip_tags($t->uraian_temuan));
                        $sheet->setCellValue("G{$currentRow}", strip_tags($t->rekomendasi));
                        $sheet->setCellValue("H{$currentRow}", $t->ada_unsur_administrasi ? 'Ya' : 'Tidak');
                        $sheet->setCellValue("I{$currentRow}", $t->nilai_kerugian);
                        $sheet->setCellValue("J{$currentRow}", $t->total_disetor);
                        $sheet->setCellValue("K{$currentRow}", $t->sisa_kerugian);
                        $sheet->setCellValue("L{$currentRow}", $t->status_pengajuan);
                        $sheet->setCellValue("M{$currentRow}", $t->status_penyelesaian);

                        $sheet->getStyle("A{$currentRow}:M{$currentRow}")->applyFromArray($borderThin);
                        $sheet->getStyle("A{$currentRow}:C{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("H{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("L{$currentRow}:M{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("I{$currentRow}:K{$currentRow}")->getNumberFormat()->setFormatCode('#,##0');

                        if ($idx % 2 === 1) {
                            $sheet->getStyle("A{$currentRow}:M{$currentRow}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
                        }

                        // Status styling
                        if ($t->status_penyelesaian === 'Sesuai') {
                            $sheet->getStyle("M{$currentRow}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFDCFCE7');
                            $sheet->getStyle("M{$currentRow}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF166534'));
                        } elseif ($t->status_penyelesaian === 'Belum Ditindaklanjuti') {
                            $sheet->getStyle("M{$currentRow}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEE2E2');
                            $sheet->getStyle("M{$currentRow}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF991B1B'));
                        } elseif ($t->status_penyelesaian === 'Belum Sesuai') {
                            $sheet->getStyle("M{$currentRow}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEF3C7');
                            $sheet->getStyle("M{$currentRow}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF92400E'));
                        }

                        $currentRow++;
                    }

                    // Summary Row for Rincian
                    $startDataRow = 7;
                    $endDataRow = max(7, $currentRow - 1);

                    $sheet->mergeCells("A{$currentRow}:H{$currentRow}")->setCellValue("A{$currentRow}", 'TOTAL KESELURUHAN (' . $totalTemuan . ' TEMUAN)');
                    $sheet->setCellValue("I{$currentRow}", "=SUM(I{$startDataRow}:I{$endDataRow})");
                    $sheet->setCellValue("J{$currentRow}", "=SUM(J{$startDataRow}:J{$endDataRow})");
                    $sheet->setCellValue("K{$currentRow}", "=SUM(K{$startDataRow}:K{$endDataRow})");
                    $sheet->mergeCells("L{$currentRow}:M{$currentRow}");

                    $totalRowStyle = [
                        'font' => ['bold' => true, 'size' => 10],
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF1F5F9']],
                        'borders' => [
                            'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE, 'color' => ['argb' => 'FF1E3A8A']],
                            'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE, 'color' => ['argb' => 'FF1E3A8A']],
                            'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']],
                        ],
                    ];
                    $sheet->getStyle("A{$currentRow}:M{$currentRow}")->applyFromArray($totalRowStyle);
                    $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("I{$currentRow}:K{$currentRow}")->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getRowDimension($currentRow)->setRowHeight(22);

                    $sheet->getColumnDimension('A')->setWidth(5);
                    $sheet->getColumnDimension('B')->setWidth(10);
                    $sheet->getColumnDimension('C')->setWidth(8);
                    $sheet->getColumnDimension('D')->setWidth(26);
                    $sheet->getColumnDimension('E')->setWidth(26);
                    $sheet->getColumnDimension('F')->setWidth(32);
                    $sheet->getColumnDimension('G')->setWidth(32);
                    $sheet->getColumnDimension('H')->setWidth(10);
                    $sheet->getColumnDimension('I')->setWidth(16);
                    $sheet->getColumnDimension('J')->setWidth(16);
                    $sheet->getColumnDimension('K')->setWidth(16);
                    $sheet->getColumnDimension('L')->setWidth(14);
                    $sheet->getColumnDimension('M')->setWidth(16);
                    $sheet->getStyle('D7:G' . $currentRow)->getAlignment()->setWrapText(true);
                }
            } else {
                // $jenis === 'administrasi'
                if ($tab === 'rekap') {
                    // Tab Matriks Rekapitulasi Administrasi: 4 Kolom Status Penyelesaian
                    $sheet->mergeCells('A6:A7')->setCellValue('A6', 'No');
                    $sheet->mergeCells('B6:B7')->setCellValue('B6', 'Nama OPD');
                    $sheet->mergeCells('C6:C7')->setCellValue('C6', 'Sumber Pemeriksa');
                    $sheet->mergeCells('D6:D7')->setCellValue('D6', 'Temuan');
                    $sheet->mergeCells('E6:E7')->setCellValue('E6', 'Penyebab');
                    $sheet->mergeCells('F6:F7')->setCellValue('F6', 'Rekomendasi');
                    $sheet->mergeCells('G6:G7')->setCellValue('G6', 'Jumlah Rekomendasi');
                    $sheet->mergeCells('H6:K6')->setCellValue('H6', 'Status Penyelesaian');

                    $sheet->setCellValue('H7', 'Belum Ditindaklanjuti');
                    $sheet->setCellValue('I7', 'Belum Sesuai');
                    $sheet->setCellValue('J7', 'Sesuai');
                    $sheet->setCellValue('K7', 'Tidak Dapat Ditindaklanjuti');

                    $sheet->getStyle('A6:K6')->applyFromArray($headerPrimaryStyle);
                    $sheet->getStyle('A6:K7')->applyFromArray([
                        'alignment' => [
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'wrapText' => true,
                        ],
                    ]);
                    $sheet->getStyle('H7:K7')->applyFromArray($headerSubStyle);
                    $sheet->getRowDimension(6)->setRowHeight(24);
                    $sheet->getRowDimension(7)->setRowHeight(20);
                    $sheet->freezePane('A8');

                    $currentRow = 8;
                    $totalButirAll = 0;
                    $totTl = 0;
                    $totSesuai = 0;
                    $totOk = 0;
                    $totTptd = 0;

                    foreach ($allTemuans as $idx => $t) {
                        $rekCount = $t->rekomendasis->count();
                        $totalButirAll += $rekCount;

                        if ($t->rekomendasis->isNotEmpty()) {
                            $rekLines = [];
                            foreach ($t->rekomendasis as $rIdx => $r) {
                                $rekLines[] = ($rIdx + 1) . '. ' . $r->uraian_rekomendasi;
                            }
                            $rekText = implode("\n", $rekLines);

                            $c_tl = $t->rekomendasis->where('status_penyelesaian', 'Belum Ditindaklanjuti')->count();
                            $c_sesuai = $t->rekomendasis->where('status_penyelesaian', 'Belum Sesuai')->count();
                            $c_ok = $t->rekomendasis->where('status_penyelesaian', 'Sesuai')->count();
                            $c_tptd = $t->rekomendasis->filter(fn ($r) => in_array($r->status_penyelesaian, ['Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti']))->count();
                        } else {
                            $rekText = $t->rekomendasi ?: '-';
                            $st = $t->status_penyelesaian;
                            $c_tl = ($st === 'Belum Ditindaklanjuti') ? 1 : 0;
                            $c_sesuai = ($st === 'Belum Sesuai') ? 1 : 0;
                            $c_ok = ($st === 'Sesuai') ? 1 : 0;
                            $c_tptd = in_array($st, ['Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti']) ? 1 : 0;
                        }

                        $totTl += $c_tl;
                        $totSesuai += $c_sesuai;
                        $totOk += $c_ok;
                        $totTptd += $c_tptd;

                        $sheet->setCellValue("A{$currentRow}", $idx + 1);
                        $sheet->setCellValue("B{$currentRow}", $t->opd->nama ?? '-');
                        $sheet->setCellValue("C{$currentRow}", $t->sumber_pemeriksaan);
                        $sheet->setCellValue("D{$currentRow}", $t->judul_temuan);
                        $sheet->setCellValue("E{$currentRow}", $t->penyebab ?: '-');
                        $sheet->setCellValue("F{$currentRow}", $rekText);
                        $sheet->setCellValue("G{$currentRow}", $rekCount ?: '-');
                        $sheet->setCellValue("H{$currentRow}", $c_tl ?: '-');
                        $sheet->setCellValue("I{$currentRow}", $c_sesuai ?: '-');
                        $sheet->setCellValue("J{$currentRow}", $c_ok ?: '-');
                        $sheet->setCellValue("K{$currentRow}", $c_tptd ?: '-');

                        $sheet->getStyle("A{$currentRow}:K{$currentRow}")->applyFromArray($borderThin);
                        $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("G{$currentRow}:K{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                        if ($idx % 2 === 1) {
                            $sheet->getStyle("A{$currentRow}:K{$currentRow}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
                        }

                        if ($c_tl > 0) {
                            $sheet->getStyle("H{$currentRow}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF991B1B'));
                        }
                        if ($c_sesuai > 0) {
                            $sheet->getStyle("I{$currentRow}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF92400E'));
                        }
                        if ($c_ok > 0) {
                            $sheet->getStyle("J{$currentRow}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF166534'));
                        }

                        $currentRow++;
                    }

                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}")->setCellValue("A{$currentRow}", 'TOTAL KESELURUHAN (' . $totalTemuan . ' TEMUAN ADMINISTRASI)');
                    $sheet->setCellValue("G{$currentRow}", $totalButirAll ?: '-');
                    $sheet->setCellValue("H{$currentRow}", $totTl ?: '-');
                    $sheet->setCellValue("I{$currentRow}", $totSesuai ?: '-');
                    $sheet->setCellValue("J{$currentRow}", $totOk ?: '-');
                    $sheet->setCellValue("K{$currentRow}", $totTptd ?: '-');

                    $totalRowStyle = [
                        'font' => ['bold' => true, 'size' => 10],
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF1F5F9']],
                        'borders' => [
                            'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE, 'color' => ['argb' => 'FF1E3A8A']],
                            'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE, 'color' => ['argb' => 'FF1E3A8A']],
                            'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']],
                        ],
                    ];
                    $sheet->getStyle("A{$currentRow}:K{$currentRow}")->applyFromArray($totalRowStyle);
                    $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("G{$currentRow}:K{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getRowDimension($currentRow)->setRowHeight(22);

                    $sheet->getColumnDimension('A')->setWidth(5);
                    $sheet->getColumnDimension('B')->setWidth(26);
                    $sheet->getColumnDimension('C')->setWidth(14);
                    $sheet->getColumnDimension('D')->setWidth(30);
                    $sheet->getColumnDimension('E')->setWidth(26);
                    $sheet->getColumnDimension('F')->setWidth(38);
                    $sheet->getColumnDimension('G')->setWidth(14);
                    $sheet->getColumnDimension('H')->setWidth(16);
                    $sheet->getColumnDimension('I')->setWidth(14);
                    $sheet->getColumnDimension('J')->setWidth(12);
                    $sheet->getColumnDimension('K')->setWidth(20);
                    $sheet->getStyle('B8:F' . $currentRow)->getAlignment()->setWrapText(true);

                } else {
                    // Tab Laporan Rincian Administrasi: Dikelompokkan per Pemeriksaan
                    $headers = [
                        'A6' => 'No',
                        'B6' => 'Nomor SPT / LHP',
                        'C6' => 'Nama OPD Target',
                        'D6' => 'Sumber',
                        'E6' => 'Temuan Administrasi',
                        'F6' => 'Daftar Butir Rekomendasi',
                        'G6' => 'Jumlah Butir',
                        'H6' => 'Status Penyelesaian',
                    ];

                    foreach ($headers as $cell => $val) {
                        $sheet->setCellValue($cell, $val);
                    }

                    $sheet->getStyle('A6:H6')->applyFromArray($headerPrimaryStyle);
                    $sheet->getRowDimension(6)->setRowHeight(26);
                    $sheet->freezePane('A7');

                    $currentRow = 7;
                    $totalButirAll = 0;
                    foreach ($allTemuans as $idx => $t) {
                        $rekCount = $t->rekomendasis->count();
                        $totalButirAll += $rekCount;

                        $rekLines = [];
                        if ($t->rekomendasis->isNotEmpty()) {
                            foreach ($t->rekomendasis as $rIdx => $r) {
                                $rekLines[] = ($rIdx + 1) . '. ' . $r->uraian_rekomendasi . ' [' . $r->status_penyelesaian . ']';
                            }
                        } else {
                            $rekLines[] = $t->rekomendasi ?: '-';
                        }
                        $rekFullText = implode("\n", $rekLines);

                        $sheet->setCellValue("A{$currentRow}", $idx + 1);
                        $submissionNumber = $t->pemeriksaan?->is_lhp_only_submission
                            ? ('LHP: ' . ($t->lhp?->nomor_lhp ?? '-'))
                            : ('SPT: ' . ($t->pemeriksaan?->nomor_spt ?? '-'));
                        $sheet->setCellValue("B{$currentRow}", $submissionNumber . ($t->pemeriksaan?->tahun ? ' (' . $t->pemeriksaan->tahun . ')' : ''));
                        $sheet->setCellValue("C{$currentRow}", $t->opd->nama ?? '-');
                        $sheet->setCellValue("D{$currentRow}", $t->sumber_pemeriksaan);
                        $sheet->setCellValue("E{$currentRow}", $t->judul_temuan);
                        $sheet->setCellValue("F{$currentRow}", $rekFullText);
                        $sheet->setCellValue("G{$currentRow}", $rekCount ?: '-');
                        $sheet->setCellValue("H{$currentRow}", $t->status_penyelesaian);

                        $sheet->getStyle("A{$currentRow}:H{$currentRow}")->applyFromArray($borderThin);
                        $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("D{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("G{$currentRow}:H{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                        if ($idx % 2 === 1) {
                            $sheet->getStyle("A{$currentRow}:H{$currentRow}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
                        }

                        if ($t->status_penyelesaian === 'Sesuai') {
                            $sheet->getStyle("H{$currentRow}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFDCFCE7');
                            $sheet->getStyle("H{$currentRow}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF166534'));
                        } elseif ($t->status_penyelesaian === 'Belum Ditindaklanjuti') {
                            $sheet->getStyle("H{$currentRow}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEE2E2');
                            $sheet->getStyle("H{$currentRow}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF991B1B'));
                        } elseif ($t->status_penyelesaian === 'Belum Sesuai') {
                            $sheet->getStyle("H{$currentRow}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEF3C7');
                            $sheet->getStyle("H{$currentRow}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF92400E'));
                        }

                        $currentRow++;
                    }

                    $sheet->mergeCells("A{$currentRow}:F{$currentRow}")->setCellValue("A{$currentRow}", 'TOTAL KESELURUHAN (' . $totalTemuan . ' TEMUAN ADMINISTRASI, ' . $totalButirAll . ' BUTIR REKOMENDASI)');
                    $sheet->setCellValue("G{$currentRow}", $totalButirAll ?: '-');
                    $sheet->setCellValue("H{$currentRow}", '');

                    $totalRowStyle = [
                        'font' => ['bold' => true, 'size' => 10],
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF1F5F9']],
                        'borders' => [
                            'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE, 'color' => ['argb' => 'FF1E3A8A']],
                            'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE, 'color' => ['argb' => 'FF1E3A8A']],
                            'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']],
                        ],
                    ];
                    $sheet->getStyle("A{$currentRow}:H{$currentRow}")->applyFromArray($totalRowStyle);
                    $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getRowDimension($currentRow)->setRowHeight(22);

                    $sheet->getColumnDimension('A')->setWidth(5);
                    $sheet->getColumnDimension('B')->setWidth(26);
                    $sheet->getColumnDimension('C')->setWidth(26);
                    $sheet->getColumnDimension('D')->setWidth(10);
                    $sheet->getColumnDimension('E')->setWidth(30);
                    $sheet->getColumnDimension('F')->setWidth(40);
                    $sheet->getColumnDimension('G')->setWidth(14);
                    $sheet->getColumnDimension('H')->setWidth(20);
                    $sheet->getStyle('B7:F' . $currentRow)->getAlignment()->setWrapText(true);
                }
            }

            $logo = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $logo->setName('Logo Provinsi Gorontalo');
            $logo->setDescription('Lambang Provinsi Gorontalo');
            $logo->setPath(public_path('images/logo-gorontalo-excel.png'));
            $logo->setHeight(62);
            $logo->setCoordinates('A1');
            $logo->setOffsetX(8);
            $logo->setOffsetY(4);
            $logo->setWorksheet($sheet);

            // Sisipkan ruang untuk kop dan metadata agar tabel tetap memiliki header sendiri.
            $sheet->insertNewRowBefore(6, 2);
            $sheet->mergeCells("A5:{$lastColumn}5");
            $sheet->mergeCells("A6:{$lastColumn}6");
            $sheet->setCellValue('A5', $reportTitle);
            $sheet->setCellValue('A6', $exportMetadata);
            $sheet->getStyle("A1:{$lastColumn}5")->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle("A6:{$lastColumn}6")->getAlignment()
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle('A5')->getFont()->setName('Times New Roman')->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1E3A8A'));
            $sheet->getStyle('A6')->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF64748B'));
            $sheet->getRowDimension(5)->setRowHeight(20);
            $sheet->getRowDimension(6)->setRowHeight(18);

            if ($tab === 'rekap') {
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(8, 9);
                $sheet->freezePane('A10');
            } else {
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(8, 8);
                $sheet->freezePane('A9');
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
