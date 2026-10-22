<?php
require_once __DIR__ . '/config/db.php';

$user = current_user();
$cart_count = cart_total_items();

// Lấy thông tin tổng chi tiêu & đơn hàng
$user_id = $user['id'] ?? null;
$total_spend = 0;
$user_name = $user ? $user['ho_ten'] : 'Trần Đình Khang';
$user_rank = $user ? ($user['hang_thanh_vien'] ?? 'VIP') : 'VIP';
$user_points = $user ? (int)($user['diem_tich_luy'] ?? 1890) : 1890;

if ($user_id) {
    $stmt_spend = $pdo->prepare("SELECT COALESCE(SUM(tong_tien), 0) FROM don_dat_ban WHERE khach_hang_id = ? AND trang_thai != 'Đã hủy'");
    $stmt_spend->execute([$user_id]);
    $total_spend = (float)$stmt_spend->fetchColumn();

    $stmt_orders = $pdo->prepare("
        SELECT d.*, 
               COALESCE(s.ten_set, 'Set Omakase Đặc Biệt') AS ten_set,
               COALESCE(s.hinh_anh, 'images/kiwami.jpg') AS hinh_anh,
               COALESCE(s.id, 1) AS set_id,
               COALESCE(b.ten_ban, 'Quầy bar chính') AS ten_ban
        FROM don_dat_ban d
        LEFT JOIN chi_tiet_don c ON c.don_id = d.id
        LEFT JOIN set_omakase s ON c.set_id = s.id
        LEFT JOIN ban b ON d.ban_id = b.id
        WHERE d.khach_hang_id = ? OR d.email_lien_he = ? OR d.sdt_lien_he = ?
        GROUP BY d.id
        ORDER BY d.id DESC
    ");
    $stmt_orders->execute([$user_id, $user['email'], $user['so_dien_thoai']]);
    $orders = $stmt_orders->fetchAll();
} else {
    // Nếu chưa đăng nhập, hiển thị đơn mẫu hoặc đơn gần nhất từ DB
    $total_spend = 27530000;
    $stmt_orders = $pdo->query("
        SELECT d.*, 
               COALESCE(s.ten_set, 'Set Kiwami Grand Omakase') AS ten_set,
               COALESCE(s.hinh_anh, 'images/kiwami.jpg') AS hinh_anh,
               COALESCE(s.id, 1) AS set_id,
               COALESCE(b.ten_ban, 'Quầy bar chính') AS ten_ban
        FROM don_dat_ban d
        LEFT JOIN chi_tiet_don c ON c.don_id = d.id
        LEFT JOIN set_omakase s ON c.set_id = s.id
        LEFT JOIN ban b ON d.ban_id = b.id
        GROUP BY d.id
        ORDER BY d.id DESC
        LIMIT 6
    ");
    $orders = $stmt_orders->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Lịch Sử Đặt Chỗ - Sabi Omakase</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:wght@500;600;700&family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/history.css" />
</head>
<body>

  <!-- ============ HEADER (giống trang chủ) ============ -->
  <header class="site-header">
    <div class="container">
      <a href="index.php" class="brand">
        <span class="brand-mark">祥</span>
        <span class="brand-text">SABI OMAKASE<small>一期一会</small></span>
      </a>
      <nav class="main-nav">
        <a href="index.php">Trang chủ</a>
        <a href="Omakase_Menu.php">Thực đơn</a>
        <a href="hoso_order_thanhtoan.php?view=booking">Đặt chỗ</a>
        <a href="index.php#triet-ly">Về chúng tôi</a>
        <a href="danhgia.php">Liên hệ</a>
      </nav>
      <div class="header-actions">
        <a href="Omakase_Search.php" class="icon-btn" title="Tìm kiếm"><i class="fa-solid fa-magnifying-glass"></i></a>
        <button class="icon-btn" title="Giỏ đặt bàn" onclick="location.href='hoso_order_thanhtoan.php?view=cart'"><i class="fa-solid fa-bag-shopping"></i><span class="badge"><?= $cart_count ?></span></button>
        <?php if ($user): ?>
          <a href="hoso_order_thanhtoan.php?view=profile" class="avatar-btn" title="Hồ sơ: <?= htmlspecialchars($user['ho_ten']) ?>"><i class="fa-solid fa-user-check" style="color:#d8b77a;"></i></a>
        <?php else: ?>
          <a href="login.php" class="avatar-btn" title="Tài khoản"><i class="fa-regular fa-user"></i></a>
        <?php endif; ?>
        <a href="hoso_order_thanhtoan.php?view=booking" class="btn btn-primary">Đặt Bàn</a>
      </div>
    </div>
  </header>

  <div class="container history-page">

    <p class="breadcrumb"><a href="index.php">Trang chủ</a> / <span class="current">Tài khoản thượng khách</span></p>

    <div class="history-head">
      <div>
        <h1>Lịch Sử Đặt Chỗ &amp; Đơn Hàng</h1>
        <p>Theo dõi mọi lịch sử đặt tiệc, thanh toán và đặc quyền tích lũy của quý khách hàng thượng khách Sabi Omakase.</p>
      </div>
      <div class="spend-badge">
        <div class="amount"><?= number_format($total_spend, 0, ',', '.') ?>đ</div>
        <div class="label">Tổng chi tiêu</div>
      </div>
    </div>

    <div class="history-layout">

      <!-- ============ SIDEBAR TÀI KHOẢN ============ -->
      <aside class="account-card">
        <div class="avatar-lg" style="display:flex;align-items:center;justify-content:center;background:#8c2f2f;color:#fff;font-size:24px;border-radius:50%;margin:0 auto 12px;width:72px;height:72px;">
          <?= mb_strtoupper(mb_substr($user_name, 0, 2)) ?>
        </div>
        <div class="name"><?= htmlspecialchars($user_name) ?></div>
        <div class="rank">Thượng khách <?= htmlspecialchars($user_rank) ?> &middot; <?= $user_points ?> P</div>

        <nav class="account-nav">
          <a href="hoso_order_thanhtoan.php?view=profile">Hồ sơ cá nhân</a>
          <a href="lich-su-dat-cho.php" class="active">Lịch sử đặt tiệc &amp; đơn hàng</a>
          <a href="hoso_order_thanhtoan.php?view=cart">Giỏ hàng của tôi</a>
          <a href="Omakase_Menu.php">Khám phá thực đơn</a>
          <a href="index.php#chi-nhanh">Chi nhánh yêu thích</a>
          <?php if ($user): ?>
            <a href="logout.php">Đăng xuất tài khoản</a>
          <?php else: ?>
            <a href="login.php">Đăng nhập tài khoản</a>
          <?php endif; ?>
        </nav>

        <div class="account-promo">
          &#127873; Ưu đãi sinh nhật: tặng 1 chai Sake nếu đặt bàn trong 24 giờ tới.
        </div>

        <div class="account-support">&#128222; Hỗ trợ thượng khách: 090.123.4567</div>
      </aside>

      <!-- ============ MAIN ============ -->
      <div class="history-main">

        <div class="filter-bar">
          <div class="filter-tabs">
            <button class="active" onclick="filterStatus('all', this)">Tất cả (<?= count($orders) ?>)</button>
            <button onclick="filterStatus('ĐANG XỬ LÝ', this)">Đang xử lý</button>
            <button onclick="filterStatus('ĐÃ THANH TOÁN', this)">Đã thanh toán</button>
            <button onclick="filterStatus('ĐÃ HỦY', this)">Đã hủy</button>
          </div>
          <div class="order-search">
            <span class="ic">&#128269;</span>
            <input type="text" id="orderSearchInput" placeholder="Tìm theo mã đơn, tên set..." onkeyup="searchOrders()" />
          </div>
        </div>

        <!-- ============ DANH SÁCH ĐƠN TỪ DATABASE ============ -->
        <div id="ordersContainer">
        <?php if (empty($orders)): ?>
          <div style="background:#181818;border:1px solid #282828;padding:40px;text-align:center;color:#888;border-radius:8px;">
            Quý khách chưa có đơn đặt bàn nào.
          </div>
        <?php else: ?>
          <?php foreach ($orders as $ord): 
              $badge_class = 'pending';
              $badge_text = 'ĐANG XỬ LÝ';
              if ($ord['trang_thai_thanh_toan'] === 'Đã thanh toán' || $ord['trang_thai'] === 'Hoàn tất') {
                  $badge_class = 'success';
                  $badge_text = 'ĐÃ THANH TOÁN';
              } elseif ($ord['trang_thai'] === 'Đã hủy') {
                  $badge_class = 'danger';
                  $badge_text = 'ĐÃ HỦY';
              }
              $img = !empty($ord['hinh_anh']) ? $ord['hinh_anh'] : 'images/kiwami.jpg';
          ?>
            <div class="order-card" data-status="<?= $badge_text ?>" data-search="<?= htmlspecialchars(strtolower($ord['ma_don'] . ' ' . $ord['ten_set'])) ?>">
              <div class="thumb"><img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($ord['ten_set']) ?>" /></div>
              <div class="info">
                <h3><?= htmlspecialchars($ord['ten_set']) ?> <small style="font-size:12px;color:#888;">(<?= htmlspecialchars($ord['ma_don']) ?>)</small></h3>
                <div class="meta">
                  <span><?= (int)$ord['so_khach'] ?> khách</span>
                  <span><?= htmlspecialchars(date('H:i', strtotime($ord['gio_dung']))) ?> &middot; <?= htmlspecialchars(date('d/m/Y', strtotime($ord['ngay_dung']))) ?></span>
                  <span><?= htmlspecialchars($ord['ten_ban']) ?></span>
                </div>
              </div>
              <div class="side">
                <span class="price"><?= number_format($ord['tong_tien'], 0, ',', '.') ?>đ</span>
                <span class="status-badge <?= $badge_class ?>"><?= $badge_text ?></span>
              </div>
              <?php if ($ord['trang_thai'] === 'Đã hủy'): ?>
                <a href="hoso_order_thanhtoan.php?view=cart&name=<?= urlencode($ord['ten_set']) ?>&price=<?= (float)$ord['tong_tien'] ?>&qty=<?= (int)$ord['so_khach'] ?>" class="btn btn-outline" style="padding:8px 14px;font-size:12px;">Đặt Lại</a>
              <?php else: ?>
                <a href="Omakase_Detail.php?id=<?= $ord['set_id'] ?>" class="btn btn-outline" style="padding:8px 14px;font-size:12px;">Xem Chi Tiết</a>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
        </div>

        <!-- ============ PHƯƠNG THỨC THANH TOÁN ============ -->
        <div class="payment-section">
          <h2>Phương Thức Thanh Toán Bảo Lưu</h2>
          <div class="payment-grid">
            <div class="payment-card">
              <div class="ptype">&#128179; Visa / Mastercard</div>
              <div class="pmain">•••• 8829</div>
              <div class="psub">Hết hạn 11/27</div>
            </div>
            <div class="payment-card">
              <div class="ptype">&#128241; Chuyển khoản QR</div>
              <div class="pmain">Napas 247</div>
              <div class="psub">Vietcombank</div>
            </div>
            <div class="payment-card">
              <div class="ptype">&#127825; Apple Pay / Ví Sabi</div>
              <div class="pmain">Thiết bị</div>
              <div class="psub">iPhone 15 Pro Max</div>
            </div>
          </div>

          <div class="payment-actions">
            <a href="hoso_order_thanhtoan.php?view=cart" class="btn btn-outline">+ Thêm thẻ hoặc phương thức mới</a>
            <a href="hoso_order_thanhtoan.php?view=booking" class="btn btn-primary">Xác Nhận &amp; Đặt Thêm Chỗ Mới</a>
          </div>
        </div>

        <details class="history-note">
          <summary>Quy chuẩn &amp; Lưu ý đặt tiệc Sabi</summary>
          Các đơn đặt tiệc theo yêu cầu riêng (set menu tùy chỉnh, phòng riêng trên 6 khách) cần cọc trước tối thiểu 10 giờ so với giờ dùng bữa. Vui lòng liên hệ hotline thượng khách nếu cần thay đổi giờ hẹn trong vòng 24 giờ.
        </details>

      </div>
    </div>
  </div>

  <!-- ============ FOOTER (giống trang chủ) ============ -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div>
          <div class="footer-brand">
            <span class="brand-mark">祥</span>
            <span class="footer-brand-name">SABI OMAKASE</span>
          </div>
          <p class="footer-about">Tận hưởng triết lý Wabi Sabi qua từng thớ cá tinh khiết.</p>
        </div>
        <div>
          <h4>Điều hướng</h4>
          <ul>
            <li><a href="index.php">Trang chủ</a></li>
            <li><a href="Omakase_Menu.php">Thực đơn Omakase</a></li>
            <li><a href="lich-su-dat-cho.php">Lịch sử đặt tiệc</a></li>
          </ul>
        </div>
        <div>
          <h4>Liên hệ</h4>
          <ul>
            <li>&#128222; +123456789</li>
            <li>&#9993; reservation@sabiomakase.com</li>
          </ul>
        </div>
        <div>
          <h4>Giờ mở cửa</h4>
          <ul>
            <li>Thứ 2 - Thứ 6: 17:30 - 22:30</li>
            <li>Thứ 7 - CN: 11:30 - 14:00 / 17:30 - 23:00</li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <span>&copy; 2026 Sabi Omakase. Bản quyền thuộc về nhà hàng.</span>
      </div>
    </div>
  </footer>

  <script>
    function filterStatus(status, btn) {
      document.querySelectorAll('.filter-tabs button').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const cards = document.querySelectorAll('.order-card');
      cards.forEach(c => {
        if (status === 'all' || c.getAttribute('data-status') === status) {
          c.style.display = 'flex';
        } else {
          c.style.display = 'none';
        }
      });
    }

    function searchOrders() {
      const q = document.getElementById('orderSearchInput').value.toLowerCase().trim();
      const cards = document.querySelectorAll('.order-card');
      cards.forEach(c => {
        const text = c.getAttribute('data-search') || '';
        if (!q || text.includes(q)) {
          c.style.display = 'flex';
        } else {
          c.style.display = 'none';
        }
      });
    }
  </script>
</body>
</html>
