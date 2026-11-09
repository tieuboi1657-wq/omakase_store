<?php
$page_title = 'Báo Cáo Doanh Thu';
require_once __DIR__ . '/includes/header.php';

// Khoảng thời gian lọc (mặc định 14 ngày)
$days_range = isset($_GET['range']) ? (int)$_GET['range'] : 14;
if ($days_range !== 7 && $days_range !== 14 && $days_range !== 30) {
    $days_range = 14;
}

$date_from = date('Y-m-d', strtotime("-$days_range days"));
$date_to   = date('Y-m-d');

// 1. Tổng doanh thu trong khoảng thời gian
$stmt_sum = $pdo->prepare("
    SELECT 
        COALESCE(SUM(tong_tien), 0) AS tong_dt,
        COUNT(*) AS so_don,
        COALESCE(SUM(so_khach), 0) AS so_khach
    FROM don_dat_ban
    WHERE trang_thai = 'Hoàn tất' AND ngay_dung BETWEEN ? AND ?
");
$stmt_sum->execute([$date_from, $date_to]);
$sum_data = $stmt_sum->fetch();
$rev_total = (float)$sum_data['tong_dt'];
$orders_count = (int)$sum_data['so_don'];
$guests_count = (int)$sum_data['so_khach'];

$avg_per_order = $orders_count > 0 ? $rev_total / $orders_count : 0;
$avg_per_guest = $guests_count > 0 ? $rev_total / $guests_count : 0;

// 2. Doanh thu theo ngày trong khoảng thời gian
$stmt_days = $pdo->prepare("
    SELECT ngay_dung, COALESCE(SUM(tong_tien), 0) AS doanh_thu, COUNT(*) AS so_don
    FROM don_dat_ban
    WHERE trang_thai = 'Hoàn tất' AND ngay_dung BETWEEN ? AND ?
    GROUP BY ngay_dung
    ORDER BY ngay_dung ASC
");
$stmt_days->execute([$date_from, $date_to]);
$day_records = $stmt_days->fetchAll();

$max_day = 1;
foreach ($day_records as $dr) {
    if ((float)$dr['doanh_thu'] > $max_day) $max_day = (float)$dr['doanh_thu'];
}

// 3. Doanh thu theo set Omakase
$stmt_sets = $pdo->prepare("
    SELECT s.ten_set, s.gia,
           COUNT(c.id) AS so_don,
           SUM(c.so_luong) AS so_phan,
           COALESCE(SUM(c.thanh_tien), 0) AS doanh_thu
    FROM chi_tiet_don c
    JOIN set_omakase s ON c.set_id = s.id
    JOIN don_dat_ban d ON c.don_id = d.id
    WHERE d.trang_thai = 'Hoàn tất' AND d.ngay_dung BETWEEN ? AND ?
    GROUP BY s.id
    ORDER BY doanh_thu DESC
");
$stmt_sets->execute([$date_from, $date_to]);
$set_revenues = $stmt_sets->fetchAll();

// 4. Doanh thu theo phương thức thanh toán
$stmt_pays = $pdo->prepare("
    SELECT phuong_thuc_tt,
           COUNT(*) AS so_don,
           COALESCE(SUM(tong_tien), 0) AS doanh_thu
    FROM don_dat_ban
    WHERE trang_thai = 'Hoàn tất' AND ngay_dung BETWEEN ? AND ?
    GROUP BY phuong_thuc_tt
    ORDER BY doanh_thu DESC
");
$stmt_pays->execute([$date_from, $date_to]);
$pay_revenues = $stmt_pays->fetchAll();
?>

<div class="head">
  <div>
    <h1>Báo Cáo Doanh Thu</h1>
    <p>Chỉ thống kê trên các đơn đặt bàn có trạng thái <b>Hoàn tất</b>.</p>
  </div>
  <div class="head-actions">
    <select onchange="location.href='revenue.php?range=' + this.value">
      <option value="7" <?= $days_range === 7 ? 'selected' : '' ?>>7 ngày qua</option>
      <option value="14" <?= $days_range === 14 ? 'selected' : '' ?>>14 ngày qua</option>
      <option value="30" <?= $days_range === 30 ? 'selected' : '' ?>>30 ngày qua</option>
    </select>
    <button type="button" class="btn ghost" onclick="exportTableToCSV('revBySetTable', 'doanh-thu-theo-set.csv')">
      <i class="fa-solid fa-file-csv"></i> Xuất Báo Cáo
    </button>
  </div>
</div>

<div class="kpis">
  <div class="kpi">
    <div class="l">Tổng doanh thu (<?= $days_range ?> ngày)</div>
    <div class="v" style="color:var(--gold);"><?= format_vnd($rev_total) ?></div>
    <div class="d">Từ <?= date('d/m/Y', strtotime($date_from)) ?> đến nay</div>
  </div>
  <div class="kpi">
    <div class="l">Đơn hoàn tất</div>
    <div class="v"><?= $orders_count ?></div>
    <div class="d">Đã phục vụ xong</div>
  </div>
  <div class="kpi">
    <div class="l">Trung bình / đơn</div>
    <div class="v"><?= format_vnd($avg_per_order) ?></div>
    <div class="d">Giá trị đơn bình quân</div>
  </div>
  <div class="kpi">
    <div class="l">Trung bình / khách</div>
    <div class="v"><?= format_vnd($avg_per_guest) ?></div>
    <div class="d">Phục vụ <?= $guests_count ?> lượt khách</div>
  </div>
</div>

<!-- Biểu đồ doanh thu ngày -->
<div class="panel">
  <h2><span><i class="fa-solid fa-chart-line" style="color:var(--gold);"></i> Biểu đồ doanh thu theo ngày</span></h2>
  <?php if (empty($day_records)): ?>
    <div class="empty">Chưa có giao dịch hoàn tất trong khoảng thời gian này.</div>
  <?php else: ?>
    <div class="bars" style="height: 190px;">
      <?php foreach ($day_records as $dr): 
        $val = (float)$dr['doanh_thu'];
        $pct = ($val / $max_day) * 80;
        $dm = date('d/m', strtotime($dr['ngay_dung']));
      ?>
        <div class="bar" title="<?= $dr['ngay_dung'] ?>: <?= format_vnd($val) ?> (<?= $dr['so_don'] ?> đơn)">
          <u><?= $val > 0 ? format_short_money($val) : '' ?></u>
          <i style="height: <?= max(4, $pct) ?>%;"></i>
          <em><?= $dm ?></em>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="grid2">
  <!-- Doanh thu theo Set -->
  <div class="panel">
    <h2><span><i class="fa-solid fa-utensils" style="color:var(--gold);"></i> Doanh thu theo gói Set Omakase</span></h2>
    <div class="tw">
      <table id="revBySetTable">
        <thead>
          <tr>
            <th>Set Món</th>
            <th>Số phần</th>
            <th>Doanh thu</th>
            <th style="text-align:right;">Tỷ trọng</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($set_revenues)): ?>
            <tr><td colspan="4" class="empty">Chưa có số liệu.</td></tr>
          <?php else: ?>
            <?php foreach ($set_revenues as $sr): 
              $dt = (float)$sr['doanh_thu'];
              $pct = $rev_total > 0 ? ($dt / $rev_total) * 100 : 0;
            ?>
              <tr>
                <td><b><?= htmlspecialchars($sr['ten_set']) ?></b></td>
                <td><?= (int)$sr['so_phan'] ?> suất</td>
                <td><b><?= format_vnd($dt) ?></b></td>
                <td style="text-align:right;">
                  <span class="badge b-gold"><?= number_format($pct, 1) ?>%</span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Doanh thu theo hình thức thanh toán -->
  <div class="panel">
    <h2><span><i class="fa-solid fa-credit-card" style="color:var(--gold);"></i> Theo phương thức thanh toán</span></h2>
    <div class="tw">
      <table>
        <thead>
          <tr>
            <th>Phương thức</th>
            <th>Số đơn</th>
            <th>Doanh thu</th>
            <th style="text-align:right;">Tỷ trọng</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($pay_revenues)): ?>
            <tr><td colspan="4" class="empty">Chưa có số liệu.</td></tr>
          <?php else: ?>
            <?php foreach ($pay_revenues as $pr): 
              $dt = (float)$pr['doanh_thu'];
              $pct = $rev_total > 0 ? ($dt / $rev_total) * 100 : 0;
            ?>
              <tr>
                <td><b><?= htmlspecialchars($pr['phuong_thuc_tt']) ?></b></td>
                <td><?= (int)$pr['so_don'] ?> đơn</td>
                <td><b><?= format_vnd($dt) ?></b></td>
                <td style="text-align:right;">
                  <span class="badge b-ok"><?= number_format($pct, 1) ?>%</span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
