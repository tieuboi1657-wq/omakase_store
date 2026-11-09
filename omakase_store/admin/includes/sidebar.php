<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside>
  <div class="brand">
    <b>祥</b>
    <span>SABI OMAKASE<small>HỆ THỐNG QUẢN TRỊ</small></span>
  </div>

  <nav id="adminNav">
    <a href="index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">
      <i class="fa-solid fa-chart-pie"></i> Thống kê & Tổng quan
    </a>
    <a href="products.php" class="<?= $current_page === 'products.php' ? 'active' : '' ?>">
      <i class="fa-solid fa-utensils"></i> Quản lý Sản phẩm
    </a>
    <a href="categories.php" class="<?= $current_page === 'categories.php' ? 'active' : '' ?>">
      <i class="fa-solid fa-layer-group"></i> Quản lý Danh mục
    </a>
    <a href="orders.php" class="<?= $current_page === 'orders.php' ? 'active' : '' ?>">
      <i class="fa-solid fa-calendar-check"></i> Quản lý Đơn đặt bàn
    </a>
    <a href="customers.php" class="<?= $current_page === 'customers.php' ? 'active' : '' ?>">
      <i class="fa-solid fa-users"></i> Quản lý Khách hàng
    </a>
    <a href="revenue.php" class="<?= $current_page === 'revenue.php' ? 'active' : '' ?>">
      <i class="fa-solid fa-coins"></i> Báo cáo Doanh thu
    </a>
    <a href="reviews.php" class="<?= $current_page === 'reviews.php' ? 'active' : '' ?>">
      <i class="fa-solid fa-star"></i> Đánh giá & Phản hồi
    </a>
    <a href="staff.php" class="<?= $current_page === 'staff.php' ? 'active' : '' ?>">
      <i class="fa-solid fa-id-badge"></i> Nhân sự & Lịch ca
    </a>
  </nav>

  <div class="side-foot">
    <div class="user-info">
      <div class="avatar"><?= mb_substr($admin['ho_ten'] ?? 'A', 0, 1, 'UTF-8') ?></div>
      <div style="flex:1;min-width:0;">
        <div style="font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($admin['ho_ten'] ?? 'Quản trị viên') ?></div>
        <div style="font-size:11px;color:var(--gold);text-transform:uppercase;">
          <?= $admin['quyen'] === 'admin' ? 'Quản trị tối cao' : ($admin['quyen'] === 'quan_ly' ? 'Quản lý' : 'Nhân viên') ?>
        </div>
      </div>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px;">
      <a href="../php/index.php" target="_blank" class="link-out" title="Xem giao diện khách hàng">
        <i class="fa-solid fa-arrow-up-right-from-square"></i> Xem website
      </a>
      <a href="logout.php" style="color:var(--red2);font-size:12px;display:inline-flex;align-items:center;gap:4px;" title="Đăng xuất khỏi hệ thống">
        <i class="fa-solid fa-right-from-bracket"></i> Thoát
      </a>
    </div>
  </div>
</aside>
