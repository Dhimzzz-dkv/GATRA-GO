@extends('layouts.app')

@section('title', 'Master OPD')
@section('page-title', 'Master Data OPD')
@section('page-subtitle', 'Kelola data Organisasi Perangkat Daerah Provinsi Gorontalo')

@section('content')
<div class="space-y-6">
    {{-- Action Bar & Filters --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        {{-- Search & Filter Form --}}
        <form method="GET" action="{{ route('opd.index') }}" class="idds-filter-bar min-w-0 flex-1">
            <div class="idds-filter-search relative min-w-0">
                <input type="text" name="search" value="{{ $search }}" aria-label="Cari OPD"
                       placeholder="Cari kode, nama OPD, atau kepala..."
                       class="w-full border bg-white py-2.5 pl-10 pr-4 text-sm text-gray-800 placeholder-gray-400 shadow-sm idds-form-control">
                <svg class="absolute left-3.5 top-3 h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <select name="status" aria-label="Filter berdasarkan status OPD"
                    class="border bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm idds-form-control">
                <option value="">Semua Status</option>
                <option value="1" {{ $status === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ $status === '0' ? 'selected' : '' }}>Nonaktif</option>
            </select>

            <x-action-button variant="filter" type="submit">Filter</x-action-button>

            @if($search || $status !== null && $status !== '')
                <a href="{{ route('opd.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    Reset
                </a>
            @endif
        </form>

        {{-- Add Button --}}
        <x-action-button href="{{ route('opd.create') }}" variant="primary-compact">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah OPD
        </x-action-button>
    </div>

    {{-- OPD Table Card --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm">
        <div class="idds-table-region overflow-x-auto" role="region" aria-label="Daftar OPD. Geser horizontal untuk melihat seluruh kolom." tabindex="0">
            <p class="mb-2 text-xs text-gray-500 sm:hidden" aria-hidden="true">Geser tabel ke samping untuk melihat kolom lainnya.</p>
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50/75 border-b border-gray-200 text-xs tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold">No</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Kode / Singkatan</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Nama OPD</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Kepala OPD</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Kontak</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-center">Status</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($opds as $index => $opd)
                        <tr class="hover:bg-primary-50/30 transition">
                            <td class="px-6 py-4 font-medium text-gray-500">
                                {{ $opds->firstItem() + $index }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-lg bg-primary-100 px-2.5 py-1 text-xs font-bold text-primary-800">
                                    {{ $opd->kode }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900">
                                <a href="{{ route('opd.show', $opd) }}" class="hover:text-primary-600 transition">
                                    {{ $opd->nama }}
                                </a>
                                @if($opd->alamat)
                                    <p class="text-xs text-gray-500 truncate max-w-xs">{{ $opd->alamat }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-medium text-gray-800">{{ $opd->kepala_opd ?? '-' }}</p>
                                @if($opd->nip_kepala)
                                    <p class="text-xs text-gray-500">NIP. {{ $opd->nip_kepala }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-xs text-gray-600">{{ $opd->telepon ?? '-' }}</p>
                                <p class="text-xs text-gray-500">{{ $opd->email ?? '' }}</p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <form method="POST" action="{{ route('opd.toggle-status', $opd) }}" class="inline"
                                      data-confirm-title="Ubah status OPD?"
                                      data-confirm-message="{{ $opd->is_active
                                          ? 'Nonaktifkan OPD ' . $opd->nama . '? OPD ini tidak akan muncul pada pilihan target pemeriksaan.'
                                          : 'Aktifkan kembali OPD ' . $opd->nama . '?' }}"
                                      data-confirm-variant="warning"
                                      data-confirm-accept="{{ $opd->is_active ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan' }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" title="Klik untuk mengubah status"
                                            data-loading-text="Mengubah..."
                                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium transition {{ $opd->is_active ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-rose-100 text-rose-700 hover:bg-rose-200' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $opd->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                        {{ $opd->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('opd.show', $opd) }}" title="Lihat Detail"
                                       class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('opd.edit', $opd) }}" title="Edit Data"
                                       class="rounded-lg p-1.5 text-primary-600 hover:bg-primary-50 transition">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('opd.destroy', $opd) }}" class="inline"
                                          data-confirm-title="Hapus OPD?"
                                          data-confirm-message="{{ 'Apakah Anda yakin ingin menghapus OPD ' . $opd->nama . '?' }}"
                                          data-confirm-variant="danger"
                                          data-confirm-accept="Ya, Hapus">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus OPD"
                                                class="rounded-lg p-1.5 text-state-danger hover:bg-red-50 transition">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-table-empty-state colspan="7">
                            <svg class="h-10 w-10 text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <p class="text-sm font-medium">Belum ada data OPD yang ditemukan.</p>
                            @if($search || ($status !== null && $status !== ''))
                                <x-action-button href="{{ route('opd.index') }}" variant="filter-reset">
                                    Hapus filter dan lihat semua data
                                </x-action-button>
                            @endif
                        </x-table-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($opds->hasPages())
            <div class="border-t border-gray-200 px-6 py-4">
                {{ $opds->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
