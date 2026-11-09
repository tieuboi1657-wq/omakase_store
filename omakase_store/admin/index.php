<?php
$page_title = 'Thống Kê & Tổng Quan';
require_once __DIR__ . '/includes/header.php';

// 1. Thống kê doanh thu & đơn hoàn tất
$rev_stmt = $pdo->query("
    SELECT COALESCE(SUM(tong_tien), 0) AS tong_doanh_thu, COUNT(*) AS so_don_hoan_tat 
    FROM don_dat_ban 
    WHERE trang_thai = 'Hoàn tất'
");
$rev_data = $rev_stmt->fetch();
$total_revenue = (float)($rev_data['tong_doanh_thu'] ?? 0);
$completed_orders = (int)($rev_data['so_don_hoan_tat'] ?? 0);

// 2. Thống kê đơn hàng
$order_stmt = $pdo->query("
    SELECT 
        COUNT(*) AS tong_don, 
        SUM(CASE WHEN trang_thai = 'Chờ xác nhận' THEN 1 ELSE 0 END) AS cho_xac_nhan,
        SUM(CASE WHEN trang_thai = 'Đã xác nhận' THEN 1 ELSE 0 END) AS da_xac_nhan,
        SUM(CASE WHEN trang_thai = 'Đã hủy' THEN 1 ELSE 0 END) AS da_huy
    FROM don_dat_ban
");
$order_data = $order_stmt->fetch();
$total_orders = (int)($order_data['tong_don'] ?? 0);
$pending_orders = (int)($order_data['cho_xac_nhan'] ?? 0);
$confirmed_orders = (int)($order_data['da_xac_nhan'] ?? 0);
$canceled_orders = (int)($order_data['da_huy'] ?? 0);

// 3. Thống kê sản phẩm
$prod_stmt = $pdo->query("
    SELECT COUNT(*) AS tong_set, SUM(CASE WHEN trang_thai = 'Đang bán' THEN 1 ELSE 0 END) AS dang_ban 
    FROM set_omakase
");
$prod_data = $prod_stmt->fetch();
$total_sets = (int)($prod_data['tong_set'] ?? 0);
$active_sets = (int)($prod_data['dang_ban'] ?? 0);

// 4. Thống kê khách hàng
$cust_stmt = $pdo->query("
    SELECT 
        COUNT(*) AS tong_kh,
        SUM(CASE WHEN hang_thanh_vien = 'VIP' THEN 1 ELSE 0 END) AS vip,
        SUM(CASE WHEN hang_thanh_vien = 'Vàng' THEN 1 ELSE 0 END) AS vang
    FROM khach_hang
");
$cust_data = $cust_stmt->fetch();
$total_customers = (int)($cust_data['tong_kh'] ?? 0);
$vip_customers = (int)($cust_data['vip'] ?? 0);
$gold_customers = (int)($cust_data['vang'] ?? 0);

// 5. Thống kê đánh giá
$rev_eval_stmt = $pdo->query("
    SELECT 
        ROUND(COALESCE(AVG(so_sao), 0), 1) AS diem_tb,
        COUNT(*) AS tong_dg,
        SUM(CASE WHEN loai = 'Khiếu nại' AND (phan_hoi IS NULL OR phan_hoi = '') THEN 1 ELSE 0 END) AS khieu_nai_chua_xl
    FROM danh_gia
");
$rev_eval = $rev_eval_stmt->fetch();
$avg_rating = (float)($rev_eval['diem_tb'] ?? 5.0);
$total_reviews = (int)($rev_eval['tong_dg'] ?? 0);
$open_complaints = (int)($rev_eval['khieu_nai_chua_xl'] ?? 0);

// 6. Doanh thu theo 14 ngày gần nhất
$chart_stmt = $pdo->query("
    SELECT ngay_dung, COALESCE(SUM(tong_tien), 0) AS doanh_thu 
    FROM don_dat_ban 
    WHERE trang_thai = 'Hoàn tất'
    GROUP BY ngay_dung 
    ORDER BY ngay_dung DESC 
    LIMIT 14
");
$raw_days = array_reverse($chart_stmt->fetchAll());
$max_day_val = 1;
foreach ($raw_days as $rd) {
    if ((float)$rd['doanh_thu'] > $max_day_val) $max_day_val = (float)$rd['doanh_thu'];
}

// 7. Top 5 set Omakase
$top_sets_stmt = $pdo->query("
    SELECT s.id, s.ten_set, s.gia, COALESCE(SUM(c.thanh_tien), 0) AS doanh_thu, COUNT(c.id) AS so_luot
    FROM set_omakase s
    LEFT JOIN chi_tiet_don c ON s.id = c.set_id
    LEFT JOIN don_dat_ban d ON c.don_id = d.id AND d.trang_thai = 'Hoàn tất'
    GROUP BY s.id
    ORDER BY doanh_thu DESC
    LIMIT 5
");
$top_sets = $top_sets_stmt->fetchAll();
$max_top_rev = !empty($top_sets) ? max(1, (float)$top_sets[0]['doanh_thu']) : 1;

// 8. 5 đơn đặt bàn mới nhất
$recent_orders_stmt = $pdo->query("
    SELECT d.*, k.ho_ten AS ten_khach_hang, b.ten_ban
    FROM don_dat_ban d
    LEFT JOIN khach_hang k ON d.khach_hang_id = k.id
    LEFT JOIN ban b ON d.ban_id = b.id
    ORDER BY d.id DESC
    LIMIT 5
");
$recent_orders = $recent_orders_stmt->fetchAll();
?>

<div class="head">
  <div>
    <h1>Bảng Điều Khiển & Tổng Quan</h1>
    <p>Tổng quan vận hành và các chỉ số kinh doanh thực tế tại nhà hàng Sabi Omakase.</p>
  </div>
  <div class="head-actions">
    <a href="orders.php" class="btn ghost"><i class="fa-solid fa-list-check"></i> Xem danh sách đơn</a>
    <a href="products.php" class="btn"><i class="fa-solid fa-plus"></i> Thêm món mới</a>
  </div>
</div>

<!-- KPIs hàng đầu -->
<div class="kpis">
  <div class="kpi">
    <div class="l">Doanh thu tích lũy</div>
    <div class="v"><?= format_vnd($total_revenue) ?></div>
    <div class="d"><?= $completed_orders ?> đơn đặt bàn hoàn tất</div>
  </div>
  <div class="kpi">
    <div class="l">Tổng đơn đặt bàn</div>
    <div class="v"><?= $total_orders ?></div>
    <div class="d" style="color:var(--warn);"><i class="fa-solid fa-clock"></i> <?= $pending_orders ?> đơn chờ xác nhận</div>
  </div>
  <div class="kpi">
    <div class="l">Set Omakase</div>
    <div class="v"><?= $total_sets ?></div>
    <div class="d" style="color:var(--ok);"><i class="fa-solid fa-check"></i> <?= $active_sets ?> set đang mở bán</div>
  </div>
  <div class="kpi">
    <div class="l">Khách hàng</div>
    <div class="v"><?= $total_customers ?></div>
    <div class="d"><?= $vip_customers ?> VIP · <?= $gold_customers ?> Vàng</div>
  </div>
  <div class="kpi">
    <div class="l">Đánh giá trung bình</div>
    <div class="v"><?= number_format($avg_rating, 1) ?> ★</div>
    <div class="d"><?= $total_reviews ?> đánh giá <?= $open_complaints > 0 ? "· <span style='color:var(--red2)'>$open_complaints khiếu nại</span>" : '' ?></div>
  </div>
</div>

<!-- Lưới 2 cột biểu đồ -->
<div class="grid2">
  <!-- Biểu đồ doanh thu ngày -->
  <div class="panel">
    <h2>
      <span><i class="fa-solid fa-chart-simple" style="color:var(--gold);"></i> Doanh thu các ngày gần nhất</span>
      <small style="font-size:12px;color:var(--muted);font-weight:normal;">Dữ liệu đơn hoàn tất</small>
    </h2>
    <?php if (empty($raw_days)): ?>
      <div class="empty">Chưa có dữ liệu doanh thu trong khoảng thời gian này.</div>
    <?php else: ?>
      <div class="bars">
        <?php foreach ($raw_days as $day): 
          $val = (float)$day['doanh_thu'];
          $pct = ($val / $max_day_val) * 80;
          $dm = date('d/m', strtotime($day['ngay_dung']));
        ?>
          <div class="bar" title="<?= $day['ngay_dung'] ?>: <?= format_vnd($val) ?>">
            <u><?= $val > 0 ? format_short_money($val) : '' ?></u>
            <i style="height: <?= max(3, $pct) ?>%;"></i>
            <em><?= $dm ?></em>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Top set Omakase -->
  <div class="panel">
    <h2>
      <span><i class="fa-solid fa-crown" style="color:var(--gold);"></i> Top 5 Set Omakase Doanh Thu Cao</span>
      <a href="products.php" style="font-size:12px;color:var(--gold);text-decoration:underline;">Tất cả set →</a>
    </h2>
    <div style="margin-top:12px;">
      <?php if (empty($top_sets)): ?>
        <div class="empty">Chưa có dữ liệu set món.</div>
      <?php else: ?>
        <?php foreach ($top_sets as $ts): 
          $rev = (float)$ts['doanh_thu'];
          $bar_pct = round(($rev / $max_top_rev) * 100);
        ?>
          <div class="hbar">
            <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($ts['ten_set']) ?>">
              <?= htmlspecialchars(str_replace('Set ', '', $ts['ten_set'])) ?>
            </span>
            <div class="t"><i style="width: <?= $bar_pct ?>%;"></i></div>
            <span style="text-align:right;font-weight:600;"><?= format_short_money($rev) ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Bảng 5 đơn đặt bàn mới nhất -->
<div class="panel">
  <h2>
    <span><i class="fa-solid fa-bell" style="color:var(--gold);"></i> Đơn đặt bàn gần đây</span>
    <a href="orders.php" style="font-size:12px;color:var(--gold);text-decoration:underline;">Xem tất cả <?= $total_orders ?> đơn →</a>
  </h2>
  <div class="tw">
    <table>
      <thead>
        <tr>
          <th>Mã đơn</th>
          <th>Khách hàng</th>
          <th>Thời gian dùng bữa</th>
          <th>Số khách</th>
          <th>Tổng tiền</th>
          <th>Thanh toán</th>
          <th>Trạng thái</th>
          <th style="text-align:right;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recent_orders)): ?>
          <tr><td colspan="8" class="empty">Chưa có đơn đặt bàn nào trong hệ thống.</td></tr>
        <?php else: ?>
          <?php foreach ($recent_orders as $o): 
            $st_badge = [
              'Chờ xác nhận' => 'b-warn',
              'Đã xác nhận'  => 'b-gold',
              'Hoàn tất'      => 'b-ok',
              'Đã hủy'       => 'b-bad'
            ][$o['trang_thai']] ?? 'b-mute';
          ?>
            <tr>
              <td><b>#<?= htmlspecialchars($o['ma_don']) ?></b></td>
              <td>
                <div style="font-weight:600;"><?= htmlspecialchars($o['ten_lien_he'] ?: $o['ten_khach_hang']) ?></div>
                <div class="sub"><?= htmlspecialchars($o['sdt_lien_he']) ?></div>
              </td>
              <td>
                <div><?= date('d/m/Y', strtotime($o['ngay_dung'])) ?></div>
                <div class="sub"><?= substr($o['gio_dung'], 0, 5) ?> · Bàn: <?= htmlspecialchars($o['ten_ban'] ?? 'Chưa xếp') ?></div>
              </td>
              <td><b><?= (int)$o['so_khach'] ?></b> khách</td>
              <td><b><?= format_vnd($o['tong_tien']) ?></b></td>
              <td>
                <div><?= htmlspecialchars($o['phuong_thuc_tt']) ?></div>
                <div class="sub"><?= htmlspecialchars($o['trang_thai_thanh_toan']) ?></div>
              </td>
              <td>
                <span class="badge <?= $st_badge ?>"><?= htmlspecialchars($o['trang_thai']) ?></span>
              </td>
              <td style="text-align:right;">
                <a href="orders.php?view_id=<?= $o['id'] ?>" class="btn ghost sm"><i class="fa-solid fa-eye"></i> Chi tiết</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
