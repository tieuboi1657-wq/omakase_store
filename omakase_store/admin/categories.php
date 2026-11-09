<?php
$page_title = 'Quản Lý Danh Mục Ẩm Thực';
require_once __DIR__ . '/includes/header.php';

// Xử lý Thêm / Sửa / Xóa danh mục
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $ten_loai = trim($_POST['ten_loai'] ?? '');
        $mo_ta    = trim($_POST['mo_ta'] ?? '');
        $thu_tu   = (int)($_POST['thu_tu'] ?? 0);

        if (empty($ten_loai)) {
            set_flash('err', 'Tên danh mục không được để trống.');
        } else {
            $ins = $pdo->prepare("INSERT INTO loai_set (ten_loai, mo_ta, thu_tu) VALUES (?, ?, ?)");
            $ins->execute([$ten_loai, $mo_ta, $thu_tu]);
            set_flash('ok', "Đã thêm danh mục mới: $ten_loai thành công!");
        }
        header("Location: categories.php");
        exit;
    }

    if ($action === 'edit') {
        $id       = (int)($_POST['id'] ?? 0);
        $ten_loai = trim($_POST['ten_loai'] ?? '');
        $mo_ta    = trim($_POST['mo_ta'] ?? '');
        $thu_tu   = (int)($_POST['thu_tu'] ?? 0);

        if (empty($ten_loai)) {
            set_flash('err', 'Tên danh mục không được để trống.');
        } else {
            $upd = $pdo->prepare("UPDATE loai_set SET ten_loai = ?, mo_ta = ?, thu_tu = ? WHERE id = ?");
            $upd->execute([$ten_loai, $mo_ta, $thu_tu, $id]);
            set_flash('ok', "Đã cập nhật danh mục: $ten_loai thành công!");
        }
        header("Location: categories.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $chk = $pdo->prepare("SELECT COUNT(*) FROM set_omakase WHERE loai_set_id = ?");
        $chk->execute([$id]);
        $has_prods = (int)$chk->fetchColumn();

        if ($has_prods > 0) {
            set_flash('err', "Không thể xóa danh mục này vì đang có $has_prods set Omakase thuộc danh mục. Vui lòng chuyển các set sang loại khác trước!");
        } else {
            $del = $pdo->prepare("DELETE FROM loai_set WHERE id = ?");
            $del->execute([$id]);
            set_flash('ok', 'Đã xóa danh mục thành công!');
        }
        header("Location: categories.php");
        exit;
    }
}

// Lấy danh sách danh mục kèm số lượng món
$cats_stmt = $pdo->query("
    SELECT l.*, COUNT(s.id) AS so_set 
    FROM loai_set l 
    LEFT JOIN set_omakase s ON l.id = s.loai_set_id 
    GROUP BY l.id 
    ORDER BY l.thu_tu ASC, l.id ASC
");
$categories = $cats_stmt->fetchAll();
?>

<div class="head">
  <div>
    <h1>Quản Lý Danh Mục Thực Đơn</h1>
    <p>Quản lý các phân loại phong cách Omakase (Trưa, Tối, Kaiseki, Theo mùa, v.v.).</p>
  </div>
  <div class="head-actions">
    <button type="button" class="btn ghost" onclick="exportTableToCSV('catsTable', 'danh-muc-omakase.csv')">
      <i class="fa-solid fa-file-csv"></i> Xuất CSV
    </button>
    <button type="button" class="btn" onclick="openAddCatModal()">
      <i class="fa-solid fa-plus"></i> Thêm Danh Mục Mới
    </button>
  </div>
</div>

<div class="panel">
  <div class="tw">
    <table id="catsTable">
      <thead>
        <tr>
          <th style="width:70px;">Thứ tự</th>
          <th>Tên Danh Mục</th>
          <th>Mô Tả Danh Mục</th>
          <th style="text-align:center;">Số Set Thuộc Loại</th>
          <th class="no-export" style="text-align:right;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($categories)): ?>
          <tr><td colspan="5" class="empty">Chưa có danh mục nào trong hệ thống.</td></tr>
        <?php else: ?>
          <?php foreach ($categories as $c): ?>
            <tr>
              <td><b>#<?= (int)$c['thu_tu'] ?></b></td>
              <td>
                <div style="font-weight:600;font-size:14px;color:var(--text);"><?= htmlspecialchars($c['ten_loai']) ?></div>
                <div class="sub">ID: <?= $c['id'] ?></div>
              </td>
              <td><?= htmlspecialchars($c['mo_ta'] ?: 'Chưa có mô tả chi tiết') ?></td>
              <td style="text-align:center;">
                <span class="badge b-gold"><?= (int)$c['so_set'] ?> set</span>
              </td>
              <td class="no-export" style="text-align:right;white-space:nowrap;">
                <button type="button" class="btn ghost sm" onclick='openEditCatModal(<?= json_encode($c, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                  <i class="fa-solid fa-pen-to-square"></i> Sửa
                </button>
                <form action="categories.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục <?= htmlspecialchars(addslashes($c['ten_loai'])) ?>?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $c['id'] ?>">
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
function openAddCatModal() {
  const html = `
    <h2><i class="fa-solid fa-plus" style="color:var(--gold);"></i> Thêm Danh Mục Mới</h2>
    <form action="categories.php" method="POST">
      <input type="hidden" name="action" value="add">
      <label>Tên Danh Mục *</label>
      <input type="text" name="ten_loai" required placeholder="VD: Omakase Tối Cao Cấp">

      <div class="two">
        <div>
          <label>Thứ tự hiển thị</label>
          <input type="number" name="thu_tu" value="1" min="1">
        </div>
      </div>

      <label>Mô tả ngắn về phong cách ẩm thực</label>
      <textarea name="mo_ta" rows="3" placeholder="Nhập tóm tắt mô tả phong vị món ăn..."></textarea>

      <div class="acts">
        <button type="button" class="btn ghost" onclick="closeModal()">Hủy</button>
        <button type="submit" class="btn"><i class="fa-solid fa-floppy-disk"></i> Lưu Danh Mục</button>
      </div>
    </form>
  `;
  openModal(html);
}

function openEditCatModal(c) {
  const html = `
    <h2><i class="fa-solid fa-pen-to-square" style="color:var(--gold);"></i> Sửa Danh Mục: ${c.ten_loai}</h2>
    <form action="categories.php" method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="${c.id}">
      <label>Tên Danh Mục *</label>
      <input type="text" name="ten_loai" required value="${c.ten_loai || ''}">

      <div class="two">
        <div>
          <label>Thứ tự hiển thị</label>
          <input type="number" name="thu_tu" value="${c.thu_tu || 1}" min="1">
        </div>
      </div>

      <label>Mô tả ngắn về phong cách ẩm thực</label>
      <textarea name="mo_ta" rows="3">${c.mo_ta || ''}</textarea>

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
