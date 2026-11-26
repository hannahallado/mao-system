// Dashboard charts (Chart.js, loaded on the page).
window.addEventListener("load", () => {
  const dataEl = document.getElementById("dashboardCharts");
  if (!dataEl || !window.Chart) return;
  const data = JSON.parse(dataEl.textContent || "{}");
  const green = "#034e46";
  const palette = ["#034e46", "#2d6a5a", "#5b8f7b", "#8fb8a4", "#c4dccf", "#e05a2b", "#d9a441", "#6f8078", "#b7c7bf"];
  Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
  Chart.defaults.color = "#3b564c";

  const bar = document.getElementById("chartBarangay");
  if (bar && data.barangay) {
    new Chart(bar, {
      type: "bar",
      data: { labels: data.barangay.labels, datasets: [{ label: "Profiles", data: data.barangay.values, backgroundColor: green, borderRadius: 4, maxBarThickness: 44 }] },
      options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: "#ecebe6" } },
          x: { grid: { display: false }, ticks: { autoSkip: true, maxRotation: 0 } },
        },
      },
    });
  }

  const pie = document.getElementById("chartCommodity");
  if (pie && data.commodity && data.commodity.values.length) {
    new Chart(pie, {
      type: "doughnut",
      data: { labels: data.commodity.labels, datasets: [{ data: data.commodity.values, backgroundColor: palette, borderWidth: 1 }] },
      options: {
        maintainAspectRatio: false,
        cutout: "62%",
        plugins: { legend: { position: "bottom", labels: { boxWidth: 12, padding: 14 } } },
      },
    });
  }
});
