@extends('layouts.app')

@section('title', 'Laporan & Rekapitulasi')
@section('page-title', 'Pusat Laporan & Rekapitulasi Eksekutif')
@section('page-subtitle', $jenis === 'administrasi' ? 'Laporan matrikulasi pengawasan administratif, rincian butir rekomendasi, dan status tindak lanjut' : 'Laporan matrikulasi hasil pengawasan, penyelesaian tindak lanjut, dan pemulihan kerugian negara dan daerah')

@section('content')
<div class="mx-auto w-full min-w-0 max-w-7xl space-y-6">
    {{-- Top Action Bar & Export Buttons --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0 flex flex-wrap items-center gap-4">
            {{-- Tabs --}}
            <div class="w-full min-w-0 border-b border-gray-200 sm:w-auto">
                <nav aria-label="Tampilan laporan" class="-mb-px flex flex-wrap gap-x-6">
                    <a href="{{ route('laporan.index', ['tab' => 'rekap', 'jenis' => $jenis, 'sumber' => $sumber, 'opd_id' => $opdId, 'tahun' => $tahun, 'pemeriksaan_id' => $pemeriksaanId, 'status_penyelesaian' => $statusPenyelesaian]) }}" @if($tab === 'rekap') aria-current="page" @endif
                       class="inline-flex min-w-0 max-w-full flex-wrap items-center gap-2 border-b-2 px-1 py-3 text-sm font-semibold transition {{ $tab === 'rekap' ? 'border-primary-600 text-primary-700' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Matriks Rekapitulasi {{ $jenis === 'administrasi' ? 'Pemeriksaan' : 'OPD' }}</span>
                    </a>

                    <a href="{{ route('laporan.index', ['tab' => 'rincian', 'jenis' => $jenis, 'sumber' => $sumber, 'opd_id' => $opdId, 'tahun' => $tahun, 'pemeriksaan_id' => $pemeriksaanId, 'status_penyelesaian' => $statusPenyelesaian]) }}" @if($tab === 'rincian') aria-current="page" @endif
                       class="inline-flex min-w-0 max-w-full flex-wrap items-center gap-2 border-b-2 px-1 py-3 text-sm font-semibold transition {{ $tab === 'rincian' ? 'border-primary-600 text-primary-700' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        <span>Laporan Rincian Temuan</span>
                    </a>
                </nav>
            </div>

            {{-- Toggle Jenis Laporan: Kerugian / Administrasi --}}
            <div role="group" aria-label="Jenis laporan" class="inline-flex items-center rounded-xl bg-gray-100 p-1 border border-gray-200 shadow-2xs">
                <a href="{{ route('laporan.index', ['tab' => $tab, 'jenis' => 'kerugian', 'sumber' => $sumber, 'opd_id' => $opdId, 'tahun' => $tahun, 'status_penyelesaian' => $statusPenyelesaian]) }}" @if($jenis === 'kerugian') aria-current="page" @endif
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg transition {{ $jenis === 'kerugian' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-500 hover:text-gray-900' }}">
                    <span>Kerugian</span>
                </a>
                <a href="{{ route('laporan.index', ['tab' => $tab, 'jenis' => 'administrasi', 'sumber' => $sumber, 'opd_id' => $opdId, 'status_penyelesaian' => $statusPenyelesaian]) }}" @if($jenis === 'administrasi') aria-current="page" @endif
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg transition {{ $jenis === 'administrasi' ? 'bg-white text-primary-700 shadow-xs' : 'text-gray-500 hover:text-gray-900' }}">
                    <span>Administrasi</span>
                </a>
            </div>
        </div>

        {{-- Export Actions --}}
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
            <a href="{{ route('laporan.export-csv', ['tab' => $tab, 'jenis' => $jenis, 'sumber' => $sumber, 'opd_id' => $opdId, 'tahun' => $tahun, 'pemeriksaan_id' => $pemeriksaanId, 'status_penyelesaian' => $statusPenyelesaian]) }}"
               class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2 text-xs font-bold text-emerald-700 shadow-sm transition hover:bg-emerald-100 sm:w-auto">
                <svg class="h-4 w-4 text-state-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Ekspor Excel (.xlsx)
            </a>

            <a href="{{ route('laporan.cetak', ['tab' => $tab, 'jenis' => $jenis, 'sumber' => $sumber, 'opd_id' => $opdId, 'tahun' => $tahun, 'pemeriksaan_id' => $pemeriksaanId, 'status_penyelesaian' => $statusPenyelesaian]) }}" target="_blank"
               class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-gray-900 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-black sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Cetak Format Resmi
            </a>
        </div>
    </div>

    {{-- Aggregate Summary Figures --}}
    @if($jenis === 'administrasi')
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-primary-100 bg-white p-4 shadow-sm">
                <span class="text-xs font-bold  tracking-wider text-gray-500">Total Temuan Administrasi</span>
                <p class="mt-1 text-2xl font-bold text-primary-900">{{ $totalTemuan }}</p>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <span class="text-xs font-bold  tracking-wider text-gray-500">Total Pemeriksaan Terkait</span>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $totalPemeriksaan }}</p>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <span class="text-xs font-bold  tracking-wider text-gray-500">Total Butir Rekomendasi</span>
                <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $totalButirRekomendasi }}</p>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <span class="text-xs font-bold  tracking-wider text-gray-500">% Rekomendasi Selesai</span>
                <div class="mt-1 flex items-baseline gap-2">
                    <p class="text-2xl font-bold text-primary-700">{{ $persenRekomendasiSelesai }}%</p>
                    <span class="text-xs text-gray-500 font-semibold">({{ $completedRekomendasiCount }} / {{ $totalButirRekomendasi }})</span>
                </div>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <span class="text-xs font-bold  tracking-wider text-gray-500">Total Temuan</span>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $totalTemuan }}</p>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <span class="text-xs font-bold  tracking-wider text-gray-500">Total Kerugian</span>
                <p class="mt-1 text-base font-bold text-state-danger truncate">Rp {{ number_format($totalKerugian, 0, ',', '.') }}</p>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <span class="text-xs font-bold  tracking-wider text-gray-500">Total Telah Disetor</span>
                <p class="mt-1 text-base font-bold text-state-success truncate">Rp {{ number_format($totalSetoran, 0, ',', '.') }}</p>
                <div class="mt-1.5 flex items-center gap-2 text-xs text-gray-500 font-semibold">
                    <span>Kasda: <span class="text-state-success">Rp {{ number_format($totalSetoranDaerah, 0, ',', '.') }}</span></span>
                    <span class="text-gray-500">|</span>
                    <span>Kasneg: <span class="text-blue-600">Rp {{ number_format($totalSetoranNegara, 0, ',', '.') }}</span></span>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <span class="text-xs font-bold  tracking-wider text-gray-500">Sisa Kerugian</span>
                <p class="mt-1 text-base font-bold text-state-warning truncate">Rp {{ number_format($totalSisaKerugian, 0, ',', '.') }}</p>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <span class="text-xs font-bold  tracking-wider text-gray-500">% Penyelesaian</span>
                <p class="mt-1 text-2xl font-bold text-primary-700">{{ $totalPersenSelesai }}%</p>
            </div>
        </div>
    @endif

    {{-- Multi-Criteria Filters --}}
    <div class="rounded-2xl border border-gray-200/80 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('laporan.index') }}" class="idds-filter-bar min-w-0">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="hidden" name="jenis" value="{{ $jenis }}">

            @if(auth()->user()->isSuperAdmin() || auth()->user()->isPemantau())
                <select name="sumber" aria-label="Filter berdasarkan sumber pemeriksaan"
                        class="w-full min-w-0 border bg-white px-3 py-2 text-sm text-gray-700 shadow-sm idds-form-control">
                    <option value="">Semua Sumber Pemeriksaan</option>
                    @foreach($sumberList as $s)
                        <option value="{{ $s }}" {{ $sumber === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            @endif

            @if(!auth()->user()->isOpd())
                <select name="opd_id" aria-label="Filter berdasarkan OPD target"
                        class="w-full min-w-0 border bg-white px-3 py-2 text-sm text-gray-700 shadow-sm idds-form-control">
                    <option value="">Semua OPD Target</option>
                    @foreach($opds as $o)
                        <option value="{{ $o->id }}" {{ $opdId == $o->id ? 'selected' : '' }}>{{ $o->kode }}</option>
                    @endforeach
                </select>
            @endif

            @if($jenis === 'administrasi')
                <select name="pemeriksaan_id" aria-label="Filter berdasarkan pemeriksaan atau LHP"
                        class="w-full min-w-0 border bg-white px-3 py-2 text-sm text-gray-700 shadow-sm idds-form-control">
                    <option value="">Semua Pemeriksaan / LHP</option>
                    @foreach($pemeriksaanList as $p)
                        <option value="{{ $p->id }}" {{ $pemeriksaanId == $p->id ? 'selected' : '' }}>
                            @if($p->is_lhp_only_submission)
                                LHP {{ $p->lhps->pluck('nomor_lhp')->filter()->join(', ') ?: 'Pengajuan' }}
                            @else
                                SPT {{ $p->nomor_spt }}
                            @endif
                            ({{ $p->tahun }}) - {{ Str::limit($p->nama_paket ?? $p->opd?->nama ?? $p->sumber_pemeriksaan, 35) }}
                        </option>
                    @endforeach
                </select>
            @else
                <select name="tahun" aria-label="Filter berdasarkan tahun anggaran"
                        class="w-full min-w-0 border bg-white px-3 py-2 text-sm text-gray-700 shadow-sm idds-form-control">
                    <option value="">Semua Tahun</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            @endif

            <select name="status_penyelesaian" aria-label="Filter berdasarkan status penyelesaian"
                    class="w-full min-w-0 border bg-white px-3 py-2 text-sm text-gray-700 shadow-sm idds-form-control">
                <option value="">Semua Status Penyelesaian</option>
                <option value="Belum Ditindaklanjuti" {{ $statusPenyelesaian === 'Belum Ditindaklanjuti' ? 'selected' : '' }}>Belum Ditindaklanjuti</option>
                <option value="Belum Sesuai" {{ $statusPenyelesaian === 'Belum Sesuai' ? 'selected' : '' }}>Belum Sesuai</option>
                <option value="Sesuai" {{ $statusPenyelesaian === 'Sesuai' ? 'selected' : '' }}>Sesuai</option>
                <option value="Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah" {{ $statusPenyelesaian === 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah' ? 'selected' : '' }}>Tidak Dapat Ditindaklanjuti</option>
            </select>

            <div class="idds-filter-actions">
                <x-action-button variant="filter" type="submit" class="w-full sm:w-auto">Terapkan filter</x-action-button>

                @if($sumber || $opdId || $tahun || $pemeriksaanId || $statusPenyelesaian)
                    <a href="{{ route('laporan.index', ['tab' => $tab, 'jenis' => $jenis]) }}" class="inline-flex min-h-10 items-center rounded-control px-3 text-sm font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-700">
                        Reset Filter
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- TAB 1: MATRIKS REKAPITULASI --}}
    @if($tab === 'rekap')
        @if($jenis === 'administrasi')
            {{-- Tabel Matriks Rekapitulasi Administrasi (10 Kolom Matriks) --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm">
                <div class="idds-table-region overflow-x-auto" role="region" aria-label="Matriks rekapitulasi administrasi. Geser horizontal untuk melihat seluruh kolom." tabindex="0">
                    <p class="mb-2 px-4 pt-3 text-xs text-gray-600" aria-hidden="true">Geser tabel secara horizontal untuk melihat kolom lainnya.</p>
                    <table class="min-w-[1200px] w-full text-left text-sm text-gray-600">
                        <caption class="sr-only">Matriks rekapitulasi temuan administrasi dan status rekomendasi</caption>
                        <thead class="bg-gray-100 border-b border-gray-200 tracking-wider text-gray-700 text-xs">
                            <tr>
                                <th scope="col" rowspan="2" class="px-5 py-3.5 font-bold border-r border-gray-200">Nama OPD</th>
                                <th scope="col" rowspan="2" class="px-4 py-3.5 font-bold text-center border-r border-gray-200">Sumber</th>
                                <th scope="col" rowspan="2" class="px-6 py-3.5 font-bold border-r border-gray-200">Temuan</th>
                                <th scope="col" rowspan="2" class="px-5 py-3.5 font-bold border-r border-gray-200">Penyebab</th>
                                <th scope="col" rowspan="2" class="px-6 py-3.5 font-bold border-r border-gray-200">Rekomendasi</th>
                                <th scope="col" rowspan="2" class="px-4 py-3.5 font-bold text-center border-r border-gray-200">Jumlah Rekomendasi</th>
                                <th scope="colgroup" colspan="4" class="px-3 py-2 text-center border-r border-gray-200 bg-gray-50 font-bold">Status Penyelesaian</th>
                                <th scope="col" rowspan="2" class="px-4 py-3.5 font-bold text-right">Aksi</th>
                            </tr>
                            <tr class="bg-gray-50 border-t border-gray-200 text-xs">
                                <th scope="col" class="px-2 py-1.5 text-center text-rose-700 font-bold">Belum Ditindaklanjuti</th>
                                <th scope="col" class="px-2 py-1.5 text-center text-amber-700 font-bold">Belum Sesuai</th>
                                <th scope="col" class="px-2 py-1.5 text-center text-emerald-700 font-bold">Sesuai</th>
                                <th scope="col" class="px-2 py-1.5 text-center border-r border-gray-200 text-gray-700 font-bold leading-tight">Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($rincianTemuans as $t)
                                @php
                                    $reks = $t->rekomendasis;
                                    if ($reks->isNotEmpty()) {
                                        $b_tl = $reks->where('status_penyelesaian', 'Belum Ditindaklanjuti')->count();
                                        $b_sesuai = $reks->where('status_penyelesaian', 'Belum Sesuai')->count();
                                        $b_ok = $reks->where('status_penyelesaian', 'Sesuai')->count();
                                        $b_tptd = $reks->filter(fn($r) => in_array($r->status_penyelesaian, [
                                            'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah',
                                            'Tidak Dapat Ditindaklanjuti'
                                        ]))->count();
                                    } else {
                                        $st = $t->status_penyelesaian;
                                        $b_tl = ($st === 'Belum Ditindaklanjuti') ? 1 : 0;
                                        $b_sesuai = ($st === 'Belum Sesuai') ? 1 : 0;
                                        $b_ok = ($st === 'Sesuai') ? 1 : 0;
                                        $b_tptd = in_array($st, [
                                            'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah',
                                            'Tidak Dapat Ditindaklanjuti'
                                        ]) ? 1 : 0;
                                    }
                                @endphp
                                <tr class="hover:bg-primary-50/20 transition">
                                    <td class="px-5 py-4 font-semibold text-gray-900 border-r border-gray-100">
                                        {{ $t->opd->nama }}
                                    </td>
                                    <td class="px-4 py-4 text-center border-r border-gray-100">
                                        <x-sumber-badge :sumber="$t->sumber_pemeriksaan" size="xs" />
                                    </td>
                                    <td class="px-6 py-4 max-w-xs space-y-1 border-r border-gray-100">
                                        <p class="text-xs font-bold text-gray-900 leading-snug">{{ $t->judul_temuan }}</p>
                                        <p class="text-xs text-gray-500 line-clamp-2">{{ $t->uraian_temuan }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-xs text-gray-600 max-w-xs leading-relaxed border-r border-gray-100">
                                        {{ $t->penyebab ?: '-' }}
                                    </td>
                                    <td class="px-6 py-4 max-w-sm border-r border-gray-100">
                                        @if($t->rekomendasis->isNotEmpty())
                                            <div class="space-y-1.5 text-xs leading-relaxed">
                                                @foreach($t->rekomendasis as $rIdx => $r)
                                                    <p class="text-gray-800">
                                                        <span class="font-bold text-gray-600">{{ $rIdx + 1 }}.</span> {{ $r->uraian_rekomendasi }}
                                                    </p>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-xs text-gray-700 whitespace-pre-line">{{ $t->rekomendasi ?: '-' }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-center font-bold text-gray-900 border-r border-gray-100">
                                        {{ $t->rekomendasis->count() ?: '-' }}
                                    </td>
                                    <td class="px-2 py-4 text-center">
                                        @if($b_tl > 0)
                                            <span class="inline-flex items-center justify-center min-w-[24px] h-6 px-1.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                {{ $b_tl }}
                                            </span>
                                        @else
                                            <span class="text-gray-500 font-medium">-</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-4 text-center">
                                        @if($b_sesuai > 0)
                                            <span class="inline-flex items-center justify-center min-w-[24px] h-6 px-1.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                {{ $b_sesuai }}
                                            </span>
                                        @else
                                            <span class="text-gray-500 font-medium">-</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-4 text-center">
                                        @if($b_ok > 0)
                                            <span class="inline-flex items-center justify-center min-w-[24px] h-6 px-1.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                {{ $b_ok }}
                                            </span>
                                        @else
                                            <span class="text-gray-500 font-medium">-</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-4 text-center border-r border-gray-100">
                                        @if($b_tptd > 0)
                                            <span class="inline-flex items-center justify-center min-w-[24px] h-6 px-1.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700 border border-gray-300">
                                                {{ $b_tptd }}
                                            </span>
                                        @else
                                            <span class="text-gray-500 font-medium">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <a href="{{ route('temuan.show', $t) }}"
                                           class="rounded-lg bg-primary-50 px-2.5 py-1.5 text-xs font-semibold text-primary-700 hover:bg-primary-100 transition">
                                            Lihat Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-6 py-12 text-center text-gray-500 text-sm">
                                        Tidak ada data temuan administrasi yang sesuai filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-100 border-t-2 border-gray-300 text-xs font-bold text-gray-800">
                            <tr>
                                <td colspan="5" class="px-5 py-3 text-center  tracking-wider">
                                    TOTAL KESELURUHAN ({{ $totalTemuan }} Temuan Administrasi)
                                </td>
                                <td class="px-4 py-3 text-center font-extrabold text-gray-900 border-r border-gray-200">
                                    {{ $totalButirRekomendasi }}
                                </td>
                                <td class="px-2 py-3 text-center text-rose-700 font-extrabold">
                                    {{ $totalBelumTl ?: '-' }}
                                </td>
                                <td class="px-2 py-3 text-center text-amber-700 font-extrabold">
                                    {{ $totalBelumSesuai ?: '-' }}
                                </td>
                                <td class="px-2 py-3 text-center text-emerald-700 font-extrabold">
                                    {{ $totalSesuai ?: '-' }}
                                </td>
                                <td class="px-2 py-3 text-center text-gray-700 font-extrabold border-r border-gray-200">
                                    {{ $totalTidakDapatTl ?: '-' }}
                                </td>
                                <td class="px-4 py-3 text-right text-primary-700 font-bold">
                                    {{ $persenRekomendasiSelesai }}% Selesai
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($rincianTemuans->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $rincianTemuans->links() }}
                    </div>
                @endif
            </div>
        @else
            {{-- Matriks Rekapitulasi Kerugian (Status Penyelesaian + Negara, Daerah, Gabungan) --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm">
                <div class="idds-table-region overflow-x-auto" role="region" aria-label="Matriks rekapitulasi kerugian negara dan daerah. Geser horizontal untuk melihat seluruh kolom." tabindex="0">
                    <p class="mb-2 px-4 pt-3 text-xs text-gray-600" aria-hidden="true">Geser tabel secara horizontal untuk melihat kolom lainnya.</p>
                    <table class="min-w-[1300px] w-full text-left text-xs text-gray-600">
                        <caption class="sr-only">Matriks rekapitulasi kerugian negara dan daerah per OPD</caption>
                        <thead class="bg-gray-100 border-b border-gray-200 tracking-wider text-gray-800 text-xs">
                            <tr>
                                <th scope="col" rowspan="2" class="px-3 py-3.5 text-center border-r border-gray-200 w-12 font-bold">No.</th>
                                <th scope="col" rowspan="2" class="px-5 py-3.5 border-r border-gray-200 font-bold min-w-[180px]">Nama OPD</th>
                                <th scope="col" rowspan="2" class="px-3 py-3.5 text-center border-r border-gray-200 w-16 font-bold">Total Temuan</th>
                                <th scope="colgroup" colspan="4" class="px-3 py-2 text-center border-r border-gray-200 font-extrabold text-gray-800 bg-gray-50/90 tracking-normal">
                                    Status penyelesaian
                                </th>
                                <th scope="col" rowspan="2" class="px-5 py-3.5 border-r border-gray-200 font-bold min-w-[200px]">
                                    <div class="text-gray-900 font-extrabold">Kerugian Negara</div>
                                    <div class="text-xs text-gray-500 font-normal tracking-normal mt-0.5">Nilai Kerugian (Rp)</div>
                                    <div class="text-xs text-gray-500 font-normal tracking-normal">Telah Disetor (Rp)</div>
                                    <div class="text-xs text-gray-500 font-normal tracking-normal">Sisa Kerugian (Rp)</div>
                                </th>
                                <th scope="col" rowspan="2" class="px-5 py-3.5 border-r border-gray-200 font-bold min-w-[200px]">
                                    <div class="text-gray-900 font-extrabold">Kerugian Daerah</div>
                                    <div class="text-xs text-gray-500 font-normal tracking-normal mt-0.5">Nilai Kerugian (Rp)</div>
                                    <div class="text-xs text-gray-500 font-normal tracking-normal">Telah Disetor (Rp)</div>
                                    <div class="text-xs text-gray-500 font-normal tracking-normal">Sisa Kerugian (Rp)</div>
                                </th>
                                <th scope="col" rowspan="2" class="px-5 py-3.5 border-r border-gray-200 font-bold min-w-[200px] bg-slate-50">
                                    <div class="text-gray-900 font-extrabold">Gabungan</div>
                                    <div class="text-xs text-gray-500 font-normal tracking-normal mt-0.5">Nilai Kerugian (Rp)</div>
                                    <div class="text-xs text-gray-500 font-normal tracking-normal">Telah Disetor (Rp)</div>
                                    <div class="text-xs text-gray-500 font-normal tracking-normal">Sisa Kerugian (Rp)</div>
                                </th>
                                <th scope="col" rowspan="2" class="px-4 py-3.5 text-center font-bold w-24">% Selesai</th>
                            </tr>
                            <tr class="bg-gray-50 border-t border-gray-200 text-xs font-bold">
                                <th scope="col" class="px-2 py-2 text-center text-rose-700 border-r border-gray-200 min-w-[70px]">Belum Ditindaklanjuti</th>
                                <th scope="col" class="px-2 py-2 text-center text-amber-700 border-r border-gray-200 min-w-[65px]">Belum Sesuai</th>
                                <th scope="col" class="px-2 py-2 text-center text-emerald-700 border-r border-gray-200 min-w-[60px]">Sesuai</th>
                                <th scope="col" class="px-2 py-2 text-center text-blue-900 border-r border-gray-200 min-w-[90px]">Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs font-medium">
                            @forelse($opdMatrix as $index => $row)
                                <tr class="hover:bg-primary-50/20 transition">
                                    <td class="px-3 py-3.5 text-gray-500 border-r border-gray-100 text-center font-medium">{{ $loop->iteration }}</td>
                                    <td class="px-5 py-3.5 text-gray-900 border-r border-gray-100 font-bold">
                                        {{ $row['opd']->nama }}
                                    </td>
                                    <td class="px-3 py-3.5 text-center font-bold text-gray-900 border-r border-gray-100 bg-gray-50/50">
                                        {{ $row['total_temuan'] }}
                                    </td>
                                    {{-- STATUS PENYELESAIAN --}}
                                    <td class="px-2 py-3.5 text-center font-bold text-state-danger border-r border-gray-100 bg-rose-50/20">{{ $row['belum_tl'] ?: '-' }}</td>
                                    <td class="px-2 py-3.5 text-center font-bold text-state-warning border-r border-gray-100 bg-amber-50/20">{{ $row['belum_sesuai'] ?: '-' }}</td>
                                    <td class="px-2 py-3.5 text-center font-bold text-state-success border-r border-gray-100 bg-emerald-50/20">{{ $row['sesuai'] ?: '-' }}</td>
                                    <td class="px-2 py-3.5 text-center text-gray-500 border-r border-gray-100">{{ $row['tidak_dapat_tl'] ?: '-' }}</td>

                                    {{-- KERUGIAN NEGARA --}}
                                    <td class="px-5 py-3.5 border-r border-gray-100 font-medium">
                                        @if($row['nilai_kerugian_negara'] > 0 || $row['total_disetor_negara'] > 0)
                                            <div class="space-y-0.5 text-xs">
                                                <div><span class="text-gray-500">Nilai:</span> <span class="font-bold text-gray-900">Rp {{ number_format($row['nilai_kerugian_negara'], 0, ',', '.') }}</span></div>
                                                <div><span class="text-gray-500">Disetor:</span> <span class="font-semibold text-state-success">Rp {{ number_format($row['total_disetor_negara'], 0, ',', '.') }}</span></div>
                                                <div><span class="text-gray-500">Sisa:</span> <span class="{{ $row['sisa_kerugian_negara'] > 0 ? 'font-bold text-state-danger' : 'text-gray-500' }}">Rp {{ number_format($row['sisa_kerugian_negara'], 0, ',', '.') }}</span></div>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-500">Rp 0</span>
                                        @endif
                                    </td>
                                    {{-- KERUGIAN DAERAH --}}
                                    <td class="px-5 py-3.5 border-r border-gray-100 font-medium">
                                        @if($row['nilai_kerugian_daerah'] > 0 || $row['total_disetor_daerah'] > 0)
                                            <div class="space-y-0.5 text-xs">
                                                <div><span class="text-gray-500">Nilai:</span> <span class="font-bold text-gray-900">Rp {{ number_format($row['nilai_kerugian_daerah'], 0, ',', '.') }}</span></div>
                                                <div><span class="text-gray-500">Disetor:</span> <span class="font-semibold text-state-success">Rp {{ number_format($row['total_disetor_daerah'], 0, ',', '.') }}</span></div>
                                                <div><span class="text-gray-500">Sisa:</span> <span class="{{ $row['sisa_kerugian_daerah'] > 0 ? 'font-bold text-state-danger' : 'text-gray-500' }}">Rp {{ number_format($row['sisa_kerugian_daerah'], 0, ',', '.') }}</span></div>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-500">Rp 0</span>
                                        @endif
                                    </td>
                                    {{-- GABUNGAN --}}
                                    <td class="px-5 py-3.5 border-r border-gray-100 font-medium bg-slate-50/50">
                                        @if($row['nilai_kerugian'] > 0 || $row['total_disetor'] > 0)
                                            <div class="space-y-0.5 text-xs">
                                                <div><span class="text-gray-500">Nilai:</span> <span class="font-bold text-gray-900">Rp {{ number_format($row['nilai_kerugian'], 0, ',', '.') }}</span></div>
                                                <div><span class="text-gray-500">Disetor:</span> <span class="font-semibold text-state-success">Rp {{ number_format($row['total_disetor'], 0, ',', '.') }}</span></div>
                                                <div><span class="text-gray-500">Sisa:</span> <span class="{{ $row['sisa_kerugian'] > 0 ? 'font-bold text-state-danger' : 'text-gray-500' }}">Rp {{ number_format($row['sisa_kerugian'], 0, ',', '.') }}</span></div>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-500">Rp 0</span>
                                        @endif
                                    </td>
                                    {{-- % SELESAI --}}
                                    <td class="px-4 py-3.5 text-center font-bold text-sm {{ $row['persen_selesai'] >= 100 ? 'text-emerald-700' : ($row['persen_selesai'] > 0 ? 'text-primary-700' : 'text-gray-500') }}">
                                        {{ $row['persen_selesai'] }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-6 py-12 text-center text-gray-500 text-sm">
                                        Tidak ada data temuan yang memenuhi kriteria filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-100 border-t-2 border-gray-300 font-bold text-xs">
                            <tr>
                                <td colspan="2" class="px-5 py-4 text-center text-gray-900 font-bold tracking-wider  border-r border-gray-200">
                                    TOTAL
                                </td>
                                <td class="px-3 py-4 text-center text-gray-900 font-bold border-r border-gray-200 text-sm">
                                    {{ $totalTemuan }}
                                </td>
                                {{-- TOTAL STATUS PENYELESAIAN --}}
                                <td class="px-2 py-4 text-center font-bold text-rose-700 border-r border-gray-200">{{ $totalBelumTl ?: '-' }}</td>
                                <td class="px-2 py-4 text-center font-bold text-amber-700 border-r border-gray-200">{{ $totalBelumSesuai ?: '-' }}</td>
                                <td class="px-2 py-4 text-center font-bold text-emerald-700 border-r border-gray-200">{{ $totalSesuai ?: '-' }}</td>
                                <td class="px-2 py-4 text-center text-gray-600 border-r border-gray-200">{{ $totalTidakDapatTl ?: '-' }}</td>

                                {{-- TOTAL KERUGIAN NEGARA --}}
                                <td class="px-5 py-4 border-r border-gray-200 leading-relaxed">
                                    <div>
                                        <span class="font-bold text-gray-900">Rp {{ number_format($totalKerugianNegara, 0, ',', '.') }}</span> /<br>
                                        <span class="text-emerald-700 font-bold">Rp {{ number_format($totalSetoranNegara, 0, ',', '.') }}</span> /<br>
                                        <span class="text-rose-700 font-bold">Rp {{ number_format($totalSisaNegara, 0, ',', '.') }}</span>
                                    </div>
                                </td>
                                {{-- TOTAL KERUGIAN DAERAH --}}
                                <td class="px-5 py-4 border-r border-gray-200 leading-relaxed">
                                    <div>
                                        <span class="font-bold text-gray-900">Rp {{ number_format($totalKerugianDaerah, 0, ',', '.') }}</span> /<br>
                                        <span class="text-emerald-700 font-bold">Rp {{ number_format($totalSetoranDaerah, 0, ',', '.') }}</span> /<br>
                                        <span class="text-rose-700 font-bold">Rp {{ number_format($totalSisaDaerah, 0, ',', '.') }}</span>
                                    </div>
                                </td>
                                {{-- TOTAL GABUNGAN --}}
                                <td class="px-5 py-4 border-r border-gray-200 leading-relaxed bg-slate-200/50">
                                    <div>
                                        <span class="font-bold text-gray-900">Rp {{ number_format($totalKerugian, 0, ',', '.') }}</span> /<br>
                                        <span class="text-emerald-700 font-bold">Rp {{ number_format($totalSetoran, 0, ',', '.') }}</span> /<br>
                                        <span class="text-rose-700 font-bold">Rp {{ number_format($totalSisaKerugian, 0, ',', '.') }}</span>
                                    </div>
                                </td>
                                {{-- TOTAL % SELESAI --}}
                                <td class="px-4 py-4 text-center font-bold text-sm text-primary-800">
                                    {{ $totalPersenSelesai }}%
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif
    @endif

    {{-- TAB 2: LAPORAN RINCIAN TEMUAN --}}
    @if($tab === 'rincian')
        @if($jenis === 'administrasi')
            {{-- Laporan Rincian Temuan Administrasi Dikelompokkan per Pemeriksaan (Format Kartu Naratif) --}}
            <div class="space-y-4">
                @forelse($pemeriksaanMatrix as $pGroup)
                    @php
                        $p = $pGroup['pemeriksaan'];
                        $pTemuans = $pGroup['temuans'];
                    @endphp
                    <div class="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 pb-3 gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <x-sumber-badge :sumber="$p->sumber_pemeriksaan ?? $pTemuans->first()->sumber_pemeriksaan" size="xs" />
                                    <h4 class="text-sm font-bold text-gray-900">
                                        @if($p->is_lhp_only_submission)
                                            LHP {{ $pTemuans->first()->lhp?->nomor_lhp ?? 'Pengajuan' }}
                                        @else
                                            SPT {{ $p->nomor_spt ?? 'Pemeriksaan Temuan Langsung' }}
                                        @endif
                                        @if($p?->tahun) (TA {{ $p->tahun }}) @endif
                                    </h4>
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $p->nama_paket ?? 'Temuan Hasil Pengawasan Administratif' }} • OPD: <strong class="text-gray-700">{{ $pTemuans->first()->opd->nama ?? '-' }}</strong>
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-lg bg-gray-50 border border-gray-200 px-2.5 py-1 text-xs font-bold text-gray-700">
                                    {{ $pGroup['total_temuan'] }} Temuan Administrasi
                                </span>
                                <span class="inline-flex items-center rounded-lg bg-primary-50 border border-primary-200 px-2.5 py-1 text-xs font-bold text-primary-700">
                                    {{ $pGroup['completed_rekomendasi'] }} / {{ $pGroup['total_rekomendasi'] }} Butir Selesai ({{ $pGroup['persen_selesai'] }}%)
                                </span>
                            </div>
                        </div>

                        {{-- Temuan List inside this Pemeriksaan --}}
                        <div class="space-y-3">
                            @foreach($pTemuans as $t)
                                @php
                                    $tBadge = $t->status_penyelesaian_badge;
                                    $tReks = $t->rekomendasis;
                                    $tCompReks = $tReks->filter(fn($r) => in_array($r->status_penyelesaian, ['Sesuai', 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti']))->count();
                                    $tTotReks = $tReks->count();
                                @endphp
                                <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-4 space-y-3">
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <h5 class="text-xs font-bold text-gray-900">{{ $t->judul_temuan }}</h5>
                                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $tBadge['class'] }}">
                                                    {{ $tBadge['label'] }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-600 line-clamp-2">{{ $t->uraian_temuan }}</p>
                                            @if($t->penyebab)
                                                <p class="text-xs text-gray-500 italic"><strong class="text-gray-600">Penyebab:</strong> {{ $t->penyebab }}</p>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            <span class="inline-flex items-center rounded-md bg-white border border-gray-200 px-2 py-1 text-xs font-bold text-gray-700">
                                                {{ $tCompReks }} / {{ $tTotReks }} Selesai
                                            </span>
                                            <a href="{{ route('temuan.show', $t) }}" class="text-xs font-semibold text-primary-700 hover:underline">
                                                Lihat Detail &rarr;
                                            </a>
                                        </div>
                                    </div>

                                    {{-- Butir Rekomendasi List --}}
                                    @if($tReks->isNotEmpty())
                                        <div class="space-y-2 border-t border-gray-200/60 pt-2.5">
                                            <span class="text-xs font-bold  tracking-wider text-gray-500">Rincian Butir Rekomendasi:</span>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                @foreach($tReks as $rIdx => $r)
                                                    @php $rBadge = $r->status_penyelesaian_badge; @endphp
                                                    <div class="rounded-lg border border-gray-200 bg-white p-2.5 text-xs flex items-start justify-between gap-2">
                                                        <div class="space-y-0.5">
                                                            <span class="font-bold text-gray-800">{{ $rIdx + 1 }}.</span>
                                                            <span class="text-gray-700">{{ $r->uraian_rekomendasi }}</span>
                                                        </div>
                                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold flex-shrink-0 {{ $rBadge['class'] }}">
                                                            {{ $rBadge['label'] }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <div class="border-t border-gray-200/60 pt-2 text-xs text-gray-500 italic">
                                            (Belum ada butir rekomendasi bertahap — Rekomendasi umum: {{ $t->rekomendasi ?: '-' }})
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-gray-200 bg-white p-12 text-center text-gray-500 text-sm">
                        Tidak ada data rincian temuan administrasi yang memenuhi kriteria filter.
                    </div>
                @endforelse
            </div>
        @else
            {{-- Tabel Rincian Temuan Kerugian Negara dan Daerah --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm">
                <div class="idds-table-region overflow-x-auto" role="region" aria-label="Rincian temuan kerugian negara dan daerah. Geser horizontal untuk melihat seluruh kolom." tabindex="0">
                    <p class="mb-2 px-4 pt-3 text-xs text-gray-600" aria-hidden="true">Geser tabel secara horizontal untuk melihat kolom lainnya.</p>
                    <table class="min-w-[900px] w-full text-left text-sm text-gray-600">
                        <caption class="sr-only">Rincian temuan kerugian negara dan daerah</caption>
                        <thead class="bg-gray-50/75 border-b border-gray-200 text-xs tracking-wider text-gray-500">
                            <tr>
                                <th scope="col" class="px-6 py-4 font-semibold">Sumber &amp; Tahun</th>
                                <th scope="col" class="px-6 py-4 font-semibold">OPD Target</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Uraian &amp; Rekomendasi</th>
                                <th scope="col" class="px-6 py-4 font-semibold">Kerugian &amp; Setoran</th>
                                <th scope="col" class="px-6 py-4 font-semibold text-center">Status Penyelesaian</th>
                                <th scope="col" class="px-6 py-4 font-semibold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($rincianTemuans as $t)
                                <tr class="hover:bg-primary-50/20 transition">
                                    <td class="px-6 py-4">
                                        <x-sumber-badge :sumber="$t->sumber_pemeriksaan" size="xs" />
                                        <p class="text-xs text-gray-500 font-semibold mt-1">TA {{ $t->tahun }}</p>
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-gray-900">
                                        {{ $t->opd->nama }}
                                    </td>
                                    <td class="px-6 py-4 max-w-xs space-y-1">
                                        <p class="text-xs font-bold text-gray-900 line-clamp-1">{{ $t->judul_temuan }}</p>
                                        <p class="text-xs text-gray-600 line-clamp-2">{{ $t->uraian_temuan }}</p>
                                        @if($t->rekomendasi)
                                            <p class="text-xs text-emerald-800 line-clamp-1"><strong>Rekomendasi:</strong> {{ $t->rekomendasi }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap space-y-0.5">
                                        <p class="text-xs text-gray-700">Kerugian: <strong class="text-state-danger">{{ $t->formatted_nilai_kerugian }}</strong></p>
                                        <p class="text-xs text-gray-700">Disetor: <strong class="text-state-success">{{ $t->formatted_total_disetor }}</strong></p>
                                        <p class="text-xs text-gray-500">Sisa: <strong class="text-state-warning">{{ $t->formatted_sisa_kerugian }}</strong></p>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @php $badge = $t->status_penyelesaian_badge; @endphp
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $badge['class'] }}">
                                            {{ $badge['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('temuan.show', $t) }}"
                                           class="rounded-lg bg-primary-50 px-2.5 py-1.5 text-xs font-semibold text-primary-700 hover:bg-primary-100 transition">
                                            Lihat Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500 text-sm">
                                        Tidak ada data rincian temuan yang sesuai filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($rincianTemuans->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $rincianTemuans->links() }}
                    </div>
                @endif
            </div>
        @endif
    @endif
</div>
@endsection
