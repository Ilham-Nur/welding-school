import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../public/templates/welding-school/attendance-area.js', import.meta.url), 'utf8');
function setup(options = {}) {
  function element(value = '') {
    return { value, checked: false, hidden: true, disabled: false, dataset: {}, listeners: {},
      addEventListener(name, callback) { this.listeners[name] = callback; },
      removeAttribute(name) { delete this[name]; },
      click() { return this.listeners.click?.(); },
      input(value) { this.value = value; this.listeners.input?.(); },
    };
  }
  const fields = { attendance_latitude: element(), attendance_longitude: element(), attendance_radius_meters: element('100') };
  const elements = Object.fromEntries(['location', 'maps-resolve', 'maps-input', 'confirm', 'preview', 'preview-text', 'map-link', 'message'].map((key) => [key, element()]));
  const requests = [];
  const form = { elements: fields, dataset: { resolveUrl: '/resolve' }, listeners: {},
    querySelector: (selector) => elements[selector.slice(11, -1)],
    addEventListener(name, callback) { this.listeners[name] = callback; },
  };
  const response = options.response || { latitude: -6.2, longitude: 106.8 };
  const context = { document: { querySelector: (selector) => selector === '[data-attendance-area]' ? form : { content: 'csrf' } },
    window: { isSecureContext: true }, navigator: { geolocation: { getCurrentPosition(success) { success({ coords: { latitude: -6.2, longitude: 106.8, accuracy: options.accuracy ?? 5 } }); } } },
    fetch: async (url, init) => {
      requests.push({ url, init });
      if (options.fetch) return options.fetch();
      return { ok: options.ok ?? true, status: options.status ?? 200, redirected: options.redirected ?? false, json: async () => response };
    },
  };
  vm.runInNewContext(source, context);
  return { fields, elements, requests, submit() {
    let prevented = false;
    form.listeners.submit({ preventDefault() { prevented = true; } });
    return prevented;
  } };
}

test('reading a map input previews coordinates without saving and requires confirmation', async () => {
  const page = setup();
  page.elements['maps-input'].input('https://maps.app.goo.gl/Example');
  await page.elements['maps-resolve'].click();
  assert.equal(page.requests.length, 1);
  assert.equal(JSON.parse(page.requests[0].init.body).maps_location, 'https://maps.app.goo.gl/Example');
  assert.equal(page.requests[0].init.headers['X-CSRF-TOKEN'], 'csrf');
  assert.equal(page.fields.attendance_latitude.value, '-6.2000000');
  assert.equal(page.elements.preview.hidden, false);
  assert.match(page.elements['map-link'].href, /query=-6.2000000%2C106.8000000/);
  assert.equal(page.submit(), true);
  page.elements.confirm.checked = true;
  assert.equal(page.submit(), false);
  page.fields.attendance_radius_meters.input('150');
  assert.equal(page.elements.confirm.checked, false);
  assert.match(page.elements['preview-text'].textContent, /150 meter/);
});

test('edited map input must be reread and manual coordinates clear the old map source', async () => {
  const page = setup();
  page.elements['maps-input'].input('-6.2,106.8');
  await page.elements['maps-resolve'].click();
  page.elements['maps-input'].input('-6.3,106.9');
  page.elements.confirm.checked = true;
  assert.equal(page.submit(), true);
  page.fields.attendance_latitude.input('-6.3');
  assert.equal(page.elements['maps-input'].value, '');
  assert.equal(page.elements.confirm.checked, false);
  page.elements.confirm.checked = true;
  assert.equal(page.submit(), false);
});

test('a stale asynchronous response cannot overwrite a newer manual point', async () => {
  let resolve;
  const page = setup({ fetch: () => new Promise((done) => { resolve = done; }) });
  page.elements['maps-input'].input('-6.2,106.8');
  const reading = page.elements['maps-resolve'].click();
  assert.equal(page.submit(), true);
  page.fields.attendance_latitude.input('-6.3');
  resolve({ ok: true, json: async () => ({ latitude: -6.2, longitude: 106.8 }) });
  await reading;
  assert.equal(page.fields.attendance_latitude.value, '-6.3');
  assert.match(page.elements.message.textContent, /Hasil lama tidak diterapkan/);
  assert.equal(page.elements['maps-resolve'].disabled, false);
});

test('invalid links and expired authentication show errors and never overwrite coordinates', async () => {
  for (const options of [{ ok: false, response: { errors: { maps_location: ['Salin koordinat pin.'] } } }, { redirected: true }]) {
    const page = setup(options);
    page.elements['maps-input'].input('invalid');
    await page.elements['maps-resolve'].click();
    assert.equal(page.elements.message.dataset.error, 'true');
    assert.equal(page.fields.attendance_latitude.value, '');
    assert.equal(page.elements['maps-resolve'].disabled, false);
  }
});

test('device location still previews the point, rejects bad accuracy, and requires confirmation', async () => {
  const page = setup();
  await page.elements.location.click();
  assert.equal(page.fields.attendance_latitude.value, '-6.2000000');
  assert.equal(page.submit(), true);
  page.elements.confirm.checked = true;
  assert.equal(page.submit(), false);
  const inaccurate = setup({ accuracy: 100 });
  await inaccurate.elements.location.click();
  assert.equal(inaccurate.fields.attendance_latitude.value, '');
  assert.equal(inaccurate.elements.message.dataset.error, 'true');
});
