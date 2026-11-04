{{-- Step 1: SPT & Tim Pemeriksa --}}
<div class="wizard-step" id="wizard-step-1" data-step="1">
    <div class="rounded-3xl border border-gray-200/80 bg-white p-6 md:p-8 shadow-sm">
        {{-- Header --}}
        <div class="flex items-center gap-3 border-b border-gray-100 pb-4 mb-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-800 text-white font-bold text-lg shadow-sm">
                📋
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900 tracking-tight">Surat Perintah Tugas (SPT)</h2>
                <p class="text-xs text-gray-500 font-medium">Input data penugasan dan tim pemeriksa</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            {{-- Judul SPT / Nama Kegiatan --}}
            <div class="md:col-span-2">
                <label for="judul" class="mb-1.5 block text-sm font-semibold text-gray-800">
                    Judul / Nama Kegiatan Pemeriksaan <span class="text-state-danger">*</span>
                </label>
                <input type="text" id="judul" name="judul" value="{{ old('judul', $draft['judul'] ?? '') }}" required
                       class="w-full border bg-white px-4 py-3 text-sm text-gray-800 transition shadow-sm idds-form-control"
                       placeholder="Contoh: Pemeriksaan Kepatuhan atas Pengelolaan Keuangan dan BMD TA 2025"
                       data-wizard-field>
                @error('judul')
                    <p class="mt-1 text-xs text-state-danger font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- No. SPT --}}
            <div>
                <label for="nomor_spt" class="mb-1.5 block text-sm font-semibold text-gray-800">
                    No. SPT <span class="text-state-danger">*</span>
                </label>
                <input type="text" id="nomor_spt" name="nomor_spt" value="{{ old('nomor_spt', $draft['nomor_spt'] ?? '') }}" required
                       class="w-full border bg-white px-4 py-3 text-sm text-gray-800 transition shadow-sm idds-form-control"
                       placeholder="Contoh: 700/ITPROV/042/SPT/2026"
                       data-wizard-field>
                @error('nomor_spt')
                    <p class="mt-1 text-xs text-state-danger font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tanggal SPT --}}
            <div>
                <label for="tanggal_spt" class="mb-1.5 block text-sm font-semibold text-gray-800">
                    Tanggal SPT <span class="text-state-danger">*</span>
                </label>
                <input type="date" id="tanggal_spt" name="tanggal_spt" value="{{ old('tanggal_spt', $draft['tanggal_spt'] ?? date('Y-m-d')) }}" required
                       class="w-full border bg-white px-4 py-3 text-sm text-gray-800 transition shadow-sm idds-form-control"
                       data-wizard-field>
                @error('tanggal_spt')
                    <p class="mt-1 text-xs text-state-danger font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- Sumber Pemeriksaan --}}
            <div>
                <label for="sumber_pemeriksaan" class="mb-1.5 block text-sm font-semibold text-gray-800">
                    Sumber Pemeriksaan <span class="text-state-danger">*</span>
                </label>
                @if($lockedSumber)
                    <input type="hidden" name="sumber_pemeriksaan" value="{{ $lockedSumber }}">
                    <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-bold text-primary-900">
                        <span class="inline-flex items-center rounded-md bg-primary-100 px-2 py-0.5 text-xs text-primary-800">Terkunci</span>
                        {{ $lockedSumber }}
                    </div>
                @else
                    <select id="sumber_pemeriksaan" name="sumber_pemeriksaan" required
                            class="w-full border bg-white px-4 py-3 text-sm text-gray-800 transition shadow-sm idds-form-control"
                            data-wizard-field>
                        <option value="">-- Pilih Sumber --</option>
                        @foreach(['BPK', 'BPKP', 'ITJEN', 'Inspektorat'] as $s)
                            <option value="{{ $s }}" {{ old('sumber_pemeriksaan', $draft['sumber_pemeriksaan'] ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                @endif
                @error('sumber_pemeriksaan')
                    <p class="mt-1 text-xs text-state-danger font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tahun --}}
            <div>
                <label for="tahun" class="mb-1.5 block text-sm font-semibold text-gray-800">
                    Tahun Pemeriksaan <span class="text-state-danger">*</span>
                </label>
                <select id="tahun" name="tahun" required
                        class="w-full border bg-white px-4 py-3 text-sm text-gray-800 transition shadow-sm idds-form-control"
                        data-wizard-field>
                    @for($y = date('Y'); $y >= 2015; $y--)
                        <option value="{{ $y }}" {{ old('tahun', $draft['tahun'] ?? date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                @error('tahun')
                    <p class="mt-1 text-xs text-state-danger font-medium">{{ $message }}</p>
                @enderror
            </div>

        </div>

        {{-- Tim Pemeriksa Section --}}
        <div class="mt-8 rounded-2xl border border-primary-200/60 bg-primary-50/30 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-gray-900  tracking-wider">👥 Tim Pemeriksa</h3>
                <button type="button" onclick="addTimPemeriksa()" 
                        class="inline-flex items-center gap-1.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white px-3 py-2 text-xs font-bold shadow-sm transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Anggota Tim
                </button>
            </div>

            <div id="tim-pemeriksa-container" class="space-y-3">
                {{-- Template will be added here via JavaScript --}}
                @if(old('tim_pemeriksa') || isset($draft['tim_pemeriksa']))
                    @foreach(old('tim_pemeriksa', $draft['tim_pemeriksa'] ?? []) as $index => $tim)
                        <div class="tim-item bg-white rounded-xl border border-gray-200 p-4 shadow-sm" data-index="{{ $index }}">
                            <div class="flex items-start gap-3">
                                <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Pegawai <span class="text-state-danger">*</span></label>
                                        <select name="tim_pemeriksa[{{ $index }}][pegawai_id]" required class="w-full border px-3 py-2 text-sm idds-form-control" data-wizard-field>
                                            <option value="">-- Pilih Pegawai --</option>
                                            @foreach($pegawais as $p)
                                                <option value="{{ $p->id }}" {{ ($tim['pegawai_id'] ?? '') == $p->id ? 'selected' : '' }}>
                                                    {{ $p->official_name }} - {{ $p->pangkat_golongan ?? 'N/A' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Peran <span class="text-state-danger">*</span></label>
                                        <select name="tim_pemeriksa[{{ $index }}][peran_tim]" required class="w-full border px-3 py-2 text-sm idds-form-control" data-wizard-field>
                                            <option value="">-- Pilih Peran --</option>
                                            @foreach(['Penanggung Jawab', 'Asisten Penanggung Jawab', 'Pengendali Teknis', 'Ketua Tim', 'Anggota Tim'] as $peran)
                                                <option value="{{ $peran }}" {{ ($tim['peran_tim'] ?? '') == $peran ? 'selected' : '' }}>{{ $peran }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <button type="button" onclick="removeTimPemeriksa(this)" class="text-state-danger hover:text-red-700 text-xs font-bold mt-6 cursor-pointer">Hapus</button>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <p class="text-xs text-gray-500 mt-3 italic">Minimal harus ada 1 anggota tim pemeriksa</p>
        </div>
    </div>

    {{-- Navigation Buttons --}}
    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('pemeriksaan.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Batal
        </a>
        <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
            <button type="button" onclick="saveDraft()" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-50 transition cursor-pointer">
                💾 Simpan Draft
            </button>
            <button type="button" onclick="nextStep()" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 hover:bg-primary-700 text-white px-6 py-2.5 text-sm font-bold shadow-sm transition cursor-pointer">
                Lanjut ke LHP
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<script>
// Tim Pemeriksa Management
let timIndex = {{ count(old('tim_pemeriksa', $draft['tim_pemeriksa'] ?? [])) }};

function addTimPemeriksa() {
    const container = document.getElementById('tim-pemeriksa-container');
    const template = `
        <div class="tim-item bg-white rounded-xl border border-gray-200 p-4 shadow-sm" data-index="${timIndex}">
            <div class="flex items-start gap-3">
                <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Pegawai <span class="text-state-danger">*</span></label>
                        <select name="tim_pemeriksa[${timIndex}][pegawai_id]" required class="w-full border px-3 py-2 text-sm idds-form-control" data-wizard-field>
                            <option value="">-- Pilih Pegawai --</option>
                            @foreach($pegawais as $p)
                                <option value="{{ $p->id }}">{{ $p->official_name }} - {{ $p->pangkat_golongan ?? 'N/A' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Peran <span class="text-state-danger">*</span></label>
                        <select name="tim_pemeriksa[${timIndex}][peran_tim]" required class="w-full border px-3 py-2 text-sm idds-form-control" data-wizard-field>
                            <option value="">-- Pilih Peran --</option>
                            <option value="Penanggung Jawab">Penanggung Jawab</option>
                            <option value="Asisten Penanggung Jawab">Asisten Penanggung Jawab</option>
                            <option value="Ketua Tim">Ketua Tim</option>
                            <option value="Anggota Tim">Anggota Tim</option>
                            <option value="Pengendali Teknis">Pengendali Teknis</option>
                        </select>
                    </div>
                </div>
                <button type="button" onclick="removeTimPemeriksa(this)" class="text-state-danger hover:text-red-700 text-xs font-bold mt-6 cursor-pointer">Hapus</button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', template);
    timIndex++;
}

function removeTimPemeriksa(btn) {
    const container = document.getElementById('tim-pemeriksa-container');
    if (container.querySelectorAll('.tim-item').length <= 1) {
        window.showAppNotice('Minimal harus ada 1 anggota tim pemeriksa.', { title: 'Susunan tim belum lengkap', variant: 'warning' });
        return;
    }
    
    window.showAppConfirm('Hapus anggota tim ini?', {
        title: 'Hapus anggota tim?',
        variant: 'danger',
        acceptLabel: 'Ya, Hapus',
    }).then((confirmed) => {
        if (confirmed) btn.closest('.tim-item')?.remove();
    });
}

// Tampilkan 1 baris tim otomatis agar form tidak terasa kosong
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('tim-pemeriksa-container');
    if (container && container.querySelectorAll('.tim-item').length === 0) {
        addTimPemeriksa();
    }
});
</script>
