<?php
session_start(); require 'config.php'; require_role(['admin']);

$action = $_GET['action'] ?? '';

if ($action === 'export_sql') {
    $filename = 'backup_quanly_' . date('Ymd_His') . '.sql';
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Pragma: no-cache');

    echo "-- ============================================================\n";
    echo "-- Backup Database: quanly\n";
    echo "-- Thời gian: " . date('d/m/Y H:i:s') . "\n";
    echo "-- Xuất bởi: " . htmlspecialchars($_SESSION['username']) . "\n";
    echo "-- ============================================================\n\n";
    echo "SET NAMES utf8mb4;\n";
    echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables = $conn->query("SHOW TABLES")->fetch_all(MYSQLI_NUM);
    foreach ($tables as $table) {
        $t = $table[0];
        $create = $conn->query("SHOW CREATE TABLE `$t`")->fetch_assoc();
        echo "-- Bảng: $t\n";
        echo "DROP TABLE IF EXISTS `$t`;\n";
        echo $create['Create Table'] . ";\n\n";

        $rows = $conn->query("SELECT * FROM `$t`");
        if ($rows && $rows->num_rows > 0) {
            echo "INSERT INTO `$t` VALUES\n";
            $lines = [];
            while ($row = $rows->fetch_assoc()) {
                $vals = array_map(function($v) use ($conn) {
                    return $v === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, $v) . "'";
                }, array_values($row));
                $lines[] = "(" . implode(",", $vals) . ")";
            }
            echo implode(",\n", $lines) . ";\n\n";
        }
    }
    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    exit();
}

if ($action === 'export_excel') {
    $filename = 'donhang_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Pragma: no-cache');

    echo "\xEF\xBB\xBF";

    $out = fopen('php://output','w');
    fputcsv($out, ['Mã đơn','Khách hàng','ĐT','Tỉnh lấy','Tỉnh giao','Loại VC','Trọng lượng','Xe','Tài xế','Giá cước','Doanh thu','Lợi nhuận','Trạng thái','Ngày tạo'], ',');

    $rows = $conn->query("SELECT dh.ma_don,dh.ten_khach,dh.dien_thoai_kh,dh.tinh_lay,dh.tinh_giao,dh.loai_van_chuyen,dh.trong_luong,x.bien_so,tx.ho_ten,dh.gia_cuoc,dh.doanh_thu,dh.loi_nhuan,dh.trang_thai,dh.ngay_tao FROM don_hang dh LEFT JOIN xe x ON dh.xe_id=x.id LEFT JOIN tai_xe tx ON dh.tai_xe_id=tx.id ORDER BY dh.ngay_tao DESC");
    while ($r = $rows->fetch_assoc()) {
        fputcsv($out, [
            $r['ma_don'], $r['ten_khach'], $r['dien_thoai_kh']??'',
            $r['tinh_lay']??'', $r['tinh_giao']??'', $r['loai_van_chuyen'],
            $r['trong_luong']??'', $r['bien_so']??'', $r['ho_ten']??'',
            $r['gia_cuoc'], $r['doanh_thu'], $r['loi_nhuan'],
            $r['trang_thai'], $r['ngay_tao']
        ], ',');
    }
    fclose($out);
    exit();
}

header("Location: admin_cai_dat.php?tab=backup");
exit();