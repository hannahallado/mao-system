// shared JS helpers

// Send the CSRF token with every fetch() POST:
//   fetch(url, { method: 'POST', headers: csrfHeaders(), body: formData })
function csrfHeaders(extra = {}) {
  const token =
    document.querySelector('meta[name="csrf-token"]')?.content ?? "";
  return {
    "X-CSRF-TOKEN": token,
    "X-Requested-With": "XMLHttpRequest",
    ...extra,
  };
}

// Custom confirmation dialog
// Can be used two ways:
// 1. confirm('message', callback) - shows modal and calls callback on confirm
// 2. onsubmit="return confirm('message')" - shows modal and submits form if confirmed
function confirm(message, onConfirm, onCancel = null) {
  // Handle onsubmit usage - when called without callback
  const isFormSubmit = typeof onConfirm !== "function";
  const form = isFormSubmit ? event?.target : null;
  
  const modal = document.createElement("div");
  modal.className = "modal fade";
  modal.setAttribute("role", "dialog");
  modal.setAttribute("tabindex", "-1");
  modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">${escapeHtml(message)}</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirm-btn">Confirm</button>
                </div>
            </div>
        </div>
    `;

  document.body.appendChild(modal);
  const bsModal = new bootstrap.Modal(modal);
  let confirmed = false;

  modal.querySelector("#confirm-btn").addEventListener("click", () => {
    confirmed = true;
    bsModal.hide();
  });

  modal.addEventListener("hidden.bs.modal", () => {
    document.body.removeChild(modal);
    if (confirmed) {
      if (typeof onConfirm === "function") {
        onConfirm();
      } else if (form) {
        form.submit();
      }
    } else if (onCancel) {
      onCancel();
    }
  });

  bsModal.show();
  return false; // Prevent default form submission
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

// Filter bars: dropdowns submit immediately; the search box submits on Enter.
// Empty fields are left out of the URL so shared links stay short (?status=active).
document.querySelectorAll("form[data-auto-submit]").forEach((form) => {
  form.addEventListener("submit", () => {
    form.querySelectorAll("input[name], select[name]").forEach((el) => {
      if (el.value === "") {
        el.disabled = true;
      }
    });
  });
  form
    .querySelectorAll("select")
    .forEach((sel) =>
      sel.addEventListener("change", () => form.requestSubmit()),
    );
});
