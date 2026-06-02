<?php
session_start(); require 'config.php'; require_role(['dieuphoI']);
$login_time = isset($_SESSION['login_time']) ? date('d/m/Y H:i',$_SESSION['login_time']) : 'N/A';


$s = [];
foreach (['cho_duyet','dang_xu_ly','dang_lay_hang','dang_van_chuyen','da_giao','hoan_thanh','huy'] as $tt)
    $s[$tt] = $conn->query("SELECT COUNT(*) AS c FROM don_hang WHERE trang_thai='$tt'")->fetch_assoc()['c']??0;

$xe_san_sang = $conn->query("SELECT COUNT(*) AS c FROM xe WHERE tinh_trang='san_sang'")->fetch_assoc()['c']??0;
$xe_chay     = $conn->query("SELECT COUNT(*) AS c FROM xe WHERE tinh_trang='dang_chay'")->fetch_assoc()['c']??0;
$xe_bao_duong= $conn->query("SELECT COUNT(*) AS c FROM xe WHERE tinh_trang='bao_duong'")->fetch_assoc()['c']??0;


$canh_bao_xe = $conn->query("SELECT bien_so,han_dang_kiem,han_bao_hiem FROM xe WHERE (han_dang_kiem<=DATE_ADD(CURDATE(),INTERVAL 30 DAY) OR han_bao_hiem<=DATE_ADD(CURDATE(),INTERVAL 30 DAY)) AND tinh_trang!='nghi'")->fetch_all(MYSQLI_ASSOC);

$uid = $_SESSION['user_id'];
$notif_unread = $conn->query("SELECT COUNT(*) AS c FROM thong_bao WHERE nguoi_nhan_id=$uid AND da_doc=0")->fetch_assoc()['c']??0;


$doanh_thu_thang = $conn->query("SELECT COALESCE(SUM(doanh_thu),0) AS t FROM don_hang WHERE MONTH(ngay_tao)=MONTH(CURDATE()) AND YEAR(ngay_tao)=YEAR(CURDATE()) AND trang_thai!='huy'")->fetch_assoc()['t']??0;

$recent_don = $conn->query(
    "SELECT dh.ma_don,dh.ten_khach,dh.tinh_lay,dh.tinh_giao,dh.trang_thai,dh.ngay_tao,dh.loai_van_chuyen,
            x.bien_so, tx.ho_ten AS ten_tai_xe
     FROM don_hang dh
     LEFT JOIN xe x ON dh.xe_id=x.id
     LEFT JOIN tai_xe tx ON dh.tai_xe_id=tx.id
     ORDER BY dh.ngay_tao DESC LIMIT 7"
);
$recent_xe = $conn->query("SELECT bien_so,nhan_hieu,loai_xe,tinh_trang,tai_trong FROM xe ORDER BY tinh_trang='dang_chay' DESC, tinh_trang='san_sang' DESC LIMIT 6");

$tt_map = [
    'cho_duyet'       =>['l'=>'Chờ duyệt',    'c'=>'b-cho_duyet'],
    'dang_xu_ly'      =>['l'=>'Đang xử lý',   'c'=>'b-dang_xu_ly'],
    'dang_lay_hang'   =>['l'=>'Đang lấy hàng','c'=>'b-dang_lay_rong'],
    'dang_van_chuyen' =>['l'=>'Đang chạy',    'c'=>'b-dang_giao'],
    'da_giao'         =>['l'=>'Đã giao',       'c'=>'b-hoan_thanh'],
    'hoan_thanh'      =>['l'=>'Hoàn thành',   'c'=>'b-hoan_thanh'],
    'da_thanh_toan'   =>['l'=>'Đã TT',         'c'=>'b-da_thanh_toan'],
    'huy'             =>['l'=>'Đã hủy',        'c'=>'b-huy'],
];

$loai_xe_map = [
    'xe_tai_nhe'=>'Xe tải nhẹ','xe_tai_trung'=>'Xe tải trung','xe_tai_nang'=>'Xe tải nặng',
    'dau_keo'=>'Đầu kéo','xe_dong_lanh'=>'Xe đông lạnh','xe_chuyen_dung'=>'Xe chuyên dụng',
];

$active = 'dashboard'; require 'sidebar_dieuphoI.php';
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard — Vận Tải Đường Bộ</title>
<link rel="stylesheet" href="dieuphoI_layout.css">
<style>
.welcome{background:linear-gradient(135deg,#1a3c5e,#2c6fad);border-radius:14px;padding:22px 28px;color:#fff;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between}
.welcome h2{font-size:20px;font-weight:700}
.welcome p{font-size:13px;color:rgba(255,255,255,.75);margin-top:5px}
.welcome .big{font-size:52px;opacity:.8}
.panels{display:grid;grid-template-columns:1.2fr 1fr;gap:20px;margin-bottom:20px}
.panel-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.panel-card h3{font-size:14px;font-weight:700;color:var(--green);margin-bottom:14px;padding-bottom:10px;border-bottom:2px solid var(--green-light);display:flex;align-items:center;justify-content:space-between}
.panel-card h3 a{font-size:12px;text-decoration:none;color:var(--green);font-weight:500}
.row-item{display:flex;align-items:center;justify-content:space-between;padding:9px 0;border-bottom:1px solid #f4f6f8;font-size:13px}
.row-item:last-child{border-bottom:none}
.ri-main{font-weight:600;color:var(--text)}
.ri-sub{font-size:11px;color:var(--muted);margin-top:2px}
.xe-row{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid #f4f6f8;font-size:13px}
.xe-row:last-child{border-bottom:none}
.warn-box{background:#fff8e1;border:1px solid #ffe082;border-radius:10px;padding:14px 18px;margin-bottom:20px}
.warn-box h4{font-size:13px;font-weight:700;color:#f57f17;margin-bottom:10px}
.warn-item{font-size:12px;color:#795548;padding:4px 0;border-bottom:1px solid #fff3cd}
.warn-item:last-child{border-bottom:none}
@media(max-width:900px){.panels{grid-template-columns:1fr}}
</style>
</head><body>
<div class="wrapper">

<main class="main">
<div class="topbar">
    <div class="topbar-left">
        <div class="topbar-title">📊 Dashboard — Vận Tải Đường Bộ</div>
        <div style="font-size:12px;color:var(--muted)">Đăng nhập lúc <?= $login_time ?></div>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
        <?php if($notif_unread>0): ?>
        <a href="dieuphoI_thong_bao.php" style="position:relative;text-decoration:none;font-size:22px">
            🔔<span style="position:absolute;top:-4px;right:-6px;background:#e74c3c;color:#fff;font-size:10px;font-weight:700;padding:1px 5px;border-radius:10px"><?= $notif_unread ?></span>
        </a>
        <?php endif; ?>
        <div class="user-chip">
            <div class="chip-avatar"><?= mb_strtoupper(mb_substr($full_name,0,1)) ?></div>
            <div><div class="chip-name"><?= htmlspecialchars($full_name) ?></div>
            <div class="chip-role">Điều Phối Viên</div></div>
        </div>
    </div>
</div>

<div class="content">

    <div class="welcome">
        <div>
            <h2>Xin chào, <?= htmlspecialchars($full_name) ?>! 🚛</h2>
            <p>Hệ thống vận tải đường bộ đang hoạt động. Chúc bạn điều phối hiệu quả!</p>
        </div>
        <div class="big">🛣️</div>
    </div>

    <?php if (!empty($canh_bao_xe)): ?>
    <div class="warn-box">
        <h4>⚠️ Cảnh báo — <?= count($canh_bao_xe) ?> xe sắp hết hạn đăng kiểm / bảo hiểm</h4>
        <?php foreach($canh_bao_xe as $w): ?>
        <div class="warn-item">
            🚛 <strong><?= htmlspecialchars($w['bien_so']) ?></strong>
            — Đăng kiểm: <?= $w['han_dang_kiem']??'—' ?>
            &nbsp;|&nbsp; Bảo hiểm: <?= $w['han_bao_hiem']??'—' ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="stat-cards" style="margin-bottom:24px">
        <div class="stat-card" style="border-top-color:#f39c12">
            <div class="sc-icon">🟡</div><div class="sc-label">Chờ duyệt</div>
            <div class="sc-value"><?= $s['cho_duyet'] ?></div><div class="sc-sub">Đơn hàng</div>
        </div>
        <div class="stat-card" style="border-top-color:#e67e22">
            <div class="sc-icon">📦</div><div class="sc-label">Đang lấy hàng</div>
            <div class="sc-value"><?= $s['dang_lay_hang']+$s['dang_xu_ly'] ?></div><div class="sc-sub">Đơn hàng</div>
        </div>
        <div class="stat-card" style="border-top-color:#27ae60">
            <div class="sc-icon">🚛</div><div class="sc-label">Đang vận chuyển</div>
            <div class="sc-value"><?= $s['dang_van_chuyen'] ?></div><div class="sc-sub">Trên đường</div>
        </div>
        <div class="stat-card" style="border-top-color:#2980b9">
            <div class="sc-icon">✅</div><div class="sc-label">Đã giao hôm nay</div>
            <div class="sc-value"><?= $s['da_giao'] ?></div><div class="sc-sub">Đơn hàng</div>
        </div>
        <div class="stat-card">
            <div class="sc-icon">🟢</div><div class="sc-label">Xe sẵn sàng</div>
            <div class="sc-value"><?= $xe_san_sang ?></div><div class="sc-sub">Phương tiện</div>
        </div>
        <div class="stat-card" style="border-top-color:#8e44ad">
            <div class="sc-icon">💰</div><div class="sc-label">Doanh thu tháng</div>
            <div class="sc-value" style="font-size:17px">₫<?= number_format($doanh_thu_thang/1000000,1) ?>tr</div>
            <div class="sc-sub">Tháng <?= date('m/Y') ?></div>
        </div>
    </div>

    <div class="panels">
       
        <div class="panel-card">
            <h3>📋 Đơn Hàng Gần Đây <a href="dieuphoI_don_hang.php">Xem tất cả →</a></h3>
            <?php if($recent_don && $recent_don->num_rows>0):
                while($d=$recent_don->fetch_assoc()): ?>
            <div class="row-item">
                <div style="min-width:0;flex:1">
                    <div class="ri-main"><?= htmlspecialchars($d['ma_don']) ?> — <?= htmlspecialchars($d['ten_khach']) ?></div>
                    <div class="ri-sub">
                        📍 <?= htmlspecialchars($d['tinh_lay']??'—') ?> → 🏁 <?= htmlspecialchars($d['tinh_giao']??'—') ?>
                        &nbsp;·&nbsp; 🚛 <?= htmlspecialchars($d['bien_so']??'Chưa phân') ?>
                    </div>
                </div>
                <span class="badge <?= $tt_map[$d['trang_thai']]['c']??'' ?>" style="flex-shrink:0;margin-left:8px">
                    <?= $tt_map[$d['trang_thai']]['l']??$d['trang_thai'] ?>
                </span>
            </div>
            <?php endwhile; else: ?>
                <div class="empty-state" style="padding:30px"><div class="ei">📋</div><p>Chưa có đơn hàng.</p></div>
            <?php endif; ?>
            <div style="text-align:center;margin-top:14px">
                <a href="dieuphoI_tao_don.php" class="btn btn-primary btn-sm">➕ Tạo đơn mới</a>
            </div>
        </div>

        <div class="panel-card">
            <h3>🚛 Phương Tiện <a href="dieuphoI_phan_xe.php">Phân xe →</a></h3>
            <?php while($x=$recent_xe->fetch_assoc()): ?>
            <div class="xe-row">
                <div style="font-size:20px">
                    <?= $x['loai_xe']==='xe_dong_lanh'?'❄️':'🚛' ?>
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-weight:700;font-size:13px"><?= htmlspecialchars($x['bien_so']) ?></div>
                    <div style="font-size:11px;color:var(--muted)">
                        <?= htmlspecialchars($loai_xe_map[$x['loai_xe']]??$x['loai_xe']) ?>
                        · <?= $x['tai_trong'] ?>T
                        · <?= htmlspecialchars($x['nhan_hieu']??'') ?>
                    </div>
                </div>
                <span class="badge b-<?= $x['tinh_trang'] ?>"><?= $x['tinh_trang'] ?></span>
            </div>
            <?php endwhile; ?>
            <div style="text-align:center;margin-top:14px">
                <a href="dieuphoI_phan_xe.php" class="btn btn-ghost btn-sm">Xem tất cả xe</a>
            </div>
        </div>
    </div>

</div>
</main>
</div>
</body></html>
