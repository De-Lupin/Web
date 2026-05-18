<?php
// ============================================================
// ADMIN_DASHBOARD.PHP - Bảng điều khiển dành cho Admin
// Giữ nguyên giao diện cũ — thêm: kiểm tra role + thống kê thật từ DB
// ============================================================
session_start();
require 'config.php';

// Bảo vệ trang: chỉ admin được vào
require_role(['admin']);

$admin_username = $_SESSION['username']  ?? 'Admin';
$admin_fullname = $_SESSION['full_name'] ?? 'Quản trị viên';
$admin_email    = $_SESSION['email']     ?? 'N/A';
$login_time     = isset($_SESSION['login_time'])
                    ? date('d/m/Y H:i:s', $_SESSION['login_time'])
                    : 'N/A';

// ── Lấy thống kê từ DB ───────────────────────────────────
// Tổng người dùng (trừ admin)
$r = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role != 'admin' AND is_active = 1");
$total_users = $r->fetch_assoc()['total'] ?? 0;

// Điều phối viên
$r = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'dieuphoI' AND is_active = 1");
$total_dieuphoI = $r->fetch_assoc()['total'] ?? 0;

// Khách hàng
$r = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'khachhang' AND is_active = 1");
$total_kh = $r->fetch_assoc()['total'] ?? 0;

// Đăng nhập hôm nay (từ audit_log)
$r = $conn->query("SELECT COUNT(*) AS total FROM audit_log WHERE action = 'LOGIN' AND DATE(created_at) = CURDATE()");
$logins_today = $r->fetch_assoc()['total'] ?? 0;

// 10 nhật ký gần nhất
$logs = [];
$r = $conn->query(
    "SELECT username, action, detail, ip_address, created_at
     FROM audit_log ORDER BY created_at DESC LIMIT 10"
);
while ($row = $r->fetch_assoc()) $logs[] = $row;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điều khiển Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── Giữ nguyên toàn bộ style cũ ── */
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:#f5f7fa; }
        .admin-wrapper { display:flex; min-height:100vh; }

        /* SIDEBAR */
        .sidebar {
            width:260px;
            background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            color:white; padding:30px 20px;
            box-shadow:2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar-header { text-align:center; margin-bottom:40px; padding-bottom:20px; border-bottom:2px solid rgba(255,255,255,0.2); }
        .sidebar-header .logo { font-size:32px; margin-bottom:10px; }
        .sidebar-header h2  { font-size:18px; font-weight:600; }
        .sidebar-menu { list-style:none; }
        .sidebar-menu li   { margin-bottom:15px; }
        .sidebar-menu a {
            color:rgba(255,255,255,0.8); text-decoration:none;
            display:block; padding:12px 15px; border-radius:8px;
            transition:0.3s; font-size:14px;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background:rgba(255,255,255,0.2); color:white;
        }
        .sidebar-menu a.active { font-weight:600; }

        /* MAIN */
        .main-content { flex:1; display:flex; flex-direction:column; }
        .top-navbar {
            background:white; padding:20px 40px;
            box-shadow:0 2px 10px rgba(0,0,0,0.08);
            display:flex; justify-content:space-between; align-items:center;
        }
        .navbar-title h1  { color:#333; font-size:24px; }
        .navbar-user      { display:flex; align-items:center; gap:20px; }
        .user-info p      { color:#666; font-size:13px; margin:2px 0; }
        .user-info strong { color:#333; font-size:14px; }
        .logout-btn {
            padding:8px 20px; background:#e74c3c; color:white;
            border:none; border-radius:6px; cursor:pointer;
            font-size:14px; transition:0.3s; text-decoration:none;
        }
        .logout-btn:hover { background:#c0392b; }

        /* DASHBOARD CONTENT */
        .dashboard-content { flex:1; padding:40px; }
        .dashboard-cards {
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
            gap:20px; margin-bottom:30px;
        }
        .card {
            background:white; padding:25px; border-radius:10px;
            box-shadow:0 2px 15px rgba(0,0,0,0.08); transition:0.3s;
        }
        .card:hover { transform:translateY(-5px); box-shadow:0 8px 25px rgba(0,0,0,0.12); }
        .card-icon  { font-size:40px; margin-bottom:15px; }
        .card h3    { color:#333; margin-bottom:10px; font-size:16px; }
        .card p     { color:#666; font-size:13px; margin-bottom:15px; }
        .card-value { font-size:28px; font-weight:700; color:#667eea; }

        /* Content sections */
        .content-section {
            background:white; padding:30px; border-radius:10px;
            box-shadow:0 2px 15px rgba(0,0,0,0.08); margin-bottom:24px;
        }
        .content-section h2 {
            color:#333; margin-bottom:20px; font-size:20px;
            border-bottom:2px solid #f0f0f0; padding-bottom:15px;
        }

        /* Info table (giữ nguyên) */
        .info-table { width:100%; border-collapse:collapse; }
        .info-table tr      { border-bottom:1px solid #f0f0f0; }
        .info-table td      { padding:12px 0; color:#666; font-size:14px; }
        .info-table td:first-child { font-weight:600; color:#333; width:200px; }

        /* Log table */
        .log-table { width:100%; border-collapse:collapse; font-size:13px; }
        .log-table th {
            background:#f8f9fa; text-align:left;
            padding:10px 12px; color:#555; font-weight:600;
            border-bottom:2px solid #e9ecef;
        }
        .log-table td { padding:10px 12px; border-bottom:1px solid #f0f0f0; color:#666; }
        .log-table tr:hover td { background:#fafafa; }
        .badge-action {
            display:inline-block; padding:2px 8px; border-radius:4px;
            font-size:11px; font-weight:700;
        }
        .badge-LOGIN        { background:#d4edda; color:#155724; }
        .badge-LOGOUT       { background:#e2e3e5; color:#383d41; }
        .badge-LOGIN_FAILED { background:#f8d7da; color:#721c24; }
        .badge-ADMIN_LOGIN  { background:#cce5ff; color:#004085; }

        @media(max-width:768px){
            .admin-wrapper  { flex-direction:column; }
            .sidebar        { width:100%; }
            .dashboard-content { padding:20px; }
            .top-navbar     { flex-direction:column; gap:15px; }
        }
    </style>
</head>
<body>
<div class="admin-wrapper">

    <!-- SIDEBAR (giữ nguyên cũ) -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">👨‍💼</div>
            <h2>Admin Panel</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php" class="active">📊 Dashboard</a></li>
            <li><a href="indext.php">👥 Quản lý Người dùng</a></li>
            <li><a href="#">📦 Đơn hàng</a></li>
            <li><a href="#">🚛 Phương tiện</a></li>
            <li><a href="#">📈 Báo cáo</a></li>
            <li><a href="#">📝 Nhật ký</a></li>
            <li><a href="#">⚙️ Cài đặt</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="top-navbar">
            <div class="navbar-title">
                <h1>Bảng Điều Khiển Quản Trị</h1>
            </div>
            <div class="navbar-user">
                <div class="user-info">
                    <p>Xin chào,</p>
                    <strong><?= htmlspecialchars($admin_fullname) ?></strong>
                </div>
                <a href="logout.php" class="logout-btn">Đăng xuất</a>
            </div>
        </div>

        <div class="dashboard-content">

            <!-- Cards thống kê — giờ lấy số thật từ DB -->
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-icon">👥</div>
                    <h3>Tổng Người Dùng</h3>
                    <p>Điều phối &amp; Khách hàng</p>
                    <div class="card-value"><?= number_format($total_users) ?></div>
                </div>
                <div class="card">
                    <div class="card-icon">📋</div>
                    <h3>Điều Phối Viên</h3>
                    <p>Đang hoạt động</p>
                    <div class="card-value"><?= number_format($total_dieuphoI) ?></div>
                </div>
                <div class="card">
                    <div class="card-icon">🏢</div>
                    <h3>Khách Hàng</h3>
                    <p>Đang hoạt động</p>
                    <div class="card-value"><?= number_format($total_kh) ?></div>
                </div>
                <div class="card">
                    <div class="card-icon">📊</div>
                    <h3>Đăng Nhập Hôm Nay</h3>
                    <p>Lượt truy cập</p>
                    <div class="card-value"><?= number_format($logins_today) ?></div>
                </div>
            </div>

            <!-- Thông tin admin (giữ nguyên cũ) -->
            <div class="content-section">
                <h2>📋 Thông Tin Quản Trị Viên</h2>
                <table class="info-table">
                    <tr>
                        <td>Tên đăng nhập:</td>
                        <td><?= htmlspecialchars($admin_username) ?></td>
                    </tr>
                    <tr>
                        <td>Họ và tên:</td>
                        <td><?= htmlspecialchars($admin_fullname) ?></td>
                    </tr>
                    <tr>
                        <td>Email:</td>
                        <td><?= htmlspecialchars($admin_email) ?></td>
                    </tr>
                    <tr>
                        <td>Quyền hạn:</td>
                        <td><span style="background:#667eea;color:white;padding:4px 10px;border-radius:4px;font-weight:600">Super Admin</span></td>
                    </tr>
                    <tr>
                        <td>Thời gian đăng nhập:</td>
                        <td><?= htmlspecialchars($login_time) ?></td>
                    </tr>
                    <tr>
                        <td>Phiên hoạt động:</td>
                        <td style="color:#27ae60;font-weight:600">✓ Đang hoạt động</td>
                    </tr>
                </table>
            </div>

            <!-- Nhật ký hệ thống — dữ liệu thật từ audit_log -->
            <div class="content-section">
                <h2>📝 Nhật Ký Hoạt Động Gần Đây</h2>
                <?php if (empty($logs)): ?>
                    <p style="color:#999;font-size:14px">Chưa có nhật ký nào.</p>
                <?php else: ?>
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Người dùng</th>
                            <th>Hành động</th>
                            <th>Chi tiết</th>
                            <th>IP</th>
                            <th>Thời gian</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['username'] ?? '—') ?></td>
                            <td>
                                <span class="badge-action badge-<?= htmlspecialchars($log['action']) ?>">
                                    <?= htmlspecialchars($log['action']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($log['detail'] ?? '') ?></td>
                            <td><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
                            <td><?= htmlspecialchars($log['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
</body>
</html>
