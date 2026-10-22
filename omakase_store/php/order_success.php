<?php
require_once __DIR__ . '/config/db.php';

$ma_don = $_GET['ma_don'] ?? '';
if (empty($ma_don)) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT d.*, b.ten_ban, b.khu_vuc
    FROM don_dat_ban d
    LEFT JOIN ban b ON d.ban_id = b.id
    WHERE d.ma_don = ?
");
$stmt->execute([$ma_don]);
$order = $stmt->fetch();

if (!$order) {
    die("Không tìm thấy đơn đặt bàn với mã: " . htmlspecialchars($ma_don));
}

// Lấy chi tiết món
$stmt_items = $pdo->prepare("
    SELECT ct.*, s.ten_set, s.ten_tieng_nhat, s.hinh_anh
    FROM chi_tiet_don ct
    JOIN set_omakase s ON ct.set_id = s.id
    WHERE ct.don_id = ?
");
$stmt_items->execute([$order['id']]);
$items = $stmt_items->fetchAll();

$page_title = 'Xác Nhận Đặt Bàn Thành Công - Sabi Omakase';
require_once __DIR__ . '/includes/header.php';
?>

<div style="background:#0a0a0a;min-height:85vh;padding:50px 20px;display:flex;justify-content:center;align-items:center;">
    <div style="max-width:680px;width:100%;background:#141414;border:1px solid #2e2e2e;border-radius:12px;padding:36px;box-shadow:0 20px 60px rgba(0,0,0,0.8);position:relative;">
        
        <!-- Header thiệp xác nhận -->
        <div style="text-align:center;margin-bottom:30px;border-bottom:1px solid #252525;padding-bottom:24px;">
            <div style="width:50px;height:50px;background:rgba(40,167,69,0.15);border:1px solid #28a745;color:#28a745;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:14px;">
                <i class="fa-solid fa-check"></i>
            </div>
            <span style="display:block;font-size:11px;color:#d8b77a;letter-spacing:4px;text-transform:uppercase;">RESERVATION CONFIRMED</span>
            <h1 style="font-family:var(--font-headline);font-size:28px;color:#fff;margin:6px 0 8px;">Đặt Bàn Thành Công</h1>
            <p style="color:#888;font-size:13px;">Cảm ơn quý khách đã gửi gắm trải nghiệm ẩm thực cho nghệ nhân Sabi Omakase.</p>
        </div>

        <!-- Thông tin mã đơn & trạng thái -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;background:#1b1b1b;border:1px solid #282828;padding:16px;border-radius:8px;margin-bottom:24px;font-size:13px;">
            <div>
                <span style="color:#777;font-size:11px;text-transform:uppercase;display:block;">MÃ ĐẶT BÀN</span>
                <strong style="color:#d8b77a;font-size:16px;letter-spacing:1px;"><?= htmlspecialchars($order['ma_don']) ?></strong>
            </div>
            <div>
                <span style="color:#777;font-size:11px;text-transform:uppercase;display:block;">TRẠNG THÁI</span>
                <span style="color:#75e691;font-weight:600;"><i class="fa-solid fa-circle" style="font-size:8px;margin-right:5px;"></i> <?= htmlspecialchars($order['trang_thai']) ?></span>
            </div>
            <div>
                <span style="color:#777;font-size:11px;text-transform:uppercase;display:block;">NGÀY & GIỜ DÙNG</span>
                <strong style="color:#fff;"><?= date('d/m/Y', strtotime($order['ngay_dung'])) ?> lúc <?= date('H:i', strtotime($order['gio_dung'])) ?></strong>
            </div>
            <div>
                <span style="color:#777;font-size:11px;text-transform:uppercase;display:block;">SỐ LƯỢNG KHÁCH</span>
                <strong style="color:#fff;"><?= $order['so_khach'] ?> Khách <?= !empty($order['ten_ban']) ? ' • ' . htmlspecialchars($order['ten_ban']) : '' ?></strong>
            </div>
        </div>

        <!-- Chi tiết các món đã đặt -->
        <div style="margin-bottom:24px;">
            <h3 style="font-size:13px;color:#aaa;letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;">CHI TIẾT SET MÓN</h3>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <?php foreach ($items as $it): ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;background:#191919;padding:12px 14px;border-radius:6px;font-size:13px;">
                        <div>
                            <strong style="color:#fff;"><?= htmlspecialchars($it['ten_set']) ?></strong>
                            <div style="color:#777;font-size:11px;"><?= format_vnd($it['don_gia']) ?> x <?= $it['so_luong'] ?> suất</div>
                        </div>
                        <div style="color:#d8b77a;font-weight:700;">
                            <?= format_vnd($it['thanh_tien'] ?? ($it['don_gia'] * $it['so_luong'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Bảng tính tiền -->
        <div style="border-top:1px solid #282828;padding-top:16px;margin-bottom:24px;font-size:13px;">
            <div style="display:flex;justify-content:space-between;color:#888;margin-bottom:6px;">
                <span>Tạm tính:</span>
                <span style="color:#ccc;"><?= format_vnd($order['tam_tinh']) ?></span>
            </div>
            <?php if ($order['giam_gia'] > 0): ?>
                <div style="display:flex;justify-content:space-between;color:#28a745;margin-bottom:6px;">
                    <span>Ưu đãi thành viên:</span>
                    <span>-<?= format_vnd($order['giam_gia']) ?></span>
                </div>
            <?php endif; ?>
            <div style="display:flex;justify-content:space-between;color:#fff;font-size:18px;font-weight:700;margin-top:10px;padding-top:10px;border-top:1px solid #282828;">
                <span>Tổng chi phí:</span>
                <span style="color:#d8b77a;"><?= format_vnd($order['tong_tien']) ?></span>
            </div>
            <div style="color:#777;font-size:11px;margin-top:6px;">
                Phương thức thanh toán: <strong><?= htmlspecialchars($order['phuong_thuc_tt']) ?></strong> (<?= htmlspecialchars($order['trang_thai_thanh_toan']) ?>)
            </div>
        </div>

        <!-- Thông tin người liên hệ -->
        <div style="background:#181818;border-left:3px solid #8c2f2f;padding:12px 16px;border-radius:4px;margin-bottom:28px;font-size:12px;color:#bbb;">
            <div>Người liên hệ: <strong style="color:#fff;"><?= htmlspecialchars($order['ten_lien_he']) ?></strong> (<?= htmlspecialchars($order['sdt_lien_he']) ?>)</div>
            <?php if (!empty($order['ghi_chu'])): ?>
                <div style="margin-top:4px;">Ghi chú khẩu vị: <span style="color:#d8b77a;"><?= htmlspecialchars($order['ghi_chu']) ?></span></div>
            <?php endif; ?>
        </div>

        <!-- Nút điều hướng -->
        <div style="display:flex;gap:12px;">
            <a href="index.php" class="btn btn-outline" style="flex:1;text-align:center;padding:14px;text-decoration:none;">
                <i class="fa-solid fa-house"></i> Về Trang Chủ
            </a>
            <a href="history.php" class="btn btn-primary" style="flex:1;text-align:center;padding:14px;text-decoration:none;">
                <i class="fa-solid fa-clock-rotate-left"></i> Xem Lịch Sử Đặt
            </a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
