@extends('layouts.app')

@php
    $isLhpOnlyAdmin = auth()->user()->isAdminSumber() && ! auth()->user()->canManageSpt();
@endphp

@section('title', 'Dashboard Monitoring & Analitik')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Selamat datang, ' . $user->official_name . ' — GATRA-GO, Gerbang Akuntabilitas dan Tindak Lanjut Rekomendasi Audit Inspektorat Provinsi Gorontalo')

@section('content')
<div class="space-y-6">
    {{-- Header Banner with Filter Toolbar (Sesuai Desain User) --}}
    <div class="rounded-lg border border-gray-200 bg-white p-5 primary-gradient-wash">
        <div class="flex min-w-0 flex-col gap-4">
            <div class="min-w-0">
                <h2 class="text-lg font-semibold tracking-tight text-gray-900">Ringkasan Pengawasan</h2>
                <p class="text-xs text-gray-500 mt-0.5">Monitoring rekapitulasi, verifikasi, dan penyelesaian temuan pengawasan OPD se-Provinsi Gorontalo</p>
            </div>

            {{-- Filter Form --}}
            <form method="GET" action="{{ route('dashboard') }}" class="idds-filter-bar min-w-0">
                {{-- OPD Filter (untuk Non-OPD) --}}
                @if(!auth()->user()->isOpd())
                    <div class="relative min-w-0">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <select name="opd_id" aria-label="Filter dashboard berdasarkan OPD"
                                class="w-full border bg-white py-2.5 pl-9 pr-8 text-sm text-gray-700 transition-colors hover:bg-gray-50 idds-form-control">
                            <option value="">Semua OPD Gorontalo</option>
                            @foreach($opds as $o)
                                <option value="{{ $o->id }}" {{ $opdId == $o->id ? 'selected' : '' }}>
                                    {{ $o->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Sumber Pemeriksaan Filter (untuk Non-Admin Sumber) --}}
                @if(auth()->user()->isSuperAdmin() || auth()->user()->isPemantau() || auth()->user()->isOpd())
                    <div class="relative min-w-0">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                        <select name="sumber" aria-label="Filter dashboard berdasarkan sumber pemeriksaan"
                                class="w-full border bg-white py-2.5 pl-9 pr-8 text-sm text-gray-700 transition-colors hover:bg-gray-50 idds-form-control">
                            <option value="">Semua Sumber (BPK, BPKP, ITJEN, Insp)</option>
                            @foreach($sumberList as $s)
                                <option value="{{ $s }}" {{ $sumber === $s ? 'selected' : '' }}>
                                    {{ $s }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Tahun Filter --}}
                <div class="relative min-w-0">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <select name="tahun" aria-label="Filter dashboard berdasarkan tahun"
                            class="w-full border bg-white py-2.5 pl-9 pr-8 text-sm text-gray-700 transition-colors hover:bg-gray-50 idds-form-control">
                        <option value="">Semua Tahun</option>
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>
                                Tahun {{ $y }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="idds-filter-actions min-w-0">
                    <x-action-button variant="filter" type="submit" class="w-full sm:w-auto">
                        Terapkan
                    </x-action-button>

                    {{-- Reset Button --}}
                    <a href="{{ route('dashboard') }}" title="Reset Filter / Refresh" aria-label="Hapus semua filter dashboard"
                       class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-500 transition-colors hover:bg-gray-50 hover:text-gray-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Onboarding / Quick Role Guide Banner --}}
    <div id="onboarding-guide-banner" class="rounded-lg border border-gray-200 bg-white p-5 primary-gradient-wash">
        <div class="flex items-start justify-between gap-4">
            <div class="flex min-w-0 flex-1 items-start gap-3.5">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 primary-gradient-mark text-primary-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <h3 class="text-sm font-bold tracking-tight text-gray-900">
                            @if(auth()->user()->isSuperAdmin())
                                Panduan Alur Kerja Super Admin
                            @elseif(auth()->user()->isAdminInspektorat())
                                Panduan Alur Kerja Admin Inspektorat
                            @elseif(auth()->user()->isAdminSumber())
                                Panduan Alur Kerja Admin Sumber ({{ auth()->user()->getSumberPemeriksaan() }})
                            @elseif(auth()->user()->isOpd())
                                Panduan Alur Kerja Operator OPD ({{ auth()->user()->opd?->nama ?? 'OPD' }})
                            @else
                                Panduan Alur Kerja Pengguna
                            @endif
                        </h3>
                            <span class="text-xs font-medium text-gray-500">Panduan singkat</span>
                    </div>

                    <div class="mt-2.5 grid grid-cols-1 gap-2.5 sm:grid-cols-3 text-xs text-gray-700">
                        @if(auth()->user()->isSuperAdmin())
                            <div class="flex items-start gap-2 border-l-2 border-primary-200 py-1 pl-3">
                                <span class="shrink-0 font-semibold text-primary-700">1</span>
                                <div><strong class="text-gray-900">Pemeriksaan & LHP:</strong> Pantau seluruh kegiatan pengawasan se-Provinsi Gorontalo.</div>
                            </div>
                            <div class="flex items-start gap-2 border-l-2 border-primary-200 py-1 pl-3">
                                <span class="shrink-0 font-semibold text-primary-700">2</span>
                                <div><strong class="text-gray-900">Verifikasi TL & Setoran:</strong> Tinjau dan sahkan bukti tindak lanjut serta setoran uang ke Kasda / Kas Negara.</div>
                            </div>
                            <div class="flex items-start gap-2 border-l-2 border-primary-200 py-1 pl-3">
                                <span class="shrink-0 font-semibold text-primary-700">3</span>
                                <div><strong class="text-gray-900">Master Data:</strong> Kelola data instansi OPD, database Pegawai, dan akun pengguna.</div>
                            </div>
                        @elseif(auth()->user()->isAdminInspektorat())
                            <div class="flex items-start gap-2 border-l-2 border-primary-200 py-1 pl-3">
                                <span class="shrink-0 font-semibold text-primary-700">1</span>
                                <div><strong class="text-gray-900">Buat SPT & Tim:</strong> Input Surat Perintah Tugas dan susunan tim pemeriksa Inspektorat.</div>
                            </div>
                            <div class="flex items-start gap-2 border-l-2 border-primary-200 py-1 pl-3">
                                <span class="shrink-0 font-semibold text-primary-700">2</span>
                                <div><strong class="text-gray-900">Input LHP & Temuan:</strong> Masukkan laporan pemeriksaan, target OPD, serta rincian temuan.</div>
                            </div>
                            <div class="flex items-start gap-2 border-l-2 border-primary-200 py-1 pl-3">
                                <span class="shrink-0 font-semibold text-primary-700">3</span>
                                <div><strong class="text-gray-900">Verifikasi Progres:</strong> Pantau dan verifikasi penyelesaian temuan dari OPD terperiksa.</div>
                            </div>
                        @elseif(auth()->user()->isAdminSumber())
                            <div class="flex items-start gap-2 border-l-2 border-primary-200 py-1 pl-3">
                                <span class="shrink-0 font-semibold text-primary-700">1</span>
                                <div><strong class="text-gray-900">Input LHP Terpadu:</strong> Masukkan dokumen LHP dan rekomendasi temuan tanpa perlu SPT.</div>
                            </div>
                            <div class="flex items-start gap-2 border-l-2 border-primary-200 py-1 pl-3">
                                <span class="shrink-0 font-semibold text-primary-700">2</span>
                                <div><strong class="text-gray-900">Input Tindak Lanjut:</strong> Input bukti penyelesaian dan setoran (Kasda/Kas Negara) untuk temuan Anda.</div>
                            </div>
                            <div class="flex items-start gap-2 border-l-2 border-primary-200 py-1 pl-3">
                                <span class="shrink-0 font-semibold text-primary-700">3</span>
                                <div><strong class="text-gray-900">Pantau Ketuntasan:</strong> Lihat status ketuntasan laporan langsung di tabel & quick filter.</div>
                            </div>
                        @elseif(auth()->user()->isOpd())
                            <div class="flex items-start gap-2 border-l-2 border-primary-200 py-1 pl-3">
                                <span class="shrink-0 font-semibold text-primary-700">1</span>
                                <div><strong class="text-gray-900">Daftar Temuan:</strong> Periksa temuan aktif yang ditujukan kepada OPD Anda.</div>
                            </div>
                            <div class="flex items-start gap-2 border-l-2 border-primary-200 py-1 pl-3">
                                <span class="shrink-0 font-semibold text-primary-700">2</span>
                                <div><strong class="text-gray-900">Sampaikan Tindak Lanjut:</strong> Unggah bukti rekomendasi & STS/NTPN setoran.</div>
                            </div>
                            <div class="flex items-start gap-2 border-l-2 border-primary-200 py-1 pl-3">
                                <span class="shrink-0 font-semibold text-primary-700">3</span>
                                <div><strong class="text-gray-900">Status Verifikasi:</strong> Pantau apakah bukti Anda telah disetujui / perlu perbaikan.</div>
                            </div>
                        @else
                            <div class="flex items-start gap-2 border-l-2 border-primary-200 py-1 pl-3">
                                <span class="shrink-0 font-semibold text-primary-700">1</span>
                                <div><strong class="text-gray-900">Monitoring Real-time:</strong> Pantau progres pengawasan dan statistik seluruh OPD.</div>
                            </div>
                            <div class="flex items-start gap-2 border-l-2 border-primary-200 py-1 pl-3">
                                <span class="shrink-0 font-semibold text-primary-700">2</span>
                                <div><strong class="text-gray-900">Daftar Temuan:</strong> Telusuri rincian temuan per OPD dan per sumber pengawasan.</div>
                            </div>
                            <div class="flex items-start gap-2 border-l-2 border-primary-200 py-1 pl-3">
                                <span class="shrink-0 font-semibold text-primary-700">3</span>
                                <div><strong class="text-gray-900">Ekspor Laporan:</strong> Unduh laporan rekapitulasi data temuan dalam format cetak/PDF.</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Close / Dismiss Button --}}
            <button type="button" onclick="dismissGuideBanner()" class="shrink-0 p-1.5 rounded-lg text-gray-500 hover:text-gray-600 hover:bg-gray-100 transition cursor-pointer" title="Tutup panduan ini">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Reopen Guide Toggle (Hidden when banner is visible) --}}
    <div id="reopen-guide-wrapper" class="hidden flex justify-end">
        <button type="button" onclick="restoreGuideBanner()" class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary-600 hover:text-primary-800 transition cursor-pointer">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Tampilkan Panduan Alur Kerja
        </button>
    </div>

    {{-- Role Action Alerts: Verifikasi Tindak Lanjut & Kasda --}}
    @if((auth()->user()->isSuperAdmin() || auth()->user()->isAdminSumber()) && ($actionItems['pendingTindakLanjut'] ?? 0) > 0)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700 flex-shrink-0">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-amber-900">Perhatian: Verifikasi Bukti Setoran & Tindak Lanjut</h4>
                        <p class="text-xs text-amber-800 mt-0.5">
                            Terdapat <strong>{{ $actionItems['pendingTindakLanjut'] }}</strong> bukti tindak lanjut / setoran yang menunggu verifikasi.
                        </p>
                    </div>
                </div>

                <a href="{{ route('tindak-lanjut.index') }}"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-amber-700 transition flex-shrink-0">
                    Buka Tindak Lanjut &rarr;
                </a>
            </div>
        </div>
    @elseif(auth()->user()->isAdminSumber() && $actionItems['revisiTemuan'] > 0)
        <div class="rounded-2xl border border-orange-200 bg-orange-50 p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100 text-orange-700 flex-shrink-0">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-orange-900">Catatan Revisi dari Super Admin</h4>
                        <p class="text-xs text-orange-800 mt-0.5">
                            Ada <strong>{{ $actionItems['revisiTemuan'] }}</strong> data temuan yang memerlukan perbaikan.
                        </p>
                    </div>
                </div>

                <a href="{{ route('temuan.index', ['status_pengajuan' => 'PERLU REVISI']) }}" class="shrink-0 text-xs font-bold text-orange-700 hover:underline">
                    Lihat Revisi &rarr;
                </a>
            </div>
        </div>
    @elseif(auth()->user()->isOpd() && $actionItems['belumDitindaklanjutiOpd'] > 0)
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100 text-rose-700 flex-shrink-0">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-rose-900">Tindak Lanjut Tertunda</h4>
                        <p class="text-xs text-rose-800 mt-0.5">
                            Instansi Anda memiliki <strong>{{ $actionItems['belumDitindaklanjutiOpd'] }}</strong> temuan yang belum ditindaklanjuti.
                        </p>
                    </div>
                </div>

                <a href="{{ route('temuan.index', ['status_penyelesaian' => 'Belum Ditindaklanjuti']) }}" class="shrink-0 text-xs font-bold text-rose-700 hover:underline">
                    Tindak Lanjuti Sekarang &rarr;
                </a>
            </div>
        </div>
    @endif

    {{-- Main Stat Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {{-- Total OPD --}}
        <div class="group min-w-0 rounded-lg border border-gray-200 bg-white p-5 transition-colors hover:border-blue-200">
            <div class="flex items-center justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-wide text-blue-700">Instansi OPD Terdata</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $totalOpd }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-600/20 transition-colors group-hover:bg-blue-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-gray-500 font-medium">
                <span>Pemerintah Provinsi Gorontalo</span>
            </div>
        </div>

        {{-- Total Pemeriksaan --}}
        <div class="group min-w-0 rounded-lg border border-gray-200 bg-white p-5 transition-colors hover:border-primary-200">
            <div class="flex items-center justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-wide text-primary-700">{{ $isLhpOnlyAdmin ? 'Pengajuan LHP' : (auth()->user()->isSuperAdmin() ? 'Total Pengajuan' : 'Total Pemeriksaan') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $totalPemeriksaan }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-600 text-white shadow-md shadow-primary-600/20 transition-colors group-hover:bg-primary-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-gray-500 font-medium">
                <a href="{{ $isLhpOnlyAdmin ? route('pengajuan-saya.index') : (auth()->user()->isSuperAdmin() ? route('pengajuan.index') : route('pemeriksaan.index')) }}" class="hover:text-primary-700 hover:underline">
                    {{ $isLhpOnlyAdmin ? 'Riwayat pengajuan LHP' : (auth()->user()->isSuperAdmin() ? 'Antrean pengajuan' : 'Daftar pemeriksaan') }} &rarr;
                </a>
            </div>
        </div>

        {{-- Total Temuan --}}
        <div class="group min-w-0 rounded-lg border border-gray-200 bg-white p-5 transition-colors hover:border-amber-200">
            <div class="flex items-center justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-wide text-amber-700">Total Temuan</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $totalTemuan }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500 text-white shadow-md shadow-amber-500/20 transition-colors group-hover:bg-amber-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-gray-500 font-medium">
                <a href="{{ route('temuan.index') }}" class="hover:text-amber-700 hover:underline">Semua temuan &rarr;</a>
            </div>
        </div>

        {{-- Total Kerugian --}}
        <div class="group min-w-0 rounded-lg border border-gray-200 bg-white p-5 transition-colors hover:border-rose-200">
            <div class="flex items-center justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-wide text-rose-700">Total Nilai Kerugian</p>
                    <p class="mt-2 text-lg font-bold text-state-danger truncate">
                        Rp {{ number_format($totalNilaiKerugian, 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-600 text-white shadow-md shadow-rose-600/20 transition-colors group-hover:bg-rose-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-rose-700 font-medium">
                <span>Temuan berunsur kerugian daerah & negara</span>
            </div>
        </div>
    </div>

    {{-- Widget Rasio Pemulihan Kerugian Negara dan Daerah (PRD Seksi 17) --}}
    <div class="rounded-lg border border-gray-200 border-t-4 border-t-primary-700 bg-white primary-gradient-wash p-5 sm:p-6">
        <div class="flex min-w-0 flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0 space-y-2">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span class="text-xs font-semibold  tracking-wide text-primary-700">
                        Setoran Kerugian Negara dan Daerah Terverifikasi
                    </span>
                    <span class="text-xs text-gray-500">
                        @if($opdId) Filter OPD Aktif @else Akumulasi Provinsi Gorontalo @endif
                    </span>
                </div>
                <h3 class="text-xl font-semibold tracking-tight text-gray-900">Rasio Pemulihan Kerugian Negara dan Daerah</h3>
                <p class="max-w-xl text-sm leading-6 text-gray-600">
                    Perbandingan total setoran terverifikasi ke Kas Negara dan Kas Daerah dengan total nilai temuan kerugian negara dan daerah.
                </p>
            </div>

            <div class="grid min-w-0 grid-cols-2 gap-x-4 gap-y-4 border-t border-gray-200 pt-4 sm:grid-cols-3 xl:shrink-0 xl:gap-x-6 xl:border-l xl:border-t-0 xl:pl-6 xl:pt-0">
                <div>
                    <span class="text-xs font-medium text-gray-500">Telah Disetor</span>
                    <p class="mt-1 break-words text-sm font-semibold text-emerald-700 sm:text-base">Rp {{ number_format($totalDisetor, 0, ',', '.') }}</p>
                </div>
                <div>
                    <span class="text-xs font-medium text-gray-500">Sisa Kerugian</span>
                    <p class="mt-1 break-words text-sm font-semibold text-rose-700 sm:text-base">Rp {{ number_format($totalSisaKerugian, 0, ',', '.') }}</p>
                </div>
                <div>
                    <span class="text-xs font-medium text-gray-500">Tingkat Pemulihan</span>
                    <p class="mt-1 text-lg font-semibold text-gray-900 sm:text-xl">{{ $persenPemulihan }}%</p>
                </div>
            </div>
        </div>

        {{-- Progress Bar Container --}}
        <div class="mt-5" role="progressbar" aria-label="Tingkat pemulihan kerugian negara dan daerah" aria-valuenow="{{ $persenPemulihan }}" aria-valuemin="0" aria-valuemax="100">
            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                <x-progress-indicator :value="$persenPemulihan" />
            </div>
        </div>
    </div>

    {{-- 2 Interactive Visual Charts --}}
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        {{-- Chart 1: Donut Chart - Distribusi Sumber --}}
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <div class="border-b border-gray-100 pb-3">
                <h3 class="text-sm font-semibold text-gray-900">Distribusi per Sumber</h3>
                <p class="text-xs text-gray-500">BPK, BPKP, ITJEN, Inspektorat</p>
            </div>
            <div class="relative mt-4 h-60 w-full min-w-0">
                <canvas id="chartSumber"></canvas>
            </div>
        </div>

        {{-- Chart 2: Bar Chart - Status Penyelesaian (Utama) --}}
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <div class="border-b border-gray-100 pb-3">
                <h3 class="text-sm font-semibold text-gray-900">Status Penyelesaian Tindak Lanjut</h3>
                <p class="text-xs text-gray-500">Distribusi Temuan Berdasarkan Progres Penyelesaian</p>
            </div>
            <div class="relative mt-4 h-60 w-full min-w-0">
                <canvas id="chartStatus"></canvas>
            </div>
        </div>
    </div>

    {{-- Top 5 OPD Leaderboard & Recent Findings Feed --}}
    <div class="grid grid-cols-1 gap-4 {{ !auth()->user()->isOpd() ? 'xl:grid-cols-2' : '' }}">
        @if(!auth()->user()->isOpd())
        {{-- Top 5 OPD Leaderboard --}}
        <div class="min-w-0 rounded-lg border border-gray-200 bg-white p-5">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div>
                    <h3 class="font-bold text-gray-900">Peringkat OPD Terbanyak Temuan</h3>
                    <p class="text-xs text-gray-500">Top 5 Instansi dengan temuan & nilai kerugian</p>
                </div>
                @if(auth()->user()->isSuperAdmin())
                    <a href="{{ route('opd.index') }}" class="text-xs font-semibold text-primary-700 hover:underline">
                        Semua OPD &rarr;
                    </a>
                @endif
            </div>

            <div class="mt-4 divide-y divide-gray-100">
                @forelse($topOpds as $index => $topOpd)
                    <div class="flex min-w-0 items-center justify-between gap-3 py-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-100 text-xs font-bold text-gray-700">
                                {{ $index + 1 }}
                            </span>
                            <div class="min-w-0">
                                <p class="break-words text-sm font-bold text-gray-900">{{ $topOpd->nama }}</p>
                                <p class="text-xs text-gray-500">Kode: {{ $topOpd->kode }}</p>
                            </div>
                        </div>

                        <div class="shrink-0 text-right">
                            <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-bold text-amber-700 border border-amber-200">
                                {{ $topOpd->temuans_count }} Temuan
                            </span>
                            @if($topOpd->temuans_sum_nilai_kerugian > 0)
                                <p class="text-xs font-bold text-state-danger mt-0.5">
                                    Rp {{ number_format($topOpd->temuans_sum_nilai_kerugian, 0, ',', '.') }}
                                </p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-xs text-gray-500">Belum ada data temuan per OPD.</p>
                @endforelse
            </div>
        </div>
        @endif

        {{-- Recent Findings Feed --}}
        <div class="min-w-0 rounded-lg border border-gray-200 bg-white p-5">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div>
                    <h3 class="font-bold text-gray-900">Temuan Terbaru Masuk</h3>
                    <p class="text-xs text-gray-500">Pencatatan temuan hasil pemeriksaan terkini</p>
                </div>
                <a href="{{ route('temuan.index') }}" class="text-xs font-semibold text-primary-700 hover:underline">
                    Lihat Semua &rarr;
                </a>
            </div>

            <div class="mt-4 divide-y divide-gray-100">
                @forelse($recentTemuans as $rt)
                    <div class="flex min-w-0 items-center justify-between gap-3 py-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-sumber-badge :sumber="$rt->sumber_pemeriksaan" size="xs" />
                                <span class="text-xs font-medium text-gray-500">TA {{ $rt->tahun }}</span>
                            </div>
                            <a href="{{ route('temuan.show', $rt) }}" class="text-xs font-semibold text-gray-800 hover:text-primary-700 line-clamp-1 mt-1">
                                {{ $rt->judul_temuan }}
                            </a>
                            <p class="break-words text-xs text-gray-500">{{ $rt->opd->nama }}</p>
                        </div>

                        <div class="text-right flex-shrink-0">
                            @if($rt->ada_unsur_kerugian)
                                <p class="text-xs font-bold text-state-danger">{{ $rt->formatted_nilai_kerugian }}</p>
                            @endif
                            @php $badge = $rt->status_penyelesaian_badge; @endphp
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium {{ $badge['class'] }}">
                                {{ $badge['label'] }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-xs text-gray-500">Belum ada data temuan.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Load Chart.js from CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Chart 1: Donut Chart Sumber Pemeriksaan
        const ctxSumber = document.getElementById('chartSumber').getContext('2d');
        new Chart(ctxSumber, {
            type: 'doughnut',
            data: {
                labels: ['BPK', 'BPKP', 'ITJEN', 'Inspektorat'],
                datasets: [{
                    data: [
                        {{ $temuanPerSumber['BPK'] }},
                        {{ $temuanPerSumber['BPKP'] }},
                        {{ $temuanPerSumber['ITJEN'] }},
                        {{ $temuanPerSumber['Inspektorat'] }}
                    ],
                    backgroundColor: [
                        '#10b981', // Emerald (BPK)
                        '#3b82f6', // Blue (BPKP)
                        '#f59e0b', // Amber (ITJEN)
                        '#8b5cf6', // Purple (Inspektorat)
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 10 } }
                    }
                },
                cutout: '65%'
            }
        });

        // Chart 2: Bar Chart Status Penyelesaian (Utama)
        const ctxStatus = document.getElementById('chartStatus').getContext('2d');
        new Chart(ctxStatus, {
            type: 'bar',
            data: {
                labels: ['Belum TL', 'Menunggu Verif', 'Belum Sesuai', 'Sesuai', 'Tidak Dapat TL'],
                datasets: [{
                    label: 'Jumlah Temuan',
                    data: [
                        {{ $statusPenyelesaianCounts['Belum Ditindaklanjuti'] }},
                        {{ $statusPenyelesaianCounts['Menunggu Verifikasi'] }},
                        {{ $statusPenyelesaianCounts['Belum Sesuai'] }},
                        {{ $statusPenyelesaianCounts['Sesuai'] }},
                        {{ $statusPenyelesaianCounts['Tidak Dapat Ditindaklanjuti'] }}
                    ],
                    backgroundColor: [
                        '#f43f5e', // Rose (Belum TL)
                        '#3b82f6', // Blue (Menunggu Verifikasi)
                        '#f59e0b', // Amber (Belum Sesuai)
                        '#10b981', // Emerald (Sesuai)
                        '#6b7280'  // Gray (Tidak Dapat TL)
                    ],
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { size: 10 } }
                    },
                    x: {
                        ticks: { font: { size: 9 } },
                        grid: { display: false }
                    }
                }
            }
        });
    });

    function dismissGuideBanner() {
        const banner = document.getElementById('onboarding-guide-banner');
        const reopen = document.getElementById('reopen-guide-wrapper');
        if (banner) banner.classList.add('hidden');
        if (reopen) reopen.classList.remove('hidden');
        try { localStorage.setItem('hide_simantap_onboarding', 'true'); } catch (e) {}
    }

    function restoreGuideBanner() {
        const banner = document.getElementById('onboarding-guide-banner');
        const reopen = document.getElementById('reopen-guide-wrapper');
        if (banner) banner.classList.remove('hidden');
        if (reopen) reopen.classList.add('hidden');
        try { localStorage.removeItem('hide_simantap_onboarding'); } catch (e) {}
    }

    document.addEventListener('DOMContentLoaded', function() {
        try {
            if (localStorage.getItem('hide_simantap_onboarding') === 'true') {
                const banner = document.getElementById('onboarding-guide-banner');
                const reopen = document.getElementById('reopen-guide-wrapper');
                if (banner) banner.classList.add('hidden');
                if (reopen) reopen.classList.remove('hidden');
            }
        } catch (e) {}
    });
</script>
@endsection
