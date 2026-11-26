// Registry list: View / Edit / Delete modals (Register is a plain Bootstrap modal).
// View and Edit load their content from the server (/farmers/{id} and
// /farmers/{id}/edit return just the modal body for X-Requested-With requests),
// so the details and form stay rendered in PHP like every other page.
(function () {
  const viewEl = document.getElementById("viewModal");
  const editEl = document.getElementById("editModal");
  const deleteEl = document.getElementById("deleteModal"); // agriculturists only
  const registerEl = document.getElementById("registerModal");
  if (!viewEl || !editEl || !window.bootstrap) return;

  const modal = (el) => bootstrap.Modal.getOrCreateInstance(el);
  const LOADING =
    '<div class="modal-loading"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Loading…</div>';

  async function load(url, target) {
    target.innerHTML = LOADING;
    try {
      const res = await fetch(url, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
        credentials: "same-origin",
      });
      if (res.redirected) {
        // Logged out meanwhile: follow the server to the login modal
        window.location.href = res.url;
        return false;
      }
      if (!res.ok) throw new Error("HTTP " + res.status);
      target.innerHTML = await res.text();
      return true;
    } catch (err) {
      target.innerHTML =
        '<div class="alert alert-danger mb-0">This profile could not be loaded. Please try again.</div>';
      return false;
    }
  }

  // ---- View
  function openView(btn) {
    viewEl.querySelector('[data-role="title"]').textContent = btn.dataset.name;
    viewEl.dataset.name = btn.dataset.name;
    viewEl.dataset.editUrl = btn.dataset.editUrl;
    viewEl.dataset.updateUrl = btn.dataset.updateUrl;
    modal(viewEl).show();
    load(btn.dataset.url, viewEl.querySelector('[data-role="body"]'));
  }

  // "Edit profile" inside the View modal: swap to the Edit modal
  viewEl.querySelector('[data-role="edit"]').addEventListener("click", () => {
    viewEl.addEventListener(
      "hidden.bs.modal",
      () => openEdit(viewEl.dataset.name, viewEl.dataset.editUrl, viewEl.dataset.updateUrl),
      { once: true },
    );
    modal(viewEl).hide();
  });

  // ---- Edit
  function openEdit(name, editUrl, updateUrl) {
    const form = editEl.querySelector("form");
    const submit = form.querySelector('[type="submit"]');
    form.action = updateUrl;
    editEl.querySelector('[data-role="title"]').textContent = name;
    submit.disabled = true; // until the form has loaded
    modal(editEl).show();
    load(editUrl, editEl.querySelector('[data-role="body"]')).then((ok) => {
      submit.disabled = !ok;
    });
  }

  // ---- Delete
  function openDelete(btn) {
    if (!deleteEl) return;
    deleteEl.querySelector('[data-role="name"]').textContent = btn.dataset.name;
    deleteEl.querySelector('[data-role="form"]').action = btn.dataset.url;
    modal(deleteEl).show();
  }

  document.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-action]");
    if (!btn) return;
    const action = btn.dataset.action;
    if (action === "view") openView(btn);
    else if (action === "edit") openEdit(btn.dataset.name, btn.dataset.url, btn.dataset.updateUrl);
    else if (action === "delete") openDelete(btn);
  });

  // ---- Reopen after a failed save (?register=1 / ?edit={id})
  const params = new URLSearchParams(location.search);
  if (params.has("register") || params.has("edit")) {
    params.delete("register");
    params.delete("edit");
    const qs = params.toString();
    history.replaceState(null, "", location.pathname + (qs ? "?" + qs : "") + location.hash);
  }
  // With a duplicate warning, duplicate-guard.js shows it first and returns
  // to the form on "Back to form"
  if (!window.__duplicatePayload) {
    [registerEl, editEl].forEach((el) => {
      if (el && el.dataset.openOnLoad) modal(el).show();
    });
  }
})();
