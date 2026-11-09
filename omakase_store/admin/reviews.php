<?php
$page_title = 'Đánh Giá & Phản Hồi';
require_once __DIR__ . '/includes/header.php';

// Xử lý gửi phản hồi / xử lý khiếu nại
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'reply') {
        $id       = (int)($_POST['id'] ?? 0);
        $phan_hoi = trim($_POST['phan_hoi'] ?? '');
        $staff_id = $admin['nhan_vien_id'] ?? 7;

        if (empty($phan_hoi)) {
            set_flash('err', 'Nội dung phản hồi không được để trống.');
        } else {
            $upd = $pdo->prepare("
                UPDATE danh_gia SET 
                    phan_hoi = ?, 
                    nguoi_phan_hoi_id = ?, 
                    ngay_phan_hoi = NOW(),
                    da_xu_ly = 1
                WHERE id = ?
            ");
            $upd->execute([$phan_hoi, $staff_id, $id]);
            set_flash('ok', 'Đã lưu phản hồi cho đánh giá thành công!');
        }
        header("Location: reviews.php");
        exit;
    }

    if ($action === 'toggle_display') {
        $id = (int)($_POST['id'] ?? 0);
        $curr = $pdo->prepare("SELECT hien_thi FROM danh_gia WHERE id = ?");
        $curr->execute([$id]);
        $val = (int)$curr->fetchColumn();
        $new_val = $val === 1 ? 0 : 1;
        $pdo->prepare("UPDATE danh_gia SET hien_thi = ? WHERE id = ?")->execute([$new_val, $id]);
        set_flash('ok', "Đã " . ($new_val === 1 ? 'hiển thị' : 'ẩn') . " đánh giá trên website.");
        header("Location: reviews.php");
        exit;
    }
}

// Bộ lọc
$filter_type  = trim($_GET['type'] ?? '');
$filter_stars = isset($_GET['stars']) && $_GET['stars'] !== '' ? (int)$_GET['stars'] : null;
$filter_reply = trim($_GET['reply'] ?? '');

$sql = "
    SELECT d.*, k.ho_ten AS ten_kh, k.email AS email_kh, s.ten_set, s.ma_set, nv.ho_ten AS ten_nguoi_tl
    FROM danh_gia d
    JOIN khach_hang k ON d.khach_hang_id = k.id
    JOIN set_omakase s ON d.set_id = s.id
    LEFT JOIN nhan_vien nv ON d.nguoi_phan_hoi_id = nv.id
    WHERE 1=1
";
$params = [];

if ($filter_type !== '') {
    $sql .= " AND d.loai = ?";
    $params[] = $filter_type;
}
if ($filter_stars !== null) {
    $sql .= " AND d.so_sao = ?";
    $params[] = $filter_stars;
}
if ($filter_reply === 'yes') {
    $sql .= " AND d.phan_hoi IS NOT NULL AND d.phan_hoi != ''";
} elseif ($filter_reply === 'no') {
    $sql .= " AND (d.phan_hoi IS NULL OR d.phan_hoi = '')";
}

$sql .= " ORDER BY d.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Điểm trung bình và số lượng
$stat_stmt = $pdo->query("
    SELECT 
        ROUND(AVG(so_sao), 1) AS diem_tb,
        COUNT(*) AS tong_dg,
        SUM(CASE WHEN phan_hoi IS NULL OR phan_hoi = '' THEN 1 ELSE 0 END) AS chua_rep,
        SUM(CASE WHEN loai = 'Khiếu nại' AND da_xu_ly = 0 THEN 1 ELSE 0 END) AS khieu_nai_ton
    FROM danh_gia
");
$r_stats = $stat_stmt->fetch();
?>

<div class="head">
  <div>
    <h1>Đánh Giá & Phản Hồi Thượng Khách</h1>
    <p>Lắng nghe cảm nhận của khách hàng và gửi phản hồi đại diện nhà hàng.</p>
  </div>
</div>

<div class="kpis">
  <div class="kpi">
    <div class="l">Điểm đánh giá trung bình</div>
    <div class="v" style="color:var(--gold);"><?= $r_stats['diem_tb'] ?? 5 ?> ★</div>
    <div class="d">Trên tổng <?= $r_stats['tong_dg'] ?? 0 ?> lượt đánh giá</div>
  </div>
  <div class="kpi">
    <div class="l">Chưa có phản hồi</div>
    <div class="v" style="color:var(--warn);"><?= $r_stats['chua_rep'] ?? 0 ?></div>
    <div class="d">Cần chăm sóc khách hàng</div>
  </div>
  <div class="kpi">
    <div class="l">Khiếu nại chưa xử lý</div>
    <div class="v" style="color:var(--red2);"><?= $r_stats['khieu_nai_ton'] ?? 0 ?></div>
    <div class="d">Cần xử lý ưu tiên</div>
  </div>
</div>

<div class="panel">
  <form method="GET" action="reviews.php" class="tools">
    <select name="type" onchange="this.form.submit()">
      <option value="">-- Mọi phân loại --</option>
      <option value="Đánh giá" <?= $filter_type === 'Đánh giá' ? 'selected' : '' ?>>Đánh giá thông thường</option>
      <option value="Khiếu nại" <?= $filter_type === 'Khiếu nại' ? 'selected' : '' ?>>Khiếu nại / Góp ý khẩn</option>
    </select>

    <select name="stars" onchange="this.form.submit()">
      <option value="">-- Mọi số sao --</option>
      <?php for ($s = 5; $s >= 1; $s--): ?>
        <option value="<?= $s ?>" <?= $filter_stars === $s ? 'selected' : '' ?>><?= $s ?> sao (<?= str_repeat('★', $s) ?>)</option>
      <?php endfor; ?>
    </select>

    <select name="reply" onchange="this.form.submit()">
      <option value="">-- Trạng thái phản hồi --</option>
      <option value="no" <?= $filter_reply === 'no' ? 'selected' : '' ?>>Chưa phản hồi</option>
      <option value="yes" <?= $filter_reply === 'yes' ? 'selected' : '' ?>>Đã phản hồi</option>
    </select>

    <?php if ($filter_type !== '' || $filter_stars !== null || $filter_reply !== ''): ?>
      <a href="reviews.php" class="btn ghost sm" style="color:var(--red2);"><i class="fa-solid fa-rotate-left"></i> Đặt lại</a>
    <?php endif; ?>
    <span class="sub" style="margin-left:auto;"><?= count($reviews) ?> đánh giá</span>
  </form>

  <div>
    <?php if (empty($reviews)): ?>
      <div class="empty">Không tìm thấy đánh giá nào phù hợp.</div>
    <?php else: ?>
      <?php foreach ($reviews as $r): ?>
        <div style="background:var(--panel2);border:1px solid var(--line);border-radius:4px;padding:16px;margin-bottom:14px;">
          <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <div>
              <b style="font-size:14px;"><?= htmlspecialchars($r['ten_kh']) ?></b>
              <span class="sub">· Set <?= htmlspecialchars($r['ten_set']) ?> · <?= date('d/m/Y', strtotime($r['ngay_danh_gia'])) ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <span style="color:var(--gold);font-size:15px;letter-spacing:2px;">
                <?= str_repeat('★', (int)$r['so_sao']) ?><?= str_repeat('☆', 5 - (int)$r['so_sao']) ?>
              </span>
              <?php if ($r['loai'] === 'Khiếu nại'): ?>
                <span class="badge <?= $r['da_xu_ly'] ? 'b-ok' : 'b-bad' ?>">
                  Khiếu nại · <?= $r['da_xu_ly'] ? 'Đã giải quyết' : 'Chưa xử lý' ?>
                </span>
              <?php endif; ?>
              <form action="reviews.php" method="POST" style="display:inline;">
                <input type="hidden" name="action" value="toggle_display">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button type="submit" class="btn ghost sm" title="Ẩn hoặc hiện trên website">
                  <?= $r['hien_thi'] ? '<i class="fa-solid fa-eye" style="color:var(--ok);"></i> Đang hiện' : '<i class="fa-solid fa-eye-slash" style="color:var(--muted);"></i> Đã ẩn' ?>
                </button>
              </form>
            </div>
          </div>

          <p style="margin:10px 0 12px;font-size:13.5px;color:#f0ebe5;">
            <?= nl2br(htmlspecialchars($r['noi_dung'] ?: 'Không có nội dung nhận xét.')) ?>
          </p>

          <?php if (!empty($r['phan_hoi'])): ?>
            <div style="margin:10px 0;padding:10px 14px;border-left:3px solid var(--gold);background:#181310;border-radius:0 4px 4px 0;">
              <div class="sub" style="color:var(--gold);margin-bottom:4px;font-size:11px;text-transform:uppercase;">
                <i class="fa-solid fa-reply"></i> Phản hồi từ Sabi Omakase (bởi <?= htmlspecialchars($r['ten_nguoi_tl'] ?? 'Quản lý') ?>):
              </div>
              <div style="font-size:13px;"><?= nl2br(htmlspecialchars($r['phan_hoi'])) ?></div>
            </div>
          <?php endif; ?>

          <form action="reviews.php" method="POST" style="margin-top:10px;">
            <input type="hidden" name="action" value="reply">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <textarea name="phan_hoi" rows="2" style="width:100%;margin-bottom:8px;" placeholder="Viết phản hồi cảm ơn hoặc hướng giải quyết cho thượng khách..."><?= htmlspecialchars($r['phan_hoi'] ?? '') ?></textarea>
            <button type="submit" class="btn sm">
              <i class="fa-solid fa-paper-plane"></i> <?= !empty($r['phan_hoi']) ? 'Cập nhật phản hồi' : 'Gửi phản hồi cho khách' ?>
            </button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
