<?php
// ============================================================
// customer_lo_trinh.php — Theo Dõi Lộ Trình (Khách Hàng)
// Auto-refresh mỗi 30 giây, hiển thị timeline đẹp
// ============================================================
session_start();
require 'config.php';
require_role(['khachhang']);

$uid       = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? '';
$fn_esc    = mysqli_real_escape_string($conn, $full_name);

$search   = trim($_GET['search']  ?? '');
$don_id   = (int)($_GET['don_id'] ?? 0);
$error    = '';

$su_kien_map = [
    'tao_don'         => ['label' => 'Đơn được tạo',               'icon' => '📝', 'color' => '#64748b'],
    'duyet_don'       => ['label' => 'Đơn đã được duyệt',          'icon' => '✅', 'color' => '#2563eb'],
    'lay_hang'        => ['label' => 'Đang lấy hàng',              'icon' => '📦', 'color' => '#f59e0b'],
    'den_kho'         => ['label' => 'Hàng đến kho trung chuyển',  'icon' => '🏭', 'color' => '#8b5cf6'],
    'roi_kho'         => ['label' => 'Hàng rời kho',               'icon' => '🚛', 'color' => '#2563eb'],
    'dang_van_chuyen' => ['label' => 'Đang vận chuyển',            'icon' => '🛣️', 'color' => '#3b82f6'],
    'den_kho_dich'    => ['label' => 'Hàng đến kho đích',          'icon' => '📍', 'color' => '#10b981'],
    'dang_giao'       => ['label' => 'Đang giao đến bạn',          'icon' => '🏃', 'color' => '#f97316'],
    'da_giao'         => ['label' => 'Giao hàng thành công!',      'icon' => '🎉', 'color' => '#10b981'],
    'that_bai'        => ['label' => 'Giao hàng thất bại',         'icon' => '⚠️', 'color' => '#ef4444'],
    'hoan_hang'       => ['label' => 'Hoàn hàng về kho',           'icon' => '↩️', 'color' => '#ef4444'],
];

// Tiến độ % theo sự kiện
$progress_map = [
    'tao_don' => 5, 'duyet_don' => 15, 'lay_hang' => 30,
    'den_kho' => 45, 'roi_kho' => 55, 'dang_van_chuyen' => 65,
    'den_kho_dich' => 80, 'dang_giao' => 90, 'da_giao' => 100,
    'that_bai' => 0, 'hoan_hang' => 0,
];

$tt_map = [
    'cho_duyet'       => ['l' => 'Chờ duyệt',     'c' => '#f59e0b', 'bg' => '#fef9c3'],
    'dang_xu_ly'      => ['l' => 'Đang xử lý',    'c' => '#3b82f6', 'bg' => '#dbeafe'],
    'dang_lay_hang'   => ['l' => 'Đang lấy hàng', 'c' => '#f97316', 'bg' => '#ffedd5'],
    'dang_van_chuyen' => ['l' => 'Đang chạy',     'c' => '#2563eb', 'bg' => '#dbeafe'],
    'da_giao'         => ['l' => 'Đã giao',         'c' => '#10b981', 'bg' => '#d1fae5'],
    'hoan_thanh'      => ['l' => 'Hoàn thành',    'c' => '#10b981', 'bg' => '#d1fae5'],
    'da_thanh_toan'   => ['l' => 'Đã TT',           'c' => '#2563eb', 'bg' => '#dbeafe'],
    'huy'             => ['l' => 'Đã hủy',          'c' => '#ef4444', 'bg' => '#fee2e2'],
];

// ── Tìm kiếm đơn hàng ────────────────────────────────────────
$ds_don   = [];
$don_detail = null;
$lo_trinh   = [];
$phan_tram  = 0;

if ($search) {
    $s = mysqli_real_escape_string($conn, $search);
    // Chỉ cho xem đơn của chính mình
    $ds_don = $conn->query(
        "SELECT id, ma_don, ten_khach, tinh_lay, tinh_giao, trang_thai, ngay_tao
         FROM don_hang
         WHERE (ma_don='$s' OR dien_thoai_kh='$s')
           AND (ten_khach='$fn_esc' OR nguoi_tao_id=$uid)
           AND is_deleted=0
         ORDER BY ngay_tao DESC LIMIT 10"
    )->fetch_all(MYSQLI_ASSOC);

    if (empty($ds_don)) {
        $error = 'Không tìm thấy đơn hàng nào với thông tin này.';
    }
    if (count($ds_don) === 1 && !$don_id) {
        $don_id = $ds_don[0]['id'];
    }
}

// ── Tất cả đơn đang vận chuyển của tôi ───────────────────────
$don_dang_van = $conn->query(
    "SELECT id, ma_don, tinh_lay, tinh_giao, trang_thai, ngay_tao
     FROM don_hang
     WHERE (ten_khach='$fn_esc' OR nguoi_tao_id=$uid)
       AND trang_thai IN ('dang_xu_ly','dang_lay_hang','dang_van_chuyen')
       AND is_deleted=0
     ORDER BY ngay_tao DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

// ── Load chi tiết đơn đang xem ───────────────────────────────
if ($don_id > 0) {
    $don_detail = $conn->query(
        "SELECT dh.*, x.bien_so, tx.ho_ten AS ten_tai_xe, tx.so_dien_thoai AS sdt_tx
         FROM don_hang dh
         LEFT JOIN xe x      ON dh.xe_id = x.id
         LEFT JOIN tai_xe tx ON dh.tai_xe_id = tx.id
         WHERE dh.id = $don_id
           AND (dh.ten_khach='$fn_esc' OR dh.nguoi_tao_id=$uid)
           AND dh.is_deleted = 0
         LIMIT 1"
    )->fetch_assoc();

    if ($don_detail) {
        $lo_trinh = $conn->query(
            "SELECT lt.*, k.ten_kho, k.tinh_thanh AS kho_tinh, k.loai_kho
             FROM lo_trinh_don_hang lt
             LEFT JOIN kho k ON lt.kho_id = k.id
             WHERE lt.don_hang_id = $don_id
             ORDER BY lt.thoi_gian ASC"
        )->fetch_all(MYSQLI_ASSOC);

        // Tính phần trăm tiến độ
        if (!empty($lo_trinh)) {
            $last_sk = end($lo_trinh)['su_kien'];
            $phan_tram = $progress_map[$last_sk] ?? 0;
        }
        if ($don_detail['trang_thai'] === 'cho_duyet' && empty($lo_trinh)) {
            $phan_tram = 5;
        }
        if ($don_detail['trang_thai'] === 'hoan_thanh') {
            $phan_tram = 100;
        }
    } else {
        $error = 'Không tìm thấy đơn hàng này hoặc bạn không có quyền xem.';
        $don_id = 0;
    }
}

// Thời điểm cuối cùng cập nhật (dùng cho auto-refresh check)
$last_update = !empty($lo_trinh) ? end($lo_trinh)['created_at'] : null;

$active = 'lo_trinh';
require 'sidebar_customer.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Theo Dõi Lộ Trình — Khách Hàng</title>
<link rel="stylesheet" href="customer_layout.css">
<style>
/* ── Layout ── */
.lt-layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 18px;
    align-items: start;
}

/* ── Panel trái: danh sách đơn ── */
.panel-left {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

/* Card đơn đang vận chuyển */
.don-card-mini {
    background: #fff;
    border: 1.5px solid var(--border);
    border-radius: 10px;
    padding: 12px 14px;
    cursor: pointer;
    transition: all .2s;
    text-decoration: none;
    display: block;
    color: inherit;
}
.don-card-mini:hover  { border-color: var(--primary); box-shadow: 0 2px 10px rgba(124,58,237,.1); }
.don-card-mini.active { border-color: var(--primary); background: var(--primary-light); border-left: 4px solid var(--primary); }

/* Search box */
.search-form {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 16px;
}
.search-form h3 { font-size: 13px; font-weight: 700; margin-bottom: 10px; color: var(--text); }
.s-row { display: flex; gap: 8px; }
.s-inp {
    flex: 1;
    padding: 10px 14px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
    outline: none;
    transition: border-color .2s;
}
.s-inp:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(124,58,237,.1); }

/* ── Panel phải: timeline ── */
.don-head-card {
    background: linear-gradient(135deg, #7c3aed, #a855f7);
    border-radius: 12px;
    padding: 18px 20px;
    color: #fff;
    margin-bottom: 16px;
}
.don-head-card .don-ma   { font-size: 18px; font-weight: 800; }
.don-head-card .don-kh   { font-size: 13px; opacity: .85; margin-top: 4px; }
.don-head-card .don-route{ font-size: 13px; margin-top: 10px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

/* Thanh tiến độ */
.progress-wrap { background: #fff; border-radius: 10px; padding: 16px 20px; margin-bottom: 16px; border: 1px solid var(--border); }
.progress-label { display: flex; justify-content: space-between; font-size: 12px; color: var(--muted); margin-bottom: 8px; }
.progress-track { height: 12px; background: #e2e8f0; border-radius: 10px; overflow: hidden; }
.progress-fill {
    height: 100%;
    border-radius: 10px;
    background: linear-gradient(90deg, #7c3aed, #10b981);
    transition: width .8s ease;
}

/* Thẻ xe/tài xế */
.xe-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f8fafc;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 16px;
}

/* Timeline */
.tl { position: relative; padding-left: 28px; }
.tl::before {
    content: '';
    position: absolute;
    left: 12px; top: 8px; bottom: 8px;
    width: 2px;
    background: linear-gradient(to bottom, #e2e8f0, #e2e8f0);
}
.tl-item { position: relative; padding-bottom: 20px; }
.tl-item:last-child { padding-bottom: 0; }
.tl-dot {
    position: absolute;
    left: -21px; top: 2px;
    width: 20px; height: 20px;
    border-radius: 50%;
    background: #fff;
    border: 2.5px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    z-index: 1;
}
.tl-dot.active { border-color: var(--primary); background: var(--primary-light); box-shadow: 0 0 0 4px rgba(124,58,237,.12); }
.tl-dot.done   { background: #10b981; border-color: #10b981; color: #fff; }
.tl-body {
    background: #fff;
    border: 1px solid #f1f5f9;
    border-radius: 10px;
    padding: 12px 14px;
    transition: box-shadow .2s;
}
.tl-body:hover   { box-shadow: 0 2px 10px rgba(0,0,0,.07); }
.tl-body.current { border-color: var(--primary); background: #faf5ff; }
.tl-body.success { border-color: #10b981; background: #f0fdf4; }

/* Auto-refresh indicator */
.refresh-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--muted);
    margin-bottom: 14px;
    padding: 8px 14px;
    background: #fff;
    border-radius: 8px;
    border: 1px solid var(--border);
}
.refresh-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #10b981;
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%       { opacity: .5; transform: scale(.8); }
}

/* Kho badge */
.kho-tag {
    display: inline-flex; align-items: center; gap: 4px;
    background: var(--primary-light); color: var(--primary);
    font-size: 11px; font-weight: 700;
    padding: 2px 8px; border-radius: 8px; margin-top: 4px;
}

/* Empty */
.empty-lt { text-align: center; padding: 50px 20px; color: var(--muted); }
.empty-lt .ei { font-size: 48px; display: block; margin-bottom: 12px; }

@media (max-width: 900px) {
    .lt-layout { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="app">
<?php require 'sidebar_customer.php'; ?>

<main class="main">
    <div class="topbar">
        <div>
            <div class="topbar-title">📍 Theo Dõi Lộ Trình</div>
            <div class="breadcrumb"><a href="customer_dashboard.php">Dashboard</a> › Lộ trình</div>
        </div>
        <div class="user-chip">
            <div class="chip-avatar"><?= mb_strtoupper(mb_substr($full_name, 0, 1)) ?></div>
            <div>
                <div class="chip-name"><?= htmlspecialchars($full_name) ?></div>
                <div class="chip-role">Khách Hàng</div>
            </div>
        </div>
    </div>

    <div class="content">

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="lt-layout">

        <!-- ═══ CỘT TRÁI ═══ -->
        <div class="panel-left">

            <!-- Form tìm kiếm -->
            <div class="search-form">
                <h3>🔍 Tra cứu đơn hàng</h3>
                <form method="GET">
                    <?php if ($don_id): ?>
                    <input type="hidden" name="don_id" value="<?= $don_id ?>">
                    <?php endif; ?>
                    <div class="s-row">
                        <input type="text" name="search" class="s-inp"
                               placeholder="Nhập mã đơn hoặc SĐT..."
                               value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary btn-sm">Tìm</button>
                    </div>
                    <div style="font-size:11px;color:var(--muted);margin-top:8px">
                        VD: VT-2024-001 hoặc số điện thoại đặt hàng
                    </div>
                </form>
            </div>

            <!-- Đơn đang vận chuyển -->
            <?php if (!empty($don_dang_van)): ?>
            <div style="background:#fff;border:1px solid var(--border);border-radius:10px;overflow:hidden">
                <div style="padding:12px 14px;border-bottom:1px solid var(--border);font-size:12px;font-weight:700;color:var(--primary);background:#faf5ff">
                    🚛 Đơn đang vận chuyển (<?= count($don_dang_van) ?>)
                </div>
                <?php foreach ($don_dang_van as $d):
                    $tt = $tt_map[$d['trang_thai']] ?? ['l' => $d['trang_thai'], 'c' => '#64748b', 'bg' => '#f1f5f9'];
                ?>
                <a href="?don_id=<?= $d['id'] ?>" class="don-card-mini <?= $d['id'] === $don_id ? 'active' : '' ?>"
                   style="border-radius:0;border-left:none;border-right:none;border-top:none;border-bottom:1px solid #f1f5f9">
                    <div style="font-weight:700;font-size:13px"><?= htmlspecialchars($d['ma_don']) ?></div>
                    <div style="font-size:11px;color:var(--muted);margin-top:3px">
                        📍 <?= htmlspecialchars($d['tinh_lay']) ?> → 🏁 <?= htmlspecialchars($d['tinh_giao']) ?>
                    </div>
                    <span style="background:<?= $tt['bg'] ?>;color:<?= $tt['c'] ?>;font-size:10px;font-weight:700;padding:2px 8px;border-radius:8px;display:inline-block;margin-top:5px">
                        <?= $tt['l'] ?>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Kết quả tìm kiếm (nhiều đơn) -->
            <?php if (count($ds_don) > 1): ?>
            <div style="background:#fff;border:1px solid var(--border);border-radius:10px;overflow:hidden">
                <div style="padding:12px 14px;border-bottom:1px solid var(--border);font-size:12px;font-weight:700;color:var(--primary)">
                    📋 Kết quả tìm kiếm (<?= count($ds_don) ?>)
                </div>
                <?php foreach ($ds_don as $d):
                    $tt = $tt_map[$d['trang_thai']] ?? ['l' => $d['trang_thai'], 'c' => '#64748b', 'bg' => '#f1f5f9'];
                ?>
                <a href="?search=<?= urlencode($search) ?>&don_id=<?= $d['id'] ?>"
                   class="don-card-mini <?= $d['id'] === $don_id ? 'active' : '' ?>"
                   style="border-radius:0;border:none;border-bottom:1px solid #f1f5f9">
                    <div style="font-weight:700;font-size:13px"><?= htmlspecialchars($d['ma_don']) ?></div>
                    <div style="font-size:11px;color:var(--muted);margin-top:2px">
                        📍<?= htmlspecialchars($d['tinh_lay']) ?> → 🏁<?= htmlspecialchars($d['tinh_giao']) ?>
                    </div>
                    <span style="background:<?= $tt['bg'] ?>;color:<?= $tt['c'] ?>;font-size:10px;font-weight:700;padding:2px 8px;border-radius:8px;margin-top:4px;display:inline-block">
                        <?= $tt['l'] ?>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div><!-- /panel-left -->

        <!-- ═══ CỘT PHẢI ═══ -->
        <div>
        <?php if (!$don_detail): ?>

            <!-- Chưa chọn đơn -->
            <div style="background:#fff;border:1px solid var(--border);border-radius:12px">
                <div class="empty-lt">
                    <span class="ei">📦</span>
                    <p style="font-size:15px;font-weight:600;color:var(--text)">
                        Chọn đơn hàng để theo dõi lộ trình
                    </p>
                    <p style="font-size:13px;margin-top:8px">
                        Nhập mã đơn hàng hoặc chọn đơn đang vận chuyển ở bên trái
                    </p>
                </div>
                <!-- Tính năng -->
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;padding:0 24px 24px">
                    <?php
                    $features = [
                        ['📦','Cập nhật theo thời gian thực','Lộ trình tự động cập nhật mỗi 30 giây'],
                        ['🏭','Theo dõi qua từng kho','Biết hàng đang ở kho nào trên hành trình'],
                        ['🚛','Thông tin xe & tài xế','Xem biển số xe và liên hệ tài xế khi cần'],
                        ['🔔','Thông báo tự động','Nhận thông báo ngay khi hàng đến điểm mới'],
                    ];
                    foreach ($features as $f):
                    ?>
                    <div style="background:#faf5ff;border:1px solid var(--primary-light);border-radius:8px;padding:14px">
                        <div style="font-size:22px;margin-bottom:6px"><?= $f[0] ?></div>
                        <div style="font-size:13px;font-weight:700;color:var(--primary)"><?= $f[1] ?></div>
                        <div style="font-size:11px;color:var(--muted);margin-top:3px"><?= $f[2] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else: ?>

            <!-- Auto-refresh indicator -->
            <div class="refresh-bar">
                <div class="refresh-dot" id="refreshDot"></div>
                <span>Tự động cập nhật mỗi 30 giây</span>
                <span style="margin-left:auto;font-weight:600" id="countdown">30s</span>
                <button onclick="forceRefresh()" class="btn btn-ghost btn-sm" style="padding:3px 10px;font-size:11px">
                    🔄 Làm mới
                </button>
            </div>

            <!-- Header đơn hàng -->
            <div class="don-head-card">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:10px">
                    <div>
                        <div class="don-ma">📦 <?= htmlspecialchars($don_detail['ma_don']) ?></div>
                        <div class="don-kh">👤 <?= htmlspecialchars($don_detail['ten_khach']) ?></div>
                    </div>
                    <?php $tt = $tt_map[$don_detail['trang_thai']] ?? ['l' => $don_detail['trang_thai'], 'c' => '#fff', 'bg' => 'rgba(255,255,255,.2)']; ?>
                    <span style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);font-size:12px;font-weight:700;padding:5px 12px;border-radius:20px">
                        <?= $tt['l'] ?>
                    </span>
                </div>
                <div class="don-route">
                    <span>📍 <strong><?= htmlspecialchars($don_detail['tinh_lay']) ?></strong></span>
                    <span style="color:rgba(255,255,255,.6);font-size:18px">→→→</span>
                    <span>🏁 <strong><?= htmlspecialchars($don_detail['tinh_giao']) ?></strong></span>
                </div>
                <!-- Ngày tạo & giao dự kiến -->
                <div style="display:flex;gap:20px;margin-top:10px;font-size:12px;opacity:.8;flex-wrap:wrap">
                    <span>🗓️ Tạo: <?= date('d/m/Y', strtotime($don_detail['ngay_tao'])) ?></span>
                    <?php if ($don_detail['ngay_giao_du_kien']): ?>
                    <span>⏰ Giao dự kiến: <strong><?= date('d/m/Y', strtotime($don_detail['ngay_giao_du_kien'])) ?></strong></span>
                    <?php endif; ?>
                    <?php if ($don_detail['ngay_giao_thuc_te']): ?>
                    <span>✅ Đã giao: <?= date('d/m/Y H:i', strtotime($don_detail['ngay_giao_thuc_te'])) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Thanh tiến độ -->
            <div class="progress-wrap">
                <div class="progress-label">
                    <span style="font-weight:600">📊 Tiến độ giao hàng</span>
                    <span style="font-weight:800;color:<?= $phan_tram >= 100 ? '#10b981' : 'var(--primary)' ?>">
                        <?= $phan_tram ?>%
                    </span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" id="progressBar" style="width:<?= $phan_tram ?>%"></div>
                </div>
                <!-- Các mốc tiến độ -->
                <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:10px;color:var(--muted)">
                    <span>📝 Tạo đơn</span>
                    <span>📦 Lấy hàng</span>
                    <span>🏭 Kho TC</span>
                    <span>🚛 Đang chạy</span>
                    <span>🎉 Hoàn thành</span>
                </div>
            </div>

            <!-- Thông tin xe & tài xế -->
            <?php if ($don_detail['bien_so'] && !in_array($don_detail['trang_thai'], ['cho_duyet','dang_xu_ly'])): ?>
            <div class="xe-card">
                <div style="font-size:28px">🚛</div>
                <div style="flex:1">
                    <div style="font-weight:700;font-size:14px"><?= htmlspecialchars($don_detail['bien_so']) ?></div>
                    <div style="font-size:12px;color:var(--muted);margin-top:3px">
                        👤 Tài xế: <strong><?= htmlspecialchars($don_detail['ten_tai_xe'] ?? '—') ?></strong>
                        <?php if ($don_detail['sdt_tx'] && in_array($don_detail['trang_thai'], ['dang_van_chuyen','dang_lay_hang'])): ?>
                        &nbsp;·&nbsp;
                        <a href="tel:<?= htmlspecialchars($don_detail['sdt_tx']) ?>"
                           style="color:var(--primary);font-weight:700;text-decoration:none">
                            📞 <?= htmlspecialchars($don_detail['sdt_tx']) ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($don_detail['trang_thai'] === 'dang_van_chuyen'): ?>
                <span style="background:#dcfce7;color:#166534;font-size:11px;font-weight:700;padding:4px 10px;border-radius:8px;white-space:nowrap">
                    🟢 Đang chạy
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Timeline lộ trình -->
            <div style="background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px" id="timelineWrap">
                <div style="font-size:14px;font-weight:700;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between">
                    <span>📌 Lộ Trình Chi Tiết</span>
                    <span style="font-size:12px;color:var(--muted);font-weight:400">
                        <?= count($lo_trinh) ?> cập nhật
                        <?php if ($last_update): ?>
                        · Cuối: <?= date('H:i d/m', strtotime($last_update)) ?>
                        <?php endif; ?>
                    </span>
                </div>

                <?php if (empty($lo_trinh)): ?>
                <div style="text-align:center;padding:30px;color:var(--muted)">
                    <div style="font-size:36px;margin-bottom:8px">🕐</div>
                    <p style="font-weight:600;color:#475569">Đơn hàng đang được xử lý</p>
                    <p style="font-size:12px;margin-top:6px">Lộ trình sẽ xuất hiện khi hàng bắt đầu vận chuyển.</p>
                </div>

                <?php else: ?>
                <div class="tl" id="timeline">
                    <?php foreach ($lo_trinh as $i => $lt):
                        $sk = $su_kien_map[$lt['su_kien']] ?? ['label' => $lt['su_kien'], 'icon' => '📍', 'color' => '#64748b'];
                        $is_last = ($i === count($lo_trinh) - 1);
                        $is_done = ($lt['su_kien'] === 'da_giao');
                        $body_cls = $is_done ? 'success' : ($is_last ? 'current' : '');
                    ?>
                    <div class="tl-item">
                        <div class="tl-dot <?= $is_last ? 'active' : ($is_done ? 'done' : '') ?>">
                            <?= $is_done ? '✓' : $sk['icon'] ?>
                        </div>
                        <div class="tl-body <?= $body_cls ?>">
                            <!-- Header mốc -->
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;flex-wrap:wrap">
                                <div style="display:flex;align-items:center;gap:6px;font-weight:700;font-size:13px">
                                    <span style="font-size:16px"><?= $sk['icon'] ?></span>
                                    <span style="color:<?= $sk['color'] ?>"><?= $sk['label'] ?></span>
                                    <?php if ($is_last && !$is_done): ?>
                                    <span style="background:#ede9fe;color:#7c3aed;font-size:10px;font-weight:700;padding:1px 7px;border-radius:10px;animation:pulse 2s infinite">
                                        ĐANG Ở ĐÂY
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <span style="font-size:11px;color:var(--muted);white-space:nowrap">
                                    🕐 <?= date('d/m/Y H:i', strtotime($lt['thoi_gian'])) ?>
                                </span>
                            </div>

                            <!-- Kho trung chuyển -->
                            <?php if ($lt['ten_kho']): ?>
                            <div style="margin-bottom:5px">
                                <span class="kho-tag">🏭 <?= htmlspecialchars($lt['ten_kho']) ?></span>
                                <?php if ($lt['kho_tinh']): ?>
                                <span style="font-size:11px;color:var(--muted);margin-left:4px"><?= htmlspecialchars($lt['kho_tinh']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php elseif ($lt['dia_diem']): ?>
                            <div style="font-size:12px;font-weight:600;margin-bottom:4px">
                                📍 <?= htmlspecialchars($lt['dia_diem']) ?>
                                <?php if ($lt['tinh_thanh']): ?>
                                <span style="color:var(--muted);font-weight:400">, <?= htmlspecialchars($lt['tinh_thanh']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Mô tả -->
                            <?php if ($lt['mo_ta']): ?>
                            <div style="font-size:12px;color:#475569;line-height:1.6;margin-bottom:4px">
                                <?= nl2br(htmlspecialchars($lt['mo_ta'])) ?>
                            </div>
                            <?php endif; ?>

                            <!-- Thời gian -->
                            <div style="font-size:11px;color:#94a3b8;margin-top:4px">
                                🕐 <?= date('d/m/Y H:i', strtotime($lt['thoi_gian'])) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Ghi chú hàng hóa -->
            <?php if ($don_detail['ghi_chu']): ?>
            <div style="background:#fff;border:1px solid var(--border);border-radius:10px;padding:14px 18px;margin-top:14px">
                <div style="font-size:12px;font-weight:700;color:var(--muted);margin-bottom:6px">📝 Ghi Chú Đơn Hàng</div>
                <div style="font-size:13px;color:var(--text)"><?= htmlspecialchars($don_detail['ghi_chu']) ?></div>
            </div>
            <?php endif; ?>

        <?php endif; // don_detail ?>
        </div><!-- /cột phải -->

        </div><!-- /lt-layout -->
    </div><!-- /content -->
</main>
</div><!-- /app -->

<script>
// ── AUTO REFRESH ──────────────────────────────────────────────
<?php if ($don_detail): ?>
let countdown   = 30;
const countEl   = document.getElementById('countdown');
const refreshDot= document.getElementById('refreshDot');

// Đếm ngược
const timer = setInterval(() => {
    countdown--;
    if (countEl) countEl.textContent = countdown + 's';
    if (countdown <= 0) {
        countdown = 30;
        checkForUpdate();
    }
}, 1000);

// Gọi API kiểm tra xem có cập nhật mới không
function checkForUpdate() {
    if (refreshDot) {
        refreshDot.style.background = '#f59e0b'; // Vàng = đang kiểm tra
    }
    fetch('api_kiem_tra_cap_nhat.php?don_id=<?= $don_id ?>&last=<?= urlencode($last_update ?? '') ?>')
        .then(r => r.json())
        .then(data => {
            if (data.co_cap_nhat) {
                // Có cập nhật mới → reload trang
                window.location.reload();
            } else {
                if (refreshDot) refreshDot.style.background = '#10b981'; // Xanh = OK
            }
        })
        .catch(() => {
            if (refreshDot) refreshDot.style.background = '#ef4444'; // Đỏ = lỗi
        });
}

// Nút làm mới thủ công
function forceRefresh() {
    window.location.reload();
}

// Animate progress bar khi load
window.addEventListener('load', () => {
    const bar = document.getElementById('progressBar');
    if (bar) {
        bar.style.width = '0%';
        setTimeout(() => { bar.style.width = '<?= $phan_tram ?>%'; }, 100);
    }
});
<?php else: ?>
function forceRefresh() { window.location.reload(); }
<?php endif; ?>
</script>
</body>
</html>
