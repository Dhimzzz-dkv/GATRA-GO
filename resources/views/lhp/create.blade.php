@extends('layouts.app')

@section('title', 'Input LHP & Tindak Lanjut')
@section('page-title', 'Form Input Laporan Hasil Pemeriksaan (LHP)')
@section('page-subtitle', 'Penginputan Multi-LHP, Target OPD, Unsur Temuan, dan Tindak Lanjut')

@section('content')
<div class="max-w-5xl mx-auto pb-28 space-y-6">
    @php
        $user = auth()->user();
        $parentBreadcrumb = $user->isAdminSumber() && ! $user->canManageSpt()
            ? ['label' => 'LHP', 'href' => route('lhp.index')]
            : ['label' => 'Pemeriksaan & SPT', 'href' => route('pemeriksaan.index')];
    @endphp

    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        $parentBreadcrumb,
        ['label' => 'Input LHP'],
    ]" />

    <form id="unified-lhp-form" method="POST" action="{{ route('lhp.store-direct') }}" enctype="multipart/form-data" class="space-y-8" data-lhp-title-ellipsis="true">
        @csrf

        {{-- ========================================================================= --}}
        {{-- KARTU PENGATURAN SUMBER & TAHUN ANGGARAN                                  --}}
        {{-- ========================================================================= --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                {{-- Sumber Pemeriksaan --}}
                <div>
                    <p id="sumber-pemeriksaan-label" class="mb-1.5 block text-xs font-bold text-gray-700  tracking-wider">Sumber Pengawasan / Pemeriksaan <span class="text-state-danger">*</span></p>
                    @if($lockedSumber)
                        <input type="hidden" name="sumber_pemeriksaan" value="{{ $lockedSumber }}">
                        <div class="flex items-center gap-2.5 rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-xs font-bold text-blue-900 shadow-2xs">
                            <span class="inline-flex items-center rounded-md bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-800  tracking-wider">Terkunci</span>
                            <span>Sumber: {{ $lockedSumber }}</span>
                        </div>
                    @else
                        <select id="sumber-pemeriksaan" name="sumber_pemeriksaan" aria-labelledby="sumber-pemeriksaan-label" required
                                class="w-full border bg-white px-4 py-2.5 text-xs font-bold text-gray-800 shadow-2xs idds-form-control">
                            @foreach($sumberList as $s)
                                <option value="{{ $s }}" {{ old('sumber_pemeriksaan') == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    @endif
                    @error('sumber_pemeriksaan')
                        <p class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tahun Anggaran --}}
                <div>
                    <label for="tahun" class="mb-1.5 block text-xs font-bold text-gray-700 tracking-wider">Tahun Anggaran <span class="text-state-danger">*</span></label>
                    <select id="tahun" name="tahun" required
                            class="w-full border bg-white px-4 py-2.5 text-xs font-bold text-gray-800 shadow-2xs idds-form-control">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ old('tahun', $currentYear) == $y ? 'selected' : '' }}>Tahun Anggaran {{ $y }}</option>
                        @endforeach
                    </select>
                    @error('tahun')
                        <p class="mt-1 text-xs text-red-700 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- Bagian 1: Input Laporan Hasil Pemeriksaan (LHP) - MULTI-LHP DINAMIS       --}}
        {{-- ========================================================================= --}}
        <div class="rounded-3xl border border-gray-200/80 bg-white p-6 md:p-8 shadow-sm transition hover:shadow-md">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-800 text-white font-bold text-base shadow-sm">
                        1
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 tracking-tight">Bagian 1: Input Laporan Hasil Pemeriksaan (LHP)</h2>
                        <p class="text-xs text-gray-500 font-medium">Satu pengawasan dapat memiliki banyak LHP. Klik tombol "+ Tambah LHP" untuk menambah form LHP baru secara dinamis.</p>
                    </div>
                </div>

                {{-- Tombol + Tambah LHP (Desain Hijau Emerald Sama Persis dengan Screenshot) --}}
                <button type="button" onclick="addLhpCard()"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-700 text-white font-semibold text-xs px-5 py-3 shadow-sm hover:bg-emerald-800 transition-colors cursor-pointer">
                    <svg class="h-4 w-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah LHP</span>
                </button>
            </div>

            @error('lhps')
                <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800" role="alert">
                    {{ $message }}
                </div>
            @enderror

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
                            <label for="lhp-nomor-0" class="mb-1 block text-xs font-bold text-gray-700">No. LHP <span class="text-state-danger">*</span></label>
                            <input id="lhp-nomor-0" type="text" name="lhp_nomor[]" value="{{ old('lhp_nomor.0', old('lhps.0.nomor_lhp', old('nomor_lhp'))) }}" required oninput="syncLhpOptionsToTindakLanjut()"
                                   class="input-nomor-lhp w-full border bg-white px-3.5 py-2.5 text-sm text-gray-800 idds-form-control"
                                   placeholder="Contoh: 05/LHP/BPK/2026 atau 700/01/LHP/2026">
                        </div>

                        {{-- Tanggal LHP --}}
                        <div>
                            <label for="lhp-tanggal-0" class="mb-1 block text-xs font-bold text-gray-700">Tanggal LHP <span class="text-state-danger">*</span></label>
                            <input id="lhp-tanggal-0" type="date" name="lhps[0][tanggal_lhp]" value="{{ old('lhps.0.tanggal_lhp', date('Y-m-d')) }}" required
                                   class="w-full border bg-white px-3.5 py-2.5 text-sm text-gray-800 idds-form-control">
                        </div>

                        {{-- Judul LHP --}}
                        <div class="md:col-span-2">
                            <label for="lhp-judul-0" class="mb-1 block text-xs font-bold text-gray-700">Judul LHP</label>
                            <input id="lhp-judul-0" type="text" name="lhps[0][judul_lhp]" value="{{ old('lhps.0.judul_lhp') }}" oninput="syncLhpOptionsToTindakLanjut()"
                                   class="input-judul-lhp w-full border bg-white px-3.5 py-2.5 text-sm text-gray-800 idds-form-control"
                                   placeholder="Contoh: Laporan Hasil Pemeriksaan Kepatuhan Keuangan dan Aset TA 2025">
                        </div>

                        {{-- Daftar OPD Target (Multi-Select Searchable) --}}
                        <div class="md:col-span-2 rounded-2xl border border-primary-200/90 bg-primary-50/40 p-4">
                            <div class="flex items-center justify-between mb-2">
                                <p id="lhp-opd-target-0" class="block text-xs font-bold text-gray-800">
                                    Daftar OPD Target <span class="text-state-danger">*</span>
                                    <span class="text-xs font-normal text-gray-500 ml-1">(Dapat memilih lebih dari 1 OPD target)</span>
                                </p>
                                <span class="selected-opd-count text-xs font-bold text-primary-700 bg-primary-100 px-2.5 py-0.5 rounded-full">
                                    0 OPD Terpilih
                                </span>
                            </div>

                            {{-- Kotak Pencarian OPD --}}
                            <div class="relative mb-2">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </span>
                                <input type="text" oninput="filterOpdOptions(this)" aria-label="Cari OPD pada daftar target"
                                       class="opd-search-input w-full border bg-white pl-9 pr-4 py-2 text-xs text-gray-800 placeholder-gray-400 shadow-2xs idds-form-control"
                                       placeholder="Cari OPD (misal: Pendidikan, Kesehatan, PUPR)...">
                            </div>

                            {{-- List Checkbox OPD (Scrollable) --}}
                            <div class="opd-checkbox-list max-h-44 overflow-y-auto space-y-1 rounded-xl border border-gray-200 bg-white p-2 divide-y divide-gray-50" role="group" aria-labelledby="lhp-opd-target-0">
                                @foreach($opds as $o)
                                    <label class="opd-item flex items-center justify-between gap-2 p-1.5 rounded-lg hover:bg-primary-50/70 cursor-pointer transition text-xs select-none" data-search="{{ strtolower($o->kode . ' ' . $o->nama . ' ' . $o->singkatan) }}">
                                        <div class="flex items-center gap-2.5">
                                            <input type="checkbox" name="lhp_opd_ids[0][]" value="{{ $o->id }}"
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
                                <div class="selected-opd-chips flex flex-wrap gap-1.5 min-h-[32px] items-center p-2 rounded-xl bg-white border border-dashed border-gray-200" data-opd-chip-mode="codes">
                                    <span class="empty-opd-placeholder text-xs text-gray-500 italic">Belum ada OPD yang dipilih (centang minimal 1 OPD di atas)</span>
                                </div>
                            </div>
                        </div>

                        {{-- Uraian Singkat / Ringkasan --}}
                        <div class="md:col-span-2">
                            <label for="lhp-ringkasan-0" class="mb-1 block text-xs font-bold text-gray-700">Uraian Singkat / Ringkasan Hasil Pemeriksaan</label>
                            <textarea id="lhp-ringkasan-0" name="lhps[0][ringkasan]" rows="2"
                                      class="w-full border bg-white px-3.5 py-2 text-sm text-gray-800 idds-form-control"
                                      placeholder="Tuliskan uraian ringkas pokok hasil temuan atau evaluasi pemeriksaan..."></textarea>
                        </div>

                        {{-- Pilihan Unsur Temuan: Administrasi & Kerugian Daerah --}}
                        <div class="md:col-span-2 rounded-2xl border border-amber-200 bg-amber-50/50 p-4 space-y-4" role="group" aria-labelledby="lhp-unsur-temuan-0">
                            <div>
                                <p id="lhp-unsur-temuan-0" class="block text-xs font-bold text-amber-900  tracking-wider mb-1">
                                    Unsur Temuan &amp; Rekomendasi <span class="text-state-danger">*</span>
                                </p>
                                <p class="text-xs text-amber-800/80 mb-3">Pilih salah satu atau kedua unsur temuan di bawah ini. Rekomendasi melekat langsung pada masing-masing temuan.</p>

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
                                                <button type="button" onclick="addRekomendasiPointLhp(this)" class="inline-flex items-center gap-1 text-xs font-bold text-primary-700 hover:text-primary-900 bg-primary-50 hover:bg-primary-100 px-2.5 py-1 rounded-lg border border-primary-200 transition cursor-pointer">
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
                                                    <button type="button" onclick="removeRekomendasiPointLhp(this)" class="btn-remove-rek-point hidden text-gray-500 hover:text-state-danger p-1 cursor-pointer mt-0.5" title="Hapus poin rekomendasi">
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
                                                        <input type="radio" name="lhps[0][temuans_kerugian][0][jenis_kerugian]" value="Internal" class="input-kg-jenis h-3 w-3 text-state-danger focus:ring-rose-500">
                                                        <span>Internal (ASN/OPD)</span>
                                                    </label>
                                                    <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                                        <input type="radio" name="lhps[0][temuans_kerugian][0][jenis_kerugian]" value="Eksternal" class="input-kg-jenis h-3 w-3 text-state-danger focus:ring-rose-500">
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
                            <label class="mb-1 block text-xs font-bold text-gray-700">Unggah File Dokumen LHP (PDF / DOC / DOCX)</label>
                            <input type="file" name="lhps[0][dokumen_lhp]" accept=".pdf,.doc,.docx"
                                   class="block w-full text-xs text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary-100 file:text-primary-700 hover:file:bg-primary-200 cursor-pointer">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SCROLL INDICATOR --}}
        <div class="flex items-center justify-center gap-2 text-xs font-bold text-gray-500  tracking-widest py-1">
            <span>↓ Gulir / Scroll ke Bawah untuk Input Tindak Lanjut ↓</span>
        </div>

        {{-- ========================================================================= --}}
        {{-- BAGIAN 2: DATA TINDAK LANJUT - MULTI-TL DINAMIS                           --}}
        {{-- ========================================================================= --}}
        <div class="rounded-3xl border border-emerald-200/80 bg-emerald-50/40 p-6 md:p-8 shadow-sm transition hover:shadow-md">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-emerald-200 pb-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-700 text-white font-bold text-base shadow-sm">
                        2
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 tracking-tight">Bagian 2: Input Tindak Lanjut</h2>
                        <p class="text-xs text-gray-500 font-medium">Pilih LHP terkait dan input uraian tindak lanjut serta bukti setoran kas daerah.</p>
                    </div>
                </div>

                {{-- Tombol + Tambah Tindak Lanjut --}}
                <button type="button" onclick="addTindakLanjutCard()"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 text-white font-bold text-xs px-5 py-3 shadow-lg shadow-emerald-600/30 hover:bg-emerald-700 transition active:scale-95 cursor-pointer">
                    <svg class="h-4 w-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Tindak Lanjut</span>
                </button>
            </div>

            {{-- Container Seluruh Card Tindak Lanjut --}}
            <div id="tl-container" class="mt-6 space-y-6">
                {{-- Tindak Lanjut #1 (Default Pertama) --}}
                <div class="tl-card rounded-2xl border border-emerald-200 bg-white p-5 md:p-6 shadow-sm relative transition" data-tl-index="0">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                        <div class="flex items-center gap-2">
                            <x-sequence-badge type="tindak-lanjut">
                                Tindak Lanjut #1
                            </x-sequence-badge>
                            <span class="text-xs text-gray-500 font-bold">Progress / Realisasi Pemenuhan</span>
                        </div>
                        <button type="button" onclick="removeTindakLanjutCard(this)" class="btn-remove-tl hidden text-xs font-bold text-state-danger hover:text-red-700 hover:underline cursor-pointer">
                            Hapus Tindak Lanjut Ini
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        {{-- Pilihan LHP Terkait --}}
                        <div class="md:col-span-2">
                            <label for="lhp-tl-0-lhp" class="mb-1 block text-xs font-bold text-gray-700">Terkait Dokumen LHP <span class="text-state-danger">*</span></label>
                            <select id="lhp-tl-0-lhp" name="tindak_lanjuts[0][lhp_index]" class="select-target-lhp w-full border bg-white px-3.5 py-2.5 text-xs font-bold text-primary-900 idds-form-control">
                                <option value="0">LHP #1 (Belum ada nomor)</option>
                            </select>
                        </div>

                        {{-- Tanggal Tindak Lanjut --}}
                        <div>
                            <label for="lhp-tl-0-tanggal" class="mb-1 block text-xs font-bold text-gray-700">Tanggal Tindak Lanjut <span class="text-state-danger">*</span></label>
                            <input id="lhp-tl-0-tanggal" type="date" name="tindak_lanjuts[0][tanggal_tindak_lanjut]" value="{{ date('Y-m-d') }}"
                                   class="w-full border bg-white px-3.5 py-2.5 text-sm text-gray-800 idds-form-control">
                        </div>

                        {{-- Jenis Temuan yang Ditindaklanjuti --}}
                        <div>
                            <label for="lhp-tl-0-jenis-temuan" class="mb-1 block text-xs font-bold text-gray-700">Jenis Temuan <span class="text-state-danger">*</span></label>
                            <select id="lhp-tl-0-jenis-temuan" name="tindak_lanjuts[0][temuan_type]" onchange="toggleTlFields(this)"
                                    class="select-tl-temuan-type w-full border bg-white px-3.5 py-2.5 text-xs font-bold text-gray-800 idds-form-control">
                                <option value="kerugian">Kerugian Daerah (Pengembalian Kasda)</option>
                                <option value="kerugian_negara">Kerugian Negara (Penyetoran Kas Negara)</option>
                                <option value="administrasi">Administrasi (Perbaikan Dokumen / SOP)</option>
                            </select>
                        </div>

                        {{-- Uraian Tindak Lanjut --}}
                        <div class="md:col-span-2">
                            <label for="lhp-tl-0-uraian" class="mb-1 block text-xs font-bold text-gray-700">Uraian Tindak Lanjut <span class="text-state-danger">*</span></label>
                            <textarea id="lhp-tl-0-uraian" name="tindak_lanjuts[0][uraian_tindak_lanjut]" rows="2"
                                      class="w-full border bg-white px-3.5 py-2 text-sm text-gray-800 idds-form-control"
                                      placeholder="Tuliskan uraian rincian tindak lanjut yang telah dilaksanakan..."></textarea>
                        </div>

                        {{-- Dokumen Bukti TL Umum --}}
                        <div class="md:col-span-2">
                            <label for="lhp-tl-0-dokumen" class="mb-1 block text-xs font-bold text-gray-700">Unggah Dokumen Bukti Tindak Lanjut (PDF/DOCX/JPG)</label>
                            <input id="lhp-tl-0-dokumen" type="file" name="tindak_lanjuts[0][dokumen_bukti]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                   class="block w-full text-xs text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-700 hover:file:bg-emerald-200 cursor-pointer">
                        </div>

                        {{-- Bagian Khusus Setoran Kerugian (Dinamis Ditampilkan jika jenis kerugian) --}}
                        <div class="box-setoran-kerugian md:col-span-2 rounded-2xl border border-emerald-300 bg-emerald-50/60 p-4 space-y-4">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-emerald-100 text-xs font-bold text-emerald-700">Rp</span>
                                <h4 class="title-setoran-kerugian text-xs font-bold text-emerald-950  tracking-wider">Rincian Penyetoran ke Kas Daerah</h4>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                {{-- Nomor Bukti Setor / STS --}}
                                <div>
                                    <label for="lhp-tl-0-nomor-setor" class="mb-1 block text-xs font-bold text-gray-700">Nomor STS / NTPN</label>
                                    <input id="lhp-tl-0-nomor-setor" type="text" name="tindak_lanjuts[0][nomor_bukti_setor]"
                                           class="w-full border bg-white px-3.5 py-2 text-xs text-gray-800 idds-form-control"
                                           placeholder="Contoh: STS-2026/03/0123 atau NTPN-12345">
                                </div>

                                {{-- Tanggal Setor --}}
                                <div>
                                    <label for="lhp-tl-0-tanggal-setor" class="mb-1 block text-xs font-bold text-gray-700">Tanggal Setor</label>
                                    <input id="lhp-tl-0-tanggal-setor" type="date" name="tindak_lanjuts[0][tanggal_setor]"
                                           class="w-full border bg-white px-3.5 py-2 text-xs text-gray-800 idds-form-control">
                                </div>

                                {{-- Jumlah Setor --}}
                                <div>
                                    <label for="lhp-tl-0-jumlah-setor" class="mb-1 block text-xs font-bold text-gray-700">Jumlah Setoran (Rp)</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-xs font-bold text-gray-500">Rp</span>
                                        <input id="lhp-tl-0-jumlah-setor" type="text" name="tindak_lanjuts[0][jumlah_setor]" oninput="formatRupiahLive(this)"
                                               class="input-tl-setor w-full border bg-white pl-9 pr-3 py-2 text-xs font-bold text-emerald-700 idds-form-control"
                                               placeholder="0">
                                    </div>
                                </div>
                            </div>

                            {{-- Unggah Slip Setor STS --}}
                            <div>
                                <label for="lhp-tl-0-dokumen-setor" class="mb-1 block text-xs font-bold text-gray-700">Unggah Slip / Bukti Setor STS (PDF/JPG/PNG)</label>
                                <input id="lhp-tl-0-dokumen-setor" type="file" name="tindak_lanjuts[0][dokumen_setor]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                       class="block w-full text-xs text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-200/70 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Floating Bottom Action Bar --}}
        <div class="sticky bottom-6 z-30 rounded-2xl border border-gray-200/80 bg-white/95 p-4 shadow-xl backdrop-blur-md">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-3 w-3 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                    </span>
                    <span class="text-xs font-bold text-gray-600">Pastikan seluruh nomor LHP dan target OPD sudah dipilih dengan benar.</span>
                </div>

                <div class="flex w-full flex-col-reverse gap-2 sm:w-auto sm:flex-row sm:items-center sm:gap-3">
                    <a href="{{ route('lhp.index') }}"
                       class="w-full rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-center text-xs font-bold text-gray-700 hover:bg-gray-50 transition shadow-2xs sm:w-auto">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-700 px-6 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-primary-800 transition-colors cursor-pointer sm:w-auto">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Simpan Data LHP &amp; Temuan</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- TEMPLATE DUKUNGAN MULTI-LHP DINAMIS (HIDDEN) --}}
<template id="lhp-card-template">
    <div class="lhp-card rounded-2xl border-2 border-primary-200 bg-white p-5 md:p-6 shadow-sm relative transition" data-lhp-index="__LHP_INDEX__">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
            <div class="flex items-center gap-2">
                <x-sequence-badge type="lhp">
                    LHP #__LHP_NUM__
                </x-sequence-badge>
                <span class="text-xs text-gray-500 font-bold">Laporan Hasil Pemeriksaan</span>
            </div>
            <button type="button" onclick="removeLhpCard(this)" class="btn-remove-lhp text-xs font-bold text-state-danger hover:text-red-700 hover:underline cursor-pointer">
                Hapus LHP Ini
            </button>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            {{-- No. LHP --}}
            <div>
                <label for="lhp-nomor-__LHP_INDEX__" class="mb-1 block text-xs font-bold text-gray-700">No. LHP <span class="text-state-danger">*</span></label>
                <input id="lhp-nomor-__LHP_INDEX__" type="text" name="lhp_nomor[]" required oninput="syncLhpOptionsToTindakLanjut()"
                       class="input-nomor-lhp w-full border bg-white px-3.5 py-2.5 text-sm text-gray-800 idds-form-control"
                       placeholder="Contoh: 05/LHP/BPK/2026 atau 700/02/LHP/2026">
            </div>

            {{-- Tanggal LHP --}}
            <div>
                <label for="lhp-tanggal-__LHP_INDEX__" class="mb-1 block text-xs font-bold text-gray-700">Tanggal LHP <span class="text-state-danger">*</span></label>
                <input id="lhp-tanggal-__LHP_INDEX__" type="date" name="lhps[__LHP_INDEX__][tanggal_lhp]" value="{{ date('Y-m-d') }}" required
                       class="w-full border bg-white px-3.5 py-2.5 text-sm text-gray-800 idds-form-control">
            </div>

            {{-- Judul LHP --}}
            <div class="md:col-span-2">
                <label for="lhp-judul-__LHP_INDEX__" class="mb-1 block text-xs font-bold text-gray-700">Judul LHP</label>
                <input id="lhp-judul-__LHP_INDEX__" type="text" name="lhps[__LHP_INDEX__][judul_lhp]" oninput="syncLhpOptionsToTindakLanjut()"
                       class="input-judul-lhp w-full border bg-white px-3.5 py-2.5 text-sm text-gray-800 idds-form-control"
                       placeholder="Contoh: Laporan Hasil Pemeriksaan Kepatuhan Keuangan dan Aset TA 2025">
            </div>

            {{-- Daftar OPD Target (Multi-Select Searchable) --}}
            <div class="md:col-span-2 rounded-2xl border border-primary-200/90 bg-primary-50/40 p-4">
                <div class="flex items-center justify-between mb-2">
                    <p id="lhp-opd-target-__LHP_INDEX__" class="block text-xs font-bold text-gray-800">
                        Daftar OPD Target <span class="text-state-danger">*</span>
                        <span class="text-xs font-normal text-gray-500 ml-1">(Dapat memilih lebih dari 1 OPD target)</span>
                    </p>
                    <span class="selected-opd-count text-xs font-bold text-primary-700 bg-primary-100 px-2.5 py-0.5 rounded-full">
                        0 OPD Terpilih
                    </span>
                </div>

                {{-- Kotak Pencarian OPD --}}
                <div class="relative mb-2">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input type="text" oninput="filterOpdOptions(this)" aria-label="Cari OPD pada daftar target"
                           class="opd-search-input w-full border bg-white pl-9 pr-4 py-2 text-xs text-gray-800 placeholder-gray-400 shadow-2xs idds-form-control"
                           placeholder="Cari OPD (misal: Pendidikan, Kesehatan, PUPR)...">
                </div>

                {{-- List Checkbox OPD (Scrollable) --}}
                <div class="opd-checkbox-list max-h-44 overflow-y-auto space-y-1 rounded-xl border border-gray-200 bg-white p-2 divide-y divide-gray-50" role="group" aria-labelledby="lhp-opd-target-__LHP_INDEX__">
                    @foreach($opds as $o)
                        <label class="opd-item flex items-center justify-between gap-2 p-1.5 rounded-lg hover:bg-primary-50/70 cursor-pointer transition text-xs select-none" data-search="{{ strtolower($o->kode . ' ' . $o->nama . ' ' . $o->singkatan) }}">
                            <div class="flex items-center gap-2.5">
                                <input type="checkbox" name="lhp_opd_ids[__LHP_INDEX__][]" value="{{ $o->id }}"
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
                    <div class="selected-opd-chips flex flex-wrap gap-1.5 min-h-[32px] items-center p-2 rounded-xl bg-white border border-dashed border-gray-200" data-opd-chip-mode="codes">
                        <span class="empty-opd-placeholder text-xs text-gray-500 italic">Belum ada OPD yang dipilih (centang minimal 1 OPD di atas)</span>
                    </div>
                </div>
            </div>

            {{-- Uraian Singkat / Ringkasan --}}
            <div class="md:col-span-2">
                <label for="lhp-ringkasan-__LHP_INDEX__" class="mb-1 block text-xs font-bold text-gray-700">Uraian Singkat / Ringkasan Hasil Pemeriksaan</label>
                <textarea id="lhp-ringkasan-__LHP_INDEX__" name="lhps[__LHP_INDEX__][ringkasan]" rows="2"
                          class="w-full border bg-white px-3.5 py-2 text-sm text-gray-800 idds-form-control"
                          placeholder="Tuliskan uraian ringkas pokok hasil temuan atau evaluasi pemeriksaan..."></textarea>
            </div>

            {{-- Pilihan Unsur Temuan: Administrasi & Kerugian Daerah --}}
            <div class="md:col-span-2 rounded-2xl border border-amber-200 bg-amber-50/50 p-4 space-y-4" role="group" aria-labelledby="lhp-unsur-temuan-__LHP_INDEX__">
                <div>
                    <p id="lhp-unsur-temuan-__LHP_INDEX__" class="block text-xs font-bold text-amber-900  tracking-wider mb-1">
                        Unsur Temuan &amp; Rekomendasi <span class="text-state-danger">*</span>
                    </p>
                    <p class="text-xs text-amber-800/80 mb-3">Pilih salah satu atau kedua unsur temuan di bawah ini. Rekomendasi melekat langsung pada masing-masing temuan.</p>

                    <div class="flex flex-wrap gap-4 items-center">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none bg-white px-4 py-2 rounded-xl border border-amber-300 shadow-2xs hover:bg-amber-50 transition">
                            <input type="checkbox" name="lhps[__LHP_INDEX__][ada_administrasi]" value="1"
                                   onchange="toggleUnsurTemuan(this, __LHP_INDEX__, 'administrasi')"
                                   class="chk-unsur-administrasi h-4 w-4 rounded border-gray-300 text-state-warning focus:ring-amber-500 cursor-pointer">
                            <span class="text-xs font-bold text-gray-800">Unsur Administrasi</span>
                        </label>

                        <label class="inline-flex items-center gap-2 cursor-pointer select-none bg-white px-4 py-2 rounded-xl border border-amber-300 shadow-2xs hover:bg-amber-50 transition">
                            <input type="checkbox" name="lhps[__LHP_INDEX__][ada_kerugian]" value="1"
                                   onchange="toggleUnsurTemuan(this, __LHP_INDEX__, 'kerugian')"
                                   class="chk-unsur-kerugian h-4 w-4 rounded border-gray-300 text-state-warning focus:ring-amber-500 cursor-pointer">
                            <span class="text-xs font-bold text-gray-800">Unsur Kerugian Daerah</span>
                        </label>
                    </div>
                </div>

                {{-- CONTAINER 1: TEMUAN ADMINISTRASI (Multi-Temuan Dinamis) --}}
                <div class="container-temuan-administrasi hidden space-y-3 pt-2 border-t border-amber-200/80">
                    <div class="flex items-center justify-between">
                        <h5 class="text-xs font-bold text-primary-900  tracking-wider flex items-center gap-1.5">
                            <span>Daftar Temuan Administrasi</span>
                        </h5>
                        <button type="button" onclick="addTemuanAdministrasiItem(this, __LHP_INDEX__)"
                                class="inline-flex items-center gap-1 text-xs font-bold text-primary-700 bg-primary-100 hover:bg-primary-200 px-3 py-1.5 rounded-lg transition cursor-pointer">
                            + Tambah Temuan Administrasi
                        </button>
                    </div>

                    <div class="list-temuan-administrasi space-y-3">
                        <div class="item-temuan-adm bg-white p-3.5 rounded-xl border border-primary-200 shadow-2xs space-y-2.5" data-adm-idx="0">
                            <div class="flex items-center justify-between">
                                <span class="adm-label text-xs font-bold text-primary-700 bg-primary-50 px-2 py-0.5 rounded-md">Temuan Administrasi #1</span>
                                <button type="button" onclick="removeTemuanAdministrasiItem(this)" class="btn-remove-adm hidden text-xs font-bold text-state-danger hover:text-red-700 hover:underline cursor-pointer">
                                    Hapus Temuan Ini
                                </button>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Uraian Temuan Administrasi <span class="text-state-danger">*</span></label>
                                <textarea name="lhps[__LHP_INDEX__][temuans_administrasi][0][uraian]" rows="2"
                                          class="input-adm-uraian w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                          placeholder="Contoh: Dokumen pertanggungjawaban SPJ belum dilengkapi bukti pengeluaran yang sah..."></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Penyebab (Sebab) <span class="text-gray-500 font-normal">(Opsional)</span></label>
                                <textarea name="lhps[__LHP_INDEX__][temuans_administrasi][0][penyebab]" rows="2"
                                          class="input-adm-penyebab w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                          placeholder="Contoh: Kurangnya ketelitian bendahara pengeluaran serta lemahnya pengawasan atasan langsung..."></textarea>
                            </div>
                            <div class="space-y-1.5 pt-1">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-gray-700">Poin-Poin Rekomendasi Bertahap <span class="text-state-danger">*</span></label>
                                    <button type="button" onclick="addRekomendasiPointLhp(this)" class="inline-flex items-center gap-1 text-xs font-bold text-primary-700 hover:text-primary-900 bg-primary-50 hover:bg-primary-100 px-2.5 py-1 rounded-lg border border-primary-200 transition cursor-pointer">
                                        + Tambah Poin Rekomendasi
                                    </button>
                                </div>
                                <div class="list-rekomendasi-points space-y-1.5">
                                    <div class="item-rek-point flex items-start gap-1.5 bg-primary-50/30 p-2 rounded-lg border border-primary-100/80">
                                        <span class="rek-point-num text-xs font-bold bg-primary-600 text-white w-5 h-5 flex items-center justify-center rounded shrink-0 mt-0.5 shadow-2xs">1</span>
                                        <div class="flex-1">
                                            <textarea name="lhps[__LHP_INDEX__][temuans_administrasi][0][rekomendasi_items][]" rows="2"
                                                      class="input-adm-rekomendasi-item w-full border bg-white px-2.5 py-1.5 text-xs text-gray-800 idds-form-control"
                                                      placeholder="Contoh: Melengkapi bukti pertanggungjawaban SPJ sesuai ketentuan perundang-undangan..."></textarea>
                                        </div>
                                        <button type="button" onclick="removeRekomendasiPointLhp(this)" class="btn-remove-rek-point hidden text-gray-500 hover:text-state-danger p-1 cursor-pointer mt-0.5" title="Hapus poin rekomendasi">
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
                        <button type="button" onclick="addTemuanKerugianItem(this, '__LHP_INDEX__')"
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
                                <textarea name="lhps[__LHP_INDEX__][temuans_kerugian][0][uraian]" rows="2"
                                          class="input-kg-uraian w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                          placeholder="Contoh: Terdapat kelebihan pembayaran belanja perjalanan dinas..."></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Kategori / Sumber Kerugian <span class="text-state-danger">*</span></label>
                                    <select name="lhps[__LHP_INDEX__][temuans_kerugian][0][kategori_kerugian]" class="input-kg-kategori w-full border bg-white px-3 py-2 text-xs font-semibold text-gray-800 idds-form-control">
                                        <option value="Kerugian Daerah" selected>Kerugian Daerah (Kasda / APBD)</option>
                                        <option value="Kerugian Negara">Kerugian Negara (Kas Negara / APBN)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Klasifikasi Penyelesaian <span class="text-state-danger">*</span></label>
                                    <div class="flex items-center gap-4 mt-2">
                                        <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                            <input type="radio" name="lhps[__LHP_INDEX__][temuans_kerugian][0][jenis_kerugian]" value="Internal" class="input-kg-jenis h-3 w-3 text-state-danger focus:ring-rose-500">
                                            <span>Internal (ASN/OPD)</span>
                                        </label>
                                        <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                            <input type="radio" name="lhps[__LHP_INDEX__][temuans_kerugian][0][jenis_kerugian]" value="Eksternal" class="input-kg-jenis h-3 w-3 text-state-danger focus:ring-rose-500">
                                            <span>Eksternal (Rekanan)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Nominal Nilai Kerugian (Rp) <span class="text-state-danger">*</span></label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-xs font-bold text-gray-500">Rp</span>
                                    <input type="text" name="lhps[__LHP_INDEX__][temuans_kerugian][0][nilai_kerugian]" oninput="formatRupiahLive(this)"
                                           class="input-kg-nilai w-full border bg-white pl-9 pr-3 py-2 text-sm font-bold text-emerald-700 idds-form-control"
                                           placeholder="0">
                                </div>
                                <p class="mt-1 text-xs text-gray-500 italic">Ketik nominal tanpa titik, sistem memformat otomatis.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Penyebab (Sebab) <span class="text-gray-500 font-normal">(Opsional)</span></label>
                                <textarea name="lhps[__LHP_INDEX__][temuans_kerugian][0][penyebab]" rows="2"
                                          class="input-kg-penyebab w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                          placeholder="Contoh: Kurangnya ketelitian dalam verifikasi bukti pertanggungjawaban..."></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Rekomendasi Penyetoran <span class="text-state-danger">*</span></label>
                                <textarea name="lhps[__LHP_INDEX__][temuans_kerugian][0][rekomendasi]" rows="2"
                                          class="input-kg-rekomendasi w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                                          placeholder="Contoh: Menyetorkan kelebihan pembayaran ke Kas Daerah / Kas Negara sebesar nominal kerugian..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Upload File LHP --}}
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-bold text-gray-700">Unggah File Dokumen LHP (PDF / DOC / DOCX)</label>
                <input type="file" name="lhps[__LHP_INDEX__][dokumen_lhp]" accept=".pdf,.doc,.docx"
                       class="block w-full text-xs text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary-100 file:text-primary-700 hover:file:bg-primary-200 cursor-pointer">
            </div>
        </div>
    </div>
</template>

{{-- TEMPLATE DUKUNGAN MULTI-TINDAK LANJUT DINAMIS (HIDDEN) --}}
<template id="tl-card-template">
    <div class="tl-card rounded-2xl border border-emerald-200 bg-white p-5 md:p-6 shadow-sm relative transition" data-tl-index="__TL_INDEX__">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
            <div class="flex items-center gap-2">
                <x-sequence-badge type="tindak-lanjut">
                    Tindak Lanjut #__TL_NUM__
                </x-sequence-badge>
                <span class="text-xs text-gray-500 font-bold">Progress / Realisasi Pemenuhan</span>
            </div>
            <button type="button" onclick="removeTindakLanjutCard(this)" class="btn-remove-tl text-xs font-bold text-state-danger hover:text-red-700 hover:underline cursor-pointer">
                Hapus Tindak Lanjut Ini
            </button>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            {{-- Pilihan LHP Terkait --}}
            <div class="md:col-span-2">
                <label for="lhp-tl-__TL_INDEX__-lhp" class="mb-1 block text-xs font-bold text-gray-700">Terkait Dokumen LHP <span class="text-state-danger">*</span></label>
                <select id="lhp-tl-__TL_INDEX__-lhp" name="tindak_lanjuts[__TL_INDEX__][lhp_index]" class="select-target-lhp w-full border bg-white px-3.5 py-2.5 text-xs font-bold text-primary-900 idds-form-control">
                </select>
            </div>

            {{-- Tanggal Tindak Lanjut --}}
            <div>
                <label for="lhp-tl-__TL_INDEX__-tanggal" class="mb-1 block text-xs font-bold text-gray-700">Tanggal Tindak Lanjut <span class="text-state-danger">*</span></label>
                <input id="lhp-tl-__TL_INDEX__-tanggal" type="date" name="tindak_lanjuts[__TL_INDEX__][tanggal_tindak_lanjut]" value="{{ date('Y-m-d') }}"
                       class="w-full border bg-white px-3.5 py-2.5 text-sm text-gray-800 idds-form-control">
            </div>

            {{-- Jenis Temuan yang Ditindaklanjuti --}}
            <div>
                <label for="lhp-tl-__TL_INDEX__-jenis-temuan" class="mb-1 block text-xs font-bold text-gray-700">Jenis Temuan <span class="text-state-danger">*</span></label>
                <select id="lhp-tl-__TL_INDEX__-jenis-temuan" name="tindak_lanjuts[__TL_INDEX__][temuan_type]" onchange="toggleTlFields(this)"
                        class="select-tl-temuan-type w-full border bg-white px-3.5 py-2.5 text-xs font-bold text-gray-800 idds-form-control">
                    <option value="kerugian">Kerugian Daerah (Pengembalian Kasda)</option>
                    <option value="kerugian_negara">Kerugian Negara (Penyetoran Kas Negara)</option>
                    <option value="administrasi">Administrasi (Perbaikan Dokumen / SOP)</option>
                </select>
            </div>

            {{-- Uraian Tindak Lanjut --}}
            <div class="md:col-span-2">
                <label for="lhp-tl-__TL_INDEX__-uraian" class="mb-1 block text-xs font-bold text-gray-700">Uraian Tindak Lanjut <span class="text-state-danger">*</span></label>
                <textarea id="lhp-tl-__TL_INDEX__-uraian" name="tindak_lanjuts[__TL_INDEX__][uraian_tindak_lanjut]" rows="2"
                          class="w-full border bg-white px-3.5 py-2 text-sm text-gray-800 idds-form-control"
                          placeholder="Tuliskan uraian rincian tindak lanjut yang telah dilaksanakan..."></textarea>
            </div>

            {{-- Dokumen Bukti TL Umum --}}
            <div class="md:col-span-2">
                <label for="lhp-tl-__TL_INDEX__-dokumen" class="mb-1 block text-xs font-bold text-gray-700">Unggah Dokumen Bukti Tindak Lanjut (PDF/DOCX/JPG)</label>
                <input id="lhp-tl-__TL_INDEX__-dokumen" type="file" name="tindak_lanjuts[__TL_INDEX__][dokumen_bukti]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                       class="block w-full text-xs text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-700 hover:file:bg-emerald-200 cursor-pointer">
            </div>

            {{-- Bagian Khusus Setoran Kerugian (Dinamis Ditampilkan jika jenis kerugian) --}}
            <div class="box-setoran-kerugian md:col-span-2 rounded-2xl border border-emerald-300 bg-emerald-50/60 p-4 space-y-4">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-emerald-100 text-xs font-bold text-emerald-700">Rp</span>
                    <h4 class="title-setoran-kerugian text-xs font-bold text-emerald-950  tracking-wider">Rincian Penyetoran ke Kas Daerah</h4>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    {{-- Nomor Bukti Setor / STS --}}
                    <div>
                        <label for="lhp-tl-__TL_INDEX__-nomor-setor" class="mb-1 block text-xs font-bold text-gray-700">Nomor STS / NTPN</label>
                        <input id="lhp-tl-__TL_INDEX__-nomor-setor" type="text" name="tindak_lanjuts[__TL_INDEX__][nomor_bukti_setor]"
                               class="w-full border bg-white px-3.5 py-2 text-xs text-gray-800 idds-form-control"
                               placeholder="Contoh: STS-2026/03/0123">
                    </div>

                    {{-- Tanggal Setor --}}
                    <div>
                        <label for="lhp-tl-__TL_INDEX__-tanggal-setor" class="mb-1 block text-xs font-bold text-gray-700">Tanggal Setor</label>
                        <input id="lhp-tl-__TL_INDEX__-tanggal-setor" type="date" name="tindak_lanjuts[__TL_INDEX__][tanggal_setor]"
                               class="w-full border bg-white px-3.5 py-2 text-xs text-gray-800 idds-form-control">
                    </div>

                    {{-- Jumlah Setor --}}
                    <div>
                        <label for="lhp-tl-__TL_INDEX__-jumlah-setor" class="mb-1 block text-xs font-bold text-gray-700">Jumlah Setoran (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-xs font-bold text-gray-500">Rp</span>
                            <input id="lhp-tl-__TL_INDEX__-jumlah-setor" type="text" name="tindak_lanjuts[__TL_INDEX__][jumlah_setor]" oninput="formatRupiahLive(this)"
                                   class="input-tl-setor w-full border bg-white pl-9 pr-3 py-2 text-xs font-bold text-emerald-700 idds-form-control"
                                   placeholder="0">
                        </div>
                    </div>
                </div>

                {{-- Unggah Slip Setor STS --}}
                <div>
                    <label for="lhp-tl-__TL_INDEX__-dokumen-setor" class="mb-1 block text-xs font-bold text-gray-700">Unggah Slip / Bukti Setor STS (PDF/JPG/PNG)</label>
                    <input id="lhp-tl-__TL_INDEX__-dokumen-setor" type="file" name="tindak_lanjuts[__TL_INDEX__][dokumen_setor]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                           class="block w-full text-xs text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-200/70 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer">
                </div>
            </div>
        </div>
    </div>
</template>

{{-- SCRIPT INTERAKTIF FORM TERPADU MULTI-LHP --}}
<script>
    // =========================================================================
    // 1. MANAJEMEN KARTU LHP DINAMIS (TAMBAH / HAPUS MULTI-LHP)
    // =========================================================================
    function addLhpCard(shouldFocus = true) {
        const container = document.getElementById('lhp-container');
        const template = document.getElementById('lhp-card-template');
        if (!container || !template) return;

        const currentCards = container.querySelectorAll('.lhp-card');
        const newIndex = currentCards.length;
        const newNum = newIndex + 1;

        let html = template.innerHTML
            .replace(/__LHP_INDEX__/g, newIndex)
            .replace(/__LHP_NUM__/g, newNum);

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html.trim();
        const newCard = tempDiv.firstElementChild;

        container.appendChild(newCard);
        updateLhpIndices();
        syncLhpOptionsToTindakLanjut();
        if (shouldFocus) {
            window.focusFirstDynamicField(newCard);
        }
        window.announceDynamicChange(container, `LHP ${newNum} ditambahkan.`);
    }

    function removeLhpCard(btn) {
        const container = document.getElementById('lhp-container');
        const card = btn.closest('.lhp-card');
        if (!container || !card) return;

        const allCards = container.querySelectorAll('.lhp-card');
        if (allCards.length <= 1) {
            window.showAppNotice('Minimal harus ada 1 dokumen LHP.', { title: 'LHP tidak dapat dihapus', variant: 'warning' });
            return;
        }

        window.showAppConfirm('Apakah Anda yakin ingin menghapus LHP ini?', {
            title: 'Hapus LHP?',
            variant: 'danger',
            acceptLabel: 'Ya, Hapus',
        }).then((confirmed) => {
            if (!confirmed) return;
            const cardIndex = Array.from(allCards).indexOf(card);
            card.remove();
            updateLhpIndices();
            syncLhpOptionsToTindakLanjut();
            window.announceDynamicChange(container, `LHP ${cardIndex + 1} dihapus.`);

            const remainingCards = Array.from(container.querySelectorAll('.lhp-card'));
            window.focusFirstDynamicField(remainingCards[Math.min(cardIndex, remainingCards.length - 1)]);
        });
    }

    function updateLhpIndices() {
        const container = document.getElementById('lhp-container');
        window.updateDynamicCardList(container, {
            cardSelector: '.lhp-card',
            indexAttribute: 'data-lhp-index',
            badgeSelector: '.lhp-badge',
            badgePrefix: 'LHP',
            removeButtonSelector: '.btn-remove-lhp',
            fieldNamePrefix: 'lhps',
        });

        container?.querySelectorAll('.lhp-card').forEach((card, index) => {
            card.querySelectorAll('.opd-checkbox').forEach((checkbox) => {
                checkbox.name = `lhp_opd_ids[${index}][]`;
            });

            card.querySelectorAll('[id^="lhp-"]').forEach((field) => {
                const prefix = field.id.match(/^(lhp-[a-z-]+-)\d+$/)?.[1];
                if (prefix) {
                    field.id = `${prefix}${index}`;
                }
            });

            card.querySelectorAll('label[for^="lhp-"]').forEach((label) => {
                const prefix = label.htmlFor.match(/^(lhp-[a-z-]+-)\d+$/)?.[1];
                if (prefix) {
                    label.htmlFor = `${prefix}${index}`;
                }
            });

            window.updateLhpFindingItems(card, 'administrasi');
            window.updateLhpFindingItems(card, 'kerugian');
        });
    }

    // =========================================================================
    // 3. TOGGLE UNSUR TEMUAN & TEMUAN ADMINISTRASI DINAMIS
    // =========================================================================
    function addTemuanAdministrasiItem(btn, lhpIdx) {
        const card = btn.closest('.lhp-card');
        if (!card) return;
        const list = card.querySelector('.list-temuan-administrasi');
        if (!list) return;

        const currentItems = list.querySelectorAll('.item-temuan-adm');
        const newAdmIdx = currentItems.length;
        const newAdmNum = newAdmIdx + 1;
        const actualLhpIdx = card.getAttribute('data-lhp-index') || lhpIdx;

        const itemDiv = document.createElement('div');
        itemDiv.className = 'item-temuan-adm bg-white p-3.5 rounded-xl border border-primary-200 shadow-2xs space-y-2.5';
        itemDiv.setAttribute('data-adm-idx', newAdmIdx);
        itemDiv.innerHTML = `
            <div class="flex items-center justify-between">
                <span class="adm-label text-xs font-bold text-primary-700 bg-primary-50 px-2 py-0.5 rounded-md">Temuan Administrasi #${newAdmNum}</span>
                <button type="button" onclick="removeTemuanAdministrasiItem(this)" class="btn-remove-adm text-xs font-bold text-state-danger hover:text-red-700 hover:underline cursor-pointer">
                    Hapus Temuan Ini
                </button>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Uraian Temuan Administrasi <span class="text-state-danger">*</span></label>
                <textarea name="lhps[${actualLhpIdx}][temuans_administrasi][${newAdmIdx}][uraian]" rows="2"
                          class="input-adm-uraian w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                          placeholder="Contoh: Dokumen pertanggungjawaban SPJ belum dilengkapi bukti pengeluaran yang sah..."></textarea>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Penyebab (Sebab) <span class="text-gray-500 font-normal">(Opsional)</span></label>
                <textarea name="lhps[${actualLhpIdx}][temuans_administrasi][${newAdmIdx}][penyebab]" rows="2"
                          class="input-adm-penyebab w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                          placeholder="Contoh: Kurangnya ketelitian bendahara pengeluaran serta lemahnya pengawasan atasan langsung..."></textarea>
            </div>
            <div class="space-y-1.5 pt-1">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-gray-700">Poin-Poin Rekomendasi Bertahap <span class="text-state-danger">*</span></label>
                    <button type="button" onclick="addRekomendasiPointLhp(this)" class="inline-flex items-center gap-1 text-xs font-bold text-primary-700 hover:text-primary-900 bg-primary-50 hover:bg-primary-100 px-2.5 py-1 rounded-lg border border-primary-200 transition cursor-pointer">
                        + Tambah Poin Rekomendasi
                    </button>
                </div>
                <div class="list-rekomendasi-points space-y-1.5">
                    <div class="item-rek-point flex items-start gap-1.5 bg-primary-50/30 p-2 rounded-lg border border-primary-100/80">
                        <span class="rek-point-num text-xs font-bold bg-primary-600 text-white w-5 h-5 flex items-center justify-center rounded shrink-0 mt-0.5 shadow-2xs">1</span>
                        <div class="flex-1">
                            <textarea name="lhps[${actualLhpIdx}][temuans_administrasi][${newAdmIdx}][rekomendasi_items][]" rows="2"
                                      class="input-adm-rekomendasi-item w-full border bg-white px-2.5 py-1.5 text-xs text-gray-800 idds-form-control"
                                      placeholder="Contoh: Melengkapi bukti pertanggungjawaban SPJ sesuai ketentuan perundang-undangan..."></textarea>
                        </div>
                        <button type="button" onclick="removeRekomendasiPointLhp(this)" class="btn-remove-rek-point hidden text-gray-500 hover:text-state-danger p-1 cursor-pointer mt-0.5" title="Hapus poin rekomendasi">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        `;

        list.appendChild(itemDiv);
        updateTemuanAdministrasiLabels(card);
    }

    function removeTemuanAdministrasiItem(btn) {
        removeTemuanItem(btn, 'administrasi');
    }

    function removeTemuanItem(button, type) {
        const configurations = {
            administrasi: {
                item: '.item-temuan-adm',
                list: '.list-temuan-administrasi',
                minimumMessage: 'Minimal 1 temuan administrasi jika unsur administrasi dicentang.',
                updateLabels: updateTemuanAdministrasiLabels,
            },
            kerugian: {
                item: '.item-temuan-kg',
                list: '.list-temuan-kerugian',
                minimumMessage: 'Minimal 1 temuan kerugian jika unsur kerugian dicentang.',
                updateLabels: updateTemuanKerugianLabels,
            },
        };
        const config = configurations[type];
        if (!config) return;

        const card = button.closest('.lhp-card');
        const item = button.closest(config.item);
        const list = card?.querySelector(config.list);
        if (!card || !item || !list) return;

        if (list.querySelectorAll(config.item).length <= 1) {
            window.showAppNotice(config.minimumMessage, { title: 'Butir minimal harus tersedia', variant: 'warning' });
            return;
        }

        item.remove();
        config.updateLabels(card);
    }

    function updateTemuanLabels(card, type) {
        window.updateLhpFindingItems(card, type);
    }

    function updateTemuanAdministrasiLabels(card) {
        updateTemuanLabels(card, 'administrasi');
    }

    function addTemuanKerugianItem(btn, lhpIdx) {
        const card = btn.closest('.lhp-card');
        if (!card) return;

        const list = card.querySelector('.list-temuan-kerugian');
        if (!list) return;

        const actualLhpIdx = card.getAttribute('data-lhp-index') || lhpIdx;
        const currentItems = list.querySelectorAll('.item-temuan-kg');
        const nextIdx = currentItems.length;
        const nextNum = nextIdx + 1;

        const itemDiv = document.createElement('div');
        itemDiv.className = 'item-temuan-kg bg-white p-3.5 rounded-xl border border-rose-200 shadow-2xs space-y-2.5 animate-fade-in';
        itemDiv.setAttribute('data-kg-idx', nextIdx);
        itemDiv.innerHTML = `
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
                            <input type="radio" name="lhps[${actualLhpIdx}][temuans_kerugian][${nextIdx}][jenis_kerugian]" value="Internal" class="input-kg-jenis h-3 w-3 text-state-danger focus:ring-rose-500">
                            <span>Internal (ASN/OPD)</span>
                        </label>
                        <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                            <input type="radio" name="lhps[${actualLhpIdx}][temuans_kerugian][${nextIdx}][jenis_kerugian]" value="Eksternal" class="input-kg-jenis h-3 w-3 text-state-danger focus:ring-rose-500">
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
        `;

        list.appendChild(itemDiv);
        updateTemuanKerugianLabels(card);
    }

    function removeTemuanKerugianItem(btn) {
        removeTemuanItem(btn, 'kerugian');
    }

    function updateTemuanKerugianLabels(card) {
        updateTemuanLabels(card, 'kerugian');
    }

    // Tambah & Hapus Poin Rekomendasi di LHP Form
    window.addRekomendasiPointLhp = function(btn) {
        window.addRecommendationPoint(btn, '.item-temuan-adm', 'removeRekomendasiPointLhp', window.updateRekomendasiPointBadgesLhp);
    };

    window.removeRekomendasiPointLhp = function(btn) {
        window.removeRecommendationPoint(btn, window.updateRekomendasiPointBadgesLhp);
    };

    window.updateRekomendasiPointBadgesLhp = function(list) {
        window.updateRecommendationPointBadges(list);
    };

    // =========================================================================
    // 4. MANAJEMEN KARTU TINDAK LANJUT DINAMIS
    // =========================================================================
    function addTindakLanjutCard() {
        const container = document.getElementById('tl-container');
        const template = document.getElementById('tl-card-template');
        if (!container || !template) return;

        const currentCards = container.querySelectorAll('.tl-card');
        const newIndex = currentCards.length;
        const newNum = newIndex + 1;

        let html = template.innerHTML
            .replace(/__TL_INDEX__/g, newIndex)
            .replace(/__TL_NUM__/g, newNum);

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html.trim();
        const newCard = tempDiv.firstElementChild;

        container.appendChild(newCard);
        updateTindakLanjutIndices();
        syncLhpOptionsToTindakLanjut();
        window.focusFirstDynamicField(newCard);
        window.announceDynamicChange(container, `Tindak lanjut ${newNum} ditambahkan.`);
    }

    function removeTindakLanjutCard(btn) {
        const container = document.getElementById('tl-container');
        const card = btn.closest('.tl-card');
        if (!container || !card) return;

        const allCards = container.querySelectorAll('.tl-card');
        if (allCards.length <= 1) {
            window.showAppNotice('Minimal harus ada 1 form Tindak Lanjut.', { title: 'Tindak lanjut tidak dapat dihapus', variant: 'warning' });
            return;
        }

        window.showAppConfirm('Apakah Anda yakin ingin menghapus kartu Tindak Lanjut ini?', {
            title: 'Hapus tindak lanjut?',
            variant: 'danger',
            acceptLabel: 'Ya, Hapus',
        }).then((confirmed) => {
            if (!confirmed) return;
            const cardIndex = Array.from(allCards).indexOf(card);
            card.remove();
            updateTindakLanjutIndices();
            window.announceDynamicChange(container, `Tindak lanjut ${cardIndex + 1} dihapus.`);

            const remainingCards = Array.from(container.querySelectorAll('.tl-card'));
            window.focusFirstDynamicField(remainingCards[Math.min(cardIndex, remainingCards.length - 1)]);
        });
    }

    function updateTindakLanjutIndices() {
        const container = document.getElementById('tl-container');
        window.updateDynamicCardList(container, {
            cardSelector: '.tl-card',
            indexAttribute: 'data-tl-index',
            badgeSelector: '.tl-badge',
            badgePrefix: 'Tindak Lanjut',
            removeButtonSelector: '.btn-remove-tl',
            fieldNamePrefix: 'tindak_lanjuts',
        });

        container?.querySelectorAll('.tl-card').forEach((card, index) => {
            card.querySelectorAll('[id^="lhp-tl-"]').forEach((field) => {
                const suffix = field.id.match(/^lhp-tl-\d+-(.+)$/)?.[1];
                if (suffix) {
                    field.id = `lhp-tl-${index}-${suffix}`;
                }
            });

            card.querySelectorAll('label[for^="lhp-tl-"]').forEach((label) => {
                const suffix = label.htmlFor.match(/^lhp-tl-\d+-(.+)$/)?.[1];
                if (suffix) {
                    label.htmlFor = `lhp-tl-${index}-${suffix}`;
                }
            });
        });
    }

    function toggleTlFields(select) {
        const card = select.closest('.tl-card');
        if (!card) return;
        const setoranBox = card.querySelector('.box-setoran-kerugian');
        const headerText = card.querySelector('.title-setoran-kerugian');
        if (setoranBox) {
            if (select.value === 'kerugian' || select.value === 'kerugian_daerah') {
                setoranBox.classList.remove('hidden');
                if (headerText) headerText.textContent = 'Rincian Penyetoran ke Kas Daerah';
            } else if (select.value === 'kerugian_negara') {
                setoranBox.classList.remove('hidden');
                if (headerText) headerText.textContent = 'Rincian Penyetoran ke Kas Negara';
            } else {
                setoranBox.classList.add('hidden');
            }
        }
    }

    // =========================================================================
    // 6. LIVE RUPIAH FORMATTER
    // =========================================================================
    // Inisialisasi awal saat halaman selesai dimuat
    @php
        $oldLhpNumbers = collect(old('lhp_nomor', []));

        if ($oldLhpNumbers->isEmpty()) {
            $oldLhpNumbers = collect(old('lhps', []))
                ->map(fn ($lhp) => is_array($lhp) ? ($lhp['nomor_lhp'] ?? '') : '');
        }

        $oldLhpNumbers = $oldLhpNumbers
            ->map(fn ($value) => is_array($value) ? ($value['nomor_lhp'] ?? '') : $value)
            ->values();

        $oldLhpOpdIds = collect(old('lhp_opd_ids', []));

        if ($oldLhpOpdIds->isEmpty()) {
            $oldLhpOpdIds = collect(old('lhps', []))
                ->map(fn ($lhp) => is_array($lhp) ? ($lhp['opd_ids'] ?? []) : []);
        }

        $oldLhpOpdIds = $oldLhpOpdIds
            ->map(fn ($opdIds) => is_array($opdIds) ? array_values($opdIds) : [])
            ->values();
    @endphp
    const oldLhpNumbers = @json($oldLhpNumbers);
    const oldLhpOpdIds = @json($oldLhpOpdIds);

    document.addEventListener('DOMContentLoaded', () => {
        const lhpContainer = document.getElementById('lhp-container');
        const lhpForm = document.getElementById('unified-lhp-form');

        lhpForm?.addEventListener('submit', () => updateLhpIndices(), true);

        const oldLhpCardCount = Math.max(oldLhpNumbers.length, oldLhpOpdIds.length);

        if (oldLhpCardCount > 0 && lhpContainer) {
            while (lhpContainer.querySelectorAll('.lhp-card').length < oldLhpCardCount) {
                addLhpCard(false);
            }

            lhpContainer.querySelectorAll('.lhp-card').forEach((card, index) => {
                const numberInput = card.querySelector('.input-nomor-lhp');
                if (numberInput && oldLhpNumbers[index]) {
                    numberInput.value = oldLhpNumbers[index];
                }

                const selectedOpdIds = Array.isArray(oldLhpOpdIds[index])
                    ? oldLhpOpdIds[index].map(String)
                    : [];
                card.querySelectorAll('.opd-checkbox').forEach((checkbox) => {
                    checkbox.checked = selectedOpdIds.includes(String(checkbox.value));
                });

                const selectedCheckbox = card.querySelector('.opd-checkbox:checked');
                if (selectedCheckbox) {
                    updateOpdSelection(selectedCheckbox);
                }
            });
        }

        updateLhpIndices();
        updateTindakLanjutIndices();
        syncLhpOptionsToTindakLanjut();
    });
</script>
@endsection
