document
  .getElementById("show-add-stylist-form-btn")
  .addEventListener("click", function () {
    const formContainer = document.getElementById("add-stylist-form-container");
    const form = document.getElementById("add-stylist-form");

    // Ép form hiện ra, ẩn nút Thêm đi
    formContainer.style.display = "block";
    this.style.display = "none";

    // Xóa sạch dữ liệu cũ và đưa form về chế độ THÊM
    form.reset();
    document.getElementById("stylist-id").value = "";
    form.action = "../../../backend/add_stylist.php";
    document.querySelector(".add-stylist-block h2").innerText =
      "Thêm Thợ Cắt Mới";
    document.getElementById("submit-btn").innerHTML =
      '<i class="fas fa-save"></i> Lưu Thợ Cắt';
  });

// 2. Xử lý nút "Hủy" (Đóng form)
document
  .getElementById("cancel-add-stylist-form-btn")
  .addEventListener("click", function () {
    document.getElementById("add-stylist-form-container").style.display =
      "none";
    document.getElementById("show-add-stylist-form-btn").style.display =
      "inline-block";
  });

// 3. Xử lý nút "Sửa" trong bảng
function editStylist(button) {
  // Lấy dữ liệu từ nút Sửa
  const id = button.getAttribute("data-id");
  const name = button.getAttribute("data-name");
  const phone = button.getAttribute("data-phone");
  const branch = button.getAttribute("data-branch");
  const status = button.getAttribute("data-status");

  // Bơm dữ liệu lên các ô Input và Select của Form
  document.getElementById("stylist-id").value = id;
  document.getElementById("stylist-name").value = name;
  document.getElementById("stylist-phone").value = phone;
  document.getElementById("stylist-branch").value = branch;
  document.getElementById("stylist-status").value = status;

  // Đổi form sang chế độ CẬP NHẬT
  const form = document.getElementById("add-stylist-form");
  form.action = "../../../backend/update_stylist.php";

  document.querySelector(".add-stylist-block h2").innerText =
    "Cập nhật Thông tin Thợ";
  document.getElementById("submit-btn").innerHTML =
    '<i class="fas fa-check"></i> Cập nhật ngay';

  // Hiển thị form lên và cuộn màn hình tới đó
  document.getElementById("add-stylist-form-container").style.display = "block";
  document.getElementById("show-add-stylist-form-btn").style.display = "none";
  window.scrollTo({ top: 0, behavior: "smooth" });
}

// 4. Xử lý nút "Xóa"
function deleteStylist(id) {
  if (confirm("Cảnh báo: Bạn có chắc chắn muốn xóa thợ cắt này?")) {
    window.location.href = "../../../backend/delete_stylist.php?id=" + id;
  }
}
