<?php
// ============================================================
// api_kiem_tra_cap_nhat.php
// API kiểm tra xem có mốc lộ trình mới hơn thời điểm last không
// Gọi bởi customer_lo_trinh.php mỗi 30 giây (AJAX)
// ============================================================
session_start();
require 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Chỉ cho phép khách hàng đã đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['co_cap_nhat' => false, 'msg' => 'Chưa đăng nhập']);
    exit();
}

$uid       = $_SESSION['user_id'];
$fn_esc    = mysqli_real_escape_string($conn, $_SESSION['full_name'] ?? '');
$don_id    = (int)($_GET['don_id'] ?? 0);
$last_time = trim($_GET['last'] ?? '');

if (!$don_id) {
    echo json_encode(['co_cap_nhat' => false, 'msg' => 'Thiếu don_id']);
    exit();
}

// Kiểm tra đơn có thuộc về người dùng này không
$don = $conn->query(
    "SELECT id, trang_thai FROM don_hang
     WHERE id=$don_id
       AND (ten_khach='$fn_esc' OR nguoi_tao_id=$uid)
       AND is_deleted=0
     LIMIT 1"
)->fetch_assoc();

if (!$don) {
    echo json_encode(['co_cap_nhat' => false, 'msg' => 'Không có quyền']);
    exit();
}

// Kiểm tra xem có mốc lộ trình mới hơn last_time không
$co_cap_nhat = false;
$so_moc_moi  = 0;
$moc_moi_nhat = null;

if ($last_time) {
    $lt_esc = mysqli_real_escape_string($conn, $last_time);
    $r = $conn->query(
        "SELECT COUNT(*) AS c,
                MAX(created_at) AS moi_nhat,
                (SELECT su_kien FROM lo_trinh_don_hang
                 WHERE don_hang_id=$don_id ORDER BY thoi_gian DESC LIMIT 1) AS sk_moi
         FROM lo_trinh_don_hang
         WHERE don_hang_id=$don_id AND created_at > '$lt_esc'"
    )->fetch_assoc();

    $so_moc_moi  = (int)($r['c'] ?? 0);
    $co_cap_nhat = $so_moc_moi > 0;
    $moc_moi_nhat= $r['moi_nhat'] ?? null;
} else {
    // Nếu chưa có last_time, kiểm tra xem có mốc nào không
    $r = $conn->query(
        "SELECT COUNT(*) AS c FROM lo_trinh_don_hang WHERE don_hang_id=$don_id"
    )->fetch_assoc();
    $co_cap_nhat = ($r['c'] ?? 0) > 0;
}

echo json_encode([
    'co_cap_nhat'  => $co_cap_nhat,
    'so_moc_moi'   => $so_moc_moi,
    'trang_thai'   => $don['trang_thai'],
    'moc_moi_nhat' => $moc_moi_nhat,
], JSON_UNESCAPED_UNICODE);
