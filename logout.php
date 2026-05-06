<?php
session_start();

// Xóa tất cả dữ liệu session
$_SESSION = array();

// Xóa cookie session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Xóa session
session_destroy();

// Chuyển về trang đăng nhập
header("Location: admin.php");
exit();
?>
