@extends('layouts.app')

@section('title', 'Edit Data SPT & LHP - ' . $pemeriksaan->nomor_spt)
@section('page-title', 'Edit Data SPT & LHP')
@section('page-subtitle', 'Perbarui data Surat Perintah Tugas (SPT), Tim Pemeriksa, dan Laporan Hasil Pemeriksaan (LHP)')

@section('content')
<div class="max-w-5xl space-y-4 pb-16">
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Pemeriksaan & SPT', 'href' => route('pemeriksaan.index')],
        ['label' => $pemeriksaan->nomor_spt, 'href' => route('pemeriksaan.show', $pemeriksaan)],
        ['label' => 'Edit'],
    ]" />
    <div class="rounded-3xl border border-gray-200/90 bg-white p-6 md:p-8 shadow-sm">
        <form method="POST" action="{{ route('pemeriksaan.update', $pemeriksaan) }}" enctype="multipart/form-data" class="space-y-10" id="editSptLhpForm">
            @csrf
            @method('PUT')

            {{-- QUICK JUMP FLOATING NAVIGATION --}}
            <div class="sticky top-20 z-20 rounded-2xl bg-white/95 backdrop-blur-md border border-gray-200/90 p-2.5 shadow-md flex items-center justify-between gap-2.5 mb-6">
                <div class="flex items-center gap-1 sm:gap-2 overflow-x-auto w-full py-0.5">
                    <a href="#edit-section-spt" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition select-none">
                        <span class="flex h-5 w-5 items-center justify-center rounded-lg bg-primary-100 text-primary-700 text-xs font-bold">1</span>
                        <span class="truncate">Surat Tugas (SPT) & Tim</span>
                    </a>
                    <span class="text-gray-500">/</span>
                    <a href="#edit-section-lhp" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition select-none">
                        <span class="flex h-5 w-5 items-center justify-center rounded-lg bg-blue-100 text-blue-700 text-xs font-bold">2</span>
                        <span class="truncate">LHP & Temuan</span>
                    </a>
                </div>
                <div class="hidden sm:flex items-center text-xs font-bold text-primary-700 bg-primary-50 px-3 py-1 rounded-xl shrink-0">
                    <span>Edit SPT {{ $pemeriksaan->nomor_spt }}</span>
                </div>
            </div>

            {{-- ========================================================================= --}}
            {{-- BAGIAN 1: DATA SURAT PERINTAH TUGAS (SPT)                                 --}}
            {{-- ========================================================================= --}}
            <div id="edit-section-spt" class="space-y-6">
                <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                    <div class="flex h-9 w-9 items-center justify-center rounded-2xl bg-primary-100 text-primary-700 font-bold text-sm">
                        1
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Data Surat Perintah Tugas (SPT)</h3>
                        <p class="text-xs text-gray-500 font-medium">Informasi penugasan pemeriksaan pengawasan Inspektorat (Tanpa OPD)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    {{-- Sumber Pemeriksaan --}}
                    <div>
                        <p id="sumber_pemeriksaan-label" class="mb-1.5 block text-xs font-bold text-gray-700">Sumber Pemeriksaan <span class="text-state-danger">*</span></p>
                        @if($lockedSumber)
                            <input type="hidden" name="sumber_pemeriksaan" value="{{ $lockedSumber }}">
                            <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-xs font-bold text-primary-800">
                                <span class="inline-flex items-center rounded-md bg-primary-100 px-2 py-0.5 text-xs font-bold text-primary-800">Terkunci</span>
                                {{ $lockedSumber }}
                            </div>
                        @else
                            <select id="sumber_pemeriksaan" name="sumber_pemeriksaan" aria-labelledby="sumber_pemeriksaan-label" required @error('sumber_pemeriksaan') aria-invalid="true" aria-describedby="sumber_pemeriksaan-error" @enderror
                                    class="w-full border bg-white px-4 py-2.5 text-xs text-gray-800 font-semibold idds-form-control">
                                @foreach($sumberList as $s)
                                    <option value="{{ $s }}" {{ old('sumber_pemeriksaan', $pemeriksaan->sumber_pemeriksaan) == $s ? 'selected' : '' }}>{{ $s }}</option>
                                @endforeach
                            </select>
                        @endif
                        @error('sumber_pemeriksaan')
                            <p id="sumber_pemeriksaan-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Tahun Anggaran --}}
                    <div>
                        <label for="tahun" class="mb-1.5 block text-xs font-bold text-gray-700">Tahun Anggaran <span class="text-state-danger">*</span></label>
                        <select id="tahun" name="tahun" required @error('tahun') aria-invalid="true" aria-describedby="tahun-error" @enderror
                                class="w-full border bg-white px-4 py-2.5 text-xs text-gray-800 font-semibold idds-form-control">
                            @foreach($years as $y)
                                <option value="{{ $y }}" {{ old('tahun', $pemeriksaan->tahun) == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                        @error('tahun')
                            <p id="tahun-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Judul SPT --}}
                    <div class="md:col-span-2">
                        <label for="judul" class="mb-1.5 block text-xs font-bold text-gray-700">Judul / Perihal SPT</label>
                        <input type="text" id="judul" name="judul" value="{{ old('judul', $pemeriksaan->judul) }}" @error('judul') aria-invalid="true" aria-describedby="judul-error" @enderror
                               placeholder="Contoh: Pemeriksaan Reguler Kepatuhan Pengelolaan Anggaran TA 2026"
                               class="w-full border bg-white px-4 py-2.5 text-xs text-gray-800 font-medium idds-form-control">
                        @error('judul')
                            <p id="judul-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nomor SPT --}}
                    <div>
                        <label for="nomor_spt" class="mb-1.5 block text-xs font-bold text-gray-700">Nomor SPT <span class="text-state-danger">*</span></label>
                        <input type="text" id="nomor_spt" name="nomor_spt" value="{{ old('nomor_spt', $pemeriksaan->nomor_spt) }}" required @error('nomor_spt') aria-invalid="true" aria-describedby="nomor_spt-error" @enderror
                               class="w-full border bg-white px-4 py-2.5 text-xs text-gray-800 font-bold idds-form-control">
                        @error('nomor_spt')
                            <p id="nomor_spt-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Tanggal SPT --}}
                    <div>
                        <label for="tanggal_spt" class="mb-1.5 block text-xs font-bold text-gray-700">Tanggal SPT</label>
                        <input type="date" id="tanggal_spt" name="tanggal_spt" value="{{ old('tanggal_spt', $pemeriksaan->tanggal_spt?->format('Y-m-d')) }}" @error('tanggal_spt') aria-invalid="true" aria-describedby="tanggal_spt-error" @enderror
                               class="w-full border bg-white px-4 py-2.5 text-xs text-gray-800 idds-form-control">
                        @error('tanggal_spt')
                            <p id="tanggal_spt-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Periode Pemeriksaan --}}
                    <div>
                        <label for="tanggal_mulai" class="mb-1.5 block text-xs font-bold text-gray-700">Tanggal Mulai Pemeriksaan</label>
                        <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="{{ old('tanggal_mulai', $pemeriksaan->tanggal_mulai?->format('Y-m-d')) }}" @error('tanggal_mulai') aria-invalid="true" aria-describedby="tanggal_mulai-error" @enderror
                               class="w-full border bg-white px-4 py-2.5 text-xs text-gray-800 idds-form-control">
                        @error('tanggal_mulai')
                            <p id="tanggal_mulai-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="tanggal_selesai" class="mb-1.5 block text-xs font-bold text-gray-700">Tanggal Selesai Pemeriksaan</label>
                        <input type="date" id="tanggal_selesai" name="tanggal_selesai" value="{{ old('tanggal_selesai', $pemeriksaan->tanggal_selesai?->format('Y-m-d')) }}" @error('tanggal_selesai') aria-invalid="true" aria-describedby="tanggal_selesai-error" @enderror
                               class="w-full border bg-white px-4 py-2.5 text-xs text-gray-800 idds-form-control">
                        @error('tanggal_selesai')
                            <p id="tanggal_selesai-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nama Tim --}}
                    <div class="md:col-span-2">
                        <label for="nama_tim" class="mb-1.5 block text-xs font-bold text-gray-700">Nama Tim Pemeriksa</label>
                        <input type="text" id="nama_tim" name="nama_tim" value="{{ old('nama_tim', $pemeriksaan->nama_tim) }}" @error('nama_tim') aria-invalid="true" aria-describedby="nama_tim-error" @enderror
                               placeholder="Contoh: Tim Pemeriksa Inspektorat Pembantu Wilayah I"
                               class="w-full border bg-white px-4 py-2.5 text-xs text-gray-800 font-medium idds-form-control">
                        @error('nama_tim')
                            <p id="nama_tim-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                {{-- Susunan Anggota Tim Pemeriksa --}}
                <div class="pt-4 border-t border-gray-100 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-xs font-bold text-primary-950  tracking-wider">Susunan Tim Pemeriksa</h4>
                            <p class="text-xs text-gray-500">Pilih pegawai dari Master Data Pegawai Inspektorat</p>
                        </div>
                        <button type="button" onclick="addTimMemberRow()"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary-50 text-primary-700 hover:bg-primary-100 rounded-xl text-xs font-bold transition cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Tambah Anggota Tim
                        </button>
                    </div>
                    @error('tim_pemeriksa')
                        <p class="text-xs font-semibold text-red-700" role="alert">{{ $message }}</p>
                    @enderror

                    @php
                        $otherTimMembers = $pemeriksaan->timPemeriksas
                            ->reject(fn ($teamMember) => $teamMember->peran_tim === 'Penanggung Jawab')
                            ->values();
                    @endphp
                    <div id="tim-members-container" class="space-y-3">
                        <div class="tim-member-row flex flex-col sm:flex-row items-center gap-3 p-3 rounded-2xl bg-gray-50 border border-gray-200">
                            <div class="flex-1 w-full">
                                <label for="tim-pemeriksa-penanggung-jawab" class="mb-1 block text-xs font-semibold text-gray-700">Penanggung Jawab</label>
                                <input id="tim-pemeriksa-penanggung-jawab" type="text" readonly
                                       value="{{ $pegawaiInspektur ? $pegawaiInspektur->nama . ' — ' . $pegawaiInspektur->jabatan : '' }}"
                                       placeholder="Inspektur belum terdaftar di Master Pegawai"
                                       class="w-full border bg-gray-100 px-3 py-2 text-xs text-gray-700 idds-form-control">
                                <input type="hidden" name="tim_pemeriksa[0][pegawai_id]" value="{{ $pegawaiInspektur?->id }}">
                                <input type="hidden" name="tim_pemeriksa[0][peran_tim]" value="Penanggung Jawab">
                            </div>
                            <span class="shrink-0 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700">Terkunci</span>
                        </div>

                        @foreach($otherTimMembers as $tIndex => $tim)
                            <div class="tim-member-row flex flex-col sm:flex-row items-center gap-3 p-3 rounded-2xl bg-gray-50 border border-gray-200">
                                <div class="flex-1 w-full">
                                    <select name="tim_pemeriksa[{{ $tIndex + 1 }}][pegawai_id]" required
                                            class="w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control">
                                        <option value="">-- Pilih Pegawai --</option>
                                        @foreach($pegawais as $p)
                                            <option value="{{ $p->id }}" {{ $tim->pegawai_id == $p->id ? 'selected' : '' }}>
                                                {{ $p->nama }} — {{ $p->jabatan }} (NIP. {{ $p->nip ?? '-' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="w-full sm:w-48">
                                    <select name="tim_pemeriksa[{{ $tIndex + 1 }}][peran_tim]" required
                                            class="w-full border bg-white px-3 py-2 text-xs text-gray-800 font-semibold idds-form-control">
                                        <option value="Anggota Tim" {{ $tim->peran_tim == 'Anggota Tim' ? 'selected' : '' }}>Anggota Tim</option>
                                        <option value="Ketua Tim" {{ $tim->peran_tim == 'Ketua Tim' ? 'selected' : '' }}>Ketua Tim</option>
                                        <option value="Pengendali Teknis" {{ $tim->peran_tim == 'Pengendali Teknis' ? 'selected' : '' }}>Pengendali Teknis</option>
                                        <option value="Asisten Penanggung Jawab" {{ $tim->peran_tim == 'Asisten Penanggung Jawab' ? 'selected' : '' }}>Asisten Penanggung Jawab</option>
                                    </select>
                                </div>
                                <button type="button" onclick="removeTimMemberRow(this)"
                                        class="p-2 text-gray-500 hover:text-state-danger hover:bg-red-50 rounded-xl transition cursor-pointer" title="Hapus Anggota">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                        @if($otherTimMembers->isEmpty())
                            <p class="px-1 py-2 text-xs text-gray-500">Belum ada anggota tim lainnya.</p>
                        @endif
                    </div>
                </div>
                {{-- Upload Dokumen SPT --}}
                <div>
                    <label for="dokumen_spt" class="mb-1.5 block text-xs font-bold text-gray-700">Dokumen SPT (Kosongkan jika tidak ingin mengganti berkas)</label>
                    
                    @if($pemeriksaan->dokumen_spt)
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-gray-50 p-3.5 border border-gray-200">
                            <div class="flex min-w-0 flex-1 items-center gap-2.5">
                                <div class="w-8 h-8 shrink-0 rounded-xl bg-primary-100 text-primary-700 flex items-center justify-center">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <span class="text-xs font-bold text-gray-800 block [overflow-wrap:anywhere]">{{ $pemeriksaan->nama_dokumen_spt_asli ?? 'Dokumen SPT Terunggah' }}</span>
                                    <span class="text-xs text-gray-500">Berkas saat ini</span>
                                </div>
                            </div>
                            <a href="{{ route('pemeriksaan.download-spt', $pemeriksaan) }}" target="_blank"
                               class="inline-flex shrink-0 items-center gap-1 px-3 py-1.5 rounded-xl bg-primary-600 text-white font-bold text-xs shadow-xs hover:bg-primary-700 transition">
                                Lihat Berkas
                            </a>
                        </div>
                    @endif

                    <div class="flex items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50/50 p-5 text-center hover:bg-gray-50 transition">
                        <div>
                            <svg class="mx-auto h-8 w-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <div class="mt-2 flex text-xs text-gray-600 justify-center">
                                <label for="dokumen_spt" class="relative cursor-pointer rounded-md font-bold text-primary-600 focus-within:outline-none hover:text-primary-700">
                                    <span>Pilih berkas pengganti</span>
                                    <input id="dokumen_spt" name="dokumen_spt" type="file" accept=".pdf,.doc,.docx" class="sr-only" onchange="updateSptFileName(this)" data-file-display="spt-file-name-display" data-file-label-prefix="Berkas Baru: " data-file-display-class="mt-1 text-xs font-bold text-emerald-700" @error('dokumen_spt') aria-invalid="true" aria-describedby="dokumen_spt-error" @enderror>
                                </label>
                            </div>
                            <p id="spt-file-name-display" class="mt-1 text-xs text-gray-500">Format PDF, DOC, atau DOCX (Maksimal 10MB)</p>
                        </div>
                    </div>
                    @error('dokumen_spt')
                        <p id="dokumen_spt-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            {{-- ========================================================================= --}}
            {{-- BAGIAN 2: LAPORAN HASIL PEMERIKSAAN (LHP) & TEMUAN                        --}}
            {{-- ========================================================================= --}}
            <div id="edit-section-lhp" class="space-y-6 pt-6 border-t-2 border-dashed border-gray-200">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-2xl bg-blue-600 text-white font-bold text-sm shadow-xs">
                            2
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Data Laporan Hasil Pemeriksaan (LHP)</h3>
                            <p class="text-xs text-gray-500 font-medium">Satu SPT dapat memiliki banyak LHP dengan target multi-OPD dan unsur temuan</p>
                        </div>
                    </div>

                    <button type="button" onclick="addLhpCard()"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-md shadow-blue-600/20 hover:bg-blue-700 transition cursor-pointer">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah LHP
                    </button>
                </div>

                {{-- Container LHP List (Existing + Dynamic) --}}
                <div id="lhps-container" class="space-y-8">
                    @forelse($pemeriksaan->lhps as $lhpIdx => $lhp)
                        @php
                            $targetOpdIds = $lhp->opds->pluck('id')->toArray();
                            $admTemuans = $lhp->temuans->where('ada_unsur_administrasi', true);
                            $kgTemuans = $lhp->temuans->where('ada_unsur_kerugian', true);
                            $hasAdm = $admTemuans->isNotEmpty();
                            $hasKg = $kgTemuans->isNotEmpty();
                        @endphp

                        <div class="lhp-card rounded-3xl border border-gray-200 bg-white p-6 md:p-7 shadow-sm space-y-6 relative transition hover:border-blue-300" data-lhp-index="{{ $lhpIdx }}">
                            <input type="hidden" name="lhps[{{ $lhpIdx }}][id]" value="{{ $lhp->id }}">

                            {{-- Header Card LHP --}}
                            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                                <div class="flex items-center gap-2">
                                    <span class="lhp-card-badge px-3 py-1 rounded-xl text-xs font-bold bg-blue-600 text-white shadow-xs">
                                        LHP #{{ $lhpIdx + 1 }}
                                    </span>
                                    <span class="text-xs text-gray-500 font-medium">Dokumen Hasil Pemeriksaan</span>
                                </div>

                                @if($pemeriksaan->lhps->count() > 1)
                                    <button type="button" onclick="removeLhpCard(this)"
                                            class="text-xs text-state-danger hover:text-red-700 font-bold px-2 py-1 rounded-lg hover:bg-red-50 transition cursor-pointer">
                                        Hapus LHP Ini
                                    </button>
                                @endif
                            </div>

                            {{-- Grid LHP Details --}}
                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                {{-- Nomor LHP --}}
                                <div>
                                    <label for="edit-lhp-nomor-{{ $lhpIdx }}" class="block text-xs font-bold text-gray-700 mb-1">Nomor LHP <span class="text-state-danger">*</span></label>
                                    <input id="edit-lhp-nomor-{{ $lhpIdx }}" type="text" name="lhps[{{ $lhpIdx }}][nomor_lhp]" value="{{ old('lhps.'.$lhpIdx.'.nomor_lhp', $lhp->nomor_lhp) }}" required
                                           class="w-full border bg-white px-3.5 py-2.5 text-xs text-gray-800 font-bold idds-form-control">
                                </div>

                                {{-- Tanggal LHP --}}
                                <div>
                                    <label for="edit-lhp-tanggal-{{ $lhpIdx }}" class="block text-xs font-bold text-gray-700 mb-1">Tanggal LHP <span class="text-state-danger">*</span></label>
                                    <input id="edit-lhp-tanggal-{{ $lhpIdx }}" type="date" name="lhps[{{ $lhpIdx }}][tanggal_lhp]" value="{{ old('lhps.'.$lhpIdx.'.tanggal_lhp', $lhp->tanggal_lhp?->format('Y-m-d')) }}" required
                                           class="w-full border bg-white px-3.5 py-2.5 text-xs text-gray-800 idds-form-control">
                                </div>

                                {{-- Judul LHP --}}
                                <div class="md:col-span-2">
                                    <label for="edit-lhp-judul-{{ $lhpIdx }}" class="block text-xs font-bold text-gray-700 mb-1">Judul / Perihal LHP</label>
                                    <input id="edit-lhp-judul-{{ $lhpIdx }}" type="text" name="lhps[{{ $lhpIdx }}][judul_lhp]" value="{{ old('lhps.'.$lhpIdx.'.judul_lhp', $lhp->judul_lhp) }}"
                                           placeholder="Contoh: Laporan Hasil Pemeriksaan Kepatuhan Pengelolaan Anggaran"
                                           class="w-full border bg-white px-3.5 py-2.5 text-xs text-gray-800 font-medium idds-form-control">
                                </div>

                                {{-- OPD Target Multi-Select Checkboxes --}}
                                <div class="md:col-span-2 rounded-2xl border border-primary-200/80 bg-primary-50/40 p-4 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <p id="edit-lhp-opd-label-{{ $lhpIdx }}" class="text-xs font-bold text-primary-900  tracking-wider">
                                            Pilih OPD Target (Bisa Lebih Dari 1 OPD) <span class="text-state-danger">*</span>
                                        </p>
                                        <span id="opd-count-{{ $lhpIdx }}" class="text-xs font-bold text-primary-700 bg-primary-100 px-2.5 py-0.5 rounded-full">
                                            {{ count($targetOpdIds) }} OPD Terpilih
                                        </span>
                                    </div>

                                    <input type="text" oninput="filterOpdOptions(this, {{ $lhpIdx }})" aria-label="Cari OPD target"
                                           placeholder="Cari nama atau kode instansi OPD..."
                                           class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control">

                                    <div class="max-h-36 overflow-y-auto space-y-1.5 p-2 bg-white rounded-xl border border-gray-200" role="group" aria-labelledby="edit-lhp-opd-label-{{ $lhpIdx }}">
                                        @foreach($allOpds as $o)
                                            <label class="opd-item-{{ $lhpIdx }} flex items-center gap-2 p-1.5 rounded-lg hover:bg-primary-50 transition cursor-pointer select-none" data-search="{{ strtolower($o->kode . ' ' . $o->nama) }}">
                                                <input type="checkbox" name="lhps[{{ $lhpIdx }}][opd_ids][]" value="{{ $o->id }}"
                                                       {{ in_array($o->id, old('lhps.'.$lhpIdx.'.opd_ids', $targetOpdIds)) ? 'checked' : '' }}
                                                       onchange="updateOpdSelection({{ $lhpIdx }})"
                                                       class="opd-cb-{{ $lhpIdx }} h-3.5 w-3.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                                <span class="text-xs font-bold text-primary-900">[{{ $o->kode }}]</span>
                                                <span class="text-xs text-gray-800">{{ $o->nama }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Ringkasan LHP --}}
                                <div class="md:col-span-2">
                                    <label for="edit-lhp-ringkasan-{{ $lhpIdx }}" class="block text-xs font-bold text-gray-700 mb-1">Uraian Ringkas Hasil Pemeriksaan</label>
                                    <textarea id="edit-lhp-ringkasan-{{ $lhpIdx }}" name="lhps[{{ $lhpIdx }}][ringkasan]" rows="2" placeholder="Uraian pokok temuan atau evaluasi..."
                                              class="w-full border bg-white px-3.5 py-2.5 text-xs text-gray-800 idds-form-control">{{ old('lhps.'.$lhpIdx.'.ringkasan', $lhp->ringkasan) }}</textarea>
                                </div>

                                {{-- Upload Dokumen LHP --}}
                                <div class="md:col-span-2">
                                    <label for="edit-lhp-dokumen-{{ $lhpIdx }}" class="block text-xs font-bold text-gray-700 mb-1">Unggah Dokumen LHP (Kosongkan jika tidak mengganti berkas)</label>
                                    
                                    @if($lhp->dokumen_lhp)
                                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2 rounded-xl bg-blue-50 p-2.5 border border-blue-200 text-xs">
                                            <span class="min-w-0 flex-1 font-semibold text-blue-900 [overflow-wrap:anywhere]">{{ $lhp->nama_dokumen_lhp_asli ?? 'Dokumen-LHP.pdf' }}</span>
                                            <a href="{{ route('pemeriksaan.lhp.download', [$pemeriksaan, $lhp]) }}" target="_blank" class="shrink-0 font-bold text-blue-700 hover:underline">
                                                Unduh Berkas Lama
                                            </a>
                                        </div>
                                    @endif

                                    <input id="edit-lhp-dokumen-{{ $lhpIdx }}" type="file" name="lhps[{{ $lhpIdx }}][dokumen_lhp]" accept=".pdf,.doc,.docx"
                                           class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                </div>
                            </div>

                            {{-- UNSUR TEMUAN PER LHP --}}
                            <div class="pt-4 border-t border-gray-100 space-y-4" role="group" aria-labelledby="edit-lhp-unsur-label-{{ $lhpIdx }}">
                                <div>
                                    <p id="edit-lhp-unsur-label-{{ $lhpIdx }}" class="block text-xs font-bold text-gray-800  tracking-wider mb-2">
                                        Unsur Temuan pada LHP Ini <span class="text-state-danger">*</span>
                                    </p>
                                    <div class="flex flex-wrap gap-4">
                                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-gray-200 bg-gray-50 hover:bg-white cursor-pointer transition select-none">
                                            <input type="checkbox" name="lhps[{{ $lhpIdx }}][ada_administrasi]" value="1" onchange="toggleUnsurTemuan({{ $lhpIdx }}, 'administrasi')" id="unsur_adm_cb_{{ $lhpIdx }}" {{ $hasAdm ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                            <span class="text-xs font-bold text-gray-800">Unsur Administrasi</span>
                                        </label>
                                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-gray-200 bg-gray-50 hover:bg-white cursor-pointer transition select-none">
                                            <input type="checkbox" name="lhps[{{ $lhpIdx }}][ada_kerugian]" value="1" onchange="toggleUnsurTemuan({{ $lhpIdx }}, 'kerugian')" id="unsur_kg_cb_{{ $lhpIdx }}" {{ $hasKg ? 'checked' : '' }} class="rounded border-gray-300 text-state-warning focus:ring-amber-500">
                                            <span class="text-xs font-bold text-gray-800">Unsur Kerugian (Daerah / Negara)</span>
                                        </label>
                                    </div>
                                </div>

                                {{-- Container Temuan Administrasi --}}
                                <div id="container_adm_{{ $lhpIdx }}" class="{{ $hasAdm ? '' : 'hidden' }} space-y-3 p-4 rounded-2xl bg-primary-50/40 border border-primary-200/80">
                                    <div class="flex items-center justify-between">
                                        <h5 class="text-xs font-bold text-primary-900  tracking-wider flex items-center gap-1.5">
                                            <span>Temuan Administrasi</span>
                                        </h5>
                                        <button type="button" onclick="addTemuanAdministrasiItem({{ $lhpIdx }})"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs font-bold transition cursor-pointer">
                                            + Tambah Temuan Administrasi
                                        </button>
                                    </div>

                                    <div id="adm_items_list_{{ $lhpIdx }}" class="space-y-3">
                                        @forelse($admTemuans as $admIdx => $adm)
                                            <div class="adm-item-box bg-white p-3.5 rounded-xl border border-primary-200/90 space-y-3">
                                                <input type="hidden" name="lhps[{{ $lhpIdx }}][temuans_administrasi][{{ $admIdx }}][id]" value="{{ $adm->id }}">
                                                <div class="flex items-center justify-between">
                                                    <span class="adm-item-label text-xs font-bold text-primary-800">Temuan Administrasi #{{ $admIdx + 1 }}</span>
                                                    <button type="button" onclick="removeTemuanAdministrasiItem(this, {{ $lhpIdx }})" class="text-xs text-state-danger hover:text-red-700 font-bold cursor-pointer">Hapus</button>
                                                </div>
                                                <div>
                                                    <label for="edit-adm-uraian-{{ $lhpIdx }}-{{ $admIdx }}" class="block text-xs font-bold text-gray-700 mb-1">Uraian Temuan Administrasi <span class="text-state-danger">*</span></label>
                                                    <textarea id="edit-adm-uraian-{{ $lhpIdx }}-{{ $admIdx }}" name="lhps[{{ $lhpIdx }}][temuans_administrasi][{{ $admIdx }}][uraian]" rows="2" placeholder="Jelaskan kondisi ketidaksesuaian administrasi..."
                                                              class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control">{{ $adm->uraian_temuan }}</textarea>
                                                </div>
                                                <div>
                                                    <label for="edit-adm-penyebab-{{ $lhpIdx }}-{{ $admIdx }}" class="block text-xs font-bold text-gray-700 mb-1">Penyebab (Sebab) <span class="text-gray-500 font-normal">(Opsional)</span></label>
                                                    <textarea id="edit-adm-penyebab-{{ $lhpIdx }}-{{ $admIdx }}" name="lhps[{{ $lhpIdx }}][temuans_administrasi][{{ $admIdx }}][penyebab]" rows="2" placeholder="Jelaskan penyebab ketidaksesuaian administrasi..."
                                                              class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control">{{ $adm->penyebab }}</textarea>
                                                </div>
                                                <div class="space-y-1.5 pt-1">
                                                    <div class="flex items-center justify-between">
                                                        <p id="edit-adm-rekomendasi-label-{{ $lhpIdx }}-{{ $admIdx }}" class="text-xs font-bold text-gray-700">Poin-Poin Rekomendasi Bertahap <span class="text-state-danger">*</span></p>
                                                        <button type="button" onclick="addRekomendasiPointEdit(this)" class="inline-flex items-center gap-1 text-xs font-bold text-primary-700 hover:text-primary-900 bg-primary-50 hover:bg-primary-100 px-2.5 py-1 rounded-lg border border-primary-200 transition cursor-pointer">
                                                            + Tambah Poin Rekomendasi
                                                        </button>
                                                    </div>
                                                    <div class="list-rekomendasi-points space-y-1.5" role="group" aria-labelledby="edit-adm-rekomendasi-label-{{ $lhpIdx }}-{{ $admIdx }}">
                                                        @php
                                                            $reks = $adm->rekomendasis;
                                                            if ($reks->isEmpty() && !empty($adm->rekomendasi)) {
                                                                $parsedLines = app(\App\Services\PemeriksaanService::class)->parseRekomendasiText($adm->rekomendasi);
                                                            } else {
                                                                $parsedLines = [];
                                                            }
                                                        @endphp
                                                        @if($reks->isNotEmpty())
                                                            @foreach($reks as $rIdx => $r)
                                                                <div class="item-rek-point flex items-start gap-1.5 bg-primary-50/30 p-2 rounded-lg border border-primary-100/80">
                                                                    <span class="rek-point-num text-xs font-bold bg-primary-600 text-white w-5 h-5 flex items-center justify-center rounded shrink-0 mt-0.5 shadow-2xs">{{ $rIdx + 1 }}</span>
                                                                    <div class="flex-1">
                                                                        <textarea aria-label="Uraian butir rekomendasi {{ $rIdx + 1 }}" name="lhps[{{ $lhpIdx }}][temuans_administrasi][{{ $admIdx }}][rekomendasi_items][]" rows="2"
                                                                                  class="input-adm-rekomendasi-item w-full border bg-white px-2.5 py-1.5 text-xs text-gray-800 idds-form-control"
                                                                                  placeholder="Uraian butir rekomendasi...">{{ $r->uraian_rekomendasi }}</textarea>
                                                                    </div>
                                                                    <button type="button" onclick="removeRekomendasiPointEdit(this)" class="btn-remove-rek-point {{ $loop->count <= 1 ? 'hidden' : '' }} text-gray-500 hover:text-state-danger p-1 cursor-pointer mt-0.5" title="Hapus poin rekomendasi">
                                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                                    </button>
                                                                </div>
                                                            @endforeach
                                                        @elseif(!empty($parsedLines))
                                                            @foreach($parsedLines as $pIdx => $pLine)
                                                                <div class="item-rek-point flex items-start gap-1.5 bg-primary-50/30 p-2 rounded-lg border border-primary-100/80">
                                                                    <span class="rek-point-num text-xs font-bold bg-primary-600 text-white w-5 h-5 flex items-center justify-center rounded shrink-0 mt-0.5 shadow-2xs">{{ $pIdx + 1 }}</span>
                                                                    <div class="flex-1">
                                                                        <textarea aria-label="Uraian butir rekomendasi {{ $pIdx + 1 }}" name="lhps[{{ $lhpIdx }}][temuans_administrasi][{{ $admIdx }}][rekomendasi_items][]" rows="2"
                                                                                  class="input-adm-rekomendasi-item w-full border bg-white px-2.5 py-1.5 text-xs text-gray-800 idds-form-control"
                                                                                  placeholder="Uraian butir rekomendasi...">{{ $pLine }}</textarea>
                                                                    </div>
                                                                    <button type="button" onclick="removeRekomendasiPointEdit(this)" class="btn-remove-rek-point {{ count($parsedLines) <= 1 ? 'hidden' : '' }} text-gray-500 hover:text-state-danger p-1 cursor-pointer mt-0.5" title="Hapus poin rekomendasi">
                                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                                    </button>
                                                                </div>
                                                            @endforeach
                                                        @else
                                                            <div class="item-rek-point flex items-start gap-1.5 bg-primary-50/30 p-2 rounded-lg border border-primary-100/80">
                                                                <span class="rek-point-num text-xs font-bold bg-primary-600 text-white w-5 h-5 flex items-center justify-center rounded shrink-0 mt-0.5 shadow-2xs">1</span>
                                                                <div class="flex-1">
                                                                    <textarea aria-label="Uraian butir rekomendasi 1" name="lhps[{{ $lhpIdx }}][temuans_administrasi][{{ $admIdx }}][rekomendasi_items][]" rows="2"
                                                                              class="input-adm-rekomendasi-item w-full border bg-white px-2.5 py-1.5 text-xs text-gray-800 idds-form-control"
                                                                              placeholder="Uraian butir rekomendasi...">{{ $adm->rekomendasi }}</textarea>
                                                                </div>
                                                                <button type="button" onclick="removeRekomendasiPointEdit(this)" class="btn-remove-rek-point hidden text-gray-500 hover:text-state-danger p-1 cursor-pointer mt-0.5" title="Hapus poin rekomendasi">
                                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                                </button>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="adm-item-box bg-white p-3.5 rounded-xl border border-primary-200/90 space-y-3">
                                                <div class="flex items-center justify-between">
                                                    <span class="adm-item-label text-xs font-bold text-primary-800">Temuan Administrasi #1</span>
                                                </div>
                                                <div>
                                                    <label for="edit-adm-uraian-{{ $lhpIdx }}-0" class="block text-xs font-bold text-gray-700 mb-1">Uraian Temuan Administrasi <span class="text-state-danger">*</span></label>
                                                    <textarea id="edit-adm-uraian-{{ $lhpIdx }}-0" name="lhps[{{ $lhpIdx }}][temuans_administrasi][0][uraian]" rows="2" placeholder="Jelaskan kondisi ketidaksesuaian administrasi..."
                                                              class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control"></textarea>
                                                </div>
                                                <div>
                                                    <label for="edit-adm-penyebab-{{ $lhpIdx }}-0" class="block text-xs font-bold text-gray-700 mb-1">Penyebab (Sebab) <span class="text-gray-500 font-normal">(Opsional)</span></label>
                                                    <textarea id="edit-adm-penyebab-{{ $lhpIdx }}-0" name="lhps[{{ $lhpIdx }}][temuans_administrasi][0][penyebab]" rows="2" placeholder="Jelaskan penyebab ketidaksesuaian administrasi..."
                                                              class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control"></textarea>
                                                </div>
                                                <div class="space-y-1.5 pt-1">
                                                    <div class="flex items-center justify-between">
                                                        <p id="edit-adm-rekomendasi-label-{{ $lhpIdx }}-0" class="text-xs font-bold text-gray-700">Poin-Poin Rekomendasi Bertahap <span class="text-state-danger">*</span></p>
                                                        <button type="button" onclick="addRekomendasiPointEdit(this)" class="inline-flex items-center gap-1 text-xs font-bold text-primary-700 hover:text-primary-900 bg-primary-50 hover:bg-primary-100 px-2.5 py-1 rounded-lg border border-primary-200 transition cursor-pointer">
                                                            + Tambah Poin Rekomendasi
                                                        </button>
                                                    </div>
                                                    <div class="list-rekomendasi-points space-y-1.5" role="group" aria-labelledby="edit-adm-rekomendasi-label-{{ $lhpIdx }}-0">
                                                        <div class="item-rek-point flex items-start gap-1.5 bg-primary-50/30 p-2 rounded-lg border border-primary-100/80">
                                                            <span class="rek-point-num text-xs font-bold bg-primary-600 text-white w-5 h-5 flex items-center justify-center rounded shrink-0 mt-0.5 shadow-2xs">1</span>
                                                            <div class="flex-1">
                                                                <textarea aria-label="Uraian butir rekomendasi 1" name="lhps[{{ $lhpIdx }}][temuans_administrasi][0][rekomendasi_items][]" rows="2"
                                                                          class="input-adm-rekomendasi-item w-full border bg-white px-2.5 py-1.5 text-xs text-gray-800 idds-form-control"
                                                                          placeholder="Uraian butir rekomendasi..."></textarea>
                                                            </div>
                                                            <button type="button" onclick="removeRekomendasiPointEdit(this)" class="btn-remove-rek-point hidden text-gray-500 hover:text-state-danger p-1 cursor-pointer mt-0.5" title="Hapus poin rekomendasi">
                                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>

                                {{-- Container Temuan Kerugian --}}
                                <div id="container_kg_{{ $lhpIdx }}" class="{{ $hasKg ? '' : 'hidden' }} space-y-3 p-4 rounded-2xl bg-amber-50/40 border border-amber-200/80">
                                    <div class="flex items-center justify-between">
                                        <h5 class="text-xs font-bold text-amber-900  tracking-wider flex items-center gap-1.5">
                                            <span>Daftar Temuan Kerugian (Daerah / Negara)</span>
                                        </h5>
                                        <button type="button" onclick="addTemuanKerugianItem({{ $lhpIdx }})"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold transition cursor-pointer">
                                            + Tambah Temuan Kerugian
                                        </button>
                                    </div>

                                    <div id="kg_items_list_{{ $lhpIdx }}" class="space-y-3">
                                        @forelse($kgTemuans as $kgIdx => $kg)
                                            <div class="kg-item-box bg-white p-3.5 rounded-xl border border-rose-200/90 space-y-3">
                                                <input type="hidden" name="lhps[{{ $lhpIdx }}][temuans_kerugian][{{ $kgIdx }}][id]" value="{{ $kg->id }}">
                                                <div class="flex items-center justify-between">
                                                    <span class="kg-item-label text-xs font-bold text-rose-800">Temuan Kerugian #{{ $kgIdx + 1 }}</span>
                                                    <button type="button" onclick="removeTemuanKerugianItem(this, {{ $lhpIdx }})" class="text-xs text-state-danger hover:text-red-700 font-bold cursor-pointer">Hapus</button>
                                                </div>
                                                <div>
                                                    <label for="edit-kg-uraian-{{ $lhpIdx }}-{{ $kgIdx }}" class="block text-xs font-bold text-gray-700 mb-1">Uraian Temuan Kerugian <span class="text-state-danger">*</span></label>
                                                    <textarea id="edit-kg-uraian-{{ $lhpIdx }}-{{ $kgIdx }}" name="lhps[{{ $lhpIdx }}][temuans_kerugian][{{ $kgIdx }}][uraian]" rows="2" placeholder="Jelaskan indikasi kerugian..."
                                                              class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control">{{ $kg->uraian_temuan }}</textarea>
                                                </div>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div>
                                                        <label for="edit-kg-kategori-{{ $lhpIdx }}-{{ $kgIdx }}" class="block text-xs font-bold text-gray-700 mb-1">Kategori / Sumber Kerugian <span class="text-state-danger">*</span></label>
                                                        <select id="edit-kg-kategori-{{ $lhpIdx }}-{{ $kgIdx }}" name="lhps[{{ $lhpIdx }}][temuans_kerugian][{{ $kgIdx }}][kategori_kerugian]" class="w-full border bg-white px-3 py-1.5 text-xs font-semibold text-gray-800 idds-form-control">
                                                            <option value="Kerugian Daerah" {{ ($kg->kategori_kerugian ?? 'Kerugian Daerah') == 'Kerugian Daerah' ? 'selected' : '' }}>Kerugian Daerah (Kasda / APBD)</option>
                                                            <option value="Kerugian Negara" {{ ($kg->kategori_kerugian ?? '') == 'Kerugian Negara' ? 'selected' : '' }}>Kerugian Negara (Kas Negara / APBN)</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <p id="edit-kg-jenis-label-{{ $lhpIdx }}-{{ $kgIdx }}" class="text-xs font-bold text-gray-700 mb-1">Klasifikasi Penyelesaian <span class="text-state-danger">*</span></p>
                                                        <div class="flex items-center gap-4 mt-2" role="group" aria-labelledby="edit-kg-jenis-label-{{ $lhpIdx }}-{{ $kgIdx }}">
                                                            <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                                                <input type="radio" name="lhps[{{ $lhpIdx }}][temuans_kerugian][{{ $kgIdx }}][jenis_kerugian]" value="Internal" {{ ($kg->jenis_kerugian ?? '') == 'Internal' ? 'checked' : '' }} class="h-3.5 w-3.5 text-state-danger focus:ring-rose-500">
                                                                <span>Internal (ASN/OPD)</span>
                                                            </label>
                                                            <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                                                <input type="radio" name="lhps[{{ $lhpIdx }}][temuans_kerugian][{{ $kgIdx }}][jenis_kerugian]" value="Eksternal" {{ ($kg->jenis_kerugian ?? '') == 'Eksternal' ? 'checked' : '' }} class="h-3.5 w-3.5 text-state-danger focus:ring-rose-500">
                                                                <span>Eksternal (Rekanan)</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label for="edit-kg-nilai-{{ $lhpIdx }}-{{ $kgIdx }}" class="block text-xs font-bold text-gray-700 mb-1">Nominal Nilai Kerugian (Rp) <span class="text-state-danger">*</span></label>
                                                    <div class="relative">
                                                        <span aria-hidden="true" class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-bold text-gray-500">Rp</span>
                                                        <input id="edit-kg-nilai-{{ $lhpIdx }}-{{ $kgIdx }}" type="text" name="lhps[{{ $lhpIdx }}][temuans_kerugian][{{ $kgIdx }}][nilai_kerugian]"
                                                               value="{{ $kg->nilai_kerugian ? number_format($kg->nilai_kerugian, 0, ',', '.') : '' }}"
                                                               placeholder="0" oninput="formatRupiahInput(this)"
                                                               class="w-full pl-9 border bg-white px-3 py-1.5 text-xs font-bold text-amber-900 idds-form-control">
                                                    </div>
                                                </div>
                                                <div>
                                                    <label for="edit-kg-penyebab-{{ $lhpIdx }}-{{ $kgIdx }}" class="block text-xs font-bold text-gray-700 mb-1">Penyebab (Sebab) <span class="text-gray-500 font-normal">(Opsional)</span></label>
                                                    <textarea id="edit-kg-penyebab-{{ $lhpIdx }}-{{ $kgIdx }}" name="lhps[{{ $lhpIdx }}][temuans_kerugian][{{ $kgIdx }}][penyebab]" rows="2" placeholder="Jelaskan penyebab terjadinya kerugian..."
                                                              class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control">{{ $kg->penyebab }}</textarea>
                                                </div>
                                                <div>
                                                    <label for="edit-kg-rekomendasi-{{ $lhpIdx }}-{{ $kgIdx }}" class="block text-xs font-bold text-gray-700 mb-1">Rekomendasi Penyetoran <span class="text-state-danger">*</span></label>
                                                    <textarea id="edit-kg-rekomendasi-{{ $lhpIdx }}-{{ $kgIdx }}" name="lhps[{{ $lhpIdx }}][temuans_kerugian][{{ $kgIdx }}][rekomendasi]" rows="2" placeholder="Rekomendasi penyetoran ke Kas Daerah / Kas Negara..."
                                                              class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control">{{ $kg->rekomendasi }}</textarea>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="kg-item-box bg-white p-3.5 rounded-xl border border-rose-200/90 space-y-3">
                                                <div class="flex items-center justify-between">
                                                    <span class="kg-item-label text-xs font-bold text-rose-800">Temuan Kerugian #1</span>
                                                </div>
                                                <div>
                                                    <label for="edit-kg-uraian-{{ $lhpIdx }}-0" class="block text-xs font-bold text-gray-700 mb-1">Uraian Temuan Kerugian <span class="text-state-danger">*</span></label>
                                                    <textarea id="edit-kg-uraian-{{ $lhpIdx }}-0" name="lhps[{{ $lhpIdx }}][temuans_kerugian][0][uraian]" rows="2" placeholder="Jelaskan indikasi kerugian..."
                                                              class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control"></textarea>
                                                </div>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div>
                                                        <label for="edit-kg-kategori-{{ $lhpIdx }}-0" class="block text-xs font-bold text-gray-700 mb-1">Kategori / Sumber Kerugian <span class="text-state-danger">*</span></label>
                                                        <select id="edit-kg-kategori-{{ $lhpIdx }}-0" name="lhps[{{ $lhpIdx }}][temuans_kerugian][0][kategori_kerugian]" class="w-full border bg-white px-3 py-1.5 text-xs font-semibold text-gray-800 idds-form-control">
                                                            <option value="Kerugian Daerah" selected>Kerugian Daerah (Kasda / APBD)</option>
                                                            <option value="Kerugian Negara">Kerugian Negara (Kas Negara / APBN)</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <p id="edit-kg-jenis-label-{{ $lhpIdx }}-0" class="text-xs font-bold text-gray-700 mb-1">Klasifikasi Penyelesaian <span class="text-state-danger">*</span></p>
                                                        <div class="flex items-center gap-4 mt-2" role="group" aria-labelledby="edit-kg-jenis-label-{{ $lhpIdx }}-0">
                                                            <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                                                <input type="radio" name="lhps[{{ $lhpIdx }}][temuans_kerugian][0][jenis_kerugian]" value="Internal" class="h-3.5 w-3.5 text-state-danger focus:ring-rose-500">
                                                                <span>Internal (ASN/OPD)</span>
                                                            </label>
                                                            <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                                                <input type="radio" name="lhps[{{ $lhpIdx }}][temuans_kerugian][0][jenis_kerugian]" value="Eksternal" class="h-3.5 w-3.5 text-state-danger focus:ring-rose-500">
                                                                <span>Eksternal (Rekanan)</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label for="edit-kg-nilai-{{ $lhpIdx }}-0" class="block text-xs font-bold text-gray-700 mb-1">Nominal Nilai Kerugian (Rp) <span class="text-state-danger">*</span></label>
                                                    <div class="relative">
                                                        <span aria-hidden="true" class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-bold text-gray-500">Rp</span>
                                                        <input id="edit-kg-nilai-{{ $lhpIdx }}-0" type="text" name="lhps[{{ $lhpIdx }}][temuans_kerugian][0][nilai_kerugian]" placeholder="0" oninput="formatRupiahInput(this)"
                                                               class="w-full pl-9 border bg-white px-3 py-1.5 text-xs font-bold text-amber-900 idds-form-control">
                                                    </div>
                                                </div>
                                                <div>
                                                    <label for="edit-kg-penyebab-{{ $lhpIdx }}-0" class="block text-xs font-bold text-gray-700 mb-1">Penyebab (Sebab) <span class="text-gray-500 font-normal">(Opsional)</span></label>
                                                    <textarea id="edit-kg-penyebab-{{ $lhpIdx }}-0" name="lhps[{{ $lhpIdx }}][temuans_kerugian][0][penyebab]" rows="2" placeholder="Jelaskan penyebab terjadinya kerugian..."
                                                              class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control"></textarea>
                                                </div>
                                                <div>
                                                    <label for="edit-kg-rekomendasi-{{ $lhpIdx }}-0" class="block text-xs font-bold text-gray-700 mb-1">Rekomendasi Penyetoran <span class="text-state-danger">*</span></label>
                                                    <textarea id="edit-kg-rekomendasi-{{ $lhpIdx }}-0" name="lhps[{{ $lhpIdx }}][temuans_kerugian][0][rekomendasi]" rows="2" placeholder="Rekomendasi penyetoran ke Kas Daerah / Kas Negara..."
                                                              class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control"></textarea>
                                                </div>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center p-8 bg-gray-50 rounded-2xl border border-dashed border-gray-200 text-xs text-gray-500 italic">
                            Belum ada LHP yang terdaftar untuk SPT ini. Klik "+ Tambah LHP" di atas.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Buttons Bar --}}
            <div class="flex flex-col-reverse items-stretch gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('pemeriksaan.show', $pemeriksaan) }}"
                   class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-center text-xs font-bold text-gray-700 hover:bg-gray-50 transition cursor-pointer sm:w-auto">
                    Batal
                </a>
                <button type="submit"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-primary-700 px-6 py-2.5 text-center text-xs font-semibold text-white shadow-sm hover:bg-primary-800 transition-colors cursor-pointer sm:w-auto">
                    Simpan Perubahan SPT &amp; LHP
                </button>
            </div>
        </form>
    </div>
</div>

{{-- TEMPLATE UNTUK DYNAMIC LHP TAMBAHAN --}}
<template id="lhp-template">
    <div class="lhp-card rounded-3xl border border-gray-200 bg-white p-6 md:p-7 shadow-sm space-y-6 relative transition hover:border-blue-300" data-lhp-index="__INDEX__">
        {{-- Header Card LHP --}}
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <div class="flex items-center gap-2">
                <span class="lhp-card-badge px-3 py-1 rounded-xl text-xs font-bold bg-blue-600 text-white shadow-xs">
                    LHP #__NUM__
                </span>
                <span class="text-xs text-gray-500 font-medium">Dokumen Hasil Pemeriksaan Baru</span>
            </div>
            <button type="button" onclick="removeLhpCard(this)"
                    class="text-xs text-state-danger hover:text-red-700 font-bold px-2 py-1 rounded-lg hover:bg-red-50 transition cursor-pointer">
                Hapus LHP Ini
            </button>
        </div>

        {{-- Grid LHP Details --}}
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label for="edit-lhp-nomor-__INDEX__" class="block text-xs font-bold text-gray-700 mb-1">Nomor LHP <span class="text-state-danger">*</span></label>
                <input id="edit-lhp-nomor-__INDEX__" type="text" name="lhps[__INDEX__][nomor_lhp]" required placeholder="Contoh: 700/01/LHP/2026"
                       class="w-full border bg-white px-3.5 py-2.5 text-xs text-gray-800 font-bold idds-form-control">
            </div>

            <div>
                <label for="edit-lhp-tanggal-__INDEX__" class="block text-xs font-bold text-gray-700 mb-1">Tanggal LHP <span class="text-state-danger">*</span></label>
                <input id="edit-lhp-tanggal-__INDEX__" type="date" name="lhps[__INDEX__][tanggal_lhp]" value="{{ date('Y-m-d') }}" required
                       class="w-full border bg-white px-3.5 py-2.5 text-xs text-gray-800 idds-form-control">
            </div>

            <div class="md:col-span-2">
                <label for="edit-lhp-judul-__INDEX__" class="block text-xs font-bold text-gray-700 mb-1">Judul / Perihal LHP</label>
                <input id="edit-lhp-judul-__INDEX__" type="text" name="lhps[__INDEX__][judul_lhp]" placeholder="Contoh: LHP Kepatuhan Pengelolaan Anggaran"
                       class="w-full border bg-white px-3.5 py-2.5 text-xs text-gray-800 font-medium idds-form-control">
            </div>

            <div class="md:col-span-2 rounded-2xl border border-primary-200/80 bg-primary-50/40 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <p id="edit-lhp-opd-label-__INDEX__" class="text-xs font-bold text-primary-900  tracking-wider">
                        Pilih OPD Target (Bisa Lebih Dari 1 OPD) <span class="text-state-danger">*</span>
                    </p>
                    <span id="opd-count-__INDEX__" class="text-xs font-bold text-primary-700 bg-primary-100 px-2.5 py-0.5 rounded-full">
                        0 OPD Terpilih
                    </span>
                </div>

                <input type="text" oninput="filterOpdOptions(this, '__INDEX__')" aria-label="Cari OPD target"
                                           placeholder="Cari nama atau kode instansi OPD..."
                       class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control">

                <div class="max-h-36 overflow-y-auto space-y-1.5 p-2 bg-white rounded-xl border border-gray-200" role="group" aria-labelledby="edit-lhp-opd-label-__INDEX__">
                    @foreach($allOpds as $o)
                        <label class="opd-item-__INDEX__ flex items-center gap-2 p-1.5 rounded-lg hover:bg-primary-50 transition cursor-pointer select-none" data-search="{{ strtolower($o->kode . ' ' . $o->nama) }}">
                            <input type="checkbox" name="lhps[__INDEX__][opd_ids][]" value="{{ $o->id }}"
                                   onchange="updateOpdSelection('__INDEX__')"
                                   class="opd-cb-__INDEX__ h-3.5 w-3.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span class="text-xs font-bold text-primary-900">[{{ $o->kode }}]</span>
                            <span class="text-xs text-gray-800">{{ $o->nama }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="md:col-span-2">
                <label for="edit-lhp-ringkasan-__INDEX__" class="block text-xs font-bold text-gray-700 mb-1">Uraian Ringkas Hasil Pemeriksaan</label>
                <textarea id="edit-lhp-ringkasan-__INDEX__" name="lhps[__INDEX__][ringkasan]" rows="2" placeholder="Uraian pokok temuan atau evaluasi..."
                          class="w-full border bg-white px-3.5 py-2.5 text-xs text-gray-800 idds-form-control"></textarea>
            </div>

            <div class="md:col-span-2">
                <label for="edit-lhp-dokumen-__INDEX__" class="block text-xs font-bold text-gray-700 mb-1">Unggah Dokumen LHP (PDF / DOC / DOCX)</label>
                <input id="edit-lhp-dokumen-__INDEX__" type="file" name="lhps[__INDEX__][dokumen_lhp]" accept=".pdf,.doc,.docx"
                       class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>
        </div>

        {{-- UNSUR TEMUAN PER LHP --}}
        <div class="pt-4 border-t border-gray-100 space-y-4" role="group" aria-labelledby="edit-lhp-unsur-label-__INDEX__">
            <div>
                <p id="edit-lhp-unsur-label-__INDEX__" class="block text-xs font-bold text-gray-800  tracking-wider mb-2">
                    Unsur Temuan pada LHP Ini <span class="text-state-danger">*</span>
                </p>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-gray-200 bg-gray-50 hover:bg-white cursor-pointer transition select-none">
                        <input type="checkbox" name="lhps[__INDEX__][ada_administrasi]" value="1" onchange="toggleUnsurTemuan('__INDEX__', 'administrasi')" id="unsur_adm_cb___INDEX__" checked class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-xs font-bold text-gray-800">Unsur Administrasi</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-gray-200 bg-gray-50 hover:bg-white cursor-pointer transition select-none">
                        <input type="checkbox" name="lhps[__INDEX__][ada_kerugian]" value="1" onchange="toggleUnsurTemuan('__INDEX__', 'kerugian')" id="unsur_kg_cb___INDEX__" class="rounded border-gray-300 text-state-warning focus:ring-amber-500">
                        <span class="text-xs font-bold text-gray-800">Unsur Kerugian (Daerah / Negara)</span>
                    </label>
                </div>
            </div>

            {{-- Container Temuan Administrasi --}}
            <div id="container_adm___INDEX__" class="space-y-3 p-4 rounded-2xl bg-primary-50/40 border border-primary-200/80">
                <div class="flex items-center justify-between">
                    <h5 class="text-xs font-bold text-primary-900  tracking-wider flex items-center gap-1.5">
                        <span>Temuan Administrasi</span>
                    </h5>
                    <button type="button" onclick="addTemuanAdministrasiItem('__INDEX__')"
                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs font-bold transition cursor-pointer">
                        + Tambah Temuan Administrasi
                    </button>
                </div>

                <div id="adm_items_list___INDEX__" class="space-y-3">
                    <div class="adm-item-box bg-white p-3.5 rounded-xl border border-primary-200/90 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="adm-item-label text-xs font-bold text-primary-800">Temuan Administrasi #1</span>
                        </div>
                        <div>
                            <label for="edit-adm-uraian-__INDEX__-0" class="block text-xs font-bold text-gray-700 mb-1">Uraian Temuan Administrasi <span class="text-state-danger">*</span></label>
                            <textarea id="edit-adm-uraian-__INDEX__-0" name="lhps[__INDEX__][temuans_administrasi][0][uraian]" rows="2" placeholder="Jelaskan kondisi ketidaksesuaian administrasi..."
                                      class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control"></textarea>
                        </div>
                        <div>
                            <label for="edit-adm-penyebab-__INDEX__-0" class="block text-xs font-bold text-gray-700 mb-1">Penyebab (Sebab) <span class="text-gray-500 font-normal">(Opsional)</span></label>
                            <textarea id="edit-adm-penyebab-__INDEX__-0" name="lhps[__INDEX__][temuans_administrasi][0][penyebab]" rows="2" placeholder="Jelaskan penyebab ketidaksesuaian administrasi..."
                                      class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control"></textarea>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <div class="flex items-center justify-between">
                                <p id="edit-adm-rekomendasi-label-__INDEX__-0" class="text-xs font-bold text-gray-700">Poin-Poin Rekomendasi Bertahap <span class="text-state-danger">*</span></p>
                                <button type="button" onclick="addRekomendasiPointEdit(this)" class="inline-flex items-center gap-1 text-xs font-bold text-primary-700 hover:text-primary-900 bg-primary-50 hover:bg-primary-100 px-2.5 py-1 rounded-lg border border-primary-200 transition cursor-pointer">
                                    + Tambah Poin Rekomendasi
                                </button>
                            </div>
                            <div class="list-rekomendasi-points space-y-1.5" role="group" aria-labelledby="edit-adm-rekomendasi-label-__INDEX__-0">
                                <div class="item-rek-point flex items-start gap-1.5 bg-primary-50/30 p-2 rounded-lg border border-primary-100/80">
                                    <span class="rek-point-num text-xs font-bold bg-primary-600 text-white w-5 h-5 flex items-center justify-center rounded shrink-0 mt-0.5 shadow-2xs">1</span>
                                    <div class="flex-1">
                                        <textarea aria-label="Uraian butir rekomendasi 1" name="lhps[__INDEX__][temuans_administrasi][0][rekomendasi_items][]" rows="2"
                                                  class="input-adm-rekomendasi-item w-full border bg-white px-2.5 py-1.5 text-xs text-gray-800 idds-form-control"
                                                  placeholder="Uraian butir rekomendasi..."></textarea>
                                    </div>
                                    <button type="button" onclick="removeRekomendasiPointEdit(this)" class="btn-remove-rek-point hidden text-gray-500 hover:text-state-danger p-1 cursor-pointer mt-0.5" title="Hapus poin rekomendasi">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Container Temuan Kerugian --}}
            <div id="container_kg___INDEX__" class="hidden space-y-3 p-4 rounded-2xl bg-amber-50/40 border border-amber-200/80">
                <div class="flex items-center justify-between">
                    <h5 class="text-xs font-bold text-amber-900  tracking-wider flex items-center gap-1.5">
                        <span>Daftar Temuan Kerugian (Daerah / Negara)</span>
                    </h5>
                    <button type="button" onclick="addTemuanKerugianItem('__INDEX__')"
                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold transition cursor-pointer">
                        + Tambah Temuan Kerugian
                    </button>
                </div>

                <div id="kg_items_list___INDEX__" class="space-y-3">
                    <div class="kg-item-box bg-white p-3.5 rounded-xl border border-rose-200/90 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="kg-item-label text-xs font-bold text-rose-800">Temuan Kerugian #1</span>
                        </div>
                        <div>
                            <label for="edit-kg-uraian-__INDEX__-0" class="block text-xs font-bold text-gray-700 mb-1">Uraian Temuan Kerugian <span class="text-state-danger">*</span></label>
                            <textarea id="edit-kg-uraian-__INDEX__-0" name="lhps[__INDEX__][temuans_kerugian][0][uraian]" rows="2" placeholder="Jelaskan indikasi kerugian..."
                                      class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control"></textarea>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="edit-kg-kategori-__INDEX__-0" class="block text-xs font-bold text-gray-700 mb-1">Kategori / Sumber Kerugian <span class="text-state-danger">*</span></label>
                                <select id="edit-kg-kategori-__INDEX__-0" name="lhps[__INDEX__][temuans_kerugian][0][kategori_kerugian]" class="w-full border bg-white px-3 py-1.5 text-xs font-semibold text-gray-800 idds-form-control">
                                    <option value="Kerugian Daerah" selected>Kerugian Daerah (Kasda / APBD)</option>
                                    <option value="Kerugian Negara">Kerugian Negara (Kas Negara / APBN)</option>
                                </select>
                            </div>
                            <div>
                                <p id="edit-kg-jenis-label-__INDEX__-0" class="text-xs font-bold text-gray-700 mb-1">Klasifikasi Penyelesaian <span class="text-state-danger">*</span></p>
                                <div class="flex items-center gap-4 mt-2" role="group" aria-labelledby="edit-kg-jenis-label-__INDEX__-0">
                                    <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                        <input type="radio" name="lhps[__INDEX__][temuans_kerugian][0][jenis_kerugian]" value="Internal" class="h-3.5 w-3.5 text-state-danger focus:ring-rose-500">
                                        <span>Internal (ASN/OPD)</span>
                                    </label>
                                    <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                        <input type="radio" name="lhps[__INDEX__][temuans_kerugian][0][jenis_kerugian]" value="Eksternal" class="h-3.5 w-3.5 text-state-danger focus:ring-rose-500">
                                        <span>Eksternal (Rekanan)</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="edit-kg-nilai-__INDEX__-0" class="block text-xs font-bold text-gray-700 mb-1">Nominal Nilai Kerugian (Rp) <span class="text-state-danger">*</span></label>
                            <div class="relative">
                                <span aria-hidden="true" class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-bold text-gray-500">Rp</span>
                                <input id="edit-kg-nilai-__INDEX__-0" type="text" name="lhps[__INDEX__][temuans_kerugian][0][nilai_kerugian]" placeholder="0" oninput="formatRupiahInput(this)"
                                       class="w-full pl-9 border bg-white px-3 py-1.5 text-xs font-bold text-amber-900 idds-form-control">
                            </div>
                        </div>
                        <div>
                            <label for="edit-kg-penyebab-__INDEX__-0" class="block text-xs font-bold text-gray-700 mb-1">Penyebab (Sebab) <span class="text-gray-500 font-normal">(Opsional)</span></label>
                            <textarea id="edit-kg-penyebab-__INDEX__-0" name="lhps[__INDEX__][temuans_kerugian][0][penyebab]" rows="2" placeholder="Jelaskan penyebab terjadinya kerugian..."
                                      class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control"></textarea>
                        </div>
                        <div>
                            <label for="edit-kg-rekomendasi-__INDEX__-0" class="block text-xs font-bold text-gray-700 mb-1">Rekomendasi Penyetoran <span class="text-state-danger">*</span></label>
                            <textarea id="edit-kg-rekomendasi-__INDEX__-0" name="lhps[__INDEX__][temuans_kerugian][0][rekomendasi]" rows="2" placeholder="Rekomendasi penyetoran ke Kas Daerah / Kas Negara..."
                                      class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    let nextLhpIndex = {{ max($pemeriksaan->lhps->count(), 1) }};
    const pegawaisData = @json($pegawais);

    function addTemuanAdministrasiItem(lhpIdx) {
        const list = document.getElementById('adm_items_list_' + lhpIdx);
        if (!list) return;
        const currentCount = list.querySelectorAll('.adm-item-box').length;
        const newIndex = currentCount;

        const box = document.createElement('div');
        box.className = 'adm-item-box bg-white p-3.5 rounded-xl border border-primary-200/90 space-y-3';
        box.innerHTML = `
            <div class="flex items-center justify-between">
                <span class="adm-item-label text-xs font-bold text-primary-800">Temuan Administrasi #${newIndex + 1}</span>
                <button type="button" onclick="removeTemuanAdministrasiItem(this, '${lhpIdx}')" class="text-xs text-state-danger hover:text-red-700 font-bold cursor-pointer">Hapus</button>
            </div>
            <div>
                <label for="edit-adm-uraian-${lhpIdx}-${newIndex}" class="block text-xs font-bold text-gray-700 mb-1">Uraian Temuan Administrasi <span class="text-state-danger">*</span></label>
                <textarea id="edit-adm-uraian-${lhpIdx}-${newIndex}" name="lhps[${lhpIdx}][temuans_administrasi][${newIndex}][uraian]" rows="2" placeholder="Jelaskan kondisi ketidaksesuaian administrasi..."
                          class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control"></textarea>
            </div>
            <div>
                <label for="edit-adm-penyebab-${lhpIdx}-${newIndex}" class="block text-xs font-bold text-gray-700 mb-1">Penyebab (Sebab) <span class="text-gray-500 font-normal">(Opsional)</span></label>
                <textarea id="edit-adm-penyebab-${lhpIdx}-${newIndex}" name="lhps[${lhpIdx}][temuans_administrasi][${newIndex}][penyebab]" rows="2" placeholder="Jelaskan penyebab ketidaksesuaian administrasi..."
                          class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control"></textarea>
            </div>
            <div class="space-y-1.5 pt-1">
                <div class="flex items-center justify-between">
                    <p id="edit-adm-rekomendasi-label-${lhpIdx}-${newIndex}" class="text-xs font-bold text-gray-700">Poin-Poin Rekomendasi Bertahap <span class="text-state-danger">*</span></p>
                    <button type="button" onclick="addRekomendasiPointEdit(this)" class="inline-flex items-center gap-1 text-xs font-bold text-primary-700 hover:text-primary-900 bg-primary-50 hover:bg-primary-100 px-2.5 py-1 rounded-lg border border-primary-200 transition cursor-pointer">
                        + Tambah Poin Rekomendasi
                    </button>
                </div>
                <div class="list-rekomendasi-points space-y-1.5" role="group" aria-labelledby="edit-adm-rekomendasi-label-${lhpIdx}-${newIndex}">
                    <div class="item-rek-point flex items-start gap-1.5 bg-primary-50/30 p-2 rounded-lg border border-primary-100/80">
                        <span class="rek-point-num text-xs font-bold bg-primary-600 text-white w-5 h-5 flex items-center justify-center rounded shrink-0 mt-0.5 shadow-2xs">1</span>
                        <div class="flex-1">
                            <textarea aria-label="Uraian butir rekomendasi 1" name="lhps[${lhpIdx}][temuans_administrasi][${newIndex}][rekomendasi_items][]" rows="2"
                                      class="input-adm-rekomendasi-item w-full border bg-white px-2.5 py-1.5 text-xs text-gray-800 idds-form-control"
                                      placeholder="Uraian butir rekomendasi..."></textarea>
                        </div>
                        <button type="button" onclick="removeRekomendasiPointEdit(this)" class="btn-remove-rek-point hidden text-gray-500 hover:text-state-danger p-1 cursor-pointer mt-0.5" title="Hapus poin rekomendasi">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        `;
        list.appendChild(box);
        updateTemuanAdministrasiLabels(lhpIdx);
    }

    function removeTemuanAdministrasiItem(btn, lhpIdx) {
        const list = document.getElementById('adm_items_list_' + lhpIdx);
        const box = btn.closest('.adm-item-box');
        if (list && box) {
            const count = list.querySelectorAll('.adm-item-box').length;
            if (count <= 1) {
                window.showAppNotice('Minimal satu temuan administrasi harus diisi jika unsur administrasi dipilih.', { title: 'Temuan administrasi diperlukan', variant: 'warning' });
                return;
            }
            box.remove();
            updateTemuanAdministrasiLabels(lhpIdx);
        }
    }

    function updateTemuanAdministrasiLabels(lhpIdx) {
        const list = document.getElementById('adm_items_list_' + lhpIdx);
        window.updateNumberedItems(list, {
            itemSelector: '.adm-item-box',
            labelSelector: '.adm-item-label',
            labelPrefix: 'Temuan Administrasi',
        });
    }

    function addTemuanKerugianItem(lhpIdx) {
        const list = document.getElementById('kg_items_list_' + lhpIdx);
        if (!list) return;
        const currentCount = list.querySelectorAll('.kg-item-box').length;
        const newIndex = currentCount;

        const box = document.createElement('div');
        box.className = 'kg-item-box bg-white p-3.5 rounded-xl border border-rose-200/90 space-y-3';
        box.innerHTML = `
            <div class="flex items-center justify-between">
                <span class="kg-item-label text-xs font-bold text-rose-800">Temuan Kerugian #${newIndex + 1}</span>
                <button type="button" onclick="removeTemuanKerugianItem(this, '${lhpIdx}')" class="text-xs text-state-danger hover:text-red-700 font-bold cursor-pointer">Hapus</button>
            </div>
            <div>
                <label for="edit-kg-uraian-${lhpIdx}-${newIndex}" class="block text-xs font-bold text-gray-700 mb-1">Uraian Temuan Kerugian <span class="text-state-danger">*</span></label>
                <textarea id="edit-kg-uraian-${lhpIdx}-${newIndex}" name="lhps[${lhpIdx}][temuans_kerugian][${newIndex}][uraian]" rows="2" placeholder="Jelaskan indikasi kerugian..."
                          class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control"></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="edit-kg-kategori-${lhpIdx}-${newIndex}" class="block text-xs font-bold text-gray-700 mb-1">Kategori / Sumber Kerugian <span class="text-state-danger">*</span></label>
                    <select id="edit-kg-kategori-${lhpIdx}-${newIndex}" name="lhps[${lhpIdx}][temuans_kerugian][${newIndex}][kategori_kerugian]" class="w-full border bg-white px-3 py-1.5 text-xs font-semibold text-gray-800 idds-form-control">
                        <option value="Kerugian Daerah" selected>Kerugian Daerah (Kasda / APBD)</option>
                        <option value="Kerugian Negara">Kerugian Negara (Kas Negara / APBN)</option>
                    </select>
                </div>
                <div>
                    <p id="edit-kg-jenis-label-${lhpIdx}-${newIndex}" class="text-xs font-bold text-gray-700 mb-1">Klasifikasi Penyelesaian <span class="text-state-danger">*</span></p>
                    <div class="flex items-center gap-4 mt-2" role="group" aria-labelledby="edit-kg-jenis-label-${lhpIdx}-${newIndex}">
                        <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                            <input type="radio" name="lhps[${lhpIdx}][temuans_kerugian][${newIndex}][jenis_kerugian]" value="Internal" class="h-3.5 w-3.5 text-state-danger focus:ring-rose-500">
                            <span>Internal (ASN/OPD)</span>
                        </label>
                        <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                            <input type="radio" name="lhps[${lhpIdx}][temuans_kerugian][${newIndex}][jenis_kerugian]" value="Eksternal" class="h-3.5 w-3.5 text-state-danger focus:ring-rose-500">
                            <span>Eksternal (Rekanan)</span>
                        </label>
                    </div>
                </div>
            </div>
            <div>
                <label for="edit-kg-nilai-${lhpIdx}-${newIndex}" class="block text-xs font-bold text-gray-700 mb-1">Nominal Nilai Kerugian (Rp) <span class="text-state-danger">*</span></label>
                <div class="relative">
                    <span aria-hidden="true" class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-bold text-gray-500">Rp</span>
                    <input id="edit-kg-nilai-${lhpIdx}-${newIndex}" type="text" name="lhps[${lhpIdx}][temuans_kerugian][${newIndex}][nilai_kerugian]" placeholder="0" oninput="formatRupiahInput(this)"
                           class="w-full pl-9 border bg-white px-3 py-1.5 text-xs font-bold text-amber-900 idds-form-control">
                </div>
            </div>
            <div>
                <label for="edit-kg-penyebab-${lhpIdx}-${newIndex}" class="block text-xs font-bold text-gray-700 mb-1">Penyebab (Sebab) <span class="text-gray-500 font-normal">(Opsional)</span></label>
                <textarea id="edit-kg-penyebab-${lhpIdx}-${newIndex}" name="lhps[${lhpIdx}][temuans_kerugian][${newIndex}][penyebab]" rows="2" placeholder="Jelaskan penyebab terjadinya kerugian..."
                          class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control"></textarea>
            </div>
            <div>
                <label for="edit-kg-rekomendasi-${lhpIdx}-${newIndex}" class="block text-xs font-bold text-gray-700 mb-1">Rekomendasi Penyetoran <span class="text-state-danger">*</span></label>
                <textarea id="edit-kg-rekomendasi-${lhpIdx}-${newIndex}" name="lhps[${lhpIdx}][temuans_kerugian][${newIndex}][rekomendasi]" rows="2" placeholder="Rekomendasi penyetoran ke Kas Daerah / Kas Negara..."
                          class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control"></textarea>
            </div>
        `;
        list.appendChild(box);
        updateTemuanKerugianLabels(lhpIdx);
    }

    function removeTemuanKerugianItem(btn, lhpIdx) {
        const list = document.getElementById('kg_items_list_' + lhpIdx);
        const box = btn.closest('.kg-item-box');
        if (list && box) {
            const count = list.querySelectorAll('.kg-item-box').length;
            if (count <= 1) {
                window.showAppNotice('Minimal satu temuan kerugian harus diisi jika unsur kerugian dipilih.', { title: 'Temuan kerugian diperlukan', variant: 'warning' });
                return;
            }
            box.remove();
            updateTemuanKerugianLabels(lhpIdx);
        }
    }

    function updateTemuanKerugianLabels(lhpIdx) {
        const list = document.getElementById('kg_items_list_' + lhpIdx);
        window.updateNumberedItems(list, {
            itemSelector: '.kg-item-box',
            labelSelector: '.kg-item-label',
            labelPrefix: 'Temuan Kerugian',
        });
    }

    // Tambah & Hapus Poin Rekomendasi di Edit Pemeriksaan
    window.addRekomendasiPointEdit = function(btn) {
        window.addRecommendationPoint(btn, '.adm-item-box', 'removeRekomendasiPointEdit', window.updateRekomendasiPointBadgesEdit);
    };

    window.removeRekomendasiPointEdit = function(btn) {
        window.removeRecommendationPoint(btn, window.updateRekomendasiPointBadgesEdit);
    };

    window.updateRekomendasiPointBadgesEdit = function(list) {
        window.updateRecommendationPointBadges(list);
    };

    function addLhpCard() {
        const container = document.getElementById('lhps-container');
        const template = document.getElementById('lhp-template');
        if (!container || !template) return;

        const currentLhpCount = container.querySelectorAll('.lhp-card').length;
        const lhpNum = currentLhpCount + 1;
        const idx = nextLhpIndex++;

        let html = template.innerHTML
            .replace(/__INDEX__/g, idx)
            .replace(/__NUM__/g, lhpNum);

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html.trim();
        const newCard = tempDiv.firstChild;

        container.appendChild(newCard);
        updateLhpCardLabels();
    }

    function removeLhpCard(btn) {
        const card = btn.closest('.lhp-card');
        const container = document.getElementById('lhps-container');
        if (card && container) {
            const count = container.querySelectorAll('.lhp-card').length;
            if (count <= 1) {
                window.showAppNotice('SPT harus memiliki minimal satu LHP.', { title: 'LHP tidak dapat dihapus', variant: 'warning' });
                return;
            }
            window.showAppConfirm('Hapus kartu LHP ini?', {
                title: 'Hapus LHP?',
                variant: 'danger',
                acceptLabel: 'Ya, Hapus',
            }).then((confirmed) => {
                if (!confirmed) return;
                card.remove();
                updateLhpCardLabels();
            });
        }
    }

    function updateLhpCardLabels() {
        window.updateDynamicCardList(document.getElementById('lhps-container'), {
            cardSelector: '.lhp-card',
            badgeSelector: '.lhp-card-badge',
            badgePrefix: 'LHP',
        });
    }

    let nextTimMemberIndex = {{ $pemeriksaan->timPemeriksas->count() + 1 }};

    function addTimMemberRow() {
        const container = document.getElementById('tim-members-container');
        if (!container) return;
        const index = nextTimMemberIndex++;

        const row = document.createElement('div');
        row.className = 'tim-member-row flex flex-col sm:flex-row items-center gap-3 p-3 rounded-2xl bg-gray-50 border border-gray-200';
        row.innerHTML = `
            <div class="flex-1 w-full">
                <select name="tim_pemeriksa[${index}][pegawai_id]" data-pegawai-select required
                        class="w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control">
                    <option value="">-- Pilih Pegawai --</option>
                </select>
            </div>
            <div class="w-full sm:w-48">
                <select name="tim_pemeriksa[${index}][peran_tim]" required
                        class="w-full border bg-white px-3 py-2 text-xs text-gray-800 font-semibold idds-form-control">
                    <option value="Anggota Tim">Anggota Tim</option>
                    <option value="Ketua Tim">Ketua Tim</option>
                    <option value="Pengendali Teknis">Pengendali Teknis</option>
                    <option value="Asisten Penanggung Jawab">Asisten Penanggung Jawab</option>
                </select>
            </div>
            <button type="button" onclick="removeTimMemberRow(this)"
                    class="p-2 text-gray-500 hover:text-state-danger hover:bg-red-50 rounded-xl transition cursor-pointer" title="Hapus Anggota">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        `;

        const pegawaiSelect = row.querySelector('[data-pegawai-select]');
        pegawaisData.forEach(pegawai => {
            const option = document.createElement('option');
            option.value = String(pegawai.id);
            option.textContent = `${pegawai.nama} — ${pegawai.jabatan} (NIP. ${pegawai.nip || '-'})`;
            pegawaiSelect.appendChild(option);
        });

        container.appendChild(row);
    }

    function removeTimMemberRow(btn) {
        const container = document.getElementById('tim-members-container');
        const row = btn.closest('.tim-member-row');
        if (container && row) {
            const count = container.querySelectorAll('.tim-member-row').length;
            if (count <= 1) {
                window.showAppNotice('Tim Pemeriksa minimal memiliki satu anggota.', { title: 'Susunan tim belum lengkap', variant: 'warning' });
                return;
            }
            row.remove();
        }
    }

</script>
@endsection
