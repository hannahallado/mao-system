// System page: Add / Edit User modal and the deactivate / reactivate confirmation.
(function () {
  document.addEventListener("submit", (e) => {
    if (!e.target.matches("[data-confirm-restore]") || e.target.dataset.confirmed) return;
    e.preventDefault();
    confirm(
      "Restore this backup? The current database will be replaced. A pre-restore safety backup will be created automatically.",
      () => {
        e.target.dataset.confirmed = "1";
        e.target.submit();
      },
    );
  });

  // Confirm before deactivating or reactivating (app.js confirm() shows a modal)
  document.addEventListener("submit", (e) => {
    const btn = e.submitter || e.target.querySelector("[data-confirm]");
    if (!btn || !btn.dataset.confirm || e.target.dataset.confirmed) return;
    e.preventDefault();
    confirm(btn.dataset.confirm, () => {
      e.target.dataset.confirmed = "1";
      e.target.submit();
    });
  });

  const modalEl = document.getElementById("userModal");
  const dataEl = document.getElementById("userRecords");
  if (!modalEl || !dataEl || !window.bootstrap) return;

  const users = JSON.parse(dataEl.textContent || "{}");
  const form = modalEl.querySelector("form");
  const title = modalEl.querySelector(".modal-title");
  const hint = modalEl.querySelector('[data-role="hint"]');
  const submit = form.querySelector('[data-role="submit"]');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  const field = (name) => form.querySelector('[name="' + name + '"]');
  const role = field("role");
  const sector = field("sector");

  // Agriculturists always cover all sectors
  function syncSector() {
    const agri = role.value === "agriculturist";
    if (agri) sector.value = "all";
    sector.disabled = agri;
  }
  role.addEventListener("change", syncSector);
  // Disabled fields aren't submitted; send the value anyway
  form.addEventListener("submit", () => {
    sector.disabled = false;
    submit.disabled = true;
  });

  function clearErrors() {
    form.querySelectorAll(".is-invalid").forEach((el) => el.classList.remove("is-invalid"));
    form.querySelectorAll(".invalid-feedback.d-block").forEach((el) => el.remove());
  }

  function open(id) {
    clearErrors();
    const u = id ? users[id] : null;
    field("full_name").value = u ? u.full_name : "";
    field("username").value = u ? u.username : "";
    role.value = u ? u.role : "technician";
    sector.value = u ? u.sector : "all";
    field("password").value = "";
    field("password_confirm").value = "";
    form.action = u ? modalEl.dataset.updateBase + id : modalEl.dataset.storeUrl;
    title.textContent = u ? "Edit User" : "Add User";
    hint.textContent = u
      ? "Leave the password blank to keep the current one."
      : "The user logs in with this username and password.";
    submit.textContent = u ? "Save changes" : "Create account";
    // Password is required only for new accounts
    form.querySelectorAll('label[for="f_password"] .text-danger, label[for="f_password_confirm"] .text-danger')
      .forEach((star) => (star.hidden = !!u));
    syncSector();
    modal.show();
  }

  document.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-user-action]");
    if (!btn) return;
    open(btn.dataset.userAction === "edit" ? btn.dataset.id : null);
  });

  if (modalEl.dataset.openOnLoad) {
    syncSector();
    modal.show();
    const params = new URLSearchParams(location.search);
    params.delete("form");
    params.delete("id");
    history.replaceState(null, "", location.pathname + "?" + params.toString());
  }
})();
