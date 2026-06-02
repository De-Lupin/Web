<?php
// sidebar_customer.php — include vào tất cả trang khách hàng
// Dùng: $active = 'dashboard'; require 'sidebar_customer.php';
$customer_fullname = $_SESSION['full_name'] ?? 'Khách Hàng';
$uid = $_SESSION['user_id'] ?? 0;

// Đếm thông báo chưa đọc
$notif_count = 0;
if ($uid) {
    $nb = $conn->query("SELECT COUNT(*) AS c FROM thong_bao WHERE nguoi_nhan_id=$uid AND da_doc=0");
    $notif_count = $nb ? ($nb->fetch_assoc()['c'] ?? 0) : 0;
}

$menus = [
    'dashboard'  => ['icon' => '📊', 'label' => 'Tổng quan',           'href' => 'customer_dashboard.php'],
    'tao_don'    => ['icon' => '➕', 'label' => 'Tạo đơn hàng mới',    'href' => 'customer_tao_don.php'],
    'don_hang'   => ['icon' => '📋', 'label' => 'Đơn hàng của tôi',    'href' => 'customer_don_hang.php'],
    'lo_trinh'   => ['icon' => '📍', 'label' => 'Theo dõi lộ trình',   'href' => 'customer_lo_trinh.php'],
    'cong_no'    => ['icon' => '💰', 'label' => 'Công nợ của tôi',     'href' => 'customer_cong_no.php'],
    'thong_bao'  => ['icon' => '🔔', 'label' => 'Thông báo',           'href' => 'customer_thong_bao.php', 'badge' => $notif_count],
    'tai_khoan'  => ['icon' => '👤', 'label' => 'Tài khoản của tôi',   'href' => 'customer_tai_khoan.php'],
];
?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">🚛</div>
        <h2>Vận Tải<br>Hàng Hóa</h2>
        <p>Cổng khách hàng</p>
    </div>

    <div class="role-pill">👤 Khách Hàng</div>

    <nav class="nav-section">
        <div class="nav-label">Chức năng</div>
        <?php foreach ($menus as $key => $m): ?>
        <a href="<?= $m['href'] ?>" class="nav-item <?= ($active ?? '') === $key ? 'active' : '' ?>">
            <span class="ni"><?= $m['icon'] ?></span>
            <?= htmlspecialchars($m['label']) ?>
            <?php if (!empty($m['badge']) && $m['badge'] > 0): ?>
                <span class="badge-count"><?= $m['badge'] ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-foot">
        <div class="sidebar-user">
            <div class="su-avatar"><?= mb_strtoupper(mb_substr($customer_fullname, 0, 1)) ?></div>
            <div>
                <div class="su-name"><?= htmlspecialchars($customer_fullname) ?></div>
                <div class="su-role">Khách Hàng</div>
            </div>
        </div>
        <a href="logout.php" class="btn-logout">🚪 Đăng Xuất</a>
    </div>
</aside>
