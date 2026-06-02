<?php
// ============================================================
// customer_dashboard.php — Dashboard Khách Hàng
// Cùng phong cách với Admin & Điều Phối
// ============================================================
session_start();
require 'config.php';
require_role(['khachhang']);

$full_name  = $_SESSION['full_name'] ?? 'Khách hàng';
$username   = $_SESSION['username']  ?? '';
$email      = $_SESSION['email']     ?? '';
$uid        = $_SESSION['user_id'];
$login_time = isset($_SESSION['login_time']) ? date('d/m/Y H:i', $_SESSION['login_time']) : 'N/A';

// ── Thống kê đơn hàng của khách ──────────────────────────
$stats = [];
foreach (['cho_duyet','dang_xu_ly','dang_lay_hang','dang_van_chuyen','da_giao','hoan_thanh','huy'] as $tt) {
    $stats[$tt] = $conn->query(
        "SELECT COUNT(*) AS c FROM don_hang
         WHERE (ten_khach = '" . mysqli_real_escape_string($conn, $full_name) . "'
            OR dien_thoai_kh = '" . mysqli_real_escape_string($conn, $_SESSION['phone'] ?? '') . "')
         AND trang_thai = '$tt'"
    )->fetch_assoc()['c'] ?? 0;
}

$tong_don = array_sum($stats);
$dang_van  = $stats['dang_van_chuyen'] + $stats['dang_lay_hang'] + $stats['dang_xu_ly'];
$hoan_thanh= $stats['hoan_thanh'] + $stats['da_giao'];

// ── Đơn hàng gần đây ─────────────────────────────────────
$recent_don = $conn->query(
    "SELECT dh.*, x.bien_so, tx.ho_ten AS ten_tai_xe
     FROM don_hang dh
     LEFT JOIN xe x ON dh.xe_id = x.id
     LEFT JOIN tai_xe tx ON dh.tai_xe_id = tx.id
     WHERE dh.ten_khach = '" . mysqli_real_escape_string($conn, $full_name) . "'
     ORDER BY dh.ngay_tao DESC LIMIT 5"
);

// ── Thông báo chưa đọc ────────────────────────────────────
$notif_unread = $conn->query(
    "SELECT COUNT(*) AS c FROM thong_bao WHERE nguoi_nhan_id=$uid AND da_doc=0"
)->fetch_assoc()['c'] ?? 0;

$recent_notif = $conn->query(
    "SELECT * FROM thong_bao WHERE nguoi_nhan_id=$uid ORDER BY created_at DESC LIMIT 4"
)->fetch_all(MYSQLI_ASSOC);

$tt_map = [
    'cho_duyet'        => ['l' => 'Chờ duyệt',      'c' => 'b-cho_duyet'],
    'dang_xu_ly'       => ['l' => 'Đang xử lý',     'c' => 'b-dang_xu_ly'],
    'dang_lay_hang'    => ['l' => 'Đang lấy hàng',  'c' => 'b-dang_lay_hang'],
    'dang_van_chuyen'  => ['l' => 'Đang chạy',      'c' => 'b-dang_van_chuyen'],
    'da_giao'          => ['l' => 'Đã giao',          'c' => 'b-da_giao'],
    'hoan_thanh'       => ['l' => 'Hoàn thành',     'c' => 'b-hoan_thanh'],
    'da_thanh_toan'    => ['l' => 'Đã TT',            'c' => 'b-da_thanh_toan'],
    'huy'              => ['l' => 'Đã hủy',           'c' => 'b-huy'],
];

$loai_style = [
    'thong_tin' => ['icon' => 'ℹ️', 'bg' => '#ede9fe', 'color' => '#5b21b6'],
    'canh_bao'  => ['icon' => '⚠️', 'bg' => '#fef9c3', 'color' => '#854d0e'],
    'khan_cap'  => ['icon' => '🚨', 'bg' => '#fee2e2', 'color' => '#991b1b'],
];

$active = 'dashboard';
require 'sidebar_customer.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Khách Hàng</title>
    <link rel="stylesheet" href="customer_layout.css">
    <style>
    .welcome {
        background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
        border-radius: 14px;
        padding: 22px 28px;
        color: #fff;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .welcome h2  { font-size: 20px; font-weight: 700; }
    .welcome p   { font-size: 13px; color: rgba(255,255,255,.75); margin-top: 5px; }
    .welcome .big{ font-size: 52px; opacity: .85; }

    .panels {
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    .panel-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,.06);
    }
    .panel-card h3 {
        font-size: 14px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 14px;
        padding-bottom: 10px;
        border-bottom: 2px solid var(--primary-light);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .panel-card h3 a {
        font-size: 12px;
        text-decoration: none;
        color: var(--primary);
        font-weight: 500;
    }
    .don-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f4f6f8;
        font-size: 13px;
        gap: 10px;
    }
    .don-row:last-child { border-bottom: none; }
    .don-ma   { font-weight: 700; font-size: 13px; color: var(--text); min-width: 120px; }
    .don-route{ font-size: 11px; color: var(--muted); margin-top: 2px; }

    /* Tracking timeline mini */
    .track-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #f4f6f8;
    }
    .track-item:last-child { border-bottom: none; }
    .track-dot {
        width: 34px; height: 34px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; flex-shrink: 0;
    }

    /* Notif mini */
    .notif-row {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 9px 0;
        border-bottom: 1px solid #f4f6f8;
        font-size: 12px;
    }
    .notif-row:last-child { border-bottom: none; }
    .notif-icon-sm {
        width: 28px; height: 28px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; flex-shrink: 0;
    }

    /* Quick links */
    .quick-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        text-decoration: none;
        color: var(--text);
        transition: .2s;
        border: 1.5px solid var(--border);
    }
    .quick-link:hover {
        background: var(--primary-light);
        border-color: var(--primary);
        color: var(--primary);
    }
    .quick-link .ql-icon { font-size: 28px; }
    .quick-link span     { font-size: 13px; font-weight: 600; }

    /* Info tài khoản */
    .info-row {
        display: flex;
        padding: 10px 0;
        border-bottom: 1px solid #f8f9fa;
        font-size: 13px;
    }
    .info-row:last-child { border-bottom: none; }
    .info-lbl { width: 160px; color: var(--muted); font-weight: 600; flex-shrink: 0; }
    .info-val { color: var(--text); font-weight: 500; }

    @media(max-width:900px) { .panels { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="app">
<?php require 'sidebar_customer.php'; ?>

<main class="main">
    <!-- TOPBAR -->
    <div class="topbar">
        <div>
            <div class="topbar-title">📊 Tổng Quan</div>
            <div class="breadcrumb">Đăng nhập lúc <?= $login_time ?></div>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
            <?php if ($notif_unread > 0): ?>
            <a href="customer_thong_bao.php" style="position:relative;text-decoration:none;font-size:22px">
                🔔<span style="position:absolute;top:-4px;right:-6px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;padding:1px 5px;border-radius:10px">
                    <?= $notif_unread ?>
                </span>
            </a>
            <?php endif; ?>
            <div class="user-chip">
                <div class="chip-avatar"><?= mb_strtoupper(mb_substr($full_name, 0, 1)) ?></div>
                <div>
                    <div class="chip-name"><?= htmlspecialchars($full_name) ?></div>
                    <div class="chip-role">Khách Hàng</div>
                </div>
            </div>
        </div>
    </div>

    <div class="content">

        <!-- Welcome -->
        <div class="welcome">
            <div>
                <h2>Xin chào, <?= htmlspecialchars($full_name) ?>! 👋</h2>
                <p>Chào mừng đến cổng khách hàng vận tải hàng hóa.</p>
            </div>
            <div class="big">👤</div>
        </div>

        <!-- Stat Cards -->
        <div class="stat-cards">
            <div class="stat-card">
                <div class="sc-icon">📦</div>
                <div class="sc-label">Tổng đơn hàng</div>
                <div class="sc-value"><?= $tong_don ?></div>
                <div class="sc-sub">Đã đặt từ trước đến nay</div>
            </div>
            <div class="stat-card" style="border-top-color:#f59e0b">
                <div class="sc-icon">⏳</div>
                <div class="sc-label">Chờ duyệt</div>
                <div class="sc-value" style="color:#f59e0b"><?= $stats['cho_duyet'] ?></div>
                <div class="sc-sub">Đang chờ xử lý</div>
            </div>
            <div class="stat-card" style="border-top-color:#3b82f6">
                <div class="sc-icon">🚛</div>
                <div class="sc-label">Đang vận chuyển</div>
                <div class="sc-value" style="color:#3b82f6"><?= $dang_van ?></div>
                <div class="sc-sub">Đơn đang trên đường</div>
            </div>
            <div class="stat-card" style="border-top-color:#10b981">
                <div class="sc-icon">✅</div>
                <div class="sc-label">Hoàn thành</div>
                <div class="sc-value" style="color:#10b981"><?= $hoan_thanh ?></div>
                <div class="sc-sub">Đã giao thành công</div>
            </div>
            <div class="stat-card" style="border-top-color:#ef4444">
                <div class="sc-icon">❌</div>
                <div class="sc-label">Đã hủy</div>
                <div class="sc-value" style="color:#ef4444"><?= $stats['huy'] ?></div>
                <div class="sc-sub">Đơn bị hủy</div>
            </div>
        </div>

        <!-- Truy cập nhanh -->
        <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:24px">
            <h3 style="font-size:14px;font-weight:700;color:var(--primary);margin-bottom:16px">⚡ Truy Cập Nhanh</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:12px">
                <a href="customer_tao_don.php"   class="quick-link"><div class="ql-icon">➕</div><span>Tạo đơn mới</span></a>
                <a href="customer_don_hang.php"  class="quick-link"><div class="ql-icon">📋</div><span>Đơn của tôi</span></a>
                <a href="customer_lo_trinh.php"  class="quick-link"><div class="ql-icon">📍</div><span>Tra lộ trình</span></a>
                <a href="customer_cong_no.php"   class="quick-link"><div class="ql-icon">💰</div><span>Công nợ</span></a>
                <a href="customer_thong_bao.php" class="quick-link"><div class="ql-icon">🔔</div><span>Thông báo</span></a>
                <a href="customer_tai_khoan.php" class="quick-link"><div class="ql-icon">👤</div><span>Tài khoản</span></a>
            </div>
        </div>

        <!-- Panels: Đơn hàng gần đây + Thông báo -->
        <div class="panels">

            <!-- Đơn hàng gần đây -->
            <div class="panel-card">
                <h3>📋 Đơn Hàng Gần Đây
                    <a href="customer_don_hang.php">Xem tất cả →</a>
                </h3>

                <?php if ($recent_don && $recent_don->num_rows > 0):
                    while ($d = $recent_don->fetch_assoc()):
                        $tt_info = $tt_map[$d['trang_thai']] ?? ['l' => $d['trang_thai'], 'c' => ''];
                ?>
                <div class="don-row">
                    <div style="flex:1;min-width:0">
                        <div class="don-ma"><?= htmlspecialchars($d['ma_don']) ?></div>
                        <div class="don-route">
                            📍 <?= htmlspecialchars($d['tinh_lay'] ?? '—') ?> →
                            🏁 <?= htmlspecialchars($d['tinh_giao'] ?? '—') ?>
                        </div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:2px">
                            🗓️ <?= date('d/m/Y', strtotime($d['ngay_tao'])) ?>
                            <?php if ($d['bien_so']): ?>
                                · 🚛 <?= htmlspecialchars($d['bien_so']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="text-align:right;flex-shrink:0">
                        <span class="badge <?= $tt_info['c'] ?>"><?= $tt_info['l'] ?></span>
                        <div style="font-size:11px;font-weight:700;color:var(--primary);margin-top:4px">
                            ₫<?= number_format($d['doanh_thu']) ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; else: ?>
                <div class="empty-state" style="padding:30px">
                    <div class="ei">📦</div>
                    <p>Chưa có đơn hàng nào.</p>
                    <a href="customer_tao_don.php" class="btn btn-primary btn-sm" style="margin-top:12px">
                        ➕ Tạo đơn ngay
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Thông báo gần đây -->
            <div class="panel-card">
                <h3>🔔 Thông Báo
                    <a href="customer_thong_bao.php">Xem tất cả →</a>
                </h3>

                <?php if (!empty($recent_notif)):
                    foreach ($recent_notif as $n):
                        $style = $loai_style[$n['loai']] ?? $loai_style['thong_tin'];
                ?>
                <div class="notif-row">
                    <div class="notif-icon-sm" style="background:<?= $style['bg'] ?>">
                        <?= $style['icon'] ?>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:<?= $n['da_doc'] ? '500' : '700' ?>;font-size:13px;
                             color:<?= $n['da_doc'] ? 'var(--muted)' : 'var(--text)' ?>">
                            <?= htmlspecialchars(mb_strimwidth($n['tieu_de'], 0, 45, '...')) ?>
                            <?php if (!$n['da_doc']): ?>
                                <span style="width:7px;height:7px;border-radius:50%;background:#ef4444;display:inline-block;margin-left:4px;vertical-align:middle"></span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:3px">
                            🕐 <?= date('d/m/Y H:i', strtotime($n['created_at'])) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; else: ?>
                <div class="empty-state" style="padding:30px">
                    <div class="ei">🔔</div>
                    <p>Chưa có thông báo nào.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Thông tin tài khoản -->
        <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.06)">
            <h3 style="font-size:14px;font-weight:700;color:var(--primary);margin-bottom:16px;
                padding-bottom:10px;border-bottom:2px solid var(--primary-light);
                display:flex;align-items:center;justify-content:space-between">
                👤 Thông Tin Tài Khoản
                <a href="customer_tai_khoan.php" style="font-size:12px;text-decoration:none;color:var(--primary);font-weight:500">
                    Chỉnh sửa →
                </a>
            </h3>
            <div class="info-row">
                <span class="info-lbl">Họ và tên</span>
                <span class="info-val"><?= htmlspecialchars($full_name) ?></span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Tên đăng nhập</span>
                <span class="info-val"><?= htmlspecialchars($username) ?></span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Email</span>
                <span class="info-val"><?= htmlspecialchars($email) ?></span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Vai trò</span>
                <span class="info-val">
                    <span style="background:var(--primary-light);color:var(--primary);padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700">
                        👤 Khách Hàng
                    </span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Đăng nhập lúc</span>
                <span class="info-val"><?= $login_time ?></span>
            </div>
            <div class="info-row">
                <span class="info-lbl">Trạng thái</span>
                <span class="info-val" style="color:#10b981;font-weight:700">✅ Đang hoạt động</span>
            </div>
        </div>

    </div><!-- /content -->
</main>
</div><!-- /app -->
</body>
</html>
