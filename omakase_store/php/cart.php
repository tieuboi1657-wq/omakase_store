<?php
require_once __DIR__ . '/config/db.php';

if (isset($_GET['add_set_id'])) {
    $sid = (int) $_GET['add_set_id'];
    $stmt = $pdo->prepare("SELECT id, ten_set, gia, mo_ta_ngan, hinh_anh FROM set_omakase WHERE id = ?");
    $stmt->execute([$sid]);
    $item = $stmt->fetch();
    if ($item) {
        $found = false;
        if (!isset($_SESSION['cart']))
            $_SESSION['cart'] = [];
        foreach ($_SESSION['cart'] as &$c) {
            if ($c['id'] == $sid) {
                $c['qty'] += 1;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['cart'][$sid] = [
                'id' => $item['id'],
                'name' => $item['ten_set'],
                'desc' => $item['mo_ta_ngan'],
                'price' => (float) $item['gia'],
                'qty' => 1,
                'img' => !empty($item['hinh_anh']) ? $item['hinh_anh'] : 'images/kiwami.jpg'
            ];
        }
    }
}

if (!isset($_GET['view'])) {
    $_GET['view'] = 'cart';
}

require __DIR__ . '/hoso_order_thanhtoan.php';
