<?php
$page_title = 'Nhân Sự & Lịch Làm Việc';
require_once __DIR__ . '/includes/header.php';

// Xử lý Thêm nhân viên mới
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $ho_ten        = trim($_POST['ho_ten'] ?? '');
        $so_dien_thoai = trim($_POST['so_dien_thoai'] ?? '');
        $email         = trim($_POST['email'] ?? '');
        $bo_phan       = $_POST['bo_phan'] ?? 'Phục vụ';
        $chuc_danh     = trim($_POST['chuc_danh'] ?? 'Nhân viên');
        $chuyen_mon    = trim($_POST['chuyen_mon'] ?? '');

        if (empty($ho_ten) || empty($so_dien_thoai)) {
            set_flash('err', 'Họ tên và số điện thoại nhân viên là bắt buộc.');
        } else {
            $prefix = ['Quản lý' => 'QL', 'Phục vụ' => 'PV', 'Đầu bếp' => 'DB', 'Tạp vụ' => 'TV'][$bo_phan] ?? 'NV';
            $max_id = (int)$pdo->query("SELECT MAX(id) FROM nhan_vien")->fetchColumn() + 1;
            $ma_nv  = $prefix . $max_id;

            $ins = $pdo->prepare("
                INSERT INTO nhan_vien (ma_nv, ho_ten, email, so_dien_thoai, bo_phan, chuc_danh, chuyen_mon, ngay_vao_lam, trang_thai)
                VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), 'Hoạt động')
            ");
            $ins->execute([$ma_nv, $ho_ten, $email, $so_dien_thoai, $bo_phan, $chuc_danh, $chuyen_mon]);
            set_flash('ok', "Đã thêm nhân sự mới: $ho_ten ($ma_nv) thành công!");
        }
        header("Location: staff.php");
        exit;
    }
}

// Lấy danh sách nhân viên
$staff_stmt = $pdo->query("SELECT * FROM nhan_vien ORDER BY FIELD(bo_phan, 'Quản lý', 'Đầu bếp', 'Phục vụ', 'Tạp vụ'), id ASC");
$staff_list = $staff_stmt->fetchAll();

$dept_counts = [
    'Quản lý' => 0,
    'Đầu bếp' => 0,
    'Phục vụ' => 0,
    'Tạp vụ'  => 0,
];
foreach ($staff_list as $s) {
    if (isset($dept_counts[$s['bo_phan']])) {
        $dept_counts[$s['bo_phan']]++;
    }
}
?>

<div class="head">
  <div>
    <h1>Quản Lý Nhân Sự & Lịch Làm Việc</h1>
    <p>Danh sách nghệ nhân đầu bếp, nhân viên phục vụ và điều phối lịch ca làm việc.</p>
  </div>
  <div class="head-actions">
    <button type="button" class="btn ghost" onclick="exportTableToCSV('staffTable', 'danh-sach-nhan-vien.csv')">
      <i class="fa-solid fa-file-csv"></i> Xuất CSV
    </button>
    <button type="button" class="btn" onclick="openAddStaffModal()">
      <i class="fa-solid fa-user-plus"></i> Thêm Nhân Sự Mới
    </button>
  </div>
</div>

<div class="kpis">
  <div class="kpi">
    <div class="l">Tổng nhân sự</div>
    <div class="v"><?= count($staff_list) ?></div>
    <div class="d">Toàn bộ chi nhánh</div>
  </div>
  <div class="kpi">
    <div class="l">Bếp Omakase</div>
    <div class="v" style="color:var(--gold);"><?= $dept_counts['Đầu bếp'] ?></div>
    <div class="d">Bếp trưởng, phó & sushi chef</div>
  </div>
  <div class="kpi">
    <div class="l">Tổ phục vụ quầy</div>
    <div class="v"><?= $dept_counts['Phục vụ'] ?></div>
    <div class="d">Trưởng ca & nhân viên</div>
  </div>
  <div class="kpi">
    <div class="l">Tạp vụ & Quản lý</div>
    <div class="v"><?= $dept_counts['Tạp vụ'] + $dept_counts['Quản lý'] ?></div>
    <div class="d">Vệ sinh kho & điều hành</div>
  </div>
</div>

<div class="panel">
  <h2><span><i class="fa-solid fa-address-book" style="color:var(--gold);"></i> Danh sách nhân viên nhà hàng</span></h2>
  <div class="tw">
    <table id="staffTable">
      <thead>
        <tr>
          <th>Mã NV</th>
          <th>Họ Tên</th>
          <th>Bộ Phận</th>
          <th>Chức Danh</th>
          <th>Chuyên Môn / Vai Trò</th>
          <th>Liên Hệ</th>
          <th>Trạng Thái</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($staff_list as $s): ?>
          <tr>
            <td><code><?= htmlspecialchars($s['ma_nv']) ?></code></td>
            <td>
              <b style="font-size:14px;"><?= htmlspecialchars($s['ho_ten']) ?></b>
            </td>
            <td>
              <?php 
                $b_cls = $s['bo_phan'] === 'Đầu bếp' ? 'b-gold' : ($s['bo_phan'] === 'Phục vụ' ? 'b-ok' : 'b-mute');
              ?>
              <span class="badge <?= $b_cls ?>"><?= htmlspecialchars($s['bo_phan']) ?></span>
            </td>
            <td><?= htmlspecialchars($s['chuc_danh']) ?></td>
            <td><?= htmlspecialchars($s['chuyen_mon'] ?: '—') ?></td>
            <td>
              <div><i class="fa-solid fa-phone" style="font-size:11px;color:var(--muted);"></i> <?= htmlspecialchars($s['so_dien_thoai']) ?></div>
              <?php if (!empty($s['email'])): ?>
                <div class="sub"><?= htmlspecialchars($s['email']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge <?= $s['trang_thai'] === 'Hoạt động' ? 'b-ok' : 'b-warn' ?>">
                <?= htmlspecialchars($s['trang_thai']) ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function openAddStaffModal() {
  const html = `
    <h2><i class="fa-solid fa-user-plus" style="color:var(--gold);"></i> Thêm Nhân Viên Mới</h2>
    <form action="staff.php" method="POST">
      <input type="hidden" name="action" value="add">
      <label>Họ và tên nhân sự *</label>
      <input type="text" name="ho_ten" required placeholder="VD: Nguyễn Văn Tài">

      <div class="two">
        <div>
          <label>Số điện thoại *</label>
          <input type="text" name="so_dien_thoai" required placeholder="0901234567">
        </div>
        <div>
          <label>Email nội bộ</label>
          <input type="email" name="email" placeholder="nv@sabiomakase.vn">
        </div>
      </div>

      <div class="two">
        <div>
          <label>Bộ phận công tác *</label>
          <select name="bo_phan">
            <option value="Đầu bếp">Đầu bếp</option>
            <option value="Phục vụ" selected>Phục vụ</option>
            <option value="Quản lý">Quản lý</option>
            <option value="Tạp vụ">Tạp vụ</option>
          </select>
        </div>
        <div>
          <label>Chức danh công việc</label>
          <input type="text" name="chuc_danh" value="Phục vụ bàn" placeholder="VD: Đầu bếp sushi, Trưởng ca">
        </div>
      </div>

      <label>Chuyên môn / Ghi chú</label>
      <input type="text" name="chuyen_mon" placeholder="VD: Master Edomae, Sake Sommelier...">

      <div class="acts">
        <button type="button" class="btn ghost" onclick="closeModal()">Hủy</button>
        <button type="submit" class="btn"><i class="fa-solid fa-floppy-disk"></i> Lưu Hồ Sơ</button>
      </div>
    </form>
  `;
  openModal(html);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
