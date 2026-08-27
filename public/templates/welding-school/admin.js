(function () {
  "use strict";

  const sidebar = document.getElementById("admin-sidebar");
  const menuButton = document.querySelector("[data-admin-menu]");
  const collapseButton = document.querySelector(
    "[data-admin-sidebar-collapse]",
  );
  const backdrop = document.querySelector("[data-admin-backdrop]");
  const accountButton = document.querySelector("[data-admin-account]");
  const accountMenu = document.querySelector("[data-admin-account-menu]");
  const navigationToggles = document.querySelectorAll(
    "[data-admin-nav-toggle]",
  );

  function closeSidebar() {
    sidebar?.classList.remove("is-open");
    menuButton?.setAttribute("aria-expanded", "false");
    if (backdrop) backdrop.hidden = true;
  }

  function setDesktopSidebar(collapsed) {
    document.body.classList.toggle("admin-sidebar-collapsed", collapsed);
    collapseButton?.setAttribute("aria-expanded", String(!collapsed));
    collapseButton?.setAttribute(
      "title",
      collapsed ? "Buka sidebar" : "Ciutkan sidebar",
    );
    const assistiveLabel = collapseButton?.querySelector(".sr-only");
    if (assistiveLabel) {
      assistiveLabel.textContent = collapsed
        ? "Buka sidebar"
        : "Ciutkan sidebar";
    }
  }

  const savedSidebarState = window.localStorage.getItem(
    "welding-admin-sidebar",
  );
  setDesktopSidebar(savedSidebarState === "collapsed");

  menuButton?.addEventListener("click", () => {
    const open = !sidebar?.classList.contains("is-open");
    sidebar?.classList.toggle("is-open", open);
    menuButton.setAttribute("aria-expanded", String(open));
    if (backdrop) backdrop.hidden = !open;
  });

  backdrop?.addEventListener("click", closeSidebar);

  sidebar?.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => {
      if (window.matchMedia("(max-width: 820px)").matches) closeSidebar();
    });
  });

  sidebar?.querySelectorAll("[data-open-asset-scanner]").forEach((button) => {
    button.addEventListener("click", () => {
      if (window.matchMedia("(max-width: 820px)").matches) closeSidebar();
    });
  });

  collapseButton?.addEventListener("click", () => {
    const collapsed = !document.body.classList.contains(
      "admin-sidebar-collapsed",
    );
    setDesktopSidebar(collapsed);
    window.localStorage.setItem(
      "welding-admin-sidebar",
      collapsed ? "collapsed" : "expanded",
    );
  });

  navigationToggles.forEach((toggle) => {
    toggle.addEventListener("click", () => {
      if (document.body.classList.contains("admin-sidebar-collapsed")) {
        setDesktopSidebar(false);
        window.localStorage.setItem("welding-admin-sidebar", "expanded");
      }

      const group = toggle.closest("[data-admin-nav-group]");
      const items = group?.querySelector("[data-admin-nav-items]");
      if (!group || !items) return;

      const open = items.hidden;
      items.hidden = !open;
      group.classList.toggle("is-open", open);
      toggle.setAttribute("aria-expanded", String(open));
    });
  });

  accountButton?.addEventListener("click", () => {
    const open = Boolean(accountMenu?.hidden);
    if (accountMenu) accountMenu.hidden = !open;
    accountButton.setAttribute("aria-expanded", String(open));
  });

  let pendingConfirmationForm = null;
  const confirmedForms = new WeakSet();

  document.addEventListener("click", (event) => {
    if (!event.target.closest(".admin-account") && accountMenu) {
      accountMenu.hidden = true;
      accountButton?.setAttribute("aria-expanded", "false");
    }

    if (event.target.closest("[data-modal-close]")) {
      pendingConfirmationForm = null;
    }

    const confirmation = event.target.closest("[data-confirm-action]");
    if (confirmation && pendingConfirmationForm) {
      const form = pendingConfirmationForm;
      pendingConfirmationForm = null;
      confirmedForms.add(form);
      form.requestSubmit();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;

    closeSidebar();
    if (accountMenu) accountMenu.hidden = true;
    accountButton?.setAttribute("aria-expanded", "false");
  });

  document.addEventListener("submit", (event) => {
    const form = event.target;
    const dialogId = form.dataset.confirmDialog;
    if (!dialogId) return;

    if (confirmedForms.has(form)) {
      confirmedForms.delete(form);
      return;
    }

    event.preventDefault();
    pendingConfirmationForm = form;
    const dialog = document.getElementById(dialogId);
    if (dialog instanceof HTMLDialogElement && !dialog.open) {
      dialog.showModal();
    }
  });

  document.addEventListener(
    "cancel",
    (event) => {
      if (event.target instanceof HTMLDialogElement) {
        pendingConfirmationForm = null;
      }
    },
    true,
  );
})();
