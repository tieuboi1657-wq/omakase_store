<?php
require_once __DIR__ . '/config/db.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';
$msg = '';
if (isset($_GET['registered'])) {
    $msg = 'Đăng ký thành công! Mời bạn đăng nhập.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập tài khoản và mật khẩu.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM khach_hang WHERE email = ? OR so_dien_thoai = ? LIMIT 1");
        $stmt->execute([$email, $email]);
        $customer = $stmt->fetch();

        if ($customer && (password_verify($password, $customer['mat_khau']) || $password === 'password' || $password === $customer['mat_khau'])) {
            $_SESSION['user'] = $customer;
            header("Location: index.php");
            exit;
        } else {
            $error = 'Tài khoản hoặc mật khẩu không chính xác. (Mật khẩu mẫu: password)';
        }
    }
}
?>
<html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Đăng Nhập - Sabi Omakase</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link
    href="https://fonts.googleapis.com/css2?family=Noto+Serif:wght@500;600;700&family=Noto+Sans:wght@400;500;600;700&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="css/style.css" />
</head>

  <div class="auth-page-centered">
    <div class="auth-split-card">

      <!-- ============ CỘT TRÁI: HÌNH ẢNH + TRÍCH DẪN ============ -->
      <div class="auth-visual" style="position:relative; padding:40px; display:flex; flex-direction:column; justify-content:space-between;">

        <!-- Header: logo tròn đỏ + tên + EST. -->
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <a href="index.php" style="display:flex; align-items:center; gap:12px; text-decoration:none;">
            <div style="width:32px; height:32px; border-radius:50%; background:var(--color-primary); display:flex; align-items:center; justify-content:center; font-size:13px; color:#fff; font-family:var(--font-headline);">寂</div>
            <span style="color:rgba(255,255,255,0.7); font-size:10px; letter-spacing:4px; text-transform:uppercase; font-weight:500;">OMAKASE CEREMONY</span>
          </a>
          <span style="font-size:9px; letter-spacing:3px; color:rgba(255,255,255,0.5); text-transform:uppercase;">EST. 2018 · TOKYO / SAIGON</span>
        </div>

        <!-- Bottom: quote + meta -->
        <div>
          <blockquote style="font-family:var(--font-headline); font-size:20px; font-style:italic; line-height:1.5; color:#fff; margin-bottom:14px; position:relative;">"Thưởng thức Omakase là trao gửi niềm tin trọn vẹn cho nghệ nhân."</blockquote>
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:28px;">
            <div style="width:24px; height:1px; background:#a48b89;"></div>
            <cite style="font-size:9px; color:#a48b89; letter-spacing:3px; text-transform:uppercase; font-style:normal;">TRIẾT LÝ WABI-SABI</cite>
          </div>

          <div style="display:flex; gap:24px; font-size:11px; color:rgba(255,255,255,0.55);">
            <span style="display:flex; align-items:center; gap:7px;"><i class="fa-regular fa-square-check"></i> Chỉ 10 chỗ ngồi / lượt</span>
            <span style="display:flex; align-items:center; gap:7px;"><i class="fa-solid fa-circle-check"></i> Hải sản tươi từ Toyosu</span>
          </div>
        </div>
      </div>

      <!-- ============ CỘT PHẢI: FORM ĐĂNG NHẬP ============ -->
      <div class="auth-form-panel">
        <div class="auth-form-inner" style="max-width:420px;">

          <!-- Header right panel: 侘寂 SABI OMAKASE  •  •  • -->
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:40px;">
            <span style="font-family:var(--font-headline); font-size:14px; color:#d38d8d; letter-spacing:1px;">侘寂</span>
            <span style="font-size:10px; letter-spacing:4px; color:rgba(255,255,255,0.5); text-transform:uppercase;">SABI OMAKASE</span>
            <div style="margin-left:auto; display:flex; gap:5px;">
              <span style="width:6px; height:6px; border-radius:50%; background:#8c2f2f; display:inline-block;"></span>
              <span style="width:6px; height:6px; border-radius:50%; background:#8c2f2f; display:inline-block;"></span>
              <span style="width:6px; height:6px; border-radius:50%; background:#8c2f2f; display:inline-block;"></span>
            </div>
          </div>

          <h1 style="font-family:var(--font-headline); font-size:34px; color:#fff; font-weight:normal; margin-bottom:14px; line-height:1.2;">Đăng Nhập Tài Khoản</h1>
          <p style="margin-bottom:36px; color:rgba(255,255,255,0.45); font-size:13px; line-height:1.5;">Chào mừng quý khách quay trở lại với không gian ẩm thực Sabi.</p>

          <?php if (!empty($error)): ?>
            <div style="background:rgba(220,53,69,0.2);border:1px solid #dc3545;color:#ff8080;padding:12px 14px;border-radius:6px;margin-bottom:20px;font-size:13px;">
              <?= htmlspecialchars($error) ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($msg)): ?>
            <div style="background:rgba(40,167,69,0.2);border:1px solid #28a745;color:#75e691;padding:12px 14px;border-radius:6px;margin-bottom:20px;font-size:13px;">
              <?= htmlspecialchars($msg) ?>
            </div>
          <?php endif; ?>

          <form action="login.php" method="POST">
            <!-- Email field -->
            <div style="margin-bottom:22px;">
              <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <label for="login-email" style="font-size:9px; color:#a48b89; text-transform:uppercase; letter-spacing:0.15em;">TÀI KHOẢN HOẶC EMAIL</label>
                <span style="font-size:9px; color:rgba(255,255,255,0.3); letter-spacing:0.05em;">Bắt buộc</span>
              </div>
              <div style="position:relative;">
                <i class="fa-regular fa-user" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:rgba(255,255,255,0.25); font-size:13px;"></i>
                <input type="text" name="email" id="login-email" required value="<?= htmlspecialchars($_POST['email'] ?? 'thuongkhach@omakase.vn') ?>" placeholder="tenkhachhang@domain.com" style="width:100%; background:#111; border:none; border-radius:6px; color:#fff; padding:16px 16px 16px 44px; font-size:13px; font-family:var(--font-body); box-sizing:border-box;" />
              </div>
            </div>

            <!-- Password field -->
            <div style="margin-bottom:22px;">
              <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <label for="login-password" style="font-size:9px; color:#a48b89; text-transform:uppercase; letter-spacing:0.15em;">MẬT KHẨU THƯỢNG KHÁCH</label>
                <span style="font-size:9px; color:rgba(255,255,255,0.3); letter-spacing:0.05em;">Bảo mật</span>
              </div>
              <div style="position:relative;">
                <i class="fa-solid fa-lock" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:rgba(255,255,255,0.25); font-size:13px;"></i>
                <input type="password" name="password" id="login-password" required value="password" placeholder="••••••••••••" style="width:100%; background:#111; border:none; border-radius:6px; color:#fff; padding:16px 44px 16px 44px; font-size:16px; font-family:var(--font-body); letter-spacing:3px; box-sizing:border-box;" />
                <button type="button" id="toggle-pw" style="position:absolute; right:14px; top:50%; transform:translateY(-50%); background:none; border:none; color:rgba(255,255,255,0.3); cursor:pointer; font-size:15px; padding:0;"><i class="fa-regular fa-eye-slash"></i></button>
              </div>
            </div>

            <!-- Remember + Forgot -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:28px;">
              <label style="display:flex; align-items:center; gap:10px; color:rgba(255,255,255,0.6); font-size:12px; cursor:pointer;">
                <input type="checkbox" id="remember-me" style="width:15px; height:15px; accent-color:var(--color-primary); background:#111; border:none;" /> Ghi nhớ đăng nhập
              </label>
              <a href="#" style="color:#d38d8d; font-size:12px; text-decoration:underline; text-underline-offset:3px;">Quên mật khẩu?</a>
            </div>

            <!-- Submit -->
            <button type="submit" style="width:100%; background:#8c2f2f; border:none; border-radius:6px; color:#fff; padding:16px; font-size:12px; letter-spacing:3px; text-transform:uppercase; cursor:pointer; display:flex; justify-content:center; align-items:center; gap:10px; font-family:var(--font-body);">ĐĂNG NHẬP <i class="fa-solid fa-arrow-right"></i></button>
          </form>

          <!-- Divider -->
          <div style="display:flex; align-items:center; gap:12px; margin:28px 0; color:rgba(255,255,255,0.2); font-size:9px; letter-spacing:2px; text-transform:uppercase;">
            <div style="flex:1; height:1px; background:rgba(255,255,255,0.1);"></div>
            HOẶC TIẾP TỤC VỚI
            <div style="flex:1; height:1px; background:rgba(255,255,255,0.1);"></div>
          </div>

          <!-- Social buttons -->
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:36px;">
            <button type="button" style="background:#111; border:none; border-radius:6px; color:#fff; padding:14px; font-size:12px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; font-family:var(--font-body);"><i class="fa-brands fa-apple" style="font-size:15px;"></i> Apple ID</button>
            <button type="button" style="background:#111; border:none; border-radius:6px; color:#fff; padding:14px; font-size:12px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; font-family:var(--font-body);"><i class="fa-brands fa-google" style="font-size:13px;"></i> Google</button>
          </div>

          <!-- Footer note -->
          <p style="text-align:center; font-size:12px; color:rgba(255,255,255,0.4);">
            Chưa có tài khoản thượng khách?
            <a href="register.php" style="color:#d38d8d; font-weight:700; letter-spacing:1px; text-transform:uppercase; text-decoration:underline; text-underline-offset:3px; margin-left:6px; font-size:11px;">ĐĂNG KÝ NGAY</a>
          </p>

        </div>
      </div>

    </div>
  </div>

<script>
  var toggleBtn = document.getElementById('toggle-pw');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', function () {
      var inp = document.getElementById('login-password');
      var icon = toggleBtn.querySelector('i');
      if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'fa-regular fa-eye';
      } else {
        inp.type = 'password';
        icon.className = 'fa-regular fa-eye-slash';
      }
    });
  }
</script>
</body>
</html>