<?php

session_start();

// Nếu admin đã đăng nhập rồi, chuyển đến dashboard
if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) {
    header("Location: admin_dashboard.php");
    exit();
}

$error_message = "";
$success_message = "";
$show_verification_form = false;

// Thông tin admin (bạn nên thay đổi thông tin này hoặc kết nối database)
$valid_username = "admin";
$valid_password = "admin123";
$valid_email = "admin@example.com";

// Kiểm tra nếu ở bước xác minh email
if (isset($_SESSION['admin_pending_verification']) && $_SESSION['admin_pending_verification'] === true) {
    $show_verification_form = true;

    // Xử lý xác minh mã code
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_code'])) {
        $entered_code = $_POST['verify_code'] ?? '';

        if (!empty($entered_code)) {
            // Kiểm tra mã xác minh
            if ($entered_code === $_SESSION['admin_verification_code']) {
                // Xác minh thành công
                $_SESSION['admin_loggedin'] = true;
                $_SESSION['admin_username'] = $_SESSION['admin_temp_username'];
                $_SESSION['admin_email'] = $_SESSION['admin_temp_email'];
                $_SESSION['admin_login_time'] = time();

                // Xóa dữ liệu tạm
                unset($_SESSION['admin_pending_verification']);
                unset($_SESSION['admin_verification_code']);
                unset($_SESSION['admin_temp_username']);
                unset($_SESSION['admin_temp_email']);
                unset($_SESSION['admin_verification_time']);

                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error_message = "Mã xác minh không chính xác!";
            }
        } else {
            $error_message = "Vui lòng nhập mã xác minh!";
        }
    }
}

// Xử lý đăng nhập ban đầu
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['verify_code'])) {
    $admin_username = $_POST['admin_username'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';

    if (!empty($admin_username) && !empty($admin_password) && !empty($admin_email)) {
        // Kiểm tra thông tin đăng nhập
        if ($admin_username === $valid_username && $admin_password === $valid_password && $admin_email === $valid_email) {
            // Tạo mã xác minh
            $verification_code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Lưu vào session
            $_SESSION['admin_pending_verification'] = true;
            $_SESSION['admin_verification_code'] = $verification_code;
            $_SESSION['admin_temp_username'] = $admin_username;
            $_SESSION['admin_temp_email'] = $admin_email;
            $_SESSION['admin_verification_time'] = time();

            // Gửi email xác minh (trong môi trường thực tế)
            // For demo: hiển thị mã để test
            $success_message = "Mã xác minh đã được gửi đến email: " . htmlspecialchars($admin_email) . " - Mã: " . $verification_code;

            $show_verification_form = true;
        } else {
            $error_message = "Tên đăng nhập, mật khẩu hoặc email không chính xác!";
        }
    } else {
        $error_message = "Vui lòng nhập đầy đủ thông tin!";
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
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
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
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            display: none;
            font-size: 12px;
        }

        .success-message.show {
            display: block;
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
        }

        .admin-form-group input:focus {
            border-color: #667eea;
            box-shadow: 0 0 8px rgba(102, 126, 234, 0.2);
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
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .admin-login-btn:active {
            transform: translateY(0);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .security-info {
            background: #e3f2fd;
            padding: 12px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 12px;
            color: #1976d2;
            text-align: center;
        }

        .verification-info {
            text-align: center;
            margin-top: 15px;
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

        .resend-btn:hover {
            background-color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="admin-login-wrapper">
        <div class="admin-login-container">
            <div class="admin-icon">🔐</div>
            <h1><?php echo $show_verification_form ? "Xác Minh Email" : "Admin Login"; ?></h1>
            <p class="subtitle"><?php echo $show_verification_form ? "Nhập mã xác minh được gửi đến email" : "Đăng nhập tài khoản quản trị viên"; ?></p>

            <?php if (!empty($error_message)): ?>
                <div class="error-message show"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="success-message show"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <!-- Form Đăng Nhập Ban Đầu -->
            <?php if (!$show_verification_form): ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <div class="admin-form-group">
                        <label for="admin_username">Tên đăng nhập Admin</label>
                        <input type="text" id="admin_username" name="admin_username" placeholder="Nhập tên admin" required>
                    </div>

                    <div class="admin-form-group">
                        <label for="admin_password">Mật khẩu Admin</label>
                        <input type="password" id="admin_password" name="admin_password" placeholder="Nhập mật khẩu" required>
                    </div>

                    <div class="admin-form-group">
                        <label for="admin_email">Email Admin</label>
                        <input type="email" id="admin_email" name="admin_email" placeholder="Nhập email" required>
                    </div>

                    <button type="submit" class="admin-login-btn">Tiếp tục</button>
                </form>

                <div class="security-info">
                    Demo: Username: <strong>admin</strong> | Password: <strong>admin123</strong> | Email: <strong>admin@example.com</strong>
                </div>
            <?php else: ?>
                <!-- Form Xác Minh Email -->
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <div class="admin-form-group">
                        <label for="verify_code">Mã Xác Minh 6 Chữ Số</label>
                        <input type="text" id="verify_code" name="verify_code" placeholder="Nhập mã 6 chữ số" maxlength="6" pattern="[0-9]{6}" required>
                    </div>

                    <button type="submit" class="admin-login-btn">Xác Minh Đăng Nhập</button>
                </form>

                <div class="verification-info">
                    <p style="color: #666; margin-top: 15px; font-size: 12px;">Một mã xác minh 6 chữ số đã được gửi đến email của bạn</p>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" style="margin-top: 15px;">
                        <button type="submit" class="resend-btn">← Quay lại</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="back-link">
                <a href="indext.php">← Quay lại trang đăng nhập thường</a>
            </div>
        </div>
    </div>
</body>
</html>