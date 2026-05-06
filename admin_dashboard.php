<?php
session_start();

// Kiểm tra admin đã đăng nhập chưa
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: admin.php");
    exit();
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$admin_email = $_SESSION['admin_email'] ?? 'N/A';
$login_time = isset($_SESSION['admin_login_time']) ? date('d/m/Y H:i:s', $_SESSION['admin_login_time']) : 'N/A';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điều khiển Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-header .logo {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .sidebar-header h2 {
            font-size: 18px;
            font-weight: 600;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 15px;
        }

        .sidebar-menu a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: block;
            padding: 12px 15px;
            border-radius: 8px;
            transition: 0.3s;
            font-size: 14px;
        }

        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
            font-weight: 600;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .top-navbar {
            background: white;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-title h1 {
            color: #333;
            font-size: 24px;
        }

        .navbar-user {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            text-align: right;
        }

        .user-info p {
            color: #666;
            font-size: 13px;
            margin: 2px 0;
        }

        .user-info strong {
            color: #333;
            font-size: 14px;
        }

        .logout-btn {
            padding: 8px 20px;
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: 0.3s;
        }

        .logout-btn:hover {
            background-color: #c0392b;
        }

        .dashboard-content {
            flex: 1;
            padding: 40px;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            transition: 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .card-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .card p {
            color: #666;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .card-value {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
        }

        .content-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
        }

        .content-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table tr {
            border-bottom: 1px solid #f0f0f0;
        }

        .info-table td {
            padding: 12px 0;
            color: #666;
            font-size: 14px;
        }

        .info-table td:first-child {
            font-weight: 600;
            color: #333;
            width: 200px;
        }

        @media (max-width: 768px) {
            .admin-wrapper {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 20px;
            }

            .dashboard-content {
                padding: 20px;
            }

            .top-navbar {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">👨‍💼</div>
                <h2>Admin Panel</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php" class="active">📊 Dashboard</a></li>
                <li><a href="#users">👥 Quản lý Người dùng</a></li>
                <li><a href="#settings">⚙️ Cài đặt</a></li>
                <li><a href="#reports">📈 Báo cáo</a></li>
                <li><a href="#logs">📝 Nhật ký</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <div class="top-navbar">
                <div class="navbar-title">
                    <h1>Bảng Điều Khiển Quản Trị</h1>
                </div>
                <div class="navbar-user">
                    <div class="user-info">
                        <p>Xin chào,</p>
                        <strong><?php echo htmlspecialchars($admin_username); ?></strong>
                    </div>
                    <a href="logout.php" class="logout-btn">Đăng xuất</a>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Dashboard Cards -->
                <div class="dashboard-cards">
                    <div class="card">
                        <div class="card-icon">👥</div>
                        <h3>Tổng Người Dùng</h3>
                        <p>Số lượng người dùng tài khoản</p>
                        <div class="card-value">1,245</div>
                    </div>

                    <div class="card">
                        <div class="card-icon">📝</div>
                        <h3>Bài Viết</h3>
                        <p>Số bài viết trên hệ thống</p>
                        <div class="card-value">342</div>
                    </div>

                    <div class="card">
                        <div class="card-icon">💬</div>
                        <h3>Bình Luận</h3>
                        <p>Tổng bình luận từ người dùng</p>
                        <div class="card-value">1,563</div>
                    </div>

                    <div class="card">
                        <div class="card-icon">📊</div>
                        <h3>Thống Kê</h3>
                        <p>Lượt truy cập hôm nay</p>
                        <div class="card-value">2,847</div>
                    </div>
                </div>

                <!-- Admin Information -->
                <div class="content-section">
                    <h2>📋 Thông Tin Quản Trị Viên</h2>
                    <table class="info-table">
                        <tr>
                            <td>Tên đăng nhập:</td>
                            <td><?php echo htmlspecialchars($admin_username); ?></td>
                        </tr>
                        <tr>
                            <td>Email:</td>
                            <td><?php echo htmlspecialchars($admin_email); ?></td>
                        </tr>
                        <tr>
                            <td>Quyền hạn:</td>
                            <td><span style="background: #667eea; color: white; padding: 4px 10px; border-radius: 4px; font-weight: 600;">Super Admin</span></td>
                        </tr>
                        <tr>
                            <td>Thời gian đăng nhập:</td>
                            <td><?php echo htmlspecialchars($login_time); ?></td>
                        </tr>
                        <tr>
                            <td>Phiên hoạt động:</td>
                            <td style="color: #27ae60; font-weight: 600;">✓ Đang hoạt động</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>