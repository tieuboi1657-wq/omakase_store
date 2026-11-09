 <?php
// Bật session và kết nối cơ sở dữ liệu
require_once __DIR__ . '/config/db.php';

$msg = '';
$msg_type = '';

// ==============================================================================
// 1. XỬ LÝ GỬI ĐÁNH GIÁ HOẶC KHIẾU NẠI (POST REQUEST)
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loai     = $_POST['loai'] ?? 'Đánh giá'; // 'Đánh giá' hoặc 'Khiếu nại'
    $set_id   = (int)($_POST['set_id'] ?? 0);
    $so_sao   = isset($_POST['so_sao']) ? (int)$_POST['so_sao'] : 5;
    $noi_dung = trim($_POST['noi_dung'] ?? '');
    $ho_ten   = trim($_POST['ho_ten'] ?? '');
    $sdt      = trim($_POST['sdt'] ?? '');

    if (empty($set_id) || empty($noi_dung)) {
        $msg = 'Vui lòng chọn Set Omakase và nhập nội dung chia sẻ.';
        $msg_type = 'warning';
    } else {
        // Tìm hoặc tạo khách hàng nếu có họ tên / SĐT
        $kh_id = null;
        if (!empty($sdt)) {
            $stmt_kh = $pdo->prepare("SELECT id FROM khach_hang WHERE so_dien_thoai = ?");
            $stmt_kh->execute([$sdt]);
            $kh_id = $stmt_kh->fetchColumn();

            if (!$kh_id) {
                $stmt_new_kh = $pdo->prepare("
                    INSERT INTO khach_hang (ho_ten, so_dien_thoai, hang_thanh_vien, diem_tich_luy, ngay_tao)
                    VALUES (?, ?, 'Thành viên', 100, NOW())
                ");
                $stmt_new_kh->execute([$ho_ten ?: 'Thượng khách', $sdt]);
                $kh_id = $pdo->lastInsertId();
            }
        }

        // Nếu chưa đăng nhập và không nhập SĐT, gán mặc định khách hàng đầu tiên
        if (!$kh_id) {
            $kh_id = $pdo->query("SELECT id FROM khach_hang LIMIT 1")->fetchColumn() ?: 1;
        }

        // Đánh giá mặc định hiển thị (1), khiếu nại mặc định ẩn chờ admin xử lý (0)
        $hien_thi = ($loai === 'Khiếu nại') ? 0 : 1;

        $stmt_ins = $pdo->prepare("
            INSERT INTO danh_gia (khach_hang_id, set_id, so_sao, noi_dung, loai, hien_thi, ngay_danh_gia)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt_ins->execute([$kh_id, $set_id, $so_sao, $noi_dung, $loai, $hien_thi]);

        if ($loai === 'Khiếu nại') {
            $msg = 'Sabi Omakase đã tiếp nhận khiếu nại của quý khách. Ban Quản Trị và Bếp Trưởng Daiki sẽ liên hệ trực tiếp trong thời gian sớm nhất!';
            $msg_type = 'warning';
        } else {
            $msg = 'Cảm ơn quý khách đã gửi đánh giá trải nghiệm! Lời sẻ chia của quý khách là niềm vinh hạnh to lớn của nhà hàng Sabi.';
            $msg_type = 'success';
        }
    }
}

// ==============================================================================
// 2. LẤY TẤT CẢ CÁC SET OMAKASE TỪ DATABASE
// ==============================================================================
$all_sets = $pdo->query("
    SELECT id, ma_set, ten_set, ten_tieng_nhat, gia, so_mon, thoi_luong_phut, hinh_anh, mo_ta_ngan 
    FROM set_omakase 
    ORDER BY id ASC
")->fetchAll();

$default_set = !empty($all_sets) ? $all_sets[0] : [
    'id' => 1, 'ten_set' => 'Set Kiwami Grand Omakase', 'ten_tieng_nhat' => '極上 おまかせ',
    'gia' => 3800000, 'so_mon' => 18, 'thoi_luong_phut' => 120, 'hinh_anh' => 'images/kiwami.jpg'
];

// ==============================================================================
// 3. LẤY LỊCH SỬ ĐÁNH GIÁ MẪU CHO CỘT BÊN PHẢI
// ==============================================================================
$recent_reviews = $pdo->query("
    SELECT d.*, s.ten_set, s.ten_tieng_nhat 
    FROM danh_gia d 
    JOIN set_omakase s ON d.set_id = s.id 
    WHERE d.hien_thi = 1 
    ORDER BY d.id DESC 
    LIMIT 3
")->fetchAll();

$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 2;
?>
<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Đánh Giá &amp; Phản Hồi Trải Nghiệm - Sabi Omakase</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&family=Noto+Serif:wght@400;500;600;700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="../css/danhgia.css" />

    <style>
      /* Modal chọn Set Omakase */
      .set-modal-overlay {
        position: fixed; inset: 0; background: rgba(0,0,0,0.8);
        display: none; align-items: center; justify-content: center;
        z-index: 9999; padding: 20px;
      }
      .set-modal-overlay.open { display: flex; }
      .set-modal-box {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: 12px; width: 100%; max-width: 680px;
        max-height: 85vh; display: flex; flex-direction: column;
        box-shadow: 0 10px 40px rgba(0,0,0,0.6);
      }
      .set-modal-header {
        padding: 18px 22px; border-bottom: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center;
      }
      .set-modal-header h3 {
        font-family: var(--font-serif); font-size: 18px; color: var(--text);
      }
      .set-modal-close {
        background: transparent; border: none; color: var(--text-muted);
        font-size: 24px; cursor: pointer; line-height: 1;
      }
      .set-modal-close:hover { color: var(--text); }
      .set-modal-body {
        padding: 16px 22px; overflow-y: auto; flex: 1; display: grid; gap: 12px;
      }
      .set-option-card {
        background: var(--bg); border: 1px solid var(--border);
        border-radius: 8px; padding: 12px 16px; display: flex;
        align-items: center; justify-content: space-between; gap: 14px;
        cursor: pointer; transition: all 0.2s ease;
      }
      .set-option-card:hover {
        border-color: var(--primary); background: rgba(255,255,255,0.03);
        transform: translateY(-1px);
      }
      .set-option-card.active {
        border-color: var(--primary);
        box-shadow: 0 0 0 1px var(--primary);
      }
      .set-option-left {
        display: flex; align-items: center; gap: 14px;
      }
      .set-option-img {
        width: 50px; height: 50px; border-radius: 6px; object-fit: cover;
        border: 1px solid var(--border); background: #000; flex-shrink: 0;
      }
      .set-option-title { font-weight: 600; font-size: 14px; color: var(--text); }
      .set-option-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
      .set-option-price {
        font-weight: 700; color: var(--primary); font-size: 14px; white-space: nowrap;
      }
    </style>
  </head>
  <body>
    <!-- Top Navigation Bar -->
    <header class="site-header">
      <div class="header-container">
        <a class="brand" href="index.php">
          <div class="brand-logo">寂</div>
          <div class="brand-text">
            <h2>SABI OMAKASE</h2>
            <span>寂 侘 鮨 処</span>
          </div>
        </a>

        <nav class="nav-links">
          <a href="index.php">Trang chủ</a>
          <a href="Omakase_Menu.php">Thực đơn</a>
          <a href="hoso_order_thanhtoan.php?view=booking">Đặt chỗ</a>
          <a href="index.php#triet-ly">Về chúng tôi</a>
          <a href="danhgia.php" class="active">Đánh giá &amp; Phản hồi</a>
        </nav>

        <div class="header-actions">
          <button class="icon-btn" title="Giỏ hàng" onclick="location.href='hoso_order_thanhtoan.php?view=cart'">
            <span class="material-symbols-outlined">shopping_bag</span>
            <span class="badge"><?= $cart_count ?></span>
          </button>
        </div>
      </div>
    </header>

    <!-- Main Content Container -->
    <main class="main-wrapper">
      <!-- Breadcrumb Bar -->
      <div class="breadcrumb-wrap">
        <nav aria-label="Đường dẫn" class="breadcrumb">
          <a href="index.php">Trang chủ</a>
          <span class="sep">/</span>
          <span class="current">Đánh giá &amp; Phản hồi trải nghiệm</span>
        </nav>
        <div class="indicator-badge">
          <span class="pulse-dot"></span>
          <span>Mật thất tiếp nhận thượng lưu</span>
        </div>
      </div>

      <!-- Hero Header Banner -->
      <section class="page-banner">
        <div class="banner-content">
          <div class="eyebrow">
            <span class="material-symbols-outlined">auto_stories</span>
            <span>Thỉnh ý ẩm khách • Ichigo Ichie</span>
          </div>
          <h1>Đánh Giá &amp; Phản Hồi Trải Nghiệm</h1>
          <p>
            Lắng nghe cảm thụ vị giác và hoàn thiện nghi thức ẩm thực Omakase từ thượng khách. Mỗi lời sẻ chia là một nét vẽ hoàn mỹ cho hành trình kiếm tìm sự thanh tịnh vô bờ.
          </p>
        </div>
        <div class="banner-seal">侘寂</div>
      </section>

      <?php if (!empty($msg)): ?>
        <div style="max-width: 1280px; margin: 0 auto 20px; padding: 14px 20px; border-radius: 8px; background: <?= $msg_type === 'success' ? 'rgba(62,142,65,0.25)' : 'rgba(212,175,55,0.25)' ?>; border: 1px solid <?= $msg_type === 'success' ? '#3e8e41' : '#d4af37' ?>; color: #fff; font-size: 14px;">
          <strong><?= $msg_type === 'success' ? '✓ Thành công:' : '⚠ Lưu ý:' ?></strong> <?= htmlspecialchars($msg) ?>
        </div>
      <?php endif; ?>

      <!-- Navigation Tabs -->
      <div class="tab-navigation" role="tablist">
        <button
          id="tab-btn-review"
          class="tab-btn active"
          role="tab"
          aria-selected="true"
          onclick="switchTab('review')"
        >
          <span class="material-symbols-outlined">rate_review</span>
          <span>Gửi Đánh Giá Buổi Tiệc</span>
        </button>
        <button
          id="tab-btn-report"
          class="tab-btn"
          role="tab"
          aria-selected="false"
          onclick="switchTab('report')"
        >
          <span class="material-symbols-outlined">shield</span>
          <span>Báo Cáo Sự Cố &amp; Khiếu Nại Dịch Vụ</span>
        </button>
      </div>

      <!-- Main Layout Grid -->
      <div class="content-grid">
        <!-- Left Column: Forms -->
        <div class="content-main">

          <!-- ============================================================ -->
          <!-- TAB 1: FORM ĐÁNH GIÁ TRẢI NGHIỆM                            -->
          <!-- ============================================================ -->
          <div id="panel-review" class="tab-panel" role="tabpanel">
            <form method="POST" action="danhgia.php">
              <input type="hidden" name="loai" value="Đánh giá">
              <input type="hidden" name="so_sao" id="input_so_sao" value="5">
              <input type="hidden" name="set_id" id="input_selected_set_id" value="<?= $default_set['id'] ?>">

              <!-- Session Card (Chuẩn 100% nhóm) -->
              <article class="card">
                <div class="session-header">
                  <span class="card-eyebrow">
                    <span class="material-symbols-outlined" style="font-size: 15px; vertical-align: text-bottom;">history_edu</span>
                    Bữa tiệc ẩm thực của quý khách
                  </span>
                  <span class="status-tag">Đã hoàn tất trải nghiệm</span>
                </div>
                <div class="session-detail-box">
                  <div class="session-info">
                    <div class="session-title-wrap">
                      <span class="session-name" id="display-set-name"><?= htmlspecialchars($default_set['ten_set']) ?></span>
                      <span class="session-guest-badge" id="display-set-price"><?= number_format($default_set['gia'], 0, ',', '.') ?> ₫</span>
                    </div>
                    <div class="session-meta">
                      <span>Mã tiệc: <strong style="color: var(--text);">#SAB-<?= 9800 + $default_set['id'] ?></strong></span>
                      <span>•</span>
                      <span id="display-set-courses"><?= $default_set['so_mon'] ?? 18 ?> món</span>
                      <span>•</span>
                      <span id="display-set-duration"><?= $default_set['thoi_luong_phut'] ?? 120 ?> phút</span>
                      <span>•</span>
                      <span>Quầy Bar Gỗ Hinoki</span>
                      <span>•</span>
                      <span>Chi nhánh Quận 1</span>
                    </div>
                  </div>
                  <button class="btn-change-session" type="button" onclick="openSetModal()">
                    <span>Đổi tiệc khác</span>
                    <span class="material-symbols-outlined">sync_alt</span>
                  </button>
                </div>

                <!-- Thông tin khách hàng -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;">
                  <div>
                    <label class="form-label" style="font-size: 12px; text-transform: uppercase; color: var(--outline); display: block; margin-bottom: 6px;">Họ và Tên *</label>
                    <input type="text" name="ho_ten" class="custom-input" style="width: 100%; padding: 10px 12px; background: var(--surface-2); border: 1px solid var(--border); border-radius: 6px; color: #fff;" placeholder="Nhập họ tên quý khách" value="<?= htmlspecialchars($_SESSION['user']['ho_ten'] ?? '') ?>" required>
                  </div>
                  <div>
                    <label class="form-label" style="font-size: 12px; text-transform: uppercase; color: var(--outline); display: block; margin-bottom: 6px;">Số điện thoại *</label>
                    <input type="tel" name="sdt" class="custom-input" style="width: 100%; padding: 10px 12px; background: var(--surface-2); border: 1px solid var(--border); border-radius: 6px; color: #fff;" placeholder="Nhập số điện thoại" value="<?= htmlspecialchars($_SESSION['user']['so_dien_thoai'] ?? '') ?>" required>
                  </div>
                </div>
              </article>

              <!-- Overall Rating -->
              <article class="card overall-rating-card">
                <div class="card-title-group">
                  <span class="card-eyebrow">Thang điểm tổng quan</span>
                  <h2 class="card-title">Mức độ đắc ý cho buổi dạ tiệc</h2>
                </div>
                <div id="star-rating-container" class="star-rating-box">
                  <button class="star-btn" type="button" aria-label="1 sao" onclick="setStar(1)">
                    <span class="material-symbols-outlined">star</span>
                  </button>
                  <button class="star-btn" type="button" aria-label="2 sao" onclick="setStar(2)">
                    <span class="material-symbols-outlined">star</span>
                  </button>
                  <button class="star-btn" type="button" aria-label="3 sao" onclick="setStar(3)">
                    <span class="material-symbols-outlined">star</span>
                  </button>
                  <button class="star-btn" type="button" aria-label="4 sao" onclick="setStar(4)">
                    <span class="material-symbols-outlined">star</span>
                  </button>
                  <button class="star-btn" type="button" aria-label="5 sao" onclick="setStar(5)">
                    <span class="material-symbols-outlined">star</span>
                  </button>
                </div>
                <p id="star-desc" class="star-desc">Tuyệt hảo • Vượt trên cả kỳ vọng của nghệ thuật ẩm thực</p>
              </article>

              <!-- 4 Core Pillars -->
              <article class="card">
                <div class="card-title-group">
                  <span class="card-eyebrow">Nghi thức đánh giá 4 trụ cột</span>
                  <h3 class="card-title">Độ hài lòng theo từng tinh hoa văn hóa</h3>
                </div>
                <div class="pillar-list">
                  <!-- Pillar 1 -->
                  <div class="pillar-item">
                    <div class="pillar-top">
                      <div class="pillar-title">
                        <span class="material-symbols-outlined">set_meal</span>
                        <span>Độ tươi ngon &amp; Tinh khiết nguyên liệu hải sản Toyosu</span>
                      </div>
                      <span id="score-p1" class="pillar-score">10 / 10</span>
                    </div>
                    <input
                      class="pillar-range"
                      type="range"
                      min="1"
                      max="10"
                      value="10"
                      oninput="document.getElementById('score-p1').innerText = this.value + ' / 10'"
                    />
                    <div class="pillar-labels">
                      <span>Chưa trọn vẹn</span>
                      <span>Tuyệt hảo, cá béo mềm tinh tế</span>
                    </div>
                  </div>

                  <!-- Pillar 2 -->
                  <div class="pillar-item">
                    <div class="pillar-top">
                      <div class="pillar-title">
                        <span class="material-symbols-outlined">restaurant</span>
                        <span>Tay nghề &amp; Nghệ thuật dao kiếm của Bếp trưởng Itamae</span>
                      </div>
                      <span id="score-p2" class="pillar-score">10 / 10</span>
                    </div>
                    <input
                      class="pillar-range"
                      type="range"
                      min="1"
                      max="10"
                      value="10"
                      oninput="document.getElementById('score-p2').innerText = this.value + ' / 10'"
                    />
                    <div class="pillar-labels">
                      <span>Cần tiết chế</span>
                      <span>Đường dao điêu luyện, cơm Akazu ấm chuẩn mực</span>
                    </div>
                  </div>

                  <!-- Pillar 3 -->
                  <div class="pillar-item">
                    <div class="pillar-top">
                      <div class="pillar-title">
                        <span class="material-symbols-outlined">wine_bar</span>
                        <span>Nghệ thuật phục vụ &amp; Am hiểu rượu Sake của Sommelier</span>
                      </div>
                      <span id="score-p3" class="pillar-score">9 / 10</span>
                    </div>
                    <input
                      class="pillar-range"
                      type="range"
                      min="1"
                      max="10"
                      value="9"
                      oninput="document.getElementById('score-p3').innerText = this.value + ' / 10'"
                    />
                    <div class="pillar-labels">
                      <span>Thiếu tương tác</span>
                      <span>Kết hợp rượu Junmai Daiginjo xuất thần</span>
                    </div>
                  </div>

                  <!-- Pillar 4 -->
                  <div class="pillar-item">
                    <div class="pillar-top">
                      <div class="pillar-title">
                        <span class="material-symbols-outlined">spa</span>
                        <span>Không gian, âm nhạc Thiền &amp; Văn hóa đón tiếp Omotenashi</span>
                      </div>
                      <span id="score-p4" class="pillar-score">10 / 10</span>
                    </div>
                    <input
                      class="pillar-range"
                      type="range"
                      min="1"
                      max="10"
                      value="10"
                      oninput="document.getElementById('score-p4').innerText = this.value + ' / 10'"
                    />
                    <div class="pillar-labels">
                      <span>Cần cải thiện</span>
                      <span>Tĩnh tại, thanh khiết vô bờ bến</span>
                    </div>
                  </div>
                </div>
              </article>

              <!-- Commentary -->
              <article class="card">
                <div class="form-group">
                  <div class="form-label-row">
                    <label class="form-label" for="review-content">Cảm thụ chi tiết về món ăn và dịch vụ *</label>
                  </div>
                  <textarea
                    id="review-content"
                    name="noi_dung"
                    class="custom-textarea"
                    rows="5"
                    placeholder="Chia sẻ cảm xúc của bạn về từng món sushi, miếng cơm giấm Akazu hay khoảnh khắc đáng nhớ nhất cùng nghệ nhân..."
                    required
                  ></textarea>
                </div>
              </article>

              <!-- Submit Row -->
              <div class="submit-row">
                <div class="reward-note">
                  <span class="material-symbols-outlined">stars</span>
                  <span>Tặng ngay <strong>100 Điểm Thưởng Ẩm Thực (Sabi Miles)</strong> vào thẻ hội viên VIP.</span>
                </div>
                <button class="primary-btn" type="submit">
                  <span>Gửi Đánh Giá Trải Nghiệm</span>
                  <span class="material-symbols-outlined">send</span>
                </button>
              </div>
            </form>
          </div>

          <!-- ============================================================ -->
          <!-- TAB 2: FORM BÁO CÁO SỰ CỐ & KHIẾU NẠI                       -->
          <!-- ============================================================ -->
          <div id="panel-report" class="tab-panel hidden" role="tabpanel">
            <article class="card">
              <div class="complaint-hero">
                <div class="complaint-icon-badge">
                  <span class="material-symbols-outlined">support_agent</span>
                </div>
                <div class="complaint-intro">
                  <h3>Kênh Phản Ánh &amp; Khiếu Nại Dịch Vụ Cấp Cao</h3>
                  <p>
                    Tại Sabi Omakase, danh dự của nghệ nhân gắn liền với sự hài lòng tột bực của quý khách. Mọi sự bất tiện đều được đích thân Ban Quản Trị và Bếp Trưởng kiểm tra và phản hồi trong thời gian sớm nhất.
                  </p>
                </div>
              </div>

              <form method="POST" action="danhgia.php" class="complaint-form">
                <input type="hidden" name="loai" value="Khiếu nại">
                <input type="hidden" name="so_sao" value="1">

                <div class="form-row-2">
                  <div class="form-group">
                    <label class="form-label" style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--outline);">
                      Họ tên quý khách *
                    </label>
                    <input class="custom-input" type="text" name="ho_ten" placeholder="Họ và tên" value="<?= htmlspecialchars($_SESSION['user']['ho_ten'] ?? '') ?>" required />
                  </div>
                  <div class="form-group">
                    <label class="form-label" style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--outline);">
                      Số điện thoại liên hệ *
                    </label>
                    <input class="custom-input" type="tel" name="sdt" placeholder="Số điện thoại" value="<?= htmlspecialchars($_SESSION['user']['so_dien_thoai'] ?? '') ?>" required />
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label" style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--outline);">
                    Set Omakase liên quan *
                  </label>
                  <select name="set_id" class="custom-select" style="width: 100%; padding: 12px; background: var(--surface-2); border: 1px solid var(--border); border-radius: 6px; color: #fff;" required>
                    <option value="">-- Chọn Set Omakase gặp vấn đề --</option>
                    <?php foreach ($all_sets as $s): ?>
                      <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['ten_set']) ?> (<?= number_format($s['gia'], 0, ',', '.') ?> ₫)</option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-label" style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--outline);">
                    Chi tiết sự cố xảy ra *
                  </label>
                  <textarea
                    name="noi_dung"
                    class="custom-textarea"
                    rows="5"
                    placeholder="Xin quý khách hãy miêu tả cụ thể khoảnh khắc chưa trọn vẹn, thời điểm diễn ra và bất kỳ mong muốn giải quyết nào..."
                    required
                  ></textarea>
                </div>

                <div class="submit-row" style="margin-top: 10px;">
                  <div style="font-size: 12px; color: var(--outline);">
                    <span>Mã bảo mật: SAB-SECURE-893 • Kênh bảo mật khách hàng</span>
                  </div>
                  <button class="danger-btn" type="submit">
                    <span>Gửi Khiếu Nại Đến Ban Quản Trị</span>
                  </button>
                </div>
              </form>
            </article>
          </div>
        </div>

        <!-- Right Column: Sidebar & Widgets -->
        <aside class="content-sidebar">
          <!-- Chef Card -->
          <article class="card">
            <div class="chef-profile-top">
              <div class="chef-avatar">
                <img
                  src="https://vcdn1-dulich.vnecdn.net/2024/10/30/Oma10-5672-1730266272.jpg?w=1020&h=0&q=100&dpr=2&fit=crop&s=Dh9UMKZ6YtTGRauklQKjgA"
                  alt="Bếp trưởng Daiki Takahashi"
                />
              </div>
              <div class="chef-info">
                <h4>Bếp trưởng Daiki Takahashi</h4>
                <span>Nghệ nhân 24 năm kinh nghiệm</span>
              </div>
            </div>
            <p class="chef-quote">
              “Ẩm thực Wabi-Sabi tôn vinh sự chân thật tuyệt đối. Mọi lời góp ý của bạn là tấm gương soi sáng tay dao của chúng tôi để mỗi hạt cơm, thớ cá ngày mai lại thuần khiết hơn hôm nay.”
            </p>
            <div class="chef-card-footer">
              <span>SABI OMAKASE • KYOTO &amp; SAIGON</span>
              <span class="chef-kanji">高橋 大樹</span>
            </div>
          </article>

          <!-- VIP Fast-lane -->
          <article class="card">
            <div class="concierge-header">
              <span class="material-symbols-outlined">room_service</span>
              <h4>Quản Gia Riêng VIP Concierge</h4>
            </div>
            <p class="concierge-desc">
              Dành riêng cho khách hàng hạng Platinum và Diamond cần trợ giúp tức thời hoặc khiếu nại nóng trong quá trình diễn ra tiệc:
            </p>
            <a class="hotline-link" href="tel:0901234567">
              <div class="hotline-left">
                <span class="material-symbols-outlined">call</span>
                <div>
                  <span class="hotline-number">090 123 4567</span>
                  <span class="hotline-sub">Phục vụ 24/7 • Hotline Trưởng Bộ Phận</span>
                </div>
              </div>
              <span class="material-symbols-outlined" style="color: var(--outline);">arrow_forward</span>
            </a>
          </article>

          <!-- Past Review History (Database Real Data) -->
          <article class="card">
            <div class="history-card-header">
              <h4>Đánh giá gần đây từ thực khách</h4>
              <span class="history-count-tag"><?= count($recent_reviews) ?> Đánh giá</span>
            </div>
            <div class="history-list">
              <?php foreach ($recent_reviews as $rr): ?>
                <div class="history-item">
                  <div class="history-item-top">
                    <span class="history-set-name"><?= htmlspecialchars($rr['ten_set']) ?></span>
                    <span class="history-date"><?= date('d/m/Y', strtotime($rr['ngay_danh_gia'])) ?></span>
                  </div>
                  <div class="history-stars">
                    <?php for ($i = 0; $i < (int)$rr['so_sao']; $i++): ?>
                      <span class="material-symbols-outlined">star</span>
                    <?php endfor; ?>
                    <span class="history-score"><?= $rr['so_sao'] ?>.0</span>
                  </div>
                  <p class="history-comment">
                    “<?= htmlspecialchars($rr['noi_dung']) ?>”
                  </p>
                  <?php if (!empty($rr['phan_hoi'])): ?>
                    <div class="history-reply-note">
                      <span class="material-symbols-outlined" style="font-size: 15px;">reply</span>
                      <span>Bếp Trưởng Daiki đã gửi lời tri ân</span>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </article>
        </aside>
      </div>
    </main>

    <!-- Modal chọn Set Omakase -->
    <div class="set-modal-overlay" id="setModal">
      <div class="set-modal-box">
        <div class="set-modal-header">
          <h3>Chọn Set Omakase Bạn Đã Thưởng Thức</h3>
          <button type="button" class="set-modal-close" onclick="closeSetModal()">&times;</button>
        </div>
        <div class="set-modal-body">
          <?php foreach ($all_sets as $s): ?>
            <div 
              class="set-option-card <?= $s['id'] == $default_set['id'] ? 'active' : '' ?>"
              onclick="selectSet(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['ten_set'])) ?>', '<?= number_format($s['gia'], 0, ',', '.') ?> ₫', <?= $s['so_mon'] ?? 14 ?>, <?= $s['thoi_luong_phut'] ?? 90 ?>)"
            >
              <div class="set-option-left">
                <img src="../<?= htmlspecialchars($s['hinh_anh'] ?: 'images/kiwami.jpg') ?>" class="set-option-img" onerror="this.src='../images/kiwami.jpg'" alt="">
                <div>
                  <div class="set-option-title"><?= htmlspecialchars($s['ten_set']) ?></div>
                  <div class="set-option-sub">
                    <?= !empty($s['ten_tieng_nhat']) ? htmlspecialchars($s['ten_tieng_nhat']) . ' • ' : '' ?>
                    <?= $s['so_mon'] ?? 14 ?> món • <?= $s['thoi_luong_phut'] ?? 90 ?> phút
                  </div>
                </div>
              </div>
              <div class="set-option-price">
                <?= number_format($s['gia'], 0, ',', '.') ?> ₫
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="site-footer">
      <div class="footer-container">
        <div class="footer-grid">
          <div class="footer-col">
            <div class="brand" style="gap: 10px;">
              <div class="brand-logo">寂</div>
              <div class="brand-text">
                <h2>SABI OMAKASE</h2>
              </div>
            </div>
            <p>
              Tận hưởng triết lý Wabi-Sabi qua từng thớ cá tinh khiết. Nơi nghệ thuật Omakase giao hòa giữa sự tối giản thuần túy và nghi thức ẩm thực thượng lưu Nhật Bản.
            </p>
          </div>
          <div class="footer-col">
            <h4>Điều hướng</h4>
            <ul class="footer-links">
              <li><a href="index.php">Trang chủ</a></li>
              <li><a href="Omakase_Menu.php">Thực đơn Omakase</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4>Liên hệ</h4>
            <div class="footer-contact-item">
              <span class="material-symbols-outlined">location_on</span>
              <span>12 đường số 3, Phường 4, Quận 5, TP.HCM</span>
            </div>
          </div>
        </div>
        <div class="footer-bottom">
          <p>© 2024 Sabi Omakase. Bản quyền thuộc về nhà hàng.</p>
        </div>
      </div>
    </footer>

    <!-- Interactive Scripts -->
    <script>
      function switchTab(target) {
        const btnReview = document.getElementById("tab-btn-review");
        const btnReport = document.getElementById("tab-btn-report");
        const panelReview = document.getElementById("panel-review");
        const panelReport = document.getElementById("panel-report");

        if (target === "review") {
          btnReview.classList.add("active");
          btnReview.setAttribute("aria-selected", "true");
          btnReport.classList.remove("active");
          btnReport.setAttribute("aria-selected", "false");
          panelReview.classList.remove("hidden");
          panelReport.classList.add("hidden");
        } else {
          btnReport.classList.add("active");
          btnReport.setAttribute("aria-selected", "true");
          btnReview.classList.remove("active");
          btnReview.setAttribute("aria-selected", "false");
          panelReport.classList.remove("hidden");
          panelReview.classList.add("hidden");
        }
      }

      const starDescriptions = [
        "Cần cải thiện nhiều • Trải nghiệm chưa tương xứng",
        "Khá ổn • Cần nâng tầm thêm về dịch vụ",
        "Hài lòng • Đúng phong vị Omakase tiêu chuẩn",
        "Rất hài lòng • Thăng hoa trong từng miếng sushi",
        "Tuyệt hảo • Vượt trên cả kỳ vọng của nghệ thuật ẩm thực",
      ];

      function setStar(starCount) {
        document.getElementById("input_so_sao").value = starCount;
        const container = document.getElementById("star-rating-container");
        const buttons = container.querySelectorAll(".star-btn");
        const desc = document.getElementById("star-desc");

        buttons.forEach((btn, index) => {
          if (index < starCount) {
            btn.classList.remove("empty");
          } else {
            btn.classList.add("empty");
          }
        });

        if (desc && starDescriptions[starCount - 1]) {
          desc.innerText = starDescriptions[starCount - 1];
        }
      }

      // Modal chọn Set
      function openSetModal() {
        document.getElementById("setModal").classList.add("open");
      }
      function closeSetModal() {
        document.getElementById("setModal").classList.remove("open");
      }
      function selectSet(id, name, price, courses, duration) {
        document.getElementById("input_selected_set_id").value = id;
        document.getElementById("display-set-name").innerText = name;
        document.getElementById("display-set-price").innerText = price;
        document.getElementById("display-set-courses").innerText = courses + " món";
        document.getElementById("display-set-duration").innerText = duration + " phút";

        // Cập nhật trạng thái active trong modal
        document.querySelectorAll(".set-option-card").forEach(el => el.classList.remove("active"));
        if (event && event.currentTarget) {
          event.currentTarget.classList.add("active");
        }
        closeSetModal();
      }

      // Đóng modal khi bấm phím Escape hoặc click nền đen
      document.getElementById("setModal").addEventListener("click", function(e) {
        if (e.target === this) closeSetModal();
      });
      document.addEventListener("keydown", function(e) {
        if (e.key === "Escape") closeSetModal();
      });
    </script>
  </body>
</html>
