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

// Global slow-operation indicator for AJAX fetches.
const systemLoadingBar = document.createElement("div");
systemLoadingBar.className = "system-loading-bar";
systemLoadingBar.setAttribute("aria-hidden", "true");
document.addEventListener("DOMContentLoaded", () => {
  document.body.appendChild(systemLoadingBar);
});
if (!document.body.contains(systemLoadingBar)) {
  document.body.appendChild(systemLoadingBar);
}
let activeFetches = 0;
const nativeFetch = window.fetch.bind(window);
window.fetch = async (...args) => {
  activeFetches++;
  document.body.classList.add("is-loading");
  systemLoadingBar.classList.add("is-active");
  try {
    return await nativeFetch(...args);
  } finally {
    activeFetches = Math.max(0, activeFetches - 1);
    if (activeFetches === 0) {
      window.setTimeout(() => {
        if (activeFetches === 0) {
          document.body.classList.remove("is-loading");
          systemLoadingBar.classList.remove("is-active");
        }
      }, 180);
    }
  }
};

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
                    <button type="button" class="x-close-btn" data-bs-dismiss="modal" aria-label="Close"><span class="bar"></span><span class="bar"></span></button>
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
// Below 1200px: slide the sidebar in over the content (same breakpoint as style.css).
(function () {
  const toggle = document.getElementById("sidebarToggle");
  const backdrop = document.getElementById("sidebarBackdrop");
  if (!toggle) return;
  const mobile = window.matchMedia("(max-width: 1199px)");
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

  // The content area changes width with the sidebar; tell width-dependent
  // widgets (charts, map) once the slide has finished
  document.getElementById("appSidebar")?.addEventListener("transitionend", (e) => {
    if (e.propertyName === "width") window.dispatchEvent(new Event("resize"));
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

// Keep form actions unambiguous and prevent accidental duplicate submissions.
document.querySelectorAll("form").forEach((form) => {
  form.addEventListener("submit", () => {
    if (form.matches("[data-no-loading], [data-duplicate-check]")) return;
    const submit = form.querySelector('[type="submit"]:not([disabled])');
    if (!submit) return;
    submit.disabled = true;
    submit.dataset.originalHtml = submit.innerHTML;
    submit.innerHTML =
      '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Saving...';
  });
});

// Successful feedback is visible long enough to notice, then closes cleanly.
document.querySelectorAll(".app-content > .alert").forEach((alert) => {
  if (alert.classList.contains("alert-danger")) return;
  window.setTimeout(() => {
    if (window.bootstrap) bootstrap.Alert.getOrCreateInstance(alert).close();
  }, 5000);
});

// Close the mobile navigation after a destination is selected.
document.querySelectorAll(".sidebar a.nav-link-item").forEach((link) => {
  link.addEventListener("click", () => {
    if (window.matchMedia("(max-width: 1199px)").matches) {
      document.body.classList.remove("sidebar-open");
    }
  });
});
