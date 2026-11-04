@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('page-title', 'Manajemen Pengguna')
@section('page-subtitle', 'Kelola akun pengguna sistem dan penugasan peran (role-based access control)')

@section('content')
<div class="space-y-6">
    {{-- Action Bar & Filters --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        {{-- Search & Filter Form --}}
        <form method="GET" action="{{ route('user.index') }}" class="idds-filter-bar min-w-0 flex-1">
            <div class="idds-filter-search relative min-w-0">
            <input type="text" name="search" value="{{ $search }}" aria-label="Cari pengguna"
                       placeholder="Cari nama atau email pengguna..."
                       class="w-full border bg-white py-2.5 pl-10 pr-4 text-sm text-gray-800 placeholder-gray-400 shadow-sm idds-form-control">
                <svg class="absolute left-3.5 top-3 h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <select name="role_id" aria-label="Filter berdasarkan peran pengguna"
                    class="border bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm idds-form-control">
                <option value="">Semua Role</option>
                @foreach($roles as $r)
                    <option value="{{ $r->id }}" {{ $roleId == $r->id ? 'selected' : '' }}>{{ $r->display_name }}</option>
                @endforeach
            </select>

            <select name="status" aria-label="Filter berdasarkan status pengguna"
                    class="border bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm idds-form-control">
                <option value="">Semua Status</option>
                <option value="1" {{ $status === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ $status === '0' ? 'selected' : '' }}>Nonaktif</option>
                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Menunggu Aktivasi</option>
            </select>

            <x-action-button variant="filter" type="submit">Filter</x-action-button>

            @if($search || $roleId || ($status !== null && $status !== ''))
                <a href="{{ route('user.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    Reset
                </a>
            @endif
        </form>

        {{-- Add Button --}}
        <x-action-button href="{{ route('user.create') }}" variant="primary-compact">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Pengguna
        </x-action-button>
    </div>

    {{-- Users Table Card --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm">
        <div class="idds-table-region overflow-x-auto" role="region" aria-label="Daftar pengguna. Geser horizontal untuk melihat seluruh kolom." tabindex="0">
            <p class="mb-2 text-xs text-gray-500 sm:hidden" aria-hidden="true">Geser tabel ke samping untuk melihat kolom lainnya.</p>
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50/75 border-b border-gray-200 text-xs tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold">No</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Pengguna</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Peran / Role</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Tautan Data</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-center">Status</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $index => $u)
                        <tr class="hover:bg-primary-50/30 transition">
                            <td class="px-6 py-4 font-medium text-gray-500">
                                {{ $users->firstItem() + $index }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-100 text-sm font-bold text-primary-700">
                                        {{ strtoupper(substr($u->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $u->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $u->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full bg-primary-100 px-3 py-1 text-xs font-semibold text-primary-800">
                                    {{ $u->role->display_name ?? 'Tanpa Role' }}
                                </span>
                                @if($u->getSumberPemeriksaan())
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 ml-1">
                                        {{ $u->getSumberPemeriksaan() }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($u->pegawai)
                                    <p class="text-xs font-medium text-gray-800">Pegawai: {{ $u->pegawai->nama }}</p>
                                    <p class="text-xs text-gray-500">{{ $u->pegawai->jabatan }}</p>
                                @elseif($u->opd)
                                    <p class="text-xs font-medium text-emerald-800">OPD: {{ $u->opd->nama }}</p>
                                    <p class="text-xs text-gray-500">{{ $u->opd->kode }}</p>
                                @else
                                    <span class="text-xs text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($u->activation_required && ! $u->activated_at)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                        Menunggu Aktivasi
                                    </span>
                                @elseif($u->id === auth()->id())
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Aktif (Anda)
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('user.toggle-status', $u) }}" class="inline"
                                          data-confirm-title="Ubah status akun?"
                                          data-confirm-message="{{ $u->is_active
                                              ? 'Nonaktifkan akun ' . $u->name . '? Pengguna ini tidak akan dapat masuk ke sistem sampai diaktifkan kembali.'
                                              : 'Aktifkan kembali akun ' . $u->name . '?' }}"
                                          data-confirm-variant="warning"
                                          data-confirm-accept="{{ $u->is_active ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan' }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" title="Klik untuk mengubah status"
                                                data-loading-text="Mengubah..."
                                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium transition {{ $u->is_active ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-rose-100 text-rose-700 hover:bg-rose-200' }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $u->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                            {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($u->activation_required && ! $u->activated_at)
                                        <form method="POST" action="{{ route('user.send-activation', $u) }}" class="inline"
                                              data-confirm-title="Kirim ulang aktivasi?"
                                              data-confirm-message="{{ 'Kirim ulang tautan aktivasi ke ' . $u->email . '?' }}"
                                              data-confirm-variant="submit"
                                              data-confirm-accept="Kirim Ulang">
                                            @csrf
                                            <button type="submit" title="Kirim ulang email aktivasi" aria-label="Kirim ulang email aktivasi kepada {{ $u->name }}"
                                                    data-loading-text="Mengirim..."
                                                    class="rounded-lg px-2 py-1.5 text-xs font-medium text-primary-700 hover:bg-primary-50 transition">
                                                Kirim Ulang Aktivasi
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('user.edit', $u) }}" title="Edit Pengguna"
                                       class="rounded-lg p-1.5 text-primary-600 hover:bg-primary-50 transition">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    @if($u->id !== auth()->id())
                                        <form method="POST" action="{{ route('user.destroy', $u) }}" class="inline"
                                              data-confirm-title="Hapus akun?"
                                              data-confirm-message="{{ 'Apakah Anda yakin ingin menghapus akun ' . $u->name . '?' }}"
                                              data-confirm-variant="danger"
                                              data-confirm-accept="Ya, Hapus">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Hapus Pengguna"
                                                    class="rounded-lg p-1.5 text-state-danger hover:bg-red-50 transition">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <p class="text-sm font-medium">Belum ada data pengguna yang ditemukan.</p>
                            @if($search || $roleId || ($status !== null && $status !== ''))
                                <x-action-button href="{{ route('user.index') }}" variant="filter-reset">
                                    Hapus filter dan lihat semua data
                                </x-action-button>
                            @endif
                        </x-table-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
            <div class="border-t border-gray-200 px-6 py-4">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
