<?php
session_start(); require 'config.php'; require_role(['admin']);

// Khoảng thời gian lọc
$thang = (int)($_GET['thang'] ?? date('m'));
$nam   = (int)($_GET['nam']   ?? date('Y'));
$loai  = $_GET['loai'] ?? 'thang'; // thang | quy | nam

// Xác định khoảng lọc
if ($loai === 'nam') {
    $where_time = "YEAR(ngay_tao)=$nam";
    $group_by   = "MONTH(ngay_tao)";
    $label_fmt  = "Tháng %s/$nam";
} elseif ($loai === 'quy') {
    $quy = (int)($_GET['quy'] ?? ceil(date('m')/3));
    $thang_bd = ($quy-1)*3+1; $thang_kt = $quy*3;
    $where_time = "YEAR(ngay_tao)=$nam AND MONTH(ngay_tao) BETWEEN $thang_bd AND $thang_kt";
    $group_by   = "MONTH(ngay_tao)";
    $label_fmt  = "Tháng %s (Q$quy/$nam)";
} else {
    $where_time = "MONTH(ngay_tao)=$thang AND YEAR(ngay_tao)=$nam";
    $group_by   = "DAY(ngay_tao)";
    $label_fmt  = "Ngày %s/$thang/$nam";
}
$quy = $quy ?? ceil($thang/3);

// Tổng quan kỳ này
$tong = $conn->query("SELECT COUNT(*) AS so_don, COALESCE(SUM(doanh_thu),0) AS doanh_thu, COALESCE(SUM(loi_nhuan),0) AS loi_nhuan, COALESCE(SUM(tong_chi_phi),0) AS chi_phi FROM don_hang WHERE $where_time AND trang_thai!='huy'")->fetch_assoc();
$don_huy = $conn->query("SELECT COUNT(*) AS c FROM don_hang WHERE $where_time AND trang_thai='huy'")->fetch_assoc()['c']??0;
$don_hoan= $conn->query("SELECT COUNT(*) AS c FROM don_hang WHERE $where_time AND trang_thai IN ('hoan_thanh','da_thanh_toan')")->fetch_assoc()['c']??0;

// Dữ liệu theo thời gian (biểu đồ)
$chart_rows = $conn->query("SELECT $group_by AS ky, COALESCE(SUM(doanh_thu),0) AS dt, COALESCE(SUM(loi_nhuan),0) AS ln, COUNT(*) AS so_don FROM don_hang WHERE $where_time AND trang_thai!='huy' GROUP BY $group_by ORDER BY $group_by ASC");
$chart_data = ['labels'=>[],'dt'=>[],'ln'=>[],'don'=>[]];
while ($r = $chart_rows->fetch_assoc()) {
    $chart_data['labels'][] = sprintf($label_fmt, $r['ky']);
    $chart_data['dt'][]     = (float)$r['dt'];
    $chart_data['ln'][]     = (float)$r['ln'];
    $chart_data['don'][]    = (int)$r['so_don'];
}

// Top 5 tuyến đường nhiều đơn nhất
$top_tuyen = $conn->query("SELECT td.ten_tuyen, COUNT(*) AS so_don, COALESCE(SUM(dh.doanh_thu),0) AS doanh_thu FROM don_hang dh JOIN tuyen_duong td ON dh.tuyen_duong_id=td.id WHERE $where_time AND dh.trang_thai!='huy' GROUP BY td.id ORDER BY so_don DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Top 5 tài xế
$top_taixe = $conn->query("SELECT tx.ho_ten, COUNT(*) AS so_chuyen, COALESCE(SUM(dh.doanh_thu),0) AS doanh_thu FROM don_hang dh JOIN tai_xe tx ON dh.tai_xe_id=tx.id WHERE $where_time AND dh.trang_thai!='huy' GROUP BY tx.id ORDER BY so_chuyen DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Xe hiệu suất cao
$top_xe = $conn->query("SELECT x.bien_so, COUNT(*) AS so_chuyen, COALESCE(SUM(cx.km_thuc_te),0) AS tong_km, COALESCE(SUM(cx.nhien_lieu),0) AS tong_nl FROM chuyen_xe cx JOIN xe x ON cx.xe_id=x.id WHERE MONTH(cx.created_at)=$thang AND YEAR(cx.created_at)=$nam AND cx.trang_thai='hoan_thanh' GROUP BY x.id ORDER BY so_chuyen DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Phân tích theo loại vận chuyển
$loai_vc = $conn->query("SELECT loai_van_chuyen, COUNT(*) AS c, COALESCE(SUM(doanh_thu),0) AS dt FROM don_hang WHERE $where_time AND trang_thai!='huy' GROUP BY loai_van_chuyen ORDER BY dt DESC")->fetch_all(MYSQLI_ASSOC);

$loai_vc_labels = ['hang_le'=>'Hàng lẻ','hang_nguyen_xe'=>'Nguyên xe','hang_dong_lanh'=>'Đông lạnh','hang_qua_kho'=>'Qua kho','hang_sieu_truong'=>'Siêu trường'];

$active = 'bao_cao'; require 'sidebar_admin.php';
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Báo Cáo — Admin</title>
<link rel="stylesheet" href="admin_layout.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.filter-form{display:flex;gap:10px;flex-wrap:wrap;align-items:center;background:#fff;border-radius:12px;padding:16px 20px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:24px}
.filter-form label{font-size:13px;font-weight:600;color:var(--text)}
.filter-form select,.filter-form input{padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none}
.chart-grid{display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px}
.chart-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.chart-card h3{font-size:14px;font-weight:700;color:var(--primary);margin-bottom:16px}
.rank-item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f4f6f8;font-size:13px}
.rank-item:last-child{border-bottom:none}
.rank-num{width:24px;height:24px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0}
.rank-num.gold{background:#f39c12}.rank-num.silver{background:#95a5a6}.rank-num.bronze{background:#e67e22}
.bar-fill{height:8px;border-radius:4px;background:var(--primary);transition:.3s}
</style>
</head><body>
<div class="app">
<?php require 'sidebar_admin.php'; ?>

<main class="main">
<div class="topbar">
    <div><div class="topbar-title">📊 Báo Cáo & Thống Kê</div>
    <div class="breadcrumb"><a href="admin_dashboard.php">Dashboard</a> › Báo cáo</div></div>
    <div style="display:flex;gap:10px;align-items:center">
        <div class="user-chip">
            <div class="chip-avatar"><?= mb_strtoupper(mb_substr($_SESSION['full_name']??'A',0,1)) ?></div>
            <div><div class="chip-name"><?= htmlspecialchars($_SESSION['full_name']??'Admin') ?></div>
            <div class="chip-role">Super Admin</div></div>
        </div>
    </div>
</div>

<div class="content">

    <!-- Bộ lọc -->
    <form method="GET" class="filter-form">
        <label>Loại báo cáo:</label>
        <select name="loai" onchange="this.form.submit()">
            <option value="thang" <?= $loai==='thang'?'selected':'' ?>>Theo tháng</option>
            <option value="quy"   <?= $loai==='quy'  ?'selected':'' ?>>Theo quý</option>
            <option value="nam"   <?= $loai==='nam'  ?'selected':'' ?>>Theo năm</option>
        </select>
        <?php if($loai!=='nam'): ?>
        <label>Tháng:</label>
        <select name="thang" onchange="this.form.submit()">
            <?php for($i=1;$i<=12;$i++): ?>
            <option value="<?=$i?>" <?=$thang===$i?'selected':''?>>Tháng <?=$i?></option>
            <?php endfor; ?>
        </select>
        <?php endif; ?>
        <?php if($loai==='quy'): ?>
        <label>Quý:</label>
        <select name="quy" onchange="this.form.submit()">
            <?php for($i=1;$i<=4;$i++): ?><option value="<?=$i?>" <?=$quy===$i?'selected':''?>>Q<?=$i?></option><?php endfor; ?>
        </select>
        <?php endif; ?>
        <label>Năm:</label>
        <select name="nam" onchange="this.form.submit()">
            <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
            <option value="<?=$y?>" <?=$nam===$y?'selected':''?>><?=$y?></option>
            <?php endfor; ?>
        </select>
    </form>

    <!-- Tổng quan KPI -->
    <div class="stat-cards" style="margin-bottom:24px">
        <div class="stat-card">
            <div class="sc-icon">📦</div><div class="sc-label">Tổng đơn hàng</div>
            <div class="sc-value"><?= number_format($tong['so_don']) ?></div>
            <div class="sc-sub">✅ <?= $don_hoan ?> hoàn thành · ❌ <?= $don_huy ?> hủy</div>
        </div>
        <div class="stat-card" style="border-top-color:#8e44ad">
            <div class="sc-icon">💰</div><div class="sc-label">Doanh thu</div>
            <div class="sc-value" style="color:#8e44ad;font-size:20px">₫<?= number_format($tong['doanh_thu']/1000000,1) ?>tr</div>
            <div class="sc-sub">Tổng doanh thu kỳ này</div>
        </div>
        <div class="stat-card" style="border-top-color:#27ae60">
            <div class="sc-icon">📈</div><div class="sc-label">Lợi nhuận</div>
            <div class="sc-value" style="color:#27ae60;font-size:20px">₫<?= number_format($tong['loi_nhuan']/1000000,1) ?>tr</div>
            <div class="sc-sub">Biên LN: <?= $tong['doanh_thu']>0?round($tong['loi_nhuan']/$tong['doanh_thu']*100,1):0 ?>%</div>
        </div>
        <div class="stat-card" style="border-top-color:#e67e22">
            <div class="sc-icon">💸</div><div class="sc-label">Tổng chi phí</div>
            <div class="sc-value" style="color:#e67e22;font-size:20px">₫<?= number_format($tong['chi_phi']/1000000,1) ?>tr</div>
            <div class="sc-sub">Chi phí vận hành</div>
        </div>
    </div>

    <!-- Biểu đồ + Top tuyến -->
    <div class="chart-grid">
        <!-- Biểu đồ doanh thu -->
        <div class="chart-card">
            <h3>📈 Doanh Thu & Lợi Nhuận</h3>
            <?php if (!empty($chart_data['labels'])): ?>
            <canvas id="chartDT" height="100"></canvas>
            <?php else: ?>
            <div class="empty-state" style="padding:40px"><div class="ei">📊</div><p>Chưa có dữ liệu kỳ này.</p></div>
            <?php endif; ?>
        </div>

        <!-- Biểu đồ tròn loại vận chuyển -->
        <div class="chart-card">
            <h3>🍩 Phân Loại Vận Chuyển</h3>
            <?php if (!empty($loai_vc)): ?>
            <canvas id="chartLoai" height="160"></canvas>
            <div style="margin-top:14px">
                <?php foreach($loai_vc as $lv): ?>
                <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;border-bottom:1px solid #f4f6f8">
                    <span><?= htmlspecialchars($loai_vc_labels[$lv['loai_van_chuyen']]??$lv['loai_van_chuyen']) ?></span>
                    <span style="font-weight:700"><?= $lv['c'] ?> đơn · ₫<?= number_format($lv['dt']/1000000,1) ?>tr</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state" style="padding:30px"><div class="ei">🍩</div><p>Chưa có dữ liệu.</p></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Số đơn theo thời gian -->
    <?php if (!empty($chart_data['labels'])): ?>
    <div class="chart-card" style="margin-bottom:24px">
        <h3>📦 Số Đơn Hàng Theo Thời Gian</h3>
        <canvas id="chartDon" height="60"></canvas>
    </div>
    <?php endif; ?>

    <!-- 3 bảng ranking -->
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px">

        <!-- Top tuyến -->
        <div class="chart-card">
            <h3>🗺️ Top Tuyến Đường</h3>
            <?php if (!empty($top_tuyen)):
                $max_dt = max(array_column($top_tuyen,'doanh_thu')) ?: 1;
                foreach($top_tuyen as $i=>$t):
                $colors=['gold','silver','bronze','',''];
            ?>
            <div class="rank-item">
                <div class="rank-num <?= $colors[$i]??'' ?>"><?= $i+1 ?></div>
                <div style="flex:1;min-width:0">
                    <div style="font-weight:600;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($t['ten_tuyen']) ?></div>
                    <div class="bar-fill" style="width:<?= round($t['doanh_thu']/$max_dt*100) ?>%;margin-top:4px;background:<?= ['#f39c12','#95a5a6','#e67e22','#667eea','#667eea'][$i] ?>"></div>
                    <div style="font-size:11px;color:var(--muted);margin-top:3px"><?= $t['so_don'] ?> đơn · ₫<?= number_format($t['doanh_thu']/1000000,1) ?>tr</div>
                </div>
            </div>
            <?php endforeach; else: ?>
            <div class="empty-state" style="padding:30px"><div class="ei">🗺️</div><p>Chưa có dữ liệu.</p></div>
            <?php endif; ?>
        </div>

        <!-- Top tài xế -->
        <div class="chart-card">
            <h3>👤 Top Tài Xế</h3>
            <?php if (!empty($top_taixe)):
                foreach($top_taixe as $i=>$t):
                $colors=['gold','silver','bronze','',''];
            ?>
            <div class="rank-item">
                <div class="rank-num <?= $colors[$i]??'' ?>"><?= $i+1 ?></div>
                <div style="flex:1">
                    <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($t['ho_ten']) ?></div>
                    <div style="font-size:11px;color:var(--muted);margin-top:2px">
                        <?= $t['so_chuyen'] ?> chuyến · ₫<?= number_format($t['doanh_thu']/1000000,1) ?>tr
                    </div>
                </div>
            </div>
            <?php endforeach; else: ?>
            <div class="empty-state" style="padding:30px"><div class="ei">👤</div><p>Chưa có dữ liệu.</p></div>
            <?php endif; ?>
        </div>

        <!-- Top xe -->
        <div class="chart-card">
            <h3>🚛 Hiệu Suất Xe</h3>
            <?php if (!empty($top_xe)):
                foreach($top_xe as $i=>$x):
                $colors=['gold','silver','bronze','',''];
            ?>
            <div class="rank-item">
                <div class="rank-num <?= $colors[$i]??'' ?>"><?= $i+1 ?></div>
                <div style="flex:1">
                    <div style="font-weight:700;font-size:13px">🚛 <?= htmlspecialchars($x['bien_so']) ?></div>
                    <div style="font-size:11px;color:var(--muted);margin-top:2px">
                        <?= $x['so_chuyen'] ?> chuyến · <?= number_format($x['tong_km']) ?> km
                        <?php if($x['tong_nl']>0): ?> · <?= number_format($x['tong_nl'],1) ?>L<?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; else: ?>
            <div class="empty-state" style="padding:30px"><div class="ei">🚛</div><p>Chưa có dữ liệu.</p></div>
            <?php endif; ?>
        </div>

    </div>

</div>
</main>
</div>

<script>
const chartData = <?= json_encode($chart_data) ?>;

// Biểu đồ doanh thu & lợi nhuận
<?php if (!empty($chart_data['labels'])): ?>
new Chart(document.getElementById('chartDT'), {
    type: 'bar',
    data: {
        labels: chartData.labels,
        datasets: [
            { label: 'Doanh thu (₫)', data: chartData.dt, backgroundColor: 'rgba(102,126,234,0.7)', borderRadius: 6 },
            { label: 'Lợi nhuận (₫)', data: chartData.ln, backgroundColor: 'rgba(39,174,96,0.7)',  borderRadius: 6 }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position:'top' }, tooltip: { callbacks: {
            label: ctx => ctx.dataset.label + ': ₫' + ctx.raw.toLocaleString('vi-VN')
        }}},
        scales: { y: { ticks: { callback: v => '₫'+v.toLocaleString('vi-VN') } } }
    }
});

// Biểu đồ số đơn
new Chart(document.getElementById('chartDon'), {
    type: 'line',
    data: {
        labels: chartData.labels,
        datasets: [{ label: 'Số đơn hàng', data: chartData.don, borderColor: '#667eea', backgroundColor: 'rgba(102,126,234,.1)', fill: true, tension: 0.4, pointRadius: 4 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});
<?php endif; ?>

// Biểu đồ tròn
<?php if (!empty($loai_vc)): ?>
new Chart(document.getElementById('chartLoai'), {
    type: 'doughnut',
    data: {
        labels: [<?= implode(',',array_map(fn($l)=>'"'.($loai_vc_labels[$l['loai_van_chuyen']]??$l['loai_van_chuyen']).'"',$loai_vc)) ?>],
        datasets: [{ data: [<?= implode(',',array_column($loai_vc,'c')) ?>], backgroundColor: ['#667eea','#27ae60','#f39c12','#e74c3c','#8e44ad'], borderWidth: 2 }]
    },
    options: { responsive: true, plugins: { legend: { position:'bottom', labels: { font: { size:11 } } } } }
});
<?php endif; ?>
</script>
</body></html>