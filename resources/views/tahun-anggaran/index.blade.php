@extends('layouts.app')

@section('title', 'Master Tahun Anggaran')
@section('page-title', 'Master Tahun Anggaran')
@section('page-subtitle', 'Atur tahun yang tersedia untuk input dan tahun berjalan sistem')

@section('content')
<div class="space-y-6">
    <section class="rounded-2xl border border-primary-100 bg-primary-50/50 p-4 text-sm text-primary-900">
        <p class="font-semibold">Tahun berjalan menjadi pilihan awal pada formulir pemeriksaan.</p>
        <p class="mt-1 text-xs text-primary-800">Tahun yang sudah memiliki data tidak dapat dinonaktifkan atau dihapus agar data historis tetap dapat dilihat dan diperbarui.</p>
    </section>

    <section class="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="text-sm font-bold text-gray-900">Tambah Tahun Anggaran</h2>
        <form method="POST" action="{{ route('tahun-anggaran.store') }}" class="mt-4 grid grid-cols-1 items-end gap-3 sm:grid-cols-[minmax(180px,240px)_1fr_auto]">
            @csrf
            <div>
                <label for="tahun" class="mb-1 block text-xs font-semibold text-gray-700">Tahun</label>
                <input id="tahun" name="tahun" type="number" min="1900" max="9999" value="{{ old('tahun') }}" required @error('tahun') aria-invalid="true" aria-describedby="tahun-error" @enderror
                       class="w-full border px-3 py-2.5 text-sm text-gray-800 idds-form-control">
                @error('tahun')
                    <p id="tahun-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                @enderror
            </div>
            <label class="flex items-center gap-2 pb-2 text-xs text-gray-700">
                <input type="checkbox" name="is_current" value="1" {{ old('is_current') ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                Jadikan tahun berjalan
            </label>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Tahun
            </button>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
            <h2 class="text-sm font-bold text-gray-900">Daftar Tahun Anggaran</h2>
        </div>
        <div class="idds-table-region overflow-x-auto" role="region" aria-label="Daftar tahun anggaran. Geser horizontal untuk melihat seluruh kolom." tabindex="0">
            <table class="w-full min-w-[640px] text-left text-sm text-gray-600">
                <thead class="border-b border-gray-200 bg-gray-50/75 text-xs tracking-wider text-gray-500">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-semibold">Tahun</th>
                        <th scope="col" class="px-5 py-3 font-semibold">Data Pemeriksaan</th>
                        <th scope="col" class="px-5 py-3 font-semibold">Status Input</th>
                        <th scope="col" class="px-5 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($tahunAnggarans as $tahunAnggaran)
                        @php $isUsed = in_array($tahunAnggaran->tahun, $usedYears, true); @endphp
                        <tr class="hover:bg-gray-50/70">
                            <td class="px-5 py-4">
                                <span class="font-bold text-gray-900">{{ $tahunAnggaran->tahun }}</span>
                                @if($tahunAnggaran->is_current)
                                    <span class="ml-2 inline-flex rounded-full bg-primary-100 px-2.5 py-0.5 text-xs font-bold text-primary-800">Tahun Berjalan</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs">{{ $isUsed ? 'Sudah digunakan' : 'Belum digunakan' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $tahunAnggaran->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $tahunAnggaran->is_active ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                    {{ $tahunAnggaran->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    @if(!$tahunAnggaran->is_current && $tahunAnggaran->is_active)
                                        <form method="POST" action="{{ route('tahun-anggaran.set-current', $tahunAnggaran) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-lg border border-primary-200 px-3 py-1.5 text-xs font-semibold text-primary-700 hover:bg-primary-50">Jadikan Tahun Berjalan</button>
                                        </form>
                                    @endif
                                    @if(!$isUsed && !$tahunAnggaran->is_current)
                                        <form method="POST" action="{{ route('tahun-anggaran.toggle-status', $tahunAnggaran) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">{{ $tahunAnggaran->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('tahun-anggaran.destroy', $tahunAnggaran) }}" onsubmit="return confirm('Hapus tahun anggaran {{ $tahunAnggaran->tahun }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-table-empty-state colspan="4">
                            <p class="text-sm font-medium">Belum ada data tahun anggaran.</p>
                        </x-table-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
