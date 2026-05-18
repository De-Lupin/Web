<?php
// ============================================================
// ADMIN.PHP - Đăng nhập dành riêng cho Admin
// Giữ nguyên giao diện cũ — sửa logic: kết nối MySQL + 2FA thật
// ============================================================
session_start();
require 'config.php';

// Đã đăng nhập admin rồi → vào dashboard
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$error_message   = "";
$success_message = "";
$show_verification_form = false;

// Kiểm tra đang ở bước 2 (xác minh OTP)
if (isset($_SESSION['admin_pending_verification']) && $_SESSION['admin_pending_verification'] === true) {
    $show_verification_form = true;

    // Xử lý nhập mã OTP
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_code'])) {
        $entered_code = trim($_POST['verify_code'] ?? '');

        if (empty($entered_code)) {
            $error_message = "Vui lòng nhập mã xác minh!";

        } elseif (time() - $_SESSION['admin_verification_time'] > 300) {
            // Hết hạn 5 phút
            unset($_SESSION['admin_pending_verification'], $_SESSION['admin_verification_code'],
                  $_SESSION['admin_temp_uid'],            $_SESSION['admin_verification_time']);
            $show_verification_form = false;
            $error_message = "Mã xác minh đã hết hạn (5 phút). Vui lòng đăng nhập lại!";

        } elseif ($entered_code !== $_SESSION['admin_verification_code']) {
            $error_message = "Mã xác minh không chính xác!";

        } else {
            // ✅ XÁC MINH THÀNH CÔNG — lấy thông tin admin từ DB
            $uid  = $_SESSION['admin_temp_uid'];
            $stmt = $conn->prepare(
                "SELECT id, username, full_name, email FROM users WHERE id = ? AND role = 'admin' LIMIT 1"
            );
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            session_regenerate_id(true);
            $_SESSION['user_id']    = $admin['id'];
            $_SESSION['username']   = $admin['username'];
            $_SESSION['full_name']  = $admin['full_name'];
            $_SESSION['email']      = $admin['email'];
            $_SESSION['role']       = 'admin';
            $_SESSION['login_time'] = time();

            // Xóa dữ liệu tạm
            unset($_SESSION['admin_pending_verification'], $_SESSION['admin_verification_code'],
                  $_SESSION['admin_temp_uid'],            $_SESSION['admin_verification_time']);

            // Cập nhật last_login + ghi log
            $upd = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $upd->bind_param("i", $admin['id']); $upd->execute(); $upd->close();
            write_audit_log($conn, $admin['id'], $admin['username'], 'ADMIN_LOGIN', 'Đăng nhập Admin thành công (2FA)');

            header("Location: admin_dashboard.php");
            exit();
        }
    }
}

// Xử lý nút "Quay lại" ở bước 2
if (isset($_POST['back_step1'])) {
    unset($_SESSION['admin_pending_verification'], $_SESSION['admin_verification_code'],
          $_SESSION['admin_temp_uid'],            $_SESSION['admin_verification_time']);
    header("Location: admin.php");
    exit();
}

// ── BƯỚC 1: Kiểm tra username / password / email ─────────
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['verify_code']) && !isset($_POST['back_step1'])) {
    $admin_username = trim($_POST['admin_username'] ?? '');
    $admin_password = trim($_POST['admin_password'] ?? '');
    $admin_email    = trim($_POST['admin_email']    ?? '');

    if (empty($admin_username) || empty($admin_password) || empty($admin_email)) {
        $error_message = "Vui lòng nhập đầy đủ thông tin!";

    } else {
        // Truy vấn MySQL — chỉ lấy tài khoản role = admin
        $stmt = $conn->prepare(
            "SELECT id, username, password, email, full_name, is_active
             FROM users WHERE username = ? AND role = 'admin' LIMIT 1"
        );
        $stmt->bind_param("s", $admin_username);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$admin) {
            $error_message = "Tài khoản Admin không tồn tại!";
            write_audit_log($conn, null, $admin_username, 'ADMIN_LOGIN_FAILED', 'Tài khoản không tồn tại');

        } elseif (!$admin['is_active']) {
            $error_message = "Tài khoản Admin đã bị khóa!";

        } elseif (strtolower($admin['email']) !== strtolower($admin_email)) {
            $error_message = "Email không khớp với tài khoản!";
            write_audit_log($conn, $admin['id'], $admin_username, 'ADMIN_LOGIN_FAILED', 'Sai email');

        } elseif (!password_verify($admin_password, $admin['password'])) {
            $error_message = "Mật khẩu không đúng!";
            write_audit_log($conn, $admin['id'], $admin_username, 'ADMIN_LOGIN_FAILED', 'Sai mật khẩu');

        } else {
            // ✅ BƯỚC 1 OK — Tạo mã OTP 6 số
            $otp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

            $_SESSION['admin_pending_verification']  = true;
            $_SESSION['admin_verification_code']     = $otp;
            $_SESSION['admin_temp_uid']              = $admin['id'];
            $_SESSION['admin_verification_time']     = time();

            // Trong thực tế: gửi email thật bằng PHPMailer hoặc mail()
            // mail($admin['email'], 'Mã xác minh Admin', "Mã của bạn: $otp");

            // DEMO: Hiển thị mã trực tiếp để test
            $success_message = "Mã xác minh đã gửi đến: <b>" . htmlspecialchars($admin['email']) . "</b><br>"
                             . "<small style='color:#555'>(Demo) Mã của bạn: <b style='font-size:18px;color:#c0392b'>$otp</b></small>";
            $show_verification_form = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── Giữ nguyên style cũ, bổ sung thêm countdown ── */
        .admin-login-wrapper {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .admin-login-container {
            background: white;
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
        }
        .admin-login-container h1 {
            text-align: center;
            margin-bottom: 10px;
            color: #333;
            font-size: 28px;
        }
        .admin-login-container .subtitle {
            text-align: center;
            margin-bottom: 30px;
            color: #666;
            font-size: 14px;
        }
        .admin-login-container .admin-icon {
            text-align: center;
            margin-bottom: 30px;
            font-size: 50px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            font-size: 13px;
        }
        .admin-form-group {
            margin-bottom: 20px;
        }
        .admin-form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        .admin-form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: 0.3s;
            box-sizing: border-box;
        }
        .admin-form-group input:focus {
            border-color: #667eea;
            box-shadow: 0 0 8px rgba(102,126,234,0.2);
            outline: none;
        }
        .admin-login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .admin-login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102,126,234,0.4);
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link a:hover { text-decoration: underline; }
        .security-info {
            background: #e3f2fd;
            padding: 12px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 12px;
            color: #1976d2;
            text-align: center;
        }
        .resend-btn {
            padding: 8px 20px;
            background-color: #95a5a6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: 0.3s;
        }
        .resend-btn:hover { background-color: #7f8c8d; }
        /* Đếm ngược */
        .countdown-wrap {
            text-align: center;
            margin-top: 12px;
            font-size: 13px;
            color: #666;
        }
        .countdown-wrap #otp_timer {
            font-weight: 700;
            color: #c0392b;
        }
    </style>
</head>
<body>
    <div class="admin-login-wrapper">
        <div class="admin-login-container">
            <div class="admin-icon">🔐</div>
            <h1><?= $show_verification_form ? "Xác Minh Email" : "Admin Login" ?></h1>
            <p class="subtitle">
                <?= $show_verification_form
                    ? "Nhập mã 6 chữ số được gửi đến email"
                    : "Đăng nhập tài khoản quản trị viên" ?>
            </p>

            <?php if ($error_message): ?>
                <div class="error-message"><?= $error_message ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="success-message"><?= $success_message ?></div>
            <?php endif; ?>

            <?php if (!$show_verification_form): ?>
            <!-- BƯỚC 1: Form đăng nhập -->
            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="POST">
                <div class="admin-form-group">
                    <label>Tên đăng nhập Admin</label>
                    <input type="text" name="admin_username" placeholder="Nhập tên admin"
                           value="<?= htmlspecialchars($_POST['admin_username'] ?? '') ?>" required>
                </div>
                <div class="admin-form-group">
                    <label>Mật khẩu Admin</label>
                    <input type="password" name="admin_password" placeholder="Nhập mật khẩu" required>
                </div>
                <div class="admin-form-group">
                    <label>Email Admin</label>
                    <input type="email" name="admin_email" placeholder="Nhập email"
                           value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
                </div>
                <button type="submit" class="admin-login-btn">Tiếp tục →</button>
            </form>
            <div class="security-info">
                Test: <b>admin</b> / <b>123456</b> / <b>admin@vantai.vn</b>
            </div>

            <?php else: ?>
            <!-- BƯỚC 2: Nhập mã OTP -->
            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="POST">
                <div class="admin-form-group">
                    <label>Mã Xác Minh 6 Chữ Số</label>
                    <input type="text" name="verify_code" placeholder="Nhập mã 6 chữ số"
                           maxlength="6" pattern="[0-9]{6}"
                           autocomplete="one-time-code"
                           style="letter-spacing:6px;font-size:20px;text-align:center"
                           required autofocus>
                </div>
                <div class="countdown-wrap">
                    Mã hết hạn sau: <span id="otp_timer">05:00</span>
                </div>
                <br>
                <button type="submit" class="admin-login-btn">✅ Xác Minh Đăng Nhập</button>
            </form>

            <div style="text-align:center;margin-top:16px">
                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="POST">
                    <button type="submit" name="back_step1" value="1" class="resend-btn">← Quay lại</button>
                </form>
            </div>
            <?php endif; ?>

            <div class="back-link">
                <a href="indext.php">← Quay lại trang đăng nhập thường</a>
            </div>
        </div>
    </div>

<?php if ($show_verification_form): ?>
<script>
// Đếm ngược 5 phút
let secs = 300;
const el = document.getElementById('otp_timer');
const t  = setInterval(() => {
    secs--;
    if (secs <= 0) { clearInterval(t); el.textContent = 'Hết hạn!'; return; }
    const m = String(Math.floor(secs/60)).padStart(2,'0');
    const s = String(secs % 60).padStart(2,'0');
    el.textContent = m + ':' + s;
}, 1000);
</script>
<?php endif; ?>
</body>
</html>
