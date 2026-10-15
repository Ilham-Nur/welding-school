(function () {
  "use strict";

  document.querySelectorAll("[data-ops-status-control]").forEach((control) => {
    const status = control.querySelector('[data-ops-status-target]');
    const trigger = control.querySelector('[data-modal-open]');

    function updateStatusFields() {
      const dialogId = `${control.dataset.confirmPrefix}-${status.value}`;
      trigger.dataset.modalOpen = dialogId;
      trigger.setAttribute('aria-controls', dialogId);
    }

    status.addEventListener("change", updateStatusFields);
    updateStatusFields();
  });
})();
