<?php
session_start(); require 'config.php'; require_role(['admin']);

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['cap_nhat_tt'])) {
    $id = (int)$_POST['don_id'];
    $tt = $_POST['trang_thai'];
    $conn->query("UPDATE don_hang SET trang_thai='$tt' WHERE id=$id");
    $msg=['type'=>'success','text'=>'Đã cập nhật trạng thái!'];
}

$msg = $msg ?? null;

$stats = [];
foreach(['cho_duyet','dang_xu_ly','dang_lay_hang','dang_van_chuyen','da_giao','hoan_thanh','da_thanh_toan','huy'] as $tt)
    $stats[$tt] = $conn->query("SELECT COUNT(*) AS c FROM don_hang WHERE trang_thai='$tt'")->fetch_assoc()['c']??0;

$doanh_thu_thang = $conn->query("SELECT COALESCE(SUM(doanh_thu),0) AS t FROM don_hang WHERE MONTH(ngay_tao)=MONTH(CURDATE()) AND YEAR(ngay_tao)=YEAR(CURDATE()) AND trang_thai!='huy'")->fetch_assoc()['t']??0;
$loi_nhuan_thang = $conn->query("SELECT COALESCE(SUM(loi_nhuan),0) AS t FROM don_hang WHERE MONTH(ngay_tao)=MONTH(CURDATE()) AND trang_thai NOT IN ('huy','cho_duyet')")->fetch_assoc()['t']??0;

$search = trim($_GET['search']??'');
$tt_f   = $_GET['trang_thai']??'';
$page   = max(1,(int)($_GET['page']??1));
$per    = 12; $offset=($page-1)*$per;

$w = "WHERE 1=1";
if ($search) $w .= " AND (dh.ma_don LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR dh.ten_khach LIKE '%".mysqli_real_escape_string($conn,$search)."%')";
if ($tt_f)   $w .= " AND dh.trang_thai='".mysqli_real_escape_string($conn,$tt_f)."'";

$total = $conn->query("SELECT COUNT(*) AS c FROM don_hang dh $w")->fetch_assoc()['c'];
$pages = max(1,ceil($total/$per));
$rows  = $conn->query("SELECT dh.*,x.bien_so,tx.ho_ten AS ten_tai_xe,u.username AS nguoi_tao FROM don_hang dh LEFT JOIN xe x ON dh.xe_id=x.id LEFT JOIN tai_xe tx ON dh.tai_xe_id=tx.id LEFT JOIN users u ON dh.nguoi_tao_id=u.id $w ORDER BY dh.ngay_tao DESC LIMIT $per OFFSET $offset");

$tt_map=['cho_duyet'=>['l'=>'Chờ duyệt','c'=>'b-cho_duyet'],'dang_xu_ly'=>['l'=>'Đang xử lý','c'=>'b-dang_xu_ly'],'dang_lay_hang'=>['l'=>'Lấy hàng','c'=>'b-dang_lay_rong'],'dang_van_chuyen'=>['l'=>'Đang chạy','c'=>'b-dang_giao'],'da_giao'=>['l'=>'Đã giao','c'=>'b-hoan_thanh'],'hoan_thanh'=>['l'=>'Hoàn thành','c'=>'b-hoan_thanh'],'da_thanh_toan'=>['l'=>'Đã TT','c'=>'b-da_thanh_toan'],'huy'=>['l'=>'Đã hủy','c'=>'b-huy']];

$active='don_hang'; require 'sidebar_admin.php';
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Đơn Hàng — Admin</title>
<link rel="stylesheet" href="admin_layout.css">
<style>
.b-cho_duyet{background:#fef9e7;color:#b7770d}.b-dang_xu_ly{background:#eaf4fb;color:#1a5276}
.b-dang_lay_rong{background:#f4ecf7;color:#6c3483}.b-dang_giao{background:#eafaf1;color:#1e8449}
.b-hoan_thanh{background:#d5f5e3;color:#1e8449}.b-da_thanh_toan{background:#d6eaf8;color:#1a5276}
.b-huy{background:#fdf2f0;color:#c0392b}
</style>
</head><body>
<div class="app">
<?php require 'sidebar_admin.php'; ?>
<main class="main">
<div class="topbar">
    <div><div class="topbar-title">📦 Đơn Hàng — Toàn Hệ Thống</div>
    <div class="breadcrumb"><a href="admin_dashboard.php">Dashboard</a> › Đơn hàng</div></div>
    <div class="user-chip">
        <div class="chip-avatar"><?=mb_strtoupper(mb_substr($_SESSION['full_name']??'A',0,1))?></div>
        <div><div class="chip-name"><?=htmlspecialchars($_SESSION['full_name']??'Admin')?></div><div class="chip-role">Super Admin</div></div>
    </div>
</div>
<div class="content">
    <?php if(!empty($msg))echo"<div class='alert alert-{$msg['type']}'>{$msg['text']}</div>"; ?>

    <div class="stat-cards" style="margin-bottom:20px">
        <div class="stat-card"><div class="sc-icon">📦</div><div class="sc-label">Chờ duyệt</div><div class="sc-value" style="color:#f39c12"><?=$stats['cho_duyet']?></div></div>
        <div class="stat-card" style="border-top-color:#2980b9"><div class="sc-icon">🚛</div><div class="sc-label">Đang vận chuyển</div><div class="sc-value" style="color:#2980b9"><?=$stats['dang_van_chuyen']?></div></div>
        <div class="stat-card" style="border-top-color:#27ae60"><div class="sc-icon">✅</div><div class="sc-label">Hoàn thành</div><div class="sc-value" style="color:#27ae60"><?=$stats['hoan_thanh']+$stats['da_thanh_toan']?></div></div>
        <div class="stat-card" style="border-top-color:#e74c3c"><div class="sc-icon">❌</div><div class="sc-label">Đã hủy</div><div class="sc-value" style="color:#e74c3c"><?=$stats['huy']?></div></div>
        <div class="stat-card" style="border-top-color:#8e44ad"><div class="sc-icon">💰</div><div class="sc-label">Doanh thu tháng</div><div class="sc-value" style="color:#8e44ad;font-size:17px">₫<?=number_format($doanh_thu_thang/1000000,1)?>tr</div></div>
        <div class="stat-card" style="border-top-color:#27ae60"><div class="sc-icon">📈</div><div class="sc-label">Lợi nhuận tháng</div><div class="sc-value" style="color:#27ae60;font-size:17px">₫<?=number_format($loi_nhuan_thang/1000000,1)?>tr</div></div>
    </div>

    <div class="page-header">
        <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;flex:1">
            <div class="search-box" style="flex:1;min-width:200px">🔍<input type="text" name="search" placeholder="Tìm mã đơn, khách hàng..." value="<?=htmlspecialchars($search)?>"></div>
            <select name="trang_thai" onchange="this.form.submit()" style="padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px">
                <option value="">— Tất cả trạng thái —</option>
                <?php foreach($tt_map as $k=>$v): ?><option value="<?=$k?>" <?=$tt_f===$k?'selected':''?>><?=$v['l']?></option><?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Lọc</button>
            <a href="admin_don_hang.php" class="btn btn-ghost">Xóa lọc</a>
        </form>
    </div>

    <div class="table-wrap">
        <div class="table-scroll">
        <table>
            <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Tuyến đường</th><th>Xe / Tài xế</th><th>Doanh thu</th><th>Lợi nhuận</th><th>Ngày tạo</th><th>Người tạo</th><th>Trạng thái</th><th>Đổi TT</th></tr></thead>
            <tbody>
            <?php if($rows && $rows->num_rows>0): while($r=$rows->fetch_assoc()): ?>
            <tr>
                <td><strong><?=htmlspecialchars($r['ma_don'])?></strong></td>
                <td><div style="font-weight:600"><?=htmlspecialchars($r['ten_khach'])?></div><div style="font-size:11px;color:var(--muted)"><?=htmlspecialchars($r['dien_thoai_kh']??'')?></div></td>
                <td style="font-size:12px">📍 <?=htmlspecialchars($r['tinh_lay']??'—')?><br>🏁 <?=htmlspecialchars($r['tinh_giao']??'—')?></td>
                <td style="font-size:12px"><div>🚛 <?=htmlspecialchars($r['bien_so']??'—')?></div><div style="color:var(--muted)">👤 <?=htmlspecialchars($r['ten_tai_xe']??'—')?></div></td>
                <td style="font-weight:700;color:var(--primary)">₫<?=number_format($r['doanh_thu'])?></td>
                <td style="font-weight:700;color:<?=$r['loi_nhuan']>=0?'#1e8449':'#e74c3c'?>">₫<?=number_format($r['loi_nhuan'])?></td>
                <td style="font-size:12px;color:var(--muted)"><?=date('d/m/Y',strtotime($r['ngay_tao']))?></td>
                <td style="font-size:12px"><?=htmlspecialchars($r['nguoi_tao']??'—')?></td>
                <td><span class="badge <?=$tt_map[$r['trang_thai']]['c']??''?>"><?=$tt_map[$r['trang_thai']]['l']??$r['trang_thai']?></span></td>
                <td>
                    <form method="POST" style="display:flex;gap:4px">
                        <input type="hidden" name="don_id" value="<?=$r['id']?>">
                        <select name="trang_thai" style="font-size:11px;padding:3px 6px;border:1px solid var(--border);border-radius:5px">
                            <?php foreach($tt_map as $k=>$v): ?><option value="<?=$k?>" <?=$r['trang_thai']===$k?'selected':''?>><?=$v['l']?></option><?php endforeach; ?>
                        </select>
                        <button type="submit" name="cap_nhat_tt" class="btn btn-primary btn-sm">✓</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="10"><div class="empty-state"><div class="ei">📦</div><p>Không có đơn hàng nào.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
        <?php if($pages>1): ?><div class="pagination"><?php for($i=1;$i<=$pages;$i++): ?><a href="?page=<?=$i?>&search=<?=urlencode($search)?>&trang_thai=<?=urlencode($tt_f)?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a><?php endfor; ?></div><?php endif; ?>
    </div>
</div>
</main>
</div>
</body></html>