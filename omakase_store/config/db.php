<?php
// Bật session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = '127.0.0.1';
$db_name = 'omakase_store_ltw9';
$db_user = 'root';
$db_pass = '';
$db_ports = [3307, 3306]; // Thử port 3307 (XAMPP MariaDB hiện tại) trước, fallback sang 3306

$pdo = null;
foreach ($db_ports as $port) {
    try {
        $dsn = "mysql:host={$db_host};port={$port};dbname={$db_name};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
        if ($pdo) {
            break;
        }
    } catch (PDOException $e) {
        // Thử port tiếp theo
        continue;
    }
}

if (!$pdo) {
    die("Lỗi kết nối cơ sở dữ liệu: Không thể kết nối tới MySQL trên các cổng " . implode(', ', $db_ports) . ". Vui lòng kiểm tra MySQL trong XAMPP Control Panel.");
}

// Khởi tạo giỏ hàng trong session nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Hàm format tiền tệ VNĐ
function format_vnd($amount) {
    return number_format((float)$amount, 0, ',', '.') . ' ₫';
}

// Hàm kiểm tra đăng nhập
function is_logged_in() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

// Lấy thông tin user hiện tại
function current_user() {
    return $_SESSION['user'] ?? null;
}

// Hàm đếm tổng số lượng món trong giỏ
function cart_total_items() {
    if (empty($_SESSION['cart'])) return 0;
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += (int)($item['qty'] ?? 1);
    }
    return $count;
}
