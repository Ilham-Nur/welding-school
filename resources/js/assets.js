import QRCode from 'qrcode';

function initializeAssetForm() {
    const form = document.querySelector('[data-asset-form]');
    const categorySelect = form?.querySelector('[data-asset-category]');
    const calibrationSelect = form?.querySelector('[data-requires-calibration]');
    const statusSelect = form?.querySelector('[data-asset-status]');
    const calibrationFields = form?.querySelector('[data-calibration-fields]');
    const idPreview = form?.querySelector('[data-asset-id-preview]');

    if (!form || !calibrationSelect || !statusSelect || !calibrationFields) return;

    const updateIdPreview = () => {
        if (categorySelect && idPreview) idPreview.textContent = `AWA-${categorySelect.value}-###`;
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

    categorySelect?.addEventListener('change', updateIdPreview);
    calibrationSelect.addEventListener('change', updateCalibrationFields);
    statusSelect.addEventListener('change', updateCalibrationFields);
    updateIdPreview();
    updateCalibrationFields();
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
        const sizeLabel = compact ? 'Ringkas 60 x 35 mm' : 'Standar 90 x 55 mm';

        sheet.classList.toggle('asset-label-sheet--compact', compact);
        sheet.querySelectorAll('.asset-sticker').forEach((sticker) => {
            sticker.classList.toggle('asset-sticker--compact', compact);
        });
        sheet.querySelectorAll('[data-compact-hidden]').forEach((row) => {
            row.hidden = compact;
        });
        if (summary) summary.textContent = sizeLabel;
    };

    picker.addEventListener('change', updateSize);
    updateSize();
}

initializeAssetForm();
initializeChecklistEditor();
initializeAssetSelection();
initializeLabelSizePicker();
initializeQrLabels();
