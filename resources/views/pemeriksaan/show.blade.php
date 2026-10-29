@extends('layouts.app')

@php
    $isLhpOnlySubmission = $pemeriksaan->is_lhp_only_submission;
    $reviewIdentifier = $isLhpOnlySubmission
        ? $pemeriksaan->lhps->pluck('nomor_lhp')->filter()->implode(', ')
        : $pemeriksaan->nomor_spt;
    $submissionLabel = $isLhpOnlySubmission ? 'pengajuan LHP' : 'pemeriksaan';
@endphp

@section('title', $isLhpOnlySubmission ? 'Detail Pengajuan LHP - ' . $pemeriksaan->sumber_pemeriksaan : 'Detail SPT & LHP - ' . $pemeriksaan->nomor_spt)
@section('page-title', $isLhpOnlySubmission ? 'Detail Pengajuan LHP' : 'Detail SPT & LHP')
@section('page-subtitle', $isLhpOnlySubmission ? 'Tinjau dokumen LHP, target OPD, dan temuan yang diajukan' : 'Informasi Surat Perintah Tugas (SPT), Tim Pemeriksa, dan Laporan Hasil Pemeriksaan (LHP)')

@section('content')
<div class="mx-auto max-w-7xl space-y-5">
    {{-- Top Action Bar --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-breadcrumbs :items="[
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => $isLhpOnlySubmission ? 'LHP' : 'SPT & Pemeriksaan', 'href' => $isLhpOnlySubmission ? route('lhp.index') : route('pemeriksaan.index')],
            ['label' => $isLhpOnlySubmission ? 'Pengajuan ' . $pemeriksaan->sumber_pemeriksaan . ' TA ' . $pemeriksaan->tahun : $pemeriksaan->nomor_spt],
        ]" />

        <div class="flex flex-wrap items-center gap-3">
            @if(!auth()->user()->isPemantau() && !auth()->user()->isOpd())
                @if(! $isLhpOnlySubmission && (auth()->user()->isSuperAdmin() || (auth()->user()->isAdminSumber() && auth()->user()->getSumberPemeriksaan() === $pemeriksaan->sumber_pemeriksaan)))
                    <a href="{{ route('pemeriksaan.edit', $pemeriksaan) }}"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition cursor-pointer">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit SPT
                    </a>
                @endif
            @endif

            {{-- Tombol Arsipkan Laporan (hanya muncul jika semua temuan selesai) --}}
            @if((auth()->user()->isAdminSumber() || auth()->user()->isSuperAdmin()) && $pemeriksaan->isFullyResolved())
                <form method="POST" action="{{ route('pemeriksaan.archive', $pemeriksaan) }}" class="inline">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('Arsipkan laporan ini?\n\nLaporan akan dihapus dari daftar aktif, tetapi data temuan dan tindak lanjut tetap tersimpan di Laporan Rekapan.\n\nLanjutkan?');"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-600 transition cursor-pointer">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                        Arsipkan Laporan
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- STATUS ALUR PENGAJUAN / PERSETUJUAN BANNER --}}
    @if($pemeriksaan->status_pengajuan === 'DRAFT')
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-xs flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
            <div class="flex min-w-0 items-start gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h4 class="min-w-0 text-xs font-bold text-amber-900 tracking-wider">Status pemeriksaan: Draft</h4>
                        <span class="shrink-0 px-2 py-0.5 rounded-md bg-amber-200 text-amber-900 text-xs font-bold">Belum Diajukan</span>
                    </div>
                    <p class="text-xs text-amber-800 mt-0.5">
                        Data {{ $isLhpOnlySubmission ? 'LHP' : 'pemeriksaan' }} ini masih berstatus <strong>Draft</strong> dan belum diajukan ke Super Admin. Silakan periksa kembali data Anda, lalu ajukan agar dapat ditinjau dan diverifikasi.
                    </p>
                </div>
            </div>
            @if(auth()->user()->isAdminSumber() || auth()->user()->isSuperAdmin())
                <form method="POST" action="{{ route('pemeriksaan.ajukan', $pemeriksaan) }}" class="flex-shrink-0"
                      data-confirm-variant="submit"
                      data-confirm-title="Ajukan untuk persetujuan?"
                      data-confirm-message="{{ 'Ajukan ' . $submissionLabel . ' ' . $reviewIdentifier . ' ke Super Admin untuk ditinjau?' }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 hover:bg-primary-700 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-primary-500/20 transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        <span>Ajukan ke Super Admin</span>
                    </button>
                </form>
            @endif
        </div>
    @elseif($pemeriksaan->status_pengajuan === 'MENUNGGU PERSETUJUAN')
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 shadow-xs flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
            <div class="flex min-w-0 items-start gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h4 class="min-w-0 text-xs font-bold text-blue-900 tracking-wider">Menunggu Persetujuan Super Admin</h4>
                        <span class="shrink-0 px-2 py-0.5 rounded-md bg-blue-200 text-blue-900 text-xs font-bold">Sedang Ditinjau</span>
                    </div>
                    <p class="text-xs text-blue-800 mt-0.5">
                        Pengajuan {{ $isLhpOnlySubmission ? 'LHP' : 'pemeriksaan' }} telah dikirim oleh <strong>{{ $pemeriksaan->creator?->official_name ?? $pemeriksaan->creator?->name }}</strong> ({{ $pemeriksaan->sumber_pemeriksaan }}).
                        @if(auth()->user()->isSuperAdmin())
                            Silakan lakukan verifikasi: Anda dapat menyetujui, meminta revisi, atau menolak pengajuan ini.
                        @else
                            Anda dapat membatalkan pengajuan ini jika masih ingin memperbaiki data.
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 xl:flex-shrink-0">
                @if(auth()->user()->isSuperAdmin())
                    {{-- Tombol Setujui --}}
                    <form method="POST" action="{{ route('pengajuan.pemeriksaan.approve', $pemeriksaan) }}"
                          data-confirm-variant="approval"
                          data-confirm-title="Setujui pengajuan ini?"
                          data-confirm-message="{{ 'Setujui ' . $submissionLabel . ' ' . $reviewIdentifier . '? Data akan tersedia untuk ditindaklanjuti.' }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-xs font-bold text-white shadow-sm transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>{{ $isLhpOnlySubmission ? 'Setujui Pengajuan LHP' : 'Setujui Pemeriksaan' }}</span>
                        </button>
                    </form>

                    {{-- Tombol Minta Revisi --}}
                    <button type="button" onclick="openReviewModal('pemeriksaan', {{ $pemeriksaan->id }}, @js($reviewIdentifier))"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span>Minta Revisi</span>
                    </button>

                @elseif(auth()->user()->isAdminSumber())
                    <form method="POST" action="{{ route('pemeriksaan.batal-ajukan', $pemeriksaan) }}"
                          data-confirm-variant="cancel"
                          data-confirm-title="Batalkan pengajuan?"
                          data-confirm-message="{{ 'Batalkan pengajuan ' . $submissionLabel . ' ' . $reviewIdentifier . ' dan kembalikan statusnya ke Draft?' }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-amber-300 bg-white hover:bg-amber-50 px-4 py-2 text-xs font-bold text-amber-800 shadow-sm transition cursor-pointer">
                            <span>Batalkan Pengajuan</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @elseif(in_array($pemeriksaan->status_pengajuan, ['PERLU REVISI', 'DITOLAK'], true))
        <div class="rounded-xl border border-orange-200 bg-orange-50 p-5 shadow-xs flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
            <div class="flex min-w-0 items-start gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h4 class="min-w-0 text-xs font-bold text-orange-900 tracking-wider">Perlu Revisi</h4>
                        <span class="shrink-0 px-2 py-0.5 rounded-md bg-orange-200 text-orange-900 text-xs font-bold">Catatan Verifikator</span>
                    </div>
                    <p class="text-xs text-orange-800 mt-1">
                        <strong>Catatan Super Admin:</strong> {{ $pemeriksaan->catatan_persetujuan ?: 'Silakan periksa dan perbaiki data sesuai instruksi.' }}
                    </p>
                </div>
            </div>
            @if(auth()->user()->isAdminSumber() || auth()->user()->isSuperAdmin())
                <div class="flex flex-wrap items-center gap-2 xl:flex-shrink-0">
                    <a href="{{ $isLhpOnlySubmission && $pemeriksaan->lhps->isNotEmpty() ? route('pemeriksaan.lhp.show', [$pemeriksaan, $pemeriksaan->lhps->first()]) : ($isLhpOnlySubmission ? route('lhp.index') : route('pemeriksaan.edit', $pemeriksaan)) }}"
                       class="inline-flex items-center gap-1.5 rounded-xl border border-orange-300 bg-white hover:bg-orange-50 px-4 py-2 text-xs font-bold text-orange-800 shadow-sm transition cursor-pointer">
                        {{ $isLhpOnlySubmission ? 'Buka Data LHP' : 'Edit Data' }}
                    </a>
                    <form method="POST" action="{{ route('pemeriksaan.ajukan', $pemeriksaan) }}"
                          data-confirm-variant="submit"
                          data-confirm-title="Ajukan kembali untuk persetujuan?"
                          data-confirm-message="{{ 'Ajukan kembali ' . $submissionLabel . ' ' . $reviewIdentifier . ' setelah diperbaiki?' }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-primary-600 hover:bg-primary-700 px-4 py-2 text-xs font-bold text-white shadow-sm transition cursor-pointer">
                            Ajukan Kembali
                        </button>
                    </form>
                </div>
            @endif
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- BAGIAN 1: INFORMASI SURAT PERINTAH TUGAS (SPT)                            --}}
    {{-- ========================================================================= --}}
    @if($isLhpOnlySubmission)
        <div class="rounded-2xl border border-gray-200/90 bg-white p-5 shadow-sm sm:p-6 lg:p-7">
            <div class="flex flex-wrap items-center gap-2">
                <x-sumber-badge :sumber="$pemeriksaan->sumber_pemeriksaan" size="md" />
                <span class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700">
                    Tahun Anggaran {{ $pemeriksaan->tahun }}
                </span>
            </div>
            <h2 class="mt-3 text-xl font-extrabold text-gray-900">Pengajuan Laporan Hasil Pemeriksaan</h2>
            <p class="mt-1 text-xs text-gray-600">Diajukan oleh {{ $pemeriksaan->creator?->official_name ?? $pemeriksaan->creator?->name ?? 'Tidak diketahui' }}. Rincian nomor LHP, target OPD, dan temuan tersedia pada daftar LHP di bawah ini.</p>
        </div>
    @else
    <div class="rounded-2xl border border-gray-200/90 bg-white p-5 shadow-sm space-y-5 sm:p-6 lg:p-7">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between border-b border-gray-100 pb-5">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-sumber-badge :sumber="$pemeriksaan->sumber_pemeriksaan" size="md" />
                    <span class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700">
                        Tahun Anggaran {{ $pemeriksaan->tahun }}
                    </span>
                </div>
                <h2 class="mt-3 text-xl font-extrabold text-gray-900 leading-snug">
                    {{ $pemeriksaan->judul ?: ("Surat Perintah Tugas Nomor " . $pemeriksaan->nomor_spt) }}
                </h2>
                <p class="text-xs text-gray-500 font-medium mt-1">Surat Perintah Tugas Pengawasan dan Pemeriksaan Inspektorat</p>
            </div>

            <div class="flex flex-col items-start sm:items-end flex-shrink-0">
                <span class="text-xs text-gray-500 font-bold  tracking-wider">Status Penyelesaian</span>
                @if($pemeriksaan->isFullyResolved())
                    <span class="mt-1 inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3.5 py-1 text-xs font-bold text-emerald-800">
                        Tuntas
                    </span>
                @else
                    <span class="mt-1 inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3.5 py-1 text-xs font-bold text-amber-800">
                        Dalam Pemantauan
                    </span>
                @endif
            </div>
        </div>

        {{-- Detail Grid SPT (Bebas OPD) --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            {{-- Nomor SPT --}}
            <div class="rounded-2xl bg-gray-50/80 p-4 border border-gray-100 space-y-1">
                <p class="text-xs font-bold  tracking-wider text-gray-500">Nomor SPT</p>
                <p class="text-sm font-bold text-gray-900 break-words">{{ $pemeriksaan->nomor_spt ?? '-' }}</p>
            </div>

            {{-- Tanggal SPT --}}
            <div class="rounded-2xl bg-gray-50/80 p-4 border border-gray-100 space-y-1">
                <p class="text-xs font-bold  tracking-wider text-gray-500">Tanggal SPT</p>
                <p class="text-sm font-bold text-gray-900">
                    {{ $pemeriksaan->tanggal_spt ? $pemeriksaan->tanggal_spt->translatedFormat('d F Y') : '-' }}
                </p>
            </div>

            {{-- Nama Tim --}}
            <div class="rounded-2xl bg-gray-50/80 p-4 border border-gray-100 space-y-1">
                <p class="text-xs font-bold  tracking-wider text-gray-500">Nama Tim</p>
                <p class="text-sm font-bold text-primary-900">
                    {{ $pemeriksaan->nama_tim ?? 'Tim Pemeriksa Inspektorat' }}
                </p>
            </div>

            {{-- Rentang Waktu Pemeriksaan --}}
            <div class="rounded-2xl bg-gray-50/80 p-4 border border-gray-100 space-y-1">
                <p class="text-xs font-bold  tracking-wider text-gray-500">Tanggal Mulai Pemeriksaan</p>
                <p class="text-sm font-bold text-gray-900">
                    {{ $pemeriksaan->tanggal_mulai?->translatedFormat('d F Y') ?? '-' }}
                </p>
            </div>

            <div class="rounded-2xl bg-gray-50/80 p-4 border border-gray-100 space-y-1">
                <p class="text-xs font-bold  tracking-wider text-gray-500">Tanggal Selesai Pemeriksaan</p>
                <p class="text-sm font-bold text-gray-900">
                    {{ $pemeriksaan->tanggal_selesai?->translatedFormat('d F Y') ?? '-' }}
                </p>
            </div>

            {{-- Berkas Dokumen SPT --}}
            <div class="rounded-2xl bg-gray-50/80 p-4 border border-gray-100 space-y-1">
                <p class="text-xs font-bold  tracking-wider text-gray-500">Dokumen SPT</p>
                @if($pemeriksaan->dokumen_spt)
                    <div class="flex flex-wrap items-center justify-between gap-2 pt-0.5">
                        <span class="min-w-0 flex-1 text-xs font-semibold text-gray-700 [overflow-wrap:anywhere]" title="{{ $pemeriksaan->nama_dokumen_spt_asli }}">
                            {{ $pemeriksaan->nama_dokumen_spt_asli ?? 'Berkas-SPT.pdf' }}
                        </span>
                        <a href="{{ route('pemeriksaan.download-spt', $pemeriksaan) }}" target="_blank"
                           class="inline-flex shrink-0 items-center gap-1 rounded-xl bg-primary-600 px-3 py-1 text-xs font-bold text-white shadow-sm hover:bg-primary-700 transition">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Lihat
                        </a>
                    </div>
                @else
                    <p class="text-xs text-gray-500 italic pt-1">Belum ada berkas</p>
                @endif
            </div>
        </div>

        {{-- Identitas Penginput Data --}}
        <div class="rounded-xl border border-primary-100 bg-primary-50/30 p-3.5 sm:p-4">
            <div class="flex items-start gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-primary-100 text-primary-700 flex-shrink-0">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div class="text-xs space-y-0.5">
                    <p class="font-bold text-gray-800">Pencatatan Administrasi SPT</p>
                    <p class="text-gray-600">
                        Diinput oleh: <strong>{{ $pemeriksaan->creator?->name ?? 'Sistem' }}</strong>
                        @if($pemeriksaan->creator?->role)
                            ({{ $pemeriksaan->creator->role->display_name }})
                        @endif
                        • Tanggal: <strong>{{ $pemeriksaan->created_at->translatedFormat('d F Y, H:i') }} WITA</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- RINGKASAN & KETERANGAN TEMUAN PADA PEMERIKSAAN INI                         --}}
    {{-- ========================================================================= --}}
    @php
        $administrasiCount = $pemeriksaan->temuans->where('ada_unsur_administrasi', true)->count();
        $hasAdministrativeFindings = $administrasiCount > 0;
        $hasLossFindings = $pemeriksaan->has_kerugian;
    @endphp
    @if($hasLossFindings || $hasAdministrativeFindings)
        <div class="rounded-2xl border {{ $hasLossFindings ? 'border-amber-200/90' : 'border-blue-200/90' }} bg-white p-5 shadow-sm space-y-5 sm:p-6 lg:p-7">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4">
                <div class="flex items-center gap-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">
                            {{ $hasAdministrativeFindings && $hasLossFindings ? 'Keterangan Temuan Administrasi & Kerugian' : ($hasAdministrativeFindings ? 'Keterangan Temuan Administrasi' : 'Keterangan Temuan Kerugian') }}
                        </h3>
                        <p class="text-xs text-gray-500 font-medium">
                            @if($hasAdministrativeFindings && $hasLossFindings)
                                Ringkasan unsur temuan administrasi dan kerugian pada pemeriksaan ini
                            @elseif($hasAdministrativeFindings)
                                Ringkasan temuan administrasi pada pemeriksaan ini
                            @else
                                Ringkasan temuan kerugian pada pemeriksaan ini
                            @endif
                        </p>
                    </div>
                </div>

                @if($hasLossFindings)
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 font-bold ">Total Kerugian:</span>
                        <span class="px-3 py-1 rounded-xl bg-amber-600 text-white font-bold text-xs shadow-xs">
                            {{ $pemeriksaan->formatted_total_kerugian }}
                        </span>
                    </div>
                @endif
            </div>

            {{-- Alert Jika Terdapat Temuan Kerugian yang Belum Ditentukan Sub Kategorinya --}}
            @if($hasLossFindings && $pemeriksaan->kerugian_belum_dikategorikan_count > 0)
                <div class="rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-xs space-y-1">
                    <div class="flex items-start gap-3">
                        <div>
                            <p class="font-extrabold text-rose-950">
                                Perhatian: Dari total kerugian {{ $pemeriksaan->formatted_total_kerugian }}, terdapat nominal {{ $pemeriksaan->formatted_total_kerugian_belum_dikategorikan }} ({{ $pemeriksaan->kerugian_belum_dikategorikan_count }} temuan) yang belum ditentukan sub kategorinya (Internal atau Eksternal).
                            </p>
                            <p class="text-rose-700 text-xs leading-relaxed mt-0.5">
                                Nominal tersebut belum terhitung di kartu Kerugian Internal maupun Eksternal di bawah ini. Anda dapat langsung memilih sub kategori pada masing-masing kartu temuan di bawah ini atau melalui menu Edit Pemeriksaan.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Ringkasan Temuan Administrasi dan Sub Kategori Kerugian --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div class="p-4 rounded-2xl border {{ $hasAdministrativeFindings ? 'border-blue-200 bg-blue-50/40 shadow-xs' : 'border-gray-200/60 bg-gray-50/50 opacity-60' }} space-y-2">
                    <div class="flex items-center justify-between gap-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-blue-100 text-blue-900 border border-blue-200">
                            Temuan Administrasi
                        </span>
                        <span class="text-xs font-bold text-blue-950">{{ $administrasiCount }} temuan</span>
                    </div>
                    <p class="text-xs text-gray-700 leading-relaxed">
                        <strong class="text-blue-950">Keterangan:</strong> Temuan terkait ketidakpatuhan terhadap ketentuan, kelemahan pengendalian, atau ketidaktertiban proses dan pencatatan administrasi. Rincian uraian dan rekomendasi tersedia pada daftar temuan di bagian LHP.
                    </p>
                </div>

                @if($hasLossFindings)
                    {{-- Sub Kategori Internal --}}
                    <div class="p-4 rounded-2xl border {{ $pemeriksaan->kerugian_internal_count > 0 ? 'border-primary-200 bg-primary-50/40 shadow-xs' : 'border-gray-200/60 bg-gray-50/50 opacity-60' }} space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-primary-100 text-primary-900 border border-primary-200">
                                Kerugian Internal
                            </span>
                            <span class="text-xs font-bold text-primary-950">
                                {{ $pemeriksaan->kerugian_internal_count > 0 ? $pemeriksaan->formatted_total_kerugian_internal : 'Rp 0' }} ({{ $pemeriksaan->kerugian_internal_count }} temuan)
                            </span>
                        </div>
                        <p class="text-xs text-gray-700 leading-relaxed">
                            <strong class="text-primary-950">Keterangan:</strong> Kerugian yang ditemukan di dalam kantor / OPD / instansi pemerintah daerah. Bersumber dari ketekoran kas, kelebihan pembayaran internal, atau kekurangan pertanggungjawaban di lingkungan kantor.
                        </p>
                    </div>

                    {{-- Sub Kategori Eksternal --}}
                    <div class="p-4 rounded-2xl border {{ $pemeriksaan->kerugian_eksternal_count > 0 ? 'border-amber-200 bg-amber-50/40 shadow-xs' : 'border-gray-200/60 bg-gray-50/50 opacity-60' }} space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-amber-100 text-amber-900 border border-amber-200">
                                Kerugian Eksternal
                            </span>
                            <span class="text-xs font-bold text-amber-950">
                                {{ $pemeriksaan->kerugian_eksternal_count > 0 ? $pemeriksaan->formatted_total_kerugian_eksternal : 'Rp 0' }} ({{ $pemeriksaan->kerugian_eksternal_count }} temuan)
                            </span>
                        </div>
                        <p class="text-xs text-gray-700 leading-relaxed">
                            <strong class="text-amber-950">Keterangan:</strong> Kerugian yang ditemukan pada pihak ketiga / rekanan / kontraktor / penyedia barang &amp; jasa. Bersumber dari kekurangan volume pekerjaan, denda keterlambatan, atau kelebihan pembayaran kepada pihak ketiga.
                        </p>
                    </div>

                    @if($pemeriksaan->kerugian_belum_dikategorikan_count > 0)
                        {{-- Belum Dikategorikan --}}
                        <div class="p-4 rounded-2xl border border-rose-300 bg-rose-50/50 shadow-xs space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-rose-100 text-rose-900 border border-rose-300">
                                    Belum Dikategorikan
                                </span>
                                <span class="text-xs font-bold text-rose-950">
                                    {{ $pemeriksaan->formatted_total_kerugian_belum_dikategorikan }} ({{ $pemeriksaan->kerugian_belum_dikategorikan_count }} temuan)
                                </span>
                            </div>
                            <p class="text-xs text-rose-900 leading-relaxed">
                                <strong>Keterangan:</strong> Temuan kerugian ini belum ditentukan apakah bersumber dari <em>Internal Kantor</em> atau <em>Pihak Ketiga (Eksternal)</em>. Silakan tentukan pada kartu temuan di bawah.
                            </p>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- BAGIAN 2: TIM PEMERIKSA (MASTER DATA PEGAWAI INSPEKTORAT)                 --}}
    {{-- ========================================================================= --}}
    @if(! $isLhpOnlySubmission)
    <div class="rounded-2xl border border-gray-200/90 bg-white p-5 shadow-sm space-y-5 sm:p-6 lg:p-7">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-primary-100 text-primary-700 shadow-xs">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Tim Pemeriksa ({{ $pemeriksaan->timPemeriksas->count() }})</h3>
                    <p class="text-xs text-gray-500 font-medium">Susunan tim pengawasan dari Master Data Pegawai Inspektorat</p>
                </div>
            </div>

            @if(!auth()->user()->isPemantau() && !auth()->user()->isOpd())
                <button type="button" onclick="toggleModal('modal-tambah-tim')"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-primary-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-primary-700 transition cursor-pointer">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Anggota Tim
                </button>
            @endif
        </div>

        {{-- Daftar Anggota Tim --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($pemeriksaan->timPemeriksas as $tim)
                <div class="flex items-center justify-between p-4 rounded-2xl bg-gray-50/70 border border-gray-200/80 hover:bg-white hover:border-primary-300 transition">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-600 text-white font-bold text-sm shadow-xs">
                            {{ strtoupper(substr($tim->pegawai->nama, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-xs font-extrabold text-gray-900">{{ $tim->pegawai->nama }}</p>
                            <p class="text-xs text-gray-500 font-medium">NIP. {{ $tim->pegawai->nip ?? '-' }}</p>
                            <p class="text-xs text-primary-700 font-semibold mt-0.5">{{ $tim->pegawai->jabatan }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @php $peranBadge = $tim->peran_badge; @endphp
                        <span class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-bold {{ $peranBadge['class'] }}">
                            {{ $tim->peran_tim }}
                        </span>

                        @if(!auth()->user()->isPemantau() && !auth()->user()->isOpd() && $tim->peran_tim !== 'Penanggung Jawab')
                            <form method="POST" action="{{ route('pemeriksaan.tim.destroy', [$pemeriksaan, $tim]) }}"
                                  onsubmit="return confirm('Hapus {{ $tim->pegawai->nama }} dari susunan tim?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg p-1.5 text-gray-500 hover:text-state-danger hover:bg-red-50 transition cursor-pointer" title="Hapus Anggota">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full py-8 text-center text-xs text-gray-500 italic border border-dashed border-gray-200 rounded-2xl">
                    Belum ada anggota tim pemeriksa yang ditambahkan ke SPT ini.
                </div>
            @endforelse
        </div>
    </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- BAGIAN 3: LAPORAN HASIL PEMERIKSAAN (LHP) & TEMUAN DI BAWAHNYA            --}}
    {{-- ========================================================================= --}}
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-gray-900  tracking-tight flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Laporan Hasil Pemeriksaan (LHP) ({{ $pemeriksaan->lhps->count() }})
                </h3>
                <p class="text-xs text-gray-500 font-medium">1 SPT dapat menaungi banyak LHP dengan target multi-OPD dan daftar temuan masing-masing</p>
            </div>
        </div>



        {{-- Daftar LHP Card Berjenjang --}}
        @forelse($pemeriksaan->lhps as $lhpIndex => $lhp)
            @php
                $lhpBadge = $lhp->status_penyelesaian_badge;
                $temuansInLhp = $lhp->temuans;
                $kerugianCount = $temuansInLhp->where('ada_unsur_kerugian', true)->count();
                $lhpAdministrasiTemuans = $temuansInLhp->where('ada_unsur_administrasi', true);
                $administrasiCount = $lhpAdministrasiTemuans->count();
            @endphp
            <div class="bg-white rounded-2xl border border-gray-200/90 shadow-sm overflow-hidden space-y-5 p-5 sm:p-6 transition hover:border-primary-300">
                {{-- LHP Header Bar --}}
                <div class="flex flex-col gap-4 border-b border-gray-100 pb-5">
                    <div class="min-w-0 space-y-2">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="px-3 py-1 rounded-xl text-xs font-bold bg-primary-600 text-white shadow-xs">
                                LHP #{{ $lhpIndex + 1 }}
                            </span>
                            <span class="text-sm font-bold text-gray-900">{{ $lhp->nomor_lhp }}</span>
                            <span class="text-xs text-gray-500 font-medium">• Tanggal: {{ $lhp->tanggal_lhp ? $lhp->tanggal_lhp->translatedFormat('d F Y') : '-' }}</span>
                        </div>

                        <h4 class="text-base font-bold text-gray-900 leading-snug">{{ $lhp->judul_lhp }}</h4>

                        {{-- OPD Target Multi-Select Badges --}}
                        <div class="flex flex-wrap items-center gap-1.5 pt-1">
                            <span class="text-xs font-bold text-gray-500  tracking-wider mr-1">OPD Target:</span>
                            @forelse($lhp->opds as $targetOpd)
                                <span class="inline-flex items-center gap-1 rounded-lg bg-primary-50 border border-primary-200/80 px-2.5 py-1 text-xs font-bold text-primary-900 shadow-2xs">
                                    [{{ $targetOpd->kode }}] {{ $targetOpd->nama }}
                                </span>
                            @empty
                                <span class="text-xs text-gray-500 italic">Belum ada OPD yang ditautkan</span>
                            @endforelse
                        </div>

                        <div class="flex flex-wrap items-center gap-2 pt-1" aria-label="Ringkasan jumlah temuan">
                            <span class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                {{ $temuansInLhp->count() }} Temuan
                            </span>
                            <span class="inline-flex items-center rounded-lg border border-primary-200 bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-800">
                                {{ $administrasiCount }} Administrasi
                            </span>
                            <span class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                {{ $kerugianCount }} Kerugian
                            </span>
                        </div>

                        @if($lhp->ringkasan)
                            <div class="mt-2 rounded-xl border border-gray-100 bg-gray-50 p-3.5">
                                <p class="mb-1 text-xs font-bold  tracking-wider text-gray-500">Ringkasan LHP</p>
                                <p class="text-sm leading-relaxed text-gray-700 whitespace-pre-line">{{ $lhp->ringkasan }}</p>
                            </div>
                        @endif

                        {{-- Ringkasan Temuan Kerugian pada LHP Ini --}}
                        @php
                            $lhpKerugianTemuans = $temuansInLhp->where('ada_unsur_kerugian', true);
                            $totalLhpKerugian = $lhpKerugianTemuans->sum('nilai_kerugian');
                            $internalCount = $lhpKerugianTemuans->where('jenis_kerugian', 'Internal')->count();
                            $eksternalCount = $lhpKerugianTemuans->where('jenis_kerugian', 'Eksternal')->count();
                        @endphp
                        @if($lhpKerugianTemuans->isNotEmpty())
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-3.5 mt-3 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5 text-xs">
                                    <span class="px-2.5 py-1 rounded-lg bg-amber-600 text-white font-bold text-xs  tracking-wider shadow-2xs">
                                        Temuan Kerugian
                                    </span>
                                    <div>
                                        <span class="text-xs font-bold text-gray-500 block ">Total Nilai Kerugian LHP Ini</span>
                                        <span class="font-bold text-amber-950 text-sm">
                                            Rp {{ number_format($totalLhpKerugian, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    @if($internalCount > 0)
                                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-primary-100/90 text-primary-900 px-3 py-1 text-xs font-bold border border-primary-200 shadow-2xs" title="Kerugian yang ditemukan di dalam kantor / OPD">
                                            Internal: {{ $internalCount }} temuan
                                        </span>
                                    @endif
                                    @if($eksternalCount > 0)
                                        <span class="inline-flex items-center gap-1.5 rounded-xl bg-amber-100/90 text-amber-900 px-3 py-1 text-xs font-bold border border-amber-200 shadow-2xs" title="Kerugian yang ditemukan pada pihak ketiga">
                                            Eksternal: {{ $eksternalCount }} temuan
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($lhpAdministrasiTemuans->isNotEmpty())
                            <div class="rounded-xl border border-blue-200 bg-blue-50 p-3.5 mt-3 space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex items-center gap-2.5 text-xs">
                                        <span class="px-2.5 py-1 rounded-lg bg-blue-600 text-white font-bold text-xs  tracking-wider shadow-2xs">
                                            Temuan Administrasi
                                        </span>
                                        <div>
                                            <span class="text-xs font-bold text-gray-500 block ">Jumlah Temuan Administrasi LHP Ini</span>
                                            <span class="font-bold text-blue-950 text-sm">{{ $administrasiCount }} temuan</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-2 border-t border-blue-200 pt-2.5">
                                    <span class="text-xs font-bold text-blue-900 block  tracking-wider">Keterangan Temuan Administrasi</span>
                                    @foreach($lhpAdministrasiTemuans as $administrasiTemuan)
                                        <div class="text-xs text-blue-950 leading-relaxed {{ !$loop->last ? 'border-b border-blue-100 pb-2' : '' }}">
                                            <p class="font-semibold">{{ $administrasiTemuan->judul_temuan ?: 'Temuan Administrasi #' . $loop->iteration }}</p>
                                            <p class="mt-0.5 whitespace-pre-line">{{ $administrasiTemuan->uraian_administrasi ?: $administrasiTemuan->uraian_temuan ?: '-' }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Actions & Status LHP --}}
                    <div class="flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500 font-bold  tracking-wider block">Status LHP:</span>
                            <x-status-badge :badge="$lhpBadge" size="md" dot-class="w-1.5 h-1.5 mr-1.5" class="font-bold" />
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('pemeriksaan.lhp.show', [$pemeriksaan, $lhp]) }}"
                               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-primary-50 text-primary-700 hover:bg-primary-100 font-bold text-xs transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <span>Detail LHP</span>
                            </a>

                            @if($lhp->dokumen_lhp)
                                <a href="{{ route('pemeriksaan.lhp.download', [$pemeriksaan, $lhp]) }}" target="_blank"
                                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 font-bold text-xs transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    Unduh LHP
                                </a>
                            @endif

                            @if(!auth()->user()->isPemantau() && !auth()->user()->isOpd())
                                <form method="POST" action="{{ route('pemeriksaan.lhp.destroy', [$pemeriksaan, $lhp]) }}"
                                      onsubmit="return confirm('Hapus LHP {{ $lhp->nomor_lhp }} beserta seluruh temuannya?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-xl text-gray-500 hover:text-state-danger hover:bg-red-50 transition cursor-pointer" title="Hapus LHP">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- DAFTAR TEMUAN DI BAWAH LHP INI --}}
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h5 class="text-xs font-bold text-gray-800  tracking-wider flex items-center gap-1.5">
                            <span>Daftar Temuan &amp; Rekomendasi ({{ $temuansInLhp->count() }})</span>
                        </h5>

                        @if($pemeriksaan->status_pengajuan !== 'MENUNGGU PERSETUJUAN' && !auth()->user()->isPemantau() && !auth()->user()->isOpd() && (auth()->user()->isSuperAdmin() || auth()->user()->getSumberPemeriksaan() === $pemeriksaan->sumber_pemeriksaan))
                            <a href="{{ route('temuan.create', ['pemeriksaan_id' => $pemeriksaan->id, 'lhp_id' => $lhp->id]) }}"
                               class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-xs transition cursor-pointer">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Tambah Temuan
                            </a>
                        @endif
                    </div>

                    @forelse($temuansInLhp as $tIdx => $temuan)
                        @php
                            $isKerugian = $temuan->ada_unsur_kerugian;
                            $tStatusBadge = $temuan->status_penyelesaian_badge;
                        @endphp
                        <div class="rounded-2xl border {{ $isKerugian ? 'border-amber-200/90 bg-amber-50/20' : 'border-primary-200/90 bg-primary-50/20' }} p-5 space-y-4 transition hover:bg-white">
                            {{-- Header Temuan Card --}}
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b {{ $isKerugian ? 'border-amber-200/60' : 'border-primary-200/60' }} pb-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold {{ $isKerugian ? 'bg-amber-600 text-white' : 'bg-primary-600 text-white' }}">
                                        {{ $isKerugian ? 'Temuan Kerugian Daerah' : 'Temuan Administrasi #' . ($tIdx + 1) }}
                                    </span>
                                    @if($isKerugian)
                                        @if($temuan->kategori_kerugian)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-bold border {{ $temuan->kategori_kerugian === 'Kerugian Negara' ? 'bg-primary-100 text-primary-700 border-primary-200' : 'bg-teal-100 text-teal-700 border-teal-200' }}">
                                                {{ $temuan->kategori_kerugian }}
                                            </span>
                                        @endif
                                        @php $kgInfo = $temuan->jenis_kerugian_info; @endphp
                                        @if($temuan->jenis_kerugian)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-bold border {{ $kgInfo['badge_class'] ?? 'bg-amber-100 text-amber-800 border-amber-200' }}" title="{{ $kgInfo['keterangan'] ?? '' }}">
                                                {{ $kgInfo['label'] }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-bold border bg-rose-100 text-rose-800 border-rose-300" title="Sub kategori belum ditentukan (Internal atau Eksternal)">
                                                Belum Dikategorikan
                                            </span>
                                        @endif
                                    @endif
                                    <h6 class="text-xs font-bold text-gray-900">{{ $temuan->judul_temuan }}</h6>
                                </div>

                                <div class="flex items-center gap-2">
                                    <x-status-badge :badge="$tStatusBadge" size="xs-wide" dot-class="w-1.5 h-1.5 mr-1.5" />

                                    @if(!auth()->user()->isPemantau() && !auth()->user()->isOpd() && (auth()->user()->isSuperAdmin() || auth()->user()->getSumberPemeriksaan() === $temuan->sumber_pemeriksaan))
                                        @if($pemeriksaan->status_pengajuan === 'DISETUJUI' || $temuan->status_pengajuan === 'DISETUJUI')
                                            @if($temuan->rekomendasis->isNotEmpty())
                                                <a href="{{ route('temuan.show', $temuan) }}"
                                                   class="inline-flex items-center gap-1 px-3 py-1 bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold rounded-lg shadow-xs transition">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                    </svg>
                                                    Tindak Lanjut per Rekomendasi
                                                </a>
                                            @else
                                                <button type="button"
                                                        onclick="openInputTlForTemuan({{ $temuan->id }}, '{{ addslashes($temuan->judul_temuan) }}', '{{ $isKerugian ? 'kerugian' : 'administrasi' }}', '{{ addslashes($temuan->uraian_temuan ?? '') }}', '{{ addslashes($temuan->rekomendasi ?? '') }}', '{{ addslashes($lhp->nomor_lhp) }}', '{{ addslashes($temuan->opd?->nama ?? ($pemeriksaan->opd?->nama ?? '-')) }}', '{{ addslashes($temuan->sumber_pemeriksaan) }}', '{{ addslashes($pemeriksaan->nomor_spt ?? '-') }}')"
                                                        class="inline-flex items-center gap-1 px-3 py-1 bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold rounded-lg shadow-xs transition cursor-pointer">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                    </svg>
                                                    Input Tindak Lanjut
                                                </button>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                            </div>

                            {{-- Uraian & Rekomendasi Grid --}}
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div class="bg-white p-3.5 rounded-xl border border-gray-200/70 space-y-1">
                                    <span class="text-xs font-bold text-gray-500  tracking-wider block">Uraian Temuan</span>
                                    <p class="text-sm text-gray-800 leading-relaxed whitespace-pre-line">{{ $temuan->uraian_temuan ?: '-' }}</p>
                                </div>
                                <div class="bg-primary-50/50 p-3.5 rounded-xl border border-primary-200/60 space-y-1">
                                    <span class="text-xs font-bold text-primary-700  tracking-wider block">Rekomendasi yang Diberikan</span>
                                    <p class="text-sm text-primary-900 leading-relaxed whitespace-pre-line font-medium">{{ $temuan->rekomendasi ?: '-' }}</p>
                                </div>
                            </div>

                            {{-- Penjelasan Keterangan Sub Kategori Kerugian --}}
                            @if($isKerugian)
                                @php $kgInfo = $temuan->jenis_kerugian_info; @endphp
                                <div class="rounded-xl border {{ ($temuan->jenis_kerugian === 'Internal') ? 'border-primary-200 bg-primary-50/70' : (($temuan->jenis_kerugian === 'Eksternal') ? 'border-amber-200 bg-amber-50/70' : 'border-gray-200 bg-gray-50') }} p-3.5 text-xs">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                                        <div class="flex items-start gap-2.5">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-bold  tracking-wider text-gray-500">Sub Kategori Kerugian:</span>
                                                    <span class="font-extrabold text-gray-900 text-xs">{{ $kgInfo['label'] ?? ($temuan->jenis_kerugian ?: 'Kerugian Daerah') }}</span>
                                                </div>
                                                <p class="text-xs text-gray-600 mt-0.5">
                                                    {{ $kgInfo['keterangan'] ?? 'Temuan kerugian finansial daerah/negara.' }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 self-start sm:self-auto flex-shrink-0">
                                            @if($temuan->jenis_kerugian === 'Internal')
                                                <span class="px-2.5 py-1 rounded-lg bg-primary-100 border border-primary-300 text-primary-900 font-bold text-xs flex items-center gap-1 shadow-2xs">
                                                    Temuan di Lingkungan Kantor / OPD
                                                </span>
                                            @elseif($temuan->jenis_kerugian === 'Eksternal')
                                                <span class="px-2.5 py-1 rounded-lg bg-amber-100 border border-amber-300 text-amber-900 font-bold text-xs flex items-center gap-1 shadow-2xs">
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

                            {{-- Progress Finansial (Khusus Temuan Kerugian) --}}
                            @if($isKerugian && $temuan->nilai_kerugian > 0)
                                <div class="p-3.5 rounded-xl bg-white border border-amber-200 space-y-2.5">
                                    <div class="flex items-center justify-between text-xs font-bold">
                                        <span class="text-amber-900">Progress Penyetoran Temuan Kerugian Ini</span>
                                        <span class="text-emerald-700 font-bold">{{ $temuan->progress_persen }}% Terpenuhi</span>
                                    </div>
                                    <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden border border-gray-200 p-0.5" role="progressbar" aria-label="Progress penyetoran untuk {{ $temuan->judul_temuan }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $temuan->progress_persen }}">
                                        <x-progress-indicator :value="$temuan->progress_persen" />
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1 text-xs">
                                        <div>
                                            <span class="text-gray-500 block text-xs font-bold ">Kategori Kerugian</span>
                                            <span class="font-bold {{ $temuan->jenis_kerugian === 'Internal' ? 'text-primary-800' : ($temuan->jenis_kerugian === 'Eksternal' ? 'text-amber-800' : 'text-rose-700') }}">
                                                {{ $temuan->jenis_kerugian ?: 'Belum Dikategorikan' }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 block text-xs font-bold ">Nilai Kerugian</span>
                                            <span class="font-bold text-gray-900">{{ $temuan->formatted_nilai_kerugian }}</span>
                                        </div>
                                        <div>
                                            <span class="text-emerald-700 block text-xs font-bold ">Disetor Terverifikasi</span>
                                            <span class="font-bold text-emerald-700">{{ $temuan->formatted_total_disetor }}</span>
                                        </div>
                                        <div>
                                            <span class="text-rose-700 block text-xs font-bold ">Sisa Kewajiban</span>
                                            <span class="font-bold text-rose-700">{{ $temuan->formatted_sisa_kerugian }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Riwayat Tindak Lanjut Temuan Ini --}}
                            @if($temuan->tindakLanjuts->isNotEmpty())
                                <div class="pt-2 border-t {{ $isKerugian ? 'border-amber-200/50' : 'border-primary-200/50' }} space-y-2">
                                    <span class="text-xs font-bold text-gray-500  tracking-wider block">
                                        Riwayat Tindak Lanjut Temuan ({{ $temuan->tindakLanjuts->count() }})
                                    </span>
                                    <div class="space-y-2">
                                        @foreach($temuan->tindakLanjuts as $tlIdx => $tl)
                                            <div class="bg-white p-3 rounded-xl border border-gray-200 text-xs space-y-2">
                                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 border-b border-gray-100 pb-1.5">
                                                    <span class="font-bold text-gray-800">
                                                        TL #{{ $temuan->tindakLanjuts->count() - $tlIdx }} • Tgl: {{ $tl->tanggal_tindak_lanjut ? $tl->tanggal_tindak_lanjut->format('d/m/Y') : '-' }} ({{ $tl->creator->name ?? 'User' }})
                                                    </span>
                                                    <div class="flex items-center gap-1.5">
                                                        @if($tl->status_verifikasi === 'SESUAI')
                                                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Sesuai</span>
                                                        @elseif($tl->status_verifikasi === 'BELUM SESUAI')
                                                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">Belum Sesuai</span>
                                                        @elseif($tl->status_verifikasi === 'TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH')
                                                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-800">Tidak Dapat TL</span>
                                                        @else
                                                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">Menunggu Verifikasi</span>
                                                        @endif

                                                        @if(auth()->user()->isSuperAdmin())
                                                            <button type="button"
                                                                    onclick="openVerifikasiModal({{ $temuan->id }}, {{ $tl->id }}, '{{ $tl->status_verifikasi }}', '{{ addslashes($tl->catatan_verifikasi ?? '') }}')"
                                                                    class="px-2 py-0.5 bg-primary-600 hover:bg-primary-700 text-white rounded text-xs font-bold cursor-pointer">
                                                                Verifikasi
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>

                                                <p class="text-gray-700 leading-relaxed">{{ $tl->uraian_tindak_lanjut }}</p>

                                                @if($tl->jumlah_setor > 0 || $tl->nomor_bukti_setor)
                                                    <div class="p-2 rounded-lg bg-emerald-50 text-xs text-emerald-900 font-medium flex flex-wrap gap-3">
                                                        <span>Setoran: <strong>{{ $tl->formatted_jumlah_setor }}</strong></span>
                                                        @if($tl->nomor_bukti_setor)
                                                            <span>No. STS: <strong>{{ $tl->nomor_bukti_setor }}</strong></span>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if($tl->dokumen_bukti)
                                                    <div>
                                                        <a href="{{ route('temuan.tindak-lanjut.download', [$temuan, $tl]) }}" class="inline-flex items-center text-xs font-bold text-primary-600 hover:underline">
                                                            Unduh Lampiran Bukti
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-gray-500 italic border border-dashed border-gray-200 rounded-xl bg-gray-50/50">
                            Belum ada temuan yang dicatat untuk LHP ini.
                        </div>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="p-12 text-center bg-white rounded-3xl border border-dashed border-gray-200 text-gray-500 space-y-3">
                <div class="w-12 h-12 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h4 class="text-sm font-bold text-gray-900">Belum Ada Dokumen LHP</h4>
                <p class="text-xs text-gray-500 max-w-sm mx-auto">SPT ini belum memiliki Laporan Hasil Pemeriksaan (LHP). Klik tombol di bawah untuk menambahkan LHP pertama.</p>
                @if(!auth()->user()->isPemantau() && !auth()->user()->isOpd())
                    <button type="button" onclick="toggleModal('modal-tambah-lhp')" class="inline-flex items-center gap-1 px-4 py-2 bg-primary-600 text-white rounded-xl text-xs font-bold hover:bg-primary-700 transition cursor-pointer">
                        + Tambah LHP Pertama
                    </button>
                @endif
            </div>
        @endforelse
    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODAL 1: INPUT TINDAK LANJUT TERPADU (ADAPTIF PER TEMUAN)                 --}}
{{-- ========================================================================= --}}
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
                        LHP: <strong id="modalTemuanLhpNo" class="text-gray-900">-</strong> • SPT: <strong id="modalTemuanSptNo" class="text-gray-900">-</strong>
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

{{-- ========================================================================= --}}
{{-- MODAL 2: VERIFIKASI TINDAK LANJUT (SUPER ADMIN)                           --}}
{{-- ========================================================================= --}}
<div id="verifikasiModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="pemeriksaan-verifikasi-modal-title" tabindex="-1">
    <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100 my-8">
        <div class="px-6 py-4 bg-primary-800 text-white flex items-center justify-between">
            <h3 id="pemeriksaan-verifikasi-modal-title" class="text-sm font-bold flex items-center gap-2">
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

{{-- ========================================================================= --}}
{{-- MODAL 3: TAMBAH ANGGOTA TIM PEMERIKSA                                     --}}
{{-- ========================================================================= --}}
<div id="modal-tambah-tim" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="modal-tambah-tim-title" tabindex="-1">
    <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100">
        <div class="px-6 py-4 bg-primary-600 text-white flex items-center justify-between">
            <h3 id="modal-tambah-tim-title" class="text-sm font-bold flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Tambah Anggota Tim Pemeriksa
            </h3>
            <button type="button" onclick="toggleModal('modal-tambah-tim')" aria-label="Tutup dialog tambah anggota tim" class="text-white/80 hover:text-white text-lg font-bold cursor-pointer">✕</button>
        </div>

        <form method="POST" action="{{ route('pemeriksaan.tim.store', $pemeriksaan) }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label for="pegawai_id" class="block text-xs font-bold text-gray-700 mb-1">Pilih Pegawai Inspektorat <span class="text-state-danger">*</span></label>
                <select id="pegawai_id" name="pegawai_id" required
                        class="w-full border bg-white px-3.5 py-2.5 text-xs text-gray-800 idds-form-control">
                    <option value="">-- Pilih Pegawai --</option>
                    @foreach($availablePegawais as $p)
                        <option value="{{ $p->id }}">{{ $p->nama }} — {{ $p->jabatan }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="peran_tim" class="block text-xs font-bold text-gray-700 mb-1">Peran dalam Tim <span class="text-state-danger">*</span></label>
                <select id="peran_tim" name="peran_tim" required
                        class="w-full border bg-white px-3.5 py-2.5 text-xs text-gray-800 idds-form-control">
                    <option value="Anggota Tim">Anggota Tim</option>
                    <option value="Ketua Tim">Ketua Tim</option>
                    <option value="Pengendali Teknis">Pengendali Teknis</option>
                    <option value="Asisten Penanggung Jawab">Asisten Penanggung Jawab</option>
                </select>
            </div>

            <div class="flex flex-col items-stretch justify-end gap-3 pt-3 border-t border-gray-100 sm:flex-row sm:items-center">
                <button type="button" onclick="toggleModal('modal-tambah-tim')" class="px-4 py-2 text-xs font-bold rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl bg-primary-600 hover:bg-primary-700 text-white shadow-md shadow-primary-500/20 transition cursor-pointer">
                    Simpan Anggota
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODAL 4: TAMBAH LHP BARU (MULTI-OPD TARGET & TEMUAN DINAMIS)              --}}
{{-- ========================================================================= --}}
<div id="modal-tambah-lhp" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="modal-tambah-lhp-title" tabindex="-1">
    <div class="relative w-full max-w-3xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100 my-8 max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 bg-primary-600 text-white flex items-center justify-between flex-shrink-0">
            <h3 id="modal-tambah-lhp-title" class="text-sm font-bold flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Tambah Laporan Hasil Pemeriksaan (LHP) Baru
            </h3>
            <button type="button" onclick="toggleModal('modal-tambah-lhp')" aria-label="Tutup dialog tambah LHP" class="text-white/80 hover:text-white text-lg font-bold cursor-pointer">✕</button>
        </div>

        <form method="POST" action="{{ route('pemeriksaan.lhp.store', $pemeriksaan) }}" enctype="multipart/form-data" class="p-6 space-y-5 overflow-y-auto flex-1">
            @csrf

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                {{-- Nomor LHP --}}
                <div>
                    <label for="modal-nomor-lhp" class="block text-xs font-bold text-gray-700 mb-1">Nomor LHP <span class="text-state-danger">*</span></label>
                    <input id="modal-nomor-lhp" type="text" name="nomor_lhp" required placeholder="Contoh: 700/01/LHP-REG/2026"
                           class="w-full border bg-white px-3.5 py-2 text-xs text-gray-800 idds-form-control">
                </div>

                {{-- Tanggal LHP --}}
                <div>
                    <label for="modal-tanggal-lhp" class="block text-xs font-bold text-gray-700 mb-1">Tanggal LHP <span class="text-state-danger">*</span></label>
                    <input id="modal-tanggal-lhp" type="date" name="tanggal_lhp" required value="{{ date('Y-m-d') }}"
                           class="w-full border bg-white px-3.5 py-2 text-xs text-gray-800 idds-form-control">
                </div>

                {{-- Judul LHP --}}
                <div class="md:col-span-2">
                    <label for="modal-judul-lhp" class="block text-xs font-bold text-gray-700 mb-1">Judul / Perihal LHP</label>
                    <input id="modal-judul-lhp" type="text" name="judul_lhp" placeholder="Contoh: LHP Kepatuhan Pengelolaan Anggaran"
                           class="w-full border bg-white px-3.5 py-2 text-xs text-gray-800 idds-form-control">
                </div>

                {{-- OPD Target (Multi-Select Searchable) --}}
                <div class="md:col-span-2 rounded-2xl border border-primary-200/80 bg-primary-50/40 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <p id="modal-opd-target-label" class="text-xs font-bold text-primary-900  tracking-wider">
                            Pilih OPD Target (Bisa Lebih Dari 1 OPD) <span class="text-state-danger">*</span>
                        </p>
                        <span id="modal-selected-opd-count" class="text-xs font-bold text-primary-700 bg-primary-100 px-2.5 py-0.5 rounded-full">0 OPD Terpilih</span>
                    </div>

                    <input type="text" oninput="filterModalOpdOptions(this)" aria-label="Cari OPD target"
                           placeholder="Cari nama atau kode instansi OPD..."
                           class="w-full border bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control">

                    <div class="max-h-36 overflow-y-auto space-y-1.5 p-2 bg-white rounded-xl border border-gray-200" role="group" aria-labelledby="modal-opd-target-label">
                        @foreach($allOpds as $o)
                            <label class="modal-opd-item flex items-center gap-2 p-1.5 rounded-lg hover:bg-primary-50 transition cursor-pointer select-none" data-search="{{ strtolower($o->kode . ' ' . $o->nama) }}">
                                <input type="checkbox" name="opd_ids[]" value="{{ $o->id }}" onchange="updateModalOpdSelection()"
                                       class="modal-opd-checkbox h-3.5 w-3.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span class="text-xs font-bold text-primary-900">[{{ $o->kode }}]</span>
                                <span class="text-xs text-gray-800">{{ $o->nama }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Uraian Singkat / Ringkasan --}}
                <div class="md:col-span-2">
                    <label for="modal-ringkasan-lhp" class="block text-xs font-bold text-gray-700 mb-1">Uraian Ringkas Hasil Pemeriksaan</label>
                    <textarea id="modal-ringkasan-lhp" name="ringkasan" rows="2" placeholder="Uraian pokok temuan atau evaluasi..."
                              class="w-full border bg-white px-3.5 py-2 text-xs text-gray-800 idds-form-control"></textarea>
                </div>

                {{-- Dokumen LHP --}}
                <div class="md:col-span-2">
                    <label for="modal-dokumen-lhp" class="block text-xs font-bold text-gray-700 mb-1">Unggah Dokumen LHP (PDF / DOC / DOCX)</label>
                    <input id="modal-dokumen-lhp" type="file" name="dokumen_lhp" accept=".pdf,.doc,.docx"
                           class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                </div>
            </div>

            <div class="flex flex-col items-stretch justify-end gap-3 pt-3 border-t border-gray-100 flex-shrink-0 sm:flex-row sm:items-center">
                <button type="button" onclick="toggleModal('modal-tambah-lhp')" class="px-4 py-2 text-xs font-bold rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl bg-primary-600 hover:bg-primary-700 text-white shadow-md shadow-primary-500/20 transition cursor-pointer">
                    Simpan LHP
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL REVIEW PENGAJUAN (REVISI / TOLAK) --}}
<div id="review-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="review-modal-title" tabindex="-1">
    <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100">
        <div id="review-modal-header" class="px-6 py-4 bg-orange-500 text-white flex items-center justify-between">
            <h3 id="review-modal-title" class="text-sm font-bold flex items-center gap-2">Review Pengajuan</h3>
            <button type="button" onclick="closeReviewModal()" aria-label="Tutup dialog review pengajuan" class="text-white/80 hover:text-white text-lg font-bold cursor-pointer">✕</button>
        </div>
        <form id="review-modal-form" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label id="review-modal-label" for="review-modal-note" class="block text-xs font-bold text-gray-700 mb-1">Catatan</label>
                <textarea id="review-modal-note" name="catatan_revisi" rows="4" required minlength="5" placeholder="Tuliskan catatan atau alasan di sini..."
                          class="w-full text-xs idds-form-control"></textarea>
            </div>
            <div class="flex flex-col items-stretch justify-end gap-3 pt-2 sm:flex-row sm:items-center">
                <button type="button" onclick="closeReviewModal()" class="px-4 py-2 text-xs font-bold rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 transition cursor-pointer">Batal</button>
                <button type="submit" id="review-modal-submit" class="px-5 py-2 text-xs font-bold rounded-xl bg-orange-500 hover:bg-orange-600 text-white shadow-md transition cursor-pointer">Kirim</button>
            </div>
        </form>
    </div>
</div>

<script>
    function updateModalOpdSelection() {
        const count = document.querySelectorAll('.modal-opd-checkbox:checked').length;
        const countBadge = document.getElementById('modal-selected-opd-count');
        if (countBadge) {
            countBadge.textContent = count + ' OPD Terpilih';
            if (count > 0) {
                countBadge.className = 'text-xs font-bold text-emerald-700 bg-emerald-100 px-2.5 py-0.5 rounded-full';
            } else {
                countBadge.className = 'text-xs font-bold text-primary-700 bg-primary-100 px-2.5 py-0.5 rounded-full';
            }
        }
    }

    function openReviewModal(type, id, identifier) {
        const modal = document.getElementById('review-modal');
        const form = document.getElementById('review-modal-form');
        const header = document.getElementById('review-modal-header');
        const title = document.getElementById('review-modal-title');
        const label = document.getElementById('review-modal-label');
        const submit = document.getElementById('review-modal-submit');

        form.action = `/pengajuan/${type}/${id}/revise`;
        header.className = 'px-6 py-4 bg-orange-500 text-white flex items-center justify-between';
        title.textContent = `Minta Revisi: ${identifier}`;
        label.textContent = 'Catatan Permintaan Revisi (Wajib Diisi)';
        submit.className = 'px-5 py-2 text-xs font-bold rounded-xl bg-orange-500 hover:bg-orange-600 text-white shadow-md transition';
        submit.textContent = 'Kirim Permintaan Revisi';

        modal.classList.remove('hidden');
    }

    function closeReviewModal() {
        document.getElementById('review-modal').classList.add('hidden');
    }
</script>
@endsection
