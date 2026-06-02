<?php
// ============================================================
// dieuphoI_lo_trinh.php - Cập nhật lộ trình + Tự động thông báo
// ============================================================
session_start();
require 'config.php';
require_role(['dieuphoI']);

$msg = null;
$uid = $_SESSION['user_id'];
$search = trim($_GET['search'] ?? '');
$don_id = (int)($_GET['don_id'] ?? 0);
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 10; $offset = ($page - 1) * $per;

$su_kien_map = [
    'tao_don'         => ['label' => 'Đơn được tạo',               'icon' => '📝', 'color' => '#64748b'],
    'duyet_don'       => ['label' => 'Đơn đã được duyệt',          'icon' => '✅', 'color' => '#2563eb'],
    'lay_hang'        => ['label' => 'Đang lấy hàng',              'icon' => '📦', 'color' => '#f59e0b'],
    'den_kho'         => ['label' => 'Hàng đến kho trung chuyển',  'icon' => '🏭', 'color' => '#8b5cf6'],
    'roi_kho'         => ['label' => 'Hàng rời kho',               'icon' => '🚛', 'color' => '#2563eb'],
    'dang_van_chuyen' => ['label' => 'Đang vận chuyển',            'icon' => '🛣️', 'color' => '#3b82f6'],
    'den_kho_dich'    => ['label' => 'Đến kho đích',               'icon' => '📍', 'color' => '#10b981'],
    'dang_giao'       => ['label' => 'Đang giao hàng',             'icon' => '🏃', 'color' => '#f97316'],
    'da_giao'         => ['label' => 'Giao hàng thành công',       'icon' => '🎉', 'color' => '#10b981'],
    'that_bai'        => ['label' => 'Giao thất bại - thử lại',    'icon' => '⚠️', 'color' => '#ef4444'],
    'hoan_hang'       => ['label' => 'Hoàn hàng về kho',           'icon' => '↩️', 'color' => '#ef4444'],
];

$su_kien_to_tt = [
    'lay_hang'        => 'dang_lay_hang',
    'den_kho'         => 'dang_van_chuyen',
    'roi_kho'         => 'dang_van_chuyen',
    'dang_van_chuyen' => 'dang_van_chuyen',
    'den_kho_dich'    => 'dang_van_chuyen',
    'dang_giao'       => 'dang_van_chuyen',
    'da_giao'         => 'da_giao',
    'hoan_hang'       => 'dang_xu_ly',
];

// ── XỬ LÝ THÊM MỐC LỘ TRÌNH (POST) ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['them_lo_trinh'])) {
    $did       = (int)$_POST['don_id'];
    $su_kien   = $_POST['su_kien'] ?? '';
    $kho_id    = (int)($_POST['kho_id'] ?? 0) ?: null;
    $dia_diem  = trim($_POST['dia_diem']      ?? '');
    $tinh      = trim($_POST['tinh_thanh']    ?? '');
    $mo_ta     = trim($_POST['mo_ta']         ?? '');
    $ghi_chu   = trim($_POST['ghi_chu_noi_bo']?? '');
    $thoi_gian = trim($_POST['thoi_gian']     ?? date('Y-m-d H:i:s'));

    if ($su_kien && $did) {
        // Lấy thông tin đơn hàng
        $don = $conn->query(
            "SELECT dh.*, x.bien_so, tx.ho_ten AS ten_tai_xe
             FROM don_hang dh
             LEFT JOIN xe x      ON dh.xe_id = x.id
             LEFT JOIN tai_xe tx ON dh.tai_xe_id = tx.id
             WHERE dh.id = $did LIMIT 1"
        )->fetch_assoc();

        if ($don) {
            $xe_sql   = $don['xe_id']     ? (int)$don['xe_id']     : 'NULL';
            $tx_sql   = $don['tai_xe_id'] ? (int)$don['tai_xe_id'] : 'NULL';
            $kho_sql  = $kho_id ?: 'NULL';

            $dia_esc  = mysqli_real_escape_string($conn, $dia_diem);
            $tinh_esc = mysqli_real_escape_string($conn, $tinh);
            $mo_esc   = mysqli_real_escape_string($conn, $mo_ta);
            $gh_esc   = mysqli_real_escape_string($conn, $ghi_chu);
            $sk_esc   = mysqli_real_escape_string($conn, $su_kien);
            $tg_esc   = mysqli_real_escape_string($conn, $thoi_gian);

            // ① Lưu mốc lộ trình
            $conn->query(
                "INSERT INTO lo_trinh_don_hang
                 (don_hang_id, kho_id, su_kien, dia_diem, tinh_thanh,
                  xe_id, tai_xe_id, nguoi_cap_nhat, thoi_gian, mo_ta, ghi_chu_noi_bo)
                 VALUES ($did, $kho_sql, '$sk_esc', '$dia_esc', '$tinh_esc',
                         $xe_sql, $tx_sql, $uid, '$tg_esc', '$mo_esc', '$gh_esc')"
            );

            // ② Cập nhật trạng thái đơn hàng
            if (isset($su_kien_to_tt[$su_kien])) {
                $tt_moi = $su_kien_to_tt[$su_kien];
                $conn->query("UPDATE don_hang SET trang_thai='$tt_moi' WHERE id=$did");
                if ($su_kien === 'da_giao') {
                    $conn->query("UPDATE don_hang SET ngay_giao_thuc_te=NOW() WHERE id=$did");
                }
            }

            // ③ Tạo nội dung thông báo tự động
            $ten_kho_str = '';
            if ($kho_id) {
                $kho_r = $conn->query("SELECT ten_kho, tinh_thanh FROM kho WHERE id=$kho_id LIMIT 1")->fetch_assoc();
                if ($kho_r) $ten_kho_str = " — {$kho_r['ten_kho']} ({$kho_r['tinh_thanh']})";
            }

            $sk_label = $su_kien_map[$su_kien]['label'] ?? $su_kien;
            $tieu_de  = "🚛 Đơn {$don['ma_don']}: $sk_label";

            if ($mo_ta) {
                $noi_dung = $mo_ta;
            } else {
                $map_nd = [
                    'tao_don'         => "Đơn hàng #{$don['ma_don']} đã được tạo và đang chờ xử lý.",
                    'duyet_don'       => "Đơn hàng #{$don['ma_don']} đã được duyệt, chuẩn bị lấy hàng.",
                    'lay_hang'        => "Xe {$don['bien_so']} đang đến lấy hàng tại địa chỉ của bạn.",
                    'den_kho'         => "Hàng đã đến kho trung chuyển{$ten_kho_str}. Đang phân loại.",
                    'roi_kho'         => "Hàng đã rời kho{$ten_kho_str}, tiếp tục hành trình.",
                    'dang_van_chuyen' => "Hàng đang vận chuyển" . ($tinh ? " qua $tinh" : "") . ".",
                    'den_kho_dich'    => "Hàng đến kho đích{$ten_kho_str}. Chuẩn bị giao đến bạn.",
                    'dang_giao'       => "Tài xế {$don['ten_tai_xe']} đang giao hàng đến bạn. Vui lòng chú ý điện thoại!",
                    'da_giao'         => "Giao hàng thành công! Cảm ơn bạn đã tin tưởng dịch vụ chúng tôi.",
                    'that_bai'        => "Giao hàng thất bại. Chúng tôi sẽ liên hệ lại để sắp xếp lại.",
                    'hoan_hang'       => "Hàng đang hoàn về kho. Chúng tôi sẽ liên hệ xử lý sớm nhất.",
                ];
                $noi_dung = $map_nd[$su_kien] ?? $sk_label;
                if ($dia_diem && !$ten_kho_str) {
                    $noi_dung .= "\n📍 Vị trí: $dia_diem" . ($tinh ? ", $tinh" : "");
                }
            }

            // ④ Tìm tài khoản khách hàng & gửi thông báo
            $ten_kh = mysqli_real_escape_string($conn, $don['ten_khach'] ?? '');
            $sdt_kh = mysqli_real_escape_string($conn, $don['dien_thoai_kh'] ?? '');
            $lien_ket = "customer_lo_trinh.php?search=" . urlencode($don['ma_don']);

            $kh_user = null;
            if ($sdt_kh) {
                $kh_user = $conn->query(
                    "SELECT id FROM users WHERE phone='$sdt_kh' AND role='khách hàng' LIMIT 1"
                )->fetch_assoc();
            }
            if (!$kh_user && $ten_kh) {
                $kh_user = $conn->query(
                    "SELECT id FROM users WHERE full_name='$ten_kh' AND role='khách hàng' LIMIT 1"
                )->fetch_assoc();
            }

            $da_gui_tb = false;
            if ($kh_user) {
                $kh_id   = $kh_user['id'];
                $td_esc  = mysqli_real_escape_string($conn, $tieu_de);
                $nd_esc  = mysqli_real_escape_string($conn, $noi_dung);
                $lk_esc  = mysqli_real_escape_string($conn, $lien_ket);
                $loai_tb = in_array($su_kien, ['that_bai','hoan_hang']) ? 'canh_bao' : 'thong_tin';

                $conn->query(
                    "INSERT INTO thong_bao (nguoi_gui_id, nguoi_nhan_id, tieu_de, noi_dung, loai, lien_ket)
                     VALUES ($uid, $kh_id, '$td_esc', '$nd_esc', '$loai_tb', '$lk_esc')"
                );
                $da_gui_tb = true;
            }

            $label = $su_kien_map[$su_kien]['label'] ?? $su_kien;
            $msg   = [
                'type' => 'success',
                'text' => "✅ Đã cập nhật: <strong>$label</strong>"
                        . ($da_gui_tb ? " — 🔔 Đã gửi thông báo đến khách hàng!" : "")
                        . (!$da_gui_tb ? " — ⚠️ Không tìm thấy tài khoản khách hàng (chưa gửi TB)" : ""),
            ];
            $don_id = $did;
        }
    }
}

// ── LOAD DANH SÁCH ĐƠN ───────────────────────────────────────
$where = "WHERE dh.is_deleted=0 AND dh.trang_thai NOT IN ('huy','hoan_thanh','da_thanh_toan')";
if ($search) {
    $s = $conn->real_escape_string($search);
    $where .= " AND (dh.ma_don LIKE '%$s%' OR dh.ten_khach LIKE '%$s%'
                  OR dh.tinh_lay LIKE '%$s%' OR dh.tinh_giao LIKE '%$s%')";
}
$total    = $conn->query("SELECT COUNT(*) AS c FROM don_hang dh $where")->fetch_assoc()['c'] ?? 0;
$pages    = max(1, ceil($total / $per));
$don_list = $conn->query(
    "SELECT dh.id, dh.ma_don, dh.ten_khach, dh.tinh_lay, dh.tinh_giao, dh.trang_thai,
            (SELECT COUNT(*) FROM lo_trinh_don_hang lt WHERE lt.don_hang_id=dh.id) AS so_moc,
            (SELECT lt2.su_kien FROM lo_trinh_don_hang lt2
             WHERE lt2.don_hang_id=dh.id ORDER BY lt2.thoi_gian DESC LIMIT 1) AS sk_moi
     FROM don_hang dh $where ORDER BY dh.ngay_tao DESC LIMIT $per OFFSET $offset"
)->fetch_all(MYSQLI_ASSOC);

// ── THÔNG TIN CHI TIẾT ĐƠN ĐANG CHỌN ────────────────────────
$don_detail = null;
$lo_trinh   = [];
$ds_kho     = $conn->query(
    "SELECT * FROM kho WHERE is_active=1 ORDER BY loai_kho, ten_kho"
)->fetch_all(MYSQLI_ASSOC);

if ($don_id > 0) {
    $don_detail = $conn->query(
        "SELECT dh.*, x.bien_so, tx.ho_ten AS ten_tai_xe, tx.so_dien_thoai
         FROM don_hang dh
         LEFT JOIN xe x      ON dh.xe_id = x.id
         LEFT JOIN tai_xe tx ON dh.tai_xe_id = tx.id
         WHERE dh.id = $don_id LIMIT 1"
    )->fetch_assoc();

    $lo_trinh = $conn->query(
        "SELECT lt.*, k.ten_kho, k.tinh_thanh AS kho_tinh,
                x.bien_so, tx.ho_ten AS ten_tai_xe,
                u.full_name AS cap_nhat_boi
         FROM lo_trinh_don_hang lt
         LEFT JOIN kho k      ON lt.kho_id = k.id
         LEFT JOIN xe x       ON lt.xe_id = x.id
         LEFT JOIN tai_xe tx  ON lt.tai_xe_id = tx.id
         LEFT JOIN users u    ON lt.nguoi_cap_nhat = u.id
         WHERE lt.don_hang_id = $don_id
         ORDER BY lt.thoi_gian ASC"
    )->fetch_all(MYSQLI_ASSOC);
}

$tt_map = [
    'cho_duyet'       => ['l' => 'Chờ duyệt',    'c' => '#f59e0b'],
    'dang_xu_ly'      => ['l' => 'Đang xử lý',   'c' => '#3b82f6'],
    'dang_lay_hang'   => ['l' => 'Lấy hàng',     'c' => '#f97316'],
    'dang_van_chuyen' => ['l' => 'Đang chạy',    'c' => '#2563eb'],
    'da_giao'         => ['l' => 'Đã giao',       'c' => '#10b981'],
    'hoan_thanh'      => ['l' => 'Hoàn thành',   'c' => '#10b981'],
    'huy'             => ['l' => 'Đã hủy',        'c' => '#ef4444'],
];

// Mẫu mô tả tự động (cho JS)
$mo_ta_mau = [
    'tao_don'         => 'Đơn hàng đã được tạo và đang chờ xử lý.',
    'duyet_don'       => 'Đơn hàng đã được duyệt và chuẩn bị lấy hàng.',
    'lay_hang'        => 'Xe đang đến lấy hàng tại địa chỉ của khách.',
    'den_kho'         => 'Hàng hóa đã đến kho trung chuyển và đang được phân loại.',
    'roi_kho'         => 'Hàng đã rời kho, đang tiếp tục hành trình đến điểm tiếp theo.',
    'dang_van_chuyen' => 'Hàng đang được vận chuyển trên đường.',
    'den_kho_dich'    => 'Hàng đã đến kho đích, chuẩn bị giao đến địa chỉ khách hàng.',
    'dang_giao'       => 'Tài xế đang trên đường giao hàng, vui lòng chú ý điện thoại!',
    'da_giao'         => 'Giao hàng thành công! Cảm ơn bạn đã sử dụng dịch vụ.',
    'that_bai'        => 'Giao hàng chưa thành công. Sẽ liên hệ lại để sắp xếp lại lịch.',
    'hoan_hang'       => 'Hàng đang được hoàn về kho. Sẽ liên hệ xử lý sớm nhất.',
];

$active = 'lo_trinh';
require 'sidebar_dieuphoI.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lộ Trình Đơn Hàng</title>
<link rel="stylesheet" href="dieuphoI_layout.css">
<style>
/* ── Layout 2 cột ── */
.lt-layout { display:grid; grid-template-columns:340px 1fr; gap:18px; align-items:start; }

/* ── Danh sách đơn trái ── */
.dlw {
    background:#fff; border:1px solid var(--border); border-radius:12px;
    overflow:hidden; position:sticky; top:74px;
    max-height:calc(100vh - 100px); display:flex; flex-direction:column;
}
.dlb { overflow-y:auto; flex:1; }
.dr {
    display:flex; align-items:center; gap:10px; padding:11px 14px;
    border-bottom:1px solid #f1f5f9; cursor:pointer;
    text-decoration:none; color:inherit; transition:background .15s;
}
.dr:hover  { background:#f8fafc; }
.dr.on     { background:#eff6ff; border-left:3px solid var(--green); }

/* ── Search trong sidebar ── */
.sbar { display:flex; align-items:center; gap:8px; background:#fff; border:1.5px solid var(--border); border-radius:8px; padding:8px 12px; }
.sbar input { border:none; outline:none; font-size:13px; font-family:inherit; flex:1; background:transparent; }
.sbar:focus-within { border-color:var(--green); }

/* ── Timeline ── */
.tl { position:relative; padding:4px 0 4px 24px; }
.tl::before { content:''; position:absolute; left:24px; top:0; bottom:0; width:2px; background:#e2e8f0; }
.tli { position:relative; padding:0 0 22px 36px; }
.tli:last-child { padding-bottom:0; }
.tld {
    position:absolute; left:-7px; top:4px;
    width:16px; height:16px; border-radius:50%;
    background:#fff; border:2px solid #e2e8f0;
    display:flex; align-items:center; justify-content:center;
    font-size:10px; z-index:1;
}
.tld.act  { border-color:var(--green); background:#eff6ff; }
.tld.done { background:var(--green); border-color:var(--green); }
.tlc {
    background:#fff; border:1px solid var(--border);
    border-radius:10px; padding:12px 16px; transition:box-shadow .2s;
}
.tlc:hover { box-shadow:0 2px 10px rgba(0,0,0,.08); }
.tlc.new   { border-color:var(--green); background:#f0fdf4; }

/* ── Sự kiện nhanh ── */
.qe-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:8px; margin-bottom:16px; }
.qe {
    padding:9px 10px; border:1.5px solid var(--border);
    border-radius:8px; background:#fff; cursor:pointer;
    font-family:inherit; font-size:12px; font-weight:600;
    text-align:center; transition:all .15s;
    display:flex; align-items:center; gap:5px; justify-content:center;
    line-height:1.3;
}
.qe:hover { border-color:var(--green); color:var(--green); background:#f0fdf4; }
.qe.on    { border-color:var(--green); background:var(--green); color:#fff; }

/* ── Badge thông báo ── */
.tb-badge {
    display:inline-flex; align-items:center; gap:5px;
    background:#dcfce7; color:#166534;
    font-size:11px; font-weight:700;
    padding:4px 10px; border-radius:8px;
    margin-top:8px;
}
.tb-badge.warn { background:#fff3cd; color:#856404; }

.kho-badge {
    display:inline-flex; align-items:center; gap:4px;
    background:#f5f3ff; color:#7c3aed;
    font-size:11px; font-weight:700;
    padding:2px 8px; border-radius:8px;
}

@media(max-width:900px) {
    .lt-layout { grid-template-columns:1fr; }
    .dlw { position:static; max-height:350px; }
}
</style>
</head>
<body>
<div class="wrapper">
<main class="main">

<div class="topbar">
    <div class="topbar-left">
        <div class="topbar-title">📍 Lộ Trình & Thông Báo Vị Trí</div>
        <div class="breadcrumb"><a href="dieuphoI_dashboard.php">Dashboard</a> › Lộ trình</div>
    </div>
    <div class="user-chip">
        <div class="chip-avatar"><?= mb_strtoupper(mb_substr($full_name, 0, 1)) ?></div>
        <div>
            <div class="chip-name"><?= htmlspecialchars($full_name) ?></div>
            <div class="chip-role">Điều Phối Viên</div>
        </div>
    </div>
</div>

<div class="content">
<?php if ($msg): ?>
<div class="alert alert-<?= $msg['type'] ?>" style="margin-bottom:16px"><?= $msg['text'] ?></div>
<?php endif; ?>

<div class="lt-layout">

<!-- ═══ CỘT TRÁI: Danh sách đơn ═══ -->
<div class="dlw">
    <div style="padding:14px 16px;border-bottom:1px solid var(--border);background:#f8fafc;flex-shrink:0">
        <div style="font-size:13px;font-weight:700;margin-bottom:10px">📋 Chọn đơn hàng</div>
        <form method="GET">
            <?php if ($don_id): ?>
            <input type="hidden" name="don_id" value="<?= $don_id ?>">
            <?php endif; ?>
            <div class="sbar">
                🔍<input type="text" name="search" id="sInput"
                   placeholder="Mã đơn, tên KH, tỉnh..."
                   value="<?= htmlspecialchars($search) ?>" autocomplete="off">
                <?php if ($search): ?>
                <a href="?don_id=<?= $don_id ?>" style="color:var(--muted);text-decoration:none;font-size:18px">×</a>
                <?php endif; ?>
            </div>
        </form>
        <div style="font-size:11px;color:var(--muted);margin-top:8px"><?= $total ?> đơn đang hoạt động</div>
    </div>

    <div class="dlb">
        <?php if (empty($don_list)): ?>
        <div style="padding:30px;text-align:center;color:var(--muted);font-size:13px">📭 Không có đơn nào.</div>
        <?php else: foreach ($don_list as $d):
            $tt = $tt_map[$d['trang_thai']] ?? ['l' => $d['trang_thai'], 'c' => '#64748b'];
            $sk_i = $d['sk_moi'] ? ($su_kien_map[$d['sk_moi']]['icon'] ?? '📍') : '📝';
        ?>
        <a href="?don_id=<?= $d['id'] ?>&search=<?= urlencode($search) ?>"
           class="dr <?= $d['id'] === $don_id ? 'on' : '' ?>">
            <div style="font-size:20px;flex-shrink:0"><?= $sk_i ?></div>
            <div style="flex:1;min-width:0">
                <div style="font-weight:800;font-size:13px"><?= htmlspecialchars($d['ma_don']) ?></div>
                <div style="font-size:11px;color:var(--muted)">👤 <?= htmlspecialchars(mb_strimwidth($d['ten_khach'], 0, 20, '...')) ?></div>
                <div style="font-size:11px;color:var(--muted)">
                    📍<?= htmlspecialchars($d['tinh_lay']) ?> → 🏁<?= htmlspecialchars($d['tinh_giao']) ?>
                </div>
            </div>
            <div style="text-align:right;flex-shrink:0">
                <span style="background:<?= $tt['c'] ?>22;color:<?= $tt['c'] ?>;font-size:10px;font-weight:700;padding:2px 7px;border-radius:8px">
                    <?= $tt['l'] ?>
                </span>
                <?php if ($d['so_moc'] > 0): ?>
                <div style="font-size:10px;color:var(--muted);margin-top:3px">📌 <?= $d['so_moc'] ?> mốc</div>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; endif; ?>
    </div>

    <?php if ($pages > 1): ?>
    <div style="padding:10px 14px;border-top:1px solid var(--border);display:flex;gap:4px;flex-wrap:wrap">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?don_id=<?= $don_id ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"
           style="width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;
                  font-size:12px;font-weight:700;text-decoration:none;border:1.5px solid var(--border);
                  background:<?= $i===$page?'var(--green)':'#fff' ?>;
                  color:<?= $i===$page?'#fff':'var(--text)' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ═══ CỘT PHẢI ═══ -->
<div>
<?php if (!$don_detail): ?>
<!-- Chưa chọn đơn -->
<div style="background:#fff;border:1px solid var(--border);border-radius:12px;padding:60px 20px;text-align:center;color:var(--muted)">
    <div style="font-size:52px;margin-bottom:14px">👈</div>
    <p style="font-size:15px;font-weight:600;color:var(--text)">Chọn đơn hàng để cập nhật lộ trình</p>
    <p style="font-size:13px;margin-top:6px">Sau khi cập nhật, hệ thống sẽ tự động gửi thông báo đến khách hàng</p>
</div>

<?php else: ?>

<!-- ── Info đơn hàng ── -->
<div style="background:#fff;border:1px solid var(--border);border-radius:12px;padding:16px 20px;margin-bottom:16px">
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
        <div style="flex:1;min-width:180px">
            <div style="font-size:16px;font-weight:800"><?= htmlspecialchars($don_detail['ma_don']) ?></div>
            <div style="font-size:13px;color:var(--muted);margin-top:3px">
                👤 <?= htmlspecialchars($don_detail['ten_khach']) ?>
                <?php if ($don_detail['dien_thoai_kh']): ?>
                 · 📱 <?= htmlspecialchars($don_detail['dien_thoai_kh']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div style="font-size:13px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <span>📍 <strong><?= htmlspecialchars($don_detail['tinh_lay']) ?></strong></span>
            <span style="color:var(--muted)">→→→</span>
            <span>🏁 <strong><?= htmlspecialchars($don_detail['tinh_giao']) ?></strong></span>
        </div>
        <?php $tt = $tt_map[$don_detail['trang_thai']] ?? ['l' => $don_detail['trang_thai'], 'c' => '#64748b']; ?>
        <span style="background:<?= $tt['c'] ?>22;color:<?= $tt['c'] ?>;font-size:12px;font-weight:700;padding:5px 12px;border-radius:8px">
            <?= $tt['l'] ?>
        </span>
        <?php if ($don_detail['bien_so']): ?>
        <span style="font-size:12px;color:var(--muted)">
            🚛 <?= htmlspecialchars($don_detail['bien_so']) ?>
            · 👤 <?= htmlspecialchars($don_detail['ten_tai_xe'] ?? '—') ?>
        </span>
        <?php endif; ?>
    </div>

    <!-- Hướng dẫn thông báo -->
    <div style="margin-top:12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;font-size:12px;color:#166534">
        🔔 <strong>Hệ thống thông báo tự động:</strong>
        Mỗi khi bạn cập nhật mốc lộ trình, hệ thống sẽ tự động gửi thông báo đến tài khoản khách hàng của đơn này.
    </div>
</div>

<!-- ── TIMELINE ── -->
<div style="background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:16px">
    <div style="font-size:14px;font-weight:700;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between">
        <span>📌 Lộ Trình Vận Chuyển</span>
        <span style="font-size:12px;color:var(--muted);font-weight:400"><?= count($lo_trinh) ?> mốc đã cập nhật</span>
    </div>

    <?php if (empty($lo_trinh)): ?>
    <div style="text-align:center;padding:30px;color:var(--muted)">
        <div style="font-size:36px;margin-bottom:8px">📋</div>
        <p>Chưa có mốc lộ trình nào. Thêm mốc đầu tiên bên dưới.</p>
    </div>
    <?php else: ?>
    <div class="tl">
        <?php foreach ($lo_trinh as $i => $lt):
            $sk = $su_kien_map[$lt['su_kien']] ?? ['label' => $lt['su_kien'], 'icon' => '📍', 'color' => '#64748b'];
            $is_last = ($i === count($lo_trinh) - 1);
            $is_done = ($lt['su_kien'] === 'da_giao');
        ?>
        <div class="tli">
            <div class="tld <?= $is_last ? 'act' : ($is_done ? 'done' : '') ?>">
                <?= $is_done ? '✓' : $sk['icon'] ?>
            </div>
            <div class="tlc <?= $is_last ? 'new' : '' ?>">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;flex-wrap:wrap">
                    <div style="display:flex;align-items:center;gap:6px;font-weight:700;font-size:13px">
                        <span style="font-size:16px"><?= $sk['icon'] ?></span>
                        <span style="color:<?= $sk['color'] ?>"><?= $sk['label'] ?></span>
                        <?php if ($is_last && !$is_done): ?>
                        <span style="background:#dcfce7;color:#166534;font-size:10px;font-weight:700;padding:1px 7px;border-radius:10px">
                            MỚI NHẤT
                        </span>
                        <?php endif; ?>
                    </div>
                    <span style="font-size:11px;color:var(--muted);white-space:nowrap">
                        🕐 <?= date('d/m/Y H:i', strtotime($lt['thoi_gian'])) ?>
                    </span>
                </div>

                <!-- Kho / địa điểm -->
                <?php if ($lt['ten_kho']): ?>
                <div style="margin-bottom:5px">
                    <span class="kho-badge">🏭 <?= htmlspecialchars($lt['ten_kho']) ?></span>
                    <?php if ($lt['kho_tinh']): ?>
                    <span style="font-size:11px;color:var(--muted);margin-left:4px"><?= htmlspecialchars($lt['kho_tinh']) ?></span>
                    <?php endif; ?>
                </div>
                <?php elseif ($lt['dia_diem']): ?>
                <div style="font-size:12px;font-weight:600;margin-bottom:4px">
                    📍 <?= htmlspecialchars($lt['dia_diem']) ?>
                    <?php if ($lt['tinh_thanh']): ?>
                    <span style="color:var(--muted);font-weight:400">, <?= htmlspecialchars($lt['tinh_thanh']) ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($lt['mo_ta']): ?>
                <div style="font-size:12px;color:var(--muted);margin-bottom:4px;line-height:1.5">
                    <?= nl2br(htmlspecialchars($lt['mo_ta'])) ?>
                </div>
                <?php endif; ?>

                <!-- Meta: xe, tài xế, người cập nhật -->
                <div style="display:flex;gap:10px;flex-wrap:wrap;font-size:11px;color:#94a3b8;margin-top:4px">
                    <?php if ($lt['bien_so']): ?>
                    <span>🚛 <?= htmlspecialchars($lt['bien_so']) ?></span>
                    <?php endif; ?>
                    <?php if ($lt['ten_tai_xe']): ?>
                    <span>👤 <?= htmlspecialchars($lt['ten_tai_xe']) ?></span>
                    <?php endif; ?>
                    <?php if ($lt['cap_nhat_boi']): ?>
                    <span>✏️ <?= htmlspecialchars($lt['cap_nhat_boi']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Ghi chú nội bộ -->
                <?php if ($lt['ghi_chu_noi_bo']): ?>
                <div style="background:#fef9c3;border-radius:6px;padding:5px 9px;font-size:11px;color:#854d0e;margin-top:7px">
                    🔒 Nội bộ: <?= htmlspecialchars($lt['ghi_chu_noi_bo']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ── FORM THÊM MỐC ── -->
<?php if (!in_array($don_detail['trang_thai'], ['da_giao','hoan_thanh','da_thanh_toan','huy'])): ?>
<div style="background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px">
    <div style="font-size:14px;font-weight:700;margin-bottom:6px;padding-bottom:10px;border-bottom:2px solid var(--border);
        display:flex;align-items:center;justify-content:space-between">
        <span>➕ Thêm Mốc Lộ Trình</span>
        <span style="font-size:11px;color:var(--muted);font-weight:400">
            🔔 Sẽ tự động gửi thông báo đến khách hàng
        </span>
    </div>

    <form method="POST" id="frmLoTrinh">
        <input type="hidden" name="don_id" value="<?= $don_detail['id'] ?>">
        <input type="hidden" name="them_lo_trinh" value="1">
        <input type="hidden" name="su_kien" id="sk_v">

        <!-- Chọn sự kiện nhanh -->
        <div style="margin-bottom:16px">
            <label style="font-size:13px;font-weight:700;display:block;margin-bottom:10px">
                📌 Chọn loại sự kiện *
                <span style="font-size:11px;color:var(--muted);font-weight:400">
                    (mỗi sự kiện tương ứng một thông báo tự động gửi cho khách)
                </span>
            </label>
            <div class="qe-grid">
                <?php foreach ($su_kien_map as $k => $sk): ?>
                <button type="button" class="qe" data-v="<?= $k ?>" onclick="chon(this,'<?= $k ?>')">
                    <span><?= $sk['icon'] ?></span> <?= $sk['label'] ?>
                </button>
                <?php endforeach; ?>
            </div>
            <div id="sk_warn" style="font-size:12px;color:#ef4444;display:none">⚠️ Vui lòng chọn loại sự kiện!</div>
        </div>

        <!-- Xem trước thông báo -->
        <div id="tb_preview" style="display:none;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:13px">
            <div style="font-weight:700;color:#1e40af;margin-bottom:6px">
                🔔 Thông báo sẽ gửi đến khách:
            </div>
            <div style="font-weight:600" id="tb_tieu_de"></div>
            <div style="color:#475569;margin-top:4px" id="tb_noi_dung"></div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">

            <!-- Kho trung chuyển -->
            <div class="field">
                <label>🏭 Kho trung chuyển <span style="color:var(--muted);font-size:11px">(nếu đến kho)</span></label>
                <select name="kho_id" id="sel_kho" onchange="autoFillTinh()">
                    <option value="">— Không qua kho —</option>
                    <?php foreach ($ds_kho as $k): ?>
                    <option value="<?= $k['id'] ?>" data-tinh="<?= htmlspecialchars($k['tinh_thanh']) ?>">
                        <?= htmlspecialchars($k['ten_kho']) ?> (<?= htmlspecialchars($k['tinh_thanh']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Thời gian -->
            <div class="field">
                <label>🕐 Thời gian sự kiện</label>
                <input type="datetime-local" name="thoi_gian" value="<?= date('Y-m-d\TH:i') ?>">
            </div>

            <!-- Địa điểm -->
            <div class="field">
                <label>📍 Địa điểm tự do <span style="color:var(--muted);font-size:11px">(nếu không qua kho)</span></label>
                <input type="text" name="dia_diem" id="inp_diadiem"
                       placeholder="VD: Quốc lộ 1A, Km 1234...">
            </div>

            <!-- Tỉnh/TP -->
            <div class="field">
                <label>🗺️ Tỉnh/TP hiện tại của hàng</label>
                <input type="text" name="tinh_thanh" id="inp_tinh"
                       placeholder="VD: Đà Nẵng" list="tinh_list">
                <datalist id="tinh_list">
                    <?php foreach (['TP.HCM','Hà Nội','Đà Nẵng','Bình Dương','Đồng Nai',
                                    'Cần Thơ','Vũng Tàu','Hải Phòng','Khánh Hòa',
                                    'Huế','Đà Lạt','Cà Mau','Nha Trang'] as $t): ?>
                    <option value="<?= $t ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>

            <!-- Mô tả khách thấy -->
            <div class="field" style="grid-column:span 2">
                <label>
                    📝 Mô tả
                    <span style="background:#dbeafe;color:#1e40af;font-size:10px;font-weight:700;padding:1px 6px;border-radius:8px;margin-left:4px">
                        Khách hàng sẽ thấy nội dung này
                    </span>
                </label>
                <textarea name="mo_ta" id="inp_mota" rows="2"
                    style="width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:13px;resize:vertical"
                    placeholder="Để trống = dùng nội dung tự động..."></textarea>
            </div>

            <!-- Ghi chú nội bộ -->
            <div class="field" style="grid-column:span 2">
                <label>
                    🔒 Ghi chú nội bộ
                    <span style="background:#fef9c3;color:#854d0e;font-size:10px;font-weight:700;padding:1px 6px;border-radius:8px;margin-left:4px">
                        Chỉ điều phối thấy
                    </span>
                </label>
                <input type="text" name="ghi_chu_noi_bo"
                       placeholder="VD: Chờ xe ghép hàng, gọi lại lúc 14h..."
                       style="padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit">
            </div>
        </div>

        <div style="display:flex;gap:10px;align-items:center;margin-top:16px;flex-wrap:wrap">
            <button type="submit" class="btn btn-primary" onclick="return checkSubmit()">
                📍 Cập nhật lộ trình & Gửi thông báo
            </button>
            <span style="font-size:12px;color:var(--muted)">
                🔔 Thông báo sẽ gửi đến khách ngay khi lưu
            </span>
        </div>
    </form>
</div>

<?php else: ?>
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px 18px;text-align:center;color:#166534;font-weight:600">
    🎉 Đơn hàng đã hoàn thành — không cần cập nhật thêm lộ trình.
</div>
<?php endif; ?>

<?php endif; // don_detail ?>
</div><!-- /cột phải -->
</div><!-- /lt-layout -->
</div><!-- /content -->
</main>
</div>

<script>
// Mô tả tự động theo sự kiện
const moTaMau = <?= json_encode($mo_ta_mau, JSON_UNESCAPED_UNICODE) ?>;
const donMaDon = '<?= htmlspecialchars($don_detail['ma_don'] ?? '') ?>';

function chon(btn, val) {
    // Bỏ highlight cũ
    document.querySelectorAll('.qe').forEach(b => b.classList.remove('on'));
    btn.classList.add('on');
    document.getElementById('sk_v').value = val;
    document.getElementById('sk_warn').style.display = 'none';

    // Điền mô tả tự động
    const inp_mota = document.getElementById('inp_mota');
    if (inp_mota && !inp_mota.value) {
        inp_mota.value = moTaMau[val] || '';
    }

    // Hiện xem trước thông báo
    capNhatPreview(val);

    // Focus kho nếu sự kiện liên quan kho
    const khoEvents = ['den_kho','roi_kho','den_kho_dich'];
    if (khoEvents.includes(val)) {
        document.getElementById('sel_kho')?.focus();
    }
}

function capNhatPreview(val) {
    const preview   = document.getElementById('tb_preview');
    const tieuDeEl  = document.getElementById('tb_tieu_de');
    const noiDungEl = document.getElementById('tb_noi_dung');
    if (!preview) return;

    const labels = {
        tao_don:'Đơn được tạo', duyet_don:'Đơn đã được duyệt',
        lay_hang:'Đang lấy hàng', den_kho:'Hàng đến kho trung chuyển',
        roi_kho:'Hàng rời kho', dang_van_chuyen:'Đang vận chuyển',
        den_kho_dich:'Đến kho đích', dang_giao:'Đang giao hàng',
        da_giao:'Giao hàng thành công! 🎉', that_bai:'Giao hàng thất bại',
        hoan_hang:'Hàng đang hoàn về kho',
    };

    const moTaHT = document.getElementById('inp_mota')?.value || moTaMau[val] || '';

    tieuDeEl.textContent  = `🚛 Đơn ${donMaDon}: ${labels[val] || val}`;
    noiDungEl.textContent = moTaHT;
    preview.style.display = 'block';
}

// Cập nhật preview khi nhập mô tả
document.getElementById('inp_mota')?.addEventListener('input', function() {
    const sk = document.getElementById('sk_v').value;
    if (sk) capNhatPreview(sk);
});

// Tự điền tỉnh khi chọn kho
function autoFillTinh() {
    const sel = document.getElementById('sel_kho');
    const opt = sel.options[sel.selectedIndex];
    if (!opt?.value) return;
    const tinh = opt.dataset.tinh;
    if (tinh) {
        const el = document.getElementById('inp_tinh');
        if (el) el.value = tinh;
    }
    // Xóa địa điểm tự do nếu chọn kho
    const dd = document.getElementById('inp_diadiem');
    if (dd) dd.value = '';

    // Cập nhật preview nếu đã chọn sự kiện
    const sk = document.getElementById('sk_v').value;
    if (sk) capNhatPreview(sk);
}

// Kiểm tra trước khi submit
function checkSubmit() {
    if (!document.getElementById('sk_v').value) {
        document.getElementById('sk_warn').style.display = 'block';
        document.querySelector('.qe-grid')?.scrollIntoView({ behavior: 'smooth' });
        return false;
    }
    return true;
}

// Tìm kiếm real-time
let timer = null;
document.getElementById('sInput')?.addEventListener('input', function() {
    clearTimeout(timer);
    const v = this.value.trim();
    timer = setTimeout(() => {
        if (v.length >= 2 || v.length === 0) this.closest('form').submit();
    }, 500);
});
</script>
</body>
</html>
