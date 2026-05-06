<?php
<?php
require 'config.php';

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        // Sử dụng Prepared Statements để tránh SQL Injection
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: admin.php");
                exit;
            } else {
                $message = "Mật khẩu không đúng!";
            }
        } else {
            $message = "Tài khoản không tồn tại!";
        }
        $stmt->close();
    } else {
        $message = "Vui lòng nhập đầy đủ thông tin!";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập hệ thống</title>
    <link rel="stylesheet" href="style.css">
    <!-- Facebook SDK -->
    <script async defer crossorigin="anonymous" src="https://connect.facebook.net/vi_VN/sdk.js#xfbml=1&version=v18.0&appId=YOUR_APP_ID&autoLogAppEvents=1" nonce="YOUR_NONCE"></script>
    <!-- Google Sign-In -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>

    <div class="loginwrapper">
        <div class="leftpanel"></div>
        <div class="rightpanel">
            <div class="logincontainer">
                <h2>Đăng Nhập</h2>
                
                <?php if ($message): ?>
                    <p style="margin-bottom: 15px; color: #0984e3; font-weight: bold;"><?php echo $message; ?></p>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">

                    <div class="inputgroup">
                        <label for="username">Tên đăng nhập</label>
                        <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập" required>
                    </div>
                    
                    <div class="inputgroup">
                        <label for="password">Mật khẩu</label>
                        <div class="passwordwrapper">
                            <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
                            <button type="button" id="showbtn">Show</button>
                        </div>
                        <div class="passwordactions">
                            <button type="button" id="suggestbtn" class="actionbtn">Đề xuất mật khẩu mạnh</button>
                        </div>
                        <div id="strengthmetertext"></div>
                    </div>

                    <div class="options">
                        <label>
                            <input type="checkbox" name="remember"> Ghi nhớ tôi
                        </label>
                        <a href="#">Quên mật khẩu?</a>
                    </div>

                    <button type="submit" class="loginbtn">Đăng Nhập</button>
                    
                    <div class="adminlink">
                        <a href="admin.php">Quyền đăng nhập admin</a>
                    </div>

                </form>

                <div class="social-login">
                    <p>Hoặc đăng nhập bằng:</p>
                    <div class="fb-login-button" data-width="" data-size="large" data-button-type="continue_with" data-layout="default" data-auto-logout-link="false" data-use-continue-as="false"></div>
                    <div id="g_id_onload"
                         data-client_id="YOUR_GOOGLE_CLIENT_ID"
                         data-callback="handleCredentialResponse">
                    </div>
                    <div class="g_id_signin" data-type="standard"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>