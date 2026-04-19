document.addEventListener("DOMContentLoaded", function () {
  const fullName = localStorage.getItem("full_name");
  const authContainer = document.getElementById("auth-menu-container");
  const mobileAuthContainer = document.getElementById("mobile-auth-container");

  if (authContainer) {
    if (fullName) {
      // ĐÃ ĐĂNG NHẬP: Giao diện Desktop
      authContainer.innerHTML = `
                <div class="user-account" style="position: relative;">
                    <div id="btn-toggle-menu" style="cursor: pointer; color: #ff7f00; display: flex; align-items: center; gap: 5px; font-weight: bold; font-size: 1rem;"> 
                        <i class="fas fa-user-circle" style="font-size: 1.2rem;"></i>
                        <span>Xin chào, ${fullName}</span>
                        <i class="fas fa-chevron-down" style="font-size: 0.8rem; margin-left: 2px;"></i>
                    </div>
                    
                    <div id="dropdown-box" style="display: none; position: absolute; top: 100%; right: 0; background: #fff; border-radius: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 180px; z-index: 999999; padding: 10px 0; margin-top: 15px;"> 
                        <a href="my-profile.html" style="display: block; padding: 8px 20px; color: #333; text-decoration: none; border-bottom: 1px solid #f4f4f4;">Tài khoản của tôi</a>
                        <a href="history.html" style="display: block; padding: 8px 20px; color: #333; text-decoration: none; border-bottom: 1px solid #f4f4f4;">Lịch sử đặt lịch</a>
                        <a href="#" id="btn-logout" style="display: block; padding: 8px 20px; color: red; text-decoration: none; font-weight: bold;">Đăng xuất</a>
                    </div>
                </div>
            `;

      // ĐÃ ĐĂNG NHẬP: Giao diện Mobile
      if (mobileAuthContainer) {
        mobileAuthContainer.innerHTML = `
                    <a href="appointment.html" class="btn primary-btn">Đặt lịch hẹn</a>
                    <a href="my-profile.html" class="btn primary-btn">Tài khoản</a> 
                    <a href="#" id="mobile-btn-logout" class="btn primary-btn" style="background: red; border-color: red;">Đăng xuất</a>
                `;
      }

      // LOGIC MỞ MENU CHỐNG XUNG ĐỘT 100%
      const btnMenu = document.getElementById("btn-toggle-menu");
      const dropBox = document.getElementById("dropdown-box");

      if (btnMenu && dropBox) {
        btnMenu.onclick = function (e) {
          e.stopPropagation(); // Cực kỳ quan trọng: Khóa các click rác
          dropBox.style.display =
            dropBox.style.display === "none" || dropBox.style.display === ""
              ? "block"
              : "none";
        };

        document.onclick = function (e) {
          if (!dropBox.contains(e.target) && !btnMenu.contains(e.target)) {
            dropBox.style.display = "none";
          }
        };
      }

      // LOGIC ĐĂNG XUẤT BẰNG API
      const handleLogout = function (e) {
        e.preventDefault();
        localStorage.clear(); // Xóa sạch dữ liệu trong trình duyệt
        window.location.href = "index.html"; // Đá về trang chủ
      };
      document.getElementById("btn-logout").onclick = handleLogout;
      if (document.getElementById("mobile-btn-logout")) {
        document.getElementById("mobile-btn-logout").onclick = handleLogout;
      }
    } else {
      // CHƯA ĐĂNG NHẬP: Giao diện Desktop
      authContainer.innerHTML = `
                <div class="user-account">
                    <a href="login.html" style="color: #ff7f00; text-decoration: none; font-weight: bold; display: flex; align-items: center; gap: 5px;"> 
                        <i class="fas fa-user-circle" style="font-size: 1.2rem;"></i> <span>Đăng nhập / Đăng ký</span>
                    </a>
                </div>
            `;
      // CHƯA ĐĂNG NHẬP: Giao diện Mobile
      if (mobileAuthContainer) {
        mobileAuthContainer.innerHTML = `
                    <a href="appointment.html" class="btn primary-btn">Đặt lịch hẹn</a>
                    <a href="login.html" class="btn primary-btn">Đăng nhập</a> 
                    <a href="register.html" class="btn primary-btn">Đăng ký</a> 
                `;
      }
    }
  }
});
