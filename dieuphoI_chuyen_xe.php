<?php
session_start(); require 'config.php'; require_role(['dieuphoI']);
$full_name = $_SESSION['full_name'] ?? 'Điều phối viên';
$msg = '';

// ================================================================
// HÀM TỰ ĐỘNG TÍNH CHI PHÍ NHIÊN LIỆU
// Công thức: km_thuc_te / muc_tieu_thu * don_gia_nhien_lieu
// Mặc định: 10L/100km, 25.000đ/lít
// ================================================================
function tinh_chi_phi_nhien_lieu($conn, $xe_id, $km_thuc_te) {
    $r = $conn->query("SELECT muc_tieu_thu FROM xe WHERE id=$xe_id LIMIT 1");
    $xe = $r ? $r->fetch_assoc() : null;
    $muc_tieu_thu = ($xe && $xe['muc_tieu_thu'] > 0) ? $xe['muc_tieu_thu'] : 10; // 10L/100km
    $don_gia_nl   = 25000; // đồng/lít (có thể đưa vào system_settings)
    $lit = ($km_thuc_te / 100) * $muc_tieu_thu;
    return round($lit * $don_gia_nl);
}

// ================================================================
// XỬ LÝ CẬP NHẬT CHUYẾN XE — CÓ TỰ ĐỘNG TÍNH CHI PHÍ
// ================================================================
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['cap_nhat_chuyen'])) {
    $id          = (int)$_POST['cx_id'];
    $km_kt       = (int)$_POST['km_ket_thuc'];
    $nhien_lieu  = (float)$_POST['nhien_lieu'];
    $chi_phi     = (float)$_POST['chi_phi_duong'];
    $trang_thai  = $_POST['trang_thai_cx'];
    $ghi_chu     = trim($_POST['ghi_chu_cx'] ?? '');
    $thoi_gian_den = $trang_thai === 'hoan_thanh' ? 'NOW()' : 'NULL';

    // Lấy thông tin chuyến (xe_id, km_bat_dau, don_hang_id)
    $cx_info = $conn->query("SELECT xe_id, km_bat_dau, don_hang_id FROM chuyen_xe WHERE id=$id")->fetch_assoc();
    $km_thuc_te = $cx_info ? max(0, $km_kt - $cx_info['km_bat_dau']) : 0;

    // ---- AUTO TÍNH NHIÊN LIỆU nếu người dùng không nhập ----
    if ($nhien_lieu <= 0 && $km_thuc_te > 0 && $cx_info) {
        $r_xe = $conn->query("SELECT muc_tieu_thu FROM xe WHERE id={$cx_info['xe_id']} LIMIT 1")->fetch_assoc();
        $muc  = ($r_xe && $r_xe['muc_tieu_thu'] > 0) ? $r_xe['muc_tieu_thu'] : 10;
        $nhien_lieu = round(($km_thuc_te / 100) * $muc, 1);
    }

    $conn->query("UPDATE chuyen_xe
                  SET km_ket_thuc=$km_kt, km_thuc_te=$km_thuc_te,
                      nhien_lieu=$nhien_lieu, chi_phi_duong=$chi_phi,
                      trang_thai='$trang_thai',
                      ghi_chu_cx='".mysqli_real_escape_string($conn,$ghi_chu)."',
                      thoi_gian_den=$thoi_gian_den
                  WHERE id=$id");

    // ---- KHI HOÀN THÀNH: cập nhật xe, đơn hàng, tính lợi nhuận ----
    if ($trang_thai === 'hoan_thanh' && $cx_info) {
        // Cập nhật km xe
        $conn->query("UPDATE xe SET km_hien_tai=km_hien_tai+$km_thuc_te,
                      tinh_trang='san_sang' WHERE id={$cx_info['xe_id']}");

        // Giải phóng tài xế
        $conn->query("UPDATE tai_xe SET tinh_trang='san_sang'
                      WHERE id=(SELECT tai_xe_id FROM chuyen_xe WHERE id=$id LIMIT 1)");

        // Auto tính chi phí nhiên liệu = L * 25000
        $chi_phi_nl = round($nhien_lieu * 25000);

        // Cập nhật đơn hàng: trạng thái + chi phí thực tế + lợi nhuận
        $don_id = $cx_info['don_hang_id'];
        $don    = $conn->query("SELECT doanh_thu, phi_cao_toc, phi_boc_xep, phi_cho_hang, phi_phat_sinh
                                FROM don_hang WHERE id=$don_id LIMIT 1")->fetch_assoc();
        if ($don) {
            $tong_cp = $chi_phi_nl + $chi_phi
                     + ($don['phi_cao_toc'] ?? 0) + ($don['phi_boc_xep'] ?? 0)
                     + ($don['phi_cho_hang'] ?? 0) + ($don['phi_phat_sinh'] ?? 0);
            $loi_nhuan = $don['doanh_thu'] - $tong_cp;

            $conn->query("UPDATE don_hang
                          SET trang_thai='da_giao',
                              ngay_giao_thuc_te=NOW(),
                              tong_chi_phi=$tong_cp,
                              loi_nhuan=$loi_nhuan
                          WHERE id=$don_id");
        }
        $msg = ['type'=>'success','text'=>"✅ Hoàn thành chuyến! Km thực tế: <b>{$km_thuc_te} km</b> · Nhiên liệu: <b>{$nhien_lieu} L</b> · Chi phí NL tự tính: <b>₫".number_format($chi_phi_nl)."</b>"];
    } else {
        $msg = ['type'=>'success','text'=>'Đã cập nhật chuyến xe!'];
    }
}


if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['tao_chuyen'])) {
    $don_id   = (int)$_POST['don_id'];
    $km_bd    = (int)$_POST['km_bat_dau'];
    $tg_xuat  = $_POST['thoi_gian_xuat'] ?? null;

    $don = $conn->query("SELECT xe_id,tai_xe_id FROM don_hang WHERE id=$don_id AND xe_id IS NOT NULL")->fetch_assoc();
    if ($don) {
        $stmt = $conn->prepare("INSERT INTO chuyen_xe (don_hang_id,xe_id,tai_xe_id,km_bat_dau,thoi_gian_xuat,trang_thai) VALUES(?,?,?,?,?,'dang_di')");
        $stmt->bind_param("iiiis",$don_id,$don['xe_id'],$don['tai_xe_id'],$km_bd,$tg_xuat);
        if ($stmt->execute()) {
            $conn->query("UPDATE don_hang SET trang_thai='dang_van_chuyen' WHERE id=$don_id");
            $conn->query("UPDATE xe SET tinh_trang='dang_chay' WHERE id={$don['xe_id']}");
            $msg=['type'=>'success','text'=>'Đã tạo chuyến xe và xuất phát!'];
        }
        $stmt->close();
    } else {
        $msg=['type'=>'danger','text'=>'Đơn hàng chưa được phân xe hoặc không tồn tại!'];
    }
}


$filter = $_GET['filter'] ?? 'all';
$where  = "WHERE 1=1";
if ($filter==='dang_di')    $where .= " AND cx.trang_thai='dang_di'";
if ($filter==='hoan_thanh') $where .= " AND cx.trang_thai='hoan_thanh'";
if ($filter==='hom_nay')    $where .= " AND DATE(cx.created_at)=CURDATE()";

$rows = $conn->query(
    "SELECT cx.*,
            dh.ma_don,dh.ten_khach,dh.tinh_lay,dh.tinh_giao,
            x.bien_so,x.nhan_hieu,
            tx.ho_ten AS ten_tai_xe,tx.so_dien_thoai
     FROM chuyen_xe cx
     JOIN don_hang dh ON cx.don_hang_id=dh.id
     JOIN xe x        ON cx.xe_id=x.id
     JOIN tai_xe tx   ON cx.tai_xe_id=tx.id
     $where ORDER BY cx.created_at DESC LIMIT 50"
);


$don_cho_xuat = $conn->query(
    "SELECT dh.id,dh.ma_don,dh.ten_khach,dh.tinh_lay,dh.tinh_giao,x.bien_so,tx.ho_ten
     FROM don_hang dh
     JOIN xe x ON dh.xe_id=x.id
     JOIN tai_xe tx ON dh.tai_xe_id=tx.id
     WHERE dh.trang_thai IN ('dang_xu_ly','dang_lay_hang')
     AND dh.id NOT IN (SELECT don_hang_id FROM chuyen_xe WHERE trang_thai!='hoan_thanh')
     ORDER BY dh.ngay_lay_hang ASC"
);


$dang_di   = $conn->query("SELECT COUNT(*) AS c FROM chuyen_xe WHERE trang_thai='dang_di'")->fetch_assoc()['c']??0;
$hom_nay   = $conn->query("SELECT COUNT(*) AS c FROM chuyen_xe WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['c']??0;
$km_thang  = $conn->query("SELECT COALESCE(SUM(km_thuc_te),0) AS t FROM chuyen_xe WHERE MONTH(created_at)=MONTH(CURDATE()) AND trang_thai='hoan_thanh'")->fetch_assoc()['t']??0;
$nl_thang  = $conn->query("SELECT COALESCE(SUM(nhien_lieu),0) AS t FROM chuyen_xe WHERE MONTH(created_at)=MONTH(CURDATE()) AND trang_thai='hoan_thanh'")->fetch_assoc()['t']??0;

$active = 'chuyen_xe'
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Quản Lý Chuyến Xe</title>
<link rel="stylesheet" href="dieuphoI_layout.css">
</head><body>
<div class="wrapper">
<?php require 'sidebar_dieuphoI.php'; ?>

<main class="main">
<div class="topbar">
    <div class="topbar-left"><div class="topbar-title">🛣️ Quản Lý Chuyến Xe</div></div>
    <div class="user-chip">
        <div class="chip-avatar"><?= mb_strtoupper(mb_substr($full_name,0,1)) ?></div>
        <div><div class="chip-name"><?= htmlspecialchars($full_name) ?></div>
        <div class="chip-role">Điều Phối Viên</div></div>
    </div>
</div>
<div class="content">
    <?php if(!empty($msg)): ?><div class="alert alert-<?=$msg['type']?>"><?=$msg['text']?></div><?php endif; ?>

  
    <div class="stat-cards" style="margin-bottom:24px">
        <div class="stat-card" style="border-top-color:#2980b9">
            <div class="sc-icon">🚛</div><div class="sc-label">Đang trên đường</div>
            <div class="sc-value"><?=$dang_di?></div><div class="sc-sub">Chuyến xe</div>
        </div>
        <div class="stat-card" style="border-top-color:#27ae60">
            <div class="sc-icon">📅</div><div class="sc-label">Chuyến hôm nay</div>
            <div class="sc-value"><?=$hom_nay?></div><div class="sc-sub">Tổng chuyến</div>
        </div>
        <div class="stat-card">
            <div class="sc-icon">📍</div><div class="sc-label">Km tháng này</div>
            <div class="sc-value" style="font-size:18px"><?=number_format($km_thang)?></div><div class="sc-sub">Kilomet</div>
        </div>
        <div class="stat-card" style="border-top-color:#e67e22">
            <div class="sc-icon">⛽</div><div class="sc-label">Nhiên liệu tháng</div>
            <div class="sc-value" style="font-size:18px"><?=number_format($nl_thang,0)?></div><div class="sc-sub">Lít</div>
        </div>
    </div>

    <?php if($don_cho_xuat && $don_cho_xuat->num_rows>0): ?>
    <div class="form-card" style="margin-bottom:20px;border-left:4px solid #f39c12">
        <h3 style="font-size:14px;font-weight:700;color:#b7770d;margin-bottom:14px">
            ⏳ Đơn Hàng Chờ Xuất Phát (<?=$don_cho_xuat->num_rows?>)
        </h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px">
        <?php while($d=$don_cho_xuat->fetch_assoc()): ?>
        <div style="background:#fef9e7;border-radius:10px;padding:12px 14px;border:1px solid #fad7a0">
            <div style="font-weight:700;font-size:13px"><?= htmlspecialchars($d['ma_don']) ?></div>
            <div style="font-size:12px;color:var(--muted);margin:4px 0">
                👤 <?= htmlspecialchars($d['ten_khach']) ?><br>
                🚛 <?= htmlspecialchars($d['bien_so']) ?> · 👤 <?= htmlspecialchars($d['ho_ten']) ?><br>
                📍 <?= htmlspecialchars($d['tinh_lay']) ?> → 🏁 <?= htmlspecialchars($d['tinh_giao']) ?>
            </div>
            <button class="btn btn-primary btn-sm" onclick="openTaoChuyen(<?=$d['id']?>,'<?= htmlspecialchars($d['ma_don']) ?>')">
                🚀 Xuất Phát
            </button>
        </div>
        <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="page-header">
        <div style="display:flex;gap:8px">
            <a href="?filter=all"       class="btn <?=$filter==='all'      ?'btn-primary':'btn-ghost'?>">Tất cả</a>
            <a href="?filter=dang_di"   class="btn <?=$filter==='dang_di'  ?'btn-primary':'btn-ghost'?>">🚛 Đang đi (<?=$dang_di?>)</a>
            <a href="?filter=hom_nay"   class="btn <?=$filter==='hom_nay'  ?'btn-primary':'btn-ghost'?>">📅 Hôm nay</a>
            <a href="?filter=hoan_thanh"class="btn <?=$filter==='hoan_thanh'?'btn-primary':'btn-ghost'?>">✅ Hoàn thành</a>
        </div>
    </div>

    <div class="table-wrap">
        <div class="table-scroll">
        <table>
            <thead><tr>
                <th>Đơn hàng</th><th>Xe / Tài xế</th><th>Tuyến</th>
                <th>Km đi</th><th>Km về</th><th>Km thực tế</th>
                <th>Nhiên liệu</th><th>Chi phí đường</th>
                <th>Xuất phát</th><th>Trạng thái</th><th>Thao tác</th>
            </tr></thead>
            <tbody>
            <?php if($rows && $rows->num_rows>0):
                while($r=$rows->fetch_assoc()): ?>
            <tr>
                <td>
                    <div style="font-weight:700;font-size:13px"><?= htmlspecialchars($r['ma_don']) ?></div>
                    <div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($r['ten_khach']) ?></div>
                </td>
                <td style="font-size:12px">
                    <div style="font-weight:700">🚛 <?= htmlspecialchars($r['bien_so']) ?></div>
                    <div style="color:var(--muted)">👤 <?= htmlspecialchars($r['ten_tai_xe']) ?></div>
                    <div style="color:var(--muted)"><?= htmlspecialchars($r['so_dien_thoai']) ?></div>
                </td>
                <td style="font-size:12px">
                    📍 <?= htmlspecialchars($r['tinh_lay']) ?><br>
                    🏁 <?= htmlspecialchars($r['tinh_giao']) ?>
                </td>
                <td style="font-size:13px"><?= number_format($r['km_bat_dau']) ?></td>
                <td style="font-size:13px"><?= $r['km_ket_thuc'] ? number_format($r['km_ket_thuc']) : '—' ?></td>
                <td style="font-weight:700;color:var(--green)"><?= $r['km_ket_thuc'] ? number_format($r['km_thuc_te']).' km' : '—' ?></td>
                <td style="font-size:13px"><?= $r['nhien_lieu'] ? number_format($r['nhien_lieu'],1).' L' : '—' ?></td>
                <td style="font-size:13px"><?= $r['chi_phi_duong'] ? '₫'.number_format($r['chi_phi_duong']) : '—' ?></td>
                <td style="font-size:12px;color:var(--muted)">
                    <?= $r['thoi_gian_xuat'] ? date('d/m H:i',strtotime($r['thoi_gian_xuat'])) : '—' ?>
                </td>
                <td>
                    <?php if($r['trang_thai']==='dang_di'): ?>
                        <span class="badge b-dang_giao">Đang đi</span>
                    <?php elseif($r['trang_thai']==='hoan_thanh'): ?>
                        <span class="badge b-hoan_thanh">Hoàn thành</span>
                    <?php else: ?>
                        <span class="badge b-cho_duyet">Chờ</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($r['trang_thai']==='dang_di'): ?>
                    <button class="btn btn-primary btn-sm" onclick="openCapNhat(<?= htmlspecialchars(json_encode($r)) ?>)">
                        📝 Cập nhật
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="11"><div class="empty-state"><div class="ei">🛣️</div><p>Chưa có chuyến xe nào.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</main>
</div>

<div class="modal-overlay" id="modal_tao">
<div class="modal-box" style="max-width:440px">
    <div class="modal-header">
        <h3>🚀 Xuất Phát Chuyến Xe</h3>
        <button class="modal-close" onclick="document.getElementById('modal_tao').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
        <input type="hidden" name="don_id" id="tao_don_id">
        <div style="background:#eafaf1;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:13px">
            Đơn hàng: <strong id="tao_ma_don"></strong>
        </div>
        <div style="display:flex;flex-direction:column;gap:14px">
            <div class="field">
                <label>Số km hiện tại của xe (km)</label>
                <input type="number" name="km_bat_dau" min="0" placeholder="VD: 125000" required>
            </div>
            <div class="field">
                <label>Thời gian xuất phát</label>
                <input type="datetime-local" name="thoi_gian_xuat">
            </div>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-ghost" onclick="document.getElementById('modal_tao').classList.remove('open')">Hủy</button>
            <button type="submit" name="tao_chuyen" class="btn btn-primary">🚀 Bắt Đầu Chuyến</button>
        </div>
    </form>
</div>
</div>

<div class="modal-overlay" id="modal_capnhat">
<div class="modal-box" style="max-width:480px">
    <div class="modal-header">
        <h3>📝 Cập Nhật Chuyến Xe</h3>
        <button class="modal-close" onclick="document.getElementById('modal_capnhat').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
        <input type="hidden" name="cx_id" id="cn_id">
        <div style="background:#f8f9fa;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:13px">
            Chuyến: <strong id="cn_info"></strong>
        </div>
        <div class="form-grid">
            <div class="field">
                <label>Số km kết thúc</label>
                <input type="number" name="km_ket_thuc" id="cn_km_kt" min="0" placeholder="0">
            </div>
            <div class="field">
                <label>Nhiên liệu tiêu thụ (lít)</label>
                <input type="number" name="nhien_lieu" step="0.1" min="0" placeholder="0">
            </div>
            <div class="field">
                <label>Chi phí BOT / đường (₫)</label>
                <input type="number" name="chi_phi_duong" min="0" step="1000" placeholder="0">
            </div>
            <div class="field">
                <label>Trạng thái chuyến</label>
                <select name="trang_thai_cx">
                    <option value="dang_di">Đang đi</option>
                    <option value="hoan_thanh">✅ Hoàn thành — Đã giao hàng</option>
                </select>
            </div>
            <div class="field span2">
                <label>Ghi chú</label>
                <textarea name="ghi_chu_cx" placeholder="Sự cố, ghi chú dọc đường..."></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-ghost" onclick="document.getElementById('modal_capnhat').classList.remove('open')">Hủy</button>
            <button type="submit" name="cap_nhat_chuyen" class="btn btn-primary">💾 Cập Nhật</button>
        </div>
    </form>
</div>
</div>

<script>
function openTaoChuyen(id, maDon){
    document.getElementById('tao_don_id').value = id;
    document.getElementById('tao_ma_don').textContent = maDon;
    document.getElementById('modal_tao').classList.add('open');
}
function openCapNhat(cx){
    document.getElementById('cn_id').value = cx.id;
    document.getElementById('cn_info').textContent = cx.bien_so+' · '+cx.ma_don;
    document.getElementById('cn_km_kt').value = cx.km_ket_thuc||'';
    document.getElementById('modal_capnhat').classList.add('open');
}
document.querySelectorAll('.modal-overlay').forEach(m=>{
    m.addEventListener('click',e=>{ if(e.target===m) m.classList.remove('open'); });
});
</script>
</body></html>
