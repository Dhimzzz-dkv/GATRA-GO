<?php

namespace App\Http\Controllers;

use App\Models\Opd;
use App\Models\Temuan;
use App\Models\TindakLanjut;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TindakLanjutController extends Controller
{
    /**
     * Display a listing of all follow-ups (Monitoring & Verification Center).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $search = $request->input('search');
        $statusVerifikasi = $request->input('status_verifikasi');
        $opdId = $request->input('opd_id');
        $sumber = $request->input('sumber');

        // Relasi untuk halaman indeks tindak lanjut.
        //
        // View memakai rantai fallback, sehingga SETIAP cabang harus tersedia:
        //   $temuan = $tl->temuan
        //   $lhp    = $temuan?->lhp       ?? $tl->lhp
        //   $pem    = $lhp?->pemeriksaan  ?? $temuan?->pemeriksaan
        //   OPD     = $tl->opd?->nama     ?? $temuan?->opd?->nama
        //
        // Melengkapi hanya salah satu cabang menyebabkan halaman tetap gagal
        // pada data yang memakai cabang lain (mis. temuan tanpa LHP, atau
        // temuan yang sudah dihapus tetapi masih dirujuk tindak lanjut).
        $query = TindakLanjut::with([
            'temuan.lhp.pemeriksaan',
            'temuan.pemeriksaan',
            'temuan.opd',
            'lhp.pemeriksaan',
            'opd',
            'creator.pegawai',
            'verifier.pegawai',
        ])
            ->forUser($user)
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('uraian_tindak_lanjut', 'like', "%{$search}%")
                        ->orWhere('nomor_bukti_setor', 'like', "%{$search}%")
                        ->orWhereHas('temuan', function ($tq) use ($search) {
                            $tq->where('nomor_temuan', 'like', "%{$search}%")
                               ->orWhere('judul_temuan', 'like', "%{$search}%");
                        })
                        ->orWhereHas('temuan.lhp', function ($lq) use ($search) {
                            $lq->where('nomor_lhp', 'like', "%{$search}%")
                               ->orWhere('judul_lhp', 'like', "%{$search}%");
                        });
                });
            })
            ->when($statusVerifikasi, function ($q, $statusVerifikasi) {
                $q->where('status_verifikasi', $statusVerifikasi);
            })
            ->when($opdId, function ($q, $opdId) {
                $q->where('opd_id', $opdId);
            })
            ->when($sumber, function ($q, $sumber) {
                $q->whereHas('temuan', function ($tq) use ($sumber) {
                    $tq->where('sumber_pemeriksaan', $sumber);
                });
            });

        $tindakLanjuts = $query->orderBy('tanggal_tindak_lanjut', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $opds = Opd::active()->orderBy('nama')->get();
        $sumberList = ['BPK', 'BPKP', 'ITJEN', 'Inspektorat'];

        // Temuan aktif yang dapat ditindaklanjuti oleh user saat ini
        $availableTemuans = Temuan::forUser($user)->active()
            ->whereIn('status_penyelesaian', ['Belum Ditindaklanjuti', 'Belum Sesuai', 'Menunggu Verifikasi'])
            ->with(['opd', 'lhp'])
            ->orderBy('tahun', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view('tindak_lanjut.index', compact('tindakLanjuts', 'opds', 'search', 'statusVerifikasi', 'opdId', 'sumber', 'sumberList', 'availableTemuans'));
    }

    /**
     * Store a newly created follow-up directly bound to a Temuan.
     */
    public function store(Request $request, Temuan $temuan)
    {
        $user = $request->user();

        // 1. Role Authorization: Pemantau is strictly read-only
        if ($user->isPemantau()) {
            abort(403, 'Akses ditolak. Peran Pemantau hanya memiliki hak akses untuk memantau data.');
        }

        // 2. OPD Authorization: OPD can only submit for their own OPD findings
        if ($user->isOpd()) {
            if (! $user->opd_id || $temuan->opd_id !== $user->opd_id) {
                abort(403, 'Akses ditolak. Anda hanya dapat menginput tindak lanjut untuk temuan yang ditujukan pada OPD Anda.');
            }
        }

        // 3. Source Isolation: Admin Sumber can only submit for their own source
        if ($user->isAdminSumber() && $temuan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
            abort(403, 'Akses ditolak. Anda hanya dapat menginput tindak lanjut untuk temuan sumber ' . $user->getSumberPemeriksaan());
        }

        // 4. Status Pengajuan: Temuan wajib berstatus DISETUJUI agar dapat ditindaklanjuti
        if ($temuan->status_pengajuan !== 'DISETUJUI') {
            abort(403, 'Akses ditolak. Tindak lanjut hanya dapat disampaikan untuk temuan yang telah resmi disetujui oleh Super Admin.');
        }

        // 5. Validasi Butir Rekomendasi Bertahap:
        // Jika Temuan sudah punya butir rekomendasi, tindak lanjut WAJIB menyertakan rekomendasi_id
        if ($temuan->rekomendasis()->exists() && ! $request->filled('rekomendasi_id')) {
            $errorMsg = 'Temuan ini sudah memiliki rincian Butir Rekomendasi. Silakan sampaikan tindak lanjut melalui butir rekomendasi terkait, bukan tindak lanjut umum.';
            return back()->with('error', $errorMsg)->withErrors(['rekomendasi_id' => $errorMsg]);
        }

        // Clean amount if provided
        if ($request->has('jumlah_setor')) {
            $rawSetor = (string) $request->input('jumlah_setor');
            $cleaned = str_replace('.', '', str_replace(',', '.', $rawSetor));
            $request->merge(['jumlah_setor' => is_numeric($cleaned) ? (float) $cleaned : 0]);
        }

        $validated = $request->validate([
            'tanggal_tindak_lanjut' => ['required', 'date'],
            'uraian_tindak_lanjut' => ['required', 'string'],
            'rekomendasi_id' => ['nullable', 'integer'],
            'jumlah_setor' => ['nullable', 'numeric', 'min:0'],
            'nomor_bukti_setor' => ['nullable', 'string', 'max:100'],
            'tanggal_setor' => ['nullable', 'date'],
            'dokumen_bukti' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:15360'],
        ], [
            'dokumen_bukti.required' => 'Dokumen/berkas bukti tindak lanjut wajib dilampirkan untuk memvalidasi tindak lanjut.',
            'dokumen_bukti.file' => 'Berkas bukti tindak lanjut harus berupa file yang valid.',
            'dokumen_bukti.mimes' => 'Format berkas bukti harus berupa PDF, JPG, JPEG, PNG, DOC, atau DOCX.',
            'dokumen_bukti.max' => 'Ukuran berkas bukti maksimal 15MB.',
        ]);

        $rekomendasi = null;
        if (! empty($validated['rekomendasi_id'])) {
            $rekomendasi = $temuan->rekomendasis()->where('id', $validated['rekomendasi_id'])->first();
            if (! $rekomendasi) {
                abort(403, 'Akses ditolak. Rekomendasi yang dipilih tidak valid atau bukan milik temuan ini.');
            }
        }

        $validated['temuan_id'] = $temuan->id;
        $validated['lhp_id'] = $temuan->lhp_id;
        $validated['opd_id'] = $temuan->opd_id;
        $validated['status_verifikasi'] = 'MENUNGGU VERIFIKASI';
        $validated['created_by'] = $user->id;

        // Khusus unsur Kerugian Daerah vs Administrasi
        if ($temuan->ada_unsur_kerugian) {
            $validated['jumlah_setor'] = $validated['jumlah_setor'] ?? 0;
            $validated['nomor_bukti_setor'] = $validated['nomor_bukti_setor'] ?? null;
            $validated['tanggal_setor'] = $validated['tanggal_setor'] ?? null;
        } else {
            $validated['jumlah_setor'] = 0;
            $validated['nomor_bukti_setor'] = null;
            $validated['tanggal_setor'] = null;
        }

        if ($request->hasFile('dokumen_bukti')) {
            $file = $request->file('dokumen_bukti');
            $originalName = $file->getClientOriginalName();
            $path = $file->store('tindak_lanjut', 'local');

            $validated['dokumen_bukti'] = $path;
            $validated['nama_dokumen_asli'] = $originalName;
        }

        // ---------------------------------------------------------------------
        // Transaksi: pembuatan tindak lanjut juga mengubah rekomendasi dan
        // status penyelesaian temuan. Bila salah satu langkah gagal, seluruh
        // operasi dibatalkan — termasuk menghapus berkas yang sudah diunggah,
        // agar tidak meninggalkan berkas yatim tanpa record.
        // ---------------------------------------------------------------------
        try {
            $tl = DB::transaction(function () use ($validated, $rekomendasi, $temuan) {
                $tl = TindakLanjut::create($validated);

                if ($rekomendasi) {
                    $rekomendasi->update(['status_penyelesaian' => 'Menunggu Verifikasi']);
                    $temuan->evaluateStatusPenyelesaian();
                } else {
                    // Otomatis ubah status penyelesaian temuan menjadi
                    // 'Menunggu Verifikasi' dengan evaluasi terpadu.
                    $oldStatus = $temuan->status_penyelesaian;

                    // Bypass override terminal status jika sedang mengajukan
                    // tindak lanjut baru.
                    if (in_array($oldStatus, ['Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti'])) {
                        $temuan->update([
                            'status_penyelesaian' => 'Menunggu Verifikasi',
                            'alasan_khusus' => null,
                            'dokumen_pendukung_alasan' => null,
                        ]);
                    } else {
                        $temuan->evaluateStatusPenyelesaian();
                    }
                }

                return $tl;
            }, 3);
        } catch (\Throwable $e) {
            // Bersihkan berkas yang sudah tersimpan agar tidak menjadi yatim.
            if (! empty($validated['dokumen_bukti'])) {
                Storage::disk('local')->delete($validated['dokumen_bukti']);
            }

            throw $e;
        }

        if ($rekomendasi) {
            AuditLogger::logModelChange(
                'CREATE',
                $tl,
                'TINDAK_LANJUT',
                "Menyampaikan tindak lanjut (ID: {$tl->id}) pada Rekomendasi (ID: {$rekomendasi->id}) Temuan (ID: {$temuan->id} - {$temuan->judul_temuan}). Status penyelesaian rekomendasi berubah menjadi 'Menunggu Verifikasi'."
            );
        } else {
            $newStatus = $temuan->fresh()->status_penyelesaian;

            AuditLogger::logModelChange(
                'CREATE',
                $tl,
                'TINDAK_LANJUT',
                "Menyampaikan tindak lanjut (ID: {$tl->id}) pada Temuan (ID: {$temuan->id} - {$temuan->judul_temuan}). Status penyelesaian dievaluasi menjadi '{$newStatus}'."
            );
        }

        return back()->with('success', 'Tindak lanjut berhasil disampaikan dan diajukan untuk verifikasi Super Admin.');
    }

    /**
     * Download follow-up proof document with authorization.
     */
    public function downloadBukti(Request $request, Temuan $temuan, TindakLanjut $tindakLanjut)
    {
        $user = $request->user();

        if (! TindakLanjut::forUser($user)->where('tindak_lanjuts.id', $tindakLanjut->id)->exists()) {
            abort(403, 'Akses ditolak.');
        }

        if (! $tindakLanjut->dokumen_bukti) {
            return back()->with('error', 'Dokumen bukti tindak lanjut tidak ditemukan.');
        }

        $disk = Storage::disk('local')->exists($tindakLanjut->dokumen_bukti) ? 'local' : (Storage::disk('public')->exists($tindakLanjut->dokumen_bukti) ? 'public' : null);

        if (! $disk) {
            return back()->with('error', 'File fisik dokumen tidak ditemukan di penyimpanan server.');
        }

        AuditLogger::log('DOWNLOAD', 'TINDAK_LANJUT', "Mengunduh berkas bukti tindak lanjut (ID: {$tindakLanjut->id}).");

        return Storage::disk($disk)->download(
            $tindakLanjut->dokumen_bukti,
            $tindakLanjut->nama_dokumen_asli ?? 'Bukti-Tindak-Lanjut-' . $tindakLanjut->id . '.pdf'
        );
    }

    /**
     * Super Admin verifies follow-up and automatically determines Temuan resolution status.
     */
    public function verifikasi(Request $request, Temuan $temuan, TindakLanjut $tindakLanjut)
    {
        $user = $request->user();

        if (! $user->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang memiliki kewenangan melakukan verifikasi tindak lanjut.');
        }

        $validated = $request->validate([
            'status_verifikasi' => [
                'required',
                Rule::in(['SESUAI', 'BELUM SESUAI', 'TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH']),
            ],
            'catatan_verifikasi' => [
                'nullable',
                'required_if:status_verifikasi,BELUM SESUAI',
                'string',
            ],
            'alasan_khusus' => [
                'nullable',
                'required_if:status_verifikasi,TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH',
                'string',
            ],
            'dokumen_pendukung_alasan' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx',
                'max:10240',
            ],
        ], [
            'catatan_verifikasi.required_if' => 'Catatan / petunjuk verifikasi wajib diisi jika hasil verifikasi Belum Sesuai.',
            'alasan_khusus.required_if' => 'Alasan khusus / pertimbangan hukum wajib diisi jika status tidak dapat ditindaklanjuti dengan alasan yang sah.',
        ]);

        $pathAlasan = null;
        if ($request->hasFile('dokumen_pendukung_alasan')) {
            $file = $request->file('dokumen_pendukung_alasan');
            $pathAlasan = $file->store('alasan_khusus', 'local');
        }

        $catatan = $validated['status_verifikasi'] === 'TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH'
            ? ($validated['alasan_khusus'] ?? null)
            : ($validated['catatan_verifikasi'] ?? null);

        $oldAttributes = $tindakLanjut->getAttributes();

        // ---------------------------------------------------------------------
        // Transaksi: verifikasi mengubah TIGA tabel sekaligus —
        // tindak_lanjuts, rekomendasis, dan temuans.
        //
        // Tanpa transaksi, kegagalan di tengah proses (mis. koneksi terputus
        // setelah update tindak lanjut tetapi sebelum evaluasi temuan)
        // meninggalkan status yang tidak sinkron secara permanen:
        // tindak lanjut berstatus SESUAI sementara temuan tetap
        // "Belum Ditindaklanjuti" — dan rekapitulasi menjadi tidak akurat.
        //
        // retry(3) menangani deadlock sesaat, umum terjadi pada MySQL.
        // ---------------------------------------------------------------------
        $newTemuanStatus = DB::transaction(function () use (
            $tindakLanjut,
            $temuan,
            $validated,
            $catatan,
            $user,
            $pathAlasan
        ) {
            $tindakLanjut->update([
                'status_verifikasi' => $validated['status_verifikasi'],
                'catatan_verifikasi' => $catatan,
                'verified_by' => $user->id,
                'verified_at' => now(),
            ]);

            // Jika tindak lanjut terkait pada Rekomendasi spesifik
            if ($tindakLanjut->rekomendasi_id) {
                $tindakLanjut->loadMissing('rekomendasi');
                $rekomendasi = $tindakLanjut->rekomendasi;

                if ($rekomendasi) {
                    $newRekStatus = match ($validated['status_verifikasi']) {
                        'SESUAI' => 'Sesuai',
                        'TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH' => 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah',
                        default => 'Belum Sesuai',
                    };

                    $rekUpdate = ['status_penyelesaian' => $newRekStatus];

                    if ($validated['status_verifikasi'] === 'TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH') {
                        $rekUpdate['alasan_khusus'] = $validated['alasan_khusus'];

                        if ($pathAlasan) {
                            $rekUpdate['dokumen_pendukung_alasan'] = $pathAlasan;
                        }
                    }

                    $rekomendasi->update($rekUpdate);
                }
            }

            // Force 'Tidak Dapat Ditindaklanjuti' bila diverifikasi demikian
            // untuk keseluruhan temuan (bukan per butir rekomendasi).
            if (! $tindakLanjut->rekomendasi_id
                && $validated['status_verifikasi'] === 'TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH') {
                $updateData = [
                    'status_penyelesaian' => 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah',
                    'alasan_khusus' => $validated['alasan_khusus'],
                ];

                if ($pathAlasan) {
                    $updateData['dokumen_pendukung_alasan'] = $pathAlasan;
                }

                $temuan->update($updateData);
            } else {
                // Evaluasi ulang terpadu
                $temuan->evaluateStatusPenyelesaian();
            }

            return $temuan->fresh()->status_penyelesaian;
        }, 3);

        // Audit log DI LUAR transaksi.
        // AuditLogger sudah memiliki try/catch internal; bila diletakkan di dalam
        // transaksi, kegagalan pencatatan log dapat membatalkan verifikasi yang
        // sebenarnya sah.
        AuditLogger::logModelChange(
            'VERIFY',
            $tindakLanjut,
            'TINDAK_LANJUT',
            "Super Admin memverifikasi tindak lanjut (ID: {$tindakLanjut->id}) dengan hasil '{$validated['status_verifikasi']}'. Status Penyelesaian Temuan (ID: {$temuan->id}) otomatis dievaluasi menjadi '{$newTemuanStatus}'.",
            $oldAttributes
        );

        return back()->with('success', "Verifikasi berhasil disimpan. Status Penyelesaian Temuan dievaluasi menjadi '{$newTemuanStatus}'.");
    }

    /**
     * Remove the specified follow-up.
     */
    public function destroy(Request $request, Temuan $temuan, TindakLanjut $tindakLanjut)
    {
        $user = $request->user();

        if ($user->isPemantau()) {
            abort(403, 'Akses ditolak.');
        }

        if ($user->isOpd()) {
            // OPD can only delete their own follow-up while still pending verification
            if ($tindakLanjut->created_by !== $user->id || $tindakLanjut->status_verifikasi !== 'MENUNGGU VERIFIKASI') {
                abort(403, 'Akses ditolak. Anda hanya dapat menghapus tindak lanjut yang Anda buat sendiri dan belum diverifikasi.');
            }
        }

        if ($user->isAdminSumber() && $temuan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
            abort(403, 'Akses ditolak.');
        }

        if (! $user->isSuperAdmin() && ! $user->isOpd() && $tindakLanjut->status_verifikasi !== 'MENUNGGU VERIFIKASI') {
            abort(403, 'Tindak lanjut yang sudah diverifikasi tidak dapat dihapus.');
        }

        // Simpan path berkas TANPA menghapusnya.
        //
        // Urutan lama menghapus berkas LEBIH DULU, lalu menghapus record.
        // Bila operasi database gagal, berkas bukti setor hilang permanen
        // sementara record-nya masih ada — bukti yang tidak dapat dipulihkan.
        //
        // Prinsip yang benar: DATABASE DULU, BERKAS BELAKANGAN.
        // Berkas yatim (sisa) jauh lebih ringan dampaknya daripada record
        // tanpa bukti.
        $berkasUntukDihapus = $tindakLanjut->dokumen_bukti;

        $oldAttributes = $tindakLanjut->getAttributes();

        // ---------------------------------------------------------------------
        // Transaksi: penghapusan tindak lanjut juga mengubah rekomendasi dan
        // status temuan. Ketiganya harus berhasil bersama atau tidak sama sekali.
        // ---------------------------------------------------------------------
        DB::transaction(function () use ($tindakLanjut, $temuan) {
            $tindakLanjut->loadMissing('rekomendasi');
            $rekomendasi = $tindakLanjut->rekomendasi;

            $tindakLanjut->delete();

            if ($rekomendasi) {
                if ($rekomendasi->tindakLanjuts()->where('status_verifikasi', 'MENUNGGU VERIFIKASI')->exists()) {
                    $rekomendasi->update(['status_penyelesaian' => 'Menunggu Verifikasi']);
                } elseif ($rekomendasi->tindakLanjuts()->where('status_verifikasi', 'SESUAI')->exists()) {
                    $rekomendasi->update(['status_penyelesaian' => 'Sesuai']);
                } elseif ($rekomendasi->tindakLanjuts()->where('status_verifikasi', 'BELUM SESUAI')->exists()) {
                    $rekomendasi->update(['status_penyelesaian' => 'Belum Sesuai']);
                } else {
                    $rekomendasi->update(['status_penyelesaian' => 'Belum Ditindaklanjuti']);
                }
            }

            // Selalu evaluasi ulang temuan jika ada tindak lanjut yang dihapus
            $temuan->evaluateStatusPenyelesaian();
        }, 3);

        // Baru setelah transaksi sukses, hapus berkas fisik.
        if ($berkasUntukDihapus) {
            if (Storage::disk('local')->exists($berkasUntukDihapus)) {
                Storage::disk('local')->delete($berkasUntukDihapus);
            } elseif (Storage::disk('public')->exists($berkasUntukDihapus)) {
                Storage::disk('public')->delete($berkasUntukDihapus);
            }
        }

        AuditLogger::logModelChange('DELETE', $tindakLanjut, 'TINDAK_LANJUT', "Menghapus tindak lanjut (ID: {$tindakLanjut->id}) dari temuan (ID: {$temuan->id}).", $oldAttributes);

        return back()->with('success', 'Data tindak lanjut berhasil dihapus.');
    }
}
