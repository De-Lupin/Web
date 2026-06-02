<?php
// ============================================================
// customer_cong_no.php — Công Nợ Của Tôi
// ============================================================
session_start();
require 'config.php';
require_role(['khachhang']);

$uid       = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? '';
$fn_esc    = mysqli_real_escape_string($conn, $full_name);

// Lấy tất cả đơn chưa thanh toán (hoàn thành nhưng chưa có trạng thái da_thanh_toan)
$don_chua_tt = $conn->query(
    "SELECT * FROM don_hang
     WHERE ten_khach='$fn_esc'
       AND trang_thai IN ('hoan_thanh','da_giao')
     ORDER BY ngay_tao DESC"
)->fetch_all(MYSQLI_ASSOC);

// Đơn đã thanh toán
$don_da_tt = $conn->query(
    "SELECT * FROM don_hang
     WHERE ten_khach='$fn_esc'
       AND trang_thai = 'da_thanh_toan'
     ORDER BY ngay_giao_thuc_te DESC LIMIT 10"
)->fetch_all(MYSQLI_ASSOC);

// Tổng công nợ
$tong_cong_no = array_sum(array_column($don_chua_tt, 'doanh_thu'));

// Thống kê
$tong_tt = $conn->query(
    "SELECT COALESCE(SUM(doanh_thu),0) AS t FROM don_hang
     WHERE ten_khach='$fn_esc' AND trang_thai='da_thanh_toan'"
)->fetch_assoc()['t'] ?? 0;

$active = 'cong_no';
require 'sidebar_customer.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Công Nợ Của Tôi</title>
    <link rel="stylesheet" href="customer_layout.css">
    <style>
    .no-item {
        display:flex; align-items:center; justify-content:space-between;
        padding:14px 18px;
        border-radius:10px;
        border:1px solid #fde68a;
        background:#fffbeb;
        margin-bottom:10px;
        gap:14px;
        flex-wrap:wrap;
    }
    .no-ma   { font-weight:700; font-size:14px; color:var(--text); }
    .no-sub  { font-size:12px; color:var(--muted); margin-top:3px; }
    .no-amt  { font-size:18px; font-weight:800; color:#ef4444; white-space:nowrap; }
    .paid-item {
        display:flex; align-items:center; justify-content:space-between;
        padding:12px 18px;
        border-radius:10px;
        border:1px solid #bbf7d0;
        background:#f0fdf4;
        margin-bottom:8px;
        gap:14px;
        flex-wrap:wrap;
    }
    .paid-amt { font-size:16px; font-weight:700; color:#10b981; white-space:nowrap; }
    </style>
</head>
<body>
<div class="app">
<?php require 'sidebar_customer.php'; ?>

<main class="main">
    <div class="topbar">
        <div>
            <div class="topbar-title">💰 Công Nợ Của Tôi</div>
            <div class="breadcrumb"><a href="customer_dashboard.php">Dashboard</a> › Công nợ</div>
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

        <!-- Stat cards -->
        <div class="stat-cards" style="margin-bottom:24px">
            <div class="stat-card" style="border-top-color:#ef4444">
                <div class="sc-icon">💳</div>
                <div class="sc-label">Cần thanh toán</div>
                <div class="sc-value" style="color:#ef4444;font-size:20px">
                    ₫<?= number_format($tong_cong_no / 1000000, 1) ?>tr
                </div>
                <div class="sc-sub"><?= count($don_chua_tt) ?> đơn chưa TT</div>
            </div>
            <div class="stat-card" style="border-top-color:#10b981">
                <div class="sc-icon">✅</div>
                <div class="sc-label">Đã thanh toán</div>
                <div class="sc-value" style="color:#10b981;font-size:20px">
                    ₫<?= number_format($tong_tt / 1000000, 1) ?>tr
                </div>
                <div class="sc-sub"><?= count($don_da_tt) ?> đơn đã TT gần đây</div>
            </div>
            <div class="stat-card">
                <div class="sc-icon">📦</div>
                <div class="sc-label">Số đơn chưa TT</div>
                <div class="sc-value"><?= count($don_chua_tt) ?></div>
                <div class="sc-sub">Đơn cần xử lý</div>
            </div>
        </div>

        <?php if (!empty($don_chua_tt)): ?>
        <!-- Công nợ cần thanh toán -->
        <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:20px">
            <h3 style="font-size:14px;font-weight:700;color:#ef4444;margin-bottom:16px;
                padding-bottom:10px;border-bottom:2px solid #fee2e2;display:flex;align-items:center;gap:8px">
                ⚠️ Công Nợ Cần Thanh Toán (<?= count($don_chua_tt) ?> đơn)
            </h3>

            <?php foreach ($don_chua_tt as $d): ?>
            <div class="no-item">
                <div style="flex:1;min-width:0">
                    <div class="no-ma"><?= htmlspecialchars($d['ma_don']) ?></div>
                    <div class="no-sub">
                        📍 <?= htmlspecialchars($d['tinh_lay'] ?? '—') ?> →
                        🏁 <?= htmlspecialchars($d['tinh_giao'] ?? '—') ?>
                        &nbsp;·&nbsp;
                        🗓️ <?= date('d/m/Y', strtotime($d['ngay_tao'])) ?>
                    </div>
                    <?php if ($d['ngay_giao_thuc_te']): ?>
                    <div style="font-size:11px;color:#10b981;margin-top:3px">
                        ✅ Đã giao: <?= date('d/m/Y', strtotime($d['ngay_giao_thuc_te'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div style="text-align:right">
                    <div class="no-amt">₫<?= number_format($d['doanh_thu']) ?></div>
                    <span class="badge b-hoan_thanh" style="font-size:10px;margin-top:4px;display:inline-block">
                        <?= $d['trang_thai'] === 'hoan_thanh' ? 'Hoàn thành' : 'Đã giao' ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Tổng cộng -->
            <div style="margin-top:16px;padding:14px 18px;background:var(--primary-light);border-radius:10px;
                display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:14px;font-weight:700;color:var(--primary)">💰 Tổng cần thanh toán</span>
                <span style="font-size:22px;font-weight:800;color:#ef4444">
                    ₫<?= number_format($tong_cong_no) ?>
                </span>
            </div>

            <div style="margin-top:14px;font-size:13px;color:var(--muted);
                background:#f8fafc;border-radius:8px;padding:12px 16px">
                📞 Vui lòng liên hệ điều phối viên để xác nhận và thanh toán.<br>
                Hotline: <strong>028.1234.5678</strong> — Email: <strong>info@vantai.vn</strong>
            </div>
        </div>
        <?php else: ?>
        <div style="background:#fff;border-radius:12px;padding:40px;text-align:center;
            box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:20px;color:var(--muted)">
            <div style="font-size:48px;margin-bottom:12px">🎉</div>
            <p style="font-size:15px;font-weight:600;color:var(--text)">Không có công nợ nào!</p>
            <p style="font-size:13px;margin-top:6px">Tất cả đơn hàng đã được thanh toán.</p>
        </div>
        <?php endif; ?>

        <!-- Lịch sử đã thanh toán -->
        <?php if (!empty($don_da_tt)): ?>
        <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.06)">
            <h3 style="font-size:14px;font-weight:700;color:#10b981;margin-bottom:16px;
                padding-bottom:10px;border-bottom:2px solid #d1fae5">
                ✅ Lịch Sử Đã Thanh Toán (<?= count($don_da_tt) ?> gần nhất)
            </h3>
            <?php foreach ($don_da_tt as $d): ?>
            <div class="paid-item">
                <div style="flex:1;min-width:0">
                    <div style="font-weight:700;font-size:13px"><?= htmlspecialchars($d['ma_don']) ?></div>
                    <div style="font-size:12px;color:var(--muted);margin-top:2px">
                        📍 <?= htmlspecialchars($d['tinh_lay'] ?? '—') ?> →
                        🏁 <?= htmlspecialchars($d['tinh_giao'] ?? '—') ?>
                        &nbsp;·&nbsp; 🗓️ <?= date('d/m/Y', strtotime($d['ngay_tao'])) ?>
                    </div>
                </div>
                <div>
                    <div class="paid-amt">₫<?= number_format($d['doanh_thu']) ?></div>
                    <span class="badge b-da_thanh_toan" style="font-size:10px;display:block;text-align:right;margin-top:3px">
                        Đã TT
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</main>
</div>
</body>
</html>
