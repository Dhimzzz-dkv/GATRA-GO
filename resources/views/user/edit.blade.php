@extends('layouts.app')

@section('title', 'Edit Pengguna')
@section('page-title', 'Edit Data Pengguna')
@section('page-subtitle', 'Perbarui akun pengguna: ' . $user->name)

@section('content')
<div class="max-w-3xl space-y-4">
    <x-breadcrumbs :items="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Manajemen Pengguna', 'href' => route('user.index')],
        ['label' => 'Edit: ' . $user->name],
    ]" />
    <x-form-card>
        <form method="POST" action="{{ route('user.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @if($linkedAccountCount > 1)
                <div role="alert" class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
                    Master Pegawai ini saat ini terhubung ke {{ $linkedAccountCount }} akun aktif. Akun ini tetap dapat diperbarui, tetapi tautan ganda perlu ditinjau dan disisakan satu akun yang benar.
                </div>
            @endif

            @if($user->activation_required && ! $user->activated_at)
                <div role="status" class="rounded-lg border border-violet-200 bg-violet-50 p-3 text-sm text-violet-900">
                    Akun ini masih menunggu aktivasi. Pengguna harus menetapkan kata sandinya melalui tautan email sebelum akun dapat diaktifkan.
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                {{-- Nama Pengguna --}}
                <div class="md:col-span-2">
                    <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700">Nama Akun Pengguna <span class="text-state-danger">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 transition idds-form-control">
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
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control">
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
                            <option value="{{ $r->id }}" data-name="{{ $r->name }}" {{ old('role_id', $user->role_id) == $r->id ? 'selected' : '' }}>
                                {{ $r->display_name }} ({{ $r->description }})
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Link Pegawai (for Admin/Pegawai) --}}
                <div id="pegawai-select-group" class="md:col-span-2 {{ $user->role && $user->role->isOpd() ? 'hidden' : '' }}">
                    <label for="pegawai_id" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Tautkan ke Master Pegawai Inspektorat <span id="pegawai-required-indicator" class="hidden text-state-danger">*</span>
                    </label>
                    <select id="pegawai_id" name="pegawai_id" onchange="handlePegawaiChange(this)"
                            class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control">
                        <option value="">-- Pilih Pegawai jika diperlukan --</option>
                        @foreach($pegawais as $p)
                            <option value="{{ $p->id }}" data-nama="{{ $p->nama }}" data-email="{{ $p->email ?? '' }}" {{ old('pegawai_id', $user->pegawai_id) == $p->id ? 'selected' : '' }}>
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
                <div id="opd-select-group" class="md:col-span-2 {{ $user->role && $user->role->isOpd() ? '' : 'hidden' }}">
                    <label for="opd_id" class="mb-1.5 block text-sm font-medium text-gray-700">Pilih Instansi OPD Terkait <span class="text-state-danger">*</span></label>
                    <select id="opd_id" name="opd_id"
                            class="w-full border bg-white px-4 py-2.5 text-sm text-gray-800 idds-form-control">
                        <option value="">-- Pilih OPD --</option>
                        @foreach($opds as $o)
                            <option value="{{ $o->id }}" {{ old('opd_id', $user->opd_id) == $o->id ? 'selected' : '' }}>
                                [{{ $o->kode }}] {{ $o->nama }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Akun OPD hanya dapat melihat temuan milik instansi yang dipilih.</p>
                    @error('opd_id')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                @unless($user->activation_required && ! $user->activated_at)
                    {{-- Status Aktif --}}
                    <div class="md:col-span-2 flex items-center gap-2">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                               class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <label for="is_active" class="text-sm font-medium text-gray-700">Akun Pengguna Aktif</label>
                    </div>
                @endunless
            </div>

            {{-- Buttons --}}
            <div class="flex flex-col-reverse items-stretch gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-end">
                <x-action-button href="{{ route('user.index') }}">Batal</x-action-button>
                <x-action-button type="submit" variant="primary">
                    Perbarui Pengguna
                </x-action-button>
            </div>
        </form>

        <section class="mt-6 border-t border-gray-100 pt-5" aria-labelledby="password-reset-title">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="max-w-xl">
                    <h2 id="password-reset-title" class="text-sm font-semibold text-gray-900">Pengaturan Kata Sandi</h2>
                    @if($user->activation_required && ! $user->activated_at)
                        <p class="mt-1 text-sm text-gray-600">Akun ini belum diaktivasi. Gunakan tautan aktivasi untuk menetapkan kata sandi pertama.</p>
                    @elseif(! $user->is_active)
                        <p class="mt-1 text-sm text-gray-600">Aktifkan akun terlebih dahulu sebelum mengirim tautan reset sandi.</p>
                    @else
                        <p class="mt-1 text-sm text-gray-600">Kirim tautan reset ke <span class="font-medium text-gray-800">{{ $user->email }}</span>. Pengguna mengatur sendiri sandi barunya melalui tautan email.</p>
                    @endif
                </div>

                @if($user->is_active && ! $user->activation_required)
                    <form method="POST" action="{{ route('user.send-password-reset', $user) }}" class="shrink-0">
                        @csrf
                        <x-action-button type="submit" variant="secondary">
                            Kirim Tautan Reset Sandi
                        </x-action-button>
                    </form>
                @endif
            </div>
        </section>
    </x-form-card>
</div>


@endsection
