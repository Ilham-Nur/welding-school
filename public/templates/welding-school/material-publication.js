(() => {
  const form = document.querySelector('[data-material-publication]');
  if (!form) return;
  const checks = JSON.parse(form.dataset.publicationChecks);
  const session = form.elements.namedItem('batch_session_id');
  const visibility = form.elements.namedItem('participant_visible');
  function refresh() {
    const selected = session.selectedOptions[0];
    const sessionCheck = checks.find((check) => check.key === 'session');
    sessionCheck.done = selected?.dataset.sessionStatus === 'published';
    sessionCheck.message = !session.value ? 'Pilih sesi untuk materi ini melalui kolom Sesi di atas.' : 'Sesi belum Terbit. Buka Jadwal, terbitkan sesi, lalu kembali ke pengaturan ini.';
    checks.forEach((check) => {
      const row = form.querySelector(`[data-publication-key="${check.key}"]`);
      row.dataset.ready = String(check.done);
      row.querySelector('[data-check-mark]').textContent = check.done ? '✓' : '○';
      const message = row.querySelector('[data-check-message]');
      message.hidden = check.done; message.textContent = check.message;
    });
    const missing = checks.filter((check) => !check.done);
    visibility.setCustomValidity(visibility.value === '1' && missing.length ? missing.map((check) => check.message).join(' ') : '');
    if (!visibility.validationMessage) window.AppFeedback?.clearErrors(form, ['participant_visible']);
  }
  session.addEventListener('change', refresh);
  visibility.addEventListener('change', refresh);
  refresh();
})();
