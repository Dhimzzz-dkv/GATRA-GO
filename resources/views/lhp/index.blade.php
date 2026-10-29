@extends('layouts.app')

@section('title', 'Laporan Hasil Pemeriksaan (LHP)')
@section('page-title', 'Laporan Hasil Pemeriksaan (LHP)')
@section('page-subtitle', 'Monitoring dokumen LHP, target OPD, serta status pemenuhan temuan hasil pemeriksaan')

@section('content')
<div class="mx-auto w-full min-w-0 max-w-7xl space-y-6 pb-16">
    {{-- Top Statistics Summary Bar --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total LHP --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-bold  tracking-wider text-blue-600">Total Dokumen LHP</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalLhp) }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-600/20">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-xs font-medium text-gray-500">Dari berbagai sumber pengawasan</p>
        </div>

        {{-- Total Temuan --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-bold  tracking-wider text-primary-600">Total Temuan Terdaftar</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalTemuan) }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-600 text-white shadow-md shadow-primary-600/20">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-xs font-medium text-gray-500">Unsur administrasi &amp; kerugian</p>
        </div>

        {{-- Total Kerugian Daerah --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <div class="min-w-0 space-y-1 pr-2">
                    <p class="text-xs font-bold tracking-wider text-amber-700">Total Kerugian Negara &amp; Daerah</p>
                    <p class="text-lg font-bold text-amber-900">Rp {{ number_format($totalKerugian, 0, ',', '.') }}</p>
                </div>
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-md shadow-amber-500/20">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-xs font-medium text-gray-500">Kewajiban setoran ke Kas Negara dan Kas Daerah</p>
        </div>

        {{-- Total Disetor Kasda --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-bold  tracking-wider text-emerald-700">Disetor (Kasda/Kasneg)</p>
                    <p class="text-lg font-bold text-emerald-900">Rp {{ number_format($totalDisetor, 0, ',', '.') }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-md shadow-emerald-600/20">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-xs font-medium text-gray-500">Setoran STS terverifikasi</p>
        </div>
    </div>

    {{-- Header Action Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h3 class="text-base font-bold text-gray-900  tracking-tight flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Dokumen Hasil Pengawasan &amp; Pemeriksaan
            </h3>
            <p class="text-xs text-gray-500 font-medium">Daftar Laporan Hasil Pemeriksaan (LHP) terikat target multi-OPD</p>
        </div>

        @if(!auth()->user()->isPemantau() && !auth()->user()->isOpd() && !auth()->user()->isAdminInspektorat())
            <x-action-button href="{{ route('lhp.create') }}" variant="primary-compact">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah LHP
            </x-action-button>
        @endif
    </div>

    {{-- Filter & Search Card --}}
    <div class="rounded-3xl border border-gray-200/90 bg-white p-5 md:p-6 shadow-sm">
        <form method="GET" action="{{ route('lhp.index') }}">
            <div class="idds-filter-bar">
                {{-- Search Bar --}}
                <div class="idds-filter-search min-w-0">
                    <label for="lhp-search" class="block text-xs font-bold text-gray-600 tracking-wider mb-1">Cari LHP / Kata Kunci</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </span>
                        <input type="text" id="lhp-search" name="search" value="{{ $search }}" placeholder="Nomor LHP, judul, ringkasan..."
                               class="w-full pl-9 border bg-gray-50/50 px-3 py-2 text-xs text-gray-800 focus:bg-white idds-form-control">
                    </div>
                </div>

                {{-- Filter Sumber --}}
                @if(auth()->user()->isSuperAdmin() || auth()->user()->isPemantau())
                    <div>
                        <label for="lhp-sumber" class="block text-xs font-bold text-gray-600 tracking-wider mb-1">Sumber Pemeriksaan</label>
                        <select id="lhp-sumber" name="sumber" class="w-full border bg-gray-50/50 px-3 py-2 text-xs text-gray-800 focus:bg-white font-semibold idds-form-control">
                            <option value="">Semua Sumber</option>
                            @foreach($sumberList as $s)
                                <option value="{{ $s }}" {{ $sumber === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Filter Tahun --}}
                <div>
                    <label for="lhp-tahun" class="block text-xs font-bold text-gray-600 tracking-wider mb-1">Tahun Anggaran</label>
                    <select id="lhp-tahun" name="tahun" class="w-full border bg-gray-50/50 px-3 py-2 text-xs text-gray-800 focus:bg-white font-semibold idds-form-control">
                        <option value="">Semua Tahun</option>
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter OPD --}}
                <div>
                    <label for="lhp-opd" class="block text-xs font-bold text-gray-600 tracking-wider mb-1">Target OPD</label>
                    <select id="lhp-opd" name="opd_id" class="w-full border bg-gray-50/50 px-3 py-2 text-xs text-gray-800 focus:bg-white idds-form-control">
                        <option value="">Semua OPD</option>
                        @foreach($opds as $o)
                            <option value="{{ $o->id }}" {{ $opdId == $o->id ? 'selected' : '' }}>{{ $o->nama }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Status Penyelesaian --}}
                <div>
                    <label for="lhp-status-penyelesaian" class="block text-xs font-bold text-gray-600 tracking-wider mb-1">Status Penyelesaian</label>
                    <select id="lhp-status-penyelesaian" name="status_penyelesaian" class="w-full border bg-gray-50/50 px-3 py-2 text-xs text-gray-800 focus:bg-white font-semibold idds-form-control">
                        <option value="">Semua Status</option>
                        <option value="Belum Ditindaklanjuti" {{ $statusPenyelesaian === 'Belum Ditindaklanjuti' ? 'selected' : '' }}>Belum Ditindaklanjuti</option>
                        <option value="Menunggu Verifikasi" {{ $statusPenyelesaian === 'Menunggu Verifikasi' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                        <option value="Belum Sesuai" {{ $statusPenyelesaian === 'Belum Sesuai' ? 'selected' : '' }}>Belum Sesuai</option>
                        <option value="Sesuai" {{ $statusPenyelesaian === 'Sesuai' ? 'selected' : '' }}>Sesuai</option>
                        <option value="Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah" {{ $statusPenyelesaian === 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah' ? 'selected' : '' }}>Tidak Dapat Ditindaklanjuti</option>
                    </select>
                </div>

                <div class="idds-filter-actions">
                    <x-action-button type="submit" variant="filter">
                        Terapkan Filter
                    </x-action-button>
                    <x-action-button href="{{ route('lhp.index') }}" variant="filter-reset">
                        Reset Filter
                    </x-action-button>
                </div>
            </div>
        </form>
    </div>

    {{-- Daftar LHP Card Table / List --}}
    <div class="space-y-4">
        @forelse($lhps as $lhp)
            @php
                $sumber = $lhp->pemeriksaan?->sumber_pemeriksaan ?? '-';
                $badge = $lhp->status_penyelesaian_badge;
                $admCount = $lhp->temuans->where('ada_unsur_administrasi', true)->count();
                $kgCount = $lhp->temuans->where('ada_unsur_kerugian', true)->count();
                $totalKerugianLhp = (float) ($lhp->total_kerugian_agregat ?? 0);
                $totalDisetorLhp = (float) ($lhp->total_disetor_lhp_agregat ?? 0)
                    + (float) ($lhp->total_disetor_temuan_agregat ?? 0);
                $sisaKerugianLhp = max(0, $totalKerugianLhp - $totalDisetorLhp);
            @endphp

            <div class="rounded-3xl border border-gray-200/90 bg-white p-6 shadow-sm hover:border-primary-300 transition space-y-4">
                {{-- Top Line --}}
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-gray-100 pb-3">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <x-sumber-badge :sumber="$sumber" />
                        <span class="text-sm font-bold text-gray-900">{{ $lhp->nomor_lhp }}</span>
                        <span class="text-xs text-gray-500">•</span>
                        <span class="text-xs text-gray-500 font-medium">Tanggal LHP: {{ $lhp->tanggal_lhp ? $lhp->tanggal_lhp->translatedFormat('d F Y') : '-' }}</span>
                        <span class="text-xs text-gray-500">•</span>
                        <span class="text-xs text-gray-500 font-medium">Tahun: {{ $lhp->pemeriksaan?->tahun ?? '-' }}</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-status-badge :badge="$badge" size="md" dot-class="w-1.5 h-1.5" class="gap-1.5 font-bold" />
                    </div>
                </div>

                {{-- Content Line --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-2.5">
                        <h4 class="text-sm font-bold text-gray-900 leading-snug">
                            {{ $lhp->judul_lhp ?: ($lhp->pemeriksaan?->judul ?? 'Laporan Hasil Pemeriksaan') }}
                        </h4>
                        @if($lhp->ringkasan)
                            <p class="text-xs text-gray-600 line-clamp-2 leading-relaxed">
                                {{ $lhp->ringkasan }}
                            </p>
                        @endif

                        {{-- Multi-OPD Badges --}}
                        <div class="flex flex-wrap items-center gap-1.5 pt-1">
                            <span class="text-xs font-bold text-gray-500  tracking-wider mr-1">OPD Target:</span>
                            @forelse($lhp->opds as $opdItem)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-bold bg-primary-50 text-primary-700 border border-primary-200">
                                    <span class="text-primary-900 font-bold">[{{ $opdItem->kode }}]</span>
                                    <span>{{ $opdItem->nama }}</span>
                                </span>
                            @empty
                                <span class="text-xs text-gray-500 italic">Belum ada OPD yang terikat</span>
                            @endforelse
                        </div>

                        {{-- SPT Info --}}
                        @if($lhp->pemeriksaan && ! $lhp->pemeriksaan->is_lhp_only_submission)
                            <div class="flex items-center gap-2 text-xs text-gray-500 pt-1">
                                <span class="font-bold text-gray-700">SPT Induk:</span>
                                <span>{{ $lhp->pemeriksaan->nomor_spt }}</span>
                                @if($lhp->pemeriksaan->nama_tim)
                                    <span class="text-gray-500">•</span>
                                    <span>Tim: {{ $lhp->pemeriksaan->nama_tim }}</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Metrik Temuan & Finansial --}}
                    <div class="rounded-2xl bg-gray-50/80 border border-gray-200/80 p-4 space-y-2.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500 font-medium">Temuan Administrasi:</span>
                            <span class="font-bold text-primary-700">{{ $admCount }} Butir</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500 font-medium">Temuan Kerugian:</span>
                            <span class="font-bold text-amber-700">{{ $kgCount }} Butir</span>
                        </div>

                        @if($totalKerugianLhp > 0)
                            <div class="pt-2 border-t border-gray-200 space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">Kerugian:</span>
                                    <span class="font-bold text-amber-900">Rp {{ number_format($totalKerugianLhp, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">Disetor:</span>
                                    <span class="font-bold text-emerald-700">Rp {{ number_format($totalDisetorLhp, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">Sisa:</span>
                                    <span class="font-bold text-rose-700">Rp {{ number_format($sisaKerugianLhp, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Footer Actions Bar --}}
                <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-gray-100">
                    <div class="flex items-center gap-2">
                        @if($lhp->dokumen_lhp && $lhp->pemeriksaan)
                            <a href="{{ route('pemeriksaan.lhp.download', [$lhp->pemeriksaan, $lhp]) }}" target="_blank"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-bold transition">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Unduh Dokumen LHP
                            </a>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        @if($lhp->pemeriksaan)
                            <a href="{{ route('pemeriksaan.lhp.show', [$lhp->pemeriksaan, $lhp]) }}"
                               class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold shadow-xs transition cursor-pointer">
                                <span>Detail LHP &amp; Temuan ({{ $lhp->temuans->count() }})</span>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-dashed border-gray-200 bg-white p-12 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-500 mb-3">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-gray-900">Tidak ada dokumen LHP ditemukan</h3>
                <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
                    Data Laporan Hasil Pemeriksaan (LHP) belum tersedia atau tidak sesuai dengan filter yang dipilih.
                </p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($lhps->hasPages())
        <div class="pt-4">
            {{ $lhps->links() }}
        </div>
    @endif
</div>
@endsection
