<?php

$admin_fullname = $_SESSION['full_name'] ?? 'Admin';

$menus = [
    'dashboard'  => ['icon'=>'📊', 'label'=>'Dashboard',          'href'=>'admin_dashboard.php'],
    'nguoi_dung' => ['icon'=>'👥', 'label'=>'Quản lý Người dùng', 'href'=>'admin_nguoi_dung.php'],
    'don_hang'   => ['icon'=>'📦', 'label'=>'Đơn hàng',           'href'=>'admin_don_hang.php'],
    'phuong_tien'=> ['icon'=>'🚛', 'label'=>'Phương tiện',        'href'=>'admin_phuong_tien.php'],
    'bao_cao'    => ['icon'=>'📊', 'label'=>'Báo cáo',            'href'=>'admin_bao_cao.php'],
    'nhat_ky'    => ['icon'=>'📋', 'label'=>'Nhật ký',            'href'=>'admin_nhat_ky.php'],
    'cai_dat'    => ['icon'=>'⚙️', 'label'=>'Cài đặt',            'href'=>'admin_cai_dat.php'],
];
?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">👨‍💼</div>
        <h2>Admin Panel</h2>
        <p>Vận Tải Đường Bộ</p>
    </div>
    <nav class="nav-section">
        <div class="nav-label">Quản trị hệ thống</div>
        <?php foreach($menus as $key=>$m): ?>
        <a href="<?= $m['href'] ?>" class="nav-item <?= ($active??'')===$key?'active':'' ?>">
            <span class="ni"><?= $m['icon'] ?></span>
            <?= htmlspecialchars($m['label']) ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
        <div class="sidebar-user">
            <div class="su-avatar"><?= mb_strtoupper(mb_substr($admin_fullname,0,1)) ?></div>
            <div>
                <div class="su-name"><?= htmlspecialchars($admin_fullname) ?></div>
                <div class="su-role">Super Admin</div>
            </div>
        </div>
        <a href="logout.php" class="btn-logout">🚪 Đăng Xuất</a>
    </div>
</aside>