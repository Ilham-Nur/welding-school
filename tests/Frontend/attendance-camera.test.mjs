import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../public/templates/welding-school/attendance-capture.js', import.meta.url), 'utf8');

function setup(options = {}) {
  let now = 1791248400000;
  const requests = [];
  const listeners = {};
  const timers = new Map();
  let stopped = 0;
  let reloaded = 0;
  let cameraCalls = 0;
  let locationCalls = 0;
  let nextTimer = 0;
  function element() {
    return { hidden: true, disabled: false, dataset: {}, listeners: {}, textContent: '',
      addEventListener(name, callback) { this.listeners[name] = callback; },
      removeAttribute(name) { delete this[name]; },
      async click() { return this.listeners.click?.(); },
    };
  }
  const elements = Object.fromEntries(['camera', 'preview', 'camera-placeholder', 'capture-message', 'camera-start', 'camera-capture', 'camera-retake', 'camera-submit'].map((name) => [name, element()]));
  elements.camera.videoWidth = 640;
  elements.camera.videoHeight = 480;
  elements.camera.play = async () => {};
  const track = { readyState: 'live', stop() { stopped += 1; this.readyState = 'ended'; } };
  const stream = { getTracks: () => [track], getVideoTracks: () => [track] };
  const root = { dataset: { challengeUrl: '/challenge', submitUrl: '/capture', kind: 'in', latitude: '-6.2', longitude: '106.8', radius: '100', maxAccuracy: '50' },
    querySelector(selector) { return elements[selector.slice(6, -1)]; },
  };
  const document = { hidden: false,
    querySelector(selector) { return selector === '[data-selfie-attendance]' ? root : { content: 'csrf' }; },
    addEventListener(name, callback) { listeners[name] = callback; },
    createElement() { return { getContext: () => ({ drawImage() {} }), toBlob(callback) { callback(new Blob(['camera-frame'], { type: 'image/jpeg' })); } }; },
  };
  const context = {
    document,
    window: { isSecureContext: options.secure ?? true, location: { reload() { reloaded += 1; } }, addEventListener(name, callback) { listeners[name] = callback; } },
    navigator: {
      mediaDevices: { async getUserMedia(settings) { cameraCalls += 1; assert.equal(settings.audio, false); assert.equal(settings.video.facingMode, 'user'); if (options.camera) return options.camera(stream); return stream; } },
      geolocation: { getCurrentPosition(success, failure, settings) {
        locationCalls += 1; assert.equal(settings.maximumAge, 0); assert.equal(settings.enableHighAccuracy, true);
        if (options.geoError) failure({ code: options.geoError });
        else success({ timestamp: now, coords: { latitude: options.latitude ?? -6.2, longitude: 106.8, accuracy: options.accuracy ?? 5 } });
      } },
    },
    Date: class extends Date { static now() { return now; } },
    setTimeout(callback) { timers.set(++nextTimer, callback); return nextTimer; },
    clearTimeout(id) { timers.delete(id); },
    URL: { createObjectURL: () => 'blob:camera-preview', revokeObjectURL() {} },
    FormData, Blob,
    async fetch(url, init) {
      requests.push({ url, init });
      return { ok: true, redirected: false, async json() { return url === '/challenge' ? { token: 'x'.repeat(64), expires_in: 120 } : { message: 'Tercatat' }; } };
    },
  };
  vm.runInNewContext(source, context);
  return { elements, requests, timers, listeners, document, stream,
    get stopped() { return stopped; }, get reloaded() { return reloaded; }, get cameraCalls() { return cameraCalls; }, get locationCalls() { return locationCalls; },
    advance(milliseconds) { now += milliseconds; },
  };
}

test('camera is never opened without a click; unsupported HTTP shows an actionable error', async () => {
  const page = setup({ secure: false });
  assert.equal(page.cameraCalls, 0);
  await page.elements['camera-start'].click();
  assert.equal(page.cameraCalls, 0);
  assert.match(page.elements['capture-message'].textContent, /HTTPS/);
  assert.equal(page.requests.length, 0);
});

test('outside area and imprecise GPS close the camera and cannot create selfie submissions', async () => {
  for (const options of [{ latitude: -6.3 }, { accuracy: 500 }, { geoError: 1 }]) {
    const page = setup(options);
    await page.elements['camera-start'].click();
    assert.equal(page.stopped, 1);
    assert.equal(page.elements['camera-capture'].hidden, true);
    assert.equal(page.elements['camera-submit'].hidden, true);
    assert.equal(page.elements['capture-message'].dataset.error, 'true');
    assert.equal(page.requests.length, 0);
  }
});

test('capture uses a camera frame, a fresh GPS fix and server token, stops tracks and submits once', async () => {
  const page = setup();
  await page.elements['camera-start'].click();
  assert.equal(page.elements['camera-capture'].hidden, false);
  await page.elements['camera-capture'].click();
  assert.equal(page.locationCalls, 2);
  assert.equal(page.stopped, 1);
  assert.equal(page.elements.preview.hidden, false);
  await page.elements['camera-submit'].click();
  assert.deepEqual(page.requests.map((request) => request.url), ['/challenge', '/capture']);
  const body = page.requests[1].init.body;
  assert.equal(body.get('photo').type, 'image/jpeg');
  assert.equal(body.get('photo').name, 'selfie.jpg');
  assert.equal(body.get('token'), 'x'.repeat(64));
  assert.equal(body.get('latitude'), '-6.2');
  assert.equal(body.get('kind'), 'in');
  assert.equal(page.reloaded, 1);
});

test('old evidence cannot be sent and expiry requires taking a fresh photo', async () => {
  const page = setup();
  await page.elements['camera-start'].click();
  await page.elements['camera-capture'].click();
  page.advance(31000);
  await page.elements['camera-submit'].click();
  assert.equal(page.requests.length, 1);
  assert.match(page.elements['capture-message'].textContent, /kedaluwarsa/);
  assert.equal(page.elements['camera-submit'].disabled, true);
});

test('camera access denied does not fall back to file upload', async () => {
  const page = setup({ camera: async () => { const error = new Error('denied'); error.name = 'NotAllowedError'; throw error; } });
  await page.elements['camera-start'].click();
  assert.match(page.elements['capture-message'].textContent, /Izin kamera ditolak/);
  assert.equal(page.requests.length, 0);
  assert.equal(page.elements['camera-submit'].hidden, true);
  assert.doesNotMatch(source, /input.*type.*file|FileReader/);
});

test('leaving the tab closes tracks, including a pending camera permission request', async () => {
  let finish;
  const page = setup({ camera: (stream) => new Promise((resolve) => { finish = () => resolve(stream); }) });
  const opening = page.elements['camera-start'].click();
  page.document.hidden = true;
  page.listeners.visibilitychange();
  finish();
  await opening;
  assert.equal(page.stopped, 1);
  assert.equal(page.locationCalls, 0);
  assert.equal(page.elements['camera-capture'].hidden, true);
});
