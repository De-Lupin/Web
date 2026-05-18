<?php
// sidebar_dieuphoI.php - Vận Tải Đường Bộ
$full_name = $_SESSION['full_name'] ?? 'Điều phối viên';
$uid       = $_SESSION['user_id'];

$nb = $conn->query("SELECT COUNT(*) AS c FROM thong_bao WHERE nguoi_nhan_id=$uid AND da_doc=0");
$notif_count = $nb ? ($nb->fetch_assoc()['c'] ?? 0) : 0;

$menus = [
    'dashboard'   => ['icon'=>'📊','label'=>'Dashboard',           'href'=>'dieuphoI_dashboard.php'],
    'don_hang'    => ['icon'=>'📋','label'=>'Danh sách đơn hàng',  'href'=>'dieuphoI_don_hang.php'],
    'tao_don'     => ['icon'=>'➕','label'=>'Tạo đơn hàng mới',    'href'=>'dieuphoI_tao_don.php'],
    'phan_xe'     => ['icon'=>'🚛','label'=>'Phân xe / Phân ca',    'href'=>'dieuphoI_phan_xe.php'],
    'chuyen_xe'   => ['icon'=>'🛣️','label'=>'Quản lý chuyến xe',    'href'=>'dieuphoI_chuyen_xe.php'],
    'tuyen_duong' => ['icon'=>'🗺️','label'=>'Tuyến đường',          'href'=>'dieuphoI_tuyen_duong.php'],
    'gps'         => ['icon'=>'📡','label'=>'Theo dõi GPS',          'href'=>'dieuphoI_gps.php'],
    'thong_bao'   => ['icon'=>'🔔','label'=>'Thông báo',             'href'=>'dieuphoI_thong_bao.php','badge'=>$notif_count],
];
?>
<aside class="sidebar">
    <div class="sidebar-head">
        <div class="logo">🚛</div>
        <h2>Vận Tải<br>Đường Bộ</h2>
        <p>Quản lý vận chuyển</p>
    </div>
    <div class="role-pill">📋 Điều Phối Viên</div>
    <nav class="nav-section">
        <div class="nav-label">Chức năng chính</div>
        <?php foreach ($menus as $key => $m): ?>
        <a href="<?= $m['href'] ?>" class="nav-item <?= ($active??'')===$key?'active':'' ?>">
            <span class="ni"><?= $m['icon'] ?></span>
            <?= htmlspecialchars($m['label']) ?>
            <?php if (!empty($m['badge']) && $m['badge']>0): ?>
                <span class="badge-count"><?= $m['badge'] ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
        <div class="sidebar-user">
            <div class="su-avatar"><?= mb_strtoupper(mb_substr($full_name,0,1)) ?></div>
            <div>
                <div class="su-name"><?= htmlspecialchars($full_name) ?></div>
                <div class="su-role">Điều Phối Viên</div>
            </div>
        </div>
        <a href="logout.php" class="btn-logout">🚪 Đăng Xuất</a>
    </div>
</aside>
