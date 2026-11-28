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

// Bulk profile import: CSV or Excel, with preview and duplicate-safe server insertion.
(function () {
  const fileInput = document.getElementById("profileImportFile");
  const submit = document.getElementById("profileImportSubmit");
  const preview = document.getElementById("profileImportPreview");
  const previewHead = document.getElementById("profileImportPreviewHead");
  const previewBody = document.getElementById("profileImportPreviewBody");
  const result = document.getElementById("profileImportResult");
  const importModal = document.getElementById("importModal");
  let rows = [];

  if (!fileInput || !submit) return;

  function parseCsv(text) {
    const records = [];
    let record = [];
    let field = "";
    let quoted = false;

    for (let i = 0; i < text.length; i++) {
      const char = text[i];
      if (char === '"' && quoted && text[i + 1] === '"') {
        field += '"';
        i++;
      } else if (char === '"') {
        quoted = !quoted;
      } else if (char === "," && !quoted) {
        record.push(field.trim());
        field = "";
      } else if ((char === "\n" || char === "\r") && !quoted) {
        if (char === "\r" && text[i + 1] === "\n") i++;
        record.push(field.trim());
        if (record.some((value) => value !== "")) records.push(record);
        record = [];
        field = "";
      } else {
        field += char;
      }
    }
    if (field !== "" || record.length) {
      record.push(field.trim());
      if (record.some((value) => value !== "")) records.push(record);
    }
    return records;
  }

  function normalizeRecords(records) {
    if (!records.length) return [];
    const headers = records[0].map((header) => String(header).trim());
    return records.slice(1).slice(0, 1000).map((values) =>
      Object.fromEntries(headers.map((header, index) => [header, String(values[index] ?? "").trim()])),
    );
  }

  function showPreview(data) {
    preview.hidden = false;
    previewHead.innerHTML = "";
    previewBody.innerHTML = "";
    if (!data.length) {
      preview.hidden = true;
      submit.disabled = true;
      return;
    }
    const headers = Object.keys(data[0]).slice(0, 8);
    headers.forEach((header) => {
      const th = document.createElement("th");
      th.textContent = header;
      previewHead.appendChild(th);
    });
    data.slice(0, 5).forEach((row) => {
      const tr = document.createElement("tr");
      headers.forEach((header) => {
        const td = document.createElement("td");
        td.textContent = row[header] || "—";
        tr.appendChild(td);
      });
      previewBody.appendChild(tr);
    });
    submit.disabled = false;
  }

  fileInput.addEventListener("change", async () => {
    const file = fileInput.files?.[0];
    result.hidden = true;
    rows = [];
    submit.disabled = true;
    if (!file) return;

    try {
      const name = file.name.toLowerCase();
      if (name.endsWith(".csv")) {
        rows = normalizeRecords(parseCsv(await file.text()));
      } else if (window.XLSX) {
        const workbook = XLSX.read(await file.arrayBuffer(), { type: "array" });
        const sheet = workbook.Sheets[workbook.SheetNames[0]];
        rows = XLSX.utils.sheet_to_json(sheet, { defval: "" }).slice(0, 1000);
      } else {
        throw new Error("The Excel reader could not be loaded. Try uploading a CSV file.");
      }
      showPreview(rows);
    } catch (error) {
      result.hidden = false;
      result.className = "alert alert-danger mt-3";
      result.textContent = error.message || "The selected file could not be read.";
    }
  });

  submit.addEventListener("click", async () => {
    if (!rows.length) return;
    submit.disabled = true;
    submit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Importing...';
    result.hidden = true;
    try {
      const res = await fetch(importModal?.dataset.importUrl || "/farmers/import", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "same-origin",
        body: JSON.stringify({ rows }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || "The profiles could not be imported.");
      result.hidden = false;
      result.className = data.skipped
        ? "alert alert-warning mt-3"
        : "alert alert-success mt-3";
      result.innerHTML =
        "<strong>" + data.imported + " profile(s) imported.</strong>" +
        (data.skipped ? " " + data.skipped + " row(s) skipped." : "") +
        (data.errors?.length ? "<ul class='mb-0 mt-2'>" + data.errors.slice(0, 10).map((error) =>
          "<li>" + escapeHtml(error) + "</li>",
        ).join("") + "</ul>" : "");
      if (data.imported) window.setTimeout(() => window.location.reload(), 1500);
    } catch (error) {
      result.hidden = false;
      result.className = "alert alert-danger mt-3";
      result.textContent = error.message || "The import could not be completed.";
    } finally {
      submit.disabled = false;
      submit.innerHTML = '<i class="bi bi-upload" aria-hidden="true"></i> Import profiles';
    }
  });
})();
