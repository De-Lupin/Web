<?php
// ============================================================
// CONFIG.PHP - Kết nối MySQL qua XAMPP
// Giữ cấu trúc cũ, bổ sung hàm tiện ích dùng chung
// ============================================================

$servername = "localhost";
$db_username = "root";       // User mặc định XAMPP
$db_password = "";           // Password mặc định XAMPP (để trống)
$database    = "quanly";     // Tên database

// Kết nối
$conn = new mysqli($servername, $db_username, $db_password, $database);

// Kiểm tra lỗi
if ($conn->connect_error) {
    die("Kết nối database thất bại: " . $conn->connect_error);
}

// Charset UTF-8 hỗ trợ tiếng Việt
$conn->set_charset("utf8mb4");

// ============================================================
// HÀM TIỆN ÍCH dùng chung toàn hệ thống
// ============================================================

/**
 * Ghi nhật ký thao tác vào bảng audit_log
 */
function write_audit_log($conn, $user_id, $username, $action, $detail = '') {
    $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $conn->prepare(
        "INSERT INTO audit_log (user_id, username, action, detail, ip_address)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("issss", $user_id, $username, $action, $detail, $ip);
    $stmt->execute();
    $stmt->close();
}

/**
 * Bảo vệ trang — nếu chưa đăng nhập thì về trang login
 * Ví dụ dùng: require_login();
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: indext.php");
        exit();
    }
}

/**
 * Bảo vệ trang theo role
 * Ví dụ: require_role(['admin'])  hoặc  require_role(['admin','dieuphoI'])
 */
function require_role(array $roles) {
    require_login();
    if (!in_array($_SESSION['role'] ?? '', $roles)) {
        header("Location: indext.php?error=unauthorized");
        exit();
    }
}
?>
