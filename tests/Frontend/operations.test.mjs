import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../public/templates/welding-school/operations.js', import.meta.url), 'utf8');

test('status selector opens the matching modal without submitting or copying reasons between actions', () => {
  let onChange;
  const status = { value: 'ready', addEventListener: (event, callback) => { assert.equal(event, 'change'); onChange = callback; } };
  const trigger = { dataset: {}, setAttribute(name, value) { this[name] = value; } };
  const control = { dataset: { confirmPrefix: 'confirm-batch-status-12' }, querySelector: (selector) => selector === '[data-ops-status-target]' ? status : trigger };
  vm.runInNewContext(source, { document: { querySelectorAll: () => [control] } });
  for (const value of ['ready', 'cancelled', 'postponed', 'preparation', 'running']) {
    status.value = value;
    onChange();
    assert.equal(trigger.dataset.modalOpen, `confirm-batch-status-12-${value}`);
    assert.equal(trigger['aria-controls'], trigger.dataset.modalOpen);
  }
});

test('pages without a status selector require no special modal initialization', () => {
  assert.doesNotThrow(() => vm.runInNewContext(source, { document: { querySelectorAll: () => [] } }));
});
