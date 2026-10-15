import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../public/templates/welding-school/app.js', import.meta.url), 'utf8');
const destinationSource = source.slice(source.indexOf('  function applyParticipantDestination()'), source.indexOf('  async function loadCurrentApplication('));

function destination(query, applications = []) {
  const href = `https://academy.test/?${query}#member-programs`;
  const state = { applications, dashboardView: 'home', trainingSection: 'overview', step: 0 };
  let replaced;
  const context = { state, participantDestination: new URL(href).searchParams, URL,
    steps: [{ id: 'home' }, { id: 'member-programs' }],
    selectApplication(application) { state.application = application; },
    window: { location: { href }, history: { replaceState(_state, _title, url) { replaced = url; } } },
  };
  vm.runInNewContext(`${destinationSource}\napplyParticipantDestination();`, context);
  return { state, context, replaced };
}

test('training return links select the requested batch and tab, not the latest application', () => {
  const applications = [{ id: 21, training_batch_id: 9, enrollment: {} }, { id: 20, training_batch_id: 4, enrollment: {} }];
  const result = destination('participant_view=training-detail&training_batch=4&training_section=schedule&keep=1', applications);
  assert.equal(result.state.application.id, 20);
  assert.equal(result.state.dashboardView, 'training-detail');
  assert.equal(result.state.trainingSection, 'schedule');
  assert.equal(result.state.step, 1);
  assert.equal(result.replaced.href, 'https://academy.test/?keep=1#member-programs');
  result.state.dashboardView = 'help';
  vm.runInNewContext('applyParticipantDestination();', result.context);
  assert.equal(result.state.dashboardView, 'help', 'destination is consumed once');
});

test('unknown or unowned training batches fall back to the participant training list', () => {
  for (const applications of [[], [{ training_batch_id: 4, enrollment: null }], [{ training_batch_id: 7, enrollment: {} }]]) {
    const result = destination('participant_view=training-detail&training_batch=4&training_section=schedule', applications);
    assert.equal(result.state.dashboardView, 'training');
    assert.equal(result.state.application, undefined);
  }
});

test('dashboard destinations and training tabs are allowlisted', () => {
  for (const view of ['home', 'programs', 'applications', 'payments', 'training', 'help', 'profile']) {
    assert.equal(destination(`participant_view=${view}`).state.dashboardView, view);
  }
  assert.equal(destination('participant_view=admin').replaced, undefined);
  const result = destination('participant_view=training-detail&training_batch=4&training_section=unknown', [{ training_batch_id: 4, enrollment: {} }]);
  assert.equal(result.state.trainingSection, 'overview');
});

const renderSource = source.slice(source.indexOf('  const participantCheckoutTitles ='), source.indexOf('  function startInvoiceTimer()'));
function renderPage(id, loggedIn = true) {
  const toggles = {};
  const context = {
    state: { step: 0, loggedIn }, steps: [{ id }], app: { innerHTML: '', querySelectorAll: () => [] },
    participantSidebar: (section) => `<aside data-section="${section}">Menu peserta</aside>`,
    dashboardShellClasses: (classes) => `dashboard-preview ${classes}`,
    dashboardHeaderActions: () => '<div>Profil & Data Diri</div>',
    document: { querySelectorAll: () => [], querySelector: () => null, body: { classList: { toggle: (key, value) => { toggles[key] = value; } } } },
    startInvoiceTimer() {},
  };
  for (const name of ['Home', 'About', 'Programs', 'News', 'Article', 'Welders', 'RecruiterAccount', 'Certificate', 'MemberPrograms', 'Detail', 'Batch', 'Account', 'ForgotPassword', 'ResetPassword', 'Verification', 'Registration', 'Documents', 'Summary', 'Invoice', 'Payment', 'Success']) {
    context[`render${name}`] = () => `<div>${name} content</div>`;
  }
  vm.runInNewContext(`${renderSource}\nrender();`, context);
  return { html: context.app.innerHTML, toggles };
}

test('all authenticated checkout steps retain participant sidebar, account header, and original content', () => {
  for (const id of ['summary', 'invoice', 'payment', 'success']) {
    const result = renderPage(id);
    assert.match(result.html, /dashboard-preview/);
    assert.match(result.html, /data-section="payments"/);
    assert.match(result.html, /Profil & Data Diri/);
    assert.match(result.html, new RegExp(`${id[0].toUpperCase()}${id.slice(1)} content`));
    assert.equal(result.toggles['dashboard-mode'], true);
  }
});

test('public pages and login retain the public presentation', () => {
  for (const id of ['home', 'about', 'news', 'welders', 'certificate', 'account']) {
    const result = renderPage(id, false);
    assert.doesNotMatch(result.html, /dashboard-preview/);
    assert.equal(result.toggles['dashboard-mode'], false);
  }
});

const trainingContentSource = source.slice(source.indexOf('  function trainingSectionContent()'), source.indexOf('  function invoiceDocumentLinks('));
test('logbook uses the selected enrollment route and never grants unpaid access', () => {
  const context = { state: { trainingSection: 'logbook', application: { training_access: true, practice_url: '/my-training/4/practice' } }, escapeHtml: (value) => String(value) };
  vm.runInNewContext(`${trainingContentSource}\nvar result = trainingSectionContent();`, context);
  assert.match(context.result, /\/my-training\/4\/practice/);
  assert.match(context.result, /Satu pengiriman/);
  context.state.application.training_access = false;
  vm.runInNewContext('result = trainingSectionContent();', context);
  assert.match(context.result, /Akses pelatihan belum tersedia/);
  assert.doesNotMatch(context.result, /href=/);
});
