-- ============================================================
-- DATABASE TỔNG HỢP - Hệ Thống Quản Lý Vận Tải Hàng Hóa
-- Tích hợp cấu trúc mới + Dữ liệu gốc + Tài khoản + Lộ trình
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS quanly
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE quanly;

-- --------------------------------------------------------
-- 1. BẢNG USERS
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(50)  NOT NULL UNIQUE, 
    password        VARCHAR(255) NOT NULL,        
    email           VARCHAR(100) NOT NULL UNIQUE,
    full_name       VARCHAR(100) NOT NULL,
    phone           VARCHAR(20)  DEFAULT NULL,
    role            ENUM('admin','điều phối','khách hàng') NOT NULL DEFAULT 'khách hàng',
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,  
    
    phone_verified  TINYINT(1)   NOT NULL DEFAULT 0,
    xacnhanemail    TINYINT(1)   NOT NULL DEFAULT 0,
    login_method    ENUM('mật khẩu','số điện thoại') NOT NULL DEFAULT 'mật khẩu',
    
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login      DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO users (id, username, password, email, full_name, phone, role, is_active, phone_verified, xacnhanemail, login_method, created_at, last_login) VALUES
(1, 'admintong', '$2y$10$C442uFltz2JtmQy88nK3lehI.kLFvxd/66qVkNTNsLuTBWhz9/lXO', 'admin_tong@vantai.vn', 'Quản Trị Hệ Thống', '0999111222', 'admin', 1, 1, 1, 'mật khẩu', CURRENT_TIMESTAMP, NULL),
(2, 'dieuphoi01', '$2y$10$C442uFltz2JtmQy88nK3lehI.kLFvxd/66qVkNTNsLuTBWhz9/lXO', 'dp01@vantai.vn', 'Nguyễn Điều Phối', '0888111222', 'điều phối', 1, 1, 1, 'mật khẩu', CURRENT_TIMESTAMP, NULL),
(3, 'dieuphoi02', '$2y$10$C442uFltz2JtmQy88nK3lehI.kLFvxd/66qVkNTNsLuTBWhz9/lXO', 'dp02@vantai.vn', 'Trần Điều Phối', '0888222333', 'điều phối', 1, 1, 1, 'mật khẩu', CURRENT_TIMESTAMP, NULL),
(4, 'khachhang1', '$2y$10$C442uFltz2JtmQy88nK3lehI.kLFvxd/66qVkNTNsLuTBWhz9/lXO', 'kh1@gmail.com', 'Trần Khách Hàng Một', '0901111111', 'khách hàng', 1, 1, 1, 'mật khẩu', CURRENT_TIMESTAMP, NULL),
(5, 'khachhang2', '$2y$10$C442uFltz2JtmQy88nK3lehI.kLFvxd/66qVkNTNsLuTBWhz9/lXO', 'kh2@gmail.com', 'Lê Khách Hàng Hai', '0902222222', 'khách hàng', 1, 1, 0, 'mật khẩu', CURRENT_TIMESTAMP, NULL),
(6, 'khachhang3', '$2y$10$C442uFltz2JtmQy88nK3lehI.kLFvxd/66qVkNTNsLuTBWhz9/lXO', 'kh3@gmail.com', 'Phạm Khách Hàng Ba', '0903333333', 'khách hàng', 1, 0, 0, 'mật khẩu', CURRENT_TIMESTAMP, NULL);

-- --------------------------------------------------------
-- 2. BẢNG MAXACNHAN (OTP)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS maxacnhan (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    thongtinlienhe  VARCHAR(100) NOT NULL,              
    loailienhe      ENUM('email','số điện thoại') NOT NULL DEFAULT 'email',
    maotp           VARCHAR(6)   NOT NULL,              
    loaixacnhan     ENUM('đăng nhập','đăng ký','đổi mật khẩu') NOT NULL DEFAULT 'đăng nhập',
    dasudung        TINYINT(1)   NOT NULL DEFAULT 0,    
    thoigianhethan  DATETIME     NOT NULL,              
    thoigiantaoluc  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_maxacnhan (thongtinlienhe, maotp, thoigianhethan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. BẢNG AUDIT_LOG
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          DEFAULT NULL,
    username    VARCHAR(50)  DEFAULT NULL,
    action      VARCHAR(100) NOT NULL,            
    detail      TEXT         DEFAULT NULL,         
    ip_address  VARCHAR(50)  DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. BẢNG XE
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS xe (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    bien_so         VARCHAR(20)  NOT NULL UNIQUE,  
    loai_xe         ENUM('xe tải nhẹ','xe tải trung','xe tải nặng','đầu kéo','xe đông lạnh','xe chuyên dụng') NOT NULL DEFAULT 'xe tải trung',
    nhan_hieu       VARCHAR(100) DEFAULT NULL,      
    nam_sx          YEAR         DEFAULT NULL,      
    tai_trong       DECIMAL(10,2) DEFAULT NULL,      
    the_tich        DECIMAL(10,2) DEFAULT NULL,      
    han_dang_kiem   DATE         DEFAULT NULL,      
    han_bao_hiem    DATE         DEFAULT NULL,      
    km_hien_tai     INT          NOT NULL DEFAULT 0,
    muc_tieu_thu    DECIMAL(10,2) DEFAULT NULL,      
    tinh_trang      ENUM('sẵn sàng','đang chạy','bảo dưỡng','hỏng','nghỉ') NOT NULL DEFAULT 'sẵn sàng',
    ghi_chu         TEXT         DEFAULT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO xe (bien_so, loai_xe, nhan_hieu, nam_sx, tai_trong, the_tich, han_dang_kiem, han_bao_hiem, km_hien_tai, muc_tieu_thu, tinh_trang) VALUES
('51C-123.45', 'xe tải trung',  'Hyundai HD210',  2021, 8.50,  30.0, '2025-06-30', '2025-03-15', 125000, 18.0, 'sẵn sàng'),
('51C-678.90', 'xe tải nặng',   'Dongfeng 3 cầu', 2020, 15.00, 50.0, '2025-09-30', '2025-08-20', 98000,  25.0, 'sẵn sàng'),
('51C-246.80', 'đầu kéo',       'Volvo FH16',     2022, 30.00, 0.0,  '2026-01-15', '2025-12-10', 45000,  32.0, 'đang chạy'),
('51C-135.79', 'xe đông lạnh',  'Isuzu Elf',      2023, 3.50,  15.0, '2026-03-20', '2026-02-28', 22000,  14.0, 'sẵn sàng');

-- --------------------------------------------------------
-- 5. BẢNG TAI_XE
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS tai_xe (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    ho_ten          VARCHAR(100) NOT NULL,
    so_dien_thoai   VARCHAR(20)  DEFAULT NULL,
    so_gplx         VARCHAR(50)  DEFAULT NULL,      
    hang_gplx       ENUM('B1','B2','C','D','E','FC') NOT NULL DEFAULT 'C',
    han_gplx        DATE         DEFAULT NULL,      
    kinh_nghiem     INT          NOT NULL DEFAULT 0,
    luong_co_ban    DECIMAL(15,2) NOT NULL DEFAULT 0,
    tinh_trang      ENUM('sẵn sàng','đang chạy','nghỉ phép','nghỉ việc') NOT NULL DEFAULT 'sẵn sàng',
    ghi_chu         TEXT         DEFAULT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO tai_xe (ho_ten, so_dien_thoai, so_gplx, hang_gplx, han_gplx, kinh_nghiem, luong_co_ban, tinh_trang) VALUES
('Nguyễn Văn An',    '0911.111.001', 'GPLX-001-2019', 'C',  '2029-05-20', 8,  12000000, 'sẵn sàng'),
('Trần Minh Đức',    '0911.111.002', 'GPLX-002-2020', 'C',  '2030-03-15', 5,  10500000, 'đang chạy'),
('Lê Văn Bình',      '0911.111.003', 'GPLX-003-2018', 'E',  '2028-07-10', 12, 15000000, 'sẵn sàng');

-- --------------------------------------------------------
-- 6. BẢNG TUYEN_DUONG
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS tuyen_duong (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    ma_tuyen        VARCHAR(20)  DEFAULT NULL UNIQUE,   
    ten_tuyen       VARCHAR(200) NOT NULL,           
    diem_di         VARCHAR(200) DEFAULT NULL,           
    diem_den        VARCHAR(200) DEFAULT NULL,           
    loai_tuyen      ENUM('liên tỉnh','nội vùng','nội thành') NOT NULL DEFAULT 'liên tỉnh',
    khoang_cach     DECIMAL(10,2) DEFAULT NULL,       
    thoi_gian       DECIMAL(5,2) DEFAULT NULL,       
    gia_co_ban      DECIMAL(12,2) NOT NULL DEFAULT 0,
    mo_ta           TEXT         DEFAULT NULL,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1, 
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO tuyen_duong (id, ma_tuyen, ten_tuyen, diem_di, diem_den, loai_tuyen, khoang_cach, thoi_gian, gia_co_ban) VALUES
(1, 'T001', 'TP.HCM - Hà Nội', 'TP. Hồ Chí Minh', 'Hà Nội', 'liên tỉnh', 1726.00, 30.00, 45000000.00);

-- --------------------------------------------------------
-- 7. BẢNG KHO (Đã gộp đầy đủ từ db_lo_trinh.sql)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS kho (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ma_kho      VARCHAR(20)  NOT NULL UNIQUE,   
    ten_kho     VARCHAR(150) NOT NULL,           
    dia_chi     TEXT         DEFAULT NULL,
    tinh_thanh  VARCHAR(100) NOT NULL,
    lat         DECIMAL(10,8) DEFAULT NULL,      
    lng         DECIMAL(11,8) DEFAULT NULL,      
    loai_kho    ENUM('kho chính','kho trung chuyển','điểm giao') NOT NULL DEFAULT 'kho trung chuyển',
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO kho (ma_kho, ten_kho, dia_chi, tinh_thanh, lat, lng, loai_kho) VALUES
('KHO-HCM', 'Kho TP. Hồ Chí Minh', '123 Đường Bình Thới, Q.11, TP.HCM', 'TP.HCM', 10.76590, 106.66230, 'kho chính'),
('KHO-HN', 'Kho Hà Nội', '45 Đường Giải Phóng, Hoàng Mai, Hà Nội', 'Hà Nội', 21.00120, 105.84170, 'kho chính'),
('KHO-DN', 'Kho Đà Nẵng', '88 Nguyễn Tất Thành, Hải Châu, Đà Nẵng', 'Đà Nẵng', 16.06780, 108.22080, 'kho trung chuyển'),
('KHO-CT', 'Kho Cần Thơ', '200 Nguyễn Văn Cừ, Ninh Kiều, Cần Thơ', 'Cần Thơ', 10.04500, 105.74680, 'kho trung chuyển'),
('KHO-BD', 'Kho Bình Dương', '56 Đại Lộ Bình Dương, Thuận An', 'Bình Dương', 10.97870, 106.65180, 'kho trung chuyển'),
('KHO-NTR', 'Kho Nha Trang', '12 Lê Hồng Phong, Nha Trang, Khánh Hòa', 'Khánh Hòa', 12.24600, 109.19400, 'kho trung chuyển'),
('KHO-HP', 'Kho Hải Phòng', '78 Lạch Tray, Ngô Quyền, Hải Phòng', 'Hải Phòng', 20.85540, 106.68810, 'kho trung chuyển'),
('KHO-HUE', 'Kho Huế', '99 Hùng Vương, TP. Huế, Thừa Thiên Huế', 'Huế', 16.46280, 107.59580, 'kho trung chuyển');

-- --------------------------------------------------------
-- 8. BẢNG DON_HANG
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS don_hang (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    ma_don              VARCHAR(50)  NOT NULL UNIQUE,   
    
    ten_khach           VARCHAR(100) DEFAULT NULL,
    dien_thoai_kh       VARCHAR(20)  DEFAULT NULL,
    
    ten_gui             VARCHAR(100) DEFAULT NULL,
    sdt_gui             VARCHAR(20)  DEFAULT NULL,
    email_gui           VARCHAR(100) DEFAULT NULL,
    dia_chi_gui         VARCHAR(255) DEFAULT NULL,
    ten_nhan            VARCHAR(100) DEFAULT NULL,
    sdt_nhan            VARCHAR(20)  DEFAULT NULL,
    email_nhan          VARCHAR(100) DEFAULT NULL,
    dia_chi_nhan        VARCHAR(255) DEFAULT NULL,
    
    dia_chi_lay         TEXT         DEFAULT NULL,           
    dia_chi_giao        TEXT         DEFAULT NULL,           
    tinh_lay            VARCHAR(100) DEFAULT NULL,           
    tinh_giao           VARCHAR(100) DEFAULT NULL,           
    
    ten_hang            VARCHAR(200) DEFAULT NULL,
    loai_hang           VARCHAR(200) DEFAULT 'thường',
    khoi_luong          DECIMAL(10,2) DEFAULT 0,
    trong_luong         DECIMAL(8,3) DEFAULT NULL,       
    the_tich            DECIMAL(10,3) DEFAULT 0,       
    loai_van_chuyen     ENUM('hàng lẻ','hàng nguyên xe','hàng đông lạnh','hàng quá khổ','hàng siêu trường') DEFAULT 'hàng lẻ',
    phuong_thuc         VARCHAR(30)  DEFAULT 'đường bộ',
    
    tuyen_duong_id      INT          DEFAULT NULL,
    xe_id               INT          DEFAULT NULL,
    tai_xe_id           INT          DEFAULT NULL,
    nguoi_tao_id        INT          DEFAULT NULL,       
    
    ngay_lay_hang       DATETIME     DEFAULT NULL,
    ngay_giao_du_kien   DATETIME     DEFAULT NULL,
    ngay_giao_thuc_te   DATETIME     DEFAULT NULL,
    ngay_gui            DATE         DEFAULT NULL,
    
    gia_cuoc            DECIMAL(12,2) NOT NULL DEFAULT 0, 
    phi_cao_toc         DECIMAL(12,2) NOT NULL DEFAULT 0, 
    phi_boc_xep         DECIMAL(12,2) NOT NULL DEFAULT 0, 
    phi_cho_hang        DECIMAL(12,2) NOT NULL DEFAULT 0, 
    phi_phat_sinh       DECIMAL(12,2) NOT NULL DEFAULT 0, 
    tong_chi_phi        DECIMAL(15,2) DEFAULT 0, 
    doanh_thu           DECIMAL(15,2) DEFAULT 0, 
    loi_nhuan           DECIMAL(15,2) DEFAULT 0, 
    khoang_cach         DECIMAL(8,2)  DEFAULT NULL,        
    thanh_toan          VARCHAR(30)   DEFAULT 'tiền mặt',
    payment_status      ENUM('chưa thanh toán','đang chờ','đã thanh toán','thất bại') DEFAULT 'chưa thanh toán',
    
    trang_thai          ENUM('chờ duyệt','đang xử lý','đang lấy hàng','đang vận chuyển','đang giao','đã giao','hoàn thành','đã thanh toán','hủy','chờ thanh toán') NOT NULL DEFAULT 'chờ duyệt',
    ly_do_huy           VARCHAR(500) DEFAULT NULL,
    ghi_chu             TEXT         DEFAULT NULL,
    
    is_deleted          TINYINT(1)   NOT NULL DEFAULT 0,
    deleted_at          DATETIME     DEFAULT NULL,
    deleted_by          INT          DEFAULT NULL,
    ly_do_xoa           TEXT         DEFAULT NULL,
    diem_hop_le         INT          DEFAULT NULL,

    ngay_tao            DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tuyen_duong_id) REFERENCES tuyen_duong(id) ON DELETE SET NULL,
    FOREIGN KEY (xe_id)          REFERENCES xe(id)          ON DELETE SET NULL,
    FOREIGN KEY (tai_xe_id)      REFERENCES tai_xe(id)      ON DELETE SET NULL,
    FOREIGN KEY (nguoi_tao_id)   REFERENCES users(id)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO don_hang (id, ma_don, nguoi_tao_id, xe_id, tai_xe_id, tuyen_duong_id, trang_thai, doanh_thu, loi_nhuan, ghi_chu, ngay_tao, tong_chi_phi, loai_van_chuyen, email_gui, email_nhan, the_tich, thanh_toan, ten_gui, sdt_gui, ten_nhan, sdt_nhan, loai_hang, khoi_luong, phuong_thuc, ten_hang, dia_chi_gui, dia_chi_nhan, payment_status, ngay_gui, ly_do_huy) VALUES
-- Đơn hàng cũ hỗ trợ test Lộ Trình
(1, 'VT-2024-001', 2, (SELECT id FROM xe WHERE bien_so='51C-246.80' LIMIT 1), (SELECT id FROM tai_xe WHERE so_dien_thoai='0911.111.002' LIMIT 1), 1, 'hoàn thành', 50000000.00, 6000000.00, '', '2024-12-15 08:00:00', 44000000.00, 'hàng nguyên xe', NULL, NULL, 0.000, 'tiền mặt', 'Công Ty CP Điện Tử', '0289876543', 'Chi Nhánh HN', '024111222', 'thiết bị', 5000.00, 'đường bộ', 'Thiết bị điện tử', '123 Cộng Hòa, TP.HCM', '45 Nguyễn Chí Thanh, Hà Nội', 'đã thanh toán', NULL, NULL),
-- Các đơn hàng từ DB của bạn (Gắn cho khachhang1 - ID 4)
(2, 'VT-20260525-2961', 4, NULL, NULL, NULL, 'hủy', 750000000.00, 0.00, '', '2026-05-26 01:17:13', 0.00, NULL, 'vannguyen@gmail.com', 'van@gmail.com', 0.000, 'chuyen_khoan', 'Nguyễn Văn A', '0901234567', 'Kim Vạn', '0985566475', 'thuong', 500000.00, 'duong_bo', 'Gạo', '123 Lê Lợi, Huế', '256 Hoàng Văn Thụ', 'chưa thanh toán', NULL, NULL),
(3, 'VT-20260526-3655', 4, NULL, NULL, NULL, 'hủy', 750000000.00, 0.00, '', '2026-05-26 07:52:58', 0.00, NULL, 'vannguyen@gmail.com', 'vantruong@gmail.com', 0.000, 'chuyen_khoan', 'Nguyễn Văn A', '0901234567', 'Kim Vạn', '0985566475', 'thuong', 500000.00, 'duong_bo', 'Gạo', '123 Lê Lợi, Huế', '256 Hoàng Văn Thụ', 'đang chờ', NULL, NULL),
(4, 'VT-20260526-0071', 4, NULL, NULL, NULL, 'hủy', 750000000.00, 0.00, '', '2026-05-26 18:44:42', 0.00, NULL, 'vannguyen@gmail.com', 'vankim@gmail.com', 0.000, 'tien_mat', 'Nguyễn Văn A', '0901234567', 'Kim Vạn', '0985566475', 'thuong', 500000.00, 'duong_bo', 'Gạo nếp', '123 Lê Lợi, Huế', '256 Hoàng Văn Thụ', 'chưa thanh toán', NULL, NULL),
(5, 'VT-20260531-9015', 4, NULL, NULL, NULL, 'hủy', 3000000.00, 0.00, '', '2026-05-31 12:36:57', 0.00, NULL, 'vannguyen@gmail.com', 'vinhdt@gmail.com', 0.000, 'tien_mat', 'Nguyễn Văn A', '0901234567', 'vinh', '0985553322', 'thuong', 20000.00, 'duong_bo', 'Ngô', '128 Quang Trung, xã Tây Sơn', '300 Quang Trung', 'chưa thanh toán', NULL, NULL),
(6, 'VT-20260531-2024', 4, NULL, NULL, NULL, 'hủy', 3350000.00, 0.00, '', '2026-05-31 18:23:02', 0.00, NULL, 'vannguyen@gmail.com', 'vinhdt@gmail.com', 0.000, 'chuyen_khoan', 'Nguyễn Văn A', '0901234567', 'vinh', '0985553322', 'thuong', 20000.00, 'duong_bo', 'Ngô', '123 Lê Lợi', '256 Hoàng Văn Thụ', 'thất bại', NULL, NULL),
(7, 'VT-20260531-9030', 4, NULL, NULL, NULL, 'hủy', 5899981.00, 0.00, 'Không giao ngoài giờ hành chính', '2026-05-31 23:56:47', 0.00, NULL, 'vannguyen@gmail.com', 'vinhdt@gmail.com', 0.000, 'tien_mat', 'Nguyễn Văn A', '0901234567', 'vinh', '0985566475', 'thuong', 29999.90, 'duong_bo', 'Nông sản', '123 Lê Lợi', '300 Quang Trung', 'thất bại', NULL, NULL),
(8, 'VT-20260601-0562', 4, NULL, NULL, NULL, 'hủy', 4175000.00, 0.00, '', '2026-06-01 12:22:10', 0.00, NULL, 'vannguyen@gmail.com', 'vinhdt@gmail.com', 0.000, 'chuyen_khoan', 'Nguyễn Văn A', '0988877727', 'vinh', '0985553322', 'thuong', 25000.00, 'duong_bo', 'Ngô', '123 Lê Lợi', '300 Quang Trung', 'thất bại', NULL, NULL),
(9, 'VT-20260601-3501', 4, NULL, NULL, NULL, 'hủy', 2300000.00, 0.00, 'Giao hàng trong giờ hành chính (8h-17h)', '2026-06-01 12:40:24', 0.00, NULL, 'vannguyen@gmail.com', 'vinhdt@gmail.com', 0.000, 'tien_mat', 'Nguyễn Văn A', '0988877727', 'vinh', '0985553322', 'thuong', 10000.00, 'duong_bo', 'Nông sản', '123 Lê Lợi', '256 Hoàng Văn Thụ', 'chưa thanh toán', NULL, NULL),
(10, 'VT-20260601-4053', 4, NULL, NULL, NULL, 'hủy', 1025000.00, 0.00, 'Giao hàng trong giờ hành chính (8h-17h)', '2026-06-01 12:52:49', 0.00, NULL, 'vannguyen@gmail.com', 'van@gmail.com', 0.000, 'chuyen_khoan', 'Nguyễn Văn A', '0988877727', 'Kim Vạn', '0985553322', 'thuong', 9999.90, 'duong_bo', 'Gạo nếp', '123 Lê Lợi', '300 Quang Trung', 'thất bại', NULL, NULL),
(11, 'VT-20260601-5816', 4, NULL, NULL, NULL, 'hủy', 635000.00, 0.00, 'Gọi điện thoại cho người nhận trước khi giao', '2026-06-01 15:54:08', 0.00, NULL, 'vannguyen@gmail.com', 'vantruong@gmail.com', 0.000, 'chuyen_khoan', 'Nguyễn Văn A', '0988877727', 'Kim Vạn', '0985553322', 'thuong', 3000.00, 'duong_bo', 'Thực phẩm', '123 Lê Lợi', '300 Quang Trung', 'thất bại', NULL, NULL),
(12, 'VT-20260601-8306', 4, NULL, NULL, NULL, 'hủy', 1625000.00, 0.00, 'Gọi điện thoại cho người nhận trước khi giao', '2026-06-01 17:03:26', 0.00, NULL, 'vannguyen@gmail.com', 'vinhdt@gmail.com', 0.000, 'tien_mat', 'Nguyễn Văn A', '0988877727', 'vinh', '0985566475', 'thuong', 7000.00, 'duong_bo', 'Thực phẩm', '123 Lê Lợi, Gia Lai', '300 Quang Trung, Đồng Nai', 'chưa thanh toán', NULL, NULL),
(13, 'VT-20260601-1214', 4, NULL, NULL, NULL, 'hủy', 3000000.00, 0.00, '', '2026-06-01 20:00:27', 0.00, NULL, 'vannguyen@gmail.com', 'Vinhdt@gmail.com', 0.000, 'tien_mat', 'Nguyễn A', '0988555555', 'vinh', '0965888444', 'thuong', 20000.00, 'duong_bo', 'gạo', '2567 quang trung', '567 nguyen thai hoc', 'chưa thanh toán', NULL, 'Đổi ý, không muốn gửi nữa'),
(14, 'VT-20260601-6329', 4, NULL, NULL, NULL, 'chờ duyệt', 1024981.00, 0.00, '', '2026-06-01 20:05:12', 0.00, NULL, 'vannguyen@gmail.com', 'vinhdt@gmail.com', 0.000, 'tien_mat', 'Nguyễn Văn A', '0988877727', 'vinh', '0985566475', 'thuong', 4999.90, 'duong_bo', 'Ngô', '123 Lê Lợi,, Gia Lai', '300 Quang Trung, Cao Bằng', 'chưa thanh toán', NULL, NULL);

-- --------------------------------------------------------
-- 9. BẢNG PHAN_CONG_LOG 
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS phan_cong_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    don_id      INT          NOT NULL,
    xe_id       INT          DEFAULT NULL,
    tai_xe_id   INT          DEFAULT NULL,
    ly_do       TEXT         DEFAULT NULL,
    diem_phu_hop INT         DEFAULT NULL,
    trang_thai  ENUM('thành công','thất bại','thùng rác') NOT NULL,
    nguoi_chay  INT          DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (don_id) REFERENCES don_hang(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 10. BẢNG LO_TRINH_DON_HANG
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS lo_trinh_don_hang (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    don_hang_id     INT          NOT NULL,
    kho_id          INT          DEFAULT NULL,   
    su_kien         ENUM('tạo đơn','duyệt đơn','lấy hàng','đến kho','rời kho','đang vận chuyển','đến kho đích','đang giao','đã giao','thất bại','hoàn hàng') NOT NULL,
    dia_diem        VARCHAR(200) DEFAULT NULL,   
    tinh_thanh      VARCHAR(100) DEFAULT NULL,   
    xe_id           INT          DEFAULT NULL,
    tai_xe_id       INT          DEFAULT NULL,
    nguoi_cap_nhat  INT          DEFAULT NULL,   
    thoi_gian       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    mo_ta           TEXT         DEFAULT NULL,   
    ghi_chu_noi_bo  TEXT         DEFAULT NULL,   
    hinh_anh        VARCHAR(500) DEFAULT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (don_hang_id)    REFERENCES don_hang(id)   ON DELETE CASCADE,
    FOREIGN KEY (kho_id)         REFERENCES kho(id)        ON DELETE SET NULL,
    FOREIGN KEY (xe_id)          REFERENCES xe(id)         ON DELETE SET NULL,
    FOREIGN KEY (tai_xe_id)      REFERENCES tai_xe(id)     ON DELETE SET NULL,
    FOREIGN KEY (nguoi_cap_nhat) REFERENCES users(id)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_lo_trinh_don ON lo_trinh_don_hang(don_hang_id, thoi_gian);

-- CHÈN DỮ LIỆU LỘ TRÌNH (Tự động map với ID mới nhất của đơn VT-2024-001)
SET @don_id = (SELECT id FROM don_hang WHERE ma_don='VT-2024-001' LIMIT 1);
SET @dp_id  = 2;
SET @xe_id  = (SELECT id FROM xe WHERE bien_so='51C-246.80' LIMIT 1);
SET @tx_id  = (SELECT id FROM tai_xe WHERE so_dien_thoai='0911.111.002' LIMIT 1);
SET @kho_hcm= (SELECT id FROM kho WHERE ma_kho='KHO-HCM' LIMIT 1);
SET @kho_dn = (SELECT id FROM kho WHERE ma_kho='KHO-DN' LIMIT 1);
SET @kho_hn = (SELECT id FROM kho WHERE ma_kho='KHO-HN' LIMIT 1);

INSERT IGNORE INTO lo_trinh_don_hang (don_hang_id, kho_id, su_kien, dia_diem, tinh_thanh, xe_id, tai_xe_id, nguoi_cap_nhat, thoi_gian, mo_ta)
SELECT @don_id, NULL, 'tạo đơn', NULL, 'TP.HCM', NULL, NULL, @dp_id, '2024-12-15 07:30:00', 'Đơn hàng VT-2024-001 đã được tạo và đang chờ xử lý.'
WHERE @don_id IS NOT NULL
UNION ALL SELECT @don_id, NULL, 'duyệt đơn', NULL, 'TP.HCM', NULL, NULL, @dp_id, '2024-12-15 08:00:00', 'Đơn hàng đã được duyệt và phân công xe 51C-246.80, chuẩn bị lấy hàng.' WHERE @don_id IS NOT NULL
UNION ALL SELECT @don_id, @kho_hcm, 'lấy hàng', '123 Cộng Hòa, Tân Bình', 'TP.HCM', @xe_id, @tx_id, @dp_id, '2024-12-15 08:30:00', 'Xe 51C-246.80 đã đến lấy hàng tại địa chỉ của bạn.' WHERE @don_id IS NOT NULL
UNION ALL SELECT @don_id, @kho_dn, 'đến kho', NULL, 'Đà Nẵng', @xe_id, @tx_id, @dp_id, '2024-12-16 06:00:00', 'Hàng hóa đã đến kho Đà Nẵng. Đang được phân loại và chuẩn bị tiếp tục hành trình.' WHERE @don_id IS NOT NULL
UNION ALL SELECT @don_id, @kho_dn, 'rời kho', NULL, 'Đà Nẵng', @xe_id, @tx_id, @dp_id, '2024-12-16 10:00:00', 'Hàng đã rời kho Đà Nẵng, tiếp tục hành trình ra Hà Nội.' WHERE @don_id IS NOT NULL
UNION ALL SELECT @don_id, NULL, 'đang vận chuyển', 'Quốc lộ 1A, Km 763', 'Nghệ An', @xe_id, @tx_id, @dp_id, '2024-12-16 20:00:00', 'Hàng đang vận chuyển qua Nghệ An, dự kiến đến Hà Nội ngày mai.' WHERE @don_id IS NOT NULL
UNION ALL SELECT @don_id, @kho_hn, 'đến kho đích', NULL, 'Hà Nội', @xe_id, @tx_id, @dp_id, '2024-12-17 14:00:00', 'Hàng đã đến kho Hà Nội. Chuẩn bị giao đến địa chỉ của bạn.' WHERE @don_id IS NOT NULL
UNION ALL SELECT @don_id, NULL, 'đang giao', '45 Nguyễn Chí Thanh, Đống Đa', 'Hà Nội', @xe_id, @tx_id, @dp_id, '2024-12-17 16:00:00', 'Tài xế đang trên đường giao hàng đến địa chỉ của bạn. Vui lòng chú ý điện thoại!' WHERE @don_id IS NOT NULL
UNION ALL SELECT @don_id, NULL, 'đã giao', '45 Nguyễn Chí Thanh, Đống Đa', 'Hà Nội', @xe_id, @tx_id, @dp_id, '2024-12-17 17:45:00', 'Giao hàng thành công! Cảm ơn bạn đã tin tưởng sử dụng dịch vụ của chúng tôi.' WHERE @don_id IS NOT NULL;

-- --------------------------------------------------------
-- 11. BẢNG CHUYEN_XE 
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS chuyen_xe (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    don_hang_id     INT          DEFAULT NULL,
    xe_id           INT          DEFAULT NULL,
    tai_xe_id       INT          DEFAULT NULL,
    km_bat_dau      INT          NOT NULL DEFAULT 0,  
    km_ket_thuc     INT          DEFAULT NULL,         
    km_thuc_te      DECIMAL(10,2) DEFAULT 0.00, 
    nhien_lieu      DECIMAL(10,2) DEFAULT 0.00,         
    chi_phi_duong   DECIMAL(12,2) DEFAULT NULL,        
    thoi_gian_xuat  DATETIME     DEFAULT NULL,         
    thoi_gian_den   DATETIME     DEFAULT NULL,         
    trang_thai      ENUM('chờ xuất','đang đi','hoàn thành','sự cố') NOT NULL DEFAULT 'chờ xuất',
    ghi_chu_cx      TEXT         DEFAULT NULL,         
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (don_hang_id) REFERENCES don_hang(id) ON DELETE CASCADE,
    FOREIGN KEY (xe_id)       REFERENCES xe(id)       ON DELETE SET NULL,
    FOREIGN KEY (tai_xe_id)   REFERENCES tai_xe(id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 12. BẢNG THONG_BAO 
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS thong_bao (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nguoi_gui_id    INT          DEFAULT NULL,         
    nguoi_nhan_id   INT          DEFAULT NULL,
    tieu_de         VARCHAR(200) DEFAULT NULL,
    noi_dung        TEXT         DEFAULT NULL,
    loai            ENUM('thông tin','cảnh báo','khẩn cấp') NOT NULL DEFAULT 'thông tin',
    da_doc          TINYINT(1)   DEFAULT 0,   
    lien_ket        VARCHAR(200) DEFAULT NULL,          
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nguoi_nhan_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 13. BẢNG SYSTEM_SETTINGS 
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS system_settings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    `key`       VARCHAR(100) NOT NULL UNIQUE,
    `value`     TEXT         DEFAULT NULL,
    `group`     VARCHAR(50)  DEFAULT 'general',
    label       VARCHAR(200) DEFAULT NULL,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO system_settings (id, `key`, `value`, `group`, label, updated_at) VALUES
(1, 'ten_cong_ty', 'Công Ty TNHH Vận Tải Đường Bộ', 'general', 'Tên công ty', '2026-05-24 16:35:06'),
(2, 'dia_chi_cong_ty', '123 Đường ABC, Q.1, TP.HCM', 'general', 'Địa chỉ', '2026-05-24 16:35:06'),
(3, 'so_dien_thoai', '028.1234.5678', 'general', 'Số điện thoại', '2026-05-24 16:35:06'),
(4, 'email_cong_ty', 'info@vantai.vn', 'general', 'Email công ty', '2026-05-24 16:35:06'),
(5, 'website', 'www.vantai.vn', 'general', 'Website', '2026-05-24 16:35:06'),
(6, 'thue_vat', '10', 'finance', 'Thuế VAT (%)', '2026-05-24 16:35:06'),
(7, 'tien_te', 'VND', 'finance', 'Đơn vị tiền tệ', '2026-05-24 16:35:06'),
(8, 'phi_boc_xep_mac_dinh', '200000', 'finance', 'Phí bốc xếp mặc định (₫)', '2026-05-24 16:35:06'),
(9, 'phi_cao_toc_mac_dinh', '150000', 'finance', 'Phí cao tốc mặc định (₫)', '2026-05-24 16:35:06'),
(10, 'so_km_bao_duong', '20000', 'vehicle', 'Km bảo dưỡng định kỳ', '2026-05-24 16:35:06'),
(11, 'canh_bao_dang_kiem', '30', 'vehicle', 'Cảnh báo đăng kiểm trước (ngày)', '2026-05-24 16:35:06'),
(12, 'canh_bao_gplx', '60', 'vehicle', 'Cảnh báo GPLX trước (ngày)', '2026-05-24 16:35:06'),
(13, 'session_timeout', '480', 'security', 'Session timeout (phút)', '2026-05-24 16:35:06'),
(14, 'max_login_fail', '5', 'security', 'Số lần đăng nhập sai tối đa', '2026-05-24 16:35:06'),
(15, 'require_2fa_admin', '1', 'security', 'Bắt buộc 2FA cho Admin (1=Có)', '2026-05-24 16:35:06');

-- --------------------------------------------------------
-- 14. BẢNG GPS_LOG 
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS gps_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    xe_id       INT          NOT NULL,
    chuyen_id   INT          DEFAULT NULL,
    lat         DECIMAL(10,8) NOT NULL,              
    lng         DECIMAL(11,8) NOT NULL,              
    van_toc     INT          DEFAULT NULL,            
    ghi_chu     VARCHAR(200) DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (xe_id)     REFERENCES xe(id)         ON DELETE CASCADE,
    FOREIGN KEY (chuyen_id) REFERENCES chuyen_xe(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;