const form = document.querySelector('[data-storage-transaction-form]');

const normalize = (value) => String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLocaleLowerCase('id-ID')
    .trim();

const formatLocalizedNumber = (value, decimals = 3) => {
    const raw = String(value ?? '').trim();
    if (!raw) return '';

    const cleaned = raw.replace(/[^\d,]/g, '');
    if (!cleaned) return '';

    const [integerPart = '', decimalPart] = cleaned.split(',', 2);
    const integer = integerPart.replace(/^0+(?=\d)/, '') || (integerPart.includes('0') ? '0' : '');
    const grouped = integer.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    return decimalPart !== undefined ? `${grouped},${decimalPart.slice(0, decimals)}` : grouped;
};

const normalizeLocalizedNumber = (value) => String(value ?? '').trim().replaceAll('.', '');

const enhanceNumberInput = (input) => {
    if (input.dataset.numberEnhanced === 'true') return;
    input.dataset.numberEnhanced = 'true';
    if (input.value) {
        input.value = formatLocalizedNumber(input.value, Number(input.dataset.numberDecimals || 3));
    }
    input.addEventListener('input', () => {
        const decimals = Number(input.dataset.numberDecimals || 3);
        const cursorPosition = input.selectionStart ?? input.value.length;
        const rawBeforeCursor = input.value.slice(0, cursorPosition);
        const significantBefore = rawBeforeCursor.replace(/[^\d,]/g, '').length;

        const formatted = formatLocalizedNumber(input.value, decimals);
        input.value = formatted;

        let newPos = 0;
        let count = 0;
        for (let i = 0; i < formatted.length; i++) {
            if (/[\d,]/.test(formatted[i])) {
                count++;
            }
            if (count >= significantBefore) {
                newPos = i + 1;
                break;
            }
        }
        if (significantBefore === 0) {
            newPos = 0;
        } else if (count < significantBefore) {
            newPos = formatted.length;
        }
        input.setSelectionRange(newPos, newPos);
    });
};

const enhanceNumberInputs = (root = document) => {
    root.querySelectorAll('[data-number-format]').forEach(enhanceNumberInput);
};

const closePicker = (picker) => {
    const trigger = picker.querySelector('[data-storage-picker-trigger]');
    const panel = picker.querySelector('[data-storage-picker-panel]');

    if (!trigger || !panel) return;
    trigger.setAttribute('aria-expanded', 'false');
    panel.hidden = true;
    picker.classList.remove('is-open');
    picker.closest('[data-storage-line]')?.classList.remove('has-open-picker');
};

const enhanceItemPicker = (picker) => {
    if (picker.dataset.enhanced === 'true') return;

    const select = picker.querySelector('[data-storage-item-select]');
    if (!select) return;

    picker.dataset.enhanced = 'true';
    picker.classList.add('is-enhanced');

    const panelId = `storage-item-options-${Math.random().toString(36).slice(2, 10)}`;
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'storage-item-picker__trigger';
    trigger.dataset.storagePickerTrigger = '';
    trigger.setAttribute('role', 'combobox');
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.setAttribute('aria-controls', panelId);

    const triggerText = document.createElement('span');
    triggerText.className = 'storage-item-picker__value';
    const triggerTitle = document.createElement('strong');
    triggerText.append(triggerTitle);

    const chevron = document.createElement('span');
    chevron.className = 'storage-item-picker__chevron';
    chevron.setAttribute('aria-hidden', 'true');
    trigger.append(triggerText, chevron);

    const panel = document.createElement('div');
    panel.id = panelId;
    panel.className = 'storage-item-picker__panel';
    panel.dataset.storagePickerPanel = '';
    panel.hidden = true;

    const search = document.createElement('input');
    search.type = 'search';
    search.className = 'storage-item-picker__search';
    search.placeholder = 'Cari consumable...';
    search.setAttribute('aria-label', 'Cari kode, nama, satuan, merek, atau spesifikasi consumable');
    search.autocomplete = 'off';

    const searchWrap = document.createElement('div');
    searchWrap.className = 'storage-item-picker__search-wrap';
    searchWrap.append(search);

    const results = document.createElement('div');
    results.className = 'storage-item-picker__results';
    results.setAttribute('role', 'listbox');
    panel.append(searchWrap, results);
    picker.append(trigger, panel);

    const itemOptions = [...select.options].filter((option) => option.value !== '');

    const updateTrigger = () => {
        const selected = select.selectedOptions[0];
        const hasSelection = Boolean(selected?.value);
        trigger.classList.toggle('is-placeholder', !hasSelection);
        triggerTitle.textContent = hasSelection
            ? `${selected.dataset.code} \u00b7 ${selected.dataset.name}`
            : 'Pilih barang';
    };

    const choose = (option) => {
        select.value = option.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        picker.classList.remove('is-invalid');
        closePicker(picker);
        trigger.focus();
    };

    const renderResults = (query = '') => {
        const keyword = normalize(query);
        const matches = itemOptions.filter((option) => normalize(option.dataset.search || option.textContent).includes(keyword));
        results.replaceChildren();

        if (matches.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'storage-item-picker__empty';
            empty.textContent = 'Consumable tidak ditemukan.';
            results.append(empty);
            return;
        }

        matches.forEach((option) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'storage-item-picker__option';
            button.setAttribute('role', 'option');
            button.setAttribute('aria-selected', String(select.value === option.value));

            const content = document.createElement('span');
            content.className = 'storage-item-picker__option-content';
            const title = document.createElement('strong');
            title.textContent = `${option.dataset.code} \u00b7 ${option.dataset.name}`;
            const meta = document.createElement('small');
            meta.textContent = option.dataset.meta;
            content.append(title, meta);

            const selectedMark = document.createElement('span');
            selectedMark.className = 'storage-item-picker__selected-mark';
            selectedMark.setAttribute('aria-hidden', 'true');
            selectedMark.textContent = '\u2713';
            button.append(content, selectedMark);
            button.addEventListener('click', () => choose(option));
            results.append(button);
        });
    };

    const openPicker = () => {
        document.querySelectorAll('[data-storage-item-picker][data-enhanced="true"]').forEach((otherPicker) => {
            if (otherPicker !== picker) closePicker(otherPicker);
        });
        search.value = '';
        renderResults();
        panel.hidden = false;
        picker.classList.add('is-open');
        picker.closest('[data-storage-line]')?.classList.add('has-open-picker');
        trigger.setAttribute('aria-expanded', 'true');
        window.requestAnimationFrame(() => search.focus());
    };

    trigger.addEventListener('click', () => {
        if (panel.hidden) openPicker();
        else closePicker(picker);
    });

    search.addEventListener('input', () => renderResults(search.value));
    search.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            results.querySelector('[role="option"]')?.focus();
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            closePicker(picker);
            trigger.focus();
        }
    });

    results.addEventListener('keydown', (event) => {
        const options = [...results.querySelectorAll('[role="option"]')];
        const current = options.indexOf(document.activeElement);

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            options[Math.min(current + 1, options.length - 1)]?.focus();
        }
        if (event.key === 'ArrowUp') {
            event.preventDefault();
            if (current <= 0) search.focus();
            else options[current - 1]?.focus();
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            closePicker(picker);
            trigger.focus();
        }
    });

    select.addEventListener('change', updateTrigger);
    select.addEventListener('invalid', (event) => {
        event.preventDefault();
        picker.classList.add('is-invalid');
        openPicker();
    });

    updateTrigger();
};

if (form) {
    const lines = form.querySelector('[data-storage-lines]');
    const template = form.querySelector('[data-storage-line-template]');
    const addButton = form.querySelector('[data-storage-line-add]');
    let index = lines?.querySelectorAll('[data-storage-line]').length ?? 0;

    const bindRow = (row) => {
        row.querySelectorAll('[data-storage-item-picker]').forEach(enhanceItemPicker);
        enhanceNumberInputs(row);
        row.querySelector('[data-storage-line-remove]')?.addEventListener('click', () => {
            if (lines.querySelectorAll('[data-storage-line]').length > 1) row.remove();
        });
    };

    lines?.querySelectorAll('[data-storage-line]').forEach(bindRow);
    addButton?.addEventListener('click', () => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(index++));
        const row = wrapper.firstElementChild;
        bindRow(row);
        lines.append(row);
        row.querySelector('[data-storage-picker-trigger]')?.focus();
    });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('[data-storage-item-picker][data-enhanced="true"]').forEach((picker) => {
            if (!picker.contains(event.target)) closePicker(picker);
        });
    });
}

enhanceNumberInputs();
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => enhanceNumberInputs());
}

document.addEventListener('submit', (event) => {
    const currentForm = event.target;
    if (currentForm instanceof HTMLFormElement) {
        currentForm.querySelectorAll('[data-number-format]').forEach((input) => {
            input.value = normalizeLocalizedNumber(input.value);
        });
    }
});

const loanAssetPicker = document.querySelector('[data-loan-asset-picker]');
const loanAssetSelect = loanAssetPicker?.querySelector('[data-loan-asset-select]');

if (loanAssetPicker && loanAssetSelect) {
    loanAssetPicker.classList.add('is-enhanced');
    const panelId = `loan-asset-options-${Math.random().toString(36).slice(2, 10)}`;
    const control = document.createElement('div');
    control.className = 'storage-loan-asset-control';
    control.tabIndex = 0;
    control.setAttribute('role', 'combobox');
    control.setAttribute('aria-haspopup', 'listbox');
    control.setAttribute('aria-expanded', 'false');
    control.setAttribute('aria-controls', panelId);
    control.setAttribute('aria-label', 'Pilih aset yang dipinjam');

    const selection = document.createElement('div');
    selection.className = 'storage-loan-asset-selection';
    const chevron = document.createElement('span');
    chevron.className = 'storage-loan-asset-chevron';
    chevron.setAttribute('aria-hidden', 'true');
    control.append(selection, chevron);

    const panel = document.createElement('div');
    panel.id = panelId;
    panel.className = 'storage-loan-asset-panel';
    panel.hidden = true;

    const searchWrap = document.createElement('div');
    searchWrap.className = 'storage-loan-asset-search-wrap';
    const search = document.createElement('input');
    search.type = 'search';
    search.className = 'storage-loan-asset-search';
    search.placeholder = 'Cari nama aset...';
    search.setAttribute('aria-label', 'Cari nama aset');
    search.autocomplete = 'off';
    searchWrap.append(search);

    const results = document.createElement('div');
    results.className = 'storage-loan-asset-results';
    results.setAttribute('role', 'listbox');
    results.setAttribute('aria-multiselectable', 'true');
    panel.append(searchWrap, results);
    loanAssetPicker.append(control, panel);

    const options = [...loanAssetSelect.options];
    const selectedOptions = () => options.filter((option) => option.selected);

    const closeLoanPicker = ({ restoreFocus = false } = {}) => {
        panel.hidden = true;
        control.setAttribute('aria-expanded', 'false');
        loanAssetPicker.classList.remove('is-open');
        if (restoreFocus) control.focus();
    };

    const updateSelection = () => {
        selection.replaceChildren();
        const selected = selectedOptions();

        if (!selected.length) {
            const placeholder = document.createElement('span');
            placeholder.className = 'storage-loan-asset-placeholder';
            placeholder.textContent = 'Pilih aset';
            selection.append(placeholder);
        } else {
            selected.forEach((option) => {
                const tag = document.createElement('span');
                tag.className = 'storage-loan-asset-tag';
                const name = document.createElement('span');
                name.textContent = option.dataset.name;
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'storage-loan-asset-tag-remove';
                remove.setAttribute('aria-label', `Hapus ${option.dataset.name}`);
                remove.textContent = '\u00d7';
                remove.addEventListener('click', (event) => {
                    event.stopPropagation();
                    option.selected = false;
                    loanAssetSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    control.focus();
                });
                tag.append(name, remove);
                selection.append(tag);
            });
        }

        if (selected.length) loanAssetPicker.classList.remove('is-invalid');
    };

    const render = () => {
        const keyword = normalize(search.value);
        results.replaceChildren();
        const matches = options.filter((option) => normalize(option.dataset.name).includes(keyword));

        if (!matches.length) {
            const empty = document.createElement('p');
            empty.className = 'storage-item-picker__empty';
            empty.textContent = 'Aset tidak ditemukan.';
            results.append(empty);
            return;
        }

        matches.forEach((option) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'storage-loan-asset-option';
            button.setAttribute('role', 'option');
            button.setAttribute('aria-selected', String(option.selected));

            const name = document.createElement('span');
            name.textContent = option.dataset.name;
            const selectedMark = document.createElement('span');
            selectedMark.className = 'storage-loan-asset-option-mark';
            selectedMark.setAttribute('aria-hidden', 'true');
            selectedMark.textContent = '\u2713';
            button.append(name, selectedMark);
            button.addEventListener('click', () => {
                option.selected = !option.selected;
                loanAssetSelect.dispatchEvent(new Event('change', { bubbles: true }));
                search.focus();
            });
            results.append(button);
        });
    };

    const openLoanPicker = () => {
        panel.hidden = false;
        control.setAttribute('aria-expanded', 'true');
        loanAssetPicker.classList.add('is-open');
        search.value = '';
        render();
        window.requestAnimationFrame(() => search.focus());
    };

    control.addEventListener('click', () => {
        if (panel.hidden) openLoanPicker();
        else closeLoanPicker();
    });
    control.addEventListener('keydown', (event) => {
        if (['Enter', ' ', 'ArrowDown'].includes(event.key)) {
            event.preventDefault();
            if (panel.hidden) openLoanPicker();
            else search.focus();
        }
        if (event.key === 'Escape' && !panel.hidden) {
            event.preventDefault();
            closeLoanPicker();
        }
    });
    search.addEventListener('input', render);
    search.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            results.querySelector('[role="option"]')?.focus();
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            closeLoanPicker({ restoreFocus: true });
        }
    });
    results.addEventListener('keydown', (event) => {
        const resultOptions = [...results.querySelectorAll('[role="option"]')];
        const current = resultOptions.indexOf(document.activeElement);
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            resultOptions[Math.min(current + 1, resultOptions.length - 1)]?.focus();
        }
        if (event.key === 'ArrowUp') {
            event.preventDefault();
            if (current <= 0) search.focus();
            else resultOptions[current - 1]?.focus();
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            closeLoanPicker({ restoreFocus: true });
        }
    });
    loanAssetSelect.addEventListener('change', () => {
        updateSelection();
        render();
    });
    loanAssetSelect.addEventListener('invalid', (event) => {
        event.preventDefault();
        loanAssetPicker.classList.add('is-invalid');
        openLoanPicker();
    });
    document.addEventListener('click', (event) => {
        if (!loanAssetPicker.contains(event.target)) closeLoanPicker();
    });
    updateSelection();
    render();

    loanAssetPicker.closest('form')?.addEventListener('submit', (event) => {
        if (!selectedOptions().length) {
            event.preventDefault();
            loanAssetPicker.classList.add('is-invalid');
            openLoanPicker();
        }
    });
}

const invalidReturnForm = document.querySelector('[data-loan-return-errors]');
const returnDialog = invalidReturnForm?.closest('dialog');

if (returnDialog && !returnDialog.open) {
    returnDialog.showModal();
}

const unitCreateForm = document.querySelector('[data-storage-unit-create-form]');
const unitSelect = document.querySelector('[data-storage-unit-select]');

if (unitCreateForm && unitSelect) {
    const dialog = unitCreateForm.closest('dialog');
    const submitButton = dialog?.querySelector('[data-storage-unit-create-submit]');
    const errorMessage = unitCreateForm.querySelector('[data-storage-unit-create-error]');

    unitCreateForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!unitCreateForm.reportValidity()) return;

        if (errorMessage) {
            errorMessage.hidden = true;
            errorMessage.textContent = '';
        }
        if (submitButton) submitButton.disabled = true;

        try {
            const response = await fetch(unitCreateForm.action, {
                method: 'POST',
                body: new FormData(unitCreateForm),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const validationMessage = Object.values(payload.errors || {}).flat()[0];
                throw new Error(validationMessage || payload.message || 'Satuan gagal ditambahkan.');
            }

            const option = new Option(payload.unit.label, String(payload.unit.id), true, true);
            unitSelect.add(option);
            unitSelect.dispatchEvent(new Event('change', { bubbles: true }));
            unitCreateForm.reset();
            if (dialog?.open) dialog.close();
            window.AppToast?.show(payload.message, 'success');
        } catch (error) {
            if (errorMessage) {
                errorMessage.textContent = error.message;
                errorMessage.hidden = false;
            }
        } finally {
            if (submitButton) submitButton.disabled = false;
        }
    });
}
