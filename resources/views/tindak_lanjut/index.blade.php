@extends('layouts.app')

@section('title', 'Pusat Monitoring Tindak Lanjut')
@section('page-title', 'Pusat Monitoring & Riwayat Tindak Lanjut')
@section('page-subtitle', 'Monitoring penyelesaian temuan, penyetoran kas daerah / kas negara, dan verifikasi tindak lanjut')

@section('content')
<div class="space-y-6">
    {{-- Top Action & Title Bar for OPD & Admins --}}
    @if(!auth()->user()->isPemantau())
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div>
                <h2 class="text-base font-bold tracking-tight">
                    @if(auth()->user()->isOpd())
                        Realisasi & Bukti Tindak Lanjut OPD
                    @else
                        Pusat Monitoring & Realisasi Tindak Lanjut
                    @endif
                </h2>
                <p class="text-xs text-gray-600 mt-0.5">
                    @if(auth()->user()->isOpd())
                        Sampaikan realisasi rekomendasi dan unggah bukti setor atas temuan yang ditujukan ke instansi Anda.
                    @else
                        Pantau riwayat tindak lanjut, verifikasi keabsahan setoran, dan pemenuhan rekomendasi temuan.
                    @endif
                </p>
            </div>
            <div class="w-full sm:w-auto">
                <button type="button" onclick="openGlobalTlModal()"
                        class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-lg bg-primary-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-primary-800 sm:w-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Sampaikan Tindak Lanjut</span>
                </button>
            </div>
        </div>
    @endif

    {{-- Filter Bar & Search --}}
    <div class="rounded-2xl border border-gray-200/80 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('tindak-lanjut.index') }}" class="idds-filter-bar min-w-0">
            <div class="idds-filter-search relative min-w-0">
                <input type="text" name="search" value="{{ $search }}" aria-label="Cari tindak lanjut"
                       placeholder="Cari uraian, no. bukti setor, no. LHP, atau temuan..."
                       class="w-full border bg-white py-2 pl-9 pr-4 text-xs text-gray-800 placeholder-gray-400 idds-form-control">
                <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            {{-- Filter Status Verifikasi --}}
            <select name="status_verifikasi" aria-label="Filter berdasarkan status verifikasi"
                    class="w-full min-w-0 border bg-white px-3 py-2 text-xs font-medium text-gray-700 idds-form-control">
                <option value="">Semua Status Verifikasi</option>
                <option value="MENUNGGU VERIFIKASI" {{ $statusVerifikasi === 'MENUNGGU VERIFIKASI' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                <option value="SESUAI" {{ $statusVerifikasi === 'SESUAI' ? 'selected' : '' }}>Diverifikasi Sesuai</option>
                <option value="BELUM SESUAI" {{ $statusVerifikasi === 'BELUM SESUAI' ? 'selected' : '' }}>Belum Sesuai</option>
                <option value="TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH" {{ $statusVerifikasi === 'TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH' ? 'selected' : '' }}>Tidak dapat ditindaklanjuti (alasan sah)</option>
            </select>

            @if(auth()->user()->isSuperAdmin() || auth()->user()->isPemantau())
                <select name="sumber" aria-label="Filter berdasarkan sumber pemeriksaan"
                        class="w-full min-w-0 border bg-white px-3 py-2 text-xs font-medium text-gray-700 idds-form-control">
                    <option value="">Semua Sumber</option>
                    @foreach($sumberList as $s)
                        <option value="{{ $s }}" {{ $sumber === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            @endif

            @if(!auth()->user()->isOpd())
                <select name="opd_id" aria-label="Filter berdasarkan OPD"
                        class="w-full min-w-0 border bg-white px-3 py-2 text-xs font-medium text-gray-700 idds-form-control">
                    <option value="">Semua OPD</option>
                    @foreach($opds as $o)
                        <option value="{{ $o->id }}" {{ $opdId == $o->id ? 'selected' : '' }}>{{ $o->kode }} - {{ $o->nama }}</option>
                    @endforeach
                </select>
            @endif

            <div class="idds-filter-actions">
                <x-action-button variant="filter" type="submit" class="w-full sm:w-auto">
                    Filter
                </x-action-button>

                @if($search || $statusVerifikasi || $opdId || $sumber)
                    <a href="{{ route('tindak-lanjut.index') }}" class="inline-flex min-h-10 items-center rounded-control px-3 text-xs font-bold text-gray-500 hover:bg-gray-50 hover:text-gray-700">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabel Monitoring Tindak Lanjut --}}
    <div class="overflow-hidden rounded-3xl border border-gray-200/90 bg-white shadow-sm">
        <div class="idds-table-region overflow-x-auto" role="region" aria-label="Daftar tindak lanjut. Geser horizontal untuk melihat seluruh kolom." tabindex="0">
            <p class="mb-2 text-xs text-gray-500 sm:hidden" aria-hidden="true">Geser tabel ke samping untuk melihat kolom lainnya.</p>
            <table class="w-full text-left text-xs text-gray-600">
                <caption class="sr-only">Daftar tindak lanjut hasil pemeriksaan</caption>
                <thead class="bg-gray-50/80 border-b border-gray-200 text-xs tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="px-5 py-4 font-bold">Tanggal & Penginput</th>
                        <th scope="col" class="px-5 py-4 font-bold">Temuan Terkait & LHP</th>
                        <th scope="col" class="px-5 py-4 font-bold">OPD Target</th>
                        <th scope="col" class="px-5 py-4 font-bold">Uraian & Bukti</th>
                        <th scope="col" class="px-5 py-4 font-bold">Nilai Setoran</th>
                        <th scope="col" class="px-5 py-4 font-bold text-center">Status Verifikasi</th>
                        <th scope="col" class="px-5 py-4 font-bold text-center">Status Temuan</th>
                        <th scope="col" class="px-5 py-4 font-bold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($tindakLanjuts as $tl)
                        @php
                            $temuan = $tl->temuan;
                            $lhp = $temuan?->lhp ?? $tl->lhp;
                            $pemeriksaan = $lhp?->pemeriksaan ?? $temuan?->pemeriksaan;
                            $vBadge = $tl->status_verifikasi_badge;
                            $tBadge = $temuan ? $temuan->status_penyelesaian_badge : null;
                        @endphp
                        <tr class="hover:bg-primary-50/20 transition">
                            {{-- Tanggal & Penginput --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="font-bold text-gray-900 block">{{ $tl->tanggal_tindak_lanjut ? $tl->tanggal_tindak_lanjut->format('d/m/Y') : '-' }}</span>
                                <span class="text-xs text-gray-500 block mt-0.5">{{ $tl->creator?->official_name ?? 'User' }}</span>
                                @if($temuan && $temuan->sumber_pemeriksaan)
                                    <x-sumber-badge :sumber="$temuan->sumber_pemeriksaan" size="xs" class="mt-1" />
                                @endif
                            </td>

                            {{-- Temuan & LHP --}}
                            <td class="px-5 py-4 max-w-xs">
                                @if($temuan)
                                    <a href="{{ route('temuan.show', $temuan) }}" class="font-bold text-primary-700 hover:underline block line-clamp-1">
                                        {{ $temuan->judul_temuan }}
                                    </a>
                                    <span class="text-xs text-gray-500 block mt-0.5">
                                        No: {{ $temuan->nomor_temuan ?? '-' }} • {{ $temuan->ada_unsur_kerugian ? ($temuan->kategori_kerugian ?? 'Kerugian Daerah') : 'Administrasi' }}
                                    </span>
                                @endif

                                @if($lhp)
                                    <span class="text-xs text-gray-500 block mt-0.5 line-clamp-1">
                                        LHP: <strong>{{ $lhp->nomor_lhp }}</strong>
                                    </span>
                                @endif
                            </td>

                            {{-- OPD Target --}}
                            <td class="px-5 py-4 max-w-[160px]">
                                <span class="font-semibold text-gray-800 block line-clamp-2">
                                    {{ $tl->opd?->nama ?? ($temuan?->opd?->nama ?? '-') }}
                                </span>
                            </td>

                            {{-- Uraian & Bukti --}}
                            <td class="px-5 py-4 max-w-xs min-w-0">
                                <p class="text-gray-800 line-clamp-2 leading-relaxed">{{ $tl->uraian_tindak_lanjut }}</p>
                                @if($tl->dokumen_bukti && $temuan)
                                    <div class="mt-1.5 min-w-0">
                                        <a href="{{ route('temuan.tindak-lanjut.download', [$temuan, $tl]) }}" target="_blank"
                                           aria-label="Lihat bukti tindak lanjut"
                                           class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-700 whitespace-nowrap hover:bg-gray-200 transition">
                                            <svg class="h-3 w-3 shrink-0 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            <span>Lihat Bukti</span>
                                        </a>
                                    </div>
                                @endif
                            </td>

                            {{-- Setoran Kasda --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($tl->jumlah_setor > 0)
                                    <span class="font-bold text-emerald-700 block">{{ $tl->formatted_jumlah_setor }}</span>
                                    @if($tl->nomor_bukti_setor)
                                        <span class="text-xs text-gray-500 block mt-0.5">STS: {{ $tl->nomor_bukti_setor }}</span>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-500 italic">Administrasi</span>
                                @endif
                            </td>

                            {{-- Status Verifikasi --}}
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <x-status-badge :badge="$vBadge" size="xs" />
                                @if($tl->catatan_verifikasi)
                                    <p class="text-xs text-gray-500 italic mt-1 line-clamp-1 max-w-[140px] mx-auto" title="{{ $tl->catatan_verifikasi }}">
                                        "{{ $tl->catatan_verifikasi }}"
                                    </p>
                                @endif
                            </td>

                            {{-- Status Penyelesaian Temuan --}}
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                @if($tBadge)
                                    <x-status-badge :badge="$tBadge" size="xs" dot-class="w-1.5 h-1.5 mr-1" />
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if(auth()->user()->isSuperAdmin() && $temuan)
                                        <button type="button"
                                                onclick="openVerifikasiModal({{ $temuan->id }}, {{ $tl->id }}, '{{ $tl->status_verifikasi }}', '{{ addslashes($tl->catatan_verifikasi ?? '') }}', '{{ addslashes($temuan->judul_temuan) }}', '{{ $tl->formatted_jumlah_setor }}')"
                                                class="px-2.5 py-1 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs font-bold shadow-xs transition cursor-pointer">
                                            Verifikasi
                                        </button>
                                    @endif

                                    @if($temuan)
                                        <a href="{{ route('temuan.show', $temuan) }}"
                                           class="inline-flex items-center gap-1 rounded-lg bg-primary-50 px-2 py-1 text-xs font-bold text-primary-700 hover:bg-primary-100 transition">
                                            Lihat Detail
                                        </a>
                                    @endif

                                    @php
                                        $canDeleteThisTl = $tl->status_verifikasi === 'MENUNGGU VERIFIKASI' && $temuan && (
                                            auth()->user()->isSuperAdmin() ||
                                            (auth()->user()->isAdminSumber() && auth()->user()->getSumberPemeriksaan() === $temuan->sumber_pemeriksaan) ||
                                            (auth()->user()->isOpd() && $tl->created_by === auth()->id())
                                        );
                                    @endphp
                                    @if($canDeleteThisTl)
                                        <form method="POST" action="{{ route('temuan.tindak-lanjut.destroy', [$temuan, $tl]) }}"
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus tindak lanjut ini?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg px-2 py-1 text-state-danger hover:bg-red-50 text-xs font-bold transition cursor-pointer" title="Hapus Draf Tindak Lanjut">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 text-xs font-medium">
                                <div class="flex flex-col items-center">
                                    <p>Belum ada catatan riwayat tindak lanjut yang sesuai filter.</p>
                                    @if($search || $statusVerifikasi || $opdId || $sumber)
                                        <x-action-button href="{{ route('tindak-lanjut.index') }}" variant="filter-reset">
                                            Hapus filter dan lihat semua data
                                        </x-action-button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tindakLanjuts->hasPages())
            <div class="border-t border-gray-200 px-6 py-4">
                {{ $tindakLanjuts->links() }}
            </div>
        @endif
    </div>
</div>

{{-- MODAL VERIFIKASI LANGSUNG SUPER ADMIN --}}
@if(auth()->user()->isSuperAdmin())
<div id="verifikasiModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="verifikasi-modal-title" tabindex="-1">
    <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100 my-8">
        <div class="px-6 py-4 bg-primary-800 text-white flex items-center justify-between">
            <h3 id="verifikasi-modal-title" class="text-sm font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-primary-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Verifikasi Tindak Lanjut (Super Admin)
            </h3>
            <button type="button" onclick="closeVerifikasiModal()" aria-label="Tutup dialog verifikasi tindak lanjut" class="text-white/80 hover:text-white text-lg font-bold cursor-pointer">✕</button>
        </div>

        <form id="verifikasiForm" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf

            <div class="p-3 bg-primary-50 rounded-xl border border-primary-100 text-xs text-primary-900 space-y-1">
                <span class="text-xs  font-bold text-primary-600 block">Target Temuan:</span>
                <p id="modalVerifTemuanTitle" class="font-bold"></p>
                <p id="modalVerifSetoranInfo" class="text-xs text-primary-700 font-semibold"></p>
            </div>

            <fieldset>
                <legend class="block text-xs font-bold text-gray-700 mb-2">Hasil Verifikasi <span class="text-state-danger">*</span></legend>
                <div class="space-y-2">
                    <label class="flex items-start p-3 border rounded-xl cursor-pointer hover:bg-emerald-50/40 border-gray-200 transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50">
                        <input type="radio" name="status_verifikasi" value="SESUAI" required onchange="handleVerifikasiChange(this.value)" class="mt-0.5 text-state-success focus:ring-emerald-500">
                        <div class="ml-2.5">
                            <span class="text-xs font-bold text-gray-900 block">Diterima / Sesuai (Bukti Valid & Diakui Masuk Kasda / Kas Negara)</span>
                            <span class="text-xs text-gray-500 block">Nominal setoran diakui. Sistem otomatis menghitung: jika baru bayar sebagian, status temuan menjadi <strong>"Belum Sesuai"</strong>; jika sudah lunas 100%, otomatis <strong>"Sesuai"</strong>.</span>
                        </div>
                    </label>

                    <label class="flex items-start p-3 border rounded-xl cursor-pointer hover:bg-rose-50/40 border-gray-200 transition has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50/50">
                        <input type="radio" name="status_verifikasi" value="BELUM SESUAI" required onchange="handleVerifikasiChange(this.value)" class="mt-0.5 text-state-danger focus:ring-rose-500">
                        <div class="ml-2.5">
                            <span class="text-xs font-bold text-gray-900 block">Belum Sesuai (Bukti Tidak Sah / Perlu Perbaikan)</span>
                            <span class="text-xs text-gray-500 block">Tindak lanjut ditolak atau dikembalikan ke OPD untuk diperbaiki bukti setoran / dokumen pendukungnya.</span>
                        </div>
                    </label>

                    <label class="flex items-start p-3 border rounded-xl cursor-pointer hover:bg-amber-50/40 border-gray-200 transition has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50/50">
                        <input type="radio" name="status_verifikasi" value="TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH" required onchange="handleVerifikasiChange(this.value)" class="mt-0.5 text-state-warning focus:ring-amber-500">
                        <div class="ml-2.5">
                            <span class="text-xs font-bold text-gray-900 block">Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah</span>
                            <span class="text-xs text-gray-500 block">Diperlukan alasan hukum / pertimbangan sah (misal debitur meninggal dunia tanpa ahli waris / pailit).</span>
                        </div>
                    </label>
                </div>
            </fieldset>

            <div>
                <label for="catatan_verifikasi" class="block text-xs font-bold text-gray-700 mb-1">Catatan Verifikasi / Petunjuk Perbaikan</label>
                <textarea id="catatan_verifikasi" name="catatan_verifikasi" rows="3"
                          placeholder="Tuliskan catatan pemeriksaan berkas bukti..."
                          class="w-full border p-3 text-xs text-gray-800 idds-form-control"></textarea>
            </div>

            <div id="alasanKhususSection" class="hidden space-y-3 rounded-xl border border-amber-200 bg-amber-50/60 p-4">
                <div>
                    <label for="alasan_khusus" class="block text-xs font-bold text-amber-900 mb-1">Alasan Sah / Pertimbangan Hukum <span class="text-state-danger">*</span></label>
                    <textarea id="alasan_khusus" name="alasan_khusus" rows="3"
                              placeholder="Uraikan dasar hukum tidak dapat ditindaklanjuti..."
                              class="w-full border border-amber-200 bg-white p-3 text-xs text-gray-800 idds-form-control"></textarea>
                </div>
                <div>
                    <label for="dokumen_pendukung_alasan" class="block text-xs font-bold text-amber-900 mb-1">Dokumen Pendukung Alasan (PDF/DOC)</label>
                    <input id="dokumen_pendukung_alasan" type="file" name="dokumen_pendukung_alasan" accept=".pdf,.doc,.docx"
                           class="w-full rounded-xl border border-amber-200 bg-white px-3 py-1.5 text-xs text-gray-800 focus:outline-none">
                </div>
            </div>

            <div class="flex flex-col items-stretch justify-end gap-2 pt-2 border-t border-gray-100 sm:flex-row sm:items-center">
                <button type="button" onclick="closeVerifikasiModal()" class="px-4 py-2 rounded-xl border border-gray-200 text-xs font-bold text-gray-600 hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold shadow-xs">
                    Simpan Verifikasi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
</script>
@endif

{{-- MODAL INPUT TINDAK LANJUT TERPADU (OPD / ADMIN SUMBER / SUPER ADMIN) --}}
@if(!auth()->user()->isPemantau())
<div id="modal-tambah-tl-global" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="tl-global-modal-title" tabindex="-1">
    <div class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl border border-gray-100 my-8">
        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 id="tl-global-modal-title" class="font-bold text-gray-900">Sampaikan Tindak Lanjut Temuan</h3>
                    <p class="text-xs text-gray-500">Unggah bukti pemenuhan rekomendasi &amp; setoran kas daerah</p>
                </div>
            </div>
            <button type="button" onclick="closeGlobalTlModal()" aria-label="Tutup dialog tindak lanjut" title="Tutup" class="text-gray-500 hover:text-gray-600 cursor-pointer">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        @if($availableTemuans->isEmpty())
            <div class="py-10 text-center space-y-3">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-state-success">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h4 class="text-sm font-bold text-gray-800">Tidak Ada Temuan Aktif yang Membutuhkan Tindak Lanjut</h4>
                <p class="text-xs text-gray-500 max-w-sm mx-auto">Seluruh temuan yang menjadi kewenangan Anda telah diselesaikan atau berstatus Sesuai.</p>
                <button type="button" onclick="closeGlobalTlModal()" class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 cursor-pointer">
                    Tutup
                </button>
            </div>
        @else
            <form id="form-tambah-tl-global" method="POST" action="" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf

                {{-- Pilih Temuan Target --}}
                <div>
                    <label for="global_temuan_select" class="mb-1 block text-xs font-bold tracking-wider text-gray-700">
                        Pilih Temuan Hasil Pemeriksaan <span class="text-state-danger">*</span>
                    </label>
                    <select id="global_temuan_select" required onchange="handleGlobalTemuanChange(this)"
                            class="w-full border bg-white px-3 py-2 text-xs font-semibold text-gray-800 idds-form-control">
                        <option value="">-- Pilih Salah Satu Temuan --</option>
                        @foreach($availableTemuans as $at)
                            <option value="{{ $at->id }}"
                                    data-action="{{ route('temuan.tindak-lanjut.store', $at) }}"
                                    data-kerugian="{{ $at->ada_unsur_kerugian ? 1 : 0 }}"
                                    data-sisa="{{ (float) $at->sisa_kerugian }}"
                                    data-format-sisa="{{ $at->formatted_sisa_kerugian }}"
                                    data-judul="{{ $at->judul_temuan }}"
                                    data-sumber="{{ $at->sumber_pemeriksaan }}"
                                    data-tahun="{{ $at->tahun }}"
                                    data-opd="{{ $at->opd?->nama ?? '-' }}"
                                    data-rekomendasi="{{ Str::limit($at->rekomendasi, 120) }}">
                                [{{ $at->sumber_pemeriksaan }} {{ $at->tahun }}] {{ Str::limit($at->judul_temuan, 65) }} — {{ $at->ada_unsur_kerugian ? 'Sisa: ' . $at->formatted_sisa_kerugian : 'Administrasi' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Preview Card Temuan Terpilih --}}
                <div id="global_preview_card" class="hidden rounded-xl border border-emerald-100 bg-emerald-50/40 p-3.5 space-y-1.5 text-xs text-gray-700">
                    <div class="flex items-center justify-between">
                        <span id="preview_sumber_badge" class="rounded-md border px-2 py-0.5 text-xs font-bold  tracking-wider"></span>
                        <span id="preview_opd_text" class="text-xs font-semibold text-gray-500"></span>
                    </div>
                    <p id="preview_judul" class="font-bold text-gray-900 text-xs leading-snug"></p>
                    <p id="preview_rekomendasi" class="text-xs text-gray-600 italic line-clamp-2"></p>
                </div>

                {{-- Tanggal Tindak Lanjut --}}
                <div>
                    <label for="gtl_tanggal" class="mb-1 block text-xs font-bold text-gray-700">Tanggal Pelaksanaan Tindak Lanjut <span class="text-state-danger">*</span></label>
                    <input type="date" id="gtl_tanggal" name="tanggal_tindak_lanjut" value="{{ date('Y-m-d') }}" required
                           class="w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control">
                </div>

                {{-- Uraian Tindak Lanjut --}}
                <div>
                    <label for="gtl_uraian" class="mb-1 block text-xs font-bold text-gray-700">Uraian Realisasi Tindak Lanjut <span class="text-state-danger">*</span></label>
                    <textarea id="gtl_uraian" name="uraian_tindak_lanjut" rows="3" required
                              class="w-full border bg-white px-3 py-2 text-xs text-gray-800 idds-form-control"
                              placeholder="Uraikan tindakan perbaikan yang telah dilaksanakan oleh instansi OPD..."></textarea>
                </div>

                {{-- Bagian Khusus Setoran Kasda (Jika Temuan memiliki unsur kerugian) --}}
                <div id="gtl_kerugian_box" class="hidden rounded-xl border border-emerald-200 bg-emerald-50/40 p-3.5 space-y-3">
                    <div>
                        <label for="gtl_jumlah_setor" class="mb-1 block text-xs font-bold text-emerald-900">
                            Jumlah Nominal yang Disetor (Kasda / Kas Negara) <span class="text-state-danger">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-xs font-bold text-emerald-700">Rp</span>
                            <input type="text" id="gtl_jumlah_setor" name="jumlah_setor" inputmode="numeric"
                                   class="w-full border border-emerald-200 bg-white py-1.5 pl-9 pr-3 text-xs font-bold text-gray-900 idds-form-control"
                                   placeholder="0" oninput="calculateGlobalSisa(this)" autocomplete="off">
                        </div>

                        {{-- Box Kalkulasi Sisa Real-time --}}
                        <div class="mt-2 rounded-lg border border-emerald-200/80 bg-white p-2.5 space-y-1 text-xs">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500">Sisa Kerugian Belum Disetor:</span>
                                <span id="gtl_display_sisa_awal" class="font-bold text-state-danger">Rp 0</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500">Nominal Setoran Saat Ini:</span>
                                <span id="gtl_display_setor_live" class="font-bold text-state-success">Rp 0</span>
                            </div>
                            <div class="border-t border-gray-100 pt-1 flex items-center justify-between text-xs">
                                <span class="font-semibold text-gray-700">Estimasi Sisa Setelah Setor:</span>
                                <span id="gtl_display_sisa_akhir" class="font-bold text-gray-900">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="gtl_nomor_bukti" class="mb-1 block text-xs font-bold text-emerald-900">Nomor Bukti Setor / STS / NTPN</label>
                        <input type="text" id="gtl_nomor_bukti" name="nomor_bukti_setor"
                               class="w-full border border-emerald-200 bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control"
                               placeholder="Contoh: STS-2026/04/001">
                    </div>

                    <div>
                        <label for="gtl_tanggal_setor" class="mb-1 block text-xs font-bold text-emerald-900">Tanggal Penyetoran ke Bank / Kasda / Kas Negara</label>
                        <input type="date" id="gtl_tanggal_setor" name="tanggal_setor"
                               class="w-full border border-emerald-200 bg-white px-3 py-1.5 text-xs text-gray-800 idds-form-control">
                    </div>
                </div>

                {{-- Dokumen Bukti --}}
                <div>
                    <label for="gtl_dokumen" class="mb-1 block text-xs font-bold text-gray-700">
                        Unggah Berkas Bukti (PDF / JPG / PNG / DOC, Maks. 15MB) <span class="text-state-danger">*</span>
                    </label>
                    <input type="file" id="gtl_dokumen" name="dokumen_bukti" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                           class="w-full rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-800 focus:outline-none">
                    <p class="text-xs text-gray-500 mt-0.5">Wajib melampirkan scan STS / slip setoran bank atau berkas administrasi pendukung.</p>
                </div>

                <div class="flex flex-col items-stretch justify-end gap-2 pt-3 border-t border-gray-100 sm:flex-row sm:items-center">
                    <button type="button" onclick="closeGlobalTlModal()"
                            class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" id="gtl_submit_btn" disabled
                            class="rounded-xl bg-emerald-600 px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition cursor-pointer">
                        Ajukan Tindak Lanjut
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endif

<script>
    // --- Global Modal Tambah Tindak Lanjut (OPD / Admin Sumber / Super Admin) ---
    let currentSelectedSisa = 0;

    function openGlobalTlModal() {
        const modal = document.getElementById('modal-tambah-tl-global');
        if (modal) modal.classList.remove('hidden');
    }

    function closeGlobalTlModal() {
        const modal = document.getElementById('modal-tambah-tl-global');
        if (modal) modal.classList.add('hidden');
    }

    function handleGlobalTemuanChange(select) {
        const selectedOpt = select.options[select.selectedIndex];
        const form = document.getElementById('form-tambah-tl-global');
        const previewCard = document.getElementById('global_preview_card');
        const kerugianBox = document.getElementById('gtl_kerugian_box');
        const submitBtn = document.getElementById('gtl_submit_btn');

        if (!select.value) {
            if (form) form.action = '';
            if (previewCard) previewCard.classList.add('hidden');
            if (kerugianBox) kerugianBox.classList.add('hidden');
            if (submitBtn) submitBtn.disabled = true;
            currentSelectedSisa = 0;
            return;
        }

        const action = selectedOpt.getAttribute('data-action');
        const hasKerugian = selectedOpt.getAttribute('data-kerugian') === '1';
        const sisa = parseFloat(selectedOpt.getAttribute('data-sisa') || '0');
        const formatSisa = selectedOpt.getAttribute('data-format-sisa') || 'Rp 0';
        const judul = selectedOpt.getAttribute('data-judul') || '';
        const sumber = selectedOpt.getAttribute('data-sumber') || '';
        const opd = selectedOpt.getAttribute('data-opd') || '';
        const rekomendasi = selectedOpt.getAttribute('data-rekomendasi') || '';

        if (form) form.action = action;
        currentSelectedSisa = sisa;

        // Tampilkan Preview Card
        if (previewCard) {
            const sourceBadge = document.getElementById('preview_sumber_badge');
            const sourceClasses = {
                BPK: 'bg-emerald-100 text-emerald-800 border-emerald-200',
                BPKP: 'bg-blue-100 text-blue-800 border-blue-200',
                ITJEN: 'bg-amber-100 text-amber-800 border-amber-200',
                Inspektorat: 'bg-primary-100 text-primary-800 border-primary-200',
            };
            sourceBadge.className = `rounded-md border px-2 py-0.5 text-xs font-bold  tracking-wider ${sourceClasses[sumber] || 'bg-gray-100 text-gray-800 border-gray-200'}`;
            sourceBadge.textContent = sumber;
            document.getElementById('preview_opd_text').textContent = 'OPD: ' + opd;
            document.getElementById('preview_judul').textContent = judul;
            document.getElementById('preview_rekomendasi').textContent = rekomendasi ? 'Rekomendasi: "' + rekomendasi + '"' : '';
            previewCard.classList.remove('hidden');
        }

        // Tampilkan / Sembunyikan Box Setoran Kerugian
        if (kerugianBox) {
            if (hasKerugian) {
                kerugianBox.classList.remove('hidden');
                document.getElementById('gtl_display_sisa_awal').textContent = formatSisa;
                document.getElementById('gtl_display_setor_live').textContent = 'Rp 0';
                document.getElementById('gtl_display_sisa_akhir').textContent = formatSisa;
                const setorInput = document.getElementById('gtl_jumlah_setor');
                if (setorInput) setorInput.value = '';
            } else {
                kerugianBox.classList.add('hidden');
            }
        }

        if (submitBtn) submitBtn.disabled = false;
    }

    function calculateGlobalSisa(input) {
        updateInstallmentRemaining(input, currentSelectedSisa, 'gtl_display_setor_live', 'gtl_display_sisa_akhir');
    }
</script>
@endsection
