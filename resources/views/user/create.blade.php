@extends('layouts.app')

@section('title', 'Tambah Pengguna')
@section('page-title', 'Tambah Pengguna Baru')
@section('page-subtitle', 'Buat akun pengguna baru dan tentukan peran serta tautan master datanya')

@section('content')
<div class="max-w-3xl space-y-4">
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Manajemen Pengguna', 'href' => route('user.index')],
        ['label' => 'Tambah Pengguna'],
    ]" />
    <x-form-card>
        <form method="POST" action="{{ route('user.store') }}" class="space-y-6">
            @csrf

            <div class="rounded-lg border border-violet-200 bg-violet-50 p-3 text-sm text-violet-900" role="status">
                Akun baru akan berstatus <strong>Menunggu Aktivasi</strong>. Setelah disimpan, sistem memproses email aktivasi agar pengguna menetapkan kata sandinya sendiri.
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                {{-- Nama Pengguna --}}
                <div class="md:col-span-2">
                    <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700">Nama Akun Pengguna <span class="text-state-danger">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                           class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 transition idds-form-control"
                           placeholder="Contoh: Admin BPK - Ahmad, S.E. atau Dinas Kesehatan Prov. Gorontalo">
                    <p id="identity-master-hint" class="mt-1 hidden text-xs text-gray-500">
                        Nama dan email mengikuti data pegawai yang dipilih dari Master Pegawai.
                    </p>
                    @error('name')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">Alamat Email <span class="text-state-danger">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                           class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control"
                           placeholder="admin.bpk@inspektorat.go.id">
                    <p class="mt-1 text-xs text-gray-500">Untuk akun yang ditautkan ke pegawai, email diambil dari Master Pegawai.</p>
                    @error('email')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Role --}}
                <div class="md:col-span-2">
                    <label for="role_id" class="mb-1.5 block text-sm font-medium text-gray-700">Peran / Hak Akses (Role) <span class="text-state-danger">*</span></label>
                    <select id="role_id" name="role_id" required onchange="handleRoleChange(this.value, true)"
                            class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control">
                        <option value="">-- Pilih Peran / Role --</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}" data-name="{{ $r->name }}" {{ old('role_id') == $r->id ? 'selected' : '' }}>
                                {{ $r->display_name }} ({{ $r->description }})
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Link Pegawai (for Admin/Pegawai) --}}
                <div id="pegawai-select-group" class="md:col-span-2">
                    <label for="pegawai_id" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Tautkan ke Master Pegawai Inspektorat <span id="pegawai-required-indicator" class="hidden text-state-danger">*</span>
                    </label>
                    <select id="pegawai_id" name="pegawai_id" onchange="handlePegawaiChange(this)"
                            class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control">
                        <option value="">-- Pilih Pegawai jika diperlukan --</option>
                        @foreach($pegawais as $p)
                            <option value="{{ $p->id }}" data-nama="{{ $p->nama }}" data-email="{{ $p->email ?? '' }}" {{ old('pegawai_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->nama }} — {{ $p->jabatan }} ({{ $p->unit_bidang }})
                            </option>
                        @endforeach
                    </select>
                    <p id="pegawai-link-hint" class="mt-1 text-xs text-gray-500">Wajib untuk Super Admin dan Admin Inspektorat. Pegawai yang sudah memiliki akun tidak ditampilkan.</p>
                    @error('pegawai_id')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Link OPD (for OPD accounts) --}}
                <div id="opd-select-group" class="md:col-span-2 hidden">
                    <label for="opd_id" class="mb-1.5 block text-sm font-medium text-gray-700">Pilih Instansi OPD Terkait <span class="text-state-danger">*</span></label>
                    <select id="opd_id" name="opd_id"
                            class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control">
                        <option value="">-- Pilih OPD --</option>
                        @foreach($opds as $o)
                            <option value="{{ $o->id }}" {{ old('opd_id') == $o->id ? 'selected' : '' }}>
                                [{{ $o->kode }}] {{ $o->nama }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Akun OPD hanya dapat melihat temuan milik instansi yang dipilih.</p>
                    @error('opd_id')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            {{-- Buttons --}}
            <div class="flex flex-col-reverse items-stretch gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-end">
                <x-action-button href="{{ route('user.index') }}">Batal</x-action-button>
                <x-action-button type="submit" variant="primary">
                    Buat Akun & Kirim Aktivasi
                </x-action-button>
            </div>
        </form>
    </x-form-card>
</div>


@endsection
