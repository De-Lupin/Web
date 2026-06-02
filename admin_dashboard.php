<?php
session_start(); require 'config.php'; require_role(['admin']);

$admin_fullname = $_SESSION['full_name'] ?? 'Admin';
$login_time     = isset($_SESSION['login_time']) ? date('d/m/Y H:i:s',$_SESSION['login_time']) : 'N/A';

// Thống kê người dùng
$total_users   = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role!='admin' AND is_active=1")->fetch_assoc()['c']??0;
$total_dp      = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='dieuphoI' AND is_active=1")->fetch_assoc()['c']??0;
$total_kh      = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='khachhang' AND is_active=1")->fetch_assoc()['c']??0;
$logins_today  = $conn->query("SELECT COUNT(*) AS c FROM audit_log WHERE action='LOGIN' AND DATE(created_at)=CURDATE()")->fetch_assoc()['c']??0;

// Thống kê vận hành (nếu bảng tồn tại)
$don_thang = $xe_total = $doanh_thu = 0;
if ($conn->query("SHOW TABLES LIKE 'don_hang'")->num_rows > 0) {
    $don_thang  = $conn->query("SELECT COUNT(*) AS c FROM don_hang WHERE MONTH(ngay_tao)=MONTH(CURDATE()) AND YEAR(ngay_tao)=YEAR(CURDATE()) AND trang_thai!='huy'")->fetch_assoc()['c']??0;
    $doanh_thu  = $conn->query("SELECT COALESCE(SUM(doanh_thu),0) AS t FROM don_hang WHERE MONTH(ngay_tao)=MONTH(CURDATE()) AND trang_thai NOT IN ('huy','cho_duyet')")->fetch_assoc()['t']??0;
}
if ($conn->query("SHOW TABLES LIKE 'xe'")->num_rows > 0) {
    $xe_total = $conn->query("SELECT COUNT(*) AS c FROM xe")->fetch_assoc()['c']??0;
    $xe_chay  = $conn->query("SELECT COUNT(*) AS c FROM xe WHERE tinh_trang='dang_chay'")->fetch_assoc()['c']??0;
}

// Log gần nhất
$logs = $conn->query("SELECT username,action,detail,ip_address,created_at FROM audit_log ORDER BY created_at DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);

$active = 'dashboard'; require 'sidebar_admin.php';
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard Admin</title>
<link rel="stylesheet" href="admin_layout.css">
<style>
.welcome{background:var(--sidebar-bg);border-radius:14px;padding:22px 28px;color:#fff;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between}
.welcome h2{font-size:20px;font-weight:700}
.welcome p{font-size:13px;color:rgba(255,255,255,.75);margin-top:5px}
.panels{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.panel-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.panel-card h3{font-size:14px;font-weight:700;color:var(--primary);margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid #f0f2ff;display:flex;align-items:center;justify-content:space-between}
.panel-card h3 a{font-size:12px;text-decoration:none;color:var(--primary);font-weight:500}
.log-row{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid #f4f6f8;font-size:12px}
.log-row:last-child{border-bottom:none}
.quick-link{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;background:#f8f9fa;border-radius:12px;padding:20px;text-decoration:none;color:var(--text);transition:.2s;border:1.5px solid var(--border)}
.quick-link:hover{background:#f0f2ff;border-color:var(--primary);color:var(--primary)}
.quick-link .ql-icon{font-size:28px}
.quick-link span{font-size:13px;font-weight:600}
@media(max-width:900px){.panels{grid-template-columns:1fr}}
</style>
</head><body>
<div class="app">
<?php require 'sidebar_admin.php'; ?>

<main class="main">
<div class="topbar">
    <div>
        <div class="topbar-title">📊 Bảng Điều Khiển Admin</div>
        <div class="breadcrumb">Đăng nhập lúc <?= $login_time ?></div>
    </div>
    <div class="user-chip">
        <div class="chip-avatar"><?= mb_strtoupper(mb_substr($admin_fullname,0,1)) ?></div>
        <div><div class="chip-name"><?= htmlspecialchars($admin_fullname) ?></div>
        <div class="chip-role">Super Admin</div></div>
    </div>
</div>

<div class="content">
    <div class="welcome">
        <div>
            <h2>Xin chào, <?= htmlspecialchars($admin_fullname) ?>! 👋</h2>
            <p>Chào mừng trở lại hệ thống quản trị Vận Tải Đường Bộ.</p>
        </div>
        <div style="font-size:52px;opacity:.8">👨‍💼</div>
    </div>

    <!-- Stat cards -->
    <div class="stat-cards">
        <div class="stat-card">
            <div class="sc-icon">👥</div><div class="sc-label">Tổng người dùng</div>
            <div class="sc-value"><?= $total_users ?></div><div class="sc-sub">Điều phối & Khách hàng</div>
        </div>
        <div class="stat-card" style="border-top-color:#27ae60">
            <div class="sc-icon">📋</div><div class="sc-label">Điều Phối Viên</div>
            <div class="sc-value" style="color:#27ae60"><?= $total_dp ?></div><div class="sc-sub">Đang hoạt động</div>
        </div>
        <div class="stat-card" style="border-top-color:#2980b9">
            <div class="sc-icon">🏢</div><div class="sc-label">Khách Hàng</div>
            <div class="sc-value" style="color:#2980b9"><?= $total_kh ?></div><div class="sc-sub">Đang hoạt động</div>
        </div>
        <div class="stat-card" style="border-top-color:#e67e22">
            <div class="sc-icon">📊</div><div class="sc-label">Đăng Nhập Hôm Nay</div>
            <div class="sc-value" style="color:#e67e22"><?= $logins_today ?></div><div class="sc-sub">Lượt truy cập</div>
        </div>
        <?php if($don_thang > 0 || true): ?>
        <div class="stat-card" style="border-top-color:#8e44ad">
            <div class="sc-icon">📦</div><div class="sc-label">Đơn Hàng Tháng Này</div>
            <div class="sc-value" style="color:#8e44ad"><?= $don_thang ?></div><div class="sc-sub">Tháng <?= date('m/Y') ?></div>
        </div>
        <div class="stat-card" style="border-top-color:#1e8449">
            <div class="sc-icon">💰</div><div class="sc-label">Doanh Thu Tháng</div>
            <div class="sc-value" style="color:#1e8449;font-size:18px">₫<?= number_format($doanh_thu/1000000,1) ?>tr</div>
            <div class="sc-sub">Tháng <?= date('m/Y') ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Truy cập nhanh -->
    <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:24px">
        <h3 style="font-size:14px;font-weight:700;color:var(--primary);margin-bottom:16px">⚡ Truy Cập Nhanh</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:12px">
            <a href="admin_nguoi_dung.php" class="quick-link"><div class="ql-icon">👥</div><span>Người dùng</span></a>
            <a href="admin_don_hang.php"   class="quick-link"><div class="ql-icon">📦</div><span>Đơn hàng</span></a>
            <a href="admin_phuong_tien.php"class="quick-link"><div class="ql-icon">🚛</div><span>Phương tiện</span></a>
            <a href="admin_bao_cao.php"    class="quick-link"><div class="ql-icon">📊</div><span>Báo cáo</span></a>
            <a href="admin_nhat_ky.php"    class="quick-link"><div class="ql-icon">📋</div><span>Nhật ký</span></a>
            <a href="admin_cai_dat.php"    class="quick-link"><div class="ql-icon">⚙️</div><span>Cài đặt</span></a>
        </div>
    </div>
                    
    <!-- Log gần nhất -->
    <div class="panels">
        <div class="panel-card" style="grid-column:span 2">
            <h3>📋 Nhật Ký Hoạt Động Gần Đây <a href="admin_nhat_ky.php">Xem tất cả →</a></h3>
            <?php foreach($logs as $log):
                $action_class = 'b-'.$log['action'];
            ?>
            <div class="log-row">
                <span class="badge <?= $action_class ?>"><?= htmlspecialchars($log['action']) ?></span>
                <span style="font-weight:600;min-width:120px"><?= htmlspecialchars($log['username']??'—') ?></span>
                <span style="color:var(--muted);flex:1"><?= htmlspecialchars($log['detail']??'') ?></span>
                <span style="color:#bdc3c7;white-space:nowrap"><?= htmlspecialchars($log['ip_address']??'') ?></span>
                <span style="color:#bdc3c7;white-space:nowrap"><?= date('d/m H:i',strtotime($log['created_at'])) ?></span>
            </div>
            <?php endforeach; ?>
            <?php if(empty($logs)): ?>
                <div class="empty-state" style="padding:30px"><div class="ei">📋</div><p>Chưa có nhật ký.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>
</div>
</body></html>