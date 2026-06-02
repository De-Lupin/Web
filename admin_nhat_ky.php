<?php
session_start(); require 'config.php'; require_role(['admin']);

// Xóa log cũ (chỉ admin được xóa)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['xoa_log'])) {
    $ngay = $_POST['xoa_truoc_ngay'] ?? '';
    if ($ngay) {
        $conn->query("DELETE FROM audit_log WHERE created_at < '".mysqli_real_escape_string($conn,$ngay)."'");
        $msg = ['type'=>'success','text'=>'Đã xóa log trước ngày '.date('d/m/Y',strtotime($ngay)).'!'];
    }
}

$msg = $msg ?? null;

// Bộ lọc
$search   = trim($_GET['search']   ?? '');
$action_f = $_GET['action']        ?? '';
$user_f   = trim($_GET['user']     ?? '');
$from     = $_GET['from']          ?? '';
$to       = $_GET['to']            ?? '';
$page     = max(1,(int)($_GET['page']??1));
$per      = 20; $offset = ($page-1)*$per;

$w = "WHERE 1=1";
if ($search)   $w .= " AND (username LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR detail LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR ip_address LIKE '%".mysqli_real_escape_string($conn,$search)."%')";
if ($action_f) $w .= " AND action='".mysqli_real_escape_string($conn,$action_f)."'";
if ($user_f)   $w .= " AND username='".mysqli_real_escape_string($conn,$user_f)."'";
if ($from)     $w .= " AND DATE(created_at)>='".mysqli_real_escape_string($conn,$from)."'";
if ($to)       $w .= " AND DATE(created_at)<='".mysqli_real_escape_string($conn,$to)."'";

$total = $conn->query("SELECT COUNT(*) AS c FROM audit_log $w")->fetch_assoc()['c'];
$pages = max(1,ceil($total/$per));
$rows  = $conn->query("SELECT * FROM audit_log $w ORDER BY created_at DESC LIMIT $per OFFSET $offset");

// Thống kê action
$action_stats = $conn->query("SELECT action, COUNT(*) AS c FROM audit_log GROUP BY action ORDER BY c DESC")->fetch_all(MYSQLI_ASSOC);

// Danh sách action để lọc
$actions = $conn->query("SELECT DISTINCT action FROM audit_log ORDER BY action")->fetch_all(MYSQLI_ASSOC);

// Thống kê 7 ngày gần đây
$week_stats = $conn->query("SELECT DATE(created_at) AS ngay, COUNT(*) AS c FROM audit_log WHERE created_at >= DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY ngay ASC")->fetch_all(MYSQLI_ASSOC);

$action_color = [
    'LOGIN'        => ['bg'=>'#d5f5e3','color'=>'#1e8449'],
    'LOGOUT'       => ['bg'=>'#e2e3e5','color'=>'#555'],
    'LOGIN_FAILED' => ['bg'=>'#fdf2f0','color'=>'#c0392b'],
    'ADMIN_LOGIN'  => ['bg'=>'#e8e0ff','color'=>'#4f3ba9'],
    'ADMIN_LOGIN_FAILED'=>['bg'=>'#fdf2f0','color'=>'#c0392b'],
];

$active = 'nhat_ky'; require 'sidebar_admin.php';
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Nhật Ký Hệ Thống</title>
<link rel="stylesheet" href="admin_layout.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.filter-grid{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.filter-grid input,.filter-grid select{padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none}
.filter-grid input:focus,.filter-grid select:focus{border-color:var(--primary)}
.action-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700}
.log-ip{font-size:11px;color:var(--muted);font-family:monospace}
.stat-action{display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f4f6f8;font-size:13px}
.stat-action:last-child{border-bottom:none}
.danger-zone{background:#fdf2f0;border:1px solid #f5c6cb;border-radius:12px;padding:20px;margin-top:20px}
.danger-zone h3{font-size:14px;font-weight:700;color:#c0392b;margin-bottom:12px}
</style>
</head><body>
<div class="app">
<?php require 'sidebar_admin.php'; ?>

<main class="main">
<div class="topbar">
    <div>
        <div class="topbar-title">📋 Nhật Ký Hệ Thống</div>
        <div class="breadcrumb"><a href="admin_dashboard.php">Dashboard</a> › Nhật ký · Tổng: <?= number_format($total) ?> bản ghi</div>
    </div>
    <div class="user-chip">
        <div class="chip-avatar"><?= mb_strtoupper(mb_substr($_SESSION['full_name']??'A',0,1)) ?></div>
        <div><div class="chip-name"><?= htmlspecialchars($_SESSION['full_name']??'Admin') ?></div>
        <div class="chip-role">Super Admin</div></div>
    </div>
</div>

<div class="content">
    <?php if(!empty($msg)): ?><div class="alert alert-<?=$msg['type']?>"><?=$msg['text']?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start">

        <!-- Cột chính -->
        <div>
            <!-- Bộ lọc -->
            <div style="background:#fff;border-radius:12px;padding:16px 18px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:18px">
                <form method="GET" class="filter-grid">
                    <div class="search-box" style="flex:1;min-width:200px">
                        🔍<input type="text" name="search" placeholder="Tìm username, IP, chi tiết..." value="<?=htmlspecialchars($search)?>">
                    </div>
                    <select name="action" onchange="this.form.submit()">
                        <option value="">— Tất cả hành động —</option>
                        <?php foreach($actions as $a): ?>
                        <option value="<?=$a['action']?>" <?=$action_f===$a['action']?'selected':''?>><?=$a['action']?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="user" placeholder="Username..." value="<?=htmlspecialchars($user_f)?>" style="width:140px">
                    <input type="date" name="from" value="<?=htmlspecialchars($from)?>" title="Từ ngày">
                    <input type="date" name="to"   value="<?=htmlspecialchars($to)?>"   title="Đến ngày">
                    <button type="submit" class="btn btn-primary">Lọc</button>
                    <a href="admin_nhat_ky.php" class="btn btn-ghost">Xóa lọc</a>
                </form>
            </div>

            <!-- Bảng log -->
            <div class="table-wrap">
                <div class="table-scroll">
                <table>
                    <thead><tr>
                        <th style="width:40px">#</th>
                        <th>Người dùng</th>
                        <th>Hành động</th>
                        <th>Chi tiết</th>
                        <th>IP Address</th>
                        <th>Thời gian</th>
                    </tr></thead>
                    <tbody>
                    <?php if($rows && $rows->num_rows>0):
                        $stt = ($page-1)*$per+1;
                        while($r=$rows->fetch_assoc()):
                        $ac = $action_color[$r['action']] ?? ['bg'=>'#f0f2ff','color'=>'#667eea'];
                    ?>
                    <tr>
                        <td style="color:var(--muted);font-size:12px"><?= $stt++ ?></td>
                        <td>
                            <div style="font-weight:700;font-size:13px"><?= htmlspecialchars($r['username']??'—') ?></div>
                            <?php if($r['user_id']): ?>
                            <div style="font-size:11px;color:var(--muted)">ID: <?= $r['user_id'] ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="action-badge" style="background:<?=$ac['bg']?>;color:<?=$ac['color']?>">
                                <?= htmlspecialchars($r['action']) ?>
                            </span>
                        </td>
                        <td style="font-size:12px;max-width:300px;color:var(--muted)">
                            <?= htmlspecialchars($r['detail']??'—') ?>
                        </td>
                        <td class="log-ip"><?= htmlspecialchars($r['ip_address']??'—') ?></td>
                        <td style="font-size:12px;white-space:nowrap;color:var(--muted)">
                            <?= date('d/m/Y',strtotime($r['created_at'])) ?><br>
                            <span style="color:#bdc3c7"><?= date('H:i:s',strtotime($r['created_at'])) ?></span>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="6">
                        <div class="empty-state"><div class="ei">📋</div><p>Không tìm thấy bản ghi nào.</p></div>
                    </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>

                <!-- Phân trang -->
                <?php if($pages>1): ?>
                <div class="pagination">
                    <?php
                    $q = http_build_query(['search'=>$search,'action'=>$action_f,'user'=>$user_f,'from'=>$from,'to'=>$to]);
                    for($i=1;$i<=$pages;$i++): ?>
                    <a href="?page=<?=$i?>&<?=$q?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Vùng nguy hiểm: Xóa log -->
            <div class="danger-zone">
                <h3>⚠️ Xóa Nhật Ký Cũ</h3>
                <p style="font-size:13px;color:var(--muted);margin-bottom:14px">Xóa toàn bộ log trước một ngày nhất định. Thao tác này không thể hoàn tác!</p>
                <form method="POST" onsubmit="return confirm('Bạn chắc chắn muốn xóa log trước ngày này? Không thể hoàn tác!')">
                    <div style="display:flex;gap:10px;align-items:center">
                        <input type="date" name="xoa_truoc_ngay" required
                               max="<?= date('Y-m-d',strtotime('-7 days')) ?>"
                               style="padding:9px 12px;border:1.5px solid #f5c6cb;border-radius:8px;font-size:13px">
                        <button type="submit" name="xoa_log" class="btn btn-danger">🗑️ Xóa Log Cũ</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cột phải: Thống kê -->
        <div style="display:flex;flex-direction:column;gap:16px">

            <!-- Biểu đồ 7 ngày -->
            <div style="background:#fff;border-radius:12px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,.06)">
                <h3 style="font-size:14px;font-weight:700;color:var(--primary);margin-bottom:14px">📈 Hoạt động 7 ngày</h3>
                <?php if(!empty($week_stats)): ?>
                <canvas id="chartWeek" height="120"></canvas>
                <?php else: ?>
                <div class="empty-state" style="padding:20px"><div class="ei">📈</div><p>Chưa có dữ liệu.</p></div>
                <?php endif; ?>
            </div>

            <!-- Thống kê theo action -->
            <div style="background:#fff;border-radius:12px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,.06)">
                <h3 style="font-size:14px;font-weight:700;color:var(--primary);margin-bottom:14px">📊 Thống kê hành động</h3>
                <?php
                $max_c = !empty($action_stats) ? max(array_column($action_stats,'c')) : 1;
                foreach($action_stats as $as):
                    $ac = $action_color[$as['action']] ?? ['bg'=>'#f0f2ff','color'=>'#667eea'];
                ?>
                <div class="stat-action">
                    <div style="display:flex;align-items:center;gap:8px">
                        <span class="action-badge" style="background:<?=$ac['bg']?>;color:<?=$ac['color']?>">
                            <?= htmlspecialchars($as['action']) ?>
                        </span>
                    </div>
                    <div style="text-align:right">
                        <div style="font-weight:800;font-size:15px;color:var(--text)"><?= number_format($as['c']) ?></div>
                        <div style="font-size:10px;color:var(--muted)"><?= round($as['c']/($total?:1)*100,1) ?>%</div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($action_stats)): ?>
                <div class="empty-state" style="padding:20px"><div class="ei">📊</div><p>Chưa có dữ liệu.</p></div>
                <?php endif; ?>
            </div>

            <!-- Thông tin hệ thống -->
            <div style="background:#fff;border-radius:12px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,.06)">
                <h3 style="font-size:14px;font-weight:700;color:var(--primary);margin-bottom:14px">⚙️ Thông Tin Hệ Thống</h3>
                <?php
                $info_rows = [
                    ['PHP Version', phpversion()],
                    ['Server', $_SERVER['SERVER_SOFTWARE']??'N/A'],
                    ['Database', 'MySQL · quanly'],
                    ['Timezone', date_default_timezone_get()],
                    ['Server Time', date('d/m/Y H:i:s')],
                    ['Total Log Records', number_format($conn->query("SELECT COUNT(*) AS c FROM audit_log")->fetch_assoc()['c'])],
                ];
                foreach($info_rows as $ir): ?>
                <div style="display:flex;justify-content:space-between;font-size:12px;padding:6px 0;border-bottom:1px solid #f4f6f8">
                    <span style="color:var(--muted)"><?= $ir[0] ?></span>
                    <span style="font-weight:600"><?= htmlspecialchars($ir[1]) ?></span>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</div>
</main>
</div>

<script>
<?php if(!empty($week_stats)): ?>
new Chart(document.getElementById('chartWeek'), {
    type: 'bar',
    data: {
        labels: [<?= implode(',',array_map(fn($r)=>'"'.date('d/m',strtotime($r['ngay'])).'"',$week_stats)) ?>],
        datasets: [{
            data: [<?= implode(',',array_column($week_stats,'c')) ?>],
            backgroundColor: 'rgba(102,126,234,0.7)',
            borderRadius: 5
        }]
    },
    options: {
        responsive:true,
        plugins:{ legend:{display:false} },
        scales:{ y:{beginAtZero:true,ticks:{stepSize:1}} }
    }
});
<?php endif; ?>
</script>
</body></html>