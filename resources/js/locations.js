document.querySelectorAll('[data-location-parts-form]').forEach((partsForm) => {
    const list = partsForm.querySelector('[data-location-part-list]');
    const template = partsForm.querySelector('[data-location-part-template]');
    const addButton = partsForm.querySelector('[data-location-part-add]');
    let index = list?.querySelectorAll('[data-location-part-row]').length ?? 0;

    const refreshRows = () => {
        const rows = [...list.querySelectorAll('[data-location-part-row]')];
        rows.forEach((row, rowIndex) => {
            const label = row.querySelector('.ui-field__label');
            if (label) label.textContent = `Nama bagian ${rowIndex + 1}`;
            const remove = row.querySelector('[data-location-part-remove]');
            if (remove) remove.disabled = rows.length === 1;
        });
    };

    const bindRow = (row) => {
        row.querySelector('[data-location-part-remove]')?.addEventListener('click', () => {
            if (list.querySelectorAll('[data-location-part-row]').length > 1) {
                row.remove();
                refreshRows();
            }
        });
    };

    list?.querySelectorAll('[data-location-part-row]').forEach(bindRow);
    refreshRows();

    addButton?.addEventListener('click', () => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(index++));
        const row = wrapper.firstElementChild;
        bindRow(row);
        list.append(row);
        refreshRows();
        row.querySelector('input')?.focus();
    });

    if (partsForm.querySelector('[data-location-parts-errors]')) {
        const dialog = document.getElementById(partsForm.dataset.locationModalId);
        if (dialog && !dialog.open) dialog.showModal();
    }
});
