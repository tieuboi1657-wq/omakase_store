<?php
require_once __DIR__ . '/config/db.php';

$id = $_GET['id'] ?? 1;
if (is_numeric($id)) {
    $stmt = $pdo->prepare("
        SELECT s.*, l.ten_loai, nv.ho_ten AS ten_bep_truong, nv.chuc_danh AS chuc_danh_bep, nv.chuyen_mon,
               COALESCE(v.diem_tb, 5.0) AS diem_tb, COALESCE(v.so_danh_gia, 48) AS so_danh_gia
        FROM set_omakase s
        JOIN loai_set l ON s.loai_set_id = l.id
        LEFT JOIN nhan_vien nv ON s.bep_truong_id = nv.id
        LEFT JOIN v_set_danh_gia v ON v.id = s.id
        WHERE s.id = ?
    ");
    $stmt->execute([(int)$id]);
} else {
    $stmt = $pdo->prepare("
        SELECT s.*, l.ten_loai, nv.ho_ten AS ten_bep_truong, nv.chuc_danh AS chuc_danh_bep, nv.chuyen_mon,
               COALESCE(v.diem_tb, 5.0) AS diem_tb, COALESCE(v.so_danh_gia, 48) AS so_danh_gia
        FROM set_omakase s
        JOIN loai_set l ON s.loai_set_id = l.id
        LEFT JOIN nhan_vien nv ON s.bep_truong_id = nv.id
        LEFT JOIN v_set_danh_gia v ON v.id = s.id
        WHERE LOWER(s.ma_set) = LOWER(?)
    ");
    $stmt->execute([$id]);
}
$set = $stmt->fetch();
if (!$set) {
    header("Location: Omakase_Menu.php");
    exit;
}

// Lấy danh sách ảnh
$stmt_img = $pdo->prepare("SELECT * FROM hinh_anh_set WHERE set_id = ? ORDER BY thu_tu ASC");
$stmt_img->execute([$set['id']]);
$gallery = $stmt_img->fetchAll();
if (empty($gallery)) {
    $main_pic = !empty($set['hinh_anh']) ? $set['hinh_anh'] : 'https://images.unsplash.com/photo-1611143669185-af224c5e3252?q=80&w=1200&auto=format&fit=crop';
    $gallery = [
        ['duong_dan' => $main_pic],
        ['duong_dan' => 'https://images.unsplash.com/photo-1553621042-f6e147245754?q=80&w=400&auto=format&fit=crop'],
        ['duong_dan' => 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?q=80&w=400&auto=format&fit=crop'],
        ['duong_dan' => 'https://images.unsplash.com/photo-1544025162-811114757e79?q=80&w=400&auto=format&fit=crop'],
    ];
}

// Lấy danh sách món
$stmt_mon = $pdo->prepare("SELECT * FROM mon_trong_set WHERE set_id = ? ORDER BY thu_tu ASC");
$stmt_mon->execute([$set['id']]);
$courses = $stmt_mon->fetchAll();
if (empty($courses)) {
    $courses = [
        ['nhom_mon' => 'KHAI VỊ', 'noi_dung' => 'Gan ngỗng Ankimo ủ rượu vang ngọt, Súp nấm Matsutake thượng hạng ấm nồng.'],
        ['nhom_mon' => 'SASHIMI', 'noi_dung' => 'Bào ngư Ezo hầm rượu sake 14 ngày & sốt gan, Cá Cam Buri hun khói rơm ra mùa đông.'],
        ['nhom_mon' => 'NIGIRI 10', 'noi_dung' => 'Otoro ủ lạnh 14 ngày dát vàng, Nhum biển Murasaki Uni Hokkaido, Sò điệp Hotate áp chảo muối tuyết, Lươn biển Anago sốt tsume cổ truyền...'],
        ['nhom_mon' => 'TRÁNG MIỆNG', 'noi_dung' => 'Trứng cuộn Tamagoyaki xốp mịn vị mật ong rừng, Kem Wagashi trà xanh Uji và dưa lưới Shizuoka.']
    ];
}

$cart_count = cart_total_items();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($set['ten_set']) ?> - Sabi Omakase</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/Omakase_Detail.css">
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

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <span><a href="index.php" style="color:inherit;text-decoration:none;">Trang chủ</a></span> <span style="margin: 0 5px; color: #555;">-</span> <span><a href="Omakase_Menu.php" style="color:inherit;text-decoration:none;">Thực đơn Omakase</a></span> <span style="margin: 0 5px; color: #555;">-</span> <span class="current"><?= htmlspecialchars($set['ten_set']) ?></span>
    </div>

    <!-- Product Hero Section -->
    <section class="product-hero">
        <!-- Left: Gallery -->
        <div class="hero-gallery">
            <div class="main-image-container">
                <div class="main-image-tag"><?= htmlspecialchars($set['ma_set']) ?> - <?= htmlspecialchars($set['ten_tieng_nhat'] ?? '極み') ?></div>
                <img id="mainImage" src="<?= htmlspecialchars($gallery[0]['duong_dan']) ?>" alt="<?= htmlspecialchars($set['ten_set']) ?>">
                <div class="main-image-credit">Toyosu Tokyo Direct</div>
            </div>
            <div class="thumbnail-list">
                <?php foreach ($gallery as $idx => $g): ?>
                    <div class="thumbnail <?= $idx === 0 ? 'active' : '' ?>" onclick="switchThumb(this, '<?= htmlspecialchars($g['duong_dan']) ?>')">
                        <img src="<?= htmlspecialchars($g['duong_dan']) ?>" alt="Thumbnail <?= $idx + 1 ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="experience-banner">
                <div><i class="fa-solid fa-braille" style="margin-right: 8px;"></i> Trải nghiệm đa giác quan tại quầy gỗ Hinoki 300 năm tuổi</div>
                <div class="highlight">TOKYO STANDARD</div>
            </div>
        </div>

        <!-- Right: Info -->
        <div class="hero-info">
            <div class="hero-tags">
                <i class="fa-solid fa-circle"></i> <?= htmlspecialchars($set['nhan'] ?? "CHEF'S MASTERPIECE") ?> • TOYOSU DIRECT
            </div>
            
            <h1 class="hero-title"><?= htmlspecialchars($set['ten_set']) ?><br><span>(<?= htmlspecialchars($set['ten_tieng_nhat'] ?? '極み') ?>)</span></h1>
            
            <div class="hero-meta">
                <div><i class="fa-solid fa-star"></i> <?= number_format($set['diem_tb'] > 0 ? $set['diem_tb'] : 5.0, 1) ?> <span style="color: #777;">(<?= $set['so_danh_gia'] ?> đánh giá)</span></div>
                <div style="color: #444;">•</div>
                <div><i class="fa-regular fa-clock"></i> <?= $set['thoi_luong_phut'] ?> phút</div>
                <div style="color: #444;">•</div>
                <div><i class="fa-solid fa-utensils"></i> <?= $set['so_mon'] ?> món tinh hoa</div>
            </div>

            <div class="hero-price-box">
                <div class="price-box-header">
                    <div class="price-label">GIÁ NIÊM YẾT BÀN TIỆC</div>
                    <div class="price-tag-green">Đã gồm thuế & phí phục vụ</div>
                </div>
                <div class="price-display">
                    <span class="price-value"><?= number_format($set['gia'], 0, ',', '.') ?> ₫</span> <span class="price-unit">/ Khách</span>
                </div>
            </div>

            <p class="hero-desc"><?= htmlspecialchars($set['mo_ta_ngan'] ?? '"Hành trình ẩm thực chạm tới đỉnh cao Wabi-Sabi, nơi bếp trưởng kết nối từng nhịp thở của đại dương Nhật Bản với nhiệt độ chuẩn xác của đôi bàn tay nghệ nhân."') ?></p>

            <div class="course-list-box">
                <div class="course-list-header">
                    <h3><i class="fa-solid fa-book-open"></i> Danh sách <?= $set['so_mon'] ?> món tiêu biểu</h3>
                    <span>TRÌNH TỰ PHỤC VỤ</span>
                </div>
                <div class="course-list">
                    <?php foreach ($courses as $c): ?>
                        <div class="course-item">
                            <div class="course-tag <?= strpos($c['nhom_mon'], 'NIGIRI') !== false ? 'tag-red' : 'tag-gray' ?>"><?= htmlspecialchars($c['nhom_mon']) ?></div>
                            <div class="course-text"><?= htmlspecialchars($c['noi_dung']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="booking-controls">
                <div class="qty-row">
                    <div class="qty-label">Số lượng khách đặt:</div>
                    <div class="qty-selector">
                        <button class="qty-btn" type="button" onclick="changeQty(-1)">-</button>
                        <div class="qty-val" id="qtyVal">2</div>
                        <button class="qty-btn" type="button" onclick="changeQty(1)">+</button>
                    </div>
                </div>
                <div class="booking-notice">
                    <i class="fa-solid fa-circle-info"></i>
                    <div>Giới hạn tối đa 12 khách/phiên quầy counter để đảm bảo độ chuẩn xác vị giác.</div>
                </div>
                <div class="action-buttons">
                    <button class="btn-book" type="button" onclick="bookNow()"><i class="fa-regular fa-calendar"></i> ĐẶT NGAY SUẤT NÀY</button>
                    <button class="btn-save" type="button" onclick="saveBooking()"><i class="fa-regular fa-bookmark"></i> LƯU ĐẶT CHỖ</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Lower Content Section -->
    <section class="content-section">
        <div class="tabs">
            <div class="tab active" onclick="showTab(0, this)">Mô tả chi tiết & Nguồn gốc nguyên liệu</div>
            <div class="tab" onclick="showTab(1, this)">Bếp trưởng phụ trách</div>
            <div class="tab" onclick="showTab(2, this)">Đánh giá từ thượng khách (<?= $set['so_danh_gia'] ?>)</div>
        </div>

        <div class="tab-content-area" id="tab0">
            <div class="content-left">
                <h2 class="content-title">Hành trình vị giác Toyosu bay trong đêm</h2>
                <div class="content-text">
                    <p><?= nl2br(htmlspecialchars($set['mo_ta_chi_tiet'] ?? "Mỗi buổi sáng lúc 4:30 AM tại chợ cá Toyosu (Tokyo), các chuyên gia tuyển chọn hải sản (Nakagai) danh tiếng làm việc trực tiếp cùng bếp trưởng để chọn ra những cá thể ngừ vây xanh hon-maguro loại 1, nhum biển nguyên con vùng Nemuro và cá cam Buri mùa béo ngậy.")) ?></p>
                    <p>Hải sản tươi sống được xử lý theo phương pháp <strong>Ikejime</strong> cổ truyền nhằm khóa trọn độ tinh khiết và vận chuyển máy bay hỏa tốc đến căn bếp Sabi trước 15:00 mỗi ngày.</p>
                </div>
                <div class="detail-cards">
                    <div class="detail-card">
                        <div class="detail-card-title">GẠO NIGIRI</div>
                        <div class="detail-card-desc">Koshihikari Niigata ủ với giấm đỏ Akazu ủ mặn gỗ 3 năm.</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-card-title">GIA VỊ NỀM</div>
                        <div class="detail-card-desc">Wasabi củ tươi Shizuoka mài bàn cá đuối truyền thống.</div>
                    </div>
                </div>
            </div>
            <div class="content-right">
                <img src="./images/toyosu_market.jpg" alt="Toyosu Market">
                <div class="content-img-caption">Toyosu Market, Tokyo - 04:30 AM Daily Selection</div>
            </div>
        </div>

        <div class="tab-content-area" id="tab1" style="display:none;">
            <div class="content-left">
                <h2 class="content-title"><?= htmlspecialchars($set['ten_bep_truong'] ?? 'Chef Daiki Takahashi') ?></h2>
                <div class="content-text">
                    <p><strong><?= htmlspecialchars($set['chuc_danh_bep'] ?? 'Bếp trưởng') ?></strong> • <?= htmlspecialchars($set['chuyen_mon'] ?? 'Master Edomae (Ginza, Tokyo)') ?></p>
                    <p>Với hơn 25 năm kinh nghiệm phục vụ tại các nhà hàng danh tiếng tại Ginza, bếp trưởng coi mỗi phiên Omakase là một cuộc giao hòa nghệ thuật duy nhất trong đời giữa nghệ nhân và thực khách.</p>
                </div>
            </div>
            <div class="content-right">
                <img src="./images/chef.jpg" alt="Chef">
            </div>
        </div>

        <div class="tab-content-area" id="tab2" style="display:none;">
            <div class="content-left" style="width:100%;">
                <h2 class="content-title">Nhận xét từ thượng khách đã trải nghiệm</h2>
                <div style="margin-top:20px;display:flex;flex-direction:column;gap:15px;">
                    <div style="background:#1a1a1a;padding:16px;border-radius:6px;border:1px solid #2a2a2a;">
                        <div style="color:#d8b77a;font-size:12px;margin-bottom:6px;">★★★★★ • Trần Đình Khang (Hội viên VIP)</div>
                        <p style="font-size:13px;color:#ccc;">"Nguyên liệu tươi, bếp trưởng giải thích từng món rất tận tình. Otoro dát vàng xuất sắc."</p>
                    </div>
                    <div style="background:#1a1a1a;padding:16px;border-radius:6px;border:1px solid #2a2a2a;">
                        <div style="color:#d8b77a;font-size:12px;margin-bottom:6px;">★★★★★ • Nguyễn Đăng Quang (Hội viên VIP)</div>
                        <p style="font-size:13px;color:#ccc;">"Otoro tan ngay khi vào miệng. Không gian yên tĩnh, ấm cúng và tinh tế."</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-col brand-col">
                <div class="navbar-logo" style="margin-bottom: 20px;">
                    <div class="logo-icon" style="background-color: transparent; border: 1px solid var(--border-color); padding: 5px; border-radius: 4px;">
                        <img src="https://cdn-icons-png.flaticon.com/512/1895/1895684.png" alt="Sabi Logo" style="width: 20px; filter: invert(1);">
                    </div>
                    <div>
                        <div class="logo-text" style="font-size: 16px;">SABI OMAKASE</div>
                    </div>
                </div>
                <p>Tận hưởng triết lý Wabi-Sabi qua từng thớ cá tinh khiết. Nơi nghệ thuật Omakase giao hòa giữa sự tối giản thuần túy và nghi thức ẩm thực thượng lưu Nhật Bản.</p>
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
                    <li><span>Thứ 7 - CN:</span> <div>11:30 - 14:00<br>17:30 - 23:00</div></li>
                </ul>
                <button class="btn-outline w-100 mt-20" style="font-size: 10px; padding: 12px;" onclick="location.href='hoso_order_thanhtoan.php?view=booking'">CHỈ NHẬN PHỤC VỤ ĐẶT TRƯỚC</button>
            </div>
        </div>
        <div class="footer-bottom">
            <div>© 2024 Sabi Omakase. Bản quyền thuộc về nhà hàng.</div>
            <div>Cam kết nguồn nguyên liệu tươi nhập khẩu trực tiếp từ chợ Toyosu mỗi sáng.</div>
        </div>
    </footer>

    <script type="application/json" id="set-data"><?= json_encode(['price' => (float)$set['gia'], 'name' => $set['ten_set']], JSON_UNESCAPED_UNICODE) ?></script>
    <script>
        let curQty = 2;
        const setData = JSON.parse(document.getElementById('set-data').textContent || '{}');
        const setPrice = Number(setData.price) || 0;
        const setName = setData.name || '';

        function switchThumb(el, src) {
            document.getElementById('mainImage').src = src;
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            el.classList.add('active');
        }

        function changeQty(d) {
            curQty = Math.max(1, Math.min(12, curQty + d));
            document.getElementById('qtyVal').textContent = curQty;
        }

        function bookNow() {
            location.href = 'hoso_order_thanhtoan.php?view=cart&name=' + encodeURIComponent(setName) + '&price=' + setPrice + '&qty=' + curQty;
        }

        function saveBooking() {
            location.href = 'hoso_order_thanhtoan.php?view=booking&name=' + encodeURIComponent(setName) + '&price=' + setPrice + '&qty=' + curQty;
        }

        function showTab(idx, tabEl) {
            document.querySelectorAll('.tabs .tab').forEach(t => t.classList.remove('active'));
            tabEl.classList.add('active');
            ['tab0', 'tab1', 'tab2'].forEach((id, i) => {
                const el = document.getElementById(id);
                if (el) el.style.display = (i === idx) ? 'flex' : 'none';
            });
        }
    </script>
</body>
</html>
