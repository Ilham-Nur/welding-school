(() => {
  'use strict';
  const forms = new WeakSet();
  const errorsByForm = new WeakMap();
  const pendingInvalid = new Set();
  let sequence = 0;
  let invalidScheduled = false;
  let lastForm = null;
  let lastSubmitAt = 0;

  const key = (name) => String(name || '').replace(/\[([^\]]*)\]/g, '.$1').replace(/\.$/, '').replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);
  const controls = (form) => [...form.elements].filter((control) => ['INPUT', 'SELECT', 'TEXTAREA'].includes(control.tagName) && !['hidden', 'submit', 'button', 'reset'].includes(control.type));

  function label(control) {
    if (control.getAttribute('aria-label')) return control.getAttribute('aria-label');
    const source = control.labels?.[0];
    if (source) {
      const copy = source.cloneNode(true);
      copy.querySelectorAll('input, select, textarea, small, em, [data-required-marker], [data-feedback-error]').forEach((element) => element.remove());
      return copy.textContent.replace(/\s+/g, ' ').trim();
    }
    return key(control.name).split('.').pop().replaceAll('_', ' ');
  }

  function example(control, form) {
    const name = key(control.name).split('.').pop();
    const action = new URL(form.action, window.location.href).pathname;
    const examples = {
      email: 'Contoh: nama@contoh.id', username: 'Contoh: budi.santoso', phone: 'Contoh: 081234567890',
      full_name: 'Contoh: Budi Santoso, sesuai identitas', nik: '16 digit sesuai KTP',
      address: 'Contoh: Jl. Merdeka No. 10, Batam', birth_place: 'Contoh: Batam',
      identity_number: 'Nomor sesuai KTP atau paspor yang dipilih', city: 'Contoh: Batam',
      emergency_name: 'Contoh: Siti Aminah', emergency_phone: 'Contoh: 081234567890',
      position: 'Contoh: 1', capacity: 'Contoh: 15', duration_minutes: 'Contoh: 90',
      effective_minutes: 'Contoh: 90, tidak termasuk waktu istirahat', verified_minutes: 'Contoh: 75, sesuai praktik yang diperiksa',
      attendance_latitude: 'Contoh: -6.2000000', attendance_longitude: 'Contoh: 106.8000000',
      attendance_radius_meters: 'Contoh: 100', late_grace_minutes: 'Contoh: 15',
      objectives: 'Contoh: Peserta mampu menyiapkan alat las dengan aman.',
      topics: 'Contoh: Pemeriksaan alat, APD, dan persiapan area kerja.',
      guidance: 'Contoh: Peragakan pemeriksaan alat sebelum peserta berlatih.',
      instructions: 'Contoh: Ikuti job sheet, lalu foto hasil sambungan setelah latihan.',
      execution_notes: 'Contoh: Praktik selesai; satu mesin perlu diperiksa sebelum sesi berikutnya.',
      reason: 'Jelaskan alasan perubahan dan tindak lanjut yang diperlukan.',
      review_notes: 'Jelaskan hasil pemeriksaan atau bagian yang perlu diperbaiki.',
      notes: 'Tambahkan informasi penting jika diperlukan; tidak perlu mengulang data di atas.',
      label: 'Contoh: Job sheet praktik pengelasan 3G',
      maps_location: 'Tempel link pin Google Maps atau koordinat latitude, longitude.',
    };
    if (examples[name]) return examples[name];
    if (control.type === 'email') return examples.email;
    if (control.type === 'tel') return examples.phone;
    if (control.type === 'password') return control.autocomplete === 'new-password' ? 'Buat kata sandi baru sesuai ketentuan di bawah.' : 'Masukkan kata sandi akun Anda.';
    if (name === 'title' && /sessions/.test(action)) return 'Contoh: Praktik pengelasan FCAW 3G';
    if (name === 'title' && /quality-documents\/training/.test(action)) return 'Contoh: Keselamatan dan persiapan alat las';
    if (name === 'title' && /programs/.test(action)) return 'Contoh: FCAW Welder 3G';
    if (name === 'name' && /budgets/.test(action)) return 'Contoh: RAB FCAW 3G — Angkatan 1';
    if (name === 'name' && /batches/.test(action)) return 'Contoh: FCAW 3G — Angkatan 1';
    if (name === 'name' && /locations/.test(action)) return 'Contoh: Workshop pengelasan A';
    if (name === 'code' && /quality-documents\/training/.test(action)) return 'Contoh: TR-FCAW-001';
    if (name === 'code' && /programs/.test(action)) return 'Contoh: FCAW-3G';
    if (['price', 'amount', 'administration_fee', 'unit_price'].includes(name)) return 'Contoh: 500000, tanpa Rp atau pemisah ribuan';
    return '';
  }

  function initialize(form) {
    if (!forms.has(form)) {
      forms.add(form);
      const signature = `${new URL(form.action, window.location.href).pathname}:${form.method}:${[...document.forms].indexOf(form)}`;
      form.dataset.feedbackForm = signature;
      if (form.method.toLowerCase() !== 'get') {
        const identity = document.createElement('input');
        identity.type = 'hidden'; identity.name = '_feedback_form'; identity.value = signature;
        form.append(identity);
      }
    }
    controls(form).forEach((control) => {
      if (!control.id) control.id = `feedback-field-${++sequence}`;
      const labelSpan = control.labels?.[0]?.querySelector(':scope > span:first-child');
      if (labelSpan && !control.getAttribute('aria-label') && !control.getAttribute('aria-labelledby') && !['radio', 'checkbox'].includes(control.type)) {
        labelSpan.id ||= `${control.id}-label`;
        control.setAttribute('aria-labelledby', labelSpan.id);
      }
      if (!control.placeholder && !control.readOnly && !control.disabled && ['text', 'email', 'tel', 'url', 'number', 'password', 'textarea', 'search'].includes(control.type)) {
        const value = control.dataset.example || example(control, form);
        if (value) control.placeholder = value;
      }
    });
  }

  function candidates(form, name) {
    return controls(form).filter((control) => {
      const field = key(control.name || control.id);
      return field === name || name.startsWith(`${field}.`) || field.startsWith(`${name}.`);
    });
  }

  function clearField(form, name) {
    const fields = candidates(form, name);
    fields.forEach((control) => {
      if (control.dataset.feedbackOwnedInvalid === 'true') {
        control.removeAttribute('aria-invalid'); control.classList.remove('is-invalid');
        delete control.dataset.feedbackOwnedInvalid;
      }
      control.closest('.upload-row')?.classList.remove('has-validation-error');
      const id = control.dataset.feedbackErrorId;
      if (id) {
        control.setAttribute('aria-describedby', (control.getAttribute('aria-describedby') || '').split(/\s+/).filter((value) => value && value !== id).join(' '));
        delete control.dataset.feedbackErrorId;
      }
    });
    form.querySelectorAll('[data-feedback-error]').forEach((element) => { if (element.dataset.feedbackError === name) element.remove(); });
  }

  function mark(form, name, messages) {
    const fields = candidates(form, name);
    if (!fields.length) return null;
    const first = fields[0];
    const existingId = (first.getAttribute('aria-describedby') || '').split(/\s+/).find((id) => document.getElementById(id)?.classList.contains('ui-field__error'));
    const error = existingId ? document.getElementById(existingId) : document.createElement('small');
    error.id ||= `feedback-error-${++sequence}`;
    error.className = 'ui-field__error';
    error.dataset.feedbackError = name;
    error.textContent = messages.join(' ');
    if (!existingId) {
      const uploadCopy = first.closest('.upload-row')?.querySelector('.upload-row__copy');
      const container = uploadCopy || (first.closest('fieldset') && ['radio', 'checkbox'].includes(first.type) ? first.closest('fieldset') : first.closest('label, .ui-field, .admin-field, .practice-field, .field') || first);
      if (container === first) first.insertAdjacentElement('afterend', error);
      else container.append(error);
    }
    fields.forEach((control) => {
      control.classList.add('is-invalid'); control.setAttribute('aria-invalid', 'true');
      control.closest('.upload-row')?.classList.add('has-validation-error');
      control.dataset.feedbackOwnedInvalid = 'true'; control.dataset.feedbackErrorId = error.id;
      control.setAttribute('aria-describedby', [...new Set(`${control.getAttribute('aria-describedby') || ''} ${error.id}`.trim().split(/\s+/))].join(' '));
    });
    return first;
  }

  function summary(form, values, supplied = null) {
    let element = supplied || (form ? form.querySelector('[data-form-error-summary]') : document.querySelector('[data-global-feedback]'));
    if (!element) {
      element = document.createElement('section'); element.dataset.formErrorSummary = '';
      if (!form) element.dataset.globalFeedback = '';
      (form || document.querySelector('#app, #admin-content, main') || document.body).prepend(element);
    }
    element.className = 'ui-feedback-summary'; element.tabIndex = -1; element.setAttribute('role', 'alert');
    element.replaceChildren();
    element.hidden = Object.keys(values).length === 0;
    if (element.hidden) return;
    const heading = document.createElement('strong'); heading.textContent = 'Periksa kembali isian Anda';
    const hint = document.createElement('p'); hint.textContent = 'Data belum tersimpan. Perbaiki bagian berikut, lalu coba kembali.';
    const list = document.createElement('ul');
    Object.entries(values).forEach(([name, messages]) => {
      const target = form ? candidates(form, name)[0] : null;
      messages.forEach((message) => {
        const item = document.createElement('li');
        if (target) {
          const link = document.createElement('a'); link.href = `#${target.id}`; link.textContent = message;
          link.addEventListener('click', (event) => { event.preventDefault(); focusControl(target); });
          item.append(link);
        } else item.textContent = message;
        list.append(item);
      });
    });
    element.append(heading, hint, list);
  }

  function applyErrors(form, values, options = {}) {
    const normalized = Object.fromEntries(Object.entries(values || {}).map(([name, messages]) => [name, Array.isArray(messages) ? messages.map(String) : [String(messages)]]));
    if (form) {
      initialize(form);
      Object.keys(errorsByForm.get(form) || {}).forEach((name) => clearField(form, name));
      errorsByForm.set(form, normalized);
      Object.entries(normalized).forEach(([name, messages]) => mark(form, name, messages));
    }
    summary(form, normalized, options.summary || (form?.dataset.feedbackServer === 'true' ? document.querySelector('[data-feedback-summary]') : null));
    if (options.notify !== false && Object.keys(normalized).length) window.AppToast?.show('Data belum tersimpan. Periksa bagian yang ditandai pada formulir.', 'danger');
    if (options.focus !== false && form) {
      const first = Object.keys(normalized).flatMap((name) => candidates(form, name)).find((control) => !control.disabled && (control.getClientRects().length || control.closest('dialog, details')));
      if (first) focusControl(first);
    }
  }

  function focusControl(control) {
    let parent = control.parentElement;
    while (parent) { if (parent.tagName === 'DETAILS') parent.open = true; parent = parent.parentElement; }
    const dialog = control.closest('dialog');
    if (dialog && !dialog.open) dialog.showModal();
    control.focus(); control.scrollIntoView({ block: 'center', behavior: 'smooth' });
  }

  function validityMessage(control) {
    const validity = control.validity;
    const title = label(control) || 'isian ini';
    if (validity.customError) return control.validationMessage;
    if (validity.valueMissing) return `${['select-one', 'select-multiple', 'radio', 'checkbox', 'file'].includes(control.type) ? 'Pilih' : 'Lengkapi'} ${title.toLowerCase()} terlebih dahulu.`;
    if (validity.typeMismatch && control.type === 'email') return 'Masukkan alamat email yang valid, misalnya nama@contoh.id.';
    if (validity.typeMismatch && control.type === 'url') return 'Masukkan tautan lengkap, misalnya https://contoh.id.';
    if (validity.rangeUnderflow) return `${title} minimal ${control.min}.`;
    if (validity.rangeOverflow) return `${title} maksimal ${control.max}.`;
    if (validity.tooShort) return `${title} minimal ${control.minLength} karakter.`;
    if (validity.tooLong) return `${title} maksimal ${control.maxLength} karakter.`;
    if (validity.patternMismatch) return control.dataset.formatHint || `Format ${title.toLowerCase()} belum sesuai. Ikuti contoh atau petunjuk pada kolom.`;
    if (validity.badInput || validity.stepMismatch) return `Periksa angka pada ${title.toLowerCase()}${control.step && control.step !== 'any' ? `; gunakan kelipatan ${control.step}` : ''}.`;
    return `Periksa ${title.toLowerCase()} sebelum melanjutkan.`;
  }

  document.addEventListener('invalid', (event) => {
    const control = event.target;
    if (!control.form || control.form.hasAttribute('data-native-validation')) return;
    event.preventDefault();
    pendingInvalid.add(control.form);
    if (invalidScheduled) return;
    invalidScheduled = true;
    queueMicrotask(() => {
      invalidScheduled = false;
      pendingInvalid.forEach((form) => {
        const values = {};
        controls(form).filter((field) => field.willValidate && !field.validity.valid).forEach((field) => { values[key(field.name || field.id)] ||= [validityMessage(field)]; });
        applyErrors(form, values);
      });
      pendingInvalid.clear();
    });
  }, true);

  function clearErrors(form, names) {
    const values = errorsByForm.get(form);
    if (!values) return;
    names.forEach((name) => { clearField(form, name); delete values[name]; });
    summary(form, values, form.dataset.feedbackServer === 'true' ? document.querySelector('[data-feedback-summary]') : null);
  }

  function edited(event) {
    const control = event.target;
    if (!control.form) return;
    const values = errorsByForm.get(control.form);
    if (!values) return;
    clearErrors(control.form, Object.keys(values).filter((name) => candidates(control.form, name).includes(control)));
  }
  document.addEventListener('input', edited);
  document.addEventListener('change', edited);

  document.addEventListener('submit', (event) => { lastForm = event.target; lastSubmitAt = Date.now(); }, true);

  function init(root = document) {
    if (root.matches?.('form')) initialize(root);
    root.querySelectorAll?.('form').forEach(initialize);
  }
  init();
  new MutationObserver((records) => {
    records.forEach((record) => record.addedNodes.forEach((node) => {
      if (node.nodeType !== 1 || node.matches('[data-feedback-error], [data-form-error-summary]')) return;
      if (node.closest('form')) initialize(node.closest('form'));
      init(node);
    }));
  }).observe(document.body, { childList: true, subtree: true });

  const configElement = document.querySelector('[data-feedback-config]');
  if (configElement) {
    const config = JSON.parse(configElement.textContent || '{}');
    if (Object.keys(config.errors || {}).length) {
      const allForms = [...document.forms];
      let target = allForms.find((form) => form.dataset.feedbackForm === config.form);
      if (!target) {
        const scores = allForms.map((form) => ({ form, score: Object.keys(config.errors).filter((name) => candidates(form, name).length).length })).sort((a, b) => b.score - a.score);
        if (scores[0]?.score > 0 && scores[0].score !== scores[1]?.score) target = scores[0].form;
      }
      if (target) target.dataset.feedbackServer = 'true';
      applyErrors(target, config.errors, { summary: document.querySelector('[data-feedback-summary]'), notify: false });
    }
  }
  window.AppFeedback = { applyErrors, initialize: init, validityMessage, clearErrors,
    currentForm: () => lastForm?.isConnected && Date.now() - lastSubmitAt < 1000 ? lastForm : null,
  };
})();
