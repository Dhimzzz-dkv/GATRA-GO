<?php

namespace App\Http\Controllers;

use App\Models\Opd;
use App\Models\Pemeriksaan;
use App\Models\Temuan;
use App\Models\TahunAnggaran;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TemuanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $search = $request->input('search');
        $sumber = $request->input('sumber');
        $opdId = $request->input('opd_id');
        $tahun = $request->input('tahun');
        $statusPengajuan = $request->input('status_pengajuan');
        $statusPenyelesaian = $request->input('status_penyelesaian');
        $jenis = $request->input('jenis'); // 'administrasi', 'kerugian', 'keduanya'

        $query = Temuan::with(['pemeriksaan', 'lhp', 'opd', 'creator'])
            ->forUser($user)
            ->active()
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('judul_temuan', 'like', "%{$search}%")
                        ->orWhere('uraian_temuan', 'like', "%{$search}%")
                        ->orWhereHas('opd', function ($oq) use ($search) {
                            $oq->where('nama', 'like', "%{$search}%")
                               ->orWhere('kode', 'like', "%{$search}%");
                        });
                });
            })
            ->when($sumber, function ($q, $sumber) {
                $q->where('sumber_pemeriksaan', $sumber);
            })
            ->when($opdId, function ($q, $opdId) {
                $q->where('opd_id', $opdId);
            })
            ->when($tahun, function ($q, $tahun) {
                $q->where('tahun', $tahun);
            })
            ->when($statusPengajuan, function ($q, $statusPengajuan) {
                $q->where('status_pengajuan', $statusPengajuan);
            })
            ->when($statusPenyelesaian, function ($q, $statusPenyelesaian) {
                $q->where('status_penyelesaian', $statusPenyelesaian);
            })
            ->when($jenis, function ($q, $jenis) {
                if ($jenis === 'administrasi') {
                    $q->where('ada_unsur_administrasi', true);
                } elseif ($jenis === 'kerugian') {
                    $q->where('ada_unsur_kerugian', true);
                } elseif ($jenis === 'keduanya') {
                    $q->where('ada_unsur_administrasi', true)->where('ada_unsur_kerugian', true);
                }
            });

        // Summary Statistics for current filter
        $statsQuery = clone $query;
        $totalTemuan = $statsQuery->count();
        $totalNilaiKerugian = $statsQuery->where('ada_unsur_kerugian', true)->sum('nilai_kerugian');
        $totalAdministrasi = (clone $query)->where('ada_unsur_administrasi', true)->count();
        $totalKerugian = (clone $query)->where('ada_unsur_kerugian', true)->count();

        $temuans = $query->orderBy('tahun', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        $opds = Opd::active()->orderBy('nama')->get();
        $years = TahunAnggaran::allYears();
        $sumberList = ['BPK', 'BPKP', 'ITJEN', 'Inspektorat'];

        return view('temuan.index', compact(
            'temuans',
            'opds',
            'years',
            'sumberList',
            'totalTemuan',
            'totalNilaiKerugian',
            'totalAdministrasi',
            'totalKerugian',
            'search',
            'sumber',
            'opdId',
            'tahun',
            'statusPengajuan',
            'statusPenyelesaian',
            'jenis'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $user = $request->user();

        if ($user->isPemantau() || $user->isOpd()) {
            abort(403, 'Akses ditolak.');
        }

        $pemeriksaanId = $request->input('pemeriksaan_id');

        // Tampilkan semua pemeriksaan yang aktif
        $pemeriksaans = Pemeriksaan::forUser($user)
            ->active()
            ->with(['opd', 'lhps'])
            ->orderBy('tahun', 'desc')
            ->get();

        $selectedPemeriksaan = null;
        if ($pemeriksaanId) {
            // with('lhps.opds') wajib: view temuan.create membaca relasi lhps
            // (beserta OPD targetnya) untuk pilihan awal.
            $selectedPemeriksaan = Pemeriksaan::with(['opd', 'lhps.opds'])->findOrFail($pemeriksaanId);

            // Validasi kepemilikan sumber
            if ($user->isAdminSumber() && $selectedPemeriksaan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
                abort(403, 'Anda hanya dapat mengakses pemeriksaan untuk sumber ' . $user->getSumberPemeriksaan());
            }

            if ($selectedPemeriksaan->status_pengajuan === 'MENUNGGU PERSETUJUAN' && ! $user->isSuperAdmin()) {
                abort(403, 'Pemeriksaan sedang menunggu persetujuan.');
            }
        }

        $lhps = $selectedPemeriksaan ? $selectedPemeriksaan->lhps : collect();

        return view('temuan.create', compact('pemeriksaans', 'selectedPemeriksaan', 'lhps', 'pemeriksaanId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->isPemantau() || $user->isOpd()) {
            abort(403, 'Akses ditolak.');
        }

        if ($request->has('nilai_kerugian')) {
            $rawKerugian = (string) $request->input('nilai_kerugian');
            $cleaned = str_replace('.', '', str_replace(',', '.', $rawKerugian));
            $request->merge(['nilai_kerugian' => is_numeric($cleaned) ? (float) $cleaned : 0]);
        }

        $validated = $request->validate([
            'pemeriksaan_id' => ['required', 'exists:pemeriksaans,id'],
            'lhp_id' => ['nullable', 'exists:lhps,id'],
            'judul_temuan' => ['required', 'string', 'max:255'],
            'uraian_temuan' => ['required', 'string'],
            'penyebab' => ['nullable', 'string'],
            'akibat' => ['nullable', 'string'],
            'rekomendasi' => ['nullable', 'string'],
            'rekomendasi_items' => ['nullable', 'array'],
            'rekomendasi_items.*' => ['nullable', 'string'],
            'ada_unsur_administrasi' => ['nullable', 'boolean'],
            'uraian_administrasi' => ['nullable', 'string'],
            'ada_unsur_kerugian' => ['nullable', 'boolean'],
            'kategori_kerugian' => ['nullable', 'in:Kerugian Daerah,Kerugian Negara'],
            'jenis_kerugian' => ['nullable', 'in:Internal,Eksternal'],
            'nilai_kerugian' => ['nullable', 'numeric', 'min:0'],
            'uraian_kerugian' => ['nullable', 'string'],
            'dokumen_pendukung' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $pemeriksaan = Pemeriksaan::findOrFail($validated['pemeriksaan_id']);

        // Check if user has write access for this pemeriksaan source
        if ($user->isAdminSumber() && $pemeriksaan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
            abort(403, 'Anda hanya dapat menambahkan temuan untuk sumber pemeriksaan ' . $user->getSumberPemeriksaan());
        }

        if ($pemeriksaan->status_pengajuan === 'MENUNGGU PERSETUJUAN' && ! $user->isSuperAdmin()) {
            abort(403, 'Tidak dapat menambahkan temuan saat pemeriksaan sedang menunggu persetujuan.');
        }

        // Automatic synchronization from Pemeriksaan
        $validated['sumber_pemeriksaan'] = $pemeriksaan->sumber_pemeriksaan;
        $validated['tahun'] = $pemeriksaan->tahun;

        // Temuan wajib memiliki OPD karena kolom `temuans.opd_id` bersifat NOT NULL.
        //
        // Pemeriksaan dapat berdiri tanpa OPD tunggal (pola "LHP lintas OPD",
        // di mana target OPD dicatat pada pivot lhp_opd). Untuk kasus itu, OPD
        // temuan diambil dari OPD target LHP terkait.
        $opdId = $pemeriksaan->opd_id;

        if (empty($opdId) && ! empty($validated['lhp_id'])) {
            $opdId = \App\Models\Lhp::with('opds')->find($validated['lhp_id'])?->opds->first()?->id;
        }

        if (empty($opdId)) {
            return back()
                ->withInput()
                ->withErrors([
                    'opd_id' => 'Temuan tidak dapat disimpan: OPD target belum ditentukan. '
                        . 'Pemeriksaan induk tidak memiliki OPD utama, sehingga OPD harus dipilih pada LHP '
                        . '(centang minimal satu OPD target) sebelum menambahkan temuan.',
                ]);
        }

        $validated['opd_id'] = $opdId;

        // Flags & Defaults
        $validated['ada_unsur_administrasi'] = $request->has('ada_unsur_administrasi');
        $validated['ada_unsur_kerugian'] = $request->has('ada_unsur_kerugian');
        if ($validated['ada_unsur_kerugian']) {
            $validated['kategori_kerugian'] = $request->input('kategori_kerugian', 'Kerugian Daerah');
            $validated['jenis_kerugian'] = $request->input('jenis_kerugian');
            $validated['nilai_kerugian'] = $validated['nilai_kerugian'] ?? 0;
        } else {
            unset($validated['kategori_kerugian'], $validated['jenis_kerugian']);
            $validated['nilai_kerugian'] = 0;
        }

        $validated['status_pengajuan'] = in_array($pemeriksaan->status_pengajuan, ['DRAFT', 'PERLU REVISI', 'DITOLAK']) ? 'DRAFT' : 'DISETUJUI';
        $validated['status_penyelesaian'] = 'Belum Ditindaklanjuti';
        $validated['created_by'] = $user->id;
        $validated['updated_by'] = $user->id;

        if ($request->hasFile('dokumen_pendukung')) {
            $file = $request->file('dokumen_pendukung');
            $originalName = $file->getClientOriginalName();
            $path = $file->store('temuan', 'local');

            $validated['dokumen_pendukung'] = $path;
            $validated['nama_dokumen_asli'] = $originalName;
        }

        // Process Rekomendasi Bertahap for Unsur Administrasi
        $submittedItems = [];
        if ($validated['ada_unsur_administrasi']) {
            $rawItems = $request->input('rekomendasi_items', []);
            if (is_array($rawItems)) {
                foreach ($rawItems as $val) {
                    $t = trim((string) $val);
                    if ($t !== '') {
                        $submittedItems[] = $t;
                    }
                }
            }

            // Fallback: jika form lama/API/test mengirim string 'rekomendasi'
            if (empty($submittedItems) && !empty($validated['rekomendasi'])) {
                $submittedItems[] = trim($validated['rekomendasi']);
            }

            if (empty($submittedItems)) {
                throw ValidationException::withMessages([
                    'rekomendasi_items' => 'Minimal 1 butir rekomendasi harus diisi untuk temuan administrasi.',
                ]);
            }

            // Gabungkan butir-butir rekomendasi dengan penomoran rapi untuk kolom lama 'rekomendasi'
            $numbered = [];
            foreach ($submittedItems as $i => $itemText) {
                $numbered[] = preg_match('/^\d+[\.\)]\s*/', $itemText) ? $itemText : (($i + 1) . '. ' . $itemText);
            }
            $validated['rekomendasi'] = implode("\n", $numbered);
        }

        // `rekomendasi_items` adalah input perantara, BUKAN kolom database.
        // Isinya sudah diolah menjadi baris `rekomendasis` + kolom `rekomendasi`.
        // Bila ikut diteruskan ke Temuan::create(), akan melempar
        // MassAssignmentException karena tidak terdaftar di $fillable.
        unset($validated['rekomendasi_items']);

        $temuan = DB::transaction(function () use ($validated, $submittedItems, $user, $pemeriksaan) {
            $temuan = Temuan::create($validated);

            if ($validated['ada_unsur_administrasi'] && !empty($submittedItems)) {
                foreach ($submittedItems as $itemText) {
                    $temuan->rekomendasis()->create([
                        'uraian_rekomendasi' => $itemText,
                        'status_penyelesaian' => 'Belum Ditindaklanjuti',
                        'created_by' => $user->id,
                    ]);
                }
                $temuan->syncStatusPenyelesaianFromRekomendasi();
            }

            AuditLogger::logModelChange(
                'CREATE',
                $temuan,
                'TEMUAN',
                "Menambahkan Temuan baru (ID: {$temuan->id}): '{$temuan->judul_temuan}' pada Pemeriksaan {$pemeriksaan->nomor_spt}."
            );

            return $temuan;
        });

        return redirect()->route('temuan.show', $temuan)
            ->with('success', 'Data Temuan berhasil ditambahkan dan siap untuk ditindaklanjuti.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Temuan $temuan)
    {
        $user = $request->user();

        if (! $temuan->forUser($user)->where('id', $temuan->id)->exists()) {
            if ($user->isSuperAdmin() && $temuan->status_pengajuan === 'DRAFT') {
                abort(403, 'Akses ditolak: Data Temuan ini masih berstatus DRAFT pada Admin Sumber (' . $temuan->sumber_pemeriksaan . ') dan belum diajukan kepada Super Admin.');
            }
            if ($user->isAdminSumber() && $temuan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
                abort(403, 'Akses ditolak: Anda hanya memiliki hak akses untuk data temuan sumber ' . $user->getSumberPemeriksaan() . '.');
            }
            if ($user->isOpd() && $temuan->opd_id !== $user->opd_id) {
                abort(403, 'Akses ditolak: Data temuan ini bukan kewenangan OPD Anda.');
            }
            if ($user->isOpd() && $temuan->status_pengajuan !== 'DISETUJUI') {
                abort(403, 'Akses ditolak: Data temuan ini masih dalam proses internal pemeriksaan dan belum resmi disetujui Super Admin.');
            }

            abort(403, 'Akses ditolak.');
        }

        // Relasi bertingkat wajib di-eager load. Accessor User::official_name membaca
        // relasi pegawai, sehingga tanpa `*.pegawai` akan terjadi lazy loading yang
        // memicu exception di lingkungan produksi.
        $temuan->load([
            'pemeriksaan.opd',
            'lhp',
            'opd',
            'creator.pegawai',
            'updater.pegawai',
            'approver.pegawai',
            'rekomendasis.tindakLanjuts.opd',
            'rekomendasis.tindakLanjuts.creator.pegawai',
            'rekomendasis.creator.pegawai',
            'tindakLanjuts.creator.pegawai',
        ]);

        return view('temuan.show', compact('temuan'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Temuan $temuan)
    {
        $user = $request->user();

        if ($user->isPemantau() || $user->isOpd()) {
            abort(403, 'Akses ditolak.');
        }

        if ($user->isAdminSumber() && $temuan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
            abort(403, 'Akses ditolak.');
        }

        // View temuan.edit membaca $temuan->pemeriksaan dan $temuan->opd,
        // sehingga keduanya wajib dimuat lebih dulu.
        $temuan->load(['pemeriksaan.opd', 'pemeriksaan.lhps', 'opd', 'lhp']);

        $pemeriksaans = Pemeriksaan::forUser($user)->active()->with(['opd', 'lhps'])->get();
        $lhps = $temuan->pemeriksaan ? $temuan->pemeriksaan->lhps : collect();

        return view('temuan.edit', compact('temuan', 'pemeriksaans', 'lhps'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Temuan $temuan)
    {
        $user = $request->user();

        if ($user->isPemantau() || $user->isOpd()) {
            abort(403, 'Akses ditolak.');
        }

        if ($user->isAdminSumber() && $temuan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
            abort(403, 'Akses ditolak.');
        }

        if ($request->has('nilai_kerugian')) {
            $rawKerugian = (string) $request->input('nilai_kerugian');
            $cleaned = str_replace('.', '', str_replace(',', '.', $rawKerugian));
            $request->merge(['nilai_kerugian' => is_numeric($cleaned) ? (float) $cleaned : 0]);
        }

        $validated = $request->validate([
            'lhp_id' => ['nullable', 'exists:lhps,id'],
            'judul_temuan' => ['required', 'string', 'max:255'],
            'uraian_temuan' => ['required', 'string'],
            'penyebab' => ['nullable', 'string'],
            'akibat' => ['nullable', 'string'],
            'rekomendasi' => ['nullable', 'string'],
            'rekomendasi_items' => ['nullable', 'array'],
            'rekomendasi_items.*' => ['nullable', 'string'],
            'rekomendasi_ids' => ['nullable', 'array'],
            'rekomendasi_ids.*' => ['nullable', 'integer'],
            'ada_unsur_administrasi' => ['nullable', 'boolean'],
            'uraian_administrasi' => ['nullable', 'string'],
            'ada_unsur_kerugian' => ['nullable', 'boolean'],
            'kategori_kerugian' => ['nullable', 'in:Kerugian Daerah,Kerugian Negara'],
            'jenis_kerugian' => ['nullable', 'in:Internal,Eksternal'],
            'nilai_kerugian' => ['nullable', 'numeric', 'min:0'],
            'uraian_kerugian' => ['nullable', 'string'],
            'dokumen_pendukung' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $validated['ada_unsur_administrasi'] = $request->has('ada_unsur_administrasi');
        $validated['ada_unsur_kerugian'] = $request->has('ada_unsur_kerugian');
        if ($validated['ada_unsur_kerugian']) {
            $validated['kategori_kerugian'] = $request->input('kategori_kerugian', 'Kerugian Daerah');
            $validated['jenis_kerugian'] = $request->input('jenis_kerugian');
            $validated['nilai_kerugian'] = $validated['nilai_kerugian'] ?? 0;
        } else {
            unset($validated['kategori_kerugian'], $validated['jenis_kerugian']);
            $validated['nilai_kerugian'] = 0;
        }
        $validated['updated_by'] = $user->id;

        if ($request->hasFile('dokumen_pendukung')) {
            if ($temuan->dokumen_pendukung) {
                if (Storage::disk('local')->exists($temuan->dokumen_pendukung)) {
                    Storage::disk('local')->delete($temuan->dokumen_pendukung);
                } elseif (Storage::disk('public')->exists($temuan->dokumen_pendukung)) {
                    Storage::disk('public')->delete($temuan->dokumen_pendukung);
                }
            }

            $file = $request->file('dokumen_pendukung');
            $originalName = $file->getClientOriginalName();
            $path = $file->store('temuan', 'local');

            $validated['dokumen_pendukung'] = $path;
            $validated['nama_dokumen_asli'] = $originalName;
        }

        DB::transaction(function () use ($temuan, $validated, $request, $user) {
            if ($validated['ada_unsur_administrasi']) {
                $rawItems = $request->input('rekomendasi_items', []);
                $rawIds = $request->input('rekomendasi_ids', []);
                $submittedItems = [];

                if (is_array($rawItems)) {
                    foreach ($rawItems as $k => $val) {
                        $t = trim((string) $val);
                        $id = isset($rawIds[$k]) && is_numeric($rawIds[$k]) ? (int) $rawIds[$k] : null;
                        if ($t !== '') {
                            $submittedItems[] = [
                                'id' => $id,
                                'uraian' => $t,
                            ];
                        }
                    }
                }

                // Fallback: Jika form lama/API mengirim string 'rekomendasi'
                if (empty($submittedItems) && !empty($validated['rekomendasi'])) {
                    $submittedItems[] = [
                        'id' => null,
                        'uraian' => trim($validated['rekomendasi']),
                    ];
                }

                if (empty($submittedItems)) {
                    throw ValidationException::withMessages([
                        'rekomendasi_items' => 'Minimal 1 butir rekomendasi harus diisi untuk temuan administrasi.',
                    ]);
                }

                $existingReks = $temuan->rekomendasis()->get();
                $submittedIds = array_filter(array_column($submittedItems, 'id'));

                foreach ($existingReks as $existingRek) {
                    $hasTindakLanjut = $existingRek->tindakLanjuts()->exists() || $existingRek->status_penyelesaian !== 'Belum Ditindaklanjuti';

                    if (in_array($existingRek->id, $submittedIds)) {
                        $matching = collect($submittedItems)->firstWhere('id', $existingRek->id);
                        if ($matching && $matching['uraian'] !== $existingRek->uraian_rekomendasi) {
                            $existingRek->update(['uraian_rekomendasi' => $matching['uraian']]);
                        }
                    } else {
                        // Jangan hapus jika sudah ada tindak lanjut/riwayat verifikasi
                        if (! $hasTindakLanjut) {
                            $existingRek->delete();
                        }
                    }
                }

                // Tambahkan rekomendasi baru yang belum ada ID
                foreach ($submittedItems as $item) {
                    if (empty($item['id']) || ! $existingReks->contains('id', $item['id'])) {
                        $temuan->rekomendasis()->create([
                            'uraian_rekomendasi' => $item['uraian'],
                            'status_penyelesaian' => 'Belum Ditindaklanjuti',
                            'created_by' => $user->id,
                        ]);
                    }
                }

                $temuan->syncStatusPenyelesaianFromRekomendasi();

                // Sinkronkan ulang kolom 'rekomendasi' (lama) dengan penomoran rapi
                $allReks = $temuan->rekomendasis()->orderBy('id')->pluck('uraian_rekomendasi')->toArray();
                $numbered = [];
                foreach ($allReks as $i => $u) {
                    $trimmed = trim($u);
                    $numbered[] = preg_match('/^\d+[\.\)]\s*/', $trimmed) ? $trimmed : (($i + 1) . '. ' . $trimmed);
                }
                $validated['rekomendasi'] = implode("\n", $numbered);
            }

            // `rekomendasi_items` / `rekomendasi_ids` adalah input perantara,
            // bukan kolom database. Wajib dibuang sebelum update() agar tidak
            // melempar MassAssignmentException.
            unset($validated['rekomendasi_items'], $validated['rekomendasi_ids']);

            $oldAttributes = $temuan->getAttributes();
            $temuan->update($validated);

            AuditLogger::logModelChange(
                'UPDATE',
                $temuan,
                'TEMUAN',
                "Memperbarui data Temuan (ID: {$temuan->id}): '{$temuan->judul_temuan}'.",
                $oldAttributes
            );
        });

        return redirect()->route('temuan.show', $temuan)
            ->with('success', 'Data Temuan berhasil diperbarui.');
    }

    /**
     * Download or view finding supporting document.
     */
    public function downloadDokumen(Request $request, Temuan $temuan)
    {
        $user = $request->user();

        if (! $temuan->forUser($user)->where('id', $temuan->id)->exists()) {
            abort(403, 'Akses ditolak.');
        }

        if (! $temuan->dokumen_pendukung) {
            return back()->with('error', 'Dokumen pendukung temuan tidak ditemukan.');
        }

        $disk = Storage::disk('local')->exists($temuan->dokumen_pendukung) ? 'local' : (Storage::disk('public')->exists($temuan->dokumen_pendukung) ? 'public' : null);

        if (! $disk) {
            return back()->with('error', 'Dokumen pendukung temuan tidak ditemukan di penyimpanan server.');
        }

        AuditLogger::log('DOWNLOAD', 'TEMUAN', "Mengunduh dokumen pendukung temuan (ID: {$temuan->id}).");

        return Storage::disk($disk)->download(
            $temuan->dokumen_pendukung,
            $temuan->nama_dokumen_asli ?? 'Dokumen-Temuan-' . $temuan->id . '.pdf'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Temuan $temuan)
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && ! ($user->isAdminSumber() && $temuan->sumber_pemeriksaan === $user->getSumberPemeriksaan())) {
            abort(403, 'Akses ditolak.');
        }

        if ($temuan->dokumen_pendukung) {
            if (Storage::disk('local')->exists($temuan->dokumen_pendukung)) {
                Storage::disk('local')->delete($temuan->dokumen_pendukung);
            } elseif (Storage::disk('public')->exists($temuan->dokumen_pendukung)) {
                Storage::disk('public')->delete($temuan->dokumen_pendukung);
            }
        }

        $oldAttributes = $temuan->getAttributes();

        AuditLogger::logModelChange(
            'DELETE',
            $temuan,
            'TEMUAN',
            "Menghapus Temuan (ID: {$temuan->id}): '{$temuan->judul_temuan}'.",
            $oldAttributes
        );

        $temuan->delete();

        return redirect()->route('temuan.index')->with('success', 'Data Temuan berhasil dihapus.');
    }

    /**
     * Update cepat jenis kerugian (Internal / Eksternal) untuk temuan kerugian.
     */
    public function updateJenisKerugian(Request $request, Temuan $temuan)
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && ! ($user->isAdminSumber() && $temuan->sumber_pemeriksaan === $user->getSumberPemeriksaan())) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'jenis_kerugian' => ['required', 'in:Internal,Eksternal'],
        ]);

        $oldAttributes = $temuan->getAttributes();

        $temuan->update([
            'jenis_kerugian' => $validated['jenis_kerugian'],
            'updated_by' => $user->id,
        ]);

        AuditLogger::logModelChange(
            'UPDATE',
            $temuan,
            'TEMUAN',
            "Mengubah sub kategori kerugian temuan ID {$temuan->id} menjadi {$validated['jenis_kerugian']}.",
            $oldAttributes
        );

        return back()->with('success', "Kategori kerugian untuk '{$temuan->judul_temuan}' berhasil diatur menjadi {$validated['jenis_kerugian']}.");
    }
}
