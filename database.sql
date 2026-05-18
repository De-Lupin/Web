-- ============================================================
-- DATABASE.SQL - Hệ Thống Quản Lý Vận Tải Hàng Hóa
-- 3 role: admin, dieuphoI (Điều phối), khachhang (Khách hàng)
-- Công cụ: XAMPP (MySQL)
-- ============================================================

CREATE DATABASE IF NOT EXISTS quanly
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE quanly;

-- ============================================================
-- BẢNG USERS
-- Dùng cột `role` để phân quyền, không cần nhiều bảng riêng
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    full_name   VARCHAR(100) NOT NULL,
    phone       VARCHAR(20)  DEFAULT NULL,
    role        ENUM('admin','dieuphoI','khachhang') NOT NULL DEFAULT 'khachhang',
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login  DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- BẢNG AUDIT_LOG - Nhật ký hành động người dùng (phân hệ 3.1)
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT         DEFAULT NULL,
    username    VARCHAR(50) DEFAULT NULL,
    action      VARCHAR(100) NOT NULL,
    detail      TEXT        DEFAULT NULL,
    ip_address  VARCHAR(50) DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Tài khoản mẫu sẽ được tạo qua file create_test_users.php
-- Mật khẩu chung: 123456
-- ============================================================
