{{-- Step 2: Data LHP --}}
<div class="wizard-step hidden" id="wizard-step-2" data-step="2">
    <div class="rounded-3xl border border-gray-200/80 bg-white p-6 md:p-8 shadow-sm">
        {{-- Header --}}
        <div class="flex items-center gap-3 border-b border-gray-100 pb-4 mb-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-800 text-white font-bold text-lg shadow-sm">
                📄
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900 tracking-tight">Laporan Hasil Pemeriksaan (LHP)</h2>
                <p class="text-xs text-gray-500 font-medium">Input data LHP, OPD target, dan ringkasan hasil pemeriksaan</p>
            </div>
        </div>

        <div id="lhp-cards-container" class="space-y-6">
            {{-- LHP cards will be added here dynamically --}}
            @if(old('lhps') || isset($draft['lhps']))
                @foreach(old('lhps', $draft['lhps'] ?? []) as $lhpIndex => $lhp)
                    <div class="lhp-card rounded-2xl border-2 border-primary-200 bg-primary-50/30 p-5" data-lhp-index="{{ $lhpIndex }}">
<div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-primary-900 flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-600 text-white text-xs">{{ $lhpIndex + 1 }}</span>
                        <span class="lhp-title-label">LHP #{{ $lhpIndex + 1 }}</span>
                    </h3>
                    <button type="button" onclick="removeLhpCard(this)" class="remove-lhp-btn text-xs font-bold text-state-danger hover:text-red-700 hover:underline cursor-pointer">
                        Hapus LHP Ini
                    </button>
                </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Nomor LHP --}}
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-gray-700">Nomor LHP <span class="text-state-danger">*</span></label>
                                <input type="text" name="lhps[{{ $lhpIndex }}][nomor_lhp]" value="{{ $lhp['nomor_lhp'] ?? '' }}" required
                                       class="w-full border bg-white px-3 py-2 text-sm idds-form-control"
                                       placeholder="Contoh: 01/LHP/ITPROV/2026"
                                       data-wizard-field>
                            </div>

                            {{-- Tanggal LHP --}}
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-gray-700">Tanggal LHP <span class="text-state-danger">*</span></label>
                                <input type="date" name="lhps[{{ $lhpIndex }}][tanggal_lhp]" value="{{ $lhp['tanggal_lhp'] ?? date('Y-m-d') }}" required
                                       class="w-full border bg-white px-3 py-2 text-sm idds-form-control"
                                       data-wizard-field>
                            </div>

                            {{-- Judul LHP --}}
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-semibold text-gray-700">Judul LHP</label>
                                <input type="text" name="lhps[{{ $lhpIndex }}][judul_lhp]" value="{{ $lhp['judul_lhp'] ?? '' }}"
                                       class="w-full border bg-white px-3 py-2 text-sm idds-form-control"
                                       placeholder="Opsional - biarkan kosong jika sama dengan judul SPT"
                                       data-wizard-field>
                            </div>

                            {{-- OPD Target (Multi-select dengan Checkbox) --}}
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-bold text-gray-700 tracking-wider">
                                    OPD Target Pemeriksaan <span class="text-state-danger">*</span>
                                </label>
                                <p class="text-xs text-gray-500 mb-2">Centang minimal 1 OPD yang menjadi objek pemeriksaan pada LHP ini</p>

                                {{-- Search Box --}}
                                <div class="relative mb-2">
                                    <input type="text" oninput="filterOpdOptions(this)" 
                                           class="opd-search-input w-full border bg-white pl-9 pr-4 py-2 text-xs idds-form-control" 
                                           placeholder="Cari OPD...">
                                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>

                                {{-- OPD Checkboxes --}}
                                <div class="opd-checkbox-list max-h-48 overflow-y-auto space-y-1 rounded-lg border border-gray-200 bg-white p-2">
                                    @foreach($opds as $o)
                                        <label class="opd-item flex items-center gap-2 p-1.5 rounded hover:bg-primary-50 cursor-pointer text-xs" 
                                               data-search="{{ strtolower($o->kode . ' ' . $o->nama) }}">
                                            <input type="checkbox" name="lhps[{{ $lhpIndex }}][opd_ids][]" value="{{ $o->id }}"
                                                   {{ in_array($o->id, $lhp['opd_ids'] ?? []) ? 'checked' : '' }}
                                                   class="opd-checkbox h-4 w-4 rounded border-gray-300 text-primary-600"
                                                   data-wizard-field>
                                            <span class="font-bold text-primary-900">[{{ $o->kode }}]</span>
                                            <span class="text-gray-700">{{ $o->nama }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                {{-- Selected OPD Badges --}}
                                <div class="mt-2 selected-opd-chips flex flex-wrap gap-1.5 min-h-[32px] items-center p-2 rounded-lg bg-white border border-dashed border-gray-200">
                                    <span class="empty-opd-placeholder text-xs text-gray-500 italic">Belum ada OPD dipilih</span>
                                </div>
                            </div>

                            {{-- Ringkasan --}}
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-semibold text-gray-700">Uraian Singkat / Ringkasan Hasil Pemeriksaan</label>
                                <textarea name="lhps[{{ $lhpIndex }}][ringkasan]" rows="2"
                                          class="w-full border bg-white px-3 py-2 text-sm idds-form-control"
                                          placeholder="Tuliskan ringkasan pokok hasil pemeriksaan..."
                                          data-wizard-field>{{ $lhp['ringkasan'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Add LHP Button --}}
        <button type="button" id="btn-tambah-lhp" onclick="if (typeof addLhpCard === 'function') { try { addLhpCard(); } catch (e) { console.error('Gagal menambah LHP:', e); alert('Gagal menambah LHP: ' + e.message); } }"
                class="mt-4 w-full rounded-xl border-2 border-dashed border-primary-300 bg-primary-50/50 hover:bg-primary-50 px-4 py-3 text-sm font-bold text-primary-700 transition cursor-pointer">
            + Tambah LHP Lainnya
        </button>

        <p class="text-xs text-gray-500 mt-3 italic">💡 Tip: Satu SPT bisa menghasilkan beberapa LHP jika pemeriksaan dilakukan terhadap beberapa OPD/unit</p>
    </div>

    {{-- Navigation Buttons --}}
    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
        <button type="button" onclick="prevStep()" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50 transition cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali
        </button>
        <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
            <button type="button" onclick="saveDraft()" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-50 transition cursor-pointer">
                💾 Simpan Draft
            </button>
            <button type="button" onclick="nextStep()" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 hover:bg-primary-700 text-white px-6 py-2.5 text-sm font-bold shadow-sm transition cursor-pointer">
                Lanjut ke Temuan
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<script>
function addLhpCard() {
    const container = document.getElementById('lhp-cards-container');
    if (!container) return;
    
    const idx = container.querySelectorAll('.lhp-card').length;
    const template = `
        <div class="lhp-card rounded-2xl border-2 border-primary-200 bg-primary-50/30 p-5" data-lhp-index="${idx}">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-primary-900 flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-600 text-white text-xs">${idx + 1}</span>
                    <span class="lhp-title-label">LHP #${idx + 1}</span>
                </h3>
                <button type="button" onclick="removeLhpCard(this)" class="remove-lhp-btn text-xs font-bold text-state-danger hover:text-red-700 hover:underline cursor-pointer">
                    Hapus LHP Ini
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-700">Nomor LHP <span class="text-state-danger">*</span></label>
                    <input type="text" name="lhps[${idx}][nomor_lhp]" required
                           class="w-full border bg-white px-3 py-2 text-sm idds-form-control"
                           placeholder="Contoh: 01/LHP/ITPROV/2026" data-wizard-field>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-700">Tanggal LHP <span class="text-state-danger">*</span></label>
                    <input type="date" name="lhps[${idx}][tanggal_lhp]" value="${new Date().toISOString().split('T')[0]}" required
                           class="w-full border bg-white px-3 py-2 text-sm idds-form-control" data-wizard-field>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-gray-700">Judul LHP</label>
                    <input type="text" name="lhps[${idx}][judul_lhp]"
                           class="w-full border bg-white px-3 py-2 text-sm idds-form-control"
                           placeholder="Opsional - biarkan kosong jika sama dengan judul SPT" data-wizard-field>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-gray-700 tracking-wider">
                        OPD Target Pemeriksaan <span class="text-state-danger">*</span>
                    </label>
                    <p class="text-xs text-gray-500 mb-2">Centang minimal 1 OPD yang menjadi objek pemeriksaan pada LHP ini</p>

                    <div class="relative mb-2">
                        <input type="text" oninput="filterOpdOptions(this)" 
                               class="opd-search-input w-full border bg-white pl-9 pr-4 py-2 text-xs idds-form-control" 
                               placeholder="Cari OPD...">
                        <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <div class="opd-checkbox-list max-h-48 overflow-y-auto space-y-1 rounded-lg border border-gray-200 bg-white p-2">
                        @foreach($opds as $o)
                            <label class="opd-item flex items-center gap-2 p-1.5 rounded hover:bg-primary-50 cursor-pointer text-xs" 
                                   data-search="{{ strtolower($o->kode . ' ' . $o->nama) }}">
                                <input type="checkbox" name="lhps[${idx}][opd_ids][]" value="{{ $o->id }}"
                                       class="opd-checkbox h-4 w-4 rounded border-gray-300 text-primary-600" data-wizard-field>
                                <span class="font-bold text-primary-900">[{{ $o->kode }}]</span>
                                <span class="text-gray-700">{{ $o->nama }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-2 selected-opd-chips flex flex-wrap gap-1.5 min-h-[32px] items-center p-2 rounded-lg bg-white border border-dashed border-gray-200">
                        <span class="empty-opd-placeholder text-xs text-gray-500 italic">Belum ada OPD dipilih</span>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-gray-700">Uraian Singkat / Ringkasan Hasil Pemeriksaan</label>
                    <textarea name="lhps[${idx}][ringkasan]" rows="2"
                              class="w-full border bg-white px-3 py-2 text-sm idds-form-control"
                              placeholder="Tuliskan ringkasan pokok hasil pemeriksaan..." data-wizard-field></textarea>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', template);
    updateOpdSelections();
    document.dispatchEvent(new CustomEvent('wizardLhpChanged'));
}

function removeLhpCard(btn) {
    const container = document.getElementById('lhp-cards-container');
    if (!container) return;
    
    if (container.querySelectorAll('.lhp-card').length <= 1) {
        alert('Minimal harus ada 1 LHP. Tambahkan LHP baru bila ingin menambah.');
        return;
    }
    
    if (confirm('Hapus LHP ini?')) {
        btn.closest('.lhp-card').remove();
        updateLhpNumbers();
        updateOpdSelections();
        document.dispatchEvent(new CustomEvent('wizardLhpChanged'));
    }
}

function updateLhpNumbers() {
    const cards = document.querySelectorAll('#wizard-step-2 .lhp-card');
    cards.forEach((card, idx) => {
        card.dataset.lhpIndex = idx;
        
        // Renumber input name agar sesuai urutan terbaru
        card.querySelectorAll('input[name], select[name], textarea[name]').forEach(field => {
            field.name = field.name.replace(/^lhps\[\d+\]/, `lhps[${idx}]`);
        });
        
        const heading = card.querySelector('h3');
        if (heading) {
            const badge = heading.querySelector('span');
            const label = heading.querySelector('.lhp-title-label');
            if (badge) badge.textContent = idx + 1;
            if (label) label.textContent = `LHP #${idx + 1}`;
        }
    });
}

function updateOpdSelections() {
    const cards = document.querySelectorAll('.lhp-card');
    cards.forEach(card => {
        const chipsContainer = card.querySelector('.selected-opd-chips');
        const checkboxes = card.querySelectorAll('.opd-checkbox:checked');
        const placeholder = card.querySelector('.empty-opd-placeholder');
        
        if (checkboxes.length > 0) {
            if (placeholder) placeholder.style.display = 'none';
            chipsContainer.replaceChildren();
            checkboxes.forEach(cb => {
                const label = cb.closest('.opd-item');
                const code = label.querySelector('.text-primary-900').textContent;
                const name = label.querySelector('.text-gray-700').textContent;
                const chip = document.createElement('span');
                chip.className = 'inline-flex items-center gap-1 rounded-full bg-primary-100 border border-primary-200 px-2 py-0.5 text-xs font-semibold text-primary-800';
                chip.textContent = `${code} ${name}`;
                chipsContainer.appendChild(chip);
            });
        } else {
            const emptyPlaceholder = document.createElement('span');
            emptyPlaceholder.className = 'empty-opd-placeholder text-xs text-gray-500 italic';
            emptyPlaceholder.textContent = 'Belum ada OPD dipilih';
            chipsContainer.replaceChildren(emptyPlaceholder);
        }
    });
}

// Initialize OPD selections on load
document.addEventListener('DOMContentLoaded', function() {
    // Tampilkan 1 kartu LHP otomatis agar form tidak terasa kosong
    const container = document.getElementById('lhp-cards-container');
    if (container && container.querySelectorAll('.lhp-card').length === 0) {
        addLhpCard();
    }
    
    updateOpdSelections();
    
    // Listen to checkbox changes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('opd-checkbox')) {
            updateOpdSelections();
        }
    });
});
</script>
