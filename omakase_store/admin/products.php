<?php
$page_title = 'Quản Lý Sản Phẩm & Set Omakase';
require_once __DIR__ . '/includes/header.php';

// Xử lý các thao tác Thêm / Sửa / Xóa qua POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $ma_set          = trim($_POST['ma_set'] ?? '');
        $ten_set         = trim($_POST['ten_set'] ?? '');
        $ten_tieng_nhat  = trim($_POST['ten_tieng_nhat'] ?? '');
        $loai_set_id     = (int)($_POST['loai_set_id'] ?? 1);
        $bep_truong_id   = !empty($_POST['bep_truong_id']) ? (int)$_POST['bep_truong_id'] : null;
        $gia             = (float)($_POST['gia'] ?? 0);
        $so_mon          = (int)($_POST['so_mon'] ?? 12);
        $thoi_luong_phut = (int)($_POST['thoi_luong_phut'] ?? 90);
        $nhan            = trim($_POST['nhan'] ?? '');
        $mo_ta_ngan      = trim($_POST['mo_ta_ngan'] ?? '');
        $mo_ta_chi_tiet  = trim($_POST['mo_ta_chi_tiet'] ?? '');
        $hinh_anh        = trim($_POST['hinh_anh'] ?? 'images/kiwami.jpg');
        $trang_thai      = $_POST['trang_thai'] ?? 'Đang bán';

        if (empty($ma_set) || empty($ten_set) || $gia <= 0) {
            set_flash('err', 'Vui lòng nhập đầy đủ mã set, tên set và giá hợp lệ (> 0).');
        } else {
            // Kiểm tra trùng mã set
            $chk = $pdo->prepare("SELECT id FROM set_omakase WHERE ma_set = ?");
            $chk->execute([$ma_set]);
            if ($chk->fetch()) {
                set_flash('err', "Mã set '$ma_set' đã tồn tại trong hệ thống. Vui lòng chọn mã khác.");
            } else {
                $ins = $pdo->prepare("
                    INSERT INTO set_omakase (
                        ma_set, ten_set, ten_tieng_nhat, loai_set_id, bep_truong_id, 
                        gia, so_mon, thoi_luong_phut, nhan, mo_ta_ngan, mo_ta_chi_tiet, 
                        hinh_anh, trang_thai
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([
                    $ma_set, $ten_set, $ten_tieng_nhat, $loai_set_id, $bep_truong_id,
                    $gia, $so_mon, $thoi_luong_phut, $nhan, $mo_ta_ngan, $mo_ta_chi_tiet,
                    $hinh_anh, $trang_thai
                ]);
                set_flash('ok', "Đã thêm thành công set mới: $ten_set ($ma_set)!");
            }
        }
        header("Location: products.php");
        exit;
    }

    if ($action === 'edit') {
        $id              = (int)($_POST['id'] ?? 0);
        $ten_set         = trim($_POST['ten_set'] ?? '');
        $ten_tieng_nhat  = trim($_POST['ten_tieng_nhat'] ?? '');
        $loai_set_id     = (int)($_POST['loai_set_id'] ?? 1);
        $bep_truong_id   = !empty($_POST['bep_truong_id']) ? (int)$_POST['bep_truong_id'] : null;
        $gia             = (float)($_POST['gia'] ?? 0);
        $so_mon          = (int)($_POST['so_mon'] ?? 12);
        $thoi_luong_phut = (int)($_POST['thoi_luong_phut'] ?? 90);
        $nhan            = trim($_POST['nhan'] ?? '');
        $mo_ta_ngan      = trim($_POST['mo_ta_ngan'] ?? '');
        $mo_ta_chi_tiet  = trim($_POST['mo_ta_chi_tiet'] ?? '');
        $hinh_anh        = trim($_POST['hinh_anh'] ?? 'images/kiwami.jpg');
        $trang_thai      = $_POST['trang_thai'] ?? 'Đang bán';

        if (empty($ten_set) || $gia <= 0) {
            set_flash('err', 'Tên sản phẩm và đơn giá không được để trống.');
        } else {
            $upd = $pdo->prepare("
                UPDATE set_omakase SET 
                    ten_set = ?, ten_tieng_nhat = ?, loai_set_id = ?, bep_truong_id = ?,
                    gia = ?, so_mon = ?, thoi_luong_phut = ?, nhan = ?, mo_ta_ngan = ?,
                    mo_ta_chi_tiet = ?, hinh_anh = ?, trang_thai = ?
                WHERE id = ?
            ");
            $upd->execute([
                $ten_set, $ten_tieng_nhat, $loai_set_id, $bep_truong_id,
                $gia, $so_mon, $thoi_luong_phut, $nhan, $mo_ta_ngan,
                $mo_ta_chi_tiet, $hinh_anh, $trang_thai, $id
            ]);
            set_flash('ok', "Đã cập nhật thông tin set: $ten_set thành công!");
        }
        header("Location: products.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        // Kiểm tra xem set này đã phát sinh đơn đặt bàn chưa
        $chk_orders = $pdo->prepare("SELECT COUNT(*) FROM chi_tiet_don WHERE set_id = ?");
        $chk_orders->execute([$id]);
        $has_orders = (int)$chk_orders->fetchColumn();

        if ($has_orders > 0) {
            // Không xóa cứng để bảo vệ tính toàn vẹn dữ liệu đơn hàng
            $pdo->prepare("UPDATE set_omakase SET trang_thai = 'Ngừng bán' WHERE id = ?")->execute([$id]);
            set_flash('warn', "Set món này đã có trong $has_orders đơn đặt bàn lịch sử, hệ thống đã tự động chuyển trạng thái sang 'Ngừng bán' để bảo toàn dữ liệu.");
        } else {
            $del = $pdo->prepare("DELETE FROM set_omakase WHERE id = ?");
            $del->execute([$id]);
            set_flash('ok', 'Đã xóa sản phẩm khỏi cơ sở dữ liệu thành công!');
        }
        header("Location: products.php");
        exit;
    }
}

// Lấy danh sách loại set để lọc và thêm mới
$categories = $pdo->query("SELECT id, ten_loai FROM loai_set ORDER BY thu_tu ASC")->fetchAll();

// Lấy danh sách bếp trưởng
$chefs = $pdo->query("SELECT id, ho_ten, chuc_danh FROM nhan_vien WHERE bo_phan = 'Đầu bếp' ORDER BY id ASC")->fetchAll();

// Bộ lọc & Tìm kiếm
$q           = trim($_GET['q'] ?? '');
$filter_cat  = isset($_GET['cat']) && $_GET['cat'] !== '' ? (int)$_GET['cat'] : null;
$filter_stat = trim($_GET['status'] ?? '');

$sql = "
    SELECT s.*, l.ten_loai, nv.ho_ten AS ten_bep_truong
    FROM set_omakase s
    LEFT JOIN loai_set l ON s.loai_set_id = l.id
    LEFT JOIN nhan_vien nv ON s.bep_truong_id = nv.id
    WHERE 1=1
";
$params = [];

if ($q !== '') {
    $sql .= " AND (s.ma_set LIKE ? OR s.ten_set LIKE ? OR s.ten_tieng_nhat LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($filter_cat !== null) {
    $sql .= " AND s.loai_set_id = ?";
    $params[] = $filter_cat;
}
if ($filter_stat !== '') {
    $sql .= " AND s.trang_thai = ?";
    $params[] = $filter_stat;
}
$sql .= " ORDER BY s.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Thống kê nhanh
$total_p    = count($products);
$active_p   = 0;
$total_vals = 0;
foreach ($products as $p) {
    if ($p['trang_thai'] === 'Đang bán') $active_p++;
    $total_vals += (float)$p['gia'];
}
$avg_p = $total_p > 0 ? $total_vals / $total_p : 0;
?>

<div class="head">
  <div>
    <h1>Quản Lý Sản Phẩm & Thực Đơn Omakase</h1>
    <p>Quản lý toàn bộ gói set thực đơn, giá niêm yết, số món và tình trạng phục vụ của nhà hàng.</p>
  </div>
  <div class="head-actions">
    <button type="button" class="btn ghost" onclick="exportTableToCSV('productsTable', 'danh-sach-set-omakase.csv')">
      <i class="fa-solid fa-file-csv"></i> Xuất CSV
    </button>
    <button type="button" class="btn" onclick="openAddModal()">
      <i class="fa-solid fa-plus"></i> Thêm Set Omakase Mới
    </button>
  </div>
</div>

<!-- Thống kê KPIs -->
<div class="kpis">
  <div class="kpi">
    <div class="l">Tổng sản phẩm tìm thấy</div>
    <div class="v"><?= $total_p ?></div>
    <div class="d">Trong <?= count($categories) ?> danh mục ẩm thực</div>
  </div>
  <div class="kpi">
    <div class="l">Đang phục vụ</div>
    <div class="v" style="color:var(--ok);"><?= $active_p ?></div>
    <div class="d"><?= $total_p - $active_p ?> tạm ngừng hoặc ngừng bán</div>
  </div>
  <div class="kpi">
    <div class="l">Giá trung bình</div>
    <div class="v"><?= format_vnd($avg_p) ?></div>
    <div class="d">Theo danh sách hiện tại</div>
  </div>
</div>

<!-- Bảng dữ liệu và Bộ lọc -->
<div class="panel">
  <form method="GET" action="products.php" class="tools">
    <input type="text" name="q" placeholder="Tìm theo mã set, tên món, tiếng Nhật..." value="<?= htmlspecialchars($q) ?>">
    
    <select name="cat" onchange="this.form.submit()">
      <option value="">-- Tất cả danh mục --</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>" <?= $filter_cat === (int)$cat['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($cat['ten_loai']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="status" onchange="this.form.submit()">
      <option value="">-- Tất cả trạng thái --</option>
      <option value="Đang bán" <?= $filter_stat === 'Đang bán' ? 'selected' : '' ?>>Đang bán</option>
      <option value="Tạm ngừng" <?= $filter_stat === 'Tạm ngừng' ? 'selected' : '' ?>>Tạm ngừng</option>
      <option value="Ngừng bán" <?= $filter_stat === 'Ngừng bán' ? 'selected' : '' ?>>Ngừng bán</option>
    </select>

    <button type="submit" class="btn ghost sm"><i class="fa-solid fa-magnifying-glass"></i> Lọc</button>
    <?php if ($q !== '' || $filter_cat !== null || $filter_stat !== ''): ?>
      <a href="products.php" class="btn ghost sm" style="color:var(--red2);"><i class="fa-solid fa-rotate-left"></i> Đặt lại</a>
    <?php endif; ?>
    <span class="sub" style="margin-left:auto;"><?= $total_p ?> sản phẩm</span>
  </form>

  <div class="tw">
    <table id="productsTable">
      <thead>
        <tr>
          <th>Sản phẩm / Set Omakase</th>
          <th>Danh mục</th>
          <th>Bếp trưởng</th>
          <th>Đơn giá</th>
          <th>Quy cách</th>
          <th>Trạng thái</th>
          <th class="no-export" style="text-align:right;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($products)): ?>
          <tr><td colspan="7" class="empty">Không tìm thấy sản phẩm nào phù hợp điều kiện lọc.</td></tr>
        <?php else: ?>
          <?php foreach ($products as $p): 
            $badge_class = [
              'Đang bán'  => 'b-ok',
              'Tạm ngừng' => 'b-warn',
              'Ngừng bán' => 'b-bad'
            ][$p['trang_thai']] ?? 'b-mute';
          ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:12px;">
                  <img src="../<?= htmlspecialchars(!empty($p['hinh_anh']) ? $p['hinh_anh'] : 'images/kiwami.jpg') ?>" 
                       alt="<?= htmlspecialchars($p['ten_set']) ?>" 
                       style="width:48px;height:48px;border-radius:4px;object-fit:cover;border:1px solid var(--line);background:#000;flex-shrink:0;"
                       onerror="this.onerror=null;this.src='../images/kiwami.jpg';">
                  <div>
                    <div style="font-weight:600;font-size:14px;color:var(--text);"><?= htmlspecialchars($p['ten_set']) ?></div>
                    <div class="sub">
                      <code><?= htmlspecialchars($p['ma_set']) ?></code>
                      <?php if (!empty($p['ten_tieng_nhat'])): ?> · <span><?= htmlspecialchars($p['ten_tieng_nhat']) ?></span><?php endif; ?>
                      <?php if (!empty($p['nhan'])): ?> · <span class="badge b-gold" style="font-size:10px;padding:1px 5px;"><?= htmlspecialchars($p['nhan']) ?></span><?php endif; ?>
                    </div>
                  </div>
                </div>
              </td>
              <td><?= htmlspecialchars($p['ten_loai'] ?? 'Chưa phân loại') ?></td>
              <td>
                <div><?= htmlspecialchars($p['ten_bep_truong'] ?? 'Chef Daiki S.') ?></div>
              </td>
              <td><b><?= format_vnd($p['gia']) ?></b></td>
              <td>
                <div><?= (int)$p['so_mon'] ?> món</div>
                <div class="sub"><?= (int)$p['thoi_luong_phut'] ?> phút</div>
              </td>
              <td>
                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($p['trang_thai']) ?></span>
              </td>
              <td class="no-export" style="text-align:right;white-space:nowrap;">
                <button type="button" class="btn ghost sm" onclick='openEditModal(<?= json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                  <i class="fa-solid fa-pen-to-square"></i> Sửa
                </button>
                <form action="products.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa/ngừng bán set <?= htmlspecialchars(addslashes($p['ten_set'])) ?>?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <button type="submit" class="btn ghost sm danger" style="padding:5px 8px;"><i class="fa-solid fa-trash"></i></button>
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
const categoriesList = <?= json_encode($categories) ?>;
const chefsList = <?= json_encode($chefs) ?>;

function openAddModal() {
  let catOptions = categoriesList.map(c => `<option value="${c.id}">${c.ten_loai}</option>`).join('');
  let chefOptions = `<option value="">-- Mặc định (Bếp trưởng) --</option>` + 
                    chefsList.map(cf => `<option value="${cf.id}">${cf.ho_ten} (${cf.chuc_danh})</option>`).join('');

  const html = `
    <h2><i class="fa-solid fa-plus" style="color:var(--gold);"></i> Thêm Set Omakase Mới</h2>
    <form action="products.php" method="POST">
      <input type="hidden" name="action" value="add">
      <div class="two">
        <div>
          <label>Mã sản phẩm (duy nhất) *</label>
          <input type="text" name="ma_set" required placeholder="VD: SET-KIWAMI, MATSU" style="text-transform:uppercase;">
        </div>
        <div>
          <label>Đơn giá (VNĐ) *</label>
          <input type="number" name="gia" min="100000" step="50000" required value="2000000">
        </div>
      </div>
      <label>Tên Set Omakase *</label>
      <input type="text" name="ten_set" required placeholder="VD: Set Kiwami Grand Omakase">

      <div class="two">
        <div>
          <label>Tên tiếng Nhật</label>
          <input type="text" name="ten_tieng_nhat" placeholder="VD: 極上 おまかせ">
        </div>
        <div>
          <label>Danh mục ẩm thực *</label>
          <select name="loai_set_id" required>${catOptions}</select>
        </div>
      </div>

      <div class="two">
        <div>
          <label>Đầu bếp phụ trách</label>
          <select name="bep_truong_id">${chefOptions}</select>
        </div>
        <div>
          <label>Nhãn nổi bật (Badge)</label>
          <input type="text" name="nhan" placeholder="VD: CHEF'S CHOICE, SIGNATURE, PRESTIGE">
        </div>
      </div>

      <div class="two">
        <div>
          <label>Số món phục vụ</label>
          <input type="number" name="so_mon" min="1" max="50" value="15">
        </div>
        <div>
          <label>Thời lượng ước tính (phút)</label>
          <input type="number" name="thoi_luong_phut" min="30" max="300" step="15" value="120">
        </div>
      </div>

      <div class="two">
        <div>
          <label>Đường dẫn hình ảnh</label>
          <input type="text" name="hinh_anh" value="images/kiwami.jpg" placeholder="images/... hoặc link ảnh">
        </div>
        <div>
          <label>Trạng thái</label>
          <select name="trang_thai">
            <option value="Đang bán">Đang bán</option>
            <option value="Tạm ngừng">Tạm ngừng</option>
            <option value="Ngừng bán">Ngừng bán</option>
          </select>
        </div>
      </div>

      <label>Mô tả ngắn</label>
      <textarea name="mo_ta_ngan" rows="2" placeholder="Tóm tắt ngắn gọn trải nghiệm món ăn..."></textarea>

      <label>Mô tả chi tiết</label>
      <textarea name="mo_ta_chi_tiet" rows="3" placeholder="Các kỹ thuật chế biến, hải sản nhập khẩu trực tiếp..."></textarea>

      <div class="acts">
        <button type="button" class="btn ghost" onclick="closeModal()">Hủy</button>
        <button type="submit" class="btn"><i class="fa-solid fa-floppy-disk"></i> Lưu Sản Phẩm</button>
      </div>
    </form>
  `;
  openModal(html);
}

function openEditModal(p) {
  let catOptions = categoriesList.map(c => `
    <option value="${c.id}" ${c.id == p.loai_set_id ? 'selected' : ''}>${c.ten_loai}</option>
  `).join('');

  let chefOptions = `<option value="">-- Mặc định (Bếp trưởng) --</option>` + 
    chefsList.map(cf => `
      <option value="${cf.id}" ${cf.id == p.bep_truong_id ? 'selected' : ''}>${cf.ho_ten} (${cf.chuc_danh})</option>
    `).join('');

  const html = `
    <h2><i class="fa-solid fa-pen-to-square" style="color:var(--gold);"></i> Chỉnh Sửa Set: ${p.ten_set}</h2>
    <form action="products.php" method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="${p.id}">
      <div class="two">
        <div>
          <label>Mã sản phẩm (Cố định)</label>
          <input type="text" value="${p.ma_set}" readonly style="opacity:0.7;background:#1a1613;">
        </div>
        <div>
          <label>Đơn giá (VNĐ) *</label>
          <input type="number" name="gia" min="100000" step="50000" required value="${p.gia}">
        </div>
      </div>
      <label>Tên Set Omakase *</label>
      <input type="text" name="ten_set" required value="${p.ten_set || ''}">

      <div class="two">
        <div>
          <label>Tên tiếng Nhật</label>
          <input type="text" name="ten_tieng_nhat" value="${p.ten_tieng_nhat || ''}">
        </div>
        <div>
          <label>Danh mục ẩm thực *</label>
          <select name="loai_set_id" required>${catOptions}</select>
        </div>
      </div>

      <div class="two">
        <div>
          <label>Đầu bếp phụ trách</label>
          <select name="bep_truong_id">${chefOptions}</select>
        </div>
        <div>
          <label>Nhãn nổi bật (Badge)</label>
          <input type="text" name="nhan" value="${p.nhan || ''}">
        </div>
      </div>

      <div class="two">
        <div>
          <label>Số món phục vụ</label>
          <input type="number" name="so_mon" min="1" max="50" value="${p.so_mon}">
        </div>
        <div>
          <label>Thời lượng ước tính (phút)</label>
          <input type="number" name="thoi_luong_phut" min="30" max="300" step="15" value="${p.thoi_luong_phut}">
        </div>
      </div>

      <div class="two">
        <div>
          <label>Đường dẫn hình ảnh</label>
          <input type="text" name="hinh_anh" value="${p.hinh_anh || ''}">
        </div>
        <div>
          <label>Trạng thái</label>
          <select name="trang_thai">
            <option value="Đang bán" ${p.trang_thai === 'Đang bán' ? 'selected' : ''}>Đang bán</option>
            <option value="Tạm ngừng" ${p.trang_thai === 'Tạm ngừng' ? 'selected' : ''}>Tạm ngừng</option>
            <option value="Ngừng bán" ${p.trang_thai === 'Ngừng bán' ? 'selected' : ''}>Ngừng bán</option>
          </select>
        </div>
      </div>

      <label>Mô tả ngắn</label>
      <textarea name="mo_ta_ngan" rows="2">${p.mo_ta_ngan || ''}</textarea>

      <label>Mô tả chi tiết</label>
      <textarea name="mo_ta_chi_tiet" rows="3">${p.mo_ta_chi_tiet || ''}</textarea>

      <div class="acts">
        <button type="button" class="btn ghost" onclick="closeModal()">Hủy</button>
        <button type="submit" class="btn"><i class="fa-solid fa-floppy-disk"></i> Cập Nhật Thay Đổi</button>
      </div>
    </form>
  `;
  openModal(html);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
