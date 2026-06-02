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

// ── Chuẩn hóa role từ DB (có dấu) → code (không dấu) ───────
// DB lưu: 'admin', 'điều phối', 'khách hàng'
// Code dùng: 'admin', 'dieuphoI', 'khachhang'
function normalize_role(string $db_role): string {
    return match(trim($db_role)) {
        'điều phối'  => 'dieuphoI',
        'khách hàng' => 'khachhang',
        'admin'      => 'admin',
        // Trường hợp DB đã được cập nhật sang không dấu
        'dieuphoI'   => 'dieuphoI',
        'khachhang'  => 'khachhang',
        default      => $db_role,
    };
}

// ── Chuyển role code → giá trị DB để INSERT/UPDATE ──────────
function db_role(string $code_role): string {
    return match(trim($code_role)) {
        'dieuphoI'   => 'điều phối',
        'khachhang'  => 'khách hàng',
        'admin'      => 'admin',
        default      => $code_role,
    };
}

// ── Kiểm tra mật khẩu (hỗ trợ cả bcrypt lẫn plain text) ────
// DB hiện tại dùng bcrypt (password_hash), một số tài khoản
// cũ có thể còn plain text → hàm này xử lý cả 2 trường hợp
function verify_password(string $input, string $stored): bool {
    // Nếu stored bắt đầu bằng $2y$ → bcrypt
    if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2a$')) {
        return password_verify($input, $stored);
    }
    // Ngược lại → plain text
    return $input === $stored;
}

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
// Nhận mảng role code: ['admin'], ['dieuphoI'], ['khachhang']
// So sánh với session['role'] đã được normalize
function require_role(array $roles) {
    require_login();
    $session_role = $_SESSION['role'] ?? '';
    if (!in_array($session_role, $roles)) {
        header("Location: indext.php?error=unauthorized");
        exit();
    }
}
?>
