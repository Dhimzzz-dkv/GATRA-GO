@extends('layouts.app')

@section('title', 'Master Pegawai')
@section('page-title', 'Master Data Pegawai Inspektorat')
@section('page-subtitle', 'Daftar pejabat dan auditor Inspektorat Provinsi Gorontalo untuk Tim Pemeriksa dan Akun Admin')

@section('content')
<div class="space-y-6">
    {{-- Action Bar & Filters --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        {{-- Search & Filter Form --}}
        <form method="GET" action="{{ route('pegawai.index') }}" class="idds-filter-bar min-w-0 flex-1">
            <div class="idds-filter-search relative min-w-0">
            <input type="text" name="search" value="{{ $search }}" aria-label="Cari pegawai"
                       placeholder="Cari nama, NIP, atau jabatan..."
                       class="w-full border bg-white py-2.5 pl-10 pr-4 text-sm text-gray-800 placeholder-gray-400 shadow-sm idds-form-control">
                <svg class="absolute left-3.5 top-3 h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <select name="unit" aria-label="Filter berdasarkan unit atau irban"
                    class="border bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm idds-form-control">
                <option value="">Semua Unit / Irban</option>
                @foreach($units as $u)
                    <option value="{{ $u }}" {{ $unit === $u ? 'selected' : '' }}>{{ $u }}</option>
                @endforeach
            </select>

            <select name="status" aria-label="Filter berdasarkan status pegawai"
                    class="border bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm idds-form-control">
                <option value="">Semua Status</option>
                <option value="1" {{ $status === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ $status === '0' ? 'selected' : '' }}>Nonaktif</option>
            </select>

            <x-action-button variant="filter" type="submit">Filter</x-action-button>

            @if($search || $unit || ($status !== null && $status !== ''))
                <a href="{{ route('pegawai.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    Reset
                </a>
            @endif
        </form>

        {{-- Add Button --}}
        <x-action-button href="{{ route('pegawai.create') }}" variant="primary-compact">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Pegawai
        </x-action-button>
    </div>

    {{-- Pegawai Table Card --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm">
        <div class="idds-table-region overflow-x-auto" role="region" aria-label="Daftar pegawai. Geser horizontal untuk melihat seluruh kolom." tabindex="0">
            <p class="mb-2 text-xs text-gray-500 sm:hidden" aria-hidden="true">Geser tabel ke samping untuk melihat kolom lainnya.</p>
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50/75 border-b border-gray-200 text-xs tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold">No</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Nama Lengkap & NIP</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Jabatan</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Unit / Bidang</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Kontak</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-center">Status</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pegawais as $index => $pegawai)
                        <tr class="hover:bg-primary-50/30 transition">
                            <td class="px-6 py-4 font-medium text-gray-500">
                                {{ $pegawais->firstItem() + $index }}
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('pegawai.show', $pegawai) }}" class="font-semibold text-gray-900 hover:text-primary-600 transition">
                                    {{ $pegawai->nama }}
                                </a>
                                <p class="text-xs text-gray-500">NIP. {{ $pegawai->nip ?? '-' }}</p>
                                @if($pegawai->pangkat_golongan)
                                    <p class="text-xs text-primary-600/80">{{ $pegawai->pangkat_golongan }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-800">
                                {{ $pegawai->jabatan }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                    {{ $pegawai->unit_bidang }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-xs text-gray-600">{{ $pegawai->no_hp ?? '-' }}</p>
                                <p class="text-xs text-gray-500">{{ $pegawai->email ?? '' }}</p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <form method="POST" action="{{ route('pegawai.toggle-status', $pegawai) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" title="Klik untuk mengubah status"
                                            data-loading-text="Mengubah..."
                                            onclick="return confirm('{{ $pegawai->is_active
                                                ? 'Nonaktifkan pegawai ' . addslashes($pegawai->nama) . '? Pegawai ini tidak akan muncul pada pilihan anggota tim pemeriksa.'
                                                : 'Aktifkan kembali pegawai ' . addslashes($pegawai->nama) . '?' }}');"
                                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium transition {{ $pegawai->is_active ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-rose-100 text-rose-700 hover:bg-rose-200' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $pegawai->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                        {{ $pegawai->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('pegawai.show', $pegawai) }}" title="Lihat Detail"
                                       class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('pegawai.edit', $pegawai) }}" title="Edit Pegawai"
                                       class="rounded-lg p-1.5 text-primary-600 hover:bg-primary-50 transition">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('pegawai.destroy', $pegawai) }}"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus data pegawai {{ $pegawai->nama }}?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus Pegawai"
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <p class="text-sm font-medium">Belum ada data Pegawai Inspektorat yang ditemukan.</p>
                            @if($search || $unit || ($status !== null && $status !== ''))
                                <x-action-button href="{{ route('pegawai.index') }}" variant="filter-reset">
                                    Hapus filter dan lihat semua data
                                </x-action-button>
                            @endif
                        </x-table-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($pegawais->hasPages())
            <div class="border-t border-gray-200 px-6 py-4">
                {{ $pegawais->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
