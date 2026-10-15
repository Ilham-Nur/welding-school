(() => {
  const form = document.querySelector('[data-budget-form]');
  if (!form) return;
  const program = form.querySelector('[data-budget-program]');
  const container = form.querySelector('[data-budget-items]');
  const filter = form.querySelector('[data-budget-filter]');
  const status = form.querySelector('[data-preview-status]');
  const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
  const number = value => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 3 }).format(value);
  const rows = () => [...container.querySelectorAll('[data-budget-item]')];
  const field = (row, key) => form.elements.namedItem(`items[${row.dataset.index}][${key}]`);
  const detail = row => document.getElementById(`rab-detail-${row.dataset.index}`);
  let next = Math.max(-1, ...rows().map(row => Number(row.dataset.index))) + 1;
  const planning = !!form.elements.namedItem('planning');
  let nextPlan = Math.max(0, ...[...form.querySelectorAll('[data-plan] input[type=hidden]')].map(input => Number(input.value))) + 1;
  let stock = null;
  let totals = null;
  let timer;
  let controller;
  let version = 0;

  function selectedBatches() {
    return [...form.querySelectorAll('[data-budget-batch]')].filter(row => !row.querySelector('fieldset').disabled).map(row => {
      const values = {};
      row.querySelectorAll('input[name]').forEach(input => {
        const key = input.name.match(/\[([^\]]+)\]$/)[1];
        if (input.type === 'number' || input.type === 'hidden') values[key] = Number(input.value);
      });
      return { ...values, label: row.querySelector('strong').textContent.trim() };
    });
  }

  function syncBatches() {
    form.querySelectorAll('[data-budget-batch]').forEach(row => {
      row.hidden = !planning && row.dataset.program !== program.value;
      row.querySelector('fieldset').disabled = row.hidden || (!planning && !row.querySelector('[data-batch-toggle]').checked);
      if (planning) row.querySelector('[data-plan-label]').textContent = row.querySelector('[data-plan-name]').value || 'Rencana batch';
    });
    const previous = filter.value;
    filter.replaceChildren(new Option('Semua batch & biaya bersama', 'all'), new Option('Biaya bersama saja', 'shared'));
    selectedBatches().forEach(batch => filter.add(new Option(`${batch.label} (biaya langsung)`, String(batch.id))));
    filter.value = [...filter.options].some(option => option.value === previous) ? previous : 'all';
    if (planning) rows().forEach(row => {
      const select = field(row, 'batch_id');
      const previousId = select.value;
      select.replaceChildren(new Option('Semua batch terpilih', ''));
      selectedBatches().forEach(batch => select.add(new Option(batch.label, String(batch.id))));
      if (previousId && ![...select.options].some(option => option.value === previousId)) select.add(new Option('Rencana batch sudah dihapus — pilih ulang', previousId));
      select.value = previousId;
    });
  }

  function toggleDetails(row, open) {
    row.dataset.expanded = String(open);
    detail(row).hidden = row.hidden || !open;
    row.querySelector('[data-item-details]').setAttribute('aria-expanded', String(open));
  }

  function factor(item, batch, kind) {
    const basis = field(item, `${kind}_basis`).value;
    if (basis === 'manual') return 1;
    if (basis === 'staff') return batch.instructors + batch.assistants;
    return batch[basis];
  }

  function paintStock() {
    const body = form.querySelector('[data-stock-rows]');
    const message = form.querySelector('[data-stock-status]');
    body.replaceChildren();
    if (!stock?.rows.length) {
      const tr = document.createElement('tr');
      const td = document.createElement('td');
      const hint = document.createElement('small');
      td.colSpan = 5;
      hint.textContent = 'Belum ada baris material untuk ditampilkan.';
      td.append(hint); tr.append(td); body.append(tr);
    }
    if (!stock) { message.textContent = 'Pemeriksaan stok belum tersedia.'; return; }
    message.textContent = stock.unmapped.length ? `Material belum ditautkan: ${stock.unmapped.join(', ')}.` : !stock.rows.length ? 'Belum ada material yang terhubung. Ini belum menyatakan stok mencukupi.' : stock.has_shortage ? 'Ada kekurangan stok. Lengkapi rencana pengadaan.' : 'Stok tercatat mencukupi kebutuhan material yang ditautkan saat ini.';
    stock.rows.forEach(row => {
      const tr = document.createElement('tr');
      const material = document.createElement('td');
      const name = document.createElement('strong');
      const location = document.createElement('small');
      name.textContent = row.item; location.textContent = row.location;
      material.append(name, location); tr.append(material);
      [row.unit, number(row.required), number(row.available), number(row.shortage)].forEach((value, index) => {
        const td = document.createElement('td');
        if (index > 0) td.className = 'rab-stock-number';
        td.textContent = value; tr.append(td);
      });
      body.append(tr);
    });
  }

  function paint() {
    const batches = selectedBatches();
    const lines = new Map((totals?.lines || []).map(line => [String(line.index), line]));
    let visible = 0;
    container.querySelectorAll('[data-budget-category]').forEach(group => {
      let subtotal = 0;
      let count = 0;
      group.querySelectorAll('[data-budget-item]').forEach(row => {
        const shared = field(row, 'scope').value === 'shared';
        const batchId = field(row, 'batch_id').value;
        row.hidden = filter.value === 'shared' ? !shared : filter.value === 'all' ? false : shared || (batchId !== '' && batchId !== filter.value);
        detail(row).hidden = row.hidden || row.dataset.expanded !== 'true';
        if (!row.hidden) row.querySelector('[data-row-number]').textContent = ++count;
        const applicable = batches.filter(batch => !batchId || String(batch.id) === batchId);
        const scope = shared ? 'Bersama · sekali' : batchId ? (applicable[0]?.label || 'Batch belum dipilih') : `${batches.length} batch terpilih`;
        row.querySelector('[data-item-caption]').textContent = `${scope} · ${field(row, 'unit').value || 'satuan'}`;
        ['quantity', 'frequency'].forEach(kind => {
          const basis = field(row, `${kind}_basis`);
          const hint = row.querySelector(`[data-${kind}-hint]`);
          const sample = applicable.length === 1 ? factor(row, applicable[0], kind) : null;
          hint.textContent = basis.value === 'manual' ? 'Manual' : sample !== null ? `× ${number(sample)} ${basis.selectedOptions[0].textContent.toLowerCase()}` : `× ${basis.selectedOptions[0].textContent.toLowerCase()} tiap batch`;
        });
        row.querySelector('[data-unit-price-hint]').textContent = field(row, 'unit_price').value === '' ? '—' : money(Number(field(row, 'unit_price').value));
        const line = lines.get(row.dataset.index);
        const amount = !line ? null : filter.value === 'all' || filter.value === 'shared' ? line.amount : (line.by_batch[filter.value] ?? 0);
        row.querySelector('[data-line-total]').textContent = amount === null ? '—' : money(amount);
        if (!row.hidden && amount !== null) subtotal += amount;
        const breakdown = detail(row).querySelector('[data-line-breakdown]');
        breakdown.replaceChildren();
        if (line) {
          Object.entries(line.by_batch).forEach(([id, value]) => {
            const batch = batches.find(batch => String(batch.id) === id);
            const li = document.createElement('li');
            const qty = Number(field(row, 'quantity').value) * (batch ? factor(row, batch, 'quantity') : 1);
            const frequency = Number(field(row, 'frequency').value) * (batch ? factor(row, batch, 'frequency') : 1);
            li.textContent = `${id === 'shared' ? 'Biaya bersama' : batch?.label || 'Batch'}: ${number(qty)} × ${number(frequency)} × ${money(Number(field(row, 'unit_price').value))} = ${money(value)}`;
            breakdown.append(li);
          });
        }
      });
      group.hidden = count === 0;
      group.querySelector('[data-category-total]').textContent = totals ? money(subtotal) : '—';
      visible += count;
    });
    form.querySelector('[data-empty-items]').hidden = visible > 0;
    form.querySelectorAll('[data-live]').forEach(output => {
      const key = output.dataset.live;
      output.textContent = !totals ? '—' : key === 'participants' ? `${number(totals[key])} orang` : money(totals[key]);
      output.classList.toggle('rab-negative', key === 'profit' && totals?.profit < 0);
    });
  }

  function invalidate() {
    clearTimeout(timer);
    controller?.abort();
    version++;
    totals = null;
    stock = null;
    paintStock();
    paint();
    if (!program.value || selectedBatches().length === 0) {
      status.textContent = 'Pilih program dan minimal satu batch untuk melihat perhitungan.';
      return;
    }
    status.textContent = 'Menghitung perubahan…';
    timer = setTimeout(preview, 350);
  }

  async function preview() {
    const requestedVersion = version;
    const requestController = new AbortController();
    controller = requestController;
    const timeout = setTimeout(() => requestController.abort(), 15000);
    const data = new FormData(form);
    data.delete('_method'); // Edit forms use PUT only when actually saved.
    form.querySelectorAll('[data-preview-invalid]').forEach(input => {
      input.removeAttribute('aria-invalid');
      delete input.dataset.previewInvalid;
    });
    try {
      const response = await fetch(form.dataset.previewUrl, {
        method: 'POST', body: data, signal: requestController.signal,
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const result = await response.json();
      if (requestedVersion !== version) return;
      if (!response.ok) {
        const entries = Object.entries(result.errors || {});
        entries.forEach(([path]) => {
          const [first, ...parts] = path.split('.');
          const input = form.elements.namedItem(first + parts.map(part => `[${part}]`).join(''));
          if (input?.setAttribute) {
            input.setAttribute('aria-invalid', 'true');
            input.dataset.previewInvalid = 'true';
          }
        });
        status.textContent = entries.length ? `Perhitungan belum tersedia: ${entries[0][1][0]}` : 'Perhitungan gagal. Periksa koneksi atau muat ulang halaman.';
        return;
      }
      totals = result.totals;
      stock = result.stock;
      paintStock();
      status.textContent = 'Estimasi seluruh RAB · perubahan belum disimpan.';
      paint();
    } catch (error) {
      if (requestedVersion === version) status.textContent = 'Perhitungan belum tersedia. Periksa koneksi, lalu ubah input untuk mencoba lagi.';
    } finally {
      clearTimeout(timeout);
    }
  }

  function category(name) {
    let group = [...container.querySelectorAll('[data-budget-category]')].find(group => group.dataset.category === name);
    if (group) return group;
    group = form.querySelector('[data-category-template]').content.firstElementChild.cloneNode(true);
    group.dataset.category = name;
    group.querySelector('[data-category-title]').textContent = name;
    group.querySelector('[data-category-label]').textContent = name;
    group.querySelector('[role="region"]').setAttribute('aria-label', `Komponen ${name}`);
    container.append(group);
    const option = document.createElement('option');
    option.value = name;
    form.querySelector('#rab-category-options').append(option);
    return group;
  }

  function removeEmptyCategories() {
    container.querySelectorAll('[data-budget-category]').forEach(group => {
      if (!group.querySelector('[data-budget-item]')) group.remove();
    });
  }

  function addItem(name) {
    if (rows().length >= 50) {
      status.textContent = 'Maksimal 50 komponen per RAB.';
      return;
    }
    const group = category(name);
    const template = document.createElement('template');
    template.innerHTML = form.querySelector('[data-item-template]').innerHTML.replaceAll('__INDEX__', String(next++));
    const row = template.content.querySelector('[data-budget-item]');
    group.querySelector('[data-category-items]').append(template.content);
    field(row, 'category').value = name;
    if (filter.value === 'shared') field(row, 'scope').value = 'shared';
    else if (filter.value !== 'all') field(row, 'batch_id').value = filter.value;
    syncBatches();
    invalidate();
    field(row, 'description').focus();
  }

  form.addEventListener('click', event => {
    if (event.target.closest('[data-add-plan]')) {
      if (form.querySelectorAll('[data-plan]').length >= 20) { status.textContent = 'Maksimal 20 rencana batch.'; return; }
      const template = document.createElement('template');
      const id = nextPlan++;
      template.innerHTML = form.querySelector('[data-plan-template]').innerHTML.replaceAll('__PLAN__', String(id));
      template.content.querySelector('[data-plan-name]').value = `Batch ${id}`;
      form.querySelector('[data-budget-plans]').append(template.content);
      syncBatches(); invalidate();
    }
    if (event.target.closest('[data-remove-plan]')) {
      event.target.closest('[data-plan]').remove(); syncBatches(); invalidate();
    }
    const row = event.target.closest('[data-budget-item]');
    if (event.target.closest('[data-item-details]')) toggleDetails(row, row.dataset.expanded !== 'true');
    if (event.target.closest('[data-remove-item]')) {
      detail(row).remove();
      row.remove();
      removeEmptyCategories();
      invalidate();
    }
    if (event.target.closest('[data-add-category-item]')) addItem(event.target.closest('[data-budget-category]').dataset.category);
    if (event.target.closest('[data-add-item]')) addItem('Biaya tambahan');
  });
  form.addEventListener('input', event => {
    if (event.target === filter || event.target === program || event.target.matches('[data-batch-toggle]')) return;
    if (event.target.matches('[data-plan-name]')) syncBatches();
    invalidate();
  });
  form.addEventListener('change', event => {
    if (event.target === filter) { paint(); return; }
    if (event.target === program || event.target.matches('[data-batch-toggle]')) syncBatches();
    if (event.target.name?.endsWith('[category]') && event.target.value.trim()) {
      const expanded = event.target.closest('[data-item-detail]');
      const row = rows().find(row => row.dataset.index === expanded.dataset.itemDetail);
      const group = category(event.target.value.trim());
      event.target.value = event.target.value.trim();
      group.querySelector('[data-category-items]').append(row, expanded);
      removeEmptyCategories();
    }
    invalidate();
  });
  form.addEventListener('invalid', event => {
    const expanded = event.target.closest('[data-item-detail]');
    const row = event.target.closest('[data-budget-item]') || rows().find(row => row.dataset.index === expanded?.dataset.itemDetail);
    if (row) {
      filter.value = 'all';
      paint();
      if (expanded) toggleDetails(row, true);
    }
  }, true);
  syncBatches();
  invalidate();
})();
