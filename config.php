<?php
<?php
$servername = "quanly";
$username = "root";        // User mặc định XAMPP
$password = "";            // Password mặc định XAMPP (để trống)
$database = "ten_database"; // Tên database của bạn

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $database);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
?>