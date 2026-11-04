@extends('layouts.app')

@section('title', 'Daftar Temuan')
@section('page-title', 'Daftar Temuan Hasil Pemeriksaan')
@section('page-subtitle', 'Monitoring dan rekapitulasi temuan administrasi dan kerugian daerah / negara')

@section('content')
<div class="space-y-6">
    {{-- Summary Stats Bar --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Temuan --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold  tracking-wider text-gray-500">Total Temuan</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ $totalTemuan }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-100 text-primary-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total Nilai Kerugian --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold  tracking-wider text-gray-500">Total Nilai Kerugian</p>
                    <p class="mt-1 text-lg font-bold text-state-danger truncate">
                        Rp {{ number_format($totalNilaiKerugian, 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-100 text-state-danger">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Temuan Administrasi --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold  tracking-wider text-gray-500">Unsur Administrasi</p>
                    <p class="mt-1 text-2xl font-bold text-blue-600">{{ $totalAdministrasi }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Temuan Kerugian --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold  tracking-wider text-gray-500">Unsur Kerugian</p>
                    <p class="mt-1 text-2xl font-bold text-state-warning">{{ $totalKerugian }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-state-warning">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters & Add Action --}}
    <div class="space-y-4">
        <div class="rounded-2xl border border-gray-200/80 bg-white p-4 shadow-sm sm:p-5">
            {{-- Search & Filters --}}
            <form method="GET" action="{{ route('temuan.index') }}" class="idds-filter-bar">
                <div class="idds-filter-search relative min-w-0">
                    <input type="text" name="search" value="{{ $search }}" aria-label="Cari temuan"
                           placeholder="Cari nomor temuan, judul, atau uraian..."
                           class="h-10 w-full border bg-white py-2.5 pl-10 pr-4 text-sm text-gray-800 placeholder-gray-400 shadow-sm idds-form-control">
                    <svg class="absolute left-3.5 top-3 h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                @if(auth()->user()->isSuperAdmin() || auth()->user()->isPemantau())
                    <select name="sumber" aria-label="Filter berdasarkan sumber pemeriksaan"
                            class="h-10 min-w-[130px] flex-[1_1_130px] border bg-white px-3 text-sm text-gray-700 shadow-sm idds-form-control">
                        <option value="">Semua Sumber</option>
                        @foreach($sumberList as $s)
                            <option value="{{ $s }}" {{ $sumber === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                @endif

                @if(!auth()->user()->isOpd())
                    <select name="opd_id" aria-label="Filter berdasarkan OPD"
                            class="h-10 min-w-[110px] flex-[1_1_110px] border bg-white px-3 text-sm text-gray-700 shadow-sm idds-form-control">
                        <option value="">Semua OPD</option>
                        @foreach($opds as $o)
                            <option value="{{ $o->id }}" {{ $opdId == $o->id ? 'selected' : '' }}>{{ $o->kode }}</option>
                        @endforeach
                    </select>
                @endif

                <select name="tahun" aria-label="Filter berdasarkan tahun pemeriksaan"
                        class="h-10 min-w-[110px] flex-[1_1_110px] border bg-white px-3 text-sm text-gray-700 shadow-sm idds-form-control">
                    <option value="">Semua Tahun</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>

                <select name="jenis" aria-label="Filter berdasarkan unsur temuan"
                        class="h-10 min-w-[120px] flex-[1_1_120px] border bg-white px-3 text-sm text-gray-700 shadow-sm idds-form-control">
                    <option value="">Semua Unsur</option>
                    <option value="administrasi" {{ $jenis === 'administrasi' ? 'selected' : '' }}>Administrasi</option>
                    <option value="kerugian" {{ $jenis === 'kerugian' ? 'selected' : '' }}>Kerugian</option>
                    <option value="keduanya" {{ $jenis === 'keduanya' ? 'selected' : '' }}>Keduanya</option>
                </select>

                <select name="status_penyelesaian" aria-label="Filter berdasarkan status penyelesaian"
                        class="h-10 min-w-[190px] flex-[1.5_1_190px] border bg-white px-3 text-sm text-gray-700 shadow-sm idds-form-control">
                    <option value="">Semua Status Penyelesaian</option>
                    <option value="Belum Ditindaklanjuti" {{ $statusPenyelesaian === 'Belum Ditindaklanjuti' ? 'selected' : '' }}>Belum Ditindaklanjuti</option>
                    <option value="Belum Sesuai" {{ $statusPenyelesaian === 'Belum Sesuai' ? 'selected' : '' }}>Belum Sesuai</option>
                    <option value="Sesuai" {{ $statusPenyelesaian === 'Sesuai' ? 'selected' : '' }}>Sesuai</option>
                    <option value="Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah" {{ $statusPenyelesaian === 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah' ? 'selected' : '' }}>Tidak Dapat Ditindaklanjuti</option>
                </select>

                <div class="idds-filter-actions">
                    <x-action-button variant="filter" type="submit">
                        Filter
                    </x-action-button>

                    @if($search || $sumber || $opdId || $tahun || $jenis || $statusPenyelesaian)
                        <a href="{{ route('temuan.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-xl px-3 text-sm font-medium text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            {{-- Tambah Temuan (Super Admin & Admin Sumber) --}}
            @if(!auth()->user()->isPemantau() && !auth()->user()->isOpd())
                <div class="mt-3 flex flex-col gap-2 border-t border-gray-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('temuan.create') }}"
                       class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-primary-700 sm:w-auto">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Tambah Temuan</span>
                    </a>
                    <span class="text-xs text-gray-500 sm:text-right">
                        Temuan juga dapat ditambahkan langsung dari halaman detail pemeriksaan.
                    </span>
                </div>
            @endif
        </div>
    </div>

    {{-- Temuan Table Card --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm">
        <div class="idds-table-region overflow-x-auto" role="region" aria-label="Daftar temuan. Geser horizontal untuk melihat seluruh kolom." tabindex="0">
            <p class="mb-2 text-xs text-gray-500 sm:hidden" aria-hidden="true">Geser tabel ke samping untuk melihat kolom lainnya.</p>
            <table class="w-full text-left text-sm text-gray-600">
                <caption class="sr-only">Daftar temuan hasil pemeriksaan</caption>
                <thead class="bg-gray-50/75 border-b border-gray-200 text-xs tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold">No</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Sumber &amp; Tahun</th>
                        <th scope="col" class="px-6 py-4 font-semibold">OPD Target</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Judul &amp; Uraian Temuan</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Unsur &amp; Nilai Kerugian</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-center">Status Penyelesaian</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($temuans as $index => $t)
                        @php
                            $detailUrl = auth()->user()->isOpd() && $t->pemeriksaan
                                ? route('pemeriksaan.show', $t->pemeriksaan)
                                : route('temuan.show', $t);
                            $detailTitle = auth()->user()->isOpd()
                                ? 'Lihat Rincian LHP dan Temuan OPD'
                                : 'Lihat Detail';
                        @endphp
                        <tr class="hover:bg-primary-50/30 transition">
                            <td class="px-6 py-4 font-medium text-gray-500">
                                {{ $temuans->firstItem() + $index }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1.5">
                                    <x-sumber-badge :sumber="$t->sumber_pemeriksaan" />
                                </div>
                                <p class="text-xs text-gray-500 font-medium mt-1">Tahun Anggaran {{ $t->tahun }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-gray-900">{{ $t->opd->nama }}</span>
                                <p class="text-xs text-gray-500">Kode: {{ $t->opd->kode }}</p>
                            </td>
                            <td class="px-6 py-4 max-w-xs">
                                <a href="{{ $detailUrl }}" class="font-semibold text-gray-900 hover:text-primary-600 transition block">
                                    {{ $t->judul_temuan }}
                                </a>
                                <p class="text-xs text-gray-500 line-clamp-2 mt-0.5">{{ $t->uraian_temuan }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @if($t->ada_unsur_administrasi)
                                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 border border-blue-200">
                                            Administrasi
                                        </span>
                                    @endif
                                    @if($t->ada_unsur_kerugian)
                                        <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-bold text-rose-700 border border-rose-200">
                                            {{ $t->formatted_nilai_kerugian }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php $badge = $t->status_penyelesaian_badge; @endphp
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium {{ $badge['class'] }}">
                                    {{ $badge['label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ $detailUrl }}" title="{{ $detailTitle }}" aria-label="{{ $detailTitle }}: {{ $t->judul_temuan }}"
                                       class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 transition">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    @if(auth()->user()->isOpd() && auth()->user()->opd_id === $t->opd_id && $t->status_penyelesaian !== 'Sesuai' && !str_contains($t->status_penyelesaian, 'Tidak Dapat Ditindaklanjuti'))
                                        <a href="{{ route('temuan.show', $t) }}" title="Input Tindak Lanjut Temuan"
                                           class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700 hover:bg-emerald-100 transition border border-emerald-200">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            TL
                                        </a>
                                    @endif

                                    @if(!auth()->user()->isPemantau() && !auth()->user()->isOpd())
                                        @if(auth()->user()->isSuperAdmin() || (auth()->user()->isAdminSumber() && $t->sumber_pemeriksaan === auth()->user()->getSumberPemeriksaan()))

                                            <a href="{{ route('temuan.edit', $t) }}" title="Edit Temuan"
                                               class="rounded-lg p-1.5 text-primary-600 hover:bg-primary-50 transition">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>

                                            <form method="POST" action="{{ route('temuan.destroy', $t) }}" class="inline"
                                                  data-confirm-title="Hapus temuan?"
                                                  data-confirm-message="{{ 'Hapus temuan ' . Str::limit($t->judul_temuan ?? 'ini', 40) . '? Tindak lanjut dan rekomendasi yang terkait juga akan ikut terhapus.' }}"
                                                  data-confirm-variant="danger"
                                                  data-confirm-accept="Ya, Hapus">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Hapus Temuan"
                                                        data-loading-text="Menghapus..."
                                                        class="rounded-lg p-1.5 text-state-danger hover:bg-red-50 transition cursor-pointer">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-table-empty-state colspan="7">
                            <svg class="h-10 w-10 text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            <p class="text-sm font-medium">Belum ada data temuan yang sesuai dengan filter.</p>
                            @if($search || $sumber || $opdId || $tahun || $jenis || $statusPenyelesaian)
                                <x-action-button href="{{ route('temuan.index') }}" variant="filter-reset">
                                    Hapus filter dan lihat semua data
                                </x-action-button>
                            @endif
                        </x-table-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($temuans->hasPages())
            <div class="border-t border-gray-200 px-6 py-4">
                {{ $temuans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
