<?php
// ============================================================
// dieuphoI_gps.php - Theo dõi vị trí xe trực tiếp (GPS)
// Dùng dữ liệu demo + bản đồ OpenStreetMap (miễn phí, không cần API key)
// Khi có GPS thật: cập nhật bảng gps_log từ thiết bị xe
// ============================================================
session_start(); require 'config.php'; require_role(['dieuphoI']);

// Lấy danh sách xe đang chạy kèm thông tin chuyến xe
$xe_dang_chay = $conn->query(
    "SELECT x.id, x.bien_so, x.nhan_hieu, x.loai_xe,
            cx.id AS chuyen_id,
            dh.ma_don, dh.ten_khach, dh.tinh_lay, dh.tinh_giao,
            tx.ho_ten AS ten_tai_xe, tx.so_dien_thoai,
            cx.thoi_gian_xuat, cx.km_bat_dau
     FROM xe x
     LEFT JOIN chuyen_xe cx ON cx.xe_id = x.id AND cx.trang_thai = 'dang_di'
     LEFT JOIN don_hang dh  ON cx.don_hang_id = dh.id
     LEFT JOIN tai_xe tx    ON cx.tai_xe_id = tx.id
     WHERE x.tinh_trang = 'dang_chay'
     ORDER BY x.bien_so"
)->fetch_all(MYSQLI_ASSOC);

// Tổng số xe theo trạng thái
$tong_chay    = $conn->query("SELECT COUNT(*) AS c FROM xe WHERE tinh_trang='dang_chay'")->fetch_assoc()['c'] ?? 0;
$tong_san_sang= $conn->query("SELECT COUNT(*) AS c FROM xe WHERE tinh_trang='san_sang'")->fetch_assoc()['c'] ?? 0;
$tong_bao_duong=$conn->query("SELECT COUNT(*) AS c FROM xe WHERE tinh_trang='bao_duong'")->fetch_assoc()['c'] ?? 0;

// Dữ liệu vị trí demo cho mỗi xe đang chạy
// (Khi tích hợp GPS thật: lấy từ bảng gps_log thay thế)
$demo_locations = [
    // Các điểm trên tuyến TP.HCM - Hà Nội
    ['lat' => 13.0827, 'lng' => 109.0926, 'city' => 'Nha Trang'],
    ['lat' => 15.8800, 'lng' => 108.3380, 'city' => 'Đà Nẵng'],
    ['lat' => 10.8231, 'lng' => 106.6297, 'city' => 'TP.HCM'],
    ['lat' => 10.9804, 'lng' => 106.6519, 'city' => 'Bình Dương'],
    ['lat' => 11.0686, 'lng' => 106.8739, 'city' => 'Đồng Nai'],
];

// Gán vị trí demo cho mỗi xe
foreach ($xe_dang_chay as $i => &$xe) {
    $loc = $demo_locations[$i % count($demo_locations)];
    $xe['lat']  = $loc['lat'];
    $xe['lng']  = $loc['lng'];
    $xe['city'] = $loc['city'];
    // Tốc độ giả lập 60-90 km/h
    $xe['speed'] = rand(60, 90);
}
unset($xe); // Xóa tham chiếu

$active = 'gps'; require 'sidebar_dieuphoI.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Theo Dõi GPS — Điều Phối</title>
<link rel="stylesheet" href="dieuphoI_layout.css">

<!-- Leaflet.js: thư viện bản đồ mã nguồn mở, miễn phí -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
/* Layout trang GPS: danh sách xe bên trái + bản đồ bên phải */
.gps-layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 16px;
    height: calc(100vh - 160px);   /* Chiều cao = màn hình - topbar - padding */
    min-height: 500px;
}

/* Danh sách xe bên trái - có thể cuộn */
.xe-list {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 10px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}
.xe-list-header {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
    font-size: 14px;
    font-weight: 700;
    color: var(--text);
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 1;
}

/* Card mỗi xe trong danh sách */
.xe-item {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: background .15s;
}
.xe-item:hover    { background: #f8fafc; }
.xe-item.selected { background: #eff6ff; border-left: 3px solid var(--green); }
.xe-item:last-child { border-bottom: none; }

.xe-item-bs   { font-weight: 700; font-size: 14px; color: var(--text); }
.xe-item-route{ font-size: 12px; color: var(--muted); margin-top: 3px; }
.xe-item-speed{
    display: inline-block;
    background: #dcfce7;
    color: #166534;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 10px;
    margin-top: 5px;
}

/* Bản đồ bên phải */
#map {
    border-radius: 10px;
    border: 1px solid var(--border);
    height: 100%;
    min-height: 500px;
    z-index: 0;
}

/* Nhãn chú thích demo */
.demo-notice {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 12px;
    color: #92400e;
    margin-bottom: 14px;
}
</style>
</head>
<body>
<div class="wrapper">

<main class="main">
<div class="topbar">
    <div class="topbar-left">
        <div class="topbar-title">📡 Theo Dõi GPS Xe</div>
        <div class="breadcrumb">
            <a href="dieuphoI_dashboard.php">Dashboard</a> › GPS
        </div>
    </div>
    <div class="user-chip">
        <div class="chip-avatar"><?= mb_strtoupper(mb_substr($full_name, 0, 1)) ?></div>
        <div>
            <div class="chip-name"><?= htmlspecialchars($full_name) ?></div>
            <div class="chip-role">Điều Phối Viên</div>
        </div>
    </div>
</div>

<div class="content">

    <!-- Thống kê nhanh -->
    <div class="stat-cards" style="margin-bottom:16px">
        <div class="stat-card" style="border-top-color:#3b82f6">
            <span class="sc-icon">🚛</span>
            <div class="sc-label">Đang chạy</div>
            <div class="sc-value" style="color:#3b82f6"><?= $tong_chay ?></div>
            <div class="sc-sub">Xe trên đường</div>
        </div>
        <div class="stat-card" style="border-top-color:#10b981">
            <span class="sc-icon">🟢</span>
            <div class="sc-label">Sẵn sàng</div>
            <div class="sc-value" style="color:#10b981"><?= $tong_san_sang ?></div>
            <div class="sc-sub">Xe chờ điều phối</div>
        </div>
        <div class="stat-card" style="border-top-color:#f59e0b">
            <span class="sc-icon">🔧</span>
            <div class="sc-label">Bảo dưỡng</div>
            <div class="sc-value" style="color:#f59e0b"><?= $tong_bao_duong ?></div>
            <div class="sc-sub">Xe đang sửa chữa</div>
        </div>
    </div>

    <!-- Chú thích demo -->
    <div class="demo-notice">
        ⚠️ <strong>Chế độ Demo:</strong> Vị trí hiển thị là dữ liệu giả lập.
        Để dùng GPS thật: cài thiết bị GPS trên xe, cập nhật tọa độ vào bảng <code>gps_log</code> định kỳ.
    </div>

    <?php if (!empty($xe_dang_chay)): ?>
    <!-- Layout: danh sách xe + bản đồ -->
    <div class="gps-layout">

        <!-- Cột trái: Danh sách xe đang chạy -->
        <div class="xe-list">
            <div class="xe-list-header">
                🚛 Xe đang trên đường (<?= count($xe_dang_chay) ?>)
            </div>
            <?php foreach ($xe_dang_chay as $i => $xe): ?>
            <div class="xe-item <?= $i === 0 ? 'selected' : '' ?>"
                 id="item-<?= $i ?>"
                 onclick="focusXe(<?= $i ?>, <?= $xe['lat'] ?>, <?= $xe['lng'] ?>)">

                <div class="xe-item-bs">🚛 <?= htmlspecialchars($xe['bien_so']) ?></div>

                <?php if ($xe['ma_don']): ?>
                    <div class="xe-item-route">
                        <?= htmlspecialchars($xe['tinh_lay'] ?? '—') ?> →
                        <?= htmlspecialchars($xe['tinh_giao'] ?? '—') ?>
                    </div>
                    <div style="font-size:11px; color:var(--muted); margin-top:2px">
                        📋 <?= htmlspecialchars($xe['ma_don']) ?>
                        · 👤 <?= htmlspecialchars($xe['ten_tai_xe'] ?? '—') ?>
                    </div>
                <?php endif; ?>

                <div style="display:flex; gap:8px; align-items:center; margin-top:6px">
                    <span class="xe-item-speed">🏎️ <?= $xe['speed'] ?> km/h</span>
                    <span style="font-size:11px; color:var(--muted)">
                        📍 <?= htmlspecialchars($xe['city']) ?>
                    </span>
                </div>

                <?php if ($xe['so_dien_thoai']): ?>
                    <div style="margin-top:5px">
                        <a href="tel:<?= $xe['so_dien_thoai'] ?>"
                           style="font-size:12px; color:var(--green); text-decoration:none; font-weight:600">
                            📞 <?= $xe['so_dien_thoai'] ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Cột phải: Bản đồ -->
        <div id="map"></div>

    </div>

    <?php else: ?>
    <!-- Không có xe đang chạy -->
    <div style="background:#fff; border:1px solid var(--border); border-radius:10px; padding:60px 20px; text-align:center; color:var(--muted)">
        <div style="font-size:52px; margin-bottom:14px">📡</div>
        <p style="font-size:15px; font-weight:600">Hiện không có xe nào đang trên đường.</p>
        <p style="font-size:13px; margin-top:8px">Khi điều phối chuyến xe, vị trí sẽ hiện ở đây.</p>
        <a href="dieuphoI_phan_xe.php" class="btn btn-primary" style="margin-top:16px">
            🚛 Phân xe ngay
        </a>
    </div>
    <?php endif; ?>

</div><!-- /content -->
</main>
</div><!-- /wrapper -->

<!-- Dữ liệu xe cho JavaScript -->
<script>
// Dữ liệu tất cả xe từ PHP
const xeData = <?= json_encode($xe_dang_chay) ?>;

// Khởi tạo bản đồ (trung tâm Việt Nam)
const map = L.map('map').setView([14.0583, 108.2772], 6);

// Tile bản đồ OpenStreetMap (miễn phí)
// Bản đồ Google Maps Standard
// Bản đồ Google Maps Standard
L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
    maxZoom: 20,
    subdomains:['mt0','mt1','mt2','mt3'],
    attribution: '© Google Maps'
}).addTo(map);

// Icon xe tùy chỉnh
const truckIcon = L.divIcon({
    html: '<div style="font-size:28px; line-height:1">🚛</div>',
    className: '',
    iconSize: [32, 32],
    iconAnchor: [16, 16],
    popupAnchor: [0, -16]
});

// Thêm marker cho mỗi xe lên bản đồ
const markers = [];
xeData.forEach((xe, i) => {
    const marker = L.marker([xe.lat, xe.lng], { icon: truckIcon })
        .addTo(map)
        .bindPopup(`
            <div style="min-width:200px; font-family:sans-serif">
                <div style="font-weight:700; font-size:14px; margin-bottom:6px">🚛 ${xe.bien_so}</div>
                <div style="font-size:12px; color:#64748b; margin-bottom:4px">
                    ${xe.nhan_hieu || ''}
                </div>
                ${xe.ma_don ? `
                    <div style="font-size:12px; margin-bottom:3px">📋 ${xe.ma_don}</div>
                    <div style="font-size:12px; margin-bottom:3px">
                        📍 ${xe.tinh_lay || '—'} → 🏁 ${xe.tinh_giao || '—'}
                    </div>
                ` : ''}
                <div style="font-size:12px; margin-bottom:3px">👤 ${xe.ten_tai_xe || '—'}</div>
                <div style="font-size:12px">
                    🏎️ <strong>${xe.speed} km/h</strong>
                    · 📍 ${xe.city}
                </div>
                ${xe.so_dien_thoai ? `<div style="margin-top:8px"><a href="tel:${xe.so_dien_thoai}" style="color:#2563eb; font-size:12px; font-weight:600">📞 ${xe.so_dien_thoai}</a></div>` : ''}
            </div>
        `);
    markers.push(marker);
});

// Mở popup xe đầu tiên nếu có
if (markers.length > 0) {
    markers[0].openPopup();
}

// Hàm: Khi click vào xe trong danh sách → zoom đến xe trên bản đồ
function focusXe(index, lat, lng) {
    // Bỏ highlight tất cả
    document.querySelectorAll('.xe-item').forEach(el => el.classList.remove('selected'));
    // Highlight xe được chọn
    document.getElementById('item-' + index)?.classList.add('selected');
    // Zoom bản đồ đến xe
    map.setView([lat, lng], 12);
    markers[index]?.openPopup();
}
</script>
</body>
</html>
