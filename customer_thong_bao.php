<?php
// ============================================================
// customer_thong_bao.php — Thông Báo Khách Hàng
// ============================================================
session_start();
require 'config.php';
require_role(['khachhang']);

$uid = $_SESSION['user_id'];
$msg = null;

// Đánh dấu một thông báo đã đọc
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doc_mot'])) {
    $nid = (int)$_POST['notif_id'];
    $conn->query("UPDATE thong_bao SET da_doc=1 WHERE id=$nid AND nguoi_nhan_id=$uid");
}

// Đánh dấu tất cả đã đọc
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doc_tat_ca'])) {
    $conn->query("UPDATE thong_bao SET da_doc=1 WHERE nguoi_nhan_id=$uid");
    $msg = ['type' => 'success', 'text' => '✅ Đã đánh dấu tất cả là đã đọc!'];
}

// Lọc
$filter = $_GET['filter'] ?? 'all';
$where  = "WHERE nguoi_nhan_id=$uid";
if ($filter === 'unread') $where .= " AND da_doc=0";
if ($filter === 'read')   $where .= " AND da_doc=1";

$rows     = $conn->query("SELECT * FROM thong_bao $where ORDER BY da_doc ASC, created_at DESC");
$chua_doc = $conn->query("SELECT COUNT(*) AS c FROM thong_bao WHERE nguoi_nhan_id=$uid AND da_doc=0")->fetch_assoc()['c'] ?? 0;
$tong     = $conn->query("SELECT COUNT(*) AS c FROM thong_bao WHERE nguoi_nhan_id=$uid")->fetch_assoc()['c'] ?? 0;

$loai_style = [
    'thong_tin' => ['icon' => 'ℹ️', 'bg' => '#ede9fe', 'color' => '#5b21b6', 'label' => 'Thông tin'],
    'canh_bao'  => ['icon' => '⚠️', 'bg' => '#fef9c3', 'color' => '#854d0e', 'label' => 'Cảnh báo'],
    'khan_cap'  => ['icon' => '🚨', 'bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Khẩn cấp'],
];

$active = 'thong_bao';
require 'sidebar_customer.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Báo — Khách Hàng</title>
    <link rel="stylesheet" href="customer_layout.css">
    <style>
    .notif-card {
        display: flex;
        gap: 14px;
        padding: 16px;
        border-radius: 10px;
        border: 1px solid var(--border);
        margin-bottom: 10px;
        transition: box-shadow .2s;
        background: #fff;
    }
    .notif-card.unread {
        background: #faf5ff;
        border-left: 4px solid var(--primary);
    }
    .notif-card.unread .notif-title { font-weight: 700; }
    .notif-card.read { opacity: .75; }
    .notif-icon {
        width: 42px; height: 42px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; flex-shrink: 0;
    }
    .notif-title { font-size: 14px; color: var(--text); margin-bottom: 4px; }
    .notif-time  { font-size: 11px; color: #94a3b8; margin-top: 6px; }

    .filter-tabs {
        display: flex; gap: 4px;
        background: #f1f5f9; padding: 4px;
        border-radius: 8px; width: fit-content;
        margin-bottom: 20px;
    }
    .filter-tab {
        padding: 7px 16px; border-radius: 6px;
        font-size: 13px; font-weight: 600;
        text-decoration: none; color: var(--muted); transition: .15s;
    }
    .filter-tab.active {
        background: #fff; color: var(--primary);
        box-shadow: 0 1px 3px rgba(0,0,0,.08);
    }
    </style>
</head>
<body>
<div class="app">
<?php require 'sidebar_customer.php'; ?>

<main class="main">
    <div class="topbar">
        <div>
            <div class="topbar-title">
                🔔 Thông Báo
                <?php if ($chua_doc > 0): ?>
                    <span style="margin-left:8px;background:#ef4444;color:#fff;font-size:12px;font-weight:700;padding:2px 8px;border-radius:10px">
                        <?= $chua_doc ?> mới
                    </span>
                <?php endif; ?>
            </div>
            <div class="breadcrumb">
                <a href="customer_dashboard.php">Dashboard</a> › Thông báo · Tổng: <?= $tong ?>
            </div>
        </div>
        <div class="user-chip">
            <div class="chip-avatar"><?= mb_strtoupper(mb_substr($_SESSION['full_name'] ?? 'K', 0, 1)) ?></div>
            <div>
                <div class="chip-name"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></div>
                <div class="chip-role">Khách Hàng</div>
            </div>
        </div>
    </div>

    <div class="content">
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg['type'] ?>"><?= $msg['text'] ?></div>
        <?php endif; ?>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
            <div class="filter-tabs">
                <a href="?filter=all"    class="filter-tab <?= $filter==='all'?'active':'' ?>">Tất cả (<?= $tong ?>)</a>
                <a href="?filter=unread" class="filter-tab <?= $filter==='unread'?'active':'' ?>">Chưa đọc (<?= $chua_doc ?>)</a>
                <a href="?filter=read"   class="filter-tab <?= $filter==='read'?'active':'' ?>">Đã đọc</a>
            </div>
            <?php if ($chua_doc > 0): ?>
            <form method="POST">
                <button type="submit" name="doc_tat_ca" class="btn btn-ghost btn-sm">✅ Đọc tất cả</button>
            </form>
            <?php endif; ?>
        </div>

        <?php if ($rows && $rows->num_rows > 0):
            while ($n = $rows->fetch_assoc()):
                $style  = $loai_style[$n['loai']] ?? $loai_style['thong_tin'];
                $da_doc = (bool)$n['da_doc'];
        ?>
        <div class="notif-card <?= $da_doc ? 'read' : 'unread' ?>">
            <div class="notif-icon" style="background:<?= $style['bg'] ?>">
                <?= $style['icon'] ?>
            </div>
            <div style="flex:1;min-width:0">
                <div class="notif-title"><?= htmlspecialchars($n['tieu_de']) ?></div>
                <?php if ($n['noi_dung']): ?>
                    <div style="font-size:13px;color:var(--muted);line-height:1.5"><?= htmlspecialchars($n['noi_dung']) ?></div>
                <?php endif; ?>
                <div style="display:flex;align-items:center;gap:10px;margin-top:8px;flex-wrap:wrap">
                    <span class="notif-time">🕐 <?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></span>
                    <span style="background:<?= $style['bg'] ?>;color:<?= $style['color'] ?>;font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px">
                        <?= $style['label'] ?>
                    </span>
                    <?php if ($n['lien_ket']): ?>
                        <a href="<?= htmlspecialchars($n['lien_ket']) ?>" style="font-size:12px;color:var(--primary);text-decoration:none;font-weight:600">
                            → Xem chi tiết
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!$da_doc): ?>
            <div style="flex-shrink:0">
                <form method="POST">
                    <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                    <button type="submit" name="doc_mot" class="btn btn-ghost btn-sm" title="Đánh dấu đã đọc">✓</button>
                </form>
            </div>
            <?php else: ?>
                <div style="flex-shrink:0;color:#94a3b8;font-size:18px">✓</div>
            <?php endif; ?>
        </div>
        <?php endwhile; else: ?>
        <div class="empty-state">
            <span class="ei">🔔</span>
            <p>Không có thông báo nào<?= $filter !== 'all' ? ' phù hợp' : '' ?>.</p>
        </div>
        <?php endif; ?>
    </div>
</main>
</div>
</body>
</html>
