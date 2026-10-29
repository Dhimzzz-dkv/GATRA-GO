{{-- Step 4: Review & Submit --}}
<div class="wizard-step hidden" id="wizard-step-4" data-step="4">
    <div class="rounded-3xl border border-gray-200/80 bg-white p-6 md:p-8 shadow-sm">
        {{-- Header --}}
        <div class="flex items-center gap-3 border-b border-gray-100 pb-4 mb-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-700 text-white font-bold text-lg shadow-sm">
                ✅
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900 tracking-tight">Review & Konfirmasi</h2>
                <p class="text-xs text-gray-500 font-medium">Tinjau ulang semua data sebelum menyimpan pemeriksaan</p>
            </div>
        </div>

        {{-- Success Message Area (shown after validation) --}}
        <div id="review-validation-success" class="hidden mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-state-success mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h4 class="text-sm font-bold text-emerald-900 mb-1">✓ Data Telah Tervalidasi</h4>
                    <p class="text-xs text-emerald-800">Semua field wajib sudah terisi dengan benar. Klik tombol "Simpan Pemeriksaan" untuk menyimpan.</p>
                </div>
            </div>
        </div>

        {{-- Review Sections --}}
        <div class="space-y-6">
            {{-- 1. SPT & Tim Review --}}
            <div class="rounded-xl border border-primary-200 bg-primary-50/30 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary-600 text-white text-xs">1</span>
                        📋 Surat Perintah Tugas
                    </h3>
                    <button type="button" onclick="goToStep(1)" class="text-xs font-bold text-primary-700 hover:underline cursor-pointer">Edit</button>
                </div>
                <div id="review-spt-content" class="space-y-2 text-sm">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <div><span class="text-gray-500">Judul:</span> <span class="font-semibold text-gray-900" id="review-judul">-</span></div>
                        <div><span class="text-gray-500">No. SPT:</span> <span class="font-semibold text-gray-900" id="review-nomor-spt">-</span></div>
                        <div><span class="text-gray-500">Tanggal:</span> <span class="font-semibold text-gray-900" id="review-tanggal-spt">-</span></div>
                        <div><span class="text-gray-500">Sumber:</span> <span class="font-semibold text-gray-900" id="review-sumber">-</span></div>
                        <div><span class="text-gray-500">Tahun:</span> <span class="font-semibold text-gray-900" id="review-tahun">-</span></div>
                    </div>
                    <div class="pt-2 border-t border-primary-100">
                        <span class="text-gray-500">Tim Pemeriksa:</span>
                        <div id="review-tim" class="mt-1 text-xs space-y-1">
                            <span class="text-gray-500 italic">Belum ada anggota tim</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. LHP Review --}}
            <div class="rounded-xl border border-primary-200 bg-primary-50/30 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary-600 text-white text-xs">2</span>
                        📄 Laporan Hasil Pemeriksaan
                    </h3>
                    <button type="button" onclick="goToStep(2)" class="text-xs font-bold text-primary-700 hover:underline cursor-pointer">Edit</button>
                </div>
                <div id="review-lhp-content" class="space-y-3 text-sm">
                    <span class="text-gray-500 italic text-xs">Belum ada LHP yang ditambahkan</span>
                </div>
            </div>

            {{-- 3. Temuan Review --}}
            <div class="rounded-xl border border-amber-200 bg-amber-50/30 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-600 text-white text-xs">3</span>
                        🔍 Temuan Pemeriksaan
                    </h3>
                    <button type="button" onclick="goToStep(3)" class="text-xs font-bold text-amber-700 hover:underline cursor-pointer">Edit</button>
                </div>
                <div id="review-temuan-content" class="space-y-3 text-sm">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="text-center p-3 rounded-lg bg-white border border-primary-200">
                            <div class="text-2xl font-bold text-primary-900" id="review-total-temuan">0</div>
                            <div class="text-xs text-gray-600 mt-1">Total Temuan</div>
                        </div>
                        <div class="text-center p-3 rounded-lg bg-white border border-amber-200">
                            <div class="text-lg font-bold text-amber-900" id="review-total-kerugian">Rp 0</div>
                            <div class="text-xs text-gray-600 mt-1">Total Kerugian</div>
                        </div>
                    </div>
                    
                    {{-- Detailed Temuan List --}}
                    <div id="review-temuan-detail" class="space-y-2">
                        <p class="text-xs text-gray-500 italic">Belum ada temuan yang ditambahkan</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Final Confirmation --}}
        <div class="mt-6 rounded-xl border-2 border-gray-200 bg-gray-50 p-4">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" id="final-confirmation" class="mt-1 h-4 w-4 rounded border-gray-300 text-state-success focus:ring-emerald-500 cursor-pointer">
                <div class="text-sm">
                    <span class="font-bold text-gray-900">Saya menyatakan bahwa data yang saya inputkan sudah benar dan dapat dipertanggungjawabkan</span>
                    <p class="text-xs text-gray-600 mt-1">Data pemeriksaan ini akan disimpan ke dalam sistem dan dapat dilihat oleh pengguna lain sesuai hak akses.</p>
                </div>
            </label>
        </div>
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
            <button type="submit" id="final-submit-btn" disabled class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 text-sm font-bold shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Simpan Pemeriksaan
            </button>
        </div>
    </div>
</div>

<script>
function populateReviewData() {
    // SPT Data
    document.getElementById('review-judul').textContent = document.getElementById('judul')?.value || '-';
    document.getElementById('review-nomor-spt').textContent = document.getElementById('nomor_spt')?.value || '-';
    document.getElementById('review-tanggal-spt').textContent = formatDate(document.getElementById('tanggal_spt')?.value) || '-';
    
    const sumberSelect = document.getElementById('sumber_pemeriksaan');
    document.getElementById('review-sumber').textContent = sumberSelect?.options[sumberSelect.selectedIndex]?.text || '-';
    
    document.getElementById('review-tahun').textContent = document.getElementById('tahun')?.value || '-';
    
    // Tim Pemeriksa
    const timContainer = document.getElementById('review-tim');
    const timItems = document.querySelectorAll('#wizard-step-1 .tim-item');
    if (timItems.length > 0) {
        timContainer.innerHTML = '';
        timItems.forEach((item, index) => {
            const pegawaiSelect = item.querySelector('select[name*="[pegawai_id]"]');
            const peranSelect = item.querySelector('select[name*="[peran_tim]"]');
            const pegawaiName = pegawaiSelect?.options[pegawaiSelect.selectedIndex]?.text || 'Pegawai tidak dipilih';
            const peran = peranSelect?.value || 'Peran tidak dipilih';
            
            const div = document.createElement('div');
            div.className = 'flex items-center gap-2 p-2 bg-white rounded border border-primary-100';
            div.innerHTML = `
                <span class="flex h-5 w-5 items-center justify-center rounded bg-primary-100 text-primary-700 text-xs font-bold">${index + 1}</span>
                <span class="text-xs"><strong>${escapeHtmlText(peran)}:</strong> ${escapeHtmlText(pegawaiName)}</span>
            `;
            timContainer.appendChild(div);
        });
    } else {
        timContainer.innerHTML = '<span class="text-gray-500 italic text-xs">Belum ada anggota tim</span>';
    }
    
    // LHP Data
    const lhpContainer = document.getElementById('review-lhp-content');
    const lhpCards = document.querySelectorAll('#wizard-step-2 .lhp-card');
    if (lhpCards.length > 0) {
        lhpContainer.innerHTML = '';
        lhpCards.forEach((card, index) => {
            const nomorLhp = card.querySelector('input[name*="[nomor_lhp]"]')?.value || '-';
            const tanggalLhp = card.querySelector('input[name*="[tanggal_lhp]"]')?.value || '-';
            const judulLhp = card.querySelector('input[name*="[judul_lhp]"]')?.value || '(Sama dengan judul SPT)';
            const checkedOpds = card.querySelectorAll('.opd-checkbox:checked');
            
            const div = document.createElement('div');
            div.className = 'p-3 bg-white rounded-lg border border-primary-200';
            div.innerHTML = `
                <div class="flex items-center gap-2 mb-2">
                    <span class="flex h-5 w-5 items-center justify-center rounded bg-primary-600 text-white text-xs font-bold">${index + 1}</span>
                    <span class="text-xs font-bold text-gray-900">${escapeHtmlText(nomorLhp)}</span>
                    <span class="text-xs text-gray-500">• ${escapeHtmlText(formatDate(tanggalLhp))}</span>
                </div>
                <p class="text-xs text-gray-600 mb-2">${escapeHtmlText(judulLhp)}</p>
                <div class="text-xs">
                    <span class="text-gray-500">OPD Target (${checkedOpds.length}):</span>
                    <div class="flex flex-wrap gap-1 mt-1">
                        ${Array.from(checkedOpds).map(cb => {
                            const label = cb.closest('.opd-item');
                            const code = label?.querySelector('.text-primary-900')?.textContent || '';
                            return `<span class="inline-block px-2 py-0.5 bg-primary-100 text-primary-800 rounded text-xs font-semibold">${escapeHtmlText(code)}</span>`;
                        }).join('')}
                    </div>
                </div>
            `;
            lhpContainer.appendChild(div);
        });
    } else {
        lhpContainer.innerHTML = '<span class="text-gray-500 italic text-xs">Belum ada LHP yang ditambahkan</span>';
    }
    
    // Temuan Stats and Details
    const totalTemuanEl = document.getElementById('review-total-temuan');
    const totalKerugianEl = document.getElementById('review-total-kerugian');
    const temuanDetailEl = document.getElementById('review-temuan-detail');
    
    // Get saved temuans from Step 3
    const savedTemuans = window.savedTemuans || {};
    
    let totalCount = 0;
    let totalAmount = 0;
    let hasTemuans = false;
    
    // Build detailed temuan list
    if (temuanDetailEl) {
        temuanDetailEl.innerHTML = '';
        
        Object.keys(savedTemuans).forEach(lhpIndex => {
            const lhpTemuans = savedTemuans[lhpIndex];
            if (lhpTemuans && lhpTemuans.length > 0) {
                hasTemuans = true;
                
                // Get LHP info
                const lhpCard = document.querySelector(`#wizard-step-2 .lhp-card[data-lhp-index="${lhpIndex}"]`);
                const nomorLhp = lhpCard?.querySelector('input[name*="[nomor_lhp]"]')?.value || `LHP #${parseInt(lhpIndex) + 1}`;
                
                // Create LHP group
                const lhpGroup = document.createElement('div');
                lhpGroup.className = 'rounded-lg border border-amber-200 bg-white p-3 space-y-2';
                lhpGroup.innerHTML = `<div class="text-xs font-bold text-amber-900 border-b border-amber-100 pb-2 mb-2">${escapeHtmlText(nomorLhp)}</div>`;
                
                lhpTemuans.forEach(temuan => {
                    totalCount++;
                    
                    const temuanCard = document.createElement('div');
                    temuanCard.className = 'rounded border border-gray-200 bg-gray-50 p-2 space-y-1';
                    
                    let badgeClass = temuan.type === 'administrasi' ? 'bg-primary-100 text-primary-700' : 'bg-amber-100 text-amber-700';
                    let icon = temuan.type === 'administrasi' ? '📋' : '💰';
                    
                    let content = `
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="inline-block px-1.5 py-0.5 rounded text-xs font-bold ${badgeClass}">
                                        ${icon} ${temuan.type === 'administrasi' ? 'Administrasi' : 'Kerugian'}
                                    </span>
                                    <span class="text-xs font-bold text-gray-900">${escapeHtmlText(temuan.nomor_temuan)}</span>
                                </div>
                                <h6 class="text-xs font-bold text-gray-900">${escapeHtmlText(temuan.judul_temuan)}</h6>
                                <p class="text-xs text-gray-600 line-clamp-1 mt-0.5">${escapeHtmlText(temuan.uraian_temuan)}</p>
                    `;
                    
                    if (temuan.type === 'kerugian') {
                        const nilaiFormatted = new Intl.NumberFormat('id-ID').format(temuan.nilai_kerugian);
                        totalAmount += parseInt(temuan.nilai_kerugian);
                        content += `
                                <div class="mt-1.5 flex items-center gap-2 text-xs">
                                    <span class="font-bold text-emerald-700">Rp ${nilaiFormatted}</span>
                                    <span class="text-gray-500">•</span>
                                    <span class="text-gray-600">${escapeHtmlText(temuan.kategori_kerugian)}</span>
                                    <span class="text-gray-500">•</span>
                                    <span class="text-gray-600">${escapeHtmlText(temuan.jenis_kerugian)}</span>
                                </div>
                        `;
                    }
                    
                    content += `
                            </div>
                        </div>
                    `;
                    
                    temuanCard.innerHTML = content;
                    lhpGroup.appendChild(temuanCard);
                });
                
                temuanDetailEl.appendChild(lhpGroup);
            }
        });
        
        if (!hasTemuans) {
            temuanDetailEl.innerHTML = '<p class="text-xs text-gray-500 italic">Belum ada temuan yang ditambahkan. Temuan dapat ditambahkan setelah pemeriksaan tersimpan.</p>';
        }
    }
    
    if (totalTemuanEl) totalTemuanEl.textContent = totalCount;
    if (totalKerugianEl) totalKerugianEl.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(totalAmount)}`;
    
    // Validate all required fields
    validateAllFields();
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

function validateAllFields() {
    const requiredFields = document.querySelectorAll('[required]');
    let allValid = true;
    const validatedRadioGroups = new Set();
    
    requiredFields.forEach(field => {
        if (field.disabled || field.type === 'hidden') return;

        // Langkah wizard sebelumnya memang tersembunyi saat review, tetapi
        // kolom wajibnya tetap harus diperiksa. Hanya kontainer kondisional
        // yang disembunyikan di dalam langkah yang dilewati.
        for (let element = field.parentElement; element && !element.classList.contains('wizard-step'); element = element.parentElement) {
            if (element.hidden || element.classList.contains('hidden') || getComputedStyle(element).display === 'none') return;
        }

        if (field.type === 'radio' && field.name) {
            if (validatedRadioGroups.has(field.name)) return;
            validatedRadioGroups.add(field.name);
            const selected = Array.from(document.querySelectorAll('input[type="radio"]'))
                .some(radio => radio.name === field.name && radio.checked && !radio.disabled);
            if (!selected) allValid = false;
            return;
        }

        const isEmpty = field.type === 'checkbox'
            ? !field.checked
            : !String(field.value ?? '').trim();
        const isInvalid = typeof field.checkValidity === 'function' && !field.checkValidity();

        if (isEmpty || isInvalid) {
            allValid = false;
        }
    });
    
    // Setiap LHP wajib punya minimal 1 OPD target
    const lhpCards = document.querySelectorAll('#wizard-step-2 .lhp-card');
    if (lhpCards.length === 0) {
        allValid = false;
    } else {
        for (const card of lhpCards) {
            if (card.querySelectorAll('.opd-checkbox:checked').length === 0) {
                allValid = false;
            }
        }
    }
    
    // Minimal 1 anggota tim pemeriksa
    const timItems = document.querySelectorAll('#wizard-step-1 .tim-item');
    if (timItems.length === 0) {
        allValid = false;
    }
    
    // Tim Pemeriksa harus terisi lengkap (pegawai & peran)
    for (const item of document.querySelectorAll('#wizard-step-1 .tim-item')) {
        const selects = item.querySelectorAll('select[required]');
        for (const s of selects) {
            if (!s.value) { allValid = false; break; }
        }
    }
    
    const successMsg = document.getElementById('review-validation-success');
    if (allValid && successMsg) {
        successMsg.classList.remove('hidden');
    } else if (successMsg) {
        successMsg.classList.add('hidden');
    }
    
    return allValid;
}

// Enable submit button when confirmation checkbox is checked
document.addEventListener('DOMContentLoaded', function() {
    const confirmCheckbox = document.getElementById('final-confirmation');
    const submitBtn = document.getElementById('final-submit-btn');
    
    if (confirmCheckbox && submitBtn) {
        confirmCheckbox.addEventListener('change', function() {
            submitBtn.disabled = !this.checked;
        });
    }
});

// Populate review when entering Step 4
document.addEventListener('wizardStepChanged', function(e) {
    if (e.detail.step === 4) {
        populateReviewData();
    }
});
</script>
