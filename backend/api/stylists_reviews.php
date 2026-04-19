<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Đánh Giá Thợ Cắt - Barber Shop</title>
    <link rel="stylesheet" href="../../css/css-client/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
      .reviews-container {
        max-width: 900px;
        margin: 30px auto;
        padding: 20px;
      }

      .stylist-info {
        background: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
        text-align: center;
      }

      .rating-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: #ff7f00;
      }

      .rating-label {
        color: #666;
        font-size: 0.9rem;
        margin-top: 5px;
      }

      .star-distribution {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
      }

      .star-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
      }

      .star-label {
        min-width: 80px;
        text-align: right;
      }

      .star-bar {
        flex: 1;
        height: 20px;
        background: #e0e0e0;
        border-radius: 4px;
        overflow: hidden;
      }

      .star-bar-fill {
        height: 100%;
        background: #ff7f00;
      }

      .star-count {
        min-width: 50px;
        text-align: right;
        color: #666;
      }

      .reviews-list {
        background: white;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
      }

      .review-item {
        padding: 20px;
        border-bottom: 1px solid #e0e0e0;
      }

      .review-item:last-child {
        border-bottom: none;
      }

      .review-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
      }

      .review-customer {
        font-weight: bold;
        color: #333;
      }

      .review-date {
        color: #999;
        font-size: 0.9rem;
      }

      .review-stars {
        color: #ff7f00;
        margin-bottom: 10px;
        font-size: 1.2rem;
      }

      .review-comment {
        color: #666;
        line-height: 1.6;
      }

      .pagination {
        text-align: center;
        margin-top: 20px;
        padding: 20px 0;
      }

      .pagination button {
        margin: 0 5px;
        padding: 8px 12px;
        border: 1px solid #28a745;
        background: white;
        color: #28a745;
        border-radius: 4px;
        cursor: pointer;
        transition: 0.3s;
      }

      .pagination button.active {
        background: #28a745;
        color: white;
      }

      .pagination button:hover:not(:disabled) {
        background: #28a745;
        color: white;
      }

      .no-reviews {
        text-align: center;
        padding: 30px;
        color: #999;
      }

      .error-message {
        text-align: center;
        padding: 20px;
        color: #e74c3c;
        background: #f5f5f5;
        border-radius: 4px;
        margin: 20px 0;
      }
    </style>
  </head>
  <body>
    <header class="site-header">
      <div class="container header-container">
        <div class="logo">
          <img src="../../image/logo.png" alt="Barber Shop Logo" />
        </div>
        <nav class="desktop-nav">
          <ul>
            <li><a href="index.html">Trang chủ</a></li>
            <li><a href="about.html">Về chúng tôi</a></li>
            <li><a href="services.html">Dịch vụ</a></li>
          </ul>
        </nav>
        <div class="header-buttons desktop-buttons" id="auth-menu-container"></div>
      </div>
    </header>

    <main class="page-main">
      <div class="container reviews-container">
        <h1>Đánh Giá Thợ Cắt</h1>
        
        <div id="stylist-info-container"></div>
        
        <h2>Danh Sách Đánh Giá</h2>
        <div class="reviews-list" id="reviews-list-container">
          <p style="text-align: center; padding: 20px;">Đang tải đánh giá...</p>
        </div>
        
        <div class="pagination" id="pagination-container"></div>
      </div>
    </main>

    <footer style="background-color: #1a1a1a; color: #fff; text-align: center; padding: 20px;">
      <p>Barber Shop. All rights reserved.</p>
    </footer>

    <script src="../../js/js-client/script.js"></script>
    <script src="../../js/js-client/auth-menu.js"></script>

    <script>
      let stylistId = new URLSearchParams(window.location.search).get('stylist_id');

      document.addEventListener('DOMContentLoaded', function() {
        if (stylistId) {
          loadStylistReviews(1);
        } else {
          document.getElementById('reviews-list-container').innerHTML = 
            '<div class="error-message">❌ Không tìm thấy thợ cắt!</div>';
        }
      });

      function loadStylistReviews(page = 1) {
        const url = `../../../backend/api/get_stylist_reviews.php?stylist_id=${stylistId}&page=${page}`;
        console.log('Fetching:', url);
        
        fetch(url)
          .then(res => {
            console.log('Response status:', res.status);
            return res.json();
          })
          .then(res => {
            console.log('Response data:', res);
            
            if (res.status === 'success') {
              renderStats(res.stats);
              renderReviews(res.reviews, res.pagination);
              renderPagination(res.pagination, page);
            } else {
              document.getElementById('reviews-list-container').innerHTML = 
                '<div class="error-message">❌ Lỗi: ' + (res.message || 'Không xác định') + '</div>';
            }
          })
          .catch(err => {
            console.error('Lỗi fetch:', err);
            document.getElementById('reviews-list-container').innerHTML = 
              '<div class="error-message">❌ Lỗi kết nối API!</div>';
          });
      }

      function renderStats(stats) {
        const container = document.getElementById('stylist-info-container');
        
        const avgRating = stats.average_rating || 0;
        const totalReviews = stats.total_reviews || 0;
        const starDist = stats.star_distribution || {5: 0, 4: 0, 3: 0, 2: 0, 1: 0};
        const maxCount = Math.max(...Object.values(starDist), 1);

        let starsHtml = '';
        for (let i = 0; i < 5; i++) {
          starsHtml += i < Math.floor(avgRating) ? '⭐' : '☆';
        }

        let html = `
          <div class="stylist-info">
            <h2>Thông Tin Đánh Giá</h2>
            <div class="rating-number">${avgRating.toFixed(1)}</div>
            <div style="color: #ff7f00; margin: 10px 0; font-size: 1.2rem;">${starsHtml}</div>
            <div class="rating-label">${totalReviews} đánh giá</div>
            
            <div class="star-distribution" style="margin-top: 20px;">
              <h3>Phân Bố Đánh Giá</h3>
        `;

        for (let rating = 5; rating >= 1; rating--) {
          const count = starDist[rating] || 0;
          const percentage = maxCount > 0 ? (count / maxCount * 100) : 0;
          
          html += `
            <div class="star-row">
              <div class="star-label">${rating} ⭐</div>
              <div class="star-bar">
                <div class="star-bar-fill" style="width: ${percentage}%"></div>
              </div>
              <div class="star-count">${count}</div>
            </div>
          `;
        }

        html += `</div></div>`;
        container.innerHTML = html;
      }

      function renderReviews(reviews, pagination) {
        const container = document.getElementById('reviews-list-container');
        
        if (!reviews || reviews.length === 0) {
          container.innerHTML = '<div class="no-reviews">⭐ Chưa có đánh giá nào</div>';
          return;
        }

        let html = '';
        reviews.forEach(review => {
          const stars = '⭐'.repeat(review.rating) + '☆'.repeat(5 - review.rating);
          const date = new Date(review.created_at).toLocaleDateString('vi-VN');
          
          html += `
            <div class="review-item">
              <div class="review-header">
                <span class="review-customer">${review.customer_name || 'Khách hàng'}</span>
                <span class="review-date">${date}</span>
              </div>
              <div class="review-stars">${stars}</div>
              <div class="review-comment">${review.comment || '(Không có bình luận)'}</div>
            </div>
          `;
        });

        container.innerHTML = html;
      }

      function renderPagination(pagination, currentPage) {
        const container = document.getElementById('pagination-container');
        
        if (!pagination || pagination.total_pages <= 1) {
          container.innerHTML = '';
          return;
        }

        let html = '';
        for (let i = 1; i <= pagination.total_pages; i++) {
          const activeClass = i === currentPage ? 'active' : '';
          html += `<button class="${activeClass}" onclick="loadStylistReviews(${i})">${i}</button>`;
        }

        container.innerHTML = html;
      }
    </script>
  </body>
</html>