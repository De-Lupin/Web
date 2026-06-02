<?php
session_start(); require 'config.php'; require_role(['dieuphoI']);
$full_name = $_SESSION['full_name'] ?? 'Điều phối viên';
$msg = '';

// ================================================================
// HÀM TỰ ĐỘNG GỢI Ý XE PHÙ HỢP
// Logic: chọn xe sẵn sàng có tải trọng >= trọng lượng đơn,
//        ưu tiên xe nhỏ nhất vừa đủ để tiết kiệm chi phí
// ================================================================
function auto_suggest_xe($conn, $trong_luong, $loai_van_chuyen) {
    $trong_luong = (float)$trong_luong;

    // Ưu tiên loại xe theo loại vận chuyển
    $loai_uu_tien = '';
    if ($loai_van_chuyen === 'hang_dong_lanh')    $loai_uu_tien = " AND loai_xe='xe_dong_lanh'";
    elseif ($loai_van_chuyen === 'hang_sieu_truong') $loai_uu_tien = " AND loai_xe IN ('dau_keo','xe_tai_nang')";

    // Tìm xe phù hợp nhất
    $sql = "SELECT id, bien_so, loai_xe, tai_trong, nhan_hieu
            FROM xe
            WHERE tinh_trang = 'san_sang'
              AND (tai_trong >= $trong_luong OR $trong_luong = 0)
              $loai_uu_tien
            ORDER BY tai_trong ASC
            LIMIT 1";
    $r = $conn->query($sql);
    if ($r && $r->num_rows > 0) return $r->fetch_assoc();

    // Fallback: bỏ điều kiện loại xe nếu không tìm được
    if ($loai_uu_tien) {
        $r2 = $conn->query("SELECT id,bien_so,loai_xe,tai_trong,nhan_hieu FROM xe
                            WHERE tinh_trang='san_sang' AND (tai_trong>=$trong_luong OR $trong_luong=0)
                            ORDER BY tai_trong ASC LIMIT 1");
        if ($r2 && $r2->num_rows > 0) return $r2->fetch_assoc();
    }
    return null;
}

// ================================================================
// HÀM TỰ ĐỘNG GỢI Ý TÀI XẾ PHÙ HỢP
// Logic: chọn tài xế sẵn sàng, hạng GPLX còn hạn,
//        ưu tiên tài xế nhiều kinh nghiệm nhất
// ================================================================
function auto_suggest_taixe($conn, $loai_xe_id) {
    // Lấy loại xe để chọn hạng GPLX phù hợp
    $loai_xe = '';
    if ($loai_xe_id) {
        $r = $conn->query("SELECT loai_xe FROM xe WHERE id=$loai_xe_id LIMIT 1");
        if ($r) $loai_xe = $r->fetch_assoc()['loai_xe'] ?? '';
    }

    // Hạng GPLX tối thiểu theo loại xe
    $hang_ok = "'C','D','E','FC'";
    if (in_array($loai_xe, ['dau_keo','xe_tai_nang','xe_sieu_truong']))
        $hang_ok = "'E','FC'";
    elseif ($loai_xe === 'xe_dong_lanh')
        $hang_ok = "'D','E','FC'";

    $r = $conn->query("SELECT id,ho_ten,so_dien_thoai,hang_gplx,kinh_nghiem
                       FROM tai_xe
                       WHERE tinh_trang='san_sang'
                         AND hang_gplx IN ($hang_ok)
                         AND (han_gplx IS NULL OR han_gplx > CURDATE())
                       ORDER BY kinh_nghiem DESC
                       LIMIT 1");
    if ($r && $r->num_rows > 0) return $r->fetch_assoc();

    // Fallback: bất kỳ tài xế sẵn sàng nào
    $r2 = $conn->query("SELECT id,ho_ten,so_dien_thoai,hang_gplx,kinh_nghiem
                        FROM tai_xe WHERE tinh_trang='san_sang'
                        ORDER BY kinh_nghiem DESC LIMIT 1");
    return ($r2 && $r2->num_rows > 0) ? $r2->fetch_assoc() : null;
}

// ================================================================
// XỬ LÝ AUTO PHÂN XE (1 CLICK)
// ================================================================
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['auto_phan'])) {
    $don_id = (int)$_POST['don_id'];

    // Lấy thông tin đơn
    $don_info = $conn->query("SELECT trong_luong, loai_van_chuyen
                              FROM don_hang WHERE id=$don_id LIMIT 1")->fetch_assoc();

    $xe_goi_y    = auto_suggest_xe($conn, $don_info['trong_luong'] ?? 0, $don_info['loai_van_chuyen'] ?? '');
    $taixe_goi_y = auto_suggest_taixe($conn, $xe_goi_y['id'] ?? null);

    if ($xe_goi_y && $taixe_goi_y) {
        $xe_id    = $xe_goi_y['id'];
        $taixe_id = $taixe_goi_y['id'];

        $conn->query("UPDATE don_hang SET xe_id=$xe_id, tai_xe_id=$taixe_id,
                      trang_thai='dang_xu_ly' WHERE id=$don_id AND trang_thai='cho_duyet'");
        if ($conn->affected_rows > 0) {
            $conn->query("UPDATE xe SET tinh_trang='dang_chay' WHERE id=$xe_id");
            $conn->query("UPDATE tai_xe SET tinh_trang='dang_chay' WHERE id=$taixe_id");
            $msg = ['type'=>'success','text'=>"✅ Đã tự động phân: Xe <b>{$xe_goi_y['bien_so']}</b> — Tài xế <b>{$taixe_goi_y['ho_ten']}</b>"];
        } else {
            $msg = ['type'=>'warning','text'=>'⚠️ Đơn đã được xử lý trước đó.'];
        }
    } elseif (!$xe_goi_y) {
        $msg = ['type'=>'danger','text'=>'❌ Không tìm được xe phù hợp đang sẵn sàng!'];
    } else {
        $msg = ['type'=>'danger','text'=>'❌ Không tìm được tài xế đang sẵn sàng!'];
    }
}

// ================================================================
// AUTO PHÂN TẤT CẢ ĐƠN CHỜ
// ================================================================
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['auto_phan_tat_ca'])) {
    $don_cho_ids = $conn->query(
        "SELECT id, trong_luong, loai_van_chuyen FROM don_hang WHERE trang_thai='cho_duyet'"
    );
    $so_thanh_cong = 0;
    $so_that_bai   = 0;
    while ($don = $don_cho_ids->fetch_assoc()) {
        $xe_goi_y    = auto_suggest_xe($conn, $don['trong_luong'] ?? 0, $don['loai_van_chuyen'] ?? '');
        $taixe_goi_y = auto_suggest_taixe($conn, $xe_goi_y['id'] ?? null);
        if ($xe_goi_y && $taixe_goi_y) {
            $conn->query("UPDATE don_hang SET xe_id={$xe_goi_y['id']}, tai_xe_id={$taixe_goi_y['id']},
                          trang_thai='dang_xu_ly' WHERE id={$don['id']} AND trang_thai='cho_duyet'");
            if ($conn->affected_rows > 0) {
                $conn->query("UPDATE xe SET tinh_trang='dang_chay' WHERE id={$xe_goi_y['id']}");
                $conn->query("UPDATE tai_xe SET tinh_trang='dang_chay' WHERE id={$taixe_goi_y['id']}");
                $so_thanh_cong++;
            }
        } else {
            $so_that_bai++;
        }
    }
    if ($so_that_bai === 0)
        $msg = ['type'=>'success','text'=>"✅ Đã tự động phân xe cho <b>$so_thanh_cong</b> đơn hàng!"];
    else
        $msg = ['type'=>'warning','text'=>"⚡ Phân được <b>$so_thanh_cong</b> đơn. Còn <b>$so_that_bai</b> đơn không đủ xe/tài xế sẵn sàng."];
}

// Xử lý phân xe + tài xế cho đơn hàng
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['phan_xe'])) {
    $don_id    = (int)$_POST['don_id'];
    $xe_id     = (int)$_POST['xe_id']    ?: null;
    $taixe_id  = (int)$_POST['taixe_id'] ?: null;
    $tt_moi    = 'dang_xu_ly';

    $conn->query("UPDATE don_hang SET xe_id=".($xe_id?"$xe_id":"NULL").",
                  tai_xe_id=".($taixe_id?"$taixe_id":"NULL").",
                  trang_thai='$tt_moi'
                  WHERE id=$don_id AND trang_thai='cho_duyet'");
    if ($conn->affected_rows > 0) {
        if ($xe_id)    $conn->query("UPDATE xe SET tinh_trang='dang_chay' WHERE id=$xe_id");
        if ($taixe_id) $conn->query("UPDATE tai_xe SET tinh_trang='dang_chay' WHERE id=$taixe_id");
        $msg = ['type'=>'success','text'=>'✅ Đã phân xe và tài xế thành công!'];
    } else {
        $msg = ['type'=>'warning','text'=>'⚠️ Không thể phân xe — đơn đã được xử lý hoặc không tồn tại.'];
    }
}

// Xử lý cập nhật trạng thái đơn
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['cap_nhat_tt'])) {
    $don_id = (int)$_POST['don_id'];
    $tt     = $_POST['trang_thai'];
    $conn->query("UPDATE don_hang SET trang_thai='".mysqli_real_escape_string($conn,$tt)."' WHERE id=$don_id");
    $msg = ['type'=>'success','text'=>'✅ Đã cập nhật trạng thái đơn hàng!'];
}

// Lấy danh sách đơn chờ phân xe
$don_cho = $conn->query(
    "SELECT dh.id,dh.ma_don,dh.ten_khach,dh.tinh_lay,dh.tinh_giao,
            dh.loai_van_chuyen,dh.trong_luong,dh.ngay_lay_hang,
            dh.trang_thai,dh.ngay_tao,
            x.bien_so, tx.ho_ten AS ten_tai_xe
     FROM don_hang dh
     LEFT JOIN xe x ON dh.xe_id=x.id
     LEFT JOIN tai_xe tx ON dh.tai_xe_id=tx.id
     WHERE dh.trang_thai IN ('cho_duyet','dang_xu_ly')
     ORDER BY dh.ngay_tao ASC"
);

// Xe sẵn sàng
$ds_xe = $conn->query(
    "SELECT id,bien_so,loai_xe,tai_trong,nhan_hieu FROM xe
     WHERE tinh_trang='san_sang' ORDER BY bien_so"
);

// Tài xế sẵn sàng
$ds_taixe = $conn->query(
    "SELECT id,ho_ten,so_dien_thoai,hang_gplx FROM tai_xe
     WHERE tinh_trang='san_sang' ORDER BY ho_ten"
);

// Thống kê nhanh
$so_cho    = $conn->query("SELECT COUNT(*) AS c FROM don_hang WHERE trang_thai='cho_duyet'")->fetch_assoc()['c']??0;
$so_xu_ly  = $conn->query("SELECT COUNT(*) AS c FROM don_hang WHERE trang_thai='dang_xu_ly'")->fetch_assoc()['c']??0;
$xe_trong  = $conn->query("SELECT COUNT(*) AS c FROM xe WHERE tinh_trang='san_sang'")->fetch_assoc()['c']??0;
$taixe_trong=$conn->query("SELECT COUNT(*) AS c FROM tai_xe WHERE tinh_trang='san_sang'")->fetch_assoc()['c']??0;

$tt_map = [
    'cho_duyet'      =>['l'=>'Chờ duyệt',  'c'=>'b-cho_duyet'],
    'dang_xu_ly'     =>['l'=>'Đang xử lý', 'c'=>'b-dang_xu_ly'],
    'dang_lay_hang'  =>['l'=>'Lấy hàng',   'c'=>'b-dang_lay_rong'],
    'dang_van_chuyen'=>['l'=>'Đang chạy',  'c'=>'b-dang_giao'],
];
$loai_xe_map=['xe_tai_nhe'=>'Tải nhẹ','xe_tai_trung'=>'Tải trung','xe_tai_nang'=>'Tải nặng','dau_keo'=>'Đầu kéo','xe_dong_lanh'=>'Đông lạnh','xe_chuyen_dung'=>'Chuyên dụng'];

$active = 'phan_xe'
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Phân Xe / Phân Ca</title>
<link rel="stylesheet" href="dieuphoI_layout.css">
<style>
.don-card{background:#fff;border-radius:12px;padding:18px 20px;box-shadow:0 2px 8px rgba(0,0,0,.06);border-left:4px solid var(--green);margin-bottom:16px;transition:.2s}
.don-card:hover{box-shadow:0 6px 18px rgba(0,0,0,.1)}
.don-card.urgent{border-left-color:#e74c3c}
.don-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.don-ma{font-size:15px;font-weight:800;color:var(--text)}
.don-info{display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:12px;color:var(--muted);margin-bottom:14px}
.don-info span strong{color:var(--text)}
.phan-form{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
.phan-form select{flex:1;min-width:180px;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none}
.phan-form select:focus{border-color:var(--green)}
.section-title{font-size:16px;font-weight:700;color:var(--text);margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid var(--green-light);display:flex;align-items:center;gap:8px}
.suggest-badge{background:#eafaf1;border:1px solid #a9dfbf;border-radius:8px;padding:7px 12px;font-size:12px;color:#1e8449;display:flex;align-items:center;gap:6px;margin-bottom:10px}
.suggest-badge strong{color:#1e8449}
.btn-auto{background:linear-gradient(135deg,#27ae60,#1e8449);color:#fff;border:none;border-radius:8px;padding:9px 16px;font-size:13px;font-weight:700;font-family:inherit;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:.2s}
.btn-auto:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(39,174,96,.35)}
.btn-auto-all{background:linear-gradient(135deg,#2980b9,#1a5276);color:#fff;border:none;border-radius:8px;padding:10px 20px;font-size:14px;font-weight:700;font-family:inherit;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:.2s}
.btn-auto-all:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(41,128,185,.4)}
</style>
</head><body>
<div class="wrapper">
<?php require 'sidebar_dieuphoI.php'; ?>
<main class="main">
<div class="topbar">
    <div class="topbar-left">
        <div class="topbar-title">🚛 Phân Xe / Phân Ca</div>
        <div style="font-size:12px;color:var(--muted)">Gán xe và tài xế cho đơn hàng chờ xử lý</div>
    </div>
    <div class="user-chip">
        <div class="chip-avatar"><?= mb_strtoupper(mb_substr($full_name,0,1)) ?></div>
        <div><div class="chip-name"><?= htmlspecialchars($full_name) ?></div>
        <div class="chip-role">Điều Phối Viên</div></div>
    </div>
</div>
<div class="content">
    <?php if(!empty($msg)): ?><div class="alert alert-<?=$msg['type']?>"><?=$msg['text']?></div><?php endif; ?>

    <!-- Thống kê nhanh -->
    <div class="stat-cards" style="margin-bottom:24px">
        <div class="stat-card" style="border-top-color:#f39c12">
            <div class="sc-icon">🟡</div><div class="sc-label">Chờ phân xe</div>
            <div class="sc-value" style="color:#f39c12"><?= $so_cho ?></div><div class="sc-sub">Đơn hàng</div>
        </div>
        <div class="stat-card" style="border-top-color:#2980b9">
            <div class="sc-icon">⚙️</div><div class="sc-label">Đang xử lý</div>
            <div class="sc-value" style="color:#2980b9"><?= $so_xu_ly ?></div><div class="sc-sub">Đã phân xe</div>
        </div>
        <div class="stat-card">
            <div class="sc-icon">🚛</div><div class="sc-label">Xe sẵn sàng</div>
            <div class="sc-value"><?= $xe_trong ?></div><div class="sc-sub">Phương tiện</div>
        </div>
        <div class="stat-card" style="border-top-color:#8e44ad">
            <div class="sc-icon">👤</div><div class="sc-label">Tài xế sẵn sàng</div>
            <div class="sc-value" style="color:#8e44ad"><?= $taixe_trong ?></div><div class="sc-sub">Người</div>
        </div>
    </div>

    <?php if($don_cho && $don_cho->num_rows > 0): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px">
        <div class="section-title" style="margin:0">📋 Đơn Hàng Cần Phân Xe (<?= $don_cho->num_rows ?>)</div>
        <form method="POST" onsubmit="return confirm('Hệ thống sẽ tự động gán xe + tài xế cho TẤT CẢ đơn đang chờ. Tiếp tục?')">
            <input type="hidden" name="auto_phan_tat_ca" value="1">
            <button type="submit" class="btn-auto-all">⚡ Tự Động Phân Tất Cả</button>
        </form>
    </div>

    <?php while($d = $don_cho->fetch_assoc()):
        $is_urgent = strtotime($d['ngay_lay_hang']) && (strtotime($d['ngay_lay_hang']) - time()) < 86400;
    ?>
    <div class="don-card <?= $is_urgent ? 'urgent' : '' ?>">
        <div class="don-head">
            <div>
                <div class="don-ma">
                    <?= $is_urgent ? '🔴' : '🟡' ?>
                    <?= htmlspecialchars($d['ma_don']) ?>
                    <?php if($is_urgent): ?><span style="font-size:11px;color:#e74c3c;font-weight:600;margin-left:8px">⚡ KHẨN</span><?php endif; ?>
                </div>
                <div style="font-size:13px;color:var(--muted);margin-top:3px">
                    👤 <?= htmlspecialchars($d['ten_khach']) ?> · Tạo: <?= date('d/m/Y H:i', strtotime($d['ngay_tao'])) ?>
                </div>
            </div>
            <span class="badge <?= $tt_map[$d['trang_thai']]['c']??'' ?>"><?= $tt_map[$d['trang_thai']]['l']??$d['trang_thai'] ?></span>
        </div>

        <div class="don-info">
            <span>📍 Lấy hàng: <strong><?= htmlspecialchars($d['tinh_lay']) ?></strong></span>
            <span>🏁 Giao hàng: <strong><?= htmlspecialchars($d['tinh_giao']) ?></strong></span>
            <span>📦 Loại: <strong><?= htmlspecialchars($d['loai_van_chuyen']) ?></strong></span>
            <span>⚖️ Trọng lượng: <strong><?= $d['trong_luong'] ? $d['trong_luong'].'T' : '—' ?></strong></span>
            <?php if($d['ngay_lay_hang']): ?>
            <span>📅 Ngày lấy hàng: <strong style="color:<?= $is_urgent?'#e74c3c':'inherit' ?>"><?= date('d/m/Y H:i', strtotime($d['ngay_lay_hang'])) ?></strong></span>
            <?php endif; ?>
            <?php if($d['bien_so']): ?>
            <span>🚛 Xe hiện tại: <strong style="color:var(--green)"><?= htmlspecialchars($d['bien_so']) ?></strong></span>
            <?php endif; ?>
            <?php if($d['ten_tai_xe']): ?>
            <span>👤 Tài xế: <strong style="color:var(--green)"><?= htmlspecialchars($d['ten_tai_xe']) ?></strong></span>
            <?php endif; ?>
        </div>

        <?php
        // Tính toán gợi ý cho đơn này
        if ($d['trang_thai'] === 'cho_duyet') {
            $xe_goi_y    = auto_suggest_xe($conn, $d['trong_luong'] ?? 0, $d['loai_van_chuyen'] ?? '');
            $taixe_goi_y = auto_suggest_taixe($conn, $xe_goi_y['id'] ?? null);
        } else {
            $xe_goi_y = $taixe_goi_y = null;
        }
        ?>

        <?php if($xe_goi_y || $taixe_goi_y): ?>
        <div class="suggest-badge">
            🤖 <strong>Gợi ý tự động:</strong>
            <?php if($xe_goi_y): ?>
                🚛 <strong><?= htmlspecialchars($xe_goi_y['bien_so']) ?></strong>
                (<?= $loai_xe_map[$xe_goi_y['loai_xe']]??$xe_goi_y['loai_xe'] ?> · <?= $xe_goi_y['tai_trong'] ?>T)
            <?php endif; ?>
            <?php if($taixe_goi_y): ?>
                &nbsp;·&nbsp; 👤 <strong><?= htmlspecialchars($taixe_goi_y['ho_ten']) ?></strong>
                (<?= $taixe_goi_y['hang_gplx'] ?> · <?= $taixe_goi_y['kinh_nghiem'] ?> năm KN)
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-start">

        <?php if($d['trang_thai'] === 'cho_duyet' && $xe_goi_y && $taixe_goi_y): ?>
        <form method="POST">
            <input type="hidden" name="don_id" value="<?= $d['id'] ?>">
            <button type="submit" name="auto_phan" class="btn-auto">⚡ Tự Động Phân</button>
        </form>
        <?php endif; ?>

        <form method="POST" class="phan-form" style="flex:1">
            <input type="hidden" name="don_id" value="<?= $d['id'] ?>">

            <select name="xe_id" id="xe_<?= $d['id'] ?>">
                <option value="">🚛 — Chọn xe thủ công —</option>
                <?php $ds_xe->data_seek(0); while($xe=$ds_xe->fetch_assoc()): ?>
                <option value="<?= $xe['id'] ?>"
                    <?= ($d['xe_id']==$xe['id'] || ($xe_goi_y && $xe_goi_y['id']==$xe['id'] && !$d['xe_id']))?'selected':'' ?>>
                    <?= htmlspecialchars($xe['bien_so']) ?>
                    (<?= $loai_xe_map[$xe['loai_xe']]??$xe['loai_xe'] ?> · <?= $xe['tai_trong'] ?>T)
                </option>
                <?php endwhile; ?>
            </select>

            <select name="taixe_id" id="tx_<?= $d['id'] ?>">
                <option value="">👤 — Chọn tài xế thủ công —</option>
                <?php $ds_taixe->data_seek(0); while($tx=$ds_taixe->fetch_assoc()): ?>
                <option value="<?= $tx['id'] ?>"
                    <?= ($d['tai_xe_id']==$tx['id'] || ($taixe_goi_y && $taixe_goi_y['id']==$tx['id'] && !$d['tai_xe_id']))?'selected':'' ?>>
                    <?= htmlspecialchars($tx['ho_ten']) ?>
                    (<?= $tx['hang_gplx'] ?> · <?= $tx['so_dien_thoai'] ?>)
                </option>
                <?php endwhile; ?>
            </select>

            <button type="submit" name="phan_xe" class="btn btn-primary">✅ Xác nhận</button>

            <?php if($d['trang_thai']==='dang_xu_ly'): ?>
            <button type="submit" name="cap_nhat_tt" class="btn btn-warning"
                onclick="document.getElementById('_tt_<?=$d['id']?>').value='dang_lay_hang'">
                📦 Chuyển → Lấy hàng
            </button>
            <input type="hidden" name="trang_thai" id="_tt_<?=$d['id']?>" value="dang_lay_hang">
            <?php endif; ?>
        </form>

        </div>
    </div>
    <?php endwhile; ?>

    <?php else: ?>
    <div class="empty-state" style="margin-top:40px">
        <div class="ei">✅</div>
        <p>Không có đơn hàng nào đang chờ phân xe!</p>
        <a href="dieuphoI_tao_don.php" class="btn btn-primary" style="margin-top:16px">➕ Tạo đơn mới</a>
    </div>
    <?php endif; ?>

    <!-- Bảng tổng quan xe đang hoạt động -->
    <div style="margin-top:32px">
    <div class="section-title">🚛 Tình Trạng Xe Hiện Tại</div>
    <div class="table-wrap">
        <div class="table-scroll">
        <table>
            <thead><tr><th>Biển số</th><th>Loại xe</th><th>Tải trọng</th><th>Trạng thái</th><th>Đơn hàng</th></tr></thead>
            <tbody>
            <?php
            $all_xe = $conn->query(
                "SELECT x.*, dh.ma_don, dh.ten_khach
                 FROM xe x
                 LEFT JOIN don_hang dh ON dh.xe_id=x.id AND dh.trang_thai IN ('dang_xu_ly','dang_lay_hang','dang_van_chuyen')
                 WHERE x.tinh_trang != 'nghi'
                 ORDER BY x.tinh_trang='dang_chay' DESC, x.bien_so"
            );
            while($x=$all_xe->fetch_assoc()):
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($x['bien_so']) ?></strong><div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($x['nhan_hieu']??'') ?></div></td>
                <td><?= $loai_xe_map[$x['loai_xe']]??$x['loai_xe'] ?></td>
                <td><?= $x['tai_trong'] ?>T</td>
                <td><span class="badge b-<?= $x['tinh_trang'] ?>"><?= $x['tinh_trang'] ?></span></td>
                <td style="font-size:12px"><?= $x['ma_don'] ? htmlspecialchars($x['ma_don'].' — '.$x['ten_khach']) : '—' ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
    </div>

</div>
</main>
</div>
</body></html>