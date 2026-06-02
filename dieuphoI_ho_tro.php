<?php
// ============================================================
// dieuphoI_ho_tro.php — Hỗ Trợ Khách Hàng
// Điều phối viên tra cứu thông tin KH, xem đơn hàng,
// và gửi thông báo hỗ trợ trực tiếp.
// ============================================================
session_start();
require 'config.php';
require_role(['dieuphoI']);

$msg       = null;
$uid       = $_SESSION['user_id'];
$full_name = '';
$stmt      = $conn->prepare("SELECT full_name FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $uid);
$stmt->execute();
$stmt->bind_result($full_name);
$stmt->fetch();
$stmt->close();
$kh_id     = (int)($_GET['id'] ?? 0);   // ID khách hàng đang xem chi tiết
$search    = trim($_GET['search'] ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$per       = 10;
$offset    = ($page - 1) * $per;

// ── Gửi thông báo hỗ trợ đến khách hàng ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gui_thong_bao'])) {
    $to_id    = (int)$_POST['kh_id'];
    $tieu_de  = trim($_POST['tieu_de'] ?? '');
    $noi_dung = trim($_POST['noi_dung'] ?? '');
    $loai     = $_POST['loai'] ?? 'thong_tin';

    if ($tieu_de && $noi_dung && $to_id) {
        $stmt = $conn->prepare(
            "INSERT INTO thong_bao (nguoi_gui_id, nguoi_nhan_id, tieu_de, noi_dung, loai)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iisss", $uid, $to_id, $tieu_de, $noi_dung, $loai);
        if ($stmt->execute()) {
            $msg = ['type' => 'success', 'text' => '✅ Đã gửi thông báo đến khách hàng!'];
        } else {
            $msg = ['type' => 'danger', 'text' => '❌ Lỗi gửi thông báo: ' . $stmt->error];
        }
        $stmt->close();
    } else {
        $msg = ['type' => 'warning', 'text' => 'Vui lòng điền đầy đủ tiêu đề và nội dung!'];
    }
    // Giữ lại trang chi tiết sau khi gửi
    if ($to_id) $kh_id = $to_id;
}

// ── Load chi tiết một khách hàng ──────────────────────────
$kh_detail   = null;
$kh_dons     = [];
$kh_thong_bao= [];

if ($kh_id > 0) {
    // Thông tin tài khoản
    $stmt = $conn->prepare(
        "SELECT id, username, full_name, email, phone,
                is_active, created_at, last_login,
                google_id, avatar_url, login_method, phone_verified
         FROM users WHERE id = ? AND role = 'khachhang' LIMIT 1"
    );
    $stmt->bind_param("i", $kh_id);
    $stmt->execute();
    $kh_detail = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($kh_detail) {
        // Đơn hàng của khách (5 gần nhất)
        $stmt2 = $conn->prepare(
            "SELECT dh.ma_don, dh.trang_thai, dh.tinh_lay, dh.tinh_giao,
                    dh.doanh_thu, dh.ngay_tao, dh.loai_van_chuyen,
                    x.bien_so, tx.ho_ten AS ten_tai_xe
             FROM don_hang dh
             LEFT JOIN xe x     ON dh.xe_id = x.id
             LEFT JOIN tai_xe tx ON dh.tai_xe_id = tx.id
             WHERE dh.ten_khach = ? OR dh.dien_thoai_kh = ?
             ORDER BY dh.ngay_tao DESC LIMIT 5"
        );
        $phone = $kh_detail['phone'] ?? '';
        $name  = $kh_detail['full_name'];
        $stmt2->bind_param("ss", $name, $phone);
        $stmt2->execute();
        $kh_dons = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt2->close();

        // Thông báo đã gửi cho khách (5 gần nhất)
        $stmt3 = $conn->prepare(
            "SELECT tieu_de, noi_dung, loai, da_doc, created_at
             FROM thong_bao WHERE nguoi_nhan_id = ?
             ORDER BY created_at DESC LIMIT 5"
        );
        $stmt3->bind_param("i", $kh_id);
        $stmt3->execute();
        $kh_thong_bao = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt3->close();
    }
}

// ── Danh sách khách hàng (có tìm kiếm) ────────────────────
$where = "WHERE role = 'khachhang'";
if ($search) {
    $s      = mysqli_real_escape_string($conn, $search);
    $where .= " AND (full_name LIKE '%$s%'
                  OR email LIKE '%$s%'
                  OR phone LIKE '%$s%'
                  OR username LIKE '%$s%')";
}

$total = $conn->query("SELECT COUNT(*) AS c FROM users $where")->fetch_assoc()['c'] ?? 0;
$pages = max(1, ceil($total / $per));

$kh_list = $conn->query(
    "SELECT u.id, u.full_name, u.email, u.phone, u.is_active,
            u.last_login, u.created_at, u.google_id, u.login_method,
            u.avatar_url, u.phone_verified,
            (SELECT COUNT(*) FROM don_hang dh
             WHERE dh.ten_khach = u.full_name
               AND is_deleted = 0) AS so_don,
            (SELECT COUNT(*) FROM thong_bao tb
             WHERE tb.nguoi_nhan_id = u.id AND tb.da_doc = 0) AS tb_chua_doc
     FROM users u $where
     ORDER BY u.last_login DESC, u.created_at DESC
     LIMIT $per OFFSET $offset"
)->fetch_all(MYSQLI_ASSOC);

// Thống kê nhanh
$st_tong   = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='khachhang'")->fetch_assoc()['c'] ?? 0;
$st_active = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='khachhang' AND is_active=1")->fetch_assoc()['c'] ?? 0;
$st_google = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='khachhang' AND login_method='google'")->fetch_assoc()['c'] ?? 0;
$st_phone  = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='khachhang' AND login_method='phone'")->fetch_assoc()['c'] ?? 0;

$tt_map = [
    'cho_duyet'       => ['l' => 'Chờ duyệt',     'c' => '#f59e0b'],
    'dang_xu_ly'      => ['l' => 'Đang xử lý',    'c' => '#3b82f6'],
    'dang_van_chuyen' => ['l' => 'Đang chạy',     'c' => '#10b981'],
    'da_giao'         => ['l' => 'Đã giao',        'c' => '#10b981'],
    'hoan_thanh'      => ['l' => 'Hoàn thành',    'c' => '#10b981'],
    'da_thanh_toan'   => ['l' => 'Đã TT',          'c' => '#3b82f6'],
    'huy'             => ['l' => 'Đã hủy',         'c' => '#ef4444'],
];

$active = 'ho_tro';
require 'sidebar_dieuphoI.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Hỗ Trợ Khách Hàng — Điều Phối</title>
<link rel="stylesheet" href="dieuphoI_layout.css">
<style>
/* ── Layout 2 cột ─────────────────────────────── */
.ht-layout {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 18px;
    align-items: start;
}

/* ── Danh sách KH bên trái ───────────────────── */
.kh-list-wrap {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    position: sticky;
    top: 74px;          /* dính dưới topbar khi cuộn */
    max-height: calc(100vh - 100px);
    display: flex;
    flex-direction: column;
}
.kh-list-head {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
    background: #f8fafc;
    flex-shrink: 0;
}
.kh-list-body {
    overflow-y: auto;
    flex: 1;
}

/* Mỗi dòng khách hàng */
.kh-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: background .15s;
    text-decoration: none;
    color: inherit;
}
.kh-row:hover    { background: #f8fafc; }
.kh-row.selected { background: #eff6ff; border-left: 3px solid var(--green); }
.kh-row:last-child { border-bottom: none; }

/* Avatar tròn */
.kh-avatar {
    width: 40px; height: 40px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    background: var(--green-light);
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; font-weight: 700; color: var(--green);
    overflow: hidden;
}
.kh-avatar img { width: 100%; height: 100%; object-fit: cover; }

.kh-name  { font-size: 13px; font-weight: 700; color: var(--text); }
.kh-sub   { font-size: 11px; color: var(--muted); margin-top: 2px; }

/* Badge phương thức đăng nhập */
.method-badge {
    font-size: 10px; font-weight: 700;
    padding: 2px 7px; border-radius: 10px;
    flex-shrink: 0;
}
.method-google  { background: #fff3e0; color: #e65100; }
.method-phone   { background: #e8f5e9; color: #1b5e20; }
.method-password{ background: #e3f2fd; color: #0d47a1; }

/* ── Chi tiết KH bên phải ────────────────────── */
.kh-detail-wrap {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Card thông tin liên lạc */
.info-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
}
.ic-head {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
}
.ic-avatar {
    width: 60px; height: 60px;
    border-radius: 50%;
    background: rgba(255,255,255,.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; font-weight: 800; flex-shrink: 0;
    overflow: hidden; border: 2px solid rgba(255,255,255,.4);
}
.ic-avatar img { width: 100%; height: 100%; object-fit: cover; }
.ic-name  { font-size: 18px; font-weight: 800; }
.ic-role  { font-size: 12px; opacity: .75; margin-top: 3px; }

/* Lưới thông tin liên hệ */
.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
}
.contact-item {
    padding: 14px 20px;
    border-bottom: 1px solid #f1f5f9;
    border-right: 1px solid #f1f5f9;
    transition: background .15s;
}
.contact-item:nth-child(2n) { border-right: none; }
.contact-item:nth-last-child(-n+2) { border-bottom: none; }
.contact-item:hover { background: #f8fafc; }

.ci-label {
    font-size: 10px; font-weight: 700;
    color: var(--muted); text-transform: uppercase;
    letter-spacing: .5px; margin-bottom: 5px;
}
.ci-value {
    font-size: 13px; font-weight: 600; color: var(--text);
    display: flex; align-items: center; gap: 6px;
    word-break: break-all;
}

/* Nút liên hệ nhanh */
.quick-contact {
    display: flex; gap: 8px; padding: 14px 20px;
    border-top: 1px solid #f1f5f9; flex-wrap: wrap;
}

/* Card đơn hàng gần đây */
.don-mini {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 16px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 12px;
}
.don-mini:last-child { border-bottom: none; }
.don-mini-ma { font-weight: 700; min-width: 110px; }
.don-mini-route { flex: 1; color: var(--muted); }
.don-status-dot {
    width: 8px; height: 8px;
    border-radius: 50%; flex-shrink: 0;
}

/* Form gửi thông báo */
.form-send {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
}
.form-send h3 {
    font-size: 14px; font-weight: 700;
    color: var(--text); margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--border);
    display: flex; align-items: center; gap: 8px;
}

/* Trống - chưa chọn KH */
.empty-select {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 60px 20px;
    text-align: center;
    color: var(--muted);
}
.empty-select .ei { font-size: 52px; display: block; margin-bottom: 14px; }

/* Thanh tìm kiếm KH */
.kh-search {
    display: flex; align-items: center; gap: 8px;
    background: #fff;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    padding: 8px 12px;
    margin-bottom: 12px;
}
.kh-search input {
    border: none; outline: none;
    font-size: 13px; font-family: inherit;
    flex: 1; background: transparent;
}
.kh-search:focus-within { border-color: var(--green); }

/* Badge thông báo chưa đọc */
.unread-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #ef4444;
    flex-shrink: 0;
}

@media (max-width: 900px) {
    .ht-layout { grid-template-columns: 1fr; }
    .kh-list-wrap { position: static; max-height: 400px; }
    .contact-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="wrapper">
<main class="main">

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-left">
        <div class="topbar-title">🎧 Hỗ Trợ Khách Hàng</div>
        <div class="breadcrumb">
            <a href="dieuphoI_dashboard.php">Dashboard</a> › Hỗ trợ khách hàng
            · Tổng: <strong><?= $st_tong ?></strong> khách
        </div>
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
    <div class="alert alert-<?= $msg['type'] ?>" style="margin-bottom:16px">
        <?= $msg['text'] ?>
    </div>
    <?php endif; ?>

    <!-- Thống kê nhanh -->
    <div class="stat-cards" style="margin-bottom:18px">
        <div class="stat-card">
            <span class="sc-icon">👥</span>
            <div class="sc-label">Tổng khách hàng</div>
            <div class="sc-value"><?= $st_tong ?></div>
            <div class="sc-sub">Đã đăng ký</div>
        </div>
        <div class="stat-card" style="border-top-color:#10b981">
            <span class="sc-icon">✅</span>
            <div class="sc-label">Đang hoạt động</div>
            <div class="sc-value" style="color:#10b981"><?= $st_active ?></div>
            <div class="sc-sub">Tài khoản</div>
        </div>
        <div class="stat-card" style="border-top-color:#ea4335">
            <span class="sc-icon">📧</span>
            <div class="sc-label">Đăng nhập Google</div>
            <div class="sc-value" style="color:#ea4335"><?= $st_google ?></div>
            <div class="sc-sub">Tài khoản</div>
        </div>
        <div class="stat-card" style="border-top-color:#10b981">
            <span class="sc-icon">📱</span>
            <div class="sc-label">Đăng nhập SĐT</div>
            <div class="sc-value" style="color:#10b981"><?= $st_phone ?></div>
            <div class="sc-sub">Tài khoản</div>
        </div>
    </div>

    <!-- Layout 2 cột -->
    <div class="ht-layout">

        <!-- ═══ CỘT TRÁI: Danh sách khách hàng ═══ -->
        <div class="kh-list-wrap">
            <div class="kh-list-head">
                <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:10px">
                    🔍 Tìm kiếm khách hàng
                </div>
                <!-- Form tìm kiếm -->
                <form method="GET">
                    <input type="hidden" name="id" value="<?= $kh_id ?>">
                    <div class="kh-search">
                        🔍
                        <input type="text" name="search" id="searchKH"
                               placeholder="Tên, email, số điện thoại..."
                               value="<?= htmlspecialchars($search) ?>"
                               autocomplete="off">
                        <?php if ($search): ?>
                        <a href="?id=<?= $kh_id ?>" style="color:var(--muted);text-decoration:none;font-size:18px">×</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div style="font-size:11px;color:var(--muted)">
                    <?= $total ?> khách hàng<?= $search ? " cho \"$search\"" : '' ?>
                </div>
            </div>

            <div class="kh-list-body">
                <?php if (empty($kh_list)): ?>
                <div style="padding:40px 20px;text-align:center;color:var(--muted)">
                    <div style="font-size:36px;margin-bottom:8px">👥</div>
                    <div style="font-size:13px">Không tìm thấy khách hàng nào.</div>
                </div>
                <?php else: ?>
                <?php foreach ($kh_list as $kh): ?>
                <a href="?id=<?= $kh['id'] ?>&search=<?= urlencode($search) ?>"
                   class="kh-row <?= $kh['id'] === $kh_id ? 'selected' : '' ?>">

                    <!-- Avatar -->
                    <div class="kh-avatar">
                        <?php if (!empty($kh['avatar_url'])): ?>
                            <img src="<?= htmlspecialchars($kh['avatar_url']) ?>"
                                 alt="avatar"
                                 onerror="this.style.display='none';this.nextSibling.style.display='flex'">
                            <span style="display:none;width:100%;height:100%;align-items:center;justify-content:center">
                                <?= mb_strtoupper(mb_substr($kh['full_name'], 0, 1)) ?>
                            </span>
                        <?php else: ?>
                            <?= mb_strtoupper(mb_substr($kh['full_name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>

                    <!-- Tên và thông tin -->
                    <div style="flex:1;min-width:0">
                        <div style="display:flex;align-items:center;gap:6px">
                            <div class="kh-name"><?= htmlspecialchars($kh['full_name']) ?></div>
                            <?php if (!$kh['is_active']): ?>
                                <span style="background:#fee2e2;color:#991b1b;font-size:9px;font-weight:700;padding:1px 5px;border-radius:8px">Khóa</span>
                            <?php endif; ?>
                        </div>
                        <div class="kh-sub">
                            <?php if ($kh['phone']): ?>
                                📱 <?= htmlspecialchars($kh['phone']) ?>
                            <?php elseif ($kh['email']): ?>
                                📧 <?= htmlspecialchars(mb_strimwidth($kh['email'], 0, 22, '...')) ?>
                            <?php endif; ?>
                        </div>
                        <div class="kh-sub" style="margin-top:2px">
                            <?php if ($kh['so_don'] > 0): ?>
                                📦 <?= $kh['so_don'] ?> đơn hàng
                            <?php endif; ?>
                            <?php if ($kh['last_login']): ?>
                                · 🕐 <?= date('d/m', strtotime($kh['last_login'])) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Badge phương thức & thông báo chưa đọc -->
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:5px;flex-shrink:0">
                        <?php
                        $method = $kh['login_method'] ?? 'password';
                        $method_lbl  = ['google'=>'Google','phone'=>'SĐT','password'=>'Pass'][$method] ?? $method;
                        $method_cls  = 'method-' . $method;
                        ?>
                        <span class="method-badge <?= $method_cls ?>">
                            <?= $method === 'google' ? '📧' : ($method === 'phone' ? '📱' : '🔑') ?>
                            <?= $method_lbl ?>
                        </span>
                        <?php if ($kh['tb_chua_doc'] > 0): ?>
                            <span style="background:#ef4444;color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px">
                                <?= $kh['tb_chua_doc'] ?> TB
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Phân trang trong cột trái -->
            <?php if ($pages > 1): ?>
            <div style="padding:10px 14px;border-top:1px solid var(--border);display:flex;gap:4px;flex-wrap:wrap">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="?id=<?= $kh_id ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"
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

        <!-- ═══ CỘT PHẢI: Chi tiết khách hàng ═══ -->
        <div class="kh-detail-wrap">

            <?php if (!$kh_detail): ?>
            <!-- Chưa chọn khách hàng -->
            <div class="empty-select">
                <span class="ei">👈</span>
                <p style="font-size:15px;font-weight:600;color:var(--text)">
                    Chọn một khách hàng để xem thông tin
                </p>
                <p style="font-size:13px;margin-top:8px">
                    Tìm kiếm theo tên, email hoặc số điện thoại ở danh sách bên trái.
                </p>
            </div>

            <?php else: ?>

            <!-- === CARD THÔNG TIN LIÊN HỆ === -->
            <div class="info-card">

                <!-- Header avatar + tên -->
                <div class="ic-head">
                    <div class="ic-avatar">
                        <?php if (!empty($kh_detail['avatar_url'])): ?>
                            <img src="<?= htmlspecialchars($kh_detail['avatar_url']) ?>"
                                 alt="avatar"
                                 onerror="this.outerHTML='<?= mb_strtoupper(mb_substr($kh_detail['full_name'],0,1)) ?>'">
                        <?php else: ?>
                            <?= mb_strtoupper(mb_substr($kh_detail['full_name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1">
                        <div class="ic-name"><?= htmlspecialchars($kh_detail['full_name']) ?></div>
                        <div class="ic-role">
                            <?php
                            $method = $kh_detail['login_method'] ?? 'password';
                            echo match($method) {
                                'google' => '📧 Tài khoản Google',
                                'phone'  => '📱 Tài khoản số điện thoại',
                                default  => '🔑 Tài khoản mật khẩu',
                            };
                            ?>
                            · <?= $kh_detail['is_active'] ? '✅ Đang hoạt động' : '🔒 Đã bị khóa' ?>
                        </div>
                    </div>
                    <!-- Trạng thái hoạt động -->
                    <div style="text-align:right;flex-shrink:0">
                        <?php if ($kh_detail['last_login']): ?>
                        <div style="font-size:11px;opacity:.75">Đăng nhập cuối:</div>
                        <div style="font-size:12px;font-weight:700">
                            <?= date('d/m/Y H:i', strtotime($kh_detail['last_login'])) ?>
                        </div>
                        <?php else: ?>
                        <div style="font-size:11px;opacity:.75">Chưa đăng nhập</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Lưới thông tin liên hệ -->
                <div class="contact-grid">

                    <!-- Số điện thoại -->
                    <div class="contact-item">
                        <div class="ci-label">📱 Số điện thoại</div>
                        <div class="ci-value">
                            <?php if ($kh_detail['phone']): ?>
                                <strong><?= htmlspecialchars($kh_detail['phone']) ?></strong>
                                <?php if ($kh_detail['phone_verified']): ?>
                                    <span style="background:#dcfce7;color:#166534;font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px">✓ Đã xác minh</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:var(--muted)">Chưa đăng ký</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Email / Gmail -->
                    <div class="contact-item">
                        <div class="ci-label">📧 Email / Gmail</div>
                        <div class="ci-value">
                            <?php if ($kh_detail['email'] && !str_ends_with($kh_detail['email'], '@phone.local')): ?>
                                <div>
                                    <div style="word-break:break-all"><?= htmlspecialchars($kh_detail['email']) ?></div>
                                    <?php if ($kh_detail['google_id']): ?>
                                        <span style="background:#fff3e0;color:#e65100;font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px;margin-top:3px;display:inline-block">
                                            📧 Liên kết Google
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span style="color:var(--muted)">Chưa đăng ký</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tên đăng nhập -->
                    <div class="contact-item">
                        <div class="ci-label">🔑 Tên đăng nhập</div>
                        <div class="ci-value">
                            <?php
                            $un = $kh_detail['username'] ?? '';
                            // Ẩn username hệ thống tự tạo (g_xxxxx / kh_0xxx)
                            $show = !preg_match('/^(g_|kh_)/', $un);
                            echo $show ? htmlspecialchars($un) : '<span style="color:var(--muted)">Tự động</span>';
                            ?>
                        </div>
                    </div>

                    <!-- Ngày tham gia -->
                    <div class="contact-item">
                        <div class="ci-label">📅 Ngày tham gia</div>
                        <div class="ci-value">
                            <?= date('d/m/Y', strtotime($kh_detail['created_at'])) ?>
                            <span style="font-size:11px;color:var(--muted)">
                                (<?= floor((time() - strtotime($kh_detail['created_at'])) / 86400) ?> ngày trước)
                            </span>
                        </div>
                    </div>

                    <!-- Phương thức đăng nhập -->
                    <div class="contact-item">
                        <div class="ci-label">🔒 Phương thức đăng nhập</div>
                        <div class="ci-value">
                            <?php echo match($kh_detail['login_method'] ?? 'password') {
                                'google'   => '<span style="background:#fff3e0;color:#e65100;font-size:12px;font-weight:700;padding:3px 10px;border-radius:8px">📧 Google OAuth</span>',
                                'phone'    => '<span style="background:#e8f5e9;color:#1b5e20;font-size:12px;font-weight:700;padding:3px 10px;border-radius:8px">📱 OTP Số điện thoại</span>',
                                default    => '<span style="background:#e3f2fd;color:#0d47a1;font-size:12px;font-weight:700;padding:3px 10px;border-radius:8px">🔑 Mật khẩu</span>',
                            }; ?>
                        </div>
                    </div>

                    <!-- Tổng đơn hàng -->
                    <div class="contact-item">
                        <div class="ci-label">📦 Tổng đơn hàng</div>
                        <div class="ci-value">
                            <strong style="font-size:16px;color:var(--green)"><?= count($kh_dons) ?></strong>
                            <span style="font-size:11px;color:var(--muted)">&nbsp;đơn gần đây</span>
                        </div>
                    </div>

                </div><!-- /contact-grid -->

                <!-- Nút liên hệ nhanh -->
                <div class="quick-contact">
                    <?php if ($kh_detail['phone']): ?>
                    <a href="tel:<?= htmlspecialchars($kh_detail['phone']) ?>"
                       class="btn btn-success btn-sm">
                        📞 Gọi <?= htmlspecialchars($kh_detail['phone']) ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($kh_detail['email'] && !str_ends_with($kh_detail['email'], '@phone.local')): ?>
                    <a href="mailto:<?= htmlspecialchars($kh_detail['email']) ?>"
                       class="btn btn-ghost btn-sm">
                        📧 Gửi email
                    </a>
                    <?php endif; ?>
                    <button class="btn btn-primary btn-sm"
                            onclick="document.getElementById('form-gui-tb').scrollIntoView({behavior:'smooth'})">
                        🔔 Gửi thông báo
                    </button>
                </div>

            </div><!-- /info-card -->


            <!-- === ĐƠNNHÀNG GẦN ĐÂY === -->
            <div class="info-card">
                <div style="padding:14px 16px;border-bottom:1px solid #f1f5f9;
                            display:flex;align-items:center;justify-content:space-between">
                    <div style="font-size:13px;font-weight:700">📦 Đơn Hàng Gần Đây</div>
                    <a href="dieuphoI_don_hang.php?search=<?= urlencode($kh_detail['full_name']) ?>"
                       style="font-size:12px;color:var(--green);text-decoration:none;font-weight:600">
                        Xem tất cả →
                    </a>
                </div>

                <?php if (empty($kh_dons)): ?>
                <div style="padding:30px;text-align:center;color:var(--muted);font-size:13px">
                    📭 Khách hàng chưa có đơn hàng nào.
                </div>
                <?php else: ?>
                <?php foreach ($kh_dons as $d):
                    $tt_info = $tt_map[$d['trang_thai']] ?? ['l' => $d['trang_thai'], 'c' => '#64748b'];
                ?>
                <div class="don-mini">
                    <div class="don-status-dot" style="background:<?= $tt_info['c'] ?>"></div>
                    <div class="don-mini-ma"><?= htmlspecialchars($d['ma_don']) ?></div>
                    <div class="don-mini-route">
                        <?= htmlspecialchars($d['tinh_lay'] ?? '—') ?> →
                        <?= htmlspecialchars($d['tinh_giao'] ?? '—') ?>
                    </div>
                    <span style="font-size:11px;background:#f1f5f9;padding:2px 7px;border-radius:6px;font-weight:600;color:<?= $tt_info['c'] ?>">
                        <?= $tt_info['l'] ?>
                    </span>
                    <span style="font-size:11px;color:var(--muted);min-width:70px;text-align:right">
                        <?= date('d/m/Y', strtotime($d['ngay_tao'])) ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>


            <!-- === THÔNG BÁO ĐÃ GỬI === -->
            <?php if (!empty($kh_thong_bao)): ?>
            <div class="info-card">
                <div style="padding:14px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;font-weight:700">
                    📨 Thông Báo Đã Gửi (<?= count($kh_thong_bao) ?> gần nhất)
                </div>
                <?php foreach ($kh_thong_bao as $tb):
                    $loai_style = [
                        'thong_tin' => ['bg'=>'#eff6ff','color'=>'#1e40af','icon'=>'ℹ️'],
                        'canh_bao'  => ['bg'=>'#fffbeb','color'=>'#92400e','icon'=>'⚠️'],
                        'khan_cap'  => ['bg'=>'#fef2f2','color'=>'#991b1b','icon'=>'🚨'],
                    ][$tb['loai']] ?? ['bg'=>'#f1f5f9','color'=>'#64748b','icon'=>'📢'];
                ?>
                <div style="padding:10px 16px;border-bottom:1px solid #f8fafc;display:flex;gap:10px;align-items:flex-start">
                    <div style="background:<?= $loai_style['bg'] ?>;color:<?= $loai_style['color'] ?>;
                                width:28px;height:28px;border-radius:8px;display:flex;align-items:center;
                                justify-content:center;font-size:14px;flex-shrink:0">
                        <?= $loai_style['icon'] ?>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px">
                            <?= htmlspecialchars($tb['tieu_de']) ?>
                            <?php if (!$tb['da_doc']): ?>
                                <span class="unread-dot" title="Chưa đọc"></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($tb['noi_dung']): ?>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px">
                            <?= htmlspecialchars(mb_strimwidth($tb['noi_dung'], 0, 80, '...')) ?>
                        </div>
                        <?php endif; ?>
                        <div style="font-size:11px;color:#94a3b8;margin-top:4px">
                            🕐 <?= date('d/m/Y H:i', strtotime($tb['created_at'])) ?>
                            · <?= $tb['da_doc'] ? '<span style="color:#10b981">✓ Đã đọc</span>' : '<span style="color:#ef4444">Chưa đọc</span>' ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>


            <!-- === FORM GỬI THÔNG BÁO === -->
            <div class="form-send" id="form-gui-tb">
                <h3>🔔 Gửi Thông Báo Hỗ Trợ</h3>
                <form method="POST">
                    <input type="hidden" name="kh_id" value="<?= $kh_detail['id'] ?>">
                    <input type="hidden" name="gui_thong_bao" value="1">

                    <div class="field" style="margin-bottom:12px">
                        <label>Loại thông báo</label>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:4px">
                            <label style="cursor:pointer;display:flex;align-items:center;gap:5px;font-size:13px">
                                <input type="radio" name="loai" value="thong_tin" checked>
                                <span style="background:#eff6ff;color:#1e40af;padding:4px 10px;border-radius:8px;font-weight:600">ℹ️ Thông tin</span>
                            </label>
                            <label style="cursor:pointer;display:flex;align-items:center;gap:5px;font-size:13px">
                                <input type="radio" name="loai" value="canh_bao">
                                <span style="background:#fffbeb;color:#92400e;padding:4px 10px;border-radius:8px;font-weight:600">⚠️ Cảnh báo</span>
                            </label>
                            <label style="cursor:pointer;display:flex;align-items:center;gap:5px;font-size:13px">
                                <input type="radio" name="loai" value="khan_cap">
                                <span style="background:#fef2f2;color:#991b1b;padding:4px 10px;border-radius:8px;font-weight:600">🚨 Khẩn cấp</span>
                            </label>
                        </div>
                    </div>

                    <div class="field" style="margin-bottom:12px">
                        <label>Tiêu đề *</label>
                        <input type="text" name="tieu_de" required
                               placeholder="VD: Đơn hàng của bạn đang được xử lý..."
                               style="width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none">
                    </div>

                    <div class="field" style="margin-bottom:16px">
                        <label>Nội dung *</label>
                        <textarea name="noi_dung" required rows="4"
                                  placeholder="Nhập nội dung thông báo hỗ trợ..."
                                  style="width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none;resize:vertical"></textarea>
                    </div>

                    <!-- Mẫu nhanh -->
                    <div style="margin-bottom:14px">
                        <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;margin-bottom:8px">
                            ⚡ Mẫu nhanh
                        </div>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <?php
                            $templates = [
                                ['Đơn hàng đang xử lý', 'Đơn hàng của bạn đang được chúng tôi xử lý và sẽ được giao trong thời gian sớm nhất. Cảm ơn bạn đã tin tưởng dịch vụ của chúng tôi!'],
                                ['Xe đã xuất phát', 'Xe vận chuyển đã xuất phát và đang trên đường đến địa điểm giao hàng. Chúng tôi sẽ thông báo ngay khi hàng đến nơi.'],
                                ['Hàng đã giao thành công', 'Hàng hóa của bạn đã được giao thành công. Vui lòng kiểm tra và xác nhận. Chân thành cảm ơn bạn đã sử dụng dịch vụ!'],
                                ['Liên hệ để cập nhật thông tin', 'Chúng tôi cần cập nhật một số thông tin về đơn hàng của bạn. Vui lòng liên hệ lại với điều phối viên để được hỗ trợ.'],
                            ];
                            foreach ($templates as $t): ?>
                            <button type="button"
                                    onclick="dung_mau('<?= addslashes($t[0]) ?>','<?= addslashes($t[1]) ?>')"
                                    style="font-size:11px;padding:5px 10px;border:1px solid var(--border);background:#f8fafc;border-radius:6px;cursor:pointer;font-family:inherit;transition:.15s"
                                    onmouseover="this.style.borderColor='var(--green)';this.style.color='var(--green)'"
                                    onmouseout="this.style.borderColor='var(--border)';this.style.color=''">
                                <?= htmlspecialchars($t[0]) ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div style="display:flex;gap:8px;align-items:center">
                        <button type="submit" class="btn btn-primary">
                            📨 Gửi thông báo đến <?= htmlspecialchars($kh_detail['full_name']) ?>
                        </button>
                        <span style="font-size:12px;color:var(--muted)">
                            Thông báo sẽ hiển thị trong app của khách hàng
                        </span>
                    </div>
                </form>
            </div>

            <?php endif; // kh_detail ?>

        </div><!-- /kh-detail-wrap -->
    </div><!-- /ht-layout -->

</div><!-- /content -->
</main>
</div><!-- /wrapper -->

<script>
// Điền mẫu nhanh vào form
function dung_mau(tieu_de, noi_dung) {
    const t = document.querySelector('[name="tieu_de"]');
    const n = document.querySelector('[name="noi_dung"]');
    if (t) t.value = tieu_de;
    if (n) n.value = noi_dung;
    document.getElementById('form-gui-tb')?.scrollIntoView({ behavior: 'smooth' });
}

// Tìm kiếm real-time — gõ 2 ký tự là tìm
let timer = null;
document.getElementById('searchKH')?.addEventListener('input', function() {
    clearTimeout(timer);
    const v = this.value.trim();
    timer = setTimeout(() => {
        if (v.length >= 2 || v.length === 0) {
            this.closest('form').submit();
        }
    }, 500);
});

// Focus input tìm kiếm khi load
document.addEventListener('DOMContentLoaded', () => {
    <?php if (!$kh_id): ?>
    document.getElementById('searchKH')?.focus();
    <?php endif; ?>
});
</script>
</body>
</html>
