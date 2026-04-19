document.addEventListener("DOMContentLoaded", function () {
  // 1. LOGIC MENU MOBILE
  const hb = document.querySelector(".hamburger-icon");
  const ov = document.querySelector(".mobile-menu-overlay");
  const cl = document.querySelector(".close-icon");
  if (hb && ov && cl) {
    hb.onclick = () => ov.classList.add("active");
    cl.onclick = () => ov.classList.remove("active");
  }

  // 2. LOGIC SLIDER TRANG CHỦ
  const slides = document.querySelectorAll(".hero-background .slide");
  if (slides.length > 0) {
    let cur = 0;
    setInterval(() => {
      slides[cur].classList.remove("active");
      cur = (cur + 1) % slides.length;
      slides[cur].classList.add("active");
    }, 5000);
  }
});

// 3. HÀM MỞ MENU TÀI KHOẢN (ĐƯỢC GỌI TỪ FILE PHP)
window.toggleAccountMenu = function (event) {
  event.preventDefault();
  event.stopPropagation();
  const drop = document.getElementById("my-account-dropdown");
  if (drop)
    drop.style.display = drop.style.display === "none" ? "block" : "none";
};

// 4. BẤM RA NGOÀI TỰ ĐÓNG MENU
document.addEventListener("click", function (event) {
  const drop = document.getElementById("my-account-dropdown");
  if (drop && drop.style.display === "block") drop.style.display = "none";
});
