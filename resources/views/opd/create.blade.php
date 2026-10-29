@extends('layouts.app')

@section('title', 'Tambah OPD')
@section('page-title', 'Tambah OPD')
@section('page-subtitle', 'Formulir penambahan Organisasi Perangkat Daerah baru')

@section('content')
<div class="max-w-3xl space-y-4">
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Data OPD', 'href' => route('opd.index')],
        ['label' => 'Tambah OPD'],
    ]" />
    <x-form-card>
        <form method="POST" action="{{ route('opd.store') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                {{-- Kode OPD --}}
                <div>
                    <label for="kode" class="mb-1.5 block text-sm font-medium text-gray-700">Kode OPD / Singkatan <span class="text-state-danger">*</span></label>
                    <input type="text" id="kode" name="kode" value="{{ old('kode') }}" required
                           class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                           placeholder="Contoh: DINKES, DIKBUD, BAPPEDA">
                    @error('kode')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Singkatan Alternatif --}}
                <div>
                    <label for="singkatan" class="mb-1.5 block text-sm font-medium text-gray-700">Singkatan / Label Lain</label>
                    <input type="text" id="singkatan" name="singkatan" value="{{ old('singkatan') }}"
                           class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                           placeholder="Contoh: Dinas Kesehatan">
                    @error('singkatan')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Nama Lengkap OPD --}}
                <div class="md:col-span-2">
                    <label for="nama" class="mb-1.5 block text-sm font-medium text-gray-700">Nama Lengkap OPD <span class="text-state-danger">*</span></label>
                    <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required
                           class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                           placeholder="Contoh: Dinas Kesehatan Provinsi Gorontalo">
                    @error('nama')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Kepala OPD --}}
                <div>
                    <label for="kepala_opd" class="mb-1.5 block text-sm font-medium text-gray-700">Nama Kepala OPD</label>
                    <input type="text" id="kepala_opd" name="kepala_opd" value="{{ old('kepala_opd') }}"
                           class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                           placeholder="Nama Kepala Dinas / Badan">
                    @error('kepala_opd')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- NIP Kepala --}}
                <div>
                    <label for="nip_kepala" class="mb-1.5 block text-sm font-medium text-gray-700">NIP Kepala OPD</label>
                    <input type="text" id="nip_kepala" name="nip_kepala" value="{{ old('nip_kepala') }}"
                           class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                           placeholder="19800101 200501 1 001">
                    @error('nip_kepala')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Telepon --}}
                <div>
                    <label for="telepon" class="mb-1.5 block text-sm font-medium text-gray-700">Nomor Telepon / Kontak</label>
                    <input type="text" id="telepon" name="telepon" value="{{ old('telepon') }}"
                           class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                           placeholder="0435-xxxxxx">
                    @error('telepon')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">Alamat Email Resmi</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                           placeholder="dinkes@gorontaloprov.go.id">
                    @error('email')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Alamat --}}
                <div class="md:col-span-2">
                    <label for="alamat" class="mb-1.5 block text-sm font-medium text-gray-700">Alamat Kantor</label>
                    <textarea id="alamat" name="alamat" rows="3"
                              class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                              placeholder="Jl. Sapta Marga No. ... Kota Gorontalo">{{ old('alamat') }}</textarea>
                    @error('alamat')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Status Aktif --}}
                <div class="md:col-span-2 flex items-center gap-2">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <label for="is_active" class="text-sm font-medium text-gray-700">Status OPD Aktif</label>
                </div>
            </div>

            {{-- Buttons --}}
            <div class="flex flex-col-reverse items-stretch gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-end">
                <x-action-button href="{{ route('opd.index') }}">Batal</x-action-button>
                <x-action-button type="submit" variant="primary">
                    Simpan OPD
                </x-action-button>
            </div>
        </form>
    </x-form-card>
</div>
@endsection
