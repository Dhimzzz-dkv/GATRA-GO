@extends('layouts.app')

@section('title', 'Input Pemeriksaan')
@section('page-title', 'Form Pemeriksaan')
@section('page-subtitle', 'Isi data pemeriksaan langkah demi langkah.')

@section('content')
<div class="max-w-5xl mx-auto pb-28">
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Pemeriksaan & SPT', 'href' => route('pemeriksaan.index')],
        ['label' => 'Input Pemeriksaan'],
    ]" />

    <div class="mb-4">
        <div class="inline-flex items-center gap-2 rounded-xl bg-gray-100 border border-gray-200 px-4 py-2 shadow-sm">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="text-sm font-bold text-gray-700">Form Pemeriksaan · 4 Langkah</span>
        </div>
    </div>

    <form id="unified-pemeriksaan-form" method="POST" action="{{ route('pemeriksaan.store') }}" enctype="multipart/form-data" class="space-y-5" novalidate>
        @csrf

        <nav class="sticky top-20 z-20 rounded-2xl border border-gray-200 bg-white/95 p-3 shadow-sm backdrop-blur-md" aria-label="Langkah pengisian pemeriksaan">
            <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                @foreach([
                    1 => ['title' => 'SPT & Tim', 'description' => 'Data penugasan'],
                    2 => ['title' => 'LHP & Temuan', 'description' => 'LHP dan temuan wajib'],
                    3 => ['title' => 'Tindak Lanjut', 'description' => 'Opsional'],
                    4 => ['title' => 'Tinjau & Simpan', 'description' => 'Periksa ringkasan'],
                ] as $step => $item)
                    <button type="button" data-wizard-nav="{{ $step }}" aria-controls="wizard-panel-{{ $step }}" class="wizard-nav-item flex min-w-0 items-center gap-2 rounded-xl border border-transparent px-2.5 py-2 text-left transition disabled:cursor-not-allowed disabled:opacity-50">
                        <span data-wizard-number="{{ $step }}" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600">{{ $step }}</span>
                        <span class="min-w-0">
                            <span data-wizard-title="{{ $step }}" class="block truncate text-xs font-bold text-gray-700">{{ $item['title'] }}</span>
                            <span class="block truncate text-[10px] text-gray-500">{{ $item['description'] }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
            <div class="mt-3 flex items-center gap-3 border-t border-gray-100 pt-3">
                <p id="wizard-step-caption" class="shrink-0 text-xs font-semibold text-gray-600" aria-live="polite">Langkah 1 dari 4</p>
                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-100" role="progressbar" aria-label="Progres pengisian" aria-valuemin="1" aria-valuemax="4" aria-valuenow="1">
                    <div id="wizard-progress-bar" class="h-full rounded-full bg-primary-600 transition-all duration-300" style="width: 25%"></div>
                </div>
                <div class="hidden items-center gap-2 sm:flex">
                    <span class="rounded-full bg-primary-50 px-2.5 py-1 text-[11px] font-bold text-primary-800"><span id="live-total-temuan">0</span> Temuan</span>
                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-800">Rp <span id="live-total-kerugian">0</span></span>
                </div>
            </div>
        </nav>

        @if($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <p class="font-semibold">Data belum dapat disimpan.</p>
                <p class="mt-1 text-xs">Periksa langkah yang ditandai dan lengkapi kembali data yang diminta.</p>
            </div>
        @endif

        <section id="wizard-panel-1" data-wizard-panel="1" class="wizard-panel space-y-8">

        {{-- ========================================================================= --}}
        {{-- BAGIAN 1: DATA SURAT PERINTAH TUGAS (SPT)                                 --}}
        {{-- ========================================================================= --}}
        <div id="section-spt" class="rounded-3xl border border-gray-200/80 bg-white p-6 md:p-8 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-800 text-white font-bold text-base shadow-sm">
                        1
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 tracking-tight">Bagian 1: Surat Perintah Tugas (SPT)</h2>
                        <p class="text-xs text-gray-500 font-medium">Input identitas kegiatan pemeriksaan, nomor penugasan SPT, dan susunan tim pemeriksa.</p>
                    </div>
                </div>
                <span class="hidden sm:inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-xs font-bold text-primary-700 border border-primary-200/60">
                    Induk SPT
                </span>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                {{-- Judul SPT / Nama Kegiatan --}}
                <div class="md:col-span-2">
                    <label for="judul" class="mb-1.5 block text-sm font-semibold text-gray-800">Judul / Nama Kegiatan Pemeriksaan <span class="text-state-danger">*</span></label>
                    <input type="text" id="judul" name="judul" value="{{ old('judul') }}" required
                           class="w-full border bg-white px-4 py-3 text-sm text-gray-800 transition shadow-sm idds-form-control"
                           placeholder="Contoh: Pemeriksaan Kepatuhan atas Pengelolaan Keuangan dan BMD TA 2025">
                    @error('judul')
                        <p class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- No. SPT --}}
                <div>
                    <label for="nomor_spt" class="mb-1.5 block text-sm font-semibold text-gray-800">No. SPT <span class="text-state-danger">*</span></label>
                    <input type="text" id="nomor_spt" name="nomor_spt" value="{{ old('nomor_spt') }}" required
                           class="w-full border bg-white px-4 py-3 text-sm text-gray-800 transition shadow-sm idds-form-control"
                           placeholder="Contoh: 700/ITPROV/042/SPT/2026">
                    @error('nomor_spt')
                        <p class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tanggal SPT --}}
                <div>
                    <label for="tanggal_spt" class="mb-1.5 block text-sm font-semibold text-gray-800">Tanggal SPT <span class="text-state-danger">*</span></label>
                    <input type="date" id="tanggal_spt" name="tanggal_spt" value="{{ old('tanggal_spt', date('Y-m-d')) }}" required
                           class="w-full border bg-white px-4 py-3 text-sm text-gray-800 transition shadow-sm idds-form-control">
                    @error('tanggal_spt')
                        <p class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Sumber Pemeriksaan --}}
                <div>
                    <label for="sumber_pemeriksaan" class="mb-1.5 block text-sm font-semibold text-gray-800">Sumber Pemeriksaan <span class="text-state-danger">*</span></label>
                    @if($lockedSumber)
                        <input type="hidden" name="sumber_pemeriksaan" value="{{ $lockedSumber }}">
                        <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-bold text-primary-900">
                            <span class="inline-flex items-center rounded-md bg-primary-100 px-2 py-0.5 text-xs text-primary-800">Terkunci</span>
                            {{ $lockedSumber }} (Inspektorat Gorontalo)
                        </div>
                    @else
                        <select id="sumber_pemeriksaan" name="sumber_pemeriksaan" required
                                class="w-full border bg-white px-4 py-3 text-sm text-gray-800 transition shadow-sm idds-form-control">
                            @foreach($sumberList as $s)
                                <option value="{{ $s }}" {{ old('sumber_pemeriksaan', 'Inspektorat') == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    @endif
                    @error('sumber_pemeriksaan')
                        <p class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tahun Anggaran --}}
                <div>
                    <label for="tahun" class="mb-1.5 block text-sm font-semibold text-gray-800">Tahun Anggaran <span class="text-state-danger">*</span></label>
                    <select id="tahun" name="tahun" required
                            class="w-full border bg-white px-4 py-3 text-sm text-gray-800 transition shadow-sm idds-form-control">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ old('tahun', $currentYear) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                    @error('tahun')
                        <p class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>
                    @enderror
                </div>



                {{-- Periode Mulai & Selesai --}}
                <div>
                    <label for="tanggal_mulai" class="mb-1.5 block text-sm font-semibold text-gray-800">Tanggal Mulai Pemeriksaan</label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}"
                           class="w-full border bg-white px-4 py-3 text-sm text-gray-800 transition shadow-sm idds-form-control">
                    @error('tanggal_mulai')
                        <p class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tanggal_selesai" class="mb-1.5 block text-sm font-semibold text-gray-800">Tanggal Selesai Pemeriksaan</label>
                    <input type="date" id="tanggal_selesai" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}"
                           class="w-full border bg-white px-4 py-3 text-sm text-gray-800 transition shadow-sm idds-form-control">
                    @error('tanggal_selesai')
                        <p class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Susunan Tim Pemeriksa --}}
                <div class="md:col-span-2 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 sm:p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-sm font-bold text-gray-800">Susunan Tim Pemeriksa (Inspektorat)</label>
                            <p class="text-xs text-gray-500 font-medium">Penanggung Jawab ditetapkan otomatis kepada Inspektur.</p>
                        </div>
                    </div>

                    {{-- Jabatan utama tim pemeriksa --}}
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <div class="min-w-0">
                            <label for="tim-pemeriksa-penanggung-jawab" class="idds-field-label mb-1 block min-h-5">Penanggung Jawab</label>
                            <input id="tim-pemeriksa-penanggung-jawab" type="text" readonly
                                   value="{{ $pegawaiInspektur ? $pegawaiInspektur->nama . ' — ' . $pegawaiInspektur->jabatan : '' }}"
                                   placeholder="Inspektur belum terdaftar di Master Pegawai"
                                   class="w-full border bg-gray-100 px-3 py-2 text-xs text-gray-700 shadow-2xs idds-form-control">
                            <input type="hidden" name="tim_pemeriksa[0][pegawai_id]" value="{{ $pegawaiInspektur?->id }}">
                            <input type="hidden" name="tim_pemeriksa[0][peran_tim]" value="Penanggung Jawab">
                        </div>
                        <div class="min-w-0">
                            <label for="asisten-penanggung-jawab" class="idds-field-label mb-1 block min-h-5">Asisten Penanggung Jawab</label>
                            <select id="asisten-penanggung-jawab" name="tim_pemeriksa[1][pegawai_id]" class="w-full border bg-white px-3 py-2 text-xs text-gray-800 shadow-2xs idds-form-control">
                                <option value="">-- Pilih Pegawai --</option>
                                @foreach($pegawais as $p)
                                    <option value="{{ $p->id }}" {{ old('tim_pemeriksa.1.pegawai_id') == $p->id ? 'selected' : '' }}>{{ $p->nama }} — {{ $p->jabatan }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="tim_pemeriksa[1][peran_tim]" value="Asisten Penanggung Jawab">
                        </div>
                        <div class="min-w-0">
                            <label for="tim-pemeriksa-pengendali-teknis" class="idds-field-label mb-1 block min-h-5">Pengendali Teknis / Dalnis</label>
                            <select id="tim-pemeriksa-pengendali-teknis" name="tim_pemeriksa[2][pegawai_id]" class="w-full border bg-white px-3 py-2 text-xs text-gray-800 shadow-2xs idds-form-control">
                                <option value="">-- Pilih Pegawai --</option>
                                @foreach($pegawais as $p)
                                    <option value="{{ $p->id }}">{{ $p->nama }} — {{ $p->jabatan }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="tim_pemeriksa[2][peran_tim]" value="Pengendali Teknis">
                        </div>
                        <div class="min-w-0">
                            <label for="tim-pemeriksa-ketua-tim" class="idds-field-label mb-1 block min-h-5">Ketua Tim</label>
                            <select id="tim-pemeriksa-ketua-tim" name="tim_pemeriksa[3][pegawai_id]" class="w-full border bg-white px-3 py-2 text-xs text-gray-800 shadow-2xs idds-form-control">
                                <option value="">-- Pilih Pegawai --</option>
                                @foreach($pegawais as $p)
                                    <option value="{{ $p->id }}">{{ $p->nama }} — {{ $p->jabatan }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="tim_pemeriksa[3][peran_tim]" value="Ketua Tim">
                        </div>
                    </div>
                    @error('tim_pemeriksa')
                        <p class="text-xs font-semibold text-red-700" role="alert">{{ $message }}</p>
                    @enderror

                    {{-- Anggota Tim Section (Dinamis / Multi-Anggota) --}}
                    <div class="pt-3.5 border-t border-gray-200/80 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-gray-800 block">Anggota Tim Pemeriksa</span>
                                <span class="text-xs text-gray-500">Dapat menambahkan 1 atau lebih pegawai sebagai anggota tim</span>
                            </div>
                            <button type="button" onclick="addAnggotaTimRow()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl bg-primary-50 text-primary-700 hover:bg-primary-100 border border-primary-200/70 transition shadow-2xs cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span>Tambah Anggota Tim</span>
                            </button>
                        </div>

                        <div id="anggota-tim-container" class="space-y-2">
                            <div class="anggota-tim-row flex items-center gap-2" data-anggota-idx="4">
                                <div class="flex-1">
                                    <select name="tim_pemeriksa[4][pegawai_id]" aria-label="Pilih pegawai anggota tim pemeriksa" class="w-full border bg-white px-3 py-2 text-xs text-gray-800 shadow-2xs idds-form-control">
                                        <option value="">-- Pilih Pegawai (Anggota Tim) --</option>
                                        @foreach($pegawais as $p)
                                            <option value="{{ $p->id }}">{{ $p->nama }} — {{ $p->jabatan }}</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="tim_pemeriksa[4][peran_tim]" value="Anggota Tim">
                                </div>
                                <button type="button" onclick="removeAnggotaTimRow(this)" class="btn-remove-anggota hidden p-2 text-gray-500 hover:text-state-danger hover:bg-red-50 rounded-xl transition cursor-pointer" title="Hapus Anggota Ini">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Upload Dokumen SPT --}}
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800">Unggah Dokumen SPT (PDF / DOC / DOCX)</label>
                    <div class="flex items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50/50 p-5 text-center hover:bg-gray-50 transition">
                        <div>
                            <svg class="mx-auto h-8 w-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <div class="mt-2 flex text-sm text-gray-600 justify-center">
                                <label for="dokumen_spt" class="relative cursor-pointer rounded-md font-bold text-primary-600 focus-within:outline-none hover:text-primary-700">
                                    <span>Pilih berkas dokumen SPT</span>
                                    <input id="dokumen_spt" name="dokumen_spt" type="file" accept=".pdf,.doc,.docx" class="sr-only" onchange="updateSptFileName(this)" data-file-display="spt-file-display" data-file-label-prefix="Berkas SPT: " data-file-display-class="mt-1 text-xs font-bold text-primary-700">
                                </label>
                            </div>
                            <p id="spt-file-display" class="mt-1 text-xs text-gray-500 font-medium">Format PDF, DOC, atau DOCX (Maksimal 10MB)</p>
                        </div>
                    </div>
                    @error('dokumen_spt')
                        <p class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        </section>

        {{-- ========================================================================= --}}
        {{-- BAGIAN 2: DATA LAPORAN HASIL PEMERIKSAAN (LHP) - MULTI-LHP DINAMIS       --}}
        {{-- ========================================================================= --}}
        <section id="wizard-panel-2" data-wizard-panel="2" class="wizard-panel hidden space-y-8">
        <div id="section-lhp" class="rounded-3xl border border-primary-200/80 bg-primary-50/40 p-6 md:p-8 shadow-sm transition hover:shadow-md">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-primary-200 pb-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-800 text-white font-bold text-base shadow-sm">
                        2
                    </div>
                    <div>
                            <h2 class="text-lg font-bold text-gray-900 tracking-tight">Bagian 2: Laporan Hasil Pemeriksaan (LHP)</h2>
                        <p class="text-xs text-gray-500 font-medium">Satu SPT dapat memiliki banyak LHP. Klik tombol "+ Tambah LHP" untuk menambah form LHP baru secara dinamis.</p>
                    </div>
                </div>

                {{-- Tombol  Tambah LHP (Sama seperti Tambah Tindak Lanjut) --}}
                <button type="button" onclick="addLhpCard()"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-700 text-white font-semibold text-xs px-5 py-3 shadow-sm hover:bg-emerald-800 transition-colors cursor-pointer">
                    <svg class="h-4 w-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah LHP</span>
                </button>
            </div>

            {{-- Container Seluruh Card LHP --}}
            <div id="lhp-container" class="mt-6 space-y-6">
                {{-- LHP #1 (Default Pertama) --}}
                <div class="lhp-card rounded-2xl border-2 border-primary-200 bg-white p-5 md:p-6 shadow-sm relative transition" data-lhp-index="0">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                        <div class="flex items-center gap-2">
                            <x-sequence-badge type="lhp">
                                LHP #1
                            </x-sequence-badge>
                            <span class="text-xs text-gray-500 font-bold">Laporan Hasil Pemeriksaan</span>
                        </div>
                        <button type="button" onclick="removeLhpCard(this)" class="btn-remove-lhp hidden text-xs font-bold text-state-danger hover:text-red-700 hover:underline cursor-pointer">
                            Hapus LHP Ini
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        {{-- No. LHP --}}
                        <div>
                            <label for="pemeriksaan-lhp-0-nomor" class="mb-1 block text-xs font-bold text-gray-700">No. LHP <span class="text-state-danger">*</span></label>
                            <input id="pemeriksaan-lhp-0-nomor" type="text" name="lhps[0][nomor_lhp]" required oninput="syncLhpOptionsToTindakLanjut()"
                                   class="input-nomor-lhp w-full border bg-white px-3.5 py-2.5 text-sm text-gray-800 idds-form-control"
                                   placeholder="Contoh: 700/01/LHP/ITPROV/2026">
                        </div>

                        {{-- Tanggal LHP --}}
                        <div>
                            <label for="pemeriksaan-lhp-0-tanggal" class="mb-1 block text-xs font-bold text-gray-700">Tanggal LHP <span class="text-state-danger">*</span></label>
                            <input id="pemeriksaan-lhp-0-tanggal" type="date" name="lhps[0][tanggal_lhp]" value="{{ date('Y-m-d') }}" required
                                   class="w-full border bg-white px-3.5 py-2.5 text-sm text-gray-800 idds-form-control">
                        </div>

                        {{-- Judul LHP --}}
                        <div class="md:col-span-2">
                            <label for="pemeriksaan-lhp-0-judul" class="mb-1 block text-xs font-bold text-gray-700">Judul LHP</label>
                            <input id="pemeriksaan-lhp-0-judul" type="text" name="lhps[0][judul_lhp]" oninput="syncLhpOptionsToTindakLanjut()"
                                   class="input-judul-lhp w-full border bg-white px-3.5 py-2.5 text-sm text-gray-800 idds-form-control"
                                   placeholder="Contoh: Laporan Hasil Pemeriksaan Kepatuhan Keuangan dan Aset TA 2025">
                        </div>

                        {{-- Daftar OPD Target (Multi-Select Searchable) --}}
                        <div class="md:col-span-2 rounded-2xl border border-primary-200/90 bg-primary-50/40 p-4">
                            <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-bold text-gray-800">
                                    Daftar OPD Target <span class="text-state-danger">*</span>
                                    <span class="text-xs font-normal text-gray-500 ml-1">(Dapat memilih lebih dari 1 OPD target)</span>
                                </label>
                                <span class="selected-opd-count text-xs font-bold text-primary-700 bg-primary-100 px-2.5 py-0.5 rounded-full">
                                    0 OPD Terpilih
                                </span>
                            </div>

                            {{-- Kotak Pencarian OPD & Quick Action --}}
                            <div class="flex items-center gap-2 mb-2">
                                <div class="relative flex-1">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </span>
                                    <input type="text" oninput="filterOpdOptions(this)"
                                           class="opd-search-input w-full border bg-white pl-9 pr-4 py-2 text-xs text-gray-800 placeholder-gray-400 shadow-2xs idds-form-control"
                                           placeholder="Cari OPD (misal: Pendidikan, Kesehatan, PUPR)..." aria-label="Cari OPD untuk LHP #1">
                                </div>
                                <button type="button" onclick="clearOpdSelection(this)"
                                        class="text-xs font-bold text-gray-500 hover:text-state-danger bg-white border border-gray-200 hover:border-red-200 px-3 py-2 rounded-xl transition shrink-0 cursor-pointer shadow-2xs"
                                        title="Hapus semua centang OPD">
                                    Bersihkan
                                </button>
                            </div>

                            {{-- List Checkbox OPD (Scrollable) --}}
                            <div class="opd-checkbox-list max-h-44 overflow-y-auto space-y-1 rounded-xl border border-gray-200 bg-white p-2 divide-y divide-gray-50">
                                @foreach($opds as $o)
                                    <label class="opd-item flex items-center justify-between gap-2 p-1.5 rounded-lg hover:bg-primary-50/70 cursor-pointer transition text-xs select-none" data-search="{{ strtolower($o->kode . ' ' . $o->nama . ' ' . $o->singkatan) }}">
                                        <div class="flex items-center gap-2.5">
                                            <input type="checkbox" name="lhps[0][opd_ids][]" value="{{ $o->id }}"
                                                   onchange="updateOpdSelection(this)"
                                                   class="opd-checkbox h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
                                                   data-name="{{ $o->nama }}" data-code="{{ $o->kode }}">
                                            <span class="font-bold text-primary-900">[{{ $o->kode }}]</span>
                                            <span class="text-gray-700 font-medium">{{ $o->nama }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>

                            {{-- Badge / Chips OPD yang Dipilih --}}
                            <div class="mt-2.5">
                                <span class="text-xs font-bold text-gray-600  tracking-wider block mb-1">OPD Terpilih:</span>
                                <div class="selected-opd-chips flex flex-wrap gap-1.5 min-h-[32px] items-center p-2 rounded-xl bg-white border border-dashed border-gray-200" data-opd-chip-mode="removable">
                                    <span class="empty-opd-placeholder text-xs text-gray-500 italic">Belum ada OPD yang dipilih (centang minimal 1 OPD di atas)</span>
                                </div>
                            </div>
                        </div>

                        {{-- Uraian Singkat / Ringkasan --}}
                        <div class="md:col-span-2">
                            <label for="pemeriksaan-lhp-0-ringkasan" class="mb-1 block text-xs font-bold text-gray-700">Uraian Singkat / Ringkasan Hasil Pemeriksaan</label>
                            <textarea id="pemeriksaan-lhp-0-ringkasan" name="lhps[0][ringkasan]" rows="2"
                                      class="w-full border bg-white px-3.5 py-2 text-sm text-gray-800 idds-form-control"
                                      placeholder="Tuliskan uraian ringkas pokok hasil temuan atau evaluasi pemeriksaan..."></textarea>
                        </div>

                        {{-- Pilihan Unsur Temuan: Administrasi & Kerugian Daerah --}}
                        <div class="md:col-span-2 rounded-2xl border border-amber-200 bg-amber-50/50 p-4 space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-amber-900 tracking-wider mb-1">
                                    Unsur Temuan &amp; Rekomendasi <span class="text-state-danger">*</span>
                                </label>
                                <p class="text-xs text-amber-800/80 mb-3">Isi minimal satu temuan administrasi atau kerugian untuk setiap LHP. Uraian dan rekomendasi wajib dilengkapi.</p>

                                <div class="flex flex-wrap gap-4 items-center">
                                    <label class="inline-flex items-center gap-2 cursor-pointer select-none bg-white px-4 py-2 rounded-xl border border-amber-300 shadow-2xs hover:bg-amber-50 transition">
                                        <input type="checkbox" name="lhps[0][ada_administrasi]" value="1"
                                               onchange="toggleUnsurTemuan(this, 0, 'administrasi')"
                                               class="chk-unsur-administrasi h-4 w-4 rounded border-gray-300 text-state-warning focus:ring-amber-500 cursor-pointer">
                                        <span class="text-xs font-bold text-gray-800">Unsur Administrasi</span>
                                    </label>

                                    <label class="inline-flex items-center gap-2 cursor-pointer select-none bg-white px-4 py-2 rounded-xl border border-amber-300 shadow-2xs hover:bg-amber-50 transition">
                                        <input type="checkbox" name="lhps[0][ada_kerugian]" value="1"
                                               onchange="toggleUnsurTemuan(this, 0, 'kerugian')"
                                               class="chk-unsur-kerugian h-4 w-4 rounded border-gray-300 text-state-warning focus:ring-amber-500 cursor-pointer">
                                        <span class="text-xs font-bold text-gray-800">Unsur Kerugian (Daerah / Negara)</span>
                                    </label>
                                </div>
                            </div>

                            {{-- CONTAINER 1: TEMUAN ADMINISTRASI (Multi-Temuan Dinamis) --}}
                            <div class="container-temuan-administrasi hidden space-y-3 pt-2 border-t border-amber-200/80">
                                <div class="flex items-center justify-between">
                                    <h5 class="text-xs font-bold text-primary-900  tracking-wider flex items-center gap-1.5">
                                        <span>Daftar Temuan Administrasi</span>
                                    </h5>
                                    <button type="button" onclick="addTemuanAdministrasiItem(this, 0)"
                                            class="inline-flex items-center gap-1 text-xs font-bold text-primary-700 bg-primary-100 hover:bg-primary-200 px-3 py-1.5 rounded-lg transition cursor-pointer">
                                        + Tambah Temuan Administrasi
                                    </button>
                                </div>

                                <div class="list-temuan-administrasi space-y-3">
                                    {{-- Item Temuan Administrasi #1 --}}
                                    <div class="item-temuan-adm bg-white p-3.5 rounded-xl border border-primary-200 shadow-2xs space-y-2.5" data-adm-idx="0">
                                        <div class="flex items-center justify-between">
                                            <span class="adm-label text-xs font-bold text-primary-700 bg-primary-50 px-2 py-0.5 rounded-md">Temuan Administrasi #1</span>
                                            <button type="button" onclick="removeTemuanAdministrasiItem(this)" class="btn-remove-adm hidden text-xs font-bold text-state-danger hover:text-red-700 hover:underline cursor-pointer">
                                                Hapus Temuan Ini
                                            </button>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Uraian Temuan Administrasi <span class="text-state-danger">*</span></label>
                                            <textarea name="lhps[0][temuans_administrasi][0][uraian]" rows="2"
                                                      class="input-adm-uraian w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                                      placeholder="Contoh: Dokumen pertanggungjawaban SPJ belum dilengkapi bukti pengeluaran yang sah..."></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Penyebab (Sebab) <span class="text-gray-500 font-normal">(Opsional)</span></label>
                                            <textarea name="lhps[0][temuans_administrasi][0][penyebab]" rows="2"
                                                      class="input-adm-penyebab w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                                      placeholder="Contoh: Kurangnya ketelitian bendahara pengeluaran serta lemahnya pengawasan atasan langsung..."></textarea>
                                        </div>
                                        <div class="space-y-1.5 pt-1">
                                            <div class="flex items-center justify-between">
                                                <label class="block text-xs font-bold text-gray-700">Poin-Poin Rekomendasi Bertahap <span class="text-state-danger">*</span></label>
                                                <button type="button" onclick="addRekomendasiPointPemeriksaan(this)" class="inline-flex items-center gap-1 text-xs font-bold text-primary-700 hover:text-primary-900 bg-primary-50 hover:bg-primary-100 px-2.5 py-1 rounded-lg border border-primary-200 transition cursor-pointer">
                                                    + Tambah Poin Rekomendasi
                                                </button>
                                            </div>
                                            <div class="list-rekomendasi-points space-y-1.5">
                                                <div class="item-rek-point flex items-start gap-1.5 bg-primary-50/30 p-2 rounded-lg border border-primary-100/80">
                                                    <span class="rek-point-num text-xs font-bold bg-primary-600 text-white w-5 h-5 flex items-center justify-center rounded shrink-0 mt-0.5 shadow-2xs">1</span>
                                                    <div class="flex-1">
                                                        <textarea name="lhps[0][temuans_administrasi][0][rekomendasi_items][]" rows="2"
                                                                  class="input-adm-rekomendasi-item w-full border bg-white px-2.5 py-1.5 text-xs text-gray-800 idds-form-control"
                                                                  placeholder="Contoh: Melengkapi bukti pertanggungjawaban SPJ sesuai ketentuan perundang-undangan..."></textarea>
                                                    </div>
                                                    <button type="button" onclick="removeRekomendasiPointPemeriksaan(this)" class="btn-remove-rek-point hidden text-gray-500 hover:text-state-danger p-1 cursor-pointer mt-0.5" title="Hapus poin rekomendasi">
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- CONTAINER 2: TEMUAN KERUGIAN (Multi-Temuan Dinamis) --}}
                            <div class="container-temuan-kerugian hidden space-y-3 pt-2 border-t border-amber-200/80">
                                <div class="flex items-center justify-between">
                                    <h5 class="text-xs font-bold text-rose-900  tracking-wider flex items-center gap-1.5">
                                        <span>Daftar Temuan Kerugian (Daerah / Negara)</span>
                                    </h5>
                                    <button type="button" onclick="addTemuanKerugianItem(this, 0)"
                                            class="inline-flex items-center gap-1 text-xs font-bold text-rose-700 bg-rose-100 hover:bg-rose-200 px-3 py-1.5 rounded-lg transition cursor-pointer">
                                        + Tambah Temuan Kerugian
                                    </button>
                                </div>

                                <div class="list-temuan-kerugian space-y-3">
                                    {{-- Item Temuan Kerugian #1 --}}
                                    <div class="item-temuan-kg bg-white p-3.5 rounded-xl border border-rose-200 shadow-2xs space-y-2.5" data-kg-idx="0">
                                        <div class="flex items-center justify-between">
                                            <span class="kg-label text-xs font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-md">Temuan Kerugian #1</span>
                                            <button type="button" onclick="removeTemuanKerugianItem(this)" class="btn-remove-kg hidden text-xs font-bold text-state-danger hover:text-red-700 hover:underline cursor-pointer">
                                                Hapus Temuan Ini
                                            </button>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Uraian Temuan Kerugian <span class="text-state-danger">*</span></label>
                                            <textarea name="lhps[0][temuans_kerugian][0][uraian]" rows="2"
                                                      class="input-kg-uraian w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                                      placeholder="Contoh: Terdapat kelebihan pembayaran belanja perjalanan dinas..."></textarea>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 mb-1">Kategori / Sumber Kerugian <span class="text-state-danger">*</span></label>
                                                <select name="lhps[0][temuans_kerugian][0][kategori_kerugian]" class="input-kg-kategori w-full border bg-white px-3 py-2 text-xs font-semibold text-gray-800 idds-form-control">
                                                    <option value="Kerugian Daerah" selected>Kerugian Daerah (Kasda / APBD)</option>
                                                    <option value="Kerugian Negara">Kerugian Negara (Kas Negara / APBN)</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 mb-1">Klasifikasi Penyelesaian <span class="text-state-danger">*</span></label>
                                                <div class="flex items-center gap-4 mt-2">
                                                    <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                                        <input type="radio" name="lhps[0][temuans_kerugian][0][jenis_kerugian]" value="Internal" class="input-kg-jenis h-3.5 w-3.5 text-state-danger focus:ring-rose-500">
                                                        <span>Internal (ASN/OPD)</span>
                                                    </label>
                                                    <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                                        <input type="radio" name="lhps[0][temuans_kerugian][0][jenis_kerugian]" value="Eksternal" class="input-kg-jenis h-3.5 w-3.5 text-state-danger focus:ring-rose-500">
                                                        <span>Eksternal (Rekanan)</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Nominal Nilai Kerugian (Rp) <span class="text-state-danger">*</span></label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-2 text-xs font-bold text-gray-500">Rp</span>
                                                <input type="text" name="lhps[0][temuans_kerugian][0][nilai_kerugian]" oninput="formatRupiahLive(this)"
                                                       class="input-kg-nilai w-full border bg-white pl-9 pr-3 py-2 text-sm font-bold text-emerald-700 idds-form-control"
                                                       placeholder="0">
                                            </div>
                                            <p class="mt-1 text-xs text-gray-500 italic">Ketik nominal tanpa titik, sistem memformat otomatis.</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Penyebab (Sebab) <span class="text-gray-500 font-normal">(Opsional)</span></label>
                                            <textarea name="lhps[0][temuans_kerugian][0][penyebab]" rows="2"
                                                      class="input-kg-penyebab w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                                      placeholder="Contoh: Kurangnya ketelitian dalam verifikasi bukti pertanggungjawaban..."></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Rekomendasi Penyetoran <span class="text-state-danger">*</span></label>
                                            <textarea name="lhps[0][temuans_kerugian][0][rekomendasi]" rows="2"
                                                      class="input-kg-rekomendasi w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                                      placeholder="Contoh: Menyetorkan kelebihan pembayaran ke Kas Daerah / Kas Negara sebesar nominal kerugian..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Upload File LHP --}}
                        <div class="md:col-span-2">
                            <label for="pemeriksaan-lhp-0-dokumen" class="mb-1 block text-xs font-bold text-gray-700">Unggah File Dokumen LHP (PDF / DOC / DOCX)</label>
                            <input id="pemeriksaan-lhp-0-dokumen" type="file" name="lhps[0][dokumen_lhp]" accept=".pdf,.doc,.docx"
                                   class="block w-full text-xs text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary-100 file:text-primary-700 hover:file:bg-primary-200 cursor-pointer">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        </section>

        {{-- ========================================================================= --}}
        {{-- BAGIAN 3: DATA TINDAK LANJUT - MULTI-TL DINAMIS (OPSIONAL REKAP)          --}}
        {{-- ========================================================================= --}}
        <section id="wizard-panel-3" data-wizard-panel="3" class="wizard-panel hidden space-y-8">
        <div id="section-tl" class="rounded-3xl border border-emerald-200/80 bg-emerald-50/40 p-6 md:p-8 shadow-sm transition hover:shadow-md">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-emerald-200 pb-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-700 text-white font-bold text-base shadow-sm">
                        3
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-bold text-gray-900 tracking-tight">Bagian 3: Tindak Lanjut Awal</h2>
                            <span class="rounded-full bg-emerald-100 text-emerald-800 px-2.5 py-0.5 text-xs font-bold  tracking-wider border border-emerald-200">Opsional</span>
                        </div>
                        <p class="text-xs text-gray-500 font-medium">Tindak Lanjut adalah proses yang sedang berjalan. Buka bagian ini hanya jika Anda ingin merekap data tindak lanjut lama secara bersamaan.</p>
                    </div>
                </div>

                {{-- Toggle Switch Buka/Tutup Form Tindak Lanjut --}}
                <button type="button" id="btn-toggle-tl" onclick="toggleTindakLanjutSection()"
                        class="inline-flex items-center gap-2 rounded-xl bg-white border border-emerald-300 px-4 py-2.5 text-xs font-bold text-emerald-800 shadow-2xs hover:bg-emerald-50 transition active:scale-95 cursor-pointer">
                    <span id="tl-toggle-icon">➕</span>
                    <span id="tl-toggle-text">Buka Form Tindak Lanjut</span>
                </button>
            </div>

            {{-- Banner Notifikasi Collapsed (Saat Ditutup) --}}
            <div id="tl-collapsed-notice" class="mt-5 rounded-2xl bg-white p-5 border border-dashed border-emerald-300 text-center space-y-2.5">
                <div class="w-10 h-10 rounded-full bg-emerald-50 text-state-success flex items-center justify-center mx-auto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-gray-800">Form Tindak Lanjut Ditutup (Opsional)</h4>
                    <p class="text-xs text-gray-500 max-w-lg mx-auto mt-0.5">
                        Jika OPD belum melakukan tindak lanjut / setor, Anda dapat langsung menyimpan SPT & LHP sekarang. Tindak lanjut dapat dicatat kapan saja di masa mendatang melalui menu <strong>Detail Pemeriksaan</strong>.
                    </p>
                </div>
                <div class="pt-1">
                    <button type="button" onclick="toggleTindakLanjutSection()"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-4 py-2 rounded-xl transition cursor-pointer shadow-2xs">
                        <span>➕ Rekap Tindak Lanjut Awal Sekarang</span>
                    </button>
                </div>
            </div>

            {{-- Container Form Tindak Lanjut (Hidden Default) --}}
            <div id="tl-expanded-section" class="hidden mt-6 space-y-6 animate-fade-in">
                <div class="flex items-center justify-between pb-1 border-b border-emerald-200/60">
                    <span class="text-xs font-bold text-emerald-900  tracking-wider">Daftar Formulir Tindak Lanjut</span>
                    <button type="button" onclick="addTindakLanjutCard()"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 text-white font-bold text-xs px-4 py-2 shadow-sm hover:bg-emerald-700 transition active:scale-95 cursor-pointer">
                        <svg class="h-3.5 w-3.5 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Tambah Kartu TL Baru</span>
                    </button>
                </div>

                {{-- Container Seluruh Card Tindak Lanjut --}}
                <div id="tl-container" class="space-y-6">
                    {{-- Tindak Lanjut #1 (Default Pertama) --}}
                    <div class="tl-card rounded-2xl border border-emerald-200 bg-white p-5 md:p-6 shadow-sm relative transition" data-tl-index="0">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                            <div class="flex items-center gap-2">
                                <x-sequence-badge type="tindak-lanjut">
                                    Tindak Lanjut #1
                                </x-sequence-badge>
                                <span class="text-xs text-gray-500 font-bold">Progress &amp; Setoran</span>
                            </div>
                            <button type="button" onclick="removeTlCard(this)" class="btn-remove-tl hidden text-xs font-bold text-state-danger hover:text-red-700 hover:underline cursor-pointer">
                                Hapus Tindak Lanjut
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            {{-- Dropdown Pilih LHP --}}
                            <div class="md:col-span-2">
                                <label for="pemeriksaan-tl-0-lhp" class="mb-1 block text-xs font-bold text-gray-700">Pilih LHP Terkait <span class="text-state-danger">*</span></label>
                                <select id="pemeriksaan-tl-0-lhp" name="tindak_lanjuts[0][lhp_index]" class="select-target-lhp w-full border bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-800 idds-form-control">
                                    <option value="0">LHP #1 (LHP Utama)</option>
                                </select>
                            </div>

                            {{-- Tanggal Tindak Lanjut --}}
                            <div>
                                <label for="pemeriksaan-tl-0-tanggal" class="mb-1 block text-xs font-bold text-gray-700">Tanggal Tindak Lanjut</label>
                                <input id="pemeriksaan-tl-0-tanggal" type="date" name="tindak_lanjuts[0][tanggal_tindak_lanjut]" value="{{ date('Y-m-d') }}"
                                       class="w-full border bg-white px-3.5 py-2.5 text-sm text-gray-800 idds-form-control">
                            </div>

                            {{-- Uraian Tindak Lanjut --}}
                            <div>
                                <label for="pemeriksaan-tl-0-uraian" class="mb-1 block text-xs font-bold text-gray-700">Uraian Pelaksanaan Tindak Lanjut</label>
                                <input id="pemeriksaan-tl-0-uraian" type="text" name="tindak_lanjuts[0][uraian_tindak_lanjut]"
                                       class="w-full border bg-white px-3.5 py-2.5 text-sm text-gray-800 idds-form-control"
                                       placeholder="Contoh: Telah menyetor pengembalian dan memperbaiki SOP pengelolaan BMD">
                            </div>

                            {{-- Bagian Bukti Setoran Kasda / Kas Negara --}}
                            <div class="md:col-span-2 rounded-xl border border-emerald-200/80 bg-emerald-50/40 p-4">
                                <h4 class="text-xs font-bold text-emerald-900  tracking-wider mb-3">Penyetoran Kas Daerah / Kas Negara (Jika ada pengembalian kerugian)</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <label for="pemeriksaan-tl-0-nomor-setor" class="mb-1 block text-xs font-bold text-gray-700">Nomor Bukti Setor (STS / NTPN)</label>
                                        <input id="pemeriksaan-tl-0-nomor-setor" type="text" name="tindak_lanjuts[0][nomor_bukti_setor]"
                                               class="w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                               placeholder="Contoh: STS-2026/08/01 atau NTPN-12345">
                                    </div>
                                    <div>
                                        <label for="pemeriksaan-tl-0-tanggal-setor" class="mb-1 block text-xs font-bold text-gray-700">Tanggal Setoran</label>
                                        <input id="pemeriksaan-tl-0-tanggal-setor" type="date" name="tindak_lanjuts[0][tanggal_setor]"
                                               class="w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control">
                                    </div>
                                    <div>
                                        <label for="pemeriksaan-tl-0-jumlah-setor" class="mb-1 block text-xs font-bold text-gray-700">Nilai Setoran (Rp)</label>
                                        <div class="relative">
                                            <span class="absolute left-2.5 top-2 text-xs font-bold text-gray-500">Rp</span>
                                            <input id="pemeriksaan-tl-0-jumlah-setor" type="text" name="tindak_lanjuts[0][jumlah_setor]" oninput="formatRupiahLive(this)"
                                                   class="w-full border bg-white pl-8 pr-3 py-2 text-xs font-bold text-emerald-700 idds-form-control"
                                                   placeholder="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Upload Dokumen Bukti --}}
                            <div class="md:col-span-2">
                                <label for="pemeriksaan-tl-0-dokumen" class="mb-1 block text-xs font-bold text-gray-700">Unggah Berkas Bukti Tindak Lanjut / STS (PDF / JPG / PNG)</label>
                                <input id="pemeriksaan-tl-0-dokumen" type="file" name="tindak_lanjuts[0][dokumen_bukti]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                       class="block w-full text-xs text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-700 hover:file:bg-emerald-200 cursor-pointer">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        </section>

        {{-- ========================================================================= --}}
        {{-- LANGKAH 4: TINJAU DATA & SIMPAN                                           --}}
        {{-- ========================================================================= --}}
        <section id="wizard-panel-4" data-wizard-panel="4" class="wizard-panel hidden space-y-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm md:p-6">
                <div class="border-b border-gray-100 pb-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-primary-700">Langkah 4</p>
                    <h2 class="mt-1 text-lg font-bold text-gray-900">Tinjau data pemeriksaan</h2>
                    <p class="mt-1 text-sm text-gray-600">Seluruh data yang diisi ditampilkan di sini. Periksa kembali sebelum menyimpan atau mengajukan.</p>
                </div>

                <div class="mt-5 space-y-5" aria-live="polite">
                    <section>
                        <h3 class="mb-2 text-sm font-bold text-gray-900">Data SPT &amp; Tim Pemeriksa</h3>
                        <div id="wizard-review-spt" class="space-y-3"></div>
                    </section>

                    <section>
                        <h3 class="mb-2 text-sm font-bold text-gray-900">Data LHP &amp; Temuan</h3>
                        <div id="wizard-review-lhps" class="space-y-3"></div>
                    </section>

                    <section>
                        <h3 class="mb-2 text-sm font-bold text-gray-900">Tindak Lanjut Awal</h3>
                        <div id="wizard-review-tl" class="space-y-3"></div>
                    </section>
                </div>
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <a href="{{ route('pemeriksaan.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Batal</a>
                <button type="button" id="wizard-prev" class="hidden items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Kembali</button>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <button type="button" id="wizard-next" class="inline-flex items-center justify-center rounded-xl bg-primary-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-800">Lanjut</button>
                <div id="wizard-submit-actions" class="hidden flex-col gap-2 sm:flex-row sm:items-center">
                    @if(auth()->user()->isSuperAdmin())
                        <button type="submit" name="action" value="submit" class="inline-flex items-center justify-center rounded-xl bg-primary-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-800">Simpan Data Pemeriksaan</button>
                    @else
                        <button type="submit" name="action" value="draft" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Simpan sebagai Draft</button>
                        <button type="submit" name="action" value="submit" class="inline-flex items-center justify-center rounded-xl bg-primary-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-800">Simpan &amp; Ajukan ke Super Admin</button>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>

{{-- HTML TEMPLATES --}}
<template id="anggota-tim-template">
    <div class="anggota-tim-row flex items-center gap-2 animate-fade-in" data-anggota-idx="__INDEX__">
        <div class="flex-1">
            <select name="tim_pemeriksa[__INDEX__][pegawai_id]" aria-label="Pilih pegawai anggota tim pemeriksa" class="w-full border bg-white px-3 py-2 text-xs text-gray-800 shadow-2xs idds-form-control">
                <option value="">-- Pilih Pegawai (Anggota Tim) --</option>
                @foreach($pegawais as $p)
                    <option value="{{ $p->id }}">{{ $p->nama }} — {{ $p->jabatan }}</option>
                @endforeach
            </select>
            <input type="hidden" name="tim_pemeriksa[__INDEX__][peran_tim]" value="Anggota Tim">
        </div>
        <button type="button" onclick="removeAnggotaTimRow(this)" class="btn-remove-anggota p-2 text-gray-500 hover:text-state-danger hover:bg-red-50 rounded-xl transition cursor-pointer" title="Hapus Anggota Ini">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
        </button>
    </div>
</template>

<template id="lhp-template">
    <div class="lhp-card rounded-2xl border-2 border-primary-200 bg-white p-5 md:p-6 shadow-sm relative transition animate-fade-in" data-lhp-index="__INDEX__">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
            <div class="flex items-center gap-2">
                <x-sequence-badge type="lhp">
                    LHP #__NUM__
                </x-sequence-badge>
                <span class="text-xs text-gray-500 font-bold">Laporan Hasil Pemeriksaan Tambahan</span>
            </div>
            <button type="button" onclick="removeLhpCard(this)" class="btn-remove-lhp text-xs font-bold text-state-danger hover:text-red-700 hover:underline cursor-pointer">
                Hapus LHP Ini
            </button>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label for="pemeriksaan-lhp-__INDEX__-nomor" class="mb-1 block text-xs font-bold text-gray-700">No. LHP <span class="text-state-danger">*</span></label>
                <input id="pemeriksaan-lhp-__INDEX__-nomor" type="text" name="lhps[__INDEX__][nomor_lhp]" required oninput="syncLhpOptionsToTindakLanjut()"
                       class="input-nomor-lhp w-full border bg-white px-3.5 py-2.5 text-sm text-gray-800 idds-form-control"
                       placeholder="Contoh: 700/0__NUM__/LHP/ITPROV/2026">
            </div>

            <div>
                <label for="pemeriksaan-lhp-__INDEX__-tanggal" class="mb-1 block text-xs font-bold text-gray-700">Tanggal LHP <span class="text-state-danger">*</span></label>
                <input id="pemeriksaan-lhp-__INDEX__-tanggal" type="date" name="lhps[__INDEX__][tanggal_lhp]" value="{{ date('Y-m-d') }}" required
                       class="w-full border bg-white px-3.5 py-2.5 text-sm text-gray-800 idds-form-control">
            </div>

            <div class="md:col-span-2">
                <label for="pemeriksaan-lhp-__INDEX__-judul" class="mb-1 block text-xs font-bold text-gray-700">Judul LHP</label>
                <input id="pemeriksaan-lhp-__INDEX__-judul" type="text" name="lhps[__INDEX__][judul_lhp]" oninput="syncLhpOptionsToTindakLanjut()"
                       class="input-judul-lhp w-full border bg-white px-3.5 py-2.5 text-sm text-gray-800 idds-form-control"
                       placeholder="Judul / Perihal LHP #__NUM__">
            </div>

            {{-- Daftar OPD Target (Multi-Select Searchable) --}}
            <div class="md:col-span-2 rounded-2xl border border-primary-200/90 bg-primary-50/40 p-4">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-gray-800">
                        Daftar OPD Target <span class="text-state-danger">*</span>
                        <span class="text-xs font-normal text-gray-500 ml-1">(Dapat memilih lebih dari 1 OPD target)</span>
                    </label>
                    <span class="selected-opd-count text-xs font-bold text-primary-700 bg-primary-100 px-2.5 py-0.5 rounded-full">
                        0 OPD Terpilih
                    </span>
                </div>

                {{-- Kotak Pencarian OPD & Quick Action --}}
                <div class="flex items-center gap-2 mb-2">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </span>
                        <input type="text" oninput="filterOpdOptions(this)"
                               class="opd-search-input w-full border bg-white pl-9 pr-4 py-2 text-xs text-gray-800 placeholder-gray-400 shadow-2xs idds-form-control"
                               placeholder="Cari OPD (misal: Pendidikan, Kesehatan, PUPR)..." aria-label="Cari OPD untuk LHP #__NUM__">
                    </div>
                    <button type="button" onclick="clearOpdSelection(this)"
                            class="text-xs font-bold text-gray-500 hover:text-state-danger bg-white border border-gray-200 hover:border-red-200 px-3 py-2 rounded-xl transition shrink-0 cursor-pointer shadow-2xs"
                            title="Hapus semua centang OPD">
                        Bersihkan
                    </button>
                </div>

                {{-- List Checkbox OPD (Scrollable) --}}
                <div class="opd-checkbox-list max-h-44 overflow-y-auto space-y-1 rounded-xl border border-gray-200 bg-white p-2 divide-y divide-gray-50">
                    @foreach($opds as $o)
                        <label class="opd-item flex items-center justify-between gap-2 p-1.5 rounded-lg hover:bg-primary-50/70 cursor-pointer transition text-xs select-none" data-search="{{ strtolower($o->kode . ' ' . $o->nama . ' ' . $o->singkatan) }}">
                            <div class="flex items-center gap-2.5">
                                <input type="checkbox" name="lhps[__INDEX__][opd_ids][]" value="{{ $o->id }}"
                                       onchange="updateOpdSelection(this)"
                                       class="opd-checkbox h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
                                       data-name="{{ $o->nama }}" data-code="{{ $o->kode }}">
                                <span class="font-bold text-primary-900">[{{ $o->kode }}]</span>
                                <span class="text-gray-700 font-medium">{{ $o->nama }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>

                {{-- Badge / Chips OPD yang Dipilih --}}
                <div class="mt-2.5">
                    <span class="text-xs font-bold text-gray-600  tracking-wider block mb-1">OPD Terpilih:</span>
                    <div class="selected-opd-chips flex flex-wrap gap-1.5 min-h-[32px] items-center p-2 rounded-xl bg-white border border-dashed border-gray-200" data-opd-chip-mode="removable">
                        <span class="empty-opd-placeholder text-xs text-gray-500 italic">Belum ada OPD yang dipilih (centang minimal 1 OPD di atas)</span>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2">
                <label for="pemeriksaan-lhp-__INDEX__-ringkasan" class="mb-1 block text-xs font-bold text-gray-700">Uraian Singkat / Ringkasan Hasil Pemeriksaan</label>
                <textarea id="pemeriksaan-lhp-__INDEX__-ringkasan" name="lhps[__INDEX__][ringkasan]" rows="2"
                          class="w-full border bg-white px-3.5 py-2 text-sm text-gray-800 idds-form-control"
                          placeholder="Tuliskan uraian ringkas pokok hasil temuan..."></textarea>
            </div>

            {{-- Pilihan Unsur Temuan: Administrasi & Kerugian Daerah --}}
            <div class="md:col-span-2 rounded-2xl border border-amber-200 bg-amber-50/50 p-4 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-amber-900 tracking-wider mb-1">
                        Unsur Temuan &amp; Rekomendasi <span class="text-state-danger">*</span>
                    </label>
                    <p class="text-xs text-amber-800/80 mb-3">Isi minimal satu temuan administrasi atau kerugian untuk setiap LHP. Uraian dan rekomendasi wajib dilengkapi.</p>

                    <div class="flex flex-wrap gap-4 items-center">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none bg-white px-4 py-2 rounded-xl border border-amber-300 shadow-2xs hover:bg-amber-50 transition">
                            <input type="checkbox" name="lhps[__INDEX__][ada_administrasi]" value="1"
                                   onchange="toggleUnsurTemuan(this, '__INDEX__', 'administrasi')"
                                   class="chk-unsur-administrasi h-4 w-4 rounded border-gray-300 text-state-warning focus:ring-amber-500 cursor-pointer">
                            <span class="text-xs font-bold text-gray-800">Unsur Administrasi</span>
                        </label>

                        <label class="inline-flex items-center gap-2 cursor-pointer select-none bg-white px-4 py-2 rounded-xl border border-amber-300 shadow-2xs hover:bg-amber-50 transition">
                            <input type="checkbox" name="lhps[__INDEX__][ada_kerugian]" value="1"
                                   onchange="toggleUnsurTemuan(this, '__INDEX__', 'kerugian')"
                                   class="chk-unsur-kerugian h-4 w-4 rounded border-gray-300 text-state-warning focus:ring-amber-500 cursor-pointer">
                            <span class="text-xs font-bold text-gray-800">Unsur Kerugian (Daerah / Negara)</span>
                        </label>
                    </div>
                </div>

                {{-- CONTAINER 1: TEMUAN ADMINISTRASI (Multi-Temuan Dinamis) --}}
                <div class="container-temuan-administrasi hidden space-y-3 pt-2 border-t border-amber-200/80">
                    <div class="flex items-center justify-between">
                        <h5 class="text-xs font-bold text-primary-900  tracking-wider flex items-center gap-1.5">
                            <span>Daftar Temuan Administrasi</span>
                        </h5>
                        <button type="button" onclick="addTemuanAdministrasiItem(this, '__INDEX__')"
                                class="inline-flex items-center gap-1 text-xs font-bold text-primary-700 bg-primary-100 hover:bg-primary-200 px-3 py-1.5 rounded-lg transition cursor-pointer">
                            + Tambah Temuan Administrasi
                        </button>
                    </div>

                    <div class="list-temuan-administrasi space-y-3">
                        {{-- Item Temuan Administrasi #1 --}}
                        <div class="item-temuan-adm bg-white p-3.5 rounded-xl border border-primary-200 shadow-2xs space-y-2.5" data-adm-idx="0">
                            <div class="flex items-center justify-between">
                                <span class="adm-label text-xs font-bold text-primary-700 bg-primary-50 px-2 py-0.5 rounded-md">Temuan Administrasi #1</span>
                                <button type="button" onclick="removeTemuanAdministrasiItem(this)" class="btn-remove-adm hidden text-xs font-bold text-state-danger hover:text-red-700 hover:underline cursor-pointer">
                                    Hapus Temuan Ini
                                </button>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Uraian Temuan Administrasi <span class="text-state-danger">*</span></label>
                                <textarea name="lhps[__INDEX__][temuans_administrasi][0][uraian]" rows="2"
                                          class="input-adm-uraian w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                          placeholder="Uraian kelemahan administrasi..."></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Penyebab (Sebab) <span class="text-gray-500 font-normal">(Opsional)</span></label>
                                <textarea name="lhps[__INDEX__][temuans_administrasi][0][penyebab]" rows="2"
                                          class="input-adm-penyebab w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                          placeholder="Penyebab kelemahan administrasi..."></textarea>
                            </div>
                            <div class="space-y-1.5 pt-1">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-gray-700">Poin-Poin Rekomendasi Bertahap <span class="text-state-danger">*</span></label>
                                    <button type="button" onclick="addRekomendasiPointPemeriksaan(this)" class="inline-flex items-center gap-1 text-xs font-bold text-primary-700 hover:text-primary-900 bg-primary-50 hover:bg-primary-100 px-2.5 py-1 rounded-lg border border-primary-200 transition cursor-pointer">
                                        + Tambah Poin Rekomendasi
                                    </button>
                                </div>
                                <div class="list-rekomendasi-points space-y-1.5">
                                    <div class="item-rek-point flex items-start gap-1.5 bg-primary-50/30 p-2 rounded-lg border border-primary-100/80">
                                        <span class="rek-point-num text-xs font-bold bg-primary-600 text-white w-5 h-5 flex items-center justify-center rounded shrink-0 mt-0.5 shadow-2xs">1</span>
                                        <div class="flex-1">
                                            <textarea name="lhps[__INDEX__][temuans_administrasi][0][rekomendasi_items][]" rows="2"
                                                      class="input-adm-rekomendasi-item w-full border bg-white px-2.5 py-1.5 text-xs text-gray-800 idds-form-control"
                                                      placeholder="Rekomendasi perbaikan administrasi..."></textarea>
                                        </div>
                                        <button type="button" onclick="removeRekomendasiPointPemeriksaan(this)" class="btn-remove-rek-point hidden text-gray-500 hover:text-state-danger p-1 cursor-pointer mt-0.5" title="Hapus poin rekomendasi">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CONTAINER 2: TEMUAN KERUGIAN (Multi-Temuan Dinamis) --}}
                <div class="container-temuan-kerugian hidden space-y-3 pt-2 border-t border-amber-200/80">
                    <div class="flex items-center justify-between">
                        <h5 class="text-xs font-bold text-rose-900  tracking-wider flex items-center gap-1.5">
                            <span>Daftar Temuan Kerugian (Daerah / Negara)</span>
                        </h5>
                        <button type="button" onclick="addTemuanKerugianItem(this, '__INDEX__')"
                                class="inline-flex items-center gap-1 text-xs font-bold text-rose-700 bg-rose-100 hover:bg-rose-200 px-3 py-1.5 rounded-lg transition cursor-pointer">
                            + Tambah Temuan Kerugian
                        </button>
                    </div>

                    <div class="list-temuan-kerugian space-y-3">
                        {{-- Item Temuan Kerugian #1 --}}
                        <div class="item-temuan-kg bg-white p-3.5 rounded-xl border border-rose-200 shadow-2xs space-y-2.5" data-kg-idx="0">
                            <div class="flex items-center justify-between">
                                <span class="kg-label text-xs font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-md">Temuan Kerugian #1</span>
                                <button type="button" onclick="removeTemuanKerugianItem(this)" class="btn-remove-kg hidden text-xs font-bold text-state-danger hover:text-red-700 hover:underline cursor-pointer">
                                    Hapus Temuan Ini
                                </button>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Uraian Temuan Kerugian <span class="text-state-danger">*</span></label>
                                <textarea name="lhps[__INDEX__][temuans_kerugian][0][uraian]" rows="2"
                                          class="input-kg-uraian w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                          placeholder="Uraian temuan kerugian..."></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Kategori / Sumber Kerugian <span class="text-state-danger">*</span></label>
                                    <select name="lhps[__INDEX__][temuans_kerugian][0][kategori_kerugian]" class="input-kg-kategori w-full border bg-white px-3 py-2 text-xs font-semibold text-gray-800 idds-form-control">
                                        <option value="Kerugian Daerah" selected>Kerugian Daerah (Kasda / APBD)</option>
                                        <option value="Kerugian Negara">Kerugian Negara (Kas Negara / APBN)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Klasifikasi Penyelesaian <span class="text-state-danger">*</span></label>
                                    <div class="flex items-center gap-4 mt-2">
                                        <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                            <input type="radio" name="lhps[__INDEX__][temuans_kerugian][0][jenis_kerugian]" value="Internal" class="input-kg-jenis h-3.5 w-3.5 text-state-danger focus:ring-rose-500">
                                            <span>Internal (ASN/OPD)</span>
                                        </label>
                                        <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                            <input type="radio" name="lhps[__INDEX__][temuans_kerugian][0][jenis_kerugian]" value="Eksternal" class="input-kg-jenis h-3.5 w-3.5 text-state-danger focus:ring-rose-500">
                                            <span>Eksternal (Rekanan)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Nominal Nilai Kerugian (Rp) <span class="text-state-danger">*</span></label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-xs font-bold text-gray-500">Rp</span>
                                    <input type="text" name="lhps[__INDEX__][temuans_kerugian][0][nilai_kerugian]" oninput="formatRupiahLive(this)"
                                           class="input-kg-nilai w-full border bg-white pl-9 pr-3 py-2 text-sm font-bold text-emerald-700 idds-form-control"
                                           placeholder="0">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Penyebab (Sebab) <span class="text-gray-500 font-normal">(Opsional)</span></label>
                                <textarea name="lhps[__INDEX__][temuans_kerugian][0][penyebab]" rows="2"
                                          class="input-kg-penyebab w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                          placeholder="Penyebab temuan kerugian..."></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Rekomendasi Penyetoran <span class="text-state-danger">*</span></label>
                                <textarea name="lhps[__INDEX__][temuans_kerugian][0][rekomendasi]" rows="2"
                                          class="input-kg-rekomendasi w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                          placeholder="Rekomendasi tindak lanjut penyetoran..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2">
                <label for="pemeriksaan-lhp-__INDEX__-dokumen" class="mb-1 block text-xs font-bold text-gray-700">Unggah File Dokumen LHP (PDF / DOC / DOCX)</label>
                <input id="pemeriksaan-lhp-__INDEX__-dokumen" type="file" name="lhps[__INDEX__][dokumen_lhp]" accept=".pdf,.doc,.docx"
                       class="block w-full text-xs text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary-100 file:text-primary-700 hover:file:bg-primary-200 cursor-pointer">
            </div>
        </div>
    </div>
</template>

<template id="tl-template">
    <div class="tl-card rounded-2xl border border-emerald-200 bg-white p-5 md:p-6 shadow-sm relative transition animate-fade-in" data-tl-index="__INDEX__">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
            <div class="flex items-center gap-2">
                <x-sequence-badge type="tindak-lanjut">
                    Tindak Lanjut #__NUM__
                </x-sequence-badge>
                <span class="text-xs text-gray-500 font-bold">Tindak Lanjut Tambahan</span>
            </div>
            <button type="button" onclick="removeTlCard(this)" class="btn-remove-tl text-xs font-bold text-state-danger hover:text-red-700 hover:underline cursor-pointer">
                Hapus Tindak Lanjut
            </button>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="pemeriksaan-tl-__INDEX__-lhp" class="mb-1 block text-xs font-bold text-gray-700">Pilih LHP Terkait <span class="text-state-danger">*</span></label>
                <select id="pemeriksaan-tl-__INDEX__-lhp" name="tindak_lanjuts[__INDEX__][lhp_index]" class="select-target-lhp w-full border bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-800 idds-form-control">
                    <option value="0">LHP #1</option>
                </select>
            </div>

            <div>
                <label for="pemeriksaan-tl-__INDEX__-tanggal" class="mb-1 block text-xs font-bold text-gray-700">Tanggal Tindak Lanjut</label>
                <input id="pemeriksaan-tl-__INDEX__-tanggal" type="date" name="tindak_lanjuts[__INDEX__][tanggal_tindak_lanjut]" value="{{ date('Y-m-d') }}"
                       class="w-full border bg-white px-3.5 py-2.5 text-sm text-gray-800 idds-form-control">
            </div>

            <div>
                <label for="pemeriksaan-tl-__INDEX__-uraian" class="mb-1 block text-xs font-bold text-gray-700">Uraian Pelaksanaan Tindak Lanjut</label>
                <input id="pemeriksaan-tl-__INDEX__-uraian" type="text" name="tindak_lanjuts[__INDEX__][uraian_tindak_lanjut]"
                       class="w-full border bg-white px-3.5 py-2.5 text-sm text-gray-800 idds-form-control"
                       placeholder="Uraian penyelesaian...">
            </div>

            <div class="md:col-span-2 rounded-xl border border-emerald-200/80 bg-emerald-50/40 p-4">
                <h4 class="text-xs font-bold text-emerald-900  tracking-wider mb-3">Penyetoran Kas Daerah</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label for="pemeriksaan-tl-__INDEX__-nomor-setor" class="mb-1 block text-xs font-bold text-gray-700">Nomor Bukti Setor / STS</label>
                        <input id="pemeriksaan-tl-__INDEX__-nomor-setor" type="text" name="tindak_lanjuts[__INDEX__][nomor_bukti_setor]"
                               class="w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                               placeholder="No. STS">
                    </div>
                    <div>
                        <label for="pemeriksaan-tl-__INDEX__-tanggal-setor" class="mb-1 block text-xs font-bold text-gray-700">Tanggal Setoran</label>
                        <input id="pemeriksaan-tl-__INDEX__-tanggal-setor" type="date" name="tindak_lanjuts[__INDEX__][tanggal_setor]"
                               class="w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control">
                    </div>
                    <div>
                        <label for="pemeriksaan-tl-__INDEX__-jumlah-setor" class="mb-1 block text-xs font-bold text-gray-700">Nilai Setoran (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-2.5 top-2 text-xs font-bold text-gray-500">Rp</span>
                            <input id="pemeriksaan-tl-__INDEX__-jumlah-setor" type="text" name="tindak_lanjuts[__INDEX__][jumlah_setor]" oninput="formatRupiahLive(this)"
                                   class="w-full border bg-white pl-8 pr-3 py-2 text-xs font-bold text-emerald-700 idds-form-control"
                                   placeholder="0">
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2">
                <label for="pemeriksaan-tl-__INDEX__-dokumen" class="mb-1 block text-xs font-bold text-gray-700">Unggah Berkas Bukti Tindak Lanjut / STS</label>
                <input id="pemeriksaan-tl-__INDEX__-dokumen" type="file" name="tindak_lanjuts[__INDEX__][dokumen_bukti]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                       class="block w-full text-xs text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-700 hover:file:bg-emerald-200 cursor-pointer">
            </div>
        </div>
    </div>
</template>

{{-- JAVASCRIPT --}}
<script>
    window.lhpCounter = 1;
    window.tlCounter = 1;

    window.addLhpCard = function() {
        const template = document.getElementById('lhp-template');
        const container = document.getElementById('lhp-container');
        if (!template || !container) return;

        const newIndex = window.lhpCounter++;
        const cardNum = document.querySelectorAll('.lhp-card').length + 1;

        let html = template.innerHTML
            .replace(/__INDEX__/g, newIndex)
            .replace(/__NUM__/g, cardNum);

        container.insertAdjacentHTML('beforeend', html);
        window.updateLhpBadges();
        window.syncLhpOptionsToTindakLanjut();
        window.focusFirstDynamicField(container.lastElementChild);
        window.announceDynamicChange(container, `LHP ${cardNum} ditambahkan.`);
    };

    window.removeLhpCard = function(btn) {
        const card = btn.closest('.lhp-card');
        const container = card?.parentElement;
        if (!card || !container) return;

        const cards = Array.from(container.querySelectorAll('.lhp-card'));
        const cardIndex = cards.indexOf(card);
        card.remove();
        window.updateLhpBadges();
        window.syncLhpOptionsToTindakLanjut();
        window.announceDynamicChange(container, `LHP ${cardIndex + 1} dihapus.`);

        const remainingCards = cards.filter(item => item !== card);
        window.focusFirstDynamicField(remainingCards[Math.min(cardIndex, remainingCards.length - 1)]);
    };

    window.updateLhpBadges = function() {
        window.updateDynamicCardList(document, {
            cardSelector: '.lhp-card',
            badgeSelector: '.lhp-badge',
            badgePrefix: 'LHP',
            removeButtonSelector: '.btn-remove-lhp',
        });
    };

    window.addTindakLanjutCard = function() {
        const template = document.getElementById('tl-template');
        const container = document.getElementById('tl-container');
        if (!template || !container) return;

        const newIndex = window.tlCounter++;
        const cardNum = document.querySelectorAll('.tl-card').length + 1;

        let html = template.innerHTML
            .replace(/__INDEX__/g, newIndex)
            .replace(/__NUM__/g, cardNum);

        container.insertAdjacentHTML('beforeend', html);
        window.updateTlBadges();
        window.syncLhpOptionsToTindakLanjut();
        window.focusFirstDynamicField(container.lastElementChild);
        window.announceDynamicChange(container, `Tindak lanjut ${cardNum} ditambahkan.`);
    };

    window.removeTlCard = function(btn) {
        const card = btn.closest('.tl-card');
        const container = card?.parentElement;
        if (!card || !container) return;

        const cards = Array.from(container.querySelectorAll('.tl-card'));
        const cardIndex = cards.indexOf(card);
        card.remove();
        window.updateTlBadges();
        window.announceDynamicChange(container, `Tindak lanjut ${cardIndex + 1} dihapus.`);

        const remainingCards = cards.filter(item => item !== card);
        window.focusFirstDynamicField(remainingCards[Math.min(cardIndex, remainingCards.length - 1)]);
    };

    window.updateTlBadges = function() {
        window.updateDynamicCardList(document, {
            cardSelector: '.tl-card',
            badgeSelector: '.tl-badge',
            badgePrefix: 'Tindak Lanjut',
            removeButtonSelector: '.btn-remove-tl',
        });
    };

    // Tambah item temuan administrasi baru di dalam LHP tertentu
    window.addTemuanAdministrasiItem = function(btn, lhpIdx) {
        const card = btn.closest('.lhp-card');
        if (!card) return;

        const listContainer = card.querySelector('.list-temuan-administrasi');
        if (!listContainer) return;

        const items = listContainer.querySelectorAll('.item-temuan-adm');
        const nextIdx = items.length;
        const nextNum = nextIdx + 1;
        const actualLhpIdx = card.getAttribute('data-lhp-index') || lhpIdx;

        const html = `
            <div class="item-temuan-adm bg-white p-3.5 rounded-xl border border-primary-200 shadow-2xs space-y-2.5 animate-fade-in" data-adm-idx="${nextIdx}">
                <div class="flex items-center justify-between">
                    <span class="adm-label text-xs font-bold text-primary-700 bg-primary-50 px-2 py-0.5 rounded-md">Temuan Administrasi #${nextNum}</span>
                    <button type="button" onclick="removeTemuanAdministrasiItem(this)" class="btn-remove-adm text-xs font-bold text-state-danger hover:text-red-700 hover:underline cursor-pointer">
                        Hapus Temuan Ini
                    </button>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Uraian Temuan Administrasi <span class="text-state-danger">*</span></label>
                    <textarea name="lhps[${actualLhpIdx}][temuans_administrasi][${nextIdx}][uraian]" rows="2"
                              class="input-adm-uraian w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                              placeholder="Contoh: Dokumen pertanggungjawaban SPJ belum dilengkapi bukti pengeluaran yang sah..."></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Penyebab (Sebab) <span class="text-gray-500 font-normal">(Opsional)</span></label>
                    <textarea name="lhps[${actualLhpIdx}][temuans_administrasi][${nextIdx}][penyebab]" rows="2"
                              class="input-adm-penyebab w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                              placeholder="Contoh: Kurangnya ketelitian bendahara pengeluaran serta lemahnya pengawasan atasan langsung..."></textarea>
                </div>
                <div class="space-y-1.5 pt-1">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-gray-700">Poin-Poin Rekomendasi Bertahap <span class="text-state-danger">*</span></label>
                        <button type="button" onclick="addRekomendasiPointPemeriksaan(this)" class="inline-flex items-center gap-1 text-xs font-bold text-primary-700 hover:text-primary-900 bg-primary-50 hover:bg-primary-100 px-2.5 py-1 rounded-lg border border-primary-200 transition cursor-pointer">
                            + Tambah Poin Rekomendasi
                        </button>
                    </div>
                    <div class="list-rekomendasi-points space-y-1.5">
                        <div class="item-rek-point flex items-start gap-1.5 bg-primary-50/30 p-2 rounded-lg border border-primary-100/80">
                            <span class="rek-point-num text-xs font-bold bg-primary-600 text-white w-5 h-5 flex items-center justify-center rounded shrink-0 mt-0.5 shadow-2xs">1</span>
                            <div class="flex-1">
                                <textarea name="lhps[${actualLhpIdx}][temuans_administrasi][${nextIdx}][rekomendasi_items][]" rows="2"
                                          class="input-adm-rekomendasi-item w-full border bg-white px-2.5 py-1.5 text-xs text-gray-800 idds-form-control"
                                          placeholder="Contoh: Melengkapi bukti pertanggungjawaban SPJ sesuai ketentuan perundang-undangan..."></textarea>
                            </div>
                            <button type="button" onclick="removeRekomendasiPointPemeriksaan(this)" class="btn-remove-rek-point hidden text-gray-500 hover:text-state-danger p-1 cursor-pointer mt-0.5" title="Hapus poin rekomendasi">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        listContainer.insertAdjacentHTML('beforeend', html);
        window.updateTemuanAdministrasiLabels(card);
        window.focusFirstDynamicField(listContainer.lastElementChild);
        window.announceDynamicChange(listContainer, `Temuan administrasi ${nextNum} ditambahkan pada LHP ${Number(actualLhpIdx) + 1}.`);
    };

    // Tambah & Hapus Poin Rekomendasi Bertahap di Form Pemeriksaan
    window.addRekomendasiPointPemeriksaan = function(btn) {
        window.addRecommendationPoint(
            btn,
            '.item-temuan-adm',
            'removeRekomendasiPointPemeriksaan',
            window.updateRekomendasiPointBadges
        );
    };

    window.removeRekomendasiPointPemeriksaan = function(btn) {
        window.removeRecommendationPoint(btn, window.updateRekomendasiPointBadges);
    };

    window.updateRekomendasiPointBadges = function(list) {
        window.updateRecommendationPointBadges(list);
    };

    // Hapus item temuan administrasi
    window.removeTemuanAdministrasiItem = function(btn) {
        const card = btn.closest('.lhp-card');
        const item = btn.closest('.item-temuan-adm');
        const listContainer = item?.parentElement;
        if (!item || !card || !listContainer) return;

        const items = Array.from(listContainer.querySelectorAll('.item-temuan-adm'));
        if (items.length <= 1) return;
        const itemIndex = items.indexOf(item);
        const lhpIndex = Number(card.getAttribute('data-lhp-index') || 0);
        item.remove();
        window.updateTemuanAdministrasiLabels(card);
        window.announceDynamicChange(listContainer, `Temuan administrasi ${itemIndex + 1} dihapus dari LHP ${lhpIndex + 1}.`);

        const remainingItems = items.filter(remainingItem => remainingItem !== item);
        window.focusFirstDynamicField(remainingItems[Math.min(itemIndex, remainingItems.length - 1)]);
    };

    // Update label urutan Temuan Administrasi #1, #2, dst.
    window.updateTemuanAdministrasiLabels = function(card) {
        window.updateLhpFindingItems(card, 'administrasi');
    };

    // Tambah item temuan kerugian baru di dalam LHP tertentu
    window.addTemuanKerugianItem = function(btn, lhpIdx) {
        const card = btn.closest('.lhp-card');
        if (!card) return;

        const listContainer = card.querySelector('.list-temuan-kerugian');
        if (!listContainer) return;

        const items = listContainer.querySelectorAll('.item-temuan-kg');
        const nextIdx = items.length;
        const nextNum = nextIdx + 1;
        const actualLhpIdx = card.getAttribute('data-lhp-index') || lhpIdx;

        const html = `
            <div class="item-temuan-kg bg-white p-3.5 rounded-xl border border-rose-200 shadow-2xs space-y-2.5 animate-fade-in" data-kg-idx="${nextIdx}">
                <div class="flex items-center justify-between">
                    <span class="kg-label text-xs font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-md">Temuan Kerugian #${nextNum}</span>
                    <button type="button" onclick="removeTemuanKerugianItem(this)" class="btn-remove-kg text-xs font-bold text-state-danger hover:text-red-700 hover:underline cursor-pointer">
                        Hapus Temuan Ini
                    </button>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Uraian Temuan Kerugian <span class="text-state-danger">*</span></label>
                    <textarea name="lhps[${actualLhpIdx}][temuans_kerugian][${nextIdx}][uraian]" rows="2"
                              class="input-kg-uraian w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                              placeholder="Contoh: Terdapat kelebihan pembayaran belanja perjalanan dinas..."></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Kategori / Sumber Kerugian <span class="text-state-danger">*</span></label>
                        <select name="lhps[${actualLhpIdx}][temuans_kerugian][${nextIdx}][kategori_kerugian]" class="input-kg-kategori w-full border bg-white px-3 py-2 text-xs font-semibold text-gray-800 idds-form-control">
                            <option value="Kerugian Daerah" selected>Kerugian Daerah (Kasda / APBD)</option>
                            <option value="Kerugian Negara">Kerugian Negara (Kas Negara / APBN)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Klasifikasi Penyelesaian <span class="text-state-danger">*</span></label>
                        <div class="flex items-center gap-4 mt-2">
                            <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                <input type="radio" name="lhps[${actualLhpIdx}][temuans_kerugian][${nextIdx}][jenis_kerugian]" value="Internal" class="input-kg-jenis h-3.5 w-3.5 text-state-danger focus:ring-rose-500">
                                <span>Internal (ASN/OPD)</span>
                            </label>
                            <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                <input type="radio" name="lhps[${actualLhpIdx}][temuans_kerugian][${nextIdx}][jenis_kerugian]" value="Eksternal" class="input-kg-jenis h-3.5 w-3.5 text-state-danger focus:ring-rose-500">
                                <span>Eksternal (Rekanan)</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nominal Nilai Kerugian (Rp) <span class="text-state-danger">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-xs font-bold text-gray-500">Rp</span>
                        <input type="text" name="lhps[${actualLhpIdx}][temuans_kerugian][${nextIdx}][nilai_kerugian]" oninput="formatRupiahLive(this)"
                               class="input-kg-nilai w-full border bg-white pl-9 pr-3 py-2 text-sm font-bold text-emerald-700 idds-form-control"
                               placeholder="0">
                    </div>
                    <p class="mt-1 text-xs text-gray-500 italic">Ketik nominal tanpa titik, sistem memformat otomatis.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Penyebab (Sebab) <span class="text-gray-500 font-normal">(Opsional)</span></label>
                    <textarea name="lhps[${actualLhpIdx}][temuans_kerugian][${nextIdx}][penyebab]" rows="2"
                              class="input-kg-penyebab w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                              placeholder="Contoh: Kurangnya ketelitian dalam verifikasi bukti pertanggungjawaban..."></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Rekomendasi Penyetoran <span class="text-state-danger">*</span></label>
                    <textarea name="lhps[${actualLhpIdx}][temuans_kerugian][${nextIdx}][rekomendasi]" rows="2"
                              class="input-kg-rekomendasi w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                              placeholder="Contoh: Menyetorkan kelebihan pembayaran ke Kas Daerah / Kas Negara sebesar nominal kerugian..."></textarea>
                </div>
            </div>
        `;

        listContainer.insertAdjacentHTML('beforeend', html);
        window.updateTemuanKerugianLabels(card);
        window.focusFirstDynamicField(listContainer.lastElementChild);
        window.announceDynamicChange(listContainer, `Temuan kerugian ${nextNum} ditambahkan pada LHP ${Number(actualLhpIdx) + 1}.`);
    };

    // Hapus item temuan kerugian
    window.removeTemuanKerugianItem = function(btn) {
        const card = btn.closest('.lhp-card');
        const item = btn.closest('.item-temuan-kg');
        const listContainer = item?.parentElement;
        if (!item || !card || !listContainer) return;

        const items = Array.from(listContainer.querySelectorAll('.item-temuan-kg'));
        if (items.length <= 1) return;
        const itemIndex = items.indexOf(item);
        const lhpIndex = Number(card.getAttribute('data-lhp-index') || 0);
        item.remove();
        window.updateTemuanKerugianLabels(card);
        window.announceDynamicChange(listContainer, `Temuan kerugian ${itemIndex + 1} dihapus dari LHP ${lhpIndex + 1}.`);

        const remainingItems = items.filter(remainingItem => remainingItem !== item);
        window.focusFirstDynamicField(remainingItems[Math.min(itemIndex, remainingItems.length - 1)]);
    };

    // Update label urutan Temuan Kerugian #1, #2, dst.
    window.updateTemuanKerugianLabels = function(card) {
        window.updateLhpFindingItems(card, 'kerugian');
    };

    window.anggotaTimCounter = 5;

    // Tambah baris Anggota Tim Pemeriksa baru
    window.addAnggotaTimRow = function() {
        const template = document.getElementById('anggota-tim-template');
        const container = document.getElementById('anggota-tim-container');
        if (!template || !container) return;

        const idx = window.anggotaTimCounter++;
        let html = template.innerHTML.replace(/__INDEX__/g, idx);

        container.insertAdjacentHTML('beforeend', html);
        window.updateAnggotaRemoveButtons();
        window.focusFirstDynamicField(container.lastElementChild);
        window.announceDynamicChange(container, `Anggota tim ke-${container.querySelectorAll('.anggota-tim-row').length} ditambahkan.`);
    };

    // Hapus baris Anggota Tim Pemeriksa
    window.removeAnggotaTimRow = function(btn) {
        const row = btn.closest('.anggota-tim-row');
        const container = row?.parentElement;
        if (!row || !container) return;

        const rows = Array.from(container.querySelectorAll('.anggota-tim-row'));
        if (rows.length <= 1) return;
        const rowIndex = rows.indexOf(row);
        row.remove();
        window.updateAnggotaRemoveButtons();
        window.announceDynamicChange(container, `Anggota tim ke-${rowIndex + 1} dihapus.`);

        const remainingRows = rows.filter(item => item !== row);
        window.focusFirstDynamicField(remainingRows[Math.min(rowIndex, remainingRows.length - 1)]);
    };

    // Update visibilitas tombol hapus baris anggota
    window.updateAnggotaRemoveButtons = function() {
        const rows = document.querySelectorAll('#anggota-tim-container .anggota-tim-row');
        rows.forEach(r => {
            const btn = r.querySelector('.btn-remove-anggota');
            if (btn) {
                if (rows.length > 1) {
                    btn.classList.remove('hidden');
                } else {
                    btn.classList.add('hidden');
                }
            }
        });
    };

    // Toggle Buka/Tutup Section Tindak Lanjut (Phase 1)
    window.isTlSectionOpen = false;
    window.toggleTindakLanjutSection = function() {
        window.isTlSectionOpen = !window.isTlSectionOpen;
        const notice = document.getElementById('tl-collapsed-notice');
        const section = document.getElementById('tl-expanded-section');
        const icon = document.getElementById('tl-toggle-icon');
        const text = document.getElementById('tl-toggle-text');
        const btn = document.getElementById('btn-toggle-tl');

        if (window.isTlSectionOpen) {
            if (notice) notice.classList.add('hidden');
            if (section) section.classList.remove('hidden');
            if (icon) icon.textContent = '✕';
            if (text) text.textContent = 'Sembunyikan Tindak Lanjut';
            if (btn) btn.className = 'inline-flex items-center gap-2 rounded-xl bg-emerald-600 text-white border border-emerald-600 px-4 py-2.5 text-xs font-bold shadow-2xs hover:bg-emerald-700 transition active:scale-95 cursor-pointer';
        } else {
            if (notice) notice.classList.remove('hidden');
            if (section) section.classList.add('hidden');
            if (icon) icon.textContent = '➕';
            if (text) text.textContent = 'Buka Form Tindak Lanjut';
            if (btn) btn.className = 'inline-flex items-center gap-2 rounded-xl bg-white border border-emerald-300 px-4 py-2.5 text-xs font-bold text-emerald-800 shadow-2xs hover:bg-emerald-50 transition active:scale-95 cursor-pointer';
        }
    };

    // Clear all checked OPDs in an LHP card
    window.clearOpdSelection = function(btn) {
        const card = btn.closest('.lhp-card') || btn.closest('div.rounded-2xl');
        if (!card) return;
        const checkboxes = card.querySelectorAll('.opd-checkbox');
        checkboxes.forEach(cb => { cb.checked = false; });
        if (checkboxes.length > 0) {
            window.updateOpdSelection(checkboxes[0]);
        }
    };

    // Live Calculation & Summary Stats for Fast Data Entry
    window.updateLiveSummaryStats = function() {
        let totalAdm = 0;
        let totalKg = 0;
        let sumKgNominal = 0;

        const cards = document.querySelectorAll('.lhp-card');
        cards.forEach(card => {
            const admItems = card.querySelectorAll('.item-temuan-adm');
            const chkAdm = card.querySelector('.chk-unsur-administrasi');
            if (chkAdm && chkAdm.checked) {
                totalAdm += admItems.length;
            }

            const chkKg = card.querySelector('.chk-unsur-kerugian');
            const financialItems = Array.from(card.querySelectorAll('.item-temuan-kg'));
            if (chkKg && chkKg.checked) {
                totalKg += financialItems.length;
                financialItems.forEach(item => {
                    const amountInput = item.querySelector('.input-kg-nilai');
                    const raw = (amountInput?.value || '').replace(/\D/g, '');
                    if (raw) sumKgNominal += parseInt(raw, 10);
                });
            }
        });

        const elTemuan = document.getElementById('live-total-temuan');
        const elKerugian = document.getElementById('live-total-kerugian');
        if (elTemuan) elTemuan.textContent = (totalAdm + totalKg);
        if (elKerugian) elKerugian.textContent = new Intl.NumberFormat('id-ID').format(sumKgNominal);
    };

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.lhp-card').forEach(card => {
            window.updateLhpFindingItems(card, 'administrasi');
            window.updateLhpFindingItems(card, 'kerugian');
        });

        window.syncLhpOptionsToTindakLanjut();
        window.updateLiveSummaryStats();

        // Auto-sync Tanggal SPT ke Tanggal LHP #1
        const tglSptInput = document.getElementById('tanggal_spt');
        if (tglSptInput) {
            tglSptInput.addEventListener('change', function() {
                const firstLhpDate = document.querySelector('input[name="lhps[0][tanggal_lhp]"]');
                if (firstLhpDate) {
                    firstLhpDate.value = this.value;
                }
            });
        }

        // Auto-sync Judul SPT ke Placeholder Judul LHP #1
        const judulSptInput = document.getElementById('judul');
        if (judulSptInput) {
            judulSptInput.addEventListener('input', function() {
                const firstLhpJudul = document.querySelector('input[name="lhps[0][judul_lhp]"]');
                if (firstLhpJudul && !firstLhpJudul.value) {
                    firstLhpJudul.placeholder = this.value ? ('LHP: ' + this.value) : 'Contoh: LHP Kepatuhan Keuangan dan Aset';
                }
            });
        }

        const form = document.getElementById('unified-pemeriksaan-form');
        if (form) {
            const wizardPanels = Array.from(form.querySelectorAll('[data-wizard-panel]'));
            const wizardNavItems = Array.from(form.querySelectorAll('[data-wizard-nav]'));
            const previousButton = document.getElementById('wizard-prev');
            const nextButton = document.getElementById('wizard-next');
            const submitActions = document.getElementById('wizard-submit-actions');
            const stepCaption = document.getElementById('wizard-step-caption');
            const progressBar = document.getElementById('wizard-progress-bar');
            const progressTrack = progressBar?.parentElement;
            const completedSteps = new Set();
            const stepNames = {
                1: 'SPT & Tim',
                2: 'LHP & Temuan',
                3: 'Tindak Lanjut',
                4: 'Tinjau & Simpan',
            };
            let currentStep = 1;

            function updateWizardNavigation() {
                wizardPanels.forEach(panel => {
                    const isCurrent = Number(panel.dataset.wizardPanel) === currentStep;
                    panel.classList.toggle('hidden', !isCurrent);
                    panel.setAttribute('aria-hidden', isCurrent ? 'false' : 'true');
                });

                wizardNavItems.forEach(button => {
                    const step = Number(button.dataset.wizardNav);
                    const number = button.querySelector('[data-wizard-number]');
                    const title = button.querySelector('[data-wizard-title]');
                    const canOpen = step <= currentStep || Array.from({ length: step - 1 }, (_, index) => completedSteps.has(index + 1)).every(Boolean);

                    button.disabled = !canOpen;
                    button.setAttribute('aria-current', step === currentStep ? 'step' : 'false');
                    button.classList.toggle('border-primary-200', step === currentStep);
                    button.classList.toggle('bg-primary-50', step === currentStep);
                    button.classList.toggle('border-emerald-200', completedSteps.has(step) && step !== currentStep);
                    button.classList.toggle('bg-emerald-50', completedSteps.has(step) && step !== currentStep);

                    if (number) {
                        number.classList.toggle('bg-primary-700', step === currentStep);
                        number.classList.toggle('text-white', step === currentStep || completedSteps.has(step));
                        number.classList.toggle('bg-emerald-600', completedSteps.has(step) && step !== currentStep);
                        number.classList.toggle('bg-gray-100', step !== currentStep && !completedSteps.has(step));
                        number.classList.toggle('text-gray-600', step !== currentStep && !completedSteps.has(step));
                    }

                    if (title) {
                        title.classList.toggle('text-primary-800', step === currentStep);
                        title.classList.toggle('text-emerald-800', completedSteps.has(step) && step !== currentStep);
                        title.classList.toggle('text-gray-700', step !== currentStep && !completedSteps.has(step));
                    }
                });

                if (stepCaption) {
                    stepCaption.textContent = `Langkah ${currentStep} dari ${wizardPanels.length} · ${stepNames[currentStep]}`;
                }
                if (progressBar) {
                    progressBar.style.width = `${(currentStep / wizardPanels.length) * 100}%`;
                }
                if (progressTrack) {
                    progressTrack.setAttribute('aria-valuenow', String(currentStep));
                }
                if (previousButton) {
                    previousButton.classList.toggle('hidden', currentStep === 1);
                    previousButton.classList.toggle('inline-flex', currentStep !== 1);
                }
                if (nextButton) {
                    nextButton.classList.toggle('hidden', currentStep === wizardPanels.length);
                }
                if (submitActions) {
                    submitActions.classList.toggle('hidden', currentStep !== wizardPanels.length);
                    submitActions.classList.toggle('flex', currentStep === wizardPanels.length);
                }
            }

            function showWizardStep(step, shouldScroll = true) {
                currentStep = Math.min(Math.max(Number(step), 1), wizardPanels.length);
                updateWizardNavigation();

                if (currentStep === 4) {
                    updateWizardReview();
                }
                if (shouldScroll) {
                    const navigation = form.querySelector('[aria-label="Langkah pengisian pemeriksaan"]');
                    const top = navigation ? navigation.getBoundingClientRect().top + window.scrollY - 105 : form.offsetTop;
                    window.scrollTo({ top: Math.max(top, 0), behavior: 'smooth' });
                }
            }

            function isFieldAvailableInPanel(field, panel) {
                let ancestor = field;
                while (ancestor && ancestor !== panel) {
                    if (ancestor.hidden || ancestor.classList.contains('hidden') || window.getComputedStyle(ancestor).display === 'none') {
                        return false;
                    }
                    ancestor = ancestor.parentElement;
                }
                return ancestor === panel;
            }

            function validateWizardPanel(panel) {
                const invalidField = Array.from(panel.querySelectorAll('input, select, textarea'))
                    .find(field => field.required && field.willValidate && !field.disabled
                        && isFieldAvailableInPanel(field, panel) && !field.validity.valid);

                return { valid: !invalidField, invalidField };
            }

            function validateLhpCards() {
                const lhpCards = Array.from(form.querySelectorAll('.lhp-card'));
                for (let index = 0; index < lhpCards.length; index++) {
                    const card = lhpCards[index];
                    const checkedOpds = card.querySelectorAll('.opd-checkbox:checked');
                    if (checkedOpds.length === 0) {
                        return { valid: false, card, message: `Harap pilih minimal 1 OPD Target pada LHP #${index + 1}.` };
                    }

                    const administrativeSelected = card.querySelector('.chk-unsur-administrasi')?.checked;
                    const lossSelected = card.querySelector('.chk-unsur-kerugian')?.checked;
                    const administrativeItems = Array.from(card.querySelectorAll('.item-temuan-adm'));
                    const lossItems = Array.from(card.querySelectorAll('.item-temuan-kg'));
                    const administrativeComplete = Boolean(administrativeSelected)
                        && administrativeItems.length > 0
                        && administrativeItems.every(item => {
                            const description = item.querySelector('.input-adm-uraian')?.value.trim();
                            const hasRecommendation = Array.from(item.querySelectorAll('.input-adm-rekomendasi-item'))
                                .some(input => input.value.trim() !== '');
                            return Boolean(description) && hasRecommendation;
                        });
                    const lossComplete = Boolean(lossSelected)
                        && lossItems.length > 0
                        && lossItems.every(item => {
                            const description = item.querySelector('.input-kg-uraian')?.value.trim();
                            const recommendation = item.querySelector('.input-kg-rekomendasi')?.value.trim();
                            const amount = (item.querySelector('.input-kg-nilai')?.value || '').replace(/\D/g, '');
                            const classification = item.querySelector('.input-kg-jenis:checked');
                            const category = item.querySelector('.input-kg-kategori')?.value;
                            return Boolean(description) && Boolean(recommendation) && Number(amount) > 0
                                && Boolean(classification) && ['Kerugian Daerah', 'Kerugian Negara'].includes(category);
                        });
                    const allSelectedFindingsComplete = (!administrativeSelected || administrativeComplete)
                        && (!lossSelected || lossComplete);

                    if ((!administrativeComplete && !lossComplete) || !allSelectedFindingsComplete) {
                        return {
                            valid: false,
                            card,
                            message: `Harap isi minimal satu temuan yang lengkap pada LHP #${index + 1}. Lengkapi uraian dan rekomendasi; temuan kerugian juga memerlukan klasifikasi serta nilai kerugian.`,
                        };
                    }
                }

                return { valid: true };
            }

            function markStepIncompleteFrom(step) {
                for (let index = step; index <= wizardPanels.length; index++) {
                    completedSteps.delete(index);
                }
                updateWizardNavigation();
            }

            function displayReviewValue(value, fallback = 'Belum diisi') {
                const normalizedValue = String(value ?? '').trim();
                return normalizedValue || fallback;
            }

            function getReviewFieldValue(scope, selector, fallback = 'Belum diisi') {
                const field = scope.querySelector(selector);
                if (!field) {
                    return fallback;
                }
                if (field.type === 'file') {
                    return field.files?.[0]?.name || 'Tidak dilampirkan';
                }
                if (field instanceof HTMLSelectElement) {
                    return field.selectedOptions[0]?.textContent.trim() || fallback;
                }
                return displayReviewValue(field.value, fallback);
            }

            function formatReviewDate(value) {
                const match = String(value ?? '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
                return match ? `${match[3]}/${match[2]}/${match[1]}` : displayReviewValue(value, 'Belum diisi');
            }

            function createReviewCard(container, heading, entries) {
                const card = document.createElement('article');
                card.className = 'rounded-xl border border-gray-200 bg-white p-3.5 shadow-sm';

                const title = document.createElement('h4');
                title.className = 'mb-3 break-words text-sm font-bold text-gray-900';
                title.textContent = heading;

                const details = document.createElement('dl');
                details.className = 'grid grid-cols-1 gap-x-5 gap-y-3 sm:grid-cols-2';

                entries.forEach(entry => {
                    const item = document.createElement('div');
                    item.className = 'min-w-0';

                    const label = document.createElement('dt');
                    label.className = 'text-[11px] font-semibold text-gray-500';
                    label.textContent = entry.label;

                    const value = document.createElement('dd');
                    value.className = 'mt-0.5 break-words whitespace-pre-wrap text-xs leading-relaxed text-gray-900';
                    value.textContent = displayReviewValue(entry.value, entry.fallback || 'Belum diisi');

                    item.append(label, value);
                    details.append(item);
                });

                card.append(title, details);
                container.append(card);
                return card;
            }

            function updateWizardReview() {
                const reviewSpt = document.getElementById('wizard-review-spt');
                reviewSpt.replaceChildren();

                const sourceField = form.querySelector('[name="sumber_pemeriksaan"]');
                const sourceValue = sourceField?.selectedOptions?.[0]?.textContent.trim() || sourceField?.value;
                const teamEntries = Array.from(form.querySelectorAll('input[name$="[peran_tim]"]')).map(roleField => {
                    const match = roleField.name.match(/tim_pemeriksa\[(\d+)\]\[peran_tim\]/);
                    const employeeField = match
                        ? form.querySelector(`[name="tim_pemeriksa[${match[1]}][pegawai_id]"]`)
                        : null;
                    const employeeValue = employeeField instanceof HTMLSelectElement
                        ? employeeField.selectedOptions[0]?.textContent.trim()
                        : employeeField?.value;

                    return {
                        label: roleField.value,
                        value: employeeValue || 'Belum dipilih',
                    };
                });

                createReviewCard(reviewSpt, 'Identitas SPT', [
                    { label: 'Judul / Nama Kegiatan Pemeriksaan', value: getReviewFieldValue(form, '[name="judul"]') },
                    { label: 'Nomor SPT', value: getReviewFieldValue(form, '[name="nomor_spt"]') },
                    { label: 'Tanggal SPT', value: formatReviewDate(form.querySelector('[name="tanggal_spt"]')?.value) },
                    { label: 'Sumber Pemeriksaan', value: sourceValue },
                    { label: 'Tahun Anggaran', value: getReviewFieldValue(form, '[name="tahun"]') },
                    { label: 'Tanggal Mulai Pemeriksaan', value: formatReviewDate(form.querySelector('[name="tanggal_mulai"]')?.value) },
                    { label: 'Tanggal Selesai Pemeriksaan', value: formatReviewDate(form.querySelector('[name="tanggal_selesai"]')?.value) },
                    { label: 'Dokumen SPT', value: getReviewFieldValue(form, '[name="dokumen_spt"]', 'Tidak dilampirkan') },
                ]);

                createReviewCard(reviewSpt, 'Susunan Tim Pemeriksa', teamEntries.length > 0
                    ? teamEntries
                    : [{ label: 'Pegawai', value: 'Belum ada susunan tim' }]);

                const reviewLhps = document.getElementById('wizard-review-lhps');
                reviewLhps.replaceChildren();
                const lhpCards = Array.from(form.querySelectorAll('.lhp-card'));

                lhpCards.forEach((lhpCard, lhpIndex) => {
                    const selectedOpds = Array.from(lhpCard.querySelectorAll('.opd-checkbox:checked')).map(checkbox => {
                        const code = checkbox.dataset.code;
                        const name = checkbox.dataset.name;
                        return code ? `[${code}] ${name}` : name;
                    });
                    const administrationSelected = Boolean(lhpCard.querySelector('.chk-unsur-administrasi')?.checked);
                    const lossSelected = Boolean(lhpCard.querySelector('.chk-unsur-kerugian')?.checked);
                    const lhpReviewCard = createReviewCard(reviewLhps, `LHP #${lhpIndex + 1}`, [
                        { label: 'Nomor LHP', value: getReviewFieldValue(lhpCard, '[name*="[nomor_lhp]"]') },
                        { label: 'Tanggal LHP', value: formatReviewDate(lhpCard.querySelector('[name*="[tanggal_lhp]"]')?.value) },
                        { label: 'Judul LHP', value: getReviewFieldValue(lhpCard, '[name*="[judul_lhp]"]') },
                        { label: 'OPD Target', value: selectedOpds.length ? selectedOpds.join('\n') : 'Belum ada OPD dipilih' },
                        { label: 'Ringkasan Hasil Pemeriksaan', value: getReviewFieldValue(lhpCard, '[name*="[ringkasan]"]') },
                        { label: 'Jenis Temuan', value: `Administrasi: ${administrationSelected ? 'Ya' : 'Tidak'} · Kerugian: ${lossSelected ? 'Ya' : 'Tidak'}` },
                        { label: 'Dokumen LHP', value: getReviewFieldValue(lhpCard, '[name*="[dokumen_lhp]"]', 'Tidak dilampirkan') },
                    ]);

                    const findingList = document.createElement('div');
                    findingList.className = 'mt-4 space-y-3 border-t border-gray-100 pt-3';
                    const findingHeading = document.createElement('h5');
                    findingHeading.className = 'text-xs font-bold uppercase tracking-wide text-gray-700';
                    findingHeading.textContent = 'Rincian temuan dan rekomendasi';
                    findingList.append(findingHeading);

                    if (administrationSelected) {
                        const administrativeItems = Array.from(lhpCard.querySelectorAll('.item-temuan-adm'));
                        administrativeItems.forEach((item, itemIndex) => {
                            const recommendations = Array.from(item.querySelectorAll('.input-adm-rekomendasi-item'));
                            const recommendationText = recommendations.length
                                ? recommendations.map((field, recommendationIndex) => `${recommendationIndex + 1}. ${displayReviewValue(field.value)}`).join('\n')
                                : getReviewFieldValue(item, '.input-adm-rekomendasi');
                            createReviewCard(findingList, `Temuan Administrasi #${itemIndex + 1}`, [
                                { label: 'Uraian Temuan', value: getReviewFieldValue(item, '.input-adm-uraian') },
                                { label: 'Penyebab', value: getReviewFieldValue(item, '.input-adm-penyebab', 'Tidak diisi') },
                                { label: 'Poin Rekomendasi', value: recommendationText, fallback: 'Belum ditambahkan' },
                            ]);
                        });
                    }

                    if (lossSelected) {
                        const lossItems = Array.from(lhpCard.querySelectorAll('.item-temuan-kg'));
                        lossItems.forEach((item, itemIndex) => {
                            const classification = item.querySelector('.input-kg-jenis:checked')?.closest('label')?.textContent.trim();
                            const amount = getReviewFieldValue(item, '.input-kg-nilai');
                            createReviewCard(findingList, `Temuan Kerugian #${itemIndex + 1}`, [
                                { label: 'Uraian Temuan', value: getReviewFieldValue(item, '.input-kg-uraian') },
                                { label: 'Kategori / Sumber Kerugian', value: getReviewFieldValue(item, '.input-kg-kategori') },
                                { label: 'Klasifikasi Penyelesaian', value: classification || 'Belum dipilih' },
                                { label: 'Nilai Kerugian', value: amount === 'Belum diisi' ? amount : `Rp ${amount}` },
                                { label: 'Penyebab', value: getReviewFieldValue(item, '.input-kg-penyebab', 'Tidak diisi') },
                                { label: 'Rekomendasi Penyetoran', value: getReviewFieldValue(item, '.input-kg-rekomendasi') },
                            ]);
                        });
                    }

                    if (!administrationSelected && !lossSelected) {
                        const emptyFindings = document.createElement('p');
                        emptyFindings.className = 'rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900';
                        emptyFindings.textContent = 'Belum ada jenis temuan yang dipilih.';
                        findingList.append(emptyFindings);
                    }

                    lhpReviewCard.append(findingList);
                });

                if (lhpCards.length === 0) {
                    const emptyLhp = document.createElement('p');
                    emptyLhp.className = 'rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900';
                    emptyLhp.textContent = 'Belum ada LHP ditambahkan.';
                    reviewLhps.append(emptyLhp);
                }

                const reviewTl = document.getElementById('wizard-review-tl');
                reviewTl.replaceChildren();
                const tindakLanjutCards = Array.from(form.querySelectorAll('.tl-card')).filter(card => {
                    const meaningfulFields = Array.from(card.querySelectorAll(
                        '[name$="[uraian_tindak_lanjut]"], [name$="[nomor_bukti_setor]"], [name$="[tanggal_setor]"], [name$="[jumlah_setor]"]'
                    ));
                    const hasMeaningfulValue = meaningfulFields.some(field => field.value.trim() !== '');
                    const tindakLanjutDate = card.querySelector('[name$="[tanggal_tindak_lanjut]"]');
                    const hasChangedDate = tindakLanjutDate && tindakLanjutDate.value !== tindakLanjutDate.defaultValue;
                    const hasDocument = Array.from(card.querySelectorAll('input[type="file"]')).some(field => field.files?.length);
                    return hasMeaningfulValue || hasChangedDate || hasDocument;
                });

                tindakLanjutCards.forEach((card, index) => {
                    const lhpField = card.querySelector('.select-target-lhp');
                    const lhpLabel = lhpField?.selectedOptions?.[0]?.textContent.trim() || 'Belum dipilih';
                    createReviewCard(reviewTl, `Tindak Lanjut #${index + 1}`, [
                        { label: 'LHP Terkait', value: lhpLabel },
                        { label: 'Tanggal Tindak Lanjut', value: formatReviewDate(card.querySelector('[name$="[tanggal_tindak_lanjut]"]')?.value) },
                        { label: 'Uraian Pelaksanaan', value: getReviewFieldValue(card, '[name$="[uraian_tindak_lanjut]"]') },
                        { label: 'Nomor Bukti Setor (STS / NTPN)', value: getReviewFieldValue(card, '[name$="[nomor_bukti_setor]"]', 'Tidak diisi') },
                        { label: 'Tanggal Setoran', value: formatReviewDate(card.querySelector('[name$="[tanggal_setor]"]')?.value) },
                        { label: 'Nilai Setoran', value: getReviewFieldValue(card, '[name$="[jumlah_setor]"]', 'Tidak diisi') },
                        { label: 'Dokumen Bukti', value: getReviewFieldValue(card, 'input[type="file"]', 'Tidak dilampirkan') },
                    ]);
                });

                if (tindakLanjutCards.length === 0) {
                    const noTindakLanjut = document.createElement('p');
                    noTindakLanjut.className = 'rounded-xl border border-emerald-100 bg-emerald-50/70 px-3 py-2 text-xs text-emerald-900';
                    noTindakLanjut.textContent = 'Tidak ada tindak lanjut awal yang ditambahkan. Data tindak lanjut dapat dicatat kemudian melalui menu Tindak Lanjut.';
                    reviewTl.append(noTindakLanjut);
                }
            }

            wizardNavItems.forEach(button => {
                button.addEventListener('click', function() {
                    const targetStep = Number(button.dataset.wizardNav);
                    if (targetStep <= currentStep || Array.from({ length: targetStep - 1 }, (_, index) => completedSteps.has(index + 1)).every(Boolean)) {
                        showWizardStep(targetStep);
                    }
                });
            });

            nextButton?.addEventListener('click', function() {
                const currentPanel = wizardPanels.find(panel => Number(panel.dataset.wizardPanel) === currentStep);
                const panelValidation = validateWizardPanel(currentPanel);
                if (!panelValidation.valid) {
                    panelValidation.invalidField.focus({ preventScroll: true });
                    panelValidation.invalidField.reportValidity();
                    panelValidation.invalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }

                if (currentStep === 2) {
                    const lhpValidation = validateLhpCards();
                    if (!lhpValidation.valid) {
                        alert(lhpValidation.message);
                        lhpValidation.card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return;
                    }
                }

                completedSteps.add(currentStep);
                showWizardStep(currentStep + 1);
            });

            previousButton?.addEventListener('click', function() {
                showWizardStep(currentStep - 1);
            });

            form.addEventListener('input', function(event) {
                const changedPanel = event.target.closest('[data-wizard-panel]');
                if (changedPanel) {
                    markStepIncompleteFrom(Number(changedPanel.dataset.wizardPanel));
                }
                window.updateLiveSummaryStats();
                updateWizardReview();
            });

            form.addEventListener('change', function(event) {
                const changedPanel = event.target.closest('[data-wizard-panel]');
                if (changedPanel) {
                    markStepIncompleteFrom(Number(changedPanel.dataset.wizardPanel));
                }
                window.updateLiveSummaryStats();
                updateWizardReview();
            });

            form.addEventListener('submit', function(event) {
                for (let step = 1; step <= 3; step++) {
                    const panel = wizardPanels.find(item => Number(item.dataset.wizardPanel) === step);
                    const validation = validateWizardPanel(panel);
                    if (!validation.valid) {
                        event.preventDefault();
                        markStepIncompleteFrom(step);
                        showWizardStep(step);
                        window.requestAnimationFrame(() => {
                            validation.invalidField.focus({ preventScroll: true });
                            validation.invalidField.reportValidity();
                            validation.invalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        });
                        return;
                    }

                    if (step === 2) {
                        const lhpValidation = validateLhpCards();
                        if (!lhpValidation.valid) {
                            event.preventDefault();
                            markStepIncompleteFrom(2);
                            showWizardStep(2);
                            alert(lhpValidation.message);
                            lhpValidation.card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return;
                        }
                    }

                    completedSteps.add(step);
                }
                updateWizardNavigation();
            });

            const serverErrorKeys = @json($errors->keys());
            const initialStep = serverErrorKeys.some(key => key.startsWith('lhps.'))
                ? 2
                : (serverErrorKeys.some(key => key.startsWith('tindak_lanjuts.')) ? 3 : 1);
            showWizardStep(initialStep, false);
            updateWizardReview();
        }
    });
</script>
@endsection
