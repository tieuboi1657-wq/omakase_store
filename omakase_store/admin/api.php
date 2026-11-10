<?php
require_once __DIR__ . '/config/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$action = $input['action'] ?? '';

try {
    if ($action === 'save_product') {
        $p = $input['data'] ?? [];
        $code  = trim($p['id'] ?? '');
        $name  = trim($p['name'] ?? '');
        $jp    = trim($p['jp'] ?? '');
        $cat   = trim($p['cat'] ?? 'Omakase Tối Cao Cấp');
        $price = (float)($p['price'] ?? 0);
        $courses = (int)($p['courses'] ?? 12);
        $duration = (int)($p['duration'] ?? 90);
        $badge = trim($p['badge'] ?? '');
        $status = trim($p['status'] ?? 'Đang bán');
        $img = trim($p['img'] ?? 'images/kiwami.jpg');
        $desc = trim($p['desc'] ?? '');

        // Lấy loai_set_id
        $cat_stmt = $pdo->prepare("SELECT id FROM loai_set WHERE ten_loai = ? LIMIT 1");
        $cat_stmt->execute([$cat]);
        $cat_id = $cat_stmt->fetchColumn() ?: 1;

        // Kiểm tra tồn tại
        $chk = $pdo->prepare("SELECT id FROM set_omakase WHERE ma_set = ?");
        $chk->execute([$code]);
        $existing_id = $chk->fetchColumn();

        if ($existing_id) {
            $upd = $pdo->prepare("
                UPDATE set_omakase SET 
                    ten_set = ?, ten_tieng_nhat = ?, loai_set_id = ?, 
                    gia = ?, so_mon = ?, thoi_luong_phut = ?, nhan = ?, 
                    hinh_anh = ?, mo_ta_ngan = ?, trang_thai = ?
                WHERE id = ?
            ");
            $upd->execute([$name, $jp, $cat_id, $price, $courses, $duration, $badge, $img, $desc, $status, $existing_id]);
        } else {
            $ins = $pdo->prepare("
                INSERT INTO set_omakase (
                    ma_set, ten_set, ten_tieng_nhat, loai_set_id,
                    gia, so_mon, thoi_luong_phut, nhan, hinh_anh, mo_ta_ngan, trang_thai
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([$code, $name, $jp, $cat_id, $price, $courses, $duration, $badge, $img, $desc, $status]);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete_product') {
        $code = trim($input['data']['id'] ?? $input['id'] ?? '');
        $chk = $pdo->prepare("SELECT id FROM set_omakase WHERE ma_set = ?");
        $chk->execute([$code]);
        $pid = $chk->fetchColumn();
        if ($pid) {
            $has_orders = $pdo->prepare("SELECT COUNT(*) FROM chi_tiet_don WHERE set_id = ?");
            $has_orders->execute([$pid]);
            if ((int)$has_orders->fetchColumn() > 0) {
                $pdo->prepare("UPDATE set_omakase SET trang_thai = 'Ngừng bán' WHERE id = ?")->execute([$pid]);
            } else {
                $pdo->prepare("DELETE FROM set_omakase WHERE id = ?")->execute([$pid]);
            }
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'save_order') {
        $o = $input['data'] ?? [];
        $oid    = trim($o['id'] ?? '');
        $cname  = trim($o['cname'] ?? '');
        $cid    = trim($o['cid'] ?? '');
        $set    = trim($o['set'] ?? '');
        $price  = (float)($o['price'] ?? 0);
        $guests = (int)($o['guests'] ?? 2);
        $date   = trim($o['date'] ?? date('Y-m-d'));
        $time   = trim($o['time'] ?? '19:00');
        $pay    = trim($o['pay'] ?? 'Thẻ');
        $status = trim($o['status'] ?? 'Chờ xác nhận');
        $note   = trim($o['note'] ?? '');
        $total  = $price * $guests;

        // Lấy khách hàng id
        $kh_id = null;
        if ($cid) {
            $kh_stmt = $pdo->prepare("SELECT id FROM khach_hang WHERE ma_kh = ? LIMIT 1");
            $kh_stmt->execute([$cid]);
            $kh_id = $kh_stmt->fetchColumn() ?: null;
        }

        // Lấy set_id
        $s_stmt = $pdo->prepare("SELECT id, gia FROM set_omakase WHERE ten_set = ? LIMIT 1");
        $s_stmt->execute([$set]);
        $s_row = $s_stmt->fetch();
        $set_id = $s_row ? (int)$s_row['id'] : 1;

        $chk = $pdo->prepare("SELECT id FROM don_dat_ban WHERE ma_don = ?");
        $chk->execute([$oid]);
        $don_id = $chk->fetchColumn();

        if ($don_id) {
            $upd = $pdo->prepare("
                UPDATE don_dat_ban SET 
                    ten_lien_he = ?, ngay_dung = ?, gio_dung = ?,
                    so_khach = ?, tam_tinh = ?, tong_tien = ?, phuong_thuc_tt = ?,
                    trang_thai = ?, ghi_chu = ?
                WHERE id = ?
            ");
            $upd->execute([$cname, $date, $time . ':00', $guests, $total, $total, $pay, $status, $note, $don_id]);
        } else {
            $ins = $pdo->prepare("
                INSERT INTO don_dat_ban (
                    ma_don, khach_hang_id, ten_lien_he, ngay_dung, gio_dung,
                    so_khach, tam_tinh, tong_tien, phuong_thuc_tt, trang_thai, ghi_chu
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([$oid, $kh_id, $cname, $date, $time . ':00', $guests, $total, $total, $pay, $status, $note]);
            $new_don_id = $pdo->lastInsertId();

            $ins_c = $pdo->prepare("INSERT INTO chi_tiet_don (don_id, set_id, so_luong, don_gia) VALUES (?, ?, ?, ?)");
            $ins_c->execute([$new_don_id, $set_id, $guests, $price]);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'save_customer') {
        $c = $input['data'] ?? [];
        $cid    = trim($c['id'] ?? '');
        $name   = trim($c['name'] ?? '');
        $phone  = trim($c['phone'] ?? '');
        $email  = trim($c['email'] ?? '');
        $tier   = trim($c['tier'] ?? 'Thành viên');
        $points = (int)($c['points'] ?? 0);

        $chk = $pdo->prepare("SELECT id FROM khach_hang WHERE ma_kh = ?");
        $chk->execute([$cid]);
        $kh_id = $chk->fetchColumn();

        if ($kh_id) {
            $upd = $pdo->prepare("
                UPDATE khach_hang SET 
                    ho_ten = ?, so_dien_thoai = ?, email = ?,
                    hang_thanh_vien = ?, diem_tich_luy = ?
                WHERE id = ?
            ");
            $upd->execute([$name, $phone, $email, $tier, $points, $kh_id]);
        } else {
            $hashed = password_hash('password', PASSWORD_BCRYPT);
            $ins = $pdo->prepare("
                INSERT INTO khach_hang (
                    ma_kh, ho_ten, so_dien_thoai, email, mat_khau,
                    hang_thanh_vien, diem_tich_luy, trang_thai
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Hoạt động')
            ");
            $ins->execute([$cid, $name, $phone, $email, $hashed, $tier, $points]);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete_customer') {
        $cid = trim($input['id'] ?? '');
        $pdo->prepare("DELETE FROM khach_hang WHERE ma_kh = ?")->execute([$cid]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'reply_review') {
        $rid   = (int)($input['data']['id'] ?? $input['id'] ?? 0);
        $reply = trim($input['data']['reply'] ?? $input['reply'] ?? '');
        $admin = current_admin();
        $nv_id = $admin['nhan_vien_id'] ?? 7;

        $upd = $pdo->prepare("
            UPDATE danh_gia SET 
                phan_hoi = ?, nguoi_phan_hoi_id = ?, ngay_phan_hoi = NOW(), da_xu_ly = 1
            WHERE id = ?
        ");
        $upd->execute([$reply, $nv_id, $rid]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'resolve_review') {
        $rid = (int)($input['data']['id'] ?? $input['id'] ?? 0);
        $res = !empty($input['data']['resolved'] ?? $input['resolved'] ?? false) ? 1 : 0;
        $pdo->prepare("UPDATE danh_gia SET da_xu_ly = ? WHERE id = ?")->execute([$res, $rid]);
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
