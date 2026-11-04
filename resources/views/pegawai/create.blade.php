@extends('layouts.app')

@section('title', 'Tambah Pegawai Inspektorat')
@section('page-title', 'Tambah Pegawai Inspektorat')
@section('page-subtitle', 'Formulir penambahan data pegawai / auditor Inspektorat Provinsi Gorontalo')

@section('content')
<div class="max-w-3xl space-y-4">
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Pegawai Pemeriksa', 'href' => route('pegawai.index')],
        ['label' => 'Tambah Pegawai'],
    ]" />
    <x-form-card>
        <form method="POST" action="{{ route('pegawai.store') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                {{-- NIP Pegawai --}}
                <div>
                    <label for="nip" class="mb-1.5 block text-sm font-medium text-gray-700">NIP (Nomor Induk Pegawai)</label>
                    <input type="text" id="nip" name="nip" value="{{ old('nip') }}"
                           class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                           placeholder="Contoh: 19780512 200312 1 002">
                    @error('nip')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Unit / Bidang --}}
                <div>
                    <label for="unit_bidang" class="mb-1.5 block text-sm font-medium text-gray-700">Unit / Irban / Bidang <span class="text-state-danger">*</span></label>
                    <input type="text" id="unit_bidang" name="unit_bidang" value="{{ old('unit_bidang') }}" required
                           data-suggestions='["Sekretariat","Irban Wilayah I","Irban Wilayah II","Irban Wilayah III","Irban Khusus / Investigasi"]'
                           class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                           placeholder="Contoh: Irban I, Irban II, Sekretariat">
                    @error('unit_bidang')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Nama Lengkap Beserta Gelar --}}
                <div class="md:col-span-2">
                    <label for="nama" class="mb-1.5 block text-sm font-medium text-gray-700">Nama Lengkap & Gelar <span class="text-state-danger">*</span></label>
                    <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required
                           class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                           placeholder="Contoh: Dr. Ir. H. Ahmad Rahman, M.Si., CRMO">
                    @error('nama')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Jabatan --}}
                <div>
                    <label for="jabatan" class="mb-1.5 block text-sm font-medium text-gray-700">Jabatan <span class="text-state-danger">*</span></label>
                    <input type="text" id="jabatan" name="jabatan" value="{{ old('jabatan') }}" required
                           class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                           placeholder="Contoh: Auditor Ahli Madya / PPUPD">
                    @error('jabatan')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Pangkat / Golongan --}}
                <div>
                    <label for="pangkat_golongan" class="mb-1.5 block text-sm font-medium text-gray-700">Pangkat / Golongan Ruang</label>
                    <input type="text" id="pangkat_golongan" name="pangkat_golongan" value="{{ old('pangkat_golongan') }}"
                           class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                           placeholder="Contoh: Pembina Tk. I (IV/b)">
                    @error('pangkat_golongan')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Nomor HP / WhatsApp --}}
                <div>
                    <label for="no_hp" class="mb-1.5 block text-sm font-medium text-gray-700">Nomor HP / WhatsApp</label>
                    <input type="text" id="no_hp" name="no_hp" value="{{ old('no_hp') }}"
                           class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                           placeholder="0812-xxxx-xxxx">
                    @error('no_hp')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">Email Pegawai</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                           placeholder="ahmad@inspektorat.gorontaloprov.go.id">
                    @error('email')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Status Aktif --}}
                <div class="md:col-span-2 flex items-center gap-2">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <label for="is_active" class="text-sm font-medium text-gray-700">Status Pegawai Aktif</label>
                </div>
            </div>

            {{-- Buttons --}}
            <div class="flex flex-col-reverse items-stretch gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-end">
                <x-action-button href="{{ route('pegawai.index') }}">Batal</x-action-button>
                <x-action-button type="submit" variant="primary">
                    Simpan Pegawai
                </x-action-button>
            </div>
        </form>
    </x-form-card>
</div>
@endsection
