<?php
// ============================================================
// dieuphoI_thong_bao.php - Trang thông báo nội bộ
// Điều phối viên xem và đánh dấu đã đọc các thông báo
// ============================================================
session_start(); require 'config.php'; require_role(['dieuphoI']);

$uid = $_SESSION['user_id'];
$msg = '';

// Đánh dấu MỘT thông báo đã đọc
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doc_mot'])) {
    $nid = (int)$_POST['notif_id'];
    $conn->query("UPDATE thong_bao SET da_doc=1 WHERE id=$nid AND nguoi_nhan_id=$uid");
}

// Đánh dấu TẤT CẢ đã đọc
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doc_tat_ca'])) {
    $conn->query("UPDATE thong_bao SET da_doc=1 WHERE nguoi_nhan_id=$uid");
    $msg = ['type' => 'success', 'text' => 'Đã đánh dấu tất cả là đã đọc!'];
}

// Xóa thông báo đã đọc cũ (trên 30 ngày)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xoa_cu'])) {
    $conn->query("DELETE FROM thong_bao WHERE nguoi_nhan_id=$uid AND da_doc=1 AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $msg = ['type' => 'success', 'text' => 'Đã xóa các thông báo cũ!'];
}

// Lọc: tất cả / chưa đọc / đã đọc
$filter = $_GET['filter'] ?? 'all';
$where = "WHERE nguoi_nhan_id=$uid";
if ($filter === 'unread') $where .= " AND da_doc=0";
if ($filter === 'read')   $where .= " AND da_doc=1";

// Lấy danh sách thông báo
$rows = $conn->query("SELECT * FROM thong_bao $where ORDER BY da_doc ASC, created_at DESC");

// Đếm chưa đọc
$chua_doc = $conn->query("SELECT COUNT(*) AS c FROM thong_bao WHERE nguoi_nhan_id=$uid AND da_doc=0")->fetch_assoc()['c'] ?? 0;
$tong     = $conn->query("SELECT COUNT(*) AS c FROM thong_bao WHERE nguoi_nhan_id=$uid")->fetch_assoc()['c'] ?? 0;

// Màu sắc theo loại thông báo
$loai_style = [
    'thong_tin' => ['icon' => 'ℹ️', 'bg' => '#eff6ff', 'border' => '#bfdbfe', 'badge_bg' => '#dbeafe', 'badge_color' => '#1e40af'],
    'canh_bao'  => ['icon' => '⚠️', 'bg' => '#fffbeb', 'border' => '#fde68a', 'badge_bg' => '#fef9c3', 'badge_color' => '#854d0e'],
    'khan_cap'  => ['icon' => '🚨', 'bg' => '#fef2f2', 'border' => '#fecaca', 'badge_bg' => '#fee2e2', 'badge_color' => '#991b1b'],
];

$active = 'thong_bao'; require 'sidebar_dieuphoI.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Thông Báo — Điều Phối</title>
<link rel="stylesheet" href="dieuphoI_layout.css">
<style>
/* Card mỗi thông báo */
.notif-card {
    display: flex;
    gap: 14px;
    padding: 16px;
    border-radius: 10px;
    border: 1px solid var(--border);
    margin-bottom: 10px;
    transition: box-shadow .2s;
    cursor: pointer;
}
.notif-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.08); }

/* Thông báo chưa đọc: đậm hơn */
.notif-card.unread {
    background: #fafcff;
    border-left: 4px solid var(--green);
}
.notif-card.unread .notif-title { font-weight: 700; }

/* Thông báo đã đọc: nhạt */
.notif-card.read {
    background: #fff;
    opacity: .75;
}

/* Icon loại thông báo */
.notif-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

/* Nội dung thông báo */
.notif-title   { font-size: 14px; color: var(--text); margin-bottom: 4px; }
.notif-content { font-size: 13px; color: var(--muted); line-height: 1.5; }
.notif-time    { font-size: 11px; color: #94a3b8; margin-top: 6px; }

/* Các nút lọc */
.filter-tabs {
    display: flex;
    gap: 4px;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 8px;
    width: fit-content;
    margin-bottom: 20px;
}
.filter-tab {
    padding: 7px 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    color: var(--muted);
    transition: .15s;
}
.filter-tab.active {
    background: #fff;
    color: var(--green);
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
}
</style>
</head>
<body>
<div class="wrapper">

<main class="main">
<div class="topbar">
    <div class="topbar-left">
        <div class="topbar-title">
            🔔 Thông Báo
            <?php if ($chua_doc > 0): ?>
                <span style="margin-left:8px; background:#ef4444; color:#fff; font-size:12px; font-weight:700; padding:2px 8px; border-radius:10px">
                    <?= $chua_doc ?> mới
                </span>
            <?php endif; ?>
        </div>
        <div class="breadcrumb">
            <a href="dieuphoI_dashboard.php">Dashboard</a> › Thông báo · Tổng: <?= $tong ?> thông báo
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
    <?php if (!empty($msg)): ?>
        <div class="alert alert-<?= $msg['type'] ?>"><?= $msg['text'] ?></div>
    <?php endif; ?>

    <!-- Thanh lọc + nút hành động -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px">

        <!-- Tab lọc -->
        <div class="filter-tabs">
            <a href="?filter=all"    class="filter-tab <?= $filter === 'all'    ? 'active' : '' ?>">Tất cả (<?= $tong ?>)</a>
            <a href="?filter=unread" class="filter-tab <?= $filter === 'unread' ? 'active' : '' ?>">Chưa đọc (<?= $chua_doc ?>)</a>
            <a href="?filter=read"   class="filter-tab <?= $filter === 'read'   ? 'active' : '' ?>">Đã đọc</a>
        </div>

        <!-- Nút hành động hàng loạt -->
        <div style="display:flex; gap:8px">
            <?php if ($chua_doc > 0): ?>
            <form method="POST" style="display:inline">
                <button type="submit" name="doc_tat_ca" class="btn btn-ghost btn-sm">
                    ✅ Đọc tất cả
                </button>
            </form>
            <?php endif; ?>
            <form method="POST" style="display:inline">
                <button type="submit" name="xoa_cu" class="btn btn-ghost btn-sm"
                        onclick="return confirm('Xóa thông báo đã đọc trên 30 ngày?')">
                    🗑️ Xóa cũ
                </button>
            </form>
        </div>
    </div>

    <!-- Danh sách thông báo -->
    <?php if ($rows && $rows->num_rows > 0): ?>
        <?php while ($n = $rows->fetch_assoc()):
            $style    = $loai_style[$n['loai']] ?? $loai_style['thong_tin'];
            $da_doc   = (bool)$n['da_doc'];
            $css_card = $da_doc ? 'read' : 'unread';
        ?>
        <div class="notif-card <?= $css_card ?>"
             style="<?= !$da_doc ? "background:{$style['bg']}; border-color:{$style['border']};" : '' ?>">

            <!-- Icon loại -->
            <div class="notif-icon" style="background:<?= $style['badge_bg'] ?>">
                <?= $style['icon'] ?>
            </div>

            <!-- Nội dung -->
            <div style="flex:1; min-width:0">
                <div class="notif-title"><?= htmlspecialchars($n['tieu_de']) ?></div>

                <?php if ($n['noi_dung']): ?>
                    <div class="notif-content"><?= htmlspecialchars($n['noi_dung']) ?></div>
                <?php endif; ?>

                <div style="display:flex; align-items:center; gap:10px; margin-top:8px; flex-wrap:wrap">
                    <!-- Thời gian -->
                    <span class="notif-time">
                        🕐 <?= date('d/m/Y H:i', strtotime($n['created_at'])) ?>
                    </span>

                    <!-- Badge loại -->
                    <span style="background:<?= $style['badge_bg'] ?>; color:<?= $style['badge_color'] ?>; font-size:10px; font-weight:700; padding:2px 8px; border-radius:10px; text-transform:uppercase">
                        <?= $n['loai'] === 'khan_cap' ? 'Khẩn cấp' : ($n['loai'] === 'canh_bao' ? 'Cảnh báo' : 'Thông tin') ?>
                    </span>

                    <!-- Link đến trang liên quan -->
                    <?php if ($n['lien_ket']): ?>
                        <a href="<?= htmlspecialchars($n['lien_ket']) ?>"
                           style="font-size:12px; color:var(--green); text-decoration:none; font-weight:600">
                            → Xem chi tiết
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Nút đánh dấu đã đọc (chỉ hiện nếu chưa đọc) -->
            <?php if (!$da_doc): ?>
            <div style="flex-shrink:0">
                <form method="POST">
                    <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                    <button type="submit" name="doc_mot"
                            class="btn btn-ghost btn-sm" title="Đánh dấu đã đọc">
                        ✓
                    </button>
                </form>
            </div>
            <?php else: ?>
                <!-- Dấu đã đọc -->
                <div style="flex-shrink:0; color:#94a3b8; font-size:18px" title="Đã đọc">✓</div>
            <?php endif; ?>

        </div>
        <?php endwhile; ?>

    <?php else: ?>
        <div class="empty-state">
            <span class="ei">🔔</span>
            <p>Không có thông báo nào<?= $filter !== 'all' ? ' phù hợp với bộ lọc' : '' ?>.</p>
        </div>
    <?php endif; ?>

</div><!-- /content -->
</main>
</div><!-- /wrapper -->
</body>
</html>
