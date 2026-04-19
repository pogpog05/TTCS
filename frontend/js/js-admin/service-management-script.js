document.addEventListener("DOMContentLoaded", function () {
  // Lấy tham chiếu đến các phần tử cần thiết
  const showFormBtn = document.getElementById("show-add-service-form-btn");
  const addServiceFormContainer = document.getElementById(
    "add-service-form-container",
  );
  const cancelFormBtn = document.getElementById("cancel-add-service-form-btn");
  const addServiceForm = document.getElementById("add-service-form"); // Tham chiếu đến form

  // Kiểm tra xem các phần tử có tồn tại trên trang không
  if (
    showFormBtn &&
    addServiceFormContainer &&
    cancelFormBtn &&
    addServiceForm
  ) {
    console.log(
      "Service management form elements found. Initializing form toggle logic.",
    );

    // Hàm hiển thị form
    function showAddServiceForm() {
      addServiceFormContainer.classList.add("visible"); // Thêm class 'visible' để hiển thị
      showFormBtn.style.display = "none"; // Ẩn nút "Thêm Dịch vụ"
      console.log("Add service form shown.");
    }

    // Hàm ẩn form
    function hideAddServiceForm() {
      addServiceFormContainer.classList.remove("visible"); // Xóa class 'visible' để ẩn
      showFormBtn.style.display = "inline-block"; // Hiển thị lại nút "Thêm Dịch vụ" (hoặc block tùy style ban đầu)
      addServiceForm.reset(); // Reset form về trạng thái ban đầu
      console.log("Add service form hidden.");
    }

    // Lắng nghe sự kiện click vào nút "Thêm Dịch vụ"
    showFormBtn.addEventListener("click", showAddServiceForm);

    // Lắng nghe sự kiện click vào nút "Hủy" trong form
    cancelFormBtn.addEventListener("click", hideAddServiceForm);

    // Tùy chọn: Lắng nghe sự kiện submit form
    addServiceForm.addEventListener("submit", function (event) {
      // event.preventDefault(); // Ngăn chặn submit form mặc định
      // // --- Logic xử lý dữ liệu form tại đây ---
      // const serviceName = document.getElementById("service-name").value;
      // const servicePrice = document.getElementById("service-price").value;
      // const serviceDuration = document.getElementById("service-duration").value;
      // // const serviceDescription = document.getElementById('service-description').value; // Nếu có
      // console.log("Form submitted:", {
      //   name: serviceName,
      //   price: servicePrice,
      //   duration: serviceDuration,
      //   // description: serviceDescription
      // });
      // // --- Mô phỏng lưu thành công và ẩn form ---
      // // Nếu không dùng backend, chỉ ẩn form và reset
      // hideAddServiceForm();
      // // Tùy chọn: Cập nhật bảng giả định hoặc hiển thị thông báo
      // alert("Dịch vụ đã được thêm (mô phỏng)."); // Sử dụng alert tạm thời
    });
  } else {
    console.log("Service management form elements not found on this page.");
  }
});
function editService(button) {
  const id = button.getAttribute("data-id");
  const name = button.getAttribute("data-name");
  const price = button.getAttribute("data-price");
  const duration = button.getAttribute("data-duration");
  const desc = button.getAttribute("data-desc");

  document.getElementById("service-id").value = id;
  document.getElementById("service-name").value = name;
  document.getElementById("service-price").value = price;
  document.getElementById("service-duration").value = duration;
  document.getElementById("service-description").value = desc;

  const form = document.getElementById("add-service-form");
  form.action = "../../../backend/update_service.php"; // Chuyển sang chế độ sửa

  document.querySelector(".add-service-block h2").innerText =
    "Cập nhật Dịch vụ";
  document
    .getElementById("add-service-form-container")
    .classList.add("visible");
  document.getElementById("show-add-service-form-btn").style.display = "none";

  window.scrollTo({ top: 0, behavior: "smooth" });
} // End DOMContentLoaded
