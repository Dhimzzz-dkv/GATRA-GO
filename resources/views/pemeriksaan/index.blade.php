@extends('layouts.app')

@section('title', 'Data SPT & Pemeriksaan')
@section('page-title', 'Data SPT & Pemeriksaan')
@section('page-subtitle', 'Kelola Surat Perintah Tugas (SPT), Tim Pemeriksa, dan Laporan Hasil Pemeriksaan (LHP)')

@section('content')
<div class="space-y-6">
    {{-- Summary Stats Bar (Interactive Quick-Filter Cards) --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Card 1: Total Pemeriksaan --}}
        <a href="{{ route('pemeriksaan.index', array_merge(request()->except(['penyelesaian', 'page']))) }}"
           class="group rounded-2xl border transition p-5 shadow-xs hover:shadow-md {{ empty($penyelesaian) ? 'border-primary-500/80 bg-white ring-2 ring-primary-500/20' : 'border-gray-200/80 bg-white hover:border-gray-300' }}">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold  tracking-wider text-gray-500">Total Pemeriksaan</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ $totalPemeriksaan }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-50 text-primary-600 transition group-hover:bg-primary-100">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs font-medium text-gray-500">
                <span>Semua kegiatan terdaftar</span>
                @if(empty($penyelesaian))
                    <span class="text-primary-700 font-bold">● Aktif</span>
                @endif
            </div>
        </a>

        {{-- Card 2: Pemeriksaan Tuntas --}}
        <a href="{{ route('pemeriksaan.index', array_merge(request()->except('page'), ['penyelesaian' => 'tuntas'])) }}"
           class="group rounded-2xl border transition p-5 shadow-xs hover:shadow-md {{ $penyelesaian === 'tuntas' ? 'border-emerald-500/80 bg-emerald-50/20 ring-2 ring-emerald-500/20' : 'border-gray-200/80 bg-white hover:border-gray-300' }}">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold  tracking-wider text-emerald-700">Laporan Tuntas</p>
                    <p class="mt-1 text-2xl font-bold text-state-success">{{ $totalTuntas }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-state-success transition group-hover:bg-emerald-100">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs font-medium text-gray-500">
                <span>Seluruh temuan terselesaikan</span>
                @if($penyelesaian === 'tuntas')
                    <span class="text-emerald-700 font-bold">● Difilter</span>
                @endif
            </div>
        </a>

        {{-- Card 3: Belum Tuntas --}}
        <a href="{{ route('pemeriksaan.index', array_merge(request()->except('page'), ['penyelesaian' => 'belum_tuntas'])) }}"
           class="group rounded-2xl border transition p-5 shadow-xs hover:shadow-md {{ $penyelesaian === 'belum_tuntas' ? 'border-amber-500/80 bg-amber-50/20 ring-2 ring-amber-500/20' : 'border-gray-200/80 bg-white hover:border-gray-300' }}">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold  tracking-wider text-amber-700">Belum Tuntas</p>
                    <p class="mt-1 text-2xl font-bold text-state-warning">{{ $totalBelumTuntas }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-state-warning transition group-hover:bg-amber-100">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs font-medium text-gray-500">
                <span>Ada temuan dalam tindak lanjut</span>
                @if($penyelesaian === 'belum_tuntas')
                    <span class="text-amber-700 font-bold">● Difilter</span>
                @endif
            </div>
        </a>

        {{-- Card 4: Belum Ada Temuan --}}
        <a href="{{ route('pemeriksaan.index', array_merge(request()->except('page'), ['penyelesaian' => 'belum_ada_temuan'])) }}"
           class="group rounded-2xl border transition p-5 shadow-xs hover:shadow-md {{ $penyelesaian === 'belum_ada_temuan' ? 'border-gray-500/80 bg-gray-50/50 ring-2 ring-gray-400/20' : 'border-gray-200/80 bg-white hover:border-gray-300' }}">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold  tracking-wider text-gray-500">Belum Ada Temuan</p>
                    <p class="mt-1 text-2xl font-bold text-gray-700">{{ $totalBelumAdaTemuan }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gray-100 text-gray-600 transition group-hover:bg-gray-200">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs font-medium text-gray-500">
                <span>SPT belum menginput temuan</span>
                @if($penyelesaian === 'belum_ada_temuan')
                    <span class="text-gray-700 font-bold">● Difilter</span>
                @endif
            </div>
        </a>
    </div>

    {{-- Action Bar & Filters --}}
    <div class="flex flex-col gap-3">
        {{-- Search & Filters --}}
        <form method="GET" action="{{ route('pemeriksaan.index') }}" class="idds-filter-bar min-w-0">
                <div class="idds-filter-search relative min-w-0">
                    <input type="text" name="search" value="{{ $search }}" aria-label="Cari pemeriksaan"
                           placeholder="Cari nomor SPT, judul, atau nama OPD..."
                           class="w-full border bg-white py-2.5 pl-10 pr-4 text-sm text-gray-800 placeholder-gray-400 shadow-sm idds-form-control">
                    <svg class="absolute left-3.5 top-3 h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                @if(auth()->user()->isSuperAdmin() || auth()->user()->isPemantau())
                    <select name="sumber" aria-label="Filter berdasarkan sumber pemeriksaan"
                            class="w-full min-w-0 border bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm idds-form-control">
                        <option value="">Semua Sumber</option>
                        @foreach($sumberList as $s)
                            <option value="{{ $s }}" {{ $sumber === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                @endif

                @if(!auth()->user()->isOpd())
                    <select name="opd_id" aria-label="Filter berdasarkan OPD"
                            class="w-full min-w-0 border bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm idds-form-control">
                        <option value="">Semua OPD</option>
                        @foreach($opds as $o)
                            <option value="{{ $o->id }}" {{ $opdId == $o->id ? 'selected' : '' }}>{{ $o->kode }}</option>
                        @endforeach
                    </select>
                @endif

                <select name="tahun" aria-label="Filter berdasarkan tahun pemeriksaan"
                        class="w-full min-w-0 border bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm idds-form-control">
                    <option value="">Semua Tahun</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>

                <select name="status_pengajuan" aria-label="Filter berdasarkan status pengajuan"
                        class="w-full min-w-0 border bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm idds-form-control">
                    <option value="">Semua Status Pengajuan</option>
                    @if(!auth()->user()->isSuperAdmin() && !auth()->user()->isPemantau())
                        <option value="DRAFT" {{ $statusPengajuan === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                    @endif
                    <option value="MENUNGGU PERSETUJUAN" {{ $statusPengajuan === 'MENUNGGU PERSETUJUAN' ? 'selected' : '' }}>Menunggu Persetujuan</option>
                    <option value="DISETUJUI" {{ $statusPengajuan === 'DISETUJUI' ? 'selected' : '' }}>Disetujui</option>
                    <option value="PERLU REVISI" {{ $statusPengajuan === 'PERLU REVISI' ? 'selected' : '' }}>Perlu Revisi</option>
                </select>

                <select name="penyelesaian" aria-label="Filter berdasarkan status penyelesaian"
                        class="w-full min-w-0 border bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm idds-form-control xl:col-span-2">
                    <option value="">Semua Status Penyelesaian</option>
                    <option value="tuntas" {{ ($penyelesaian ?? '') === 'tuntas' ? 'selected' : '' }}>Tuntas</option>
                    <option value="belum_tuntas" {{ ($penyelesaian ?? '') === 'belum_tuntas' ? 'selected' : '' }}>Belum Tuntas</option>
                    <option value="belum_ada_temuan" {{ ($penyelesaian ?? '') === 'belum_ada_temuan' ? 'selected' : '' }}>Belum Ada Temuan</option>
                </select>

                <div class="idds-filter-actions">
                    <x-action-button variant="filter" type="submit" class="w-full sm:w-auto">Filter</x-action-button>

                    @if($search || $sumber || $opdId || $tahun || $statusPengajuan || ($penyelesaian ?? null))
                        <a href="{{ route('pemeriksaan.index') }}" class="inline-flex min-h-10 items-center rounded-control px-3 text-sm font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-700">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

        {{-- Add Button (Super Admin & Admin Inspektorat: SPT & LHP; Admin Sumber lain: LHP & Tindak Lanjut) --}}
        @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdminSumber())
            <div class="flex justify-end">
                @if(auth()->user()->canManageSpt())
                    <div class="flex w-full items-center gap-2 sm:w-auto sm:shrink-0">
                        <a href="{{ route('pemeriksaan.create') }}"
                           class="inline-flex min-h-10 w-full items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-primary-700 sm:w-auto">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>Tambah Pemeriksaan</span>
                        </a>
                    </div>
                @else
                    <a href="{{ route('lhp.create') }}"
                       class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-primary-700 sm:w-auto">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Input LHP &amp; Tindak Lanjut</span>
                    </a>
                @endif
            </div>
        @endif
    </div>

    {{-- Pemeriksaan Table Card --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm">
        <div class="idds-table-region overflow-x-auto" role="region" aria-label="Daftar pemeriksaan. Geser horizontal untuk melihat seluruh kolom." tabindex="0">
            <p class="mb-2 text-xs text-gray-500 sm:hidden" aria-hidden="true">Geser tabel ke samping untuk melihat kolom lainnya.</p>
            <table class="w-full text-left text-sm text-gray-600">
                <caption class="sr-only">Daftar SPT dan pemeriksaan</caption>
                <thead class="bg-gray-50/75 border-b border-gray-200 text-xs tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold">No</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Nomor SPT &amp; Periode</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Sumber &amp; Tahun</th>
                        <th scope="col" class="px-6 py-4 font-semibold">OPD Target</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-center">Status</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pemeriksaans as $index => $p)
                        <tr class="hover:bg-primary-50/30 transition">
                            <td class="px-6 py-4 font-medium text-gray-500">
                                {{ $pemeriksaans->firstItem() + $index }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-medium text-gray-800">{{ $p->nomor_spt ?? '-' }}</span>
                                    @if($p->dokumen_spt)
                                        <a href="{{ route('pemeriksaan.download-spt', $p) }}" target="_blank" title="Unduh Berkas Dokumen SPT"
                                           class="inline-flex items-center text-primary-600 hover:text-primary-800 transition p-0.5 rounded hover:bg-primary-50">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                                @if($p->tanggal_spt)
                                    <p class="text-xs text-gray-500">Tgl SPT: {{ $p->tanggal_spt->format('d/m/Y') }}</p>
                                @endif
                                @if($p->tanggal_mulai && $p->tanggal_selesai)
                                    <p class="text-xs text-gray-500">{{ $p->tanggal_mulai->format('d/m/Y') }} s.d {{ $p->tanggal_selesai->format('d/m/Y') }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <x-sumber-badge :sumber="$p->sumber_pemeriksaan" />
                                <p class="mt-1 text-xs font-semibold text-gray-700">Tahun {{ $p->tahun }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('pemeriksaan.show', $p) }}" class="font-semibold text-gray-900 hover:text-primary-600 transition">
                                    {{ $p->opd?->nama ?? ($p->judul ?: 'Pemeriksaan Lintas OPD') }}
                                </a>
                                @if($p->judul)
                                    <p class="text-xs text-gray-500 truncate max-w-xs">{{ $p->judul }}</p>
                                @endif
                                <p class="text-xs text-gray-500 mt-0.5">Penginput: {{ $p->creator?->official_name ?? '-' }}</p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $activeTemuans = $p->temuans->where('is_archived', false);
                                    $totalTemuan = $activeTemuans->count();

                                    if ($totalTemuan === 0) {
                                        $penyelesaianBadge = [
                                            'label' => 'Belum Ada Temuan',
                                            'class' => 'bg-gray-100 text-gray-600 border-gray-200',
                                        ];
                                    } else {
                                        $resolvedStatuses = ['Sesuai', 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti'];
                                        $resolvedCount = $activeTemuans->whereIn('status_penyelesaian', $resolvedStatuses)->count();
                                        $isFullyResolved = ($resolvedCount === $totalTemuan);

                                        if ($isFullyResolved) {
                                            $penyelesaianBadge = [
                                                'label' => 'Tuntas',
                                                'class' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                            ];
                                        } else {
                                            $penyelesaianBadge = [
                                                'label' => 'Belum Tuntas',
                                                'class' => 'bg-amber-100 text-amber-800 border-amber-200',
                                            ];
                                        }
                                    }

                                    $statusBadge = $p->status_pengajuan_badge ?? [
                                        'label' => $p->status_pengajuan,
                                        'class' => 'bg-gray-100 text-gray-700 border-gray-200'
                                    ];
                                @endphp
                                <div class="flex flex-col items-center gap-1">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-bold  tracking-wider {{ $statusBadge['class'] }}">
                                        {{ $statusBadge['label'] }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium {{ $penyelesaianBadge['class'] }}">
                                        {{ $penyelesaianBadge['label'] }}
                                    </span>
                                    @if($totalTemuan > 0 && !($penyelesaianBadge['label'] === 'Tuntas'))
                                        <p class="text-xs text-gray-500">{{ $resolvedCount }}/{{ $totalTemuan }} temuan selesai</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    {{-- Tombol Ajukan (Khusus Admin Sumber untuk Draft atau Perlu Revisi) --}}
                                    @if(auth()->user()->isAdminSumber() && in_array($p->status_pengajuan, ['DRAFT', 'PERLU REVISI', 'DITOLAK']))
                                        <form method="POST" action="{{ route('pemeriksaan.ajukan', $p) }}" class="inline"
                                              data-confirm-variant="submit"
                                              data-confirm-title="Ajukan untuk persetujuan?"
                                              data-confirm-message="{{ 'Ajukan data SPT ' . $p->nomor_spt . ' ke Super Admin untuk persetujuan?' }}">
                                            @csrf
                                            <button type="submit" title="Ajukan ke Super Admin"
                                                    class="rounded-lg p-1.5 text-blue-600 hover:bg-blue-50 transition cursor-pointer">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Tombol Batalkan Pengajuan (Khusus Admin Sumber saat Menunggu Persetujuan) --}}
                                    @if(auth()->user()->isAdminSumber() && $p->status_pengajuan === 'MENUNGGU PERSETUJUAN')
                                        <form method="POST" action="{{ route('pemeriksaan.batal-ajukan', $p) }}" class="inline"
                                              data-confirm-variant="cancel"
                                              data-confirm-title="Batalkan pengajuan?"
                                              data-confirm-message="{{ 'Batalkan pengajuan SPT ' . $p->nomor_spt . ' dan kembalikan ke Draft?' }}">
                                            @csrf
                                            <button type="submit" title="Batalkan Pengajuan (Kembali ke Draft)"
                                                    class="rounded-lg p-1.5 text-state-warning hover:bg-amber-50 transition cursor-pointer">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Tombol Cepat Setujui (Khusus Super Admin saat Menunggu Persetujuan) --}}
                                    @if(auth()->user()->isSuperAdmin() && $p->status_pengajuan === 'MENUNGGU PERSETUJUAN')
                                        <form method="POST" action="{{ route('pengajuan.pemeriksaan.approve', $p) }}" class="inline"
                                              data-confirm-variant="approval"
                                              data-confirm-title="Setujui pemeriksaan ini?"
                                              data-confirm-message="{{ 'Setujui data SPT ' . $p->nomor_spt . '? Data akan tersedia untuk ditindaklanjuti.' }}">
                                            @csrf
                                            <button type="submit" title="Setujui Pemeriksaan Ini"
                                                    class="rounded-lg p-1.5 text-state-success hover:bg-emerald-50 transition cursor-pointer">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('pemeriksaan.show', $p) }}" title="Lihat Detail"
                                       class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    @if(!auth()->user()->isPemantau() && !auth()->user()->isOpd())
                                        @if(auth()->user()->isSuperAdmin() || (auth()->user()->isAdminSumber() && in_array($p->status_pengajuan, ['DRAFT', 'PERLU REVISI', 'DITOLAK'])))
                                            <a href="{{ route('pemeriksaan.edit', $p) }}" title="Edit Pemeriksaan"
                                               class="rounded-lg p-1.5 text-primary-600 hover:bg-primary-50 transition">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>

                                            <form method="POST" action="{{ route('pemeriksaan.destroy', $p) }}"
                                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus data pemeriksaan ini?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Hapus Pemeriksaan"
                                                        class="rounded-lg p-1.5 text-state-danger hover:bg-red-50 transition">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    @endif

                                    {{-- Tombol Arsipkan Laporan (hanya muncul jika semua temuan selesai) --}}
                                    @if((auth()->user()->isAdminSumber() || auth()->user()->isSuperAdmin()) && $p->isFullyResolved())
                                        <form method="POST" action="{{ route('pemeriksaan.archive', $p) }}"
                                              onsubmit="return confirm('Arsipkan laporan ini?\n\nLaporan akan dihapus dari daftar aktif, tetapi data temuan dan tindak lanjut tetap tersimpan di Laporan Rekapan.\n\nLanjutkan?');" class="inline">
                                            @csrf
                                            <button type="submit" title="Arsipkan Laporan (Selesai)"
                                                    class="rounded-lg p-1.5 text-state-warning hover:bg-amber-50 transition">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-table-empty-state colspan="6">
                            <svg class="h-10 w-10 text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            <p class="text-sm font-medium">Belum ada data pemeriksaan yang ditemukan.</p>
                            @if($search || $sumber || $opdId || $tahun || $statusPengajuan || ($penyelesaian ?? null))
                                <x-action-button href="{{ route('pemeriksaan.index') }}" variant="filter-reset">
                                    Hapus filter dan lihat semua data
                                </x-action-button>
                            @endif
                        </x-table-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($pemeriksaans->hasPages())
            <div class="border-t border-gray-200 px-6 py-4">
                {{ $pemeriksaans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
