@extends('layouts.app')

@section('title', 'Wizard Input Pemeriksaan')
@section('page-title', 'Input Pemeriksaan - Mode Wizard')
@section('page-subtitle', 'Panduan langkah demi langkah untuk input data pemeriksaan dengan lebih mudah')

@section('content')
<div class="max-w-5xl mx-auto pb-20">
    {{-- Wizard Mode Badge --}}
    <div class="mb-4 flex items-center justify-between">
        <div class="inline-flex items-center gap-2 rounded-lg bg-primary-800 px-4 py-2 shadow-sm">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <span class="text-sm font-bold text-white">Mode Wizard - 4 Langkah Mudah</span>
        </div>
        <a href="{{ route('pemeriksaan.create') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-700 underline">
            Pakai form klasik?
        </a>
    </div>

    <form id="wizard-form" method="POST" action="{{ route('pemeriksaan.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        
        {{-- Progress Bar Component --}}
        @include('pemeriksaan.wizard._progress', ['currentStep' => 1])

        {{-- Step 1: SPT & Tim --}}
        @include('pemeriksaan.wizard.step1', [
            'lockedSumber' => $lockedSumber,
            'opds' => $opds,
            'pegawais' => $pegawais,
            'draft' => $draft ?? []
        ])

        {{-- Step 2: Data LHP --}}
        @include('pemeriksaan.wizard.step2', [
            'opds' => $opds,
            'draft' => $draft ?? []
        ])

        {{-- Step 3: Input Temuan --}}
        @include('pemeriksaan.wizard.step3', [
            'draft' => $draft ?? []
        ])

        {{-- Step 4: Review & Submit --}}
        @include('pemeriksaan.wizard.step4')
    </form>
</div>

{{-- Auto-save notification toast --}}
<div id="autosave-toast" class="fixed bottom-6 right-6 hidden rounded-xl bg-gray-900 px-4 py-3 text-white shadow-lg z-50 transition-opacity">
    <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-emerald-400 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-sm font-semibold">Menyimpan draft...</span>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ========================================
// Wizard Navigation & State Management
// ========================================
let currentStep = 1;
const totalSteps = 4;
const draftStorageKey = @json('pemeriksaan_wizard_draft_' . auth()->id());

// Initialize wizard on page load
document.addEventListener('DOMContentLoaded', function() {
    showStep(1, false);
    initializeAutoSave();
});

function showStep(step, moveFocus = true) {
    // Hide all steps
    document.querySelectorAll('.wizard-step').forEach(el => {
        el.classList.add('hidden');
    });
    
    // Show current step
    const stepEl = document.getElementById(`wizard-step-${step}`);
    if (stepEl) {
        stepEl.classList.remove('hidden');

        if (moveFocus) {
            const heading = stepEl.querySelector('h1, h2, h3');
            if (heading) {
                heading.setAttribute('tabindex', '-1');
                heading.focus({ preventScroll: true });
            }
        }
    }
    
    currentStep = step;
    
    // Update progress bar
    updateProgressBar(step);
    
    // Dispatch custom event for step change
    const event = new CustomEvent('wizardStepChanged', { detail: { step } });
    document.dispatchEvent(event);
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function updateProgressBar(step) {
    // Update progress bar width
    const progressBar = document.querySelector('.wizard-progress-bar');
    if (progressBar) {
        const percentage = ((step - 1) / (totalSteps - 1)) * 100;
        progressBar.style.width = `${percentage}%`;
        progressBar.setAttribute('aria-valuenow', String(step));
        progressBar.setAttribute('aria-valuetext', `Langkah ${step} dari ${totalSteps}`);
    }

    const stepDescriptions = {
        1: 'Masukkan data Surat Perintah Tugas dan susunan tim pemeriksa',
        2: 'Input informasi Laporan Hasil Pemeriksaan (LHP) dan OPD target',
        3: 'Tambahkan temuan pemeriksaan (administrasi dan/atau kerugian daerah)',
        4: 'Tinjau ulang semua data sebelum menyimpan'
    };
    const description = document.getElementById('wizard-step-description');
    if (description) description.textContent = stepDescriptions[step] || '';
    
    // Update step circles
    document.querySelectorAll('.wizard-step-circle').forEach(circle => {
        const circleStep = parseInt(circle.dataset.step);
        
        // Remove all classes
        circle.classList.remove('bg-emerald-500', 'text-white', 'border-emerald-500',
                                'bg-primary-600', 'border-primary-600', 'ring-4', 'ring-primary-100',
                                'bg-gray-100', 'text-gray-500', 'border-gray-200');
        
        if (circleStep < step) {
            // Completed
            circle.classList.add('bg-emerald-500', 'text-white', 'border-2', 'border-emerald-500');
            circle.innerHTML = `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>`;
        } else if (circleStep === step) {
            // Current
            circle.classList.add('bg-primary-600', 'text-white', 'border-2', 'border-primary-600', 'ring-4', 'ring-primary-100');
            circle.textContent = circleStep;
        } else {
            // Upcoming
            circle.classList.add('bg-gray-100', 'text-gray-500', 'border-2', 'border-gray-200');
            circle.textContent = circleStep;
        }
    });
}

function nextStep() {
    if (validateCurrentStep()) {
        if (currentStep < totalSteps) {
            showStep(currentStep + 1);
        }
    }
}

function prevStep() {
    if (currentStep > 1) {
        showStep(currentStep - 1);
    }
}

function goToStep(step) {
    if (step >= 1 && step <= totalSteps) {
        showStep(step);
    }
}

function validateCurrentStep() {
    const currentStepEl = document.getElementById(`wizard-step-${currentStep}`);
    if (!currentStepEl) return true;
    
    const requiredFields = currentStepEl.querySelectorAll('[required]');
    let isValid = true;
    let firstErrorTarget = null;
    const validatedRadioGroups = new Set();

    const clearFieldError = field => {
        const errorId = field.dataset.validationErrorId;
        if (errorId) {
            document.getElementById(errorId)?.remove();
            delete field.dataset.validationErrorId;
            const describedBy = (field.getAttribute('aria-describedby') || '').split(/\s+/).filter(id => id && id !== errorId);
            if (describedBy.length) field.setAttribute('aria-describedby', describedBy.join(' '));
            else field.removeAttribute('aria-describedby');
        }
        field.removeAttribute('aria-invalid');
        field.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
    };

    const showFieldError = (field, message, errorAfter = field) => {
        clearFieldError(field);
        const errorId = `wizard-error-${Math.random().toString(36).slice(2)}`;
        const error = document.createElement('p');
        error.id = errorId;
        error.className = 'mt-1 text-xs font-medium text-state-danger';
        error.textContent = message;
        errorAfter.insertAdjacentElement('afterend', error);
        field.dataset.validationErrorId = errorId;
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', [field.getAttribute('aria-describedby'), errorId].filter(Boolean).join(' '));
        field.classList.add('border-red-500', 'ring-2', 'ring-red-200');
        if (!firstErrorTarget) firstErrorTarget = field;
    };

    const isConditionallyHidden = field => {
        for (let element = field; element && element !== currentStepEl; element = element.parentElement) {
            if (element.hidden || element.classList.contains('hidden') || getComputedStyle(element).display === 'none') {
                return true;
            }
        }

        return field.closest('.wizard-step') !== currentStepEl;
    };
    
    requiredFields.forEach(field => {
        // Ignore conditional fields hidden within this step, but still validate
        // fields when the whole step is hidden during final submission.
        if (field.disabled || field.type === 'hidden' || isConditionallyHidden(field)) return;

        if (field.type === 'radio' && field.name) {
            if (validatedRadioGroups.has(field.name)) return;
            validatedRadioGroups.add(field.name);

            const radioGroup = Array.from(currentStepEl.querySelectorAll('input[type="radio"]'))
                .filter(radio => radio.name === field.name && !radio.disabled && !isConditionallyHidden(radio));
            const selectedRadio = radioGroup.find(radio => radio.checked);

            if (!selectedRadio) {
                showFieldError(radioGroup[0] || field, 'Pilih salah satu pilihan.');
                isValid = false;
            } else {
                radioGroup.forEach(clearFieldError);
            }
            return;
        }
        
        const isEmpty = field.type === 'checkbox' || field.type === 'radio'
            ? !field.checked
            : !String(field.value).trim();

        if (isEmpty || !field.checkValidity()) {
            isValid = false;
            showFieldError(field, isEmpty ? 'Bagian ini wajib diisi.' : (field.validationMessage || 'Periksa kembali nilai yang dimasukkan.'));
        } else {
            clearFieldError(field);
        }
    });
    
    // Additional validation for Step 1: Tim Pemeriksa
    if (currentStep === 1) {
        const timItems = currentStepEl.querySelectorAll('.tim-item');
        if (timItems.length === 0) {
            const addButton = currentStepEl.querySelector('[onclick="addTimPemeriksa()"]');
            showFieldError(addButton, 'Tambahkan minimal 1 anggota tim pemeriksa.');
            isValid = false;
        }
    }
    
    // Additional validation for Step 2: LHP & OPD
    if (currentStep === 2) {
        const lhpCards = currentStepEl.querySelectorAll('.lhp-card');
        if (lhpCards.length === 0) {
            const addButton = currentStepEl.querySelector('#btn-tambah-lhp');
            showFieldError(addButton, 'Tambahkan minimal 1 LHP.');
            isValid = false;
        }
        
        // Check each LHP has at least one OPD selected
        for (let card of lhpCards) {
            const checkedOpds = card.querySelectorAll('.opd-checkbox:checked');
            if (checkedOpds.length === 0) {
                const firstCheckbox = card.querySelector('.opd-checkbox');
                if (firstCheckbox) {
                    const checkboxList = firstCheckbox.closest('.opd-checkbox-list');
                    showFieldError(firstCheckbox, 'Pilih minimal 1 OPD target untuk LHP ini.', checkboxList);
                    checkboxList?.classList.add('border-red-500');
                }
                isValid = false;
            }
        }
    }
    
    if (!isValid) {
        if (firstErrorTarget) {
            firstErrorTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => firstErrorTarget.focus({ preventScroll: true }), 250);
        }
    }
    
    return isValid;
}

document.addEventListener('input', clearWizardFieldError);
document.addEventListener('change', clearWizardFieldError);

function clearWizardFieldError(event) {
    const target = event.target;
    const field = target.classList?.contains('opd-checkbox')
        ? target.closest('.lhp-card')?.querySelector('.opd-checkbox')
        : target;
    if (!(field instanceof HTMLElement) || !field.dataset.validationErrorId) return;
    document.getElementById(field.dataset.validationErrorId)?.remove();
    delete field.dataset.validationErrorId;
    field.removeAttribute('aria-invalid');
    const describedBy = (field.getAttribute('aria-describedby') || '').split(/\s+/).filter(id => id);
    const remainingDescriptions = describedBy.filter(id => document.getElementById(id));
    if (remainingDescriptions.length) field.setAttribute('aria-describedby', remainingDescriptions.join(' '));
    else field.removeAttribute('aria-describedby');
    field.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
    field.closest('.lhp-card')?.querySelector('.opd-checkbox-list')?.classList.remove('border-red-500');
}

// ========================================
// Draft Auto-save & Local Storage
// ========================================
let autoSaveTimer = null;

function initializeAutoSave() {
    // Auto-save every 2 minutes
    setInterval(() => {
        saveDraft(true); // silent save
    }, 120000);
    
    // Save on input changes (debounced)
    document.addEventListener('input', function(e) {
        if (e.target.hasAttribute('data-wizard-field')) {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(() => {
                saveDraft(true);
            }, 5000); // Save 5 seconds after last input
        }
    });
}

function saveDraft(silent = false) {
    const formData = collectFormData();
    
    // Save to localStorage
    localStorage.setItem(draftStorageKey, JSON.stringify({
        data: formData,
        timestamp: new Date().toISOString(),
        currentStep: currentStep
    }));
    
    if (!silent) {
        showToast('Draft tersimpan secara lokal', 'success');
    }
}

function loadDraft() {
    const draft = localStorage.getItem(draftStorageKey);
    if (draft) {
        try {
            const parsed = JSON.parse(draft);
            if (confirm(`Draft terakhir ditemukan (${new Date(parsed.timestamp).toLocaleString('id-ID')}). Lanjutkan dari draft?`)) {
                populateFormFromDraft(parsed.data);
                showStep(parsed.currentStep || 1);
            }
        } catch (e) {
            console.error('Failed to load draft:', e);
        }
    }
}

function collectFormData() {
    const form = document.getElementById('wizard-form');
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        if (key === '_token') continue;

        if (Object.prototype.hasOwnProperty.call(data, key)) {
            if (!Array.isArray(data[key])) data[key] = [data[key]];
            data[key].push(value);
        } else {
            data[key] = value;
        }
    }
    
    return data;
}

function populateFormFromDraft(data) {
    Object.keys(data).forEach(key => {
        const fields = Array.from(document.getElementsByName(key));
        if (fields.length === 0) return;

        const values = (Array.isArray(data[key]) ? data[key] : [data[key]]).map(String);
        const firstField = fields[0];

        if (firstField.type === 'checkbox' || firstField.type === 'radio') {
            fields.forEach(field => {
                field.checked = values.includes(String(field.value));
            });
            return;
        }

        fields.forEach((field, index) => {
            if (field.type === 'file') return;
            field.value = values[Math.min(index, values.length - 1)] ?? '';
        });
    });
}

function clearDraft() {
    localStorage.removeItem(draftStorageKey);
}

function showToast(message, type = 'info') {
    // Simple toast implementation
    const toast = document.createElement('div');
    toast.className = `fixed bottom-6 right-6 rounded-xl px-4 py-3 text-white shadow-lg z-50 ${
        type === 'success' ? 'bg-emerald-600' : 
        type === 'error' ? 'bg-red-600' : 
        'bg-gray-900'
    }`;
    const content = document.createElement('div');
    content.className = 'flex items-center gap-2';
    const text = document.createElement('span');
    text.className = 'text-sm font-semibold';
    text.textContent = message;
    content.appendChild(text);
    toast.appendChild(content);
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ========================================
// Form Submission
// ========================================
document.getElementById('wizard-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validate all steps
    for (let step = 1; step <= totalSteps; step++) {
        showStep(step, false);
        if (!validateCurrentStep()) {
            return false;
        }
    }
    
    // Show loading
    const submitBtn = document.getElementById('final-submit-btn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Menyimpan...</span>
        `;
    }
    
    // Submit form
    this.submit();
    
    // Clear draft after successful submission
    setTimeout(() => {
        clearDraft();
    }, 1000);
});

// Check for existing draft on load
setTimeout(() => {
    loadDraft();
}, 500);

// Prevent accidental page leave
window.addEventListener('beforeunload', function(e) {
    const formData = collectFormData();
    if (Object.keys(formData).length > 3) { // More than just CSRF token
        e.preventDefault();
        e.returnValue = 'Perubahan Anda mungkin belum tersimpan. Yakin ingin meninggalkan halaman?';
        return e.returnValue;
    }
});
</script>
@endpush
