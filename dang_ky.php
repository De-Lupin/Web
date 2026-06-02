<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: indext.php"); exit();
}

$message = '';
$message_type = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten   = trim($_POST['ho_ten']    ?? '');
    $username = trim($_POST['username']  ?? '');
    $email    = trim($_POST['email']     ?? '');
    $password = trim($_POST['password']  ?? '');
    $confirm  = trim($_POST['confirm']   ?? '');
    $sdt      = trim($_POST['sdt']       ?? '');

    if (empty($ho_ten)||empty($username)||empty($email)||empty($password)) {
        $message = 'Vui lòng điền đầy đủ thông tin bắt buộc!';
    } elseif (strlen($password) < 6) {
        $message = 'Mật khẩu phải có ít nhất 6 ký tự!';
    } elseif ($password !== $confirm) {
        $message = 'Xác nhận mật khẩu không khớp!';
    } else {
        // Kiểm tra username và email đã tồn tại chưa
        $chk = $conn->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");
        $chk->bind_param("ss", $username, $email);
        $chk->execute();
        $exists = $chk->get_result()->fetch_assoc();
        $chk->close();

        if ($exists) {
            $message = 'Tên đăng nhập hoặc email đã được sử dụng!';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username,password,email,full_name,phone,role,is_active) VALUES (?,?,?,?,?,'khachhang',1)");
            $stmt->bind_param("sssss", $username, $hash, $email, $ho_ten, $sdt);
            $stmt->execute();
            $stmt->close();

            write_audit_log($conn, $conn->insert_id, $username, 'REGISTER', 'Đăng ký tài khoản mới');

            header("Location: indext.php?msg=registered"); exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Đăng ký tài khoản</title>
<link rel="stylesheet" href="style.css">
<style>
.loginwrapper{display:flex;min-height:100vh}
.leftpanel{flex:1;background:linear-gradient(135deg,#1A65C8,#1550A8);display:none}
@media(min-width:768px){.leftpanel{display:block}}
.rightpanel{flex:1;display:flex;align-items:center;justify-content:center;padding:30px 20px;background:#F0F2F5}
.logincontainer{background:#fff;padding:36px 32px;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,.1);width:100%;max-width:480px}
.logincontainer h2{font-size:22px;font-weight:700;color:#1A2340;margin-bottom:6px;text-align:center}
.logincontainer .sub{font-size:13px;color:#5A6480;text-align:center;margin-bottom:22px}
.frow{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.inputgroup{margin-bottom:14px}
.inputgroup label{display:block;font-size:12px;font-weight:700;color:#5A6480;margin-bottom:5px;letter-spacing:.3px;text-transform:uppercase}
.inputgroup input{width:100%;padding:10px 12px;border:1.5px solid #DDE3EE;border-radius:6px;font-size:14px;background:#F4F6FA;outline:none;transition:.15s}
.inputgroup input:focus{border-color:#1A65C8;box-shadow:0 0 0 2px rgba(26,101,200,.12);background:#fff}
.req{color:#C62828}
.loginbtn{width:100%;padding:11px;background:#1A65C8;color:#fff;border:none;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;transition:.15s;margin-top:4px}
.loginbtn:hover{background:#1550A8}
.msg-err{background:#FFEBEE;color:#C62828;border:1px solid #f1b8b8;padding:10px 14px;border-radius:6px;font-size:13px;margin-bottom:16px}
.msg-ok{background:#E6F5EE;color:#1E7D4E;border:1px solid #b7dfc8;padding:10px 14px;border-radius:6px;font-size:13px;margin-bottom:16px}
.bottom-link{text-align:center;margin-top:16px;font-size:13px;color:#5A6480}
.bottom-link a{color:#1A65C8;font-weight:600}
@media(max-width:480px){.frow{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="loginwrapper">
  <div class="leftpanel"></div>
  <div class="rightpanel">
    <div class="logincontainer">
      <h2>Đăng Ký Tài Khoản</h2>
      <p class="sub">Tạo tài khoản để sử dụng dịch vụ vận tải</p>

      <?php if ($message): ?>
        <div class="msg-err"><?=htmlspecialchars($message)?></div>
      <?php endif; ?>

      <form method="post">
        <div class="frow">
          <div class="inputgroup">
            <label>Họ và tên <span class="req">*</span></label>
            <input type="text" name="ho_ten" placeholder="Nguyễn Văn A" value="<?=htmlspecialchars($_POST['ho_ten']??'')?>" required>
          </div>
          <div class="inputgroup">
            <label>Số điện thoại</label>
            <input type="tel" name="sdt" placeholder="0901234567" value="<?=htmlspecialchars($_POST['sdt']??'')?>">
          </div>
        </div>
        <div class="inputgroup">
          <label>Tên đăng nhập <span class="req">*</span></label>
          <input type="text" name="username" placeholder="Nhập tên đăng nhập" value="<?=htmlspecialchars($_POST['username']??'')?>" required>
        </div>
        <div class="inputgroup">
          <label>Email <span class="req">*</span></label>
          <input type="email" name="email" placeholder="email@example.com" value="<?=htmlspecialchars($_POST['email']??'')?>" required>
        </div>
        <div class="frow">
          <div class="inputgroup">
            <label>Mật khẩu <span class="req">*</span></label>
            <input type="password" name="password" placeholder="Tối thiểu 6 ký tự" required>
          </div>
          <div class="inputgroup">
            <label>Xác nhận mật khẩu <span class="req">*</span></label>
            <input type="password" name="confirm" placeholder="Nhập lại mật khẩu" required>
          </div>
        </div>
        <button type="submit" class="loginbtn">✅ Đăng ký</button>
      </form>

      <div class="bottom-link">
        Đã có tài khoản? <a href="indext.php">Đăng nhập ngay</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
