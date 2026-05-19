<?php
session_start(); require 'config.php'; require_role(['admin']);
$msg = null;

// Tạo bảng cài đặt nếu chưa có
$conn->query("CREATE TABLE IF NOT EXISTS system_settings (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    `key`    VARCHAR(100) NOT NULL UNIQUE,
    `value`  TEXT         DEFAULT NULL,
    `group`  VARCHAR(50)  DEFAULT 'general',
    label    VARCHAR(200) DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Insert default settings nếu chưa có
$defaults = [
    ['ten_cong_ty',        'Công Ty TNHH Vận Tải Đường Bộ',   'general', 'Tên công ty'],
    ['dia_chi_cong_ty',    '123 Đường ABC, Q.1, TP.HCM',       'general', 'Địa chỉ'],
    ['so_dien_thoai',      '028.1234.5678',                     'general', 'Số điện thoại'],
    ['email_cong_ty',      'info@vantai.vn',                    'general', 'Email công ty'],
    ['website',            'www.vantai.vn',                     'general', 'Website'],
    ['thue_vat',           '10',                                'finance', 'Thuế VAT (%)'],
    ['tien_te',            'VND',                               'finance', 'Đơn vị tiền tệ'],
    ['phi_boc_xep_mac_dinh','200000',                           'finance', 'Phí bốc xếp mặc định (₫)'],
    ['phi_cao_toc_mac_dinh','150000',                           'finance', 'Phí cao tốc mặc định (₫)'],
    ['so_km_bao_duong',    '20000',                             'vehicle', 'Km bảo dưỡng định kỳ'],
    ['canh_bao_dang_kiem', '30',                                'vehicle', 'Cảnh báo đăng kiểm trước (ngày)'],
    ['canh_bao_gplx',      '60',                                'vehicle', 'Cảnh báo GPLX trước (ngày)'],
    ['session_timeout',    '480',                               'security','Session timeout (phút)'],
    ['max_login_fail',     '5',                                 'security','Số lần đăng nhập sai tối đa'],
    ['require_2fa_admin',  '1',                                 'security','Bắt buộc 2FA cho Admin (1=Có)'],
];
foreach($defaults as $d) {
    $conn->query("INSERT IGNORE INTO system_settings (`key`,`value`,`group`,`label`) VALUES('$d[0]','$d[1]','$d[2]','$d[3]')");
}

// Lưu cài đặt
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['luu_cai_dat'])) {
    $group_save = $_POST['group_save'] ?? 'general';
    $settings   = $_POST['settings'] ?? [];
    foreach ($settings as $key => $val) {
        $key = mysqli_real_escape_string($conn, $key);
        $val = mysqli_real_escape_string($conn, $val);
        $conn->query("UPDATE system_settings SET `value`='$val' WHERE `key`='$key' AND `group`='$group_save'");
    }
    $msg = ['type'=>'success','text'=>'Đã lưu cài đặt thành công!'];
}

// Đổi mật khẩu admin
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['doi_mat_khau'])) {
    $pw_cu   = $_POST['mat_khau_cu']   ?? '';
    $pw_moi  = $_POST['mat_khau_moi']  ?? '';
    $pw_xn   = $_POST['xac_nhan_mat_khau'] ?? '';
    $admin_id= $_SESSION['user_id'];

    $r = $conn->query("SELECT password FROM users WHERE id=$admin_id");
    $admin = $r->fetch_assoc();

    if (!password_verify($pw_cu, $admin['password'])) {
        $msg = ['type'=>'danger','text'=>'Mật khẩu hiện tại không đúng!'];
    } elseif (strlen($pw_moi) < 8) {
        $msg = ['type'=>'danger','text'=>'Mật khẩu mới phải ít nhất 8 ký tự!'];
    } elseif ($pw_moi !== $pw_xn) {
        $msg = ['type'=>'danger','text'=>'Xác nhận mật khẩu không khớp!'];
    } else {
        $hash = password_hash($pw_moi, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hash' WHERE id=$admin_id");
        write_audit_log($conn, $admin_id, $_SESSION['username'], 'CHANGE_PASSWORD', 'Admin đổi mật khẩu');
        $msg = ['type'=>'success','text'=>'Đã đổi mật khẩu thành công!'];
    }
}

// Lấy tất cả settings theo group
$all_settings = [];
$r = $conn->query("SELECT * FROM system_settings ORDER BY `group`,id");
while($row=$r->fetch_assoc()) $all_settings[$row['group']][$row['key']] = $row;

$tab = $_GET['tab'] ?? 'general';

$tab_labels = [
    'general'  => ['icon'=>'🏢','label'=>'Thông tin công ty'],
    'finance'  => ['icon'=>'💰','label'=>'Tài chính'],
    'vehicle'  => ['icon'=>'🚛','label'=>'Phương tiện'],
    'security' => ['icon'=>'🔒','label'=>'Bảo mật'],
    'password' => ['icon'=>'🔑','label'=>'Đổi mật khẩu'],
    'backup'   => ['icon'=>'💾','label'=>'Sao lưu dữ liệu'],
];

$active = 'cai_dat'; require 'sidebar_admin.php';
?>
<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Cài Đặt Hệ Thống</title>
<link rel="stylesheet" href="admin_layout.css">
<style>
.tab-nav { display:flex; gap:4px; flex-wrap:wrap; background:#f8f9fa; border-radius:12px; padding:4px; margin-bottom:24px; }
.tab-btn { padding:9px 16px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; border:none; background:transparent; color:var(--muted); transition:.2s; text-decoration:none; white-space:nowrap; }
.tab-btn.active { background:#fff; color:var(--primary); box-shadow:0 2px 8px rgba(0,0,0,.08); }
.tab-btn:hover:not(.active) { color:var(--primary); }
.setting-section { background:#fff; border-radius:12px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.setting-section h3 { font-size:15px; font-weight:700; color:var(--primary); margin-bottom:20px; padding-bottom:12px; border-bottom:2px solid #f0f2ff; display:flex; align-items:center; gap:8px; }
.setting-row { display:grid; grid-template-columns:200px 1fr; gap:20px; align-items:center; padding:12px 0; border-bottom:1px solid #f8f9fa; }
.setting-row:last-child { border-bottom:none; }
.setting-label { font-size:13px; font-weight:600; color:var(--text); }
.setting-label small { display:block; color:var(--muted); font-weight:400; font-size:11px; margin-top:2px; }
.setting-input { padding:9px 14px; border:1.5px solid var(--border); border-radius:8px; font-size:13px; font-family:inherit; outline:none; transition:.2s; width:100%; max-width:400px; }
.setting-input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(102,126,234,.1); }
.backup-card { background:#f8f9fa; border-radius:10px; padding:16px 18px; border:1.5px solid var(--border); }
.backup-card h4 { font-size:14px; font-weight:700; margin-bottom:8px; }
.backup-card p  { font-size:13px; color:var(--muted); margin-bottom:14px; }
</style>
</head><body>
<div class="app">
<?php require 'sidebar_admin.php'; ?>

<main class="main">
<div class="topbar">
    <div>
        <div class="topbar-title">⚙️ Cài Đặt Hệ Thống</div>
        <div class="breadcrumb"><a href="admin_dashboard.php">Dashboard</a> › Cài đặt</div>
    </div>
    <div class="user-chip">
        <div class="chip-avatar"><?= mb_strtoupper(mb_substr($_SESSION['full_name']??'A',0,1)) ?></div>
        <div><div class="chip-name"><?= htmlspecialchars($_SESSION['full_name']??'Admin') ?></div>
        <div class="chip-role">Super Admin</div></div>
    </div>
</div>

<div class="content">
    <?php if(!empty($msg)): ?><div class="alert alert-<?=$msg['type']?>"><?=$msg['text']?></div><?php endif; ?>

    <!-- Tab navigation -->
    <div class="tab-nav">
        <?php foreach($tab_labels as $k=>$v): ?>
        <a href="?tab=<?=$k?>" class="tab-btn <?=$tab===$k?'active':''?>"><?=$v['icon']?> <?=$v['label']?></a>
        <?php endforeach; ?>
    </div>

    <?php if ($tab === 'password'): ?>
    <!-- ── Tab đổi mật khẩu ── -->
    <div class="setting-section" style="max-width:500px">
        <h3>🔑 Đổi Mật Khẩu Admin</h3>
        <form method="POST">
            <div style="display:flex;flex-direction:column;gap:16px">
                <div class="field">
                    <label>Mật khẩu hiện tại *</label>
                    <input type="password" name="mat_khau_cu" required placeholder="Nhập mật khẩu hiện tại">
                </div>
                <div class="field">
                    <label>Mật khẩu mới * (tối thiểu 8 ký tự)</label>
                    <input type="password" name="mat_khau_moi" required placeholder="Nhập mật khẩu mới" minlength="8" id="pw_moi">
                </div>
                <div class="field">
                    <label>Xác nhận mật khẩu mới *</label>
                    <input type="password" name="xac_nhan_mat_khau" required placeholder="Nhập lại mật khẩu mới" id="pw_xn">
                </div>
                <div id="pw_match_msg" style="font-size:12px;display:none"></div>
            </div>
            <div class="form-actions">
                <button type="submit" name="doi_mat_khau" class="btn btn-primary">🔑 Đổi Mật Khẩu</button>
            </div>
        </form>
    </div>

    <?php elseif ($tab === 'backup'): ?>
    <!-- ── Tab sao lưu ── -->
    <div class="setting-section">
        <h3>💾 Sao Lưu & Phục Hồi Dữ Liệu</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

            <div class="backup-card">
                <h4>📥 Xuất Database (SQL)</h4>
                <p>Tải về toàn bộ dữ liệu database dưới dạng file SQL để sao lưu.</p>
                <a href="admin_backup.php?action=export_sql" class="btn btn-primary">📥 Xuất file SQL</a>
            </div>

            <div class="backup-card">
                <h4>📊 Xuất Báo Cáo (Excel)</h4>
                <p>Tải về dữ liệu đơn hàng, khách hàng dưới dạng file Excel.</p>
                <a href="admin_backup.php?action=export_excel" class="btn btn-success">📊 Xuất Excel</a>
            </div>

            <div class="backup-card" style="border-color:#f5c6cb">
                <h4>📤 Nhập Database</h4>
                <p style="color:#e74c3c">⚠️ Sẽ ghi đè dữ liệu hiện tại. Hãy sao lưu trước!</p>
                <form method="POST" enctype="multipart/form-data" action="admin_backup.php">
                    <input type="file" name="sql_file" accept=".sql" style="font-size:13px;margin-bottom:10px;display:block">
                    <button type="submit" name="import_sql" class="btn btn-danger" onclick="return confirm('Chắc chắn nhập? Dữ liệu cũ sẽ bị thay thế!')">📤 Nhập SQL</button>
                </form>
            </div>

            <div class="backup-card">
                <h4>📋 Thông Tin Database</h4>
                <?php
                $tables = ['users','don_hang','xe','tai_xe','tuyen_duong','chuyen_xe','audit_log','thong_bao','system_settings'];
                foreach($tables as $t):
                    $r = $conn->query("SELECT COUNT(*) AS c FROM `$t`");
                    $c = $r ? $r->fetch_assoc()['c'] : '—';
                ?>
                <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;border-bottom:1px solid #f4f6f8">
                    <span style="font-family:monospace;color:var(--primary)"><?=$t?></span>
                    <span style="font-weight:700"><?= number_format((int)$c) ?> bản ghi</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- ── Các tab cài đặt thông thường ── -->
    <div class="setting-section">
        <h3><?= $tab_labels[$tab]['icon']??'⚙️' ?> <?= $tab_labels[$tab]['label']??'Cài đặt' ?></h3>
        <form method="POST">
            <input type="hidden" name="group_save" value="<?= htmlspecialchars($tab) ?>">

            <?php
            $group_settings = $all_settings[$tab] ?? [];
            if (empty($group_settings)):
            ?>
            <div class="empty-state" style="padding:30px"><div class="ei">⚙️</div><p>Không có cài đặt nào cho nhóm này.</p></div>
            <?php else: ?>

            <?php foreach($group_settings as $key=>$setting): ?>
            <div class="setting-row">
                <div class="setting-label">
                    <?= htmlspecialchars($setting['label']??$key) ?>
                    <small><?= htmlspecialchars($key) ?></small>
                </div>
                <div>
                    <?php if (str_contains($key,'require') || str_contains($key,'enable')): ?>
                    <select name="settings[<?= htmlspecialchars($key) ?>]" class="setting-input" style="max-width:200px">
                        <option value="1" <?= $setting['value']==='1'?'selected':'' ?>>✅ Bật</option>
                        <option value="0" <?= $setting['value']==='0'?'selected':'' ?>>❌ Tắt</option>
                    </select>
                    <?php elseif (str_contains($key,'email')): ?>
                    <input type="email" name="settings[<?= htmlspecialchars($key) ?>]" class="setting-input" value="<?= htmlspecialchars($setting['value']??'') ?>">
                    <?php elseif (str_contains($key,'phi_') || str_contains($key,'luong') || str_contains($key,'km')): ?>
                    <input type="number" name="settings[<?= htmlspecialchars($key) ?>]" class="setting-input" value="<?= htmlspecialchars($setting['value']??'') ?>" min="0">
                    <?php else: ?>
                    <input type="text" name="settings[<?= htmlspecialchars($key) ?>]" class="setting-input" value="<?= htmlspecialchars($setting['value']??'') ?>">
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="form-actions" style="margin-top:20px">
                <button type="submit" name="luu_cai_dat" class="btn btn-primary">💾 Lưu Cài Đặt</button>
            </div>
            <?php endif; ?>
        </form>
    </div>
    <?php endif; ?>

</div>
</main>
</div>

<script>
// Kiểm tra mật khẩu khớp
const pwMoi = document.getElementById('pw_moi');
const pwXn  = document.getElementById('pw_xn');
const pwMsg = document.getElementById('pw_match_msg');
if (pwMoi && pwXn) {
    function checkMatch(){
        if (!pwXn.value) { pwMsg.style.display='none'; return; }
        const match = pwMoi.value === pwXn.value;
        pwMsg.style.display = 'block';
        pwMsg.textContent   = match ? '✅ Mật khẩu khớp' : '❌ Mật khẩu không khớp';
        pwMsg.style.color   = match ? '#27ae60' : '#e74c3c';
    }
    pwMoi.addEventListener('input', checkMatch);
    pwXn.addEventListener('input',  checkMatch);
}
</script>
</body></html>