<?php
require_once __DIR__ . '/config/db.php';

$cart_count = cart_total_items();
$user = current_user();

// Nhận tham số lọc
$loai_id   = isset($_GET['loai_id']) && $_GET['loai_id'] !== '' ? (int)$_GET['loai_id'] : null;
$price_max = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? (int)$_GET['price_max'] : null;
$chef_id   = isset($_GET['chef_id']) && $_GET['chef_id'] !== '' ? (int)$_GET['chef_id'] : null;
$sort      = $_GET['sort'] ?? 'popular';

// Truy vấn danh mục
$stmt_cats = $pdo->query("SELECT * FROM loai_set ORDER BY thu_tu ASC");
$all_cats = $stmt_cats->fetchAll();

// Xây dựng SQL lọc
$sql = "
    SELECT s.*, l.ten_loai, 
           COALESCE(v.diem_tb, 5.0) as diem_tb, 
           COALESCE(v.so_danh_gia, 36) as so_danh_gia 
    FROM set_omakase s 
    JOIN loai_set l ON s.loai_set_id = l.id 
    LEFT JOIN v_set_danh_gia v ON v.id = s.id 
    WHERE s.trang_thai = 'Đang bán'
";
$params = [];
if ($loai_id !== null && $loai_id > 0) {
    $sql .= " AND s.loai_set_id = ?";
    $params[] = $loai_id;
}
if ($price_max !== null && $price_max > 0) {
    $sql .= " AND s.gia <= ?";
    $params[] = $price_max;
}
if ($chef_id !== null && $chef_id > 0) {
    $sql .= " AND s.bep_truong_id = ?";
    $params[] = $chef_id;
}

if ($sort === 'price_asc') {
    $sql .= " ORDER BY s.gia ASC";
} elseif ($sort === 'price_desc') {
    $sql .= " ORDER BY s.gia DESC";
} else {
    $sql .= " ORDER BY s.noi_bat DESC, s.id ASC";
}

$stmt_sets = $pdo->prepare($sql);
$stmt_sets->execute($params);
$sets = $stmt_sets->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sabi Omakase - Thực Đơn</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@400;600;700&display=swap"
        rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/Omakase_Menu.css">
</head>

<body>

  <header class="site-header">
    <div class="container">
      <a href="index.php" class="brand">
        <span class="brand-mark">祥</span>
        <span class="brand-text">SABI OMAKASE <small>一期一会</small></span>
      </a>

      <nav class="main-nav">
        <a href="index.php">Trang chủ</a>
        <a href="Omakase_Menu.php" class="active">Thực đơn</a>
        <a href="hoso_order_thanhtoan.php?view=booking">Đặt chỗ</a>
        <a href="index.php#triet-ly">Về chúng tôi</a>
        <a href="index.php#chi-nhanh">Liên hệ</a>
      </nav>

      <div class="header-actions">
        <a href="Omakase_Menu.php" class="icon-btn" title="Thực đơn Omakase"><i class="fa-solid fa-utensils"></i></a>
        <button class="icon-btn" title="Giỏ đặt bàn"
          onclick="location.href='hoso_order_thanhtoan.php?view=cart'"><i class="fa-solid fa-bag-shopping"></i><span class="badge"><?= $cart_count ?></span></button>
        <?php if ($user): ?>
          <a href="hoso_order_thanhtoan.php?view=profile" class="avatar-btn" title="Tài khoản"><i class="fa-solid fa-user-check" style="color:#d8b77a;"></i></a>
        <?php else: ?>
          <a href="login.php" class="avatar-btn" title="Tài khoản / Đăng nhập"><i class="fa-regular fa-user"></i></a>
        <?php endif; ?>
        <a href="hoso_order_thanhtoan.php?view=booking" class="btn btn-primary">ĐẶT BÀN</a>
      </div>
    </div>
  </header>

    <!-- Page Header -->
    <section class="page-header">
        <div class="breadcrumb">
            <a href="index.php" style="color:inherit;text-decoration:none;">Trang chủ</a> / <span class="current">Thực đơn Omakase</span>
        </div>
        <div class="header-content">
            <div class="header-text">
                <span class="tag"><i class="fa-solid fa-circle" style="font-size: 6px; margin-right: 5px;"></i> TOYOSU
                    DAILY SELECTION</span>
                <h1>Thực Đơn Omakase Thượng Hạng</h1>
                <p>Tuyển chọn hải sản tươi sống đánh bắt theo mùa và vận chuyển hỏa tốc trực tiếp từ chợ cá Toyosu
                    (Tokyo). Trải nghiệm nét giao hòa của nghệ thuật dao kiếm Edomae và kỹ thuật dưỡng vị Wabi-Sabi tinh
                    tế.</p>
            </div>
            <div class="header-info-card">
                <div class="icon"><i class="fa-solid fa-utensils"></i></div>
                <div class="info">
                    <div class="title">QUY CHUẨN PHỤC VỤ</div>
                    <div class="desc">12 Khách / Phiên Counter</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Sidebar Filter -->
        <aside class="sidebar">
            <form action="Omakase_Menu.php" method="GET" id="filterForm">
                   <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                <div class="sidebar-header">
                    <h3><i class="fa-solid fa-sliders"></i> Bộ Lọc</h3>
                    <a href="Omakase_Menu.php" class="clear-btn" style="text-decoration:none;display:inline-block;">XÓA BỘ LỌC</a>
                </div>

                <div class="filter-group">
                    <h4>LOẠI SET OMAKASE</h4>
                    <label class="checkbox-container">Tất cả
    <input type="radio" name="loai_id" value="" <?= $loai_id === null ? 'checked' : '' ?> onchange="this.form.submit()">
    <span class="checkmark" style="border-radius:50%;"></span>
</label>
                    <?php foreach ($all_cats as $cat): ?>
                        <label class="checkbox-container"><?= htmlspecialchars($cat['ten_loai']) ?>
                            <input type="radio" name="loai_id" value="<?= $cat['id'] ?>" <?= $loai_id == $cat['id'] ? 'checked' : '' ?> onchange="this.form.submit()">
                            <span class="checkmark" style="border-radius:50%;"></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="filter-group">
                    <h4>KHOẢNG GIÁ <span class="price-val" id="priceDisplay"><?= $price_max ? number_format($price_max, 0, ',', '.') . ' đ' : '5.000.000 đ' ?></span></h4>
                    <input type="range" name="price_max" min="1500000" max="5000000" step="200000" value="<?= $price_max ?: 5000000 ?>" class="slider" oninput="document.getElementById('priceDisplay').textContent = new Intl.NumberFormat('vi-VN').format(this.value) + ' đ'">
                    <div class="slider-labels">
                        <span>1.500.000 đ</span>
                        <span>5.000.000 đ</span>
                    </div>
                </div>

                <div class="filter-group">
                    <h4>SỐ LƯỢNG MÓN</h4>
                    <label class="radio-container">8 - 12 Món (Nhẹ nhàng)
                        <input type="radio" name="mon" value="12">
                        <span class="radiomark"></span>
                    </label>
                    <label class="radio-container">14 - 18 Món (Tiêu chuẩn)
                        <input type="radio" name="mon" value="18" checked>
                        <span class="radiomark"></span>
                    </label>
                    <label class="radio-container">20+ Món (Đặc biệt cao cấp)
                        <input type="radio" name="mon" value="20">
                        <span class="radiomark"></span>
                    </label>
                </div>

                <div class="filter-group">
                    <h4>BẾP TRƯỞNG PHỤ TRÁCH</h4>
                    <label class="checkbox-container">
                        <div>
                            <div>Chef Daiki Takahashi</div>
                            <div class="sub-text">Master Edomae (Ginza, Tokyo)</div>
                        </div>
                        <input type="radio" name="chef_id" value="7" <?= $chef_id == 7 ? 'checked' : '' ?> onchange="this.form.submit()">
                        <span class="checkmark" style="border-radius:50%;"></span>
                    </label>
                    <label class="checkbox-container">
                        <div>
                            <div>Chef Kenji Sato</div>
                            <div class="sub-text">Kaiseki Specialist (Kyoto)</div>
                        </div>
                        <input type="radio" name="chef_id" value="8" <?= $chef_id == 8 ? 'checked' : '' ?> onchange="this.form.submit()">
                        <span class="checkmark" style="border-radius:50%;"></span>
                    </label>
                    <label class="checkbox-container">
                        <div>
                            <div>Chef Hiroshi Mori</div>
                            <div class="sub-text">Sashimi & Sake Sommelier</div>
                        </div>
                        <input type="radio" name="chef_id" value="9" <?= $chef_id == 9 ? 'checked' : '' ?> onchange="this.form.submit()">
                        <span class="checkmark" style="border-radius:50%;"></span>
                    </label>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn-primary w-100"><i class="fa-solid fa-filter" style="margin-right: 5px;"></i> ÁP DỤNG BỘ LỌC</button>
                    <a href="Omakase_Menu.php" class="btn-outline w-100 mt-10" style="display:block;text-align:center;text-decoration:none;box-sizing:border-box;">ĐẶT LẠI MẶC ĐỊNH</a>
                </div>
            </form>
        </aside>

        <!-- Product List -->
        <div class="product-area">
            <div class="product-top-bar">
                <div>Hiển thị <strong><?= count($sets) ?></strong> set Omakase tuyển chọn</div>
                <div class="sort-by">
                    SẮP XẾP:
                    <div class="custom-select-wrapper">
                        <select class="custom-select" onchange="applySort(this.value)">
                            <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Được yêu thích nhất</option>
                            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="product-grid">
                <?php foreach ($sets as $set): 
                    $img = !empty($set['hinh_anh']) ? $set['hinh_anh'] : '../images/kiwami.jpg';
                    $tag = !empty($set['nhan']) ? $set['nhan'] : "SIGNATURE";
                    $tag_class = (strpos(strtoupper($tag), 'CHEF') !== false || $set['noi_bat']) ? 'tag-red' : 'tag-dark';
                ?>
                    <div class="product-card">
                        <div class="product-img-wrapper">
                            <span class="product-tag <?= $tag_class ?>"><?= htmlspecialchars($tag) ?></span>
                            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($set['ten_set']) ?>">
                            <div class="product-meta">
                                <div><i class="fa-solid fa-star"></i> <?= number_format($set['diem_tb'] > 0 ? $set['diem_tb'] : 5.0, 1) ?> (<?= $set['so_danh_gia'] ?> đánh giá)</div>
                                <div class="meta-separator"></div>
                                <div><i class="fa-regular fa-clock"></i> <?= $set['thoi_luong_phut'] ?> phút</div>
                            </div>
                        </div>
                        <div class="product-content">
                            <div class="product-subtitle"><?= htmlspecialchars($set['ten_tieng_nhat'] ?? 'おまかせ') ?> - <?= $set['so_mon'] ?> MÓN</div>
                            <h3 class="product-title"><?= htmlspecialchars($set['ten_set']) ?></h3>
                            <p class="product-desc"><?= htmlspecialchars($set['mo_ta_ngan'] ?? 'Trải nghiệm đỉnh cao với hải sản Toyosu thượng hạng...') ?></p>
                            <div class="product-bottom">
                                <div class="product-price">Giá cho 1 khách<span class="val"><?= number_format($set['gia'], 0, ',', '.') ?> đ</span></div>
                                <button class="btn-detail" onclick="location.href='Omakase_Detail.php?id=<?= $set['id'] ?>'">XEM CHI TIẾT</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <div class="pagination-info">
                Hiển thị <?= count($sets) ?> trên tổng số <?= count($sets) ?> thực đơn Omakase
            </div>
            <div class="pagination">
                <a href="#" class="prev"><i class="fa-solid fa-chevron-left"></i></a>
                <a href="#" class="active">1</a>
                <a href="#" class="next"><i class="fa-solid fa-chevron-right"></i></a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-col brand-col">
                <div class="navbar-logo" style="margin-bottom: 20px;">
                    <div class="logo-icon"
                        style="background-color: transparent; border: 1px solid var(--border-color); padding: 5px; border-radius: 4px;">
                        <img src="https://cdn-icons-png.flaticon.com/512/1895/1895684.png" alt="Sabi Logo"
                            style="width: 20px; filter: invert(1);">
                    </div>
                    <div>
                        <div class="logo-text" style="font-size: 16px;">SABI OMAKASE</div>
                    </div>
                </div>
                <p>Tận hưởng triết lý Wabi-Sabi qua từng thớ cá tinh khiết. Nơi nghệ thuật Omakase giao hòa giữa sự tối
                    giản thuần túy và nghi thức ẩm thực thượng lưu Nhật Bản.</p>
                <div class="locations">TOKYO • KYOTO • SAIGON</div>
            </div>
            <div class="footer-col">
                <h4>Điều hướng</h4>
                <ul>
                    <li><a href="index.php">Trang chủ</a></li>
                    <li><a href="Omakase_Menu.php">Thực đơn Omakase</a></li>
                    <li><a href="hoso_order_thanhtoan.php?view=cart">Đặt chỗ &amp; Giỏ hàng</a></li>
                    <li><a href="index.php#triet-ly">Về chúng tôi</a></li>
                    <li><a href="index.php#chi-nhanh">Liên hệ</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Liên hệ</h4>
                <ul>
                    <li><i class="fa-solid fa-phone"></i> +123456789</li>
                    <li><i class="fa-solid fa-envelope"></i> reservation@sabiomakase.com</li>
                    <li><i class="fa-solid fa-location-dot"></i> <span>12 đường số 3, Phường 4, Quận 5, Thành phố 6</span></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Giờ mở cửa</h4>
                <ul class="hours-list">
                    <li><span>Thứ 2 - Thứ 6:</span> 17:30 - 22:30</li>
                    <li><span>Thứ 7 - CN:</span>
                        <div>11:30 - 14:00<br>17:30 - 23:00</div>
                    </li>
                </ul>
                <button class="btn-outline w-100 mt-20" style="font-size: 10px; padding: 12px;" onclick="location.href='hoso_order_thanhtoan.php?view=booking'">CHỈ NHẬN PHỤC VỤ ĐẶT TRƯỚC</button>
            </div>
        </div>
        <div class="footer-bottom">
            <div>© 2024 Sabi Omakase. Bản quyền thuộc về nhà hàng.</div>
            <div>Cam kết nguồn nguyên liệu tươi nhập khẩu trực tiếp từ chợ Toyosu mỗi sáng.</div>
        </div>
    </footer>

    <script>
        function applySort(val) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', val);
            window.location.href = url.toString();
        }
    </script>
</body>

</html>
