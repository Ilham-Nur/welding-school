import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../public/templates/welding-school/practice.js', import.meta.url), 'utf8');

function setup(options = {}) {
  const calls = [];
  const timers = new Map();
  const listeners = {};
  let timerId = 0;
  let requestId = 0;
  let reloads = 0;
  let version = 0;
  function element(value = '') {
    return { value, hidden: false, disabled: false, textContent: '', dataset: {}, attributes: {}, listeners: {},
      setAttribute(name, value) { this.attributes[name] = value; },
      removeAttribute(name) { delete this.attributes[name]; },
      addEventListener(name, callback) { this.listeners[name] = callback; }, focus() { this.focused = true; },
    };
  }
  const fields = Object.fromEntries(['outcome', 'obstacle', 'notes', 'version', 'request_id', 'photo'].map((name) => [name, element(name === 'version' ? '0' : '')]));
  const elements = Object.fromEntries(['message', 'preview', 'obstacles', 'evidence', 'notes-label'].map((name) => [name, element()]));
  const errorElements = ['outcome', 'notes', 'photo'].map((name) => ({ ...element(), dataset: { error: name } }));
  const buttons = [element('submit'), element('draft')];
  const selectors = { '[data-practice-message]': 'message', '[data-practice-preview]': 'preview', '[data-practice-obstacles]': 'obstacles', '[data-practice-evidence]': 'evidence', '[data-notes-label]': 'notes-label' };
  const form = { ...element(), action: '/my-training/1/practice/1', elements: { namedItem: (name) => fields[name] },
    querySelector: (selector) => elements[selectors[selector]],
    querySelectorAll: (selector) => selector === '[data-error]' ? errorElements : buttons,
  };
  class MockData {
    constructor() { this.values = new Map(Object.entries(fields).map(([key, value]) => [key, value.value])); }
    set(key, value) { this.values.set(key, String(value)); }
    get(key) { return this.values.get(key); }
    delete(key) { this.values.delete(key); }
    append(key, value) { this.values.set(key, value); }
  }
  const context = { document: { querySelector: (selector) => selector === '[data-practice-form]' ? form : { content: 'csrf' },
    createElement: () => ({ getContext: () => ({ fillRect() {}, drawImage() {} }), toBlob: (callback) => callback(new Blob(['small-image'], { type: 'image/jpeg' })) }),
  },
    window: { location: { reload() { reloads += 1; } }, addEventListener: (name, callback) => { listeners[name] = callback; } },
    URL: { createObjectURL: () => 'blob:practice', revokeObjectURL() {} },
    crypto: { randomUUID: () => `request-${++requestId}` },
    FormData: MockData, Blob, AbortController,
    createImageBitmap: async () => ({ width: 4000, height: 3000, close() {} }),
    setTimeout(callback, delay) { timers.set(++timerId, { callback, delay }); return timerId; },
    clearTimeout: (id) => timers.delete(id),
    async fetch(url, init) {
      calls.push(init);
      if (options.fetch) return options.fetch(init, calls.length);
      return { ok: true, status: 200, json: async () => ({ message: 'Tersimpan', record: { version: ++version, status: init.body.get('action') === 'submit' ? 'submitted' : 'draft', photo_url: '/photo' } }) };
    },
  };
  vm.runInNewContext(source, context);
  async function flush() { for (let index = 0; index < 30; index += 1) await Promise.resolve(); }
  function change(name, value) { fields[name].value = value; form.listeners.input({ target: fields[name] }); }
  async function autosave() {
    await flush();
    const timer = [...timers.entries()].find(([, entry]) => entry.delay === 1000);
    assert.ok(timer, 'autosave scheduled');
    timers.delete(timer[0]);
    await timer[1].callback();
    await flush();
  }
  async function submit(action = 'submit') { await form.listeners.submit({ preventDefault() {}, submitter: { value: action } }); }
  return { fields, elements, form, calls, buttons, errorElements, listeners, change, autosave, submit, flush, reloads: () => reloads };
}

test('conditional fields avoid mandatory essays and hide photo for work not started', () => {
  const app = setup();
  assert.equal(app.elements.obstacles.hidden, true);
  app.change('outcome', 'in_progress');
  assert.equal(app.elements.obstacles.hidden, false);
  app.change('obstacle', 'other');
  assert.match(app.elements['notes-label'].textContent, /wajib/);
  app.change('outcome', 'completed');
  assert.equal(app.elements.obstacles.hidden, true);
  assert.match(app.elements['notes-label'].textContent, /opsional/);
  app.change('outcome', 'not_started');
  assert.equal(app.elements.evidence.hidden, true);
});

test('autosave persists only a draft and updates the optimistic version without reloading', async () => {
  const app = setup();
  app.change('notes', 'Catatan sementara');
  await app.autosave();
  assert.equal(app.calls.length, 1);
  assert.equal(app.calls[0].body.get('action'), 'draft');
  assert.equal(app.calls[0].body.get('notes'), 'Catatan sementara');
  assert.equal(app.fields.version.value, 1);
  assert.equal(app.reloads(), 0);
  const event = { preventDefault() { this.blocked = true; } };
  app.listeners.beforeunload(event);
  assert.equal(event.blocked, undefined);
});

test('failed upload preserves photo and notes, reuses request ID and restores loading controls', async () => {
  const app = setup({ fetch: async (init, count) => {
    if (count === 1) throw new Error('offline');
    return { ok: true, status: 200, json: async () => ({ message: 'Draf tersimpan', record: { version: 1, status: 'draft', photo_url: '/photo' } }) };
  } });
  app.change('outcome', 'completed');
  app.change('notes', 'Hasil saya');
  app.fields.photo.files = [new Blob(['photo'], { type: 'image/jpeg' })];
  app.fields.photo.listeners.change();
  await app.flush();
  await app.submit('draft');
  assert.match(app.elements.message.textContent, /Koneksi/);
  assert.equal(app.buttons.some((button) => button.disabled), false);
  assert.equal(app.form.inert, false);
  assert.equal(app.fields.notes.value, 'Hasil saya');
  const event = { preventDefault() { this.blocked = true; } };
  app.listeners.beforeunload(event);
  assert.equal(event.blocked, true);
  await app.submit('draft');
  assert.equal(app.calls.length, 2);
  assert.equal(app.calls[0].body.get('request_id'), app.calls[1].body.get('request_id'));
  assert.ok(app.calls[1].body.get('photo') instanceof Blob);
  assert.equal(app.fields.version.value, 1);
  assert.equal(app.reloads(), 0);
});

test('server validation errors preserve inputs and show the exact field without stuck loading', async () => {
  const app = setup({ fetch: async () => ({ ok: false, status: 422, json: async () => ({ errors: { photo: ['Tambahkan foto hasil.'] } }) }) });
  app.change('outcome', 'completed');
  app.change('notes', 'Tidak hilang');
  await app.submit();
  assert.equal(app.errorElements.find((element) => element.dataset.error === 'photo').textContent, 'Tambahkan foto hasil.');
  assert.equal(app.fields.notes.value, 'Tidak hilang');
  assert.equal(app.form.attributes['aria-busy'], undefined);
  assert.equal(app.buttons.some((button) => button.disabled), false);
  assert.equal(app.reloads(), 0);
});

test('missing outcome or unspecified other obstacle is validated without network writes', async () => {
  const app = setup();
  await app.submit();
  assert.equal(app.calls.length, 0);
  assert.match(app.elements.message.textContent, /Pilih hasil/);
  app.change('outcome', 'in_progress');
  app.change('obstacle', 'other');
  await app.submit();
  assert.equal(app.calls.length, 0);
  assert.equal(app.fields.notes.focused, true);
});

test('successful submission reloads only after server confirmation and removes navigation warning', async () => {
  const app = setup();
  app.change('outcome', 'completed');
  await app.submit();
  assert.equal(app.calls.length, 1);
  assert.equal(app.calls[0].body.get('action'), 'submit');
  assert.equal(app.reloads(), 1);
  const event = { preventDefault() { this.blocked = true; } };
  app.listeners.beforeunload(event);
  assert.equal(event.blocked, undefined);
});

test('new edits during autosave are saved after the first response with the new version', async () => {
  let release;
  const app = setup({ fetch: async (init, count) => {
    if (count === 1) await new Promise((resolve) => { release = resolve; });
    return { ok: true, status: 200, json: async () => ({ message: 'Tersimpan', record: { version: count, status: 'draft' } }) };
  } });
  app.change('notes', 'Pertama');
  const pending = app.autosave();
  while (!release) await app.flush();
  app.change('notes', 'Terbaru');
  release();
  await pending;
  await app.autosave();
  assert.equal(app.calls.length, 2);
  assert.equal(app.calls[1].body.get('version'), '1');
  assert.equal(app.calls[1].body.get('notes'), 'Terbaru');
});

test('invalid image format is rejected locally without losing the existing draft', async () => {
  const app = setup();
  app.fields.photo.files = [new Blob(['svg'], { type: 'image/svg+xml' })];
  app.fields.photo.listeners.change();
  await app.flush();
  assert.match(app.elements.message.textContent, /JPG, PNG, atau WebP/);
  assert.equal(app.calls.length, 0);
});
