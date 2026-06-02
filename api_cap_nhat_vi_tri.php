<?php
// ============================================================
// api_cap_nhat_vi_tri.php
// API endpoint: Cập nhật vị trí xe → tự động thông báo khách
// Gọi từ: dieuphoI_lo_trinh.php hoặc thiết bị GPS
// ============================================================
session_start();
require 'config.php';

// Chỉ cho phép điều phối viên hoặc admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['dieuphoI', 'admin'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Không có quyền truy cập']);
    exit();
}

header('Content-Type: application/json; charset=utf-8');

$uid = $_SESSION['user_id'];

// ── SỰ KIỆN → TRẠNG THÁI ĐƠN HÀNG ──────────────────────────
$su_kien_map = [
    'tao_don'         => ['label' => 'Đơn được tạo',               'icon' => '📝', 'loai_tb' => 'thong_tin'],
    'duyet_don'       => ['label' => 'Đơn đã được duyệt',          'icon' => '✅', 'loai_tb' => 'thong_tin'],
    'lay_hang'        => ['label' => 'Đang lấy hàng',              'icon' => '📦', 'loai_tb' => 'thong_tin'],
    'den_kho'         => ['label' => 'Hàng đã đến kho trung chuyển','icon' => '🏭', 'loai_tb' => 'thong_tin'],
    'roi_kho'         => ['label' => 'Hàng đã rời kho',            'icon' => '🚛', 'loai_tb' => 'thong_tin'],
    'dang_van_chuyen' => ['label' => 'Đang vận chuyển',            'icon' => '🛣️', 'loai_tb' => 'thong_tin'],
    'den_kho_dich'    => ['label' => 'Hàng đến kho đích',          'icon' => '📍', 'loai_tb' => 'thong_tin'],
    'dang_giao'       => ['label' => 'Đang giao hàng đến bạn',     'icon' => '🏃', 'loai_tb' => 'thong_tin'],
    'da_giao'         => ['label' => 'Giao hàng thành công! 🎉',   'icon' => '🎉', 'loai_tb' => 'thong_tin'],
    'that_bai'        => ['label' => 'Giao hàng thất bại',         'icon' => '⚠️', 'loai_tb' => 'canh_bao'],
    'hoan_hang'       => ['label' => 'Hàng đang được hoàn về kho', 'icon' => '↩️', 'loai_tb' => 'canh_bao'],
];

$su_kien_to_tt = [
    'lay_hang'        => 'dang_lay_hang',
    'den_kho'         => 'dang_van_chuyen',
    'roi_kho'         => 'dang_van_chuyen',
    'dang_van_chuyen' => 'dang_van_chuyen',
    'den_kho_dich'    => 'dang_van_chuyen',
    'dang_giao'       => 'dang_van_chuyen',
    'da_giao'         => 'da_giao',
    'hoan_hang'       => 'dang_xu_ly',
];

// ── NHẬN DỮ LIỆU ─────────────────────────────────────────────
$don_id   = (int)($_POST['don_id']    ?? 0);
$su_kien  = trim($_POST['su_kien']    ?? '');
$kho_id   = (int)($_POST['kho_id']    ?? 0) ?: null;
$dia_diem = trim($_POST['dia_diem']   ?? '');
$tinh     = trim($_POST['tinh_thanh'] ?? '');
$mo_ta    = trim($_POST['mo_ta']      ?? '');
$ghi_chu  = trim($_POST['ghi_chu']    ?? '');
$thoi_gian= trim($_POST['thoi_gian']  ?? date('Y-m-d H:i:s'));

// Validate
if (!$don_id || !$su_kien || !isset($su_kien_map[$su_kien])) {
    echo json_encode(['ok' => false, 'msg' => 'Thiếu thông tin bắt buộc']);
    exit();
}

// ── LẤY THÔNG TIN ĐƠN HÀNG ──────────────────────────────────
$don = $conn->query(
    "SELECT dh.*, x.bien_so, tx.ho_ten AS ten_tai_xe
     FROM don_hang dh
     LEFT JOIN xe x      ON dh.xe_id = x.id
     LEFT JOIN tai_xe tx ON dh.tai_xe_id = tx.id
     WHERE dh.id = $don_id LIMIT 1"
)->fetch_assoc();

if (!$don) {
    echo json_encode(['ok' => false, 'msg' => 'Không tìm thấy đơn hàng']);
    exit();
}

// ── THÊM MỐC LỘ TRÌNH ────────────────────────────────────────
$xe_id    = $don['xe_id']    ? (int)$don['xe_id']    : 'NULL';
$taixe_id = $don['tai_xe_id']? (int)$don['tai_xe_id']: 'NULL';
$kho_sql  = $kho_id ?: 'NULL';

$dia_esc  = mysqli_real_escape_string($conn, $dia_diem);
$tinh_esc = mysqli_real_escape_string($conn, $tinh);
$mo_ta_esc= mysqli_real_escape_string($conn, $mo_ta);
$ghi_esc  = mysqli_real_escape_string($conn, $ghi_chu);
$sk_esc   = mysqli_real_escape_string($conn, $su_kien);
$tg_esc   = mysqli_real_escape_string($conn, $thoi_gian);

$ok = $conn->query(
    "INSERT INTO lo_trinh_don_hang
     (don_hang_id, kho_id, su_kien, dia_diem, tinh_thanh,
      xe_id, tai_xe_id, nguoi_cap_nhat, thoi_gian, mo_ta, ghi_chu_noi_bo)
     VALUES
     ($don_id, $kho_sql, '$sk_esc', '$dia_esc', '$tinh_esc',
      $xe_id, $taixe_id, $uid, '$tg_esc', '$mo_ta_esc', '$ghi_esc')"
);

if (!$ok) {
    echo json_encode(['ok' => false, 'msg' => 'Lỗi lưu lộ trình: ' . $conn->error]);
    exit();
}

$lo_trinh_id = $conn->insert_id;

// ── CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG ────────────────────────────
if (isset($su_kien_to_tt[$su_kien])) {
    $tt_moi = $su_kien_to_tt[$su_kien];
    $conn->query("UPDATE don_hang SET trang_thai='$tt_moi' WHERE id=$don_id");

    if ($su_kien === 'da_giao') {
        $conn->query("UPDATE don_hang SET ngay_giao_thuc_te = NOW() WHERE id = $don_id");
    }
}

// ── TẠO NỘI DUNG THÔNG BÁO ───────────────────────────────────
$sk_info   = $su_kien_map[$su_kien];
$sk_label  = $sk_info['label'];
$loai_tb   = $sk_info['loai_tb'];

// Lấy tên kho nếu có
$ten_kho = '';
if ($kho_id) {
    $kho_r = $conn->query("SELECT ten_kho, tinh_thanh FROM kho WHERE id=$kho_id LIMIT 1")->fetch_assoc();
    if ($kho_r) $ten_kho = " — {$kho_r['ten_kho']} ({$kho_r['tinh_thanh']})";
}

// Tạo tiêu đề và nội dung thông báo rõ ràng
$tieu_de = "🚛 Đơn {$don['ma_don']}: $sk_label";

if ($mo_ta) {
    $noi_dung = $mo_ta;
} else {
    // Tạo nội dung tự động theo sự kiện
    $noi_dung_map = [
        'tao_don'         => "Đơn hàng #{$don['ma_don']} của bạn đã được tạo và đang chờ xử lý.",
        'duyet_don'       => "Đơn hàng #{$don['ma_don']} đã được duyệt và chuẩn bị lấy hàng.",
        'lay_hang'        => "Xe {$don['bien_so']} đang đến lấy hàng tại địa chỉ của bạn.",
        'den_kho'         => "Hàng hóa đã đến kho trung chuyển{$ten_kho}. Đang được phân loại và chuẩn bị.",
        'roi_kho'         => "Hàng đã rời kho{$ten_kho}, đang trên đường vận chuyển đến điểm tiếp theo.",
        'dang_van_chuyen' => "Hàng đang được vận chuyển. Xe {$don['bien_so']} đang trên đường" . ($tinh ? " qua $tinh" : "") . ".",
        'den_kho_dich'    => "Hàng đã đến kho đích{$ten_kho}. Chuẩn bị giao đến địa chỉ của bạn.",
        'dang_giao'       => "Tài xế {$don['ten_tai_xe']} đang trên đường giao hàng đến bạn. Vui lòng chú ý điện thoại.",
        'da_giao'         => "Hàng hóa đã được giao thành công! Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi.",
        'that_bai'        => "Giao hàng chưa thành công. Chúng tôi sẽ liên hệ lại để sắp xếp lại lịch giao.",
        'hoan_hang'       => "Hàng đang được hoàn về kho. Chúng tôi sẽ liên hệ để xử lý trong thời gian sớm nhất.",
    ];
    $noi_dung = $noi_dung_map[$su_kien] ?? $sk_label;
}

// Thêm địa điểm vào nội dung nếu có
if ($dia_diem && !$ten_kho) {
    $noi_dung .= "\n📍 Vị trí hiện tại: $dia_diem" . ($tinh ? ", $tinh" : "");
}

// ── TÌM TÀI KHOẢN KHÁCH HÀNG ─────────────────────────────────
$ten_kh = mysqli_real_escape_string($conn, $don['ten_khach'] ?? '');
$sdt_kh = mysqli_real_escape_string($conn, $don['dien_thoai_kh'] ?? '');

$kh_user = null;
if ($sdt_kh) {
    $kh_user = $conn->query(
        "SELECT id FROM users WHERE phone='$sdt_kh' AND role='khachhang' LIMIT 1"
    )->fetch_assoc();
}
if (!$kh_user && $ten_kh) {
    $kh_user = $conn->query(
        "SELECT id FROM users WHERE full_name='$ten_kh' AND role='khachhang' LIMIT 1"
    )->fetch_assoc();
}

// ── GỬI THÔNG BÁO CHO KHÁCH ──────────────────────────────────
$thong_bao_id = null;
if ($kh_user) {
    $kh_id     = $kh_user['id'];
    $td_esc    = mysqli_real_escape_string($conn, $tieu_de);
    $nd_esc    = mysqli_real_escape_string($conn, $noi_dung);
    $lien_ket  = "customer_lo_trinh.php?search=" . urlencode($don['ma_don']);
    $lk_esc    = mysqli_real_escape_string($conn, $lien_ket);

    $conn->query(
        "INSERT INTO thong_bao (nguoi_gui_id, nguoi_nhan_id, tieu_de, noi_dung, loai, lien_ket)
         VALUES ($uid, $kh_id, '$td_esc', '$nd_esc', '$loai_tb', '$lk_esc')"
    );
    $thong_bao_id = $conn->insert_id;
}

// ── TRẢ KẾT QUẢ ──────────────────────────────────────────────
echo json_encode([
    'ok'           => true,
    'msg'          => "Đã cập nhật: $sk_label",
    'lo_trinh_id'  => $lo_trinh_id,
    'thong_bao_id' => $thong_bao_id,
    'da_thong_bao' => $kh_user ? true : false,
    'su_kien'      => $su_kien,
    'tieu_de'      => $tieu_de,
    'noi_dung'     => $noi_dung,
], JSON_UNESCAPED_UNICODE);
