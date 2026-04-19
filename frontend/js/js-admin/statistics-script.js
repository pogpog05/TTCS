const chartInstances = {};

function formatNumber(value) {
  return new Intl.NumberFormat("vi-VN").format(Number(value || 0));
}

function formatCurrency(value) {
  return `${formatNumber(Math.round(Number(value || 0)))} VNĐ`;
}

function formatPercent(value) {
  return `${Number(value || 0).toFixed(2)}%`;
}

function formatDateDisplay(dateString) {
  if (!dateString) return "";
  const parts = String(dateString).split("-");
  if (parts.length !== 3) return dateString;
  return `${parts[2]}/${parts[1]}`;
}

function setDefaultDateRange() {
  const startInput = document.getElementById("start-date");
  const endInput = document.getElementById("end-date");

  const end = new Date();
  const start = new Date();
  start.setDate(end.getDate() - 29);

  endInput.value = end.toISOString().split("T")[0];
  startInput.value = start.toISOString().split("T")[0];
}

function renderDelta(elementId, value) {
  const el = document.getElementById(elementId);
  if (!el) return;

  el.classList.remove("positive", "negative", "neutral");

  const num = Number(value || 0);
  if (num > 0) {
    el.textContent = `So với kỳ trước: tăng ${num.toFixed(2)}%`;
    el.classList.add("positive");
  } else if (num < 0) {
    el.textContent = `So với kỳ trước: giảm ${Math.abs(num).toFixed(2)}%`;
    el.classList.add("negative");
  } else {
    el.textContent = "So với kỳ trước: không đổi";
    el.classList.add("neutral");
  }
}

function upsertChart(canvasId, config) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;

  if (chartInstances[canvasId]) {
    chartInstances[canvasId].destroy();
  }

  // Reset inline size to avoid cumulative resize artifacts after repeated re-render.
  canvas.style.width = "";
  canvas.style.height = "";

  chartInstances[canvasId] = new Chart(canvas, config);
}

function buildContinuousDailySeries(rows, startDate, endDate) {
  const dataRows = Array.isArray(rows) ? rows : [];
  if (!startDate || !endDate) {
    return {
      labels: dataRows.map((r) => formatDateDisplay(r.report_date)),
      appointments: dataRows.map((r) => Number(r.total_appointments || 0)),
      revenue: dataRows.map((r) => Number(r.revenue || 0)),
    };
  }

  const mapByDate = new Map();
  dataRows.forEach((row) => {
    mapByDate.set(row.report_date, {
      total_appointments: Number(row.total_appointments || 0),
      revenue: Number(row.revenue || 0),
    });
  });

  const labels = [];
  const appointments = [];
  const revenue = [];

  const cursor = new Date(`${startDate}T00:00:00`);
  const end = new Date(`${endDate}T00:00:00`);

  while (cursor <= end) {
    const y = cursor.getFullYear();
    const m = String(cursor.getMonth() + 1).padStart(2, "0");
    const d = String(cursor.getDate()).padStart(2, "0");
    const key = `${y}-${m}-${d}`;
    const item = mapByDate.get(key) || { total_appointments: 0, revenue: 0 };

    labels.push(`${d}/${m}`);
    appointments.push(item.total_appointments);
    revenue.push(item.revenue);

    cursor.setDate(cursor.getDate() + 1);
  }

  return { labels, appointments, revenue };
}

function renderSummary(summary, comparison) {
  const deltas = comparison?.deltas || {};

  document.getElementById("kpi-total-appointments").textContent = formatNumber(
    summary.total_appointments,
  );
  document.getElementById("kpi-revenue").textContent = formatCurrency(
    summary.revenue,
  );
  document.getElementById("kpi-completion-rate").textContent = formatPercent(
    summary.completion_rate,
  );
  document.getElementById("kpi-cancellation-rate").textContent = formatPercent(
    summary.cancellation_rate,
  );
  document.getElementById("kpi-unique-customers").textContent = formatNumber(
    summary.unique_customers,
  );
  document.getElementById("kpi-repeat-customers").textContent = formatNumber(
    summary.repeat_customers,
  );
  document.getElementById("kpi-new-customers").textContent = formatNumber(
    summary.new_customers,
  );
  document.getElementById("kpi-pending-leave").textContent = formatNumber(
    summary.pending_leave_requests,
  );

  renderDelta("delta-total-appointments", deltas.total_appointments_pct);
  renderDelta("delta-revenue", deltas.revenue_pct);
  renderDelta("delta-unique-customers", deltas.unique_customers_pct);
  renderDelta("delta-repeat-customers", deltas.repeat_customers_pct);
  renderDelta("delta-new-customers", deltas.new_customers_pct);
}

function renderAlerts(alerts) {
  const container = document.getElementById("statistics-alerts");
  if (!container) return;

  container.innerHTML = "";
  if (!alerts || alerts.length === 0) {
    container.innerHTML =
      '<div class="alert-item success">Tình hình đang ổn định, chưa có cảnh báo quan trọng.</div>';
    return;
  }

  alerts.forEach((item) => {
    const level = item.level || "info";
    const div = document.createElement("div");
    div.className = `alert-item ${level}`;
    div.textContent = item.message || "Không có nội dung cảnh báo.";
    container.appendChild(div);
  });
}

function renderDailyTrendChart(rows, filters = {}) {
  const series = buildContinuousDailySeries(
    rows,
    filters.start_date,
    filters.end_date,
  );

  const labels =
    series.labels.length > 0 ? series.labels : ["Không có dữ liệu"];
  const appointmentData =
    series.appointments.length > 0 ? series.appointments : [0];
  const revenueData = series.revenue.length > 0 ? series.revenue : [0];

  upsertChart("dailyTrendChart", {
    type: "line",
    data: {
      labels,
      datasets: [
        {
          label: "Lịch hẹn",
          data: appointmentData,
          borderColor: "#ff8c2f",
          backgroundColor: "rgba(255, 140, 47, 0.2)",
          yAxisID: "y",
          tension: 0.25,
          fill: false,
          pointRadius: 3,
        },
        {
          label: "Doanh thu",
          data: revenueData,
          borderColor: "#22b573",
          backgroundColor: "rgba(34, 181, 115, 0.15)",
          yAxisID: "y1",
          tension: 0.25,
          fill: false,
          pointRadius: 3,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: "top" },
      },
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: "Số lịch hẹn",
          },
        },
        y1: {
          beginAtZero: true,
          position: "right",
          grid: { drawOnChartArea: false },
          title: {
            display: true,
            text: "Doanh thu (VNĐ)",
          },
        },
      },
    },
  });
}

function renderStatusChart(rows) {
  const statusLabelMap = {
    pending: "Chờ xác nhận",
    confirmed: "Đã xác nhận",
    completed: "Đã hoàn thành",
    cancelled: "Đã hủy",
  };

  const dataRows = Array.isArray(rows) ? rows : [];
  const labels =
    dataRows.length > 0
      ? dataRows.map((r) => statusLabelMap[r.status] || r.status)
      : ["Không có dữ liệu"];

  const values =
    dataRows.length > 0 ? dataRows.map((r) => Number(r.total || 0)) : [1];

  upsertChart("statusDistributionChart", {
    type: "doughnut",
    data: {
      labels,
      datasets: [
        {
          data: values,
          backgroundColor: ["#f6ad55", "#60a5fa", "#34d399", "#f87171"],
          borderWidth: 1,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: "bottom",
        },
      },
    },
  });
}

function renderTopServicesChart(rows) {
  const dataRows = Array.isArray(rows) ? rows : [];
  const labels =
    dataRows.length > 0
      ? dataRows.map((r) => r.service_name)
      : ["Không có dữ liệu"];
  const values =
    dataRows.length > 0 ? dataRows.map((r) => Number(r.revenue || 0)) : [0];

  upsertChart("topServicesChart", {
    type: "bar",
    data: {
      labels,
      datasets: [
        {
          label: "Doanh thu",
          data: values,
          backgroundColor: "rgba(29, 115, 232, 0.7)",
          borderColor: "#1d73e8",
          borderWidth: 1,
          borderRadius: 6,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
      },
      scales: {
        y: {
          beginAtZero: true,
        },
      },
    },
  });
}

function renderTopStylists(rows) {
  const tbody = document.getElementById("top-stylists-body");
  if (!tbody) return;

  tbody.innerHTML = "";
  const dataRows = Array.isArray(rows) ? rows : [];

  if (dataRows.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="4" class="cell-center">Chưa có dữ liệu stylist trong kỳ.</td></tr>';
    return;
  }

  dataRows.forEach((row, idx) => {
    tbody.innerHTML += `
      <tr>
        <td>${idx + 1}</td>
        <td>${row.stylist_name}</td>
        <td>${formatNumber(row.total_appointments)}</td>
        <td>${formatCurrency(row.revenue)}</td>
      </tr>`;
  });
}

function renderBranchPerformance(rows) {
  const tbody = document.getElementById("branch-performance-body");
  if (!tbody) return;

  tbody.innerHTML = "";
  const dataRows = Array.isArray(rows) ? rows : [];

  if (dataRows.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="4" class="cell-center">Không có dữ liệu chi nhánh trong kỳ.</td></tr>';
    return;
  }

  dataRows.forEach((row) => {
    tbody.innerHTML += `
      <tr>
        <td>${row.branch_name}</td>
        <td>${formatNumber(row.total_appointments)}</td>
        <td>${formatNumber(row.completed_appointments)}</td>
        <td>${formatCurrency(row.revenue)}</td>
      </tr>`;
  });
}

function renderPeakHours(rows) {
  const container = document.getElementById("peak-hours-list");
  if (!container) return;

  container.innerHTML = "";
  const dataRows = Array.isArray(rows) ? rows : [];

  if (dataRows.length === 0) {
    container.innerHTML = '<span class="hour-chip">Chưa có dữ liệu</span>';
    return;
  }

  dataRows.forEach((row) => {
    const chip = document.createElement("span");
    chip.className = "hour-chip";
    chip.textContent = `${row.hour_slot} - ${formatNumber(row.total)} lịch`;
    container.appendChild(chip);
  });
}

async function loadBranchOptions() {
  try {
    const response = await fetch("../../../backend/api/get_branches_admin.php");
    const result = await response.json();
    const select = document.getElementById("branch-filter");
    if (!select) return;

    if (result.status === "success" && Array.isArray(result.data)) {
      result.data.forEach((branch) => {
        const option = document.createElement("option");
        option.value = branch.branch_id;
        option.textContent = branch.branch_name;
        select.appendChild(option);
      });
    }
  } catch (error) {
    console.error("Lỗi tải danh sách chi nhánh:", error);
  }
}

async function loadStatistics() {
  const startDate = document.getElementById("start-date").value;
  const endDate = document.getElementById("end-date").value;
  const branchId = document.getElementById("branch-filter").value;

  if (startDate > endDate) {
    alert("Ngày bắt đầu không được lớn hơn ngày kết thúc.");
    return;
  }

  const query = new URLSearchParams({
    start_date: startDate,
    end_date: endDate,
  });

  if (branchId) {
    query.append("branch_id", branchId);
  }

  try {
    const response = await fetch(
      `../../../backend/api/get_statistics_admin.php?${query.toString()}`,
    );
    const result = await response.json();

    if (result.status !== "success") {
      throw new Error(result.message || "Không thể tải dữ liệu thống kê.");
    }

    renderSummary(result.summary || {}, result.comparison || {});
    renderAlerts(result.alerts || []);

    const charts = result.charts || {};
    renderDailyTrendChart(charts.daily_trend || [], result.filters || {});
    renderStatusChart(charts.status_distribution || []);
    renderTopServicesChart(charts.top_services || []);
    renderTopStylists(charts.top_stylists || []);
    renderBranchPerformance(charts.branch_performance || []);
    renderPeakHours(charts.peak_hours || []);
  } catch (error) {
    console.error("Lỗi tải thống kê:", error);
    alert("Không thể tải dữ liệu thống kê. Vui lòng thử lại.");
  }
}

document.addEventListener("DOMContentLoaded", async function () {
  setDefaultDateRange();
  await loadBranchOptions();
  await loadStatistics();

  const filterForm = document.getElementById("statistics-filter-form");
  const resetBtn = document.getElementById("filter-reset-btn");

  filterForm.addEventListener("submit", async function (event) {
    event.preventDefault();
    await loadStatistics();
  });

  resetBtn.addEventListener("click", async function () {
    document.getElementById("branch-filter").value = "";
    setDefaultDateRange();
    await loadStatistics();
  });
});
