<?php
session_start(); require 'config.php'; require_role(['admin']);
$msg=''; $tab=$_GET['tab']??'xe';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['them_xe'])) {
    $bs=$_POST['bien_so']??''; $loai=$_POST['loai_xe']??'xe_tai_trung';
    $nh=$_POST['nhan_hieu']??''; $ns=(int)($_POST['nam_sx']??0)?:null;
    $tt=(float)($_POST['tai_trong']??0)?:null; $tv=(float)($_POST['the_tich']??0)?:null;
    $dk=$_POST['han_dang_kiem']??null; $bh=$_POST['han_bao_hiem']??null;
    $km=(int)($_POST['km_hien_tai']??0); $ml=(float)($_POST['muc_tieu_thu']??0)?:null;
    $stmt=$conn->prepare("INSERT INTO xe (bien_so,loai_xe,nhan_hieu,nam_sx,tai_trong,the_tich,han_dang_kiem,han_bao_hiem,km_hien_tai,muc_tieu_thu) VALUES(?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sssiddssiid",$bs,$loai,$nh,$ns,$tt,$tv,$dk,$bh,$km,$ml);
    if($stmt->execute()) $msg=['type'=>'success','text'=>"Đã thêm xe $bs!"]; else $msg=['type'=>'danger','text'=>'Lỗi: '.$stmt->error];
    $stmt->close();
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['sua_xe'])) {
    $id=(int)$_POST['xe_id']; $loai=$_POST['loai_xe']??''; $tt=(float)($_POST['tai_trong']??0);
    $nh=mysqli_real_escape_string($conn,$_POST['nhan_hieu']??'');
    $dk=$_POST['han_dang_kiem']??null; $bh=$_POST['han_bao_hiem']??null;
    $tinh=$_POST['tinh_trang']??'san_sang'; $km=(int)($_POST['km_hien_tai']??0);
    $conn->query("UPDATE xe SET loai_xe='$loai',nhan_hieu='$nh',tai_trong=$tt,han_dang_kiem=".($dk?"'$dk'":"NULL").",han_bao_hiem=".($bh?"'$bh'":"NULL").",tinh_trang='$tinh',km_hien_tai=$km WHERE id=$id");
    $msg=['type'=>'success','text'=>'Đã cập nhật xe!'];
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['xoa_xe'])) {
    $id=(int)$_POST['xe_id'];
    $conn->query("UPDATE xe SET tinh_trang='nghi' WHERE id=$id");
    $msg=['type'=>'success','text'=>'Đã đưa xe vào trạng thái nghỉ!'];
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['them_taixe'])) {
    $ht=trim($_POST['ho_ten']??''); $sdt=trim($_POST['so_dien_thoai']??'');
    $gplx=trim($_POST['so_gplx']??''); $hang=$_POST['hang_gplx']??'C';
    $han=$_POST['han_gplx']??null; $kn=(int)($_POST['kinh_nghiem']??0);
    $luong=(float)($_POST['luong_co_ban']??0);
    if($ht&&$sdt){
        $stmt=$conn->prepare("INSERT INTO tai_xe (ho_ten,so_dien_thoai,so_gplx,hang_gplx,han_gplx,kinh_nghiem,luong_co_ban) VALUES(?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssid",$ht,$sdt,$gplx,$hang,$han,$kn,$luong);
        if($stmt->execute()) $msg=['type'=>'success','text'=>"Đã thêm tài xế $ht!"]; else $msg=['type'=>'danger','text'=>'Lỗi: '.$stmt->error];
        $stmt->close();
    } else $msg=['type'=>'danger','text'=>'Nhập đủ thông tin!'];
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['sua_taixe'])) {
    $id=(int)$_POST['tx_id']; $sdt=mysqli_real_escape_string($conn,$_POST['so_dien_thoai']??'');
    $hang=$_POST['hang_gplx']??'C'; $han=$_POST['han_gplx']??null;
    $kn=(int)($_POST['kinh_nghiem']??0); $luong=(float)($_POST['luong_co_ban']??0);
    $tt=$_POST['tinh_trang']??'san_sang';
    $conn->query("UPDATE tai_xe SET so_dien_thoai='$sdt',hang_gplx='$hang',han_gplx=".($han?"'$han'":"NULL").",kinh_nghiem=$kn,luong_co_ban=$luong,tinh_trang='$tt' WHERE id=$id");
    $msg=['type'=>'success','text'=>'Đã cập nhật tài xế!'];
}

$ds_xe    = $conn->query("SELECT * FROM xe ORDER BY tinh_trang,bien_so");
$ds_taixe = $conn->query("SELECT * FROM tai_xe ORDER BY tinh_trang,ho_ten");

$warn_xe  = $conn->query("SELECT bien_so,han_dang_kiem,han_bao_hiem FROM xe WHERE (han_dang_kiem<=DATE_ADD(CURDATE(),INTERVAL 30 DAY) OR han_bao_hiem<=DATE_ADD(CURDATE(),INTERVAL 30 DAY)) AND tinh_trang!='nghi'")->fetch_all(MYSQLI_ASSOC);
$warn_tx  = $conn->query("SELECT ho_ten,han_gplx FROM tai_xe WHERE han_gplx<=DATE_ADD(CURDATE(),INTERVAL 60 DAY) AND tinh_trang!='nghi_viec'")->fetch_all(MYSQLI_ASSOC);

$loai_xe_labels=['xe_tai_nhe'=>'Xe tải nhẹ','xe_tai_trung'=>'Xe tải trung','xe_tai_nang'=>'Xe tải nặng','dau_keo'=>'Đầu kéo','xe_dong_lanh'=>'Đông lạnh','xe_chuyen_dung'=>'Chuyên dụng'];

$active='phuong_tien'; require 'sidebar_admin.php';
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Phương Tiện — Admin</title>
<link rel="stylesheet" href="admin_layout.css">
<style>
.tab-bar{display:flex;gap:4px;margin-bottom:20px;background:#f8f9fa;border-radius:10px;padding:4px}
.tab-btn{flex:1;text-align:center;padding:9px 20px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;border:none;background:transparent;color:var(--muted);transition:.2s;text-decoration:none}
.tab-btn.active{background:#fff;color:var(--primary);box-shadow:0 2px 8px rgba(0,0,0,.08)}
.warn-box{background:#fff8e1;border:1px solid #ffe082;border-radius:10px;padding:12px 16px;margin-bottom:18px}
.warn-box h4{font-size:13px;font-weight:700;color:#f57f17;margin-bottom:8px}
</style>
</head><body>
<div class="app">
<?php require 'sidebar_admin.php'; ?>
<main class="main">
<div class="topbar">
    <div><div class="topbar-title">🚛 Quản Lý Phương Tiện</div>
    <div class="breadcrumb"><a href="admin_dashboard.php">Dashboard</a> › Phương tiện</div></div>
    <div class="user-chip">
        <div class="chip-avatar"><?=mb_strtoupper(mb_substr($_SESSION['full_name']??'A',0,1))?></div>
        <div><div class="chip-name"><?=htmlspecialchars($_SESSION['full_name']??'Admin')?></div><div class="chip-role">Super Admin</div></div>
    </div>
</div>
<div class="content">
    <?php if(!empty($msg))echo"<div class='alert alert-{$msg['type']}'>{$msg['text']}</div>"; ?>

    <?php if(!empty($warn_xe)||!empty($warn_tx)): ?>
    <div class="warn-box">
        <h4>⚠️ Cảnh báo hết hạn</h4>
        <?php foreach($warn_xe as $w): ?>
        <div style="font-size:12px;color:#795548;padding:2px 0">🚛 <strong><?=$w['bien_so']?></strong> — ĐK: <?=$w['han_dang_kiem']?> · BH: <?=$w['han_bao_hiem']?></div>
        <?php endforeach; ?>
        <?php foreach($warn_tx as $w): ?>
        <div style="font-size:12px;color:#795548;padding:2px 0">👤 <strong><?=$w['ho_ten']?></strong> — GPLX hết hạn: <?=$w['han_gplx']?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="tab-bar">
        <a href="?tab=xe"     class="tab-btn <?=$tab==='xe'?'active':''?>">🚛 Xe (<?=$ds_xe->num_rows?>)</a>
        <a href="?tab=taixe"  class="tab-btn <?=$tab==='taixe'?'active':''?>">👤 Tài Xế (<?=$ds_taixe->num_rows?>)</a>
    </div>

    <?php if($tab==='xe'): ?>
    <div class="page-header">
        <h1>Danh Sách Xe</h1>
        <button class="btn btn-primary" onclick="document.getElementById('modal_them_xe').classList.add('open')">➕ Thêm Xe</button>
    </div>
    <div class="table-wrap">
        <div class="table-scroll">
        <table>
            <thead><tr><th>Biển số</th><th>Loại xe</th><th>Nhãn hiệu</th><th>Tải trọng</th><th>KM hiện tại</th><th>Đăng kiểm</th><th>Bảo hiểm</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php $ds_xe->data_seek(0); while($r=$ds_xe->fetch_assoc()):
                $dk_warn = $r['han_dang_kiem'] && strtotime($r['han_dang_kiem'])<strtotime('+30 days');
                $bh_warn = $r['han_bao_hiem']  && strtotime($r['han_bao_hiem']) <strtotime('+30 days');
            ?>
            <tr <?=$dk_warn||$bh_warn?'style="background:#fff8e1"':''?>>
                <td><strong><?=htmlspecialchars($r['bien_so'])?></strong></td>
                <td><?=$loai_xe_labels[$r['loai_xe']]??$r['loai_xe']?></td>
                <td><?=htmlspecialchars($r['nhan_hieu']??'—')?></td>
                <td><?=$r['tai_trong']?$r['tai_trong'].'T':'—'?></td>
                <td><?=number_format($r['km_hien_tai'])?> km</td>
                <td style="color:<?=$dk_warn?'#e74c3c':'inherit'?>"><?=$r['han_dang_kiem']??'—'?><?=$dk_warn?' ⚠️':''?></td>
                <td style="color:<?=$bh_warn?'#e74c3c':'inherit'?>"><?=$r['han_bao_hiem']??'—'?><?=$bh_warn?' ⚠️':''?></td>
                <td><span class="badge b-<?=$r['tinh_trang']?>"><?=$r['tinh_trang']?></span></td>
                <td>
                    <div style="display:flex;gap:5px">
                        <button class="btn btn-warning btn-sm" onclick="openSuaXe(<?=htmlspecialchars(json_encode($r))?>)">✏️</button>
                        <form method="POST" onsubmit="return confirm('Đưa xe vào trạng thái nghỉ?')" style="display:inline">
                            <input type="hidden" name="xe_id" value="<?=$r['id']?>">
                            <button type="submit" name="xoa_xe" class="btn btn-danger btn-sm">🚫</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>

    <?php else: ?>
    <div class="page-header">
        <h1>Danh Sách Tài Xế</h1>
        <button class="btn btn-primary" onclick="document.getElementById('modal_them_tx').classList.add('open')">➕ Thêm Tài Xế</button>
    </div>
    <div class="table-wrap">
        <div class="table-scroll">
        <table>
            <thead><tr><th>Họ tên</th><th>Điện thoại</th><th>Hạng GPLX</th><th>Số GPLX</th><th>Hạn GPLX</th><th>Kinh nghiệm</th><th>Lương cơ bản</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php $ds_taixe->data_seek(0); while($r=$ds_taixe->fetch_assoc()):
                $gplx_warn = $r['han_gplx'] && strtotime($r['han_gplx'])<strtotime('+60 days');
            ?>
            <tr <?=$gplx_warn?'style="background:#fff8e1"':''?>>
                <td><strong><?=htmlspecialchars($r['ho_ten'])?></strong></td>
                <td><?=htmlspecialchars($r['so_dien_thoai'])?></td>
                <td><span class="badge b-dieuphoI"><?=$r['hang_gplx']?></span></td>
                <td style="font-size:12px"><?=htmlspecialchars($r['so_gplx']??'—')?></td>
                <td style="color:<?=$gplx_warn?'#e74c3c':'inherit'?>"><?=$r['han_gplx']??'—'?><?=$gplx_warn?' ⚠️':''?></td>
                <td><?=$r['kinh_nghiem']?> năm</td>
                <td>₫<?=number_format($r['luong_co_ban'])?></td>
                <td><span class="badge b-<?=$r['tinh_trang']?>"><?=$r['tinh_trang']?></span></td>
                <td><button class="btn btn-warning btn-sm" onclick="openSuaTx(<?=htmlspecialchars(json_encode($r))?>)">✏️</button></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>
</div>
</main>
</div>

<div class="modal-overlay" id="modal_them_xe">
<div class="modal-box"><div class="modal-header"><h3>➕ Thêm Xe Mới</h3><button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('open')">✕</button></div>
<form method="POST"><div class="form-grid">
    <div class="field"><label>Biển số *</label><input type="text" name="bien_so" required placeholder="VD: 51C-123.45" style="text-transform:uppercase"></div>
    <div class="field"><label>Loại xe</label><select name="loai_xe"><?php foreach($loai_xe_labels as $k=>$v): ?><option value="<?=$k?>"><?=$v?></option><?php endforeach; ?></select></div>
    <div class="field"><label>Nhãn hiệu</label><input type="text" name="nhan_hieu" placeholder="VD: Hyundai HD320"></div>
    <div class="field"><label>Năm sản xuất</label><input type="number" name="nam_sx" min="2000" max="2030" placeholder="2022"></div>
    <div class="field"><label>Tải trọng (tấn)</label><input type="number" name="tai_trong" step="0.1" min="0" placeholder="5.0"></div>
    <div class="field"><label>Km hiện tại</label><input type="number" name="km_hien_tai" min="0" placeholder="0"></div>
    <div class="field"><label>Hạn đăng kiểm</label><input type="date" name="han_dang_kiem"></div>
    <div class="field"><label>Hạn bảo hiểm</label><input type="date" name="han_bao_hiem"></div>
</div>
<div class="form-actions"><button type="button" class="btn btn-ghost" onclick="this.closest('.modal-overlay').classList.remove('open')">Hủy</button><button type="submit" name="them_xe" class="btn btn-primary">💾 Thêm Xe</button></div>
</form></div></div>

<div class="modal-overlay" id="modal_sua_xe">
<div class="modal-box"><div class="modal-header"><h3>✏️ Sửa Xe — <span id="sua_xe_bs"></span></h3><button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('open')">✕</button></div>
<form method="POST"><input type="hidden" name="xe_id" id="sxa_id">
<div class="form-grid">
    <div class="field"><label>Loại xe</label><select name="loai_xe" id="sxa_loai"><?php foreach($loai_xe_labels as $k=>$v): ?><option value="<?=$k?>"><?=$v?></option><?php endforeach; ?></select></div>
    <div class="field"><label>Trạng thái</label><select name="tinh_trang" id="sxa_tt"><option value="san_sang">Sẵn sàng</option><option value="dang_chay">Đang chạy</option><option value="bao_duong">Bảo dưỡng</option><option value="hong">Hỏng</option><option value="nghi">Nghỉ</option></select></div>
    <div class="field"><label>Nhãn hiệu</label><input type="text" name="nhan_hieu" id="sxa_nh"></div>
    <div class="field"><label>Tải trọng (T)</label><input type="number" name="tai_trong" id="sxa_tt2" step="0.1"></div>
    <div class="field"><label>Km hiện tại</label><input type="number" name="km_hien_tai" id="sxa_km"></div>
    <div class="field"></div>
    <div class="field"><label>Hạn đăng kiểm</label><input type="date" name="han_dang_kiem" id="sxa_dk"></div>
    <div class="field"><label>Hạn bảo hiểm</label><input type="date" name="han_bao_hiem" id="sxa_bh"></div>
</div>
<div class="form-actions"><button type="button" class="btn btn-ghost" onclick="this.closest('.modal-overlay').classList.remove('open')">Hủy</button><button type="submit" name="sua_xe" class="btn btn-primary">💾 Lưu</button></div>
</form></div></div>

<div class="modal-overlay" id="modal_them_tx">
<div class="modal-box"><div class="modal-header"><h3>➕ Thêm Tài Xế</h3><button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('open')">✕</button></div>
<form method="POST"><div class="form-grid">
    <div class="field"><label>Họ tên *</label><input type="text" name="ho_ten" required placeholder="Nhập họ tên đầy đủ"></div>
    <div class="field"><label>Điện thoại *</label><input type="text" name="so_dien_thoai" required placeholder="09xxxxxxxx"></div>
    <div class="field"><label>Số GPLX</label><input type="text" name="so_gplx" placeholder="GPLX-XXX"></div>
    <div class="field"><label>Hạng GPLX</label><select name="hang_gplx"><option>B1</option><option>B2</option><option selected>C</option><option>D</option><option>E</option><option>FC</option></select></div>
    <div class="field"><label>Hạn GPLX</label><input type="date" name="han_gplx"></div>
    <div class="field"><label>Kinh nghiệm (năm)</label><input type="number" name="kinh_nghiem" min="0" placeholder="0"></div>
    <div class="field span2"><label>Lương cơ bản (₫)</label><input type="number" name="luong_co_ban" min="0" step="100000" placeholder="10000000"></div>
</div>
<div class="form-actions"><button type="button" class="btn btn-ghost" onclick="this.closest('.modal-overlay').classList.remove('open')">Hủy</button><button type="submit" name="them_taixe" class="btn btn-primary">💾 Thêm Tài Xế</button></div>
</form></div></div>

<div class="modal-overlay" id="modal_sua_tx">
<div class="modal-box"><div class="modal-header"><h3>✏️ Sửa Tài Xế — <span id="sua_tx_ten"></span></h3><button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('open')">✕</button></div>
<form method="POST"><input type="hidden" name="tx_id" id="stx_id">
<div class="form-grid">
    <div class="field"><label>Điện thoại</label><input type="text" name="so_dien_thoai" id="stx_sdt"></div>
    <div class="field"><label>Hạng GPLX</label><select name="hang_gplx" id="stx_hang"><option>B1</option><option>B2</option><option>C</option><option>D</option><option>E</option><option>FC</option></select></div>
    <div class="field"><label>Hạn GPLX</label><input type="date" name="han_gplx" id="stx_han"></div>
    <div class="field"><label>Kinh nghiệm (năm)</label><input type="number" name="kinh_nghiem" id="stx_kn" min="0"></div>
    <div class="field"><label>Lương cơ bản (₫)</label><input type="number" name="luong_co_ban" id="stx_luong" min="0" step="100000"></div>
    <div class="field"><label>Trạng thái</label><select name="tinh_trang" id="stx_tt"><option value="san_sang">Sẵn sàng</option><option value="dang_chay">Đang chạy</option><option value="nghi_phep">Nghỉ phép</option><option value="nghi_viec">Nghỉ việc</option></select></div>
</div>
<div class="form-actions"><button type="button" class="btn btn-ghost" onclick="this.closest('.modal-overlay').classList.remove('open')">Hủy</button><button type="submit" name="sua_taixe" class="btn btn-primary">💾 Lưu</button></div>
</form></div></div>

<script>
function openSuaXe(x){
    document.getElementById('sxa_id').value=x.id; document.getElementById('sua_xe_bs').textContent=x.bien_so;
    document.getElementById('sxa_loai').value=x.loai_xe; document.getElementById('sxa_nh').value=x.nhan_hieu||'';
    document.getElementById('sxa_tt').value=x.tinh_trang; document.getElementById('sxa_tt2').value=x.tai_trong||'';
    document.getElementById('sxa_km').value=x.km_hien_tai||0; document.getElementById('sxa_dk').value=x.han_dang_kiem||'';
    document.getElementById('sxa_bh').value=x.han_bao_hiem||''; document.getElementById('modal_sua_xe').classList.add('open');
}
function openSuaTx(t){
    document.getElementById('stx_id').value=t.id; document.getElementById('sua_tx_ten').textContent=t.ho_ten;
    document.getElementById('stx_sdt').value=t.so_dien_thoai; document.getElementById('stx_hang').value=t.hang_gplx;
    document.getElementById('stx_han').value=t.han_gplx||''; document.getElementById('stx_kn').value=t.kinh_nghiem||0;
    document.getElementById('stx_luong').value=t.luong_co_ban; document.getElementById('stx_tt').value=t.tinh_trang;
    document.getElementById('modal_sua_tx').classList.add('open');
}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('open')}));
</script>
</body></html>