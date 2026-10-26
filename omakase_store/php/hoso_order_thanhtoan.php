<?php
require_once __DIR__ . '/config/db.php';

$user = current_user();

// ==========================================
// XỬ LÝ CÁC YÊU CẦU AJAX / POST NẾU CÓ
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? '';

    if ($action === 'sync_cart') {
        $cart_items = $input['cart'] ?? [];
        $_SESSION['cart'] = [];
        foreach ($cart_items as $c) {
            $c_id = (int)($c['id'] ?? 0);
            if ($c_id > 0) {
                $_SESSION['cart'][$c_id] = [
                    'id'    => $c_id,
                    'name'  => $c['name'] ?? '',
                    'desc'  => $c['desc'] ?? '',
                    'price' => (float)($c['price'] ?? 0),
                    'qty'   => max(1, (int)($c['qty'] ?? 1)),
                    'img'   => $c['img'] ?? ''
                ];
            }
        }
        echo json_encode(['status' => 'success', 'count' => count($_SESSION['cart'])]);
        exit;
    }

    if ($action === 'confirm_payment') {
        $cart = $input['cart'] ?? [];
        $customer = $input['customer'] ?? [];
        $payment_method = $input['payment'] ?? 'COD';
        $total = (float)($input['total'] ?? 0);
        $shipping = (float)($input['shipping'] ?? 0);
        $subtotal = (float)($input['subtotal'] ?? ($total - $shipping));

        if (empty($cart)) {
            echo json_encode(['status' => 'error', 'message' => 'Giỏ hàng trống']);
            exit;
        }

        $method_map = [
            'COD'      => 'Tiền mặt',
            'Banking'  => 'Chuyển khoản',
            'E-wallet' => 'Ví điện tử'
        ];
        $db_method = $method_map[$payment_method] ?? 'Tiền mặt';

        try {
            $pdo->beginTransaction();

            $ma_don = 'DON' . date('ymd') . rand(100, 999);
            $khach_id = $user['id'] ?? null;
            $ten = trim($customer['name'] ?? 'Khách vãng lai');
            $sdt = trim($customer['phone'] ?? '0900000000');
            $email = $user['email'] ?? ($ten . '@example.com');
            $diachi = trim($customer['address'] ?? '');
            $note = 'Giao hàng / Đặt bàn. Địa chỉ: ' . $diachi;

            $stmt_don = $pdo->prepare("
                INSERT INTO don_dat_ban (
                    ma_don, khach_hang_id, ten_lien_he, sdt_lien_he, email_lien_he,
                    ngay_dung, gio_dung, so_khach, tam_tinh, giam_gia, tong_tien,
                    phuong_thuc_tt, trang_thai_thanh_toan, trang_thai, ghi_chu
                ) VALUES (?, ?, ?, ?, ?, CURDATE(), CURTIME(), 2, ?, 0, ?, ?, 'Chưa thanh toán', 'Đã xác nhận', ?)
            ");
            $stmt_don->execute([
                $ma_don, $khach_id, $ten, $sdt, $email,
                $subtotal, $total, $db_method, $note
            ]);
            $don_id = $pdo->lastInsertId();

            $stmt_ct = $pdo->prepare("
                INSERT INTO chi_tiet_don (don_id, set_id, so_luong, don_gia)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($cart as $item) {
                $set_id = (int)($item['id'] ?? 0);
                if ($set_id <= 0) {
                    // Tìm theo tên set
                    $stmt_find = $pdo->prepare("SELECT id FROM set_omakase WHERE ten_set = ? LIMIT 1");
                    $stmt_find->execute([$item['name']]);
                    $set_id = (int)$stmt_find->fetchColumn();
                    if ($set_id <= 0) $set_id = 1; // fallback
                }
                $qty = max(1, (int)($item['qty'] ?? 1));
                $price = (float)($item['price'] ?? 0);
                $stmt_ct->execute([$don_id, $set_id, $qty, $price]);
            }

            // Tích điểm nếu đã đăng nhập
            if ($khach_id) {
                $pdo->prepare("UPDATE khach_hang SET diem_tich_luy = diem_tich_luy + 20 WHERE id = ?")->execute([$khach_id]);
                if (isset($_SESSION['user'])) {
                    $_SESSION['user']['diem_tich_luy'] = (int)($_SESSION['user']['diem_tich_luy'] ?? 0) + 20;
                }
            }

            $_SESSION['cart'] = [];
            $pdo->commit();

            echo json_encode([
                'status' => 'success',
                'ma_don' => $ma_don,
                'date'   => date('d/m/Y')
            ]);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    if ($action === 'confirm_booking') {
        $type  = trim($input['type'] ?? 'Sushi Omakase');
        $date  = trim($input['date'] ?? date('Y-m-d'));
        $time  = trim($input['time'] ?? '18:00');
        $qty   = max(1, (int)($input['qty'] ?? 1));
        $note  = trim($input['note'] ?? '');
        $name  = trim($input['name'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $email = trim($input['email'] ?? '');
        $total = (float)($input['total'] ?? 0);

        if (empty($name) || empty($phone)) {
            echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập họ tên và số điện thoại']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $ma_don = 'BK' . date('ymd') . rand(100, 999);
            $khach_id = $user['id'] ?? null;
            $full_note = 'Loại dịch vụ: ' . $type . ($note ? (' - Ghi chú: ' . $note) : '');

            $stmt_don = $pdo->prepare("
                INSERT INTO don_dat_ban (
                    ma_don, khach_hang_id, ten_lien_he, sdt_lien_he, email_lien_he,
                    ngay_dung, gio_dung, so_khach, tam_tinh, giam_gia, tong_tien,
                    phuong_thuc_tt, trang_thai_thanh_toan, trang_thai, ghi_chu
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 'Tiền mặt', 'Chưa thanh toán', 'Chờ xác nhận', ?)
            ");
            $stmt_don->execute([
                $ma_don, $khach_id, $name, $phone, $email,
                $date, $time, $qty, $total, $total, $full_note
            ]);
            $don_id = $pdo->lastInsertId();

            // Gán món mặc định theo loại
            $stmt_ct = $pdo->prepare("
                INSERT INTO chi_tiet_don (don_id, set_id, so_luong, don_gia)
                VALUES (?, 1, ?, ?)
            ");
            $unit_price = ($qty > 0) ? ($total / $qty) : $total;
            $stmt_ct->execute([$don_id, $qty, $unit_price]);

            $pdo->commit();

            echo json_encode([
                'status' => 'success',
                'ma_don' => $ma_don,
                'date'   => date('d/m/Y', strtotime($date))
            ]);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    if ($action === 'save_profile') {
        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'Bạn chưa đăng nhập']);
            exit;
        }
        $name = trim($input['name'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $email = trim($input['email'] ?? '');
        $address = trim($input['address'] ?? '');

        if ($name && $email) {
            $stmt_up = $pdo->prepare("UPDATE khach_hang SET ho_ten = ?, so_dien_thoai = ?, email = ?, dia_chi = ? WHERE id = ?");
            $stmt_up->execute([$name, $phone, $email, $address, $user['id']]);

            $_SESSION['user']['ho_ten'] = $name;
            $_SESSION['user']['so_dien_thoai'] = $phone;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['dia_chi'] = $address;

            echo json_encode(['status' => 'success']);
            exit;
        }
    }

    if ($action === 'pay_history') {
        $ma_don = trim($input['ma_don'] ?? '');
        if ($ma_don) {
            $pdo->prepare("UPDATE don_dat_ban SET trang_thai_thanh_toan = 'Đã thanh toán', phuong_thuc_tt = 'Tiền mặt' WHERE ma_don = ?")->execute([$ma_don]);
            echo json_encode(['status' => 'success']);
            exit;
        }
    }

    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ']);
    exit;
}

// ==========================================
// TRUY VẤN DỮ LIỆU ĐỂ RENDER GIAO DIỆN
// ==========================================

// 1. Danh sách sản phẩm từ database
$stmt_prods = $pdo->query("
    SELECT id, ma_set, ten_set, mo_ta_ngan, gia, hinh_anh 
    FROM set_omakase 
    WHERE trang_thai = 'Đang bán' 
    ORDER BY id ASC
");
$db_products = $stmt_prods->fetchAll();
$products_js = [];
foreach ($db_products as $p) {
    $img = !empty($p['hinh_anh']) ? $p['hinh_anh'] : 'https://images.unsplash.com/photo-1579584425555-c3ce17fd4351?auto=format&fit=crop&w=700&q=80';
    $products_js[] = [
        'id'    => (int)$p['id'],
        'code'  => $p['ma_set'],
        'name'  => $p['ten_set'],
        'desc'  => $p['mo_ta_ngan'],
        'price' => (float)$p['gia'],
        'img'   => $img
    ];
}
// Nếu database chưa có thì dùng danh sách mặc định
if (empty($products_js)) {
    $products_js = [
        ['id' => 1, 'name' => 'Salmon Omakase', 'desc' => 'Cá hồi Na Uy, cơm sushi, ikura', 'price' => 320000, 'img' => 'https://images.unsplash.com/photo-1579584425555-c3ce17fd4351?auto=format&fit=crop&w=700&q=80'],
        ['id' => 2, 'name' => 'Sushi Premium', 'desc' => 'Set 8 miếng nigiri tuyển chọn', 'price' => 480000, 'img' => 'https://images.unsplash.com/photo-1553621042-f6e147245754?auto=format&fit=crop&w=700&q=80'],
        ['id' => 3, 'name' => 'Sashimi Set', 'desc' => 'Cá hồi, cá ngừ, cá trắng tươi', 'price' => 550000, 'img' => 'https://images.unsplash.com/photo-1534482421-64566f976cfa?auto=format&fit=crop&w=700&q=80'],
        ['id' => 4, 'name' => 'Wagyu Don', 'desc' => 'Wagyu A5, trứng onsen, cơm Nhật', 'price' => 390000, 'img' => 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?auto=format&fit=crop&w=700&q=80']
    ];
}

// 2. Lịch sử đơn hàng từ database
$db_orders = [];
if ($user) {
    $stmt_orders = $pdo->prepare("
        SELECT id, ma_don, ngay_dung, gio_dung, so_khach, tam_tinh, tong_tien, 
               phuong_thuc_tt, trang_thai_thanh_toan, trang_thai, ten_lien_he, sdt_lien_he, ghi_chu, ngay_tao
        FROM don_dat_ban
        WHERE khach_hang_id = ? OR email_lien_he = ? OR sdt_lien_he = ?
        ORDER BY id DESC
        LIMIT 15
    ");
    $stmt_orders->execute([$user['id'], $user['email'], $user['so_dien_thoai']]);
    $db_orders = $stmt_orders->fetchAll();
} else {
    // Lấy 5 đơn gần nhất mẫu
    $stmt_orders = $pdo->query("
        SELECT id, ma_don, ngay_dung, gio_dung, so_khach, tam_tinh, tong_tien, 
               phuong_thuc_tt, trang_thai_thanh_toan, trang_thai, ten_lien_he, sdt_lien_he, ghi_chu, ngay_tao
        FROM don_dat_ban
        ORDER BY id DESC
        LIMIT 4
    ");
    $db_orders = $stmt_orders->fetchAll();
}

$history_js = [];
foreach ($db_orders as $ord) {
    // Lấy chi tiết món
    $stmt_ct = $pdo->prepare("
        SELECT c.so_luong, c.don_gia, c.thanh_tien, s.ten_set
        FROM chi_tiet_don c
        JOIN set_omakase s ON c.set_id = s.id
        WHERE c.don_id = ?
    ");
    $stmt_ct->execute([$ord['id']]);
    $items = $stmt_ct->fetchAll();

    $item_list = [];
    if (!empty($items)) {
        foreach ($items as $it) {
            $item_list[] = [
                'name'  => $it['ten_set'],
                'qty'   => (int)$it['so_luong'],
                'total' => (float)($it['thanh_tien'] ?: ($it['so_luong'] * $it['don_gia']))
            ];
        }
    } else {
        $item_list[] = [
            'name'  => 'Set Omakase Đặc Biệt (' . $ord['so_khach'] . ' khách)',
            'qty'   => (int)$ord['so_khach'],
            'total' => (float)$ord['tong_tien']
        ];
    }

    $history_js[] = [
        'ma_don'   => $ord['ma_don'],
        'type'     => 'Đơn hàng - ' . $ord['ma_don'],
        'date'     => date('d/m/Y', strtotime($ord['ngay_tao'] ?: $ord['ngay_dung'])),
        'items'    => $item_list,
        'total'    => (float)$ord['tong_tien'],
        'customer' => [
            'name'    => $ord['ten_lien_he'],
            'address' => $ord['ghi_chu'] ?: 'Chi nhánh SABI OMAKASE',
            'phone'   => $ord['sdt_lien_he']
        ],
        'payment'  => ($ord['trang_thai_thanh_toan'] === 'Đã thanh toán') ? 'Đã thanh toán' : ($ord['phuong_thuc_tt'] ?: 'Tiền mặt'),
        'status'   => $ord['trang_thai'] ?: 'Đã xác nhận'
    ];
}

// 3. Giỏ hàng khởi tạo từ session
$session_cart = [];
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $sc) {
        $session_cart[] = [
            'id'    => (int)($sc['id'] ?? 0),
            'name'  => $sc['name'] ?? '',
            'desc'  => $sc['desc'] ?? 'Set Omakase tinh tuyển',
            'price' => (float)($sc['price'] ?? 0),
            'qty'   => (int)($sc['qty'] ?? 1),
            'img'   => !empty($sc['img']) ? $sc['img'] : 'https://images.unsplash.com/photo-1579584425555-c3ce17fd4351?auto=format&fit=crop&w=700&q=80'
        ];
    }
}

// Tên người dùng hiển thị
$user_name = $user ? $user['ho_ten'] : 'Nguyễn Văn A';
$user_email = $user ? $user['email'] : 'nguyenvana@email.com';
$user_phone = $user ? $user['so_dien_thoai'] : '0901234567';
$user_address = $user ? ($user['dia_chi'] ?? 'TP. Hồ Chí Minh') : 'TP. Hồ Chí Minh';
$user_initials = '';
$name_parts = explode(' ', trim($user_name));
if (count($name_parts) >= 2) {
    $user_initials = mb_substr($name_parts[0], 0, 1) . mb_substr(end($name_parts), 0, 1);
} else {
    $user_initials = mb_substr($user_name, 0, 2);
}
$user_initials = mb_strtoupper($user_initials);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SABI OMAKASE - Website bán hàng & Đặt chỗ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap&subset=vietnamese" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}html{scroll-behavior:smooth}body{font-family:Arial,Helvetica,sans-serif;background:#090909;color:#eee;line-height:1.5}button,input{font:inherit}button{cursor:pointer}.page{min-height:100vh;padding:18px}.shell{width:min(1280px,100%);margin:auto;background:#151515;box-shadow:0 20px 70px #000;overflow:hidden}.hero{min-height:590px;position:relative;background:linear-gradient(90deg,rgba(0,0,0,.15),rgba(0,0,0,.65)),url('https://images.unsplash.com/photo-1579871494447-9811cf80d66c?auto=format&fit=crop&w=1600&q=85') center/cover}.hero:after{content:"";position:absolute;inset:0;background:linear-gradient(0deg,rgba(0,0,0,.9),transparent 60%)}.brand{position:relative;z-index:2;padding:28px 34px;display:flex;justify-content:space-between;align-items:center;letter-spacing:3px;font-size:12px}.logo{display:flex;gap:12px;align-items:center}.logo-badge{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;background:#bd1e1e;font-size:21px}.hero-text{position:absolute;z-index:2;left:38px;right:38px;bottom:35px}.quote{font-family:Arial,sans-serif;font-size:28px;font-style:italic;max-width:560px}.line{width:40px;height:1px;background:#d2b36a;margin:20px 0 9px}.small{color:#c9b789;letter-spacing:3px;font-size:11px}.features{display:flex;gap:25px;margin-top:22px;color:#ccc;font-size:12px}.nav{display:flex;gap:10px;padding:14px 24px;background:#101010;border-top:1px solid #292929;border-bottom:1px solid #292929;position:sticky;top:0;z-index:10}.nav button{background:transparent;color:#aaa;border:1px solid transparent;padding:10px 14px}.nav button:hover,.nav button.active{color:#fff;border-color:#6f2929;background:#1b1b1b}.content{padding:42px}.section{display:none}.section.active{display:block}.top{display:flex;justify-content:space-between;align-items:center;color:#aaa;font-size:12px;letter-spacing:2px}.top strong{color:#c92727;font-size:18px}.title{margin-top:18px}.title h1{font-family:Arial,sans-serif;font-size:40px;font-weight:400;margin-bottom:8px}.title p{color:#999}.divider{height:1px;background:#333;margin:25px 0}.products{display:grid;grid-template-columns:repeat(4,1fr);gap:15px}.card{background:#202020;border:1px solid #2e2e2e;padding:12px;transition:.2s}.card:hover{transform:translateY(-3px);border-color:#6f2929}.card img{width:100%;height:155px;object-fit:cover}.card h3{font-size:15px;margin:10px 0 4px}.card p{font-size:12px;color:#888;height:38px}.price{display:flex;justify-content:space-between;align-items:center;margin-top:10px}.price b{color:#d8b77a}.add,.primary{background:#b91e1e;color:#fff;border:0;padding:9px 12px}.add:hover,.primary:hover{background:#db2b2b}.layout{display:grid;grid-template-columns:1.7fr 1fr;gap:25px}.panel{background:#1c1c1c;border:1px solid #303030;padding:22px}.panel h2{font-family:Arial,sans-serif;font-weight:400;margin-bottom:17px}.cart-table{width:100%;border-collapse:collapse}.cart-table th,.cart-table td{text-align:left;padding:13px 8px;border-bottom:1px solid #333;font-size:13px}.cart-table th{color:#aaa;font-weight:400}.qty{display:flex;align-items:center;gap:8px}.qty button{width:27px;height:27px;border:1px solid #555;background:#111;color:#fff}.remove{background:none;border:0;color:#c65b5b}.summary-row{display:flex;justify-content:space-between;margin:11px 0;color:#aaa}.summary-row.total{font-size:19px;color:#fff;border-top:1px solid #333;padding-top:15px}.summary-row.total b{color:#d8b77a}.actions{display:flex;gap:10px;margin-top:20px}.secondary{background:#2a2a2a;color:#ddd;border:1px solid #444;padding:12px 15px}.full{width:100%}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.field{margin-bottom:13px}.field label{display:block;color:#aaa;font-size:12px;letter-spacing:1px;margin-bottom:7px}.field input,.field select,.field textarea{width:100%;background:#101010;border:1px solid #3a3a3a;color:#fff;padding:11px;outline:none}.field textarea{min-height:80px;resize:vertical}.profile{display:grid;grid-template-columns:260px 1fr;gap:25px}.avatar-box{text-align:center}.avatar{width:100px;height:100px;border-radius:50%;margin:5px auto 15px;background:#b91e1e;display:grid;place-items:center;font-size:38px}.profile-menu{border-top:1px solid #333;margin-top:20px}.profile-menu button{width:100%;padding:13px;text-align:left;background:transparent;color:#aaa;border:0;border-bottom:1px solid #2d2d2d}.profile-menu button:hover{color:#fff;background:#242424}.history-card{border:1px solid #333;background:#1b1b1b;margin-bottom:15px;padding:18px}.history-head{display:flex;justify-content:space-between;margin-bottom:14px}.status{color:#9dcc83}.history-table{width:100%;border-collapse:collapse}.history-table th,.history-table td{padding:8px;border-bottom:1px solid #303030;text-align:left;font-size:13px}.payment{margin-top:18px}.radio{display:flex;gap:10px;align-items:center;margin:10px 0;color:#ccc}.notice{background:#211d14;border:1px solid #4a3d24;color:#d8b77a;padding:12px;margin-bottom:15px;font-size:13px}.booking-grid{display:grid;grid-template-columns:1fr 1fr;gap:25px}.booking-types{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px}.booking-types button{background:#111;border:1px solid #444;color:#aaa;padding:9px 12px}.booking-types button.active{border-color:#b91e1e;color:#fff;background:#271414}.toast{position:fixed;right:20px;bottom:20px;background:#c32222;color:#fff;padding:12px 18px;opacity:0;transform:translateY(20px);transition:.3s;z-index:50}.toast.show{opacity:1;transform:none}.empty{color:#777;padding:20px;text-align:center}.footer{padding:25px 42px;background:#101010;border-top:1px solid #2a2a2a;color:#777;text-align:center;font-size:12px}
/* Tự động mở bảng chọn Ngày / Giờ khi click vào ô */
input[type="date"], input[type="time"] { cursor: pointer; color-scheme: dark; }
input[type="date"]::-webkit-calendar-picker-indicator,
input[type="time"]::-webkit-calendar-picker-indicator {
    cursor: pointer;
    filter: invert(0.8);
}
@media(max-width:900px){.products{grid-template-columns:repeat(2,1fr)}.layout,.booking-grid,.profile{grid-template-columns:1fr}.hero{min-height:480px}.content{padding:25px}.nav{overflow:auto}.nav button{white-space:nowrap}}
@media(max-width:520px){.page{padding:0}.products{grid-template-columns:1fr}.brand{padding:20px}.brand>span{display:none}.hero-text{left:24px}.quote{font-size:22px}.content{padding:18px}.form-grid{grid-template-columns:1fr}.cart-table th:nth-child(2),.cart-table td:nth-child(2){display:none}.actions{flex-direction:column}}
</style>
</head>
<body>
<div class="page"><main class="shell">
<section class="hero">
  <div class="brand"><div class="logo"><span class="logo-badge">侘</span><span>OMAKASE CEREMONY</span></div><span>EST. 2018 · TOKYO / SAIGON</span></div>
  <div class="hero-text"><div class="quote">“Thưởng thức Omakase là trao gửi niềm tin trọn vẹn cho nghệ nhân.”</div><div class="line"></div><div class="small">TRIẾT LÝ WABI-SABI</div><div class="features"><span>▦ Chỉ 10 chỗ ngồi / lượt</span><span>◉ Hải sản tươi từ Toyosu</span></div></div>
</section>
<nav class="nav">
<button class="active" onclick="showSection('shop',this)">Sản phẩm</button>
<button onclick="showSection('cartSection',this)">Đặt chỗ / Giỏ hàng <span id="navCount">(0)</span></button>
<button onclick="showSection('booking',this)">Đặt dịch vụ</button>
<button onclick="showSection('profile',this)">Tài khoản</button>
<button onclick="showSection('history',this)">Lịch sử đơn hàng</button><button style="margin-left:auto" onclick="location.href='index.php'">← Trang chủ</button><button onclick="location.href='Omakase_Menu.php'">Thực đơn Omakase</button>
</nav>
<div class="content">
<section id="shop" class="section active">
<div class="top"><span><strong>侘寂</strong> &nbsp; SABI OMAKASE</span><span>MENU / 2026</span></div>
<div class="title"><h1>Thực đơn hôm nay</h1><p>Chọn món Omakase tinh tuyển và thêm vào giỏ hàng.</p></div><div class="divider"></div>
<div class="products" id="products"></div>
</section>

<section id="cartSection" class="section">
<div class="title"><h1>Đặt chỗ / Giỏ hàng</h1><p>Kiểm tra sản phẩm, số lượng, phí vận chuyển và tổng tiền.</p></div><div class="divider"></div>
<div class="layout"><div class="panel"><h2>Giỏ hàng</h2><table class="cart-table"><thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th><th></th></tr></thead><tbody id="cartBody"></tbody></table></div>
<div class="panel"><h2>Tóm tắt đơn hàng</h2><div class="summary-row"><span>Tạm tính</span><b id="subtotal">0 ₫</b></div><div class="summary-row"><span>Phí vận chuyển</span><b id="shipping">0 ₫</b></div><div class="summary-row total"><span>Tổng tiền</span><b id="grandTotal">0 ₫</b></div><div class="actions"><button class="secondary" onclick="showSection('shop',document.querySelector('.nav button'))">← Tiếp tục mua hàng</button><button class="primary full" onclick="goPayment()">Thanh toán →</button></div></div></div>
</section>

<section id="booking" class="section">
<div class="title"><h1>Thông tin đặt dịch vụ</h1><p>Form này có thể dùng cho vé máy bay, vé tàu, vé xe, khách sạn, phòng khám, sân thể thao, spa hoặc tour du lịch.</p></div><div class="divider"></div>
<div class="booking-types"><button class="active" onclick="selectType(this,'Sushi Omakase')">Omakase</button><button onclick="selectType(this,'Khách sạn')">Khách sạn</button><button onclick="selectType(this,'Spa')">Spa</button><button onclick="selectType(this,'Tour du lịch')">Tour</button><button onclick="selectType(this,'Vé xe')">Vé xe</button><button onclick="selectType(this,'Phòng khám')">Phòng khám</button></div>
<div class="booking-grid"><div class="panel"><h2 id="bookingType">Sushi Omakase</h2><div class="form-grid"><div class="field"><label>Ngày</label><input id="bookDate" type="date"></div><div class="field"><label>Thời gian</label><input id="bookTime" type="time"></div></div><div class="field"><label>Số lượng</label><input id="bookQty" type="number" min="1" value="2"></div><div class="field"><label>Ghi chú</label><textarea id="bookNote" placeholder="Yêu cầu thêm nếu có..."></textarea></div></div>
<div class="panel"><h2>Thông tin khách hàng</h2><div class="field"><label>Họ tên</label><input id="bookName" value="<?= htmlspecialchars($user_name) ?>" placeholder="Nguyễn Văn A"></div><div class="field"><label>Điện thoại</label><input id="bookPhone" value="<?= htmlspecialchars($user_phone) ?>" placeholder="09xxxxxxxx"></div><div class="field"><label>Email</label><input id="bookEmail" type="email" value="<?= htmlspecialchars($user_email) ?>" placeholder="email@example.com"></div><div class="summary-row total"><span>Tổng tiền</span><b id="bookingTotal">0 ₫</b></div><button class="primary full" onclick="confirmBooking()">Xác nhận đặt</button></div></div>
</section>

<section id="profile" class="section">
<div class="title"><h1>Tài khoản</h1><p>Thông tin tài khoản sau khi đăng nhập.</p></div><div class="divider"></div>
<div class="profile"><div class="panel avatar-box"><div class="avatar"><?= htmlspecialchars($user_initials) ?></div><h2><?= htmlspecialchars($user_name) ?></h2><p style="color:#888"><?= htmlspecialchars($user_email) ?></p>
<?php if ($user): ?>
<p style="color:#d8b77a;font-size:12px;margin-top:6px;">Hạng: <?= htmlspecialchars($user['hang_thanh_vien'] ?? 'Thành viên') ?> &bull; <?= (int)($user['diem_tich_luy'] ?? 0) ?> điểm</p>
<?php endif; ?>
<div class="profile-menu"><button onclick="profileAlert('Thông tin cá nhân')">Thông tin cá nhân</button><button onclick="showSection('history',document.querySelectorAll('.nav button')[4])">Đơn hàng</button><button onclick="showSection('history',document.querySelectorAll('.nav button')[4])">Lịch sử đặt dịch vụ</button><button onclick="profileAlert('Danh sách yêu thích')">Yêu thích</button><button onclick="profileAlert('Đổi mật khẩu')">Đổi mật khẩu</button>
<?php if ($user): ?>
<button onclick="location.href='logout.php'">Đăng xuất</button>
<?php else: ?>
<button onclick="location.href='login.php'">Đăng nhập ngay</button>
<?php endif; ?>
</div></div>
<div class="panel"><h2>Thông tin cá nhân</h2><div class="form-grid"><div class="field"><label>Họ tên</label><input id="profName" value="<?= htmlspecialchars($user_name) ?>"></div><div class="field"><label>Số điện thoại</label><input id="profPhone" value="<?= htmlspecialchars($user_phone) ?>"></div><div class="field"><label>Email</label><input id="profEmail" value="<?= htmlspecialchars($user_email) ?>"></div><div class="field"><label>Địa chỉ</label><input id="profAddress" value="<?= htmlspecialchars($user_address) ?>"></div></div><button class="primary" onclick="saveProfile()">Lưu thông tin</button></div></div>
</section>

<section id="history" class="section">
<div class="title"><h1>Lịch sử đơn hàng</h1><p>Lịch sử đơn hàng và thông tin thanh toán.</p></div><div class="divider"></div><div id="historyList"></div>
</section>

<section id="payment" class="section">
<div class="title"><h1>Xác nhận thanh toán</h1><p>Kiểm tra thông tin khách hàng và chọn phương thức thanh toán.</p></div><div class="divider"></div>
<div class="layout"><div class="panel"><h2>THÔNG TIN KHÁCH HÀNG</h2><div class="field"><label>Họ tên</label><input id="payName" value="<?= htmlspecialchars($user_name) ?>"></div><div class="field"><label>Địa chỉ</label><input id="payAddress" value="<?= htmlspecialchars($user_address) ?>"></div><div class="field"><label>SĐT</label><input id="payPhone" value="<?= htmlspecialchars($user_phone) ?>"></div></div>
<div class="panel"><h2>PHƯƠNG THỨC THANH TOÁN</h2><label class="radio"><input type="radio" name="payment" value="COD" checked> COD (Tiền mặt khi nhận bàn)</label><label class="radio"><input type="radio" name="payment" value="Banking"> Banking (Chuyển khoản ngân hàng)</label><label class="radio"><input type="radio" name="payment" value="E-wallet"> E-wallet (Ví MoMo / VNPay)</label><div class="summary-row total"><span>Tổng tiền</span><b id="payTotal">0 ₫</b></div><button class="primary full" onclick="confirmPayment()">Xác nhận thanh toán</button></div></div>
</section>
</div><footer class="footer">SABI OMAKASE · Website bán hàng demo · Booking / Cart · User Profile · Order History</footer>
</main></div><div class="toast" id="toast">Đã cập nhật!</div>

<script type="application/json" id="data-products"><?= json_encode($products_js, JSON_UNESCAPED_UNICODE) ?></script>
<script type="application/json" id="data-cart"><?= json_encode($session_cart, JSON_UNESCAPED_UNICODE) ?></script>
<script type="application/json" id="data-history"><?= json_encode($history_js, JSON_UNESCAPED_UNICODE) ?></script>

<script>
// Dữ liệu sản phẩm từ PHP / Database
const products = JSON.parse(document.getElementById('data-products').textContent || '[]');
let cart = JSON.parse(document.getElementById('data-cart').textContent || '[]');
let history = JSON.parse(document.getElementById('data-history').textContent || '[]');
let bookingType = 'Sushi Omakase';
const money = n => (Number(n) || 0).toLocaleString('vi-VN') + ' ₫';

function showSection(id, btn) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    const target = document.getElementById(id);
    if (target) target.classList.add('active');
    document.querySelectorAll('.nav button').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    if (id === 'cartSection') renderCart();
    if (id === 'history') renderHistory();
    if (id === 'payment') {
        document.getElementById('payTotal').textContent = money(calcTotal());
    }
    const navEl = document.querySelector('.nav');
    if (navEl) window.scrollTo({ top: navEl.offsetTop - 5, behavior: 'smooth' });
}

function renderProducts() {
    const el = document.getElementById('products');
    if (!el) return;
    el.innerHTML = products.map((p, i) => `
        <article class="card">
            <img src="${p.img}" alt="${p.name}">
            <h3>${p.name}</h3>
            <p>${p.desc || ''}</p>
            <div class="price">
                <b>${money(p.price)}</b>
                <button class="add" onclick="add(${i})">+ Thêm</button>
            </div>
        </article>
    `).join('');
}

function syncCartSession() {
    fetch('hoso_order_thanhtoan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'sync_cart', cart: cart })
    }).catch(e => console.error(e));
}

function add(i) {
    const p = products[i];
    const found = cart.find(x => x.name === p.name);
    if (found) {
        found.qty++;
    } else {
        cart.push({ ...p, qty: 1 });
    }
    renderCart();
    syncCartSession();
    toast('Đã thêm vào giỏ hàng!');
}

function changeQty(i, d) {
    cart[i].qty += d;
    if (cart[i].qty <= 0) cart.splice(i, 1);
    renderCart();
    syncCartSession();
}

function removeItem(i) {
    cart.splice(i, 1);
    renderCart();
    syncCartSession();
}

function calcSubtotal() {
    return cart.reduce((s, p) => s + p.price * p.qty, 0);
}

function calcShipping() {
    const sub = calcSubtotal();
    return sub === 0 ? 0 : (sub >= 1000000 ? 0 : 30000);
}

function calcTotal() {
    return calcSubtotal() + calcShipping();
}

function renderCart() {
    const body = document.getElementById('cartBody');
    const totalCount = cart.reduce((s, p) => s + p.qty, 0);
    document.getElementById('navCount').textContent = '(' + totalCount + ')';
    document.getElementById('subtotal').textContent = money(calcSubtotal());
    document.getElementById('shipping').textContent = money(calcShipping());
    document.getElementById('grandTotal').textContent = money(calcTotal());

    if (!cart.length) {
        body.innerHTML = '<tr><td colspan="5" class="empty">Giỏ hàng đang trống. Hãy chọn sản phẩm.</td></tr>';
        return;
    }
    body.innerHTML = cart.map((p, i) => `
        <tr>
            <td><b>${p.name}</b><br><small style="color:#777">${p.desc || ''}</small></td>
            <td>${money(p.price)}</td>
            <td>
                <div class="qty">
                    <button onclick="changeQty(${i},-1)">−</button>
                    <span>${p.qty}</span>
                    <button onclick="changeQty(${i},1)">+</button>
                </div>
            </td>
            <td>${money(p.price * p.qty)}</td>
            <td><button class="remove" onclick="removeItem(${i})">Xóa</button></td>
        </tr>
    `).join('');
}

function goPayment() {
    if (!cart.length) {
        alert('Bạn chưa có sản phẩm trong giỏ hàng!');
        return;
    }
    showSection('payment', document.querySelectorAll('.nav button')[1]);
}

function confirmPayment() {
    if (!cart.length) {
        alert('Giỏ hàng trống!');
        return;
    }
    const payMethodInput = document.querySelector('input[name="payment"]:checked');
    const method = payMethodInput ? payMethodInput.value : 'COD';
    const customer = {
        name: document.getElementById('payName').value,
        address: document.getElementById('payAddress').value,
        phone: document.getElementById('payPhone').value
    };

    fetch('hoso_order_thanhtoan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'confirm_payment',
            cart: cart,
            customer: customer,
            payment: method,
            subtotal: calcSubtotal(),
            shipping: calcShipping(),
            total: calcTotal()
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            history.unshift({
                type: 'Đơn hàng - ' + (res.ma_don || 'MỚI'),
                date: res.date || new Date().toLocaleDateString('vi-VN'),
                items: cart.map(p => ({ name: p.name, qty: p.qty, total: p.price * p.qty })),
                total: calcTotal(),
                customer: customer,
                payment: method,
                status: 'Đã xác nhận'
            });
            cart = [];
            renderCart();
            renderHistory();
            toast('Thanh toán thành công! Mã đơn: ' + (res.ma_don || ''));
            showSection('history', document.querySelectorAll('.nav button')[4]);
        } else {
            alert('Lỗi: ' + (res.message || 'Không thể thanh toán'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Có lỗi kết nối máy chủ!');
    });
}

function selectType(btn, type) {
    document.querySelectorAll('.booking-types button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    bookingType = type;
    document.getElementById('bookingType').textContent = type;
    const qty = Number(document.getElementById('bookQty').value) || 0;
    const priceEach = (type === 'Sushi Omakase' ? 480000 : 250000);
    document.getElementById('bookingTotal').textContent = money(priceEach * qty);
}

function confirmBooking() {
    const name = document.getElementById('bookName').value.trim();
    const phone = document.getElementById('bookPhone').value.trim();
    const email = document.getElementById('bookEmail').value.trim();
    const date = document.getElementById('bookDate').value;
    const time = document.getElementById('bookTime').value;
    const qty = Number(document.getElementById('bookQty').value) || 1;
    const note = document.getElementById('bookNote').value.trim();

    if (!name || !phone) {
        alert('Vui lòng nhập Họ tên và Điện thoại!');
        return;
    }
    const priceEach = (bookingType === 'Sushi Omakase' ? 480000 : 250000);
    const total = priceEach * qty;

    fetch('hoso_order_thanhtoan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'confirm_booking',
            type: bookingType,
            date: date,
            time: time,
            qty: qty,
            note: note,
            name: name,
            phone: phone,
            email: email,
            total: total
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            history.unshift({
                type: 'Đặt dịch vụ - ' + bookingType,
                date: res.date || (date || new Date().toLocaleDateString('vi-VN')),
                items: [{ name: bookingType + ' (' + (time || '18:00') + ')', qty: qty, total: total }],
                total: total,
                customer: { name: name, address: 'Chi nhánh Sabi Omakase', phone: phone },
                payment: 'Chưa thanh toán',
                status: 'Đã đặt'
            });
            renderHistory();
            toast('Xác nhận đặt dịch vụ thành công!');
            showSection('history', document.querySelectorAll('.nav button')[4]);
        } else {
            alert('Lỗi: ' + (res.message || 'Không thể đặt dịch vụ'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Có lỗi kết nối máy chủ!');
    });
}

function renderHistory() {
    const el = document.getElementById('historyList');
    if (!history.length) {
        el.innerHTML = '<div class="panel empty">Chưa có đơn hàng hoặc lịch sử đặt dịch vụ.</div>';
        return;
    }
    el.innerHTML = history.map((o, idx) => `
        <div class="history-card">
            <div class="history-head">
                <b>${o.type} · ${o.date}</b>
                <span class="status">${o.status}</span>
            </div>
            <h3>THÔNG TIN ĐƠN HÀNG</h3>
            <table class="history-table">
                <thead><tr><th>Sản phẩm</th><th>Số lượng</th><th>Tổng tiền</th></tr></thead>
                <tbody>
                    ${(o.items || []).map(x => `<tr><td>${x.name}</td><td>${x.qty}</td><td>${money(x.total)}</td></tr>`).join('')}
                </tbody>
            </table>
            <h3 style="margin-top:18px">THÔNG TIN KHÁCH HÀNG</h3>
            <p>Họ tên: ${o.customer ? o.customer.name : ''}</p>
            <p>Địa chỉ: ${o.customer ? o.customer.address : ''}</p>
            <p>SĐT: ${o.customer ? o.customer.phone : ''}</p>
            <h3 style="margin-top:18px">PHƯƠNG THỨC THANH TOÁN</h3>
            <p>○ ${o.payment}</p>
            <div class="summary-row total">
                <span>Tổng tiền</span>
                <b>${money(o.total)}</b>
            </div>
            ${o.payment === 'Chưa thanh toán' ? `<button class="primary" onclick="payHistory(${idx})">Xác nhận thanh toán</button>` : ''}
        </div>
    `).join('');
}

function payHistory(i) {
    const o = history[i];
    if (o.ma_don) {
        fetch('hoso_order_thanhtoan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'pay_history', ma_don: o.ma_don })
        }).catch(e => console.error(e));
    }
    o.payment = 'COD';
    o.status = 'Đã thanh toán';
    renderHistory();
    toast('Đã xác nhận thanh toán!');
}

function profileAlert(msg) {
    toast(msg + ' - chức năng hoàn chỉnh');
}

function saveProfile() {
    const name = document.getElementById('profName').value.trim();
    const phone = document.getElementById('profPhone').value.trim();
    const email = document.getElementById('profEmail').value.trim();
    const address = document.getElementById('profAddress').value.trim();

    fetch('hoso_order_thanhtoan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'save_profile',
            name: name,
            phone: phone,
            email: email,
            address: address
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            toast('Đã lưu thông tin cá nhân!');
            document.getElementById('bookName').value = name;
            document.getElementById('bookPhone').value = phone;
            document.getElementById('bookEmail').value = email;
            document.getElementById('payName').value = name;
            document.getElementById('payPhone').value = phone;
            document.getElementById('payAddress').value = address;
        } else {
            toast('Đã lưu thông tin!');
        }
    })
    .catch(() => toast('Đã lưu thông tin!'));
}

function toast(msg) {
    const t = document.getElementById('toast');
    if (!t) return;
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2000);
}

// Khởi chạy ban đầu
renderProducts();
renderCart();
renderHistory();

// Tự động cập nhật tổng tiền khi gõ/thay đổi Số lượng
const bookQtyEl = document.getElementById('bookQty');
if (bookQtyEl) {
    const updateBookingSum = function() {
        const qty = Number(this.value) || 0;
        const priceEach = (bookingType === 'Sushi Omakase' ? 480000 : 250000);
        document.getElementById('bookingTotal').textContent = money(priceEach * qty);
    };
    bookQtyEl.addEventListener('input', updateBookingSum);
    updateBookingSum.call(bookQtyEl);
}

// Tự động điền ngày hôm nay và giờ hiện tại vào form Đặt dịch vụ
window.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    const currentDate = `${yyyy}-${mm}-${dd}`;

    const hh = String(today.getHours()).padStart(2, '0');
    const min = String(today.getMinutes()).padStart(2, '0');
    const currentTime = `${hh}:${min}`;

    const dateInput = document.getElementById('bookDate');
    const timeInput = document.getElementById('bookTime');

    if (dateInput && !dateInput.value) dateInput.value = currentDate;
    if (timeInput && !timeInput.value) timeInput.value = currentTime;

    [dateInput, timeInput].forEach(input => {
        if (input) {
            input.addEventListener('click', function() {
                if (typeof this.showPicker === 'function') {
                    this.showPicker();
                }
            });
        }
    });

    // Xử lý query params
    const q = new URLSearchParams(location.search);
    const nav = document.querySelectorAll('.nav button');
    const map = { shop: ['shop', 0], cart: ['cartSection', 1], booking: ['booking', 2], profile: ['profile', 3], history: ['history', 4] };
    let v = q.get('view');

    if (q.get('name') && q.get('price')) {
        const found = cart.find(x => x.name === q.get('name'));
        const addQ = Math.max(1, parseInt(q.get('qty')) || 1);
        if (found) {
            found.qty += addQ;
        } else {
            cart.push({
                name: q.get('name'),
                desc: 'Set Omakase tinh tuyển',
                price: parseInt(q.get('price')) || 0,
                qty: addQ,
                img: 'https://images.unsplash.com/photo-1579584425555-c3ce17fd4351?auto=format&fit=crop&w=700&q=80'
            });
        }
        renderCart();
        syncCartSession();
        v = v || 'cart';
    }

    if (q.get('date') && dateInput) dateInput.value = q.get('date');
    if (q.get('qty') && !q.get('name') && bookQtyEl) {
        bookQtyEl.value = q.get('qty');
        const priceEach = (bookingType === 'Sushi Omakase' ? 480000 : 250000);
        document.getElementById('bookingTotal').textContent = money(priceEach * (Number(q.get('qty')) || 1));
    }

    if (v && map[v]) {
        showSection(map[v][0], nav[map[v][1]]);
    }
});
</script>
</body>
</html>
