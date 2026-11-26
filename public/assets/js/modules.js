// Tabbed record modules (Resources, Monitoring): Add / View / Edit / Delete modals.
// Record data for the current page comes from <script id="moduleRecords">, so
// View and Edit open instantly without another request.
(function () {
  const dataEl = document.getElementById("moduleRecords");
  const formEl = document.getElementById("recordFormModal");
  if (!dataEl || !formEl || !window.bootstrap) return;

  const records = JSON.parse(dataEl.textContent || "{}");
  const viewEl = document.getElementById("recordViewModal");
  const deleteEl = document.getElementById("recordDeleteModal");
  const modal = (el) => bootstrap.Modal.getOrCreateInstance(el);

  const form = formEl.querySelector("form");
  const title = formEl.querySelector(".modal-title");
  const submit = form.querySelector('[data-role="submit"]');
  const singular = formEl.dataset.singular;

  // Defaults rendered by the server for "Add" (and the failed-save values)
  const initial = new Map();
  form.querySelectorAll("input[name], select[name], textarea[name]").forEach((el) => {
    if (el.name === "_csrf") return;
    initial.set(el, el.multiple ? [...el.selectedOptions].map((o) => o.value) : el.value);
  });

  function clearErrors() {
    form.querySelectorAll(".is-invalid").forEach((el) => el.classList.remove("is-invalid"));
    form.querySelectorAll(".invalid-feedback.d-block").forEach((el) => el.remove());
  }

  function setValues(values) {
    form.querySelectorAll("input[name], select[name], textarea[name]").forEach((el) => {
      if (el.name === "_csrf") return;
      const key = el.name.replace(/\[\]$/, "");
      const v = values ? values[key] : initial.get(el);
      if (el.multiple) {
        const list = (Array.isArray(v) ? v : []).map(String);
        [...el.options].forEach((o) => (o.selected = list.includes(o.value)));
      } else {
        el.value = v === null || v === undefined ? "" : String(v);
      }
    });
    updateYield();
  }

  function openAdd() {
    clearErrors();
    setValues(null);
    form.action = formEl.dataset.storeUrl;
    title.textContent = "Add " + singular;
    submit.textContent = "Save";
    modal(formEl).show();
  }

  function openEdit(id) {
    const rec = records[id];
    if (!rec) return;
    clearErrors();
    setValues(rec.values);
    form.action = formEl.dataset.updateBase + id;
    title.textContent = "Edit " + singular;
    submit.textContent = "Save changes";
    modal(formEl).show();
  }

  function openView(id) {
    const rec = records[id];
    if (!rec || !viewEl) return;
    viewEl.querySelector('[data-role="title"]').textContent = rec.name;
    const body = viewEl.querySelector('[data-role="body"]');
    body.replaceChildren();
    rec.view.forEach(([label, value]) => {
      if (label === "__section") {
        const head = document.createElement("div");
        head.className = "detail-subhead";
        head.textContent = value;
        body.append(head);
        return;
      }
      const row = document.createElement("div");
      const dt = document.createElement("dt");
      const dd = document.createElement("dd");
      dt.textContent = label;
      dd.textContent = value === "" || value === null ? "—" : value;
      row.append(dt, dd);
      body.append(row);
    });
    viewEl.dataset.id = id;
    modal(viewEl).show();
  }

  function openDelete(id) {
    const rec = records[id];
    if (!rec || !deleteEl) return;
    deleteEl.querySelector('[data-role="name"]').textContent = rec.name;
    deleteEl.querySelector('[data-role="form"]').action = deleteEl.dataset.deleteBase + id + "/delete";
    modal(deleteEl).show();
  }

  // "Edit" inside the View modal
  viewEl?.querySelector('[data-role="edit"]')?.addEventListener("click", () => {
    const id = viewEl.dataset.id;
    viewEl.addEventListener("hidden.bs.modal", () => openEdit(id), { once: true });
    modal(viewEl).hide();
  });

  document.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-module-action]");
    if (!btn) return;
    const { moduleAction, id } = btn.dataset;
    if (moduleAction === "add") openAdd();
    else if (moduleAction === "view") openView(id);
    else if (moduleAction === "edit") openEdit(id);
    else if (moduleAction === "delete") openDelete(id);
  });

  // Production: live yield preview (volume / area harvested)
  const yieldBox = form.querySelector("[data-yield-preview]");
  function updateYield() {
    if (!yieldBox) return;
    const vol = parseFloat(form.querySelector('[name="volume"]')?.value);
    const area = parseFloat(form.querySelector('[name="area_harvested_ha"]')?.value);
    const unitSel = form.querySelector('[name="unit"]');
    const unit = unitSel && unitSel.value ? unitSel.value : "units";
    const ok = vol > 0 && area > 0;
    yieldBox.hidden = !ok;
    if (ok) {
      yieldBox.querySelector("[data-yield-value]").textContent =
        (vol / area).toLocaleString(undefined, { maximumFractionDigits: 2 }) + " " + unit + " per ha";
    }
  }
  form.addEventListener("input", updateYield);
  form.addEventListener("change", updateYield);

  // Disable Save while submitting (prevents double entries)
  form.addEventListener("submit", () => {
    submit.disabled = true;
  });

  // Reopen after a failed save: server rendered the values + errors already
  if (formEl.dataset.openOnLoad) {
    const editingId = formEl.dataset.editingId;
    title.textContent = (editingId ? "Edit " : "Add ") + singular;
    updateYield();
    modal(formEl).show();
    const params = new URLSearchParams(location.search);
    params.delete("form");
    params.delete("id");
    history.replaceState(null, "", location.pathname + "?" + params.toString() + location.hash);
  }
})();
