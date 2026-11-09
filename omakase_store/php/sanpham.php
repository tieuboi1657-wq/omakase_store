<?php
// Bật session và kết nối cơ sở dữ liệu có sẵn của nhóm
require_once __DIR__ . '/config/db.php';

// ==============================================================================
// 1. XỬ LÝ CÁC THAO TÁC: THÊM, SỬA, XOÁ (POST REQUEST)
// ==============================================================================
$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- 1.1. CHỨC NĂNG THÊM SẢN PHẨM MỚI ---
    if ($action === 'add') {
        $ma_set         = strtoupper(trim($_POST['ma_set'] ?? ''));
        $ten_set        = trim($_POST['ten_set'] ?? '');
        $ten_tieng_nhat = trim($_POST['ten_tieng_nhat'] ?? '');
        $loai_set_id    = (int)($_POST['loai_set_id'] ?? 0);
        $bep_truong_id  = !empty($_POST['bep_truong_id']) ? (int)$_POST['bep_truong_id'] : null;
        $so_mon         = (int)($_POST['so_mon'] ?? 10);
        $thoi_luong     = (int)($_POST['thoi_luong_phut'] ?? 90);
        $gia            = (float)str_replace(['.', ',', ' '], '', $_POST['gia'] ?? '0');
        $nhan           = trim($_POST['nhan'] ?? '');
        $mo_ta_ngan     = trim($_POST['mo_ta_ngan'] ?? '');
        $mo_ta_chi_tiet = trim($_POST['mo_ta_chi_tiet'] ?? '');
        $hinh_anh       = trim($_POST['hinh_anh'] ?? '');
        $noi_bat        = isset($_POST['noi_bat']) ? 1 : 0;
        $trang_thai     = $_POST['trang_thai'] ?? 'Đang bán';

        // Kiểm tra dữ liệu hợp lệ
        if (empty($ma_set) || empty($ten_set) || $gia <= 0 || $loai_set_id <= 0) {
            $msg = 'Vui lòng điền đủ Mã set, Tên set, Danh mục và Giá bán (> 0).';
            $msg_type = 'danger';
        } else {
            // Kiểm tra trùng lặp mã set
            $check = $pdo->prepare("SELECT id FROM set_omakase WHERE ma_set = ?");
            $check->execute([$ma_set]);
            if ($check->fetch()) {
                $msg = "Mã set '{$ma_set}' đã tồn tại trong hệ thống. Vui lòng nhập mã khác!";
                $msg_type = 'danger';
            } else {
                if (empty($hinh_anh)) {
                    $hinh_anh = 'images/caption.jpg';
                }
                $stmt_insert = $pdo->prepare("
                    INSERT INTO set_omakase 
                    (ma_set, ten_set, ten_tieng_nhat, loai_set_id, bep_truong_id, so_mon, thoi_luong_phut, gia, nhan, mo_ta_ngan, mo_ta_chi_tiet, hinh_anh, noi_bat, trang_thai, ngay_tao)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt_insert->execute([
                    $ma_set, $ten_set, $ten_tieng_nhat, $loai_set_id, $bep_truong_id,
                    $so_mon, $thoi_luong, $gia, $nhan, $mo_ta_ngan, $mo_ta_chi_tiet,
                    $hinh_anh, $noi_bat, $trang_thai
                ]);
                $msg = "Thêm thành công set Omakase: {$ten_set}!";
                $msg_type = 'success';
            }
        }
    }

    // --- 1.2. CHỨC NĂNG CẬP NHẬT (SỬA) SẢN PHẨM ---
    elseif ($action === 'edit') {
        $id             = (int)($_POST['id'] ?? 0);
        $ma_set         = strtoupper(trim($_POST['ma_set'] ?? ''));
        $ten_set        = trim($_POST['ten_set'] ?? '');
        $ten_tieng_nhat = trim($_POST['ten_tieng_nhat'] ?? '');
        $loai_set_id    = (int)($_POST['loai_set_id'] ?? 0);
        $bep_truong_id  = !empty($_POST['bep_truong_id']) ? (int)$_POST['bep_truong_id'] : null;
        $so_mon         = (int)($_POST['so_mon'] ?? 10);
        $thoi_luong     = (int)($_POST['thoi_luong_phut'] ?? 90);
        $gia            = (float)str_replace(['.', ',', ' '], '', $_POST['gia'] ?? '0');
        $nhan           = trim($_POST['nhan'] ?? '');
        $mo_ta_ngan     = trim($_POST['mo_ta_ngan'] ?? '');
        $mo_ta_chi_tiet = trim($_POST['mo_ta_chi_tiet'] ?? '');
        $hinh_anh       = trim($_POST['hinh_anh'] ?? '');
        $noi_bat        = isset($_POST['noi_bat']) ? 1 : 0;
        $trang_thai     = $_POST['trang_thai'] ?? 'Đang bán';

        if ($id <= 0 || empty($ma_set) || empty($ten_set) || $gia <= 0 || $loai_set_id <= 0) {
            $msg = 'Thông tin cập nhật không hợp lệ.';
            $msg_type = 'danger';
        } else {
            // Kiểm tra trùng mã set với sản phẩm khác
            $check = $pdo->prepare("SELECT id FROM set_omakase WHERE ma_set = ? AND id != ?");
            $check->execute([$ma_set, $id]);
            if ($check->fetch()) {
                $msg = "Mã set '{$ma_set}' đã thuộc về một set khác!";
                $msg_type = 'danger';
            } else {
                $stmt_update = $pdo->prepare("
                    UPDATE set_omakase SET 
                        ma_set = ?, ten_set = ?, ten_tieng_nhat = ?, loai_set_id = ?, 
                        bep_truong_id = ?, so_mon = ?, thoi_luong_phut = ?, gia = ?, 
                        nhan = ?, mo_ta_ngan = ?, mo_ta_chi_tiet = ?, hinh_anh = ?, 
                        noi_bat = ?, trang_thai = ?, ngay_cap_nhat = NOW()
                    WHERE id = ?
                ");
                $stmt_update->execute([
                    $ma_set, $ten_set, $ten_tieng_nhat, $loai_set_id, $bep_truong_id,
                    $so_mon, $thoi_luong, $gia, $nhan, $mo_ta_ngan, $mo_ta_chi_tiet,
                    $hinh_anh, $noi_bat, $trang_thai, $id
                ]);
                $msg = "Đã cập nhật thông tin set: {$ten_set}!";
                $msg_type = 'success';
            }
        }
    }

    // --- 1.3. CHỨC NĂNG XOÁ SẢN PHẨM ---
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Kiểm tra sản phẩm đã phát sinh trong đơn đặt bàn chưa
            $check_orders = $pdo->prepare("SELECT COUNT(*) FROM chi_tiet_don WHERE set_id = ?");
            $check_orders->execute([$id]);
            $order_count = $check_orders->fetchColumn();

            if ($order_count > 0) {
                // Đã có trong đơn hàng => Đổi trạng thái sang "Ngừng bán" để bảo vệ toàn vẹn dữ liệu
                $stmt_soft = $pdo->prepare("UPDATE set_omakase SET trang_thai = 'Ngừng bán', ngay_cap_nhat = NOW() WHERE id = ?");
                $stmt_soft->execute([$id]);
                $msg = "Set này đã có trong {$order_count} đơn hàng lịch sử. Hệ thống đã chuyển trạng thái sang 'Ngừng bán' để bảo toàn dữ liệu!";
                $msg_type = 'warning';
            } else {
                // Chưa từng có đơn hàng => Xóa an toàn
                $stmt_del = $pdo->prepare("DELETE FROM set_omakase WHERE id = ?");
                $stmt_del->execute([$id]);
                $msg = "Đã xóa hoàn toàn set Omakase khỏi hệ thống!";
                $msg_type = 'success';
            }
        }
    }
}

// ==============================================================================
// 2. TÌM KIẾM, LỌC VÀ TRUY VẤN DỮ LIỆU HIỂN THỊ
// ==============================================================================
$categories = $pdo->query("SELECT * FROM loai_set ORDER BY thu_tu ASC")->fetchAll();
$chefs = $pdo->query("SELECT id, ho_ten, chuc_danh FROM nhan_vien WHERE bo_phan = 'Đầu bếp' ORDER BY id ASC")->fetchAll();

$keyword       = trim($_GET['keyword'] ?? '');
$filter_cat    = isset($_GET['cat_id']) && $_GET['cat_id'] !== '' ? (int)$_GET['cat_id'] : '';
$filter_status = trim($_GET['status'] ?? '');

$sql = "
    SELECT s.*, l.ten_loai, nv.ho_ten as ten_bep_truong
    FROM set_omakase s
    LEFT JOIN loai_set l ON s.loai_set_id = l.id
    LEFT JOIN nhan_vien nv ON s.bep_truong_id = nv.id
    WHERE 1=1
";
$params = [];

// Tìm kiếm theo từ khóa
if (!empty($keyword)) {
    $sql .= " AND (s.ten_set LIKE ? OR s.ma_set LIKE ? OR s.ten_tieng_nhat LIKE ?)";
    $kw = '%' . $keyword . '%';
    $params[] = $kw;
    $params[] = $kw;
    $params[] = $kw;
}

// Lọc theo danh mục
if ($filter_cat !== '' && $filter_cat > 0) {
    $sql .= " AND s.loai_set_id = ?";
    $params[] = $filter_cat;
}

// Lọc theo trạng thái
if (!empty($filter_status)) {
    $sql .= " AND s.trang_thai = ?";
    $params[] = $filter_status;
}

$sql .= " ORDER BY s.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản Lý Sản Phẩm - Sabi Omakase Admin</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&family=Noto+Serif:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    :root {
      --bg: #0e0c0b;
      --sidebar: #13100f;
      --card: #181412;
      --card-hover: #1f1a17;
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

    /* Sidebar quản trị */
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
      padding: 12px 16px;
      border-radius: 6px;
      margin-bottom: 20px;
      display: flex; align-items: center; gap: 10px;
    }
    .alert-success { background: rgba(62,142,65,0.2); border: 1px solid var(--success); color: #8ce48f; }
    .alert-danger { background: rgba(168,32,32,0.2); border: 1px solid var(--crimson); color: #ff9b9b; }
    .alert-warning { background: rgba(224,159,62,0.2); border: 1px solid var(--warning); color: #ffd175; }

    /* Thanh công cụ */
    .toolbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 14px;
      flex-wrap: wrap;
      margin-bottom: 22px;
    }
    .filter-form { display: flex; gap: 10px; flex-wrap: wrap; flex: 1; }
    .search-box { position: relative; min-width: 250px; flex: 1; }
    .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--muted); }
    .search-box input, select {
      background: var(--card);
      border: 1px solid var(--border);
      color: #fff;
      padding: 9px 12px;
      border-radius: 6px;
      font-size: 13px;
    }
    .search-box input { width: 100%; padding-left: 36px; }
    .search-box input:focus, select:focus { outline: none; border-color: var(--gold); }

    /* Buttons */
    .btn {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 8px 16px;
      border-radius: 6px;
      font-size: 13px;
      cursor: pointer;
      text-decoration: none;
      border: 1px solid transparent;
      transition: all 0.2s;
    }
    .btn-primary { background: var(--crimson); color: #fff; }
    .btn-primary:hover { background: var(--crimson-hover); }
    .btn-outline { background: transparent; border-color: var(--border-light); color: var(--text); }
    .btn-outline:hover { border-color: var(--gold); color: var(--gold); }
    .btn-sm { padding: 5px 10px; font-size: 12px; }
    .btn-danger { background: rgba(168,32,32,0.2); border-color: rgba(168,32,32,0.5); color: #ff8585; }
    .btn-danger:hover { background: var(--crimson); color: #fff; }

    /* Bảng dữ liệu */
    .table-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 8px;
      overflow: hidden;
    }
    .table-responsive { width: 100%; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th {
      background: #14110f;
      color: var(--muted);
      font-size: 11px;
      text-transform: uppercase;
      padding: 14px 16px;
      border-bottom: 1px solid var(--border);
      text-align: left;
    }
    td {
      padding: 14px 16px;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
      font-size: 13px;
    }
    tr:hover td { background: var(--card-hover); }
    .prod-thumb {
      width: 48px; height: 48px;
      object-fit: cover;
      border-radius: 6px;
      border: 1px solid var(--border);
    }
    .prod-title { font-weight: 600; color: #fff; }
    .prod-jp { font-size: 11px; color: var(--gold); }
    .prod-price { font-weight: 600; color: var(--gold-light); }

    .badge {
      display: inline-block; padding: 2px 7px;
      border-radius: 4px; font-size: 11px; font-weight: 600;
    }
    .badge-success { background: rgba(62,142,65,0.2); color: #85e088; }
    .badge-warning { background: rgba(224,159,62,0.2); color: #f0be65; }
    .badge-danger  { background: rgba(168,32,32,0.2); color: #ff8585; }
    .badge-gold    { background: rgba(212,175,55,0.15); color: var(--gold-light); }

    /* Modal Form */
    .modal-overlay {
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.75);
      display: none; align-items: center; justify-content: center;
      z-index: 1000; padding: 20px;
    }
    .modal-overlay.active { display: flex; }
    .modal-dialog {
      background: var(--card);
      border: 1px solid var(--border-light);
      border-radius: 10px;
      width: 100%; max-width: 640px;
      max-height: 90vh; overflow-y: auto;
    }
    .modal-header {
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
      display: flex; justify-content: space-between; align-items: center;
    }
    .modal-header h3 { font-size: 16px; color: #fff; }
    .modal-close { background: none; border: none; color: var(--muted); font-size: 20px; cursor: pointer; }
    .modal-body { padding: 20px; }
    .modal-footer {
      padding: 14px 20px;
      border-top: 1px solid var(--border);
      display: flex; justify-content: flex-end; gap: 10px;
    }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
    .form-group { margin-bottom: 14px; }
    .form-group label { display: block; font-size: 12px; color: var(--muted); margin-bottom: 6px; }
    .form-control {
      width: 100%; background: #0e0c0b; border: 1px solid var(--border);
      color: #fff; padding: 9px 12px; border-radius: 6px; font-size: 13px;
    }
    .form-control:focus { outline: none; border-color: var(--gold); }
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
      <a href="sanpham.php" class="nav-link active">
        <i class="fa-solid fa-utensils"></i>
        <span>Quản Lý Sản Phẩm</span>
      </a>
      <div style="margin-top: 30px; padding: 0 12px;">
        <a href="index.php" target="_blank" class="btn btn-outline" style="width: 100%; justify-content: center;">
          <i class="fa-solid fa-arrow-up-right-from-square"></i> Xem Trang Khách
        </a>
      </div>
    </nav>
  </aside>

  <!-- ============ MAIN CONTENT ============ -->
  <main class="admin-main">
    <header class="admin-topbar">
      <div class="topbar-title">
        <h1>Quản Lý Thực Đơn &amp; Set Omakase</h1>
      </div>
      <div>
        <span style="font-size: 13px; color: var(--muted);">
          <i class="fa-solid fa-user-shield" style="color: var(--gold);"></i> Admin Portal
        </span>
      </div>
    </header>

    <div class="admin-content">
      <!-- Thông báo kết quả -->
      <?php if (!empty($msg)): ?>
        <div class="alert alert-<?= $msg_type ?>">
          <i class="fa-solid <?= $msg_type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
          <span><?= htmlspecialchars($msg) ?></span>
        </div>
      <?php endif; ?>

      <!-- Thanh công cụ tìm kiếm, lọc & thêm mới -->
      <div class="toolbar">
        <form method="GET" action="sanpham.php" class="filter-form">
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="keyword" placeholder="Tìm theo tên set, tiếng Nhật, mã..." value="<?= htmlspecialchars($keyword) ?>">
          </div>

          <select name="cat_id" onchange="this.form.submit()">
            <option value="">-- Tất cả danh mục --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= (string)$filter_cat === (string)$cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['ten_loai']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <select name="status" onchange="this.form.submit()">
            <option value="">-- Tất cả trạng thái --</option>
            <option value="Đang bán" <?= $filter_status === 'Đang bán' ? 'selected' : '' ?>>Đang bán</option>
            <option value="Tạm ngừng" <?= $filter_status === 'Tạm ngừng' ? 'selected' : '' ?>>Tạm ngừng</option>
            <option value="Ngừng bán" <?= $filter_status === 'Ngừng bán' ? 'selected' : '' ?>>Ngừng bán</option>
          </select>

          <button type="submit" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-filter"></i> Lọc
          </button>

          <?php if (!empty($keyword) || $filter_cat !== '' || !empty($filter_status)): ?>
            <a href="sanpham.php" class="btn btn-outline btn-sm">
              <i class="fa-solid fa-rotate-left"></i> Đặt lại
            </a>
          <?php endif; ?>
        </form>

        <button type="button" class="btn btn-primary" onclick="openModal('modalAdd')">
          <i class="fa-solid fa-plus"></i> Thêm Set Mới
        </button>
      </div>

      <!-- Bảng danh sách sản phẩm -->
      <div class="table-card">
        <div style="padding: 14px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between;">
          <b>Danh Sách Set Omakase</b>
          <span style="color: var(--muted); font-size: 12px;">Tổng: <b><?= count($products) ?></b> món</span>
        </div>

        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th style="width: 50px;">Ảnh</th>
                <th>Mã Set</th>
                <th>Tên Set Omakase</th>
                <th>Danh Mục</th>
                <th>Giá Niêm Yết</th>
                <th>Quy Cách</th>
                <th>Nhãn</th>
                <th>Trạng Thái</th>
                <th style="text-align: right; width: 130px;">Hành Động</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($products)): ?>
                <tr>
                  <td colspan="9" style="text-align: center; padding: 30px; color: var(--muted);">
                    Không tìm thấy sản phẩm nào phù hợp.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($products as $p): ?>
                  <tr>
                    <td>
                      <img src="<?= htmlspecialchars($p['hinh_anh'] ?: 'images/caption.jpg') ?>" 
                           alt="<?= htmlspecialchars($p['ten_set']) ?>" 
                           class="prod-thumb"
                           onerror="this.src='https://images.unsplash.com/photo-1579871494447-9811cf80d66c?q=80&w=300';">
                    </td>
                    <td>
                      <b style="color: var(--gold);"><?= htmlspecialchars($p['ma_set']) ?></b>
                      <?php if ($p['noi_bat'] == 1): ?>
                        <i class="fa-solid fa-star" title="Món nổi bật" style="color: var(--gold); margin-left: 4px; font-size: 11px;"></i>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="prod-title"><?= htmlspecialchars($p['ten_set']) ?></div>
                      <?php if (!empty($p['ten_tieng_nhat'])): ?>
                        <div class="prod-jp"><?= htmlspecialchars($p['ten_tieng_nhat']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($p['ten_loai'] ?? 'Chưa phân loại') ?></td>
                    <td><span class="prod-price"><?= number_format($p['gia'], 0, ',', '.') ?> ₫</span></td>
                    <td>
                      <div><?= (int)$p['so_mon'] ?> món</div>
                      <small style="color: var(--muted);"><?= (int)$p['thoi_luong_phut'] ?> phút</small>
                    </td>
                    <td>
                      <?php if (!empty($p['nhan'])): ?>
                        <span class="badge badge-gold"><?= htmlspecialchars($p['nhan']) ?></span>
                      <?php else: ?> - <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($p['trang_thai'] === 'Đang bán'): ?>
                        <span class="badge badge-success">Đang bán</span>
                      <?php elseif ($p['trang_thai'] === 'Tạm ngừng'): ?>
                        <span class="badge badge-warning">Tạm ngừng</span>
                      <?php else: ?>
                        <span class="badge badge-danger">Ngừng bán</span>
                      <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                      <button type="button" class="btn btn-outline btn-sm" onclick='openEdit(<?= json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                        <i class="fa-solid fa-pen-to-square"></i>
                      </button>
                      <form method="POST" action="sanpham.php" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn xóa set: <?= htmlspecialchars($p['ten_set']) ?>?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <!-- ============ MODAL THÊM SẢN PHẨM ============ -->
  <div class="modal-overlay" id="modalAdd">
    <div class="modal-dialog">
      <div class="modal-header">
        <h3>Thêm Set Omakase Mới</h3>
        <button type="button" class="modal-close" onclick="closeModal('modalAdd')">&times;</button>
      </div>
      <form method="POST" action="sanpham.php">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label>Mã Set (in hoa) *</label>
              <input type="text" name="ma_set" class="form-control" placeholder="VD: SAKURA" required style="text-transform: uppercase;">
            </div>
            <div class="form-group">
              <label>Danh Mục *</label>
              <select name="loai_set_id" class="form-control" required>
                <option value="">-- Chọn danh mục --</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['ten_loai']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Tên Set Omakase *</label>
              <input type="text" name="ten_set" class="form-control" placeholder="Tên set tiếng Việt" required>
            </div>
            <div class="form-group">
              <label>Tên Tiếng Nhật</label>
              <input type="text" name="ten_tieng_nhat" class="form-control" placeholder="VD: 極上 おまかせ">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Giá Niêm Yết (VNĐ) *</label>
              <input type="number" name="gia" class="form-control" placeholder="VD: 3200000" min="10000" step="10000" required>
            </div>
            <div class="form-group">
              <label>Bếp Trưởng Phụ Trách</label>
              <select name="bep_truong_id" class="form-control">
                <option value="">-- Chọn đầu bếp --</option>
                <?php foreach ($chefs as $ch): ?>
                  <option value="<?= $ch['id'] ?>"><?= htmlspecialchars($ch['ho_ten'] . ' (' . $ch['chuc_danh'] . ')') ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Số Món Phục Vụ</label>
              <input type="number" name="so_mon" class="form-control" value="14" min="1">
            </div>
            <div class="form-group">
              <label>Thời Lượng (Phút)</label>
              <input type="number" name="thoi_luong_phut" class="form-control" value="120" min="15">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Nhãn (Badge)</label>
              <input type="text" name="nhan" class="form-control" placeholder="VD: SIGNATURE, CHEF'S CHOICE...">
            </div>
            <div class="form-group">
              <label>Trạng Thái</label>
              <select name="trang_thai" class="form-control">
                <option value="Đang bán">Đang bán</option>
                <option value="Tạm ngừng">Tạm ngừng</option>
                <option value="Ngừng bán">Ngừng bán</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Đường Dẫn Hình Ảnh</label>
            <input type="text" name="hinh_anh" class="form-control" placeholder="https://... hoặc images/sakura.jpg">
          </div>

          <div class="form-group">
            <label>Mô Tả Ngắn</label>
            <input type="text" name="mo_ta_ngan" class="form-control" placeholder="Mô tả tóm tắt...">
          </div>

          <div class="form-group">
            <label>Mô Tả Chi Tiết</label>
            <textarea name="mo_ta_chi_tiet" class="form-control" rows="3" placeholder="Chi tiết món ăn..."></textarea>
          </div>

          <div style="display: flex; align-items: center; gap: 8px;">
            <input type="checkbox" id="add_noi_bat" name="noi_bat" value="1">
            <label for="add_noi_bat" style="cursor: pointer; color: #fff;">Món nổi bật (Hiển thị trang chủ)</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('modalAdd')">Hủy</button>
          <button type="submit" class="btn btn-primary">Lưu Sản Phẩm</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ============ MODAL SỬA SẢN PHẨM ============ -->
  <div class="modal-overlay" id="modalEdit">
    <div class="modal-dialog">
      <div class="modal-header">
        <h3>Chỉnh Sửa Set Omakase</h3>
        <button type="button" class="modal-close" onclick="closeModal('modalEdit')">&times;</button>
      </div>
      <form method="POST" action="sanpham.php">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">

        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label>Mã Set *</label>
              <input type="text" name="ma_set" id="edit_ma_set" class="form-control" required style="text-transform: uppercase;">
            </div>
            <div class="form-group">
              <label>Danh Mục *</label>
              <select name="loai_set_id" id="edit_loai_set_id" class="form-control" required>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['ten_loai']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Tên Set Omakase *</label>
              <input type="text" name="ten_set" id="edit_ten_set" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Tên Tiếng Nhật</label>
              <input type="text" name="ten_tieng_nhat" id="edit_ten_tieng_nhat" class="form-control">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Giá Niêm Yết (VNĐ) *</label>
              <input type="number" name="gia" id="edit_gia" class="form-control" min="10000" step="10000" required>
            </div>
            <div class="form-group">
              <label>Bếp Trưởng Phụ Trách</label>
              <select name="bep_truong_id" id="edit_bep_truong_id" class="form-control">
                <option value="">-- Chọn đầu bếp --</option>
                <?php foreach ($chefs as $ch): ?>
                  <option value="<?= $ch['id'] ?>"><?= htmlspecialchars($ch['ho_ten'] . ' (' . $ch['chuc_danh'] . ')') ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Số Món Phục Vụ</label>
              <input type="number" name="so_mon" id="edit_so_mon" class="form-control" min="1">
            </div>
            <div class="form-group">
              <label>Thời Lượng (Phút)</label>
              <input type="number" name="thoi_luong_phut" id="edit_thoi_luong_phut" class="form-control" min="15">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Nhãn (Badge)</label>
              <input type="text" name="nhan" id="edit_nhan" class="form-control">
            </div>
            <div class="form-group">
              <label>Trạng Thái</label>
              <select name="trang_thai" id="edit_trang_thai" class="form-control">
                <option value="Đang bán">Đang bán</option>
                <option value="Tạm ngừng">Tạm ngừng</option>
                <option value="Ngừng bán">Ngừng bán</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Đường Dẫn Hình Ảnh</label>
            <input type="text" name="hinh_anh" id="edit_hinh_anh" class="form-control">
          </div>

          <div class="form-group">
            <label>Mô Tả Ngắn</label>
            <input type="text" name="mo_ta_ngan" id="edit_mo_ta_ngan" class="form-control">
          </div>

          <div class="form-group">
            <label>Mô Tả Chi Tiết</label>
            <textarea name="mo_ta_chi_tiet" id="edit_mo_ta_chi_tiet" class="form-control" rows="3"></textarea>
          </div>

          <div style="display: flex; align-items: center; gap: 8px;">
            <input type="checkbox" id="edit_noi_bat" name="noi_bat" value="1">
            <label for="edit_noi_bat" style="cursor: pointer; color: #fff;">Món nổi bật (Hiển thị trang chủ)</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('modalEdit')">Hủy</button>
          <button type="submit" class="btn btn-primary">Cập Nhật</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openModal(id) { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }

    // Đổ dữ liệu vào modal sửa
    function openEdit(p) {
      document.getElementById('edit_id').value = p.id;
      document.getElementById('edit_ma_set').value = p.ma_set || '';
      document.getElementById('edit_ten_set').value = p.ten_set || '';
      document.getElementById('edit_ten_tieng_nhat').value = p.ten_tieng_nhat || '';
      document.getElementById('edit_loai_set_id').value = p.loai_set_id || '';
      document.getElementById('edit_bep_truong_id').value = p.bep_truong_id || '';
      document.getElementById('edit_gia').value = parseInt(p.gia) || 0;
      document.getElementById('edit_so_mon').value = p.so_mon || 10;
      document.getElementById('edit_thoi_luong_phut').value = p.thoi_luong_phut || 90;
      document.getElementById('edit_nhan').value = p.nhan || '';
      document.getElementById('edit_trang_thai').value = p.trang_thai || 'Đang bán';
      document.getElementById('edit_hinh_anh').value = p.hinh_anh || '';
      document.getElementById('edit_mo_ta_ngan').value = p.mo_ta_ngan || '';
      document.getElementById('edit_mo_ta_chi_tiet').value = p.mo_ta_chi_tiet || '';
      document.getElementById('edit_noi_bat').checked = (p.noi_bat == 1);
      openModal('modalEdit');
    }
  </script>
</body>
</html>