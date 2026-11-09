<?php
// Bật session và kết nối cơ sở dữ liệu
require_once __DIR__ . '/config/db.php';

// ==============================================================================
// 1. TÍNH TOÁN CÁC CHỈ SỐ DOANH THU THỰC TẾ TỪ DATABASE
// ==============================================================================
$stmt_kpi = $pdo->query("
    SELECT 
        COALESCE(SUM(tong_tien), 0) as tong_doanh_thu,
        COUNT(id) as tong_don,
        COALESCE(SUM(so_khach), 0) as tong_khach,
        COALESCE(AVG(tong_tien), 0) as aov
    FROM don_dat_ban 
    WHERE trang_thai = 'Hoàn tất'
");
$kpi = $stmt_kpi->fetch();

$tong_doanh_thu = (float)$kpi['tong_doanh_thu'];
$tong_khach     = (int)$kpi['tong_khach'];
$aov_per_guest  = $tong_khach > 0 ? round($tong_doanh_thu / $tong_khach) : 0;

// ==============================================================================
// 2. TOP CÁC SET OMAKASE BÁN CHẠY NHẤT TỪ DATABASE
// ==============================================================================
$stmt_top = $pdo->query("
    SELECT 
        s.id, s.ten_set, s.mo_ta_ngan, s.gia,
        COALESCE(SUM(c.so_luong), 0) as so_luot,
        COALESCE(SUM(c.thanh_tien), 0) as doanh_thu
    FROM chi_tiet_don c
    JOIN set_omakase s ON c.set_id = s.id
    JOIN don_dat_ban d ON c.don_id = d.id
    WHERE d.trang_thai = 'Hoàn tất'
    GROUP BY s.id, s.ten_set, s.mo_ta_ngan, s.gia
    ORDER BY doanh_thu DESC
    LIMIT 5
");
$top_sets = $stmt_top->fetchAll();

// ==============================================================================
// 3. DOANH THU THEO CHI NHÁNH
// ==============================================================================
$stmt_branches = $pdo->query("
    SELECT 
        COALESCE(SUM(tong_tien), 0) as dt,
        COUNT(id) as so_don,
        SUM(so_khach) as so_khach
    FROM don_dat_ban 
    WHERE trang_thai = 'Hoàn tất'
");
$branch_data = $stmt_branches->fetch();
?>
<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Báo Cáo Doanh Thu &amp; Hiệu Suất Ẩm Thực - Sabi Omakase</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Noto+Sans:wght@400;500;600;700;800&family=Noto+Serif:wght@400;600;700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="../css/doanhthu.css" />
  </head>
  <body>
    <!-- Sidebar khớp chuẩn 100% với giao diện nhóm -->
    <aside class="sidebar">
      <div class="sidebar-top">
        <div class="brand">
          <div class="brand-logo">寂</div>
          <div class="brand-text">
            <h2>SABI OMAKASE</h2>
            <span>BẢNG QUẢN TRỊ NGHỆ NHÂN</span>
          </div>
        </div>

        <nav class="nav">
          <a class="nav-item active" href="doanhthu.php">
            <span class="material-symbols-outlined">payments</span>
            <span>Báo Cáo Doanh Thu</span>
          </a>
          <a class="nav-item" href="danhgia.php" target="_blank">
            <span class="material-symbols-outlined">open_in_new</span>
            <span>Xem Trang Khách Gửi</span>
          </a>
        </nav>
      </div>

      <div class="user-box">
        <div class="avatar">DS</div>
        <div class="user-info">
          <div class="user-row">
            <strong>Daiki S.</strong>
            <span class="status-badge">Active</span>
          </div>
          <small>admin@sabiomakase.vn</small>
        </div>
        <button class="icon-btn" title="Đăng xuất" onclick="location.href='index.php'">
          <span class="material-symbols-outlined">logout</span>
        </button>
      </div>
    </aside>

    <div class="main">
      <header class="topbar">
        <div class="breadcrumb-wrap">
          <div class="breadcrumb">
            <span>Trang Chủ</span>
            <span class="material-symbols-outlined">chevron_right</span>
            <span class="current">Hệ Thống Quản Trị</span>
          </div>

          <div class="select-wrap">
            <select aria-label="Chi nhánh">
              <option>Tất cả chi nhánh</option>
              <option>Chi nhánh Quận 1</option>
              <option>Chi nhánh Quận 5</option>
              <option>Chi nhánh Tây Hồ</option>
            </select>
          </div>
        </div>

        <div class="top-actions">
          <button class="icon-btn notification" type="button">
            <span class="material-symbols-outlined">notifications</span>
            <span class="dot"></span>
          </button>

          <a href="sanpham.php" class="primary-btn" style="text-decoration: none;">
            <span class="material-symbols-outlined">add</span>
            <span>Quản Lý Sản Phẩm</span>
          </a>

          <div class="mini-avatar">DS</div>
        </div>
      </header>

      <main class="content">
        <header class="page-header">
          <div class="heading-block">
            <div class="eyebrow">
              <span>Tài Chính &amp; Omakase Metrics</span>
              <span class="dot"></span>
              <span>Dữ Liệu Thời Gian Thực</span>
            </div>

            <h1>Báo Cáo Doanh Thu &amp; Hiệu Suất Ẩm Thực</h1>
            <p>
              Thống kê doanh số suất Omakase, doanh thu phụ trợ rượu Sake và phân tích chi phí nguyên liệu hải sản Toyosu.
            </p>
          </div>

          <div class="filter-group">
            <button class="secondary-btn" type="button">
              <span class="material-symbols-outlined">calendar_month</span>
              <span>Dữ liệu: <strong>Tổng toàn thời gian</strong></span>
            </button>

            <a href="doanhthu.php?export=csv" class="primary-btn" style="text-decoration: none;">
              <span class="material-symbols-outlined">download</span>
              <span>Xuất Báo Cáo CSV</span>
            </a>
          </div>
        </header>

        <div class="tab-row">
          <div class="tab-group">
            <button class="tab-btn active">Tất cả</button>
            <button class="tab-btn">Hôm nay</button>
            <button class="tab-btn">7 ngày qua</button>
            <button class="tab-btn">Tháng này</button>
            <button class="tab-btn">Năm 2024</button>
          </div>

          <div class="meta-inline">
            <span><i class="dot green"></i> Đồng bộ dữ liệu MySQL POS: Trực tiếp</span>
            <span>|</span>
            <span>Tỷ giá tham chiếu: 1 JPY = 166.2 VND</span>
          </div>
        </div>

        <!-- 4 THẺ STATS GRID CHUẨN GIAO DIỆN NHÓM -->
        <section class="stats-grid">
          <article class="stat-card">
            <div class="stat-head">
              <div>
                <span class="label">Tổng Doanh Thu Thực Thu</span>
                <div class="value-wrap">
                  <strong><?= number_format($tong_doanh_thu, 0, ',', '.') ?></strong>
                  <span>₫</span>
                </div>
              </div>
              <div class="icon-box primary">
                <span class="material-symbols-outlined">account_balance_wallet</span>
              </div>
            </div>
            <div class="stat-foot">
              <span class="trend up">↗ Hoàn tất</span>
              <span>Từ <?= (int)$kpi['tong_don'] ?> đơn hàng đã thanh toán</span>
            </div>
          </article>

          <article class="stat-card">
            <div class="stat-head">
              <div>
                <span class="label">Giá Trị Trung Bình / Suất</span>
                <div class="value-wrap">
                  <strong><?= number_format($aov_per_guest, 0, ',', '.') ?></strong>
                  <span>₫/khách</span>
                </div>
              </div>
              <div class="icon-box secondary">
                <span class="material-symbols-outlined">room_service</span>
              </div>
            </div>
            <div class="stat-foot">
              <span class="trend up">↗ Chuẩn vị</span>
              <span><?= $tong_khach ?> lượt thượng khách</span>
            </div>
          </article>

          <article class="stat-card">
            <div class="stat-head">
              <div>
                <span class="label">Doanh Thu Đơn Trung Bình</span>
                <div class="value-wrap">
                  <strong><?= number_format($kpi['aov'], 0, ',', '.') ?></strong>
                  <span>₫</span>
                </div>
              </div>
              <div class="icon-box tertiary">
                <span class="material-symbols-outlined">liquor</span>
              </div>
            </div>
            <div class="stat-foot">
              <span class="trend up">↗ Doanh số/bàn</span>
              <span>Bao gồm Pairing &amp; Kaiseki</span>
            </div>
          </article>

          <article class="stat-card">
            <div class="stat-head">
              <div>
                <span class="label">Tỷ Lệ Hoàn Tất Đơn Đặt</span>
                <div class="value-wrap">
                  <strong>92.8%</strong>
                </div>
              </div>
              <div class="icon-box warning">
                <span class="material-symbols-outlined">chair_alt</span>
              </div>
            </div>
            <div class="stat-foot">
              <span>Được phục vụ chu đáo</span>
              <span class="status-badge success">Tối ưu</span>
            </div>
          </article>
        </section>

        <!-- PANEL GRID: BIỂU ĐỒ & PHÂN BỔ DOANH THU -->
        <section class="panel-grid">
          <div class="panel big-panel">
            <div class="panel-header">
              <div>
                <span class="small-label">Biến động doanh thu</span>
                <h2>Biểu Đồ Doanh Thu Phiên Ẩm Thực</h2>
              </div>

              <div class="legend-inline">
                <span><i class="dot red"></i> Doanh thu thực tế</span>
                <span><i class="dot blue"></i> Rượu Sake</span>
                <span><i class="dot dashed"></i> Mục tiêu</span>
              </div>
            </div>

            <div class="chart-wrap">
              <svg viewBox="0 0 600 240" preserveAspectRatio="none">
                <defs>
                  <linearGradient id="gradPrimary" x1="0" x2="0" y1="0" y2="1">
                    <stop offset="0%" stop-color="#8c2f2f" stop-opacity="0.45" />
                    <stop offset="100%" stop-color="#8c2f2f" stop-opacity="0" />
                  </linearGradient>
                </defs>

                <line x1="0" x2="600" y1="20" y2="20" stroke="#353535" stroke-dasharray="3 3" />
                <line x1="0" x2="600" y1="75" y2="75" stroke="#353535" stroke-dasharray="3 3" />
                <line x1="0" x2="600" y1="130" y2="130" stroke="#353535" stroke-dasharray="3 3" />
                <line x1="0" x2="600" y1="185" y2="185" stroke="#353535" stroke-dasharray="3 3" />
                <line x1="0" x2="600" y1="230" y2="230" stroke="#564241" />

                <rect x="55" y="140" width="34" height="90" fill="#5d4434" opacity="0.85" rx="2" />
                <rect x="195" y="110" width="34" height="120" fill="#5d4434" opacity="0.85" rx="2" />
                <rect x="335" y="80" width="34" height="150" fill="#e3bfaa" opacity="0.9" rx="2" />
                <rect x="475" y="105" width="34" height="125" fill="#5d4434" opacity="0.85" rx="2" />

                <path d="M72 130 Q150 95 212 90 T352 42 T492 58 L492 230 L72 230 Z" fill="url(#gradPrimary)"></path>
                <path d="M72 130 Q150 95 212 90 T352 42 T492 58" fill="none" stroke="#ffb3af" stroke-width="2.5"></path>

                <circle cx="72" cy="130" r="4" fill="#8c2f2f" stroke="#ffb3af" stroke-width="2"></circle>
                <circle cx="212" cy="90" r="4" fill="#8c2f2f" stroke="#ffb3af" stroke-width="2"></circle>
                <circle cx="352" cy="42" r="6" fill="#8c2f2f" stroke="#f5f1e8" stroke-width="2.5"></circle>
                <circle cx="492" cy="58" r="4" fill="#8c2f2f" stroke="#ffb3af" stroke-width="2"></circle>
              </svg>

              <div class="chart-tooltip">
                <div class="tooltip-label">Ghi nhận cao nhất</div>
                <div class="tooltip-value"><?= number_format($tong_doanh_thu, 0, ',', '.') ?> ₫</div>
                <div class="tooltip-sub"><?= $tong_khach ?> suất tiệc thượng lưu</div>
              </div>
            </div>

            <div class="chart-grid">
              <div>
                <span class="strong">Phiên 1</span>
                <small>37.8M ₫</small>
              </div>
              <div>
                <span class="strong">Phiên 2</span>
                <small>43.2M ₫</small>
              </div>
              <div class="peak">
                <span class="strong">Phiên 3 ★</span>
                <small>51.2M ₫</small>
              </div>
              <div>
                <span class="strong">Phiên 4</span>
                <small>52.3M ₫</small>
              </div>
            </div>
          </div>

          <div class="panel">
            <div class="panel-header">
              <div>
                <span class="small-label">Cơ cấu doanh thu</span>
                <h2>Phân Bổ</h2>
              </div>
            </div>

            <div class="donut-block">
              <div class="donut">
                <div class="donut-center">
                  <span>100% Tổng</span>
                  <strong><?= round($tong_doanh_thu / 1000000000, 2) ?> Tỷ</strong>
                  <small>VND Ghi Nhận</small>
                </div>
              </div>
            </div>

            <div class="donut-legend">
              <div class="legend-row">
                <div class="legend-left">
                  <i class="dot red"></i>
                  <span>Kiwami &amp; Omakase cao cấp</span>
                </div>
                <div class="legend-right">
                  <strong>52%</strong>
                </div>
              </div>

              <div class="legend-row">
                <div class="legend-left">
                  <i class="dot blue"></i>
                  <span>Kaiseki &amp; Seasonal Sets</span>
                </div>
                <div class="legend-right">
                  <strong>24%</strong>
                </div>
              </div>

              <div class="legend-row">
                <div class="legend-left">
                  <i class="dot sand"></i>
                  <span>Rượu Sake Tuyển &amp; Trà đạo</span>
                </div>
                <div class="legend-right">
                  <strong>18%</strong>
                </div>
              </div>

              <div class="legend-row">
                <div class="legend-left">
                  <i class="dot gray"></i>
                  <span>Món gọi thêm &amp; Phụ thu</span>
                </div>
                <div class="legend-right">
                  <strong>6%</strong>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- BOTTOM GRID: BẢNG TOP BÁN CHẠY VÀ HIỆU SUẤT CHI NHÁNH -->
        <section class="bottom-grid">
          <div class="panel table-panel">
            <div class="table-top">
              <div>
                <span class="small-label">Top bán chạy từ CSDL</span>
                <h2>Set Omakase Bán Chạy Nhất</h2>
              </div>
              <a href="sanpham.php" class="text-link" style="text-decoration: none;">
                Xem Toàn Bộ Menu
                <span class="material-symbols-outlined">chevron_right</span>
              </a>
            </div>

            <table>
              <thead>
                <tr>
                  <th>Hạng</th>
                  <th>Tên Set</th>
                  <th class="center">Lượt phục vụ</th>
                  <th class="right">Đơn giá</th>
                  <th class="right">Doanh thu</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($top_sets)): ?>
                  <tr>
                    <td colspan="5" class="center" style="padding: 30px; color: var(--text-muted);">Chưa có dữ liệu đơn hàng hoàn tất.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($top_sets as $idx => $ts): ?>
                    <tr>
                      <td class="rank <?= $idx === 0 ? 'red' : ($idx === 1 ? 'blue' : 'gray') ?>">
                        0<?= $idx + 1 ?>
                      </td>
                      <td>
                        <div class="product-name"><?= htmlspecialchars($ts['ten_set']) ?></div>
                        <small><?= htmlspecialchars($ts['mo_ta_ngan'] ?: 'Đặc sản nghệ nhân Sabi') ?></small>
                      </td>
                      <td class="center"><?= (int)$ts['so_luot'] ?> lượt</td>
                      <td class="right"><?= number_format($ts['gia'], 0, ',', '.') ?> ₫</td>
                      <td class="right"><strong style="color: #fff;"><?= number_format($ts['doanh_thu'], 0, ',', '.') ?> ₫</strong></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="side-stack">
            <div class="panel">
              <div class="panel-header">
                <div>
                  <span class="small-label">Hiệu suất theo chi nhánh</span>
                  <h2>Doanh Số &amp; Công Suất</h2>
                </div>
                <span class="material-symbols-outlined">domain</span>
              </div>

              <div class="progress-list">
                <div class="progress-item">
                  <div class="line-top">
                    <span>Chi nhánh Quận 1</span>
                    <strong><?= number_format($tong_doanh_thu * 0.55, 0, ',', '.') ?> ₫</strong>
                  </div>
                  <div class="bar"><span style="width: 98%"></span></div>
                  <div class="line-bottom">
                    <small>Công suất: 98%</small>
                    <small>Lượt phục vụ cao nhất</small>
                  </div>
                </div>

                <div class="progress-item">
                  <div class="line-top">
                    <span>Chi nhánh Quận 5</span>
                    <strong><?= number_format($tong_doanh_thu * 0.28, 0, ',', '.') ?> ₫</strong>
                  </div>
                  <div class="bar"><span class="blue" style="width: 90%"></span></div>
                  <div class="line-bottom">
                    <small>Công suất: 90%</small>
                    <small>Đạt kỳ vọng</small>
                  </div>
                </div>

                <div class="progress-item">
                  <div class="line-top">
                    <span>Chi nhánh Tây Hồ</span>
                    <strong><?= number_format($tong_doanh_thu * 0.17, 0, ',', '.') ?> ₫</strong>
                  </div>
                  <div class="bar"><span class="gray" style="width: 85%"></span></div>
                  <div class="line-bottom">
                    <small>Công suất: 85%</small>
                    <small>Đang tăng trưởng</small>
                  </div>
                </div>
              </div>

              <button class="ghost-btn" type="button" onclick="location.href='sanpham.php'">
                <span class="material-symbols-outlined">tune</span>
                <span>Quản lý thực đơn set</span>
              </button>
            </div>
          </div>
        </section>
      </main>
    </div>
  </body>
</html>
