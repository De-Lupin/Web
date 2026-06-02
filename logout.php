<?php
// ============================================================
// LOGOUT.PHP - Đăng xuất an toàn
// Ghi nhật ký → xóa session → xóa cookie → chuyển về login
// ============================================================
session_start();
require 'config.php';

// Ghi audit log trước khi xóa session
if (isset($_SESSION['user_id'])) {
    write_audit_log(
        $conn,
        $_SESSION['user_id'],
        $_SESSION['username'] ?? 'unknown',
        'LOGOUT',
        'Đăng xuất thành công'
    );
}

// Xóa toàn bộ dữ liệu session
$_SESSION = [];

// Xóa cookie session (bảo mật)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hủy session
session_destroy();

// Chuyển về trang login với thông báo
header("Location: indext.php?msg=logout");
exit();
?>
