(function () {
  "use strict";

  const overlay = document.getElementById("global-loading");
  if (!overlay) return;

  const title = overlay.querySelector("[data-loading-title]");
  const message = overlay.querySelector("[data-loading-message]");
  const progress = overlay.querySelector("[data-loading-progress]");
  const progressBar = overlay.querySelector("[data-loading-progress-bar]");
  const longMessage = overlay.querySelector("[data-loading-long-message]");
  const activeRequests = new Map();
  const pendingForms = new Set();
  let sequence = 0;
  let showTimer = null;
  let longTimer = null;

  function normalizedOptions(options) {
    const settings = typeof options === "string" ? { title: options } : options || {};

    return {
      title: settings.title || overlay.dataset.defaultTitle || "Sedang memproses",
      message: settings.message || overlay.dataset.defaultMessage || "Mohon tunggu sebentar.",
      delay: Number.isFinite(Number(settings.delay)) ? Math.max(0, Number(settings.delay)) : 180,
      progress: settings.progress,
    };
  }

  function latestRequest() {
    return Array.from(activeRequests.values()).at(-1);
  }

  function setProgress(value) {
    if (value === null || value === undefined || value === false) {
      progress.hidden = true;
      progress.removeAttribute("aria-valuenow");
      progressBar.style.width = "0%";
      return;
    }

    const percentage = Math.min(100, Math.max(0, Number(value) || 0));
    progress.hidden = false;
    progress.setAttribute("aria-valuenow", String(Math.round(percentage)));
    progressBar.style.width = `${percentage}%`;
  }

  function render(settings) {
    window.clearTimeout(showTimer);
    window.clearTimeout(longTimer);
    title.textContent = settings.title;
    message.textContent = settings.message;
    longMessage.hidden = true;
    setProgress(settings.progress);
    overlay.hidden = false;
    overlay.setAttribute("aria-hidden", "false");
    document.body.classList.add("ui-loading-active");

    longTimer = window.setTimeout(() => {
      longMessage.hidden = false;
    }, 8000);
  }

  function show(options) {
    const token = `loading-${++sequence}`;
    const settings = normalizedOptions(options);
    activeRequests.set(token, settings);

    if (!overlay.hidden) {
      render(settings);
      return token;
    }

    window.clearTimeout(showTimer);
    showTimer = window.setTimeout(() => {
      if (activeRequests.has(token)) render(settings);
    }, settings.delay);

    return token;
  }

  function hide(token) {
    if (token) activeRequests.delete(token);
    else activeRequests.clear();

    window.clearTimeout(showTimer);
    window.clearTimeout(longTimer);

    const next = latestRequest();
    if (next) {
      render(next);
      return;
    }

    overlay.hidden = true;
    overlay.setAttribute("aria-hidden", "true");
    document.body.classList.remove("ui-loading-active");
    longMessage.hidden = true;
    setProgress(null);
  }

  function update(token, options) {
    if (!activeRequests.has(token)) return;
    const current = activeRequests.get(token);
    const settings = normalizedOptions({ ...current, ...options, delay: 0 });
    activeRequests.set(token, settings);
    if (!overlay.hidden) render(settings);
  }

  function loadingCopy(element, defaults = {}) {
    return {
      title: element?.dataset.loadingTitle || defaults.title,
      message: element?.dataset.loadingMessage || defaults.message,
      delay: element?.dataset.loadingDelay ?? defaults.delay,
    };
  }

  function fileSize(form) {
    return Array.from(form.querySelectorAll('input[type="file"]')).reduce(
      (total, input) => total + Array.from(input.files || []).reduce((size, file) => size + file.size, 0),
      0,
    );
  }

  function setFormBusy(form, submitter) {
    form.setAttribute("aria-busy", "true");
    form.dataset.loadingActive = "true";
    pendingForms.add(form);

    const buttons = submitter
      ? [submitter]
      : Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));

    buttons.forEach((button) => {
      button.dataset.loadingWasDisabled = button.disabled ? "true" : "false";
      button.disabled = true;
      button.classList.add("is-loading");
      button.setAttribute("aria-busy", "true");
    });
  }

  function restoreForms() {
    pendingForms.forEach((form) => {
      form.removeAttribute("aria-busy");
      delete form.dataset.loadingActive;
      form.querySelectorAll("[data-loading-was-disabled]").forEach((button) => {
        button.disabled = button.dataset.loadingWasDisabled === "true";
        delete button.dataset.loadingWasDisabled;
        button.classList.remove("is-loading");
        button.removeAttribute("aria-busy");
      });
    });
    pendingForms.clear();
  }

  function validNavigation(link, event) {
    if (
      event.defaultPrevented ||
      event.button !== 0 ||
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey ||
      link.matches("[data-no-loading], [data-loading-download], [download]") ||
      link.target === "_blank"
    ) {
      return false;
    }

    const url = new URL(link.href, window.location.href);
    if (!["http:", "https:"].includes(url.protocol) || url.origin !== window.location.origin) return false;

    return !(
      url.pathname === window.location.pathname &&
      url.search === window.location.search &&
      url.hash
    );
  }

  function filenameFromDisposition(value) {
    const encoded = value.match(/filename\*=UTF-8''([^;]+)/i)?.[1];
    if (encoded) return decodeURIComponent(encoded.replace(/^"|"$/g, ""));

    return value.match(/filename="?([^";]+)"?/i)?.[1] || "unduhan";
  }

  async function download(link) {
    const settings = loadingCopy(link, {
      title: "Menyiapkan file",
      message: "Data sedang diproses. File akan terunduh secara otomatis setelah siap.",
      delay: 0,
    });
    const token = show(settings);
    link.classList.add("is-loading");
    link.setAttribute("aria-busy", "true");

    try {
      const response = await fetch(link.href, {
        credentials: "same-origin",
        headers: { Accept: "application/octet-stream" },
      });
      const disposition = response.headers.get("Content-Disposition") || "";

      if (!response.ok || !/attachment/i.test(disposition)) {
        throw new Error("File belum dapat disiapkan. Silakan coba kembali.");
      }

      const blob = await response.blob();
      const objectUrl = URL.createObjectURL(blob);
      const trigger = document.createElement("a");
      trigger.href = objectUrl;
      trigger.download = filenameFromDisposition(disposition);
      trigger.hidden = true;
      document.body.append(trigger);
      trigger.click();
      trigger.remove();
      window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
    } catch (error) {
      const errorMessage = error instanceof Error
        ? error.message
        : "File belum dapat disiapkan. Silakan coba kembali.";
      if (window.AppToast?.show) window.AppToast.show(errorMessage, "danger");
      else window.alert(errorMessage);
    } finally {
      hide(token);
      link.classList.remove("is-loading");
      link.removeAttribute("aria-busy");
    }
  }

  window.AppLoading = {
    show,
    hide,
    update,
    setProgress: (token, value) => update(token, { progress: value }),
  };

  document.addEventListener("submit", (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    const submitter = event.submitter;
    if (
      event.defaultPrevented ||
      form.matches("[data-no-loading]") ||
      submitter?.matches("[data-no-loading]") ||
      form.target === "_blank" ||
      form.dataset.loadingActive === "true"
    ) {
      return;
    }

    const hasFiles = fileSize(form) > 0;
    const settings = loadingCopy(submitter || form, {
      title: hasFiles
        ? "Mengunggah data"
        : form.method.toLowerCase() === "get"
          ? "Memuat data"
          : "Menyimpan data",
      message: hasFiles
        ? "File sedang dikirim dan diproses. Mohon jangan menutup halaman."
        : "Permintaan Anda sedang diproses. Mohon tunggu sebentar.",
      delay: 0,
    });

    setFormBusy(form, submitter);
    show(settings);
  });

  document.addEventListener("click", (event) => {
    const preview = event.target.closest("[data-loading-preview]");
    if (preview) {
      const token = show(loadingCopy(preview, {
        title: "Menyiapkan laporan",
        message: "Data sedang diproses. Mohon tunggu sebentar.",
        delay: 0,
      }));
      window.setTimeout(() => hide(token), 2400);
      return;
    }

    const link = event.target.closest("a[href]");
    if (!link) return;

    if (link.matches("[data-loading-download]") && !event.defaultPrevented) {
      event.preventDefault();
      void download(link);
      return;
    }

    if (!validNavigation(link, event)) return;
    show(loadingCopy(link, {
      title: "Memuat halaman",
      message: "Halaman tujuan sedang disiapkan.",
      delay: 0,
    }));
  });

  window.addEventListener("pageshow", () => {
    hide();
    restoreForms();
  });
})();
