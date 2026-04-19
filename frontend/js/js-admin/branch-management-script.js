document.addEventListener("DOMContentLoaded", function () {
  // Lấy tham chiếu đến các phần tử cần thiết cho form thêm chi nhánh
  const showFormBtn = document.getElementById("show-add-branch-form-btn");
  const addBranchFormContainer = document.getElementById(
    "add-branch-form-container",
  );
  const cancelFormBtn = document.getElementById("cancel-add-branch-form-btn");
  const addBranchForm = document.getElementById("add-branch-form"); // Tham chiếu đến form

  // Kiểm tra xem các phần tử form có tồn tại trên trang không
  if (showFormBtn && addBranchFormContainer && cancelFormBtn && addBranchForm) {
    console.log(
      "Branch management form elements found. Initializing form toggle logic.",
    );

    // Hàm hiển thị form
    function showAddBranchForm() {
      addBranchFormContainer.classList.add("visible"); // Thêm class 'visible' để hiển thị
      showFormBtn.style.display = "none"; // Ẩn nút "Thêm Chi nhánh"
      console.log("Add branch form shown.");
    }

    // Hàm ẩn form
    function hideAddBranchForm() {
      addBranchFormContainer.classList.remove("visible"); // Xóa class 'visible' để ẩn
      showFormBtn.style.display = "inline-block"; // Hiển thị lại nút "Thêm Chi nhánh"
      addBranchForm.reset(); // Reset form về trạng thái ban đầu
      console.log("Add branch form hidden.");
    }

    // Lắng nghe sự kiện click vào nút "Thêm Chi nhánh"
    showFormBtn.addEventListener("click", showAddBranchForm);

    // Lắng nghe sự kiện click vào nút "Hủy" trong form
    cancelFormBtn.addEventListener("click", hideAddBranchForm);

    /* ================================================================================
    ĐÃ XÓA TOÀN BỘ ĐOẠN MÃ MÔ PHỎNG 'addBranchForm.addEventListener("submit", ...)' Ở ĐÂY
    ĐỂ FORM CÓ THỂ GỬI DỮ LIỆU BẰNG ACTION VÀ METHOD POST SANG BACKEND.
    ================================================================================
    */
  } else {
    console.log("Branch management form elements not found on this page.");
  }
}); // End DOMContentLoaded
