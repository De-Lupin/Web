<?php
// ============================================================
// customer_tai_khoan.php — Tài Khoản Khách Hàng
// ============================================================
session_start();
require 'config.php';
require_role(['khachhang']);

$uid = $_SESSION['user_id'];
$msg = null;

// Lấy thông tin user
$u = $conn->query("SELECT * FROM users WHERE id=$uid LIMIT 1")->fetch_assoc();

// Cập nhật thông tin cá nhân
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cap_nhat_tt'])) {
    $fn  = mysqli_real_escape_string($conn, trim($_POST['full_name'] ?? ''));
    $ph  = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
    $conn->query("UPDATE users SET full_name='$fn', phone='$ph' WHERE id=$uid");
    $_SESSION['full_name'] = $fn;
    $u = $conn->query("SELECT * FROM users WHERE id=$uid LIMIT 1")->fetch_assoc();
    $msg = ['type' => 'success', 'text' => '✅ Đã cập nhật thông tin thành công!'];
}

// Đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doi_mat_khau'])) {
    $pw_cu  = $_POST['mat_khau_cu']    ?? '';
    $pw_moi = $_POST['mat_khau_moi']   ?? '';
    $pw_xn  = $_POST['xac_nhan_mkhau'] ?? '';

    // Kiểm tra mật khẩu cũ (plain text theo hệ thống hiện tại)
    if (!verify_password($pw_cu, $u['password'])) {
        $msg = ['type' => 'danger', 'text' => '❌ Mật khẩu hiện tại không đúng!'];
    } elseif (strlen($pw_moi) < 6) {
        $msg = ['type' => 'danger', 'text' => '❌ Mật khẩu mới phải ít nhất 6 ký tự!'];
    } elseif ($pw_moi !== $pw_xn) {
        $msg = ['type' => 'danger', 'text' => '❌ Xác nhận mật khẩu không khớp!'];
    } else {
        $pw_hash = password_hash($pw_moi, PASSWORD_DEFAULT);
        $stmt_pw = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt_pw->bind_param('si', $pw_hash, $uid);
        $stmt_pw->execute(); $stmt_pw->close();
        $u = $conn->query("SELECT * FROM users WHERE id=$uid LIMIT 1")->fetch_assoc();
        $msg = ['type' => 'success', 'text' => '✅ Đã đổi mật khẩu thành công!'];
    }
}

$tab = $_GET['tab'] ?? 'thong_tin';

$active = 'tai_khoan';
require 'sidebar_customer.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài Khoản — Khách Hàng</title>
    <link rel="stylesheet" href="customer_layout.css">
    <style>
    .tab-nav { display:flex;gap:4px;flex-wrap:wrap;background:#f8f9fa;border-radius:12px;padding:4px;margin-bottom:24px; }
    .tab-btn {
        padding:9px 16px;border-radius:8px;font-size:13px;font-weight:600;
        cursor:pointer;border:none;background:transparent;color:var(--muted);
        transition:.2s;text-decoration:none;white-space:nowrap;
    }
    .tab-btn.active { background:#fff;color:var(--primary);box-shadow:0 2px 8px rgba(0,0,0,.08); }
    .tab-btn:hover:not(.active) { color:var(--primary); }

    .setting-section { background:#fff;border-radius:12px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.06); }
    .setting-section h3 {
        font-size:15px;font-weight:700;color:var(--primary);
        margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid var(--primary-light);
        display:flex;align-items:center;gap:8px;
    }
    .info-display {
        display:flex;padding:12px 0;border-bottom:1px solid #f8f9fa;font-size:13px;align-items:center;
    }
    .info-display:last-child { border-bottom:none; }
    .id-label { width:160px;color:var(--muted);font-weight:600;flex-shrink:0; }
    .id-value { color:var(--text);font-weight:500; }
    </style>
</head>
<body>
<div class="app">
<?php require 'sidebar_customer.php'; ?>

<main class="main">
    <div class="topbar">
        <div>
            <div class="topbar-title">👤 Tài Khoản Của Tôi</div>
            <div class="breadcrumb"><a href="customer_dashboard.php">Dashboard</a> › Tài khoản</div>
        </div>
        <div class="user-chip">
            <div class="chip-avatar"><?= mb_strtoupper(mb_substr($u['full_name'] ?? 'K', 0, 1)) ?></div>
            <div>
                <div class="chip-name"><?= htmlspecialchars($u['full_name'] ?? '') ?></div>
                <div class="chip-role">Khách Hàng</div>
            </div>
        </div>
    </div>

    <div class="content">
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg['type'] ?>"><?= $msg['text'] ?></div>
        <?php endif; ?>

        <!-- Tab nav -->
        <div class="tab-nav">
            <a href="?tab=thong_tin"  class="tab-btn <?= $tab==='thong_tin'?'active':'' ?>">👤 Thông tin cá nhân</a>
            <a href="?tab=mat_khau"   class="tab-btn <?= $tab==='mat_khau'?'active':'' ?>">🔑 Đổi mật khẩu</a>
            <a href="?tab=bao_mat"    class="tab-btn <?= $tab==='bao_mat'?'active':'' ?>">🔒 Bảo mật</a>
        </div>

        <?php if ($tab === 'thong_tin'): ?>
        <!-- Tab thông tin cá nhân -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">

            <!-- Form chỉnh sửa -->
            <div class="setting-section">
                <h3>✏️ Chỉnh Sửa Thông Tin</h3>
                <form method="POST">
                    <div style="display:flex;flex-direction:column;gap:14px">
                        <div class="field">
                            <label>Họ và tên</label>
                            <input type="text" name="full_name" value="<?= htmlspecialchars($u['full_name'] ?? '') ?>" required>
                        </div>
                        <div class="field">
                            <label>Số điện thoại</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($u['phone'] ?? '') ?>" placeholder="09xxxxxxxx">
                        </div>
                        <div class="field">
                            <label>Email (không thể đổi)</label>
                            <input type="email" value="<?= htmlspecialchars($u['email'] ?? '') ?>" readonly
                                   style="background:#f8f9fa;color:var(--muted)">
                        </div>
                        <div class="field">
                            <label>Tên đăng nhập (không thể đổi)</label>
                            <input type="text" value="<?= htmlspecialchars($u['username'] ?? '') ?>" readonly
                                   style="background:#f8f9fa;color:var(--muted)">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="cap_nhat_tt" class="btn btn-primary">💾 Lưu Thay Đổi</button>
                    </div>
                </form>
            </div>

            <!-- Thông tin hiển thị -->
            <div class="setting-section">
                <h3>📋 Thông Tin Tài Khoản</h3>
                <div class="info-display">
                    <span class="id-label">Họ và tên</span>
                    <span class="id-value"><?= htmlspecialchars($u['full_name'] ?? '—') ?></span>
                </div>
                <div class="info-display">
                    <span class="id-label">Tên đăng nhập</span>
                    <span class="id-value"><?= htmlspecialchars($u['username'] ?? '—') ?></span>
                </div>
                <div class="info-display">
                    <span class="id-label">Email</span>
                    <span class="id-value"><?= htmlspecialchars($u['email'] ?? '—') ?></span>
                </div>
                <div class="info-display">
                    <span class="id-label">Điện thoại</span>
                    <span class="id-value"><?= htmlspecialchars($u['phone'] ?? 'Chưa cập nhật') ?></span>
                </div>
                <div class="info-display">
                    <span class="id-label">Vai trò</span>
                    <span class="id-value">
                        <span style="background:var(--primary-light);color:var(--primary);padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700">
                            👤 Khách Hàng
                        </span>
                    </span>
                </div>
                <div class="info-display">
                    <span class="id-label">Ngày tạo tài khoản</span>
                    <span class="id-value"><?= date('d/m/Y', strtotime($u['created_at'] ?? 'now')) ?></span>
                </div>
                <div class="info-display">
                    <span class="id-label">Đăng nhập cuối</span>
                    <span class="id-value">
                        <?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'N/A' ?>
                    </span>
                </div>
                <div class="info-display">
                    <span class="id-label">Trạng thái</span>
                    <span class="id-value" style="color:#10b981;font-weight:700">✅ Đang hoạt động</span>
                </div>
            </div>
        </div>

        <?php elseif ($tab === 'mat_khau'): ?>
        <!-- Tab đổi mật khẩu -->
        <div class="setting-section" style="max-width:500px">
            <h3>🔑 Đổi Mật Khẩu</h3>
            <form method="POST">
                <div style="display:flex;flex-direction:column;gap:16px">
                    <div class="field">
                        <label>Mật khẩu hiện tại *</label>
                        <input type="password" name="mat_khau_cu" required placeholder="Nhập mật khẩu hiện tại">
                    </div>
                    <div class="field">
                        <label>Mật khẩu mới * (tối thiểu 6 ký tự)</label>
                        <input type="password" name="mat_khau_moi" required placeholder="Nhập mật khẩu mới" id="pw_moi">
                    </div>
                    <div class="field">
                        <label>Xác nhận mật khẩu mới *</label>
                        <input type="password" name="xac_nhan_mkhau" required placeholder="Nhập lại mật khẩu mới" id="pw_xn">
                    </div>
                    <div id="pw_msg" style="font-size:12px;display:none"></div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="doi_mat_khau" class="btn btn-primary">🔑 Đổi Mật Khẩu</button>
                </div>
            </form>
        </div>

        <?php elseif ($tab === 'bao_mat'): ?>
        <!-- Tab bảo mật -->
        <div class="setting-section" style="max-width:600px">
            <h3>🔒 Thông Tin Bảo Mật</h3>
            <div class="info-display">
                <span class="id-label">Phương thức đăng nhập</span>
                <span class="id-value">
                    <span style="background:#dbeafe;color:#1e40af;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700">
                        🔑 Mật khẩu
                    </span>
                </span>
            </div>
            <div class="info-display">
                <span class="id-label">Trạng thái tài khoản</span>
                <span class="id-value" style="color:#10b981;font-weight:700">✅ Đang hoạt động</span>
            </div>
            <div class="info-display">
                <span class="id-label">Lần đăng nhập cuối</span>
                <span class="id-value"><?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'N/A' ?></span>
            </div>
            <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:14px;margin-top:14px;font-size:13px;color:#795548">
                🔐 <strong>Lời khuyên bảo mật:</strong> Hãy đổi mật khẩu định kỳ và không chia sẻ tài khoản với người khác.
            </div>
        </div>
        <?php endif; ?>

    </div>
</main>
</div>

<script>
// Kiểm tra mật khẩu khớp
const pwMoi = document.getElementById('pw_moi');
const pwXn  = document.getElementById('pw_xn');
const pwMsg = document.getElementById('pw_msg');
if (pwMoi && pwXn) {
    function checkPw(){
        if (!pwXn.value) { pwMsg.style.display='none'; return; }
        const ok = pwMoi.value === pwXn.value;
        pwMsg.style.display = 'block';
        pwMsg.textContent   = ok ? '✅ Mật khẩu khớp' : '❌ Mật khẩu không khớp';
        pwMsg.style.color   = ok ? '#10b981' : '#ef4444';
    }
    pwMoi.addEventListener('input', checkPw);
    pwXn.addEventListener('input',  checkPw);
}
</script>
</body>
</html>
