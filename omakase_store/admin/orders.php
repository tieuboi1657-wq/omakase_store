<?php
$page_title = 'Quản Lý Đơn Đặt Bàn';
require_once __DIR__ . '/includes/header.php';

// Xử lý Cập nhật trạng thái / Gán bàn
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_order') {
        $order_id      = (int)($_POST['order_id'] ?? 0);
        $trang_thai    = $_POST['trang_thai'] ?? 'Chờ xác nhận';
        $trang_thai_tt = $_POST['trang_thai_thanh_toan'] ?? 'Chưa thanh toán';
        $ban_id        = !empty($_POST['ban_id']) ? (int)$_POST['ban_id'] : null;
        $ghi_chu       = trim($_POST['ghi_chu'] ?? '');

        $upd = $pdo->prepare("
            UPDATE don_dat_ban SET 
                trang_thai = ?, 
                trang_thai_thanh_toan = ?, 
                ban_id = ?, 
                ghi_chu = ?
            WHERE id = ?
        ");
        $upd->execute([$trang_thai, $trang_thai_tt, $ban_id, $ghi_chu, $order_id]);
        set_flash('ok', "Đã cập nhật đơn đặt bàn #$order_id thành công!");
        header("Location: orders.php" . (isset($_GET['view_id']) ? "?view_id=$order_id" : ''));
        exit;
    }

    if ($action === 'create_order') {
        $ten_lien_he     = trim($_POST['ten_lien_he'] ?? '');
        $sdt_lien_he     = trim($_POST['sdt_lien_he'] ?? '');
        $email_lien_he   = trim($_POST['email_lien_he'] ?? '');
        $ngay_dung       = $_POST['ngay_dung'] ?? date('Y-m-d');
        $gio_dung        = $_POST['gio_dung'] ?? '19:00:00';
        $so_khach        = (int)($_POST['so_khach'] ?? 2);
        $set_id          = (int)($_POST['set_id'] ?? 1);
        $ban_id          = !empty($_POST['ban_id']) ? (int)$_POST['ban_id'] : null;
        $phuong_thuc_tt  = $_POST['phuong_thuc_tt'] ?? 'Thẻ';
        $trang_thai      = $_POST['trang_thai'] ?? 'Chờ xác nhận';
        $ghi_chu         = trim($_POST['ghi_chu'] ?? '');

        if (empty($ten_lien_he) || empty($sdt_lien_he)) {
            set_flash('err', 'Vui lòng nhập họ tên và số điện thoại liên hệ.');
        } else {
            // Lấy giá của set được chọn
            $s_stmt = $pdo->prepare("SELECT gia FROM set_omakase WHERE id = ?");
            $s_stmt->execute([$set_id]);
            $unit_price = (float)$s_stmt->fetchColumn();
            $tong_tien  = $unit_price * $so_khach;
            $ma_don     = 'SB' . date('ymd') . rand(100, 999);

            $ins = $pdo->prepare("
                INSERT INTO don_dat_ban (
                    ma_don, ten_lien_he, sdt_lien_he, email_lien_he, ngay_dung, gio_dung,
                    so_khach, tam_tinh, tong_tien, phuong_thuc_tt, trang_thai, ban_id, ghi_chu
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([
                $ma_don, $ten_lien_he, $sdt_lien_he, $email_lien_he, $ngay_dung, $gio_dung,
                $so_khach, $tong_tien, $tong_tien, $phuong_thuc_tt, $trang_thai, $ban_id, $ghi_chu
            ]);
            $new_don_id = $pdo->lastInsertId();

            // Thêm chi tiết đơn
            $ins_ct = $pdo->prepare("
                INSERT INTO chi_tiet_don (don_id, set_id, so_luong, don_gia)
                VALUES (?, ?, ?, ?)
            ");
            $ins_ct->execute([$new_don_id, $set_id, $so_khach, $unit_price]);

            set_flash('ok', "Đã tạo thành công đơn đặt bàn mới: #$ma_don!");
        }
        header("Location: orders.php");
        exit;
    }
}

// Lấy danh sách bàn ăn
$tables = $pdo->query("SELECT id, ma_ban, ten_ban, khu_vuc, suc_chua, trang_thai FROM ban ORDER BY khu_vuc, ma_ban")->fetchAll();

// Lấy danh sách set để tạo đơn thủ công
$sets = $pdo->query("SELECT id, ten_set, gia FROM set_omakase WHERE trang_thai = 'Đang bán' ORDER BY gia ASC")->fetchAll();

// Chi tiết một đơn (nếu có tham số view_id)
$view_id = isset($_GET['view_id']) ? (int)$_GET['view_id'] : null;
$view_order = null;
$view_details = [];

if ($view_id) {
    $stmt_single = $pdo->prepare("
        SELECT d.*, k.ho_ten AS ten_khach_hang, k.email AS email_khach_hang, k.hang_thanh_vien, b.ten_ban, b.khu_vuc
        FROM don_dat_ban d
        LEFT JOIN khach_hang k ON d.khach_hang_id = k.id
        LEFT JOIN ban b ON d.ban_id = b.id
        WHERE d.id = ?
    ");
    $stmt_single->execute([$view_id]);
    $view_order = $stmt_single->fetch();

    if ($view_order) {
        $stmt_items = $pdo->prepare("
            SELECT c.*, s.ten_set, s.ma_set, s.hinh_anh
            FROM chi_tiet_don c
            JOIN set_omakase s ON c.set_id = s.id
            WHERE c.don_id = ?
        ");
        $stmt_items->execute([$view_id]);
        $view_details = $stmt_items->fetchAll();
    }
}

// Bộ lọc & Tìm kiếm danh sách đơn
$q           = trim($_GET['q'] ?? '');
$filter_stat = trim($_GET['status'] ?? '');
$filter_date = trim($_GET['date'] ?? '');

$sql = "
    SELECT d.*, k.ho_ten AS ten_khach_hang, b.ten_ban
    FROM don_dat_ban d
    LEFT JOIN khach_hang k ON d.khach_hang_id = k.id
    LEFT JOIN ban b ON d.ban_id = b.id
    WHERE 1=1
";
$params = [];

if ($q !== '') {
    $sql .= " AND (d.ma_don LIKE ? OR d.ten_lien_he LIKE ? OR d.sdt_lien_he LIKE ? OR k.ho_ten LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($filter_stat !== '') {
    $sql .= " AND d.trang_thai = ?";
    $params[] = $filter_stat;
}
if ($filter_date !== '') {
    $sql .= " AND d.ngay_dung = ?";
    $params[] = $filter_date;
}
$sql .= " ORDER BY d.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div class="head">
  <div>
    <h1>Quản Lý Đơn & Lịch Đặt Bàn</h1>
    <p>Theo dõi lịch đặt tiệc Omakase, xếp bàn quầy bar/VIP và kiểm soát doanh thu.</p>
  </div>
  <div class="head-actions">
    <button type="button" class="btn ghost" onclick="exportTableToCSV('ordersTable', 'danh-sach-don-dat-ban.csv')">
      <i class="fa-solid fa-file-csv"></i> Xuất CSV
    </button>
    <button type="button" class="btn" onclick="openCreateOrderModal()">
      <i class="fa-solid fa-plus"></i> Tạo Đơn Đặt Bàn Mới
    </button>
  </div>
</div>

<?php if ($view_order): ?>
  <!-- KHUNG XEM & CẬP NHẬT CHI TIẾT ĐƠN HÀNG -->
  <div class="panel" style="border-left: 4px solid var(--gold);margin-bottom:24px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
      <div>
        <h2 style="margin:0 0 4px 0;font-size:20px;">
          Chi Tiết Đơn Đặt Bàn: #<?= htmlspecialchars($view_order['ma_don']) ?>
        </h2>
        <div class="sub">Ngày tạo đơn: <?= date('d/m/Y H:i', strtotime($view_order['ngay_tao'])) ?></div>
      </div>
      <div>
        <a href="orders.php" class="btn ghost sm"><i class="fa-solid fa-xmark"></i> Đóng chi tiết</a>
      </div>
    </div>

    <div class="grid2">
      <!-- Thông tin liên hệ & Thời gian -->
      <div style="background:#13100e;padding:16px;border:1px solid var(--line);border-radius:4px;">
        <h3 style="font-size:14px;color:var(--gold);margin-bottom:10px;"><i class="fa-solid fa-user"></i> Thông tin thượng khách</h3>
        <p><b>Họ tên:</b> <?= htmlspecialchars($view_order['ten_lien_he'] ?: $view_order['ten_khach_hang']) ?></p>
        <p><b>Số điện thoại:</b> <?= htmlspecialchars($view_order['sdt_lien_he']) ?></p>
        <p><b>Email:</b> <?= htmlspecialchars($view_order['email_lien_he'] ?: 'Chưa cung cấp') ?></p>
        <p><b>Hạng hội viên:</b> <span class="badge b-gold"><?= htmlspecialchars($view_order['hang_thanh_vien'] ?? 'Thành viên') ?></span></p>
        <hr style="border:none;border-top:1px solid var(--line);margin:12px 0;">
        <p><b>Lịch dùng bữa:</b> <?= date('d/m/Y', strtotime($view_order['ngay_dung'])) ?> vào lúc <b><?= substr($view_order['gio_dung'], 0, 5) ?></b></p>
        <p><b>Số lượng khách:</b> <?= (int)$view_order['so_khach'] ?> vị khách</p>
        <p><b>Bàn chỉ định:</b> <?= htmlspecialchars($view_order['ten_ban'] ? $view_order['ten_ban'] . ' (' . $view_order['khu_vuc'] . ')' : 'Chưa xếp bàn') ?></p>
      </div>

      <!-- Form cập nhật trạng thái & bàn -->
      <div style="background:#13100e;padding:16px;border:1px solid var(--line);border-radius:4px;">
        <h3 style="font-size:14px;color:var(--gold);margin-bottom:10px;"><i class="fa-solid fa-sliders"></i> Xử lý đơn & Xếp bàn</h3>
        <form action="orders.php?view_id=<?= $view_order['id'] ?>" method="POST">
          <input type="hidden" name="action" value="update_order">
          <input type="hidden" name="order_id" value="<?= $view_order['id'] ?>">

          <div class="two">
            <div>
              <label style="font-size:11px;color:var(--muted);text-transform:uppercase;">Trạng thái đơn</label>
              <select name="trang_thai" style="width:100%;margin-top:4px;">
                <option value="Chờ xác nhận" <?= $view_order['trang_thai'] === 'Chờ xác nhận' ? 'selected' : '' ?>>Chờ xác nhận</option>
                <option value="Đã xác nhận" <?= $view_order['trang_thai'] === 'Đã xác nhận' ? 'selected' : '' ?>>Đã xác nhận</option>
                <option value="Hoàn tất" <?= $view_order['trang_thai'] === 'Hoàn tất' ? 'selected' : '' ?>>Hoàn tất</option>
                <option value="Đã hủy" <?= $view_order['trang_thai'] === 'Đã hủy' ? 'selected' : '' ?>>Đã hủy</option>
              </select>
            </div>
            <div>
              <label style="font-size:11px;color:var(--muted);text-transform:uppercase;">Thanh toán</label>
              <select name="trang_thai_thanh_toan" style="width:100%;margin-top:4px;">
                <option value="Chưa thanh toán" <?= $view_order['trang_thai_thanh_toan'] === 'Chưa thanh toán' ? 'selected' : '' ?>>Chưa thanh toán</option>
                <option value="Đã cọc" <?= $view_order['trang_thai_thanh_toan'] === 'Đã cọc' ? 'selected' : '' ?>>Đã cọc</option>
                <option value="Đã thanh toán" <?= $view_order['trang_thai_thanh_toan'] === 'Đã thanh toán' ? 'selected' : '' ?>>Đã thanh toán</option>
                <option value="Đã hoàn cọc" <?= $view_order['trang_thai_thanh_toan'] === 'Đã hoàn cọc' ? 'selected' : '' ?>>Đã hoàn cọc</option>
                <option value="Miễn cọc" <?= $view_order['trang_thai_thanh_toan'] === 'Miễn cọc' ? 'selected' : '' ?>>Miễn cọc</option>
              </select>
            </div>
          </div>

          <label style="font-size:11px;color:var(--muted);text-transform:uppercase;margin-top:10px;display:block;">Phân bàn ăn</label>
          <select name="ban_id" style="width:100%;margin-top:4px;">
            <option value="">-- Chưa chỉ định bàn --</option>
            <?php foreach ($tables as $tb): ?>
              <option value="<?= $tb['id'] ?>" <?= (int)$view_order['ban_id'] === (int)$tb['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($tb['ten_ban']) ?> (<?= htmlspecialchars($tb['khu_vuc']) ?> - Chứa: <?= $tb['suc_chua'] ?> người)
              </option>
            <?php endforeach; ?>
          </select>

          <label style="font-size:11px;color:var(--muted);text-transform:uppercase;margin-top:10px;display:block;">Ghi chú riêng của khách / bếp</label>
          <textarea name="ghi_chu" rows="2" style="width:100%;margin-top:4px;" placeholder="Ghi chú món ăn, dị ứng, yêu cầu đặc biệt..."><?= htmlspecialchars($view_order['ghi_chu'] ?? '') ?></textarea>

          <button type="submit" class="btn sm" style="margin-top:12px;width:100%;justify-content:center;">
            <i class="fa-solid fa-check"></i> Lưu Cập Nhật Đơn Này
          </button>
        </form>
      </div>
    </div>

    <!-- Danh sách món đặt trong đơn -->
    <div style="margin-top:20px;">
      <h3 style="font-size:14px;color:var(--gold);margin-bottom:10px;"><i class="fa-solid fa-utensils"></i> Danh sách set Omakase đặt trước</h3>
      <div class="tw">
        <table>
          <thead>
            <tr>
              <th>Set Món</th>
              <th>Số lượng</th>
              <th>Đơn giá</th>
              <th style="text-align:right;">Thành tiền</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($view_details)): ?>
              <tr><td colspan="4" class="empty">Chưa có chi tiết set món.</td></tr>
            <?php else: ?>
              <?php foreach ($view_details as $item): ?>
                <tr>
                  <td>
                    <b><?= htmlspecialchars($item['ten_set']) ?></b>
                    <span class="sub">(<?= htmlspecialchars($item['ma_set']) ?>)</span>
                  </td>
                  <td><b><?= (int)$item['so_luong'] ?></b></td>
                  <td><?= format_vnd($item['don_gia']) ?></td>
                  <td style="text-align:right;"><b><?= format_vnd($item['thanh_tien']) ?></b></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3" style="text-align:right;font-weight:bold;font-size:14px;">TỔNG CỘNG:</td>
              <td style="text-align:right;font-weight:bold;font-size:16px;color:var(--gold);">
                <?= format_vnd($view_order['tong_tien']) ?>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- DANH SÁCH TẤT CẢ ĐƠN ĐẶT BÀN -->
<div class="panel">
  <form method="GET" action="orders.php" class="tools">
    <input type="text" name="q" placeholder="Tìm theo mã đơn, tên khách, số điện thoại..." value="<?= htmlspecialchars($q) ?>">

    <select name="status" onchange="this.form.submit()">
      <option value="">-- Tất cả trạng thái --</option>
      <option value="Chờ xác nhận" <?= $filter_stat === 'Chờ xác nhận' ? 'selected' : '' ?>>Chờ xác nhận</option>
      <option value="Đã xác nhận" <?= $filter_stat === 'Đã xác nhận' ? 'selected' : '' ?>>Đã xác nhận</option>
      <option value="Hoàn tất" <?= $filter_stat === 'Hoàn tất' ? 'selected' : '' ?>>Hoàn tất</option>
      <option value="Đã hủy" <?= $filter_stat === 'Đã hủy' ? 'selected' : '' ?>>Đã hủy</option>
    </select>

    <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>" onchange="this.form.submit()" title="Lọc theo ngày dùng">

    <button type="submit" class="btn ghost sm"><i class="fa-solid fa-magnifying-glass"></i> Lọc</button>
    <?php if ($q !== '' || $filter_stat !== '' || $filter_date !== ''): ?>
      <a href="orders.php" class="btn ghost sm" style="color:var(--red2);"><i class="fa-solid fa-rotate-left"></i> Đặt lại</a>
    <?php endif; ?>
    <span class="sub" style="margin-left:auto;"><?= count($orders) ?> đơn đặt</span>
  </form>

  <div class="tw">
    <table id="ordersTable">
      <thead>
        <tr>
          <th>Mã đơn</th>
          <th>Khách hàng</th>
          <th>Lịch hẹn</th>
          <th>Khách</th>
          <th>Tổng tiền</th>
          <th>Thanh toán</th>
          <th>Bàn</th>
          <th>Trạng thái</th>
          <th class="no-export" style="text-align:right;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr><td colspan="9" class="empty">Không tìm thấy đơn đặt bàn nào.</td></tr>
        <?php else: ?>
          <?php foreach ($orders as $o): 
            $st_badge = [
              'Chờ xác nhận' => 'b-warn',
              'Đã xác nhận'  => 'b-gold',
              'Hoàn tất'      => 'b-ok',
              'Đã hủy'       => 'b-bad'
            ][$o['trang_thai']] ?? 'b-mute';
          ?>
            <tr style="<?= $view_id === (int)$o['id'] ? 'background:rgba(201,169,97,0.1);' : '' ?>">
              <td><b>#<?= htmlspecialchars($o['ma_don']) ?></b></td>
              <td>
                <div style="font-weight:600;"><?= htmlspecialchars($o['ten_lien_he'] ?: $o['ten_khach_hang']) ?></div>
                <div class="sub"><?= htmlspecialchars($o['sdt_lien_he']) ?></div>
              </td>
              <td>
                <div><?= date('d/m/Y', strtotime($o['ngay_dung'])) ?></div>
                <div class="sub"><?= substr($o['gio_dung'], 0, 5) ?></div>
              </td>
              <td><b><?= (int)$o['so_khach'] ?></b></td>
              <td><b><?= format_vnd($o['tong_tien']) ?></b></td>
              <td>
                <div><?= htmlspecialchars($o['phuong_thuc_tt']) ?></div>
                <div class="sub"><?= htmlspecialchars($o['trang_thai_thanh_toan']) ?></div>
              </td>
              <td><?= htmlspecialchars($o['ten_ban'] ?? 'Chưa xếp') ?></td>
              <td>
                <span class="badge <?= $st_badge ?>"><?= htmlspecialchars($o['trang_thai']) ?></span>
              </td>
              <td class="no-export" style="text-align:right;white-space:nowrap;">
                <a href="orders.php?view_id=<?= $o['id'] ?>" class="btn ghost sm">
                  <i class="fa-solid fa-eye"></i> <?= $view_id === (int)$o['id'] ? 'Đang xem' : 'Chi tiết' ?>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const setsList = <?= json_encode($sets) ?>;
const tablesList = <?= json_encode($tables) ?>;

function openCreateOrderModal() {
  let setOpts = setsList.map(s => `
    <option value="${s.id}">${s.ten_set} (${new Intl.NumberFormat('vi-VN').format(s.gia)}đ)</option>
  `).join('');

  let tableOpts = `<option value="">-- Chưa xếp bàn --</option>` + 
    tablesList.map(t => `
      <option value="${t.id}">${t.ten_ban} (${t.khu_vuc} - Chứa ${t.suc_chua} người)</option>
    `).join('');

  const html = `
    <h2><i class="fa-solid fa-plus" style="color:var(--gold);"></i> Tạo Đơn Đặt Bàn Thủ Công</h2>
    <form action="orders.php" method="POST">
      <input type="hidden" name="action" value="create_order">
      
      <div class="two">
        <div>
          <label>Tên khách hàng *</label>
          <input type="text" name="ten_lien_he" required placeholder="Nguyễn Văn A">
        </div>
        <div>
          <label>Số điện thoại *</label>
          <input type="text" name="sdt_lien_he" required placeholder="0901234567">
        </div>
      </div>

      <label>Email liên hệ</label>
      <input type="email" name="email_lien_he" placeholder="khachhang@domain.com">

      <div class="two">
        <div>
          <label>Ngày dùng bữa *</label>
          <input type="date" name="ngay_dung" required value="<?= date('Y-m-d') ?>">
        </div>
        <div>
          <label>Giờ dùng bữa *</label>
          <select name="gio_dung">
            <option value="11:30:00">11:30 (Trưa)</option>
            <option value="12:30:00">12:30 (Trưa)</option>
            <option value="18:00:00">18:00 (Tối)</option>
            <option value="19:00:00" selected>19:00 (Tối)</option>
            <option value="20:00:00">20:00 (Tối)</option>
          </select>
        </div>
      </div>

      <div class="two">
        <div>
          <label>Gói Set Omakase *</label>
          <select name="set_id" required>${setOpts}</select>
        </div>
        <div>
          <label>Số lượng khách *</label>
          <input type="number" name="so_khach" min="1" max="15" value="2" required>
        </div>
      </div>

      <div class="two">
        <div>
          <label>Chọn bàn phục vụ</label>
          <select name="ban_id">${tableOpts}</select>
        </div>
        <div>
          <label>Phương thức thanh toán</label>
          <select name="phuong_thuc_tt">
            <option value="Thẻ">Thẻ tín dụng / Ghi nợ</option>
            <option value="Chuyển khoản">Chuyển khoản ngân hàng</option>
            <option value="Tiền mặt">Tiền mặt tại quầy</option>
            <option value="Ví điện tử">Ví điện tử</option>
          </select>
        </div>
      </div>

      <label>Trạng thái ban đầu</label>
      <select name="trang_thai">
        <option value="Chờ xác nhận">Chờ xác nhận</option>
        <option value="Đã xác nhận" selected>Đã xác nhận</option>
      </select>

      <label>Ghi chú (Dị ứng, khẩu vị đặc biệt)</label>
      <textarea name="ghi_chu" rows="2" placeholder="Ghi chú khẩu vị..."></textarea>

      <div class="acts">
        <button type="button" class="btn ghost" onclick="closeModal()">Hủy</button>
        <button type="submit" class="btn"><i class="fa-solid fa-floppy-disk"></i> Tạo Đơn Ngay</button>
      </div>
    </form>
  `;
  openModal(html);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
