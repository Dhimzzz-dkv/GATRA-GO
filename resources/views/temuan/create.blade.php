@extends('layouts.app')

@section('title', 'Tambah Temuan')
@section('page-title', 'Tambah Data Temuan Baru')
@section('page-subtitle', 'Pencatatan temuan hasil pemeriksaan, unsur administrasi, dan nilai kerugian')

@section('content')
<div class="max-w-4xl space-y-4">
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Temuan', 'href' => route('temuan.index')],
        ['label' => 'Tambah Temuan'],
    ]" />
    <x-form-card>
        @if($pemeriksaans->isEmpty())
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50/70 p-4 text-xs text-amber-900 flex items-start gap-3">
                <svg class="h-5 w-5 flex-shrink-0 text-state-warning mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <p class="font-bold">Belum Ada Data Pemeriksaan</p>
                    <p class="mt-0.5">
                        Belum ada kegiatan pemeriksaan yang dapat ditambahkan temuan. Silakan buat data <a href="{{ route('pemeriksaan.create') }}" class="font-semibold underline hover:text-amber-800">Pemeriksaan / SPT Baru</a> terlebih dahulu.
                    </p>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('temuan.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            {{-- 1. Kaitan Kegiatan Pemeriksaan --}}
            <div>
                <h3 class="text-base font-semibold text-gray-900 border-b border-gray-100 pb-2">1. Kaitan Pemeriksaan & LHP</h3>
                <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-2">
                    {{-- Pilih Pemeriksaan --}}
                    <div class="md:col-span-2">
                        <label for="pemeriksaan_id" class="mb-1.5 block text-sm font-medium text-gray-700">Kegiatan Pemeriksaan Induk <span class="text-state-danger">*</span></label>
                        <select id="pemeriksaan_id" name="pemeriksaan_id" required onchange="handlePemeriksaanChange(this)" @error('pemeriksaan_id') aria-invalid="true" aria-describedby="pemeriksaan_id-error" @enderror
                                class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control">
                            <option value="">-- Pilih Kegiatan Pemeriksaan --</option>
                            @foreach($pemeriksaans as $p)
                                <option value="{{ $p->id }}"
                                        data-lhps="{{ json_encode($p->lhps) }}"
                                        {{ (old('pemeriksaan_id', $pemeriksaanId) == $p->id) ? 'selected' : '' }}>
                                    @if($p->is_lhp_only_submission)
                                        [{{ $p->sumber_pemeriksaan }} - TA {{ $p->tahun }}] Pengajuan LHP
                                    @else
                                        [{{ $p->sumber_pemeriksaan }} - TA {{ $p->tahun }}] {{ $p->opd?->nama ?? ($p->judul ?: 'Pemeriksaan Lintas OPD') }} — SPT: {{ $p->nomor_spt }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('pemeriksaan_id')
                            <p id="pemeriksaan_id-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Pilih LHP Terkait --}}
                    <div class="md:col-span-2">
                        <label for="lhp_id" class="mb-1.5 block text-sm font-medium text-gray-700">Laporan Hasil Pemeriksaan / LHP (Opsional)</label>
                        <select id="lhp_id" name="lhp_id" @error('lhp_id') aria-invalid="true" aria-describedby="lhp_id-error" @enderror
                                class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control">
                            <option value="">-- Tanpa Kaitan Dokumen LHP Spesifik --</option>
                            @foreach($lhps as $lhp)
                                <option value="{{ $lhp->id }}" {{ old('lhp_id') == $lhp->id ? 'selected' : '' }}>
                                    {{ $lhp->nomor_lhp }} (Tgl: {{ $lhp->tanggal_lhp->format('d/m/Y') }})
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
                        <input type="text" id="judul_temuan" name="judul_temuan" value="{{ old('judul_temuan') }}" required @error('judul_temuan') aria-invalid="true" aria-describedby="judul_temuan-error" @enderror
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
                                  placeholder="Jelaskan fakta dan kondisi temuan secara lengkap...">{{ old('uraian_temuan') }}</textarea>
                        @error('uraian_temuan')
                            <p id="uraian_temuan-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Penyebab & Akibat --}}
                    <div>
                        <label for="penyebab" class="mb-1.5 block text-sm font-medium text-gray-700">Penyebab (Sebab)</label>
                        <textarea id="penyebab" name="penyebab" rows="2"
                                  class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                                  placeholder="Kelalaian dalam verifikasi dokumen...">{{ old('penyebab') }}</textarea>
                    </div>

                    <div>
                        <label for="akibat" class="mb-1.5 block text-sm font-medium text-gray-700">Akibat</label>
                        <textarea id="akibat" name="akibat" rows="2"
                                  class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                                  placeholder="Mengakibatkan potensi kerugian kas daerah...">{{ old('akibat') }}</textarea>
                    </div>

                    {{-- Rekomendasi Tindak Lanjut (Dinamis untuk Administrasi, Tunggal untuk Non-Administrasi) --}}
                    <div class="md:col-span-2">
                        {{-- 1. Mode Rekomendasi Tunggal (Default jika Unsur Administrasi tidak dicentang) --}}
                        <div id="wrapper-rekomendasi-single" class="{{ old('ada_unsur_administrasi') ? 'hidden' : '' }}">
                            <label for="rekomendasi" class="mb-1.5 block text-sm font-medium text-gray-700">Rekomendasi Tindak Lanjut</label>
                            <textarea id="rekomendasi" name="rekomendasi" rows="3"
                                      class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                                      placeholder="Instruksikan kepada Bendahara untuk menyetorkan kelebihan sebesar...">{{ old('rekomendasi') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Rekomendasi umum untuk temuan tanpa unsur administrasi (misal temuan kerugian murni).</p>
                        </div>

                        {{-- 2. Mode Rekomendasi Bertahap Dinamis (Aktif jika Unsur Administrasi dicentang) --}}
                        <div id="wrapper-rekomendasi-bertahap" class="{{ old('ada_unsur_administrasi') ? '' : 'hidden' }} space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-primary-100 pb-2.5">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p id="rekomendasi-list-label" class="block text-sm font-bold text-gray-900">Daftar Butir Rekomendasi Bertahap <span class="text-state-danger">*</span></p>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-0.5">Temuan administrasi wajib memiliki minimal 1 butir rekomendasi. Setiap butir akan diverifikasi secara bertahap.</p>
                                </div>
                                <button type="button" onclick="addRekomendasiItem()"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-primary-50 border border-primary-200/80 px-3 py-1.5 text-xs font-bold text-primary-700 hover:bg-primary-100 transition cursor-pointer self-start sm:self-auto shadow-2xs">
                                    <svg class="h-4 w-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Tambah Poin Rekomendasi
                                </button>
                            </div>

                            <div id="list-rekomendasi-items" class="space-y-3 pt-1" role="group" aria-labelledby="rekomendasi-list-label" @error('rekomendasi_items') aria-describedby="rekomendasi_items-error" @enderror>
                                @php
                                    $oldItems = old('rekomendasi_items');
                                    if (!is_array($oldItems) || empty($oldItems)) {
                                        $oldItems = [old('rekomendasi', '')];
                                    }
                                @endphp
                                @foreach($oldItems as $idx => $itemVal)
                                    <div class="item-rekomendasi flex items-start gap-2.5 bg-primary-50/40 p-3.5 rounded-xl border border-primary-200/70 transition" data-rek-idx="{{ $idx }}">
                                        <span class="badge-rek-number flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-primary-600 text-white text-xs font-bold mt-1 shadow-2xs">
                                            {{ $idx + 1 }}
                                        </span>
                                        <div class="flex-1">
                                            <label for="rekomendasi-item-{{ $idx }}" class="block text-xs font-bold text-gray-700 mb-1">
                                                Uraian Rekomendasi <span class="text-state-danger">*</span>
                                            </label>
                                            <textarea id="rekomendasi-item-{{ $idx }}" name="rekomendasi_items[]" rows="2" @error('rekomendasi_items') aria-invalid="true" aria-describedby="rekomendasi_items-error" @enderror
                                                      class="input-rek-item w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                                      placeholder="Tuliskan butir rekomendasi spesifik perbaikan administrasi...">{{ $itemVal }}</textarea>
                                        </div>
                                        <button type="button" onclick="removeRekomendasiItem(this)"
                                                class="btn-remove-rek p-1.5 text-gray-500 hover:text-state-danger rounded-lg hover:bg-red-50 transition cursor-pointer mt-1 {{ count($oldItems) <= 1 ? 'hidden' : '' }}"
                                                title="Hapus poin rekomendasi ini">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
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

            {{-- 3. Unsur Administrasi & Kerugian (PRD Seksi 12) --}}
            <div class="pt-4">
                <h3 class="text-base font-semibold text-gray-900 border-b border-gray-100 pb-2">3. Unsur Administrasi & Kerugian</h3>
                <p class="text-xs text-gray-500 mt-1">Temuan dapat memiliki unsur administrasi, unsur kerugian daerah/negara, atau keduanya.</p>

                <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-2">
                    {{-- Unsur Administrasi Box --}}
                    <div class="rounded-2xl border border-blue-100 bg-blue-50/30 p-4">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="ada_unsur_administrasi" name="ada_unsur_administrasi" value="1"
                                   onchange="handleUnsurAdministrasiToggle(this.checked)"
                                   {{ old('ada_unsur_administrasi') ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="ada_unsur_administrasi" class="text-sm font-bold text-blue-900">Memiliki Unsur Administrasi</label>
                        </div>

                        <div id="admin-details" class="mt-3 {{ old('ada_unsur_administrasi') ? '' : 'hidden' }}">
                            <label for="uraian_administrasi" class="mb-1 block text-xs font-medium text-blue-800">Rincian Informasi Administrasi</label>
                            <textarea id="uraian_administrasi" name="uraian_administrasi" rows="3"
                                      class="w-full border border-blue-200 bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                      placeholder="Contoh: Dokumen bukti pertanggungjawaban SPJ belum lengkap ditandatangani...">{{ old('uraian_administrasi') }}</textarea>
                        </div>
                    </div>

                    {{-- Unsur Kerugian Box --}}
                    <div class="rounded-2xl border border-rose-100 bg-rose-50/30 p-4">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="ada_unsur_kerugian" name="ada_unsur_kerugian" value="1"
                                   onchange="toggleSection('kerugian-details', this.checked)"
                                   {{ old('ada_unsur_kerugian') ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-state-danger focus:ring-rose-500">
                            <label for="ada_unsur_kerugian" class="text-sm font-bold text-rose-900">Memiliki Unsur Kerugian (Nominal Rupiah)</label>
                        </div>

                        <div id="kerugian-details" class="mt-3 space-y-4 {{ old('ada_unsur_kerugian') ? '' : 'hidden' }}">
                            {{-- Kategori & Jenis Kerugian --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <p id="sumber-kerugian-label" class="mb-1 block text-xs font-medium text-rose-800">Sumber Kerugian</p>
                                    <div role="group" aria-labelledby="sumber-kerugian-label" class="flex items-center gap-4">
                                        <label class="flex items-center gap-2 text-sm text-gray-700">
                                            <input type="radio" name="kategori_kerugian" value="Kerugian Daerah" {{ old('kategori_kerugian', 'Kerugian Daerah') == 'Kerugian Daerah' ? 'checked' : '' }} class="h-4 w-4 text-state-danger focus:ring-rose-500">
                                            Daerah
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-gray-700">
                                            <input type="radio" name="kategori_kerugian" value="Kerugian Negara" {{ old('kategori_kerugian') == 'Kerugian Negara' ? 'checked' : '' }} class="h-4 w-4 text-state-danger focus:ring-rose-500">
                                            Negara
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <p id="klasifikasi-kerugian-label" class="mb-1 block text-xs font-medium text-rose-800">Klasifikasi</p>
                                    <div role="group" aria-labelledby="klasifikasi-kerugian-label" class="flex items-center gap-4">
                                        <label class="flex items-center gap-2 text-sm text-gray-700">
                                            <input type="radio" name="jenis_kerugian" value="Internal" {{ old('jenis_kerugian') == 'Internal' ? 'checked' : '' }} class="h-4 w-4 text-state-danger focus:ring-rose-500">
                                            Internal
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-gray-700">
                                            <input type="radio" name="jenis_kerugian" value="Eksternal" {{ old('jenis_kerugian') == 'Eksternal' ? 'checked' : '' }} class="h-4 w-4 text-state-danger focus:ring-rose-500">
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
                                           value="{{ old('nilai_kerugian') ? number_format((float) str_replace('.', '', old('nilai_kerugian')), 0, ',', '.') : '0' }}"
                                           class="w-full border border-rose-200 bg-white py-1.5 pl-9 pr-3 text-xs font-bold text-gray-900 idds-form-control"
                                           placeholder="0" oninput="formatRupiahInput(this)" autocomplete="off">
                                </div>
                            </div>

                            <div>
                                <label for="uraian_kerugian" class="mb-1 block text-xs font-medium text-rose-800">Rincian Informasi Kerugian</label>
                                <textarea id="uraian_kerugian" name="uraian_kerugian" rows="2"
                                          class="w-full border border-rose-200 bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                          placeholder="Kelebihan pembayaran sebesar Rp ... atas pekerjaan ...">{{ old('uraian_kerugian') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. Berkas Pendukung Temuan --}}
            <div class="pt-4">
                <h3 class="text-base font-semibold text-gray-900 border-b border-gray-100 pb-2">4. Berkas Pendukung Temuan</h3>
                <div class="mt-4">
                    <label for="dokumen_pendukung" class="mb-1.5 block text-sm font-medium text-gray-700">Unggah Berkas / Bukti Pendukung Temuan (PDF/DOC, Max 10MB)</label>
                    <input type="file" id="dokumen_pendukung" name="dokumen_pendukung" accept=".pdf,.doc,.docx" @error('dokumen_pendukung') aria-invalid="true" aria-describedby="dokumen_pendukung-error" @enderror
                           class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none">
                    @error('dokumen_pendukung')
                        <p id="dokumen_pendukung-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Buttons --}}
            <div class="flex flex-col-reverse items-stretch gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-end">
                <x-action-button href="{{ route('temuan.index') }}">Batal</x-action-button>
                <x-action-button type="submit" variant="primary">
                    Simpan Data Temuan
                </x-action-button>
            </div>
        </form>
    </x-form-card>
</div>

<script>










    function handlePemeriksaanChange(select) {
        const selectedOption = select.options[select.selectedIndex];
        const lhpsJson = selectedOption.getAttribute('data-lhps');
        const lhpSelect = document.getElementById('lhp_id');

        lhpSelect.innerHTML = '<option value="">-- Tanpa Kaitan Dokumen LHP Spesifik --</option>';

        if (lhpsJson) {
            try {
                const lhps = JSON.parse(lhpsJson);
                lhps.forEach(lhp => {
                    const opt = document.createElement('option');
                    opt.value = lhp.id;
                    opt.textContent = lhp.nomor_lhp + ' - ' + (lhp.judul_lhp || 'LHP');
                    lhpSelect.appendChild(opt);
                });
            } catch (e) {
                console.error(e);
            }
        }
    }


</script>
@endsection
