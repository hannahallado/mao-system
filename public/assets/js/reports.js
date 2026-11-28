// Report view: Print and Export to Excel (.xlsx via SheetJS, loaded on the page).
(function () {
  document.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-report-action]");
    if (!btn) return;

    if (btn.dataset.reportAction === "print") {
      window.print();
      return;
    }

    if (btn.dataset.reportAction === "excel") {
      const table = document.getElementById("reportTable");
      if (!table) {
        alert("There are no records to export. Change the filters and generate the report again.");
        return;
      }
      if (!window.XLSX) {
        alert("The Excel library could not be loaded. Check the internet connection, then try again.");
        return;
      }
      const doc = document.querySelector(".report-doc");
      const head = [
        [doc.querySelector(".report-doc-office")?.textContent.trim() || ""],
        [doc.querySelector(".report-doc-place")?.textContent.trim() || ""],
        [doc.querySelector(".report-doc-title")?.textContent.trim() || ""],
      ];
      doc.querySelectorAll(".report-doc-meta > div").forEach((row) => {
        head.push([row.querySelector("dt").textContent.trim() + ": " + row.querySelector("dd").textContent.trim()]);
      });
      head.push([]);

      const ws = XLSX.utils.aoa_to_sheet(head);
      const rows = [...table.querySelectorAll("tr")].map((tr) =>
        [...tr.children].map((cell) => {
          if (cell.dataset.v !== undefined) return Number(cell.dataset.v);
          const text = cell.textContent.trim();
          return text === "—" ? "" : text;
        }),
      );
      XLSX.utils.sheet_add_aoa(ws, rows, { origin: -1 });
      ws["!cols"] = rows[0].map((_, i) => ({
        wch: Math.min(40, Math.max(8, ...rows.map((r) => String(r[i] ?? "").length + 2))),
      }));

      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, (btn.dataset.sheet || "Report").replace(/[\\/?*[\]:]/g, " "));
      XLSX.writeFile(wb, (btn.dataset.filename || "report") + ".xlsx");
    }
  });
})();
