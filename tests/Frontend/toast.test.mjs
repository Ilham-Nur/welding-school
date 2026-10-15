import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../public/templates/welding-school/components.js', import.meta.url), 'utf8');
function setup() {
  let nextTimer = 0;
  const timers = new Map();
  function node() {
    const classes = new Set();
    return { dataset: {}, children: [], attributes: {}, listeners: {}, className: '', textContent: '',
      classList: { contains: (name) => classes.has(name), add: (name) => classes.add(name) },
      setAttribute(name, value) { this.attributes[name] = value; },
      append(...items) { this.children.push(...items); items.forEach((item) => { item.parent = this; }); },
      prepend(item) { this.children.unshift(item); item.parent = this; },
      remove() { if (this.parent) this.parent.children.splice(this.parent.children.indexOf(this), 1); },
      addEventListener(name, callback) { this.listeners[name] = callback; },
      get lastElementChild() { return this.children.at(-1); },
    };
  }
  const stack = node();
  const context = { document: { getElementById: (id) => id === 'toast-stack' ? stack : null, createElement: node,
    querySelectorAll: () => [], querySelector: () => null, addEventListener() {},
  }, window: { setTimeout(callback, delay) { timers.set(++nextTimer, { callback, delay }); return nextTimer; }, clearTimeout: (id) => timers.delete(id) }, WeakSet, Map };
  vm.runInNewContext(source, context);
  return { stack, timers, show: context.window.AppToast.show };
}

test('all toast tones use the same accessible card, title, close button, and safe text', () => {
  const app = setup();
  app.show('<img src=x onerror=alert(1)>', 'danger');
  const toast = app.stack.children[0];
  assert.equal(toast.className, 'ui-toast ui-toast--danger');
  assert.equal(toast.attributes.role, 'alert');
  assert.equal(toast.children[1].children[0].textContent, 'Terjadi kesalahan');
  assert.equal(toast.children[1].children[1].textContent, '<img src=x onerror=alert(1)>');
  assert.equal(toast.children[2].attributes['aria-label'], 'Tutup notifikasi');
});

test('duplicate messages are not stacked and at most four notifications are shown', () => {
  const app = setup();
  app.show('Tersimpan', 'success'); app.show('Tersimpan', 'success');
  assert.equal(app.stack.children.length, 1);
  for (let index = 0; index < 5; index += 1) app.show(`Pesan ${index}`, 'info');
  assert.equal(app.stack.children.length, 4);
});

test('hover and keyboard focus pause toast dismissal so users can finish reading', () => {
  const app = setup();
  app.show('Periksa formulir', 'danger');
  const toast = app.stack.children[0];
  assert.ok([...app.timers.values()][0].delay >= 10000);
  toast.listeners.mouseenter(); assert.equal(app.timers.size, 0);
  toast.listeners.mouseleave(); assert.equal(app.timers.size, 1);
  toast.listeners.focusin(); assert.equal(app.timers.size, 0);
  toast.listeners.focusout(); assert.equal(app.timers.size, 1);
});
