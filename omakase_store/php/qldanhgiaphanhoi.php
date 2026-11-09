<?php
// Bật session và kết nối cơ sở dữ liệu
require_once __DIR__ . '/config/db.php';

$msg = '';
$msg_type = '';

// ==============================================================================
// 1. XỬ LÝ ACTIONS (TRẢ LỜI PHẢN HỒI, ẨN/HIỆN, XOÁ)
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- 1.1. GỬI PHẢN HỒI CỦA NHÀ HÀNG ---
    if ($action === 'reply') {
        $id       = (int)($_POST['id'] ?? 0);
        $phan_hoi = trim($_POST['phan_hoi'] ?? '');

        if ($id > 0 && !empty($phan_hoi)) {
            $stmt = $pdo->prepare("
                UPDATE danh_gia SET 
                    phan_hoi = ?, 
                    da_xu_ly = 1, 
                    ngay_phan_hoi = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$phan_hoi, $id]);
            $msg = 'Đã gửi phản hồi thành công đến thượng khách!';
            $msg_type = 'success';
        } else {
            $msg = 'Vui lòng nhập nội dung phản hồi.';
            $msg_type = 'danger';
        }
    }

    // --- 1.2. ẨN / HIỆN ĐÁNH GIÁ (KIỂM DUYỆT) ---
    elseif ($action === 'toggle_visibility') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE danh_gia SET hien_thi = 1 - hien_thi WHERE id = ?");
            $stmt->execute([$id]);
            $msg = 'Đã thay đổi trạng thái hiển thị của đánh giá!';
            $msg_type = 'success';
        }
    }

    // --- 1.3. XOÁ ĐÁNH GIÁ (SPAM) ---
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM danh_gia WHERE id = ?");
            $stmt->execute([$id]);
            $msg = 'Đã xoá đánh giá khỏi hệ thống!';
            $msg_type = 'success';
        }
    }
}

// ==============================================================================
// 2. BỘ LỌC VÀ TRUY VẤN
// ==============================================================================
$filter_type   = $_GET['type'] ?? '';
$filter_stars  = isset($_GET['stars']) && $_GET['stars'] !== '' ? (int)$_GET['stars'] : '';
$filter_status = $_GET['status'] ?? '';

$where_clauses = ["1=1"];
$params = [];

if (!empty($filter_type)) {
    $where_clauses[] = "d.loai = ?";
    $params[] = $filter_type;
}

if ($filter_stars !== '' && $filter_stars > 0) {
    $where_clauses[] = "d.so_sao = ?";
    $params[] = $filter_stars;
}

if ($filter_status === 'replied') {
    $where_clauses[] = "d.phan_hoi IS NOT NULL AND d.phan_hoi != ''";
} elseif ($filter_status === 'pending') {
    $where_clauses[] = "(d.phan_hoi IS NULL OR d.phan_hoi = '')";
}

$where_sql = implode(' AND ', $where_clauses);

// ==============================================================================
// 3. THỐNG KÊ TỔNG QUAN
// ==============================================================================
$stat_avg     = $pdo->query("SELECT ROUND(COALESCE(AVG(so_sao), 5.0), 1) FROM danh_gia WHERE hien_thi = 1")->fetchColumn();
$stat_total   = $pdo->query("SELECT COUNT(*) FROM danh_gia")->fetchColumn();
$stat_complaint = $pdo->query("SELECT COUNT(*) FROM danh_gia WHERE loai = 'Khiếu nại' AND (phan_hoi IS NULL OR phan_hoi = '')")->fetchColumn();
$stat_sat     = $pdo->query("SELECT ROUND((COUNT(CASE WHEN so_sao >= 4 THEN 1 END) / COUNT(*)) * 100, 0) FROM danh_gia")->fetchColumn();

// Lấy danh sách đánh giá
$sql_list = "
    SELECT d.*, kh.ho_ten as ten_khach, kh.so_dien_thoai, s.ten_set, s.ma_set, don.ma_don
    FROM danh_gia d
    JOIN khach_hang kh ON d.khach_hang_id = kh.id
    JOIN set_omakase s ON d.set_id = s.id
    LEFT JOIN don_dat_ban don ON d.don_id = don.id
    WHERE {$where_sql}
    ORDER BY d.id DESC
";
$stmt_list = $pdo->prepare($sql_list);
$stmt_list->execute($params);
$reviews = $stmt_list->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản Lý Đánh Giá &amp; Phản Hồi - Sabi Omakase Admin</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&family=Noto+Serif:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    :root {
      --bg: #0e0c0b;
      --sidebar: #13100f;
      --card: #181412;
      --border: #28211d;
      --border-light: #382e28;
      --text: #ede7e1;
      --muted: #9c9085;
      --gold: #d4af37;
      --gold-light: #f3e5b3;
      --crimson: #a82020;
      --crimson-hover: #c42727;
      --success: #3e8e41;
      --warning: #e09f3e;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background-color: var(--bg);
      color: var(--text);
      font-family: 'Noto Sans', sans-serif;
      font-size: 14px;
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar */
    .admin-sidebar {
      width: 250px;
      background: var(--sidebar);
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0; bottom: 0; left: 0;
    }
    .brand-area {
      padding: 24px 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      border-bottom: 1px solid var(--border);
    }
    .brand-mark {
      width: 38px; height: 38px;
      background: var(--crimson);
      color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Noto Serif', serif;
      font-size: 20px;
      border-radius: 6px;
    }
    .brand-title h2 { font-size: 15px; color: var(--gold-light); font-family: 'Noto Serif', serif; }
    .brand-title span { font-size: 10px; color: var(--muted); letter-spacing: 2px; }

    .nav-menu { padding: 20px 12px; flex: 1; }
    .nav-link {
      display: flex; align-items: center; gap: 12px;
      padding: 11px 14px;
      color: var(--muted);
      text-decoration: none;
      border-radius: 6px;
      margin-bottom: 4px;
      transition: all 0.2s;
    }
    .nav-link:hover, .nav-link.active {
      color: #fff;
      background: rgba(255,255,255,0.05);
    }
    .nav-link.active {
      background: linear-gradient(90deg, rgba(168,32,32,0.3) 0%, transparent 100%);
      border-left: 3px solid var(--crimson);
      color: var(--gold-light);
    }

    /* Main Content */
    .admin-main {
      margin-left: 250px;
      flex: 1;
      display: flex;
      flex-direction: column;
      min-width: 0;
    }
    .admin-topbar {
      height: 65px;
      background: var(--sidebar);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 30px;
    }
    .topbar-title h1 { font-size: 18px; color: #fff; font-family: 'Noto Serif', serif; }
    .admin-content { padding: 30px; }

    /* Alert */
    .alert {
      padding: 12px 16px; border-radius: 6px; margin-bottom: 20px;
      display: flex; align-items: center; gap: 10px;
    }
    .alert-success { background: rgba(62,142,65,0.2); border: 1px solid var(--success); color: #8ce48f; }
    .alert-danger  { background: rgba(168,32,32,0.2); border: 1px solid var(--crimson); color: #ff9b9b; }

    /* KPI Cards */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }
    .kpi-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 18px;
    }
    .kpi-card .label { font-size: 12px; color: var(--muted); text-transform: uppercase; }
    .kpi-card .value {
      font-family: 'Noto Serif', serif;
      font-size: 26px;
      font-weight: 700;
      color: #fff;
      margin: 6px 0 2px;
    }
    .kpi-card .sub { font-size: 12px; color: var(--gold); }

    /* Filter Form */
    .filter-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 16px 20px;
      margin-bottom: 24px;
    }
    .filter-form { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
    select {
      background: #0e0c0b; border: 1px solid var(--border);
      color: #fff; padding: 8px 12px; border-radius: 6px; font-size: 13px;
    }
    select:focus { outline: none; border-color: var(--gold); }

    /* Review Card Item */
    .review-item {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 16px;
      transition: all 0.2s;
    }
    .review-item:hover { border-color: var(--border-light); }
    .review-top {
      display: flex; justify-content: space-between; align-items: flex-start;
      margin-bottom: 12px; flex-wrap: wrap; gap: 10px;
    }
    .user-info b { font-size: 15px; color: #fff; }
    .user-info small { color: var(--muted); margin-left: 6px; }
    .stars { color: #f5b041; font-size: 14px; letter-spacing: 2px; }
    .comment-text {
      font-size: 14px; line-height: 1.6; color: #ded6cf;
      margin-bottom: 14px; padding: 8px 0;
    }
    .reply-box {
      background: #110e0c;
      border-left: 3px solid var(--gold);
      padding: 12px 16px;
      border-radius: 0 6px 6px 0;
      margin-top: 12px;
    }
    .reply-box b { color: var(--gold); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
    .reply-box p { color: #c4b9b0; margin-top: 4px; font-size: 13px; }

    /* Badges */
    .badge {
      display: inline-block; padding: 3px 8px; border-radius: 4px;
      font-size: 11px; font-weight: 600;
    }
    .badge-review    { background: rgba(212,175,55,0.15); color: var(--gold-light); border: 1px solid rgba(212,175,55,0.3); }
    .badge-complaint { background: rgba(168,32,32,0.2); color: #ff8585; border: 1px solid rgba(168,32,32,0.4); }
    .badge-hidden    { background: rgba(100,100,100,0.2); color: #888; }

    /* Buttons */
    .btn {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 6px 12px; border-radius: 6px; font-size: 12px;
      cursor: pointer; text-decoration: none; border: 1px solid transparent;
    }
    .btn-primary { background: var(--crimson); color: #fff; }
    .btn-primary:hover { background: var(--crimson-hover); }
    .btn-outline { background: transparent; border-color: var(--border-light); color: var(--text); }
    .btn-outline:hover { border-color: var(--gold); color: var(--gold); }
    .btn-danger { background: rgba(168,32,32,0.2); border-color: rgba(168,32,32,0.4); color: #ff8585; }

    /* Modal */
    .modal-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,0.75);
      display: none; align-items: center; justify-content: center;
      z-index: 1000; padding: 20px;
    }
    .modal-overlay.active { display: flex; }
    .modal-dialog {
      background: var(--card); border: 1px solid var(--border-light);
      border-radius: 10px; width: 100%; max-width: 520px;
    }
    .modal-header {
      padding: 16px 20px; border-bottom: 1px solid var(--border);
      display: flex; justify-content: space-between; align-items: center;
    }
    .modal-header h3 { font-size: 16px; color: #fff; }
    .modal-close { background: none; border: none; color: var(--muted); font-size: 20px; cursor: pointer; }
    .modal-body { padding: 20px; }
    .modal-footer {
      padding: 14px 20px; border-top: 1px solid var(--border);
      display: flex; justify-content: flex-end; gap: 10px;
    }
    textarea.form-control {
      width: 100%; background: #0e0c0b; border: 1px solid var(--border);
      color: #fff; padding: 10px 12px; border-radius: 6px; font-size: 13px;
      resize: vertical; min-height: 100px;
    }
    textarea.form-control:focus { outline: none; border-color: var(--gold); }
  </style>
</head>
<body>

  <!-- ============ SIDEBAR ============ -->
  <aside class="admin-sidebar">
    <div class="brand-area">
      <div class="brand-mark">祥</div>
      <div class="brand-title">
        <h2>SABI OMAKASE</h2>
        <span>SITE QUẢN TRỊ</span>
      </div>
    </div>

    <nav class="nav-menu">
      <a href="qldanhgiaphanhoi.php" class="nav-link active">
        <i class="fa-solid fa-comments"></i>
        <span>Đánh Giá & Phản Hồi</span>
      </a>
      <div style="margin-top: 30px; padding: 0 12px;">
        <a href="danhgia.php" target="_blank" class="btn btn-outline" style="width: 100%; justify-content: center; margin-bottom: 8px;">
          <i class="fa-solid fa-pen-nib"></i> Xem Trang Khách Gửi
        </a>
        <a href="index.php" target="_blank" class="btn btn-outline" style="width: 100%; justify-content: center;">
          <i class="fa-solid fa-arrow-up-right-from-square"></i> Xem Trang Chủ
        </a>
      </div>
    </nav>
  </aside>

  <!-- ============ MAIN CONTENT ============ -->
  <main class="admin-main">
    <header class="admin-topbar">
      <div class="topbar-title">
        <h1>Quản Lý Đánh Giá &amp; Khiếu Nại Trải Nghiệm</h1>
      </div>
      <div>
        <span style="font-size: 13px; color: var(--gold);">
          <i class="fa-solid fa-star"></i> Điểm TB: <b><?= $stat_avg ?> / 5.0</b>
        </span>
      </div>
    </header>

    <div class="admin-content">

      <?php if (!empty($msg)): ?>
        <div class="alert alert-<?= $msg_type ?>">
          <i class="fa-solid fa-circle-check"></i>
          <span><?= htmlspecialchars($msg) ?></span>
        </div>
      <?php endif; ?>

      <!-- ============ THỐNG KÊ 4 THẺ CHỈ SỐ ============ -->
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="label">Điểm Đánh Giá Trung Bình</div>
          <div class="value" style="color: var(--gold-light);"><?= $stat_avg ?> <span style="font-size: 16px;">★</span></div>
          <div class="sub">Trên tổng số <?= $stat_total ?> lượt đánh giá</div>
        </div>

        <div class="kpi-card">
          <div class="label">Tổng Lượt Phản Hồi</div>
          <div class="value"><?= $stat_total ?></div>
          <div class="sub">Từ tất cả các buổi tiệc</div>
        </div>

        <div class="kpi-card">
          <div class="label">Khiếu Nại Cần Xử Lý</div>
          <div class="value" style="color: <?= $stat_complaint > 0 ? '#ff8585' : '#fff' ?>;"><?= $stat_complaint ?></div>
          <div class="sub">Chưa có phản hồi giải quyết</div>
        </div>

        <div class="kpi-card">
          <div class="label">Tỷ Lệ Hài Lòng (4-5★)</div>
          <div class="value" style="color: #8ce48f;"><?= $stat_sat ?>%</div>
          <div class="sub">Thực khách hoàn toàn đắc ý</div>
        </div>
      </div>

      <!-- ============ BỘ LỌC ĐÁNH GIÁ ============ -->
      <div class="filter-card">
        <form method="GET" action="qldanhgiaphanhoi.php" class="filter-form">
          <label style="font-size: 13px; color: var(--muted);"><i class="fa-solid fa-filter"></i> Lọc đánh giá:</label>

          <select name="type" onchange="this.form.submit()">
            <option value="">-- Tất cả phân loại --</option>
            <option value="Đánh giá" <?= $filter_type === 'Đánh giá' ? 'selected' : '' ?>>Đánh giá thông thường</option>
            <option value="Khiếu nại" <?= $filter_type === 'Khiếu nại' ? 'selected' : '' ?>>Khiếu nại / Sự cố</option>
          </select>

          <select name="stars" onchange="this.form.submit()">
            <option value="">-- Tất cả số sao --</option>
            <option value="5" <?= (string)$filter_stars === '5' ? 'selected' : '' ?>>5 Sao (Xuất sắc)</option>
            <option value="4" <?= (string)$filter_stars === '4' ? 'selected' : '' ?>>4 Sao (Rất tốt)</option>
            <option value="3" <?= (string)$filter_stars === '3' ? 'selected' : '' ?>>3 Sao (Bình thường)</option>
            <option value="2" <?= (string)$filter_stars === '2' ? 'selected' : '' ?>>2 Sao (Cần cải thiện)</option>
            <option value="1" <?= (string)$filter_stars === '1' ? 'selected' : '' ?>>1 Sao (Thất vọng)</option>
          </select>

          <select name="status" onchange="this.form.submit()">
            <option value="">-- Trạng thái phản hồi --</option>
            <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Chưa phản hồi</option>
            <option value="replied" <?= $filter_status === 'replied' ? 'selected' : '' ?>>Đã phản hồi</option>
          </select>

          <?php if (!empty($filter_type) || $filter_stars !== '' || !empty($filter_status)): ?>
            <a href="qldanhgiaphanhoi.php" class="btn btn-outline">
              <i class="fa-solid fa-rotate-left"></i> Đặt lại
            </a>
          <?php endif; ?>
        </form>
      </div>

      <!-- ============ DANH SÁCH CÁC ĐÁNH GIÁ ============ -->
      <?php if (empty($reviews)): ?>
        <div style="background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 40px; text-align: center; color: var(--muted);">
          <i class="fa-solid fa-comment-slash" style="font-size: 36px; margin-bottom: 10px; display: block; opacity: 0.5;"></i>
          Không tìm thấy đánh giá nào theo điều kiện lọc.
        </div>
      <?php else: ?>
        <?php foreach ($reviews as $rv): ?>
          <div class="review-item" style="<?= $rv['hien_thi'] == 0 ? 'opacity: 0.6;' : '' ?>">
            <div class="review-top">
              <div>
                <div class="user-info">
                  <b><?= htmlspecialchars($rv['ten_khach']) ?></b>
                  <small>(<?= htmlspecialchars($rv['so_dien_thoai']) ?>)</small>
                  <?php if (!empty($rv['ma_don'])): ?>
                    <span style="font-size: 11px; color: var(--gold); margin-left: 8px;">Đơn: #<?= htmlspecialchars($rv['ma_don']) ?></span>
                  <?php endif; ?>
                </div>
                <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">
                  Set trải nghiệm: <b style="color: #fff;"><?= htmlspecialchars($rv['ten_set']) ?></b>
                  • Ngày: <?= date('d/m/Y H:i', strtotime($rv['ngay_danh_gia'])) ?>
                </div>
              </div>

              <div style="display: flex; align-items: center; gap: 10px;">
                <!-- Loại -->
                <?php if ($rv['loai'] === 'Khiếu nại'): ?>
                  <span class="badge badge-complaint"><i class="fa-solid fa-triangle-exclamation"></i> Khiếu nại</span>
                <?php else: ?>
                  <span class="badge badge-review"><i class="fa-solid fa-heart"></i> Đánh giá</span>
                <?php endif; ?>

                <!-- Trạng thái ẩn/hiện -->
                <?php if ($rv['hien_thi'] == 0): ?>
                  <span class="badge badge-hidden"><i class="fa-solid fa-eye-slash"></i> Đang ẩn</span>
                <?php endif; ?>

                <!-- Số sao -->
                <div class="stars">
                  <?= str_repeat('★', (int)$rv['so_sao']) ?><span style="color: #443c36;"><?= str_repeat('★', 5 - (int)$rv['so_sao']) ?></span>
                </div>
              </div>
            </div>

            <!-- Nội dung đánh giá -->
            <div class="comment-text">
              "<?= nl2br(htmlspecialchars($rv['noi_dung'] ?: 'Không có nội dung nhận xét.')) ?>"
            </div>

            <!-- Phản hồi của nhà hàng nếu có -->
            <?php if (!empty($rv['phan_hoi'])): ?>
              <div class="reply-box">
                <div style="display: flex; justify-content: space-between;">
                  <b><i class="fa-solid fa-reply"></i> Phản hồi từ Bếp Trưởng Sabi:</b>
                  <small style="color: var(--muted); font-size: 11px;"><?= !empty($rv['ngay_phan_hoi']) ? date('d/m/Y H:i', strtotime($rv['ngay_phan_hoi'])) : '' ?></small>
                </div>
                <p><?= nl2br(htmlspecialchars($rv['phan_hoi'])) ?></p>
              </div>
            <?php endif; ?>

            <!-- Các nút hành động: Phản hồi, Ẩn/Hiện, Xóa -->
            <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 14px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.04);">
              <!-- Nút Phản Hồi -->
              <button type="button" class="btn btn-outline" onclick='openReply(<?= $rv['id'] ?>, <?= json_encode($rv['ten_khach']) ?>, <?= json_encode($rv['phan_hoi'] ?? '') ?>)'>
                <i class="fa-solid fa-reply"></i> <?= empty($rv['phan_hoi']) ? 'Gửi Phản Hồi' : 'Sửa Phản Hồi' ?>
              </button>

              <!-- Nút Ẩn / Hiện -->
              <form method="POST" action="qldanhgiaphanhoi.php" style="display: inline;">
                <input type="hidden" name="action" value="toggle_visibility">
                <input type="hidden" name="id" value="<?= $rv['id'] ?>">
                <button type="submit" class="btn btn-outline" title="<?= $rv['hien_thi'] == 1 ? 'Ẩn đánh giá' : 'Hiện đánh giá' ?>">
                  <i class="fa-solid <?= $rv['hien_thi'] == 1 ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                  <?= $rv['hien_thi'] == 1 ? 'Ẩn' : 'Hiện' ?>
                </button>
              </form>

              <!-- Nút Xoá -->
              <form method="POST" action="qldanhgiaphanhoi.php" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn xoá đánh giá này không?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $rv['id'] ?>">
                <button type="submit" class="btn btn-danger" title="Xóa đánh giá">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

    </div>
  </main>

  <!-- ============ MODAL PHẢN HỒI ĐÁNH GIÁ ============ -->
  <div class="modal-overlay" id="modalReply">
    <div class="modal-dialog">
      <div class="modal-header">
        <h3><i class="fa-solid fa-reply" style="color: var(--gold);"></i> Phản Hồi Thượng Khách: <span id="reply_name" style="color: var(--gold-light);"></span></h3>
        <button type="button" class="modal-close" onclick="closeModal('modalReply')">&times;</button>
      </div>

      <form method="POST" action="qldanhgiaphanhoi.php">
        <input type="hidden" name="action" value="reply">
        <input type="hidden" name="id" id="reply_id">

        <div class="modal-body">
          <label style="display: block; font-size: 12px; color: var(--muted); margin-bottom: 8px;">
            NỘI DUNG PHẢN HỒI TỪ NHÀ HÀNG SABI OMAKASE:
          </label>
          <textarea name="phan_hoi" id="reply_content" class="form-control" rows="4" placeholder="Nhập lời cảm ơn hoặc giải trình gửi tới thượng khách..." required></textarea>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('modalReply')">Hủy</button>
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-paper-plane"></i> Gửi Phản Hồi
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openModal(id) { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }

    function openReply(id, name, content) {
      document.getElementById('reply_id').value = id;
      document.getElementById('reply_name').innerText = name;
      document.getElementById('reply_content').value = content || 'Cảm ơn quý khách đã sẻ chia cảm nhận. Sabi Omakase luôn trân quý từng khoảnh khắc được phục vụ quý khách!';
      openModal('modalReply');
    }
  </script>
</body>
</html>
