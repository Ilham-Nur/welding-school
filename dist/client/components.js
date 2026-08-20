(function () {
  "use strict";

  const toastStack = document.getElementById("toast-stack");
  const dialogTriggers = new Map();

  function toastIcon(type) {
    if (type === "success") return "✓";
    if (type === "warning") return "!";
    if (type === "danger") return "×";
    return "i";
  }

  function removeToast(toast) {
    if (!toast || toast.classList.contains("is-leaving")) return;
    toast.classList.add("is-leaving");
    window.setTimeout(() => toast.remove(), 180);
  }

  function toastDuration(message, tone) {
    const characterCount = String(message || "").trim().length;
    const readingTime = 6000 + Math.ceil(characterCount / 20) * 1000;
    const minimum = ["danger", "warning"].includes(tone) ? 10000 : 8000;

    return Math.min(Math.max(readingTime, minimum), 18000);
  }

  function showToast(message, type) {
    if (!toastStack) return;

    const tone = ["success", "warning", "danger"].includes(type)
      ? type
      : "info";
    const toast = document.createElement("div");
    toast.className = `ui-toast ui-toast--${tone}`;
    toast.setAttribute("role", tone === "danger" ? "alert" : "status");

    const icon = document.createElement("span");
    icon.className = "ui-toast__icon";
    icon.setAttribute("aria-hidden", "true");
    icon.textContent = toastIcon(tone);

    const toastTitles = {
      success: "Berhasil",
      warning: "Peringatan",
      danger: "Terjadi kesalahan",
      info: "Informasi",
    };
    const copy = document.createElement("div");
    copy.className = "ui-toast__copy";

    const title = document.createElement("strong");
    title.className = "ui-toast__title";
    title.textContent = toastTitles[tone];

    const detail = document.createElement("span");
    detail.className = "ui-toast__message";
    detail.textContent = message;

    copy.append(title, detail);

    const close = document.createElement("button");
    close.className = "ui-toast__close";
    close.type = "button";
    close.setAttribute("aria-label", "Tutup notifikasi");
    close.textContent = "×";
    close.addEventListener("click", () => removeToast(toast));

    toast.append(icon, copy, close);
    toastStack.prepend(toast);

    while (toastStack.children.length > 4) {
      toastStack.lastElementChild.remove();
    }

    window.setTimeout(
      () => removeToast(toast),
      toastDuration(message, tone),
    );
  }

  function openDialog(id, trigger) {
    const dialog = document.getElementById(id);
    if (!(dialog instanceof HTMLDialogElement)) return;

    dialogTriggers.set(dialog, trigger);
    if (!dialog.open) dialog.showModal();
  }

  function closeDialog(dialog) {
    if (!(dialog instanceof HTMLDialogElement) || !dialog.open) return;
    dialog.close();
    const trigger = dialogTriggers.get(dialog);
    if (trigger instanceof HTMLElement) trigger.focus();
  }

  document.addEventListener("click", (event) => {
    const modalOpen = event.target.closest("[data-modal-open]");
    if (modalOpen) {
      openDialog(modalOpen.dataset.modalOpen, modalOpen);
      return;
    }

    const modalClose = event.target.closest("[data-modal-close]");
    if (modalClose) {
      closeDialog(modalClose.closest("dialog"));
      return;
    }

    const toastTrigger = event.target.closest("[data-toast]");
    if (toastTrigger) {
      showToast(
        toastTrigger.dataset.toast,
        toastTrigger.dataset.toastType || "info",
      );
      return;
    }

    const alertClose = event.target.closest("[data-alert-close]");
    if (alertClose) {
      const alert = alertClose.closest(".ui-alert");
      alert.style.opacity = "0";
      alert.style.transform = "translateY(-4px)";
      window.setTimeout(() => alert.remove(), 160);
      return;
    }

    const modalSave = event.target.closest("[data-modal-save]");
    if (modalSave) {
      const dialog = modalSave.closest("dialog");
      const form = dialog.querySelector("form");
      if (form && !form.reportValidity()) return;
      closeDialog(dialog);
      showToast("Batch pelatihan berhasil ditambahkan.", "success");
      return;
    }

    const confirmation = event.target.closest("[data-confirm-action]");
    if (confirmation) {
      closeDialog(confirmation.closest("dialog"));
      if (confirmation.dataset.toast) {
        showToast(confirmation.dataset.toast, "success");
      }
    }
  });

  document.querySelectorAll("dialog").forEach((dialog) => {
    dialog.addEventListener("click", (event) => {
      if (event.target === dialog) closeDialog(dialog);
    });
  });

  document.querySelectorAll("[data-file-drop]").forEach((dropZone) => {
    const input = dropZone.querySelector('input[type="file"]');
    const label = dropZone.querySelector("[data-file-label]");
    const defaultLabel = label.textContent;

    function updateFile(file) {
      if (!file) {
        dropZone.classList.remove("has-file");
        label.textContent = defaultLabel;
        return;
      }

      if (file.size > 5 * 1024 * 1024) {
        input.value = "";
        dropZone.classList.remove("has-file");
        label.textContent = defaultLabel;
        showToast("Ukuran file melebihi batas maksimal 5 MB.", "danger");
        return;
      }

      dropZone.classList.add("has-file");
      label.textContent = `${file.name} · ${(file.size / 1024 / 1024).toFixed(2)} MB`;
      showToast(`File ${file.name} siap diunggah.`, "success");
    }

    input.addEventListener("change", () => updateFile(input.files[0]));

    ["dragenter", "dragover"].forEach((name) => {
      dropZone.addEventListener(name, (event) => {
        event.preventDefault();
        dropZone.classList.add("is-dragging");
      });
    });

    ["dragleave", "drop"].forEach((name) => {
      dropZone.addEventListener(name, (event) => {
        event.preventDefault();
        dropZone.classList.remove("is-dragging");
      });
    });

    dropZone.addEventListener("drop", (event) => {
      const file = event.dataTransfer.files[0];
      if (!file) return;

      try {
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
      } catch (_error) {
        // Some older browsers do not allow assigning FileList.
      }

      updateFile(file);
    });
  });

  document.querySelectorAll("[data-demo-form]").forEach((form) => {
    form.addEventListener("submit", (event) => {
      event.preventDefault();
      if (!form.reportValidity()) return;
      showToast("Contoh data peserta berhasil disimpan.", "success");
    });

    form.addEventListener("reset", () => {
      window.setTimeout(() => {
        showToast("Formulir dikembalikan ke kondisi awal.", "info");
      });
    });
  });

  const menuButton = document.querySelector("[data-ui-menu]");
  const navigation = document.getElementById("public-navigation");
  if (menuButton && navigation) {
    menuButton.addEventListener("click", () => {
      const isOpen = navigation.classList.toggle("is-open");
      menuButton.setAttribute("aria-expanded", String(isOpen));
      menuButton.setAttribute("aria-label", isOpen ? "Tutup menu" : "Buka menu");
    });
  }

  document.querySelectorAll("[data-flash-toast]").forEach((trigger) => {
    trigger.click();
  });
})();
