 @extends('layouts.app')

@section('title', 'Rincian Arsip Pemeriksaan: ' . $pemeriksaan->nomor_spt)
@section('page-title', 'Rincian Data Arsip Pemeriksaan')
@section('page-subtitle', 'Melihat detail lengkap data pemeriksaan dan riwayat temuan yang berada di arsip pemulihan.')

@section('content')
<div class="space-y-6">

    {{-- Top Action Bar --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-breadcrumbs :items="[
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Arsip', 'href' => route('audit-log.trash')],
            ['label' => $pemeriksaan->nomor_spt],
        ]" />

        <div class="flex flex-wrap items-center gap-2.5">
            @if($pemeriksaan->dokumen_spt)
                <a href="{{ route('audit-log.download-spt', $pemeriksaan->id) }}"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-xs font-bold text-gray-700 shadow-2xs hover:bg-gray-50 transition cursor-pointer">
                    <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Unduh Berkas SPT
                </a>
            @endif

            @if(auth()->user()->isSuperAdmin())
                <form method="POST" action="{{ route('audit-log.restore', ['type' => 'pemeriksaan', 'id' => $pemeriksaan->id]) }}" class="inline"
                      data-confirm-title="Pulihkan pemeriksaan?"
                      data-confirm-message="Pulihkan data pemeriksaan ini ke daftar aktif?"
                      data-confirm-variant="submit"
                      data-confirm-accept="Ya, Pulihkan">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Pulihkan Data
                    </button>
                </form>

                <form method="POST" action="{{ route('audit-log.force-delete', ['type' => 'pemeriksaan', 'id' => $pemeriksaan->id]) }}" class="inline"
                      data-confirm-title="Hapus data permanen?"
                      data-confirm-message="PERINGATAN KRITIS: Seluruh data pemeriksaan, LHP, temuan, dan berkas bukti di bawahnya akan dihapus permanen."
                      data-confirm-variant="danger"
                      data-confirm-accept="Hapus Permanen">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-rose-700 transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Hapus Permanen
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Alert Banner Status Arsip --}}
    <div class="rounded-2xl border border-rose-200 bg-rose-50/80 p-4 sm:p-5 text-rose-900 shadow-sm">
        <div class="flex items-start gap-3.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-100 text-state-danger shrink-0 mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold  tracking-wider bg-rose-200 text-rose-800">
                        Status: Dalam Arsip Sampah
                    </span>
                    <span class="text-xs text-rose-700 font-semibold">Dihapus pada: {{ $pemeriksaan->deleted_at?->translatedFormat('d F Y, H:i') ?? '-' }} WIB</span>
                </div>
                <p class="text-xs text-rose-800 leading-relaxed">
                    Data pemeriksaan ini dinonaktifkan / dihapus dari daftar aktif. Seluruh data tim pemeriksa, LHP, temuan, dan riwayat tindak lanjut tetap tersimpan utuh di sistem ini dan dapat dipulihkan kapan saja.
                </p>
            </div>
        </div>
    </div>

    {{-- Card 1: Informasi SPT & Pemeriksaan --}}
    <div class="rounded-2xl border border-gray-200/80 bg-white p-6 shadow-sm space-y-5">
        <div class="pb-4 border-b border-gray-100">
            <span class="mb-1.5 inline-flex items-center gap-1">
                <x-sumber-badge :sumber="$pemeriksaan->sumber_pemeriksaan" />
                <span class="text-xs font-bold  tracking-wider text-gray-500">(TA {{ $pemeriksaan->tahun }})</span>
            </span>
            <h2 class="text-base font-bold text-gray-900">SPT: {{ $pemeriksaan->nomor_spt }}</h2>
            <p class="text-xs text-gray-500 mt-0.5">{{ $pemeriksaan->judul ?? 'Pemeriksaan ' . $pemeriksaan->sumber_pemeriksaan . ' Tahun ' . $pemeriksaan->tahun }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            <div class="p-3.5 bg-gray-50/70 rounded-xl border border-gray-100">
                <span class="text-xs font-bold  tracking-wider text-gray-500 block mb-1">Instansi / OPD Target</span>
                <p class="font-bold text-gray-900">{{ $pemeriksaan->opd->nama ?? '-' }}</p>
                <span class="text-xs text-gray-500">{{ $pemeriksaan->opd->kode ?? '' }}</span>
            </div>

            <div class="p-3.5 bg-gray-50/70 rounded-xl border border-gray-100">
                <span class="text-xs font-bold  tracking-wider text-gray-500 block mb-1">Tanggal &amp; Periode SPT</span>
                <p class="font-bold text-gray-900">{{ $pemeriksaan->tanggal_spt?->translatedFormat('d F Y') ?? '-' }}</p>
                <span class="text-xs text-gray-500">
                    {{ $pemeriksaan->tanggal_mulai?->translatedFormat('d M Y') ?? '-' }} s/d {{ $pemeriksaan->tanggal_selesai?->translatedFormat('d M Y') ?? '-' }}
                </span>
            </div>

            <div class="p-3.5 bg-gray-50/70 rounded-xl border border-gray-100">
                <span class="text-xs font-bold  tracking-wider text-gray-500 block mb-1">Status Pengajuan saat Dihapus</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $pemeriksaan->status_pengajuan_badge['class'] ?? 'bg-gray-100 text-gray-700' }}">
                    {{ $pemeriksaan->status_pengajuan }}
                </span>
            </div>

            <div class="p-3.5 bg-gray-50/70 rounded-xl border border-gray-100">
                <span class="text-xs font-bold  tracking-wider text-gray-500 block mb-1">Penginput Data</span>
                <p class="font-bold text-gray-900">{{ $pemeriksaan->creator->official_name ?? $pemeriksaan->creator->name ?? '-' }}</p>
                <span class="text-xs text-gray-500">{{ $pemeriksaan->created_at->translatedFormat('d M Y, H:i') }}</span>
            </div>
        </div>
    </div>

    {{-- Card 2: Susunan Tim Pemeriksa --}}
    @if($pemeriksaan->timPemeriksas->isNotEmpty())
        <div class="rounded-2xl border border-gray-200/80 bg-white p-6 shadow-sm space-y-4">
            <h3 class="text-xs font-bold  tracking-wider text-gray-700 flex items-center gap-2">
                <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Susunan Tim Pemeriksa ({{ $pemeriksaan->timPemeriksas->count() }} Personel)
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                @foreach($pemeriksaan->timPemeriksas as $tim)
                    <div class="p-3 bg-primary-50/40 rounded-xl border border-primary-100 flex items-start gap-2.5">
                        <div class="h-7 w-7 rounded-lg bg-primary-100 text-primary-700 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">
                            {{ substr($tim->peran_tim, 0, 1) }}
                        </div>
                        <div>
                            <span class="text-xs font-bold  tracking-wider text-primary-600 block">{{ $tim->peran_tim }}</span>
                            <p class="font-bold text-gray-900">{{ $tim->pegawai->nama ?? $tim->nama_pegawai_manual ?? '-' }}</p>
                            @if($tim->pegawai?->nip)
                                <span class="text-xs text-gray-500 font-mono">NIP: {{ $tim->pegawai->nip }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Card 3: Rincian LHP & Temuan & Tindak Lanjut --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold  tracking-wider text-gray-700 flex items-center gap-2">
                <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Rincian LHP, Temuan &amp; Tindak Lanjut Terkait ({{ $pemeriksaan->lhps->count() }} LHP / {{ $pemeriksaan->temuans->count() }} Temuan)
            </h3>
        </div>

        @forelse($pemeriksaan->lhps as $lhp)
            <div class="rounded-2xl border border-gray-200/80 bg-white p-6 shadow-sm space-y-5">
                {{-- LHP Header --}}
                <div class="pb-3 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-2">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-primary-50 text-primary-700 border border-primary-200">
                                LHP
                            </span>
                            <h4 class="text-sm font-bold text-gray-900">No: {{ $lhp->nomor_lhp }}</h4>
                            <span class="text-xs text-gray-500">Tanggal: {{ $lhp->tanggal_lhp?->translatedFormat('d M Y') ?? '-' }}</span>
                        </div>
                        <p class="text-xs text-gray-600 mt-0.5">{{ $lhp->judul_lhp }}</p>
                    </div>

                    @if($lhp->dokumen_lhp)
                        <span class="text-xs text-gray-500 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Berkas LHP Tersimpan di Arsip
                        </span>
                    @endif
                </div>

                {{-- Temuan List under this LHP --}}
                @php
                    $temuansUnderLhp = $pemeriksaan->temuans->where('lhp_id', $lhp->id);
                @endphp

                @if($temuansUnderLhp->isNotEmpty())
                    <div class="space-y-4">
                        @foreach($temuansUnderLhp as $tIdx => $t)
                            <div class="rounded-xl border border-gray-200/80 bg-gray-50/40 p-4 space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-2.5">
                                        <span class="h-6 w-6 rounded-lg bg-primary-100 text-primary-700 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">
                                            #{{ $tIdx + 1 }}
                                        </span>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                @if($t->ada_unsur_kerugian)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                        Temuan Kerugian Daerah
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                        Temuan Administrasi
                                                    </span>
                                                @endif
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $t->status_penyelesaian_badge['class'] ?? 'bg-gray-100 text-gray-700' }}">
                                                    Status: {{ $t->status_penyelesaian }}
                                                </span>
                                            </div>
                                            <h5 class="text-xs font-bold text-gray-900 mt-1">{{ $t->judul_temuan }}</h5>
                                        </div>
                                    </div>

                                    @if($t->ada_unsur_kerugian && $t->nilai_kerugian > 0)
                                        <div class="text-right shrink-0">
                                            <span class="text-xs  font-bold text-gray-500 block">Nilai Kerugian:</span>
                                            <span class="text-xs font-bold text-amber-700">{{ $t->formatted_nilai_kerugian }}</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Uraian & Rekomendasi --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs bg-white p-3 rounded-lg border border-gray-100">
                                    <div>
                                        <span class="text-xs font-bold  text-gray-500 block mb-0.5">Uraian Temuan:</span>
                                        <p class="text-gray-700 leading-relaxed">{{ $t->uraian_temuan }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold  text-gray-500 block mb-0.5">Rekomendasi / Instruksi:</span>
                                        <p class="text-gray-700 leading-relaxed">{{ $t->rekomendasi }}</p>
                                    </div>
                                </div>

                                {{-- Tindak Lanjut History --}}
                                @if($t->tindakLanjuts->isNotEmpty())
                                    <div class="pt-2 border-t border-gray-200/60">
                                        <span class="text-xs font-bold  tracking-wider text-primary-700 block mb-2">
                                            Riwayat Tindak Lanjut ({{ $t->tindakLanjuts->count() }}):
                                        </span>
                                        <div class="space-y-2">
                                            @foreach($t->tindakLanjuts as $tl)
                                                <div class="p-2.5 bg-white rounded-lg border border-gray-200 text-xs flex flex-col md:flex-row md:items-center justify-between gap-2">
                                                    <div>
                                                        <div class="flex items-center gap-2">
                                                            <span class="font-bold text-gray-900">{{ $tl->tanggal_tindak_lanjut?->translatedFormat('d M Y') ?? '-' }}</span>
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $tl->status_verifikasi_badge['class'] ?? 'bg-gray-100 text-gray-700' }}">
                                                                {{ $tl->status_verifikasi }}
                                                            </span>
                                                        </div>
                                                        <p class="text-gray-600 text-xs mt-0.5">{{ $tl->uraian_tindak_lanjut }}</p>
                                                    </div>

                                                    @if($tl->jumlah_setor > 0)
                                                        <div class="text-right shrink-0">
                                                            <span class="text-xs text-gray-500 block font-semibold">Setoran:</span>
                                                            <span class="text-xs font-bold text-emerald-700">{{ $tl->formatted_jumlah_setor }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-gray-500 italic">Belum ada data temuan yang tercatat di bawah LHP ini.</p>
                @endif
            </div>
        @empty
            <div class="rounded-2xl border border-gray-200/80 bg-white p-8 text-center text-gray-500">
                <p class="text-xs">Tidak ada LHP atau temuan yang tercatat pada pemeriksaan ini.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
