<?php

namespace App\Services;

use App\Models\Lhp;
use App\Models\Pegawai;
use App\Models\Pemeriksaan;
use App\Models\Temuan;
use App\Models\TindakLanjut;
use App\Models\TimPemeriksa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class PemeriksaanService
{
    /**
     * Handle the creation of a new Pemeriksaan along with Tim, LHP, Temuan, and Tindak Lanjut.
     *
     * @param array $validated Validated request data.
     * @param array $files Uploaded files from request (dokumen_spt, lhps.*.dokumen_lhp, etc.)
     * @param User $user Authenticated user performing the action.
     * @return Pemeriksaan
     */
    public function createPemeriksaan(array $validated, array $files, User $user): Pemeriksaan
    {
        return DB::transaction(function () use ($validated, $files, $user) {
            // Tentukan primary OPD (jika ada) untuk fallback
            $primaryOpdId = $validated['opd_id'] ?? null;
            if (! $primaryOpdId && !empty($validated['lhps'][0]['opd_ids'][0])) {
                $primaryOpdId = $validated['lhps'][0]['opd_ids'][0];
            } elseif (! $primaryOpdId && !empty($validated['lhps'][0]['opd_id'])) {
                $primaryOpdId = $validated['lhps'][0]['opd_id'];
            }

            $action = request()->input('action') ?? ($validated['action'] ?? null);
            if ($user->isSuperAdmin()) {
                $statusPengajuan = 'DISETUJUI';
            } elseif ($action === 'submit' || (isset($validated['status_pengajuan']) && $validated['status_pengajuan'] === 'MENUNGGU PERSETUJUAN')) {
                $statusPengajuan = 'MENUNGGU PERSETUJUAN';
            } elseif ($action === 'publish' || (isset($validated['status_pengajuan']) && $validated['status_pengajuan'] === 'DISETUJUI')) {
                $statusPengajuan = 'DISETUJUI';
            } else {
                $statusPengajuan = 'DRAFT';
            }

            $pemeriksaanData = [
                'sumber_pemeriksaan' => $validated['sumber_pemeriksaan'],
                'jenis_pengajuan' => Pemeriksaan::JENIS_PENGAJUAN_SPT_DAN_LHP,
                'opd_id' => $primaryOpdId,
                'tahun' => $validated['tahun'],
                'judul' => $validated['judul'] ?? null,
                'nomor_spt' => $validated['nomor_spt'] ?? null,
                'tanggal_spt' => $validated['tanggal_spt'] ?? null,
                'tanggal_mulai' => $validated['tanggal_mulai'] ?? null,
                'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
                'created_by' => $user->id,
                'status_pengajuan' => $statusPengajuan,
            ];

            if (isset($files['dokumen_spt']) && $files['dokumen_spt'] instanceof UploadedFile) {
                $file = $files['dokumen_spt'];
                $pemeriksaanData['dokumen_spt'] = $file->store('spt', 'local');
                $pemeriksaanData['nama_dokumen_spt_asli'] = $file->getClientOriginalName();
            }

            $pemeriksaan = Pemeriksaan::create($pemeriksaanData);

            AuditLogger::log(
                'CREATE',
                'PEMERIKSAAN',
                "Membuat data Pemeriksaan {$pemeriksaan->sumber_pemeriksaan} (SPT: {$pemeriksaan->nomor_spt})."
            );

            $this->saveTeamMembers($pemeriksaan, $validated['tim_pemeriksa'] ?? []);

            // Map untuk menampung LHP yang berhasil dibuat
            $createdLhps = [];

            // 1. Simpan dari array lhps jika ada
            if (!empty($validated['lhps']) && is_array($validated['lhps'])) {
                foreach ($validated['lhps'] as $idx => $lhpInput) {
                    if (! empty($lhpInput['nomor_lhp'])) {
                        $lhpData = [
                            'pemeriksaan_id' => $pemeriksaan->id,
                            'nomor_lhp' => $lhpInput['nomor_lhp'],
                            'tanggal_lhp' => $lhpInput['tanggal_lhp'] ?? ($pemeriksaan->tanggal_spt ?? now()->format('Y-m-d')),
                            'judul_lhp' => $lhpInput['judul_lhp'] ?: ($pemeriksaan->judul ?: "LHP {$pemeriksaan->nomor_spt}"),
                            'ringkasan' => $lhpInput['ringkasan'] ?? null,
                            'created_by' => $user->id,
                        ];

                        if (isset($files["lhps_{$idx}_dokumen_lhp"]) && $files["lhps_{$idx}_dokumen_lhp"] instanceof UploadedFile) {
                            $fileLhp = $files["lhps_{$idx}_dokumen_lhp"];
                            $lhpData['dokumen_lhp'] = $fileLhp->store('lhp', 'local');
                            $lhpData['nama_dokumen_lhp_asli'] = $fileLhp->getClientOriginalName();
                        }

                        $lhp = Lhp::create($lhpData);
                        $createdLhps[$idx] = $lhp;

                        // Simpan relasi Many-to-Many ke tabel pivot lhp_opd
                        $selectedOpdIds = [];
                        if (! empty($lhpInput['opd_ids']) && is_array($lhpInput['opd_ids'])) {
                            $selectedOpdIds = array_unique(array_filter($lhpInput['opd_ids']));
                        } elseif (! empty($lhpInput['opd_id'])) {
                            $selectedOpdIds = [$lhpInput['opd_id']];
                        }

                        if (! empty($selectedOpdIds)) {
                            $lhp->opds()->sync($selectedOpdIds);
                        }

                        AuditLogger::log(
                            'CREATE',
                            'LHP',
                            "Menambahkan LHP {$lhp->nomor_lhp} bersamaan pada SPT {$pemeriksaan->nomor_spt}."
                        );

                        $opdTargetId = ! empty($selectedOpdIds) ? $selectedOpdIds[0] : ($pemeriksaan->opd_id ?? null);

                        // A. Simpan Temuan Administrasi (Multi-Temuan) jika diinput
                        if (! empty($lhpInput['temuans_administrasi']) && is_array($lhpInput['temuans_administrasi'])) {
                            foreach ($lhpInput['temuans_administrasi'] as $admIdx => $admInput) {
                                if (! empty($admInput['uraian']) || ! empty($admInput['rekomendasi']) || ! empty($admInput['rekomendasi_items'])) {
                                    $uraianAdm = $admInput['uraian'] ?? 'Temuan Administrasi';
                                    
                                    $items = [];
                                    if (! empty($admInput['rekomendasi_items']) && is_array($admInput['rekomendasi_items'])) {
                                        foreach ($admInput['rekomendasi_items'] as $val) {
                                            $t = trim((string) $val);
                                            if ($t !== '') {
                                                $items[] = $t;
                                            }
                                        }
                                    } elseif (! empty($admInput['rekomendasi'])) {
                                        $items = $this->parseRekomendasiText($admInput['rekomendasi']);
                                    }

                                    $rekAdm = ! empty($admInput['rekomendasi']) ? $admInput['rekomendasi'] : 'Agar ditindaklanjuti dan dilengkapi sesuai ketentuan perundang-undangan.';
                                    if (! empty($items)) {
                                        $numbered = [];
                                        foreach ($items as $i => $u) {
                                            $numbered[] = preg_match('/^\d+[\.\)]\s*/', $u) ? $u : (($i + 1) . '. ' . $u);
                                        }
                                        $rekAdm = implode("\n", $numbered);
                                    }

                                    $admTemuan = Temuan::create([
                                        'pemeriksaan_id' => $pemeriksaan->id,
                                        'lhp_id' => $lhp->id,
                                        'sumber_pemeriksaan' => $pemeriksaan->sumber_pemeriksaan,
                                        'opd_id' => $opdTargetId,
                                        'tahun' => $pemeriksaan->tahun,
                                        'judul_temuan' => 'Temuan Administrasi #' . ($admIdx + 1) . ': ' . Str::limit($uraianAdm, 40),
                                        'uraian_temuan' => $uraianAdm,
                                        'ada_unsur_administrasi' => true,
                                        'uraian_administrasi' => $uraianAdm,
                                        'penyebab' => ! empty($admInput['penyebab']) ? trim($admInput['penyebab']) : null,
                                        'ada_unsur_kerugian' => false,
                                        'nilai_kerugian' => 0,
                                        'uraian_kerugian' => null,
                                        'rekomendasi' => $rekAdm,
                                        'status_pengajuan' => $statusPengajuan,
                                        'status_penyelesaian' => 'Belum Ditindaklanjuti',
                                        'created_by' => $user->id,
                                    ]);

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

                        // B. Simpan Temuan Kerugian jika diinput (Multi-Temuan Dinamis atau Single Fallback)
                        $kerugianList = [];
                        if (! empty($lhpInput['temuans_kerugian']) && is_array($lhpInput['temuans_kerugian'])) {
                            $kerugianList = $lhpInput['temuans_kerugian'];
                        } elseif (! empty($lhpInput['temuan_kerugian']) && is_array($lhpInput['temuan_kerugian'])) {
                            $kerugianList = [$lhpInput['temuan_kerugian']];
                        }

                        foreach ($kerugianList as $kgIdx => $kgInput) {
                            $kategoriKg = ! empty($kgInput['kategori_kerugian']) ? $kgInput['kategori_kerugian'] : 'Kerugian Daerah';
                            $nilaiKerugian = ! empty($kgInput['nilai_kerugian']) ? (float) str_replace(['.', ','], ['', '.'], $kgInput['nilai_kerugian']) : 0;
                            if ($nilaiKerugian > 0 || ! empty($kgInput['uraian']) || ! empty($kgInput['rekomendasi'])) {
                                $uraianKg = $kgInput['uraian'] ?? ("{$kategoriKg} pada LHP " . $lhp->nomor_lhp);
                                $kasTujuan = $kategoriKg === 'Kerugian Negara' ? 'Kas Negara' : 'Kas Daerah';
                                $nomorUrutLabel = count($kerugianList) > 1 ? ' #' . ($kgIdx + 1) : '';
                                Temuan::create([
                                    'pemeriksaan_id' => $pemeriksaan->id,
                                    'lhp_id' => $lhp->id,
                                    'sumber_pemeriksaan' => $pemeriksaan->sumber_pemeriksaan,
                                    'opd_id' => $opdTargetId,
                                    'tahun' => $pemeriksaan->tahun,
                                    'judul_temuan' => "Temuan {$kategoriKg}{$nomorUrutLabel}: " . Str::limit($uraianKg, 40),
                                    'uraian_temuan' => $uraianKg,
                                    'ada_unsur_administrasi' => false,
                                    'uraian_administrasi' => null,
                                    'ada_unsur_kerugian' => true,
                                    'kategori_kerugian' => $kategoriKg,
                                    'jenis_kerugian' => $kgInput['jenis_kerugian'] ?? null,
                                    'nilai_kerugian' => $nilaiKerugian,
                                    'uraian_kerugian' => $nilaiKerugian > 0 ? "{$kategoriKg} sebesar Rp " . number_format($nilaiKerugian, 0, ',', '.') : null,
                                    'penyebab' => ! empty($kgInput['penyebab']) ? trim($kgInput['penyebab']) : null,
                                    'rekomendasi' => $kgInput['rekomendasi'] ?? "Agar menyetorkan kerugian ke {$kasTujuan} sesuai ketentuan.",
                                    'status_pengajuan' => $statusPengajuan,
                                    'status_penyelesaian' => 'Belum Ditindaklanjuti',
                                    'created_by' => $user->id,
                                ]);
                            }
                        }

                        // C. Fallback: Array temuans generik (backward compatibility)
                        if (empty($lhpInput['temuans_administrasi']) && empty($lhpInput['temuan_kerugian']) && empty($lhpInput['temuans_kerugian']) && ! empty($lhpInput['temuans']) && is_array($lhpInput['temuans'])) {
                            foreach ($lhpInput['temuans'] as $tInput) {
                                $nilaiKerugian = ! empty($tInput['nilai_kerugian']) ? (float) str_replace(['.', ','], ['', '.'], $tInput['nilai_kerugian']) : 0;
                                $adaKerugian = $nilaiKerugian > 0;
                                $adaAdmin = ! empty($tInput['unsur_administrasi']);

                                if ($adaKerugian || $adaAdmin || ! empty($tInput['judul_temuan']) || ! empty($tInput['rekomendasi'])) {
                                    Temuan::create([
                                        'pemeriksaan_id' => $pemeriksaan->id,
                                        'lhp_id' => $lhp->id,
                                        'sumber_pemeriksaan' => $pemeriksaan->sumber_pemeriksaan,
                                        'opd_id' => $opdTargetId,
                                        'tahun' => $pemeriksaan->tahun,
                                        'judul_temuan' => $tInput['judul_temuan'] ?: ($lhp->judul_lhp ?? "Temuan LHP {$lhp->nomor_lhp}"),
                                        'uraian_temuan' => $tInput['uraian_temuan'] ?? ($lhpInput['ringkasan'] ?? ($lhp->judul_lhp ?? 'Uraian temuan')),
                                        'ada_unsur_administrasi' => $adaAdmin,
                                        'uraian_administrasi' => $tInput['unsur_administrasi'] ?? null,
                                        'ada_unsur_kerugian' => $adaKerugian,
                                        'nilai_kerugian' => $nilaiKerugian,
                                        'uraian_kerugian' => $adaKerugian ? "Kerugian daerah sebesar Rp " . number_format($nilaiKerugian, 0, ',', '.') : null,
                                        'rekomendasi' => $tInput['rekomendasi'] ?? 'Agar ditindaklanjuti sesuai ketentuan peraturan perundang-undangan.',
                                        'status_pengajuan' => $statusPengajuan,
                                        'status_penyelesaian' => 'Belum Ditindaklanjuti',
                                        'created_by' => $user->id,
                                    ]);
                                }
                            }
                        } elseif (empty($lhpInput['temuans_administrasi']) && empty($lhpInput['temuan_kerugian'])) {
                            // D. Fallback data level LHP langsung (legacy)
                            $nilaiKerugian = ! empty($lhpInput['nilai_kerugian']) ? (float) str_replace(['.', ','], ['', '.'], $lhpInput['nilai_kerugian']) : 0;
                            $adaKerugian = $nilaiKerugian > 0;
                            $adaAdmin = ! empty($lhpInput['unsur_administrasi']);

                            if ($adaKerugian || $adaAdmin || ! empty($lhpInput['rekomendasi'])) {
                                Temuan::create([
                                    'pemeriksaan_id' => $pemeriksaan->id,
                                    'lhp_id' => $lhp->id,
                                    'sumber_pemeriksaan' => $pemeriksaan->sumber_pemeriksaan,
                                    'opd_id' => $opdTargetId,
                                    'tahun' => $pemeriksaan->tahun,
                                    'judul_temuan' => $lhp->judul_lhp ?? "Temuan LHP {$lhp->nomor_lhp}",
                                    'uraian_temuan' => $lhpInput['ringkasan'] ?? ($lhp->judul_lhp ?? 'Uraian temuan'),
                                    'ada_unsur_administrasi' => $adaAdmin,
                                    'uraian_administrasi' => $lhpInput['unsur_administrasi'] ?? null,
                                    'ada_unsur_kerugian' => $adaKerugian,
                                    'nilai_kerugian' => $nilaiKerugian,
                                    'uraian_kerugian' => $adaKerugian ? "Kerugian daerah sebesar Rp " . number_format($nilaiKerugian, 0, ',', '.') : null,
                                    'rekomendasi' => $lhpInput['rekomendasi'] ?? 'Agar ditindaklanjuti sesuai ketentuan peraturan perundang-undangan.',
                                    'status_pengajuan' => $statusPengajuan,
                                    'status_penyelesaian' => 'Belum Ditindaklanjuti',
                                    'created_by' => $user->id,
                                ]);
                            }
                        }
                    }
                }
            }

            // 2. Fallback single LHP input
            if (empty($createdLhps) && !empty($validated['nomor_lhp'])) {
                $lhpData = [
                    'pemeriksaan_id' => $pemeriksaan->id,
                    'nomor_lhp' => $validated['nomor_lhp'],
                    'tanggal_lhp' => $validated['tanggal_lhp'] ?? ($validated['tanggal_spt'] ?? now()->format('Y-m-d')),
                    'judul_lhp' => $validated['judul_lhp'] ?: ($pemeriksaan->judul ?: "LHP {$pemeriksaan->nomor_spt}"),
                    'ringkasan' => $validated['ringkasan_lhp'] ?? null,
                    'created_by' => $user->id,
                ];

                if (isset($files['dokumen_lhp']) && $files['dokumen_lhp'] instanceof UploadedFile) {
                    $fileLhp = $files['dokumen_lhp'];
                    $lhpData['dokumen_lhp'] = $fileLhp->store('lhp', 'local');
                    $lhpData['nama_dokumen_lhp_asli'] = $fileLhp->getClientOriginalName();
                }

                $lhp = Lhp::create($lhpData);
                $createdLhps[0] = $lhp;

                if ($primaryOpdId) {
                    $lhp->opds()->sync([$primaryOpdId]);
                }

                AuditLogger::log(
                    'CREATE',
                    'LHP',
                    "Menambahkan LHP {$lhp->nomor_lhp} bersamaan pada SPT {$pemeriksaan->nomor_spt}."
                );
            }

            // 3. Simpan Tindak Lanjut jika ada
            if (!empty($validated['tindak_lanjuts']) && is_array($validated['tindak_lanjuts'])) {
                foreach ($validated['tindak_lanjuts'] as $tlIdx => $tlInput) {
                    if (! empty($tlInput['uraian_tindak_lanjut']) || ! empty($tlInput['jumlah_setor']) || ! empty($tlInput['nomor_bukti_setor'])) {
                        $targetLhpIndex = $tlInput['lhp_index'] ?? 0;
                        $targetLhp = $createdLhps[$targetLhpIndex] ?? ($pemeriksaan->lhps()->first() ?? null);

                        $jumlahSetor = ! empty($tlInput['jumlah_setor']) ? (float) str_replace(['.', ','], ['', '.'], $tlInput['jumlah_setor']) : 0;

                        $tlOpdId = $targetLhp?->opds()->first()?->id ?? ($pemeriksaan->opd_id ?? null);

                        // Catatan: tabel `tindak_lanjuts` TIDAK memiliki kolom
                        // `pemeriksaan_id`. Keterkaitan ke pemeriksaan diperoleh
                        // melalui `lhp.pemeriksaan_id` atau `temuan.pemeriksaan_id`.
                        // Sebelumnya baris ini menulis `pemeriksaan_id` dan nilainya
                        // terbuang diam-diam.
                        $tlData = [
                            'lhp_id' => $targetLhp?->id,
                            'opd_id' => $tlOpdId,
                            'temuan_id' => $targetLhp?->temuans()->first()?->id,
                            'tanggal_tindak_lanjut' => $tlInput['tanggal_tindak_lanjut'] ?? ($pemeriksaan->tanggal_spt ?? now()->format('Y-m-d')),
                            'uraian_tindak_lanjut' => $tlInput['uraian_tindak_lanjut'] ?? 'Tindak lanjut kegiatan pemeriksaan',
                            'nomor_bukti_setor' => $tlInput['nomor_bukti_setor'] ?? null,
                            'tanggal_setor' => $tlInput['tanggal_setor'] ?? null,
                            'jumlah_setor' => $jumlahSetor,
                            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
                            'created_by' => $user->id,
                        ];

                        if (isset($files["tindak_lanjuts_{$tlIdx}_dokumen_bukti"]) && $files["tindak_lanjuts_{$tlIdx}_dokumen_bukti"] instanceof UploadedFile) {
                            $fileTl = $files["tindak_lanjuts_{$tlIdx}_dokumen_bukti"];
                            $tlData['dokumen_bukti'] = $fileTl->store('tindak_lanjut', 'local');
                            $tlData['nama_dokumen_asli'] = $fileTl->getClientOriginalName();
                        }

                        $tl = TindakLanjut::create($tlData);

                        AuditLogger::log(
                            'CREATE',
                            'TINDAK_LANJUT',
                            "Menambahkan Tindak Lanjut pada LHP: " . ($targetLhp?->nomor_lhp ?? '-') . " (SPT: {$pemeriksaan->nomor_spt})."
                        );
                    }
                }
            }
            
            $pemeriksaan->lhp_count_for_message = count($createdLhps);

            return $pemeriksaan;
        });
    }

    /**
     * Handle the update of Pemeriksaan along with related entities.
     *
     * @param Pemeriksaan $pemeriksaan Existing model.
     * @param array $validated Validated request data.
     * @param array $files Uploaded files from request.
     * @param User $user Authenticated user.
     * @return Pemeriksaan
     */
    public function updatePemeriksaan(Pemeriksaan $pemeriksaan, array $validated, array $files, User $user): Pemeriksaan
    {
        return DB::transaction(function () use ($validated, $files, $pemeriksaan, $user) {
            $pemeriksaanData = [
                'sumber_pemeriksaan' => $validated['sumber_pemeriksaan'],
                'opd_id' => $validated['opd_id'] ?? $pemeriksaan->opd_id,
                'tahun' => $validated['tahun'],
                'judul' => $validated['judul'] ?? null,
                'nomor_spt' => $validated['nomor_spt'] ?? null,
                'tanggal_spt' => $validated['tanggal_spt'] ?? null,
                'nama_tim' => $validated['nama_tim'] ?? $pemeriksaan->nama_tim,
                'tanggal_mulai' => $validated['tanggal_mulai'] ?? null,
                'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
            ];

            if (isset($files['dokumen_spt']) && $files['dokumen_spt'] instanceof UploadedFile) {
                if ($pemeriksaan->dokumen_spt && Storage::disk('local')->exists($pemeriksaan->dokumen_spt)) {
                    Storage::disk('local')->delete($pemeriksaan->dokumen_spt);
                }

                $file = $files['dokumen_spt'];
                $pemeriksaanData['dokumen_spt'] = $file->store('spt', 'local');
                $pemeriksaanData['nama_dokumen_spt_asli'] = $file->getClientOriginalName();
            }

            $oldAttributes = $pemeriksaan->getAttributes();
            $pemeriksaan->update($pemeriksaanData);

            // Penanggung Jawab selalu disinkronkan dengan Inspektur aktif.
            if (isset($validated['tim_pemeriksa']) && is_array($validated['tim_pemeriksa'])) {
                $pemeriksaan->timPemeriksas()->delete();
                $this->saveTeamMembers($pemeriksaan, $validated['tim_pemeriksa']);
            }

            AuditLogger::logModelChange(
                'UPDATE',
                $pemeriksaan,
                'PEMERIKSAAN',
                "Memperbarui data SPT (ID: {$pemeriksaan->id}, No: {$pemeriksaan->nomor_spt}).",
                $oldAttributes
            );

            // 1. Process Multi-LHP (Array)
            if (isset($validated['lhps']) && is_array($validated['lhps'])) {
                $submittedLhpIds = [];

                foreach ($validated['lhps'] as $lhpIdx => $lhpInput) {
                    if (empty($lhpInput['nomor_lhp'])) {
                        continue;
                    }

                    $lhp = null;
                    if (! empty($lhpInput['id'])) {
                        $lhp = Lhp::where('pemeriksaan_id', $pemeriksaan->id)->find($lhpInput['id']);
                    }

                    $lhpData = [
                        'pemeriksaan_id' => $pemeriksaan->id,
                        'nomor_lhp' => $lhpInput['nomor_lhp'],
                        'tanggal_lhp' => $lhpInput['tanggal_lhp'] ?? ($pemeriksaan->tanggal_spt ?? now()->format('Y-m-d')),
                        'judul_lhp' => $lhpInput['judul_lhp'] ?: ($pemeriksaan->judul ?: "LHP {$pemeriksaan->nomor_spt}"),
                        'ringkasan' => $lhpInput['ringkasan'] ?? null,
                    ];

                    if (isset($files["lhps_{$lhpIdx}_dokumen_lhp"]) && $files["lhps_{$lhpIdx}_dokumen_lhp"] instanceof UploadedFile) {
                        if ($lhp && $lhp->dokumen_lhp && Storage::disk('local')->exists($lhp->dokumen_lhp)) {
                            Storage::disk('local')->delete($lhp->dokumen_lhp);
                        }
                        $fileLhp = $files["lhps_{$lhpIdx}_dokumen_lhp"];
                        $lhpData['dokumen_lhp'] = $fileLhp->store('lhp', 'local');
                        $lhpData['nama_dokumen_lhp_asli'] = $fileLhp->getClientOriginalName();
                    }

                    if ($lhp) {
                        $lhp->update($lhpData);
                    } else {
                        $lhpData['created_by'] = $user->id;
                        $lhp = Lhp::create($lhpData);
                    }

                    $submittedLhpIds[] = $lhp->id;

                    // Sync OPDs
                    if (isset($lhpInput['opd_ids']) && is_array($lhpInput['opd_ids'])) {
                        $lhp->opds()->sync(array_unique(array_filter($lhpInput['opd_ids'])));
                    }

                    $primaryOpdId = ! empty($lhpInput['opd_ids']) ? $lhpInput['opd_ids'][0] : ($pemeriksaan->opd_id ?? null);

                    // ==========================================
                    // 1.A. Sync Temuan Administrasi
                    // ==========================================
                    $activeAdmIds = [];
                    $hasAdmInput = ! empty($lhpInput['ada_administrasi']) || (isset($lhpInput['temuans_administrasi']) && is_array($lhpInput['temuans_administrasi']));

                    if ($hasAdmInput && isset($lhpInput['temuans_administrasi']) && is_array($lhpInput['temuans_administrasi'])) {
                        foreach ($lhpInput['temuans_administrasi'] as $admIdx => $admInput) {
                            if (empty($admInput['uraian']) && empty($admInput['rekomendasi']) && empty($admInput['rekomendasi_items'])) {
                                continue;
                            }
                            $uraianAdm = $admInput['uraian'] ?? 'Temuan Administrasi';

                            $items = [];
                            if (! empty($admInput['rekomendasi_items']) && is_array($admInput['rekomendasi_items'])) {
                                foreach ($admInput['rekomendasi_items'] as $val) {
                                    $t = trim((string) $val);
                                    if ($t !== '') {
                                        $items[] = $t;
                                    }
                                }
                            } elseif (! empty($admInput['rekomendasi'])) {
                                $items = $this->parseRekomendasiText($admInput['rekomendasi']);
                            }

                            $rekAdm = ! empty($admInput['rekomendasi']) ? $admInput['rekomendasi'] : 'Agar ditindaklanjuti dan dilengkapi sesuai ketentuan perundang-undangan.';
                            if (! empty($items)) {
                                $numbered = [];
                                foreach ($items as $i => $u) {
                                    $numbered[] = preg_match('/^\d+[\.\)]\s*/', $u) ? $u : (($i + 1) . '. ' . $u);
                                }
                                $rekAdm = implode("\n", $numbered);
                            }

                            $admTemuan = null;
                            if (! empty($admInput['id'])) {
                                $admTemuan = Temuan::where('lhp_id', $lhp->id)->find($admInput['id']);
                            }

                            if ($admTemuan) {
                                $admTemuan->update([
                                    'uraian_temuan' => $uraianAdm,
                                    'uraian_administrasi' => $uraianAdm,
                                    'penyebab' => ! empty($admInput['penyebab']) ? trim($admInput['penyebab']) : null,
                                    'rekomendasi' => $rekAdm,
                                    'opd_id' => $primaryOpdId ?? $admTemuan->opd_id,
                                ]);

                                if (!empty($items)) {
                                    $existingReks = $admTemuan->rekomendasis()->get();
                                    if ($existingReks->isEmpty()) {
                                        foreach ($items as $itemText) {
                                            $admTemuan->rekomendasis()->create([
                                                'uraian_rekomendasi' => $itemText,
                                                'status_penyelesaian' => 'Belum Ditindaklanjuti',
                                                'created_by' => $user->id,
                                            ]);
                                        }
                                    } else {
                                        foreach ($existingReks as $i => $existingRek) {
                                            $hasTindakLanjut = $existingRek->tindakLanjuts()->exists() || $existingRek->status_penyelesaian !== 'Belum Ditindaklanjuti';
                                            if (isset($items[$i])) {
                                                if ($existingRek->uraian_rekomendasi !== $items[$i]) {
                                                    $existingRek->update(['uraian_rekomendasi' => $items[$i]]);
                                                }
                                            } elseif (!$hasTindakLanjut) {
                                                $existingRek->delete();
                                            }
                                        }
                                        if (count($items) > $existingReks->count()) {
                                            for ($i = $existingReks->count(); $i < count($items); $i++) {
                                                $admTemuan->rekomendasis()->create([
                                                    'uraian_rekomendasi' => $items[$i],
                                                    'status_penyelesaian' => 'Belum Ditindaklanjuti',
                                                    'created_by' => $user->id,
                                                ]);
                                            }
                                        }
                                    }
                                    $admTemuan->syncStatusPenyelesaianFromRekomendasi();
                                }
                                $activeAdmIds[] = $admTemuan->id;
                            } else {
                                $newAdm = Temuan::create([
                                    'pemeriksaan_id' => $pemeriksaan->id,
                                    'lhp_id' => $lhp->id,
                                    'sumber_pemeriksaan' => $pemeriksaan->sumber_pemeriksaan,
                                    'opd_id' => $primaryOpdId,
                                    'tahun' => $pemeriksaan->tahun,
                                    'judul_temuan' => 'Temuan Administrasi #' . ($admIdx + 1) . ': ' . Str::limit($uraianAdm, 40),
                                    'uraian_temuan' => $uraianAdm,
                                    'ada_unsur_administrasi' => true,
                                    'uraian_administrasi' => $uraianAdm,
                                    'penyebab' => ! empty($admInput['penyebab']) ? trim($admInput['penyebab']) : null,
                                    'ada_unsur_kerugian' => false,
                                    'nilai_kerugian' => 0,
                                    'rekomendasi' => $rekAdm,
                                    'status_pengajuan' => $pemeriksaan->status_pengajuan ?? 'DRAFT',
                                    'status_penyelesaian' => 'Belum Ditindaklanjuti',
                                    'created_by' => $user->id,
                                ]);

                                foreach ($items as $itemText) {
                                    $newAdm->rekomendasis()->create([
                                        'uraian_rekomendasi' => $itemText,
                                        'status_penyelesaian' => 'Belum Ditindaklanjuti',
                                        'created_by' => $user->id,
                                    ]);
                                }
                                $activeAdmIds[] = $newAdm->id;
                            }
                        }
                    }

                    // Hapus temuan administrasi yang dihapus/dihilangkan pengguna dari form
                    $staleAdmTemuans = Temuan::where('lhp_id', $lhp->id)
                        ->where('ada_unsur_administrasi', true)
                        ->whereNotIn('id', $activeAdmIds)
                        ->get();

                    foreach ($staleAdmTemuans as $staleAdm) {
                        if ($staleAdm->dokumen_pendukung && Storage::disk('local')->exists($staleAdm->dokumen_pendukung)) {
                            Storage::disk('local')->delete($staleAdm->dokumen_pendukung);
                        }
                        $staleAdm->tindakLanjuts()->delete();
                        $staleAdm->rekomendasis()->delete();
                        $staleAdm->delete();
                        AuditLogger::log('DELETE', 'TEMUAN', "Menghapus Temuan Administrasi (ID: {$staleAdm->id}) saat pembaruan LHP {$lhp->nomor_lhp}.");
                    }

                    // ==========================================
                    // 1.B. Sync Temuan Kerugian
                    // ==========================================
                    $activeKgIds = [];
                    $hasKgInput = ! empty($lhpInput['ada_kerugian']) || ! empty($lhpInput['temuans_kerugian']) || ! empty($lhpInput['temuan_kerugian']);

                    $kerugianList = [];
                    if ($hasKgInput) {
                        if (! empty($lhpInput['temuans_kerugian']) && is_array($lhpInput['temuans_kerugian'])) {
                            $kerugianList = $lhpInput['temuans_kerugian'];
                        } elseif (! empty($lhpInput['temuan_kerugian']) && is_array($lhpInput['temuan_kerugian'])) {
                            $kerugianList = [$lhpInput['temuan_kerugian']];
                        }
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
                            $kgTemuan = null;
                            if (! empty($kgInput['id'])) {
                                $kgTemuan = Temuan::where('lhp_id', $lhp->id)->find($kgInput['id']);
                            }

                            if ($kgTemuan) {
                                $currentKategori = $kgInput['kategori_kerugian'] ?? $kgTemuan->kategori_kerugian ?? 'Kerugian Daerah';
                                $currentKasTujuan = $currentKategori === 'Kerugian Negara' ? 'Kas Negara' : 'Kas Daerah';
                                $kgTemuan->update([
                                    'judul_temuan' => "Temuan {$currentKategori}{$nomorUrutLabel}: " . Str::limit($uraianKg, 40),
                                    'uraian_temuan' => $uraianKg,
                                    'kategori_kerugian' => $currentKategori,
                                    'jenis_kerugian' => $kgInput['jenis_kerugian'] ?? $kgTemuan->jenis_kerugian,
                                    'nilai_kerugian' => $nilaiKerugian,
                                    'uraian_kerugian' => $nilaiKerugian > 0 ? "{$currentKategori} sebesar Rp " . number_format($nilaiKerugian, 0, ',', '.') : null,
                                    'penyebab' => $penyebabKg ?? $kgTemuan->penyebab,
                                    'rekomendasi' => $kgInput['rekomendasi'] ?? "Agar menyetorkan kerugian ke {$currentKasTujuan} sesuai ketentuan.",
                                    'opd_id' => $primaryOpdId ?? $kgTemuan->opd_id,
                                ]);
                                $activeKgIds[] = $kgTemuan->id;
                            } else {
                                $newKg = Temuan::create([
                                    'pemeriksaan_id' => $pemeriksaan->id,
                                    'lhp_id' => $lhp->id,
                                    'sumber_pemeriksaan' => $pemeriksaan->sumber_pemeriksaan,
                                    'opd_id' => $primaryOpdId,
                                    'tahun' => $pemeriksaan->tahun,
                                    'judul_temuan' => "Temuan {$kategoriKg}{$nomorUrutLabel}: " . Str::limit($uraianKg, 40),
                                    'uraian_temuan' => $uraianKg,
                                    'ada_unsur_administrasi' => false,
                                    'ada_unsur_kerugian' => true,
                                    'kategori_kerugian' => $kategoriKg,
                                    'jenis_kerugian' => $kgInput['jenis_kerugian'] ?? null,
                                    'nilai_kerugian' => $nilaiKerugian,
                                    'uraian_kerugian' => $nilaiKerugian > 0 ? "{$kategoriKg} sebesar Rp " . number_format($nilaiKerugian, 0, ',', '.') : null,
                                    'penyebab' => $penyebabKg,
                                    'rekomendasi' => $rekKg,
                                    'status_pengajuan' => $pemeriksaan->status_pengajuan ?? 'DRAFT',
                                    'status_penyelesaian' => 'Belum Ditindaklanjuti',
                                    'created_by' => $user->id,
                                ]);
                                $activeKgIds[] = $newKg->id;
                            }
                        }
                    }

                    // Hapus temuan kerugian yang dihapus/dihilangkan pengguna dari form
                    $staleKgTemuans = Temuan::where('lhp_id', $lhp->id)
                        ->where('ada_unsur_kerugian', true)
                        ->whereNotIn('id', $activeKgIds)
                        ->get();

                    foreach ($staleKgTemuans as $staleKg) {
                        if ($staleKg->dokumen_pendukung && Storage::disk('local')->exists($staleKg->dokumen_pendukung)) {
                            Storage::disk('local')->delete($staleKg->dokumen_pendukung);
                        }
                        $staleKg->tindakLanjuts()->delete();
                        $staleKg->rekomendasis()->delete();
                        $staleKg->delete();
                        AuditLogger::log('DELETE', 'TEMUAN', "Menghapus Temuan Kerugian (ID: {$staleKg->id}) saat pembaruan LHP {$lhp->nomor_lhp}.");
                    }
                }

                // ==========================================
                // 1.C. Hapus LHP yang dihapus dari form (beserta temuannya)
                // ==========================================
                $staleLhps = Lhp::where('pemeriksaan_id', $pemeriksaan->id)
                    ->whereNotIn('id', $submittedLhpIds)
                    ->get();

                foreach ($staleLhps as $staleLhp) {
                    foreach ($staleLhp->temuans as $staleTemuan) {
                        if ($staleTemuan->dokumen_pendukung && Storage::disk('local')->exists($staleTemuan->dokumen_pendukung)) {
                            Storage::disk('local')->delete($staleTemuan->dokumen_pendukung);
                        }
                        $staleTemuan->tindakLanjuts()->delete();
                        $staleTemuan->rekomendasis()->delete();
                        $staleTemuan->delete();
                    }
                    if ($staleLhp->dokumen_lhp && Storage::disk('local')->exists($staleLhp->dokumen_lhp)) {
                        Storage::disk('local')->delete($staleLhp->dokumen_lhp);
                    }
                    $staleLhp->delete();
                    AuditLogger::log('DELETE', 'LHP', "Menghapus LHP (ID: {$staleLhp->id}, No: {$staleLhp->nomor_lhp}) saat pembaruan SPT {$pemeriksaan->nomor_spt}.");
                }
            }

            // 2. Fallback single LHP (backward compatibility)
            elseif (!empty($validated['nomor_lhp'])) {
                $lhp = null;
                if (!empty($validated['lhp_id'])) {
                    $lhp = Lhp::where('pemeriksaan_id', $pemeriksaan->id)->find($validated['lhp_id']);
                }
                if (! $lhp) {
                    $lhp = $pemeriksaan->lhps()->first();
                }

                $lhpData = [
                    'nomor_lhp' => $validated['nomor_lhp'],
                    'tanggal_lhp' => $validated['tanggal_lhp'] ?? ($validated['tanggal_spt'] ?? now()->format('Y-m-d')),
                    'judul_lhp' => $validated['judul_lhp'] ?: ($pemeriksaan->judul ?: "LHP {$pemeriksaan->nomor_spt}"),
                    'ringkasan' => $validated['ringkasan_lhp'] ?? null,
                ];

                if (isset($files['dokumen_lhp']) && $files['dokumen_lhp'] instanceof UploadedFile) {
                    if ($lhp && $lhp->dokumen_lhp && Storage::disk('local')->exists($lhp->dokumen_lhp)) {
                        Storage::disk('local')->delete($lhp->dokumen_lhp);
                    }
                    $fileLhp = $files['dokumen_lhp'];
                    $lhpData['dokumen_lhp'] = $fileLhp->store('lhp', 'local');
                    $lhpData['nama_dokumen_lhp_asli'] = $fileLhp->getClientOriginalName();
                }

                if ($lhp) {
                    $lhp->update($lhpData);
                    if (isset($validated['opd_ids']) && is_array($validated['opd_ids'])) {
                        $lhp->opds()->sync(array_unique(array_filter($validated['opd_ids'])));
                    }
                    AuditLogger::log('UPDATE', 'LHP', "Memperbarui data LHP {$lhp->nomor_lhp} pada SPT {$pemeriksaan->nomor_spt}.");
                } else {
                    $lhpData['pemeriksaan_id'] = $pemeriksaan->id;
                    $lhpData['created_by'] = $user->id;
                    $newLhp = Lhp::create($lhpData);
                    if (isset($validated['opd_ids']) && is_array($validated['opd_ids'])) {
                        $newLhp->opds()->sync(array_unique(array_filter($validated['opd_ids'])));
                    }
                    AuditLogger::log('CREATE', 'LHP', "Menambahkan LHP {$newLhp->nomor_lhp} pada SPT {$pemeriksaan->nomor_spt}.");
                }
            }

            return $pemeriksaan;
        });
    }

    /**
     * Ensure the Inspector is the only Penanggung Jawab and persist the other team members.
     *
     * @param array<int|string, mixed> $submittedMembers
     */
    private function saveTeamMembers(Pemeriksaan $pemeriksaan, array $submittedMembers): void
    {
        $inspektur = Pegawai::inspekturDaerah()->first();

        if (! $inspektur) {
            throw ValidationException::withMessages([
                'tim_pemeriksa' => 'Pegawai aktif dengan jabatan Inspektur belum tersedia di Master Pegawai.',
            ]);
        }

        TimPemeriksa::create([
            'pemeriksaan_id' => $pemeriksaan->id,
            'pegawai_id' => $inspektur->id,
            'peran_tim' => 'Penanggung Jawab',
        ]);

        $savedPegawaiIds = [$inspektur->id];

        foreach ($submittedMembers as $teamMember) {
            if (! is_array($teamMember)
                || empty($teamMember['pegawai_id'])
                || ($teamMember['peran_tim'] ?? null) === 'Penanggung Jawab'
                || in_array($teamMember['pegawai_id'], $savedPegawaiIds)) {
                continue;
            }

            TimPemeriksa::create([
                'pemeriksaan_id' => $pemeriksaan->id,
                'pegawai_id' => $teamMember['pegawai_id'],
                'peran_tim' => $teamMember['peran_tim'] ?? 'Anggota Tim',
            ]);

            $savedPegawaiIds[] = $teamMember['pegawai_id'];
        }
    }

    /**
     * Memecah teks rekomendasi jika berisi beberapa baris atau poin bernomor (1., 2., dst.)
     */
    public function parseRekomendasiText(?string $text): array
    {
        if (empty($text)) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($text));
        $items = [];
        $currentItem = '';

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') continue;

            // Jika baris diawali nomor (1., 1), 1\t) atau bullet (- , * , • )
            if (preg_match('/^(\d+[\.\)]|\d+\t|[-*•])\s*(.*)$/u', $trimmed, $matches)) {
                if ($currentItem !== '') {
                    $items[] = $currentItem;
                }
                $currentItem = trim($matches[2]);
            } else {
                if ($currentItem !== '') {
                    $currentItem .= ' ' . $trimmed;
                } else {
                    $currentItem = $trimmed;
                }
            }
        }
        if ($currentItem !== '') {
            $items[] = $currentItem;
        }

        // Jika tidak berformat nomor tetapi ada beberapa baris terpisah
        if (count($items) <= 1 && count(array_filter(array_map('trim', $lines))) > 1) {
            $items = array_values(array_filter(array_map(function ($l) {
                return trim(preg_replace('/^(\d+[\.\)]|\d+\t|[-*•])\s*/u', '', trim($l)));
            }, $lines), fn ($l) => $l !== ''));
        }

        if (empty($items) && trim($text) !== '') {
            $items = [trim($text)];
        }

        return array_values(array_filter($items, fn ($i) => $i !== ''));
    }
}
