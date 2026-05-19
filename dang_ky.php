<?php
// ============================================================
// REGISTER.PHP - Trang đăng ký tài khoản
// Dành cho: Khách hàng (khachhang) và Điều phối viên (dieuphoI)
// Lưu ý: dieuphoI cần được Admin duyệt (is_active = 0) hoặc
//         có thể cho active ngay tùy chính sách — hiện tại
//         khachhang active ngay, dieuphoI cần Admin duyệt.
// ============================================================
session_start();
require 'config.php';

// Đã đăng nhập rồi → về dashboard
if (isset($_SESSION['user_id'])) {
    $r = $_SESSION['role'] ?? '';
    if ($r === 'admin')    { header("Location: admin_dashboard.php"); exit(); }
    if ($r === 'dieuphoI') { header("Location: dieuphoI_dashboard.php"); exit(); }
    header("Location: customer_dashboard.php"); exit();
}

$errors  = [];
$success = '';
$old     = []; // Lưu lại giá trị cũ khi submit lỗi

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $username  = trim($_POST['username']  ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email']     ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $role      = $_POST['role']           ?? 'khachhang';
    $password  = $_POST['password']       ?? '';
    $password2 = $_POST['password2']      ?? '';

    // Lưu lại để điền lại form
    $old = compact('username','full_name','email','phone','role');

    // ── Validation ──────────────────────────────────────────
    if (empty($username)) {
        $errors[] = "Tên đăng nhập không được để trống!";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{4,50}$/', $username)) {
        $errors[] = "Tên đăng nhập chỉ gồm chữ, số, dấu gạch dưới (4–50 ký tự)!";
    }

    if (empty($full_name)) {
        $errors[] = "Họ và tên không được để trống!";
    } elseif (mb_strlen($full_name) < 3) {
        $errors[] = "Họ và tên phải có ít nhất 3 ký tự!";
    }

    if (empty($email)) {
        $errors[] = "Email không được để trống!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không đúng định dạng!";
    }

    if (!empty($phone) && !preg_match('/^(0|\+84)[0-9]{9,10}$/', $phone)) {
        $errors[] = "Số điện thoại không hợp lệ! (VD: 0912345678)";
    }

    if (!in_array($role, ['khachhang', 'dieuphoI'])) {
        $errors[] = "Loại tài khoản không hợp lệ!";
    }

    if (empty($password)) {
        $errors[] = "Mật khẩu không được để trống!";
    } elseif (strlen($password) < 8) {
        $errors[] = "Mật khẩu phải ít nhất 8 ký tự!";
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = "Mật khẩu phải có cả chữ và số!";
    }

    if ($password !== $password2) {
        $errors[] = "Xác nhận mật khẩu không khớp!";
    }

    // Kiểm tra username/email trùng (chỉ khi không có lỗi cơ bản)
    if (empty($errors)) {
        $chk = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $chk->bind_param("ss", $username, $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            // Phân biệt cụ thể
            $chk2 = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $chk2->bind_param("s", $username);
            $chk2->execute();
            if ($chk2->get_result()->num_rows > 0) $errors[] = "Tên đăng nhập <strong>$username</strong> đã tồn tại!";
            else $errors[] = "Email <strong>$email</strong> đã được sử dụng!";
            $chk2->close();
        }
        $chk->close();
    }

    // ── Lưu vào DB nếu không có lỗi ────────────────────────
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Khách hàng: active ngay
        // Điều phối viên: chờ Admin duyệt (is_active = 0)
        $is_active = ($role === 'khachhang') ? 1 : 0;

        $stmt = $conn->prepare(
            "INSERT INTO users (username, password, email, full_name, phone, role, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssssi", $username, $hash, $email, $full_name, $phone, $role, $is_active);

        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            write_audit_log($conn, $new_id, $username, 'REGISTER',
                "Đăng ký tài khoản mới · Role: $role · is_active: $is_active");

            if ($role === 'khachhang') {
                $success = "Đăng ký thành công! Bạn có thể <a href='indext.php'>đăng nhập ngay</a>.";
            } else {
                $success = "Đăng ký thành công! Tài khoản Điều phối viên cần được <strong>Admin duyệt</strong> trước khi sử dụng. Vui lòng chờ thông báo.";
            }
            $old = []; // Xóa dữ liệu cũ sau khi đăng ký thành công
        } else {
            $errors[] = "Lỗi hệ thống: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký Tài Khoản — Vận Tải Hàng Hóa</title>
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 16px;
        }

        /* ── Wrapper 2 cột ── */
        .reg-wrapper {
            display: flex;
            width: 100%;
            max-width: 980px;
            min-height: 600px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,.15);
            overflow: hidden;
            background: #fff;
        }

        /* ── Cột trái: Banner ── */
        .reg-banner {
            flex: 0 0 340px;
            background: linear-gradient(160deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 32px;
            text-align: center;
        }
        .reg-banner .truck-icon { font-size: 72px; margin-bottom: 20px; }
        .reg-banner h2 { font-size: 22px; font-weight: 800; line-height: 1.4; }
        .reg-banner p  { font-size: 13px; color: rgba(255,255,255,.7); margin-top: 10px; line-height: 1.7; }

        .feature-list { margin-top: 32px; width: 100%; text-align: left; }
        .feature-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 0; border-bottom: 1px solid rgba(255,255,255,.12);
            font-size: 13px; color: rgba(255,255,255,.85);
        }
        .feature-item:last-child { border-bottom: none; }
        .feature-item .fi { font-size: 18px; width: 26px; text-align: center; }

        .reg-banner .login-link {
            margin-top: 28px;
            padding: 10px 24px;
            border: 1.5px solid rgba(255,255,255,.5);
            border-radius: 8px;
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: .2s;
        }
        .reg-banner .login-link:hover {
            background: rgba(255,255,255,.15);
        }

        /* ── Cột phải: Form ── */
        .reg-form-area {
            flex: 1;
            padding: 40px 36px;
            overflow-y: auto;
        }

        .reg-form-area h1 {
            font-size: 24px;
            font-weight: 800;
            color: #2c3e50;
            margin-bottom: 6px;
        }
        .reg-form-area .subtitle {
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 24px;
        }

        /* ── Alert ── */
        .alert {
            padding: 13px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        .alert-error   { background: #fdf2f0; border-color: #e74c3c; color: #c0392b; }
        .alert-success { background: #eafaf1; border-color: #27ae60; color: #1e8449; }
        .alert ul { padding-left: 18px; margin-top: 6px; }
        .alert ul li { margin: 4px 0; }

        /* ── Role Selector ── */
        .role-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 22px;
        }
        .role-option { position: relative; }
        .role-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
        .role-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            padding: 14px 10px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            cursor: pointer;
            transition: .2s;
            text-align: center;
        }
        .role-card .rc-icon { font-size: 28px; }
        .role-card .rc-name { font-size: 13px; font-weight: 700; color: #2c3e50; }
        .role-card .rc-desc { font-size: 11px; color: #7f8c8d; }
        .role-option input:checked + .role-card {
            border-color: #667eea;
            background: #f0f2ff;
        }
        .role-option input:checked + .role-card .rc-name { color: #667eea; }
        .role-card:hover { border-color: #aab3f0; }

        /* ── Form Grid ── */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .field { display: flex; flex-direction: column; gap: 6px; }
        .field.span2 { grid-column: span 2; }

        .field label {
            font-size: 13px;
            font-weight: 600;
            color: #2c3e50;
        }
        .field label .req { color: #e74c3c; }

        .field input, .field select {
            padding: 10px 14px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            color: #2c3e50;
            outline: none;
            transition: .2s;
            background: #fafafa;
        }
        .field input:focus, .field select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,.1);
            background: #fff;
        }
        .field input.error-input { border-color: #e74c3c; }

        /* Strength meter */
        .pw-strength {
            height: 4px;
            border-radius: 2px;
            background: #eee;
            margin-top: 6px;
            overflow: hidden;
        }
        .pw-strength-bar {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: width .3s, background .3s;
        }
        .pw-hint { font-size: 11px; color: #7f8c8d; margin-top: 4px; }

        /* Password match indicator */
        .match-msg { font-size: 12px; margin-top: 4px; }

        /* Note box */
        .note-box {
            background: #fff8e1;
            border: 1px solid #ffe082;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 12px;
            color: #795548;
            margin-top: 16px;
            display: none;
        }
        .note-box.show { display: block; }

        /* Submit button */
        .btn-submit {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            border: none;
            border-radius: 9px;
            font-size: 15px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: .2s;
            margin-top: 22px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102,126,234,.4);
        }
        .btn-submit:active { transform: none; }

        .terms-note {
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 14px;
        }

        /* ── Responsive ── */
        @media (max-width: 750px) {
            .reg-wrapper { flex-direction: column; }
            .reg-banner  { flex: 0 0 auto; padding: 30px 24px; }
            .reg-form-area { padding: 28px 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .field.span2 { grid-column: span 1; }
        }
    </style>
</head>
<body>

<div class="reg-wrapper">

    <!-- ── Banner trái ── -->
    <aside class="reg-banner">
        <div class="truck-icon">🚛</div>
        <h2>Vận Tải<br>Hàng Hóa</h2>
        <p>Đăng ký tài khoản để truy cập hệ thống quản lý vận tải chuyên nghiệp</p>

        <div class="feature-list">
            <div class="feature-item"><span class="fi">📦</span> Theo dõi đơn hàng thời gian thực</div>
            <div class="feature-item"><span class="fi">💰</span> Xem công nợ và thanh toán</div>
            <div class="feature-item"><span class="fi">🔔</span> Nhận thông báo tự động</div>
            <div class="feature-item"><span class="fi">📊</span> Báo cáo & thống kê chi tiết</div>
        </div>

        <a href="indext.php" class="login-link">← Đã có tài khoản? Đăng nhập</a>
    </aside>

    <!-- ── Form phải ── -->
    <main class="reg-form-area">
        <h1>Tạo Tài Khoản Mới</h1>
        <p class="subtitle">Điền thông tin bên dưới để đăng ký sử dụng hệ thống</p>

        <!-- Thông báo lỗi / thành công -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <strong>⚠️ Vui lòng kiểm tra lại:</strong>
            <ul>
                <?php foreach ($errors as $e): ?>
                <li><?= $e ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">
            ✅ <?= $success ?>
        </div>
        <?php endif; ?>

        <?php if (empty($success)): // Chỉ hiện form khi chưa đăng ký thành công ?>
        <form method="POST" novalidate id="regForm">

            <!-- Chọn loại tài khoản -->
            <div style="margin-bottom:6px">
                <label style="font-size:13px;font-weight:600;color:#2c3e50">
                    Loại tài khoản <span style="color:#e74c3c">*</span>
                </label>
            </div>
            <div class="role-selector">
                <label class="role-option">
                    <input type="radio" name="role" value="khachhang"
                           <?= (($old['role'] ?? 'khachhang') === 'khachhang') ? 'checked' : '' ?>
                           onchange="onRoleChange(this)">
                    <div class="role-card">
                        <div class="rc-icon">🏢</div>
                        <div class="rc-name">Khách Hàng</div>
                    </div>
                </label>
                <label class="role-option">
                    <input type="radio" name="role" value="dieuphoI"
                           <?= (($old['role'] ?? '') === 'dieuphoI') ? 'checked' : '' ?>
                           onchange="onRoleChange(this)">
                    <div class="role-card">
                        <div class="rc-icon">📋</div>
                        <div class="rc-name">Điều Phối Viên</div>
                    </div>
                </label>
            </div>

            <!-- Ghi chú cho điều phối viên -->
            <div class="note-box <?= (($old['role'] ?? '') === 'dieuphoI') ? 'show' : '' ?>" id="noteDP">
                ⚠️ <strong>Lưu ý:</strong> Tài khoản Điều phối viên cần được <strong>Admin duyệt</strong> trước khi đăng nhập được. Sau khi đăng ký, vui lòng liên hệ quản trị viên để kích hoạt.
            </div>

            <div style="margin:20px 0 4px;border-top:1px solid #f0f0f0"></div>

            <div class="form-grid">
                <!-- Username -->
                <div class="field">
                    <label>Tên đăng nhập <span class="req">*</span></label>
                    <input type="text" name="username" id="username"
                           value="<?= htmlspecialchars($old['username'] ?? '') ?>"
                           maxlength="50" autocomplete="username">
                    <span class="pw-hint">Chỉ gồm chữ, số, dấu gạch dưới (4–50 ký tự)</span>
                </div>

                <!-- Họ tên -->
                <div class="field">
                    <label>Họ và tên <span class="req">*</span></label>
                    <input type="text" name="full_name"
                           value="<?= htmlspecialchars($old['full_name'] ?? '') ?>"
                           maxlength="100">
                </div>

                <!-- Email -->
                <div class="field">
                    <label>Email <span class="req">*</span></label>
                    <input type="email" name="email"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                           maxlength="100" autocomplete="email">
                </div>

                <!-- Điện thoại -->
                <div class="field">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone"
                           value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                           maxlength="15">
                </div>

                <!-- Mật khẩu -->
                <div class="field">
                    <label>Mật khẩu <span class="req">*</span></label>
                    <input type="password" name="password" id="pw"
                           placeholder="Tối thiểu 8 ký tự, có chữ và số"
                           maxlength="100" autocomplete="new-password"
                           oninput="checkStrength(this.value)">
                    <div class="pw-strength"><div class="pw-strength-bar" id="pwBar"></div></div>
                    <span class="pw-hint" id="pwHint">Nhập mật khẩu để xem độ mạnh</span>
                </div>

                <!-- Xác nhận mật khẩu -->
                <div class="field">
                    <label>Xác nhận mật khẩu <span class="req">*</span></label>
                    <input type="password" name="password2" id="pw2"
                           maxlength="100" autocomplete="new-password"
                           oninput="checkMatch()">
                    <div class="match-msg" id="matchMsg"></div>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                 Tạo Tài Khoản
            </button>

            <p class="terms-note">
                Bằng cách đăng ký, bạn đồng ý với các điều khoản sử dụng hệ thống.
            </p>

        </form>
        <?php else: ?>
        <!-- Nút quay về đăng nhập sau khi đăng ký thành công -->
        <div style="text-align:center;margin-top:20px">
            <a href="indext.php" style="
                display:inline-block;padding:13px 32px;
                background:linear-gradient(135deg,#667eea,#764ba2);
                color:#fff;border-radius:9px;text-decoration:none;
                font-size:15px;font-weight:700;
            ">→ Đi đến trang Đăng Nhập</a>
        </div>
        <?php endif; ?>

    </main>
</div>

<script>
// ── Hiện/ẩn ghi chú điều phối viên ──
function onRoleChange(el) {
    const note = document.getElementById('noteDP');
    note.classList.toggle('show', el.value === 'dieuphoI');
}

// ── Kiểm tra độ mạnh mật khẩu ──
function checkStrength(pw) {
    const bar  = document.getElementById('pwBar');
    const hint = document.getElementById('pwHint');
    let score  = 0;
    if (pw.length >= 8)               score++;
    if (pw.length >= 12)              score++;
    if (/[A-Z]/.test(pw))            score++;
    if (/[0-9]/.test(pw))            score++;
    if (/[^A-Za-z0-9]/.test(pw))    score++;

    const levels = [
        { w:'0%',   bg:'#eee',     txt:'' },
        { w:'25%',  bg:'#e74c3c', txt:'🔴 Yếu — thêm ký tự' },
        { w:'50%',  bg:'#f39c12', txt:'🟡 Trung bình' },
        { w:'75%',  bg:'#3498db', txt:'🔵 Khá mạnh' },
        { w:'90%',  bg:'#2ecc71', txt:'🟢 Mạnh' },
        { w:'100%', bg:'#1e8449', txt:'🟢 Rất mạnh!' },
    ];
    const lv = levels[Math.min(score, 5)];
    bar.style.width      = pw.length === 0 ? '0%' : lv.w;
    bar.style.background = lv.bg;
    hint.textContent     = lv.txt;
    checkMatch();
}

// ── Kiểm tra khớp mật khẩu ──
function checkMatch() {
    const pw    = document.getElementById('pw').value;
    const pw2   = document.getElementById('pw2').value;
    const msg   = document.getElementById('matchMsg');
    if (!pw2) { msg.textContent = ''; return; }
    if (pw === pw2) {
        msg.textContent = '✅ Mật khẩu khớp';
        msg.style.color = '#1e8449';
    } else {
        msg.textContent = '❌ Mật khẩu chưa khớp';
        msg.style.color = '#e74c3c';
    }
}

// ── Validation phía client trước khi submit ──
document.getElementById('regForm')?.addEventListener('submit', function(e) {
    const pw  = document.getElementById('pw').value;
    const pw2 = document.getElementById('pw2').value;
    if (pw !== pw2) {
        e.preventDefault();
        document.getElementById('matchMsg').textContent = '❌ Mật khẩu chưa khớp!';
        document.getElementById('pw2').focus();
    }
});
</script>

</body>
</html>