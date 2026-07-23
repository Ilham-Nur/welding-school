/**
 * Welding School UI helpers
 * Bisa dipakai langsung pada Laravel Blade atau halaman HTML biasa.
 *
 * Data attributes:
 * - data-menu-toggle="#main-menu"
 * - data-drawer-open="#program-detail"
 * - data-drawer-close
 * - data-tab-target="#panel-id"
 * - data-toast="Pesan berhasil"
 */
(function () {
  "use strict";

  const select = (selector, scope = document) => scope.querySelector(selector);
  const selectAll = (selector, scope = document) => [
    ...scope.querySelectorAll(selector),
  ];

  function toggleMenu(button) {
    const menu = select(button.dataset.menuToggle);
    if (!menu) return;
    const isOpen = menu.classList.toggle("is-open");
    button.setAttribute("aria-expanded", String(isOpen));
  }

  function openDrawer(selector) {
    const drawer = select(selector);
    if (!drawer) return;
    drawer.hidden = false;
    drawer.classList.add("is-open");
    document.body.style.overflow = "hidden";
    select("[data-drawer-close]", drawer)?.focus();
  }

  function closeDrawer(drawer) {
    if (!drawer) return;
    drawer.classList.remove("is-open");
    drawer.hidden = true;
    document.body.style.overflow = "";
  }

  function showToast(message) {
    let toast = select("#welding-toast");
    if (!toast) {
      toast = document.createElement("div");
      toast.id = "welding-toast";
      toast.className = "toast";
      toast.setAttribute("role", "status");
      document.body.appendChild(toast);
    }
    toast.textContent = `✓ ${message}`;
    toast.hidden = false;
    window.clearTimeout(showToast.timer);
    showToast.timer = window.setTimeout(() => {
      toast.hidden = true;
    }, 3200);
  }

  function activateTab(button) {
    const group = button.closest("[data-tabs]");
    if (!group) return;
    selectAll("[data-tab-target]", group).forEach((tab) => {
      const active = tab === button;
      tab.classList.toggle("is-active", active);
      tab.setAttribute("aria-selected", String(active));
      const panel = select(tab.dataset.tabTarget);
      if (panel) panel.hidden = !active;
    });
  }

  document.addEventListener("click", (event) => {
    const target = event.target.closest(
      "[data-menu-toggle], [data-drawer-open], [data-drawer-close], [data-tab-target], [data-toast]",
    );
    if (!target) return;

    if (target.matches("[data-menu-toggle]")) toggleMenu(target);
    if (target.matches("[data-drawer-open]")) {
      openDrawer(target.dataset.drawerOpen);
    }
    if (target.matches("[data-drawer-close]")) {
      closeDrawer(target.closest("[data-drawer]"));
    }
    if (target.matches("[data-tab-target]")) activateTab(target);
    if (target.matches("[data-toast]")) showToast(target.dataset.toast);
  });

  document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;
    closeDrawer(select("[data-drawer].is-open"));
  });

  window.WeldingUI = {
    openDrawer,
    closeDrawer,
    showToast,
  };
})();
