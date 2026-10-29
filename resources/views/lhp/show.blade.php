@extends('layouts.app')

@php
    $isLhpOnlySubmission = $pemeriksaan->is_lhp_only_submission;
@endphp

@section('title', ($isOpdView ? 'Detail LHP OPD: ' : 'Detail LHP: ') . $lhp->nomor_lhp)
@section('page-title', $isOpdView ? 'Rincian LHP untuk OPD' : 'Detail Laporan Hasil Pemeriksaan')
@section('page-subtitle', $isOpdView
    ? 'Informasi LHP dan tindak lanjut untuk OPD Anda — Nomor LHP: ' . $lhp->nomor_lhp
    : 'Nomor LHP: ' . $lhp->nomor_lhp . (! $isLhpOnlySubmission ? ' • SPT: ' . $pemeriksaan->nomor_spt : ''))

@section('content')
<div class="mx-auto max-w-7xl space-y-5">
    {{-- Breadcrumb & Top Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        @php
            $breadcrumbs = [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                [
                    'label' => $isOpdView ? 'Temuan OPD' : ($isLhpOnlySubmission ? 'LHP' : 'SPT & Pemeriksaan'),
                    'href' => $isOpdView ? route('temuan.index') : ($isLhpOnlySubmission ? route('lhp.index') : route('pemeriksaan.index')),
                ],
            ];

            if (! $isLhpOnlySubmission) {
                $breadcrumbs[] = ['label' => $pemeriksaan->nomor_spt, 'href' => route('pemeriksaan.show', $pemeriksaan)];
            }

            $breadcrumbs[] = ['label' => $lhp->nomor_lhp];
        @endphp
        <x-breadcrumbs :items="$breadcrumbs" />
    </div>

    @if($isLhpOnlySubmission && in_array($pemeriksaan->status_pengajuan, ['DRAFT', 'PERLU REVISI', 'DITOLAK'], true) && (auth()->user()->isSuperAdmin() || (auth()->user()->isAdminSumber() && auth()->user()->getSumberPemeriksaan() === $pemeriksaan->sumber_pemeriksaan)))
        <section class="flex flex-col gap-3 rounded-xl border border-orange-200 bg-orange-50 p-4 sm:flex-row sm:items-center sm:justify-between" aria-label="Perbaikan pengajuan LHP">
            <div class="min-w-0">
                <h2 class="text-sm font-bold text-orange-950">Pengajuan memerlukan perbaikan</h2>
                @if($pemeriksaan->catatan_persetujuan)
                    <p class="mt-1 whitespace-pre-line text-sm text-orange-900">{{ $pemeriksaan->catatan_persetujuan }}</p>
                @else
                    <p class="mt-1 text-xs text-orange-800">Perbarui data LHP atau temuan sesuai catatan, lalu ajukan kembali melalui menu Pengajuan Saya.</p>
                @endif
            </div>
            @if(auth()->user()->isAdminSumber())
                <a href="{{ route('pemeriksaan.lhp.edit', [$pemeriksaan, $lhp]) }}" class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-lg border border-orange-300 bg-white px-4 text-xs font-bold text-orange-900 transition hover:bg-orange-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500">Perbaiki Data LHP</a>
            @endif
        </section>
    @endif

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-5">
        {{-- Card 1: Identitas LHP --}}
        <div class="min-w-0 space-y-5 rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm sm:p-6 xl:col-span-3">
            <div class="flex flex-col gap-3 border-b border-gray-100 pb-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <x-sumber-badge :sumber="$pemeriksaan->sumber_pemeriksaan" />
                        <span class="text-xs text-gray-500 font-medium">Tahun {{ $pemeriksaan->tahun }}</span>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 leading-snug">{{ $lhp->judul_lhp ?: 'Laporan Hasil Pemeriksaan' }}</h2>
                    <p class="text-xs font-mono font-semibold text-primary-700 mt-0.5">No. LHP: {{ $lhp->nomor_lhp }}</p>
                </div>

                <x-status-badge :badge="$lhpStatusBadge" size="md" dot-class="w-2 h-2 mr-1.5" class="font-bold" />
            </div>

            <div class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-2">
                <div class="min-w-0 space-y-1 rounded-xl border border-gray-100 bg-gray-50/70 p-3.5">
                    <span class="text-gray-500 font-semibold tracking-wider text-xs">{{ $isOpdView ? 'OPD Anda' : 'Instansi OPD Target' }}</span>
                    @if($lhp->opds->isNotEmpty())
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach($lhp->opds as $targetOpd)
                                <span class="inline-flex items-center rounded-md bg-primary-50 border border-primary-200/60 px-2 py-0.5 text-xs font-bold text-primary-800">
                                    [{{ $targetOpd->kode }}] {{ $targetOpd->nama }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm font-bold text-gray-900">{{ $pemeriksaan->opd?->nama ?? '-' }}</p>
                    @endif
                </div>
                <div class="min-w-0 space-y-1 rounded-xl border border-gray-100 bg-gray-50/70 p-3.5">
                    <span class="text-gray-500 font-semibold  tracking-wider text-xs">Tanggal LHP</span>
                    <p class="text-sm font-bold text-gray-900">{{ $lhp->tanggal_lhp ? $lhp->tanggal_lhp->translatedFormat('d F Y') : '-' }}</p>
                </div>
                @if(! $isLhpOnlySubmission)
                <div class="min-w-0 space-y-1 rounded-xl border border-gray-100 bg-gray-50/70 p-3.5">
                    <span class="text-gray-500 font-semibold  tracking-wider text-xs">Induk Surat Perintah Tugas (SPT)</span>
                    <p class="text-sm font-bold text-gray-900">{{ $pemeriksaan->nomor_spt }}</p>
                </div>
                @endif
                <div class="min-w-0 space-y-1 rounded-xl border border-gray-100 bg-gray-50/70 p-3.5">
                    <span class="text-gray-500 font-semibold  tracking-wider text-xs">Dokumen LHP</span>
                    <div>
                        @if($lhp->dokumen_lhp)
                            <a href="{{ route('pemeriksaan.lhp.download', [$pemeriksaan, $lhp]) }}" class="inline-flex items-center text-xs font-bold text-primary-600 hover:text-primary-800 transition">
                                <svg class="w-4 h-4 mr-1 text-primary-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Unduh Berkas LHP
                            </a>
                        @else
                            <span class="text-gray-500 italic">Tidak ada lampiran dokumen</span>
                        @endif
                    </div>
                </div>
            </div>

            @if($lhp->ringkasan)
                <div class="rounded-xl border border-primary-100 bg-primary-50/30 p-4">
                    <h4 class="font-bold text-gray-900 mb-1">Ringkasan LHP:</h4>
                    <p class="text-sm leading-relaxed text-gray-700 whitespace-pre-line">{{ $lhp->ringkasan }}</p>
                </div>
            @endif
        </div>

        {{-- Card 2: Ringkasan Finansial & Progress Penyelesaian --}}
        <div class="min-w-0 space-y-5 rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm sm:p-6 xl:col-span-2">
            <h3 class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 border-b border-gray-100 pb-3 text-sm font-bold tracking-wider text-gray-900">
                <span>{{ $isOpdView ? 'Ringkasan untuk OPD Anda' : 'Ringkasan Finansial LHP' }}</span>
                <span class="text-xs font-bold text-primary-700">{{ $lhpSummary['progress'] }}% Selesai</span>
            </h3>

            {{-- Progress Bar --}}
            <div class="space-y-1.5">
                <div class="flex justify-between text-xs font-bold">
                    <span class="text-gray-600">{{ $isOpdView ? 'Progress Tindak Lanjut OPD' : 'Progress Tindak Lanjut' }}</span>
                    <span class="text-primary-700 font-extrabold">{{ $lhpSummary['progress'] }}%</span>
                </div>
                <div class="w-full h-3 overflow-hidden rounded-full border border-gray-200 bg-gray-100 p-0.5" role="progressbar" aria-label="Progress tindak lanjut LHP" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $lhpSummary['progress'] }}">
                    <x-progress-indicator :value="$lhpSummary['progress']" />
                </div>
            </div>

            <div class="space-y-3 pt-2">
                <div class="flex flex-col gap-1 rounded-xl border border-gray-100 bg-gray-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                    <span class="text-xs font-semibold text-gray-500">{{ $isOpdView ? 'Total Kerugian OPD Anda' : 'Total Nilai Kerugian' }}</span>
                    <span class="text-sm font-bold text-gray-900">{{ $lhpSummary['total_kerugian'] }}</span>
                </div>

                <div class="flex flex-col gap-1 rounded-xl border border-emerald-100 bg-emerald-50/60 p-3 sm:flex-row sm:items-center sm:justify-between">
                    <span class="text-xs font-semibold text-emerald-800">{{ $isOpdView ? 'Setoran Terverifikasi OPD Anda' : 'Total Telah Disetor (Kasda / Kas Negara)' }}</span>
                    <span class="text-sm font-bold text-emerald-700">{{ $lhpSummary['total_disetor'] }}</span>
                </div>

                <div class="flex flex-col gap-1 rounded-xl border border-rose-100 bg-rose-50/60 p-3 sm:flex-row sm:items-center sm:justify-between">
                    <span class="text-xs font-semibold text-rose-800">{{ $isOpdView ? 'Sisa Kewajiban OPD Anda' : 'Sisa Kewajiban' }}</span>
                    <span class="text-sm font-bold text-rose-700">{{ $lhpSummary['sisa_kerugian'] }}</span>
                </div>
            </div>

            @if($lhpSummary['total_kerugian_value'] > 0)
                {{-- Sub Kategori Kerugian Breakdown & Keterangan --}}
                <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3.5 space-y-2 text-xs">
                    <div class="flex items-center justify-between font-bold text-amber-950">
                        <span class="flex items-center gap-1.5">
                            Sub Kategori Kerugian:
                        </span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div class="p-2.5 rounded-lg bg-white border border-primary-200">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-primary-900 flex items-center gap-1">
                                    Internal
                                </span>
                                <span class="font-bold text-primary-950 text-xs">{{ $lhpSummary['total_kerugian_internal'] }}</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Ditemukan di dalam kantor / OPD ({{ $lhpSummary['kerugian_internal_count'] }} temuan)</p>
                        </div>
                        <div class="p-2.5 rounded-lg bg-white border border-amber-200">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-amber-900 flex items-center gap-1">
                                    Eksternal
                                </span>
                                <span class="font-bold text-amber-950 text-xs">{{ $lhpSummary['total_kerugian_eksternal'] }}</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Ditemukan pada pihak ketiga ({{ $lhpSummary['kerugian_eksternal_count'] }} temuan)</p>
                        </div>
                        @if($lhpSummary['kerugian_belum_dikategorikan_count'] > 0)
                            <div class="p-2.5 rounded-lg bg-rose-50 border border-rose-300 col-span-full">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-rose-900 flex items-center gap-1">
                                        Belum Dikategorikan
                                    </span>
                                    <span class="font-bold text-rose-950 text-xs">{{ $lhpSummary['total_kerugian_belum_dikategorikan'] }}</span>
                                </div>
                                <p class="text-xs text-rose-700 mt-1">Belum ditentukan Internal / Eksternal ({{ $lhpSummary['kerugian_belum_dikategorikan_count'] }} temuan)</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="pt-2 text-xs text-gray-500 border-t border-gray-100 flex items-center justify-between">
                <span>{{ $isOpdView ? 'Jumlah Temuan OPD Anda:' : 'Jumlah Temuan Terkait:' }}</span>
                <span class="font-bold text-gray-900">{{ $lhpSummary['temuan_count'] }} Temuan</span>
            </div>
        </div>
    </div>

    {{-- Section Temuan dalam LHP --}}
    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="flex items-center gap-2 text-base font-bold text-gray-900">
                    <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    {{ $isOpdView ? 'Temuan dan Tindak Lanjut OPD Anda' : 'Daftar Temuan & Tindak Lanjut di Bawah LHP Ini' }} ({{ $lhpSummary['temuan_count'] }})
                </h2>
                <span class="text-xs text-gray-500 font-medium">Tindak lanjut dilakukan dan diverifikasi per masing-masing temuan</span>
            </div>

            @if($pemeriksaan->status_pengajuan !== 'MENUNGGU PERSETUJUAN' && !auth()->user()->isPemantau() && !auth()->user()->isOpd() && (auth()->user()->isSuperAdmin() || auth()->user()->getSumberPemeriksaan() === $pemeriksaan->sumber_pemeriksaan))
                <a href="{{ route('temuan.create', ['pemeriksaan_id' => $pemeriksaan->id, 'lhp_id' => $lhp->id]) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Temuan
                </a>
            @endif
        </div>

        @forelse($lhp->temuans as $temuanIndex => $temuan)
            @php
                $tBadge = $temuan->status_penyelesaian_badge;
                $isKerugian = $temuan->ada_unsur_kerugian;
            @endphp
            <div class="overflow-hidden rounded-2xl border border-gray-200/90 bg-white shadow-sm transition hover:border-primary-300">
                {{-- Header Temuan Card --}}
                <div class="flex flex-col justify-between gap-3 border-b px-5 py-4 sm:flex-row sm:items-center {{ $isKerugian ? 'border-amber-200 bg-amber-50' : 'border-gray-200 bg-gray-50' }}">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="px-3 py-1 rounded-xl text-xs font-bold {{ $isKerugian ? 'bg-amber-600 text-white shadow-sm shadow-amber-600/20' : 'bg-primary-600 text-white shadow-sm shadow-primary-600/20' }}">
                            {{ $isKerugian ? 'Temuan Kerugian Daerah' : 'Temuan Administrasi #' . ($temuanIndex + 1) }}
                        </span>
                        @if($isKerugian)
                            @if($temuan->kategori_kerugian)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-bold border {{ $temuan->kategori_kerugian === 'Kerugian Negara' ? 'bg-primary-100 text-primary-700 border-primary-200' : 'bg-teal-100 text-teal-700 border-teal-200' }}">
                                    {{ $temuan->kategori_kerugian }}
                                </span>
                            @endif
                            @php $kgInfo = $temuan->jenis_kerugian_info; @endphp
                            @if($temuan->jenis_kerugian)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-bold border {{ $kgInfo['badge_class'] ?? 'bg-amber-100 text-amber-800 border-amber-200' }}" title="{{ $kgInfo['keterangan'] ?? '' }}">
                                    {{ $kgInfo['label'] }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-bold border bg-rose-100 text-rose-800 border-rose-300" title="Sub kategori belum ditentukan (Internal atau Eksternal)">
                                    Belum Dikategorikan
                                </span>
                            @endif
                        @endif
                        <h3 class="text-sm font-bold text-gray-900">{{ $temuan->judul_temuan }}</h3>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-status-badge :badge="$tBadge" size="md" dot-class="w-2 h-2 mr-1.5" class="font-bold" />

                        @if(!auth()->user()->isPemantau() && !auth()->user()->isOpd() && (auth()->user()->isSuperAdmin() || auth()->user()->getSumberPemeriksaan() === $temuan->sumber_pemeriksaan))
                            @if(($pemeriksaan->status_pengajuan ?? 'DISETUJUI') === 'DISETUJUI' || $temuan->status_pengajuan === 'DISETUJUI')
                                @if($temuan->rekomendasis->isNotEmpty())
                                    <a href="{{ route('temuan.show', $temuan) }}"
                                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold rounded-xl shadow-sm shadow-primary-600/20 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        <span>Tindak Lanjut per Rekomendasi</span>
                                    </a>
                                @else
                                    <button type="button"
                                            onclick="openInputTlForTemuan({{ $temuan->id }}, '{{ addslashes($temuan->judul_temuan) }}', '{{ $isKerugian ? 'kerugian' : 'administrasi' }}', '{{ addslashes($temuan->uraian_temuan ?? '') }}', '{{ addslashes($temuan->rekomendasi ?? '') }}', '{{ addslashes($lhp->nomor_lhp) }}', '{{ addslashes($temuan->opd?->nama ?? ($pemeriksaan->opd?->nama ?? '-')) }}', '{{ addslashes($temuan->sumber_pemeriksaan) }}', '{{ addslashes($pemeriksaan->nomor_spt ?? '-') }}')"
                                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold rounded-xl shadow-sm shadow-primary-600/20 transition cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        <span>Input Tindak Lanjut</span>
                                    </button>
                                @endif
                            @elseif($isLhpOnlySubmission && auth()->user()->isAdminSumber() && in_array($pemeriksaan->status_pengajuan, ['DRAFT', 'PERLU REVISI', 'DITOLAK'], true))
                                <a href="{{ route('temuan.edit', $temuan) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-orange-300 bg-white px-3.5 py-1.5 text-xs font-bold text-orange-900 transition hover:bg-orange-50">
                                    Perbaiki Temuan
                                </a>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- Body Temuan --}}
                <div class="space-y-4 p-5 sm:p-6">
                    {{-- Uraian & Rekomendasi --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 rounded-2xl bg-gray-50/80 border border-gray-100 space-y-1">
                            <span class="text-xs font-bold text-gray-500  tracking-wider block">Uraian Temuan</span>
                            <p class="text-sm leading-relaxed text-gray-800 whitespace-pre-line">{{ $temuan->uraian_temuan ?: '-' }}</p>
                        </div>
                        <div class="p-4 rounded-2xl bg-primary-50/40 border border-primary-100/60 space-y-1">
                            <span class="text-xs font-bold text-primary-700  tracking-wider block">Rekomendasi yang Diberikan</span>
                            <p class="text-sm font-medium leading-relaxed text-primary-900 whitespace-pre-line">{{ $temuan->rekomendasi ?: '-' }}</p>
                        </div>
                    </div>

                    {{-- Penjelasan Keterangan Sub Kategori Kerugian --}}
                    @if($isKerugian)
                        @php $kgInfo = $temuan->jenis_kerugian_info; @endphp
                        <div class="rounded-2xl border {{ ($temuan->jenis_kerugian === 'Internal') ? 'border-primary-200 bg-primary-50/70' : (($temuan->jenis_kerugian === 'Eksternal') ? 'border-amber-200 bg-amber-50/70' : 'border-gray-200 bg-gray-50') }} p-4 text-xs">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-start gap-3">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold  tracking-wider text-gray-500">Sub Kategori Kerugian:</span>
                                            <span class="font-extrabold text-gray-900 text-xs">{{ $kgInfo['label'] ?? ($temuan->jenis_kerugian ?: 'Kerugian Daerah') }}</span>
                                        </div>
                                        <p class="text-xs text-gray-600 mt-1">
                                            {{ $kgInfo['keterangan'] ?? 'Temuan kerugian finansial daerah/negara.' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 self-start sm:self-auto flex-shrink-0">
                                    @if($temuan->jenis_kerugian === 'Internal')
                                        <span class="px-3 py-1.5 rounded-xl bg-primary-100 border border-primary-300 text-primary-900 font-bold text-xs flex items-center gap-1.5 shadow-2xs">
                                            Temuan di Lingkungan Kantor / OPD
                                        </span>
                                    @elseif($temuan->jenis_kerugian === 'Eksternal')
                                        <span class="px-3 py-1.5 rounded-xl bg-amber-100 border border-amber-300 text-amber-900 font-bold text-xs flex items-center gap-1.5 shadow-2xs">
                                            Temuan pada Pihak Ketiga / Rekanan
                                        </span>
                                    @else
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="px-2.5 py-1 rounded-lg bg-rose-100 border border-rose-300 text-rose-900 font-bold text-xs flex items-center gap-1 shadow-2xs">
                                                Belum Ditentukan
                                            </span>
                                            @if(!auth()->user()->isPemantau() && !auth()->user()->isOpd() && (auth()->user()->isSuperAdmin() || auth()->user()->getSumberPemeriksaan() === $temuan->sumber_pemeriksaan))
                                                <form method="POST" action="{{ route('temuan.update-jenis-kerugian', $temuan) }}" class="inline-flex items-center">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="jenis_kerugian" value="Internal">
                                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold transition shadow-xs cursor-pointer" title="Tetapkan sebagai Kerugian Internal (di dalam kantor/OPD)">
                                                        Set Internal
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('temuan.update-jenis-kerugian', $temuan) }}" class="inline-flex items-center">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="jenis_kerugian" value="Eksternal">
                                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold transition shadow-xs cursor-pointer" title="Tetapkan sebagai Kerugian Eksternal (pihak ketiga/rekanan)">
                                                        Set Eksternal
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Finansial jika Temuan Kerugian --}}
                    @if($isKerugian && $temuan->nilai_kerugian > 0)
                        <div class="p-4 rounded-2xl bg-amber-50/50 border border-amber-200/80 space-y-3">
                            <div class="flex items-center justify-between text-xs font-bold">
                                <span class="text-amber-900">Progress Penyetoran Temuan Kerugian Ini</span>
                                <span class="text-emerald-700 font-bold">{{ $temuan->progress_persen }}% Terpenuhi</span>
                            </div>
                            <div class="w-full h-2.5 overflow-hidden rounded-full border border-amber-200 bg-white p-0.5" role="progressbar" aria-label="Progress penyetoran temuan" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $temuan->progress_persen }}">
                                <x-progress-indicator :value="$temuan->progress_persen" />
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1 text-xs">
                                <div>
                                    <span class="text-gray-500 block text-xs font-semibold">Kategori Kerugian</span>
                                    <span class="font-bold {{ $temuan->jenis_kerugian === 'Internal' ? 'text-primary-800' : ($temuan->jenis_kerugian === 'Eksternal' ? 'text-amber-800' : 'text-rose-700') }} text-sm">
                                        {{ $temuan->jenis_kerugian ?: 'Belum Dikategorikan' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block text-xs font-semibold">Total Nilai Kerugian</span>
                                    <span class="font-bold text-gray-900 text-sm">{{ $temuan->formatted_nilai_kerugian }}</span>
                                </div>
                                <div>
                                    <span class="text-emerald-700 block text-xs font-semibold">Total Terverifikasi Disetor (Kasda / Kas Negara)</span>
                                    <span class="font-bold text-emerald-700 text-sm">{{ $temuan->formatted_total_disetor }}</span>
                                </div>
                                <div>
                                    <span class="text-rose-700 block text-xs font-semibold">Sisa Kewajiban</span>
                                    <span class="font-bold text-rose-700 text-sm">{{ $temuan->formatted_sisa_kerugian }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Riwayat Tindak Lanjut Temuan Ini --}}
                    <div class="border-t border-gray-100 pt-4 space-y-3">
                        <h4 class="text-xs font-bold text-gray-700  tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-state-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Riwayat Tindak Lanjut Temuan Ini ({{ $temuan->tindakLanjuts->count() }})
                        </h4>

                        @if($temuan->tindakLanjuts->isEmpty())
                            <div class="text-center py-6 px-4 border border-dashed border-gray-200 rounded-2xl bg-gray-50/50">
                                <p class="text-xs text-gray-500 font-medium">Belum ada riwayat tindak lanjut yang disampaikan untuk temuan ini.</p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($temuan->tindakLanjuts as $tlIdx => $tl)
                                    @php
                                        $iterationNumber = $temuan->tindakLanjuts->count() - $tlIdx;
                                    @endphp
                                    <div class="bg-gray-50/70 border border-gray-200/90 rounded-2xl p-4 transition hover:bg-white space-y-3">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-200/60 pb-2.5">
                                            <div class="flex items-center gap-2">
                                                <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs font-bold">
                                                    #{{ $iterationNumber }}
                                                </span>
                                                <div>
                                                    <span class="text-xs font-bold text-gray-900">Tindak Lanjut #{{ $iterationNumber }}</span>
                                                    <span class="text-xs text-gray-500 ml-2">Tgl: {{ $tl->tanggal_tindak_lanjut ? $tl->tanggal_tindak_lanjut->translatedFormat('d F Y') : '-' }} • Oleh: {{ $tl->creator->name ?? 'User' }}</span>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                @if($tl->status_verifikasi === 'SESUAI')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                                        Diverifikasi: Sesuai
                                                    </span>
                                                @elseif($tl->status_verifikasi === 'BELUM SESUAI')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                                        Diverifikasi: Belum Sesuai
                                                    </span>
                                                @elseif($tl->status_verifikasi === 'TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-800 border border-gray-300">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-500 mr-1.5"></span>
                                                        Tidak Dapat Ditindaklanjuti
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200 animate-pulse">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-1.5"></span>
                                                        Menunggu Verifikasi
                                                    </span>
                                                @endif

                                                @if(auth()->user()->isSuperAdmin())
                                                    <button type="button"
                                                            onclick="openVerifikasiModal({{ $temuan->id }}, {{ $tl->id }}, '{{ $tl->status_verifikasi }}', '{{ addslashes($tl->catatan_verifikasi ?? '') }}')"
                                                            class="inline-flex items-center px-2.5 py-1 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                                                        Verifikasi
                                                    </button>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="text-xs space-y-1">
                                            <p class="text-gray-800 leading-relaxed whitespace-pre-line bg-white p-3 rounded-xl border border-gray-100">{{ $tl->uraian_tindak_lanjut }}</p>
                                        </div>

                                        @if($tl->jumlah_setor > 0 || $tl->nomor_bukti_setor)
                                            <div class="p-3 rounded-xl bg-emerald-50/50 border border-emerald-200/70 grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                                                <div>
                                                    <span class="text-emerald-800 font-semibold text-xs ">Nominal Setoran</span>
                                                    <p class="text-sm font-bold text-emerald-700">{{ $tl->formatted_jumlah_setor }}</p>
                                                </div>
                                                <div>
                                                    <span class="text-emerald-800 font-semibold text-xs ">No. Bukti Setor</span>
                                                    <p class="font-bold text-gray-900">{{ $tl->nomor_bukti_setor ?: '-' }}</p>
                                                </div>
                                                <div>
                                                    <span class="text-emerald-800 font-semibold text-xs ">Tanggal Setor</span>
                                                    <p class="font-bold text-gray-900">{{ $tl->tanggal_setor ? $tl->tanggal_setor->translatedFormat('d F Y') : '-' }}</p>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs pt-1">
                                            <div class="min-w-0 flex-1">
                                                @if($tl->dokumen_bukti)
                                                    <a href="{{ route('temuan.tindak-lanjut.download', [$temuan, $tl]) }}" class="inline-flex max-w-full min-w-0 items-start font-bold text-primary-600 whitespace-normal hover:text-primary-800 transition">
                                                        <svg class="w-4 h-4 mr-1 shrink-0 text-primary-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                                        </svg>
                                                        <span class="min-w-0 [overflow-wrap:anywhere]">Lampiran Bukti ({{ $tl->nama_dokumen_asli ?? 'Unduh Dokumen' }})</span>
                                                    </a>
                                                @else
                                                    <span class="text-gray-500 italic text-xs">Tidak ada lampiran berkas</span>
                                                @endif
                                            </div>

                                            @if($tl->verifier)
                                                <div class="text-xs text-gray-500">
                                                    Diverifikasi oleh: <strong class="text-gray-800">{{ $tl->verifier->name }}</strong> ({{ $tl->verified_at ? $tl->verified_at->translatedFormat('d/m/Y H:i') : '-' }})
                                                </div>
                                            @endif
                                        </div>

                                        @if($tl->catatan_verifikasi)
                                            <div class="bg-amber-50 p-2.5 rounded-xl border border-amber-200 text-xs">
                                                <span class="font-bold text-amber-900 block mb-0.5">Catatan Verifikator:</span>
                                                <p class="text-amber-800">{{ $tl->catatan_verifikasi }}</p>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="p-8 text-center bg-white rounded-3xl border border-dashed border-gray-200 text-gray-500">
                Belum ada data temuan yang tercatat di bawah LHP ini.
            </div>
        @endforelse
    </div>
</div>

{{-- MODAL INPUT TINDAK LANJUT TERPADU --}}
<div id="inputTlModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="modalTlHeaderTitle" tabindex="-1">
    <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100 my-8 max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 bg-primary-800 text-white flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <div>
                    <h3 id="modalTlHeaderTitle" class="text-sm font-bold">Input Tindak Lanjut Temuan</h3>
                    <p class="text-xs text-primary-100">Sampaikan pelaksanaan tindak lanjut untuk rekomendasi terkait</p>
                </div>
            </div>
            <button type="button" onclick="closeInputTlModal()" aria-label="Tutup dialog tindak lanjut" class="text-white/80 hover:text-white text-lg font-bold cursor-pointer">✕</button>
        </div>

        <form id="inputTlForm" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 overflow-y-auto flex-1">
            @csrf

            {{-- Info Temuan & Rekomendasi Terpilih (Readonly) --}}
            <div class="p-4 rounded-2xl bg-gray-50 border border-gray-200/80 space-y-2.5 text-xs">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200/70 pb-2">
                    <div class="flex items-center gap-2">
                        <span id="modalTemuanBadge" class="px-2.5 py-0.5 rounded-lg text-xs font-bold  tracking-wider bg-primary-100 text-primary-800">
                            Temuan
                        </span>
                        <span id="modalTemuanSumberBadge" class="px-2 py-0.5 rounded-md text-xs font-bold  tracking-wider bg-gray-200 text-gray-800">
                            -
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 font-medium">
                        LHP: <strong id="modalTemuanLhpNo" class="text-gray-900">-</strong>
                        @if(! $isLhpOnlySubmission)
                            • SPT: <strong id="modalTemuanSptNo" class="text-gray-900">-</strong>
                        @else
                            <strong id="modalTemuanSptNo" class="hidden">-</strong>
                        @endif
                    </div>
                </div>

                <div>
                    <span class="text-gray-500 font-bold text-xs block ">OPD Target:</span>
                    <p id="modalTemuanOpd" class="font-bold text-gray-900"></p>
                </div>

                <div>
                    <span class="text-gray-500 font-bold text-xs block ">Uraian Temuan:</span>
                    <p id="modalTemuanUraian" class="font-bold text-gray-800 whitespace-pre-line"></p>
                </div>

                <div>
                    <span class="text-primary-700 font-bold text-xs block ">Rekomendasi yang Diberikan:</span>
                    <p id="modalTemuanRekomendasi" class="text-primary-900 font-semibold whitespace-pre-line"></p>
                </div>
            </div>

            {{-- Tanggal Tindak Lanjut --}}
            <div>
                <label for="tanggal_tindak_lanjut" class="block text-xs font-bold text-gray-700 mb-1">Tanggal Tindak Lanjut <span class="text-state-danger">*</span></label>
                <input type="date" name="tanggal_tindak_lanjut" id="tanggal_tindak_lanjut" required value="{{ date('Y-m-d') }}" class="w-full text-xs idds-form-control">
            </div>

            {{-- Uraian Tindak Lanjut --}}
            <div>
                <label for="uraian_tindak_lanjut" class="block text-xs font-bold text-gray-700 mb-1">Uraian Pelaksanaan Tindak Lanjut <span class="text-state-danger">*</span></label>
                <textarea name="uraian_tindak_lanjut" id="uraian_tindak_lanjut" rows="3" required placeholder="Jelaskan langkah-langkah tindak lanjut yang telah diselesaikan..." class="w-full text-xs idds-form-control"></textarea>
            </div>

            {{-- Upload Bukti TL --}}
            <div>
                <label for="dokumen_bukti" class="block text-xs font-bold text-gray-700 mb-1">
                    Upload Dokumen / Bukti Pendukung Tindak Lanjut <span class="text-state-danger">*</span>
                </label>
                <input type="file" name="dokumen_bukti" id="dokumen_bukti" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                <p class="text-xs text-gray-500 mt-1">Format: PDF, DOC, DOCX, JPG, PNG (Maks. 15MB) — Wajib dilampirkan</p>
            </div>

            {{-- Bagian Bukti Setoran (HANYA untuk Temuan Kerugian Daerah) --}}
            <div id="modalSetoranSection" class="hidden border-t border-gray-200 pt-3 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold  tracking-wider text-emerald-800 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-state-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Bukti Setoran Kas Daerah (Pengembalian Kerugian)
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label for="jumlah_setor" class="block text-xs font-bold text-gray-700 mb-1">Nilai Setoran (Rp)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-bold text-gray-500">Rp</span>
                            <input type="text" name="jumlah_setor" id="jumlah_setor" placeholder="0" oninput="formatRupiahInput(this)" class="w-full pl-9 text-xs font-bold idds-form-control">
                        </div>
                    </div>

                    <div>
                        <label for="nomor_bukti_setor" class="block text-xs font-bold text-gray-700 mb-1">Nomor Bukti Setor (STS / NTPN)</label>
                        <input type="text" name="nomor_bukti_setor" id="nomor_bukti_setor" placeholder="Contoh: STS-2026/001" class="w-full text-xs idds-form-control">
                    </div>

                    <div>
                        <label for="tanggal_setor" class="block text-xs font-bold text-gray-700 mb-1">Tanggal Setoran</label>
                        <input type="date" name="tanggal_setor" id="tanggal_setor" class="w-full text-xs idds-form-control">
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col items-stretch justify-end gap-3 pt-3 border-t border-gray-100 sm:flex-row sm:items-center">
                <button type="button" onclick="closeInputTlModal()" class="px-4 py-2 text-xs font-bold rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl bg-primary-600 hover:bg-primary-700 text-white shadow-md shadow-primary-500/20 transition cursor-pointer">
                    Ajukan Verifikasi
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL VERIFIKASI SUPER ADMIN --}}
<div id="verifikasiModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="lhp-verifikasi-modal-title" tabindex="-1">
    <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100 my-8">
        <div class="px-6 py-4 bg-primary-800 text-white flex items-center justify-between">
            <h3 id="lhp-verifikasi-modal-title" class="text-sm font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-primary-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Verifikasi Tindak Lanjut (Super Admin)
            </h3>
            <button type="button" onclick="closeVerifikasiModal()" aria-label="Tutup dialog verifikasi tindak lanjut" class="text-white/80 hover:text-white text-lg font-bold cursor-pointer">✕</button>
        </div>

        <form id="verifikasiForm" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-2">Hasil Verifikasi <span class="text-state-danger">*</span></label>
                <div class="space-y-2">
                    <label class="flex items-start p-3 border rounded-xl cursor-pointer hover:bg-emerald-50/40 border-gray-200 transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50">
                        <input type="radio" name="status_verifikasi" value="SESUAI" required onchange="handleVerifikasiChange(this.value)" class="mt-0.5 text-state-success focus:ring-emerald-500">
                        <div class="ml-2.5">
                            <span class="text-xs font-bold text-gray-900 block">Diterima / Sesuai (Bukti Valid & Diakui Masuk Kasda / Kas Negara)</span>
                            <span class="text-xs text-gray-500 block">Nominal setoran diakui. Sistem otomatis menghitung: jika baru bayar sebagian, status temuan menjadi <strong>"Belum Sesuai"</strong>; jika sudah lunas 100%, otomatis <strong>"Sesuai"</strong>.</span>
                        </div>
                    </label>

                    <label class="flex items-start p-3 border rounded-xl cursor-pointer hover:bg-amber-50/40 border-gray-200 transition has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50/50">
                        <input type="radio" name="status_verifikasi" value="BELUM SESUAI" onchange="handleVerifikasiChange(this.value)" class="mt-0.5 text-state-warning focus:ring-amber-500">
                        <div class="ml-2.5">
                            <span class="text-xs font-bold text-gray-900 block">Ditolak / Belum Sesuai (Bukti Tidak Sah / Perlu Perbaikan)</span>
                            <span class="text-xs text-gray-500 block">Bukti transfer salah rekening, tidak sah, atau berkas kurang. Nominal tidak diakui masuk kas. Wajib isi catatan.</span>
                        </div>
                    </label>

                    <label class="flex items-start p-3 border rounded-xl cursor-pointer hover:bg-gray-100 border-gray-200 transition has-[:checked]:border-gray-500 has-[:checked]:bg-gray-100">
                        <input type="radio" name="status_verifikasi" value="TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH" onchange="handleVerifikasiChange(this.value)" class="mt-0.5 text-gray-600 focus:ring-gray-500">
                        <div class="ml-2.5">
                            <span class="text-xs font-bold text-gray-900 block">Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah</span>
                            <span class="text-xs text-gray-500 block">Kondisi kahar, ketetapan hukum mengikat, atau alasan sah lainnya. Wajib isi alasan khusus & upload berkas.</span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Catatan Verifikasi --}}
            <div id="catatanSection">
                <label for="catatan_verifikasi" class="block text-xs font-bold text-gray-700 mb-1">Catatan / Arahan Verifikasi</label>
                <textarea name="catatan_verifikasi" id="catatan_verifikasi" rows="3" placeholder="Tuliskan catatan evaluasi..." class="w-full text-xs idds-form-control"></textarea>
            </div>

            {{-- Alasan Khusus (Wajib jika Tidak Dapat TL) --}}
            <div id="alasanKhususSection" class="hidden space-y-3 p-3 bg-gray-50 rounded-2xl border border-gray-200">
                <div>
                    <label for="alasan_khusus" class="block text-xs font-bold text-gray-900 mb-1">Alasan Sah Tidak Dapat Ditindaklanjuti <span class="text-state-danger">*</span></label>
                    <textarea name="alasan_khusus" id="alasan_khusus" rows="2" placeholder="Jelaskan alasan hukum / kondisi sah..." class="w-full text-xs idds-form-control"></textarea>
                </div>
                <div>
                    <label for="dokumen_pendukung_alasan" class="block text-xs font-bold text-gray-700 mb-1">Upload Berkas Pendukung Keputusan</label>
                    <input type="file" name="dokumen_pendukung_alasan" id="dokumen_pendukung_alasan" accept=".pdf,.doc,.docx" class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-gray-200 file:text-gray-700">
                </div>
            </div>

            <div class="flex flex-col items-stretch justify-end gap-3 pt-3 border-t border-gray-100 sm:flex-row sm:items-center">
                <button type="button" onclick="closeVerifikasiModal()" class="px-4 py-2 text-xs font-bold rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl bg-primary-600 hover:bg-primary-700 text-white shadow-md shadow-primary-500/20 transition cursor-pointer">
                    Simpan Verifikasi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
</script>
@endsection
