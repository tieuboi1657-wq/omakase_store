<?php
$page_title = 'Quản Lý Khách Hàng';
require_once __DIR__ . '/includes/header.php';

// Xử lý Thêm / Sửa / Khóa khách hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $ho_ten          = trim($_POST['ho_ten'] ?? '');
        $so_dien_thoai   = trim($_POST['so_dien_thoai'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $hang_thanh_vien = $_POST['hang_thanh_vien'] ?? 'Thành viên';
        $diem_tich_luy   = (int)($_POST['diem_tich_luy'] ?? 0);
        $mat_khau_raw    = $_POST['mat_khau'] ?? 'password';

        if (empty($ho_ten) || empty($so_dien_thoai)) {
            set_flash('err', 'Họ tên và số điện thoại là bắt buộc.');
        } else {
            // Kiểm tra trùng SĐT
            $chk = $pdo->prepare("SELECT id FROM khach_hang WHERE so_dien_thoai = ?");
            $chk->execute([$so_dien_thoai]);
            if ($chk->fetch()) {
                set_flash('err', "Số điện thoại $so_dien_thoai đã được đăng ký trước đó.");
            } else {
                $max_id = (int)$pdo->query("SELECT MAX(id) FROM khach_hang")->fetchColumn() + 1;
                $ma_kh  = 'KH' . str_pad($max_id + 100, 3, '0', STR_PAD_LEFT);
                $hashed = password_hash($mat_khau_raw, PASSWORD_BCRYPT);

                $ins = $pdo->prepare("
                    INSERT INTO khach_hang (ma_kh, ho_ten, email, so_dien_thoai, mat_khau, hang_thanh_vien, diem_tich_luy, trang_thai)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Hoạt động')
                ");
                $ins->execute([$ma_kh, $ho_ten, $email, $so_dien_thoai, $hashed, $hang_thanh_vien, $diem_tich_luy]);
                set_flash('ok', "Đã thêm thượng khách mới: $ho_ten ($ma_kh) thành công!");
            }
        }
        header("Location: customers.php");
        exit;
    }

    if ($action === 'edit') {
        $id              = (int)($_POST['id'] ?? 0);
        $ho_ten          = trim($_POST['ho_ten'] ?? '');
        $so_dien_thoai   = trim($_POST['so_dien_thoai'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $hang_thanh_vien = $_POST['hang_thanh_vien'] ?? 'Thành viên';
        $diem_tich_luy   = (int)($_POST['diem_tich_luy'] ?? 0);
        $trang_thai      = $_POST['trang_thai'] ?? 'Hoạt động';

        if (empty($ho_ten) || empty($so_dien_thoai)) {
            set_flash('err', 'Họ tên và số điện thoại không được để trống.');
        } else {
            $upd = $pdo->prepare("
                UPDATE khach_hang SET 
                    ho_ten = ?, email = ?, so_dien_thoai = ?, 
                    hang_thanh_vien = ?, diem_tich_luy = ?, trang_thai = ?
                WHERE id = ?
            ");
            $upd->execute([$ho_ten, $email, $so_dien_thoai, $hang_thanh_vien, $diem_tich_luy, $trang_thai, $id]);
            set_flash('ok', "Đã cập nhật thông tin khách hàng $ho_ten thành công!");
        }
        header("Location: customers.php");
        exit;
    }

    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $curr = $pdo->prepare("SELECT trang_thai, ho_ten FROM khach_hang WHERE id = ?");
        $curr->execute([$id]);
        $row = $curr->fetch();
        if ($row) {
            $new_st = ($row['trang_thai'] === 'Hoạt động') ? 'Khóa' : 'Hoạt động';
            $pdo->prepare("UPDATE khach_hang SET trang_thai = ? WHERE id = ?")->execute([$new_st, $id]);
            set_flash('ok', "Đã chuyển trạng thái tài khoản {$row['ho_ten']} sang: $new_st.");
        }
        header("Location: customers.php");
        exit;
    }
}

// Bộ lọc & Tìm kiếm
$q           = trim($_GET['q'] ?? '');
$filter_tier = trim($_GET['tier'] ?? '');
$filter_stat = trim($_GET['status'] ?? '');

$sql = "
    SELECT k.*, 
           COUNT(d.id) AS so_don, 
           COALESCE(SUM(CASE WHEN d.trang_thai = 'Hoàn tất' THEN d.tong_tien ELSE 0 END), 0) AS da_chi_tieu
    FROM khach_hang k
    LEFT JOIN don_dat_ban d ON k.id = d.khach_hang_id
    WHERE 1=1
";
$params = [];

if ($q !== '') {
    $sql .= " AND (k.ma_kh LIKE ? OR k.ho_ten LIKE ? OR k.so_dien_thoai LIKE ? OR k.email LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($filter_tier !== '') {
    $sql .= " AND k.hang_thanh_vien = ?";
    $params[] = $filter_tier;
}
if ($filter_stat !== '') {
    $sql .= " AND k.trang_thai = ?";
    $params[] = $filter_stat;
}
$sql .= " GROUP BY k.id ORDER BY k.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<div class="head">
  <div>
    <h1>Quản Lý Khách Hàng & Thượng Khách</h1>
    <p>Hồ sơ hội viên, tích lũy điểm thưởng và lịch sử trải nghiệm tiệc Omakase.</p>
  </div>
  <div class="head-actions">
    <button type="button" class="btn ghost" onclick="exportTableToCSV('custTable', 'danh-sach-khach-hang.csv')">
      <i class="fa-solid fa-file-csv"></i> Xuất CSV
    </button>
    <button type="button" class="btn" onclick="openAddCustModal()">
      <i class="fa-solid fa-user-plus"></i> Thêm Thượng Khách Mới
    </button>
  </div>
</div>

<div class="panel">
  <form method="GET" action="customers.php" class="tools">
    <input type="text" name="q" placeholder="Tìm tên, SĐT, email, mã hội viên..." value="<?= htmlspecialchars($q) ?>">

    <select name="tier" onchange="this.form.submit()">
      <option value="">-- Tất cả hạng thẻ --</option>
      <option value="VIP" <?= $filter_tier === 'VIP' ? 'selected' : '' ?>>Hạng VIP</option>
      <option value="Vàng" <?= $filter_tier === 'Vàng' ? 'selected' : '' ?>>Hạng Vàng</option>
      <option value="Thành viên" <?= $filter_tier === 'Thành viên' ? 'selected' : '' ?>>Thành viên</option>
    </select>

    <select name="status" onchange="this.form.submit()">
      <option value="">-- Tất cả trạng thái --</option>
      <option value="Hoạt động" <?= $filter_stat === 'Hoạt động' ? 'selected' : '' ?>>Hoạt động</option>
      <option value="Khóa" <?= $filter_stat === 'Khóa' ? 'selected' : '' ?>>Đã khóa</option>
    </select>

    <button type="submit" class="btn ghost sm"><i class="fa-solid fa-magnifying-glass"></i> Lọc</button>
    <?php if ($q !== '' || $filter_tier !== '' || $filter_stat !== ''): ?>
      <a href="customers.php" class="btn ghost sm" style="color:var(--red2);"><i class="fa-solid fa-rotate-left"></i> Đặt lại</a>
    <?php endif; ?>
    <span class="sub" style="margin-left:auto;"><?= count($customers) ?> khách hàng</span>
  </form>

  <div class="tw">
    <table id="custTable">
      <thead>
        <tr>
          <th>Mã KH</th>
          <th>Thượng Khách</th>
          <th>Liên Hệ</th>
          <th>Hạng Thẻ</th>
          <th>Điểm Thưởng</th>
          <th>Chi Tiêu Thực Tế</th>
          <th>Trạng Thái</th>
          <th class="no-export" style="text-align:right;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($customers)): ?>
          <tr><td colspan="8" class="empty">Không tìm thấy khách hàng nào.</td></tr>
        <?php else: ?>
          <?php foreach ($customers as $c): 
            $tier_badge = [
              'VIP'       => 'b-gold',
              'Vàng'      => 'b-warn',
              'Thành viên'=> 'b-mute'
            ][$c['hang_thanh_vien']] ?? 'b-mute';
          ?>
            <tr>
              <td><code><?= htmlspecialchars($c['ma_kh']) ?></code></td>
              <td>
                <div style="font-weight:600;font-size:14px;color:var(--text);"><?= htmlspecialchars($c['ho_ten']) ?></div>
                <div class="sub"><?= (int)$c['so_don'] ?> lần đặt tiệc</div>
              </td>
              <td>
                <div><i class="fa-solid fa-phone" style="font-size:11px;color:var(--muted);"></i> <?= htmlspecialchars($c['so_dien_thoai']) ?></div>
                <div class="sub"><i class="fa-solid fa-envelope" style="font-size:11px;color:var(--muted);"></i> <?= htmlspecialchars($c['email'] ?: 'Chưa cập nhật') ?></div>
              </td>
              <td>
                <span class="badge <?= $tier_badge ?>"><?= htmlspecialchars($c['hang_thanh_vien']) ?></span>
              </td>
              <td><b><?= number_format($c['diem_tich_luy'], 0, ',', '.') ?></b> P</td>
              <td>
                <b style="color:var(--gold);"><?= format_vnd($c['da_chi_tieu']) ?></b>
              </td>
              <td>
                <?php if ($c['trang_thai'] === 'Hoạt động'): ?>
                  <span class="badge b-ok"><i class="fa-solid fa-circle" style="font-size:8px;"></i> Hoạt động</span>
                <?php else: ?>
                  <span class="badge b-bad"><i class="fa-solid fa-lock" style="font-size:8px;"></i> Đã khóa</span>
                <?php endif; ?>
              </td>
              <td class="no-export" style="text-align:right;white-space:nowrap;">
                <button type="button" class="btn ghost sm" onclick='openEditCustModal(<?= json_encode($c, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                  <i class="fa-solid fa-pen-to-square"></i> Sửa
                </button>
                <form action="customers.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Chuyển trạng thái hoạt động của <?= htmlspecialchars(addslashes($c['ho_ten'])) ?>?');">
                  <input type="hidden" name="action" value="toggle_status">
                  <input type="hidden" name="id" value="<?= $c['id'] ?>">
                  <button type="submit" class="btn ghost sm <?= $c['trang_thai'] === 'Hoạt động' ? 'danger' : '' ?>" title="<?= $c['trang_thai'] === 'Hoạt động' ? 'Khóa tài khoản' : 'Kích hoạt lại' ?>">
                    <i class="fa-solid <?= $c['trang_thai'] === 'Hoạt động' ? 'fa-lock' : 'fa-lock-open' ?>"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function openAddCustModal() {
  const html = `
    <h2><i class="fa-solid fa-user-plus" style="color:var(--gold);"></i> Thêm Khách Hàng Mới</h2>
    <form action="customers.php" method="POST">
      <input type="hidden" name="action" value="add">
      <label>Họ và tên thượng khách *</label>
      <input type="text" name="ho_ten" required placeholder="VD: Nguyễn Đăng Quang">

      <div class="two">
        <div>
          <label>Số điện thoại *</label>
          <input type="text" name="so_dien_thoai" required placeholder="0901234567">
        </div>
        <div>
          <label>Địa chỉ Email</label>
          <input type="email" name="email" placeholder="khachhang@domain.com">
        </div>
      </div>

      <div class="two">
        <div>
          <label>Hạng thành viên</label>
          <select name="hang_thanh_vien">
            <option value="Thành viên">Thành viên</option>
            <option value="Vàng">Hạng Vàng</option>
            <option value="VIP">Hạng VIP</option>
          </select>
        </div>
        <div>
          <label>Điểm tích lũy khởi tạo</label>
          <input type="number" name="diem_tich_luy" value="50" min="0">
        </div>
      </div>

      <label>Mật khẩu khởi tạo</label>
      <input type="text" name="mat_khau" value="password" placeholder="Mặc định: password">

      <div class="acts">
        <button type="button" class="btn ghost" onclick="closeModal()">Hủy</button>
        <button type="submit" class="btn"><i class="fa-solid fa-floppy-disk"></i> Lưu Hồ Sơ</button>
      </div>
    </form>
  `;
  openModal(html);
}

function openEditCustModal(c) {
  const html = `
    <h2><i class="fa-solid fa-pen-to-square" style="color:var(--gold);"></i> Sửa Hồ Sơ: ${c.ho_ten}</h2>
    <form action="customers.php" method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="${c.id}">
      <label>Họ và tên *</label>
      <input type="text" name="ho_ten" required value="${c.ho_ten || ''}">

      <div class="two">
        <div>
          <label>Số điện thoại *</label>
          <input type="text" name="so_dien_thoai" required value="${c.so_dien_thoai || ''}">
        </div>
        <div>
          <label>Địa chỉ Email</label>
          <input type="email" name="email" value="${c.email || ''}">
        </div>
      </div>

      <div class="two">
        <div>
          <label>Hạng thành viên</label>
          <select name="hang_thanh_vien">
            <option value="Thành viên" ${c.hang_thanh_vien === 'Thành viên' ? 'selected' : ''}>Thành viên</option>
            <option value="Vàng" ${c.hang_thanh_vien === 'Vàng' ? 'selected' : ''}>Hạng Vàng</option>
            <option value="VIP" ${c.hang_thanh_vien === 'VIP' ? 'selected' : ''}>Hạng VIP</option>
          </select>
        </div>
        <div>
          <label>Điểm tích lũy</label>
          <input type="number" name="diem_tich_luy" value="${c.diem_tich_luy || 0}" min="0">
        </div>
      </div>

      <label>Trạng thái tài khoản</label>
      <select name="trang_thai">
        <option value="Hoạt động" ${c.trang_thai === 'Hoạt động' ? 'selected' : ''}>Hoạt động bình thường</option>
        <option value="Khóa" ${c.trang_thai === 'Khóa' ? 'selected' : ''}>Tạm khóa tài khoản</option>
      </select>

      <div class="acts">
        <button type="button" class="btn ghost" onclick="closeModal()">Hủy</button>
        <button type="submit" class="btn"><i class="fa-solid fa-floppy-disk"></i> Cập Nhật</button>
      </div>
    </form>
  `;
  openModal(html);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
