@extends('layouts.app')

@section('title', 'Perbaiki LHP ' . $lhp->nomor_lhp)
@section('page-title', 'Perbaiki Data LHP')
@section('page-subtitle', 'Perbarui data laporan yang dikembalikan oleh Super Admin untuk direvisi')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Pengajuan Saya', 'href' => route('pengajuan-saya.index')],
        ['label' => $lhp->nomor_lhp, 'href' => route('pemeriksaan.lhp.show', [$pemeriksaan, $lhp])],
        ['label' => 'Perbaiki LHP'],
    ]" />

    @if($pemeriksaan->catatan_persetujuan)
        <section class="rounded-xl border border-orange-200 bg-orange-50 p-4" role="note" aria-label="Catatan revisi">
            <h2 class="text-sm font-bold text-orange-900">Catatan revisi dari Super Admin</h2>
            <p class="mt-1 whitespace-pre-line text-sm text-orange-900">{{ $pemeriksaan->catatan_persetujuan }}</p>
        </section>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <form method="POST" action="{{ route('pemeriksaan.lhp.update', [$pemeriksaan, $lhp]) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="nomor_lhp" class="mb-1.5 block text-sm font-semibold text-gray-800">Nomor LHP <span class="text-state-danger">*</span></label>
                    <input id="nomor_lhp" name="nomor_lhp" type="text" value="{{ old('nomor_lhp', $lhp->nomor_lhp) }}" required maxlength="100" class="idds-form-control w-full" @error('nomor_lhp') aria-invalid="true" aria-describedby="nomor_lhp-error" @enderror>
                    @error('nomor_lhp')<p id="nomor_lhp-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="tanggal_lhp" class="mb-1.5 block text-sm font-semibold text-gray-800">Tanggal LHP <span class="text-state-danger">*</span></label>
                    <input id="tanggal_lhp" name="tanggal_lhp" type="date" value="{{ old('tanggal_lhp', $lhp->tanggal_lhp?->format('Y-m-d')) }}" required class="idds-form-control w-full" @error('tanggal_lhp') aria-invalid="true" aria-describedby="tanggal_lhp-error" @enderror>
                    @error('tanggal_lhp')<p id="tanggal_lhp-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="judul_lhp" class="mb-1.5 block text-sm font-semibold text-gray-800">Judul LHP</label>
                <input id="judul_lhp" name="judul_lhp" type="text" value="{{ old('judul_lhp', $lhp->judul_lhp) }}" maxlength="255" class="idds-form-control w-full" @error('judul_lhp') aria-invalid="true" aria-describedby="judul_lhp-error" @enderror>
                @error('judul_lhp')<p id="judul_lhp-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="ringkasan" class="mb-1.5 block text-sm font-semibold text-gray-800">Ringkasan LHP</label>
                <textarea id="ringkasan" name="ringkasan" rows="5" class="idds-form-control w-full" @error('ringkasan') aria-invalid="true" aria-describedby="ringkasan-error" @enderror>{{ old('ringkasan', $lhp->ringkasan) }}</textarea>
                @error('ringkasan')<p id="ringkasan-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
            </div>

            <fieldset class="rounded-xl border border-gray-200 p-4">
                <legend class="px-1 text-sm font-semibold text-gray-800">OPD Target <span class="text-state-danger">*</span></legend>
                <p class="mb-3 text-xs text-gray-500">Pilih minimal satu OPD yang menjadi target pada LHP ini.</p>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach($opds as $opd)
                        <label class="flex min-w-0 items-start gap-2 rounded-lg border border-gray-100 bg-gray-50/70 p-2.5 text-xs text-gray-700 hover:bg-primary-50/50">
                            <input type="checkbox" name="opd_ids[]" value="{{ $opd->id }}" @checked(in_array((string) $opd->id, old('opd_ids', $lhp->opds->pluck('id')->map(fn ($id) => (string) $id)->all()), true)) class="mt-0.5 rounded border-gray-300 text-primary-700 focus:ring-primary-500">
                            <span class="min-w-0"><span class="font-semibold">[{{ $opd->kode }}]</span> {{ $opd->nama }}</span>
                        </label>
                    @endforeach
                </div>
                @error('opd_ids')<p class="mt-2 text-xs text-red-700">{{ $message }}</p>@enderror
                @error('opd_ids.*')<p class="mt-2 text-xs text-red-700">{{ $message }}</p>@enderror
            </fieldset>

            <div>
                <label for="dokumen_lhp" class="mb-1.5 block text-sm font-semibold text-gray-800">Dokumen LHP</label>
                @if($lhp->dokumen_lhp)
                    <p class="mb-2 text-xs text-gray-600">Dokumen saat ini: <a href="{{ route('pemeriksaan.lhp.download', [$pemeriksaan, $lhp]) }}" class="font-semibold text-primary-700 hover:underline">Lihat dokumen</a></p>
                @endif
                <input id="dokumen_lhp" name="dokumen_lhp" type="file" accept=".pdf,.doc,.docx" class="idds-form-control w-full" @error('dokumen_lhp') aria-invalid="true" aria-describedby="dokumen_lhp-error" @enderror>
                <p class="mt-1 text-xs text-gray-500">Kosongkan jika dokumen tidak perlu diganti. Format PDF, DOC, atau DOCX, maksimal 15 MB.</p>
                @error('dokumen_lhp')<p id="dokumen_lhp-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-gray-100 pt-4 sm:flex-row sm:justify-end">
                <a href="{{ route('pemeriksaan.lhp.show', [$pemeriksaan, $lhp]) }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Batal</a>
                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-primary-700 px-4 text-sm font-semibold text-white transition hover:bg-primary-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2">Simpan Perbaikan</button>
            </div>
        </form>
    </section>
</div>
@endsection
