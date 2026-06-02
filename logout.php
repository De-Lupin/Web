<?php

session_start();
require 'config.php';


if (isset($_SESSION['user_id'])) {
    write_audit_log(
        $conn,
        $_SESSION['user_id'],
        $_SESSION['username'] ?? 'unknown',
        'LOGOUT',
        'Đăng xuất thành công'
    );
}


$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}


session_destroy();

header("Location: indext.php?msg=logout");
exit();
?>
