{{-- Step 3: Input Temuan --}}
<div class="wizard-step hidden" id="wizard-step-3" data-step="3">
    <div class="rounded-3xl border border-gray-200/80 bg-white p-6 md:p-8 shadow-sm">
        {{-- Header --}}
        <div class="flex items-center gap-3 border-b border-gray-100 pb-4 mb-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-700 text-white font-bold text-lg shadow-sm">
                🔍
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900 tracking-tight">Temuan Pemeriksaan</h2>
                <p class="text-xs text-gray-500 font-medium">Input temuan administrasi dan/atau kerugian daerah dengan rekomendasi</p>
            </div>
        </div>

        {{-- Info: Pilih LHP terlebih dahulu --}}
        <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h4 class="text-sm font-bold text-blue-900 mb-1">Tambahkan Temuan untuk Setiap LHP</h4>
                    <p class="text-xs text-blue-800">Tambahkan temuan administrasi dan/atau kerugian daerah langsung pada kartu LHP yang sesuai. Anda bisa melewatkan langkah ini jika belum ada temuan (dapat ditambahkan nanti).</p>
                </div>
            </div>
        </div>

        {{-- LHP Selection & Temuan Input --}}
        <div id="lhp-temuan-container" class="space-y-6">
            {{-- This will be populated dynamically based on Step 2 LHP data --}}
            <div class="text-center py-10 text-gray-500 text-sm" id="no-lhp-message">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="font-medium">Belum ada LHP yang ditambahkan</p>
                <p class="text-xs mt-1">Kembali ke Step 2 untuk menambahkan LHP terlebih dahulu</p>
            </div>
        </div>

        {{-- Summary Stats --}}
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-primary-200 bg-primary-50 p-4 text-center">
                <div class="text-2xl font-bold text-primary-900" id="total-temuan-count">0</div>
                <div class="text-xs font-semibold text-primary-700 mt-1">Total Temuan</div>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-center">
                <div class="text-lg font-bold text-amber-900" id="total-kerugian-amount">Rp 0</div>
                <div class="text-xs font-semibold text-amber-700 mt-1">Total Kerugian Daerah</div>
            </div>
        </div>

        <p class="text-xs text-gray-500 mt-4 italic">💡 Tip: Temuan bersifat opsional pada tahap ini. Anda bisa menambahkan temuan nanti setelah pemeriksaan tersimpan.</p>
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
                Lanjut ke Review
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<script>
function formatWizardRupiah(input) {
    const raw = input.value.replace(/\D/g, '').slice(0, 15);
    input.value = raw ? formatRupiahNumber(parseInt(raw, 10)) : '';
}

let renderedLhpSignature = null;
function populateTemuanFromLhp() {
    const container = document.getElementById('lhp-temuan-container');
    const noLhpMessage = document.getElementById('no-lhp-message');
    
    // Get all LHP cards from Step 2
    const lhpCards = document.querySelectorAll('#wizard-step-2 .lhp-card');
    
    if (lhpCards.length === 0) {
        if (noLhpMessage) noLhpMessage.style.display = 'block';
        return;
    }

    // Signature LHP saat ini (agar tidak mereset input temuan yg sudah disimpan)
    const signature = Array.from(lhpCards).map((c, i) => {
        const nomor = c.querySelector('input[name*="[nomor_lhp]"]')?.value || `LHP-${i}`;
        return `${i}:${nomor}`;
    }).join('|');
    
    const renderedCards = Array.from(container.querySelectorAll('.lhp-temuan-card'));
    const cardsMatchCurrentLhps = renderedCards.length === lhpCards.length
        && renderedCards.every((card, index) => card.dataset.lhpIndex === String(index));

    if (signature === renderedLhpSignature && cardsMatchCurrentLhps) {
        // Struktur LHP tidak berubah — cukup pastikan container tampil & render ulang daftar temuan
        noLhpMessage.style.display = 'none';
        container.classList.remove('hidden');
        renderAllSavedTemuan();
        return;
    }
    
    // Struktur berubah (LHP ditambah/dihapus) — bangun ulang dan pindahkan temuan
    remapSavedTemuans(lhpCards);
    
    renderedLhpSignature = signature;
    noLhpMessage.style.display = 'none';
    container.innerHTML = '';
    
    lhpCards.forEach((lhpCard, index) => {
        const nomorLhp = lhpCard.querySelector('input[name*="[nomor_lhp]"]')?.value || `LHP #${index + 1}`;
        const judulLhp = lhpCard.querySelector('input[name*="[judul_lhp]"]')?.value || '';
        
        const temuanCard = `
            <div class="lhp-temuan-card rounded-2xl border-2 border-amber-200 bg-amber-50/30 p-5" data-lhp-index="${index}">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-amber-900 flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-600 text-white text-xs">${index + 1}</span>
                            ${escapeHtml(nomorLhp)}
                        </h3>
                        ${judulLhp ? `<p class="text-xs text-gray-600 mt-1">${escapeHtml(judulLhp)}</p>` : ''}
                    </div>
                    <button type="button" class="temuan-form-toggle inline-flex items-center gap-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white px-3 py-1.5 text-xs font-bold transition cursor-pointer" aria-expanded="false" onclick="toggleTemuanForm(${index})">
                        + Tambah Temuan
                    </button>
                </div>

                <div class="temuan-list-${index} space-y-3 hidden">
                    {{-- Temuan akan ditambahkan di sini --}}
                    <div class="text-center py-6 text-gray-500 text-xs">
                        Belum ada temuan untuk LHP ini
                    </div>
                </div>

                <div class="temuan-form temuan-form-${index} hidden mt-4 rounded-xl border border-gray-200 bg-white p-4 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <h4 class="text-xs font-bold text-gray-900 ">Form Input Temuan Baru</h4>
                        <button type="button" onclick="toggleTemuanForm(${index})" class="text-xs text-gray-500 hover:text-gray-700 cursor-pointer">Tutup</button>
                    </div>

                    {{-- Pilihan Jenis Temuan --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-2">Jenis Temuan <span class="text-state-danger">*</span></label>
                        <div class="flex gap-3">
                            <label class="inline-flex items-center gap-2 cursor-pointer px-4 py-2 rounded-lg border border-gray-200 hover:bg-gray-50">
                                <input type="radio" name="temuan_type_${index}" value="administrasi" class="text-primary-600" onchange="toggleTemuanType(${index}, 'administrasi')">
                                <span class="text-xs font-semibold">📋 Administrasi</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer px-4 py-2 rounded-lg border border-gray-200 hover:bg-gray-50">
                                <input type="radio" name="temuan_type_${index}" value="kerugian" class="text-state-warning" onchange="toggleTemuanType(${index}, 'kerugian')">
                                <span class="text-xs font-semibold">💰 Kerugian Daerah</span>
                            </label>
                        </div>
                    </div>

                    {{-- Form Fields (will be shown based on type) --}}
                    <div class="temuan-fields-${index} hidden space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Nomor Temuan <span class="text-state-danger">*</span></label>
                            <input type="text" class="tm-nomor-${index} w-full border px-3 py-2 text-sm idds-form-control" placeholder="Contoh: 001/T/2026" data-wizard-field>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Judul Temuan <span class="text-state-danger">*</span></label>
                            <input type="text" class="tm-judul-${index} w-full border px-3 py-2 text-sm idds-form-control" placeholder="Contoh: Kelebihan pembayaran honorarium" data-wizard-field>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Uraian Temuan <span class="text-state-danger">*</span></label>
                            <textarea rows="2" class="tm-uraian-${index} w-full border px-3 py-2 text-sm idds-form-control" placeholder="Jelaskan detail temuan..." data-wizard-field></textarea>
                        </div>
                        
                        {{-- Kerugian-specific fields --}}
                        <div class="kerugian-fields-${index} hidden space-y-3">
                            <div class="rounded-xl border border-amber-200 bg-amber-50/30 p-4 space-y-3">
                                <h5 class="text-xs font-bold text-amber-900  tracking-wider">💰 Detail Kerugian Daerah/Negara</h5>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    {{-- Kategori Kerugian --}}
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Kategori / Sumber Kerugian <span class="text-state-danger">*</span></label>
                                        <select class="kerugian-kategori-${index} w-full border bg-white px-3 py-2 text-xs font-semibold idds-form-control" data-wizard-field>
                                            <option value="Kerugian Daerah">Kerugian Daerah (Kasda / APBD)</option>
                                            <option value="Kerugian Negara">Kerugian Negara (Kas Negara / APBN)</option>
                                        </select>
                                    </div>
                                    
                                    {{-- Jenis Kerugian --}}
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Klasifikasi Penyelesaian <span class="text-state-danger">*</span></label>
                                        <div class="flex items-center gap-4 mt-2">
                                            <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                                <input type="radio" name="jenis_kerugian_${index}" value="Internal" class="kerugian-jenis-${index} h-3.5 w-3.5 text-state-warning focus:ring-amber-500">
                                                <span>Internal (ASN/OPD)</span>
                                            </label>
                                            <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                                <input type="radio" name="jenis_kerugian_${index}" value="Eksternal" class="kerugian-jenis-${index} h-3.5 w-3.5 text-state-warning focus:ring-amber-500">
                                                <span>Eksternal (Rekanan)</span>
                                            </label>
                                        </div>
                                        <p class="text-xs text-gray-500 italic mt-1">Internal: tanggung jawab ASN. Eksternal: tanggung jawab pihak ketiga/rekanan.</p>
                                    </div>
                                </div>
                                
                                {{-- Nilai Kerugian --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Nominal Nilai Kerugian <span class="text-state-danger">*</span></label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2.5 text-sm font-bold text-gray-700">Rp</span>
                                        <input type="text" class="kerugian-nilai-${index} w-full border pl-10 pr-3 py-2 text-sm font-bold text-emerald-700 idds-form-control" placeholder="0" inputmode="numeric" oninput="formatWizardRupiah(this)" data-wizard-field>
                                    </div>
                                    <p class="text-xs text-gray-500 italic mt-1">Ketik nominal tanpa titik, sistem memformat otomatis.</p>
                                </div>
                                
                                {{-- Penyebab --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Penyebab (Sebab) <span class="text-gray-500 font-normal">(Opsional)</span></label>
                                    <textarea rows="2" class="kerugian-penyebab-${index} w-full border px-3 py-2 text-xs idds-form-control" placeholder="Contoh: Kurangnya ketelitian dalam verifikasi bukti..." data-wizard-field></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Rekomendasi <span class="text-state-danger">*</span></label>
                            <textarea rows="2" class="tm-rekomendasi-${index} w-full border px-3 py-2 text-sm idds-form-control" placeholder="Tuliskan rekomendasi perbaikan..." data-wizard-field></textarea>
                        </div>
                        <div class="flex justify-end gap-2 pt-2 border-t">
                            <button type="button" onclick="cancelTemuan(${index})" class="px-4 py-2 rounded-lg border border-gray-200 text-xs font-bold text-gray-600 hover:bg-gray-50 cursor-pointer">
                                Batal
                            </button>
                            <button type="button" onclick="saveTemuan(this)" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold cursor-pointer">
                                Simpan Temuan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', temuanCard);
    });
    
    renderAllSavedTemuan();
}

// Pindahkan data temuan ke index LHP baru (saat LHP ditambah/dihapus di Step 2)
function remapSavedTemuans(lhpCards) {
    const remapped = {};
    Object.keys(savedTemuans).forEach(oldIdx => {
        const card = lhpCards[parseInt(oldIdx)];
        if (card) {
            const newIdx = Array.from(lhpCards).indexOf(card);
            remapped[newIdx] = (remapped[newIdx] || []).concat(savedTemuans[oldIdx]);
        }
    });
    savedTemuans = remapped;
    window.savedTemuans = savedTemuans;
}

// Render ulang seluruh temuan tersimpan (beserta hidden input untuk submit)
function renderAllSavedTemuan() {
    Object.keys(savedTemuans).forEach(lhpIndex => {
        const listContainer = document.querySelector(`.temuan-list-${lhpIndex}`);
        if (!listContainer) return;
        
        listContainer.innerHTML = '';
        if (!savedTemuans[lhpIndex].length) {
            listContainer.classList.add('hidden');
            listContainer.innerHTML = '<div class="text-center py-6 text-gray-500 text-xs">Belum ada temuan untuk LHP ini</div>';
            return;
        }
        
        listContainer.classList.remove('hidden');
        savedTemuans[lhpIndex].forEach(temuan => displaySavedTemuan(lhpIndex, temuan));
    });
}

function toggleTemuanForm(lhpIndex) {
    const form = document.querySelector(`.temuan-form-${lhpIndex}`);
    if (form) {
        const isVisible = form.classList.contains('hidden');
        form.classList.toggle('hidden', !isVisible);
        const toggleButton = form.closest('.lhp-temuan-card')?.querySelector('.temuan-form-toggle');
        if (toggleButton) {
            toggleButton.textContent = isVisible ? 'Tutup Form' : '+ Tambah Temuan';
            toggleButton.setAttribute('aria-expanded', String(isVisible));
        }
    }
}

function toggleTemuanType(lhpIndex, type) {
    const fields = document.querySelector(`.temuan-fields-${lhpIndex}`);
    const kerugianFields = document.querySelector(`.kerugian-fields-${lhpIndex}`);
    
    if (fields) {
        fields.classList.remove('hidden');
    }
    
    if (type === 'kerugian' && kerugianFields) {
        kerugianFields.classList.remove('hidden');
    } else if (kerugianFields) {
        kerugianFields.classList.add('hidden');
    }
}

// Storage for temuan data (will be submitted with form)
let savedTemuans = {};
window.savedTemuans = savedTemuans;

function cancelTemuan(lhpIndex) {
    toggleTemuanForm(lhpIndex);
    // Reset form
    const form = document.querySelector(`.temuan-form-${lhpIndex}`);
    if (form) {
        form.querySelectorAll('input:not([type="radio"]), textarea').forEach(field => field.value = '');
        const radioButtons = form.querySelectorAll('input[type="radio"]');
        radioButtons.forEach(radio => radio.checked = false);
        const fields = form.querySelector(`.temuan-fields-${lhpIndex}`);
        if (fields) fields.classList.add('hidden');
        const kerugianFields = form.querySelector(`.kerugian-fields-${lhpIndex}`);
        if (kerugianFields) kerugianFields.classList.add('hidden');
    }
}

function saveTemuan(saveButton) {
    const lhpCard = saveButton.closest('.lhp-temuan-card');
    const form = lhpCard?.querySelector('.temuan-form');
    const lhpIndex = Number(lhpCard?.dataset.lhpIndex);
    if (!form || !Number.isInteger(lhpIndex)) return;
    
    // Get temuan type
    const typeRadio = form.querySelector(`input[name="temuan_type_${lhpIndex}"]:checked`);
    if (!typeRadio) {
        window.showAppNotice('Pilih jenis temuan terlebih dahulu!', { title: 'Jenis temuan belum dipilih', variant: 'warning' });
        return;
    }
    
    const temuanType = typeRadio.value;
    
    // Get common fields
    const nomorTemuan = form.querySelector(`.tm-nomor-${lhpIndex}`)?.value.trim();
    const judulTemuan = form.querySelector(`.tm-judul-${lhpIndex}`)?.value.trim();
    const uraianTemuan = form.querySelector(`.tm-uraian-${lhpIndex}`)?.value.trim();
    const rekomendasi = form.querySelector(`.tm-rekomendasi-${lhpIndex}`)?.value.trim();
    
    // Validate required fields
    if (!nomorTemuan || !judulTemuan || !uraianTemuan || !rekomendasi) {
        window.showAppNotice('Harap lengkapi semua field yang wajib diisi (Nomor, Judul, Uraian, dan Rekomendasi)!', { title: 'Data temuan belum lengkap', variant: 'warning' });
        return;
    }
    
    const temuanData = {
        type: temuanType,
        nomor_temuan: nomorTemuan,
        judul_temuan: judulTemuan,
        uraian_temuan: uraianTemuan,
        rekomendasi: rekomendasi
    };
    
    // If kerugian, get additional fields
    if (temuanType === 'kerugian') {
        const kategoriSelect = form.querySelector(`.kerugian-kategori-${lhpIndex}`);
        const jenisRadio = form.querySelector(`.kerugian-jenis-${lhpIndex}:checked`);
        const nilaiInput = form.querySelector(`.kerugian-nilai-${lhpIndex}`);
        const penyebabInput = form.querySelector(`.kerugian-penyebab-${lhpIndex}`);
        
        const nilai = (nilaiInput?.value || '').replace(/\D/g, '');
        
        if (!jenisRadio) {
            window.showAppNotice('Pilih Klasifikasi Penyelesaian (Internal / Eksternal)!', { title: 'Klasifikasi belum dipilih', variant: 'warning' });
            return;
        }
        
        if (!nilai || parseInt(nilai, 10) <= 0) {
            window.showAppNotice('Masukkan Nominal Nilai Kerugian lebih dari 0!', { title: 'Nilai kerugian tidak valid', variant: 'warning' });
            return;
        }
        
        temuanData.kategori_kerugian = kategoriSelect?.value || 'Kerugian Daerah';
        temuanData.jenis_kerugian = jenisRadio.value;
        temuanData.nilai_kerugian = nilai;
        temuanData.penyebab = penyebabInput?.value.trim() || '';
    }
    
    // Initialize array if not exists
    if (!savedTemuans[lhpIndex]) {
        savedTemuans[lhpIndex] = [];
    }
    
    // Add to saved temuans
    savedTemuans[lhpIndex].push(temuanData);
    
    // Update UI to show saved temuan
    displaySavedTemuan(lhpIndex, temuanData);
    
    // Close form and reset
    cancelTemuan(lhpIndex);
    
    // Update statistics
    updateTemuanStats();
    
    // Show success message
    const successMsg = document.createElement('div');
    successMsg.className = 'fixed bottom-6 right-6 rounded-xl bg-emerald-600 px-4 py-3 text-white shadow-lg z-50';
    successMsg.innerHTML = '<span class="text-sm font-semibold">✓ Temuan berhasil ditambahkan!</span>';
    document.body.appendChild(successMsg);
    setTimeout(() => successMsg.remove(), 3000);
}

function displaySavedTemuan(lhpIndex, temuanData) {
    const listContainer = document.querySelector(`.temuan-list-${lhpIndex}`);
    if (!listContainer) return;
    
    // Remove empty message
    const emptyMsg = listContainer.querySelector('.text-center');
    if (emptyMsg) emptyMsg.remove();
    
    // Show list container
    listContainer.classList.remove('hidden');
    
    // Use contiguous index per type so backend receives clean arrays
    const sameType = savedTemuans[lhpIndex].filter(t => t.type === temuanData.type);
    const temuanIndex = sameType.length - 1;
    const fieldKey = temuanData.type === 'administrasi' ? 'temuans_administrasi' : 'temuans_kerugian';
    const nameBase = `lhps[${lhpIndex}][${fieldKey}][${temuanIndex}]`;
    
    const isKerugian = temuanData.type === 'kerugian';
    const badgeClass = isKerugian ? 'bg-amber-100 text-amber-700' : 'bg-primary-100 text-primary-700';
    const icon = isKerugian ? '💰' : '📋';
    
    const card = document.createElement('div');
    card.className = 'rounded-lg border border-gray-200 bg-white p-3 space-y-2';
    
    card.innerHTML = `
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-block px-2 py-0.5 rounded text-xs font-bold ${badgeClass}">
                        ${icon} ${isKerugian ? 'Kerugian' : 'Administrasi'}
                    </span>
                    <span class="text-xs font-bold text-gray-900">${escapeHtml(temuanData.nomor_temuan || '-')}</span>
                </div>
                <h5 class="text-xs font-bold text-gray-900 mb-1">${escapeHtml(temuanData.judul_temuan)}</h5>
                <p class="text-xs text-gray-600 line-clamp-2">${escapeHtml(temuanData.uraian_temuan)}</p>
                ${isKerugian ? `
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                        <span class="font-bold text-emerald-700">Rp ${new Intl.NumberFormat('id-ID').format(temuanData.nilai_kerugian)}</span>
                        <span class="inline-block px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 text-xs font-semibold">${escapeHtml(temuanData.kategori_kerugian)}</span>
                        <span class="inline-block px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 text-xs font-semibold">${escapeHtml(temuanData.jenis_kerugian)}</span>
                    </div>
                ` : ''}
            </div>
            <button type="button" onclick="removeSavedTemuan(${lhpIndex}, ${temuanIndex}, '${temuanData.type}')" 
                    class="text-state-danger hover:text-red-700 text-xs font-bold ml-2 shrink-0">
                Hapus
            </button>
        </div>
        
        <input type="hidden" name="${nameBase}[uraian]" value="${escapeAttr(temuanData.uraian_temuan)}">
        <input type="hidden" name="${nameBase}[rekomendasi]" value="${escapeAttr(temuanData.rekomendasi)}">
        <input type="hidden" name="${nameBase}[penyebab]" value="${escapeAttr(temuanData.penyebab || '')}">
        ${isKerugian ? `
            <input type="hidden" name="${nameBase}[kategori_kerugian]" value="${escapeAttr(temuanData.kategori_kerugian)}">
            <input type="hidden" name="${nameBase}[jenis_kerugian]" value="${escapeAttr(temuanData.jenis_kerugian)}">
            <input type="hidden" name="${nameBase}[nilai_kerugian]" value="${escapeAttr(temuanData.nilai_kerugian)}">
        ` : ''}
    `;
    
    listContainer.appendChild(card);
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str == null ? '' : String(str);
    return div.innerHTML;
}

function escapeAttr(str) {
    return escapeHtml(str).replace(/"/g, '&quot;');
}

async function removeSavedTemuan(lhpIndex, temuanIndex, type) {
    const confirmed = await window.showAppConfirm('Hapus temuan ini?', {
        title: 'Hapus temuan?',
        variant: 'danger',
        acceptLabel: 'Ya, Hapus',
    });
    if (!confirmed) return;
    
    const list = savedTemuans[lhpIndex];
    if (!list) return;
    
    // Temuan di-render per tipe dengan index berurutan, jadi cari offset-nya
    let offset = -1;
    let seen = 0;
    for (let i = 0; i < list.length; i++) {
        if (list[i].type === type) {
            if (seen === temuanIndex) { offset = i; break; }
            seen++;
        }
    }
    
    if (offset > -1) list.splice(offset, 1);
    
    // Rebuild UI + hidden inputs
    const listContainer = document.querySelector(`.temuan-list-${lhpIndex}`);
    if (listContainer) {
        listContainer.innerHTML = '';
        if (list.length === 0) {
            listContainer.classList.add('hidden');
            listContainer.innerHTML = '<div class="text-center py-6 text-gray-500 text-xs">Belum ada temuan untuk LHP ini</div>';
        } else {
            list.forEach(temuan => displaySavedTemuan(lhpIndex, temuan));
        }
    }
    
    updateTemuanStats();
}

function updateTemuanStats() {
    const totalCount = document.getElementById('total-temuan-count');
    const totalAmount = document.getElementById('total-kerugian-amount');
    
    let count = 0;
    let amount = 0;
    
    // Count all temuans
    Object.values(savedTemuans).forEach(lhpTemuans => {
        count += lhpTemuans.length;
        lhpTemuans.forEach(temuan => {
            if (temuan.type === 'kerugian' && temuan.nilai_kerugian) {
                amount += parseInt(temuan.nilai_kerugian);
            }
        });
    });
    
    if (totalCount) totalCount.textContent = count;
    if (totalAmount) totalAmount.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(amount)}`;
}

// Initialize when entering Step 3
document.addEventListener('wizardStepChanged', function(e) {
    if (e.detail.step === 3) {
        populateTemuanFromLhp();
        updateTemuanStats();
    }
});

// Saat LHP di Step 2 berubah (tambah/hapus), segarkan daftar temuan
document.addEventListener('wizardLhpChanged', function() {
    populateTemuanFromLhp();
    updateTemuanStats();
});
</script>
