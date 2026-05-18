<?php
session_start(); require 'config.php'; require_role(['dieuphoI']);
$msg = '';

// Thêm tuyến
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['them_tuyen'])) {
    $ma    = strtoupper(trim($_POST['ma_tuyen']   ?? ''));
    $ten   = trim($_POST['ten_tuyen']   ?? '');
    $di    = trim($_POST['diem_di']     ?? '');
    $den   = trim($_POST['diem_den']    ?? '');
    $loai  = $_POST['loai_tuyen']       ?? 'lien_tinh';
    $km    = (float)($_POST['khoang_cach'] ?? 0) ?: null;
    $h     = (float)($_POST['thoi_gian']   ?? 0) ?: null;
    $gia   = (float)($_POST['gia_co_ban']  ?? 0);
    $mo_ta = trim($_POST['mo_ta'] ?? '');
    if ($ma && $ten && $di && $den) {
        $stmt = $conn->prepare("INSERT INTO tuyen_duong (ma_tuyen,ten_tuyen,diem_di,diem_den,loai_tuyen,khoang_cach,thoi_gian,gia_co_ban,mo_ta) VALUES(?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssddds",$ma,$ten,$di,$den,$loai,$km,$h,$gia,$mo_ta);
        if ($stmt->execute()) $msg=['type'=>'success','text'=>'Đã thêm tuyến đường thành công!'];
        else $msg=['type'=>'danger','text'=>'Lỗi: '.$stmt->error];
        $stmt->close();
    } else $msg=['type'=>'danger','text'=>'Vui lòng nhập đầy đủ thông tin!'];
}

// Sửa tuyến
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['sua_tuyen'])) {
    $id  = (int)$_POST['tuyen_id'];
    $ten = trim($_POST['ten_tuyen']   ?? '');
    $km  = (float)($_POST['khoang_cach'] ?? 0);
    $h   = (float)($_POST['thoi_gian']   ?? 0);
    $gia = (float)($_POST['gia_co_ban']  ?? 0);
    $act = (int)($_POST['is_active'] ?? 1);
    $conn->query("UPDATE tuyen_duong SET ten_tuyen='".mysqli_real_escape_string($conn,$ten)."',khoang_cach=$km,thoi_gian=$h,gia_co_ban=$gia,is_active=$act WHERE id=$id");
    $msg=['type'=>'success','text'=>'Đã cập nhật tuyến đường!'];
}

$filter_loai = $_GET['loai'] ?? '';
$where = $filter_loai ? "WHERE loai_tuyen='".mysqli_real_escape_string($conn,$filter_loai)."'" : '';
$rows = $conn->query("SELECT td.*, (SELECT COUNT(*) FROM don_hang dh WHERE dh.tuyen_duong_id=td.id) AS so_don FROM tuyen_duong td $where ORDER BY loai_tuyen,ma_tuyen");

$loai_map = [
    'lien_tinh' =>['l'=>'Liên tỉnh',  'c'=>'#1a5276','bg'=>'#eaf4fb'],
    'noi_vung'  =>['l'=>'Nội vùng',   'c'=>'#196f3d','bg'=>'#eafaf1'],
    'noi_thanh' =>['l'=>'Nội thành',  'c'=>'#7d6608','bg'=>'#fef9e7'],
];

$active = 'tuyen_duong'; require 'sidebar_dieuphoI.php';
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Tuyến Đường</title>
<link rel="stylesheet" href="dieuphoI_layout.css">
</head><body>
<div class="wrapper">

<main class="main">
<div class="topbar">
    <div class="topbar-left"><div class="topbar-title">🗺️ Quản Lý Tuyến Đường</div></div>
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
        <div style="display:flex;gap:8px">
            <a href="?loai=" class="btn <?= $filter_loai===''?'btn-primary':'btn-ghost' ?>">Tất cả</a>
            <?php foreach($loai_map as $k=>$v): ?>
            <a href="?loai=<?=$k?>" class="btn <?= $filter_loai===$k?'btn-primary':'btn-ghost' ?>"><?=$v['l']?></a>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-primary" onclick="document.getElementById('modal_them').classList.add('open')">
            ➕ Thêm Tuyến
        </button>
    </div>

    <div class="table-wrap">
        <div class="table-scroll">
        <table>
            <thead><tr>
                <th>Mã tuyến</th><th>Tên tuyến</th><th>Điểm đi</th><th>Điểm đến</th>
                <th>Loại</th><th>Khoảng cách</th><th>TG ước tính</th>
                <th>Giá cơ bản</th><th>Số đơn</th><th>Trạng thái</th><th>Thao tác</th>
            </tr></thead>
            <tbody>
            <?php if($rows && $rows->num_rows>0):
                while($r=$rows->fetch_assoc()):
                $loai_info = $loai_map[$r['loai_tuyen']] ?? ['l'=>$r['loai_tuyen'],'c'=>'#666','bg'=>'#f0f0f0'];
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['ma_tuyen']) ?></strong></td>
                <td style="font-weight:600"><?= htmlspecialchars($r['ten_tuyen']) ?></td>
                <td>📍 <?= htmlspecialchars($r['diem_di']) ?></td>
                <td>🏁 <?= htmlspecialchars($r['diem_den']) ?></td>
                <td>
                    <span style="background:<?=$loai_info['bg']?>;color:<?=$loai_info['c']?>;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px">
                        <?= $loai_info['l'] ?>
                    </span>
                </td>
                <td><?= $r['khoang_cach'] ? number_format($r['khoang_cach']).' km' : '—' ?></td>
                <td><?= $r['thoi_gian'] ? '~'.$r['thoi_gian'].' giờ' : '—' ?></td>
                <td style="font-weight:700;color:var(--green)">₫<?= number_format($r['gia_co_ban']) ?></td>
                <td style="text-align:center;font-weight:700"><?= $r['so_don'] ?></td>
                <td>
                    <?php if($r['is_active']): ?>
                        <span class="badge b-san_sang">Hoạt động</span>
                    <?php else: ?>
                        <span class="badge b-huy">Tạm dừng</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-warning btn-sm" onclick="openSua(<?= htmlspecialchars(json_encode($r)) ?>)">✏️</button>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="11"><div class="empty-state"><div class="ei">🗺️</div><p>Chưa có tuyến đường nào.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</main>
</div>

<!-- Modal thêm tuyến -->
<div class="modal-overlay" id="modal_them">
<div class="modal-box">
    <div class="modal-header">
        <h3>🗺️ Thêm Tuyến Đường Mới</h3>
        <button class="modal-close" onclick="document.getElementById('modal_them').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
        <div class="form-grid">
            <div class="field"><label>Mã tuyến *</label><input type="text" name="ma_tuyen" placeholder="VD: T011" style="text-transform:uppercase" required></div>
            <div class="field"><label>Loại tuyến</label>
                <select name="loai_tuyen">
                    <option value="lien_tinh">Liên tỉnh</option>
                    <option value="noi_vung">Nội vùng</option>
                    <option value="noi_thanh">Nội thành</option>
                </select>
            </div>
            <div class="field span2"><label>Tên tuyến *</label><input type="text" name="ten_tuyen" placeholder="VD: TP.HCM - Hà Nội" required></div>
            <div class="field"><label>Điểm đi *</label><input type="text" name="diem_di" placeholder="VD: TP. Hồ Chí Minh" required></div>
            <div class="field"><label>Điểm đến *</label><input type="text" name="diem_den" placeholder="VD: Hà Nội" required></div>
            <div class="field"><label>Khoảng cách (km)</label><input type="number" name="khoang_cach" step="0.1" min="0" placeholder="0"></div>
            <div class="field"><label>Thời gian ước tính (giờ)</label><input type="number" name="thoi_gian" step="0.5" min="0" placeholder="0"></div>
            <div class="field span2"><label>Giá cơ bản (₫)</label><input type="number" name="gia_co_ban" min="0" step="1000" placeholder="0"></div>
            <div class="field span2"><label>Mô tả / Lưu ý</label><textarea name="mo_ta" placeholder="Ghi chú về tuyến đường..."></textarea></div>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-ghost" onclick="document.getElementById('modal_them').classList.remove('open')">Hủy</button>
            <button type="submit" name="them_tuyen" class="btn btn-primary">💾 Lưu Tuyến</button>
        </div>
    </form>
</div>
</div>

<!-- Modal sửa tuyến -->
<div class="modal-overlay" id="modal_sua">
<div class="modal-box" style="max-width:480px">
    <div class="modal-header">
        <h3>✏️ Sửa Tuyến Đường</h3>
        <button class="modal-close" onclick="document.getElementById('modal_sua').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
        <input type="hidden" name="tuyen_id" id="sua_id">
        <div style="background:#f8f9fa;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:13px">
            Mã: <strong id="sua_ma"></strong> — <span id="sua_route"></span>
        </div>
        <div class="form-grid">
            <div class="field span2"><label>Tên tuyến</label><input type="text" name="ten_tuyen" id="sua_ten"></div>
            <div class="field"><label>Khoảng cách (km)</label><input type="number" name="khoang_cach" id="sua_km" step="0.1" min="0"></div>
            <div class="field"><label>Thời gian (giờ)</label><input type="number" name="thoi_gian" id="sua_h" step="0.5" min="0"></div>
            <div class="field"><label>Giá cơ bản (₫)</label><input type="number" name="gia_co_ban" id="sua_gia" min="0" step="1000"></div>
            <div class="field"><label>Trạng thái</label>
                <select name="is_active" id="sua_act">
                    <option value="1">Hoạt động</option>
                    <option value="0">Tạm dừng</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-ghost" onclick="document.getElementById('modal_sua').classList.remove('open')">Hủy</button>
            <button type="submit" name="sua_tuyen" class="btn btn-primary">💾 Cập Nhật</button>
        </div>
    </form>
</div>
</div>

<script>
function openSua(t){
    document.getElementById('sua_id').value  = t.id;
    document.getElementById('sua_ma').textContent    = t.ma_tuyen;
    document.getElementById('sua_route').textContent = t.diem_di+' → '+t.diem_den;
    document.getElementById('sua_ten').value = t.ten_tuyen;
    document.getElementById('sua_km').value  = t.khoang_cach;
    document.getElementById('sua_h').value   = t.thoi_gian;
    document.getElementById('sua_gia').value = t.gia_co_ban;
    document.getElementById('sua_act').value = t.is_active;
    document.getElementById('modal_sua').classList.add('open');
}
document.querySelectorAll('.modal-overlay').forEach(m=>{
    m.addEventListener('click',e=>{ if(e.target===m) m.classList.remove('open'); });
});
</script>
</body></html>
