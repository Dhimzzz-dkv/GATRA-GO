@extends('layouts.app')

@section('title', 'Edit Temuan - ' . $temuan->judul_temuan)
@section('page-title', 'Edit Data Temuan')
@section('page-subtitle', 'Perbarui informasi temuan hasil pemeriksaan, unsur administrasi, dan nilai kerugian')

@section('content')
<div class="max-w-4xl space-y-6">
    <div class="flex items-center justify-between">
        <x-breadcrumbs :items="[
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Temuan', 'href' => route('temuan.index')],
            ['label' => $temuan->judul_temuan, 'href' => route('temuan.show', $temuan)],
            ['label' => 'Edit'],
        ]" />
    </div>

    <x-form-card>
        <form method="POST" action="{{ route('temuan.update', $temuan) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- 1. Kaitan Kegiatan Pemeriksaan --}}
            <div>
                <h3 class="text-base font-semibold text-gray-900 border-b border-gray-100 pb-2">1. Kaitan Pemeriksaan & LHP</h3>
                <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-2">
                    {{-- Info Pemeriksaan (Readonly) --}}
                    <div class="md:col-span-2">
                        <p class="mb-1.5 block text-sm font-medium text-gray-700">Kegiatan Pemeriksaan Induk</p>
                        <div class="rounded-xl border border-gray-200 bg-gray-50/80 px-4 py-3 text-sm text-gray-800 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-sumber-badge :sumber="$temuan->sumber_pemeriksaan" size="xs" />
                                @if(! $temuan->pemeriksaan?->is_lhp_only_submission)
                                    <span class="font-bold text-gray-900">SPT: {{ $temuan->pemeriksaan?->nomor_spt ?? '-' }}</span>
                                @else
                                    <span class="font-bold text-gray-900">Pengajuan LHP</span>
                                @endif
                                <span class="text-xs text-gray-500">• Tahun Anggaran {{ $temuan->tahun }}</span>
                            </div>
                            <p class="text-xs text-gray-600">OPD Target: <strong>{{ $temuan->opd?->nama ?? '-' }}</strong></p>
                        </div>
                    </div>

                    {{-- Pilih LHP Terkait --}}
                    <div class="md:col-span-2">
                        <label for="lhp_id" class="mb-1.5 block text-sm font-medium text-gray-700">Laporan Hasil Pemeriksaan / LHP (Opsional)</label>
                        <select id="lhp_id" name="lhp_id" @error('lhp_id') aria-invalid="true" aria-describedby="lhp_id-error" @enderror
                                class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control">
                            <option value="">-- Tanpa Kaitan Dokumen LHP Spesifik --</option>
                            @foreach($lhps as $lhp)
                                <option value="{{ $lhp->id }}" {{ old('lhp_id', $temuan->lhp_id) == $lhp->id ? 'selected' : '' }}>
                                    {{ $lhp->nomor_lhp }} (Tgl: {{ $lhp->tanggal_lhp ? $lhp->tanggal_lhp->format('d/m/Y') : '-' }}) — {{ $lhp->judul_lhp }}
                                </option>
                            @endforeach
                        </select>
                        @error('lhp_id')
                            <p id="lhp_id-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- 2. Uraian Temuan --}}
            <div class="pt-4">
                <h3 class="text-base font-semibold text-gray-900 border-b border-gray-100 pb-2">2. Data Uraian Temuan</h3>
                <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-2">
                    {{-- Judul Temuan --}}
                    <div class="md:col-span-2">
                        <label for="judul_temuan" class="mb-1.5 block text-sm font-medium text-gray-700">Judul Pokok Temuan <span class="text-state-danger">*</span></label>
                        <input type="text" id="judul_temuan" name="judul_temuan" value="{{ old('judul_temuan', $temuan->judul_temuan) }}" required @error('judul_temuan') aria-invalid="true" aria-describedby="judul_temuan-error" @enderror
                               class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                               placeholder="Contoh: Kelebihan Pembayaran atas Belanja Perjalanan Dinas">
                        @error('judul_temuan')
                            <p id="judul_temuan-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Uraian Lengkap Temuan --}}
                    <div class="md:col-span-2">
                        <label for="uraian_temuan" class="mb-1.5 block text-sm font-medium text-gray-700">Uraian / Kondisi Temuan Lengkap <span class="text-state-danger">*</span></label>
                        <textarea id="uraian_temuan" name="uraian_temuan" rows="4" required @error('uraian_temuan') aria-invalid="true" aria-describedby="uraian_temuan-error" @enderror
                                  class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                                  placeholder="Jelaskan fakta dan kondisi temuan secara lengkap...">{{ old('uraian_temuan', $temuan->uraian_temuan) }}</textarea>
                        @error('uraian_temuan')
                            <p id="uraian_temuan-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Penyebab & Akibat --}}
                    <div>
                        <label for="penyebab" class="mb-1.5 block text-sm font-medium text-gray-700">Penyebab (Sebab)</label>
                        <textarea id="penyebab" name="penyebab" rows="2"
                                  class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                                  placeholder="Kelalaian dalam verifikasi dokumen...">{{ old('penyebab', $temuan->penyebab) }}</textarea>
                    </div>

                    <div>
                        <label for="akibat" class="mb-1.5 block text-sm font-medium text-gray-700">Akibat</label>
                        <textarea id="akibat" name="akibat" rows="2"
                                  class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                                  placeholder="Mengakibatkan potensi kerugian kas daerah...">{{ old('akibat', $temuan->akibat) }}</textarea>
                    </div>

                    {{-- Rekomendasi Tindak Lanjut (Dinamis untuk Administrasi, Tunggal untuk Non-Administrasi) --}}
                    <div class="md:col-span-2">
                        {{-- 1. Mode Rekomendasi Tunggal (Default jika Unsur Administrasi tidak dicentang) --}}
                        <div id="wrapper-rekomendasi-single" class="{{ old('ada_unsur_administrasi', $temuan->ada_unsur_administrasi) ? 'hidden' : '' }}">
                            <label for="rekomendasi" class="mb-1.5 block text-sm font-medium text-gray-700">Rekomendasi Tindak Lanjut</label>
                            <textarea id="rekomendasi" name="rekomendasi" rows="3"
                                      class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                                      placeholder="Instruksikan kepada Bendahara untuk menyetorkan kelebihan sebesar...">{{ old('rekomendasi', $temuan->rekomendasi) }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Rekomendasi umum untuk temuan tanpa unsur administrasi (misal temuan kerugian murni).</p>
                        </div>

                        {{-- 2. Mode Rekomendasi Bertahap Dinamis (Aktif jika Unsur Administrasi dicentang) --}}
                        <div id="wrapper-rekomendasi-bertahap" class="{{ old('ada_unsur_administrasi', $temuan->ada_unsur_administrasi) ? '' : 'hidden' }} space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-primary-100 pb-2.5">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p id="rekomendasi-list-label" class="block text-sm font-bold text-gray-900">Daftar Butir Rekomendasi Bertahap <span class="text-state-danger">*</span></p>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-0.5">Temuan administrasi wajib memiliki minimal 1 butir rekomendasi. Butir yang sudah memiliki tindak lanjut terkunci dari penghapusan.</p>
                                </div>
                                <button type="button" onclick="addRekomendasiItem()"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-primary-50 border border-primary-200/80 px-3 py-1.5 text-xs font-bold text-primary-700 hover:bg-primary-100 transition cursor-pointer self-start sm:self-auto shadow-2xs">
                                    <svg class="h-4 w-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Tambah Poin Rekomendasi
                                </button>
                            </div>

                            @php
                                $rekomendasiList = [];
                                if (old('rekomendasi_items')) {
                                    $oldItems = old('rekomendasi_items', []);
                                    $oldIds = old('rekomendasi_ids', []);
                                    foreach ($oldItems as $k => $val) {
                                        $rekomendasiList[] = [
                                            'id' => $oldIds[$k] ?? null,
                                            'uraian' => $val,
                                            'is_locked' => false,
                                        ];
                                    }
                                } else {
                                    $existing = $temuan->rekomendasis()->with('tindakLanjuts')->orderBy('id')->get();
                                    if ($existing->isNotEmpty()) {
                                        foreach ($existing as $rek) {
                                            $isLocked = $rek->tindakLanjuts->isNotEmpty() || $rek->status_penyelesaian !== 'Belum Ditindaklanjuti';
                                            $rekomendasiList[] = [
                                                'id' => $rek->id,
                                                'uraian' => $rek->uraian_rekomendasi,
                                                'is_locked' => $isLocked,
                                                'status' => $rek->status_penyelesaian,
                                            ];
                                        }
                                    } elseif ($temuan->rekomendasi) {
                                        $lines = array_values(array_filter(array_map('trim', explode("\n", $temuan->rekomendasi)), fn($l) => $l !== ''));
                                        foreach ($lines as $line) {
                                            $cleanLine = preg_replace('/^\d+[\.\)]\s*/', '', $line);
                                            $rekomendasiList[] = [
                                                'id' => null,
                                                'uraian' => $cleanLine ?: $line,
                                                'is_locked' => false,
                                            ];
                                        }
                                    }
                                }

                                if (empty($rekomendasiList)) {
                                    $rekomendasiList = [
                                        ['id' => null, 'uraian' => '', 'is_locked' => false]
                                    ];
                                }
                            @endphp

                            <div id="list-rekomendasi-items" class="space-y-3 pt-1" role="group" aria-labelledby="rekomendasi-list-label" data-include-recommendation-ids="true" @error('rekomendasi_items') aria-describedby="rekomendasi_items-error" @enderror>
                                @foreach($rekomendasiList as $idx => $item)
                                    <div class="item-rekomendasi {{ $item['is_locked'] ? 'locked-item bg-amber-50/50 border-amber-200' : 'bg-primary-50/40 border-primary-200/70' }} flex items-start gap-2.5 p-3.5 rounded-xl border transition" data-rek-idx="{{ $idx }}">
                                        <input type="hidden" name="rekomendasi_ids[]" value="{{ $item['id'] }}">
                                        <span class="badge-rek-number flex h-6 w-6 shrink-0 items-center justify-center rounded-lg {{ $item['is_locked'] ? 'bg-amber-600' : 'bg-primary-600' }} text-white text-xs font-bold mt-1 shadow-2xs">
                                            {{ $idx + 1 }}
                                        </span>
                                        <div class="flex-1 space-y-1.5">
                                            <div>
                                                <label for="rekomendasi-item-{{ $idx }}" class="block text-xs font-bold text-gray-700 mb-1">
                                                    Uraian Rekomendasi <span class="text-state-danger">*</span>
                                                </label>
                                                <textarea id="rekomendasi-item-{{ $idx }}" name="rekomendasi_items[]" rows="2" @error('rekomendasi_items') aria-invalid="true" aria-describedby="rekomendasi_items-error" @enderror
                                                          class="input-rek-item w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                                          placeholder="Tuliskan butir rekomendasi spesifik perbaikan administrasi...">{{ $item['uraian'] }}</textarea>
                                            </div>
                                            @if($item['is_locked'])
                                                <div class="flex items-center gap-1.5 pt-0.5">
                                                    <span class="inline-flex items-center gap-1 rounded-md bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800 border border-amber-200/80">
                                                        <svg class="h-3.5 w-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                        </svg>
                                                        Telah Ditindaklanjuti (Status: {{ $item['status'] ?? 'Sedang Berjalan' }}) — Tidak Dapat Dihapus
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                        @if(! $item['is_locked'])
                                            <button type="button" onclick="removeRekomendasiItem(this)"
                                                    class="btn-remove-rek p-1.5 text-gray-500 hover:text-state-danger rounded-lg hover:bg-red-50 transition cursor-pointer mt-1 {{ count($rekomendasiList) <= 1 ? 'hidden' : '' }}"
                                                    title="Hapus poin rekomendasi ini">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            @error('rekomendasi_items')
                                <p id="rekomendasi_items-error" class="text-xs text-red-700 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Unsur Administrasi & Kerugian --}}
            <div class="pt-4">
                <h3 class="text-base font-semibold text-gray-900 border-b border-gray-100 pb-2">3. Unsur Administrasi & Kerugian</h3>
                <p class="text-xs text-gray-500 mt-1">Temuan dapat memiliki unsur administrasi, unsur kerugian daerah/negara, atau keduanya.</p>

                <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-2">
                    {{-- Unsur Administrasi Box --}}
                    <div class="rounded-2xl border border-blue-100 bg-blue-50/30 p-4">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="ada_unsur_administrasi" name="ada_unsur_administrasi" value="1"
                                   onchange="handleUnsurAdministrasiToggle(this.checked)"
                                   {{ old('ada_unsur_administrasi', $temuan->ada_unsur_administrasi) ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="ada_unsur_administrasi" class="text-sm font-bold text-blue-900">Memiliki Unsur Administrasi</label>
                        </div>

                        <div id="admin-details" class="mt-3 {{ old('ada_unsur_administrasi', $temuan->ada_unsur_administrasi) ? '' : 'hidden' }}">
                            <label for="uraian_administrasi" class="mb-1 block text-xs font-medium text-blue-800">Rincian Informasi Administrasi</label>
                            <textarea id="uraian_administrasi" name="uraian_administrasi" rows="3"
                                      class="w-full border border-blue-200 bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                      placeholder="Contoh: Dokumen bukti pertanggungjawaban SPJ belum lengkap ditandatangani...">{{ old('uraian_administrasi', $temuan->uraian_administrasi) }}</textarea>
                        </div>
                    </div>

                    {{-- Unsur Kerugian Box --}}
                    <div class="rounded-2xl border border-rose-100 bg-rose-50/30 p-4">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="ada_unsur_kerugian" name="ada_unsur_kerugian" value="1"
                                   onchange="toggleSection('kerugian-details', this.checked)"
                                   {{ old('ada_unsur_kerugian', $temuan->ada_unsur_kerugian) ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-state-danger focus:ring-rose-500">
                            <label for="ada_unsur_kerugian" class="text-sm font-bold text-rose-900">Memiliki Unsur Kerugian (Nominal Rupiah)</label>
                        </div>

                        <div id="kerugian-details" class="mt-3 space-y-4 {{ old('ada_unsur_kerugian', $temuan->ada_unsur_kerugian) ? '' : 'hidden' }}">
                            {{-- Kategori & Jenis Kerugian --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <p id="sumber-kerugian-label" class="mb-1 block text-xs font-medium text-rose-800">Sumber Kerugian</p>
                                    <div role="group" aria-labelledby="sumber-kerugian-label" class="flex items-center gap-4">
                                        <label class="flex items-center gap-2 text-sm text-gray-700">
                                            <input type="radio" name="kategori_kerugian" value="Kerugian Daerah" {{ old('kategori_kerugian', $temuan->kategori_kerugian) == 'Kerugian Daerah' ? 'checked' : '' }} class="h-4 w-4 text-state-danger focus:ring-rose-500">
                                            Daerah
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-gray-700">
                                            <input type="radio" name="kategori_kerugian" value="Kerugian Negara" {{ old('kategori_kerugian', $temuan->kategori_kerugian) == 'Kerugian Negara' ? 'checked' : '' }} class="h-4 w-4 text-state-danger focus:ring-rose-500">
                                            Negara
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <p id="klasifikasi-kerugian-label" class="mb-1 block text-xs font-medium text-rose-800">Klasifikasi</p>
                                    <div role="group" aria-labelledby="klasifikasi-kerugian-label" class="flex items-center gap-4">
                                        <label class="flex items-center gap-2 text-sm text-gray-700">
                                            <input type="radio" name="jenis_kerugian" value="Internal" {{ old('jenis_kerugian', $temuan->jenis_kerugian) == 'Internal' ? 'checked' : '' }} class="h-4 w-4 text-state-danger focus:ring-rose-500">
                                            Internal
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-gray-700">
                                            <input type="radio" name="jenis_kerugian" value="Eksternal" {{ old('jenis_kerugian', $temuan->jenis_kerugian) == 'Eksternal' ? 'checked' : '' }} class="h-4 w-4 text-state-danger focus:ring-rose-500">
                                            Eksternal
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="nilai_kerugian" class="mb-1 block text-xs font-medium text-rose-800">Nilai Kerugian (Rupiah)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-xs font-bold text-state-danger">Rp</span>
                                    <input type="text" id="nilai_kerugian" name="nilai_kerugian" inputmode="numeric"
                                           value="{{ old('nilai_kerugian', number_format($temuan->nilai_kerugian ?? 0, 0, ',', '.')) }}"
                                           class="w-full border border-rose-200 bg-white py-1.5 pl-9 pr-3 text-xs font-bold text-gray-900 idds-form-control"
                                           placeholder="0" oninput="formatRupiahInput(this)" autocomplete="off">
                                </div>
                            </div>

                            <div>
                                <label for="uraian_kerugian" class="mb-1 block text-xs font-medium text-rose-800">Rincian Informasi Kerugian</label>
                                <textarea id="uraian_kerugian" name="uraian_kerugian" rows="2"
                                          class="w-full border border-rose-200 bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                          placeholder="Kelebihan pembayaran sebesar Rp ... atas pekerjaan ...">{{ old('uraian_kerugian', $temuan->uraian_kerugian) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. Berkas Pendukung Temuan --}}
            <div class="pt-4">
                <h3 class="text-base font-semibold text-gray-900 border-b border-gray-100 pb-2">4. Berkas Pendukung Temuan</h3>
                <div class="mt-4 space-y-3">
                    @if($temuan->dokumen_pendukung)
                        <div class="flex flex-wrap items-center gap-2 p-3 rounded-xl bg-gray-50 border border-gray-200 text-xs">
                            <svg class="h-4 w-4 shrink-0 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                            </svg>
                            <span class="min-w-0 flex-1 text-gray-700 font-medium [overflow-wrap:anywhere]">Berkas saat ini: <strong>{{ $temuan->nama_dokumen_asli ?? 'Dokumen Pendukung' }}</strong></span>
                            <a href="{{ route('temuan.download-dokumen', $temuan) }}" class="ml-auto shrink-0 font-bold text-primary-600 hover:underline">
                                Unduh
                            </a>
                        </div>
                    @endif

                    <div>
                        <label for="dokumen_pendukung" class="mb-1.5 block text-sm font-medium text-gray-700">
                            {{ $temuan->dokumen_pendukung ? 'Ganti Berkas / Bukti Pendukung (Opsional, PDF/DOC, Max 10MB)' : 'Unggah Berkas / Bukti Pendukung Temuan (PDF/DOC, Max 10MB)' }}
                        </label>
                        <input type="file" id="dokumen_pendukung" name="dokumen_pendukung" accept=".pdf,.doc,.docx" @error('dokumen_pendukung') aria-invalid="true" aria-describedby="dokumen_pendukung-error" @enderror
                               class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none">
                        @error('dokumen_pendukung')
                            <p id="dokumen_pendukung-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Buttons --}}
            <div class="flex flex-col-reverse items-stretch gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-end">
                <x-action-button href="{{ route('temuan.show', $temuan) }}">Batal</x-action-button>
                <x-action-button type="submit" variant="primary">
                    Simpan Perubahan
                </x-action-button>
            </div>
        </form>
    </x-form-card>
</div>

<script>











</script>
@endsection
