(() => {
  const form = document.querySelector('[data-practice-form]');
  if (!form) return;
  const field = (name) => form.elements.namedItem(name);
  const message = form.querySelector('[data-practice-message]');
  const preview = form.querySelector('[data-practice-preview]');
  const buttons = [...form.querySelectorAll('button[type="submit"]')];
  let timer;
  let generation = 0;
  let savedGeneration = 0;
  let photo = null;
  let previewUrl = null;
  let failedRequest = null;
  let queue = Promise.resolve();
  let preparing = Promise.resolve();
  let sending = false;
  let disposed = false;
  let explicitAction = null;

  function say(text, error = false) {
    message.textContent = text;
    message.dataset.error = String(error);
  }

  function errors(values = {}) {
    if (window.AppFeedback) {
      window.AppFeedback.applyErrors(form, values, { notify: false, focus: Object.keys(values).length > 0 });
      return;
    }
    form.querySelectorAll('[data-error]').forEach((element) => {
      element.textContent = (values[element.dataset.error] || []).join(' ');
    });
    for (const name of ['outcome', 'notes', 'photo']) {
      const control = field(name);
      if (control?.setAttribute) control.setAttribute('aria-invalid', String(Boolean(values[name])));
    }
  }

  function conditionalFields() {
    const outcome = field('outcome').value;
    form.querySelector('[data-practice-obstacles]').hidden = !outcome || outcome === 'completed';
    form.querySelector('[data-practice-evidence]').hidden = outcome === 'not_started';
    form.querySelector('[data-notes-label]').textContent = outcome !== 'completed' && field('obstacle').value === 'other'
      ? 'Keterangan kendala lainnya (wajib saat kirim)' : 'Catatan (opsional)';
  }

  function changed() {
    generation += 1;
    errors();
    conditionalFields();
    clearTimeout(timer);
    say('Perubahan belum tersimpan…');
    timer = setTimeout(() => enqueue('draft'), 1000);
  }

  async function compress(file) {
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) throw new Error('Gunakan foto JPG, PNG, atau WebP.');
    if (file.size > 25 * 1024 * 1024) throw new Error('Foto terlalu besar. Pilih foto di bawah 25 MB untuk diperkecil.');
    if (typeof createImageBitmap !== 'function') {
      if (file.size > 5 * 1024 * 1024) throw new Error('Browser ini tidak dapat memperkecil foto. Pilih foto maksimal 5 MB.');
      return file;
    }
    const bitmap = await createImageBitmap(file);
    try {
      const scale = Math.min(1, 1600 / Math.max(bitmap.width, bitmap.height));
      const canvas = document.createElement('canvas');
      canvas.width = Math.max(1, Math.round(bitmap.width * scale));
      canvas.height = Math.max(1, Math.round(bitmap.height * scale));
      const context = canvas.getContext('2d');
      context.fillStyle = '#fff';
      context.fillRect(0, 0, canvas.width, canvas.height);
      context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
      const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));
      if (!blob || blob.size > 5 * 1024 * 1024) throw new Error('Foto belum dapat diperkecil. Coba foto lain.');
      return blob;
    } finally {
      bitmap.close();
    }
  }

  function body(action) {
    const data = new FormData(form);
    data.delete('photo');
    if (photo && field('outcome').value !== 'not_started') data.append('photo', photo, 'hasil-praktik.jpg');
    data.set('action', action);
    data.set('request_id', crypto.randomUUID());
    data.set('version', field('version').value);
    return { action, data, generation, photo };
  }

  async function request(pending) {
    say(pending.action === 'submit' ? 'Mengirim hasil…' : 'Menyimpan draf…');
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 30000);
    let response;
    let result;
    try {
      response = await fetch(form.action, {
        method: 'POST', credentials: 'same-origin', body: pending.data, signal: controller.signal,
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
      });
      result = await response.json().catch(() => null);
    } catch (error) {
      failedRequest = pending;
      throw new Error('Koneksi terputus atau terlalu lama. Isian dan foto tetap di halaman ini; tekan Simpan draf / Kirim untuk mencoba lagi sebelum menutup halaman.');
    } finally {
      clearTimeout(timeout);
    }
    if (!response.ok || response.redirected || !result?.record) {
      // Keep the request ID for server/network failures; retry may follow a committed write.
      failedRequest = response.status >= 500 || (response.ok && !result?.record) ? pending : null;
      errors(result?.errors);
      throw new Error(Object.values(result?.errors || {}).flat().join(' ') || (response.status === 419 || response.redirected
        ? 'Sesi login berakhir. Isian masih di halaman ini; buka login di tab lain lalu coba kembali.'
        : 'Belum tersimpan. Periksa koneksi dan coba kembali.'));
    }
    failedRequest = null;
    field('version').value = result.record.version;
    savedGeneration = pending.generation;
    if (photo === pending.photo) photo = null;
    if (result.record.photo_url && !photo) {
      if (previewUrl) URL.revokeObjectURL(previewUrl);
      previewUrl = null;
      preview.src = `${result.record.photo_url}?v=${result.record.version}`;
      preview.hidden = false;
      field('photo').value = '';
    }
    say(result.message);
    if (sending && explicitAction === 'draft' && pending.action === 'draft') window.AppToast?.show(result.message, 'success');
    if (result.record.status === 'submitted' || result.record.status === 'accepted') {
      try { window.sessionStorage?.setItem('participant-feedback-success', result.message); } catch (_) { /* Optional flash only. */ }
      disposed = true;
      clearTimeout(timer);
      window.location.reload();
    }
  }

  async function save(action) {
    if (disposed) return;
    await preparing;
    if (action === 'submit') {
      if (!field('outcome').value) {
        errors({ outcome: ['Pilih hasil aktivitas terlebih dahulu.'] });
        throw new Error('Pilih hasil aktivitas terlebih dahulu.');
      }
      if (field('outcome').value !== 'completed' && field('obstacle').value === 'other' && !field('notes').value.trim()) {
        errors({ notes: ['Tulis keterangan singkat untuk kendala lainnya.'] });
        field('notes').focus();
        throw new Error('Tulis keterangan singkat untuk kendala lainnya.');
      }
    }
    if (failedRequest) await request(failedRequest);
    if (disposed || (action === 'draft' && savedGeneration === generation)) return;
    await request(body(action));
    if (!disposed && savedGeneration !== generation) {
      clearTimeout(timer);
      timer = setTimeout(() => enqueue('draft'), 1000);
    }
  }

  function enqueue(action) {
    queue = queue.then(() => save(action)).catch((error) => { say(error.message, true); window.AppToast?.show(error.message, 'danger'); });
    return queue;
  }

  form.addEventListener('input', (event) => {
    if (event.target !== field('photo') && !sending) changed();
  });
  field('photo').addEventListener('change', () => {
    const selected = field('photo').files?.[0];
    if (!selected) return;
    generation += 1;
    clearTimeout(timer);
    say('Memperkecil foto…');
    preparing = preparing.then(async () => {
      try {
        photo = await compress(selected);
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(photo);
        preview.src = previewUrl;
        preview.hidden = false;
        changed();
      } catch (error) {
        field('photo').value = '';
        say(error.message, true);
        errors({ photo: [error.message] });
      }
    });
  });
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (sending || disposed) return;
    const action = event.submitter?.value || 'submit';
    clearTimeout(timer);
    sending = true;
    explicitAction = action;
    buttons.forEach((button) => { button.disabled = true; });
    form.setAttribute('aria-busy', 'true');
    // Inert blocks pointer and keyboard edits without omitting values from FormData.
    form.inert = true;
    try {
      await enqueue(action);
    } finally {
      sending = false;
      buttons.forEach((button) => { button.disabled = false; });
      form.inert = false;
      form.removeAttribute('aria-busy');
      form.querySelector('[aria-invalid="true"]')?.focus();
      explicitAction = null;
    }
  });
  window.addEventListener('online', () => { if (failedRequest || savedGeneration !== generation) enqueue('draft'); });
  window.addEventListener('beforeunload', (event) => {
    if (!disposed && (failedRequest || savedGeneration !== generation)) { event.preventDefault(); event.returnValue = ''; }
  });
  window.addEventListener('pagehide', () => { if (previewUrl) URL.revokeObjectURL(previewUrl); });
  conditionalFields();
})();
