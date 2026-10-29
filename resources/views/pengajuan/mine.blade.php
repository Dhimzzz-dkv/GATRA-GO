@extends('layouts.app')

@section('title', 'Pengajuan Saya')
@section('page-title', 'Pengajuan Saya')
@section('page-subtitle', 'Pantau status persetujuan dan catatan revisi LHP yang diajukan oleh sumber ' . $source)

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        @php
            $statusCards = [
                ['label' => 'Menunggu Persetujuan', 'value' => 'MENUNGGU PERSETUJUAN', 'count' => $statusCounts['MENUNGGU PERSETUJUAN'], 'active' => 'border-amber-400 bg-amber-50/40 ring-2 ring-amber-500/15', 'title' => 'text-amber-700', 'icon' => 'bg-amber-50 text-amber-700 group-hover:bg-amber-100'],
                ['label' => 'Disetujui', 'value' => 'DISETUJUI', 'count' => $statusCounts['DISETUJUI'], 'active' => 'border-emerald-400 bg-emerald-50/40 ring-2 ring-emerald-500/15', 'title' => 'text-emerald-700', 'icon' => 'bg-emerald-50 text-emerald-700 group-hover:bg-emerald-100'],
                ['label' => 'Perlu Revisi', 'value' => 'PERLU REVISI', 'count' => $statusCounts['PERLU REVISI'], 'active' => 'border-orange-400 bg-orange-50/40 ring-2 ring-orange-500/15', 'title' => 'text-orange-700', 'icon' => 'bg-orange-50 text-orange-700 group-hover:bg-orange-100'],
            ];
        @endphp

        @foreach($statusCards as $card)
            <a href="{{ route('pengajuan-saya.index', ['status' => $card['value']]) }}"
               class="group rounded-2xl border p-5 shadow-sm transition hover:shadow-md {{ $status === $card['value'] ? $card['active'] : 'border-gray-200/80 bg-white' }}">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold tracking-wider {{ $card['title'] }}">{{ $card['label'] }}</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $card['count'] }}</p>
                    </div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl transition {{ $card['icon'] }}" aria-hidden="true">
                        @if($card['value'] === 'MENUNGGU PERSETUJUAN')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @elseif($card['value'] === 'DISETUJUI')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        @endif
                    </span>
                </div>
                <p class="mt-3 text-xs font-medium text-gray-500">Jumlah pengajuan LHP dengan status ini</p>
            </a>
        @endforeach
    </div>

    <section class="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm" aria-label="Filter pengajuan">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-2">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Riwayat Pengajuan</h2>
                <p class="mt-1 text-xs text-gray-500">Daftar ini hanya memuat laporan sumber {{ $source }}. Data SPT tidak ditampilkan untuk akun Admin Sumber.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('pengajuan-saya.index') }}" class="idds-filter-bar">
            <div class="min-w-0">
                <label for="status" class="mb-1.5 block text-xs font-bold tracking-wider text-gray-700">Status Pengajuan</label>
                <select name="status" id="status" class="idds-form-control h-10 w-full border bg-white px-3 text-sm text-gray-700">
                    <option value="">Semua Status</option>
                    <option value="MENUNGGU PERSETUJUAN" {{ $status === 'MENUNGGU PERSETUJUAN' ? 'selected' : '' }}>Menunggu Persetujuan</option>
                    <option value="DISETUJUI" {{ $status === 'DISETUJUI' ? 'selected' : '' }}>Disetujui</option>
                    <option value="PERLU REVISI" {{ $status === 'PERLU REVISI' ? 'selected' : '' }}>Perlu Revisi</option>
                    <option value="DRAFT" {{ $status === 'DRAFT' ? 'selected' : '' }}>Draft</option>
                </select>
            </div>

            <div class="min-w-0">
                <label for="tahun" class="mb-1.5 block text-xs font-bold tracking-wider text-gray-700">Tahun Anggaran</label>
                <select name="tahun" id="tahun" class="idds-form-control h-10 w-full border bg-white px-3 text-sm text-gray-700">
                    <option value="">Semua Tahun</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ (string) $tahun === (string) $year ? 'selected' : '' }}>TA {{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-0">
                <span class="mb-1 block min-h-8 text-xs font-bold tracking-wider text-gray-700">Aksi</span>
                <div class="flex h-10 min-w-0 items-center gap-3">
                    <x-action-button variant="filter" type="submit" class="min-w-0 flex-1">Terapkan</x-action-button>
                    @if($status !== '' || $tahun !== '')
                        <a href="{{ route('pengajuan-saya.index') }}" class="shrink-0 text-sm font-medium text-gray-500 transition hover:text-gray-700">Reset</a>
                    @endif
                </div>
            </div>
        </form>
    </section>

    @if($pemeriksaans->isEmpty())
        <div class="mx-auto flex max-w-3xl flex-col items-center gap-4 rounded-2xl border border-dashed border-primary-200 bg-gradient-to-r from-white via-white to-primary-50/70 p-5 text-center shadow-sm sm:flex-row sm:items-center sm:p-6 sm:text-left">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-100 text-primary-700 ring-1 ring-primary-200/70">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7h8m-8 4h8m-8 4h5m-9 4h14a2 2 0 002-2V7l-5-5H6a2 2 0 00-2 2v13a2 2 0 002 2z"/></svg>
            </div>
            <div class="min-w-0 flex-1">
                <h2 class="text-sm font-bold text-gray-900">
                    {{ $status !== '' || $tahun !== '' ? 'Tidak ada pengajuan yang sesuai filter' : 'Belum ada pengajuan' }}
                </h2>
                <p class="mt-1 text-xs leading-5 text-gray-600">
                    @if($status !== '' || $tahun !== '')
                        Coba ubah kriteria pencarian untuk melihat pengajuan lainnya.
                    @else
                        LHP yang Anda ajukan beserta status persetujuannya akan tampil di sini.
                    @endif
                </p>
            </div>
            @if($status !== '' || $tahun !== '')
                <a href="{{ route('pengajuan-saya.index') }}" class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-lg border border-primary-200 bg-white px-4 text-xs font-semibold text-primary-700 transition hover:bg-primary-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    Hapus Filter
                </a>
            @else
                <a href="{{ route('lhp.create') }}" class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-primary-700 px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-primary-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                    Input LHP
                </a>
            @endif
        </div>
    @else
        <div class="space-y-4">
            @foreach($pemeriksaans as $pemeriksaan)
                @php
                    $badge = $pemeriksaan->status_pengajuan_badge;
                    $isRevision = in_array($pemeriksaan->status_pengajuan, ['PERLU REVISI', 'DITOLAK'], true);
                @endphp

                <article class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm">
                    <div class="flex flex-col gap-3 border-b border-gray-100 bg-gray-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-bold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                <x-sumber-badge :sumber="$source" size="xs" />
                                <span class="text-xs font-medium text-gray-500">TA {{ $pemeriksaan->tahun }}</span>
                            </div>
                            <h3 class="mt-2 text-sm font-bold text-gray-900">Pengajuan LHP — Tahun Anggaran {{ $pemeriksaan->tahun }}</h3>
                            <p class="mt-1 text-xs text-gray-500">{{ $pemeriksaan->lhps_count }} laporan · Diperbarui {{ $pemeriksaan->updated_at?->format('d/m/Y H:i') ?? '-' }}</p>
                        </div>
                    </div>

                    @if($isRevision && $pemeriksaan->catatan_persetujuan)
                        <div class="mx-5 mt-4 rounded-xl border border-orange-200 bg-orange-50 p-4" role="note">
                            <p class="text-xs font-bold text-orange-900">Catatan revisi dari Super Admin</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-orange-900">{{ $pemeriksaan->catatan_persetujuan }}</p>
                        </div>
                    @endif

                    <div class="divide-y divide-gray-100 px-5">
                        @forelse($pemeriksaan->lhps as $lhp)
                            <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <h4 class="break-words text-sm font-semibold text-gray-900">{{ $lhp->nomor_lhp ?: 'LHP tanpa nomor' }}{{ $lhp->judul_lhp ? ' — ' . $lhp->judul_lhp : '' }}</h4>
                                    <p class="mt-1 text-xs text-gray-500">Tanggal LHP: {{ $lhp->tanggal_lhp?->format('d/m/Y') ?? '-' }}</p>
                                    <p class="mt-1 break-words text-xs text-gray-600">
                                        <span class="font-semibold">OPD target:</span>
                                        {{ $lhp->opds->pluck('nama')->filter()->join(', ') ?: 'Belum ditentukan' }}
                                    </p>
                                </div>
                                <a href="{{ route('pemeriksaan.lhp.show', [$pemeriksaan, $lhp]) }}"
                                   class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-xl border border-primary-200 bg-white px-3.5 py-2 text-xs font-bold text-primary-700 transition hover:bg-primary-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Lihat LHP
                                </a>
                            </div>
                        @empty
                            <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Belum ada LHP yang tersimpan</p>
                                    <p class="mt-1 text-xs text-gray-500">Lengkapi data LHP agar laporan dapat diajukan untuk persetujuan.</p>
                                </div>
                                <a href="{{ route('lhp.create') }}" class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-xl bg-primary-700 px-3.5 py-2 text-xs font-bold text-white transition hover:bg-primary-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                                    Lengkapi LHP
                                </a>
                            </div>
                        @endforelse
                    </div>

                    @if(in_array($pemeriksaan->status_pengajuan, ['DRAFT', 'PERLU REVISI', 'DITOLAK'], true))
                        <div class="flex flex-col gap-3 border-t border-gray-100 bg-gray-50/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs leading-5 text-gray-600">Setelah memperbaiki data LHP dan temuan sesuai catatan, ajukan kembali untuk diperiksa Super Admin.</p>
                            <form method="POST" action="{{ route('pemeriksaan.ajukan', $pemeriksaan) }}"
                                  data-confirm-variant="submit"
                                  data-confirm-title="Ajukan kembali untuk persetujuan?"
                                  data-confirm-message="Ajukan kembali laporan LHP ini untuk persetujuan Super Admin?">
                                @csrf
                                <button type="submit" class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-xl bg-primary-700 px-4 py-2 text-xs font-bold text-white transition hover:bg-primary-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Ajukan Kembali
                                </button>
                            </form>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        @if($pemeriksaans->hasPages())
            <div class="rounded-2xl border border-gray-200/80 bg-white px-6 py-4 shadow-sm">
                {{ $pemeriksaans->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
