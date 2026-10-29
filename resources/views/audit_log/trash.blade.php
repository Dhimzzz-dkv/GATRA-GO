@extends('layouts.app')

@section('title', 'Arsip & Pemulihan Data')
@section('page-title', 'Arsip & Pemulihan Data')
@section('page-subtitle', 'Manajemen data yang telah dihapus/diarsipkan. Anda dapat melihat rincian, memulihkan, atau menghapus permanen.')

@php
    $tabs = [
        'pemeriksaan' => ['label' => 'Pemeriksaan & SPT', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        'temuan' => ['label' => 'Temuan', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z'],
        'opd' => ['label' => 'Master OPD', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
        'pegawai' => ['label' => 'Pegawai', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
        'user' => ['label' => 'Pengguna', 'icon' => 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];
    $searchPlaceholder = match($type) {
        'temuan' => 'Cari nomor temuan, judul temuan, atau nama OPD...',
        'opd' => 'Cari kode atau nama OPD...',
        'pegawai' => 'Cari NIP, nama, atau jabatan...',
        'user' => 'Cari nama atau email pengguna...',
        default => 'Cari nomor SPT, judul, atau nama OPD...',
    };
    $showSumberTahun = in_array($type, ['pemeriksaan', 'temuan'], true);
@endphp

@section('content')
<div class="space-y-6">

    {{-- Tab Tipe Arsip --}}
    <div class="rounded-2xl border border-gray-200/80 bg-white p-2 shadow-sm">
        <nav class="flex flex-wrap gap-1.5">
            @foreach($tabs as $key => $meta)
                @php $isActive = $type === $key; @endphp
                <a href="{{ route('audit-log.trash', ['type' => $key]) }}"
                   class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2.5 text-xs font-bold transition
                          {{ $isActive
                                ? 'bg-primary-600 text-white shadow-sm'
                                : 'text-gray-600 hover:bg-gray-100' }}">
                    <svg class="h-4 w-4 {{ $isActive ? 'text-primary-100' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $meta['icon'] }}"/>
                    </svg>
                    <span>{{ $meta['label'] }}</span>
                    <span class="inline-flex items-center justify-center rounded-full px-2 py-0.5 text-xs font-bold
                                 {{ $isActive ? 'bg-white/25 text-white' : 'bg-gray-100 text-gray-600' }}">
                        {{ $typeCounts[$key] ?? 0 }}
                    </span>
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Filter & Search Card --}}
    <div class="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('audit-log.trash') }}" class="idds-filter-bar">
            <input type="hidden" name="type" value="{{ $type }}">

            {{-- Search Input --}}
            <div class="idds-filter-search min-w-0">
                <label for="search" class="block text-xs font-bold tracking-wider text-gray-700 mb-1.5">Pencarian Data</label>
                <div class="relative">
                    <input type="text" name="search" id="search" value="{{ $search }}"
                           placeholder="{{ $searchPlaceholder }}"
                           class="w-full border bg-white pl-10 pr-4 py-2 text-xs font-medium text-gray-800 placeholder-gray-400 idds-form-control">
                    <svg class="absolute left-3.5 top-2.5 h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            @if($showSumberTahun)
                {{-- Sumber Filter --}}
                <div>
                    <label for="sumber" class="block text-xs font-bold tracking-wider text-gray-700 mb-1.5">Sumber</label>
                    <select name="sumber" id="sumber"
                            class="w-full border bg-white px-3 py-2 text-xs font-medium text-gray-800 idds-form-control">
                        <option value="">Semua Sumber</option>
                        @foreach($sumberList as $s)
                            <option value="{{ $s }}" {{ $sumber === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tahun" class="block text-xs font-bold tracking-wider text-gray-700 mb-1.5">Tahun</label>
                    <select name="tahun" id="tahun"
                            class="w-full border bg-white px-3 py-2 text-xs font-medium text-gray-800 idds-form-control">
                        <option value="">Semua Tahun</option>
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="idds-filter-actions">
                <x-action-button type="submit" variant="filter">
                    Filter
                </x-action-button>
                @if($search || ($showSumberTahun && ($sumber || $tahun)))
                    <x-action-button href="{{ route('audit-log.trash', ['type' => $type]) }}" variant="filter-reset">
                        Reset
                    </x-action-button>
                @endif
            </div>
        </form>
    </div>

    {{-- Content Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-xs font-bold  tracking-wider text-gray-700 flex items-center gap-2">
                <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                {{ $tabs[$type]['label'] }} Dalam Arsip ({{ $typeCounts[$type] ?? 0 }})
            </h3>
        </div>

        <div class="idds-table-region overflow-x-auto" role="region" aria-label="Daftar data dalam arsip. Geser horizontal untuk melihat seluruh kolom." tabindex="0">
            <p class="mb-2 px-4 pt-3 text-xs text-gray-500 sm:hidden" aria-hidden="true">Geser tabel ke samping untuk melihat kolom lainnya.</p>
            @if($type === 'pemeriksaan')
                {{-- ===================== PEMERIKSAAN ===================== --}}
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50/75 border-b border-gray-200 text-xs tracking-wider text-gray-500 font-bold">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Nomor SPT &amp; Tanggal</th>
                            <th scope="col" class="px-6 py-3.5">Sumber &amp; OPD Target</th>
                            <th scope="col" class="px-6 py-3.5">Judul Kegiatan</th>
                            <th scope="col" class="px-6 py-3.5 text-center">Rincian Data</th>
                            <th scope="col" class="px-6 py-3.5">Tanggal Dihapus</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($trashedItems as $p)
                            <tr class="hover:bg-primary-50/20 transition">
                                <td class="px-6 py-4">
                                    <span class="font-bold text-gray-900 block">{{ $p->nomor_spt }}</span>
                                    <span class="text-xs text-gray-500 font-medium">{{ $p->tanggal_spt?->translatedFormat('d M Y') ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <x-sumber-badge :sumber="$p->sumber_pemeriksaan" size="xs" class="mb-1" />
                                    <p class="text-xs font-semibold text-gray-800">{{ $p->opd->nama ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4 max-w-xs">
                                    <p class="text-xs text-gray-700 line-clamp-2">{{ $p->judul ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-primary-50 text-primary-700 border border-primary-200">
                                            {{ $p->lhps->count() }} LHP
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-primary-50 text-primary-700 border border-primary-200">
                                            {{ $p->temuans->count() }} Temuan
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500 whitespace-nowrap">
                                    {{ $p->deleted_at->translatedFormat('d M Y, H:i') }}
                                </td>
                                <td class="px-6 py-4 text-right space-x-1.5 whitespace-nowrap">
                                    <a href="{{ route('audit-log.show-pemeriksaan', $p->id) }}"
                                       class="inline-flex items-center gap-1 rounded-xl bg-primary-50 px-3 py-1.5 text-xs font-bold text-primary-700 border border-primary-200 hover:bg-primary-100 transition shadow-2xs cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Lihat Detail
                                    </a>

                                    @include('audit_log._trash-actions', [
                                        'type' => 'pemeriksaan',
                                        'id' => $p->id,
                                        'restoreMessage' => 'Pulihkan data pemeriksaan ini ke daftar aktif? Data temuan di bawahnya juga akan ikut dipulihkan.',
                                        'forceMessage' => 'PERINGATAN KRITIS: Data pemeriksaan, seluruh berkas LHP, temuan, dan tindak lanjut di bawahnya akan DIHAPUS PERMANEN dari database dan tidak dapat dipulihkan lagi. Lanjutkan?',
                                    ])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center text-gray-500">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <div class="h-12 w-12 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto text-gray-500">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                            </svg>
                                        </div>
                                        <p class="text-xs font-bold text-gray-700">Tidak ada pemeriksaan di arsip</p>
                                        <p class="text-xs text-gray-500">Semua data pemeriksaan aktif atau belum pernah dihapus.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            @elseif($type === 'temuan')
                {{-- ===================== TEMUAN ===================== --}}
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50/75 border-b border-gray-200 text-xs tracking-wider text-gray-500 font-bold">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Nomor &amp; Judul Temuan</th>
                            <th scope="col" class="px-6 py-3.5">Sumber &amp; Tahun</th>
                            <th scope="col" class="px-6 py-3.5">OPD Target</th>
                            <th scope="col" class="px-6 py-3.5">Nilai Kerugian</th>
                            <th scope="col" class="px-6 py-3.5">Tanggal Dihapus</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($trashedItems as $t)
                            <tr class="hover:bg-primary-50/20 transition">
                                <td class="px-6 py-4 max-w-xs">
                                    <span class="font-bold text-gray-900 block text-xs">{{ $t->nomor_temuan ?? '-' }}</span>
                                    <span class="text-xs text-gray-700 line-clamp-2">{{ $t->judul_temuan ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <x-sumber-badge :sumber="$t->sumber_pemeriksaan" size="xs" />
                                    <span class="text-xs text-gray-500 font-medium ml-1">{{ $t->tahun }}</span>
                                </td>
                                <td class="px-6 py-4 text-xs font-semibold text-gray-800 max-w-[180px]">
                                    {{ $t->opd->nama ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($t->ada_unsur_kerugian && $t->nilai_kerugian > 0)
                                        <span class="text-xs font-bold text-state-danger">Rp {{ number_format($t->nilai_kerugian, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-xs text-gray-500 italic">Administrasi</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500 whitespace-nowrap">
                                    {{ $t->deleted_at->translatedFormat('d M Y, H:i') }}
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    @include('audit_log._trash-actions', [
                                        'type' => 'temuan',
                                        'id' => $t->id,
                                        'restoreMessage' => 'Pulihkan temuan ini ke daftar aktif?',
                                        'forceMessage' => 'PERINGATAN KRITIS: Temuan beserta seluruh dokumen pendukung dan tindak lanjutnya akan DIHAPUS PERMANEN dan tidak dapat dipulihkan lagi. Lanjutkan?',
                                    ])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center text-gray-500">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <div class="h-12 w-12 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto text-gray-500">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                            </svg>
                                        </div>
                                        <p class="text-xs font-bold text-gray-700">Tidak ada temuan di arsip</p>
                                        <p class="text-xs text-gray-500">Semua temuan aktif atau belum pernah dihapus.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            @elseif($type === 'opd')
                {{-- ===================== OPD ===================== --}}
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50/75 border-b border-gray-200 text-xs tracking-wider text-gray-500 font-bold">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Kode</th>
                            <th scope="col" class="px-6 py-3.5">Nama Instansi</th>
                            <th scope="col" class="px-6 py-3.5 text-center">Akun Terkait</th>
                            <th scope="col" class="px-6 py-3.5">Tanggal Dihapus</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($trashedItems as $o)
                            <tr class="hover:bg-primary-50/20 transition">
                                <td class="px-6 py-4">
                                    <span class="font-bold text-gray-900">{{ $o->kode }}</span>
                                </td>
                                <td class="px-6 py-4 text-xs font-semibold text-gray-800 max-w-sm">
                                    {{ $o->nama ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-primary-50 text-primary-700 border border-primary-200">
                                        {{ $o->users()->withTrashed()->count() }} akun
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500 whitespace-nowrap">
                                    {{ $o->deleted_at->translatedFormat('d M Y, H:i') }}
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    @include('audit_log._trash-actions', [
                                        'type' => 'opd',
                                        'id' => $o->id,
                                        'restoreMessage' => 'Pulihkan OPD ini ke daftar aktif?',
                                        'forceMessage' => 'PERINGATAN KRITIS: OPD akan DIHAPUS PERMANEN dan tidak dapat dipulihkan lagi. Lanjutkan?',
                                    ])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center text-gray-500">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <div class="h-12 w-12 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto text-gray-500">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                            </svg>
                                        </div>
                                        <p class="text-xs font-bold text-gray-700">Tidak ada OPD di arsip</p>
                                        <p class="text-xs text-gray-500">Semua data OPD aktif atau belum pernah dihapus.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            @elseif($type === 'pegawai')
                {{-- ===================== PEGAWAI ===================== --}}
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50/75 border-b border-gray-200 text-xs tracking-wider text-gray-500 font-bold">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">NIP</th>
                            <th scope="col" class="px-6 py-3.5">Nama &amp; Jabatan</th>
                            <th scope="col" class="px-6 py-3.5">Unit / Bidang</th>
                            <th scope="col" class="px-6 py-3.5">Tanggal Dihapus</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($trashedItems as $pg)
                            <tr class="hover:bg-primary-50/20 transition">
                                <td class="px-6 py-4">
                                    <span class="font-bold text-gray-900 text-xs">{{ $pg->nip ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-4 max-w-xs">
                                    <span class="text-xs font-semibold text-gray-800 block">{{ $pg->nama }}</span>
                                    <span class="text-xs text-gray-500">{{ $pg->jabatan ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-700 max-w-[180px]">
                                    {{ $pg->unit_bidang ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500 whitespace-nowrap">
                                    {{ $pg->deleted_at->translatedFormat('d M Y, H:i') }}
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    @include('audit_log._trash-actions', [
                                        'type' => 'pegawai',
                                        'id' => $pg->id,
                                        'restoreMessage' => 'Pulihkan data pegawai ini ke daftar aktif?',
                                        'forceMessage' => 'PERINGATAN KRITIS: Data pegawai akan DIHAPUS PERMANEN dan tidak dapat dipulihkan lagi. Lanjutkan?',
                                    ])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center text-gray-500">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <div class="h-12 w-12 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto text-gray-500">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                            </svg>
                                        </div>
                                        <p class="text-xs font-bold text-gray-700">Tidak ada pegawai di arsip</p>
                                        <p class="text-xs text-gray-500">Semua data pegawai aktif atau belum pernah dihapus.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            @else
                {{-- ===================== USER ===================== --}}
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50/75 border-b border-gray-200 text-xs tracking-wider text-gray-500 font-bold">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Nama</th>
                            <th scope="col" class="px-6 py-3.5">Email</th>
                            <th scope="col" class="px-6 py-3.5">Peran</th>
                            <th scope="col" class="px-6 py-3.5">Tanggal Dihapus</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($trashedItems as $u)
                            <tr class="hover:bg-primary-50/20 transition">
                                <td class="px-6 py-4">
                                    <span class="font-bold text-gray-900 text-xs">{{ $u->name }}</span>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-700">
                                    {{ $u->email }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-primary-50 text-primary-700 border border-primary-200">
                                        {{ $u->role->display_name ?? ($u->role->label ?? '-') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500 whitespace-nowrap">
                                    {{ $u->deleted_at->translatedFormat('d M Y, H:i') }}
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    @include('audit_log._trash-actions', [
                                        'type' => 'user',
                                        'id' => $u->id,
                                        'restoreMessage' => 'Pulihkan akun pengguna ini ke daftar aktif?',
                                        'forceMessage' => 'PERINGATAN KRITIS: Akun pengguna akan DIHAPUS PERMANEN dan tidak dapat dipulihkan lagi. Lanjutkan?',
                                    ])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center text-gray-500">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <div class="h-12 w-12 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto text-gray-500">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                            </svg>
                                        </div>
                                        <p class="text-xs font-bold text-gray-700">Tidak ada pengguna di arsip</p>
                                        <p class="text-xs text-gray-500">Semua akun pengguna aktif atau belum pernah dihapus.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>

        @if($trashedItems->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $trashedItems->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
