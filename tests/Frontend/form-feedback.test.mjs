import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../public/templates/welding-school/form-feedback.js', import.meta.url), 'utf8');

function setup(fields = [], action = '/admin/batch-operations/1/sessions') {
  const form = { action: `https://academy.test${action}`, method: 'post', dataset: {}, elements: fields,
    append(field) { fields.push(field); }, matches: (value) => value === 'form', querySelectorAll: () => [],
  };
  const events = {};
  const context = { window: { location: { href: 'https://academy.test/' } },
    document: { forms: [form], body: {}, querySelector: () => null, querySelectorAll: () => [form],
      createElement: () => ({}), addEventListener: (name, callback) => { events[name] = callback; },
    }, MutationObserver: class { observe() {} }, URL, Date,
  };
  vm.runInNewContext(source, context);
  return { api: context.window.AppFeedback, form, fields, events };
}

function field(name, type = 'text', extra = {}) {
  return { name, type, tagName: type === 'textarea' ? 'TEXTAREA' : 'INPUT', id: '', placeholder: '', dataset: {},
    value: 'Tidak boleh berubah', getAttribute: (attribute) => attribute === 'aria-label' ? 'Nama sesi' : null,
    ...extra,
  };
}

test('contextual examples are added without changing values or existing placeholders', () => {
  const fields = [field('title'), field('attendance_radius_meters', 'number'), field('email', 'email'), field('notes', 'textarea', { placeholder: 'Petunjuk khusus' }), field('system_number', 'text', { readOnly: true })];
  setup(fields);
  assert.match(fields[0].placeholder, /Praktik pengelasan/);
  assert.equal(fields[1].placeholder, 'Contoh: 100');
  assert.equal(fields[2].placeholder, 'Contoh: nama@contoh.id');
  assert.equal(fields[3].placeholder, 'Petunjuk khusus');
  assert.equal(fields[4].placeholder, '');
  assert.ok(fields.slice(0, 5).every((control) => control.value === 'Tidak boleh berubah'));
});

test('form identity is stable and does not duplicate on dynamic reinitialization', () => {
  const app = setup([field('title')]);
  assert.equal(app.fields.filter((control) => control.name === '_feedback_form').length, 1);
  const signature = app.form.dataset.feedbackForm;
  app.api.initialize(app.form);
  assert.equal(app.fields.filter((control) => control.name === '_feedback_form').length, 1);
  assert.equal(app.form.dataset.feedbackForm, signature);
  const added = field('phone', 'tel');
  app.fields.push(added);
  app.api.initialize(app.form);
  assert.equal(added.placeholder, 'Contoh: 081234567890');
});

test('forms expose Indonesian browser validation instead of English tooltips', () => {
  const { api } = setup();
  const message = (validity, extra = {}) => api.validityMessage(field('title', 'text', { validity, ...extra }));
  assert.equal(message({ valueMissing: true }), 'Lengkapi nama sesi terlebih dahulu.');
  assert.match(message({ typeMismatch: true }, { type: 'email' }), /nama@contoh.id/);
  assert.equal(message({ rangeUnderflow: true }, { min: 25 }), 'Nama sesi minimal 25.');
  assert.equal(message({ rangeOverflow: true }, { max: 1000 }), 'Nama sesi maksimal 1000.');
  assert.match(message({ tooShort: true }, { minLength: 8 }), /minimal 8 karakter/);
  assert.equal(message({ customError: true }, { validationMessage: 'Pilih sesi Terbit terlebih dahulu.' }), 'Pilih sesi Terbit terlebih dahulu.');
});

test('unnamed and nested fields get identifiers for accessible validation targeting', () => {
  const fields = [field(''), field('items[2][quantity]', 'number')];
  setup(fields);
  assert.ok(fields[0].id);
  assert.notEqual(fields[0].id, fields[1].id);
  assert.equal(fields[1].name, 'items[2][quantity]');
});

test('participant camelCase fields receive human examples without changing API field names', () => {
  const fields = [field('fullName'), field('birthPlace'), field('emergencyPhone')];
  setup(fields, '/profile');
  assert.match(fields[0].placeholder, /Budi Santoso/);
  assert.equal(fields[1].placeholder, 'Contoh: Batam');
  assert.equal(fields[2].placeholder, 'Contoh: 081234567890');
  assert.deepEqual(fields.slice(0, 3).map((control) => control.name), ['fullName', 'birthPlace', 'emergencyPhone']);
});

test('only the current connected submission is exposed to asynchronous request handlers', () => {
  const app = setup();
  app.form.isConnected = true;
  app.events.submit({ target: app.form });
  assert.equal(app.api.currentForm(), app.form);
  app.form.isConnected = false;
  assert.equal(app.api.currentForm(), null);
});

const materialSource = fs.readFileSync(new URL('../../public/templates/welding-school/material-publication.js', import.meta.url), 'utf8');
test('publication explains missing prerequisites live and only blocks sharing, not draft settings', () => {
  const listeners = {};
  const checks = [{ key: 'session', done: false, message: 'Pilih sesi' }, { key: 'participant-files', done: true, message: 'File peserta belum ada' }];
  const rows = Object.fromEntries(checks.map((check) => [check.key, { dataset: {}, querySelector: () => ({}) }]));
  const session = { value: '', selectedOptions: [{ dataset: {} }], addEventListener: (name, callback) => { listeners.session = callback; } };
  const visibility = { value: '0', setCustomValidity(value) { this.error = value; }, addEventListener: (name, callback) => { listeners.visibility = callback; } };
  const form = { dataset: { publicationChecks: JSON.stringify(checks) }, elements: { namedItem: (name) => name === 'batch_session_id' ? session : visibility }, querySelector: (selector) => rows[selector.match(/="([^"]+)"/)[1]] };
  vm.runInNewContext(materialSource, { document: { querySelector: () => form }, window: {} });
  assert.equal(visibility.error, '');
  visibility.value = '1'; listeners.visibility();
  assert.match(visibility.error, /Pilih sesi/);
  session.value = '2'; session.selectedOptions = [{ dataset: { sessionStatus: 'draft' } }]; listeners.session();
  assert.match(visibility.error, /Buka Jadwal/);
  session.selectedOptions = [{ dataset: { sessionStatus: 'published' } }]; listeners.session();
  assert.equal(visibility.error, '');
  assert.equal(rows.session.dataset.ready, 'true');
});
