<?php
// ============================================================
// config.php — Kết nối MySQL + Hàm tiện ích dùng chung
// ============================================================

$servername  = "localhost";
$db_username = "root";      // User mặc định XAMPP
$db_password = "";          // Mặc định XAMPP để trống
$database    = "quanly";

$conn = new mysqli($servername, $db_username, $db_password, $database);
if ($conn->connect_error) {
    die("Kết nối database thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// ── Ghi nhật ký hoạt động ────────────────────────────────
function write_audit_log($conn, $user_id, $username, $action, $detail = '') {
    $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $uid = $user_id ? (int)$user_id : 'NULL';
    $un  = mysqli_real_escape_string($conn, $username ?? '');
    $ac  = mysqli_real_escape_string($conn, $action);
    $dt  = mysqli_real_escape_string($conn, $detail);
    $ip  = mysqli_real_escape_string($conn, $ip);
    $conn->query("INSERT INTO audit_log (user_id,username,action,detail,ip_address)
                  VALUES ($uid,'$un','$ac','$dt','$ip')");
}

// ── Bảo vệ trang (chưa đăng nhập → về login) ─────────────
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: indext.php");
        exit();
    }
}

// ── Bảo vệ trang theo vai trò ────────────────────────────
function require_role(array $roles) {
    require_login();
    if (!in_array($_SESSION['role'] ?? '', $roles)) {
        header("Location: indext.php?error=unauthorized");
        exit();
    }
}
?>
