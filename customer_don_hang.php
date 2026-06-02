<?php
// ============================================================
// customer_don_hang.php — Đơn Hàng Của Tôi
// ============================================================
session_start();
require 'config.php';
require_role(['khachhang']);

$uid       = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? '';
$phone     = $_SESSION['phone']     ?? '';

// Lọc & phân trang
$trang_thai = $_GET['trang_thai'] ?? '';
$search     = trim($_GET['search'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$per        = 10;
$offset     = ($page - 1) * $per;

// Build WHERE — khách chỉ thấy đơn của mình
$fn_esc = mysqli_real_escape_string($conn, $full_name);
$ph_esc = mysqli_real_escape_string($conn, $phone);
$w = "WHERE (dh.ten_khach='$fn_esc' OR dh.dien_thoai_kh='$ph_esc')";

if ($trang_thai) $w .= " AND dh.trang_thai='" . mysqli_real_escape_string($conn, $trang_thai) . "'";
if ($search)     $w .= " AND (dh.ma_don LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'
                           OR dh.tinh_lay LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'
                           OR dh.tinh_giao LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";

$total = $conn->query("SELECT COUNT(*) AS c FROM don_hang dh $w")->fetch_assoc()['c'] ?? 0;
$pages = max(1, ceil($total / $per));

$rows = $conn->query(
    "SELECT dh.*, x.bien_so, tx.ho_ten AS ten_tai_xe, tx.so_dien_thoai AS sdt_tx
     FROM don_hang dh
     LEFT JOIN xe x      ON dh.xe_id = x.id
     LEFT JOIN tai_xe tx ON dh.tai_xe_id = tx.id
     $w ORDER BY dh.ngay_tao DESC LIMIT $per OFFSET $offset"
);

// Thống kê nhanh
$stats = [];
foreach (['cho_duyet','dang_xu_ly','dang_lay_hang','dang_van_chuyen','da_giao','hoan_thanh','huy'] as $tt) {
    $stats[$tt] = $conn->query(
        "SELECT COUNT(*) AS c FROM don_hang
         WHERE (ten_khach='$fn_esc' OR dien_thoai_kh='$ph_esc') AND trang_thai='$tt'"
    )->fetch_assoc()['c'] ?? 0;
}
$tong_don = array_sum($stats);

$tt_map = [
    'cho_duyet'       => ['l' => 'Chờ duyệt',     'c' => 'b-cho_duyet'],
    'dang_xu_ly'      => ['l' => 'Đang xử lý',    'c' => 'b-dang_xu_ly'],
    'dang_lay_hang'   => ['l' => 'Đang lấy hàng', 'c' => 'b-dang_lay_hang'],
    'dang_van_chuyen' => ['l' => 'Đang chạy',     'c' => 'b-dang_van_chuyen'],
    'da_giao'         => ['l' => 'Đã giao',         'c' => 'b-da_giao'],
    'hoan_thanh'      => ['l' => 'Hoàn thành',    'c' => 'b-hoan_thanh'],
    'da_thanh_toan'   => ['l' => 'Đã TT',           'c' => 'b-da_thanh_toan'],
    'huy'             => ['l' => 'Đã hủy',          'c' => 'b-huy'],
];

$active = 'don_hang';
require 'sidebar_customer.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn Hàng Của Tôi</title>
    <link rel="stylesheet" href="customer_layout.css">
    <style>
    .filter-bar { display:flex; gap:8px; flex-wrap:wrap; flex:1; }
    .filter-bar select,
    .filter-bar input {
        padding:8px 12px;
        border:1.5px solid var(--border);
        border-radius:8px;
        font-size:13px;
        font-family:inherit;
        outline:none;
    }
    .filter-bar select:focus,
    .filter-bar input:focus { border-color:var(--primary); }

    /* Modal chi tiết đơn */
    .detail-row {
        display:flex; padding:10px 0;
        border-bottom:1px solid #f1f5f9;
        font-size:13px;
    }
    .detail-row:last-child { border-bottom:none; }
    .dr-label { width:150px; color:var(--muted); font-weight:600; flex-shrink:0; }
    .dr-value { color:var(--text); flex:1; }
    </style>
</head>
<body>
<div class="app">
<?php require 'sidebar_customer.php'; ?>

<main class="main">
    <div class="topbar">
        <div>
            <div class="topbar-title">📋 Đơn Hàng Của Tôi</div>
            <div class="breadcrumb">
                <a href="customer_dashboard.php">Dashboard</a> › Đơn hàng · Tổng: <?= number_format($total) ?> đơn
            </div>
        </div>
        <div class="user-chip">
            <div class="chip-avatar"><?= mb_strtoupper(mb_substr($full_name, 0, 1)) ?></div>
            <div>
                <div class="chip-name"><?= htmlspecialchars($full_name) ?></div>
                <div class="chip-role">Khách Hàng</div>
            </div>
        </div>
    </div>

    <div class="content">

        <!-- Stat cards -->
        <div class="stat-cards" style="margin-bottom:20px">
            <div class="stat-card">
                <div class="sc-icon">📦</div>
                <div class="sc-label">Tổng đơn hàng</div>
                <div class="sc-value"><?= $tong_don ?></div>
                <div class="sc-sub">Tất cả</div>
            </div>
            <div class="stat-card" style="border-top-color:#f59e0b">
                <div class="sc-icon">⏳</div>
                <div class="sc-label">Chờ duyệt</div>
                <div class="sc-value" style="color:#f59e0b"><?= $stats['cho_duyet'] ?></div>
                <div class="sc-sub">Đang chờ</div>
            </div>
            <div class="stat-card" style="border-top-color:#3b82f6">
                <div class="sc-icon">🚛</div>
                <div class="sc-label">Đang vận chuyển</div>
                <div class="sc-value" style="color:#3b82f6">
                    <?= $stats['dang_van_chuyen'] + $stats['dang_lay_hang'] + $stats['dang_xu_ly'] ?>
                </div>
                <div class="sc-sub">Trên đường</div>
            </div>
            <div class="stat-card" style="border-top-color:#10b981">
                <div class="sc-icon">✅</div>
                <div class="sc-label">Hoàn thành</div>
                <div class="sc-value" style="color:#10b981">
                    <?= $stats['hoan_thanh'] + $stats['da_giao'] ?>
                </div>
                <div class="sc-sub">Đã giao xong</div>
            </div>
            <div class="stat-card" style="border-top-color:#ef4444">
                <div class="sc-icon">❌</div>
                <div class="sc-label">Đã hủy</div>
                <div class="sc-value" style="color:#ef4444"><?= $stats['huy'] ?></div>
                <div class="sc-sub">Đơn bị hủy</div>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div class="page-header">
            <form method="GET" class="filter-bar">
                <div class="search-box" style="flex:1;min-width:200px">
                    🔍<input type="text" name="search"
                        placeholder="Tìm mã đơn, tỉnh/TP..."
                        value="<?= htmlspecialchars($search) ?>">
                </div>
                <select name="trang_thai" onchange="this.form.submit()">
                    <option value="">— Tất cả trạng thái —</option>
                    <?php foreach ($tt_map as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $trang_thai === $k ? 'selected' : '' ?>>
                        <?= $v['l'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Lọc</button>
                <a href="customer_don_hang.php" class="btn btn-ghost">Xóa lọc</a>
            </form>
            <a href="customer_tao_don.php" class="btn btn-primary">➕ Tạo Đơn Mới</a>
        </div>

        <!-- Bảng đơn hàng -->
        <div class="table-wrap">
            <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Tuyến đường</th>
                        <th>Xe / Tài xế</th>
                        <th>Loại hàng</th>
                        <th>Ngày tạo</th>
                        <th>Ngày giao DK</th>
                        <th>Cước phí</th>
                        <th>Trạng thái</th>
                        <th>Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($rows && $rows->num_rows > 0):
                    while ($r = $rows->fetch_assoc()):
                        $tt_info = $tt_map[$r['trang_thai']] ?? ['l' => $r['trang_thai'], 'c' => ''];
                ?>
                <tr>
                    <td>
                        <strong style="color:var(--primary)"><?= htmlspecialchars($r['ma_don']) ?></strong>
                        <div style="font-size:11px;color:var(--muted)">
                            <?= date('d/m/Y', strtotime($r['ngay_tao'])) ?>
                        </div>
                    </td>
                    <td style="font-size:12px">
                        📍 <strong><?= htmlspecialchars($r['tinh_lay'] ?? '—') ?></strong><br>
                        🏁 <strong><?= htmlspecialchars($r['tinh_giao'] ?? '—') ?></strong>
                    </td>
                    <td style="font-size:12px">
                        <?php if ($r['bien_so']): ?>
                            <div>🚛 <?= htmlspecialchars($r['bien_so']) ?></div>
                            <div style="color:var(--muted)">👤 <?= htmlspecialchars($r['ten_tai_xe'] ?? '—') ?></div>
                            <?php if ($r['sdt_tx'] && in_array($r['trang_thai'], ['dang_van_chuyen','dang_lay_hang'])): ?>
                            <a href="tel:<?= htmlspecialchars($r['sdt_tx']) ?>"
                               style="font-size:11px;color:var(--primary);font-weight:700;text-decoration:none">
                                📞 <?= htmlspecialchars($r['sdt_tx']) ?>
                            </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:var(--muted);font-style:italic">Chưa phân xe</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px">
                        <?= htmlspecialchars($r['loai_hang'] ?? '—') ?>
                        <?php if ($r['trong_luong']): ?>
                            <div style="color:var(--muted)"><?= $r['trong_luong'] ?> tấn</div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:var(--muted)">
                        <?= date('d/m/Y', strtotime($r['ngay_tao'])) ?>
                    </td>
                    <td style="font-size:12px;color:var(--muted)">
                        <?= $r['ngay_giao_du_kien']
                            ? date('d/m/Y', strtotime($r['ngay_giao_du_kien']))
                            : '—' ?>
                    </td>
                    <td style="font-weight:700;color:var(--primary)">
                        ₫<?= number_format($r['doanh_thu']) ?>
                    </td>
                    <td>
                        <span class="badge <?= $tt_info['c'] ?>"><?= $tt_info['l'] ?></span>
                    </td>
                    <td>
                        <button class="btn btn-ghost btn-sm"
                                onclick="xemChiTiet(<?= htmlspecialchars(json_encode($r, JSON_UNESCAPED_UNICODE)) ?>)">
                            👁 Xem
                        </button>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <div class="ei">📋</div>
                            <p>Không tìm thấy đơn hàng nào.</p>
                            <a href="customer_tao_don.php" class="btn btn-primary btn-sm" style="margin-top:12px">
                                ➕ Tạo đơn ngay
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>

            <!-- Phân trang -->
            <?php if ($pages > 1): ?>
            <div class="pagination">
                <?php
                $q = http_build_query(['search' => $search, 'trang_thai' => $trang_thai]);
                for ($i = 1; $i <= $pages; $i++):
                ?>
                <a href="?page=<?= $i ?>&<?= $q ?>"
                   class="page-btn <?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</div>

<!-- Modal chi tiết đơn hàng -->
<div class="modal-overlay" id="modal_chitiet">
<div class="modal-box" style="max-width:620px">
    <div class="modal-header">
        <h3>📋 Chi Tiết Đơn Hàng — <span id="ct_ma"></span></h3>
        <button class="modal-close"
                onclick="document.getElementById('modal_chitiet').classList.remove('open')">✕</button>
    </div>
    <div id="ct_content"></div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
        <button class="btn btn-ghost"
                onclick="document.getElementById('modal_chitiet').classList.remove('open')">Đóng</button>
        <a id="ct_loTrinh" href="#" class="btn btn-primary" style="display:none">
            📍 Xem lộ trình
        </a>
    </div>
</div>
</div>

<script>
const tt_labels = {
    cho_duyet:       'Chờ duyệt',
    dang_xu_ly:      'Đang xử lý',
    dang_lay_hang:   'Đang lấy hàng',
    dang_van_chuyen: 'Đang vận chuyển',
    da_giao:         'Đã giao',
    hoan_thanh:      'Hoàn thành',
    da_thanh_toan:   'Đã thanh toán',
    huy:             'Đã hủy',
};

function xemChiTiet(d) {
    document.getElementById('ct_ma').textContent = d.ma_don;

    const rows = [
        ['Mã đơn hàng',       d.ma_don],
        ['Tuyến đường',       `📍 ${d.tinh_lay || '—'} → 🏁 ${d.tinh_giao || '—'}`],
        ['Địa chỉ lấy hàng',  d.dia_chi_lay || '—'],
        ['Địa chỉ giao hàng', d.dia_chi_giao || '—'],
        ['Loại hàng',         d.loai_hang || '—'],
        ['Trọng lượng',       d.trong_luong ? d.trong_luong + ' tấn' : '—'],
        ['Xe vận chuyển',     d.bien_so || 'Chưa phân'],
        ['Tài xế',            d.ten_tai_xe || 'Chưa phân'],
        ['SĐT tài xế',        d.sdt_tx || '—'],
        ['Ngày tạo đơn',      d.ngay_tao ? d.ngay_tao.slice(0,10) : '—'],
        ['Ngày lấy hàng',     d.ngay_lay_hang ? d.ngay_lay_hang.slice(0,10) : '—'],
        ['Giao dự kiến',      d.ngay_giao_du_kien ? d.ngay_giao_du_kien.slice(0,10) : '—'],
        ['Giao thực tế',      d.ngay_giao_thuc_te ? d.ngay_giao_thuc_te.slice(0,10) : 'Chưa giao'],
        ['Cước phí',          '₫' + Number(d.doanh_thu || 0).toLocaleString('vi-VN')],
        ['Ghi chú',           d.ghi_chu || '—'],
        ['Trạng thái',        tt_labels[d.trang_thai] || d.trang_thai],
    ];

    let html = rows.map(([k, v]) =>
        `<div class="detail-row">
            <span class="dr-label">${k}</span>
            <span class="dr-value">${v}</span>
        </div>`
    ).join('');

    document.getElementById('ct_content').innerHTML = html;

    // Nút xem lộ trình
    const btnLT = document.getElementById('ct_loTrinh');
    if (['dang_xu_ly','dang_lay_hang','dang_van_chuyen','da_giao','hoan_thanh'].includes(d.trang_thai)) {
        btnLT.style.display = 'inline-flex';
        btnLT.href = `customer_lo_trinh.php?search=${encodeURIComponent(d.ma_don)}`;
    } else {
        btnLT.style.display = 'none';
    }

    document.getElementById('modal_chitiet').classList.add('open');
}

// Đóng modal khi click ngoài
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});
</script>
</body>
</html>
