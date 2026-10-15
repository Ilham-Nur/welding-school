import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../public/templates/welding-school/app.js', import.meta.url), 'utf8');
const helper = source.slice(source.indexOf('  function invoiceDocumentLinks('), source.indexOf('  function paidPaymentDetailContent('));
function links(invoice) {
  const context = { invoice, escapeHtml: (value) => String(value).replaceAll('&', '&amp;').replaceAll('"', '&quot;').replaceAll('<', '&lt;') };
  return vm.runInNewContext(`${helper}\ninvoiceDocumentLinks(invoice);`, context);
}

test('unpaid invoice exposes only its invoice PDF link', () => {
  const html = links({ status: 'unpaid', invoice_pdf_url: '/invoices/3/pdf', receipt_pdf_url: null });
  assert.match(html, /href="\/invoices\/3\/pdf"/);
  assert.match(html, /Unduh Invoice \(PDF\)/);
  assert.doesNotMatch(html, /Kuitansi|LUNAS/);
  assert.match(html, /data-loading-download data-loading-title="Menyiapkan invoice PDF"/);
});

test('paid invoice exposes LUNAS invoice and backend-authorized receipt links', () => {
  const html = links({ status: 'paid', invoice_pdf_url: '/invoices/4/pdf', receipt_pdf_url: '/invoices/4/receipt' });
  assert.match(html, /Unduh Invoice LUNAS \(PDF\)/);
  assert.match(html, /href="\/invoices\/4\/receipt"/);
  assert.match(html, /Unduh Kuitansi \(PDF\)/);
  assert.equal((html.match(/data-loading-download/g) || []).length, 2);
  assert.match(html, /data-loading-title="Menyiapkan kuitansi PDF"/);
});

test('missing or revoked receipt entitlement never shows a receipt download', () => {
  assert.equal(links(null), '');
  for (const invoice of [
    { status: 'paid', invoice_pdf_url: '/invoices/1/pdf', receipt_pdf_url: null },
    { status: 'refunded', invoice_pdf_url: '/invoices/1/pdf', receipt_pdf_url: '/invoices/1/receipt' },
    { status: 'unpaid', invoice_pdf_url: '/invoices/1/pdf', receipt_pdf_url: '/invoices/1/receipt' },
  ]) assert.doesNotMatch(links(invoice), /Kuitansi/);
});

test('PDF links use the currently selected invoice and are HTML escaped', () => {
  assert.match(links({ status: 'unpaid', invoice_pdf_url: '/invoices/20/pdf?x="&y=1' }), /x=&quot;&amp;y=1/);
  assert.match(source, /invoiceDocumentLinks\(state.invoice\)/);
  assert.ok((source.match(/invoiceDocumentLinks\(invoice\)/g) || []).length >= 4);
  assert.doesNotMatch(source, /Unduh Invoice \(belum tersedia\)|Unduh Kuitansi \(belum tersedia\)/);
});
