function toggleSidebar(forceOpen) {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const toggle = document.getElementById('sidebar-toggle');

    if (!sidebar || !overlay) return;

    if (window.matchMedia('(min-width: 1024px)').matches) {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.add('hidden');
        sidebar.setAttribute('aria-hidden', 'false');
        sidebar.inert = false;
        toggle?.setAttribute('aria-expanded', 'false');
        return;
    }

    const shouldOpen = typeof forceOpen === 'boolean'
        ? forceOpen
        : sidebar.classList.contains('-translate-x-full');

    sidebar.classList.toggle('-translate-x-full', !shouldOpen);
    overlay.classList.toggle('hidden', !shouldOpen);
    sidebar.setAttribute('aria-hidden', String(!shouldOpen));
    sidebar.inert = !shouldOpen;
    toggle?.setAttribute('aria-expanded', String(shouldOpen));

    if (shouldOpen) {
        setTimeout(function () {
            sidebar.querySelector('a, button, summary')?.focus();
        }, 0);
    } else {
        toggle?.focus();
    }
}

function toggleUserMenu(forceOpen) {
    const toggle = document.getElementById('user-menu-toggle');
    const menu = document.getElementById('user-menu');
    if (!toggle || !menu) return;

    const shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : menu.classList.contains('hidden');
    menu.classList.toggle('hidden', !shouldOpen);
    toggle.setAttribute('aria-expanded', String(shouldOpen));

    if (shouldOpen) {
        menu.querySelector('a[href], button:not([disabled])')?.focus();
    }
}

// Keep these functions available to the existing inline button handlers.
window.toggleSidebar = toggleSidebar;
window.toggleUserMenu = toggleUserMenu;

function initializeConfirmationDialog() {
    const dialog = document.getElementById('app-confirm-dialog');
    const title = document.getElementById('app-confirm-title');
    const message = document.getElementById('app-confirm-message');
    const eyebrow = document.getElementById('app-confirm-eyebrow');
    const icon = document.getElementById('app-confirm-icon');
    const cancelButton = document.getElementById('app-confirm-cancel');
    const closeButton = document.getElementById('app-confirm-close');
    const acceptButton = document.getElementById('app-confirm-accept');
    const acceptLabel = document.getElementById('app-confirm-accept-label');

    if (!dialog || !title || !message || !eyebrow || !icon || !cancelButton || !closeButton || !acceptButton || !acceptLabel) {
        return;
    }

    const variants = {
        approval: {
            eyebrow: 'Konfirmasi Persetujuan',
            icon: 'bg-emerald-50 text-emerald-700',
            iconPath: 'M5 13l4 4L19 7',
            button: 'bg-emerald-600 hover:bg-emerald-700 focus-visible:ring-emerald-500',
            label: 'Ya, Setujui',
        },
        submit: {
            eyebrow: 'Konfirmasi Pengajuan',
            icon: 'bg-primary-50 text-primary-700',
            iconPath: 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8',
            button: 'bg-primary-600 hover:bg-primary-700 focus-visible:ring-primary-500',
            label: 'Ya, Ajukan',
        },
        cancel: {
            eyebrow: 'Konfirmasi Pembatalan',
            icon: 'bg-amber-50 text-amber-700',
            iconPath: 'M9 14l-4-4 4-4M5 10h9a5 5 0 015 5v1',
            button: 'bg-amber-600 hover:bg-amber-700 focus-visible:ring-amber-500',
            label: 'Ya, Batalkan',
        },
    };
    const approvedForms = new WeakSet();
    let activeForm = null;
    let activeSubmitter = null;
    let previousFocus = null;

    const closeDialog = () => {
        dialog.classList.add('hidden');
        dialog.classList.remove('flex');
        dialog.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        activeForm = null;
        activeSubmitter = null;
        previousFocus?.focus();
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        if (approvedForms.has(form)) {
            approvedForms.delete(form);
            return;
        }

        const confirmationMessage = form.dataset.confirmMessage;
        if (!confirmationMessage) return;

        event.preventDefault();
        activeForm = form;
        activeSubmitter = event.submitter instanceof HTMLElement ? event.submitter : null;
        previousFocus = activeSubmitter || document.activeElement;

        const variant = variants[form.dataset.confirmVariant] || variants.approval;
        title.textContent = form.dataset.confirmTitle || 'Konfirmasi tindakan';
        message.textContent = confirmationMessage;
        eyebrow.textContent = variant.eyebrow;
        icon.className = `flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ${variant.icon}`;
        icon.innerHTML = `<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="${variant.iconPath}"/></svg>`;
        acceptButton.className = `inline-flex min-h-10 items-center justify-center gap-2 rounded-xl px-4 text-sm font-semibold text-white shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 ${variant.button}`;
        acceptLabel.textContent = form.dataset.confirmAccept || variant.label;

        dialog.classList.remove('hidden');
        dialog.classList.add('flex');
        dialog.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        cancelButton.focus();
    }, true);

    const cancelConfirmation = () => {
        if (activeForm) closeDialog();
    };

    cancelButton.addEventListener('click', cancelConfirmation);
    closeButton.addEventListener('click', cancelConfirmation);
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) cancelConfirmation();
    });

    acceptButton.addEventListener('click', () => {
        if (!activeForm) return;

        const form = activeForm;
        const submitter = activeSubmitter;
        closeDialog();
        approvedForms.add(form);

        try {
            if (submitter && form.contains(submitter) && !submitter.disabled) {
                form.requestSubmit(submitter);
            } else {
                form.requestSubmit();
            }
        } finally {
            approvedForms.delete(form);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (dialog.classList.contains('hidden')) return;

        if (event.key === 'Escape') {
            event.preventDefault();
            cancelConfirmation();
            return;
        }

        if (event.key !== 'Tab') return;

        const focusable = Array.from(dialog.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'))
            .filter(element => !element.hasAttribute('hidden'));
        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    });
}

initializeConfirmationDialog();

let employeeSearchId = 0;

function normalizeEmployeeLabel(value) {
    return value.trim().replace(/\s+/g, ' ').normalize('NFKC').toLocaleLowerCase('id-ID');
}

function createEmployeeSearchId(base) {
    let id = base;
    let suffix = 2;

    while (document.getElementById(id)) {
        id = `${base}-${suffix++}`;
    }

    return id;
}

function enhanceEmployeeSelect(select) {
    if (!(select instanceof HTMLSelectElement) || select.dataset.searchEnhanced === 'true') return;

    const employeeOptions = Array.from(select.options).filter(option => option.value !== '');
    if (employeeOptions.length === 0) return;

    const existingLabels = Array.from(select.labels || []);
    const labelText = select.getAttribute('aria-label')
        || existingLabels.map(label => label.textContent.trim()).filter(Boolean).join(' ')
        || 'Pegawai pemeriksa';
    const inputId = createEmployeeSearchId(select.id
        ? `${select.id}-search`
        : `pegawai-pemeriksa-search-${++employeeSearchId}`);
    const listId = createEmployeeSearchId(`${inputId}-suggestions`);
    const input = document.createElement('input');
    const datalist = document.createElement('datalist');
    const optionsByLabel = new Map();
    const renderedSuggestions = new Set();

    input.type = 'text';
    input.id = inputId;
    input.className = select.className;
    input.placeholder = 'Ketik nama atau jabatan pegawai...';
    input.autocomplete = 'off';
    input.setAttribute('list', listId);
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-label', `${labelText} — ketik untuk mencari`);

    if (select.required) {
        input.required = true;
        input.setAttribute('aria-required', 'true');
    }

    if (select.disabled) {
        input.disabled = true;
    }

    const describedBy = select.getAttribute('aria-describedby');
    if (describedBy) {
        input.setAttribute('aria-describedby', describedBy);
    }

    datalist.id = listId;

    employeeOptions.forEach(option => {
        const label = option.textContent.trim().replace(/\s+/g, ' ');
        const normalizedLabel = normalizeEmployeeLabel(label);
        const matchingOptions = optionsByLabel.get(normalizedLabel) || [];

        matchingOptions.push({ value: option.value, label });
        optionsByLabel.set(normalizedLabel, matchingOptions);

        if (!renderedSuggestions.has(normalizedLabel)) {
            const suggestion = document.createElement('option');
            suggestion.value = label;
            datalist.appendChild(suggestion);
            renderedSuggestions.add(normalizedLabel);
        }
    });

    const setSelectedEmployee = function(value) {
        if (select.value === value) return;

        select.value = value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const updateSearchValidity = function() {
        const normalizedLabel = normalizeEmployeeLabel(input.value);

        if (!normalizedLabel) {
            setSelectedEmployee('');
            input.setCustomValidity('');
            input.removeAttribute('aria-invalid');
            return;
        }

        const matchingOptions = optionsByLabel.get(normalizedLabel) || [];
        if (matchingOptions.length === 1) {
            setSelectedEmployee(matchingOptions[0].value);
            input.setCustomValidity('');
            input.removeAttribute('aria-invalid');
            return;
        }

        setSelectedEmployee('');
        input.setAttribute('aria-invalid', 'true');
        input.setCustomValidity(matchingOptions.length > 1
            ? 'Nama pegawai cocok dengan lebih dari satu pilihan. Tambahkan jabatan agar lebih spesifik.'
            : 'Pilih nama pegawai dari daftar rekomendasi.');
    };

    const syncSearchFromSelect = function() {
        const selectedOption = select.selectedOptions[0];
        input.value = selectedOption?.value ? selectedOption.textContent.trim().replace(/\s+/g, ' ') : '';
        input.setCustomValidity('');
        input.removeAttribute('aria-invalid');
    };

    input.addEventListener('input', updateSearchValidity);
    select.addEventListener('change', syncSearchFromSelect);
    select.form?.addEventListener('reset', function() {
        window.setTimeout(syncSearchFromSelect, 0);
    });

    syncSearchFromSelect();
    select.insertAdjacentElement('beforebegin', input);
    select.insertAdjacentElement('afterend', datalist);
    existingLabels.forEach(label => {
        label.htmlFor = inputId;
    });
    select.required = false;
    select.hidden = true;
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');
    select.dataset.searchEnhanced = 'true';
}

function enhanceEmployeeSearchControls(root) {
    if (!(root instanceof Element)) return;

    const selector = 'select[name^="tim_pemeriksa["][name$="[pegawai_id]"]';
    const selects = [
        ...(root.matches(selector) ? [root] : []),
        ...root.querySelectorAll(selector),
    ];

    selects.forEach(enhanceEmployeeSelect);
}

function handleRoleChange(roleId, clearIncompatiblePegawai = false) {
    const roleSelect = document.getElementById('role_id');
    const selectedOption = roleSelect?.options[roleSelect.selectedIndex];
    const roleName = selectedOption?.getAttribute('data-name') || '';
    const pegawaiGroup = document.getElementById('pegawai-select-group');
    const opdGroup = document.getElementById('opd-select-group');
    const pegawaiSelect = document.getElementById('pegawai_id');
    const requiredIndicator = document.getElementById('pegawai-required-indicator');

    if (!pegawaiGroup || !opdGroup) return;

    const isOpdRole = roleName === 'opd';
    const requiresPegawai = ['super_admin', 'admin_inspektorat'].includes(roleName);
    const externalSourceRole = ['admin_bpk', 'admin_bpkp', 'admin_itjen', 'opd'].includes(roleName);
    opdGroup.classList.toggle('hidden', !isOpdRole);
    pegawaiGroup.classList.toggle('hidden', isOpdRole);

    if (clearIncompatiblePegawai && externalSourceRole && pegawaiSelect instanceof HTMLSelectElement && pegawaiSelect.value) {
        pegawaiSelect.value = '';
        handlePegawaiChange(pegawaiSelect);
    }

    if (pegawaiSelect instanceof HTMLSelectElement) {
        pegawaiSelect.required = requiresPegawai;
        pegawaiSelect.setAttribute('aria-required', String(requiresPegawai));
    }

    requiredIndicator?.classList.toggle('hidden', !requiresPegawai);
}

function handlePegawaiChange(selectElement) {
    if (!(selectElement instanceof HTMLSelectElement)) return;

    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const identityHint = document.getElementById('identity-master-hint');

    if (selectedOption?.value) {
        const pegawaiName = selectedOption.getAttribute('data-nama');
        const pegawaiEmail = selectedOption.getAttribute('data-email');

        if (nameInput instanceof HTMLInputElement) {
            nameInput.value = pegawaiName || '';
            nameInput.readOnly = true;
            nameInput.dataset.managedByPegawai = 'true';
            nameInput.classList.add('bg-gray-50', 'cursor-not-allowed');
        }

        if (emailInput instanceof HTMLInputElement) {
            emailInput.value = pegawaiEmail || '';
            emailInput.readOnly = true;
            emailInput.dataset.managedByPegawai = 'true';
            emailInput.classList.add('bg-gray-50', 'cursor-not-allowed');
        }

        identityHint?.classList.remove('hidden');
    } else {
        [nameInput, emailInput].forEach((input) => {
            if (!(input instanceof HTMLInputElement)) return;

            if (input.dataset.managedByPegawai === 'true') {
                input.value = '';
                delete input.dataset.managedByPegawai;
            }

            input.readOnly = false;
            input.classList.remove('bg-gray-50', 'cursor-not-allowed');
        });

        identityHint?.classList.add('hidden');
    }
}

function initializeUserForm() {
    const roleSelect = document.getElementById('role_id');
    if (roleSelect instanceof HTMLSelectElement && roleSelect.value) {
        handleRoleChange(roleSelect.value);
    }

    const pegawaiSelect = document.getElementById('pegawai_id');
    if (pegawaiSelect instanceof HTMLSelectElement && pegawaiSelect.value) {
        handlePegawaiChange(pegawaiSelect);
    }
}

window.handleRoleChange = handleRoleChange;
window.handlePegawaiChange = handlePegawaiChange;

document.addEventListener('DOMContentLoaded', initializeUserForm);

function formatRupiahNumber(value) {
    return new Intl.NumberFormat('id-ID').format(value);
}

function updateInstallmentRemaining(input, initialRemaining, setoranDisplayId, remainingDisplayId) {
    if (!(input instanceof HTMLInputElement)) return;

    const rawValue = input.value.replace(/\D/g, '');
    const installmentAmount = rawValue ? Number.parseInt(rawValue, 10) : 0;
    input.value = rawValue ? formatRupiahNumber(installmentAmount) : '';

    const setoranDisplay = document.getElementById(setoranDisplayId);
    const remainingDisplay = document.getElementById(remainingDisplayId);

    if (setoranDisplay) {
        setoranDisplay.textContent = 'Rp ' + formatRupiahNumber(installmentAmount);
    }

    if (remainingDisplay) {
        const remainingAmount = initialRemaining - installmentAmount;
        if (installmentAmount === 0) {
            remainingDisplay.className = 'font-bold text-gray-900';
            remainingDisplay.textContent = 'Rp ' + formatRupiahNumber(initialRemaining);
        } else if (remainingAmount <= 0) {
            remainingDisplay.className = 'font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200';
            remainingDisplay.textContent = 'LUNAS (Sisa Rp 0)';
        } else {
            remainingDisplay.className = 'font-bold text-orange-600';
            remainingDisplay.textContent = 'Rp ' + formatRupiahNumber(remainingAmount);
        }
    }
}

function escapeHtmlText(value) {
    const element = document.createElement('span');
    element.textContent = value == null ? '' : String(value);
    return element.innerHTML;
}

function formatRupiahInput(input) {
    if (!(input instanceof HTMLInputElement)) return;

    const rawValue = input.value.replace(/\D/g, '');
    input.value = rawValue
        ? formatRupiahNumber(Number.parseInt(rawValue, 10))
        : '';
}

window.formatRupiahInput = formatRupiahInput;
window.formatRupiahLive = formatRupiahInput;
window.formatRupiahNumber = formatRupiahNumber;
window.updateInstallmentRemaining = updateInstallmentRemaining;
window.escapeHtmlText = escapeHtmlText;

function updateSptFileName(input) {
    if (!(input instanceof HTMLInputElement) || !input.files?.[0]) return;

    const display = document.getElementById(input.dataset.fileDisplay || 'spt-file-display');
    if (!display) return;

    const file = input.files[0];
    const sizeInMegabytes = (file.size / 1024 / 1024).toFixed(2);
    const prefix = input.dataset.fileLabelPrefix || 'Berkas SPT: ';
    const displayClass = input.dataset.fileDisplayClass;

    display.textContent = `${prefix}${file.name} (${sizeInMegabytes} MB)`;
    if (displayClass) display.className = displayClass;
}

function filterOpdOptions(input, lhpIndex = null) {
    if (!(input instanceof HTMLInputElement)) return;

    const itemSelector = input.dataset.filterItems
        || (lhpIndex !== null && lhpIndex !== ''
            ? `.opd-item-${CSS.escape(String(lhpIndex))}`
            : '.opd-item');
    const container = input.closest('.lhp-card, [data-opd-filter-scope]') || document;
    const query = input.value.trim().toLowerCase();

    container.querySelectorAll(itemSelector).forEach(item => {
        const searchData = (item.dataset.search || '').toLowerCase();
        item.style.display = searchData.includes(query) ? '' : 'none';
    });
}

function filterModalOpdOptions(input) {
    if (input instanceof HTMLInputElement) {
        input.dataset.filterItems = '.modal-opd-item';
    }

    filterOpdOptions(input);
}

function updateOpdSelection(inputOrIndex) {
    if (inputOrIndex instanceof HTMLInputElement) {
        const card = inputOrIndex.closest('.lhp-card');
        if (!card) return;

        const checkedCheckboxes = Array.from(card.querySelectorAll('.opd-checkbox:checked'));
        const countBadge = card.querySelector('.selected-opd-count');
        const chipsContainer = card.querySelector('.selected-opd-chips');

        if (countBadge) {
            countBadge.textContent = `${checkedCheckboxes.length} OPD Terpilih`;
            countBadge.className = checkedCheckboxes.length > 0
                ? 'selected-opd-count text-xs font-bold text-emerald-700 bg-emerald-100 px-2.5 py-0.5 rounded-full'
                : 'selected-opd-count text-xs font-bold text-primary-700 bg-primary-100 px-2.5 py-0.5 rounded-full';
        }

        if (!chipsContainer) return;

        if (checkedCheckboxes.length === 0) {
            const placeholder = document.createElement('span');
            placeholder.className = 'empty-opd-placeholder text-xs text-gray-400 italic';
            placeholder.textContent = 'Belum ada OPD yang dipilih (centang minimal 1 OPD di atas)';
            chipsContainer.replaceChildren(placeholder);
            return;
        }

        const chips = checkedCheckboxes.map(checkbox => {
            const code = checkbox.dataset.code || '';
            const chip = document.createElement('span');

            if (chipsContainer.dataset.opdChipMode === 'removable') {
                chip.className = 'inline-flex items-center gap-1.5 rounded-lg bg-primary-50 border border-primary-200 px-2.5 py-1 text-xs font-bold text-primary-900 shadow-2xs';

                const label = document.createElement('span');
                label.textContent = `[${code}] ${checkbox.dataset.name || ''}`;

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.dataset.opdId = checkbox.value;
                removeButton.className = 'text-primary-400 hover:text-red-600 cursor-pointer font-bold text-sm leading-none ml-1';
                removeButton.title = 'Hapus OPD terpilih';
                removeButton.setAttribute('aria-label', `Hapus OPD ${checkbox.dataset.name || code}`);
                removeButton.textContent = '✕';
                removeButton.addEventListener('click', () => removeOpdChip(removeButton));

                chip.append(label, removeButton);
                return chip;
            }

            chip.className = 'inline-flex items-center gap-1 rounded-lg bg-primary-600 px-2.5 py-1 text-xs font-bold text-white shadow-2xs';
            chip.textContent = code;

            return chip;
        });

        chipsContainer.replaceChildren(...chips);
        return;
    }

    const lhpIndex = String(inputOrIndex ?? '');
    if (!/^[\w-]+$/.test(lhpIndex)) return;

    const checkedCheckboxes = document.querySelectorAll(`.opd-cb-${CSS.escape(lhpIndex)}:checked`);
    const badge = document.getElementById(`opd-count-${lhpIndex}`);
    if (!badge) return;

    badge.textContent = `${checkedCheckboxes.length} OPD Terpilih`;
    badge.className = checkedCheckboxes.length > 0
        ? 'text-xs font-bold text-emerald-700 bg-emerald-100 px-2.5 py-0.5 rounded-full'
        : 'text-xs font-bold text-primary-700 bg-primary-100 px-2.5 py-0.5 rounded-full';
}

function removeOpdChip(button) {
    if (!(button instanceof HTMLButtonElement)) return;

    const card = button.closest('.lhp-card');
    const checkbox = Array.from(card?.querySelectorAll('.opd-checkbox') || [])
        .find(item => item.value === button.dataset.opdId);
    if (!checkbox) return;

    checkbox.checked = false;
    updateOpdSelection(checkbox);
}

function syncLhpOptionsToTindakLanjut() {
    const options = Array.from(document.querySelectorAll('.lhp-card'), (card, index) => {
        const number = index + 1;
        const reportNumber = card.querySelector('.input-nomor-lhp')?.value.trim() || `LHP #${number}`;
        const reportTitle = card.querySelector('.input-judul-lhp')?.value.trim() || '';
        const titleEllipsis = document.querySelector('#unified-lhp-form')?.dataset.lhpTitleEllipsis === 'true'
            ? '...'
            : '';
        const titleSuffix = reportTitle ? ` - ${reportTitle.substring(0, 30)}${titleEllipsis}` : '';

        return {
            value: String(index),
            label: `LHP #${number}: ${reportNumber}${titleSuffix}`,
        };
    });

    document.querySelectorAll('.select-target-lhp').forEach(select => {
        const currentValue = select.value;
        const optionElements = options.map(optionData => {
            const option = document.createElement('option');
            option.value = optionData.value;
            option.textContent = optionData.label;
            option.selected = optionData.value === currentValue;

            return option;
        });

        select.replaceChildren(...optionElements);
    });
}

function updateDynamicCardList(container, options) {
    if (!container || typeof container.querySelectorAll !== 'function') return;

    const cards = Array.from(container.querySelectorAll(options.cardSelector));
    cards.forEach((card, index) => {
        if (options.indexAttribute) {
            card.setAttribute(options.indexAttribute, String(index));
        }

        const badge = options.badgeSelector ? card.querySelector(options.badgeSelector) : null;
        if (badge && options.badgePrefix) {
            badge.textContent = `${options.badgePrefix} #${index + 1}`;
        }

        const removeButton = options.removeButtonSelector
            ? card.querySelector(options.removeButtonSelector)
            : null;
        removeButton?.classList.toggle('hidden', cards.length <= 1);

        if (options.fieldNamePrefix) {
            const fieldNameStart = `${options.fieldNamePrefix}[`;
            card.querySelectorAll('input, select, textarea').forEach(field => {
                const name = field.getAttribute('name');
                if (!name?.startsWith(fieldNameStart)) return;

                const closingBracketIndex = name.indexOf(']', fieldNameStart.length);
                if (closingBracketIndex === -1) return;

                field.setAttribute(
                    'name',
                    `${fieldNameStart}${index}${name.slice(closingBracketIndex + 1)}`
                );
            });
        }
    });
}

function announceDynamicChange(container, message) {
    if (!(container instanceof HTMLElement)) return;

    let announcer = Array.from(container.children).find(child => child.hasAttribute('data-dynamic-announcer'));
    if (!announcer) {
        announcer = document.createElement('span');
        announcer.className = 'sr-only';
        announcer.setAttribute('role', 'status');
        announcer.setAttribute('aria-live', 'polite');
        announcer.setAttribute('aria-atomic', 'true');
        announcer.dataset.dynamicAnnouncer = 'true';
        container.appendChild(announcer);
    }

    announcer.textContent = '';
    requestAnimationFrame(() => {
        announcer.textContent = message;
    });
}

function focusFirstDynamicField(container) {
    if (!(container instanceof HTMLElement)) return;

    const field = Array.from(container.querySelectorAll(
        'input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])'
    )).find(element => {
        const styles = window.getComputedStyle(element);
        return element.getClientRects().length > 0
            && styles.visibility !== 'hidden'
            && styles.display !== 'none'
            && !element.closest('[aria-hidden="true"], [inert]');
    });

    if (!field) return;

    container.scrollIntoView({ behavior: 'smooth', block: 'center' });
    requestAnimationFrame(() => field.focus({ preventScroll: true }));
}

function updateNumberedItems(list, options) {
    if (!list || typeof list.querySelectorAll !== 'function') return [];

    const items = Array.from(list.querySelectorAll(options.itemSelector));
    items.forEach((item, index) => {
        if (options.indexAttribute) {
            item.setAttribute(options.indexAttribute, String(index));
        }

        const label = options.labelSelector ? item.querySelector(options.labelSelector) : null;
        if (label && options.labelPrefix) {
            label.textContent = `${options.labelPrefix} #${index + 1}`;
        }

        const removeButton = options.removeButtonSelector
            ? item.querySelector(options.removeButtonSelector)
            : null;
        removeButton?.classList.toggle('hidden', items.length <= 1);
    });

    return items;
}

function updateLhpFindingItems(card, type) {
    if (!(card instanceof HTMLElement)) return;

    const configurations = {
        administrasi: {
            list: '.list-temuan-administrasi',
            item: '.item-temuan-adm',
            indexAttribute: 'data-adm-idx',
            label: '.adm-label',
            removeButton: '.btn-remove-adm',
            labelPrefix: 'Temuan Administrasi',
            collection: 'temuans_administrasi',
            fields: [
                { selector: '.input-adm-uraian', name: 'uraian', accessibleName: 'Uraian temuan administrasi' },
                { selector: '.input-adm-penyebab', name: 'penyebab', accessibleName: 'Penyebab temuan administrasi' },
                { selector: '.input-adm-rekomendasi-item', name: 'rekomendasi_items', accessibleName: 'Poin rekomendasi administrasi', multiple: true, array: true },
            ],
        },
        kerugian: {
            list: '.list-temuan-kerugian',
            item: '.item-temuan-kg',
            indexAttribute: 'data-kg-idx',
            label: '.kg-label',
            removeButton: '.btn-remove-kg',
            labelPrefix: 'Temuan Kerugian',
            collection: 'temuans_kerugian',
            fields: [
                { selector: '.input-kg-uraian', name: 'uraian', accessibleName: 'Uraian temuan kerugian' },
                { selector: '.input-kg-kategori', name: 'kategori_kerugian', accessibleName: 'Sumber kerugian' },
                { selector: '.input-kg-jenis', name: 'jenis_kerugian', multiple: true },
                { selector: '.input-kg-nilai', name: 'nilai_kerugian', accessibleName: 'Nilai kerugian' },
                { selector: '.input-kg-penyebab', name: 'penyebab', accessibleName: 'Penyebab temuan kerugian' },
                { selector: '.input-kg-rekomendasi', name: 'rekomendasi', accessibleName: 'Rekomendasi penyetoran' },
            ],
        },
    };

    const config = configurations[type];
    const list = config ? card.querySelector(config.list) : null;
    if (!config || !list) return;

    const lhpIndex = card.dataset.lhpIndex || '0';
    const lhpNumber = Number.isInteger(Number(lhpIndex)) ? Number(lhpIndex) + 1 : 1;
    const items = updateNumberedItems(list, {
        itemSelector: config.item,
        indexAttribute: config.indexAttribute,
        labelSelector: config.label,
        labelPrefix: config.labelPrefix,
        removeButtonSelector: config.removeButton,
    });

    items.forEach((item, index) => {
        config.fields.forEach(field => {
            const elements = field.multiple
                ? item.querySelectorAll(field.selector)
                : [item.querySelector(field.selector)].filter(Boolean);

            elements.forEach((element, elementIndex) => {
                const arraySuffix = field.array ? '[]' : '';
                element.setAttribute(
                    'name',
                    `lhps[${lhpIndex}][${config.collection}][${index}][${field.name}]${arraySuffix}`
                );

                if (field.accessibleName) {
                    const pointLabel = field.multiple ? `, poin rekomendasi ${elementIndex + 1}` : '';
                    element.setAttribute(
                        'aria-label',
                        `LHP ${lhpNumber}, ${config.labelPrefix} ${index + 1}, ${field.accessibleName}${pointLabel}`
                    );
                }
            });
        });
    });
}

function handleVerifikasiChange(status) {
    const alasanSection = document.getElementById('alasanKhususSection');
    const alasanInput = document.getElementById('alasan_khusus');
    if (!alasanSection || !alasanInput) return;

    const requiresReason = status === 'TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH';
    alasanSection.classList.toggle('hidden', !requiresReason);
    alasanInput.required = requiresReason;
}

function toggleModal(modalId) {
    document.getElementById(modalId)?.classList.toggle('hidden');
}

function closeVerifikasiModal() {
    document.getElementById('verifikasiModal')?.classList.add('hidden');
}

function openVerifikasiModal(temuanId, tlId, currentStatus, currentNotes, temuanTitle = null, setoranFormatted = null) {
    const form = document.getElementById('verifikasiForm');
    const modal = document.getElementById('verifikasiModal');
    if (!form || !modal) return;

    form.action = `/temuan/${temuanId}/tindak-lanjut/${tlId}/verifikasi`;

    const title = document.getElementById('modalVerifTemuanTitle');
    if (title) title.textContent = temuanTitle || 'Temuan';

    const setoranInfo = document.getElementById('modalVerifSetoranInfo');
    if (setoranInfo) {
        setoranInfo.textContent = setoranFormatted ? `Setoran Dilaporkan: ${setoranFormatted}` : '';
    }

    document.getElementsByName('status_verifikasi').forEach(radio => {
        if (radio.value === currentStatus) radio.checked = true;
    });

    const notes = document.getElementById('catatan_verifikasi');
    if (notes) notes.value = currentNotes || '';

    handleVerifikasiChange(currentStatus);
    modal.classList.remove('hidden');
}

function openInputTlForTemuan(temuanId, judul, jenis, uraian, rekomendasi, lhpNo, opdName, sumber, sptNo) {
    const form = document.getElementById('inputTlForm');
    const modal = document.getElementById('inputTlModal');
    const headerTitle = document.getElementById('modalTlHeaderTitle');
    const badge = document.getElementById('modalTemuanBadge');
    const setoranSection = document.getElementById('modalSetoranSection');

    if (!form || !modal || !headerTitle || !badge || !setoranSection) return;

    form.action = `/temuan/${temuanId}/tindak-lanjut`;
    headerTitle.textContent = `Tindak Lanjut: ${judul}`;

    const isLossFinding = jenis === 'kerugian';
    badge.className = isLossFinding
        ? 'px-2.5 py-0.5 rounded-lg text-xs font-bold uppercase tracking-wider bg-amber-100 text-amber-900 border border-amber-200'
        : 'px-2.5 py-0.5 rounded-lg text-xs font-bold uppercase tracking-wider bg-primary-100 text-primary-900 border border-primary-200';
    badge.textContent = isLossFinding ? 'Temuan Kerugian Daerah' : 'Temuan Administrasi';
    setoranSection.classList.toggle('hidden', !isLossFinding);

    const sourceBadge = document.getElementById('modalTemuanSumberBadge');
    if (sourceBadge) {
        sourceBadge.textContent = sumber || '-';
        const sourceClasses = {
            BPK: 'bg-emerald-100 text-emerald-800 border border-emerald-200',
            BPKP: 'bg-blue-100 text-blue-800 border border-blue-200',
            ITJEN: 'bg-amber-100 text-amber-800 border border-amber-200',
            Inspektorat: 'bg-primary-100 text-primary-800 border border-primary-200',
        };
        const sourceColor = sourceClasses[sumber] || 'bg-gray-100 text-gray-800 border border-gray-200';
        sourceBadge.className = `px-2 py-0.5 rounded-md text-xs font-bold uppercase tracking-wider ${sourceColor}`;
    }

    const fieldValues = {
        modalTemuanLhpNo: lhpNo || '-',
        modalTemuanSptNo: sptNo || '-',
        modalTemuanOpd: opdName || '-',
        modalTemuanUraian: uraian || judul || '-',
        modalTemuanRekomendasi: rekomendasi || '-',
    };
    Object.entries(fieldValues).forEach(([id, value]) => {
        const field = document.getElementById(id);
        if (field) field.textContent = value;
    });

    modal.classList.remove('hidden');
}

function closeInputTlModal() {
    document.getElementById('inputTlModal')?.classList.add('hidden');
}

function toggleUnsurTemuan(checkboxOrLhpIndex, lhpIndexOrType, type = null) {
    let checkbox;
    let container;
    let targetType;

    if (checkboxOrLhpIndex instanceof HTMLInputElement) {
        const card = checkboxOrLhpIndex.closest('.lhp-card');
        if (!card) return;

        checkbox = checkboxOrLhpIndex;
        targetType = type;
        const containerClass = targetType === 'administrasi'
            ? '.container-temuan-administrasi'
            : '.container-temuan-kerugian';
        container = card.querySelector(containerClass);
    } else {
        const lhpIndex = String(checkboxOrLhpIndex);
        targetType = lhpIndexOrType;
        const suffix = targetType === 'administrasi' ? 'adm' : 'kg';
        checkbox = document.getElementById(`unsur_${suffix}_cb_${lhpIndex}`);
        container = document.getElementById(`container_${suffix}_${lhpIndex}`);
    }

    if (!checkbox || !container) return;
    container.classList.toggle('hidden', !checkbox.checked);
}

function addRecommendationPoint(button, containerSelector, removeHandler, updateHandler) {
    if (!(button instanceof HTMLButtonElement)) return;

    const container = button.closest(containerSelector);
    const list = container?.querySelector('.list-rekomendasi-points');
    if (!list) return;

    const nextNumber = list.querySelectorAll('.item-rek-point').length + 1;
    const existingInput = list.querySelector('.input-adm-rekomendasi-item');
    const inputName = existingInput?.name || 'rekomendasi_items[]';
    const point = document.createElement('div');

    point.className = 'item-rek-point flex items-start gap-1.5 bg-primary-50/30 p-2 rounded-lg border border-primary-100/80 animate-fade-in';
    point.innerHTML = `
        <span class="rek-point-num text-xs font-bold bg-primary-600 text-white w-5 h-5 flex items-center justify-center rounded shrink-0 mt-0.5 shadow-2xs"></span>
        <div class="flex-1">
            <textarea rows="2"
                      aria-label="Uraian butir rekomendasi ${nextNumber}"
                      class="input-adm-rekomendasi-item w-full rounded-md border border-gray-200 bg-white px-2.5 py-1.5 text-xs text-gray-800 focus:border-primary-500 focus:outline-none"
                      placeholder="Butir rekomendasi berikutnya..."></textarea>
        </div>
        <button type="button" class="btn-remove-rek-point text-gray-400 hover:text-red-600 p-1 cursor-pointer mt-0.5" title="Hapus poin rekomendasi">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
    `;

    point.querySelector('.rek-point-num').textContent = String(nextNumber);
    point.querySelector('textarea').name = inputName;
    point.querySelector('.btn-remove-rek-point').addEventListener('click', event => {
        const remove = window[removeHandler];
        if (typeof remove === 'function') remove(event.currentTarget);
    });

    list.appendChild(point);
    updateHandler(list);
    announceDynamicChange(list, `Butir rekomendasi ${nextNumber} ditambahkan.`);
    focusFirstDynamicField(point);
}

function removeRecommendationPoint(button, updateHandler) {
    const point = button.closest('.item-rek-point');
    const list = button.closest('.list-rekomendasi-points');
    if (!point || !list) return;

    const points = Array.from(list.querySelectorAll('.item-rek-point'));
    if (points.length <= 1) return;
    const pointIndex = points.indexOf(point);

    point.remove();
    updateHandler(list);
    announceDynamicChange(list, `Butir rekomendasi ${pointIndex + 1} dihapus.`);
    const remainingPoints = points.filter(item => item !== point);
    focusFirstDynamicField(remainingPoints[Math.min(pointIndex, remainingPoints.length - 1)]);
}

function updateRecommendationPointBadges(list) {
    if (!list) return;

    const points = list.querySelectorAll('.item-rek-point');
    points.forEach((point, index) => {
        const number = point.querySelector('.rek-point-num');
        const input = point.querySelector('.input-adm-rekomendasi-item');
        const removeButton = point.querySelector('.btn-remove-rek-point');

        if (number) number.textContent = String(index + 1);
        if (input) {
            const card = input.closest('.lhp-card');
            const finding = input.closest('.item-temuan-adm');
            const lhpNumber = card ? Number(card.dataset.lhpIndex || 0) + 1 : null;
            const findingNumber = finding ? Number(finding.dataset.admIdx || 0) + 1 : null;
            const accessibleName = card && finding
                ? `LHP ${lhpNumber}, temuan administrasi ${findingNumber}, poin rekomendasi ${index + 1}`
                : `Uraian butir rekomendasi ${index + 1}`;

            input.setAttribute('aria-label', accessibleName);
        }
        removeButton?.classList.toggle('hidden', points.length <= 1);
    });
}

window.updateSptFileName = updateSptFileName;
window.filterOpdOptions = filterOpdOptions;
window.filterModalOpdOptions = filterModalOpdOptions;
window.updateOpdSelection = updateOpdSelection;
window.removeOpdChip = removeOpdChip;
window.syncLhpOptionsToTindakLanjut = syncLhpOptionsToTindakLanjut;
window.updateDynamicCardList = updateDynamicCardList;
window.updateNumberedItems = updateNumberedItems;
window.updateLhpFindingItems = updateLhpFindingItems;
window.announceDynamicChange = announceDynamicChange;
window.focusFirstDynamicField = focusFirstDynamicField;
window.handleVerifikasiChange = handleVerifikasiChange;
window.toggleModal = toggleModal;
window.closeVerifikasiModal = closeVerifikasiModal;
window.openVerifikasiModal = openVerifikasiModal;
window.openInputTlForTemuan = openInputTlForTemuan;
window.closeInputTlModal = closeInputTlModal;
window.toggleUnsurTemuan = toggleUnsurTemuan;
window.addRecommendationPoint = addRecommendationPoint;
window.removeRecommendationPoint = removeRecommendationPoint;
window.updateRecommendationPointBadges = updateRecommendationPointBadges;

function toggleSection(id, show) {
    document.getElementById(id)?.classList.toggle('hidden', !show);
}

function handleUnsurAdministrasiToggle(checked) {
    toggleSection('admin-details', checked);

    const singleWrapper = document.getElementById('wrapper-rekomendasi-single');
    const stagedWrapper = document.getElementById('wrapper-rekomendasi-bertahap');
    const singleInput = document.getElementById('rekomendasi');
    const recommendationList = document.getElementById('list-rekomendasi-items');

    singleWrapper?.classList.toggle('hidden', checked);
    stagedWrapper?.classList.toggle('hidden', !checked);

    if (checked && singleInput && recommendationList) {
        const firstItem = recommendationList.querySelector('.input-rek-item');
        if (firstItem && !firstItem.value.trim() && singleInput.value.trim()) {
            firstItem.value = singleInput.value.trim();
        }
    }
}

function addRekomendasiItem() {
    const list = document.getElementById('list-rekomendasi-items');
    if (!list) return;

    const nextIndex = list.querySelectorAll('.item-rekomendasi').length;
    const nextNumber = nextIndex + 1;
    const idField = list.dataset.includeRecommendationIds === 'true'
        ? '<input type="hidden" name="rekomendasi_ids[]" value="">'
        : '';
    const item = document.createElement('div');

    item.className = 'item-rekomendasi flex items-start gap-2.5 rounded-xl border border-primary-200/70 bg-primary-50/40 p-3.5 transition';
    item.dataset.rekIdx = nextIndex;
    item.innerHTML = `
        ${idField}
        <span class="badge-rek-number mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-primary-600 text-xs font-bold text-white shadow-2xs">${nextNumber}</span>
        <div class="flex-1">
            <label for="rekomendasi-item-${nextIndex}" class="mb-1 block text-xs font-bold text-gray-700">
                Uraian Rekomendasi <span class="text-red-500">*</span>
            </label>
            <textarea id="rekomendasi-item-${nextIndex}" name="rekomendasi_items[]" rows="2"
                      class="input-rek-item w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-gray-800 focus:border-primary-500 focus:outline-none"
                      placeholder="Tuliskan butir rekomendasi spesifik perbaikan administrasi..."></textarea>
        </div>
        <button type="button" onclick="removeRekomendasiItem(this)"
                class="btn-remove-rek mt-1 rounded-lg p-1.5 text-gray-400 transition hover:bg-red-50 hover:text-red-600 cursor-pointer"
                title="Hapus poin rekomendasi ini">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </button>
    `;

    list.appendChild(item);
    updateRekomendasiItemBadges();
    announceDynamicChange(list, `Butir rekomendasi ${nextNumber} ditambahkan.`);
    focusFirstDynamicField(item);
}

function removeRekomendasiItem(button) {
    const item = button.closest('.item-rekomendasi');
    if (!item || item.classList.contains('locked-item')) return;

    const list = item.closest('#list-rekomendasi-items');
    if (!list) return;

    const items = Array.from(list.querySelectorAll('.item-rekomendasi'));
    if (items.length <= 1) return;
    const itemIndex = items.indexOf(item);

    item.remove();
    updateRekomendasiItemBadges();
    announceDynamicChange(list, `Butir rekomendasi ${itemIndex + 1} dihapus.`);
    const remainingItems = items.filter(remainingItem => remainingItem !== item);
    focusFirstDynamicField(remainingItems[Math.min(itemIndex, remainingItems.length - 1)]);
}

function updateRekomendasiItemBadges() {
    const list = document.getElementById('list-rekomendasi-items');
    if (!list) return;

    const items = list.querySelectorAll('.item-rekomendasi');
    items.forEach((item, index) => {
        const badge = item.querySelector('.badge-rek-number');
        const removeButton = item.querySelector('.btn-remove-rek');

        if (badge) badge.textContent = String(index + 1);
        if (removeButton && !item.classList.contains('locked-item')) {
            removeButton.classList.toggle('hidden', items.length <= 1);
        }
    });
}

window.toggleSection = toggleSection;
window.handleUnsurAdministrasiToggle = handleUnsurAdministrasiToggle;
window.addRekomendasiItem = addRekomendasiItem;
window.removeRekomendasiItem = removeRekomendasiItem;
window.updateRekomendasiItemBadges = updateRekomendasiItemBadges;

function initializeModalAccessibility() {
            const focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
            const lastFocusedElements = new WeakMap();

            const getModals = () => Array.from(document.querySelectorAll('.fixed.inset-0.z-50'));
            const getFocusableElements = container => Array.from(container.querySelectorAll(focusableSelector)).filter(element => {
                const styles = window.getComputedStyle(element);

                return element.getClientRects().length > 0
                    && styles.visibility !== 'hidden'
                    && styles.display !== 'none'
                    && !element.closest('[aria-hidden="true"], [inert]');
            });
            const isOpen = modal => !modal.classList.contains('hidden');
            const closeModal = modal => modal.classList.add('hidden');
            const syncPageScroll = () => {
                document.body.classList.toggle('overflow-hidden', getModals().some(isOpen));
            };

            getModals().forEach(function(modal) {
                modal.setAttribute('role', 'dialog');
                modal.setAttribute('aria-modal', 'true');
                modal.setAttribute('aria-hidden', String(!isOpen(modal)));
                modal.setAttribute('tabindex', '-1');

                const title = modal.querySelector('h2, h3, [id$="-title"], [id$="Title"]');
                if (title) {
                    if (!title.id) title.id = `${modal.id || 'dialog'}-title`;
                    modal.setAttribute('aria-labelledby', title.id);
                } else if (!modal.hasAttribute('aria-label')) {
                    modal.setAttribute('aria-label', 'Dialog');
                }

                modal.querySelectorAll('button').forEach(function(button) {
                    if (button.textContent.trim() === '✕') button.setAttribute('aria-label', 'Tutup dialog');
                });
            });

            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    const modal = mutation.target;
                    const nowOpen = isOpen(modal);
                    const wasOpen = modal.getAttribute('aria-hidden') === 'false';
                    modal.setAttribute('aria-hidden', String(!nowOpen));
                    syncPageScroll();

                    if (nowOpen && !wasOpen) {
                        lastFocusedElements.set(modal, document.activeElement);
                        setTimeout(function() {
                            const focusable = getFocusableElements(modal);
                            const initialFocus = focusable.find(element => element.hasAttribute('autofocus'))
                                || focusable.find(element => element.matches('input:not([type="hidden"]):not([type="file"]), select, textarea'))
                                || focusable[0]
                                || modal;
                            initialFocus.focus();
                        }, 0);
                    }

                    if (!nowOpen && wasOpen) {
                        const opener = lastFocusedElements.get(modal);
                        if (opener instanceof HTMLElement && document.body.contains(opener)) opener.focus();
                    }
                });
            });

            getModals().forEach(modal => observer.observe(modal, { attributes: true, attributeFilter: ['class'] }));
            syncPageScroll();

            document.addEventListener('click', function(event) {
                const modal = event.target instanceof Element ? event.target.closest('.fixed.inset-0.z-50') : null;
                if (modal && event.target === modal) closeModal(modal);
            });

            document.addEventListener('keydown', function(event) {
                // Modal terakhir berada paling depan ketika beberapa dialog dibuka berurutan.
                const openModal = getModals().reverse().find(isOpen);

                if (event.key === 'Escape') {
                    if (openModal) {
                        event.preventDefault();
                        closeModal(openModal);
                        return;
                    }

                    const userMenu = document.getElementById('user-menu');
                    if (userMenu && !userMenu.classList.contains('hidden')) {
                        event.preventDefault();
                        toggleUserMenu(false);
                        document.getElementById('user-menu-toggle')?.focus();
                        return;
                    }

                    const sidebar = document.getElementById('sidebar');
                    if (
                        window.matchMedia('(max-width: 1023px)').matches
                        && sidebar
                        && !sidebar.classList.contains('-translate-x-full')
                    ) {
                        event.preventDefault();
                        toggleSidebar(false);
                        return;
                    }
                }

                if (event.key !== 'Tab') return;

                if (openModal) {
                    const focusable = getFocusableElements(openModal);
                    if (focusable.length === 0) {
                        event.preventDefault();
                        openModal.focus();
                        return;
                    }

                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];
                    if (!openModal.contains(document.activeElement)) {
                        event.preventDefault();
                        (event.shiftKey ? last : first).focus();
                        return;
                    }

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                    return;
                }

                const sidebar = document.getElementById('sidebar');
                if (
                    !sidebar
                    || window.matchMedia('(min-width: 1024px)').matches
                    || sidebar.classList.contains('-translate-x-full')
                ) {
                    return;
                }

                const sidebarFocusable = Array.from(sidebar.querySelectorAll(
                    'a[href], button:not([disabled]), summary, [tabindex]:not([tabindex="-1"])'
                )).filter(element => element.getClientRects().length > 0);
                if (sidebarFocusable.length === 0) return;

                const first = sidebarFocusable[0];
                const last = sidebarFocusable[sidebarFocusable.length - 1];
                const focusIsOutsideSidebar = !sidebar.contains(document.activeElement);
                if (focusIsOutsideSidebar) {
                    event.preventDefault();
                    (event.shiftKey ? last : first).focus();
                } else if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            });
        }

        // Tutup otomatis pesan flash sukses setelah 8 detik.
        // Timer berhenti saat kursor berada di atas pesan, sehingga pesan panjang
        // tidak hilang sebelum selesai dibaca. Ringkasan error validasi tidak
        // pernah ditutup otomatis — pengguna harus mengatasi masalahnya lebih dulu.
        document.addEventListener('DOMContentLoaded', function() {
            initializeModalAccessibility();

            const addAccessibleNames = function(root) {
                if (!(root instanceof Element)) return;

                const elements = [root, ...root.querySelectorAll('button[title], svg')];

                elements.forEach(function(element) {
                    if (element.matches('button[title]:not([aria-label])') && !element.textContent.trim()) {
                        element.setAttribute('aria-label', element.title);
                    }

                    if (
                        element instanceof SVGElement
                        && !element.hasAttribute('aria-label')
                        && !element.hasAttribute('role')
                        && !element.hasAttribute('aria-hidden')
                        && !element.querySelector('title')
                    ) {
                        element.setAttribute('aria-hidden', 'true');
                    }
                });

                const controlSelector = 'input:not([type="hidden"]), select, textarea';
                const controls = [
                    ...(root.matches(controlSelector) ? [root] : []),
                    ...root.querySelectorAll(controlSelector),
                ];
                const commonFieldLabels = {
                    ada_administrasi: 'Ada temuan administrasi',
                    ada_kerugian: 'Ada temuan kerugian',
                    catatan: 'Catatan',
                    dokumen_bukti: 'Dokumen bukti tindak lanjut',
                    dokumen_lhp: 'Dokumen LHP',
                    dokumen_setor: 'Dokumen bukti setoran',
                    jumlah_setor: 'Jumlah setoran',
                    kategori_kerugian: 'Kategori kerugian',
                    lhp_index: 'Dokumen LHP terkait',
                    nama: 'Nama',
                    nomor_bukti_setor: 'Nomor bukti setoran',
                    nomor_lhp: 'Nomor LHP',
                    nomor_spt: 'Nomor SPT',
                    opd_ids: 'OPD target',
                    pegawai_id: 'Pegawai pemeriksa',
                    penyebab: 'Penyebab temuan',
                    rekomendasi: 'Rekomendasi',
                    rekomendasi_items: 'Butir rekomendasi',
                    status_verifikasi: 'Hasil verifikasi',
                    tanggal_lhp: 'Tanggal LHP',
                    tanggal_setor: 'Tanggal setoran',
                    tanggal_tindak_lanjut: 'Tanggal tindak lanjut',
                    temuan_type: 'Jenis temuan',
                    uraian: 'Uraian temuan',
                    uraian_tindak_lanjut: 'Uraian tindak lanjut',
                    nilai_kerugian: 'Nilai kerugian',
                };

                controls.forEach(function(control) {
                    if (control.labels?.length || control.hasAttribute('aria-label') || control.hasAttribute('aria-labelledby')) {
                        return;
                    }

                    let fieldLabel = null;
                    let ancestor = control.parentElement;
                    while (ancestor && ancestor !== document.body) {
                        const fieldControls = Array.from(ancestor.querySelectorAll(controlSelector));
                        const labels = Array.from(ancestor.querySelectorAll('label')).filter(label => label.textContent.trim());

                        if (fieldControls.length === 1 && fieldControls[0] === control && labels.length === 1) {
                            fieldLabel = labels[0];
                            break;
                        }

                        if (ancestor.matches('form')) break;
                        ancestor = ancestor.parentElement;
                    }

                    const nameSegments = (control.name || '').replace(/\[\d*\]/g, '').split(/[.\[\]]+/).filter(Boolean);
                    const fieldKey = nameSegments.at(-1) || '';
                    const fallbackLabel = commonFieldLabels[fieldKey]
                        || control.getAttribute('placeholder')
                        || (fieldKey ? fieldKey.replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase()) : 'Kolom isian');

                    if (fieldLabel) {
                        if (!control.id) {
                            const baseId = `idds-field-${fieldKey || 'input'}`.replace(/[^a-zA-Z0-9_-]/g, '-');
                            let uniqueId = baseId;
                            let suffix = 2;
                            while (document.getElementById(uniqueId)) {
                                uniqueId = `${baseId}-${suffix++}`;
                            }
                            control.id = uniqueId;
                        }
                        fieldLabel.htmlFor = control.id;
                        return;
                    }

                    const legend = control.closest('fieldset')?.querySelector('legend');
                    if (legend) {
                        if (!legend.id) legend.id = `idds-group-${Math.random().toString(36).slice(2)}`;
                        control.setAttribute('aria-labelledby', legend.id);
                    } else if (fallbackLabel) {
                        control.setAttribute('aria-label', fallbackLabel);
                    }
                });
            };

            addAccessibleNames(document.body);
            enhanceEmployeeSearchControls(document.body);
            new MutationObserver(function(records) {
                records.forEach(function(record) {
                    record.addedNodes.forEach(function(node) {
                        if (node instanceof Element) {
                            addAccessibleNames(node);
                            enhanceEmployeeSearchControls(node);
                        }
                    });
                });
            }).observe(document.body, { childList: true, subtree: true });

            const userMenuToggle = document.getElementById('user-menu-toggle');
            const userMenu = document.getElementById('user-menu');
            if (userMenuToggle && userMenu) {
                userMenuToggle.addEventListener('keydown', function(event) {
                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        toggleUserMenu(true);
                    }
                });

                userMenu.addEventListener('keydown', function(event) {
                    if (event.key === 'Tab') {
                        window.setTimeout(function() {
                            toggleUserMenu(false);
                        }, 0);
                    }
                });

                document.addEventListener('click', function(event) {
                    if (
                        !userMenu.classList.contains('hidden')
                        && !userMenu.contains(event.target)
                        && !userMenuToggle.contains(event.target)
                    ) {
                        toggleUserMenu(false);
                    }
                });
            }

            let previousDesktopSidebarMode = null;
            const syncSidebarAccessibility = function() {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebar-overlay');
                const toggle = document.getElementById('sidebar-toggle');
                if (!sidebar || !overlay || !toggle) return;

                const isDesktop = window.matchMedia('(min-width: 1024px)').matches;
                if (previousDesktopSidebarMode !== isDesktop) {
                    const focusWasInSidebar = sidebar.contains(document.activeElement);
                    sidebar.classList.toggle('-translate-x-full', !isDesktop);
                    previousDesktopSidebarMode = isDesktop;

                    if (!isDesktop && focusWasInSidebar) {
                        toggle.focus();
                    }
                }

                const isOpen = isDesktop || !sidebar.classList.contains('-translate-x-full');
                sidebar.setAttribute('aria-hidden', String(!isOpen));
                sidebar.inert = !isOpen;
                overlay.classList.toggle('hidden', !isOpen || isDesktop);
                toggle.setAttribute('aria-expanded', String(!isDesktop && isOpen));
            };

            syncSidebarAccessibility();
            window.addEventListener('resize', syncSidebarAccessibility);

            document.querySelectorAll('.sidebar-link').forEach(function(link) {
                if (link.pathname === window.location.pathname) link.setAttribute('aria-current', 'page');
            });

            document.querySelectorAll('.flash-message:not(#validation-error-summary)').forEach(function(el) {
                let timer = null;

                const start = function() {
                    timer = setTimeout(function() {
                        el.style.opacity = '0';
                        setTimeout(function() { el.remove(); }, 500);
                    }, 8000);
                };

                const stop = function() {
                    if (timer) {
                        clearTimeout(timer);
                        timer = null;
                    }
                };

                el.addEventListener('mouseenter', stop);
                el.addEventListener('mouseleave', start);
                el.addEventListener('focusin', stop);
                el.addEventListener('focusout', start);

                start();
            });
        });

        // Close navbar user dropdown when clicking outside
        document.addEventListener('click', function(e) {
            document.querySelectorAll('header .relative > div.absolute').forEach(function(dropdown) {
                const parent = dropdown.parentElement;
                if (parent && !parent.contains(e.target)) {
                    dropdown.classList.add('hidden');
                    document.getElementById('user-menu-toggle')?.setAttribute('aria-expanded', 'false');
                }
            });
        });

        /**
         * Tampilkan umpan balik submit dan cegah pengiriman ganda.
         * Form yang ingin dikecualikan dapat menambahkan data-no-loading.
         */
        document.addEventListener('submit', function(e) {
            const form = e.target;

            if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-loading')) {
                return;
            }

            if (e.defaultPrevented) {
                return;
            }

            if (form.dataset.submitting === 'true') {
                e.preventDefault();
                return;
            }

            const submitter = e.submitter || form.querySelector('[type="submit"]');
            if (!submitter || submitter.disabled) {
                return;
            }

            form.dataset.submitting = 'true';
            const originalHtml = submitter.innerHTML;
            const loadingText = submitter.getAttribute('data-loading-text') || 'Memproses...';
            let actionInput = null;

            // Tombol submit yang dinonaktifkan tidak ikut dikirim oleh browser.
            // Salin name/value-nya agar aksi seperti simpan draft tetap terjaga.
            if (submitter.name) {
                actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = submitter.name;
                actionInput.value = submitter.value;
                actionInput.dataset.submitAction = 'true';
                form.appendChild(actionInput);
            }

            const spinner = '<svg class="mr-1.5 inline-block h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">'
                + '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>'
                + '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>'
                + '</svg>';

            submitter.disabled = true;
            submitter.setAttribute('aria-busy', 'true');
            submitter.classList.add('opacity-60', 'cursor-not-allowed');
            submitter.innerHTML = spinner;
            const loadingLabel = document.createElement('span');
            loadingLabel.textContent = loadingText;
            submitter.appendChild(loadingLabel);

            // Kembalikan bila halaman tidak jadi berpindah (mis. validasi gagal)
            setTimeout(function() {
                if (document.body.contains(submitter)) {
                    submitter.disabled = false;
                    submitter.removeAttribute('aria-busy');
                    submitter.classList.remove('opacity-60', 'cursor-not-allowed');
                    submitter.innerHTML = originalHtml;
                    form.dataset.submitting = 'false';
                    actionInput?.remove();
                }
            }, 12000);
        });
