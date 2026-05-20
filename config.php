<?php


$servername = "localhost";
$db_username = "root";      
$db_password = "";           
$database    = "quanly";     

$conn = new mysqli($servername, $db_username, $db_password, $database);

if ($conn->connect_error) {
    die("Kết nối database thất bại: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


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


function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: indext.php");
        exit();
    }
}


function require_role(array $roles) {
    require_login();
    if (!in_array($_SESSION['role'] ?? '', $roles)) {
        header("Location: indext.php?error=unauthorized");
        exit();
    }
}
?>
