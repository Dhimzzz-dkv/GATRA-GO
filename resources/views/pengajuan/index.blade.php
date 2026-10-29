@extends('layouts.app')

@section('title', 'Pengajuan & Persetujuan')
@section('page-title', 'Antrean Pengajuan & Persetujuan')
@section('page-subtitle', 'Tinjau SPT Inspektorat dan pengajuan LHP dari sumber lain — setujui atau minta revisi')

@section('content')
<div class="space-y-6">

    {{-- Kartu Ringkasan --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <a href="{{ route('pengajuan.index', ['status' => 'MENUNGGU PERSETUJUAN']) }}"
           class="group rounded-2xl border p-5 shadow-sm transition hover:shadow-md {{ $status === 'MENUNGGU PERSETUJUAN' ? 'border-amber-500/80 bg-amber-50/30 ring-2 ring-amber-500/20' : 'border-gray-200/80 bg-white' }}">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold  tracking-wider text-amber-700">Menunggu Persetujuan</p>
                    <p class="mt-1 text-2xl font-bold text-state-warning">{{ $pendingPemeriksaanCount }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-state-warning transition group-hover:bg-amber-100">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="mt-3 text-xs font-medium text-gray-500">Perlu keputusan Anda</p>
        </a>

        <a href="{{ route('pengajuan.index', ['status' => 'PERLU REVISI']) }}"
           class="group rounded-2xl border p-5 shadow-sm transition hover:shadow-md {{ $status === 'PERLU REVISI' ? 'border-orange-500/80 bg-orange-50/30 ring-2 ring-orange-500/20' : 'border-gray-200/80 bg-white' }}">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold  tracking-wider text-orange-700">Perlu Revisi</p>
                    <p class="mt-1 text-2xl font-bold text-state-warning">{{ $revisiPemeriksaanCount }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-state-warning transition group-hover:bg-orange-100">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
            </div>
            <p class="mt-3 text-xs font-medium text-gray-500">Dikembalikan ke pengaju</p>
        </a>

        <a href="{{ route('pengajuan.index', ['status' => '']) }}"
           class="group rounded-2xl border p-5 shadow-sm transition hover:shadow-md {{ $status === '' ? 'border-primary-500/80 bg-white ring-2 ring-primary-500/20' : 'border-gray-200/80 bg-white' }}">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold  tracking-wider text-gray-500">Semua Pengajuan</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">Lihat</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-50 text-primary-600 transition group-hover:bg-primary-100">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </div>
            </div>
            <p class="mt-3 text-xs font-medium text-gray-500">Seluruh riwayat</p>
        </a>
    </div>

    {{-- Filter --}}
    <div class="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('pengajuan.index') }}" class="idds-filter-bar">
            <div class="min-w-0">
                <label for="status" class="mb-1.5 block text-xs font-bold tracking-wider text-gray-700">Status Pengajuan</label>
                <select name="status" id="status"
                        class="h-10 w-full border bg-white px-3 text-sm text-gray-700 idds-form-control">
                    <option value="" {{ $status === '' ? 'selected' : '' }}>Semua Status</option>
                    <option value="MENUNGGU PERSETUJUAN" {{ $status === 'MENUNGGU PERSETUJUAN' ? 'selected' : '' }}>Menunggu Persetujuan</option>
                    <option value="DISETUJUI" {{ $status === 'DISETUJUI' ? 'selected' : '' }}>Disetujui</option>
                    <option value="PERLU REVISI" {{ $status === 'PERLU REVISI' ? 'selected' : '' }}>Perlu Revisi</option>
                    <option value="DRAFT" {{ $status === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                </select>
            </div>

            <div class="min-w-0">
                <label for="sumber" class="mb-1.5 block text-xs font-bold tracking-wider text-gray-700">Sumber</label>
                <select name="sumber" id="sumber"
                        class="h-10 w-full border bg-white px-3 text-sm text-gray-700 idds-form-control">
                    <option value="">Semua Sumber</option>
                    @foreach($sumberList as $s)
                        <option value="{{ $s }}" {{ $sumber === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-0">
                <span class="mb-1 block min-h-8 text-xs font-bold tracking-wider text-gray-700">Aksi</span>
                <div class="flex h-10 min-w-0 items-center gap-3">
                    <x-action-button variant="filter" type="submit" class="min-w-0 flex-1">
                        Filter
                    </x-action-button>

                    @if($status || $sumber)
                        <a href="{{ route('pengajuan.index') }}" class="shrink-0 text-sm font-medium text-gray-500 transition hover:text-gray-700">
                            Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Daftar Pengajuan --}}
    @if($pemeriksaans->isEmpty())
        <div class="rounded-2xl border border-gray-200/80 bg-white p-12 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-state-success">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="mt-4 text-sm font-bold text-gray-800">
                @if($status === '' || $status === 'MENUNGGU PERSETUJUAN')
                    Tidak ada pengajuan yang menunggu tindakan
                @else
                    Tidak ada pengajuan yang cocok dengan filter
                @endif
            </h3>
            <p class="mx-auto mt-1 max-w-sm text-xs text-gray-500">
                @if(($status === '' || $status === 'MENUNGGU PERSETUJUAN') && !$sumber)
                    Semua pemeriksaan sudah ditindaklanjuti. Antrean persetujuan Anda kosong.
                @else
                    Coba ubah status atau sumber, atau hapus filter untuk melihat seluruh pengajuan.
                @endif
            </p>
            @if($status || $sumber)
                <a href="{{ route('pengajuan.index') }}" class="mt-3 inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold text-primary-700 hover:bg-primary-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    Hapus filter
                </a>
            @endif
        </div>
    @else
        <div class="space-y-3">
            @foreach($pemeriksaans as $p)
                @php
                    $badge = $p->status_pengajuan_badge;
                    $isPending = $p->status_pengajuan === 'MENUNGGU PERSETUJUAN';
                    $isLhpOnlySubmission = $p->is_lhp_only_submission;
                    $reviewIdentifier = $isLhpOnlySubmission
                        ? $p->lhps->pluck('nomor_lhp')->filter()->implode(', ')
                        : $p->nomor_spt;
                @endphp

                <div class="rounded-2xl border bg-white p-5 shadow-sm transition hover:shadow-md {{ $isPending ? 'border-amber-200' : 'border-gray-200/80' }}">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        {{-- Info Utama --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold {{ $badge['class'] }}">
                                    {{ $badge['label'] }}
                                </span>
                                <x-sumber-badge :sumber="$p->sumber_pemeriksaan" size="xs" />
                                <span class="text-xs font-medium text-gray-500">TA {{ $p->tahun }}</span>
                            </div>

                            <h4 class="mt-2 truncate text-sm font-bold text-gray-900">
                                {{ $isLhpOnlySubmission ? 'Pengajuan LHP' : $p->nomor_spt }}
                            </h4>
                            <p class="mt-0.5 line-clamp-2 text-xs text-gray-600">
                                @if($isLhpOnlySubmission)
                                    Nomor LHP: {{ $reviewIdentifier ?: 'Belum tersedia' }}
                                @else
                                    {{ $p->judul ?? 'Tanpa judul kegiatan' }}
                                @endif
                            </p>

                            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                                <span>
                                    <span class="font-semibold text-gray-600">OPD:</span>
                                    {{ $p->opd?->nama ?? ($isLhpOnlySubmission ? 'Lihat target pada LHP' : '-') }}
                                </span>
                                <span>
                                    <span class="font-semibold text-gray-600">LHP:</span>
                                    {{ $p->lhps->count() }}
                                </span>
                                <span>
                                    <span class="font-semibold text-gray-600">Diajukan oleh:</span>
                                    {{ $p->creator?->official_name ?? 'Tidak diketahui' }}
                                </span>
                            </div>
                        </div>

                        {{-- Aksi --}}
                        <div class="flex flex-shrink-0 flex-wrap items-center gap-2">
                            <a href="{{ route('pemeriksaan.show', $p) }}"
                               class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-bold text-gray-700 transition hover:bg-gray-50">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                {{ $isLhpOnlySubmission ? 'Tinjau LHP' : 'Lihat Detail' }}
                            </a>

                            @if($isPending)
                                <form method="POST" action="{{ route('pengajuan.pemeriksaan.approve', $p) }}" class="inline"
                                      data-confirm-variant="approval"
                                      data-confirm-title="Setujui pengajuan ini?"
                                      data-confirm-message="{{ ($isLhpOnlySubmission ? 'Setujui pengajuan LHP ' : 'Setujui pemeriksaan ') . $reviewIdentifier . '? Data akan berstatus DISETUJUI dan tersedia untuk ditindaklanjuti OPD.' }}">
                                    @csrf
                                    <button type="submit"
                                            data-loading-text="Menyetujui..."
                                            class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Setujui
                                    </button>
                                </form>

                                <button type="button"
                                        onclick="openPengajuanReview('{{ route('pengajuan.pemeriksaan.revise', $p) }}', @js($reviewIdentifier))"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-amber-600">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Minta Revisi
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($pemeriksaans->hasPages())
            <div class="rounded-2xl border border-gray-200/80 bg-white px-6 py-4 shadow-sm">
                {{ $pemeriksaans->links() }}
            </div>
        @endif
    @endif
</div>

{{-- Modal Catatan Revisi --}}
<div id="pengajuan-review-modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-gray-900/60 p-4 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="pengajuan-review-title" tabindex="-1">
    <div class="relative my-8 w-full max-w-lg overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-2xl">
        <div id="pengajuan-review-header" class="flex items-center justify-between px-6 py-4 text-white">
            <h3 id="pengajuan-review-title" class="text-sm font-bold">Review Pengajuan</h3>
            <button type="button" onclick="closePengajuanReview()" class="text-lg font-bold text-white/80 hover:text-white" aria-label="Tutup">✕</button>
        </div>

        <form id="pengajuan-review-form" method="POST" class="space-y-4 p-6">
            @csrf
            <div>
                <label for="pengajuan_catatan" id="pengajuan-review-label" class="mb-1 block text-xs font-bold text-gray-700">
                    Catatan <span class="text-state-danger">*</span>
                </label>
                <textarea id="pengajuan_catatan" name="catatan_revisi" rows="4" required minlength="5"
                          class="w-full border bg-white px-3 py-2.5 text-sm text-gray-800 idds-form-control"
                          placeholder="Jelaskan bagian yang perlu diperbaiki oleh pengaju..."></textarea>
                <p class="mt-1 text-xs text-gray-500">Minimal 5 karakter. Catatan ini akan dibaca oleh pengaju.</p>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-3">
                <button type="button" onclick="closePengajuanReview()"
                        class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit" id="pengajuan-review-submit"
                        class="rounded-xl px-5 py-2 text-sm font-bold text-white shadow-sm transition">
                    Kirim
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openPengajuanReview(actionUrl, identifier) {
        const modal = document.getElementById('pengajuan-review-modal');
        const form = document.getElementById('pengajuan-review-form');
        const header = document.getElementById('pengajuan-review-header');
        const title = document.getElementById('pengajuan-review-title');
        const label = document.getElementById('pengajuan-review-label');
        const submit = document.getElementById('pengajuan-review-submit');
        const textarea = document.getElementById('pengajuan_catatan');

        form.action = actionUrl;
        textarea.value = '';

        header.className = 'flex items-center justify-between px-6 py-4 text-white bg-orange-500';
        title.textContent = 'Minta Revisi: ' + identifier;
        label.innerHTML = 'Catatan Permintaan Revisi <span class="text-state-danger">*</span>';
        submit.className = 'rounded-xl px-5 py-2 text-sm font-bold text-white shadow-sm transition bg-orange-500 hover:bg-orange-600';
        submit.textContent = 'Kirim Permintaan Revisi';
        textarea.placeholder = 'Jelaskan apa yang perlu diperbaiki oleh pengaju...';

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closePengajuanReview() {
        const modal = document.getElementById('pengajuan-review-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

</script>
@endsection
