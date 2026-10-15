(() => {
  'use strict';
  try {
    const message = sessionStorage.getItem('participant-feedback-success');
    if (message) { sessionStorage.removeItem('participant-feedback-success'); window.AppToast?.show(message, 'success'); }
  } catch (_) { /* Browser privacy settings may disable sessionStorage. */ }
  const shell = document.querySelector('[data-participant-shell]');
  if (!shell) return;
  const mobile = window.matchMedia('(max-width: 720px)');
  const sidebar = shell.querySelector('.dashboard-sidebar');
  const toggles = shell.querySelectorAll('[data-participant-sidebar-toggle]');
  const trigger = shell.querySelector('[data-participant-profile-toggle]');
  const menu = shell.querySelector('[data-participant-profile-menu]');
  const dropdown = menu.querySelector('.profile-dropdown');
  const storageKey = 'welding-participant-sidebar';
  let collapsed = false;
  try { collapsed = window.localStorage.getItem(storageKey) === 'collapsed'; } catch { /* Storage may be disabled. */ }
  shell.classList.toggle('is-sidebar-collapsed', collapsed);

  function syncSidebar() {
    const expanded = mobile.matches ? shell.classList.contains('is-sidebar-open') : !collapsed;
    sidebar.inert = mobile.matches && !expanded;
    toggles.forEach((toggle) => {
      if (!toggle.classList.contains('dashboard-sidebar-toggle')) return;
      toggle.setAttribute('aria-expanded', String(expanded));
      toggle.setAttribute('aria-label', expanded ? 'Ciutkan sidebar' : 'Buka sidebar');
      toggle.querySelector('span').textContent = expanded ? '«' : '»';
    });
  }
  function closeProfile(focus = false) {
    dropdown.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    if (focus) trigger.focus();
  }
  toggles.forEach((toggle) => toggle.addEventListener('click', () => {
    closeProfile();
    if (mobile.matches) shell.classList.toggle('is-sidebar-open');
    else {
      collapsed = !collapsed;
      shell.classList.toggle('is-sidebar-collapsed', collapsed);
      try { window.localStorage.setItem(storageKey, collapsed ? 'collapsed' : 'expanded'); } catch { /* Navigation still works without storage. */ }
    }
    syncSidebar();
  }));
  trigger.addEventListener('click', () => {
    dropdown.hidden = !dropdown.hidden;
    trigger.setAttribute('aria-expanded', String(!dropdown.hidden));
  });
  document.addEventListener('click', (event) => { if (!menu.contains(event.target)) closeProfile(); });
  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    if (!dropdown.hidden) closeProfile(true);
    if (shell.classList.contains('is-sidebar-open')) {
      shell.classList.remove('is-sidebar-open');
      syncSidebar();
      shell.querySelector('.dashboard-sidebar-toggle').focus();
    }
  });
  mobile.addEventListener('change', () => { shell.classList.remove('is-sidebar-open'); syncSidebar(); });
  shell.querySelectorAll('[data-participant-avatar]').forEach((image) => {
    if (image.complete && !image.naturalWidth) image.remove();
    else image.addEventListener('error', () => image.remove(), { once: true });
  });
  syncSidebar();
})();
