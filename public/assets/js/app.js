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

// Sidebar menu button (top bar, left).
// Desktop: switch between the full sidebar and the icon-only rail, remembered
// in localStorage. Clicking a group icon in the rail expands the sidebar and
// opens that group.
// Small screens (< 992px): slide the sidebar in over the content.
(function () {
  const toggle = document.getElementById("sidebarToggle");
  const backdrop = document.getElementById("sidebarBackdrop");
  if (!toggle) return;
  const mobile = window.matchMedia("(max-width: 991px)");
  const body = document.body;

  function syncAria() {
    const open = mobile.matches
      ? body.classList.contains("sidebar-open")
      : !body.classList.contains("sidebar-collapsed");
    toggle.setAttribute("aria-expanded", open ? "true" : "false");
  }

  toggle.addEventListener("click", () => {
    if (mobile.matches) {
      body.classList.toggle("sidebar-open");
    } else {
      const collapsed = body.classList.toggle("sidebar-collapsed");
      try {
        localStorage.setItem("mao_sidebar", collapsed ? "collapsed" : "open");
      } catch (e) {}
    }
    syncAria();
  });

  // Rail: a group's links are hidden, so expand the sidebar and open the group
  document.querySelectorAll(".sidebar .nav-group-toggle").forEach((btn) => {
    btn.addEventListener(
      "click",
      (e) => {
        if (mobile.matches || !body.classList.contains("sidebar-collapsed")) return;
        e.preventDefault();
        e.stopPropagation(); // keep Bootstrap from toggling it closed
        body.classList.remove("sidebar-collapsed");
        try {
          localStorage.setItem("mao_sidebar", "open");
        } catch (err) {}
        syncAria();
        const target = document.querySelector(btn.dataset.bsTarget);
        if (target && window.bootstrap) {
          bootstrap.Collapse.getOrCreateInstance(target, { toggle: false }).show();
        }
      },
      true, // capture: run before Bootstrap's own click handler
    );
  });

  backdrop?.addEventListener("click", () => {
    body.classList.remove("sidebar-open");
    syncAria();
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && body.classList.contains("sidebar-open")) {
      body.classList.remove("sidebar-open");
      syncAria();
      toggle.focus();
    }
  });

  mobile.addEventListener("change", () => {
    body.classList.remove("sidebar-open");
    syncAria();
  });

  // Animate the desktop hide/show only after the first paint, so restoring a
  // collapsed sidebar on page load doesn't slide
  requestAnimationFrame(() =>
    requestAnimationFrame(() => body.classList.add("sidebar-anim")),
  );
  syncAria();
})();

// Page slide-up (see .page-anim in style.css): once the content has finished
// sliding, drop the class so the temporary clip/transform don't affect
// sticky elements, dropdowns or modals inside the page
(function () {
  const content = document.querySelector(".app-content");
  const done = () => document.body.classList.remove("page-anim");
  if (!content || window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    done();
    return;
  }
  content.addEventListener("animationend", (e) => {
    if (e.target === content) done();
  });
  setTimeout(done, 1500); // fallback if animationend never fires
})();

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
