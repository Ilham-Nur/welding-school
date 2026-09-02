import QRCode from 'qrcode';

function initializeAssetForm() {
    const form = document.querySelector('[data-asset-form]');
    const categorySelect = form?.querySelector('[data-asset-category]');
    const kindPickerRoot = form?.querySelector('[data-asset-kind-picker]');
    const kindSelect = form?.querySelector('[data-asset-kind-select]');
    const kindEmpty = form?.querySelector('[data-asset-kind-empty]');
    const kindManageLink = form?.querySelector('[data-asset-kind-manage]');
    const calibrationSelect = form?.querySelector('[data-requires-calibration]');
    const statusSelect = form?.querySelector('[data-asset-status]');
    const calibrationFields = form?.querySelector('[data-calibration-fields]');
    const idPreview = form?.querySelector('[data-asset-id-preview]');
    const sequenceSummary = form?.querySelector('[data-asset-sequence-summary]');
    const lastCode = form?.querySelector('[data-asset-last-code]');
    const kindCount = form?.querySelector('[data-asset-kind-count]');

    if (!form || !calibrationSelect || !statusSelect || !calibrationFields) return;

    let kinds = [];

    try {
        kinds = JSON.parse(document.querySelector('[data-asset-kind-options]')?.textContent || '[]');
    } catch {
        kinds = [];
    }

    let refreshKindPicker = () => {};
    let resetKindPicker = () => {};

    const findSelectedKind = () => kinds.find((kind) => String(kind.id) === kindSelect?.value);

    const updateKindDetails = () => {
        if (!categorySelect || !idPreview) return;

        const kind = findSelectedKind();
        idPreview.textContent = kind?.nextCode || `ATP-${categorySelect.value}-___-001`;

        if (sequenceSummary) sequenceSummary.hidden = !kind;
        if (lastCode) lastCode.textContent = kind?.lastCode || 'Belum ada';
        if (kindCount) kindCount.textContent = String(kind?.assetCount || 0);
    };

    const renderKinds = (preferredValue = kindSelect?.value || '') => {
        if (!categorySelect || !kindSelect) return;

        const categoryKinds = kinds.filter((kind) => kind.categoryCode === categorySelect.value);
        kindSelect.replaceChildren(new Option('Pilih jenis aset', ''));

        categoryKinds.forEach((kind) => {
            kindSelect.add(new Option(`${kind.name} | ${kind.code}`, String(kind.id)));
        });

        if (categoryKinds.some((kind) => String(kind.id) === String(preferredValue))) {
            kindSelect.value = String(preferredValue);
        }

        if (kindEmpty) {
            kindEmpty.hidden = categoryKinds.length > 0;
            kindEmpty.textContent = 'Belum ada jenis pada kategori ini. Tambahkan jenis melalui halaman master kategori & jenis aset.';
        }

        refreshKindPicker();
        updateKindDetails();
    };

    const initializeKindPicker = () => {
        if (!categorySelect || !kindPickerRoot || !kindSelect) return;

        kindPickerRoot.classList.add('is-enhanced');
        const panelId = `asset-kind-options-${Math.random().toString(36).slice(2, 10)}`;
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'asset-kind-picker__trigger is-placeholder';
        trigger.dataset.assetKindPickerTrigger = '';
        trigger.setAttribute('role', 'combobox');
        trigger.setAttribute('aria-label', 'Jenis aset');
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-controls', panelId);

        const triggerValue = document.createElement('span');
        triggerValue.className = 'asset-kind-picker__value';
        const triggerTitle = document.createElement('strong');
        triggerValue.append(triggerTitle);

        const chevron = document.createElement('span');
        chevron.className = 'asset-kind-picker__chevron';
        chevron.setAttribute('aria-hidden', 'true');
        trigger.append(triggerValue, chevron);

        const panel = document.createElement('div');
        panel.id = panelId;
        panel.className = 'asset-kind-picker__panel';
        panel.dataset.assetKindPickerPanel = '';
        panel.hidden = true;

        const searchWrap = document.createElement('div');
        searchWrap.className = 'asset-kind-picker__search-wrap';
        const search = document.createElement('input');
        search.type = 'search';
        search.className = 'asset-kind-picker__search';
        search.placeholder = 'Cari jenis aset...';
        search.setAttribute('aria-label', 'Cari nama atau kode jenis aset');
        search.autocomplete = 'off';
        searchWrap.append(search);

        const results = document.createElement('div');
        results.className = 'asset-kind-picker__results';
        results.setAttribute('role', 'listbox');
        panel.append(searchWrap, results);
        kindPickerRoot.append(trigger, panel);

        const normalize = (value) => String(value ?? '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLocaleLowerCase('id-ID')
            .trim();

        const updateTrigger = () => {
            const selectedKind = findSelectedKind();
            trigger.classList.toggle('is-placeholder', !selectedKind);
            triggerTitle.textContent = selectedKind
                ? `${selectedKind.name} | ${selectedKind.code}`
                : 'Pilih jenis aset';
        };

        const closePicker = () => {
            panel.hidden = true;
            kindPickerRoot.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
        };

        const chooseKind = (kind) => {
            kindSelect.value = String(kind.id);
            kindSelect.dispatchEvent(new Event('change', {bubbles: true}));
            kindPickerRoot.classList.remove('is-invalid');
            closePicker();
            trigger.focus();
        };

        const renderResults = (query = '') => {
            const keyword = normalize(query);
            const matches = kinds.filter((kind) => {
                if (kind.categoryCode !== categorySelect.value) return false;
                return normalize(`${kind.code} ${kind.name} ${kind.nextCode}`).includes(keyword);
            });
            results.replaceChildren();

            if (matches.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'asset-kind-picker__empty';
                empty.textContent = keyword
                    ? 'Jenis aset tidak ditemukan.'
                    : 'Belum ada jenis aset pada kategori ini.';
                results.append(empty);
                return;
            }

            matches.forEach((kind) => {
                const option = document.createElement('button');
                option.type = 'button';
                option.className = 'asset-kind-picker__option';
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', String(kindSelect.value === String(kind.id)));

                const content = document.createElement('span');
                content.className = 'asset-kind-picker__option-content';
                const title = document.createElement('strong');
                title.textContent = `${kind.name} | ${kind.code}`;
                const meta = document.createElement('small');
                meta.textContent = kind.lastCode
                    ? `Terakhir ${kind.lastCode} · Berikutnya ${kind.nextCode}`
                    : `Belum ada nomor · Berikutnya ${kind.nextCode}`;
                content.append(title, meta);

                const selectedMark = document.createElement('span');
                selectedMark.className = 'asset-kind-picker__selected-mark';
                selectedMark.setAttribute('aria-hidden', 'true');
                selectedMark.textContent = '✓';
                option.append(content, selectedMark);
                option.addEventListener('click', () => chooseKind(kind));
                results.append(option);
            });
        };

        const openPicker = () => {
            search.value = '';
            renderResults();
            panel.hidden = false;
            kindPickerRoot.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            window.requestAnimationFrame(() => search.focus());
        };

        trigger.addEventListener('click', () => {
            if (panel.hidden) openPicker();
            else closePicker();
        });
        search.addEventListener('input', () => renderResults(search.value));
        search.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                results.querySelector('[role="option"]')?.focus();
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                closePicker();
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
                closePicker();
                trigger.focus();
            }
        });
        kindSelect.addEventListener('change', updateTrigger);
        kindSelect.addEventListener('invalid', (event) => {
            event.preventDefault();
            kindPickerRoot.classList.add('is-invalid');
            openPicker();
        });
        document.addEventListener('click', (event) => {
            if (!kindPickerRoot.contains(event.target)) closePicker();
        });

        refreshKindPicker = () => {
            search.value = '';
            renderResults();
            updateTrigger();
        };
        resetKindPicker = closePicker;
        updateTrigger();
    };

    const updateKindManageLink = () => {
        if (!categorySelect || !kindManageLink) return;

        const url = new URL(kindManageLink.dataset.baseUrl || kindManageLink.href, window.location.origin);
        url.searchParams.set('category', categorySelect.value);
        url.searchParams.set('return_to', 'asset-create');
        kindManageLink.href = url.toString();
    };

    const updateCalibrationFields = () => {
        const requiresCalibration = calibrationSelect.value === '1';
        const active = statusSelect.value === 'active';

        calibrationFields.hidden = !requiresCalibration;
        form.querySelectorAll('[data-calibration-serial]').forEach((field) => {
            field.required = requiresCalibration;
        });
        calibrationFields.querySelectorAll('[data-calibration-active]').forEach((field) => {
            field.required = requiresCalibration && active;
        });

        const underCalibration = statusSelect.querySelector('option[value="under_calibration"]');
        if (underCalibration) {
            underCalibration.disabled = !requiresCalibration;
            underCalibration.hidden = !requiresCalibration;
        }
        if (!requiresCalibration && statusSelect.value === 'under_calibration') statusSelect.value = 'maintenance';
    };

    categorySelect?.addEventListener('change', () => {
        resetKindPicker();
        renderKinds('');
        updateKindManageLink();
    });
    kindSelect?.addEventListener('change', updateKindDetails);
    calibrationSelect.addEventListener('change', updateCalibrationFields);
    statusSelect.addEventListener('change', updateCalibrationFields);
    initializeKindPicker();
    renderKinds(kindSelect?.value || '');
    updateKindManageLink();
    updateCalibrationFields();
}

function initializeAssetKindMaster() {
    const form = document.querySelector('[data-asset-kind-master-form]');
    const category = form?.querySelector('[data-asset-kind-master-category]');
    const name = form?.querySelector('[data-asset-kind-master-name]');
    const code = form?.querySelector('[data-asset-kind-master-code]');
    const example = form?.querySelector('[data-asset-kind-master-example]');

    if (!form || !category || !name || !code || !example) return;

    let codeEdited = code.value.trim() !== '';

    const updateExample = () => {
        const categoryCode = category.value || '___';
        const kindCode = code.value.trim().toUpperCase().padEnd(3, '_');
        const currentNumber = example.textContent.trim().match(/-(\d+)$/)?.[1] || '001';
        example.textContent = `ATP-${categoryCode}-${kindCode}-${currentNumber}`;
    };

    name.addEventListener('input', () => {
        if (!codeEdited && !code.readOnly) {
            code.value = name.value.replace(/[^a-z]/gi, '').slice(0, 3).toUpperCase();
        }
        updateExample();
    });

    code.addEventListener('input', () => {
        codeEdited = true;
        code.value = code.value.replace(/[^a-z]/gi, '').slice(0, 3).toUpperCase();
        updateExample();
    });
    category.addEventListener('change', updateExample);
    updateExample();
}

function initializeAssetSelection() {
    const selectAll = document.querySelector('[data-asset-select-all]');
    const assetCheckboxes = Array.from(document.querySelectorAll('[data-asset-select]'));
    const printButton = document.querySelector('[data-asset-label-selection]');
    const count = document.querySelector('[data-asset-selection-count]');

    if (!assetCheckboxes.length || !printButton || !count) return;

    const updateSelection = () => {
        const selected = assetCheckboxes.filter((checkbox) => checkbox.checked).length;
        count.textContent = `(${selected})`;
        printButton.disabled = selected === 0;

        if (selectAll) {
            selectAll.checked = selected === assetCheckboxes.length;
            selectAll.indeterminate = selected > 0 && selected < assetCheckboxes.length;
        }
    };

    selectAll?.addEventListener('change', () => {
        assetCheckboxes.forEach((checkbox) => {
            checkbox.checked = selectAll.checked;
        });
        updateSelection();
    });

    assetCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelection));
    updateSelection();
}

function initializeChecklistEditor() {
    const list = document.querySelector('[data-checklist-list]');
    const addButton = document.querySelector('[data-checklist-add]');

    if (!list || !addButton) return;

    const updateRows = () => {
        const rows = Array.from(list.querySelectorAll('[data-checklist-row]'));
        rows.forEach((row, index) => {
            row.querySelector('[data-checklist-number]').textContent = String(index + 1);
            const removeButton = row.querySelector('[data-checklist-remove]');
            removeButton.disabled = rows.length === 1;
            removeButton.setAttribute('aria-label', `Hapus item pemeriksaan ${index + 1}`);
        });
        addButton.disabled = rows.length >= 30;
    };

    const createRow = () => {
        const row = document.createElement('div');
        row.className = 'asset-checklist-editor__row';
        row.dataset.checklistRow = '';

        const number = document.createElement('span');
        number.dataset.checklistNumber = '';

        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'checklist_items[]';
        input.maxLength = 255;
        input.placeholder = 'Contoh: Kabel dan konektor dalam kondisi baik';
        input.required = true;

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.dataset.checklistRemove = '';
        removeButton.textContent = 'Hapus';

        row.append(number, input, removeButton);
        list.append(row);
        updateRows();
        input.focus();
    };

    addButton.addEventListener('click', createRow);
    list.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-checklist-remove]');
        if (!removeButton || removeButton.disabled) return;
        removeButton.closest('[data-checklist-row]').remove();
        updateRows();
    });
    updateRows();
}

async function initializeQrLabels() {
    const containers = Array.from(document.querySelectorAll('[data-qr-value]'));
    const printButton = document.querySelector('[data-print-labels]');

    if (!containers.length) return;

    await Promise.all(containers.map(async (container) => {
        try {
            const source = await QRCode.toDataURL(container.dataset.qrValue, {
                errorCorrectionLevel: 'M',
                margin: 1,
                width: 320,
                color: {dark: '#071b32ff', light: '#ffffffff'},
            });
            const image = new Image();
            image.src = source;
            image.alt = `QR verifikasi aset ${container.dataset.qrLabel}`;
            await image.decode();
            container.replaceChildren(image);
        } catch (error) {
            container.classList.add('has-error');
            container.textContent = 'QR gagal dibuat';
            throw error;
        }
    })).then(() => {
        if (printButton) printButton.disabled = false;
    }).catch(() => {
        if (printButton) {
            printButton.disabled = true;
            printButton.title = 'Muat ulang halaman untuk menyiapkan QR.';
        }
    });

    printButton?.addEventListener('click', () => window.print());
}

function initializeLabelSizePicker() {
    const picker = document.querySelector('[data-label-size-select]');
    const sheet = document.querySelector('[data-label-sheet]');
    const summary = document.querySelector('[data-label-size-summary]');

    if (!picker || !sheet) return;

    const updateSize = () => {
        const compact = picker.value === 'compact';
        const sizeLabel = compact ? 'Ringkas 60 x 31 mm' : 'Standar 90 x 42 mm';

        sheet.classList.toggle('asset-label-sheet--compact', compact);
        sheet.querySelectorAll('.asset-sticker').forEach((sticker) => {
            sticker.classList.toggle('asset-sticker--compact', compact);
        });
        if (summary) summary.textContent = sizeLabel;
    };

    picker.addEventListener('change', updateSize);
    updateSize();
}

initializeAssetForm();
initializeAssetKindMaster();
initializeChecklistEditor();
initializeAssetSelection();
initializeLabelSizePicker();
initializeQrLabels();
