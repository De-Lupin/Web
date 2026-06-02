-- ============================================================
-- DATABASE.SQL - Hệ Thống Quản Lý Vận Tải Hàng Hóa
-- Phiên bản đầy đủ - bao gồm tất cả bảng và dữ liệu mẫu
-- Công cụ: XAMPP (MySQL 5.7+)
-- Cách dùng: Import file này vào phpMyAdmin hoặc chạy bằng MySQL CLI
-- ============================================================

-- Xóa database cũ nếu có (cẩn thận khi dùng trên production!)
-- DROP DATABASE IF EXISTS quanly;

CREATE DATABASE IF NOT EXISTS quanly
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE quanly;

-- ============================================================
-- BẢNG 1: USERS - Tài khoản người dùng
-- 3 loại: admin, dieuphoI (điều phối viên), khachhang
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,           -- Mã hóa bằng password_hash()
    email       VARCHAR(100) NOT NULL UNIQUE,
    full_name   VARCHAR(100) NOT NULL,
    phone       VARCHAR(20)  DEFAULT NULL,
    role        ENUM('admin','dieuphoI','khachhang') NOT NULL DEFAULT 'khachhang',
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,  -- 1=hoạt động, 0=bị khóa
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login  DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- BẢNG 2: AUDIT_LOG - Nhật ký hoạt động hệ thống
-- Ghi lại mọi hành động: đăng nhập, đăng xuất, thay đổi dữ liệu
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          DEFAULT NULL,
    username    VARCHAR(50)  DEFAULT NULL,
    action      VARCHAR(100) NOT NULL,            -- VD: LOGIN, LOGOUT, CREATE_ORDER
    detail      TEXT         DEFAULT NULL,         -- Mô tả chi tiết hành động
    ip_address  VARCHAR(50)  DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- BẢNG 3: XE - Thông tin phương tiện vận tải
-- ============================================================
CREATE TABLE IF NOT EXISTS xe (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    bien_so         VARCHAR(20)  NOT NULL UNIQUE,  -- Biển số xe VD: 51C-123.45
    loai_xe         ENUM('xe_tai_nhe','xe_tai_trung','xe_tai_nang','dau_keo','xe_dong_lanh','xe_chuyen_dung')
                    NOT NULL DEFAULT 'xe_tai_trung',
    nhan_hieu       VARCHAR(100) DEFAULT NULL,      -- VD: Hyundai HD320
    nam_sx          YEAR         DEFAULT NULL,      -- Năm sản xuất
    tai_trong       DECIMAL(5,2) DEFAULT NULL,      -- Tải trọng (tấn)
    the_tich        DECIMAL(8,2) DEFAULT NULL,      -- Thể tích thùng (m³)
    han_dang_kiem   DATE         DEFAULT NULL,      -- Hạn đăng kiểm
    han_bao_hiem    DATE         DEFAULT NULL,      -- Hạn bảo hiểm xe
    km_hien_tai     INT          NOT NULL DEFAULT 0,-- Số km hiện tại của xe
    muc_tieu_thu    DECIMAL(5,2) DEFAULT NULL,      -- Mức tiêu thụ nhiên liệu (lít/100km)
    tinh_trang      ENUM('san_sang','dang_chay','bao_duong','hong','nghi')
                    NOT NULL DEFAULT 'san_sang',
    ghi_chu         TEXT         DEFAULT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- BẢNG 4: TAI_XE - Thông tin tài xế
-- ============================================================
CREATE TABLE IF NOT EXISTS tai_xe (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    ho_ten          VARCHAR(100) NOT NULL,
    so_dien_thoai   VARCHAR(20)  NOT NULL,
    so_gplx         VARCHAR(50)  DEFAULT NULL,      -- Số giấy phép lái xe
    hang_gplx       ENUM('B1','B2','C','D','E','FC') NOT NULL DEFAULT 'C',
    han_gplx        DATE         DEFAULT NULL,      -- Hạn GPLX
    kinh_nghiem     INT          NOT NULL DEFAULT 0,-- Năm kinh nghiệm
    luong_co_ban    DECIMAL(12,2) NOT NULL DEFAULT 0,
    tinh_trang      ENUM('san_sang','dang_chay','nghi_phep','nghi_viec')
                    NOT NULL DEFAULT 'san_sang',
    ghi_chu         TEXT         DEFAULT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- BẢNG 5: TUYEN_DUONG - Danh sách tuyến đường
-- ============================================================
CREATE TABLE IF NOT EXISTS tuyen_duong (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    ma_tuyen        VARCHAR(20)  NOT NULL UNIQUE,   -- VD: T001, T002
    ten_tuyen       VARCHAR(200) NOT NULL,           -- VD: TP.HCM - Hà Nội
    diem_di         VARCHAR(100) NOT NULL,           -- Điểm xuất phát
    diem_den        VARCHAR(100) NOT NULL,           -- Điểm đến
    loai_tuyen      ENUM('lien_tinh','noi_vung','noi_thanh') NOT NULL DEFAULT 'lien_tinh',
    khoang_cach     DECIMAL(8,2) DEFAULT NULL,       -- Km
    thoi_gian       DECIMAL(5,2) DEFAULT NULL,       -- Giờ ước tính
    gia_co_ban      DECIMAL(12,2) NOT NULL DEFAULT 0,-- Giá cước cơ bản
    mo_ta           TEXT         DEFAULT NULL,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1, -- 1=đang hoạt động
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- BẢNG 6: DON_HANG - Đơn hàng vận chuyển (bảng trung tâm)
-- ============================================================
CREATE TABLE IF NOT EXISTS don_hang (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    ma_don              VARCHAR(30)  NOT NULL UNIQUE,   -- VD: VT-2024-001
    
    -- Thông tin khách hàng
    ten_khach           VARCHAR(100) NOT NULL,
    dien_thoai_kh       VARCHAR(20)  DEFAULT NULL,
    
    -- Địa điểm
    dia_chi_lay         TEXT         NOT NULL,           -- Địa chỉ lấy hàng chi tiết
    dia_chi_giao        TEXT         NOT NULL,           -- Địa chỉ giao hàng chi tiết
    tinh_lay            VARCHAR(100) NOT NULL,           -- Tỉnh/TP lấy hàng
    tinh_giao           VARCHAR(100) NOT NULL,           -- Tỉnh/TP giao hàng
    
    -- Thông tin hàng hóa
    loai_hang           VARCHAR(200) DEFAULT NULL,       -- Loại hàng: điện tử, nông sản...
    trong_luong         DECIMAL(8,3) DEFAULT NULL,       -- Tấn
    the_tich            DECIMAL(8,2) DEFAULT NULL,       -- m³
    loai_van_chuyen     ENUM('hang_le','hang_nguyen_xe','hang_dong_lanh','hang_qua_kho','hang_sieu_truong')
                        NOT NULL DEFAULT 'hang_le',
    
    -- Phân công vận hành
    tuyen_duong_id      INT          DEFAULT NULL,
    xe_id               INT          DEFAULT NULL,
    tai_xe_id           INT          DEFAULT NULL,
    nguoi_tao_id        INT          DEFAULT NULL,       -- Điều phối viên tạo đơn
    
    -- Thời gian
    ngay_lay_hang       DATETIME     DEFAULT NULL,
    ngay_giao_du_kien   DATETIME     DEFAULT NULL,
    ngay_giao_thuc_te   DATETIME     DEFAULT NULL,
    
    -- Tài chính
    gia_cuoc            DECIMAL(12,2) NOT NULL DEFAULT 0, -- Giá cước vận chuyển
    phi_cao_toc         DECIMAL(12,2) NOT NULL DEFAULT 0, -- Phí BOT/cao tốc
    phi_boc_xep         DECIMAL(12,2) NOT NULL DEFAULT 0, -- Phí bốc xếp
    phi_cho_hang        DECIMAL(12,2) NOT NULL DEFAULT 0, -- Phí chờ hàng
    phi_phat_sinh       DECIMAL(12,2) NOT NULL DEFAULT 0, -- Chi phí phát sinh khác
    tong_chi_phi        DECIMAL(12,2) NOT NULL DEFAULT 0, -- Tổng chi phí
    doanh_thu           DECIMAL(12,2) NOT NULL DEFAULT 0, -- Doanh thu từ khách
    loi_nhuan           DECIMAL(12,2) NOT NULL DEFAULT 0, -- Lợi nhuận = doanh_thu - tong_chi_phi
    khoang_cach         DECIMAL(8,2)  DEFAULT NULL,        -- Km tuyến đường
    
    -- Trạng thái & ghi chú
    trang_thai          ENUM('cho_duyet','dang_xu_ly','dang_lay_hang','dang_van_chuyen',
                             'da_giao','hoan_thanh','da_thanh_toan','huy')
                        NOT NULL DEFAULT 'cho_duyet',
    ly_do_huy           TEXT         DEFAULT NULL,
    ghi_chu             TEXT         DEFAULT NULL,
    ngay_tao            DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Khóa ngoại
    FOREIGN KEY (tuyen_duong_id) REFERENCES tuyen_duong(id) ON DELETE SET NULL,
    FOREIGN KEY (xe_id)          REFERENCES xe(id)          ON DELETE SET NULL,
    FOREIGN KEY (tai_xe_id)      REFERENCES tai_xe(id)      ON DELETE SET NULL,
    FOREIGN KEY (nguoi_tao_id)   REFERENCES users(id)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- BẢNG 7: CHUYEN_XE - Chi tiết mỗi chuyến đi của xe
-- Một đơn hàng có thể có nhiều chuyến xe
-- ============================================================
CREATE TABLE IF NOT EXISTS chuyen_xe (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    don_hang_id     INT          NOT NULL,
    xe_id           INT          NOT NULL,
    tai_xe_id       INT          NOT NULL,
    
    km_bat_dau      INT          NOT NULL DEFAULT 0,  -- Km lúc xuất phát
    km_ket_thuc     INT          DEFAULT NULL,         -- Km lúc về/đến
    km_thuc_te      INT          AS (CASE WHEN km_ket_thuc IS NOT NULL
                                    THEN km_ket_thuc - km_bat_dau
                                    ELSE 0 END) STORED, -- Km thực tế đi được
    
    nhien_lieu      DECIMAL(8,2) DEFAULT NULL,         -- Lít nhiên liệu tiêu thụ
    chi_phi_duong   DECIMAL(12,2) DEFAULT NULL,        -- Chi phí đường: xăng, BOT...
    
    thoi_gian_xuat  DATETIME     DEFAULT NULL,         -- Giờ xuất phát
    thoi_gian_den   DATETIME     DEFAULT NULL,         -- Giờ đến nơi
    
    trang_thai      ENUM('cho_xuat','dang_di','hoan_thanh','su_co')
                    NOT NULL DEFAULT 'cho_xuat',
    ghi_chu_cx      TEXT         DEFAULT NULL,         -- Ghi chú: sự cố, vấn đề dọc đường
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (don_hang_id) REFERENCES don_hang(id) ON DELETE CASCADE,
    FOREIGN KEY (xe_id)       REFERENCES xe(id)       ON DELETE RESTRICT,
    FOREIGN KEY (tai_xe_id)   REFERENCES tai_xe(id)   ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- BẢNG 8: THONG_BAO - Thông báo nội bộ hệ thống
-- ============================================================
CREATE TABLE IF NOT EXISTS thong_bao (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nguoi_gui_id    INT          DEFAULT NULL,         -- NULL = hệ thống tự gửi
    nguoi_nhan_id   INT          NOT NULL,
    tieu_de         VARCHAR(200) NOT NULL,
    noi_dung        TEXT         DEFAULT NULL,
    loai            ENUM('thong_tin','canh_bao','khan_cap') NOT NULL DEFAULT 'thong_tin',
    da_doc          TINYINT(1)   NOT NULL DEFAULT 0,   -- 0=chưa đọc, 1=đã đọc
    lien_ket        VARCHAR(200) DEFAULT NULL,          -- URL dẫn đến trang liên quan
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (nguoi_nhan_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- BẢNG 9: SYSTEM_SETTINGS - Cài đặt hệ thống
-- ============================================================
CREATE TABLE IF NOT EXISTS system_settings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    `key`       VARCHAR(100) NOT NULL UNIQUE,
    `value`     TEXT         DEFAULT NULL,
    `group`     VARCHAR(50)  DEFAULT 'general',
    label       VARCHAR(200) DEFAULT NULL,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- BẢNG 10: GPS_LOG - Lịch sử vị trí xe (tùy chọn)
-- ============================================================
CREATE TABLE IF NOT EXISTS gps_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    xe_id       INT          NOT NULL,
    chuyen_id   INT          DEFAULT NULL,
    lat         DECIMAL(10,8) NOT NULL,              -- Vĩ độ
    lng         DECIMAL(11,8) NOT NULL,              -- Kinh độ
    van_toc     INT          DEFAULT NULL,            -- km/h
    ghi_chu     VARCHAR(200) DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (xe_id)     REFERENCES xe(id)         ON DELETE CASCADE,
    FOREIGN KEY (chuyen_id) REFERENCES chuyen_xe(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- INDEX - Tăng tốc truy vấn hay dùng
-- ============================================================
CREATE INDEX idx_don_hang_trang_thai ON don_hang(trang_thai);
CREATE INDEX idx_don_hang_ngay_tao   ON don_hang(ngay_tao);
CREATE INDEX idx_don_hang_xe_id      ON don_hang(xe_id);
CREATE INDEX idx_chuyen_xe_don_id    ON chuyen_xe(don_hang_id);
CREATE INDEX idx_audit_log_user_id   ON audit_log(user_id);
CREATE INDEX idx_audit_log_ngay      ON audit_log(created_at);
CREATE INDEX idx_thong_bao_nhan      ON thong_bao(nguoi_nhan_id, da_doc);

-- ============================================================
-- DỮ LIỆU MẪU
-- Mật khẩu chung: 123456
-- password_hash('123456', PASSWORD_DEFAULT) tạo ra hash bên dưới
-- ============================================================

-- Tài khoản người dùng mẫu
INSERT INTO users (username, password, email, full_name, phone, role) VALUES
('admin',    '123456', 'admin@vantai.vn',    'Quản Trị Viên',       '028.1234.5678', 'admin'),
('dieuphoi1','123456', 'dp1@vantai.vn',      'Nguyễn Văn Minh',     '0901.111.111',  'dieuphoI'),
('dieuphoi2','123456', 'dp2@vantai.vn',      'Trần Thị Hoa',        '0902.222.222',  'dieuphoI'),
('khachhang1','123456','kh1@email.com',      'Công Ty ABC',         '0903.333.333',  'khachhang'),
('khachhang2','123456','kh2@email.com',      'Công Ty XYZ',         '0904.444.444',  'khachhang');

-- Xe mẫu
INSERT INTO xe (bien_so, loai_xe, nhan_hieu, nam_sx, tai_trong, the_tich, han_dang_kiem, han_bao_hiem, km_hien_tai, muc_tieu_thu, tinh_trang) VALUES
('51C-123.45', 'xe_tai_trung',  'Hyundai HD210',  2021, 8.50,  30.0, '2025-06-30', '2025-03-15', 125000, 18.0, 'san_sang'),
('51C-678.90', 'xe_tai_nang',   'Dongfeng 3 cầu', 2020, 15.00, 50.0, '2025-09-30', '2025-08-20', 98000,  25.0, 'san_sang'),
('51C-246.80', 'dau_keo',       'Volvo FH16',     2022, 30.00, 0.0,  '2026-01-15', '2025-12-10', 45000,  32.0, 'dang_chay'),
('51C-135.79', 'xe_dong_lanh',  'Isuzu Elf',      2023, 3.50,  15.0, '2026-03-20', '2026-02-28', 22000,  14.0, 'san_sang'),
('51C-987.65', 'xe_tai_nhe',    'Kia K250',       2022, 2.50,  10.0, '2025-05-15', '2025-04-30', 55000,  12.0, 'bao_duong'),
('51C-111.22', 'xe_tai_trung',  'JAC HFC1048K',   2021, 6.00,  22.0, '2025-11-30', '2025-10-15', 78000,  20.0, 'san_sang');

-- Tài xế mẫu
INSERT INTO tai_xe (ho_ten, so_dien_thoai, so_gplx, hang_gplx, han_gplx, kinh_nghiem, luong_co_ban, tinh_trang) VALUES
('Nguyễn Văn An',    '0911.111.001', 'GPLX-001-2019', 'C',  '2029-05-20', 8,  12000000, 'san_sang'),
('Trần Minh Đức',    '0911.111.002', 'GPLX-002-2020', 'C',  '2030-03-15', 5,  10500000, 'dang_chay'),
('Lê Văn Bình',      '0911.111.003', 'GPLX-003-2018', 'E',  '2028-07-10', 12, 15000000, 'san_sang'),
('Phạm Quốc Hùng',   '0911.111.004', 'GPLX-004-2021', 'FC', '2031-09-25', 6,  13000000, 'san_sang'),
('Hoàng Văn Tú',     '0911.111.005', 'GPLX-005-2022', 'C',  '2032-01-30', 3,  9500000,  'nghi_phep'),
('Võ Thành Long',    '0911.111.006', 'GPLX-006-2019', 'D',  '2029-11-15', 9,  11500000, 'san_sang');

-- Tuyến đường mẫu
INSERT INTO tuyen_duong (ma_tuyen, ten_tuyen, diem_di, diem_den, loai_tuyen, khoang_cach, thoi_gian, gia_co_ban) VALUES
('T001', 'TP.HCM - Hà Nội',        'TP. Hồ Chí Minh', 'Hà Nội',       'lien_tinh', 1726.0, 30.0, 45000000),
('T002', 'TP.HCM - Đà Nẵng',       'TP. Hồ Chí Minh', 'Đà Nẵng',      'lien_tinh', 964.0,  16.0, 25000000),
('T003', 'TP.HCM - Cần Thơ',       'TP. Hồ Chí Minh', 'Cần Thơ',      'lien_tinh', 170.0,  3.5,  5000000),
('T004', 'TP.HCM - Bình Dương',    'TP. Hồ Chí Minh', 'Bình Dương',   'noi_vung',  30.0,   1.0,  1500000),
('T005', 'TP.HCM - Đồng Nai',      'TP. Hồ Chí Minh', 'Đồng Nai',     'noi_vung',  35.0,   1.5,  1800000),
('T006', 'TP.HCM - Vũng Tàu',      'TP. Hồ Chí Minh', 'Vũng Tàu',     'lien_tinh', 125.0,  2.5,  4000000),
('T007', 'Hà Nội - Hải Phòng',     'Hà Nội',           'Hải Phòng',    'lien_tinh', 105.0,  2.0,  3500000),
('T008', 'TP.HCM - Nội Thành',     'Quận 1, TP.HCM',   'Quận 12, TP.HCM','noi_thanh',15.0, 0.5,  500000);

-- Đơn hàng mẫu
INSERT INTO don_hang (ma_don, ten_khach, dien_thoai_kh, dia_chi_lay, dia_chi_giao, tinh_lay, tinh_giao, loai_hang, trong_luong, loai_van_chuyen, tuyen_duong_id, xe_id, tai_xe_id, nguoi_tao_id, ngay_lay_hang, ngay_giao_du_kien, gia_cuoc, phi_cao_toc, phi_boc_xep, tong_chi_phi, doanh_thu, loi_nhuan, trang_thai) VALUES
('VT-2024-001', 'Công Ty CP Điện Tử Tân Bình', '028.9876.5432', '123 Cộng Hòa, Tân Bình, TP.HCM',   '45 Nguyễn Chí Thanh, Đống Đa, Hà Nội',  'TP.HCM', 'Hà Nội',   'Thiết bị điện tử',  5.0,  'hang_nguyen_xe', 1, 3, 2, 2, '2024-12-15 08:00:00', '2024-12-17 18:00:00', 42000000, 1500000, 500000, 44000000, 50000000, 6000000,  'hoan_thanh'),
('VT-2024-002', 'Siêu Thị Big C',              '028.1111.2222', '88 Tây Sơn, Bình Thạnh, TP.HCM',    '200 Trần Phú, Hải Châu, Đà Nẵng',       'TP.HCM', 'Đà Nẵng',  'Thực phẩm đông lạnh',3.0, 'hang_dong_lanh', 2, 4, 1, 2, '2024-12-18 06:00:00', '2024-12-19 20:00:00', 23000000, 800000,  300000, 24100000, 28000000, 3900000,  'da_giao'),
('VT-2024-003', 'Công Ty TNHH Nội Thất ABC',  '090.333.4444',  '56 Đinh Bộ Lĩnh, Bình Thạnh, HCM',  '789 Quốc Lộ 13, Thủ Đức, TP.HCM',      'TP.HCM', 'TP.HCM',   'Nội thất, gỗ',      2.0,  'hang_le',        4, 1, 6, 2, '2024-12-20 09:00:00', '2024-12-20 17:00:00', 1400000,  50000,   100000, 1550000,  2000000,  450000,   'dang_van_chuyen'),
('VT-2024-004', 'Cty CP Xuất Nhập Khẩu Miền Nam','028.5555.6666','300 Bến Vân Đồn, Q4, TP.HCM',     '12 Nguyễn Văn Linh, Cần Thơ',           'TP.HCM', 'Cần Thơ',  'Nông sản',          12.0, 'hang_nguyen_xe', 3, 2, 4, 3, '2024-12-20 07:00:00', '2024-12-20 14:00:00', 4500000,  150000,  200000, 4850000,  6000000,  1150000,  'dang_xu_ly'),
('VT-2024-005', 'Kho Vận Thành Phát',          '090.777.8888',  '99 Lê Văn Việt, Quận 9, TP.HCM',   '50 Phạm Văn Thuận, Biên Hòa, Đồng Nai', 'TP.HCM', 'Đồng Nai', 'Linh kiện điện tử', 1.5,  'hang_le',        5, 1, 1, 3, '2024-12-21 10:00:00', '2024-12-21 15:00:00', 1600000,  0,       100000, 1700000,  2200000,  500000,   'cho_duyet'),
('VT-2024-006', 'Công Ty Du Lịch Phương Nam',  '028.9999.0000', '15 Pasteur, Quận 3, TP.HCM',       '88 Trần Hưng Đạo, Vũng Tàu',            'TP.HCM', 'Vũng Tàu', 'Đồ dùng du lịch',   0.5,  'hang_le',        6, 1, 6, 2, '2024-12-22 08:00:00', '2024-12-22 12:00:00', 3500000,  200000,  0,      3700000,  4500000,  800000,   'cho_duyet');

-- Chuyến xe mẫu (cho đơn hàng đã hoàn thành)
INSERT INTO chuyen_xe (don_hang_id, xe_id, tai_xe_id, km_bat_dau, km_ket_thuc, nhien_lieu, chi_phi_duong, thoi_gian_xuat, thoi_gian_den, trang_thai) VALUES
(1, 3, 2, 44850, 46576, 553.0, 1500000, '2024-12-15 08:30:00', '2024-12-17 17:45:00', 'hoan_thanh'),
(2, 4, 1, 22000, 22964, 135.0, 800000,  '2024-12-18 06:15:00', '2024-12-19 19:50:00', 'hoan_thanh'),
(3, 1, 6, 124985,125000, 3.5,  50000,   '2024-12-20 09:30:00', NULL,                  'dang_di');

-- Cài đặt mặc định hệ thống
INSERT INTO system_settings (`key`, `value`, `group`, `label`) VALUES
('ten_cong_ty',         'Công Ty TNHH Vận Tải Đường Bộ',  'general',  'Tên công ty'),
('dia_chi_cong_ty',     '123 Đường ABC, Q.1, TP.HCM',     'general',  'Địa chỉ'),
('so_dien_thoai',       '028.1234.5678',                   'general',  'Số điện thoại'),
('email_cong_ty',       'info@vantai.vn',                  'general',  'Email công ty'),
('website',             'www.vantai.vn',                   'general',  'Website'),
('thue_vat',            '10',                              'finance',  'Thuế VAT (%)'),
('tien_te',             'VND',                             'finance',  'Đơn vị tiền tệ'),
('phi_boc_xep_mac_dinh','200000',                          'finance',  'Phí bốc xếp mặc định (₫)'),
('phi_cao_toc_mac_dinh','150000',                          'finance',  'Phí cao tốc mặc định (₫)'),
('so_km_bao_duong',     '20000',                           'vehicle',  'Km bảo dưỡng định kỳ'),
('canh_bao_dang_kiem',  '30',                              'vehicle',  'Cảnh báo đăng kiểm trước (ngày)'),
('canh_bao_gplx',       '60',                              'vehicle',  'Cảnh báo GPLX trước (ngày)'),
('session_timeout',     '480',                             'security', 'Session timeout (phút)'),
('max_login_fail',      '5',                               'security', 'Số lần đăng nhập sai tối đa'),
('require_2fa_admin',   '1',                               'security', 'Bắt buộc 2FA cho Admin (1=Có)');

-- Thông báo mẫu (gửi đến điều phối viên có id=2)
INSERT INTO thong_bao (nguoi_gui_id, nguoi_nhan_id, tieu_de, noi_dung, loai, lien_ket) VALUES
(NULL, 2, 'Xe 51C-987.65 đang bảo dưỡng',      'Xe 51C-987.65 đã được đưa vào bảo dưỡng định kỳ. Vui lòng không phân công xe này.', 'canh_bao', 'dieuphoI_phan_xe.php'),
(NULL, 2, 'Có 2 đơn hàng mới chờ xử lý',       'Đơn VT-2024-005 và VT-2024-006 đang chờ duyệt và phân xe.', 'thong_tin', 'dieuphoI_don_hang.php'),
(NULL, 3, 'Nhắc nhở: Đơn hàng cần xử lý hôm nay', 'Đơn VT-2024-004 cần xuất phát trước 7:00 sáng.', 'khan_cap', 'dieuphoI_don_hang.php');

-- ============================================================
-- GHI CHÚ QUAN TRỌNG
-- ============================================================
-- 1. Mật khẩu hash '123456'
--    là hash của '123456' dùng để test. Thay bằng hash thật khi deploy.
--
-- 2. Tài khoản test:
--    - Admin:      admin      / 123456 / admin@vantai.vn
--    - Điều phối 1: dieuphoi1 / 123456
--    - Điều phối 2: dieuphoi2 / 123456
--    - Khách hàng:  khachhang1 / 123456
--
-- 3. Bảng gps_log chỉ cần thiết nếu tích hợp GPS thật.
--    Trang dieuphoI_gps.php dùng dữ liệu demo nếu bảng trống.
-- ============================================================
