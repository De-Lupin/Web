<?php
session_start(); require 'config.php'; require_role(['dieuphoI']);
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $ma_don       = strtoupper(trim($_POST['ma_don']        ?? ''));
    $ten_khach    = trim($_POST['ten_khach']     ?? '');
    $dt_kh        = trim($_POST['dien_thoai_kh'] ?? '');
    $dia_lay      = trim($_POST['dia_chi_lay']   ?? '');
    $dia_giao     = trim($_POST['dia_chi_giao']  ?? '');
    $tinh_lay     = trim($_POST['tinh_lay']      ?? '');
    $tinh_giao    = trim($_POST['tinh_giao']     ?? '');
    $loai_hang    = trim($_POST['loai_hang']     ?? '');
    $trong_luong  = (float)($_POST['trong_luong'] ?? 0) ?: null;
    $the_tich     = (float)($_POST['the_tich']    ?? 0) ?: null;
    $loai_vc      = $_POST['loai_van_chuyen']    ?? 'hang_le';
    $tuyen_id     = (int)($_POST['tuyen_duong_id']??0) ?: null;
    $xe_id        = (int)($_POST['xe_id']         ??0) ?: null;
    $tai_xe_id    = (int)($_POST['tai_xe_id']     ??0) ?: null;
    $ngay_lay     = $_POST['ngay_lay_hang']       ?? null;
    $ngay_giao    = $_POST['ngay_giao_du_kien']   ?? null;
    $gia_cuoc     = (float)($_POST['gia_cuoc']      ??0);
    $phi_ps       = (float)($_POST['phi_phat_sinh'] ??0);
    $phi_ct       = (float)($_POST['phi_cao_toc']   ??0);
    $phi_bx       = (float)($_POST['phi_boc_xep']   ??0);
    $phi_ch       = (float)($_POST['phi_cho_hang']  ??0);
    $doanh_thu    = (float)($_POST['doanh_thu']      ??0);
    $ghi_chu      = trim($_POST['ghi_chu']        ?? '');
    $nguoi_tao    = $_SESSION['user_id'];

    $tong_chi_phi = $gia_cuoc + $phi_ps + $phi_ct + $phi_bx + $phi_ch;
    $loi_nhuan    = $doanh_thu - $tong_chi_phi;

    if (empty($ma_don)||empty($ten_khach)||empty($dia_lay)||empty($dia_giao)||empty($tinh_lay)||empty($tinh_giao)) {
        $msg = ['type'=>'danger','text'=>'Vui lòng nhập đầy đủ các trường bắt buộc (*)!'];
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO don_hang
             (ma_don,ten_khach,dien_thoai_kh,dia_chi_lay,dia_chi_giao,tinh_lay,tinh_giao,
              loai_hang,trong_luong,the_tich,loai_van_chuyen,tuyen_duong_id,xe_id,tai_xe_id,
              nguoi_tao_id,ngay_lay_hang,ngay_giao_du_kien,
              gia_cuoc,phi_phat_sinh,phi_cao_toc,phi_boc_xep,phi_cho_hang,
              tong_chi_phi,doanh_thu,loi_nhuan,ghi_chu,trang_thai)
             VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'cho_duyet')"
        );
        $stmt->bind_param(
            "ssssssssddsiiisssdddddddds",
            $ma_don,$ten_khach,$dt_kh,$dia_lay,$dia_giao,$tinh_lay,$tinh_giao,
            $loai_hang,$trong_luong,$the_tich,$loai_vc,$tuyen_id,$xe_id,$tai_xe_id,
            $nguoi_tao,$ngay_lay,$ngay_giao,
            $gia_cuoc,$phi_ps,$phi_ct,$phi_bx,$phi_ch,
            $tong_chi_phi,$doanh_thu,$loi_nhuan,$ghi_chu
        );
        if ($stmt->execute()) { header("Location: dieuphoI_don_hang.php?msg=created"); exit(); }
        else $msg = ['type'=>'danger','text'=>'Lỗi: '.$stmt->error];
        $stmt->close();
    }
}

$ds_tuyen  = $conn->query("SELECT id,ma_tuyen,ten_tuyen,khoang_cach,thoi_gian,gia_co_ban FROM tuyen_duong WHERE is_active=1 ORDER BY loai_tuyen,ten_tuyen");
$ds_xe     = $conn->query("SELECT id,bien_so,loai_xe,tai_trong,tinh_trang FROM xe WHERE tinh_trang='san_sang' ORDER BY bien_so");
$ds_taixe  = $conn->query("SELECT id,ho_ten,so_dien_thoai,hang_gplx,tinh_trang FROM tai_xe WHERE tinh_trang='san_sang' ORDER BY ho_ten");

$tuyen_json = [];
$ds_tuyen->data_seek(0);
while($t=$ds_tuyen->fetch_assoc()) $tuyen_json[$t['id']] = $t;
$ds_tuyen->data_seek(0);


$last    = $conn->query("SELECT ma_don FROM don_hang ORDER BY id DESC LIMIT 1")->fetch_assoc();
$next_n  = $last ? ((int)substr($last['ma_don'],-3)+1) : 1;
$auto_ma = 'VT-'.date('Y').'-'.str_pad($next_n,3,'0',STR_PAD_LEFT);

$tinh_list = ['TP.HCM','Hà Nội','Đà Nẵng','Bình Dương','Đồng Nai','Cần Thơ','Vũng Tàu','Long An','Tây Ninh','Bình Phước','Hải Phòng','Nha Trang','Huế','Đà Lạt','Cà Mau'];

$active = 'tao_don'; require 'sidebar_dieuphoI.php';
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Tạo Đơn Hàng Mới</title>
<link rel="stylesheet" href="dieuphoI_layout.css">
<style>
.calc-box{background:#eaf4fb;border:1px solid #aed6f1;border-radius:10px;padding:16px 20px}
.calc-row{display:flex;justify-content:space-between;font-size:13px;padding:4px 0}
.calc-row.total{border-top:2px solid #aed6f1;margin-top:8px;padding-top:10px;font-weight:800;font-size:15px}
.tuyen-info{background:#f8fff9;border:1px solid #a9dfbf;border-radius:8px;padding:10px 14px;font-size:12px;margin-top:8px;display:none}
</style>
</head><body>
<div class="wrapper">

<main class="main">
<div class="topbar">
    <div class="topbar-left">
        <div class="topbar-title">➕ Tạo Đơn Hàng Mới</div>
        <div class="breadcrumb"><a href="dieuphoI_don_hang.php">Đơn hàng</a> › Tạo mới</div>
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

    <form method="POST">
    <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">

        <div style="display:flex;flex-direction:column;gap:20px">

       
            <div class="form-card">
                <h3 style="font-size:15px;font-weight:700;color:var(--green);margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid var(--green-light)">
                    📋 Thông Tin Đơn Hàng
                </h3>
                <div class="form-grid">
                    <div class="field">
                        <label>Mã đơn hàng *</label>
                        <input type="text" name="ma_don" value="<?= htmlspecialchars($_POST['ma_don']??$auto_ma) ?>" required>
                    </div>
                    <div class="field">
                        <label>Loại vận chuyển *</label>
                        <select name="loai_van_chuyen">
                            <?php foreach(['hang_le'=>'Hàng lẻ (LTL)','hang_nguyen_xe'=>'Nguyên xe (FTL)','hang_dong_lanh'=>'Hàng đông lạnh','hang_qua_kho'=>'Hàng qua kho','hang_sieu_truong'=>'Siêu trường siêu nặng'] as $k=>$v): ?>
                            <option value="<?=$k?>" <?=($_POST['loai_van_chuyen']??'')===$k?'selected':''?>><?=$v?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Tên khách hàng *</label>
                        <input type="text" name="ten_khach" placeholder="Tên cá nhân hoặc công ty" required value="<?= htmlspecialchars($_POST['ten_khach']??'') ?>">
                    </div>
                    <div class="field">
                        <label>Điện thoại</label>
                        <input type="text" name="dien_thoai_kh" placeholder="09xxxxxxxx" value="<?= htmlspecialchars($_POST['dien_thoai_kh']??'') ?>">
                    </div>

               
                    <div class="field">
                        <label>Tỉnh/TP lấy hàng *</label>
                        <input list="tinh_list" name="tinh_lay" placeholder="VD: TP.HCM" required value="<?= htmlspecialchars($_POST['tinh_lay']??'') ?>">
                        <datalist id="tinh_list"><?php foreach($tinh_list as $t): ?><option value="<?=$t?>"><?php endforeach; ?></datalist>
                    </div>
                    <div class="field">
                        <label>Tỉnh/TP giao hàng *</label>
                        <input list="tinh_list" name="tinh_giao" placeholder="VD: Hà Nội" required value="<?= htmlspecialchars($_POST['tinh_giao']??'') ?>">
                    </div>
                    <div class="field span2">
                        <label>Địa chỉ lấy hàng chi tiết *</label>
                        <input type="text" name="dia_chi_lay" placeholder="Số nhà, đường, phường/xã, quận/huyện..." required value="<?= htmlspecialchars($_POST['dia_chi_lay']??'') ?>">
                    </div>
                    <div class="field span2">
                        <label>Địa chỉ giao hàng chi tiết *</label>
                        <input type="text" name="dia_chi_giao" placeholder="Số nhà, đường, phường/xã, quận/huyện..." required value="<?= htmlspecialchars($_POST['dia_chi_giao']??'') ?>">
                    </div>

                    <div class="field">
                        <label>Loại hàng hóa</label>
                        <input type="text" name="loai_hang" placeholder="VD: Điện tử, nông sản, vật liệu..." value="<?= htmlspecialchars($_POST['loai_hang']??'') ?>">
                    </div>
                    <div class="field">
                        <label>Trọng lượng (tấn)</label>
                        <input type="number" name="trong_luong" step="0.01" min="0" placeholder="0.00" value="<?= htmlspecialchars($_POST['trong_luong']??'') ?>">
                    </div>
                    <div class="field">
                        <label>Thể tích (m³)</label>
                        <input type="number" name="the_tich" step="0.1" min="0" placeholder="0.0" value="<?= htmlspecialchars($_POST['the_tich']??'') ?>">
                    </div>
                    <div class="field">
                        <label>Ghi chú</label>
                        <input type="text" name="ghi_chu" placeholder="Hàng dễ vỡ, cần xe mui kín..." value="<?= htmlspecialchars($_POST['ghi_chu']??'') ?>">
                    </div>
                </div>
            </div>

     
            <div class="form-card">
                <h3 style="font-size:15px;font-weight:700;color:var(--green);margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid var(--green-light)">
                    🗺️ Tuyến Đường & Lịch Trình
                </h3>
                <div class="form-grid">
                    <div class="field span2">
                        <label>Chọn tuyến đường</label>
                        <select name="tuyen_duong_id" id="sel_tuyen" onchange="chonTuyen(this)">
                            <option value="">— Chọn tuyến có sẵn hoặc tự nhập —</option>
                            <?php $ds_tuyen->data_seek(0); while($t=$ds_tuyen->fetch_assoc()): ?>
                            <option value="<?=$t['id']?>" data-km="<?=$t['khoang_cach']?>" data-h="<?=$t['thoi_gian']?>" data-gia="<?=$t['gia_co_ban']?>">
                                [<?=$t['ma_tuyen']?>] <?= htmlspecialchars($t['ten_tuyen']) ?>
                                — <?=$t['khoang_cach']?> km · ₫<?=number_format($t['gia_co_ban'])?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                        <div class="tuyen-info" id="tuyen_info"></div>
                    </div>
                    <div class="field">
                        <label>Ngày & giờ lấy hàng</label>
                        <input type="datetime-local" name="ngay_lay_hang" value="<?= htmlspecialchars($_POST['ngay_lay_hang']??'') ?>">
                    </div>
                    <div class="field">
                        <label>Ngày giao dự kiến</label>
                        <input type="datetime-local" name="ngay_giao_du_kien" value="<?= htmlspecialchars($_POST['ngay_giao_du_kien']??'') ?>">
                    </div>
                </div>
            </div>

            <div class="form-card">
                <h3 style="font-size:15px;font-weight:700;color:var(--green);margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid var(--green-light)">
                    🚛 Phân Công Xe & Tài Xế
                </h3>
                <div class="form-grid">
                    <div class="field">
                        <label>Chọn xe</label>
                        <select name="xe_id">
                            <option value="">— Chưa phân xe —</option>
                            <?php $ds_xe->data_seek(0); while($xe=$ds_xe->fetch_assoc()): ?>
                            <option value="<?=$xe['id']?>">
                                <?= htmlspecialchars($xe['bien_so']) ?>
                                (<?=$xe['tai_trong']?>T · <?=$xe['loai_xe']?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Chọn tài xế</label>
                        <select name="tai_xe_id">
                            <option value="">— Chưa phân tài xế —</option>
                            <?php $ds_taixe->data_seek(0); while($tx=$ds_taixe->fetch_assoc()): ?>
                            <option value="<?=$tx['id']?>">
                                <?= htmlspecialchars($tx['ho_ten']) ?>
                                (<?=$tx['hang_gplx']?> · <?=$tx['so_dien_thoai']?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-card" style="position:sticky;top:74px">
            <h3 style="font-size:15px;font-weight:700;color:var(--green);margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid var(--green-light)">
                💰 Tài Chính
            </h3>
            <div style="display:flex;flex-direction:column;gap:13px">
                <div class="field">
                    <label>Doanh thu (₫)</label>
                    <input type="number" name="doanh_thu" id="doanh_thu" min="0" step="1000" placeholder="0" oninput="calc()" value="<?= htmlspecialchars($_POST['doanh_thu']??'') ?>">
                </div>
                <div style="font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;padding-top:4px">Chi phí</div>
                <div class="field">
                    <label>Giá cước vận chuyển (₫)</label>
                    <input type="number" name="gia_cuoc" id="gia_cuoc" min="0" step="1000" placeholder="0" oninput="calc()" value="<?= htmlspecialchars($_POST['gia_cuoc']??'') ?>">
                </div>
                <div class="field">
                    <label>Phí đường cao tốc / BOT (₫)</label>
                    <input type="number" name="phi_cao_toc" id="phi_cao_toc" min="0" step="1000" placeholder="0" oninput="calc()" value="<?= htmlspecialchars($_POST['phi_cao_toc']??'') ?>">
                </div>
                <div class="field">
                    <label>Phí bốc xếp (₫)</label>
                    <input type="number" name="phi_boc_xep" id="phi_boc_xep" min="0" step="1000" placeholder="0" oninput="calc()" value="<?= htmlspecialchars($_POST['phi_boc_xep']??'') ?>">
                </div>
                <div class="field">
                    <label>Phí chờ hàng (₫)</label>
                    <input type="number" name="phi_cho_hang" id="phi_cho_hang" min="0" step="1000" placeholder="0" oninput="calc()" value="<?= htmlspecialchars($_POST['phi_cho_hang']??'') ?>">
                </div>
                <div class="field">
                    <label>Chi phí phát sinh khác (₫)</label>
                    <input type="number" name="phi_phat_sinh" id="phi_phat_sinh" min="0" step="1000" placeholder="0" oninput="calc()" value="<?= htmlspecialchars($_POST['phi_phat_sinh']??'') ?>">
                </div>

                <div class="calc-box">
                    <div class="calc-row"><span>Tổng chi phí</span><span id="sh_cp">₫ 0</span></div>
                    <div class="calc-row"><span>Doanh thu</span><span id="sh_dt">₫ 0</span></div>
                    <div class="calc-row total">
                        <span>Lợi nhuận</span>
                        <span id="sh_ln" style="color:var(--green)">₫ 0</span>
                    </div>
                </div>
            </div>
            <div class="form-actions" style="margin-top:18px">
                <a href="dieuphoI_don_hang.php" class="btn btn-ghost">Hủy</a>
                <button type="submit" class="btn btn-primary">💾 Lưu Đơn</button>
            </div>
        </div>

    </div>
    </form>
</div>
</main>
</div>

<script>

const tuyenData = <?= json_encode($tuyen_json) ?>;

function chonTuyen(sel){
    const id  = sel.value;
    const box = document.getElementById('tuyen_info');
    const gia = document.getElementById('gia_cuoc');
    if (id && tuyenData[id]) {
        const t = tuyenData[id];
        box.style.display = 'block';
        box.innerHTML = `🗺️ <strong>${t.ten_tuyen}</strong> — ${t.khoang_cach} km · ⏱️ ~${t.thoi_gian} giờ · Giá cơ bản: <strong>₫${parseInt(t.gia_co_ban).toLocaleString('vi-VN')}</strong>`;
        gia.value = t.gia_co_ban;
        calc();
    } else {
        box.style.display = 'none';
        gia.value = '';
        calc();
    }
}

function fmt(n){ return '₫ '+n.toLocaleString('vi-VN'); }
function g(id){ return parseFloat(document.getElementById(id).value)||0; }
function calc(){
    const cp = g('gia_cuoc')+g('phi_cao_toc')+g('phi_boc_xep')+g('phi_cho_hang')+g('phi_phat_sinh');
    const dt = g('doanh_thu');
    const ln = dt - cp;
    document.getElementById('sh_cp').textContent = fmt(cp);
    document.getElementById('sh_dt').textContent = fmt(dt);
    const el = document.getElementById('sh_ln');
    el.textContent = fmt(ln);
    el.style.color = ln>=0 ? '#1e8449' : '#e74c3c';
}
calc();
</script>
</body></html>
