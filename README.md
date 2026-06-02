# 🚛 Hệ Thống Quản Lý Vận Tải Hàng Hóa

Ứng dụng web PHP quản lý vận tải đường bộ với 3 vai trò: **Admin**, **Điều Phối Viên**, **Khách Hàng**.

---

## 📋 Cấu trúc file

```
project/
│
├── 🔐 XÁC THỰC & PHIÊN LÀM VIỆC
│   ├── indext.php          — Trang đăng nhập (Điều phối + Khách hàng)
│   ├── admin.php           — Trang đăng nhập Admin (có 2FA)
│   ├── logout.php          — Xử lý đăng xuất
│   └── config.php          — Kết nối database + hàm tiện ích
│
├── 👨‍💼 MODULE ADMIN
│   ├── admin_dashboard.php — Tổng quan hệ thống
│   ├── admin_nguoi_dung.php— Quản lý tài khoản
│   ├── admin_don_hang.php  — Xem tất cả đơn hàng
│   ├── admin_phuong_tien.php— Quản lý xe + tài xế
│   ├── admin_bao_cao.php   — Báo cáo doanh thu
│   ├── admin_nhat_ky.php   — Nhật ký hoạt động
│   ├── admin_cai_dat.php   — Cài đặt hệ thống
│   ├── admin_layout.css    — CSS cho module Admin
│   └── sidebar_admin.php   — Sidebar dùng chung Admin
│
├── 📋 MODULE ĐIỀU PHỐI VIÊN
│   ├── dieuphoI_dashboard.php  — Tổng quan điều phối
│   ├── dieuphoI_don_hang.php   — Danh sách đơn hàng
│   ├── dieuphoI_tao_don.php    — Tạo đơn hàng mới
│   ├── dieuphoI_phan_xe.php    — Phân xe + tài xế cho đơn
│   ├── dieuphoI_chuyen_xe.php  — Quản lý chuyến xe
│   ├── dieuphoI_tuyen_duong.php— Quản lý tuyến đường
│   ├── dieuphoI_gps.php        — Theo dõi xe qua GPS
│   ├── dieuphoI_thong_bao.php  — Thông báo nội bộ
│   ├── dieuphoI_layout.css     — CSS cho module Điều Phối
│   └── sidebar_dieuphoI.php    — Sidebar dùng chung Điều Phối
│
├── 👤 MODULE KHÁCH HÀNG
│   └── customer_dashboard.php  — Dashboard khách hàng
│
├── 🗄️ DATABASE
│   └── database.sql        — Tạo bảng + dữ liệu mẫu đầy đủ
│
└── 🎨 GIAO DIỆN CHUNG
    ├── style.css           — CSS trang đăng nhập
    └── script.js           — JavaScript kiểm tra mật khẩu
```

---

## ⚙️ Cài đặt

### Yêu cầu
- **XAMPP** (PHP 7.4+ và MySQL 5.7+)
- Trình duyệt web bất kỳ

### Các bước cài đặt

**Bước 1:** Cài đặt XAMPP và khởi động Apache + MySQL

**Bước 2:** Copy toàn bộ project vào thư mục:
```
C:\xampp\htdocs\vantai\
```

**Bước 3:** Import database
- Mở phpMyAdmin: `http://localhost/phpmyadmin`
- Tạo database tên `quanly`
- Chọn database `quanly` → tab **Import** → chọn file `database.sql` → **Go**

**Bước 4:** Mở trình duyệt và truy cập:
```
http://localhost/vantai/indext.php
```

---

## 🔑 Tài khoản đăng nhập mẫu

| Vai trò | Username | Mật khẩu | Trang đăng nhập |
|---------|----------|----------|-----------------|
| Admin | `admin` | `123456` | `admin.php` (cần email: admin@vantai.vn) |
| Điều phối 1 | `dieuphoi1` | `123456` | `indext.php` |
| Điều phối 2 | `dieuphoi2` | `123456` | `indext.php` |
| Khách hàng | `khachhang1` | `123456` | `indext.php` |

---

## 🎨 Giao diện

### Admin Panel
- Màu chủ đạo: **Indigo** (#4f46e5)
- Sidebar màu trắng, viền xám nhạt
- Badge và stat card theo từng trạng thái

### Điều Phối Viên
- Màu chủ đạo: **Xanh dương** (#2563eb)
- Cùng phong cách với Admin nhưng độc lập CSS

### Màu badge trạng thái đơn hàng
| Trạng thái | Màu |
|-----------|-----|
| Chờ duyệt | Vàng nhạt |
| Đang xử lý | Xanh dương nhạt |
| Đang vận chuyển | Xanh lá nhạt |
| Hoàn thành | Xanh lá đậm |
| Đã hủy | Đỏ nhạt |

---

## 🗄️ Cấu trúc Database

| Bảng | Mô tả |
|------|-------|
| `users` | Tài khoản: admin, điều phối, khách hàng |
| `xe` | Phương tiện vận tải |
| `tai_xe` | Tài xế |
| `tuyen_duong` | Tuyến đường cố định |
| `don_hang` | Đơn hàng vận chuyển (bảng trung tâm) |
| `chuyen_xe` | Chi tiết mỗi chuyến đi (km, nhiên liệu) |
| `thong_bao` | Thông báo nội bộ |
| `audit_log` | Nhật ký mọi hoạt động |
| `system_settings` | Cài đặt hệ thống |
| `gps_log` | Lịch sử vị trí GPS (tùy chọn) |

---

## 🔒 Bảo mật

- Mật khẩu mã hóa bằng `password_hash()` (bcrypt)
- Chống SQL Injection: dùng Prepared Statements
- Admin đăng nhập 2 bước (2FA): password + email OTP
- Ghi nhật ký (audit log) mọi hành động đăng nhập/đăng xuất
- Phân quyền theo role: mỗi trang kiểm tra `require_role()`
- Chống Session Fixation: `session_regenerate_id()` sau đăng nhập

---

## 📌 Lưu ý

- Trang GPS (`dieuphoI_gps.php`) dùng dữ liệu **demo** — tích hợp thiết bị GPS thật bằng cách ghi tọa độ vào bảng `gps_log`
- File `database.sql` chứa hash mật khẩu test, **thay bằng hash thật khi deploy production**
- Trang `admin.php` hiển thị mã OTP trực tiếp cho mục đích test — xóa dòng này và bật gửi email thật khi đưa lên server thực
