@extends('layouts.app')

@section('title', 'Audit Log Aktivitas')
@section('page-title', 'Audit Trail & Log Aktivitas Sistem')
@section('page-subtitle', 'Pencatatan rekam jejak aksi pengguna, login/logout, pengajuan, persetujuan, dan keamanan (PRD Seksi 22)')

@section('content')
<div class="space-y-6">
    {{-- Search & Filter Bar --}}
    <div class="rounded-2xl border border-gray-200/80 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('audit-log.index') }}" class="idds-filter-bar">
            <div class="idds-filter-search relative min-w-0">
                <input type="text" name="search" value="{{ $search }}" aria-label="Cari audit log"
                       placeholder="Cari deskripsi, nama pengguna, atau IP address..."
                       class="w-full border bg-white py-2.5 pl-10 pr-4 text-sm text-gray-800 placeholder-gray-400 shadow-sm idds-form-control">
                <svg class="absolute left-3.5 top-3 h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <select name="action" aria-label="Filter berdasarkan aksi"
                    class="border bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm idds-form-control">
                <option value="">Semua Aksi</option>
                @foreach($actions as $a)
                    <option value="{{ $a }}" {{ $action === $a ? 'selected' : '' }}>{{ \App\Models\AuditLog::actionLabel($a) }}</option>
                @endforeach
            </select>

            <select name="module" aria-label="Filter berdasarkan modul"
                    class="border bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm idds-form-control">
                <option value="">Semua Modul</option>
                @foreach($modules as $m)
                    <option value="{{ $m }}" {{ $module === $m ? 'selected' : '' }}>{{ \App\Models\AuditLog::moduleLabel($m) }}</option>
                @endforeach
            </select>

            <div class="idds-filter-date-range min-w-0">
                <input type="date" name="date_from" value="{{ $dateFrom }}" aria-label="Tanggal mulai audit log" placeholder="Dari"
                       class="min-w-0 flex-1 border bg-white px-3 py-2 text-xs text-gray-700 shadow-sm sm:flex-initial idds-form-control">
                <span class="text-xs text-gray-500">-</span>
                <input type="date" name="date_to" value="{{ $dateTo }}" aria-label="Tanggal akhir audit log" placeholder="Sampai"
                       class="min-w-0 flex-1 border bg-white px-3 py-2 text-xs text-gray-700 shadow-sm sm:flex-initial idds-form-control">
            </div>

            <x-action-button variant="filter" type="submit">Filter</x-action-button>

            @if($search || $action || $module || $dateFrom || $dateTo)
                <a href="{{ route('audit-log.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    Reset
                </a>
            @endif
        </form>
    </div>

    {{-- Audit Log Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm">
        <div class="idds-table-region overflow-x-auto" role="region" aria-label="Daftar audit log. Geser horizontal untuk melihat seluruh kolom." tabindex="0">
            <p class="mb-2 px-4 pt-3 text-xs text-gray-500 sm:hidden" aria-hidden="true">Geser tabel ke samping untuk melihat kolom lainnya.</p>
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50/75 border-b border-gray-200 text-xs tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold">Waktu & IP Address</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Pengguna / Role</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-center">Aksi & Modul</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Deskripsi Aktivitas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-primary-50/20 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-bold text-gray-900 block">{{ $log->created_at->translatedFormat('d M Y, H:i:s') }} WITA</span>
                                <span class="text-xs text-gray-500 font-mono">IP: {{ $log->ip_address ?? '127.0.0.1' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-bold text-gray-900 block">{{ $log->user_name }}</span>
                                <span class="text-xs text-primary-700 font-semibold">{{ $log->role_name }}</span>
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                @php $badge = $log->action_badge; @endphp
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $badge['class'] }}">
                                    {{ $badge['label'] }}
                                </span>
                                <span class="block text-xs text-gray-500  mt-1 font-bold">
                                    {{ \App\Models\AuditLog::moduleLabel($log->module) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-xs text-gray-800 leading-relaxed font-medium">{{ $log->description }}</p>
                                @if($log->user_agent)
                                    <p class="text-xs text-gray-500 truncate max-w-md mt-0.5" title="{{ $log->user_agent }}">
                                        {{ $log->user_agent }}
                                    </p>
                                @endif
                                @if(!empty($log->old_values) || !empty($log->new_values))
                                    <button type="button" onclick="document.getElementById('diff-{{ $log->id }}').classList.toggle('hidden')" class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-primary-600 hover:text-primary-800 transition bg-primary-50 px-2.5 py-1 rounded-md border border-primary-100">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        Rincian Perubahan
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @if(!empty($log->old_values) || !empty($log->new_values))
                            <tr id="diff-{{ $log->id }}" class="hidden bg-gray-50/50">
                                <td colspan="4" class="px-6 py-4 border-t border-dashed border-gray-200">
                                    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm">
                                        <table class="w-full text-left text-xs">
                                            <thead class="bg-gray-100 text-gray-600">
                                                <tr>
                                                    <th scope="col" class="px-4 py-2.5 font-semibold w-1/3">Kolom / Atribut Data</th>
                                                    <th scope="col" class="px-4 py-2.5 font-semibold w-1/3">Nilai Sebelum</th>
                                                    <th scope="col" class="px-4 py-2.5 font-semibold w-1/3">Nilai Sesudah</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @php
                                                    $allKeys = array_unique(array_merge(
                                                        is_array($log->old_values) ? array_keys($log->old_values) : [],
                                                        is_array($log->new_values) ? array_keys($log->new_values) : []
                                                    ));
                                                @endphp
                                                @foreach($allKeys as $key)
                                                    @php
                                                        $oldVal = is_array($log->old_values) && array_key_exists($key, $log->old_values) ? $log->old_values[$key] : null;
                                                        $newVal = is_array($log->new_values) && array_key_exists($key, $log->new_values) ? $log->new_values[$key] : null;
                                                        
                                                        $isChanged = $oldVal !== $newVal;
                                                    @endphp
                                                    <tr class="{{ $isChanged ? 'bg-yellow-50/30' : '' }}">
                                                        <td class="px-4 py-3 font-medium text-gray-700">
                                                            {{ \App\Models\AuditLog::attributeLabel($key) }}
                                                            <div class="text-xs text-gray-500 font-mono mt-0.5">{{ $key }}</div>
                                                        </td>
                                                        <td class="px-4 py-3 align-top">
                                                            @if($oldVal !== null || (is_array($log->old_values) && array_key_exists($key, $log->old_values)))
                                                                <div class="inline-block rounded px-2.5 py-1.5 bg-rose-50 text-rose-700 border border-rose-100 {{ $isChanged ? 'line-through decoration-rose-400 opacity-80' : '' }} whitespace-pre-wrap max-w-full">
                                                                    {{ \App\Models\AuditLog::formatAttributeValue($key, $oldVal) }}
                                                                </div>
                                                            @else
                                                                <span class="text-gray-500 italic text-xs">- Kosong / Baru Ditambahkan -</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-3 align-top">
                                                            @if($newVal !== null || (is_array($log->new_values) && array_key_exists($key, $log->new_values)))
                                                                <div class="inline-block rounded px-2.5 py-1.5 bg-emerald-50 text-emerald-800 border border-emerald-100 font-medium whitespace-pre-wrap max-w-full shadow-2xs">
                                                                    {{ \App\Models\AuditLog::formatAttributeValue($key, $newVal) }}
                                                                </div>
                                                            @else
                                                                <span class="text-gray-500 italic text-xs">- Dikosongkan / Dihapus -</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <x-table-empty-state colspan="4">
                            <p class="text-sm font-medium">Belum ada catatan riwayat audit trail log.</p>
                            @if($search || $action || $module || $dateFrom || $dateTo)
                                <div class="mt-3">
                                    <x-action-button href="{{ route('audit-log.index') }}" variant="filter-reset">
                                        Hapus filter dan lihat semua catatan
                                    </x-action-button>
                                </div>
                            @endif
                        </x-table-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="border-t border-gray-200 px-6 py-4">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
