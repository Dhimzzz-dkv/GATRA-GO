@extends('layouts.app')

@php
    $isFocusedLhp = $focusedLhp !== null;
    $lhpsToShow = $isFocusedLhp ? collect([$focusedLhp]) : $pemeriksaan->lhps;
    $submissionName = $pemeriksaan->is_lhp_only_submission
        ? 'Pengajuan ' . $pemeriksaan->sumber_pemeriksaan . ' TA ' . $pemeriksaan->tahun
        : 'Pemeriksaan ' . ($pemeriksaan->nomor_spt ?: 'TA ' . $pemeriksaan->tahun);
    $submissionBadge = $pemeriksaan->status_pengajuan_badge;
    $visibleFindingCount = $lhpsToShow->sum(fn ($lhpItem) => $lhpItem->temuans->count());
    $visibleLossTotal = $lhpsToShow->sum(fn ($lhpItem) => $lhpItem->temuans
        ->where('ada_unsur_kerugian', true)
        ->sum('nilai_kerugian'));

    $breadcrumbs = [
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => $pemeriksaan->is_lhp_only_submission ? 'LHP' : 'Pemeriksaan & LHP'],
    ];

    if ($isFocusedLhp) {
        $breadcrumbs[] = ['label' => $submissionName, 'href' => route('pemeriksaan.show', $pemeriksaan)];
        $breadcrumbs[] = ['label' => $focusedLhp->nomor_lhp];
    } else {
        $breadcrumbs[] = ['label' => $submissionName];
    }

    $formatRupiah = fn (float $value): string => 'Rp ' . number_format($value, 0, ',', '.');
@endphp

@section('title', ($isFocusedLhp ? 'Rincian LHP ' . $focusedLhp->nomor_lhp : 'Detail ' . $submissionName))
@section('page-title', $isFocusedLhp ? 'Rincian LHP untuk OPD Anda' : ($pemeriksaan->is_lhp_only_submission ? 'Detail Pengajuan LHP' : 'Detail Pemeriksaan'))
@section('page-subtitle', 'Informasi yang ditampilkan khusus untuk temuan dan tindak lanjut OPD Anda')

@section('content')
<div class="mx-auto max-w-6xl space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-breadcrumbs :items="$breadcrumbs" />
        <a href="{{ $isFocusedLhp ? route('pemeriksaan.show', $pemeriksaan) : route('temuan.index') }}"
           class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
            {{ $isFocusedLhp ? 'Kembali ke Pengajuan' : 'Kembali ke Temuan OPD' }}
        </a>
    </div>

    <section class="rounded-2xl border border-primary-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="opd-submission-title">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <x-sumber-badge :sumber="$pemeriksaan->sumber_pemeriksaan" size="sm" />
                    <span class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-700">Tahun Anggaran {{ $pemeriksaan->tahun }}</span>
                </div>
                <h2 id="opd-submission-title" class="mt-3 text-lg font-bold text-gray-900">{{ $isFocusedLhp ? ($focusedLhp->judul_lhp ?: 'Laporan Hasil Pemeriksaan') : $submissionName }}</h2>
                <p class="mt-1 text-sm leading-6 text-gray-600">
                    {{ $isFocusedLhp ? 'Rincian laporan dan temuan yang menjadi tanggung jawab OPD Anda.' : 'Daftar LHP dan temuan yang menjadi tanggung jawab OPD Anda.' }}
                </p>
                @if($pemeriksaan->creator)
                    <p class="mt-2 text-xs text-gray-500">Diajukan oleh {{ $pemeriksaan->creator->official_name ?? $pemeriksaan->creator->name }}</p>
                @endif
            </div>
            <span class="inline-flex shrink-0 items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-bold {{ $submissionBadge['class'] }}">
                <span class="h-2 w-2 rounded-full bg-current" aria-hidden="true"></span>
                {{ $submissionBadge['label'] }}
            </span>
        </div>
    </section>

    @if($pemeriksaan->status_pengajuan !== 'DISETUJUI')
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950" role="status">
            Pengajuan ini berstatus <strong>{{ $submissionBadge['label'] }}</strong>. Temuan yang sudah disetujui dan ditujukan kepada OPD Anda akan ditampilkan di bawah.
        </div>
    @endif

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-3" aria-label="Ringkasan untuk OPD Anda">
        <div class="rounded-xl border border-primary-200 bg-primary-50/60 p-4">
            <p class="text-xs font-semibold text-primary-800">LHP Terkait</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $lhpsToShow->count() }}</p>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50/60 p-4">
            <p class="text-xs font-semibold text-blue-800">Temuan untuk OPD Anda</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $visibleFindingCount }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4">
            <p class="text-xs font-semibold text-amber-800">Total Nilai Kerugian OPD</p>
            <p class="mt-1 text-lg font-bold text-gray-900">{{ $formatRupiah((float) $visibleLossTotal) }}</p>
        </div>
    </section>

    @forelse($lhpsToShow as $lhpItem)
        @php
            $opdTemuans = $lhpItem->temuans;
            $administrativeTemuans = $opdTemuans->where('ada_unsur_administrasi', true);
            $lossTemuans = $opdTemuans->where('ada_unsur_kerugian', true);
            $verifiedDeposits = $opdTemuans
                ->flatMap(fn ($temuan) => $temuan->tindakLanjuts)
                ->filter(fn ($tindakLanjut) =>
                    (int) $tindakLanjut->opd_id === (int) auth()->user()->opd_id
                    && $tindakLanjut->status_verifikasi === 'SESUAI'
                );
            $targetNames = $lhpItem->opds->pluck('nama')->filter()->unique()->values();
        @endphp

        <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" aria-labelledby="lhp-{{ $lhpItem->id }}-title">
            <header class="border-b border-gray-100 bg-gray-50/70 p-5 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-primary-700">Nomor LHP</p>
                        <h3 id="lhp-{{ $lhpItem->id }}-title" class="mt-1 break-words text-lg font-bold text-gray-900">{{ $lhpItem->nomor_lhp ?: 'LHP tanpa nomor' }}</h3>
                        @if($lhpItem->judul_lhp)
                            <p class="mt-1 text-sm text-gray-700">{{ $lhpItem->judul_lhp }}</p>
                        @endif
                    </div>
                    <span class="inline-flex shrink-0 items-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700">
                        Tanggal LHP: {{ $lhpItem->tanggal_lhp?->translatedFormat('d F Y') ?? '-' }}
                    </span>
                </div>

                <dl class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-gray-200 bg-white p-3">
                        <dt class="text-xs font-medium text-gray-500">OPD yang ditampilkan</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $targetNames->join(', ') ?: 'OPD Anda' }}</dd>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-3">
                        <dt class="text-xs font-medium text-gray-500">Temuan administrasi</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $administrativeTemuans->count() }} temuan</dd>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-3">
                        <dt class="text-xs font-medium text-gray-500">Temuan kerugian</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $lossTemuans->count() }} temuan · {{ $formatRupiah((float) $lossTemuans->sum('nilai_kerugian')) }}</dd>
                    </div>
                </dl>

                @if($lhpItem->ringkasan)
                    <div class="mt-4 rounded-xl border border-primary-100 bg-primary-50/50 p-4">
                        <h4 class="text-sm font-bold text-primary-950">Ringkasan LHP</h4>
                        <p class="mt-1 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $lhpItem->ringkasan }}</p>
                    </div>
                @endif

                @if(! $isFocusedLhp)
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <a href="{{ route('pemeriksaan.lhp.show', [$pemeriksaan, $lhpItem]) }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-primary-200 bg-white px-4 py-2 text-sm font-semibold text-primary-700 transition hover:bg-primary-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                            Buka Rincian LHP
                        </a>
                    </div>
                @endif
            </header>

            <div class="space-y-4 p-5 sm:p-6">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h4 class="text-base font-bold text-gray-900">Temuan untuk OPD Anda</h4>
                        <p class="mt-1 text-sm text-gray-600">Uraian, rekomendasi, dan status penyelesaian temuan yang terkait dengan OPD Anda.</p>
                    </div>
                    <span class="text-xs font-semibold text-gray-500">{{ $opdTemuans->count() }} temuan</span>
                </div>

                @forelse($opdTemuans as $temuanIndex => $temuan)
                    @php
                        $temuanStatus = $temuan->status_penyelesaian_badge;
                        $verifiedForFinding = $temuan->tindakLanjuts->filter(fn ($tindakLanjut) =>
                            (int) $tindakLanjut->opd_id === (int) auth()->user()->opd_id
                            && $tindakLanjut->status_verifikasi === 'SESUAI'
                        );
                    @endphp
                    <section class="rounded-xl border border-gray-200 p-4 sm:p-5" aria-labelledby="temuan-{{ $temuan->id }}-title">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-md border px-2 py-1 text-xs font-semibold {{ $temuan->ada_unsur_kerugian ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-blue-200 bg-blue-50 text-blue-800' }}">
                                        {{ $temuan->ada_unsur_kerugian ? 'Temuan Kerugian' : 'Temuan Administrasi' }}
                                    </span>
                                    @if($temuan->ada_unsur_administrasi && $temuan->ada_unsur_kerugian)
                                        <span class="inline-flex rounded-md border border-blue-200 bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-800">Memiliki unsur administrasi</span>
                                    @endif
                                    <span class="text-xs text-gray-500">Temuan #{{ $temuanIndex + 1 }}</span>
                                </div>
                                <h5 id="temuan-{{ $temuan->id }}-title" class="mt-2 break-words text-base font-bold text-gray-900">{{ $temuan->judul_temuan }}</h5>
                            </div>
                            <span class="inline-flex shrink-0 items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $temuanStatus['class'] }}">{{ $temuanStatus['label'] }}</span>
                        </div>

                        <dl class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div class="rounded-lg bg-gray-50 p-3">
                                <dt class="text-xs font-semibold text-gray-600">Uraian Temuan</dt>
                                <dd class="mt-1 whitespace-pre-line text-sm leading-6 text-gray-800">{{ $temuan->uraian_temuan ?: '-' }}</dd>
                            </div>
                            <div class="rounded-lg bg-primary-50/60 p-3">
                                <dt class="text-xs font-semibold text-primary-800">Rekomendasi</dt>
                                <dd class="mt-1 whitespace-pre-line text-sm leading-6 text-gray-800">{{ $temuan->rekomendasi ?: '-' }}</dd>
                            </div>
                            @if($temuan->ada_unsur_administrasi)
                                <div class="rounded-lg border border-blue-100 bg-blue-50/50 p-3">
                                    <dt class="text-xs font-semibold text-blue-800">Keterangan Administrasi</dt>
                                    <dd class="mt-1 whitespace-pre-line text-sm leading-6 text-gray-800">{{ $temuan->uraian_administrasi ?: '-' }}</dd>
                                </div>
                            @endif
                            @if($temuan->ada_unsur_kerugian)
                                <div class="rounded-lg border border-amber-100 bg-amber-50/60 p-3">
                                    <dt class="text-xs font-semibold text-amber-900">Rincian Kerugian</dt>
                                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $temuan->kategori_kerugian ?: 'Kerugian' }} · {{ $formatRupiah((float) $temuan->nilai_kerugian) }}</dd>
                                    @if($temuan->uraian_kerugian)
                                        <dd class="mt-1 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $temuan->uraian_kerugian }}</dd>
                                    @endif
                                </div>
                            @endif
                            @if($temuan->penyebab || $temuan->akibat)
                                <div class="rounded-lg border border-gray-200 bg-white p-3">
                                    <dt class="text-xs font-semibold text-gray-600">Sebab dan Akibat</dt>
                                    @if($temuan->penyebab)
                                        <dd class="mt-1 text-sm leading-6 text-gray-800"><span class="font-semibold">Sebab:</span> {{ $temuan->penyebab }}</dd>
                                    @endif
                                    @if($temuan->akibat)
                                        <dd class="mt-1 text-sm leading-6 text-gray-800"><span class="font-semibold">Akibat:</span> {{ $temuan->akibat }}</dd>
                                    @endif
                                </div>
                            @endif
                        </dl>

                        <div class="mt-4 flex flex-col gap-3 border-t border-gray-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-gray-600">Setoran terverifikasi OPD: <span class="font-semibold text-emerald-700">{{ $formatRupiah((float) $verifiedForFinding->sum('jumlah_setor')) }}</span></p>
                            <a href="{{ route('temuan.show', $temuan) }}" class="inline-flex min-h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">Lihat Rincian Temuan</a>
                        </div>
                    </section>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-center">
                        <p class="text-sm font-semibold text-gray-800">Belum ada temuan yang dapat ditampilkan untuk OPD Anda.</p>
                        <p class="mt-1 text-xs leading-5 text-gray-600">Temuan akan tampil setelah pengajuan disetujui dan temuan ditautkan ke OPD Anda.</p>
                    </div>
                @endforelse
            </div>
        </article>
    @empty
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center">
            <h3 class="text-sm font-bold text-gray-900">Belum ada LHP untuk OPD Anda</h3>
            <p class="mt-1 text-sm text-gray-600">Rincian LHP yang ditujukan kepada OPD Anda akan tampil di halaman ini.</p>
        </div>
    @endforelse
</div>
@endsection
