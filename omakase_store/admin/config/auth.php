<?php
// Khởi tạo session cho khu vực Quản trị
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kết nối CSDL từ cấu hình chung của hệ thống
require_once __DIR__ . '/../../config/db.php';

// Kiểm tra xem quản trị viên đã đăng nhập chưa
function is_admin_logged_in() {
    return isset($_SESSION['admin_user']) && !empty($_SESSION['admin_user']['id']);
}

// Bắt buộc phải đăng nhập, nếu chưa thì chuyển hướng đến trang login
function require_admin() {
    if (!is_admin_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

// Lấy thông tin tài khoản quản trị hiện tại
function current_admin() {
    return $_SESSION['admin_user'] ?? null;
}

// Kiểm tra quyền hạn
function admin_can($required_role) {
    $user = current_admin();
    if (!$user) return false;
    $roles = ['admin' => 3, 'quan_ly' => 2, 'nhan_vien' => 1];
    $user_level = $roles[$user['quyen'] ?? 'nhan_vien'] ?? 0;
    $req_level  = $roles[$required_role] ?? 0;
    return $user_level >= $req_level;
}

// Thiết lập thông báo Flash Message
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type'    => $type, // 'ok', 'err', 'warn'
        'message' => $message
    ];
}

// Lấy và xóa thông báo Flash Message
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Định dạng tiền tệ ngắn gọn (ví dụ 3.8tr)
function format_short_money($num) {
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'tr';
    }
    return number_format($num, 0, ',', '.') . 'đ';
}
