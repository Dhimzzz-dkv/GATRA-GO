<?php

namespace App\Http\Controllers;

use App\Models\Lhp;
use App\Models\Opd;
use App\Models\Pemeriksaan;
use App\Models\Temuan;
use App\Models\TindakLanjut;
use App\Models\TahunAnggaran;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LhpController extends Controller
{
    /**
     * Display a listing of LHPs.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isOpd()) {
            return redirect()->route('temuan.index')
                ->with('info', 'Rincian LHP dibuka melalui temuan yang ditujukan kepada OPD Anda.');
        }

        $search = $request->input('search');
        $sumber = $request->input('sumber');
        $opdId = $request->input('opd_id');
        $tahun = $request->input('tahun');
        $statusPenyelesaian = $request->input('status_penyelesaian');

        // Scoped LHP Query through Pemeriksaan
        $query = Lhp::forUser($user)
            ->whereHas('pemeriksaan', function ($pq) use ($user, $sumber, $tahun) {
                $pq->forUser($user)->active();
                if ($sumber) {
                    $pq->where('sumber_pemeriksaan', $sumber);
                }
                if ($tahun) {
                    $pq->where('tahun', $tahun);
                }
            })
            ->when($opdId, function ($q, $opdId) {
                $q->whereHas('opds', function ($oq) use ($opdId) {
                    $oq->where('opds.id', $opdId);
                });
            })
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nomor_lhp', 'like', "%{$search}%")
                        ->orWhere('judul_lhp', 'like', "%{$search}%")
                        ->orWhere('ringkasan', 'like', "%{$search}%")
                        ->orWhereHas('opds', function ($oq) use ($search) {
                            $oq->where('nama', 'like', "%{$search}%")
                               ->orWhere('kode', 'like', "%{$search}%");
                        });
                });
            });

        // Hitung ringkasan dengan agregasi database tanpa memuat seluruh model dan relasinya.
        $totalLhp = (clone $query)->count('lhps.id');
        $totalTemuan = Temuan::query()
            ->forUser($user)
            ->active()
            ->whereIn('lhp_id', (clone $query)->select('lhps.id'))
            ->count();
        $totalKerugian = (float) Temuan::query()
            ->whereIn('lhp_id', (clone $query)->select('lhps.id'))
            ->where('ada_unsur_kerugian', true)
            ->sum('nilai_kerugian');
        $totalDisetorLangsung = (float) TindakLanjut::query()
            ->whereIn('lhp_id', (clone $query)->select('lhps.id'))
            ->where('status_verifikasi', 'SESUAI')
            ->sum('jumlah_setor');
        $totalDisetorMelaluiTemuan = (float) TindakLanjut::query()
            ->whereNull('lhp_id')
            ->where('status_verifikasi', 'SESUAI')
            ->whereIn('temuan_id', Temuan::query()
                ->select('temuans.id')
                ->whereIn('temuans.lhp_id', (clone $query)->select('lhps.id')))
            ->sum('jumlah_setor');
        $totalDisetor = $totalDisetorLangsung + $totalDisetorMelaluiTemuan;

        // Filter status dihitung melalui keberadaan temuan agar tidak memuat seluruh relasi ke memori.
        $hasVisibleTemuan = function ($temuanQuery) use ($user) {
            $temuanQuery->forUser($user)->active();
        };
        $hasTemuanWithStatus = function (string $status) use ($user) {
            return function ($temuanQuery) use ($user, $status) {
                $temuanQuery->forUser($user)
                    ->active()
                    ->where('status_penyelesaian', $status);
            };
        };
        $completedStatuses = [
            'Sesuai',
            'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah',
            'Tidak Dapat Ditindaklanjuti',
        ];
        $notCompletedTemuan = function ($temuanQuery) use ($user, $completedStatuses) {
            $temuanQuery->forUser($user)
                ->active()
                ->where(function ($statusQuery) use ($completedStatuses) {
                    $statusQuery->whereNull('status_penyelesaian')
                        ->orWhereNotIn('status_penyelesaian', $completedStatuses);
                });
        };

        if ($statusPenyelesaian !== null && $statusPenyelesaian !== '') {
            $query->where(function ($statusQuery) use (
                $statusPenyelesaian,
                $hasVisibleTemuan,
                $hasTemuanWithStatus,
                $notCompletedTemuan,
                $user
            ) {
                switch ($statusPenyelesaian) {
                    case 'Belum Ditindaklanjuti':
                        $statusQuery->whereDoesntHave('temuans', $hasTemuanWithStatus('Menunggu Verifikasi'))
                            ->whereDoesntHave('temuans', $hasTemuanWithStatus('Belum Sesuai'))
                            ->where(function ($defaultStatusQuery) use ($hasVisibleTemuan, $notCompletedTemuan) {
                                $defaultStatusQuery->whereDoesntHave('temuans', $hasVisibleTemuan)
                                    ->orWhereHas('temuans', $notCompletedTemuan);
                            });
                        break;

                    case 'Menunggu Verifikasi':
                        $statusQuery->whereHas('temuans', $hasTemuanWithStatus('Menunggu Verifikasi'));
                        break;

                    case 'Belum Sesuai':
                        $statusQuery->whereDoesntHave('temuans', $hasTemuanWithStatus('Menunggu Verifikasi'))
                            ->whereHas('temuans', $hasTemuanWithStatus('Belum Sesuai'));
                        break;

                    case 'Sesuai':
                        $statusQuery->whereHas('temuans', $hasTemuanWithStatus('Sesuai'))
                            ->whereDoesntHave('temuans', $notCompletedTemuan);
                        break;

                    case 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah':
                        $statusQuery->whereHas('temuans', function ($temuanQuery) use ($user) {
                            $temuanQuery->forUser($user)
                                ->active()
                                ->whereIn('status_penyelesaian', [
                                    'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah',
                                    'Tidak Dapat Ditindaklanjuti',
                                ]);
                        })
                            ->whereDoesntHave('temuans', function ($temuanQuery) use ($user) {
                                $temuanQuery->forUser($user)
                                    ->active()
                                    ->where(function ($statusQuery) {
                                        $statusQuery->whereNull('status_penyelesaian')
                                            ->orWhereNotIn('status_penyelesaian', [
                                                'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah',
                                                'Tidak Dapat Ditindaklanjuti',
                                            ]);
                                    });
                            });
                        break;

                    default:
                        $statusQuery->whereRaw('1 = 0');
                        break;
                }
            });
        }

        $totalKerugianSubquery = Temuan::query()
            ->selectRaw('COALESCE(SUM(temuans.nilai_kerugian), 0)')
            ->whereColumn('temuans.lhp_id', 'lhps.id')
            ->where('temuans.ada_unsur_kerugian', true);
        $totalDisetorLangsungSubquery = TindakLanjut::query()
            ->selectRaw('COALESCE(SUM(tindak_lanjuts.jumlah_setor), 0)')
            ->whereColumn('tindak_lanjuts.lhp_id', 'lhps.id')
            ->where('tindak_lanjuts.status_verifikasi', 'SESUAI');
        $totalDisetorMelaluiTemuanSubquery = TindakLanjut::query()
            ->join('temuans as terkait_temuan', 'terkait_temuan.id', '=', 'tindak_lanjuts.temuan_id')
            ->selectRaw('COALESCE(SUM(tindak_lanjuts.jumlah_setor), 0)')
            ->whereColumn('terkait_temuan.lhp_id', 'lhps.id')
            ->whereNull('terkait_temuan.deleted_at')
            ->whereNull('tindak_lanjuts.lhp_id')
            ->where('tindak_lanjuts.status_verifikasi', 'SESUAI');

        $lhps = $query->orderBy('tanggal_lhp', 'desc')
            ->orderBy('id', 'desc')
            ->addSelect([
                'total_kerugian_agregat' => $totalKerugianSubquery,
                'total_disetor_lhp_agregat' => $totalDisetorLangsungSubquery,
                'total_disetor_temuan_agregat' => $totalDisetorMelaluiTemuanSubquery,
            ])
            ->with([
                'pemeriksaan',
                'opds',
                'temuans' => function ($temuanQuery) use ($user) {
                    $temuanQuery->forUser($user)->active();
                },
            ])
            ->paginate(10)
            ->withQueryString();

        $opds = Opd::active()->orderBy('nama')->get();
        $years = TahunAnggaran::allYears();
        $sumberList = ['BPK', 'BPKP', 'ITJEN', 'Inspektorat'];

        return view('lhp.index', compact(
            'lhps',
            'opds',
            'years',
            'sumberList',
            'totalLhp',
            'totalTemuan',
            'totalKerugian',
            'totalDisetor',
            'search',
            'sumber',
            'opdId',
            'tahun',
            'statusPenyelesaian'
        ));
    }

    /**
     * Show the form for creating a new LHP (dedicated workflow for Admin Sumber BPK, BPKP, ITJEN & Super Admin).
     */
    public function create(Request $request)
    {
        $user = $request->user();

        if ($user->isPemantau() || $user->isOpd()) {
            abort(403, 'Akses ditolak.');
        }

        if ($user->isAdminInspektorat()) {
            return redirect()->route('pemeriksaan.create')
                ->with('info', 'Admin Inspektorat menginput LHP melalui alur SPT & Pemeriksaan.');
        }

        $opds = Opd::active()->orderBy('nama')->get();
        $years = TahunAnggaran::activeYears();
        $currentYear = TahunAnggaran::currentYear();
        $sumberList = ['BPK', 'BPKP', 'ITJEN'];
        $lockedSumber = $user->isAdminSumber() ? $user->getSumberPemeriksaan() : null;

        return view('lhp.create', compact('opds', 'years', 'currentYear', 'sumberList', 'lockedSumber'));
    }

    /**
     * Store a newly created LHP directly with its temuans and tindak lanjuts (for Admin Sumber & Super Admin).
     */
    public function storeDirect(Request $request)
    {
        $user = $request->user();

        if ($user->isPemantau() || $user->isOpd()) {
            abort(403, 'Akses ditolak.');
        }

        if ($user->isAdminInspektorat()) {
            abort(403, 'Admin Inspektorat harus menginput LHP melalui alur SPT & Pemeriksaan.');
        }

        if ($user->isAdminSumber()) {
            $request->merge(['sumber_pemeriksaan' => $user->getSumberPemeriksaan()]);
        }

        $directLhpSources = ['BPK', 'BPKP', 'ITJEN'];

        $submittedLhps = $request->input('lhps', []);
        if (! is_array($submittedLhps)) {
            $submittedLhps = [];
        }

        $lhpNumbers = $request->input('lhp_nomor', []);
        if (is_array($lhpNumbers)) {
            foreach (array_values($lhpNumbers) as $index => $lhpNumber) {
                if (! is_scalar($lhpNumber) || ! filled($lhpNumber)) {
                    continue;
                }

                $submittedLhps[$index] = is_array($submittedLhps[$index] ?? null)
                    ? $submittedLhps[$index]
                    : [];

                if (! filled($submittedLhps[$index]['nomor_lhp'] ?? null)) {
                    $submittedLhps[$index]['nomor_lhp'] = trim((string) $lhpNumber);
                }
            }
        }

        $lhpOpdIds = $request->input('lhp_opd_ids', []);
        if (is_array($lhpOpdIds)) {
            foreach ($lhpOpdIds as $index => $opdIds) {
                $submittedLhps[$index] = is_array($submittedLhps[$index] ?? null)
                    ? $submittedLhps[$index]
                    : [];

                if (! array_key_exists('opd_ids', $submittedLhps[$index]) && is_array($opdIds)) {
                    $submittedLhps[$index]['opd_ids'] = $opdIds;
                }
            }
        }

        $request->merge(['lhps' => $submittedLhps]);

        $validated = $request->validate([
            'sumber_pemeriksaan' => ['required', Rule::in($directLhpSources)],
            'tahun' => ['required', 'integer', Rule::exists('tahun_anggarans', 'tahun')->where('is_active', true)],

            // Multi-LHP structure
            'lhps' => ['nullable', 'array'],
            'lhps.*.nomor_lhp' => ['nullable', 'string', 'max:100'],
            'lhp_nomor' => ['nullable', 'array'],
            'lhp_nomor.*' => ['nullable', 'string', 'max:100'],
            'lhp_opd_ids' => ['nullable', 'array'],
            'lhp_opd_ids.*' => ['nullable', 'array'],
            'lhp_opd_ids.*.*' => ['exists:opds,id'],
            'lhps.*.tanggal_lhp' => ['nullable', 'date'],
            'lhps.*.judul_lhp' => ['nullable', 'string', 'max:255'],
            'lhps.*.ringkasan' => ['nullable', 'string'],
            'lhps.*.dokumen_lhp' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
            'lhps.*.opd_ids' => ['nullable', 'array'],
            'lhps.*.opd_ids.*' => ['exists:opds,id'],
            'lhps.*.temuans_administrasi' => ['nullable', 'array'],
            'lhps.*.temuans_administrasi.*.uraian' => ['nullable', 'string'],
            'lhps.*.temuans_administrasi.*.penyebab' => ['nullable', 'string'],
            'lhps.*.temuans_administrasi.*.rekomendasi' => ['nullable', 'string'],
            'lhps.*.temuans_administrasi.*.rekomendasi_items' => ['nullable', 'array'],
            'lhps.*.temuans_administrasi.*.rekomendasi_items.*' => ['nullable', 'string'],
            'lhps.*.temuan_kerugian' => ['nullable', 'array'],
            'lhps.*.temuan_kerugian.kategori_kerugian' => ['nullable', 'in:Kerugian Daerah,Kerugian Negara'],
            'lhps.*.temuan_kerugian.jenis_kerugian' => ['nullable', 'in:Internal,Eksternal'],
            'lhps.*.temuan_kerugian.nilai_kerugian' => ['nullable', 'string'],
            'lhps.*.temuan_kerugian.uraian' => ['nullable', 'string'],
            'lhps.*.temuan_kerugian.rekomendasi' => ['nullable', 'string'],
            'lhps.*.temuans_kerugian' => ['nullable', 'array'],
            'lhps.*.temuans_kerugian.*.kategori_kerugian' => ['nullable', 'in:Kerugian Daerah,Kerugian Negara'],
            'lhps.*.temuans_kerugian.*.jenis_kerugian' => ['nullable', 'in:Internal,Eksternal'],
            'lhps.*.temuans_kerugian.*.nilai_kerugian' => ['nullable', 'string'],
            'lhps.*.temuans_kerugian.*.uraian' => ['nullable', 'string'],
            'lhps.*.temuans_kerugian.*.penyebab' => ['nullable', 'string'],
            'lhps.*.temuans_kerugian.*.rekomendasi' => ['nullable', 'string'],

            // Single LHP fallback
            'nomor_lhp' => ['nullable', 'string', 'max:100'],
            'tanggal_lhp' => ['nullable', 'date'],
            'judul_lhp' => ['nullable', 'string', 'max:255'],
            'ringkasan' => ['nullable', 'string'],
            'dokumen_lhp' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
            'opd_ids' => ['nullable', 'array'],
            'opd_ids.*' => ['exists:opds,id'],

            // Temuans
            'temuans_administrasi' => ['nullable', 'array'],
            'temuans_kerugian' => ['nullable', 'array'],
            'temuans_kerugian.*.kategori_kerugian' => ['nullable', 'in:Kerugian Daerah,Kerugian Negara'],
            'temuans_kerugian.*.jenis_kerugian' => ['nullable', 'in:Internal,Eksternal'],
            'temuans_kerugian.*.nilai_kerugian' => ['nullable', 'string'],
            'temuans_kerugian.*.uraian' => ['nullable', 'string'],
            'temuans_kerugian.*.penyebab' => ['nullable', 'string'],
            'temuans_kerugian.*.rekomendasi' => ['nullable', 'string'],
            'temuan_kerugian' => ['nullable', 'array'],
            'temuan_kerugian.kategori_kerugian' => ['nullable', 'in:Kerugian Daerah,Kerugian Negara'],
            'temuan_kerugian.jenis_kerugian' => ['nullable', 'in:Internal,Eksternal'],

            // Tindak Lanjut
            'tindak_lanjut_adm' => ['nullable', 'array'],
            'tindak_lanjut_kg' => ['nullable', 'array'],
            'tindak_lanjuts' => ['nullable', 'array'],
        ]);

        $submittedLhps = $validated['lhps'] ?? [];
        $hasLhpNumber = (is_array($submittedLhps) && collect($submittedLhps)->contains(
            fn (mixed $lhpInput): bool => is_array($lhpInput) && filled($lhpInput['nomor_lhp'] ?? null)
        )) || filled($request->input('nomor_lhp'));

        if (! $hasLhpNumber) {
            throw ValidationException::withMessages([
                'lhps' => 'Nomor LHP wajib diisi. Pengajuan tidak dapat disimpan tanpa minimal satu LHP.',
            ]);
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $validated, $user) {
            $sumber = $validated['sumber_pemeriksaan'];
            $tahun = $validated['tahun'];

            $firstDate = now()->format('Y-m-d');
            if (! empty($request->lhps[0]['tanggal_lhp'])) {
                $firstDate = $request->lhps[0]['tanggal_lhp'];
            } elseif (! empty($validated['tanggal_lhp'])) {
                $firstDate = $validated['tanggal_lhp'];
            }

            $statusPengajuanAwal = $user->isSuperAdmin() ? 'DISETUJUI' : 'MENUNGGU PERSETUJUAN';

            $pemeriksaan = Pemeriksaan::create([
                'sumber_pemeriksaan' => $sumber,
                'jenis_pengajuan' => Pemeriksaan::JENIS_PENGAJUAN_LHP_ONLY,
                'tahun' => $tahun,
                'nomor_spt' => null,
                'tanggal_spt' => null,
                'judul' => "Pengajuan LHP {$sumber} TA {$tahun}",
                'nama_tim' => null,
                'status_pengajuan' => $statusPengajuanAwal,
                'created_by' => $user->id,
            ]);

            $createdLhps = [];
            $allCreatedAdmTemuans = [];
            $allCreatedKgTemuans = [];

            // A. Proses array multi-LHP (lhps)
            if ($request->has('lhps') && is_array($request->lhps)) {
                foreach ($request->lhps as $idx => $lhpInput) {
                    if (! empty($lhpInput['nomor_lhp'])) {
                        $lhpData = [
                            'pemeriksaan_id' => $pemeriksaan->id,
                            'nomor_lhp' => $lhpInput['nomor_lhp'],
                            'tanggal_lhp' => $lhpInput['tanggal_lhp'] ?? $firstDate,
                            'judul_lhp' => ! empty($lhpInput['judul_lhp']) ? $lhpInput['judul_lhp'] : "LHP {$lhpInput['nomor_lhp']}",
                            'ringkasan' => $lhpInput['ringkasan'] ?? null,
                            'created_by' => $user->id,
                        ];

                        $selectedOpdIds = [];
                        if (! empty($lhpInput['opd_ids']) && is_array($lhpInput['opd_ids'])) {
                            $selectedOpdIds = array_values(array_unique(array_filter($lhpInput['opd_ids'])));
                        }
                        $primaryOpdId = ! empty($selectedOpdIds) ? $selectedOpdIds[0] : $pemeriksaan->opd_id;

                        if (empty($primaryOpdId)) {
                            throw ValidationException::withMessages([
                                "lhps.{$idx}.opd_ids" => 'LHP tidak dapat disimpan: OPD target belum dipilih. Centang minimal satu OPD target pada LHP ini sebelum menyimpan.',
                            ]);
                        }

                        if ($request->hasFile("lhps.{$idx}.dokumen_lhp")) {
                            $fileLhp = $request->file("lhps.{$idx}.dokumen_lhp");
                            $lhpData['dokumen_lhp'] = $fileLhp->store('lhp', 'local');
                            $lhpData['nama_dokumen_lhp_asli'] = $fileLhp->getClientOriginalName();
                        }

                        $lhp = Lhp::create($lhpData);
                        $createdLhps[$idx] = $lhp;

                        if (! empty($selectedOpdIds)) {
                            $lhp->opds()->sync($selectedOpdIds);
                        }

                        // Temuan Administrasi
                        if (! empty($lhpInput['temuans_administrasi']) && is_array($lhpInput['temuans_administrasi'])) {
                            foreach ($lhpInput['temuans_administrasi'] as $admIdx => $admInput) {
                                if (! empty($admInput['uraian']) || ! empty($admInput['rekomendasi'])) {
                                    $uraianAdm = $admInput['uraian'] ?? 'Temuan Administrasi';
                                    $rekAdm = $admInput['rekomendasi'] ?? 'Agar ditindaklanjuti dan dilengkapi sesuai ketentuan perundang-undangan.';

                                    $statusPengajuanItem = $user->isSuperAdmin() ? 'DISETUJUI' : 'MENUNGGU PERSETUJUAN';
                                    $admTemuan = \App\Models\Temuan::create([
                                        'pemeriksaan_id' => $pemeriksaan->id,
                                        'lhp_id' => $lhp->id,
                                        'sumber_pemeriksaan' => $sumber,
                                        'opd_id' => $primaryOpdId,
                                        'tahun' => $tahun,
                                        'judul_temuan' => 'Temuan Administrasi #' . ($admIdx + 1) . ': ' . \Illuminate\Support\Str::limit($uraianAdm, 40),
                                        'uraian_temuan' => $uraianAdm,
                                        'ada_unsur_administrasi' => true,
                                        'uraian_administrasi' => $uraianAdm,
                                        'penyebab' => ! empty($admInput['penyebab']) ? trim($admInput['penyebab']) : null,
                                        'ada_unsur_kerugian' => false,
                                        'nilai_kerugian' => 0,
                                        'rekomendasi' => $rekAdm,
                                        'status_pengajuan' => $statusPengajuanItem,
                                        'status_penyelesaian' => 'Belum Ditindaklanjuti',
                                        'created_by' => $user->id,
                                    ]);
                                    $allCreatedAdmTemuans[$idx][] = $admTemuan;

                                    $pService = app(\App\Services\PemeriksaanService::class);
                                    $items = ! empty($admInput['rekomendasi_items']) && is_array($admInput['rekomendasi_items'])
                                        ? array_values(array_filter(array_map('trim', $admInput['rekomendasi_items'])))
                                        : $pService->parseRekomendasiText($rekAdm);

                                    foreach ($items as $itemText) {
                                        $admTemuan->rekomendasis()->create([
                                            'uraian_rekomendasi' => $itemText,
                                            'status_penyelesaian' => 'Belum Ditindaklanjuti',
                                            'created_by' => $user->id,
                                        ]);
                                    }
                                }
                            }
                        }

                        // Temuan Kerugian (Multi-Temuan Dinamis atau Single Fallback)
                        $kerugianList = [];
                        if (! empty($lhpInput['temuans_kerugian']) && is_array($lhpInput['temuans_kerugian'])) {
                            $kerugianList = $lhpInput['temuans_kerugian'];
                        } elseif (! empty($lhpInput['temuan_kerugian']) && is_array($lhpInput['temuan_kerugian'])) {
                            $kerugianList = [$lhpInput['temuan_kerugian']];
                        }

                        foreach ($kerugianList as $kgIdx => $kgInput) {
                            $kategoriKg = ! empty($kgInput['kategori_kerugian']) ? $kgInput['kategori_kerugian'] : 'Kerugian Daerah';
                            $nilaiKerugian = ! empty($kgInput['nilai_kerugian']) ? (float) str_replace(['.', ','], ['', '.'], $kgInput['nilai_kerugian']) : 0;
                            $uraianKg = $kgInput['uraian'] ?? ("{$kategoriKg} pada LHP " . $lhp->nomor_lhp);
                            $kasTujuan = $kategoriKg === 'Kerugian Negara' ? 'Kas Negara' : 'Kas Daerah';
                            $rekKg = $kgInput['rekomendasi'] ?? "Agar menyetorkan kerugian ke {$kasTujuan} sesuai ketentuan.";
                            $penyebabKg = ! empty($kgInput['penyebab']) ? trim($kgInput['penyebab']) : null;
                            $nomorUrutLabel = count($kerugianList) > 1 ? ' #' . ($kgIdx + 1) : '';

                            if ($nilaiKerugian > 0 || ! empty($kgInput['uraian']) || ! empty($kgInput['rekomendasi'])) {
                                $statusPengajuanItem = $user->isSuperAdmin() ? 'DISETUJUI' : 'MENUNGGU PERSETUJUAN';
                                $kgTemuan = \App\Models\Temuan::create([
                                    'pemeriksaan_id' => $pemeriksaan->id,
                                    'lhp_id' => $lhp->id,
                                    'sumber_pemeriksaan' => $sumber,
                                    'opd_id' => $primaryOpdId,
                                    'tahun' => $tahun,
                                    'judul_temuan' => "Temuan {$kategoriKg}{$nomorUrutLabel}: " . \Illuminate\Support\Str::limit($uraianKg, 40),
                                    'uraian_temuan' => $uraianKg,
                                    'ada_unsur_administrasi' => false,
                                    'ada_unsur_kerugian' => true,
                                    'kategori_kerugian' => $kategoriKg,
                                    'jenis_kerugian' => $kgInput['jenis_kerugian'] ?? null,
                                    'nilai_kerugian' => $nilaiKerugian,
                                    'uraian_kerugian' => $nilaiKerugian > 0 ? "{$kategoriKg} sebesar Rp " . number_format($nilaiKerugian, 0, ',', '.') : null,
                                    'penyebab' => $penyebabKg,
                                    'rekomendasi' => $rekKg,
                                    'status_pengajuan' => $statusPengajuanItem,
                                    'status_penyelesaian' => 'Belum Ditindaklanjuti',
                                    'created_by' => $user->id,
                                ]);
                                if (! isset($allCreatedKgTemuans[$idx])) {
                                    $allCreatedKgTemuans[$idx] = $kgTemuan;
                                }
                            }
                        }

                        AuditLogger::log('CREATE', 'LHP', "Menambahkan LHP ({$lhp->nomor_lhp}) sumber {$sumber}.");
                    }
                }
            } elseif (! empty($validated['nomor_lhp'])) {
                // Fallback Single LHP
                $primaryOpdId = $validated['opd_ids'][0] ?? $pemeriksaan->opd_id;

                if (empty($primaryOpdId)) {
                    throw ValidationException::withMessages([
                        'opd_ids' => 'LHP tidak dapat disimpan: OPD target belum dipilih. '
                            . 'Centang minimal satu OPD target pada LHP ini sebelum menyimpan.',
                    ]);
                }
                $lhpData = [
                    'pemeriksaan_id' => $pemeriksaan->id,
                    'nomor_lhp' => $validated['nomor_lhp'],
                    'tanggal_lhp' => $validated['tanggal_lhp'],
                    'judul_lhp' => $validated['judul_lhp'] ?: "LHP {$validated['nomor_lhp']}",
                    'ringkasan' => $validated['ringkasan'] ?? null,
                    'created_by' => $user->id,
                ];

                if ($request->hasFile('dokumen_lhp')) {
                    $file = $request->file('dokumen_lhp');
                    $lhpData['dokumen_lhp'] = $file->store('lhp', 'local');
                    $lhpData['nama_dokumen_lhp_asli'] = $file->getClientOriginalName();
                }

                $lhp = Lhp::create($lhpData);
                $createdLhps[0] = $lhp;
                $lhp->opds()->sync($validated['opd_ids'] ?? []);

                // Single Temuan Adm
                if (! empty($request->temuans_administrasi) && is_array($request->temuans_administrasi)) {
                    foreach ($request->temuans_administrasi as $admIdx => $admInput) {
                        if (! empty($admInput['uraian']) || ! empty($admInput['rekomendasi'])) {
                            $uraianAdm = $admInput['uraian'] ?? 'Temuan Administrasi';
                            $rekAdm = $admInput['rekomendasi'] ?? 'Agar ditindaklanjuti dan dilengkapi sesuai ketentuan perundang-undangan.';
                            $statusPengajuanItem = $user->isSuperAdmin() ? 'DISETUJUI' : 'MENUNGGU PERSETUJUAN';

                            $admTemuan = \App\Models\Temuan::create([
                                'pemeriksaan_id' => $pemeriksaan->id,
                                'lhp_id' => $lhp->id,
                                'sumber_pemeriksaan' => $sumber,
                                'opd_id' => $primaryOpdId,
                                'tahun' => $tahun,
                                'judul_temuan' => 'Temuan Administrasi #' . ($admIdx + 1) . ': ' . \Illuminate\Support\Str::limit($uraianAdm, 40),
                                'uraian_temuan' => $uraianAdm,
                                'ada_unsur_administrasi' => true,
                                'uraian_administrasi' => $uraianAdm,
                                'ada_unsur_kerugian' => false,
                                'nilai_kerugian' => 0,
                                'rekomendasi' => $rekAdm,
                                'status_pengajuan' => $statusPengajuanItem,
                                'status_penyelesaian' => 'Belum Ditindaklanjuti',
                                'created_by' => $user->id,
                            ]);
                            $allCreatedAdmTemuans[0][] = $admTemuan;

                            $pService = app(\App\Services\PemeriksaanService::class);
                            $items = ! empty($admInput['rekomendasi_items']) && is_array($admInput['rekomendasi_items'])
                                ? array_values(array_filter(array_map('trim', $admInput['rekomendasi_items'])))
                                : $pService->parseRekomendasiText($rekAdm);

                            foreach ($items as $itemText) {
                                $admTemuan->rekomendasis()->create([
                                    'uraian_rekomendasi' => $itemText,
                                    'status_penyelesaian' => 'Belum Ditindaklanjuti',
                                    'created_by' => $user->id,
                                ]);
                            }
                        }
                    }
                }

                // Temuan Kg (Multi-Temuan Dinamis atau Single Fallback)
                $kerugianListSingle = [];
                if (! empty($request->temuans_kerugian) && is_array($request->temuans_kerugian)) {
                    $kerugianListSingle = $request->temuans_kerugian;
                } elseif (! empty($request->temuan_kerugian) && is_array($request->temuan_kerugian)) {
                    $kerugianListSingle = [$request->temuan_kerugian];
                }

                foreach ($kerugianListSingle as $kgIdx => $kgInput) {
                    $kategoriKg = ! empty($kgInput['kategori_kerugian']) ? $kgInput['kategori_kerugian'] : 'Kerugian Daerah';
                    $nilaiKerugian = ! empty($kgInput['nilai_kerugian']) ? (float) str_replace(['.', ','], ['', '.'], $kgInput['nilai_kerugian']) : 0;
                    $uraianKg = $kgInput['uraian'] ?? ("{$kategoriKg} pada LHP " . $lhp->nomor_lhp);
                    $kasTujuan = $kategoriKg === 'Kerugian Negara' ? 'Kas Negara' : 'Kas Daerah';
                    $rekKg = $kgInput['rekomendasi'] ?? "Agar menyetorkan kerugian ke {$kasTujuan} sesuai ketentuan.";
                    $penyebabKg = ! empty($kgInput['penyebab']) ? trim($kgInput['penyebab']) : null;
                    $nomorUrutLabel = count($kerugianListSingle) > 1 ? ' #' . ($kgIdx + 1) : '';

                    if ($nilaiKerugian > 0 || ! empty($kgInput['uraian']) || ! empty($kgInput['rekomendasi'])) {
                        $statusPengajuanItem = $user->isSuperAdmin() ? 'DISETUJUI' : 'MENUNGGU PERSETUJUAN';
                        $kgTemuan = \App\Models\Temuan::create([
                            'pemeriksaan_id' => $pemeriksaan->id,
                            'lhp_id' => $lhp->id,
                            'sumber_pemeriksaan' => $sumber,
                            'opd_id' => $primaryOpdId,
                            'tahun' => $tahun,
                            'judul_temuan' => "Temuan {$kategoriKg}{$nomorUrutLabel}: " . \Illuminate\Support\Str::limit($uraianKg, 40),
                            'uraian_temuan' => $uraianKg,
                            'ada_unsur_administrasi' => false,
                            'ada_unsur_kerugian' => true,
                            'kategori_kerugian' => $kategoriKg,
                            'jenis_kerugian' => $kgInput['jenis_kerugian'] ?? null,
                            'nilai_kerugian' => $nilaiKerugian,
                            'uraian_kerugian' => $nilaiKerugian > 0 ? "{$kategoriKg} sebesar Rp " . number_format($nilaiKerugian, 0, ',', '.') : null,
                            'penyebab' => $penyebabKg,
                            'rekomendasi' => $rekKg,
                            'status_pengajuan' => $statusPengajuanItem,
                            'status_penyelesaian' => 'Belum Ditindaklanjuti',
                            'created_by' => $user->id,
                        ]);
                        if (! isset($allCreatedKgTemuans[0])) {
                            $allCreatedKgTemuans[0] = $kgTemuan;
                        }
                    }
                }

                AuditLogger::log('CREATE', 'LHP', "Menambahkan LHP ({$lhp->nomor_lhp}) sumber {$sumber}.");
            }

            // Tindak lanjut generic / array `tindak_lanjuts`
            if ($request->filled('tindak_lanjuts') && is_array($request->tindak_lanjuts)) {
                foreach ($request->tindak_lanjuts as $tlIdx => $tlInput) {
                    $targetLhpIdx = $tlInput['lhp_index'] ?? 0;
                    $targetLhp = $createdLhps[$targetLhpIdx] ?? ($createdLhps[0] ?? null);

                    if ($targetLhp) {
                        $tlOpdId = $targetLhp->opds()->first()?->id ?? ($pemeriksaan->opd_id ?? null);
                        $nilaiSetor = ! empty($tlInput['jumlah_setor']) ? (float) str_replace(['.', ','], ['', '.'], $tlInput['jumlah_setor']) : 0;

                        // Hubungkan ke Temuan target
                        $targetTemuan = null;
                        if (! empty($tlInput['temuan_type']) && $tlInput['temuan_type'] === 'administrasi' && ! empty($allCreatedAdmTemuans[$targetLhpIdx])) {
                            $targetTemuan = $allCreatedAdmTemuans[$targetLhpIdx][0];
                        } elseif (! empty($allCreatedKgTemuans[$targetLhpIdx])) {
                            $targetTemuan = $allCreatedKgTemuans[$targetLhpIdx];
                        } elseif (! empty($allCreatedAdmTemuans[$targetLhpIdx])) {
                            $targetTemuan = $allCreatedAdmTemuans[$targetLhpIdx][0];
                        }

                        if (! empty($tlInput['uraian_tindak_lanjut']) || $nilaiSetor > 0) {
                            $tlData = [
                                'temuan_id' => $targetTemuan ? $targetTemuan->id : null,
                                'lhp_id' => $targetLhp->id,
                                'opd_id' => $tlOpdId,
                                'tanggal_tindak_lanjut' => $tlInput['tanggal_tindak_lanjut'] ?? now()->format('Y-m-d'),
                                'uraian_tindak_lanjut' => $tlInput['uraian_tindak_lanjut'] ?? 'Tindak lanjut penyelesaian hasil pemeriksaan.',
                                'nomor_bukti_setor' => $tlInput['nomor_bukti_setor'] ?? null,
                                'tanggal_setor' => $tlInput['tanggal_setor'] ?? null,
                                'jumlah_setor' => $nilaiSetor,
                                'status_verifikasi' => 'MENUNGGU VERIFIKASI',
                                'created_by' => $user->id,
                            ];

                            if ($request->hasFile("tindak_lanjuts.{$tlIdx}.dokumen_bukti")) {
                                $tlFile = $request->file("tindak_lanjuts.{$tlIdx}.dokumen_bukti");
                                $tlData['dokumen_bukti'] = $tlFile->store('tindak_lanjut', 'local');
                                $tlData['nama_dokumen_asli'] = $tlFile->getClientOriginalName();
                            }

                            if ($request->hasFile("tindak_lanjuts.{$tlIdx}.dokumen_setor")) {
                                $setorFile = $request->file("tindak_lanjuts.{$tlIdx}.dokumen_setor");
                                $tlData['dokumen_setor'] = $setorFile->store('bukti_setor', 'local');
                                $tlData['nama_dokumen_setor_asli'] = $setorFile->getClientOriginalName();
                            }

                            \App\Models\TindakLanjut::create($tlData);
                            if ($targetTemuan) {
                                $targetTemuan->update(['status_penyelesaian' => 'Menunggu Verifikasi']);
                            }
                        }
                    }
                }
            }

            // Tindak lanjut direct fallback
            if (! empty($request->tindak_lanjut_adm) && is_array($request->tindak_lanjut_adm) && ! empty($allCreatedAdmTemuans[0])) {
                $tlAdmInput = $request->tindak_lanjut_adm;
                if (! empty($tlAdmInput['uraian'])) {
                    $firstAdm = $allCreatedAdmTemuans[0][0];
                    $targetLhp = $createdLhps[0] ?? null;
                    $tlData = [
                        'temuan_id' => $firstAdm->id,
                        'lhp_id' => $targetLhp ? $targetLhp->id : null,
                        'opd_id' => $firstAdm->opd_id,
                        'tanggal_tindak_lanjut' => $tlAdmInput['tanggal'] ?? now()->format('Y-m-d'),
                        'uraian_tindak_lanjut' => $tlAdmInput['uraian'],
                        'status_verifikasi' => 'MENUNGGU VERIFIKASI',
                        'created_by' => $user->id,
                    ];
                    if ($request->hasFile('tindak_lanjut_adm.dokumen_bukti')) {
                        $tlFile = $request->file('tindak_lanjut_adm.dokumen_bukti');
                        $tlData['dokumen_bukti'] = $tlFile->store('tindak_lanjut', 'local');
                        $tlData['nama_dokumen_asli'] = $tlFile->getClientOriginalName();
                    }
                    \App\Models\TindakLanjut::create($tlData);
                    $firstAdm->update(['status_penyelesaian' => 'Menunggu Verifikasi']);
                }
            }

            if (! empty($request->tindak_lanjut_kg) && is_array($request->tindak_lanjut_kg) && ! empty($allCreatedKgTemuans[0])) {
                $tlKgInput = $request->tindak_lanjut_kg;
                if (! empty($tlKgInput['uraian']) || ! empty($tlKgInput['jumlah_setor'])) {
                    $kgTemuan = $allCreatedKgTemuans[0];
                    $targetLhp = $createdLhps[0] ?? null;
                    $jumlahSetor = ! empty($tlKgInput['jumlah_setor']) ? (float) str_replace(['.', ','], ['', '.'], $tlKgInput['jumlah_setor']) : 0;
                    $tlData = [
                        'temuan_id' => $kgTemuan->id,
                        'lhp_id' => $targetLhp ? $targetLhp->id : null,
                        'opd_id' => $kgTemuan->opd_id,
                        'tanggal_tindak_lanjut' => $tlKgInput['tanggal'] ?? now()->format('Y-m-d'),
                        'uraian_tindak_lanjut' => $tlKgInput['uraian'] ?? 'Penyetoran kerugian.',
                        'nomor_bukti_setor' => $tlKgInput['nomor_sts'] ?? null,
                        'tanggal_setor' => $tlKgInput['tanggal_setor'] ?? null,
                        'jumlah_setor' => $jumlahSetor,
                        'status_verifikasi' => 'MENUNGGU VERIFIKASI',
                        'created_by' => $user->id,
                    ];
                    if ($request->hasFile('tindak_lanjut_kg.dokumen_bukti')) {
                        $tlFile = $request->file('tindak_lanjut_kg.dokumen_bukti');
                        $tlData['dokumen_bukti'] = $tlFile->store('tindak_lanjut', 'local');
                        $tlData['nama_dokumen_asli'] = $tlFile->getClientOriginalName();
                    }
                    if ($request->hasFile('tindak_lanjut_kg.dokumen_setor')) {
                        $setorFile = $request->file('tindak_lanjut_kg.dokumen_setor');
                        $tlData['dokumen_setor'] = $setorFile->store('bukti_setor', 'local');
                        $tlData['nama_dokumen_setor_asli'] = $setorFile->getClientOriginalName();
                    }
                    \App\Models\TindakLanjut::create($tlData);
                    $kgTemuan->update(['status_penyelesaian' => 'Menunggu Verifikasi']);
                }
            }

            $countLhp = count($createdLhps);

            if ($user->isAdminSumber() && ! $user->canManageSpt()) {
                return redirect()->route('pengajuan-saya.index')->with(
                    'success',
                    "Berhasil mengajukan {$countLhp} LHP sumber {$sumber} untuk persetujuan Super Admin."
                );
            }

            return redirect()->route('lhp.index')->with('success', "Berhasil menambahkan {$countLhp} dokumen Laporan Hasil Pemeriksaan (LHP) sumber {$sumber}.");
        });
    }
    /**
     * Display the specified LHP detail page.
     */
    public function show(Request $request, Pemeriksaan $pemeriksaan, Lhp $lhp): View
    {
        $user = $request->user();
        $isOpdView = $user->isOpd();

        if (! Pemeriksaan::forUser($user)->where('id', $pemeriksaan->id)->exists()) {
            if ($user->isSuperAdmin() && $pemeriksaan->status_pengajuan === 'DRAFT') {
                abort(403, 'Akses ditolak: Dokumen ini masih berstatus DRAFT pada Admin Sumber (' . $pemeriksaan->sumber_pemeriksaan . ') dan belum diajukan kepada Super Admin.');
            }
            if ($user->isAdminSumber() && $pemeriksaan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
                abort(403, 'Akses ditolak: Anda hanya memiliki hak akses untuk data sumber ' . $user->getSumberPemeriksaan() . '.');
            }
            abort(403, 'Anda tidak memiliki hak akses untuk melihat data LHP ini.');
        }

        if ($lhp->pemeriksaan_id !== $pemeriksaan->id) {
            abort(404);
        }

        if (! \App\Models\Lhp::forUser($user)->where('id', $lhp->id)->exists()) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat data LHP ini.');
        }

        // Parent and LHP are independently route-bound, so load the parent context separately.
        $pemeriksaan->load(['creator.pegawai']);

        if ($isOpdView) {
            $lhp->load([
                'opds',
                'temuans' => function ($query) use ($user) {
                    $query->forUser($user)
                        ->active()
                        ->where('opd_id', $user->opd_id);
                },
                'temuans.tindakLanjuts',
            ]);
            $lhp->setRelation('opds', $lhp->opds->where('id', $user->opd_id)->values());

            return view('pemeriksaan.show-opd', [
                'pemeriksaan' => $pemeriksaan,
                'focusedLhp' => $lhp,
            ]);
        }

        // Semua relasi yang dipakai view lhp.show harus dimuat lebih dulu.
        // `opds` menampilkan daftar OPD target, `temuans.opd` menampilkan
        // instansi pada setiap temuan.
        $lhp->load([
            'pemeriksaan.opd',
            'opds',
            'creator.pegawai',
            'temuans' => function ($q) use ($user) {
                $q->forUser($user)->active();
            },
            'temuans.opd',
            'temuans.creator.pegawai',
            'temuans.rekomendasis',
            'temuans.tindakLanjuts.creator.pegawai',
            'temuans.tindakLanjuts.verifier.pegawai',
        ]);

        $lhpSummary = [
            'progress' => $lhp->progress_persen,
            'total_kerugian' => $lhp->formatted_total_kerugian,
            'total_kerugian_value' => $lhp->total_kerugian,
            'total_disetor' => $lhp->formatted_total_disetor,
            'sisa_kerugian' => $lhp->formatted_sisa_kerugian,
            'total_kerugian_internal' => $lhp->formatted_total_kerugian_internal,
            'kerugian_internal_count' => $lhp->kerugian_internal_count,
            'total_kerugian_eksternal' => $lhp->formatted_total_kerugian_eksternal,
            'kerugian_eksternal_count' => $lhp->kerugian_eksternal_count,
            'total_kerugian_belum_dikategorikan' => $lhp->formatted_total_kerugian_belum_dikategorikan,
            'kerugian_belum_dikategorikan_count' => $lhp->kerugian_belum_dikategorikan_count,
            'temuan_count' => $lhp->temuans->count(),
        ];
        $lhpStatusBadge = $lhp->status_penyelesaian_badge;

        return view('lhp.show', compact('pemeriksaan', 'lhp', 'isOpdView', 'lhpSummary', 'lhpStatusBadge'));
    }

    /**
     * Show the form for correcting an LHP-only submission returned for revision.
     */
    public function edit(Request $request, Pemeriksaan $pemeriksaan, Lhp $lhp): View
    {
        $user = $request->user();

        if ($lhp->pemeriksaan_id !== $pemeriksaan->id) {
            abort(404);
        }

        if (! $pemeriksaan->is_lhp_only_submission) {
            abort(404);
        }

        if (! $user->isSuperAdmin() && (! $user->isAdminSumber() || $pemeriksaan->sumber_pemeriksaan !== $user->getSumberPemeriksaan())) {
            abort(403, 'Anda tidak memiliki hak untuk mengubah LHP ini.');
        }

        if (! in_array($pemeriksaan->status_pengajuan, ['DRAFT', 'PERLU REVISI', 'DITOLAK'], true)) {
            abort(403, 'LHP hanya dapat diperbaiki saat berstatus Draft atau Perlu Revisi.');
        }

        $lhp->load('opds');
        $opds = Opd::active()->orderBy('nama')->get()
            ->merge($lhp->opds)
            ->unique('id')
            ->sortBy('nama')
            ->values();

        return view('lhp.edit', compact('pemeriksaan', 'lhp', 'opds'));
    }
    /**
     * Store a newly created LHP in storage.
     */
    public function store(Request $request, Pemeriksaan $pemeriksaan)
    {
        $user = $request->user();

        if ($user->isPemantau() || $user->isOpd()) {
            abort(403, 'Akses ditolak.');
        }

        if ($user->isAdminSumber() && $pemeriksaan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
            abort(403, 'Anda hanya dapat menginput LHP untuk sumber pemeriksaan ' . $user->getSumberPemeriksaan());
        }

        $validated = $request->validate([
            'nomor_lhp' => ['required', 'string', 'max:100'],
            'tanggal_lhp' => ['required', 'date'],
            'judul_lhp' => ['nullable', 'string', 'max:255'],
            'ringkasan' => ['nullable', 'string'],
            'dokumen_lhp' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:15360'], // Max 15MB
            'opd_ids' => ['nullable', 'array'],
            'opd_ids.*' => ['exists:opds,id'],
            'temuans_administrasi' => ['nullable', 'array'],
            'temuan_kerugian' => ['nullable', 'array'],
        ]);

        $lhpData = [
            'pemeriksaan_id' => $pemeriksaan->id,
            'nomor_lhp' => $validated['nomor_lhp'],
            'tanggal_lhp' => $validated['tanggal_lhp'],
            'judul_lhp' => $validated['judul_lhp'] ?: ($pemeriksaan->judul ?: "LHP {$pemeriksaan->nomor_spt}"),
            'ringkasan' => $validated['ringkasan'] ?? null,
            'created_by' => $user->id,
        ];

        if ($request->hasFile('dokumen_lhp')) {
            $file = $request->file('dokumen_lhp');
            $originalName = $file->getClientOriginalName();
            $path = $file->store('lhp', 'local');

            $lhpData['dokumen_lhp'] = $path;
            $lhpData['nama_dokumen_lhp_asli'] = $originalName;
        }

        $lhp = Lhp::create($lhpData);

        // Sync OPDs Many-to-Many
        if (! empty($validated['opd_ids'])) {
            $lhp->opds()->sync($validated['opd_ids']);
        }

        $primaryOpdId = ! empty($validated['opd_ids']) ? $validated['opd_ids'][0] : ($pemeriksaan->opd_id ?? null);

        if (empty($primaryOpdId)) {
            return back()
                ->withInput()
                ->withErrors([
                    'opd_ids' => 'Data tidak dapat disimpan: OPD target belum ditentukan. '
                        . 'Centang minimal satu OPD target pada LHP ini sebelum menyimpan.',
                ]);
        }

        // 1. Simpan Temuan Administrasi
        if (! empty($request->temuans_administrasi) && is_array($request->temuans_administrasi)) {
            foreach ($request->temuans_administrasi as $admIdx => $admInput) {
                if (! empty($admInput['uraian']) || ! empty($admInput['rekomendasi'])) {
                    $uraianAdm = $admInput['uraian'] ?? 'Temuan Administrasi';
                    $newAdm = \App\Models\Temuan::create([
                        'pemeriksaan_id' => $pemeriksaan->id,
                        'lhp_id' => $lhp->id,
                        'sumber_pemeriksaan' => $pemeriksaan->sumber_pemeriksaan,
                        'opd_id' => $primaryOpdId,
                        'tahun' => $pemeriksaan->tahun,
                        'judul_temuan' => 'Temuan Administrasi #' . ($admIdx + 1) . ': ' . \Illuminate\Support\Str::limit($uraianAdm, 40),
                        'uraian_temuan' => $uraianAdm,
                        'ada_unsur_administrasi' => true,
                        'uraian_administrasi' => $uraianAdm,
                        'ada_unsur_kerugian' => false,
                        'nilai_kerugian' => 0,
                        'rekomendasi' => $admInput['rekomendasi'] ?? 'Agar ditindaklanjuti dan dilengkapi sesuai ketentuan perundang-undangan.',
                        'status_pengajuan' => $pemeriksaan->status_pengajuan ?? 'DRAFT',
                        'status_penyelesaian' => 'Belum Ditindaklanjuti',
                        'created_by' => $user->id,
                    ]);

                    $pService = app(\App\Services\PemeriksaanService::class);
                    $rekText = $admInput['rekomendasi'] ?? '';
                    $items = ! empty($admInput['rekomendasi_items']) && is_array($admInput['rekomendasi_items'])
                        ? array_values(array_filter(array_map('trim', $admInput['rekomendasi_items'])))
                        : $pService->parseRekomendasiText($rekText);

                    foreach ($items as $itemText) {
                        $newAdm->rekomendasis()->create([
                            'uraian_rekomendasi' => $itemText,
                            'status_penyelesaian' => 'Belum Ditindaklanjuti',
                            'created_by' => $user->id,
                        ]);
                    }
                }
            }
        }

        // 2. Simpan Temuan Kerugian
        if (! empty($request->temuan_kerugian) && is_array($request->temuan_kerugian)) {
            $kgInput = $request->temuan_kerugian;
            $kategoriKg = ! empty($kgInput['kategori_kerugian']) ? $kgInput['kategori_kerugian'] : 'Kerugian Daerah';
            $nilaiKerugian = ! empty($kgInput['nilai_kerugian']) ? (float) str_replace(['.', ','], ['', '.'], $kgInput['nilai_kerugian']) : 0;
            if ($nilaiKerugian > 0 || ! empty($kgInput['uraian']) || ! empty($kgInput['rekomendasi'])) {
                $uraianKg = $kgInput['uraian'] ?? ("{$kategoriKg} pada LHP " . $lhp->nomor_lhp);
                $kasTujuan = $kategoriKg === 'Kerugian Negara' ? 'Kas Negara' : 'Kas Daerah';
                \App\Models\Temuan::create([
                    'pemeriksaan_id' => $pemeriksaan->id,
                    'lhp_id' => $lhp->id,
                    'sumber_pemeriksaan' => $pemeriksaan->sumber_pemeriksaan,
                    'opd_id' => $primaryOpdId,
                    'tahun' => $pemeriksaan->tahun,
                    'judul_temuan' => "Temuan {$kategoriKg}: " . \Illuminate\Support\Str::limit($uraianKg, 40),
                    'uraian_temuan' => $uraianKg,
                    'ada_unsur_administrasi' => false,
                    'ada_unsur_kerugian' => true,
                    'kategori_kerugian' => $kategoriKg,
                    'jenis_kerugian' => $kgInput['jenis_kerugian'] ?? null,
                    'nilai_kerugian' => $nilaiKerugian,
                    'uraian_kerugian' => $nilaiKerugian > 0 ? "{$kategoriKg} sebesar Rp " . number_format($nilaiKerugian, 0, ',', '.') : null,
                    'rekomendasi' => $kgInput['rekomendasi'] ?? "Agar menyetorkan kerugian ke {$kasTujuan} sesuai ketentuan.",
                    'status_pengajuan' => $user->isSuperAdmin() ? 'DISETUJUI' : 'MENUNGGU PERSETUJUAN',
                    'status_penyelesaian' => 'Belum Ditindaklanjuti',
                    'created_by' => $user->id,
                ]);
            }
        }

        AuditLogger::log('CREATE', 'LHP', "Menambahkan LHP ({$lhp->nomor_lhp}) pada SPT ({$pemeriksaan->nomor_spt}).");

        return back()->with('success', 'Dokumen Laporan Hasil Pemeriksaan (LHP) berhasil ditambahkan.');
    }

    /**
     * Update the specified LHP in storage.
     */
    public function update(Request $request, Pemeriksaan $pemeriksaan, Lhp $lhp)
    {
        $user = $request->user();

        if ($user->isPemantau() || $user->isOpd()) {
            abort(403, 'Akses ditolak.');
        }

        if ($user->isAdminSumber() && $pemeriksaan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
            abort(403, 'Akses ditolak.');
        }

        if ($lhp->pemeriksaan_id !== $pemeriksaan->id) {
            abort(404);
        }

        if ($pemeriksaan->is_lhp_only_submission) {
            if (! $user->isSuperAdmin() && (! $user->isAdminSumber() || $pemeriksaan->sumber_pemeriksaan !== $user->getSumberPemeriksaan())) {
                abort(403, 'Anda tidak memiliki hak untuk mengubah LHP ini.');
            }

            if (! in_array($pemeriksaan->status_pengajuan, ['DRAFT', 'PERLU REVISI', 'DITOLAK'], true)) {
                abort(403, 'LHP hanya dapat diperbaiki saat berstatus Draft atau Perlu Revisi.');
            }
        }

        $rules = [
            'nomor_lhp' => ['required', 'string', 'max:100'],
            'tanggal_lhp' => ['required', 'date'],
            'judul_lhp' => ['nullable', 'string', 'max:255'],
            'ringkasan' => ['nullable', 'string'],
            'dokumen_lhp' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:15360'],
        ];

        if ($pemeriksaan->is_lhp_only_submission) {
            $rules['opd_ids'] = ['required', 'array', 'min:1'];
            $rules['opd_ids.*'] = ['required', 'integer', 'exists:opds,id'];
        }

        $validated = $request->validate($rules, [
            'opd_ids.required' => 'Pilih minimal satu OPD target untuk LHP ini.',
            'opd_ids.min' => 'Pilih minimal satu OPD target untuk LHP ini.',
        ]);

        $selectedOpdIds = $validated['opd_ids'] ?? null;
        unset($validated['opd_ids']);

        if ($request->hasFile('dokumen_lhp')) {
            // Delete old file if exists
            if ($lhp->dokumen_lhp) {
                if (Storage::disk('local')->exists($lhp->dokumen_lhp)) {
                    Storage::disk('local')->delete($lhp->dokumen_lhp);
                } elseif (Storage::disk('public')->exists($lhp->dokumen_lhp)) {
                    Storage::disk('public')->delete($lhp->dokumen_lhp);
                }
            }

            $file = $request->file('dokumen_lhp');
            $originalName = $file->getClientOriginalName();
            $path = $file->store('lhp', 'local');

            $validated['dokumen_lhp'] = $path;
            $validated['nama_dokumen_lhp_asli'] = $originalName;
        }

        $lhp->update($validated);

        if ($pemeriksaan->is_lhp_only_submission) {
            $lhp->opds()->sync($selectedOpdIds);
            $lhp->temuans()->update(['opd_id' => $selectedOpdIds[0]]);
            $pemeriksaan->touch();
            AuditLogger::log('UPDATE', 'LHP', "Memperbaiki LHP (ID: {$lhp->id}, No: {$lhp->nomor_lhp}) pada pengajuan {$pemeriksaan->id}.");
        } else {
            AuditLogger::log('UPDATE', 'LHP', "Memperbarui LHP (ID: {$lhp->id}, No: {$lhp->nomor_lhp}).");
        }

        if ($pemeriksaan->is_lhp_only_submission) {
            return redirect()->route('pemeriksaan.lhp.show', [$pemeriksaan, $lhp])
                ->with('success', 'Data LHP berhasil diperbarui. Setelah seluruh koreksi selesai, ajukan kembali dari menu Pengajuan Saya.');
        }

        return back()->with('success', 'Data Laporan Hasil Pemeriksaan (LHP) berhasil diperbarui.');
    }

    /**
     * Download or view LHP document.
     */
    public function download(Request $request, Pemeriksaan $pemeriksaan, Lhp $lhp)
    {
        $user = $request->user();

        // Muat relasi yang dipakai pada jalur ini. `$lhp` berasal dari route model
        // binding, sehingga relasinya belum dimuat.
        $lhp->loadMissing(['pemeriksaan.opd']);

        if (! $pemeriksaan->forUser($user)->where('id', $pemeriksaan->id)->exists()) {
            abort(403, 'Akses ditolak.');
        }

        if ($lhp->pemeriksaan_id !== $pemeriksaan->id) {
            abort(404);
        }

        if (! $lhp->dokumen_lhp) {
            return back()->with('error', 'Dokumen LHP tidak ditemukan.');
        }

        $disk = Storage::disk('local')->exists($lhp->dokumen_lhp) ? 'local' : (Storage::disk('public')->exists($lhp->dokumen_lhp) ? 'public' : null);

        if (! $disk) {
            return back()->with('error', 'Dokumen LHP tidak ditemukan di penyimpanan server.');
        }

        AuditLogger::log('DOWNLOAD', 'LHP', "Mengunduh dokumen LHP (No: {$lhp->nomor_lhp}).");

        $downloadFilename = str_replace(['/', '\\'], '_', $lhp->nama_dokumen_asli ?? $lhp->nama_dokumen_lhp_asli ?? ('LHP_' . str_replace(['/', '\\'], '_', $lhp->nomor_lhp) . '.pdf'));

        return Storage::disk($disk)->download(
            $lhp->dokumen_lhp,
            $downloadFilename
        );
    }

    /**
     * Remove the specified LHP from storage.
     */
    public function destroy(Request $request, Pemeriksaan $pemeriksaan, Lhp $lhp)
    {
        $user = $request->user();

        if ($user->isPemantau() || $user->isOpd()) {
            abort(403, 'Akses ditolak.');
        }

        if ($user->isAdminSumber() && $pemeriksaan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
            abort(403, 'Akses ditolak.');
        }

        if ($lhp->pemeriksaan_id !== $pemeriksaan->id) {
            abort(404);
        }

        if ($lhp->dokumen_lhp) {
            if (Storage::disk('local')->exists($lhp->dokumen_lhp)) {
                Storage::disk('local')->delete($lhp->dokumen_lhp);
            } elseif (Storage::disk('public')->exists($lhp->dokumen_lhp)) {
                Storage::disk('public')->delete($lhp->dokumen_lhp);
            }
        }

        AuditLogger::log('DELETE', 'LHP', "Menghapus LHP (ID: {$lhp->id}, No: {$lhp->nomor_lhp}).");

        $lhp->delete();

        return back()->with('success', 'Dokumen LHP berhasil dihapus.');
    }
}
