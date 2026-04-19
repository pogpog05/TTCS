document.addEventListener("DOMContentLoaded", function () {
  // 1. KIỂM TRA QUYỀN TRUY CẬP API
  const userId = localStorage.getItem("user_id");
  const userRole = localStorage.getItem("user_role");

  // Nếu chưa đăng nhập HOẶC không phải là admin -> Đuổi về trang login
  if (!userId || userRole !== "admin") {
    alert("Cảnh báo: Bạn không có quyền truy cập trang quản trị!");
    window.location.href = "../html-client/login.html";
    return;
  }

  // 2. VẼ TÊN ADMIN VÀ NÚT ĐĂNG XUẤT LÊN HEADER
  const headerContainer = document.querySelector(".header-container");
  if (headerContainer) {
    const adminName = localStorage.getItem("full_name");
    const adminMenuHtml = `
            <div class="admin-auth-menu" style="display: flex; align-items: center; gap: 15px; margin-left: auto;">
                <span style="color: #ff7f00; font-weight: bold; font-size: 1rem;">
                    <i class="fas fa-user-shield"></i> Xin chào, ${adminName}
                </span>
                <a href="#" id="admin-logout-btn" class="btn" style="background-color: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-weight: bold;">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        `;

    // Chèn cụm này vào góc phải của Header Admin
    headerContainer.insertAdjacentHTML("beforeend", adminMenuHtml);

    // 3. XỬ LÝ ĐĂNG XUẤT ADMIN
    document
      .getElementById("admin-logout-btn")
      .addEventListener("click", function (e) {
        e.preventDefault();
        if (confirm("Bạn muốn đăng xuất khỏi phiên quản trị?")) {
          localStorage.clear(); // Xóa sạch mọi thứ
          window.location.href = "../html-client/login.html";
        }
      });
  }
});
