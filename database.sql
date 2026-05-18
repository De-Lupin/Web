-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2026 at 03:56 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `quanly`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `detail` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `username`, `action`, `detail`, `ip_address`, `created_at`) VALUES
(1, 1, 'admin', 'ADMIN_LOGIN', 'Đăng nhập Admin thành công (2FA)', '::1', '2026-05-11 10:05:10'),
(2, 1, 'admin', 'LOGOUT', 'Đăng xuất thành công', '::1', '2026-05-11 10:05:54'),
(3, 1, 'admin', 'ADMIN_LOGIN', 'Đăng nhập Admin thành công (2FA)', '::1', '2026-05-11 13:58:18'),
(4, 1, 'admin', 'LOGOUT', 'Đăng xuất thành công', '::1', '2026-05-11 14:57:15'),
(5, NULL, 'dieuphoI', 'LOGIN_FAILED', 'Tài khoản không tồn tại', '::1', '2026-05-11 14:57:29'),
(6, NULL, 'dieuphoI', 'ADMIN_LOGIN_FAILED', 'Tài khoản không tồn tại', '::1', '2026-05-11 14:58:05'),
(7, NULL, 'dieuphoI01', 'ADMIN_LOGIN_FAILED', 'Tài khoản không tồn tại', '::1', '2026-05-11 14:59:49'),
(8, 2, 'dieuphoI01', 'LOGIN', 'Đăng nhập thành công', '::1', '2026-05-11 15:00:09'),
(9, 1, 'admin', 'ADMIN_LOGIN', 'Đăng nhập Admin thành công (2FA)', '::1', '2026-05-18 06:55:27'),
(10, 1, 'admin', 'ADMIN_LOGIN', 'Đăng nhập Admin thành công (2FA)', '::1', '2026-05-18 18:54:30'),
(11, 1, 'admin', 'LOGOUT', 'Đăng xuất thành công', '::1', '2026-05-18 18:54:49'),
(12, 2, 'dieuphoI01', 'LOGIN', 'Đăng nhập thành công', '::1', '2026-05-18 18:55:19'),
(13, 2, 'dieuphoI01', 'LOGOUT', 'Đăng xuất thành công', '::1', '2026-05-18 19:10:04'),
(14, 2, 'dieuphoI01', 'LOGIN', 'Đăng nhập thành công', '::1', '2026-05-18 19:10:15'),
(15, 2, 'dieuphoI01', 'LOGOUT', 'Đăng xuất thành công', '::1', '2026-05-18 19:10:31'),
(16, 3, 'khach01', 'LOGIN', 'Đăng nhập thành công', '::1', '2026-05-18 19:11:27'),
(17, 3, 'khach01', 'LOGOUT', 'Đăng xuất thành công', '::1', '2026-05-18 19:15:25'),
(18, 1, 'admin', 'ADMIN_LOGIN', 'Đăng nhập Admin thành công (2FA)', '::1', '2026-05-18 19:15:48'),
(19, 1, 'admin', 'LOGOUT', 'Đăng xuất thành công', '::1', '2026-05-18 20:49:37');

-- --------------------------------------------------------

--
-- Table structure for table `chuyen_xe`
--

CREATE TABLE `chuyen_xe` (
  `id` int(11) NOT NULL,
  `don_hang_id` int(11) NOT NULL,
  `xe_id` int(11) NOT NULL,
  `tai_xe_id` int(11) NOT NULL,
  `km_bat_dau` int(11) DEFAULT 0,
  `km_ket_thuc` int(11) DEFAULT 0,
  `km_thuc_te` int(11) GENERATED ALWAYS AS (`km_ket_thuc` - `km_bat_dau`) STORED,
  `nhien_lieu` decimal(8,2) DEFAULT 0.00 COMMENT 'Lít tiêu thụ',
  `chi_phi_duong` decimal(15,2) DEFAULT 0.00 COMMENT 'Phí đường, BOT...',
  `ghi_chu_cx` text DEFAULT NULL,
  `trang_thai` enum('cho_xuat_phat','dang_di','hoan_thanh') DEFAULT 'cho_xuat_phat',
  `thoi_gian_xuat` datetime DEFAULT NULL,
  `thoi_gian_den` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `don_hang`
--

CREATE TABLE `don_hang` (
  `id` int(11) NOT NULL,
  `ma_don` varchar(30) NOT NULL,
  `khach_hang_id` int(11) DEFAULT NULL,
  `ten_khach` varchar(100) NOT NULL,
  `dien_thoai_kh` varchar(20) DEFAULT NULL,
  `dia_chi_lay` text NOT NULL,
  `dia_chi_giao` text NOT NULL,
  `tinh_lay` varchar(100) DEFAULT NULL,
  `tinh_giao` varchar(100) DEFAULT NULL,
  `loai_hang` varchar(100) DEFAULT NULL,
  `trong_luong` decimal(8,2) DEFAULT NULL COMMENT 'Tấn',
  `the_tich` decimal(8,2) DEFAULT NULL COMMENT 'm³',
  `loai_van_chuyen` enum('hang_le','hang_nguyen_xe','hang_dong_lanh','hang_qua_kho','hang_sieu_truong') DEFAULT 'hang_le',
  `tuyen_duong_id` int(11) DEFAULT NULL,
  `xe_id` int(11) DEFAULT NULL,
  `tai_xe_id` int(11) DEFAULT NULL,
  `nguoi_tao_id` int(11) DEFAULT NULL,
  `ngay_lay_hang` datetime DEFAULT NULL,
  `ngay_giao_du_kien` datetime DEFAULT NULL,
  `ngay_giao_thuc_te` datetime DEFAULT NULL,
  `gia_cuoc` decimal(15,2) DEFAULT 0.00,
  `phi_phat_sinh` decimal(15,2) DEFAULT 0.00,
  `phi_cao_toc` decimal(15,2) DEFAULT 0.00,
  `phi_boc_xep` decimal(15,2) DEFAULT 0.00,
  `phi_cho_hang` decimal(15,2) DEFAULT 0.00,
  `tong_chi_phi` decimal(15,2) DEFAULT 0.00,
  `doanh_thu` decimal(15,2) DEFAULT 0.00,
  `loi_nhuan` decimal(15,2) DEFAULT 0.00,
  `trang_thai` enum('cho_duyet','dang_xu_ly','dang_lay_hang','dang_van_chuyen','da_giao','hoan_thanh','da_thanh_toan','huy') NOT NULL DEFAULT 'cho_duyet',
  `ly_do_huy` text DEFAULT NULL,
  `ghi_chu` text DEFAULT NULL,
  `ngay_tao` datetime DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `don_hang`
--

INSERT INTO `don_hang` (`id`, `ma_don`, `khach_hang_id`, `ten_khach`, `dien_thoai_kh`, `dia_chi_lay`, `dia_chi_giao`, `tinh_lay`, `tinh_giao`, `loai_hang`, `trong_luong`, `the_tich`, `loai_van_chuyen`, `tuyen_duong_id`, `xe_id`, `tai_xe_id`, `nguoi_tao_id`, `ngay_lay_hang`, `ngay_giao_du_kien`, `ngay_giao_thuc_te`, `gia_cuoc`, `phi_phat_sinh`, `phi_cao_toc`, `phi_boc_xep`, `phi_cho_hang`, `tong_chi_phi`, `doanh_thu`, `loi_nhuan`, `trang_thai`, `ly_do_huy`, `ghi_chu`, `ngay_tao`, `ngay_cap_nhat`) VALUES
(1, 'VT-2024-001', NULL, 'Công ty ABC', '0911111111', '12 Nguyễn Huệ, Q.1, HCM', '45 Hoàng Diệu, Hà Nội', 'TP.HCM', 'Hà Nội', 'Hàng điện tử', 8.50, NULL, 'hang_nguyen_xe', 1, 1, 1, NULL, '2024-12-20 08:00:00', '2024-12-21 18:00:00', NULL, 8500000.00, 0.00, 450000.00, 300000.00, 0.00, 5800000.00, 9250000.00, 3450000.00, 'dang_van_chuyen', NULL, NULL, '2026-05-18 19:08:33', '2026-05-18 19:08:33'),
(2, 'VT-2024-002', NULL, 'Công ty XYZ', '0922222222', '56 Lê Lợi, Q.1, HCM', '78 Trần Phú, Đà Nẵng', 'TP.HCM', 'Đà Nẵng', 'Hàng may mặc', 4.20, NULL, 'hang_le', 2, 2, 2, NULL, '2024-12-20 06:00:00', '2024-12-21 08:00:00', NULL, 4800000.00, 0.00, 200000.00, 150000.00, 0.00, 3200000.00, 5150000.00, 1950000.00, 'dang_van_chuyen', NULL, NULL, '2026-05-18 19:08:33', '2026-05-18 19:08:33'),
(3, 'VT-2024-003', NULL, 'Anh Tuấn', '0933333333', '89 CMT8, Q.3, HCM', '12 Võ Văn Kiệt, Bình Dương', 'TP.HCM', 'Bình Dương', 'Máy móc', 3.00, NULL, 'hang_nguyen_xe', 4, 3, 3, NULL, '2024-12-20 07:30:00', '2024-12-20 10:00:00', NULL, 500000.00, 0.00, 50000.00, 100000.00, 0.00, 450000.00, 650000.00, 200000.00, 'da_giao', NULL, NULL, '2026-05-18 19:08:33', '2026-05-18 19:08:33'),
(4, 'VT-2024-004', NULL, 'Công ty DEF', '0944444444', '34 Đinh Tiên Hoàng, Q.BT', '23 Phạm Văn Đồng, HCM', 'TP.HCM', 'TP.HCM', 'Nông sản', 1.50, NULL, 'hang_le', 7, 5, 5, NULL, '2024-12-21 09:00:00', '2024-12-21 14:00:00', NULL, 200000.00, 0.00, 0.00, 50000.00, 0.00, 150000.00, 250000.00, 100000.00, 'cho_duyet', NULL, NULL, '2026-05-18 19:08:33', '2026-05-18 19:08:33'),
(5, 'VT-2024-005', NULL, 'Bà Lan', '0955555555', '67 Võ Thị Sáu, Q.3, HCM', '90 Nguyễn Văn Cừ, Cần Thơ', 'TP.HCM', 'Cần Thơ', 'Hàng gia dụng', 2.80, NULL, 'hang_le', 3, 3, 5, NULL, '2024-12-21 06:00:00', '2024-12-21 10:00:00', NULL, 1200000.00, 0.00, 80000.00, 120000.00, 0.00, 900000.00, 1400000.00, 500000.00, 'hoan_thanh', NULL, NULL, '2026-05-18 19:08:33', '2026-05-18 19:08:33'),
(6, 'VT-2024-006', NULL, 'Công ty GHI', '0966666666', '15 Lê Duẩn, Q.1, HCM', '56 Trường Chinh, Vũng Tàu', 'TP.HCM', 'Vũng Tàu', 'Hóa chất', 6.00, NULL, 'hang_nguyen_xe', 6, 4, 4, NULL, '2024-12-19 07:00:00', '2024-12-19 10:00:00', NULL, 1000000.00, 0.00, 120000.00, 200000.00, 0.00, 750000.00, 1320000.00, 570000.00, 'da_thanh_toan', NULL, NULL, '2026-05-18 19:08:33', '2026-05-18 19:08:33');

-- --------------------------------------------------------

--
-- Table structure for table `tai_xe`
--

CREATE TABLE `tai_xe` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `so_dien_thoai` varchar(20) NOT NULL,
  `so_gplx` varchar(50) DEFAULT NULL,
  `hang_gplx` enum('B1','B2','C','D','E','FC') DEFAULT 'C',
  `han_gplx` date DEFAULT NULL,
  `kinh_nghiem` int(11) DEFAULT 0 COMMENT 'Số năm',
  `tinh_trang` enum('san_sang','dang_chay','nghi_phep','nghi_viec') NOT NULL DEFAULT 'san_sang',
  `luong_co_ban` decimal(12,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tai_xe`
--

INSERT INTO `tai_xe` (`id`, `user_id`, `ho_ten`, `so_dien_thoai`, `so_gplx`, `hang_gplx`, `han_gplx`, `kinh_nghiem`, `tinh_trang`, `luong_co_ban`, `created_at`) VALUES
(1, NULL, 'Nguyễn Văn An', '0901111111', 'GPLX-001', 'C', '2027-01-01', 8, 'dang_chay', 12000000.00, '2026-05-18 19:08:33'),
(2, NULL, 'Trần Văn Bình', '0902222222', 'GPLX-002', 'C', '2026-06-15', 5, 'dang_chay', 11000000.00, '2026-05-18 19:08:33'),
(3, NULL, 'Lê Văn Cường', '0903333333', 'GPLX-003', 'E', '2025-12-01', 12, 'san_sang', 15000000.00, '2026-05-18 19:08:33'),
(4, NULL, 'Phạm Văn Dũng', '0904444444', 'GPLX-004', 'C', '2026-03-20', 6, 'dang_chay', 11500000.00, '2026-05-18 19:08:33'),
(5, NULL, 'Hoàng Văn Em', '0905555555', 'GPLX-005', 'D', '2027-08-10', 9, 'san_sang', 13000000.00, '2026-05-18 19:08:33'),
(6, NULL, 'Vũ Thị Phương', '0906666666', 'GPLX-006', 'B2', '2026-11-15', 3, 'san_sang', 9500000.00, '2026-05-18 19:08:33');

-- --------------------------------------------------------

--
-- Table structure for table `thong_bao`
--

CREATE TABLE `thong_bao` (
  `id` int(11) NOT NULL,
  `nguoi_nhan_id` int(11) NOT NULL,
  `tieu_de` varchar(200) NOT NULL,
  `noi_dung` text DEFAULT NULL,
  `loai` enum('don_hang','phuong_tien','tai_chinh','he_thong') NOT NULL DEFAULT 'he_thong',
  `da_doc` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tuyen_duong`
--

CREATE TABLE `tuyen_duong` (
  `id` int(11) NOT NULL,
  `ma_tuyen` varchar(20) NOT NULL,
  `ten_tuyen` varchar(200) NOT NULL,
  `diem_di` varchar(200) NOT NULL,
  `diem_den` varchar(200) NOT NULL,
  `loai_tuyen` enum('lien_tinh','noi_thanh','noi_vung') NOT NULL DEFAULT 'lien_tinh',
  `khoang_cach` decimal(8,2) DEFAULT NULL COMMENT 'km',
  `thoi_gian` decimal(5,2) DEFAULT NULL COMMENT 'Giờ ước tính',
  `gia_co_ban` decimal(15,2) DEFAULT 0.00 COMMENT 'Đồng/chuyến',
  `mo_ta` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tuyen_duong`
--

INSERT INTO `tuyen_duong` (`id`, `ma_tuyen`, `ten_tuyen`, `diem_di`, `diem_den`, `loai_tuyen`, `khoang_cach`, `thoi_gian`, `gia_co_ban`, `mo_ta`, `is_active`, `created_at`) VALUES
(1, 'T001', 'HCM - Hà Nội', 'TP. Hồ Chí Minh', 'Hà Nội', 'lien_tinh', 1726.00, 30.00, 8500000.00, NULL, 1, '2026-05-18 19:08:33'),
(2, 'T002', 'HCM - Đà Nẵng', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'lien_tinh', 964.00, 16.00, 4800000.00, NULL, 1, '2026-05-18 19:08:33'),
(3, 'T003', 'HCM - Cần Thơ', 'TP. Hồ Chí Minh', 'Cần Thơ', 'lien_tinh', 170.00, 3.00, 1200000.00, NULL, 1, '2026-05-18 19:08:33'),
(4, 'T004', 'HCM - Bình Dương', 'TP. Hồ Chí Minh', 'Bình Dương', 'noi_vung', 30.00, 1.00, 500000.00, NULL, 1, '2026-05-18 19:08:33'),
(5, 'T005', 'HCM - Đồng Nai', 'TP. Hồ Chí Minh', 'Đồng Nai', 'noi_vung', 40.00, 1.50, 650000.00, NULL, 1, '2026-05-18 19:08:33'),
(6, 'T006', 'HCM - Vũng Tàu', 'TP. Hồ Chí Minh', 'Vũng Tàu', 'lien_tinh', 125.00, 2.50, 1000000.00, NULL, 1, '2026-05-18 19:08:33'),
(7, 'T007', 'Nội thành HCM', 'Quận 1, TP.HCM', 'Các quận nội thành', 'noi_thanh', 15.00, 1.00, 200000.00, NULL, 1, '2026-05-18 19:08:33'),
(8, 'T008', 'HCM - Long An', 'TP. Hồ Chí Minh', 'Long An', 'noi_vung', 47.00, 1.50, 700000.00, NULL, 1, '2026-05-18 19:08:33'),
(9, 'T009', 'HCM - Bình Phước', 'TP. Hồ Chí Minh', 'Bình Phước', 'lien_tinh', 120.00, 2.50, 950000.00, NULL, 1, '2026-05-18 19:08:33'),
(10, 'T010', 'HCM - Tây Ninh', 'TP. Hồ Chí Minh', 'Tây Ninh', 'lien_tinh', 99.00, 2.00, 850000.00, NULL, 1, '2026-05-18 19:08:33');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','dieuphoI','khachhang') NOT NULL DEFAULT 'khachhang',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `phone`, `role`, `is_active`, `created_at`, `last_login`) VALUES
(1, 'admin', '$2y$10$315is97YJAcIBXrXjoja3eHVwDEXsQuKbSH8vyJmc26O5KJThuiqe', 'admin@vantai.vn', 'Nguyễn Văn Admin', '0901111111', 'admin', 1, '2026-05-11 09:28:26', '2026-05-18 19:15:48'),
(2, 'dieuphoI01', '$2y$10$315is97YJAcIBXrXjoja3eHVwDEXsQuKbSH8vyJmc26O5KJThuiqe', 'dieuphoI@vantai.vn', 'Lê Thị Điều Phối', '0902222222', 'dieuphoI', 1, '2026-05-11 09:28:26', '2026-05-18 19:10:15'),
(3, 'khach01', '$2y$10$315is97YJAcIBXrXjoja3eHVwDEXsQuKbSH8vyJmc26O5KJThuiqe', 'khach01@gmail.com', 'Trần Văn Khách Hàng', '0903333333', 'khachhang', 1, '2026-05-11 09:28:26', '2026-05-18 19:11:27');

-- --------------------------------------------------------

--
-- Table structure for table `xe`
--

CREATE TABLE `xe` (
  `id` int(11) NOT NULL,
  `bien_so` varchar(20) NOT NULL,
  `loai_xe` enum('xe_tai_nhe','xe_tai_trung','xe_tai_nang','dau_keo','xe_dong_lanh','xe_chuyen_dung') NOT NULL DEFAULT 'xe_tai_trung',
  `nhan_hieu` varchar(100) DEFAULT NULL,
  `nam_sx` year(4) DEFAULT NULL,
  `tai_trong` decimal(6,2) DEFAULT NULL COMMENT 'Tấn',
  `the_tich` decimal(8,2) DEFAULT NULL COMMENT 'm³',
  `tinh_trang` enum('san_sang','dang_chay','bao_duong','hong','nghi') NOT NULL DEFAULT 'san_sang',
  `han_dang_kiem` date DEFAULT NULL,
  `han_bao_hiem` date DEFAULT NULL,
  `km_hien_tai` int(11) DEFAULT 0,
  `muc_tieu_thu` decimal(5,2) DEFAULT NULL COMMENT 'Lít/100km',
  `ghi_chu` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `xe`
--

INSERT INTO `xe` (`id`, `bien_so`, `loai_xe`, `nhan_hieu`, `nam_sx`, `tai_trong`, `the_tich`, `tinh_trang`, `han_dang_kiem`, `han_bao_hiem`, `km_hien_tai`, `muc_tieu_thu`, `ghi_chu`, `created_at`) VALUES
(1, '51C-123.45', 'xe_tai_nang', 'Hyundai HD320', '2021', 15.00, 40.00, 'dang_chay', '2025-06-15', '2025-03-01', 125000, 28.00, NULL, '2026-05-18 19:08:33'),
(2, '51D-678.90', 'dau_keo', 'Volvo FH', '2020', 20.00, NULL, 'dang_chay', '2025-08-20', '2025-04-10', 198000, 35.00, NULL, '2026-05-18 19:08:33'),
(3, '51E-111.22', 'xe_tai_trung', 'Isuzu NQR', '2022', 5.00, 18.00, 'san_sang', '2025-05-10', '2025-02-28', 55000, 18.00, NULL, '2026-05-18 19:08:33'),
(4, '51G-333.44', 'xe_tai_nang', 'Hino 700', '2019', 15.00, 38.00, 'dang_chay', '2025-07-30', '2025-05-15', 210000, 30.00, NULL, '2026-05-18 19:08:33'),
(5, '51H-555.66', 'xe_tai_nhe', 'Kia K250', '2023', 2.50, 8.00, 'san_sang', '2026-01-15', '2025-12-01', 12000, 12.00, NULL, '2026-05-18 19:08:33'),
(6, '51K-777.88', 'xe_dong_lanh', 'Thaco Auman', '2021', 8.00, 22.00, 'bao_duong', '2025-09-01', '2025-06-01', 88000, 25.00, NULL, '2026-05-18 19:08:33'),
(7, '51L-999.00', 'xe_tai_trung', 'Mitsubishi Fuso', '2022', 7.00, 20.00, 'san_sang', '2026-03-20', '2026-01-01', 43000, 20.00, NULL, '2026-05-18 19:08:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `chuyen_xe`
--
ALTER TABLE `chuyen_xe`
  ADD PRIMARY KEY (`id`),
  ADD KEY `don_hang_id` (`don_hang_id`),
  ADD KEY `xe_id` (`xe_id`),
  ADD KEY `tai_xe_id` (`tai_xe_id`);

--
-- Indexes for table `don_hang`
--
ALTER TABLE `don_hang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_don` (`ma_don`),
  ADD KEY `tuyen_duong_id` (`tuyen_duong_id`),
  ADD KEY `xe_id` (`xe_id`),
  ADD KEY `tai_xe_id` (`tai_xe_id`),
  ADD KEY `nguoi_tao_id` (`nguoi_tao_id`);

--
-- Indexes for table `tai_xe`
--
ALTER TABLE `tai_xe`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `thong_bao`
--
ALTER TABLE `thong_bao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nguoi_nhan_id` (`nguoi_nhan_id`);

--
-- Indexes for table `tuyen_duong`
--
ALTER TABLE `tuyen_duong`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_tuyen` (`ma_tuyen`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `xe`
--
ALTER TABLE `xe`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bien_so` (`bien_so`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `chuyen_xe`
--
ALTER TABLE `chuyen_xe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `don_hang`
--
ALTER TABLE `don_hang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tai_xe`
--
ALTER TABLE `tai_xe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `thong_bao`
--
ALTER TABLE `thong_bao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tuyen_duong`
--
ALTER TABLE `tuyen_duong`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `xe`
--
ALTER TABLE `xe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `chuyen_xe`
--
ALTER TABLE `chuyen_xe`
  ADD CONSTRAINT `chuyen_xe_ibfk_1` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chuyen_xe_ibfk_2` FOREIGN KEY (`xe_id`) REFERENCES `xe` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chuyen_xe_ibfk_3` FOREIGN KEY (`tai_xe_id`) REFERENCES `tai_xe` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `don_hang`
--
ALTER TABLE `don_hang`
  ADD CONSTRAINT `don_hang_ibfk_1` FOREIGN KEY (`tuyen_duong_id`) REFERENCES `tuyen_duong` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `don_hang_ibfk_2` FOREIGN KEY (`xe_id`) REFERENCES `xe` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `don_hang_ibfk_3` FOREIGN KEY (`tai_xe_id`) REFERENCES `tai_xe` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `don_hang_ibfk_4` FOREIGN KEY (`nguoi_tao_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tai_xe`
--
ALTER TABLE `tai_xe`
  ADD CONSTRAINT `tai_xe_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `thong_bao`
--
ALTER TABLE `thong_bao`
  ADD CONSTRAINT `thong_bao_ibfk_1` FOREIGN KEY (`nguoi_nhan_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
