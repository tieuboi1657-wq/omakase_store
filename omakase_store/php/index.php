<?php
require_once __DIR__ . '/config/db.php';

// Lấy danh sách gói cho widget
$stmt_cats = $pdo->query("SELECT * FROM loai_set ORDER BY thu_tu ASC");
$categories = $stmt_cats->fetchAll();

// Lấy các set nổi bật từ database
$stmt_featured = $pdo->query("
    SELECT s.*, l.ten_loai, 
           COALESCE(v.diem_tb, 5.0) as diem_tb, 
           COALESCE(v.so_danh_gia, 48) as so_danh_gia 
    FROM set_omakase s 
    LEFT JOIN loai_set l ON s.loai_set_id = l.id 
    LEFT JOIN v_set_danh_gia v ON v.id = s.id 
    WHERE s.noi_bat = 1 AND s.trang_thai = 'Đang bán' 
    ORDER BY s.id ASC
    LIMIT 4
");
$featured_sets = $stmt_featured->fetchAll();
$cart_count = cart_total_items();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Trang Chủ - Sabi Omakase</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link
    href="https://fonts.googleapis.com/css2?family=Noto+Serif:wght@500;600;700&family=Noto+Sans:wght@400;500;600;700&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css" />
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
        <a href="index.php" class="active">Trang chủ</a>
        <a href="Omakase_Menu.php">Thực đơn</a>
        <a href="hoso_order_thanhtoan.php?view=booking">Đặt chỗ</a>
        <a href="index.php#triet-ly">Về chúng tôi</a>
        <a href="#chi-nhanh">Liên hệ</a>
      </nav>

      <div class="header-actions">
        <a href="Omakase_Menu.php" class="icon-btn" title="Thực đơn Omakase"><i class="fa-solid fa-utensils"></i></a>
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

  <!-- ============ HERO ============ -->
  <section class="hero">
    <div class="container">
      <div class="hero-content">
        <p class="eyebrow">TRẢI NGHIỆM ẨM THỰC TỐI THƯỢNG — 一期一会</p>
        <h1>Nghệ Thuật Omakase — Tinh Hoa Nhật Bản Khởi Nguồn Từ Đam Mê</h1>
        <p>Dưới bàn tay điêu luyện của nghệ nhân, từng thớ cá Toyosu hòa quyện cùng hạt gạo Koshihikari ủ giấm Akazu.
          Một khoảnh khắc tĩnh lặng, nơi vị giác giao hòa cùng triết lý vạn vật vô thường.</p>
        <div class="hero-actions">
          <a href="Omakase_Menu.php" class="btn btn-primary">Xem Thực Đơn &rarr;</a>
          <a href="hoso_order_thanhtoan.php?view=booking" class="btn btn-outline">Đặt Bàn Ngay</a>
        </div>
      </div>

      <!-- Ô tìm chỗ trống -->
      <div class="booking-widget">
        <div class="field">
          <label>Gói Omakase</label>
          <select id="slotSet">
            <option value="">Tất cả set Omakase</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['ten_loai']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Ngày dự kiến</label>
          <input type="date" id="slotDate" />
        </div>
        <div class="field">
          <label>Số lượng khách</label>
          <select id="slotGuests">
            <option value="2">2 Khách (Trải nghiệm đôi)</option>
            <option value="4">4 Khách</option>
            <option value="6">6 Khách</option>
          </select>
        </div>
        <a href="hoso_order_thanhtoan.php?view=booking" id="findSlot" class="btn btn-primary">&#128269; Tìm Chỗ Trống</a>
      </div>
    </div>
  </section>

  <!-- ============ HÀNH TRÌNH VỊ GIÁC ============ -->
  <section class="section">
    <div class="container">
      <div class="section-head">
        <div>
          <p class="eyebrow">HƯƠNG VỊ BỐN MÙA</p>
          <h2>Hành Trình Vị Giác</h2>
        </div>
        <p>Mỗi khía cạnh trong thực đơn Omakase là một nốt nhạc tinh tế hòa điệu giữa kỹ nghệ chế tác và tôn vinh nguồn dưỡng chất tự nhiên.</p>
      </div>

      <div class="category-grid">
        <a href="Omakase_Menu.php?loai_id=2">
          <img src="images/exquisite-japanese-omakase-platter-with-delicately-arranged-sashimi_167857-53957.jpg"
            alt="Nigiri Omakase" />
          <h3>Nigiri Omakase</h3>
          <span>18 Món kinh điển</span>
        </a>
        <a href="Omakase_Menu.php?loai_id=2">
          <img src="images/sashimi.jpg" alt="Sashimi Cao Cấp" />
          <h3>Sashimi Cao Cấp</h3>
          <span>12 Loại cá tươi</span>
        </a>
        <a href="Omakase_Menu.php?loai_id=3">
          <img src="images/kaiseki.jpg" alt="Kaiseki Truyền Thống" />
          <h3>Kaiseki Truyền Thống</h3>
          <span>8 Món yến tiệc</span>
        </a>
        <a href="Omakase_Menu.php?loai_id=4">
          <img src="images/sake.jpg" alt="Sake Tuyển Chọn" />
          <h3>Sake Tuyển Chọn</h3>
          <span>24 Dòng rượu quý</span>
        </a>
        <a href="Omakase_Menu.php?loai_id=1">
          <img src="images/wagashi.jpg" alt="Tráng Miệng Wagashi" />
          <h3>Tráng Miệng Wagashi</h3>
          <span>6 Vị thanh tao</span>
        </a>
      </div>
    </div>
  </section>

  <!-- ============ SET OMAKASE ĐẶC TRƯNG ============ -->
  <section class="section" style="padding-top:0;">
    <div class="container">
      <div class="section-head">
        <div>
          <p class="eyebrow">THỰC ĐƠN ĐỘC BẢN</p>
          <h2>Set Omakase Đặc Trưng</h2>
        </div>
        <a href="Omakase_Menu.php">Xem Tất Cả Thực Đơn &#8599;</a>
      </div>

      <div class="card-grid">
        <?php foreach ($featured_sets as $f_item): 
            $f_img = !empty($f_item['hinh_anh']) ? $f_item['hinh_anh'] : 'images/kiwami.jpg';
            $f_tag = !empty($f_item['nhan']) ? $f_item['nhan'] : "CHEF'S SIGNATURE";
        ?>
          <article class="dish-card">
            <div class="thumb">
              <img src="<?= htmlspecialchars($f_img) ?>" alt="<?= htmlspecialchars($f_item['ten_set']) ?>" />
              <span class="tag"><?= htmlspecialchars($f_tag) ?></span>
            </div>
            <div class="body">
              <p class="rating"><strong><?= number_format($f_item['diem_tb'] > 0 ? $f_item['diem_tb'] : 5.0, 1) ?></strong> (<?= $f_item['so_danh_gia'] ?> đánh giá)</p>
              <h3><?= htmlspecialchars($f_item['ten_set']) ?></h3>
              <p><?= htmlspecialchars($f_item['mo_ta_ngan'] ?? 'Đỉnh cao nghệ thuật Omakase với Bào ngư hấp rượu Sake, Otoro...') ?></p>
              <div class="price-row">
                <span class="price"><?= number_format($f_item['gia'], 0, ',', '.') ?>đ<small>Giá mỗi khách</small></span>
                <a href="Omakase_Detail.php?id=<?= $f_item['id'] ?>" class="btn btn-outline" style="padding:8px 14px;font-size:12px;">Xem Chi Tiết</a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ============ TRIẾT LÝ ============ -->
  <section class="section" id="triet-ly">
    <div class="container philosophy">
      <div class="portrait">
        <img src="images/chef.jpg" alt="Chef Daiki Takahashi" style="width:100%;height:100%;object-fit:cover;" />
        <div class="chef-tag">
          &#26408; Chef Daiki Takahashi
          <small>25 Năm Kinh Nghiệm Ginza, Tokyo</small>
        </div>
      </div>
      <div>
        <p class="eyebrow">TRIẾT LÝ WABI-SABI · 侘寂</p>
        <h2>Triết Lý Sabi Omakase — Gửi Trọn Niềm Tin Vào Đôi Tay Nghệ Nhân</h2>
        <p>Trong tiếng Nhật, "Omakase" mang ý nghĩa giao phó hoàn toàn trải nghiệm ẩm thực cho người đầu bếp. Tại Sabi, chúng tôi không đơn thuần dọn lên một bữa tiệc, mà là một cuộc hành trình đối thoại không lời giữa vị giác của thực khách và tâm hồn người chế tác, thấu cảm sự hoàn hảo trong nét dung dị nguyên bản.</p>
        <div class="pillars">
          <div>
            <p class="num">01.</p>
            <h4>Hải Sản Toyosu</h4>
            <p>Được tuyển chọn trực tiếp tại chợ đầu mối Toyosu (Tokyo) mỗi sớm và chuyển bằng đường hàng không trong ngày.</p>
          </div>
          <div>
            <p class="num">02.</p>
            <h4>Giấm Đỏ Akazu</h4>
            <p>Gạo Koshihikari phối trộn giấm đỏ ủ từ hèm rượu sake thượng hạng suốt 3 năm, nâng tầm vị ngọt béo của từng lát cá.</p>
          </div>
          <div>
            <p class="num">03.</p>
            <h4>12 Chỗ Ngồi Riêng Tư</h4>
            <p>Không gian giới hạn tối đa 12 vị khách để bếp trưởng có thể chăm chút từng chi tiết nhỏ nhất trong suốt 120 phút phục vụ.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ BANNER ƯU ĐÃI ============ -->
  <section class="section" style="padding-top:0;">
    <div class="container">
      <div class="promo-banner">
        <div>
          <p class="eyebrow">ĐẶC QUYỀN HỘI VIÊN · 予約特典</p>
          <h2>Ưu Đãi Đặt Bàn Trước 7 Ngày — Tặng Kèm 01 Bình Sake Junmai Daiginjo Thượng Hạng</h2>
          <p>Dành riêng cho thực khách đặt chỗ sớm cho nhóm từ 2 khách trở lên tại tất cả các cơ sở trực thuộc Sabi Omakase.</p>
        </div>
        <a href="hoso_order_thanhtoan.php?view=booking" class="btn btn-primary" style="white-space:nowrap;">Nhận Ưu Đãi Ngay</a>
      </div>
    </div>
  </section>

  <!-- ============ KHÔNG GIAN TRẢI NGHIỆM ============ -->
  <section class="section" id="chi-nhanh">
    <div class="container">
      <div class="section-head">
        <div>
          <p class="eyebrow">HỆ THỐNG NHÀ HÀNG</p>
          <h2>Không Gian Trải Nghiệm</h2>
        </div>
        <p>Mỗi không gian được kiến tạo theo tinh thần kiến trúc Sukiya-zukuri thanh tịnh, tôn trọng sự riêng tư và vẻ đẹp của vật liệu tự nhiên.</p>
      </div>

      <div class="location-grid">
        <article class="location-card">
          <div class="thumb">
            <img src="images/quan1.jpg" alt="Chi nhánh Quận 1" style="width:100%;height:100%;object-fit:cover;" />
            <span class="tag">QUẬN 1 · TRUNG TÂM</span>
          </div>
          <div class="body">
            <h3>Sabi Omakase — Chi Nhánh Quận 1</h3>
            <p class="meta">&#128205; 12 đường số 3, Phường 4, Thành phố 6</p>
            <p class="meta">&#128222; +123456789</p>
            <p class="meta">&#128340; Trưa: 11:30 - 14:00 | Tối: 17:30 - 22:30</p>
            <div class="actions">
              <a href="hoso_order_thanhtoan.php?view=booking" class="btn btn-primary">Đặt Bàn Tại Đây</a>
              <a href="https://www.google.com/maps/search/?api=1&query=12+%C4%91%C6%B0%E1%BB%9Dng+s%E1%BB%91+3+Ph%C6%B0%E1%BB%9Dng+4+Qu%E1%BA%ADn+5"
                target="_blank" rel="noopener" class="btn btn-outline icon-square">&#128506;</a>
            </div>
          </div>
        </article>

        <article class="location-card">
          <div class="thumb">
            <img src="images/quan5.jpg" alt="Chi nhánh Quận 5" style="width:100%;height:100%;object-fit:cover;" />
            <span class="tag">QUẬN 5 · FLAGSHIP</span>
          </div>
          <div class="body">
            <h3>Sabi Omakase — Chi Nhánh Quận 5</h3>
            <p class="meta">&#128205; 12 đường số 3, Phường 4, Thành phố 6</p>
            <p class="meta">&#128222; +123456789</p>
            <p class="meta">&#128340; Tối: 17:30 - 23:00 (Chỉ phục vụ 2 tối)</p>
            <div class="actions">
              <a href="hoso_order_thanhtoan.php?view=booking" class="btn btn-primary">Đặt Bàn Tại Đây</a>
              <a href="https://www.google.com/maps/search/?api=1&query=12+%C4%91%C6%B0%E1%BB%9Dng+s%E1%BB%91+3+Ph%C6%B0%E1%BB%9Dng+4+Qu%E1%BA%ADn+5"
                target="_blank" rel="noopener" class="btn btn-outline icon-square">&#128506;</a>
            </div>
          </div>
        </article>

        <article class="location-card">
          <div class="thumb">
            <img src="images/tayho.jpg" alt="Chi nhánh Tây Hồ" style="width:100%;height:100%;object-fit:cover;" />
            <span class="tag">TÂY HỒ · VEN HỒ</span>
          </div>
          <div class="body">
            <h3>Sabi Omakase — Chi Nhánh Tây Hồ</h3>
            <p class="meta">&#128205; 12 đường số 3, Phường 4, Thành phố 6</p>
            <p class="meta">&#128222; +123456789</p>
            <p class="meta">&#128340; Trưa: 11:30 - 14:00 | Tối: 17:30 - 22:30</p>
            <div class="actions">
              <a href="hoso_order_thanhtoan.php?view=booking" class="btn btn-primary">Đặt Bàn Tại Đây</a>
              <a href="https://www.google.com/maps/search/?api=1&query=12+%C4%91%C6%B0%E1%BB%9Dng+s%E1%BB%91+3+Ph%C6%B0%E1%BB%9Dng+4+Qu%E1%BA%ADn+5"
                target="_blank" rel="noopener" class="btn btn-outline icon-square">&#128506;</a>
            </div>
          </div>
        </article>
      </div>
    </div>
  </section>

  <!-- ============ QUOTE ============ -->
  <section class="quote-section">
    <div class="quote-mark">&#10077;</div>
    <blockquote>"Omakase không chỉ là bữa ăn, đó là nghi thức tôn kính cái đẹp mong manh của thời khắc."</blockquote>
    <cite>— Đầu bếp Daiki Takahashi</cite>
    <a href="hoso_order_thanhtoan.php?view=booking" class="btn btn-primary">Khởi Đầu Trải Nghiệm Của Quý Khách</a>
  </section>

  <!-- ============ FOOTER ============ -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div>
          <div class="footer-brand">
            <span class="brand-mark">祥</span>
            <span class="footer-brand-name">SABI OMAKASE</span>
          </div>
          <p class="footer-about">Tận hưởng triết lý Wabi Sabi qua từng thớ cá tinh khiết. Nơi nghệ thuật ẩm thực giao hòa giữa sự tối giản thuần túy và nghi thức ẩm thực thượng lưu Nhật Bản.</p>
          <p class="footer-about">TOKYO &middot; KYOTO &middot; SAIGON</p>
        </div>
        <div>
          <h4>Điều hướng</h4>
          <ul>
            <li><a href="index.php">Trang chủ</a></li>
            <li><a href="Omakase_Menu.php">Thực đơn Omakase</a></li>
            <li><a href="hoso_order_thanhtoan.php?view=cart">Đặt chỗ &amp; Giỏ hàng</a></li>
            <li><a href="index.php#triet-ly">Về chúng tôi</a></li>
            <li><a href="#chi-nhanh">Liên hệ</a></li>
          </ul>
        </div>
        <div>
          <h4>Liên hệ</h4>
          <ul>
            <li>&#128222; +123456789</li>
            <li>&#9993; reservation@sabiomakase.com</li>
            <li>&#128205; 12 đường số 3, Phường 4, Thành phố 6</li>
          </ul>
        </div>
        <div>
          <h4>Giờ mở cửa</h4>
          <ul>
            <li>Thứ 2 - Thứ 6: 17:30 - 22:30</li>
            <li>Thứ 7 - CN: 11:30 - 14:00 / 17:30 - 23:00</li>
          </ul>
          <a href="hoso_order_thanhtoan.php?view=booking" class="btn btn-outline"
            style="margin-top:12px;font-size:12px;padding:8px 14px;">Chỉ Nhận Phục Vụ Đặt Trước</a>
        </div>
      </div>
      <div class="footer-bottom">
        <span>&copy; 2024 Sabi Omakase. Bản quyền thuộc về nhà hàng.</span>
        <span>Cam kết nguồn nguyên liệu tươi nhập trực tiếp từ chợ Toyosu mỗi sáng.</span>
      </div>
    </div>
  </footer>

  <script>
    const d = document.getElementById('slotDate');
    if (d) {
      d.value = new Date().toISOString().slice(0, 10); d.min = d.value;
    }
    document.getElementById('findSlot')?.addEventListener('click', function (e) {
      e.preventDefault();
      const q = parseInt(document.getElementById('slotGuests').value) || 2;
      const s = document.getElementById('slotSet').value;
      let url = 'hoso_order_thanhtoan.php?view=booking&date=' + (d ? d.value : '') + '&qty=' + q;
      if (s) url += '&cat_id=' + s;
      location.href = url;
    });
  </script>
</body>

</html>
