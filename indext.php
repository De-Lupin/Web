<?php
// ============================================================
// indext.php — Đăng nhập & Đăng ký
// Dành cho: Khách hàng (đăng ký mới) + Điều Phối Viên (đăng nhập)
// ============================================================
session_start();
require 'config.php';

// Đã đăng nhập → chuyển thẳng vào dashboard
if (isset($_SESSION['user_id'])) {
    $r = $_SESSION['role'] ?? '';
    if ($r === 'admin')    { header("Location: admin_dashboard.php");    exit(); }
    if ($r === 'dieuphoI') { header("Location: dieuphoI_dashboard.php"); exit(); }
    header("Location: customer_dashboard.php"); exit();
}

$error   = '';
$success = '';
$tab     = $_GET['tab'] ?? 'login';

// ============================================================
// XỬ LÝ ĐĂNG NHẬP
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $tab = 'login';

    if (!$username || !$password) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!';
    } else {
        // Dùng prepared statement để tránh SQL injection
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || !verify_password($password, $user['password'])) {
            // Không tìm thấy user HOẶC sai mật khẩu → cùng 1 thông báo (bảo mật)
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng!';
            write_audit_log($conn, null, $username, 'LOGIN_FAILED', 'Sai thông tin đăng nhập');

        } elseif (!$user['is_active']) {
            $error = 'Tài khoản đã bị khóa. Liên hệ quản trị viên!';

        } elseif (normalize_role($user['role']) === 'admin') {
            $error = 'Tài khoản Admin vui lòng đăng nhập tại trang Admin.';

        } else {
            // ✅ Đăng nhập thành công
            $role_code = normalize_role($user['role']); // 'dieuphoI' hoặc 'khachhang'

            session_regenerate_id(true);
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['full_name']  = $user['full_name'];
            $_SESSION['email']      = $user['email'];
            $_SESSION['phone']      = $user['phone'] ?? '';
            $_SESSION['role']       = $role_code;      // Lưu role đã normalize
            $_SESSION['login_time'] = time();

            $conn->query("UPDATE users SET last_login=NOW() WHERE id={$user['id']}");
            write_audit_log($conn, $user['id'], $user['username'], 'LOGIN', 'Đăng nhập thành công');

            $dest = $role_code === 'dieuphoI'
                  ? 'dieuphoI_dashboard.php'
                  : 'customer_dashboard.php';
            header("Location: $dest"); exit();
        }
    }
}

// ============================================================
// XỬ LÝ ĐĂNG KÝ (chỉ dành cho khách hàng)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_register'])) {
    $username  = trim($_POST['reg_username']  ?? '');
    $password  = trim($_POST['reg_password']  ?? '');
    $full_name = trim($_POST['reg_full_name'] ?? '');
    $email     = trim($_POST['reg_email']     ?? '');
    $phone     = trim($_POST['reg_phone']     ?? '');
    $tab = 'register';

    if (!$username || !$password || !$full_name) {
        $error = 'Vui lòng điền đầy đủ: Tên đăng nhập, Mật khẩu và Họ tên!';
    } elseif (!$email && !$phone) {
        $error = 'Vui lòng nhập ít nhất Gmail hoặc Số điện thoại!';
    } elseif (strlen($username) < 4) {
        $error = 'Tên đăng nhập phải có ít nhất 4 ký tự!';
    } elseif (strlen($password) < 4) {
        $error = 'Mật khẩu phải có ít nhất 4 ký tự!';
    } else {
        // Kiểm tra username đã tồn tại chưa
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $check = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($check) {
            $error = 'Tên đăng nhập này đã được sử dụng. Vui lòng chọn tên khác!';
        } else {
            // Hash mật khẩu bằng bcrypt trước khi lưu
            $pw_hash  = password_hash($password, PASSWORD_DEFAULT);
            $em_final = $email ?: ($username . '@khachhang.local');
            // Lưu role đúng với ENUM trong DB: 'khách hàng'
            $db_role_val = db_role('khachhang'); // = 'khách hàng'

            $stmt = $conn->prepare(
                "INSERT INTO users (username, password, email, full_name, phone, role, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, 1)"
            );
            $stmt->bind_param("ssssss", $username, $pw_hash, $em_final, $full_name, $phone, $db_role_val);

            if ($stmt->execute()) {
                $new_id  = $conn->insert_id;
                $success = 'Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.';
                $tab     = 'login';
                write_audit_log($conn, $new_id, $username, 'REGISTER', 'Tạo tài khoản mới');
            } else {
                $error = 'Đăng ký thất bại: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Thông báo từ URL
if (isset($_GET['msg']) && $_GET['msg'] === 'logout') {
    $success = 'Bạn đã đăng xuất thành công.';
}
if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $error = 'Bạn không có quyền truy cập trang đó!';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Đăng Nhập / Đăng Ký — Vận Tải Đường Bộ</title>
<style>
/* ── RESET ── */
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
:root {
    --blue:      #2563eb;
    --blue-dark: #1d4ed8;
    --blue-light:#eff6ff;
    --green:     #10b981;
    --red:       #ef4444;
    --text:      #1e293b;
    --muted:     #64748b;
    --border:    #e2e8f0;
    --bg:        #f1f5f9;
    --white:     #ffffff;
}
body {
    font-family: 'Segoe UI', system-ui, sans-serif;
    background: var(--bg);
    min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
    padding: 20px;
}

/* ── WRAPPER ── */
.wrap {
    display: flex;
    width: 100%; max-width: 920px;
    background: var(--white);
    border-radius: 20px;
    box-shadow: 0 8px 40px rgba(0,0,0,.12);
    overflow: hidden;
    min-height: 560px;
}

/* ── CỘT TRÁI: Banner ── */
.banner {
    flex: 1;
    background: linear-gradient(145deg, #1d4ed8 0%, #2563eb 55%, #3b82f6 100%);
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 48px 36px; color: #fff;
    position: relative; overflow: hidden;
}
.banner::before {
    content:''; position:absolute;
    width:280px; height:280px; border-radius:50%;
    background:rgba(255,255,255,.07); top:-70px; right:-70px;
}
.banner::after {
    content:''; position:absolute;
    width:180px; height:180px; border-radius:50%;
    background:rgba(255,255,255,.07); bottom:-50px; left:-40px;
}
.banner-icon  { font-size:68px; margin-bottom:18px; position:relative; z-index:1; }
.banner-title { font-size:24px; font-weight:800; position:relative; z-index:1; }
.banner-sub   { font-size:13px; color:rgba(255,255,255,.75); margin-top:8px; text-align:center; line-height:1.7; position:relative; z-index:1; }
.banner-feats { margin-top:28px; display:flex; flex-direction:column; gap:10px; position:relative; z-index:1; }
.feat-item    { display:flex; align-items:center; gap:10px; font-size:13px; }
.feat-icon    { width:30px; height:30px; border-radius:8px; background:rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0; }

/* ── CỘT PHẢI: Form ── */
.form-side {
    flex:1; padding:44px 40px;
    display:flex; flex-direction:column;
    overflow-y:auto; max-width:460px;
}
.form-logo    { font-size:26px; margin-bottom:6px; }
.form-title   { font-size:22px; font-weight:800; color:var(--text); }
.form-sub     { font-size:13px; color:var(--muted); margin-top:4px; margin-bottom:24px; }

/* ── TAB ── */
.tabs {
    display:flex; background:#f1f5f9;
    border-radius:10px; padding:4px;
    margin-bottom:24px; gap:3px;
}
.tab-btn {
    flex:1; padding:10px;
    border:none; border-radius:8px;
    font-size:13px; font-weight:700;
    cursor:pointer; font-family:inherit;
    background:transparent; color:var(--muted);
    transition:all .2s;
}
.tab-btn.active {
    background:var(--white); color:var(--blue);
    box-shadow:0 1px 4px rgba(0,0,0,.10);
}

/* ── FIELD ── */
.field { margin-bottom:14px; }
.field label {
    display:block; font-size:12px; font-weight:700;
    color:var(--text); margin-bottom:5px;
}
.field input {
    width:100%; padding:11px 14px;
    border:1.5px solid var(--border); border-radius:9px;
    font-size:14px; font-family:inherit; color:var(--text);
    outline:none; background:#fdfdfd; transition:border-color .2s, box-shadow .2s;
}
.field input:focus {
    border-color:var(--blue);
    box-shadow:0 0 0 3px rgba(37,99,235,.10);
    background:#fff;
}

/* Nhóm 2 field ngang */
.field-row { display:flex; gap:10px; }
.field-row .field { flex:1; }

/* Gợi ý bên trong field */
.field-hint { font-size:11px; color:var(--muted); margin-top:4px; }

/* ── NÚT ── */
.btn-primary {
    width:100%; padding:13px;
    background:var(--blue); color:#fff;
    border:none; border-radius:10px;
    font-size:14px; font-weight:700;
    cursor:pointer; font-family:inherit;
    transition:all .2s; margin-top:4px;
}
.btn-primary:hover { background:var(--blue-dark); transform:translateY(-1px); box-shadow:0 4px 14px rgba(37,99,235,.3); }

/* ── ALERT ── */
.alert {
    padding:11px 14px; border-radius:9px;
    font-size:13px; margin-bottom:16px;
    display:flex; align-items:flex-start; gap:8px;
    border:1px solid transparent; font-weight:600;
}
.alert-error   { background:#fef2f2; border-color:#fecaca; color:#991b1b; }
.alert-success { background:#f0fdf4; border-color:#bbf7d0; color:#166534; }

/* ── HOẶC ── */
.divider {
    display:flex; align-items:center; gap:10px;
    color:var(--muted); font-size:12px; margin:16px 0;
}
.divider::before, .divider::after { content:''; flex:1; height:1px; background:var(--border); }

/* ── ADMIN LINK ── */
.admin-link {
    text-align:center; margin-top:20px;
    padding-top:16px; border-top:1px solid var(--border);
}
.admin-link a {
    font-size:12px; color:#78716c; text-decoration:none;
}
.admin-link a:hover { color:var(--text); }

/* ── SHOW/HIDE PW ── */
.pw-wrap { position:relative; }
.pw-wrap input { padding-right:46px; }
.pw-eye {
    position:absolute; right:12px; top:50%; transform:translateY(-50%);
    background:none; border:none; cursor:pointer;
    font-size:16px; color:var(--muted); padding:4px;
    line-height:1;
}
.pw-eye:hover { color:var(--blue); }

/* ── RESPONSIVE ── */
@media(max-width:680px){
    .wrap   { flex-direction:column; max-width:460px; }
    .banner { padding:28px 24px; min-height:auto; }
    .banner-feats { display:none; }
    .form-side { padding:28px 24px; max-width:none; }
    .field-row { flex-direction:column; gap:0; }
}
</style>
</head>
<body>

<div class="wrap">

    <!-- ═══ CỘT TRÁI: Banner ═══ -->
    <div class="banner">
        <div class="banner-icon">🚛</div>
        <div class="banner-title">Vận Tải Đường Bộ</div>
        <div class="banner-sub">
            Hệ thống quản lý vận chuyển hàng hóa<br>
            nhanh chóng · an toàn · tin cậy
        </div>
        <div class="banner-feats">
            <div class="feat-item">
                <div class="feat-icon">📦</div>
                <span>Theo dõi đơn hàng real-time</span>
            </div>
            <div class="feat-item">
                <div class="feat-icon">📍</div>
                <span>Cập nhật lộ trình từng kho</span>
            </div>
            <div class="feat-item">
                <div class="feat-icon">🔔</div>
                <span>Thông báo trực tiếp</span>
            </div>
            <div class="feat-item">
                <div class="feat-icon">🎧</div>
                <span>Hỗ trợ khách hàng 24/7</span>
            </div>
        </div>
    </div>

    <!-- ═══ CỘT PHẢI: Form ═══ -->
    <div class="form-side">
        <div class="form-logo">👤</div>
        <div class="form-title">
            <?= $tab === 'register' ? 'Tạo tài khoản' : 'Xin chào!' ?>
        </div>
        <div class="form-sub">
            <?= $tab === 'register'
                ? 'Đăng ký để theo dõi đơn hàng của bạn'
                : 'Đăng nhập vào hệ thống vận tải' ?>
        </div>

        <!-- Thông báo -->
        <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Tab chọn Đăng nhập / Đăng ký -->
        <div class="tabs">
            <button class="tab-btn <?= $tab==='login'?'active':'' ?>"
                    onclick="switchTab('login')">
                🔑 Đăng nhập
            </button>
            <button class="tab-btn <?= $tab==='register'?'active':'' ?>"
                    onclick="switchTab('register')">
                📝 Đăng ký mới
            </button>
        </div>

        <!-- ════════════════════════════
             FORM ĐĂNG NHẬP
             ════════════════════════════ -->
        <div id="tab-login" style="<?= $tab!=='login'?'display:none':'' ?>">
            <form method="POST">
                <div class="field">
                    <label>Tên đăng nhập</label>
                    <input type="text" name="username"
                           placeholder="Nhập tên đăng nhập"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           autocomplete="username" required>
                </div>
                <div class="field">
                    <label>Mật khẩu</label>
                    <div class="pw-wrap">
                        <input type="password" name="password" id="pw-login"
                               placeholder="Nhập mật khẩu"
                               autocomplete="current-password" required>
                        <button type="button" class="pw-eye" onclick="togglePw('pw-login')">👁️</button>
                    </div>
                </div>
                <button type="submit" name="action_login" class="btn-primary">
                    🔑 Đăng nhập
                </button>
            </form>

            <div class="divider">hoặc</div>

            <div style="text-align:center;font-size:13px;color:var(--muted)">
                Chưa có tài khoản?
                <button onclick="switchTab('register')"
                        style="background:none;border:none;color:var(--blue);font-weight:700;cursor:pointer;font-size:13px;font-family:inherit;padding:0">
                    Đăng ký ngay
                </button>
            </div>
        </div>

        <!-- ════════════════════════════
             FORM ĐĂNG KÝ
             ════════════════════════════ -->
        <div id="tab-register" style="<?= $tab!=='register'?'display:none':'' ?>">
            <form method="POST" onsubmit="return validateRegister()">

                <!-- Họ tên -->
                <div class="field">
                    <label>Họ và tên *</label>
                    <input type="text" name="reg_full_name"
                           placeholder="Nguyễn Văn A"
                           value="<?= htmlspecialchars($_POST['reg_full_name'] ?? '') ?>"
                           required>
                </div>

                <!-- Tên đăng nhập + Mật khẩu -->
                <div class="field-row">
                    <div class="field">
                        <label>Tên đăng nhập *</label>
                        <input type="text" name="reg_username"
                               placeholder="VD: nguyenvana"
                               value="<?= htmlspecialchars($_POST['reg_username'] ?? '') ?>"
                               autocomplete="username" required>
                        <div class="field-hint">Tối thiểu 4 ký tự, không dấu</div>
                    </div>
                    <div class="field">
                        <label>Mật khẩu *</label>
                        <div class="pw-wrap">
                            <input type="password" name="reg_password" id="pw-reg"
                                   placeholder="Tối thiểu 4 ký tự"
                                   autocomplete="new-password" required>
                            <button type="button" class="pw-eye" onclick="togglePw('pw-reg')">👁️</button>
                        </div>
                    </div>
                </div>

                <!-- Gmail + Số điện thoại -->
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:9px;padding:12px 14px;margin-bottom:14px">
                    <div style="font-size:12px;font-weight:700;color:#1e40af;margin-bottom:10px">
                        📬 Điền ít nhất một trong hai bên dưới
                    </div>
                    <div class="field" style="margin-bottom:10px">
                        <label>📧 Gmail / Email</label>
                        <input type="email" name="reg_email"
                               placeholder="example@gmail.com"
                               value="<?= htmlspecialchars($_POST['reg_email'] ?? '') ?>"
                               autocomplete="email" id="reg-email">
                    </div>
                    <div class="field" style="margin-bottom:0">
                        <label>📱 Số điện thoại</label>
                        <input type="tel" name="reg_phone"
                               placeholder="09xxxxxxxx"
                               value="<?= htmlspecialchars($_POST['reg_phone'] ?? '') ?>"
                               autocomplete="tel" id="reg-phone"
                               oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)">
                    </div>
                </div>

                <!-- Cảnh báo validate -->
                <div id="reg-warn" style="font-size:12px;color:#ef4444;margin-bottom:10px;display:none">
                    ⚠️ Vui lòng nhập ít nhất Gmail hoặc Số điện thoại!
                </div>

                <button type="submit" name="action_register" class="btn-primary">
                    📝 Tạo tài khoản
                </button>
            </form>

            <div style="text-align:center;font-size:13px;color:var(--muted);margin-top:14px">
                Đã có tài khoản?
                <button onclick="switchTab('login')"
                        style="background:none;border:none;color:var(--blue);font-weight:700;cursor:pointer;font-size:13px;font-family:inherit;padding:0">
                    Đăng nhập
                </button>
            </div>
        </div>

        <!-- Link Admin -->
        <div class="admin-link">
            <a href="admin.php">🔐 Đăng nhập trang quản trị Admin</a>
        </div>
    </div>

</div><!-- /wrap -->

<script>
// Chuyển tab đăng nhập / đăng ký
function switchTab(name) {
    document.getElementById('tab-login').style.display    = name === 'login'    ? '' : 'none';
    document.getElementById('tab-register').style.display = name === 'register' ? '' : 'none';
    document.querySelectorAll('.tab-btn').forEach((b, i) => {
        b.classList.toggle('active', (i === 0 && name === 'login') || (i === 1 && name === 'register'));
    });
}

// Hiện/ẩn mật khẩu
function togglePw(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.type = el.type === 'password' ? 'text' : 'password';
}

// Validate form đăng ký: phải có email hoặc SĐT
function validateRegister() {
    const email = document.getElementById('reg-email')?.value.trim();
    const phone = document.getElementById('reg-phone')?.value.trim();
    const warn  = document.getElementById('reg-warn');
    if (!email && !phone) {
        if (warn) warn.style.display = 'block';
        return false;
    }
    if (warn) warn.style.display = 'none';
    return true;
}

// Ẩn cảnh báo khi nhập
['reg-email','reg-phone'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', () => {
        document.getElementById('reg-warn').style.display = 'none';
    });
});
</script>
</body>
</html>
