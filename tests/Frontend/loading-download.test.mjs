import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../public/templates/welding-school/loading.js', import.meta.url), 'utf8');
const settle = () => new Promise((resolve) => setImmediate(resolve));
function setup() {
  function element() {
    const classes = new Set();
    return { hidden: true, dataset: {}, attributes: {}, style: {},
      classList: { add: (name) => classes.add(name), remove: (name) => classes.delete(name), contains: (name) => classes.has(name) },
      setAttribute(name, value) { this.attributes[name] = value; },
      removeAttribute(name) { delete this.attributes[name]; },
    };
  }
  const elements = Object.fromEntries(['title', 'message', 'progress', 'progress-bar', 'long-message'].map((name) => [name, element()]));
  const overlay = Object.assign(element(), { querySelector: (selector) => elements[selector.slice(14, -1)] });
  const listeners = {};
  const timers = new Map();
  const requests = [];
  const downloads = [];
  const notices = [];
  let sequence = 0;
  let revoked = 0;
  const body = Object.assign(element(), { append(anchor) { downloads.push(anchor); } });
  class DownloadURL extends URL {
    static createObjectURL() { return 'blob:pdf-download'; }
    static revokeObjectURL() { revoked += 1; }
  }
  const context = { Error, URL: DownloadURL,
    document: { body, getElementById: () => overlay, addEventListener(name, callback) { listeners[name] = callback; },
      createElement() { return { click() { this.clicked = true; }, remove() { this.removed = true; } }; } },
    window: { location: { href: 'https://academy.test/#member-programs', origin: 'https://academy.test' },
      setTimeout(callback, delay) { timers.set(++sequence, { callback, delay }); return sequence; },
      clearTimeout(id) { timers.delete(id); }, addEventListener() {}, AppToast: { show(message) { notices.push(message); } } },
    fetch(url, init) { return new Promise((resolve, reject) => requests.push({ url, init, resolve, reject })); },
  };
  vm.runInNewContext(source, context);
  function link() {
    const anchor = Object.assign(element(), { href: 'https://academy.test/invoices/1/pdf', dataset: { loadingTitle: 'Menyiapkan invoice PDF' },
      matches: (selector) => selector.includes('[data-loading-download]'),
    });
    const event = { defaultPrevented: false, button: 0,
      target: { closest: (selector) => selector === 'a[href]' ? anchor : null },
      preventDefault() { this.defaultPrevented = true; } };
    return { anchor, event };
  }
  function click(pageLink) { listeners.click(pageLink.event); }
  function runDelay(delay) {
    for (const [id, timer] of [...timers]) if (timer.delay === delay) { timers.delete(id); timer.callback(); }
  }
  return { overlay, body, elements, requests, downloads, notices, link, click, runDelay,
    loading: context.window.AppLoading, get revoked() { return revoked; } };
}
function response(options = {}) {
  return { ok: options.ok ?? true,
    headers: { get: () => options.attachment === false ? '' : 'attachment; filename="invoice-001.pdf"' },
    async blob() { if (options.blobError) throw new Error('PDF stream failed'); return new Blob(['%PDF-test']); },
  };
}

test('PDF download closes loading after receiving the file without waiting for page navigation', async () => {
  const page = setup();
  const link = page.link();
  page.click(link);
  page.runDelay(0);
  assert.equal(link.event.defaultPrevented, true);
  assert.equal(page.overlay.hidden, false);
  assert.equal(page.elements.title.textContent, 'Menyiapkan invoice PDF');
  assert.equal(page.requests[0].init.credentials, 'same-origin');
  page.requests[0].resolve(response());
  await settle();
  assert.equal(page.downloads[0].download, 'invoice-001.pdf');
  assert.equal(page.downloads[0].clicked, true);
  assert.equal(page.downloads[0].removed, true);
  assert.equal(page.overlay.hidden, true);
  assert.equal(page.body.classList.contains('ui-loading-active'), false);
  assert.equal(link.anchor.classList.contains('is-loading'), false);
  assert.equal(link.anchor.attributes['aria-busy'], undefined);
  page.runDelay(1000);
  assert.equal(page.revoked, 1);
});

test('HTTP, login HTML, network, and PDF body failures always close the loader and allow retry', async () => {
  for (const failure of ['http', 'html', 'network', 'body']) {
    const page = setup();
    const link = page.link();
    page.click(link);
    page.runDelay(0);
    if (failure === 'network') page.requests[0].reject(new Error('offline'));
    else page.requests[0].resolve(response({ ok: failure !== 'http', attachment: failure !== 'html', blobError: failure === 'body' }));
    await settle();
    assert.equal(page.overlay.hidden, true, failure);
    assert.equal(link.anchor.attributes['aria-busy'], undefined);
    assert.equal(link.anchor.classList.contains('is-loading'), false);
    assert.equal(page.downloads.length, 0);
    assert.equal(page.notices.length, 1);
    const retry = page.link();
    page.click(retry);
    page.requests[1].resolve(response());
    await settle();
    assert.equal(page.downloads.length, 1);
    assert.equal(page.overlay.hidden, true);
  }
});

test('finishing a download does not hide loading for an unrelated active request', async () => {
  const page = setup();
  const other = page.loading.show({ title: 'Menyimpan data', delay: 0 });
  const link = page.link();
  page.click(link);
  page.runDelay(0);
  page.requests[0].resolve(response());
  await settle();
  assert.equal(page.overlay.hidden, false);
  assert.equal(page.elements.title.textContent, 'Menyimpan data');
  page.loading.hide(other);
  assert.equal(page.overlay.hidden, true);
});
