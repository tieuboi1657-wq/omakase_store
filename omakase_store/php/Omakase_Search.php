<?php
require_once __DIR__ . '/config/db.php';

$cart_count = cart_total_items();
$user = current_user();

$keyword = trim($_GET['q'] ?? $_GET['keyword'] ?? 'Omakase');

// Truy vấn các danh mục
$stmt_cats = $pdo->query("SELECT * FROM loai_set ORDER BY thu_tu ASC");
$categories = $stmt_cats->fetchAll();

// Truy vấn danh sách set
$sql = "
    SELECT s.*, l.ten_loai, 
           COALESCE(v.diem_tb, 5.0) as diem_tb, 
           COALESCE(v.so_danh_gia, 48) as so_danh_gia 
    FROM set_omakase s 
    LEFT JOIN loai_set l ON s.loai_set_id = l.id 
    LEFT JOIN v_set_danh_gia v ON v.id = s.id 
    WHERE s.trang_thai = 'Đang bán'
";
$params = [];
if (!empty($keyword) && $keyword !== 'Omakase') {
    $sql .= " AND (s.ten_set LIKE ? OR s.mo_ta_ngan LIKE ? OR l.ten_loai LIKE ?)";
    $term = "%{$keyword}%";
    $params = [$term, $term, $term];
}
$sql .= " ORDER BY s.noi_bat DESC, s.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();
$total_count = count($results);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sabi Omakase - Tìm Kiếm Thực Đơn</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@400;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/Omakase_Search.css">
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
        <a href="Omakase_Menu.php">Thực đơn</a>
        <a href="hoso_order_thanhtoan.php?view=booking">Đặt chỗ</a>
        <a href="index.php#triet-ly">Về chúng tôi</a>
        <a href="danhgia.php">Liên hệ</a>
      </nav>

      <div class="header-actions">
        <a href="Omakase_Search.php" class="icon-btn active" title="Tìm kiếm"><i class="fa-solid fa-magnifying-glass"></i></a>
        <button class="icon-btn" title="Giỏ đặt bàn"
          onclick="location.href='hoso_order_thanhtoan.php?view=cart'"><i class="fa-solid fa-bag-shopping"></i><span class="badge"><?= $cart_count ?></span></button>
        <?php if ($user): ?>
          <a href="hoso_order_thanhtoan.php?view=profile" class="avatar-btn" title="Hồ sơ: <?= htmlspecialchars($user['ho_ten']) ?>"><i class="fa-solid fa-user-check" style="color:#d8b77a;"></i></a>
        <?php else: ?>
          <a href="login.php" class="avatar-btn" title="Tài khoản / Đăng nhập"><i class="fa-regular fa-user"></i></a>
        <?php endif; ?>
        <a href="hoso_order_thanhtoan.php?view=booking" class="btn btn-primary">ĐẶT BÀN</a>
      </div>
    </div>
  </header>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <i class="fa-solid fa-house" style="margin-right: 5px;"></i> <a href="index.php" style="color:inherit;text-decoration:none;">Trang chủ</a> <span
            style="margin: 0 5px; color: #444;">/</span> <span class="current">Tìm kiếm thực đơn</span>
    </div>

    <!-- Search Hero Section -->
    <section class="search-hero">
        <div class="search-tag"><i class="fa-solid fa-circle"
                style="font-size: 6px; margin-right: 6px; color: var(--primary-red);"></i>TUYỂN TẬP ẨM THỰC OMAKASE
            THƯỢNG HẠNG</div>
        <h1>Khám Phá Hương Vị Mùa Thu Toyosu</h1>

        <form action="Omakase_Search.php" method="GET" class="search-bar">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>">
            <button type="submit">TÌM KIẾM <i class="fa-solid fa-arrow-right"></i></button>
        </form>

        <div class="search-suggestions">
            <span class="label"><i class="fa-solid fa-arrow-turn-up" style="transform: rotate(90deg);"></i> Gợi
                ý:</span>
            <span class="tag">Set Otoro Thượng Hạng</span>
            <span class="tag">Omakase Trưa</span>
            <span class="tag">Bếp Trưởng Daiki</span>
            <span class="tag">Wagyu A5</span>
            <span class="tag">Kaiseki</span>
        </div>
    </section>

    <!-- Results Header -->
    <div class="results-header">
        <div class="results-text">
            <span class="dot"></span> Tìm thấy <strong><?= $total_count ?> kết quả</strong> phù hợp cho từ khoá: <em>"<?= htmlspecialchars($keyword) ?>"</em>
        </div>
        <div class="sort-by">
            SẮP XẾP THEO:
            <div class="custom-select-wrapper">
                <select class="custom-select" onchange="location.href='Omakase_Menu.php?sort=' + this.value">
                    <option value="popular">Được khuyên dùng nhất</option>
                    <option value="price_asc">Giá tăng dần</option>
                    <option value="price_desc">Giá giảm dần</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Sidebar Filter -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fa-solid fa-sliders"></i> Bộ Lọc Tìm Kiếm</h3>
                <button class="clear-btn" onclick="location.href='Omakase_Search.php?q=Omakase'">XÓA LỌC</button>
            </div>

            <div class="filter-group">
                <h4><i class="fa-solid fa-square" style="font-size: 4px;"></i> DANH MỤC THỰC ĐƠN</h4>
                <?php foreach ($categories as $idx => $cat): ?>
                <label class="checkbox-container">
                    <div class="label-text">
                        <span><?= htmlspecialchars($cat['ten_loai']) ?></span>
                        <span class="count">(<?= $cat['so_set'] ?? 3 ?>)</span>
                    </div>
                    <input type="checkbox" <?= $idx < 2 ? 'checked' : '' ?> onchange="location.href='Omakase_Menu.php?loai_id=<?= $cat['id'] ?>'">
                    <span class="checkmark"></span>
                </label>
                <?php endforeach; ?>
            </div>

            <div class="filter-group">
                <h4><i class="fa-solid fa-square" style="font-size: 4px;"></i> KHOẢNG GIÁ <span
                        style="float:right; color:var(--text-gray); font-size:9px;">1M - 5M+ đ</span></h4>
                <div class="range-slider-mock">
                    <div class="active-track"></div>
                    <div class="thumb left"></div>
                    <div class="thumb right"></div>
                </div>
                <div class="slider-labels">
                    <span>1.000.000 <u>đ</u></span>
                    <span>5.000.000 <u>đ</u></span>
                </div>
            </div>

            <div class="filter-group">
                <h4><i class="fa-solid fa-square" style="font-size: 4px;"></i> CHI NHÁNH PHỤC VỤ</h4>
                <label class="checkbox-container">
                    <div class="label-text"><span>Quận 1 (Sài Gòn)</span></div>
                    <input type="checkbox" checked>
                    <span class="checkmark"></span>
                </label>
                <label class="checkbox-container">
                    <div class="label-text"><span>Quận 5 (Chợ Lớn)</span></div>
                    <input type="checkbox" checked>
                    <span class="checkmark"></span>
                </label>
                <label class="checkbox-container">
                    <div class="label-text"><span>Tây Hồ (Hà Nội)</span></div>
                    <input type="checkbox">
                    <span class="checkmark"></span>
                </label>
            </div>

            <div class="filter-group" style="border-bottom: none; margin-bottom: 20px;">
                <h4><i class="fa-solid fa-square" style="font-size: 4px;"></i> ĐÁNH GIÁ TỐI THIỂU</h4>
                <label class="radio-container">
                    <div class="label-text">
                        <span>Từ 4.8 sao</span>
                        <span class="stars">
                            <i class="fa-solid fa-star active"></i>
                            <i class="fa-solid fa-star active"></i>
                            <i class="fa-solid fa-star active"></i>
                            <i class="fa-solid fa-star active"></i>
                            <i class="fa-regular fa-star active"></i>
                        </span>
                    </div>
                    <input type="radio" name="rating" checked>
                    <span class="radiomark"></span>
                </label>
                <label class="radio-container">
                    <div class="label-text">
                        <span>Từ 4.5 sao</span>
                        <span class="stars">
                            <i class="fa-solid fa-star active"></i>
                            <i class="fa-solid fa-star active"></i>
                            <i class="fa-solid fa-star active"></i>
                            <i class="fa-solid fa-star active"></i>
                            <i class="fa-regular fa-star"></i>
                        </span>
                    </div>
                    <input type="radio" name="rating">
                    <span class="radiomark"></span>
                </label>
            </div>

            <button class="btn-apply" onclick="location.href='Omakase_Menu.php'"><i class="fa-solid fa-filter"></i> ÁP DỤNG BỘ LỌC</button>
        </aside>

        <!-- Product List -->
        <div class="product-area">
            <div class="product-grid">
                <?php if (empty($results)): ?>
                    <div style="grid-column: 1 / -1; padding: 60px 20px; text-align: center; color: #888;">
                        <i class="fa-solid fa-magnifying-glass" style="font-size: 36px; color: #444; margin-bottom: 15px; display: block;"></i>
                        Không tìm thấy set Omakase nào phù hợp với từ khóa "<?= htmlspecialchars($keyword) ?>".
                    </div>
                <?php else: ?>
                    <?php foreach ($results as $set): 
                        $img = !empty($set['hinh_anh']) ? $set['hinh_anh'] : 'https://images.unsplash.com/photo-1611143669185-af224c5e3252?q=80&w=600&auto=format&fit=crop';
                    ?>
                        <div class="product-card" onclick="location.href='Omakase_Detail.php?id=<?= $set['id'] ?>'" style="cursor:pointer;">
                            <div class="product-img-wrapper">
                                <?php if ($set['noi_bat']): ?>
                                    <span class="product-tag tag-red">PHÙ HỢP NHẤT</span>
                                <?php else: ?>
                                    <span class="product-tag tag-dark">EDOMAE CỔ ĐIỂN</span>
                                <?php endif; ?>
                                <i class="fa-regular fa-heart product-heart" onclick="event.stopPropagation();this.classList.toggle('fa-solid');this.classList.toggle('fa-regular');"></i>
                                <span class="product-bottom-tag"><?= (int)($set['so_mon'] ?? 16) ?> MÓN TINH HOA</span>
                                <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($set['ten_set']) ?>">
                            </div>
                            <div class="product-content">
                                <div class="product-meta">
                                    <span class="meta-tag">CHEF DAIKI SELECTION</span>
                                    <span class="meta-rating"><i class="fa-regular fa-star"></i> <?= number_format($set['diem_tb'], 1) ?> (<?= (int)$set['so_danh_gia'] ?>)</span>
                                </div>
                                <h3 class="product-title"><?= htmlspecialchars($set['ten_set']) ?></h3>
                                <p class="product-desc"><?= htmlspecialchars($set['mo_ta_ngan']) ?></p>
                                <div class="product-bottom">
                                    <div>
                                        <span class="price-label">GIÁ MỖI KHÁCH</span>
                                        <span class="price-val"><?= format_vnd($set['gia']) ?></span>
                                    </div>
                                    <div class="product-action">CHI TIẾT <i class="fa-solid fa-chevron-right"></i></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-col brand-col">
                <div class="logo">
                    <span class="logo-circle">祥</span>
                    <span class="logo-text">SABI OMAKASE</span>
                </div>
                <p>Tận hưởng triết lý Wabi Sabi qua từng thớ cá tinh khiết. Nơi nghệ thuật ẩm thực giao hòa giữa sự tối
                    giản thuần túy và nghi thức ẩm thực thượng lưu Nhật Bản.</p>
                <div class="cities">TOKYO • KYOTO • SAIGON</div>
            </div>
            <div class="footer-col">
                <h4>Điều hướng</h4>
                <ul>
                    <li><a href="index.php">Trang chủ</a></li>
                    <li><a href="Omakase_Menu.php">Thực đơn Omakase</a></li>
                    <li><a href="hoso_order_thanhtoan.php?view=cart">Đặt chỗ &amp; Giỏ hàng</a></li>
                    <li><a href="lich-su-dat-cho.php">Lịch sử đặt tiệc</a></li>
                    <li><a href="index.php#triet-ly">Về chúng tôi</a></li>
                    <li><a href="danhgia.php">Liên hệ</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Chi nhánh</h4>
                <div class="branch-item">
                    <h5>SABI SÀI GÒN - QUẬN 1</h5>
                    <p>18Bis/24 Nguyễn Thị Minh Khai, Đa Kao</p>
                </div>
                <div class="branch-item mt-10">
                    <h5>SABI CHỢ LỚN - QUẬN 5</h5>
                    <p>12 Đường số 3, Cư xá Trần Hưng Đạo</p>
                </div>
            </div>
            <div class="footer-col">
                <h4>Giờ phục vụ</h4>
                <ul class="hours-list">
                    <li>
                        <span>Thứ 2 - Thứ 6:</span>
                        <span>17:30 - 22:30</span>
                    </li>
                    <li>
                        <span>Thứ 7 - CN:</span>
                        <span>11:30 - 14:00<br>17:30 - 23:00</span>
                    </li>
                </ul>
                <button class="btn-outline w-100 mt-20"
                    style="font-size: 10px; padding: 12px; letter-spacing: 0.5px;" onclick="location.href='hoso_order_thanhtoan.php?view=booking'">CHỈ NHẬN PHỤC VỤ ĐẶT TRƯỚC</button>
            </div>
        </div>
        <div class="footer-bottom">
            <div>© 2026 Sabi Omakase. Bản quyền thuộc về nhà hàng.</div>
            <div>Cam kết nguồn nguyên liệu tươi nhập khẩu trực tiếp từ chợ Toyosu mỗi sáng.</div>
        </div>
    </footer>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const searchBar = document.querySelector(".search-bar");
        const searchInput = searchBar ? searchBar.querySelector("input") : null;
        const suggestionTags = document.querySelectorAll(".search-suggestions .tag");
        const resultsText = document.querySelector(".results-text");
        const productCards = document.querySelectorAll(".product-card");

        function handleSearch(keyword) {
            if (!keyword) return;
            const query = keyword.toLowerCase().trim();
            let matchCount = 0;

            productCards.forEach(card => {
                const title = card.querySelector(".product-title")?.textContent.toLowerCase() || "";
                const desc = card.querySelector(".product-desc")?.textContent.toLowerCase() || "";
                const meta = card.querySelector(".meta-tag")?.textContent.toLowerCase() || "";

                if (title.includes(query) || desc.includes(query) || meta.includes(query)) {
                    card.style.display = "block";
                    matchCount++;
                } else {
                    card.style.display = "none";
                }
            });

            if (resultsText) {
                resultsText.innerHTML = `<span class="dot"></span> Tìm thấy <strong>${matchCount} kết quả</strong> phù hợp cho từ khoá: <em>"${keyword}"</em>`;
            }
        }

        suggestionTags.forEach(tag => {
            tag.addEventListener("click", function () {
                const selectedText = this.textContent.trim();
                if (searchInput) {
                    searchInput.value = selectedText;
                }
                handleSearch(selectedText);
            });
        });
    });
    </script>
</body>
</html>
