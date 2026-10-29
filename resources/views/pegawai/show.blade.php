@extends('layouts.app')

@section('title', 'Detail Pegawai - ' . $pegawai->nama)
@section('page-title', 'Detail Pegawai Inspektorat')
@section('page-subtitle', $pegawai->nama)

@section('content')
<div class="max-w-4xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-breadcrumbs :items="[
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Pegawai Pemeriksa', 'href' => route('pegawai.index')],
            ['label' => $pegawai->nama],
        ]" />

        <div class="flex items-center gap-3">
            <a href="{{ route('pegawai.edit', $pegawai) }}"
               class="inline-flex items-center gap-1.5 rounded-xl bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
        </div>
    </div>

    {{-- Detail Card --}}
    <div class="rounded-2xl border border-gray-200/80 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 pb-5">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-primary-800 text-xl font-bold text-white shadow-sm">
                    {{ strtoupper(substr($pegawai->nama, 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">{{ $pegawai->nama }}</h3>
                    <p class="text-sm text-gray-500">NIP. {{ $pegawai->nip ?? '-' }}</p>
                </div>
            </div>

            <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium {{ $pegawai->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $pegawai->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                {{ $pegawai->is_active ? 'Pegawai Aktif' : 'Pegawai Nonaktif' }}
            </span>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <p class="text-xs font-semibold  tracking-wider text-gray-500">Jabatan</p>
                <p class="mt-1 text-sm font-medium text-gray-900">{{ $pegawai->jabatan }}</p>
            </div>

            <div>
                <p class="text-xs font-semibold  tracking-wider text-gray-500">Pangkat / Golongan</p>
                <p class="mt-1 text-sm font-medium text-gray-900">{{ $pegawai->pangkat_golongan ?? '-' }}</p>
            </div>

            <div>
                <p class="text-xs font-semibold  tracking-wider text-gray-500">Unit / Irban / Bidang</p>
                <p class="mt-1 text-sm font-medium text-gray-900">{{ $pegawai->unit_bidang }}</p>
            </div>

            <div>
                <p class="text-xs font-semibold  tracking-wider text-gray-500">Akun Pengguna Terhubung</p>
                @if($pegawai->user)
                    <p class="mt-1 text-sm font-medium text-primary-600">{{ $pegawai->user->name }} ({{ $pegawai->user->email }})</p>
                @else
                    <p class="mt-1 text-sm text-gray-500">Belum ada akun login terhubung</p>
                @endif
            </div>

            <div>
                <p class="text-xs font-semibold  tracking-wider text-gray-500">Nomor HP / WhatsApp</p>
                <p class="mt-1 text-sm font-medium text-gray-900">{{ $pegawai->no_hp ?? '-' }}</p>
            </div>

            <div>
                <p class="text-xs font-semibold  tracking-wider text-gray-500">Email Pegawai</p>
                <p class="mt-1 text-sm font-medium text-gray-900">{{ $pegawai->email ?? '-' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
