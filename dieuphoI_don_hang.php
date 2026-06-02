<?php
session_start(); require 'config.php'; require_role(['dieuphoI']);

$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['huy_don'])) {
    $id  = (int)$_POST['don_id'];
    $ly_do = trim($_POST['ly_do'] ?? 'Điều phối hủy');
    $r   = $conn->query("SELECT ngay_tao,trang_thai FROM don_hang WHERE id=$id");
    $d   = $r->fetch_assoc();
    if ($d && $d['trang_thai']!=='huy' && (time()-strtotime($d['ngay_tao']))<86400) {
        $conn->query("UPDATE don_hang SET trang_thai='huy',ly_do_huy='".mysqli_real_escape_string($conn,$ly_do)."' WHERE id=$id");
        $msg = ['type'=>'success','text'=>'Đã hủy đơn hàng thành công!'];
    } else {
        $msg = ['type'=>'danger','text'=>'Không thể hủy — đã quá 24h hoặc đơn đã hủy!'];
    }
}

$search    = trim($_GET['search']    ?? '');
$trang_thai= $_GET['trang_thai']     ?? '';
$tinh      = $_GET['tinh']           ?? '';
$loai_vc   = $_GET['loai_van_chuyen']?? '';
$page      = max(1,(int)($_GET['page']??1));
$per_page  = 12;
$offset    = ($page-1)*$per_page;

$where = "WHERE 1=1";
if ($search)    $where .= " AND (dh.ma_don LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR dh.ten_khach LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR dh.tinh_giao LIKE '%".mysqli_real_escape_string($conn,$search)."%')";
if ($trang_thai)$where .= " AND dh.trang_thai='".mysqli_real_escape_string($conn,$trang_thai)."'";
if ($tinh)      $where .= " AND (dh.tinh_giao LIKE '%".mysqli_real_escape_string($conn,$tinh)."%' OR dh.tinh_lay LIKE '%".mysqli_real_escape_string($conn,$tinh)."%')";
if ($loai_vc)   $where .= " AND dh.loai_van_chuyen='".mysqli_real_escape_string($conn,$loai_vc)."'";

$total = $conn->query("SELECT COUNT(*) AS c FROM don_hang dh $where")->fetch_assoc()['c'];
$pages = max(1,ceil($total/$per_page));

$rows = $conn->query(
    "SELECT dh.*,x.bien_so,x.loai_xe,tx.ho_ten AS ten_tai_xe,td.ten_tuyen,td.khoang_cach
     FROM don_hang dh
     LEFT JOIN xe x ON dh.xe_id=x.id
     LEFT JOIN tai_xe tx ON dh.tai_xe_id=tx.id
     LEFT JOIN tuyen_duong td ON dh.tuyen_duong_id=td.id
     $where ORDER BY dh.ngay_tao DESC LIMIT $per_page OFFSET $offset"
);

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
$loai_vc_map = [
    'hang_le'          =>'Hàng lẻ',
    'hang_nguyen_xe'   =>'Nguyên xe',
    'hang_dong_lanh'   =>'Đông lạnh',
    'hang_qua_kho'     =>'Qua kho',
    'hang_sieu_truong' =>'Siêu trường',
];

$active = 'don_hang'; require 'sidebar_dieuphoI.php';
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Danh Sách Đơn Hàng</title>
<link rel="stylesheet" href="dieuphoI_layout.css">
<style>
.filter-bar{display:flex;gap:8px;flex-wrap:wrap;flex:1}
.filter-bar select,.filter-bar input{padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none;background:#fdfdfd}
.filter-bar select:focus,.filter-bar input:focus{border-color:var(--green)}
.route-cell{font-size:12px;line-height:1.6}
.modal-huy .form-grid{grid-template-columns:1fr}
</style>
</head><body>
<div class="wrapper">

<main class="main">
<div class="topbar">
    <div class="topbar-left">
        <div class="topbar-title">📋 Danh Sách Đơn Hàng</div>
        <div style="font-size:12px;color:var(--muted)">Tổng: <?= number_format($total) ?> đơn</div>
    </div>
    <div class="user-chip">
        <div class="chip-avatar"><?= mb_strtoupper(mb_substr($full_name,0,1)) ?></div>
        <div><div class="chip-name"><?= htmlspecialchars($full_name) ?></div>
        <div class="chip-role">Điều Phối Viên</div></div>
    </div>
</div>

<div class="content">
    <?php if(!empty($msg)): ?>
        <div class="alert alert-<?= $msg['type'] ?>"><?= $msg['text'] ?></div>
    <?php endif; ?>

    <div class="page-header">
        <form method="GET" class="filter-bar">
            <div class="search-box" style="flex:1;min-width:200px">
                🔍<input type="text" name="search" placeholder="Tìm mã đơn, khách hàng, tỉnh..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="trang_thai" onchange="this.form.submit()">
                <option value="">— Tất cả trạng thái —</option>
                <?php foreach($tt_map as $k=>$v): ?>
                <option value="<?= $k ?>" <?= $trang_thai===$k?'selected':'' ?>><?= $v['l'] ?></option>
                <?php endforeach; ?>
            </select>
            <select name="loai_van_chuyen" onchange="this.form.submit()">
                <option value="">— Loại vận chuyển —</option>
                <?php foreach($loai_vc_map as $k=>$v): ?>
                <option value="<?= $k ?>" <?= $loai_vc===$k?'selected':'' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="tinh" placeholder="Lọc theo tỉnh/TP..." value="<?= htmlspecialchars($tinh) ?>">
            <button type="submit" class="btn btn-primary">Lọc</button>
            <a href="dieuphoI_don_hang.php" class="btn btn-ghost">Xóa lọc</a>
        </form>
        <a href="dieuphoI_tao_don.php" class="btn btn-primary">➕ Tạo Đơn Mới</a>
    </div>

    <div class="table-wrap">
        <div class="table-scroll">
        <table>
            <thead><tr>
                <th>Mã đơn</th>
                <th>Khách hàng</th>
                <th>Tuyến đường</th>
                <th>Loại VC</th>
                <th>Xe / Tài xế</th>
                <th>KM</th>
                <th>Doanh thu</th>
                <th>Lợi nhuận</th>
                <th>Ngày giao DK</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr></thead>
            <tbody>
            <?php if($rows && $rows->num_rows>0):
                while($row=$rows->fetch_assoc()):
                $can_huy = $row['trang_thai']!=='huy' && (time()-strtotime($row['ngay_tao']))<86400;
            ?>
            <tr>
                <td>
                    <strong style="font-size:13px"><?= htmlspecialchars($row['ma_don']) ?></strong>
                    <div style="font-size:11px;color:var(--muted)"><?= date('d/m/Y',strtotime($row['ngay_tao'])) ?></div>
                </td>
                <td>
                    <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($row['ten_khach']) ?></div>
                    <div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($row['dien_thoai_kh']??'') ?></div>
                </td>
                <td class="route-cell">
                    📍 <strong><?= htmlspecialchars($row['tinh_lay']??'—') ?></strong><br>
                    🏁 <strong><?= htmlspecialchars($row['tinh_giao']??'—') ?></strong>
                    <?php if($row['ten_tuyen']): ?>
                    <br><span style="color:var(--muted)"><?= htmlspecialchars($row['ten_tuyen']) ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <span style="font-size:12px;background:#f0f4f8;padding:3px 8px;border-radius:6px">
                        <?= htmlspecialchars($loai_vc_map[$row['loai_van_chuyen']]??$row['loai_van_chuyen']) ?>
                    </span>
                    <?php if($row['trong_luong']): ?>
                    <div style="font-size:11px;color:var(--muted);margin-top:3px"><?= $row['trong_luong'] ?>T</div>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px">
                    <div>🚛 <?= htmlspecialchars($row['bien_so']??'—') ?></div>
                    <div style="color:var(--muted)">👤 <?= htmlspecialchars($row['ten_tai_xe']??'—') ?></div>
                </td>
                <td style="font-size:12px;color:var(--muted)">
                    <?= $row['khoang_cach'] ? number_format($row['khoang_cach']).' km' : '—' ?>
                </td>
                <td style="font-weight:700;color:var(--green);font-size:13px">
                    ₫<?= number_format($row['doanh_thu']) ?>
                </td>
                <td style="font-weight:700;font-size:13px;color:<?= $row['loi_nhuan']>=0?'#1e8449':'#e74c3c' ?>">
                    ₫<?= number_format($row['loi_nhuan']) ?>
                </td>
                <td style="font-size:12px">
                    <?= $row['ngay_giao_du_kien'] ? date('d/m H:i',strtotime($row['ngay_giao_du_kien'])) : '—' ?>
                </td>
                <td>
                    <span class="badge <?= $tt_map[$row['trang_thai']]['c']??'' ?>">
                        <?= $tt_map[$row['trang_thai']]['l']??$row['trang_thai'] ?>
                    </span>
                </td>
                <td>
                    <div style="display:flex;gap:5px">
                        <a href="dieuphoI_sua_don.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm" title="Sửa">✏️</a>
                        <?php if($can_huy): ?>
                        <button class="btn btn-danger btn-sm" onclick="openHuy(<?= $row['id'] ?>,'<?= htmlspecialchars($row['ma_don']) ?>')" title="Hủy đơn">🗑️</button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="11">
                <div class="empty-state"><div class="ei">📋</div><p>Không tìm thấy đơn hàng nào.</p></div>
            </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>

        <!-- Phân trang -->
        <?php if($pages>1): ?>
        <div class="pagination">
            <?php for($i=1;$i<=$pages;$i++): ?>
            <a href="?page=<?=$i?>&search=<?=urlencode($search)?>&trang_thai=<?=urlencode($trang_thai)?>&tinh=<?=urlencode($tinh)?>&loai_van_chuyen=<?=urlencode($loai_vc)?>"
               class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
</main>
</div>

<div class="modal-overlay" id="modal_huy">
<div class="modal-box" style="max-width:420px">
    <div class="modal-header">
        <h3>🗑️ Hủy Đơn Hàng</h3>
        <button class="modal-close" onclick="document.getElementById('modal_huy').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
        <input type="hidden" name="don_id" id="huy_don_id">
        <div style="background:#fff5f5;border-radius:8px;padding:12px;margin-bottom:16px;font-size:13px;color:#c0392b">
            ⚠️ Chỉ có thể hủy đơn trong vòng <strong>24 giờ</strong> sau khi tạo.<br>
            Đơn: <strong id="huy_ma_don"></strong>
        </div>
        <div class="field">
            <label>Lý do hủy</label>
            <textarea name="ly_do" placeholder="Nhập lý do hủy đơn..." style="width:100%;padding:10px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:13px;min-height:80px"></textarea>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-ghost" onclick="document.getElementById('modal_huy').classList.remove('open')">Thôi</button>
            <button type="submit" name="huy_don" class="btn btn-danger">🗑️ Xác Nhận Hủy</button>
        </div>
    </form>
</div>
</div>

<script>
function openHuy(id, maDon){
    document.getElementById('huy_don_id').value = id;
    document.getElementById('huy_ma_don').textContent = maDon;
    document.getElementById('modal_huy').classList.add('open');
}
document.querySelectorAll('.modal-overlay').forEach(m=>{
    m.addEventListener('click',e=>{ if(e.target===m) m.classList.remove('open'); });
});
</script>
</body></html>
