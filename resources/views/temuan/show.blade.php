@extends('layouts.app')

@section('title', 'Detail Temuan - ' . $temuan->judul_temuan)
@section('page-title', 'Detail Data Temuan')
@section('page-subtitle', 'Informasi lengkap temuan hasil pemeriksaan, tindak lanjut, dan verifikasi')

@section('content')
@php
    $labelKasTujuan = $temuan->kategori_kerugian === 'Kerugian Negara' ? 'Kas Negara' : 'Kas Daerah';
@endphp
<div class="mx-auto w-full min-w-0 max-w-5xl space-y-6">
    {{-- Top Action Bar --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-breadcrumbs :items="[
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Temuan', 'href' => route('temuan.index')],
            ['label' => $temuan->judul_temuan],
        ]" />
    </div>

    {{-- ALERT REVISI / PENOLAKAN --}}
    @if(in_array($temuan->status_pengajuan, ['PERLU REVISI', 'DITOLAK'], true))
        <div class="rounded-xl border border-orange-200 bg-orange-50 p-5 shadow-xs flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
            <div class="flex min-w-0 items-start gap-3">
                <div class="p-2.5 rounded-lg bg-orange-100 text-orange-700 font-semibold text-sm">Perhatian</div>
                <div>
                    <div class="flex items-center gap-2">
                        <h4 class="text-xs font-bold text-orange-900  tracking-wider">Perlu Revisi</h4>
                        <span class="px-2 py-0.5 rounded-md bg-orange-200 text-orange-900 text-xs font-bold">Catatan Verifikator</span>
                    </div>
                    <p class="text-xs text-orange-800 mt-1">
                        <strong>Catatan Super Admin:</strong> {{ $temuan->catatan_revisi ?: 'Silakan periksa dan perbaiki data temuan sesuai instruksi.' }}
                    </p>
                </div>
            </div>
            @if(auth()->user()->isAdminSumber() || auth()->user()->isSuperAdmin())
                <div class="flex flex-wrap items-center gap-2 xl:flex-shrink-0">
                    <a href="{{ route('temuan.edit', $temuan) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-orange-300 bg-white hover:bg-orange-50 px-4 py-2 text-xs font-bold text-orange-800 shadow-sm transition cursor-pointer">
                        Edit Temuan
                    </a>
                </div>
            @endif
        </div>
    @endif

    {{-- Main Header Card --}}
    <div class="rounded-2xl border border-gray-200/80 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between border-b border-gray-100 pb-5">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <x-sumber-badge :sumber="$temuan->sumber_pemeriksaan" />
                    <span class="inline-flex items-center rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-700">
                        Tahun {{ $temuan->tahun }}
                    </span>
                    @if($temuan->pemeriksaan && ! $temuan->pemeriksaan->is_lhp_only_submission)
                        <span class="inline-flex items-center rounded-lg bg-primary-50 border border-primary-200 px-2.5 py-1 text-xs font-bold text-primary-700">
                            SPT: {{ $temuan->pemeriksaan->nomor_spt ?? '-' }}
                        </span>
                    @endif
                    @if($temuan->lhp)
                        <span class="inline-flex items-center rounded-lg bg-sky-50 border border-sky-200 px-2.5 py-1 text-xs font-bold text-sky-700">
                            LHP: {{ $temuan->lhp->nomor_lhp }}
                        </span>
                    @endif
                </div>
                <h2 class="mt-3 text-xl font-bold text-gray-900">{{ $temuan->judul_temuan }}</h2>
                <p class="text-sm text-gray-600 mt-1">
                    Instansi OPD:
                    @if($temuan->opd)
                        @if(auth()->user()->isSuperAdmin())
                            <a href="{{ route('opd.show', $temuan->opd) }}" class="font-semibold text-primary-700 hover:underline">{{ $temuan->opd->nama }}</a>
                        @else
                            <span class="font-semibold text-gray-700">{{ $temuan->opd->nama }}</span>
                        @endif
                    @else
                        <span class="font-semibold text-gray-500">-</span>
                    @endif
                </p>
            </div>

                <div class="flex flex-col items-start gap-2 sm:items-end sm:flex-shrink-0">
                <div>
                    <span class="text-xs text-gray-500 block text-right">Status Penyelesaian</span>
                    @php $penyelesaian = $temuan->status_penyelesaian_badge; @endphp
                    <x-status-badge :badge="$penyelesaian" size="md" class="mt-0.5" />
                </div>
            </div>
        </div>

        {{-- Progress Recovery / Penyetoran Kerugian (PRD Seksi 15) --}}
        @if($temuan->ada_unsur_kerugian)
            <div class="mt-6 rounded-2xl border border-gray-200 bg-gray-50/70 p-5">
                <div class="flex min-w-0 flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span class="text-xs font-bold tracking-wider text-gray-500">Progress Penyetoran Kerugian ke {{ $labelKasTujuan }}</span>
                            @if($temuan->jenis_kerugian)
                                @php $kgInfo = $temuan->jenis_kerugian_info; @endphp
                                <span class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-0.5 text-xs font-bold {{ $kgInfo['badge_class'] }}" title="{{ $kgInfo['keterangan'] }}">
                                    <span>{{ $kgInfo['icon'] }}</span>
                                    {{ $kgInfo['label'] }}
                                </span>
                            @endif
                        </div>
                        <div class="mt-1 flex flex-wrap items-baseline gap-x-2 gap-y-1">
                            <span class="text-xl font-bold text-emerald-700">{{ $temuan->formatted_total_disetor }}</span>
                            <span class="text-xs text-gray-500">dari total {{ $temuan->formatted_nilai_kerugian }}</span>
                        </div>
                    </div>

                    <div class="text-left xl:text-right">
                        <span class="text-xs text-gray-500">Sisa Kerugian Belum Disetor</span>
                        <p class="text-base font-bold text-state-danger">{{ $temuan->formatted_sisa_kerugian }}</p>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="mt-3">
                    <div class="mb-1 flex flex-wrap items-center justify-between gap-x-3 gap-y-1 text-xs font-semibold text-gray-600">
                        <span>Penyelesaian</span>
                        <span>{{ $temuan->progress_persen }}%</span>
                    </div>
                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200" role="progressbar" aria-label="Penyelesaian temuan kerugian" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $temuan->progress_persen }}">
                        <x-progress-indicator :value="$temuan->progress_persen" />
                    </div>
                </div>
            </div>
        @endif

        {{-- Alasan Khusus (Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah - PRD Seksi 16) --}}
        @if($temuan->status_penyelesaian === 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah' && $temuan->alasan_khusus)
            <div class="mt-6 rounded-2xl border border-gray-300 bg-gray-50 p-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-200 text-gray-700 flex-shrink-0">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-900">Pertimbangan Hukum & Alasan Sah Tidak Dapat Ditindaklanjuti</h4>
                        <p class="mt-1 text-xs text-gray-800 whitespace-pre-line leading-relaxed">{{ $temuan->alasan_khusus }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Induk Pemeriksaan & LHP Reference --}}
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 bg-gray-50/70 p-4 rounded-xl border border-gray-100">
            <div>
                <span class="text-xs font-semibold tracking-wider text-gray-500">{{ $temuan->pemeriksaan?->is_lhp_only_submission ? 'Pengajuan LHP' : 'Pemeriksaan & SPT' }}</span>
                <p class="text-sm font-semibold text-gray-900 mt-0.5">
                    @if($temuan->pemeriksaan)
                        <a href="{{ route('pemeriksaan.show', $temuan->pemeriksaan) }}" class="text-primary-700 hover:underline">
                            @if($temuan->pemeriksaan->is_lhp_only_submission)
                                {{ $temuan->pemeriksaan->sumber_pemeriksaan }} — TA {{ $temuan->pemeriksaan->tahun }}
                            @else
                                SPT: {{ $temuan->pemeriksaan->nomor_spt }}
                            @endif
                        </a>
                    @else
                        <span class="text-gray-500 italic">Tidak dikaitkan ke SPT/Pemeriksaan</span>
                    @endif
                </p>
                @if($temuan->pemeriksaan?->judul)
                    <p class="text-xs text-gray-500 truncate">{{ $temuan->pemeriksaan->judul }}</p>
                @endif
            </div>

            <div>
                <span class="text-xs font-semibold  tracking-wider text-gray-500">Laporan Hasil Pemeriksaan (LHP)</span>
                @if($temuan->lhp && $temuan->pemeriksaan)
                    <p class="text-sm font-semibold text-gray-900 mt-0.5">
                        <a href="{{ route('pemeriksaan.lhp.show', ['pemeriksaan' => $temuan->pemeriksaan->getKey(), 'lhp' => $temuan->lhp->getKey()]) }}"
                           aria-label="Buka rincian LHP {{ $temuan->lhp->nomor_lhp }}{{ auth()->user()->isOpd() ? ' untuk OPD Anda' : '' }}"
                           class="text-primary-700 hover:underline">
                            {{ $temuan->lhp->nomor_lhp }}
                        </a>
                    </p>
                    <p class="text-xs text-gray-500">Tanggal: {{ $temuan->lhp->tanggal_lhp->format('d/m/Y') }}</p>
                @elseif($temuan->lhp)
                    <p class="text-sm font-semibold text-gray-900 mt-0.5">{{ $temuan->lhp->nomor_lhp }}</p>
                    <p class="text-xs text-gray-500">Tanggal: {{ $temuan->lhp->tanggal_lhp->format('d/m/Y') }}</p>
                @else
                    <p class="text-sm text-gray-500 mt-0.5 italic">Tidak dikaitkan ke LHP spesifik</p>
                @endif
            </div>
        </div>

        {{-- Unsur Administrasi & Kerugian Cards --}}
        <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
            {{-- Unsur Administrasi Card --}}
            <div class="rounded-2xl border {{ $temuan->ada_unsur_administrasi ? 'border-blue-200 bg-blue-50/30' : 'border-gray-100 bg-gray-50/40 opacity-60' }} p-5">
                <div class="flex items-center gap-2 border-b border-blue-100/60 pb-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900">Unsur Administrasi</h4>
                        <span class="text-xs font-semibold {{ $temuan->ada_unsur_administrasi ? 'text-blue-700' : 'text-gray-500' }}">
                            {{ $temuan->ada_unsur_administrasi ? 'Terdapat Unsur Administrasi' : 'Tidak Ada' }}
                        </span>
                    </div>
                </div>

                <div class="mt-3">
                    @if($temuan->ada_unsur_administrasi)
                        <p class="text-xs text-gray-700 whitespace-pre-line">{{ $temuan->uraian_administrasi ?: 'Tidak ada rincian catatan administrasi tambahan.' }}</p>
                    @else
                        <p class="text-xs text-gray-500 italic">Temuan ini tidak memiliki catatan administratif khusus.</p>
                    @endif
                </div>
            </div>

            {{-- Unsur Kerugian Card --}}
            <div class="rounded-2xl border {{ $temuan->ada_unsur_kerugian ? 'border-rose-200 bg-rose-50/30' : 'border-gray-100 bg-gray-50/40 opacity-60' }} p-5">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-rose-100/60 pb-3">
                    <div class="flex min-w-0 items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100 text-rose-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">Unsur Kerugian</h4>
                            <span class="text-xs font-semibold {{ $temuan->ada_unsur_kerugian ? 'text-rose-700' : 'text-gray-500' }}">
                                {{ $temuan->ada_unsur_kerugian ? 'Terdapat Kerugian Daerah/Negara' : 'Tidak Ada Kerugian' }}
                            </span>
                        </div>
                    </div>

                    @if($temuan->ada_unsur_kerugian)
                        <div class="ml-auto shrink-0 text-right">
                            <span class="block whitespace-nowrap text-base font-extrabold text-rose-700">
                                {{ $temuan->formatted_nilai_kerugian }}
                            </span>
                        </div>
                    @endif
                </div>

                @if($temuan->ada_unsur_kerugian && ($temuan->kategori_kerugian || $temuan->jenis_kerugian))
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        @if($temuan->kategori_kerugian)
                            <span class="inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-bold {{ $temuan->kategori_kerugian === 'Kerugian Negara' ? 'bg-primary-100 text-primary-700 border-primary-200' : 'bg-teal-100 text-teal-700 border-teal-200' }}">
                                {{ $temuan->kategori_kerugian }}
                            </span>
                        @endif
                        @if($temuan->jenis_kerugian)
                            @php $kgInfo = $temuan->jenis_kerugian_info; @endphp
                            <span class="inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-bold {{ $kgInfo['badge_class'] ?? 'bg-rose-100 text-rose-700' }}">
                                <span>{{ $kgInfo['icon'] ?? '??' }}</span>
                                {{ $kgInfo['label'] ?? $temuan->jenis_kerugian }}
                            </span>
                        @endif
                    </div>
                @endif

                <div class="mt-3 space-y-2">
                    @if($temuan->ada_unsur_kerugian)
                        @if($temuan->jenis_kerugian)
                            @php $kgInfo = $temuan->jenis_kerugian_info; @endphp
                            <div class="rounded-xl border {{ $temuan->jenis_kerugian === 'Internal' ? 'border-primary-200 bg-primary-50/80 text-primary-900' : 'border-amber-200 bg-amber-50/80 text-amber-900' }} p-3 text-xs">
                                <div class="mb-1 flex flex-wrap items-center justify-between gap-1.5 font-bold">
                                    <span class="flex items-center gap-1.5">
                                        <span>{{ $kgInfo['icon'] }}</span>
                                        <span>Sub Kategori: {{ $kgInfo['label'] }}</span>
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-xs  font-extrabold {{ $temuan->jenis_kerugian === 'Internal' ? 'bg-primary-200 text-primary-900' : 'bg-amber-200 text-amber-900' }}">
                                        {{ $kgInfo['lingkup'] }}
                                    </span>
                                </div>
                                <p class="text-xs leading-relaxed {{ $temuan->jenis_kerugian === 'Internal' ? 'text-primary-800' : 'text-amber-800' }}">
                                    <strong>Keterangan:</strong> {{ $kgInfo['keterangan'] }}
                                </p>
                            </div>
                        @else
                            <div class="rounded-xl border border-rose-300 bg-rose-50/90 p-3.5 text-xs space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-extrabold text-rose-900 flex items-center gap-1.5">
                                        Sub Kategori Belum Ditentukan
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-xs  font-bold bg-rose-200 text-rose-900">
                                        Perlu Ditentukan
                                    </span>
                                </div>
                                <p class="text-xs text-rose-800 leading-relaxed">
                                    Temuan ini memiliki nilai kerugian <strong>{{ $temuan->formatted_nilai_kerugian }}</strong>, namun belum dikategorikan apakah bersumber dari <strong>Internal</strong> (di dalam kantor/OPD) atau <strong>Eksternal</strong> (pihak ketiga/rekanan).
                                </p>
                                @if(!auth()->user()->isPemantau() && !auth()->user()->isOpd() && (auth()->user()->isSuperAdmin() || auth()->user()->getSumberPemeriksaan() === $temuan->sumber_pemeriksaan))
                                    <div class="pt-1 flex items-center gap-2">
                                        <form method="POST" action="{{ route('temuan.update-jenis-kerugian', $temuan) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="jenis_kerugian" value="Internal">
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold transition shadow-xs cursor-pointer">
                                                Tetapkan: Kerugian Internal
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('temuan.update-jenis-kerugian', $temuan) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="jenis_kerugian" value="Eksternal">
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold transition shadow-xs cursor-pointer">
                                                Tetapkan: Kerugian Eksternal
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @endif
                        <p class="text-xs text-gray-700 whitespace-pre-line">{{ $temuan->uraian_kerugian ?: 'Tidak ada rincian catatan kerugian tambahan.' }}</p>
                    @else
                        <p class="text-xs text-gray-500 italic">Temuan ini tidak menimbulkan kerugian finansial daerah/negara.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Uraian Lengkap, Sebab, Akibat, Rekomendasi --}}
        <div class="mt-6 space-y-4">
            <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-4">
                <h4 class="text-xs font-bold  tracking-wider text-gray-500">Uraian / Kondisi Temuan</h4>
                <p class="mt-2 text-sm text-gray-800 whitespace-pre-line leading-relaxed">{{ $temuan->uraian_temuan }}</p>
            </div>

            @if($temuan->penyebab || $temuan->akibat)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @if($temuan->penyebab)
                        <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-4">
                            <h4 class="text-xs font-bold  tracking-wider text-gray-500">Penyebab (Sebab)</h4>
                            <p class="mt-1 text-xs text-gray-800 whitespace-pre-line">{{ $temuan->penyebab }}</p>
                        </div>
                    @endif

                    @if($temuan->akibat)
                        <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-4">
                            <h4 class="text-xs font-bold  tracking-wider text-gray-500">Akibat</h4>
                            <p class="mt-1 text-xs text-gray-800 whitespace-pre-line">{{ $temuan->akibat }}</p>
                        </div>
                    @endif
                </div>
            @endif

            @if($temuan->rekomendasi)
                <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-4">
                    <h4 class="text-xs font-bold  tracking-wider text-emerald-800">Rekomendasi Tindak Lanjut</h4>
                    <p class="mt-2 text-sm text-emerald-950 whitespace-pre-line leading-relaxed">{{ $temuan->rekomendasi }}</p>
                </div>
            @endif
        </div>

        {{-- Berkas Pendukung Temuan --}}
        @if($temuan->dokumen_pendukung)
            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50/70 p-4">
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-100 text-primary-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold  tracking-wider text-gray-500">Berkas Pendukung Temuan</p>
                        <p class="text-sm font-semibold text-gray-900 [overflow-wrap:anywhere]">{{ $temuan->nama_dokumen_asli ?? 'Berkas-Pendukung-Temuan.pdf' }}</p>
                    </div>
                </div>

                <a href="{{ route('temuan.download-dokumen', $temuan) }}" target="_blank"
                   class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-primary-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-primary-700 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Unduh Berkas
                </a>
            </div>
        @endif

        {{-- Identitas Pencatatan Data (PRD Seksi 13) --}}
        <div class="mt-6 rounded-xl border border-primary-100 bg-primary-50/40 p-4">
            <div class="flex items-start gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-100 text-primary-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div class="text-xs space-y-0.5">
                    <p class="font-bold text-gray-800">Identitas Pencatatan Temuan (Otomatis)</p>
                    <p class="text-gray-600">
                        Penginput: <strong>{{ $temuan->creator?->official_name ?? 'Sistem' }}</strong>
                        @if($temuan->creator?->role)
                            — Role: <strong>{{ $temuan->creator->role->display_name }}</strong>
                        @endif
                        — Tanggal Input: <strong>{{ $temuan->created_at->translatedFormat('d F Y, H:i') }} WITA</strong>
                    </p>
                    @if($temuan->approver && $temuan->approved_at)
                        <p class="text-emerald-700 font-medium">
                            Disetujui oleh: <strong>{{ $temuan->approver->official_name }}</strong> pada {{ $temuan->approved_at->translatedFormat('d F Y, H:i') }} WITA
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Section Rekomendasi Bertahap (Khusus Temuan Administrasi) --}}
    @if($temuan->rekomendasis->isNotEmpty() || $temuan->ada_unsur_administrasi)
        @php
            $rekomendasis = $temuan->rekomendasis;
            $totalRek = $rekomendasis->count();
            $completedRek = $rekomendasis->filter(function($r) {
                return in_array($r->status_penyelesaian, ['Sesuai', 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti']);
            })->count();
            $rekPercent = $totalRek > 0 ? (int) round(($completedRek / $totalRek) * 100) : 0;
            $canAddRek = $temuan->status_pengajuan === 'DISETUJUI' && !auth()->user()->isPemantau() &&
                !auth()->user()->isOpd() &&
                (!auth()->user()->isAdminSumber() || auth()->user()->getSumberPemeriksaan() === $temuan->sumber_pemeriksaan);
        @endphp

        <div class="rounded-2xl border border-gray-200/80 bg-white p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 pb-4 gap-3">
                <div class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-100 text-primary-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-gray-900">Daftar Rekomendasi Bertahap</h3>
                            <span class="inline-flex items-center rounded-md bg-primary-50 border border-primary-200 px-2 py-0.5 text-xs font-bold text-primary-700">
                                {{ $completedRek }} dari {{ $totalRek }} Rekomendasi Selesai
                            </span>
                        </div>
                        <p class="text-xs text-gray-500">Setiap butir rekomendasi ditindaklanjuti dan diverifikasi secara bertahap</p>
                    </div>
                </div>

                @if($canAddRek)
                    <button type="button" onclick="toggleModal('modal-tambah-rekomendasi')"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-primary-700 transition cursor-pointer">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Tambah Rekomendasi</span>
                    </button>
                @endif
            </div>

            {{-- Progress Bar Rekomendasi --}}
            @if($totalRek > 0)
                <div class="mt-4 rounded-xl border border-primary-100 bg-primary-50/30 p-4">
                    <div class="flex items-center justify-between text-xs font-semibold text-gray-700 mb-1.5">
                        <span class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full {{ $rekPercent === 100 ? 'bg-emerald-500' : 'bg-primary-500' }}"></span>
                            <span>Progress Penyelesaian Rekomendasi</span>
                        </span>
                        <span class="font-bold {{ $rekPercent === 100 ? 'text-emerald-700' : 'text-primary-700' }}">{{ $completedRek }} / {{ $totalRek }} Selesai ({{ $rekPercent }}%)</span>
                    </div>
                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200" role="progressbar" aria-label="Penyelesaian rekomendasi" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $rekPercent }}">
                        <x-progress-indicator :value="$rekPercent" />
                    </div>
                </div>
            @endif

            {{-- List Rekomendasi --}}
            <div class="mt-4 space-y-4">
                @forelse($rekomendasis as $index => $rek)
                    @php
                        $rekBadge = $rek->status_penyelesaian_badge;
                        $isRekCompleted = in_array($rek->status_penyelesaian, ['Sesuai', 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti']);
                        $canDeleteRek = $rek->tindakLanjuts->isEmpty() && $canAddRek;
                        $rekTlList = $rek->tindakLanjuts->sortByDesc('tanggal_tindak_lanjut')->values();
                        $canSubmitTlRek = ($temuan->status_pengajuan === 'DISETUJUI') && (
                            auth()->user()->isSuperAdmin() ||
                            (auth()->user()->isAdminSumber() && auth()->user()->getSumberPemeriksaan() === $temuan->sumber_pemeriksaan) ||
                            (auth()->user()->isOpd() && auth()->user()->opd_id === $temuan->opd_id)
                        );
                    @endphp
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-2xs space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="space-y-1 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-primary-100 text-primary-800 text-xs font-bold">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="text-xs font-bold text-gray-500">Butir Rekomendasi #{{ $index + 1 }}</span>
                                    <x-status-badge :badge="$rekBadge" size="sm" dot-class="h-1.5 w-1.5" class="gap-1.5" />
                                </div>
                                <p class="text-sm font-medium text-gray-900 whitespace-pre-line leading-relaxed pt-1">
                                    {{ $rek->uraian_rekomendasi }}
                                </p>
                                @if($rek->alasan_khusus)
                                    <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50/70 p-2.5 text-xs text-amber-900">
                                        <strong>Pertimbangan Hukum:</strong> {{ $rek->alasan_khusus }}
                                    </div>
                                @endif
                                <p class="text-xs text-gray-500">
                                    Ditambahkan: {{ $rek->created_at->format('d M Y, H:i') }}
                                    @if($rek->creator) • Oleh: {{ $rek->creator->official_name }} @endif
                                </p>
                            </div>

                            <div class="flex flex-wrap sm:flex-col items-end gap-2 flex-shrink-0">
                                {{-- Tombol Input Tindak Lanjut Spesifik untuk Rekomendasi ini --}}
                                @if(!$isRekCompleted && $canSubmitTlRek)
                                    <button type="button" onclick="openTambahTlModal('{{ $rek->id }}', '{{ addslashes(str_replace(["\r", "\n"], ' ', $rek->uraian_rekomendasi)) }}')"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white transition shadow-2xs cursor-pointer">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        <span>Tindak Lanjut Butir Ini</span>
                                    </button>
                                @endif

                                {{-- Tombol Hapus Rekomendasi jika belum ada tindak lanjut --}}
                                @if($canDeleteRek)
                                    <form method="POST" action="{{ route('temuan.rekomendasi.destroy', [$temuan, $rek]) }}" class="inline"
                                          data-confirm-title="Hapus rekomendasi?"
                                          data-confirm-message="Apakah Anda yakin ingin menghapus butir rekomendasi ini?"
                                          data-confirm-variant="danger"
                                          data-confirm-accept="Ya, Hapus">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-state-danger hover:text-red-800 hover:underline cursor-pointer">
                                            Hapus Butir
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        {{-- Riwayat Tindak Lanjut Rekomendasi ini --}}
                        @if($rekTlList->isNotEmpty())
                            <div class="border-t border-gray-100 pt-3 space-y-2">
                                <span class="text-xs font-bold  tracking-wider text-gray-500">
                                    Riwayat Tindak Lanjut Butir Ini ({{ $rekTlList->count() }}):
                                </span>

                                @foreach($rekTlList->take(2) as $tl)
                                    @php $vBadge = $tl->status_verifikasi_badge; @endphp
                                    <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-3 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                                        <div class="min-w-0 flex-1 space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-gray-800">{{ $tl->tanggal_tindak_lanjut->format('d/m/Y') }}</span>
                                                <span class="text-xs text-gray-500">• Oleh: {{ $tl->creator?->official_name ?? 'OPD' }}</span>
                                            </div>
                                            <p class="text-xs text-gray-700 whitespace-pre-line leading-relaxed">{{ $tl->uraian_tindak_lanjut }}</p>

                                            @if($tl->dokumen_bukti)
                                                <div class="min-w-0 pt-1">
                                                    <a href="{{ route('temuan.tindak-lanjut.download', [$temuan, $tl]) }}" target="_blank"
                                                       class="inline-flex max-w-full min-w-0 items-start gap-1 whitespace-normal text-xs font-semibold text-primary-700 bg-primary-50 px-2 py-0.5 rounded border border-primary-200 hover:bg-primary-100 transition">
                                                        <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                        </svg>
                                                        <span class="min-w-0 [overflow-wrap:anywhere]">{{ $tl->nama_dokumen_asli ?? 'Dokumen Bukti' }}</span>
                                                    </a>
                                                </div>
                                            @endif

                                            @if($tl->catatan_verifikasi)
                                                <p class="text-xs text-gray-500 italic mt-1">
                                                    Catatan Verifikasi: "{{ $tl->catatan_verifikasi }}"
                                                </p>
                                            @endif
                                        </div>

                                        <div class="flex flex-col items-start sm:items-end gap-1.5 flex-shrink-0">
                                            <x-status-badge :badge="$vBadge" size="sm" />

                                            @if(auth()->user()->isSuperAdmin() && $tl->status_verifikasi === 'MENUNGGU VERIFIKASI')
                                                <button type="button" onclick="openVerifModal('{{ $tl->id }}', '{{ $tl->formatted_jumlah_setor }}')"
                                                        class="inline-flex items-center gap-1 rounded-lg bg-primary-600 hover:bg-primary-700 px-2.5 py-1 text-xs font-semibold text-white transition cursor-pointer">
                                                    Verifikasi TL
                                                </button>
                                            @endif

                                            @php
                                                $canDeleteTl = $tl->status_verifikasi === 'MENUNGGU VERIFIKASI' && (
                                                    auth()->user()->isSuperAdmin() ||
                                                    (auth()->user()->isAdminSumber() && auth()->user()->getSumberPemeriksaan() === $temuan->sumber_pemeriksaan) ||
                                                    (auth()->user()->isOpd() && $tl->created_by === auth()->id())
                                                );
                                            @endphp
                                            @if($canDeleteTl)
                                                <form method="POST" action="{{ route('temuan.tindak-lanjut.destroy', [$temuan, $tl]) }}" class="inline"
                                                      data-confirm-title="Hapus tindak lanjut?"
                                                      data-confirm-message="Apakah Anda yakin ingin menghapus tindak lanjut ini?"
                                                      data-confirm-variant="danger"
                                                      data-confirm-accept="Ya, Hapus">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs text-state-danger hover:underline cursor-pointer">
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                                @if($rekTlList->count() > 2)
                                    <details class="group mt-2">
                                        <summary class="text-xs font-semibold text-primary-600 hover:text-primary-800 cursor-pointer list-none flex items-center gap-1">
                                            <span class="group-open:hidden">▶ Lihat {{ $rekTlList->count() - 2 }} tindak lanjut sebelumnya</span>
                                            <span class="hidden group-open:inline">▼ Sembunyikan riwayat sebelumnya</span>
                                        </summary>
                                        <div class="mt-2 space-y-2 pl-2 border-l-2 border-primary-100">
                                            @foreach($rekTlList->slice(2) as $tl)
                                                @php $vBadge = $tl->status_verifikasi_badge; @endphp
                                                    <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-3 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                                                    <div class="min-w-0 flex-1 space-y-1">
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-xs font-bold text-gray-800">{{ $tl->tanggal_tindak_lanjut->format('d/m/Y') }}</span>
                                                            <span class="text-xs text-gray-500">• Oleh: {{ $tl->creator?->official_name ?? 'OPD' }}</span>
                                                        </div>
                                                        <p class="text-xs text-gray-700 whitespace-pre-line leading-relaxed">{{ $tl->uraian_tindak_lanjut }}</p>

                                                        @if($tl->dokumen_bukti)
                                                            <div class="min-w-0 pt-1">
                                                                <a href="{{ route('temuan.tindak-lanjut.download', [$temuan, $tl]) }}" target="_blank"
                                                                   class="inline-flex max-w-full min-w-0 items-start gap-1 whitespace-normal text-xs font-semibold text-primary-700 bg-primary-50 px-2 py-0.5 rounded border border-primary-200 hover:bg-primary-100 transition">
                                                                    <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                                    </svg>
                                                                    <span class="min-w-0 [overflow-wrap:anywhere]">{{ $tl->nama_dokumen_asli ?? 'Dokumen Bukti' }}</span>
                                                                </a>
                                                            </div>
                                                        @endif

                                                        @if($tl->catatan_verifikasi)
                                                            <p class="text-xs text-gray-500 italic mt-1">
                                                                Catatan Verifikasi: "{{ $tl->catatan_verifikasi }}"
                                                            </p>
                                                        @endif
                                                    </div>

                                                    <div class="flex flex-col items-start sm:items-end gap-1.5 flex-shrink-0">
                                                        <x-status-badge :badge="$vBadge" size="sm" />

                                                        @if(auth()->user()->isSuperAdmin() && $tl->status_verifikasi === 'MENUNGGU VERIFIKASI')
                                                <button type="button" onclick="openVerifModal('{{ $tl->id }}', '{{ $tl->formatted_jumlah_setor }}')"
                                                                    class="inline-flex items-center gap-1 rounded-lg bg-primary-600 hover:bg-primary-700 px-2.5 py-1 text-xs font-semibold text-white transition cursor-pointer">
                                                                Verifikasi TL
                                                            </button>
                                                        @endif

                                                        @php
                                                            $canDeleteTl = $tl->status_verifikasi === 'MENUNGGU VERIFIKASI' && (
                                                                auth()->user()->isSuperAdmin() ||
                                                                (auth()->user()->isAdminSumber() && auth()->user()->getSumberPemeriksaan() === $temuan->sumber_pemeriksaan) ||
                                                                (auth()->user()->isOpd() && $tl->created_by === auth()->id())
                                                            );
                                                        @endphp
                                                        @if($canDeleteTl)
                                                            <form method="POST" action="{{ route('temuan.tindak-lanjut.destroy', [$temuan, $tl]) }}" class="inline"
                                                                  data-confirm-title="Hapus tindak lanjut?"
                                                                  data-confirm-message="Apakah Anda yakin ingin menghapus tindak lanjut ini?"
                                                                  data-confirm-variant="danger"
                                                                  data-confirm-accept="Ya, Hapus">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-xs text-state-danger hover:underline cursor-pointer">
                                                                    Hapus
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50/50 p-6 text-center">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-primary-50 text-primary-700 mb-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <p class="text-xs font-semibold text-gray-800">Belum Ada Rekomendasi Bertahap</p>
                        <p class="text-xs text-gray-500 mt-0.5">Temuan administrasi ini belum dirinci menjadi butir-butir rekomendasi.</p>
                        @if($canAddRek)
                            <button type="button" onclick="toggleModal('modal-tambah-rekomendasi')"
                                    class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-primary-700 transition cursor-pointer">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span>Tambah Butir Rekomendasi Pertama</span>
                            </button>
                        @endif
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    {{-- Section Riwayat Tindak Lanjut & Bukti Penyetoran --}}
    @php
        $hasRekomendasi = $temuan->rekomendasis->isNotEmpty();
        $legacyTindakLanjuts = $temuan->tindakLanjuts->whereNull('rekomendasi_id');
        $hasLegacyTl = $legacyTindakLanjuts->isNotEmpty();
    @endphp

    @if(!$hasRekomendasi)
        {{-- Section Riwayat Tindak Lanjut Umum (Temuan Kerugian atau Temuan Lama tanpa butir) --}}
        @php
            $canSubmitTl = ($temuan->status_pengajuan === 'DISETUJUI') && (
                auth()->user()->isSuperAdmin() ||
                (auth()->user()->isAdminSumber() && auth()->user()->getSumberPemeriksaan() === $temuan->sumber_pemeriksaan) ||
                (auth()->user()->isOpd() && auth()->user()->opd_id === $temuan->opd_id)
            );
        @endphp
        <div class="rounded-2xl border border-gray-200/80 bg-white p-6 shadow-sm">
            <div class="flex items-center gap-2 border-b border-gray-100 pb-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900">Riwayat Tindak Lanjut & Bukti Penyetoran</h3>
                        <p class="text-xs text-gray-500">Pencatatan realisasi tindak lanjut OPD dan verifikasi Inspektorat</p>
                    </div>
                </div>
            </div>

            {{-- Timeline / Table Tindak Lanjut --}}
            <div class="mt-4 divide-y divide-gray-100">
                @forelse($temuan->tindakLanjuts as $tl)
                    <div class="py-4 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="min-w-0 flex-1 space-y-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                    <span class="text-xs font-bold text-gray-900">{{ $tl->tanggal_tindak_lanjut->format('d F Y') }}</span>
                                    <span class="text-xs text-gray-500">• Disampaikan oleh: {{ $tl->creator?->official_name ?? 'Admin' }}</span>
                                </div>
                                <p class="text-sm text-gray-800 whitespace-pre-line leading-relaxed">{{ $tl->uraian_tindak_lanjut }}</p>

                                @if($tl->jumlah_setor > 0)
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <span class="text-xs text-gray-500">Setoran {{ $labelKasTujuan }}:</span>
                                        <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                            {{ $tl->formatted_jumlah_setor }}
                                        </span>
                                        @if($tl->nomor_bukti_setor)
                                            <span class="text-xs text-gray-500">No. STS: <strong class="text-gray-700">{{ $tl->nomor_bukti_setor }}</strong></span>
                                        @endif
                                    </div>
                                @endif

                                @if($tl->dokumen_bukti)
                                    <div class="mt-2 min-w-0">
                                        <a href="{{ route('temuan.tindak-lanjut.download', [$temuan, $tl]) }}" target="_blank"
                                           class="inline-flex max-w-full min-w-0 items-start gap-1 whitespace-normal text-xs font-semibold text-primary-700 bg-primary-50 px-2.5 py-1 rounded-lg border border-primary-200 hover:bg-primary-100 transition">
                                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            <span class="min-w-0 [overflow-wrap:anywhere]">{{ $tl->nama_dokumen_asli ?? 'Bukti Setor' }}</span>
                                        </a>
                                    </div>
                                @endif
                            </div>

                            {{-- Verification Badge & Actions --}}
                            <div class="flex flex-col sm:items-end gap-2 flex-shrink-0">
                                @php $vBadge = $tl->status_verifikasi_badge; @endphp
                                <x-status-badge :badge="$vBadge" size="md" />

                                @if($tl->catatan_verifikasi)
                                    <p class="text-xs text-gray-500 italic max-w-xs text-right">
                                        "{{ $tl->catatan_verifikasi }}"
                                    </p>
                                @endif

                                {{-- Verification Button for Super Admin Only --}}
                                @if(auth()->user()->isSuperAdmin() && $tl->status_verifikasi === 'MENUNGGU VERIFIKASI')
                                    <button type="button" onclick="openVerifModal('{{ $tl->id }}', '{{ $tl->formatted_jumlah_setor }}')"
                                            class="inline-flex items-center gap-1 rounded-lg bg-primary-600 hover:bg-primary-700 px-2.5 py-1 text-xs font-semibold text-white transition cursor-pointer">
                                        Verifikasi TL
                                    </button>
                                @endif

                                {{-- Delete TL if still pending --}}
                                @php
                                    $canDeleteTl = $tl->status_verifikasi === 'MENUNGGU VERIFIKASI' && (
                                        auth()->user()->isSuperAdmin() ||
                                        (auth()->user()->isAdminSumber() && auth()->user()->getSumberPemeriksaan() === $temuan->sumber_pemeriksaan) ||
                                        (auth()->user()->isOpd() && $tl->created_by === auth()->id())
                                    );
                                @endphp
                                @if($canDeleteTl)
                                    <form method="POST" action="{{ route('temuan.tindak-lanjut.destroy', [$temuan, $tl]) }}" class="inline"
                                          data-confirm-title="Hapus tindak lanjut?"
                                          data-confirm-message="Apakah Anda yakin ingin menghapus tindak lanjut ini?"
                                          data-confirm-variant="danger"
                                          data-confirm-accept="Ya, Hapus">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-state-danger hover:underline cursor-pointer">
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 mb-3">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-gray-800">Belum ada riwayat tindak lanjut</p>
                        <p class="mx-auto mt-1 max-w-sm text-xs text-gray-500">Realisasi perbaikan rekomendasi dan bukti setoran kas negara atau kas daerah dapat dicatat melalui tombol berikut.</p>
                        @if($canSubmitTl)
                            <button type="button" onclick="openTambahTlModal(null)"
                                    class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition cursor-pointer">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span>Input Tindak Lanjut Sekarang</span>
                            </button>
                        @endif
                    </div>
                @endforelse
            </div>
        </div>
    @elseif($hasLegacyTl)
        {{-- Section Riwayat Tindak Lanjut Sebelumnya (Arsip) - Khusus temuan yang sudah punya butir rekomendasi tapi memiliki arsip TL lama --}}
        <div class="rounded-2xl border border-amber-200/80 bg-amber-50/20 p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-amber-100 pb-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-800">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900">Riwayat Tindak Lanjut Sebelumnya (Arsip)</h3>
                        <p class="text-xs text-gray-500">Catatan tindak lanjut yang disampaikan sebelum temuan ini dibagi menjadi butir rekomendasi bertahap (Read-Only)</p>
                    </div>
                </div>
                <span class="inline-flex items-center rounded-md bg-amber-100 border border-amber-300 px-2.5 py-1 text-xs font-bold text-amber-800">
                    Arsip Riwayat Umum
                </span>
            </div>

            <div class="mt-4 divide-y divide-gray-100">
                @foreach($legacyTindakLanjuts as $tl)
                    <div class="py-4 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="min-w-0 flex-1 space-y-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                    <span class="text-xs font-bold text-gray-900">{{ $tl->tanggal_tindak_lanjut->format('d F Y') }}</span>
                                    <span class="text-xs text-gray-500">• Disampaikan oleh: {{ $tl->creator?->official_name ?? 'Admin' }}</span>
                                </div>
                                <p class="text-sm text-gray-800 whitespace-pre-line leading-relaxed">{{ $tl->uraian_tindak_lanjut }}</p>

                                @if($tl->dokumen_bukti)
                                    <div class="mt-2 min-w-0">
                                        <a href="{{ route('temuan.tindak-lanjut.download', [$temuan, $tl]) }}" target="_blank"
                                           class="inline-flex max-w-full min-w-0 items-start gap-1 whitespace-normal text-xs font-semibold text-primary-700 bg-primary-50 px-2.5 py-1 rounded-lg border border-primary-200 hover:bg-primary-100 transition">
                                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            <span class="min-w-0 [overflow-wrap:anywhere]">{{ $tl->nama_dokumen_asli ?? 'Bukti Setor' }}</span>
                                        </a>
                                    </div>
                                @endif
                            </div>

                            {{-- Read-only verification badge (TANPA tombol verifikasi baru, TANPA tombol hapus) --}}
                            <div class="flex flex-col sm:items-end gap-1.5 flex-shrink-0">
                                @php $vBadge = $tl->status_verifikasi_badge; @endphp
                                <x-status-badge :badge="$vBadge" size="md" />
                                @if($tl->catatan_verifikasi)
                                    <p class="text-xs text-gray-500 italic max-w-xs text-right">
                                        "{{ $tl->catatan_verifikasi }}"
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

{{-- MODAL TAMBAH REKOMENDASI (ADMIN SUMBER / SUPER ADMIN) --}}
@if(!$temuan->ada_unsur_kerugian && $canAddRek)
<div id="modal-tambah-rekomendasi" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="modal-tambah-rekomendasi-title" tabindex="-1">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-100 text-primary-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <h3 id="modal-tambah-rekomendasi-title" class="font-bold text-gray-900">Tambah Rekomendasi Bertahap</h3>
                    <p class="text-xs text-gray-500">Tambahkan butir rekomendasi tindak lanjut untuk temuan administrasi ini</p>
                </div>
            </div>
            <button type="button" onclick="toggleModal('modal-tambah-rekomendasi')" aria-label="Tutup dialog rekomendasi" title="Tutup" class="text-gray-500 hover:text-gray-600 cursor-pointer">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('temuan.rekomendasi.store', $temuan) }}" class="mt-4 space-y-4">
            @csrf

            <div>
                <label for="uraian_rekomendasi" class="mb-1 block text-sm font-medium text-gray-700">
                    Uraian Rekomendasi <span class="text-state-danger">*</span>
                </label>
                <textarea id="uraian_rekomendasi" name="uraian_rekomendasi" rows="3" required
                          class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                          placeholder="Tuliskan butir rekomendasi spesifik yang harus ditindaklanjuti OPD..."></textarea>
            </div>

            <div class="flex flex-col items-stretch justify-end gap-2 pt-3 border-t border-gray-100 sm:flex-row sm:items-center">
                <button type="button" onclick="toggleModal('modal-tambah-rekomendasi')"
                        class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        class="rounded-xl bg-primary-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition cursor-pointer">
                    Simpan Rekomendasi
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- MODAL TAMBAH TINDAK LANJUT (OPD / ADMIN) --}}
<div id="modal-tambah-tl" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="modal-tambah-tl-title" tabindex="-1">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 id="modal-tambah-tl-title" class="font-bold text-gray-900">Sampaikan Tindak Lanjut Temuan</h3>
            <button type="button" onclick="toggleModal('modal-tambah-tl')" aria-label="Tutup dialog tindak lanjut" title="Tutup" class="text-gray-500 hover:text-gray-600 cursor-pointer">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('temuan.tindak-lanjut.store', $temuan) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="rekomendasi_id" id="modal_rekomendasi_id" value="">

            {{-- Target Rekomendasi Box (hanya muncul jika menyasar rekomendasi bertahap) --}}
            <div id="modal_rekomendasi_info" class="hidden rounded-xl border border-primary-200 bg-primary-50/80 p-3 text-xs text-primary-900 space-y-1">
                <div class="flex items-center gap-1.5 font-bold text-primary-800">
                    <svg class="h-4 w-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span>Target Butir Rekomendasi:</span>
                </div>
                <p id="modal_rekomendasi_text" class="text-xs text-primary-950 font-medium"></p>
            </div>

            {{-- Tanggal Tindak Lanjut --}}
            <div>
                <label for="tanggal_tindak_lanjut" class="mb-1 block text-sm font-medium text-gray-700">Tanggal Pelaksanaan Tindak Lanjut <span class="text-state-danger">*</span></label>
                <input type="date" id="tanggal_tindak_lanjut" name="tanggal_tindak_lanjut" value="{{ date('Y-m-d') }}" required
                       class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control">
            </div>

            {{-- Uraian Tindak Lanjut --}}
            <div>
                <label for="uraian_tindak_lanjut" class="mb-1 block text-sm font-medium text-gray-700">Uraian Realisasi Tindak Lanjut <span class="text-state-danger">*</span></label>
                <textarea id="uraian_tindak_lanjut" name="uraian_tindak_lanjut" rows="4" required
                          class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                          placeholder="Jelaskan langkah-langkah dan tindakan korektif yang telah dilakukan oleh OPD..."></textarea>
            </div>

            {{-- Jika Temuan memiliki unsur kerugian --}}
            @if($temuan->ada_unsur_kerugian)
                <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-4 space-y-3">
                    <div>
                        <label for="jumlah_setor" class="mb-1 block text-xs font-bold text-emerald-900">
                            Jumlah Nominal yang Disetor ke {{ $labelKasTujuan }} (Rupiah) <span class="text-state-danger">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-xs font-bold text-emerald-700">Rp</span>
                            <input type="text" id="jumlah_setor" name="jumlah_setor" inputmode="numeric"
                                   class="w-full border border-emerald-200 bg-white py-1.5 pl-9 pr-3 text-sm font-bold text-gray-900 idds-form-control"
                                   placeholder="0" oninput="calculateSisaTindakLanjut(this)" autocomplete="off">
                        </div>

                        {{-- Real-time Live Calculation Box --}}
                        <div class="mt-2.5 rounded-xl border border-emerald-200/80 bg-white p-3 space-y-1.5 shadow-2xs">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500">Sisa Kerugian Belum Disetor:</span>
                                <span class="font-bold text-state-danger">Rp {{ number_format($temuan->sisa_kerugian, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500">Nominal Setoran Saat Ini:</span>
                                <span id="display_setoran_live" class="font-bold text-state-success">Rp 0</span>
                            </div>
                            <div class="border-t border-gray-100 pt-1.5 flex items-center justify-between text-xs">
                                <span class="font-semibold text-gray-700">Sisa Kerugian Setelah Setoran:</span>
                                <span id="display_sisa_estimasi" class="font-bold text-gray-900">
                                    Rp {{ number_format($temuan->sisa_kerugian, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="nomor_bukti_setor" class="mb-1 block text-xs font-bold text-emerald-900">Nomor Bukti Setor / STS / NTPN</label>
                        <input type="text" id="nomor_bukti_setor" name="nomor_bukti_setor"
                               class="w-full border border-emerald-200 bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control"
                               placeholder="Contoh: STS-2026/04/001">
                    </div>
                </div>
            @endif

            {{-- Dokumen Bukti --}}
            <div>
                <label for="dokumen_bukti" class="mb-1 block text-sm font-medium text-gray-700">
                    Unggah Berkas Bukti Tindak Lanjut (PDF/JPG/PNG/DOC, Maks. 15MB) <span class="text-state-danger">*</span>
                </label>
                <input type="file" id="dokumen_bukti" name="dokumen_bukti" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                       class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none">
                <p class="text-xs text-gray-500 mt-1">Wajib melampirkan berkas bukti (scan STS/slip setoran atau dokumen penyelesaian administrasi).</p>
            </div>

            <div class="flex flex-col items-stretch justify-end gap-2 pt-3 border-t border-gray-100 sm:flex-row sm:items-center">
                <button type="button" onclick="toggleModal('modal-tambah-tl')"
                        class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit"
                        class="rounded-xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                    Ajukan Verifikasi
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL VERIFIKASI TINDAK LANJUT (SUPER ADMIN) --}}
@if(auth()->user()->isSuperAdmin())
<div id="modal-verif-tl" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="modal-verif-tl-title" tabindex="-1">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <div>
                <h3 id="modal-verif-tl-title" class="font-bold text-gray-900">Verifikasi Tindak Lanjut</h3>
                <p class="text-xs text-gray-500 mt-0.5">Pilihan hasil verifikasi menentukan Status Penyelesaian secara otomatis.</p>
            </div>
            <button type="button" onclick="toggleModal('modal-verif-tl')" aria-label="Tutup dialog verifikasi" title="Tutup" class="text-gray-500 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="form-verif-tl" method="POST" action="" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf

            <div>
                <label for="status_verifikasi" class="mb-1.5 block text-xs font-bold tracking-wider text-gray-700">
                    Pilih Hasil Verifikasi <span class="text-state-danger">*</span>
                </label>
                <select id="status_verifikasi" name="status_verifikasi" required onchange="handleTemuanVerifikasiChange(this.value)"
                        class="w-full border bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 idds-form-control">
                    <option value="SESUAI">1. Diterima / sesuai (bukti valid dan diakui masuk kas daerah atau kas negara)</option>
                    <option value="BELUM SESUAI">2. Ditolak / belum sesuai (bukti tidak sah atau perlu perbaikan)</option>
                    <option value="TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH">3. Tidak dapat ditindaklanjuti dengan alasan yang sah</option>
                </select>
            </div>

            {{-- Catatan Verifikasi (Wajib jika Belum Sesuai) --}}
            <div id="verif-catatan-box" class="space-y-1.5">
                <label for="catatan_verifikasi" class="block text-xs font-bold text-gray-700">
                    Catatan Verifikasi / Petunjuk Perbaikan <span id="verif-catatan-star" class="text-state-danger hidden">*</span>
                </label>
                <textarea id="catatan_verifikasi" name="catatan_verifikasi" rows="3"
                          class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                          placeholder="Catatan hasil pengujian fisik / administrasi..."></textarea>
            </div>

            {{-- Alasan Khusus & Dokumen (Wajib jika Tidak Dapat Ditindaklanjuti) --}}
            <div id="verif-alasan-box" class="hidden space-y-3 rounded-xl border border-amber-200 bg-amber-50/50 p-4">
                <div>
                    <label for="alasan_khusus" class="mb-1 block text-xs font-bold text-amber-900">
                        Pertimbangan Khusus / Telaahan Hukum <span class="text-state-danger">*</span>
                    </label>
                    <textarea id="alasan_khusus" name="alasan_khusus" rows="3"
                              class="w-full border border-amber-200 bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                              placeholder="Misal: Pihak ketiga telah meninggal dunia tanpa ahli waris / Putusan inkracht pengadilan..."></textarea>
                </div>

                <div>
                    <label for="dokumen_pendukung_alasan" class="mb-1 block text-xs font-bold text-amber-900">
                        Dokumen Pendukung / Surat Keputusan (PDF/DOC)
                    </label>
                    <input type="file" id="dokumen_pendukung_alasan" name="dokumen_pendukung_alasan" accept=".pdf,.doc,.docx"
                           class="w-full rounded-xl border border-amber-200 bg-white px-3 py-1.5 text-xs text-gray-800 focus:outline-none">
                </div>
            </div>

            <div class="flex flex-col items-stretch justify-end gap-2 pt-3 border-t border-gray-100 sm:flex-row sm:items-center">
                <button type="button" onclick="toggleModal('modal-verif-tl')"
                        class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit"
                        class="rounded-xl bg-primary-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700">
                    Simpan Hasil Verifikasi
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- MODAL VERIFIKASI TINDAK LANJUT (SUPER ADMIN) --}}

<script>
    function openTambahTlModal(rekId = null, rekUraian = '') {
        const inputRekId = document.getElementById('modal_rekomendasi_id');
        const infoBox = document.getElementById('modal_rekomendasi_info');
        const textBox = document.getElementById('modal_rekomendasi_text');
        const modalTitle = document.getElementById('modal-tambah-tl-title');

        if (rekId) {
            if (inputRekId) inputRekId.value = rekId;
            if (textBox) textBox.textContent = rekUraian;
            if (infoBox) infoBox.classList.remove('hidden');
            if (modalTitle) modalTitle.textContent = 'Sampaikan Tindak Lanjut Rekomendasi';
        } else {
            if (inputRekId) inputRekId.value = '';
            if (infoBox) infoBox.classList.add('hidden');
            if (modalTitle) modalTitle.textContent = 'Sampaikan Tindak Lanjut Temuan';
        }
        toggleModal('modal-tambah-tl');
    }

    function openVerifModal(tlId, jumlahSetor) {
        const form = document.getElementById('form-verif-tl');
        form.action = '/temuan/{{ $temuan->id }}/tindak-lanjut/' + tlId + '/verifikasi';
        const select = document.getElementById('status_verifikasi');
        if (select) {
            select.value = 'SESUAI';
            handleTemuanVerifikasiChange('SESUAI');
        }
        toggleModal('modal-verif-tl');
    }

    function handleTemuanVerifikasiChange(status) {
        const catatanBox = document.getElementById('verif-catatan-box');
        const catatanStar = document.getElementById('verif-catatan-star');
        const catatanInput = document.getElementById('catatan_verifikasi');
        const alasanBox = document.getElementById('verif-alasan-box');
        const alasanInput = document.getElementById('alasan_khusus');

        if (status === 'BELUM SESUAI') {
            if (catatanBox) catatanBox.classList.remove('hidden');
            if (catatanStar) catatanStar.classList.remove('hidden');
            if (catatanInput) catatanInput.required = true;
            if (alasanBox) alasanBox.classList.add('hidden');
            if (alasanInput) alasanInput.required = false;
        } else if (status === 'TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH') {
            if (catatanBox) catatanBox.classList.add('hidden');
            if (catatanInput) catatanInput.required = false;
            if (alasanBox) alasanBox.classList.remove('hidden');
            if (alasanInput) alasanInput.required = true;
        } else {
            if (catatanBox) catatanBox.classList.remove('hidden');
            if (catatanStar) catatanStar.classList.add('hidden');
            if (catatanInput) catatanInput.required = false;
            if (alasanBox) alasanBox.classList.add('hidden');
            if (alasanInput) alasanInput.required = false;
        }
    }

    const SISA_KERUGIAN_AWAL = {{ (float) ($temuan->sisa_kerugian ?? 0) }};

    function calculateSisaTindakLanjut(input) {
        updateInstallmentRemaining(input, SISA_KERUGIAN_AWAL, 'display_setoran_live', 'display_sisa_estimasi');
    }
</script>
@endsection
