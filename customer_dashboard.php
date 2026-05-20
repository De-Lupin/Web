<?php

session_start();
require 'config.php';

require_role(['khachhang']);

$full_name  = $_SESSION['full_name'] ?? 'Khách hàng';
$username   = $_SESSION['username']  ?? '';
$email      = $_SESSION['email']     ?? '';
$login_time = isset($_SESSION['login_time']) ? date('d/m/Y H:i', $_SESSION['login_time']) : 'N/A';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Khách Hàng</title>
    <link rel="stylesheet" href="style.css">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
            background:#f5f0fb;
        }

        
        .wrapper { display:flex; min-height:100vh; }

        
        .sidebar {
            width:255px; flex-shrink:0;
            background:linear-gradient(180deg,#6c3483 0%,#512e5f 100%);
            color:#fff;
            display:flex; flex-direction:column;
            position:fixed; top:0; left:0; bottom:0;
            overflow-y:auto;
        }
        .sidebar-head {
            padding:28px 22px 20px;
            border-bottom:1px solid rgba(255,255,255,.15);
        }
        .sidebar-head .logo { font-size:36px; }
        .sidebar-head h2    { font-size:16px; font-weight:700; margin-top:8px; line-height:1.35; }
        .sidebar-head p     { font-size:12px; color:rgba(255,255,255,.5); margin-top:4px; }

        .role-pill {
            margin:16px 16px 0;
            background:rgba(255,255,255,.12);
            border:1px solid rgba(255,255,255,.2);
            border-radius:8px; padding:9px 14px;
            font-size:13px; font-weight:700;
            display:flex; align-items:center; gap:8px;
        }

        .nav-section { padding:18px 0 0; flex:1; }
        .nav-label {
            font-size:10px; font-weight:700; letter-spacing:1px;
            text-transform:uppercase; color:rgba(255,255,255,.4);
            padding:10px 22px 5px;
        }
        .nav-item {
            display:flex; align-items:center; gap:11px;
            padding:11px 22px; font-size:13px; font-weight:500;
            color:rgba(255,255,255,.7); text-decoration:none;
            transition:.2s; border-left:3px solid transparent;
        }
        .nav-item:hover, .nav-item.active {
            background:rgba(255,255,255,.1);
            color:#fff; border-left-color:#c39bd3;
        }
        .nav-item .ni { font-size:17px; width:22px; text-align:center; }

        .sidebar-foot {
            padding:16px; border-top:1px solid rgba(255,255,255,.12);
        }
        .btn-logout {
            display:flex; align-items:center; justify-content:center; gap:8px;
            width:100%; padding:10px 14px;
            background:rgba(220,53,69,.15); border:1px solid rgba(220,53,69,.35);
            border-radius:8px; color:#ff7f8a;
            font-size:13px; font-weight:600; font-family:inherit;
            cursor:pointer; text-decoration:none; transition:.2s;
        }
        .btn-logout:hover { background:rgba(220,53,69,.3); }

    
        .main { margin-left:255px; flex:1; display:flex; flex-direction:column; }

   
        .topbar {
            background:#fff; padding:15px 32px;
            display:flex; align-items:center; justify-content:space-between;
            box-shadow:0 1px 4px rgba(0,0,0,.08);
            position:sticky; top:0; z-index:50;
        }
        .topbar-title { font-size:18px; font-weight:700; color:#6c3483; }
        .user-chip {
            display:flex; align-items:center; gap:10px;
            background:#f5eef8; border-radius:10px; padding:8px 14px;
        }
        .avatar {
            width:36px; height:36px; border-radius:50%;
            background:#6c3483; color:#fff;
            display:flex; align-items:center; justify-content:center;
            font-size:15px; font-weight:700;
        }
        .chip-name { font-size:13px; font-weight:700; color:#6c3483; }
        .chip-role { font-size:11px; color:#a569bd; font-weight:600; }

  
        .content { padding:30px 32px; flex:1; }

      
        .welcome {
            background:linear-gradient(135deg,#6c3483,#9b59b6);
            border-radius:14px; padding:24px 28px;
            color:#fff; margin-bottom:26px;
            display:flex; align-items:center; justify-content:space-between;
        }
        .welcome h2 { font-size:20px; font-weight:700; }
        .welcome p  { font-size:13px; color:rgba(255,255,255,.75); margin-top:5px; }
        .welcome .big { font-size:52px; opacity:.85; }

       
        .cards {
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(190px,1fr));
            gap:16px; margin-bottom:26px;
        }
        .card {
            background:#fff; border-radius:12px; padding:22px;
            box-shadow:0 2px 8px rgba(0,0,0,.06); transition:.2s;
            border-top:4px solid transparent;
        }
        .card:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,.1); }
        .card.c1 { border-top-color:#9b59b6; }
        .card.c2 { border-top-color:#27ae60; }
        .card.c3 { border-top-color:#e67e22; }
        .card.c4 { border-top-color:#e74c3c; }
        .card .icon  { font-size:28px; margin-bottom:10px; }
        .card .label { font-size:12px; font-weight:700; color:#7f8c8d; text-transform:uppercase; letter-spacing:.5px; }
        .card .value { font-size:30px; font-weight:800; color:#2c3e50; margin:6px 0 3px; }
        .card .sub   { font-size:12px; color:#95a5a6; }

          .panels { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .panel {
            background:#fff; border-radius:12px; padding:22px;
            box-shadow:0 2px 8px rgba(0,0,0,.06);
        }
        .panel h3 {
            font-size:15px; font-weight:700; color:#6c3483;
            margin-bottom:16px; padding-bottom:10px;
            border-bottom:2px solid #f5eef8;
        }

        
        .track-item {
            display:flex; align-items:flex-start; gap:14px;
            padding:12px 0; border-bottom:1px solid #f8f9fa;
        }
        .track-item:last-child { border-bottom:none; }
        .track-dot {
            width:36px; height:36px; border-radius:50%; flex-shrink:0;
            display:flex; align-items:center; justify-content:center;
            font-size:16px;
        }
        .td-purple { background:#f5eef8; }
        .td-green  { background:#eafaf1; }
        .td-orange { background:#fef9e7; }
        .track-id   { font-weight:700; font-size:13px; color:#2c3e50; }
        .track-info { font-size:12px; color:#7f8c8d; margin-top:3px; }
        .track-status { margin-left:auto; flex-shrink:0; }
        .sb {
            padding:3px 10px; border-radius:20px;
            font-size:11px; font-weight:700;
        }
        .sb-danggiao { background:#d5f5e3; color:#1e8449; }
        .sb-dangxuly { background:#fef9e7; color:#b7770d; }
        .sb-hoan     { background:#eaf4ff; color:#1a5276; }

     
        .cono-item {
            display:flex; justify-content:space-between; align-items:center;
            padding:11px 0; border-bottom:1px solid #f8f9fa; font-size:13px;
        }
        .cono-item:last-child { border-bottom:none; }
        .cono-code   { font-weight:700; color:#2c3e50; }
        .cono-date   { font-size:12px; color:#7f8c8d; margin-top:2px; }
        .cono-amount { font-weight:800; color:#6c3483; font-size:14px; }
        .cono-due    { font-size:11px; color:#e74c3c; margin-top:2px; text-align:right; }

        
        .info-box {
            background:#fff; border-radius:12px; padding:22px;
            box-shadow:0 2px 8px rgba(0,0,0,.06); margin-top:20px;
        }
        .info-box h3 {
            font-size:15px; font-weight:700; color:#6c3483;
            margin-bottom:16px; padding-bottom:10px;
            border-bottom:2px solid #f5eef8;
        }
        .info-row { display:flex; padding:10px 0; border-bottom:1px solid #f8f9fa; font-size:13px; }
        .info-row:last-child { border-bottom:none; }
        .info-lbl { width:160px; color:#7f8c8d; font-weight:600; flex-shrink:0; }
        .info-val { color:#2c3e50; font-weight:500; }

        @media(max-width:900px){
            .sidebar { position:static; width:100%; }
            .main    { margin-left:0; }
            .panels  { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<div class="wrapper">

    
    <aside class="sidebar">
        <div class="sidebar-head">
            <div class="logo">🚛</div>
            <h2>Vận Tải<br>Hàng Hóa</h2>
            <p>Cổng khách hàng</p>
        </div>
        <div class="role-pill">👤 Khách Hàng</div>

        <nav class="nav-section">
            <div class="nav-label">Chức năng</div>
            <a href="customer_dashboard.php" class="nav-item active">
                <span class="ni">📊</span> Tổng quan
            </a>
            <a href="#" class="nav-item">
                <span class="ni">➕</span> Tạo đơn hàng mới
            </a>
            <a href="#" class="nav-item">
                <span class="ni">📋</span> Đơn hàng của tôi
            </a>
            <a href="#" class="nav-item">
                <span class="ni">🗺️</span> Theo dõi hàng hóa
            </a>
            <a href="#" class="nav-item">
                <span class="ni">💰</span> Công nợ của tôi
            </a>
            <a href="#" class="nav-item">
                <span class="ni">🔔</span> Thông báo
                <span style="margin-left:auto;background:#e74c3c;color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px">2</span>
            </a>
            <a href="#" class="nav-item">
                <span class="ni">👤</span> Tài khoản của tôi
            </a>
        </nav>

        <div class="sidebar-foot">
            <a href="logout.php" class="btn-logout">🚪 Đăng Xuất</a>
        </div>
    </aside>

   
    <main class="main">
        
        <div class="topbar">
            <div class="topbar-title">📊 Tổng Quan — Khách Hàng</div>
            <div class="user-chip">
                <div class="avatar"><?= mb_strtoupper(mb_substr($full_name, 0, 1)) ?></div>
                <div>
                    <div class="chip-name"><?= htmlspecialchars($full_name) ?></div>
                    <div class="chip-role">👤 Khách Hàng</div>
                </div>
            </div>
        </div>

        <div class="content">

            <div class="welcome">
                <div>
                    <h2>Xin chào, <?= htmlspecialchars($full_name) ?>! 👋</h2>
                    <p>Đăng nhập lúc <?= $login_time ?> — Theo dõi đơn hàng của bạn tại đây.</p>
                </div>
                <div class="big">👤</div>
            </div>

            <div class="cards">
                <div class="card c1">
                    <div class="icon">📦</div>
                    <div class="label">Đang vận chuyển</div>
                    <div class="value">3</div>
                    <div class="sub">Đơn đang xử lý</div>
                </div>
                <div class="card c2">
                    <div class="icon">✅</div>
                    <div class="label">Đã hoàn thành</div>
                    <div class="value">28</div>
                    <div class="sub">Tổng đơn hàng</div>
                </div>
                <div class="card c3">
                    <div class="icon">💰</div>
                    <div class="label">Cần thanh toán</div>
                    <div class="value">₫12 tr</div>
                    <div class="sub">Công nợ hiện tại</div>
                </div>
                <div class="card c4">
                    <div class="icon">🔔</div>
                    <div class="label">Thông báo mới</div>
                    <div class="value">2</div>
                    <div class="sub">Chưa đọc</div>
                </div>
            </div>

            <div class="panels">

                <div class="panel">
                    <h3>🗺️ Theo Dõi Hàng Hóa</h3>

                    <div class="track-item">
                        <div class="track-dot td-green">🚛</div>
                        <div style="flex:1">
                            <div class="track-id">#DH-2024-031</div>
                            <div class="track-info">20ft Container — HCM → Hà Nội<br>Xe: 51C-123.45 — Tài xế: Nguyễn Văn A</div>
                        </div>
                        <div class="track-status">
                            <span class="sb sb-danggiao">Đang giao</span>
                        </div>
                    </div>

                    <div class="track-item">
                        <div class="track-dot td-orange">⏳</div>
                        <div style="flex:1">
                            <div class="track-id">#DH-2024-032</div>
                            <div class="track-info">Hàng lẻ 3 tấn — HCM → Đà Nẵng<br>Dự kiến lấy hàng: 20/12/2024</div>
                        </div>
                        <div class="track-status">
                            <span class="sb sb-dangxuly">Đang xử lý</span>
                        </div>
                    </div>

                    <div class="track-item">
                        <div class="track-dot td-purple">📦</div>
                        <div style="flex:1">
                            <div class="track-id">#DH-2024-033</div>
                            <div class="track-info">40ft Container — HCM → Cần Thơ<br>Đã giao thành công ngày 18/12/2024</div>
                        </div>
                        <div class="track-status">
                            <span class="sb sb-hoan">Hoàn thành</span>
                        </div>
                    </div>

                    <div style="text-align:right;margin-top:14px">
                        <a href="#" style="font-size:13px;color:#6c3483;font-weight:600;text-decoration:none">
                            Xem tất cả đơn →
                        </a>
                    </div>
                </div>

                <div class="panel">
                    <h3>💰 Công Nợ Của Tôi</h3>

                    <div class="cono-item">
                        <div>
                            <div class="cono-code">#DH-2024-028</div>
                            <div class="cono-date">Ngày lập: 10/12/2024</div>
                        </div>
                        <div style="text-align:right">
                            <div class="cono-amount">₫ 4,500,000</div>
                            <div class="cono-due">⚠️ Hạn: 25/12/2024</div>
                        </div>
                    </div>

                    <div class="cono-item">
                        <div>
                            <div class="cono-code">#DH-2024-029</div>
                            <div class="cono-date">Ngày lập: 12/12/2024</div>
                        </div>
                        <div style="text-align:right">
                            <div class="cono-amount">₫ 7,800,000</div>
                            <div class="cono-due">⚠️ Hạn: 28/12/2024</div>
                        </div>
                    </div>

                    <div style="
                        margin-top:16px; padding:12px 16px;
                        background:#f9f0ff; border-radius:8px;
                        border-left:4px solid #9b59b6;
                        display:flex; justify-content:space-between; align-items:center;
                    ">
                        <span style="font-size:13px;font-weight:700;color:#6c3483">Tổng cần thanh toán</span>
                        <span style="font-size:18px;font-weight:800;color:#6c3483">₫ 12,300,000</span>
                    </div>

                    <div style="text-align:right;margin-top:14px">
                        <a href="#" style="font-size:13px;color:#6c3483;font-weight:600;text-decoration:none">
                            Xem chi tiết công nợ →
                        </a>
                    </div>
                </div>

            </div>

           
            <div class="info-box">
                <h3>👤 Thông Tin Tài Khoản</h3>
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
                        <span style="background:#f5eef8;color:#6c3483;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700">
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
                    <span class="info-val" style="color:#27ae60;font-weight:700">✅ Đang hoạt động</span>
                </div>
            </div>

        </div>
    </main>

</div>
</body>
</html>
