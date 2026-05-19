<?php
// ============================================================
// INDEXT.PHP - Trang đăng nhập cho Điều Phối & Khách Hàng
// Giữ nguyên giao diện cũ — chỉ sửa logic PHP bên trong
// ============================================================
session_start();
require 'config.php';

// Đã đăng nhập → vào đúng dashboard
if (isset($_SESSION['user_id'])) {
    $r = $_SESSION['role'] ?? '';
    if ($r === 'admin')     { header("Location: admin_dashboard.php"); exit(); }
    if ($r === 'dieuphoI')  { header("Location: dieuphoI_dashboard.php"); exit(); }
    header("Location: customer_dashboard.php"); exit();
}

// ── Lấy thông báo từ URL ─────────────────────────────────
$message      = "";
$message_type = "error"; // mặc định màu đỏ

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'logout') {
        $message      = "Bạn đã đăng xuất thành công.";
        $message_type = "success";
    }
}
if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $message = "Bạn không có quyền truy cập trang đó!";
}

// ── Xử lý form đăng nhập ─────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $message = "Vui lòng nhập đầy đủ thông tin!";

    } else {
        // Prepared Statement — chống SQL Injection (giữ đúng code cũ)
        $stmt = $conn->prepare(
            "SELECT id, username, password, full_name, email, role, is_active
             FROM users WHERE username = ? LIMIT 1"
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (!$user['is_active']) {
                $message = "Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên!";

            } elseif ($user['role'] === 'admin') {
                // Admin phải vào trang riêng
                $message = "Tài khoản Admin vui lòng đăng nhập tại trang Admin bên dưới.";

            } elseif (!password_verify($password, $user['password'])) {
                $message = "Mật khẩu không đúng!";
                // Ghi log thất bại
                write_audit_log($conn, $user['id'], $username, 'LOGIN_FAILED', 'Sai mật khẩu');

            } else {
                // ✅ ĐĂNG NHẬP THÀNH CÔNG
                session_regenerate_id(true); // Chống Session Fixation

                $_SESSION['user_id']    = $user['id'];
                $_SESSION['username']   = $user['username'];
                $_SESSION['full_name']  = $user['full_name'];
                $_SESSION['email']      = $user['email'];
                $_SESSION['role']       = $user['role'];
                $_SESSION['login_time'] = time();

                // Cập nhật last_login
                $upd = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $upd->bind_param("i", $user['id']);
                $upd->execute();
                $upd->close();

                // Ghi audit log
                write_audit_log($conn, $user['id'], $username, 'LOGIN', 'Đăng nhập thành công');

                // Chuyển đúng dashboard theo role
                if ($user['role'] === 'dieuphoI') {
                    header("Location: dieuphoI_dashboard.php");
                } else {
                    header("Location: customer_dashboard.php");
                }
                exit();
            }
        } else {
            $message = "Tài khoản không tồn tại!";
            write_audit_log($conn, null, $username, 'LOGIN_FAILED', 'Tài khoản không tồn tại');
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập hệ thống</title>
    <link rel="stylesheet" href="style.css">
    <!-- Google Sign-In -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>

    <div class="loginwrapper">
        <div class="leftpanel"></div>
        <div class="rightpanel">
            <div class="logincontainer">
                <h2>Đăng Nhập</h2>

                <?php if ($message): ?>
                    <p style="margin-bottom:15px;
                              color:<?= $message_type === 'success' ? '#27ae60' : '#e74c3c' ?>;
                              font-weight:bold;">
                        <?= htmlspecialchars($message) ?>
                    </p>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">

                    <div class="inputgroup">
                        <label for="username">Tên đăng nhập</label>
                        <input type="text" id="username" name="username"
                               placeholder="Nhập tên đăng nhập"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               required>
                    </div>

                    <div class="inputgroup">
                        <label for="password">Mật khẩu</label>
                        <div class="passwordwrapper">
                            <input type="password" id="password" name="password"
                                   placeholder="Nhập mật khẩu" required>
                            <button type="button" id="showbtn">Show</button>
                        </div>
                        <div class="passwordactions">
                            <button type="button" id="suggestbtn" class="actionbtn">Đề xuất mật khẩu mạnh</button>
                        </div>
                        <div id="strengthmetertext"></div>
                    </div>

                    <div class="options">
                        <label>
                            <input type="checkbox" name="remember"> Ghi nhớ tôi
                        </label>
                        <a href="#">Quên mật khẩu?</a>
                    </div>

                    <button type="submit" class="loginbtn">Đăng Nhập</button>

                    <div class="adminlink">
                        <a href="admin.php">🔐 Quyền đăng nhập Admin</a>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
