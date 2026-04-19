document.addEventListener("DOMContentLoaded", function () {
  const fullName = localStorage.getItem("full_name");
  if (fullName)
    document.getElementById("barber-name-display").innerHTML =
      `Xin chào, Thợ ${fullName}`;

  document
    .getElementById("barber-logout-btn")
    .addEventListener("click", function (e) {
      e.preventDefault();
      if (confirm("Bạn có muốn đăng xuất?")) {
        localStorage.clear();
        window.location.href = "../html-client/login.html";
      }
    });

  const tabs = {
    appointments: {
      btn: document.getElementById("tab-appointments-btn"),
      section: document.getElementById("appointments-section"),
    },
    leave: {
      btn: document.getElementById("tab-leave-btn"),
      section: document.getElementById("leave-section"),
    },
    history: {
      btn: document.getElementById("tab-history-btn"),
      section: document.getElementById("history-section"),
    },
  };

  function switchTab(activeKey) {
    Object.keys(tabs).forEach((key) => {
      if (key === activeKey) {
        tabs[key].btn.classList.add("active");
        tabs[key].section.classList.add("active");
      } else {
        tabs[key].btn.classList.remove("active");
        tabs[key].section.classList.remove("active");
      }
    });
  }

  if (tabs.appointments.btn)
    tabs.appointments.btn.addEventListener("click", () =>
      switchTab("appointments"),
    );
  if (tabs.leave.btn)
    tabs.leave.btn.addEventListener("click", () => switchTab("leave"));
  if (tabs.history.btn)
    tabs.history.btn.addEventListener("click", () => switchTab("history"));

  function loadBarberData() {
    fetch(
      `../../../backend/api/get_barber_schedule.php?user_id=${localStorage.getItem("user_id")}`,
    )
      .then((res) => res.json())
      .then((res) => {
        if (res.status === "success") {
          const tbodyApp = document.getElementById("schedule-table-body");
          tbodyApp.innerHTML = "";
          if (res.data.appointments.length > 0) {
            res.data.appointments.forEach((app) => {
              let stText = "Chờ xác nhận",
                stClass = "pending";
              if (app.status === "confirmed") {
                stText = "Đã xác nhận";
                stClass = "confirmed";
              }
              if (app.status === "completed") {
                stText = "Hoàn thành";
                stClass = "completed";
              }
              if (app.status === "cancelled") {
                stText = "Đã hủy";
                stClass = "cancelled";
              }
              let d = app.appointment_date.split("-");
              let fDate = `${d[2]}/${d[1]}/${d[0]}`;
              tbodyApp.innerHTML += `<tr><td>${fDate}</td><td>${app.appointment_time.substring(0, 5)}</td><td style="font-weight:bold;">${app.cust_name}</td><td>${app.cust_phone}</td><td>${app.service_name}</td><td>${app.notes || "Không"}</td><td><span class="status-badge ${stClass}">${stText}</span></td></tr>`;
            });
          } else
            tbodyApp.innerHTML =
              '<tr><td colspan="7" style="text-align: center;">Chưa có lịch hẹn.</td></tr>';

          const tbodyLeave = document.getElementById(
            "leave-history-table-body",
          );
          tbodyLeave.innerHTML = "";
          if (res.data.leave_history.length > 0) {
            res.data.leave_history.forEach((req) => {
              let stText = "Chờ duyệt",
                stColor = "#ff7f00";
              if (req.status === "approved") {
                stText = "Đã duyệt";
                stColor = "#28a745";
              }
              if (req.status === "rejected") {
                stText = "Từ chối";
                stColor = "#dc3545";
              }

              let ds = req.start_date.split("-");
              let fStart = `${ds[2]}/${ds[1]}/${ds[0]}`;
              let de = req.end_date.split("-");
              let fEnd = `${de[2]}/${de[1]}/${de[0]}`;
              let leaveRange =
                fStart === fEnd ? fStart : `Từ ${fStart}<br>Đến ${fEnd}`;
              let c = req.created_at.split(" ")[0].split("-");
              let fCreated = `${c[2]}/${c[1]}/${c[0]}`;

              tbodyLeave.innerHTML += `<tr><td>${fCreated}</td><td style="font-weight: bold; color: #333;">${leaveRange}</td><td>${req.leave_type}</td><td>${req.reason || "Không có"}</td><td style="font-weight: bold; color: ${stColor};">${stText}</td></tr>`;
            });
          } else
            tbodyLeave.innerHTML =
              '<tr><td colspan="5" style="text-align: center;">Chưa nộp đơn nào.</td></tr>';
        } else alert("Lỗi: " + res.message); // Hiển thị chi tiết lỗi
      });
  }

  loadBarberData();

  const leaveForm = document.getElementById("leave-request-form");
  if (leaveForm) {
    leaveForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      formData.append("user_id", localStorage.getItem("user_id"));

      const btn = document.getElementById("submit-leave-btn");
      btn.innerText = "Đang gửi...";
      btn.disabled = true;

      fetch("../../../backend/api/submit_leave.php", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((res) => {
          alert(res.message);
          if (res.status === "success") {
            leaveForm.reset();
            loadBarberData();
            switchTab("history");
          }
        })
        .finally(() => {
          btn.innerText = "Gửi Yêu Cầu";
          btn.disabled = false;
        });
    });
  }
});
