<?php
require_once __DIR__ . '/../config/db.php';
$current_page = basename($_SERVER['PHP_SELF']);
$cart_count = cart_total_items();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title ?? 'Sabi Omakase - Tinh Hoa Ẩm Thực Nhật Bản') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link
    href="https://fonts.googleapis.com/css2?family=Noto+Serif:wght@500;600;700&family=Noto+Sans:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css" />
  <?php if (!empty($extra_css)): ?>
    <?php foreach ($extra_css as $css_file): ?>
      <link rel="stylesheet" href="<?= htmlspecialchars($css_file) ?>" />
    <?php endforeach; ?>
  <?php endif; ?>
  <style>
    /* User dropdown menu */
    .user-dropdown {
      position: relative;
      display: inline-block;
    }
    .user-btn {
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(255,255,255,0.08);
      color: #fff;
      border: 1px solid rgba(255,255,255,0.15);
      border-radius: 20px;
      padding: 6px 14px;
      cursor: pointer;
      font-size: 13px;
      text-decoration: none;
      transition: all 0.2s ease;
    }
    .user-btn:hover {
      background: rgba(255,255,255,0.15);
      border-color: rgba(255,255,255,0.3);
    }
    .user-dropdown-menu {
      display: none;
      position: absolute;
      right: 0;
      top: 100%;
      margin-top: 8px;
      background: #191919;
      border: 1px solid #333;
      border-radius: 8px;
      min-width: 220px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.8);
      z-index: 1000;
      overflow: hidden;
    }
    .user-dropdown:hover .user-dropdown-menu {
      display: block;
    }
    .user-dropdown-header {
      padding: 12px 16px;
      border-bottom: 1px solid #2a2a2a;
      background: #141414;
    }
    .user-dropdown-header .name {
      font-weight: 600;
      color: #fff;
      font-size: 14px;
    }
    .user-dropdown-header .membership {
      font-size: 11px;
      color: #d8b77a;
      margin-top: 3px;
      display: inline-block;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .user-dropdown-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 16px;
      color: #bbb;
      text-decoration: none;
      font-size: 13px;
      transition: background 0.2s;
    }
    .user-dropdown-item:hover {
      background: #252525;
      color: #fff;
    }
    .user-dropdown-item.logout {
      color: #e06c75;
      border-top: 1px solid #2a2a2a;
    }
    .user-dropdown-item.logout:hover {
      background: rgba(224, 108, 117, 0.1);
      color: #ff7b85;
    }
    .badge {
      background: #b91e1e;
      color: #fff;
      font-size: 10px;
      font-weight: bold;
      border-radius: 10px;
      padding: 2px 6px;
      margin-left: 4px;
    }
  </style>
</head>
<body>

  <!-- ============ HEADER ============ -->
  <header class="site-header">
    <div class="container">
      <a href="index.php" class="brand">
        <span class="brand-mark">祥</span>
        <span class="brand-text">SABI OMAKASE <small>一期一会</small></span>
      </a>

      <nav class="main-nav">
        <a href="index.php" class="<?= in_array($current_page, ['index.php', '']) ? 'active' : '' ?>">Trang chủ</a>
        <a href="menu.php" class="<?= in_array($current_page, ['menu.php', 'detail.php']) ? 'active' : '' ?>">Thực đơn</a>
        <a href="cart.php" class="<?= $current_page === 'cart.php' ? 'active' : '' ?>">Giỏ hàng</a>
        <a href="index.php#triet-ly">Về chúng tôi</a>
        <a href="index.php#chi-nhanh">Chi nhánh</a>
      </nav>

      <div class="header-actions">
        <a href="menu.php" class="icon-btn" title="Tìm kiếm món"><i class="fa-solid fa-magnifying-glass"></i></a>
        
        <button class="icon-btn" title="Giỏ hàng & Đặt bàn" onclick="location.href='cart.php'">
          <i class="fa-solid fa-bag-shopping"></i>
          <span class="badge" id="headerCartBadge"><?= $cart_count ?></span>
        </button>

        <?php if ($user): ?>
          <div class="user-dropdown">
            <button class="user-btn">
              <i class="fa-solid fa-user-check" style="color: #d8b77a;"></i>
              <span><?= htmlspecialchars(explode(' ', trim($user['ho_ten']))[count(explode(' ', trim($user['ho_ten'])))-1]) ?></span>
              <i class="fa-solid fa-chevron-down" style="font-size: 10px; opacity: 0.7;"></i>
            </button>
            <div class="user-dropdown-menu">
              <div class="user-dropdown-header">
                <div class="name"><?= htmlspecialchars($user['ho_ten']) ?></div>
                <div class="membership"><i class="fa-solid fa-crown"></i> <?= htmlspecialchars($user['hang_thanh_vien'] ?? 'Thành viên') ?> (<?= $user['diem_tich_luy'] ?? 0 ?> điểm)</div>
              </div>
              <a href="history.php" class="user-dropdown-item"><i class="fa-solid fa-receipt"></i> Lịch sử đơn & đặt bàn</a>
              <a href="cart.php" class="user-dropdown-item"><i class="fa-solid fa-cart-shopping"></i> Giỏ hàng (<?= $cart_count ?>)</a>
              <a href="logout.php" class="user-dropdown-item logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất</a>
            </div>
          </div>
        <?php else: ?>
          <a href="login.php" class="avatar-btn" title="Đăng nhập"><i class="fa-regular fa-user"></i></a>
        <?php endif; ?>

        <a href="cart.php?view=booking" class="btn btn-primary">ĐẶT BÀN</a>
      </div>
    </div>
  </header>
