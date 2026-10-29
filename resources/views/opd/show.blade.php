@extends('layouts.app')

@section('title', 'Detail OPD - ' . $opd->nama)
@section('page-title', 'Detail Data OPD')
@section('page-subtitle', $opd->nama)

@section('content')
<div class="max-w-4xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-breadcrumbs :items="[
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Data OPD', 'href' => route('opd.index')],
            ['label' => $opd->nama],
        ]" />

        <div class="flex items-center gap-3">
            <a href="{{ route('opd.edit', $opd) }}"
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
            <div>
                <span class="inline-flex items-center rounded-lg bg-primary-100 px-3 py-1 text-xs font-bold text-primary-800">
                    {{ $opd->kode }}
                </span>
                <h3 class="mt-2 text-xl font-bold text-gray-900">{{ $opd->nama }}</h3>
                @if($opd->singkatan)
                    <p class="text-sm text-gray-500">Dikenal sebagai: {{ $opd->singkatan }}</p>
                @endif
            </div>

            <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium {{ $opd->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $opd->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                {{ $opd->is_active ? 'Status Aktif' : 'Status Nonaktif' }}
            </span>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <p class="text-xs font-semibold  tracking-wider text-gray-500">Kepala OPD</p>
                <p class="mt-1 text-sm font-medium text-gray-900">{{ $opd->kepala_opd ?? '-' }}</p>
            </div>

            <div>
                <p class="text-xs font-semibold  tracking-wider text-gray-500">NIP Kepala</p>
                <p class="mt-1 text-sm font-medium text-gray-900">{{ $opd->nip_kepala ?? '-' }}</p>
            </div>

            <div>
                <p class="text-xs font-semibold  tracking-wider text-gray-500">Nomor Telepon</p>
                <p class="mt-1 text-sm font-medium text-gray-900">{{ $opd->telepon ?? '-' }}</p>
            </div>

            <div>
                <p class="text-xs font-semibold  tracking-wider text-gray-500">Alamat Email</p>
                <p class="mt-1 text-sm font-medium text-gray-900">{{ $opd->email ?? '-' }}</p>
            </div>

            <div class="sm:col-span-2">
                <p class="text-xs font-semibold  tracking-wider text-gray-500">Alamat Kantor</p>
                <p class="mt-1 text-sm font-medium text-gray-900">{{ $opd->alamat ?? '-' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
