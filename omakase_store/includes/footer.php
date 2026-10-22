  <!-- ============ FOOTER ============ -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div>
          <div class="footer-brand">
            <span class="brand-mark">祥</span>
            <span class="footer-brand-name">SABI OMAKASE</span>
          </div>
          <p class="footer-about">Tận hưởng triết lý Wabi Sabi qua từng thớ cá tinh khiết. Nơi nghệ thuật ẩm thực giao
            hòa giữa sự tối giản thuần túy và nghi thức ẩm thực thượng lưu Nhật Bản.</p>
          <p class="footer-about">TOKYO &middot; KYOTO &middot; SAIGON</p>
        </div>
        <div>
          <h4>Điều hướng</h4>
          <ul>
            <li><a href="index.php">Trang chủ</a></li>
            <li><a href="menu.php">Thực đơn Omakase</a></li>
            <li><a href="cart.php">Đặt chỗ &amp; Giỏ hàng</a></li>
            <li><a href="history.php">Lịch sử đơn &amp; đặt tiệc</a></li>
            <li><a href="index.php#triet-ly">Về chúng tôi</a></li>
            <li><a href="index.php#triet-ly">Triết lý Wabi-Sabi</a></li>
          </ul>
        </div>
        <div>
          <h4>Liên hệ</h4>
          <ul>
            <li><i class="fa-solid fa-phone"></i> +84 901 000 001</li>
            <li><i class="fa-solid fa-envelope"></i> reservation@sabiomakase.vn</li>
            <li><i class="fa-solid fa-location-dot"></i> 12 đường số 3, Phường 4, Quận 5, TP. HCM</li>
          </ul>
        </div>
        <div>
          <h4>Giờ mở cửa</h4>
          <ul>
            <li>Thứ 2 - Thứ 6: 17:30 - 22:30</li>
            <li>Thứ 7 - CN: 11:30 - 14:00 / 17:30 - 23:00</li>
          </ul>
          <a href="cart.php?view=booking" class="btn btn-outline"
            style="margin-top:12px;font-size:12px;padding:8px 14px;display:inline-block;">CHỈ NHẬN PHỤC VỤ ĐẶT TRƯỚC</a>
        </div>
      </div>
      <div class="footer-bottom">
        <span>&copy; 2026 Sabi Omakase. Bài tập lớn Lập Trình Web.</span>
        <span>Cam kết nguồn nguyên liệu tươi nhập trực tiếp từ chợ Toyosu mỗi sáng.</span>
      </div>
    </div>
  </footer>

  <!-- Toast Notification Container -->
  <div id="toast" style="position:fixed;right:24px;bottom:24px;background:#8c2f2f;color:#fff;padding:14px 22px;border-radius:8px;font-size:13px;letter-spacing:1px;box-shadow:0 8px 25px rgba(0,0,0,0.6);opacity:0;transform:translateY(20px);transition:all 0.3s ease;z-index:9999;pointer-events:none;">
    Thông báo!
  </div>

  <script>
    function showToast(msg, isSuccess = true) {
      const t = document.getElementById('toast');
      if (!t) return;
      t.textContent = msg;
      t.style.background = isSuccess ? '#2e7d32' : '#c62828';
      t.style.opacity = '1';
      t.style.transform = 'translateY(0)';
      setTimeout(() => {
        t.style.opacity = '0';
        t.style.transform = 'translateY(20px)';
      }, 2500);
    }
  </script>
</body>
</html>
