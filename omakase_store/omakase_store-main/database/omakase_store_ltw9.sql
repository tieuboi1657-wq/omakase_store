git add .
git commit -m "update code"
-- =====================================================================
--  SABI OMAKASE - omakase_store_ltw9 (MySQL 5.7+ / MariaDB 10.3+, XAMPP)
--  Website khách + trang quản trị. Mật khẩu mẫu mọi tài khoản: password
-- =====================================================================

SET NAMES utf8mb4;
DROP DATABASE IF EXISTS omakase_store_ltw9;
CREATE DATABASE omakase_store_ltw9 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE omakase_store_ltw9;

-- ============================ 1. NHÂN SỰ ============================
CREATE TABLE nhan_vien (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  ma_nv         VARCHAR(10)  NOT NULL UNIQUE,
  ho_ten        VARCHAR(100) NOT NULL,
  email         VARCHAR(120) NULL UNIQUE,
  so_dien_thoai VARCHAR(15)  NOT NULL,
  bo_phan       ENUM('Quản lý','Phục vụ','Đầu bếp','Tạp vụ') NOT NULL,
  chuc_danh     VARCHAR(100) NOT NULL,
  chuyen_mon    VARCHAR(150) NULL,
  ngay_vao_lam  DATE NULL,
  trang_thai    ENUM('Hoạt động','Tạm nghỉ','Đã nghỉ') NOT NULL DEFAULT 'Hoạt động',
  ngay_tao      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Đăng nhập trang quản trị
CREATE TABLE tai_khoan_quan_tri (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  nhan_vien_id       INT NOT NULL UNIQUE,
  ten_dang_nhap      VARCHAR(50)  NOT NULL UNIQUE,
  mat_khau           VARCHAR(255) NOT NULL,
  quyen              ENUM('admin','quan_ly','nhan_vien') NOT NULL DEFAULT 'nhan_vien',
  lan_dang_nhap_cuoi DATETIME NULL,
  trang_thai         TINYINT(1) NOT NULL DEFAULT 1,
  ngay_tao           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (nhan_vien_id) REFERENCES nhan_vien(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================== 2. KHÁCH HÀNG ===========================
CREATE TABLE khach_hang (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  ma_kh           VARCHAR(10)  NOT NULL UNIQUE,
  ho_ten          VARCHAR(100) NOT NULL,
  email           VARCHAR(120) NOT NULL UNIQUE,
  so_dien_thoai   VARCHAR(15)  NOT NULL UNIQUE,
  mat_khau        VARCHAR(255) NOT NULL,
  dia_chi         VARCHAR(255) NULL,
  ngay_sinh       DATE NULL,
  hang_thanh_vien ENUM('Thành viên','Vàng','VIP') NOT NULL DEFAULT 'Thành viên',
  diem_tich_luy   INT UNSIGNED NOT NULL DEFAULT 0,
  ghi_chu_khau_vi TEXT NULL,
  trang_thai      ENUM('Hoạt động','Chờ xác thực','Đã khóa') NOT NULL DEFAULT 'Hoạt động',
  ngay_tao        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ngay_cap_nhat   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE phuong_thuc_luu (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  khach_hang_id INT NOT NULL,
  loai          ENUM('Thẻ','Chuyển khoản','Ví điện tử') NOT NULL,
  mo_ta         VARCHAR(100) NOT NULL,
  so_cuoi       VARCHAR(4) NULL,
  han_su_dung   VARCHAR(5) NULL,
  mac_dinh      TINYINT(1) NOT NULL DEFAULT 0,
  ngay_tao      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (khach_hang_id) REFERENCES khach_hang(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================ 3. THỰC ĐƠN ============================
CREATE TABLE loai_set (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  ten_loai VARCHAR(100) NOT NULL UNIQUE,
  mo_ta    VARCHAR(255) NULL,
  thu_tu   TINYINT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE set_omakase (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  ma_set          VARCHAR(20)  NOT NULL UNIQUE,
  ten_set         VARCHAR(150) NOT NULL,
  ten_tieng_nhat  VARCHAR(100) NULL,
  loai_set_id     INT NOT NULL,
  bep_truong_id   INT NULL,
  so_mon          TINYINT UNSIGNED  NOT NULL,
  thoi_luong_phut SMALLINT UNSIGNED NOT NULL,
  gia             DECIMAL(12,0) NOT NULL CHECK (gia > 0),
  nhan            VARCHAR(50)  NULL,
  mo_ta_ngan      VARCHAR(255) NULL,
  mo_ta_chi_tiet  TEXT NULL,
  hinh_anh        VARCHAR(255) NULL,
  noi_bat         TINYINT(1) NOT NULL DEFAULT 0,
  trang_thai      ENUM('Đang bán','Tạm ngừng','Ngừng bán') NOT NULL DEFAULT 'Đang bán',
  ngay_tao        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ngay_cap_nhat   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_set_gia (gia),
  FOREIGN KEY (loai_set_id)   REFERENCES loai_set(id)  ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (bep_truong_id) REFERENCES nhan_vien(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE hinh_anh_set (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  set_id    INT NOT NULL,
  duong_dan VARCHAR(255) NOT NULL,
  thu_tu    TINYINT NOT NULL DEFAULT 0,
  INDEX idx_ha_set (set_id, thu_tu),
  FOREIGN KEY (set_id) REFERENCES set_omakase(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE mon_trong_set (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  set_id   INT NOT NULL,
  nhom_mon VARCHAR(50)  NOT NULL,
  noi_dung VARCHAR(500) NOT NULL,
  thu_tu   TINYINT NOT NULL DEFAULT 0,
  INDEX idx_mon_set (set_id, thu_tu),
  FOREIGN KEY (set_id) REFERENCES set_omakase(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===================== 4. BÀN, GIỎ HÀNG, ĐƠN ĐẶT BÀN =====================
CREATE TABLE ban (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  ma_ban     VARCHAR(10) NOT NULL UNIQUE,
  ten_ban    VARCHAR(50) NOT NULL,
  khu_vuc    ENUM('Quầy bar chính','Phòng riêng','Bàn ngoài trời','Lounge','VIP') NOT NULL,
  suc_chua   TINYINT UNSIGNED NOT NULL,
  trang_thai ENUM('Trống','Đang dùng','Bảo trì') NOT NULL DEFAULT 'Trống'
) ENGINE=InnoDB;

CREATE TABLE gio_hang (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  khach_hang_id INT NOT NULL,
  set_id        INT NOT NULL,
  so_luong      TINYINT UNSIGNED NOT NULL DEFAULT 1 CHECK (so_luong BETWEEN 1 AND 12),
  ngay_them     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_gio (khach_hang_id, set_id),
  FOREIGN KEY (khach_hang_id) REFERENCES khach_hang(id)  ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (set_id)        REFERENCES set_omakase(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE don_dat_ban (
  id                    INT AUTO_INCREMENT PRIMARY KEY,
  ma_don                VARCHAR(20) NOT NULL UNIQUE,
  khach_hang_id         INT NULL,                          -- NULL = khách vãng lai
  ban_id                INT NULL,
  nhan_vien_tao_id      INT NULL,
  ten_lien_he           VARCHAR(100) NOT NULL,
  sdt_lien_he           VARCHAR(15)  NOT NULL,
  email_lien_he         VARCHAR(120) NULL,
  ngay_dung             DATE NOT NULL,
  gio_dung              TIME NOT NULL,
  so_khach              TINYINT UNSIGNED NOT NULL CHECK (so_khach BETWEEN 1 AND 12),
  tam_tinh              DECIMAL(14,0) NOT NULL DEFAULT 0,  -- trigger tự tính
  giam_gia              DECIMAL(14,0) NOT NULL DEFAULT 0,
  tong_tien             DECIMAL(14,0) NOT NULL DEFAULT 0,  -- trigger tự tính
  tien_coc              DECIMAL(14,0) NOT NULL DEFAULT 0,
  phuong_thuc_tt        ENUM('Thẻ','Chuyển khoản','Tiền mặt','Ví điện tử') NOT NULL DEFAULT 'Thẻ',
  trang_thai_thanh_toan ENUM('Chưa thanh toán','Đã cọc','Đã thanh toán','Đã hoàn cọc','Miễn cọc') NOT NULL DEFAULT 'Chưa thanh toán',
  trang_thai            ENUM('Chờ xác nhận','Đã xác nhận','Hoàn tất','Đã hủy') NOT NULL DEFAULT 'Chờ xác nhận',
  ghi_chu               TEXT NULL,
  ngay_tao              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ngay_cap_nhat         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_don_ngay (ngay_dung, gio_dung),
  INDEX idx_don_tt (trang_thai),
  FOREIGN KEY (khach_hang_id)    REFERENCES khach_hang(id) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (ban_id)           REFERENCES ban(id)        ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (nhan_vien_tao_id) REFERENCES nhan_vien(id)  ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE chi_tiet_don (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  don_id     INT NOT NULL,
  set_id     INT NOT NULL,
  so_luong   TINYINT UNSIGNED NOT NULL DEFAULT 1,
  don_gia    DECIMAL(12,0) NOT NULL,                       -- giá lúc đặt
  thanh_tien DECIMAL(14,0) AS (so_luong * don_gia) STORED,
  UNIQUE KEY uq_ctd (don_id, set_id),
  FOREIGN KEY (don_id) REFERENCES don_dat_ban(id) ON DELETE CASCADE  ON UPDATE CASCADE,
  FOREIGN KEY (set_id) REFERENCES set_omakase(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================ 5. ĐÁNH GIÁ ============================
CREATE TABLE danh_gia (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  khach_hang_id     INT NOT NULL,
  set_id            INT NOT NULL,
  don_id            INT NULL,
  so_sao            TINYINT UNSIGNED NOT NULL CHECK (so_sao BETWEEN 1 AND 5),
  noi_dung          TEXT NULL,
  loai              ENUM('Đánh giá','Khiếu nại') NOT NULL DEFAULT 'Đánh giá',
  phan_hoi          TEXT NULL,
  nguoi_phan_hoi_id INT NULL,
  ngay_phan_hoi     DATETIME NULL,
  da_xu_ly          TINYINT(1) NOT NULL DEFAULT 0,
  hien_thi          TINYINT(1) NOT NULL DEFAULT 1,
  ngay_danh_gia     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (khach_hang_id)     REFERENCES khach_hang(id)  ON DELETE CASCADE  ON UPDATE CASCADE,
  FOREIGN KEY (set_id)            REFERENCES set_omakase(id) ON DELETE CASCADE  ON UPDATE CASCADE,
  FOREIGN KEY (don_id)            REFERENCES don_dat_ban(id) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (nguoi_phan_hoi_id) REFERENCES nhan_vien(id)   ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============== 6. TRIGGER: tự cập nhật tạm tính / tổng tiền đơn ==============
DELIMITER $$
CREATE TRIGGER trg_ctd_ai AFTER INSERT ON chi_tiet_don FOR EACH ROW
  UPDATE don_dat_ban d
  SET d.tam_tinh  = (SELECT COALESCE(SUM(thanh_tien),0) FROM chi_tiet_don WHERE don_id = NEW.don_id),
      d.tong_tien = GREATEST(0, d.tam_tinh - d.giam_gia)
  WHERE d.id = NEW.don_id$$

CREATE TRIGGER trg_ctd_au AFTER UPDATE ON chi_tiet_don FOR EACH ROW
  UPDATE don_dat_ban d
  SET d.tam_tinh  = (SELECT COALESCE(SUM(thanh_tien),0) FROM chi_tiet_don WHERE don_id = NEW.don_id),
      d.tong_tien = GREATEST(0, d.tam_tinh - d.giam_gia)
  WHERE d.id = NEW.don_id$$

CREATE TRIGGER trg_ctd_ad AFTER DELETE ON chi_tiet_don FOR EACH ROW
  UPDATE don_dat_ban d
  SET d.tam_tinh  = (SELECT COALESCE(SUM(thanh_tien),0) FROM chi_tiet_don WHERE don_id = OLD.don_id),
      d.tong_tien = GREATEST(0, d.tam_tinh - d.giam_gia)
  WHERE d.id = OLD.don_id$$
DELIMITER ;

-- =========================== 7. DỮ LIỆU MẪU ===========================
SET @pw = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';  -- bcrypt "password"

-- id 1-6 phục vụ, 7-12 đầu bếp, 13-15 tạp vụ
INSERT INTO nhan_vien (ma_nv, ho_ten, bo_phan, chuc_danh, chuyen_mon, email, so_dien_thoai, ngay_vao_lam) VALUES
('PV1','Nguyễn Thu Hà',  'Phục vụ','Trưởng ca phục vụ',NULL,'ha.nguyen@sabiomakase.vn','0901000001','2023-03-01'),
('PV2','Trần Bảo Ngọc',  'Phục vụ','Phục vụ bàn',NULL,'ngoc.tran@sabiomakase.vn','0901000002','2023-06-15'),
('PV3','Lê Minh Anh',    'Phục vụ','Phục vụ bàn',NULL,'anh.le@sabiomakase.vn',   '0901000003','2024-01-10'),
('PV4','Phạm Quỳnh Như', 'Phục vụ','Phục vụ bàn',NULL,'nhu.pham@sabiomakase.vn', '0901000004','2024-04-02'),
('PV5','Võ Đức Long',    'Phục vụ','Phục vụ bàn',NULL,'long.vo@sabiomakase.vn',  '0901000005','2024-08-20'),
('PV6','Đỗ Khánh Vy',    'Phục vụ','Phục vụ bàn',NULL,'vy.do@sabiomakase.vn',    '0901000006','2025-02-01'),
('DB1','Daiki Takahashi','Đầu bếp','Bếp trưởng','Master Edomae (Ginza, Tokyo)','daiki.master@sabiomakase.vn','0902000001','2018-01-01'),
('DB2','Kenji Sato',     'Đầu bếp','Bếp phó (Kaiseki)','Kaiseki Specialist (Kyoto) · Sake Sommelier','sake.sato@sabiomakase.vn','0902000002','2019-03-12'),
('DB3','Hiroshi Mori',   'Đầu bếp','Bếp phó (Sashimi)','Sashimi & Sake Sommelier','hiroshi.mori@sabiomakase.vn','0902000003','2020-05-05'),
('DB4','Nguyễn Văn Tài', 'Đầu bếp','Đầu bếp sushi',NULL,'tai.nguyen@sabiomakase.vn','0902000004','2021-07-01'),
('DB5','Trần Quốc Việt', 'Đầu bếp','Đầu bếp sushi',NULL,'viet.tran@sabiomakase.vn', '0902000005','2022-02-14'),
('DB6','Lê Hữu Phước',   'Đầu bếp','Đầu bếp nướng',NULL,'phuoc.le@sabiomakase.vn', '0902000006','2022-09-01'),
('TV1','Nguyễn Thị Lan', 'Tạp vụ', 'Vệ sinh & kho',NULL,NULL,'0903000001','2022-01-05'),
('TV2','Trần Văn Hòa',   'Tạp vụ', 'Vệ sinh & kho',NULL,NULL,'0903000002','2023-08-08'),
('TV3','Lê Thị Mai',     'Tạp vụ', 'Vệ sinh & kho',NULL,NULL,'0903000003','2024-06-18');

INSERT INTO tai_khoan_quan_tri (nhan_vien_id, ten_dang_nhap, mat_khau, quyen) VALUES
(7,'admin',@pw,'admin'), (8,'kenji',@pw,'quan_ly'), (1,'thuha',@pw,'nhan_vien');

INSERT INTO khach_hang (ma_kh, ho_ten, email, so_dien_thoai, mat_khau, hang_thanh_vien, diem_tich_luy, trang_thai, ngay_tao, ghi_chu_khau_vi) VALUES
('KH101','Trần Đình Khang',  'khang.tran@sabi-patron.com','0342345678',@pw,'VIP',       1850,'Hoạt động',   '2025-12-07','Không dùng Wasabi sống, dị ứng nhẹ hải sản có vỏ. Rượu ưa thích: Junmai Daiginjo lạnh 10°C.'),
('KH102','Nguyễn Đăng Quang','thuongkhach@omakase.vn',    '0987654321',@pw,'VIP',       1230,'Hoạt động',   '2025-12-28','Ưu tiên rượu vang đỏ nhẹ, không thêm mực nướng than. Sommelier: Kenji Sato.'),
('KH103','Hoàng Mỹ Linh',    'linh.hoang@gmail.com',      '0912345678',@pw,'Vàng',       459,'Hoạt động',   '2026-01-18',NULL),
('KH104','Lê Hoàng Kiêm',    'kiem.le@gmail.com',         '0908765430',@pw,'Vàng',       520,'Hoạt động',   '2026-02-08',NULL),
('KH105','Nguyễn Chí Kiên',  'kien.nguyen@corp.com',      '0981112220',@pw,'VIP',        990,'Hoạt động',   '2026-03-01',NULL),
('KH106','Phạm Gia Bảo',     'bao.pham@gmail.com',        '0912223330',@pw,'Thành viên', 120,'Hoạt động',   '2026-03-22',NULL),
('KH107','Vũ Thùy Linh',     'linh.vu@gmail.com',         '0975558880',@pw,'Thành viên',  80,'Hoạt động',   '2026-04-12',NULL),
('KH108','Lê Tuấn Anh',      'tuananh.le@corp.com',       '0901234567',@pw,'Thành viên',   0,'Chờ xác thực','2026-05-03',NULL),
('KH109','Đặng Minh Châu',   'chau.dang@gmail.com',       '0933445566',@pw,'Vàng',       310,'Hoạt động',   '2026-05-24',NULL),
('KH110','Bùi Quốc Huy',     'huy.bui@gmail.com',         '0944556677',@pw,'Thành viên', 150,'Hoạt động',   '2026-06-14',NULL),
('KH111','Ngô Thanh Hà',     'ha.ngo@gmail.com',          '0955667788',@pw,'Vàng',       280,'Hoạt động',   '2026-07-05',NULL),
('KH112','Trương Anh Dũng',  'dung.truong@corp.com',      '0966778899',@pw,'Thành viên',  60,'Hoạt động',   '2026-07-26',NULL);

INSERT INTO phuong_thuc_luu (khach_hang_id, loai, mo_ta, so_cuoi, han_su_dung, mac_dinh) VALUES
(1,'Thẻ','Visa / Mastercard','8829','11/27',1),
(1,'Chuyển khoản','QR Napas 247 - Vietcombank',NULL,NULL,0),
(1,'Ví điện tử','Apple Pay / Ví Sabi',NULL,NULL,0);

INSERT INTO loai_set (ten_loai, thu_tu) VALUES
('Omakase Trưa (Lunch)',1), ('Omakase Tối Cao Cấp',2), ('Kaiseki 12 Món Truyền Thống',3),
('Set Theo Mùa Đặc Biệt',4), ('Set Bò Wagyu A5 & Otoro',5);

-- set id: 1 Kiwami, 2 Miyabi, 3 Tsuki, 4 Sakura, 5 Wagyu, 6 Tenmangu, 7 Uni, 8 Akami, 9 Matsu, 10 Aki, 11 Sake
INSERT INTO set_omakase (ma_set, ten_set, ten_tieng_nhat, loai_set_id, bep_truong_id, so_mon, thoi_luong_phut, gia, nhan, mo_ta_ngan, hinh_anh, noi_bat) VALUES
('KIWAMI',  'Set Kiwami Grand Omakase','極上 おまかせ',  2,7,18,120,3800000,'CHEF''S CHOICE','Trải nghiệm đỉnh cao với Otoro ủ lạnh 14 ngày, Bào ngư Ezo hầm rượu Sake.','https://images.unsplash.com/photo-1579871494447-9811cf80d66c?q=80&w=600&auto=format&fit=crop',1),
('MIYABI',  'Set Miyabi Kaiseki',      '雅 懐石',        3,8,14,105,2800000,'SIGNATURE','Sự hài hòa thi vị giữa các món nướng than Binchotan thơm nồng và Sashimi.','https://images.unsplash.com/photo-1553621042-f6e147245754?q=80&w=600&auto=format&fit=crop',1),
('TSUKI',   'Set Tsuki Nigiri',        '月 握り',        2,7,12, 90,1950000,'TRADITIONAL','Khám phá nghệ thuật cơm giấm đỏ Akasu bí truyền đi kèm 10 tuyệt phẩm Nigiri.','https://images.unsplash.com/photo-1611143669185-af224c5e3252?q=80&w=600&auto=format&fit=crop',0),
('SAKURA',  'Set Sakura Seasonal',     '桜 季節限定',    4,9,16,120,3200000,'LIMITED SEASONAL','Hương vị đầu mùa với Mực đom đóm Toyama, Cá thu Sawara xông rơm.','https://images.unsplash.com/photo-1617196034183-421b4917c92d?q=80&w=600&auto=format&fit=crop',1),
('WAGYU',   'Set Wagyu & Otoro',       '和牛 & 大トロ',  5,7,20,135,4800000,'PRESTIGE','Sự thăng hoa giữa Bò Wagyu A5 tỉnh Miyazaki và Bụng cá ngừ vây xanh.','images/wagyu_otoro.jpg',1),
('TENMANGU','Set Tenmangu Lunch',      '天満宮 昼食',    1,9,10, 60,1500000,'LUNCH EXCLUSIVE','Phiên trải nghiệm giờ trưa với Chawanmushi trứng nhím biển, 8 món Nigiri.','https://images.unsplash.com/photo-1558985250-27a406d64cb3?q=80&w=600&auto=format&fit=crop',0),
('UNI',     'Set Uni & Caviar Special','雲丹 & キャビア',2,7,18,120,4200000,'PREMIUM','Nhum biển Hokkaido và trứng cá tầm muối thượng hạng.',NULL,0),
('AKAMI',   'Set Akami Edomae',        '赤身 江戸前',    2,7,14,100,2600000,NULL,'Các phần thịt đỏ cá ngừ ủ tương theo lối Edomae cổ điển.',NULL,0),
('MATSU',   'Set Sashimi Matsu',       '松 刺身',        2,9,12, 90,3200000,NULL,'Tuyển tập sashimi cắt tay từ hải sản Toyosu trong ngày.',NULL,0),
('AKI',     'Set Kaiseki Mùa Thu',     '秋 懐石',        4,8,16,120,4500000,'SEASONAL','Kaiseki mùa thu với nấm Matsutake và hạt dẻ Kyoto.',NULL,0),
('SAKE',    'Set Omakase Sake Pairing','酒 ペアリング',  2,8,18,150,4900000,'PAIRING','Omakase kết hợp 6 loại Sake do sommelier tuyển chọn.',NULL,0);

UPDATE set_omakase SET mo_ta_chi_tiet =
'Mỗi buổi sáng lúc 4:30 AM tại chợ cá Toyosu (Tokyo), các Nakagai làm việc trực tiếp cùng bếp trưởng Daiki Takahashi để chọn ngừ vây xanh hon-maguro loại 1, nhum biển Nemuro và cá cam Buri. Hải sản xử lý theo phương pháp Ikejime và vận chuyển hỏa tốc đến bếp Sabi trước 15:00 mỗi ngày. Gạo Koshihikari Niigata ủ giấm đỏ Akazu 3 năm; Wasabi củ tươi Shizuoka.'
WHERE ma_set = 'KIWAMI';

INSERT INTO hinh_anh_set (set_id, duong_dan, thu_tu) VALUES
(1,'https://images.unsplash.com/photo-1611143669185-af224c5e3252?q=80&w=1200&auto=format&fit=crop',1),
(1,'https://images.unsplash.com/photo-1553621042-f6e147245754?q=80&w=1200&auto=format&fit=crop',2),
(1,'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?q=80&w=1200&auto=format&fit=crop',3),
(1,'https://images.unsplash.com/photo-1544025162-811114757e79?q=80&w=1200&auto=format&fit=crop',4);

INSERT INTO mon_trong_set (set_id, nhom_mon, noi_dung, thu_tu) VALUES
(1,'KHAI VỊ',    'Gan ngỗng Ankimo ủ rượu vang ngọt, Súp nấm Matsutake thượng hạng ấm nồng.',1),
(1,'SASHIMI',    'Bào ngư Ezo hầm rượu sake 14 ngày & sốt gan, Cá Cam Buri hun khói rơm mùa đông.',2),
(1,'NIGIRI 10',  'Otoro ủ lạnh 14 ngày dát vàng, Nhum biển Murasaki Uni Hokkaido, Sò điệp Hotate áp chảo muối tuyết, Lươn biển Anago sốt tsume cổ truyền...',3),
(1,'TRÁNG MIỆNG','Trứng cuộn Tamagoyaki vị mật ong rừng, Kem Wagashi trà xanh Uji và dưa lưới Shizuoka.',4);

INSERT INTO ban (ma_ban, ten_ban, khu_vuc, suc_chua) VALUES
('B01','Quầy bar 01','Quầy bar chính',4), ('B02','Quầy bar 02','Quầy bar chính',4),
('B03','Quầy bar 03','Quầy bar chính',2), ('B04','Bàn 04 (Riêng tư)','Quầy bar chính',2),
('B05','Lounge 05','Lounge',4),           ('B06','Phòng Tokyo Room','Phòng riêng',8),
('B07','Bàn ngoài trời 07','Bàn ngoài trời',4), ('B08','Phòng VIP Kyoto','VIP',6);

INSERT INTO gio_hang (khach_hang_id, set_id, so_luong) VALUES (1,1,2),(3,6,1);

INSERT INTO don_dat_ban (ma_don, khach_hang_id, ban_id, ten_lien_he, sdt_lien_he, email_lien_he, ngay_dung, gio_dung, so_khach, tien_coc, phuong_thuc_tt, trang_thai_thanh_toan, trang_thai, ghi_chu) VALUES
('KW-8813', 1,4,'Trần Đình Khang',  '0342345678','khang.tran@sabi-patron.com','2026-10-03','18:00',2,2500000,'Thẻ','Đã cọc','Đã xác nhận','Dị ứng: không dùng tôm/hải sản có vỏ.'),
('WG-8812', 4,6,'Lê Hoàng Kiêm',    '0908765430','kiem.le@gmail.com',         '2026-10-03','20:00',6,0,'Chuyển khoản','Chưa thanh toán','Chờ xác nhận','Yêu cầu cọc 30%.'),
('KS-8811', 5,8,'Nguyễn Chí Kiên',  '0981112220','kien.nguyen@corp.com',      '2026-10-04','12:00',4,0,'Thẻ','Miễn cọc','Đã xác nhận','Khách VIP - miễn cọc.'),
('SK-8810', 6,7,'Phạm Gia Bảo',     '0912223330','bao.pham@gmail.com',        '2026-10-04','18:00',2,0,'Chuyển khoản','Đã hoàn cọc','Đã hủy',NULL),
('NG-8809', 7,1,'Vũ Thùy Linh',     '0975558880','linh.vu@gmail.com',         '2026-10-02','13:00',1,0,'Tiền mặt','Đã thanh toán','Hoàn tất',NULL),
('KS-8820', 1,6,'Trần Đình Khang',  '0342345678','khang.tran@sabi-patron.com','2026-10-12','19:00',4,0,'Thẻ','Chưa thanh toán','Chờ xác nhận','Phòng riêng.'),
('SK-8790', 1,7,'Trần Đình Khang',  '0342345678','khang.tran@sabi-patron.com','2026-09-28','12:00',2,0,'Thẻ','Đã thanh toán','Hoàn tất',NULL),
('NG-8701', 1,1,'Trần Đình Khang',  '0342345678','khang.tran@sabi-patron.com','2026-09-02','13:00',1,0,'Thẻ','Đã hoàn cọc','Đã hủy',NULL),
('SB-1001', 2,6,'Nguyễn Đăng Quang','0987654321','thuongkhach@omakase.vn',    '2026-09-21','20:00',6,0,'Thẻ','Đã thanh toán','Hoàn tất','Sommelier phụ trách: Kenji Sato.'),
('SB-1002', 3,2,'Hoàng Mỹ Linh',    '0912345678','linh.hoang@gmail.com',      '2026-09-22','12:00',2,0,'Ví điện tử','Đã thanh toán','Hoàn tất',NULL),
('SB-1003', 9,3,'Đặng Minh Châu',   '0933445566','chau.dang@gmail.com',       '2026-09-23','19:00',2,0,'Chuyển khoản','Đã thanh toán','Hoàn tất',NULL),
('SB-1004',10,5,'Bùi Quốc Huy',     '0944556677','huy.bui@gmail.com',         '2026-09-24','18:00',3,0,'Tiền mặt','Đã thanh toán','Hoàn tất',NULL),
('SB-1005',11,2,'Ngô Thanh Hà',     '0955667788','ha.ngo@gmail.com',          '2026-09-25','19:00',2,0,'Thẻ','Đã thanh toán','Hoàn tất','Sinh nhật - chuẩn bị bánh.'),
('SB-1006',12,1,'Trương Anh Dũng',  '0966778899','dung.truong@corp.com',      '2026-09-26','20:00',4,0,'Thẻ','Đã thanh toán','Hoàn tất',NULL),
('SB-1007', 5,8,'Nguyễn Chí Kiên',  '0981112220','kien.nguyen@corp.com',      '2026-09-27','19:00',5,0,'Chuyển khoản','Đã thanh toán','Hoàn tất',NULL),
('SB-1008', 2,4,'Nguyễn Đăng Quang','0987654321','thuongkhach@omakase.vn',    '2026-09-30','18:00',2,0,'Thẻ','Đã thanh toán','Hoàn tất',NULL);

INSERT INTO chi_tiet_don (don_id, set_id, so_luong, don_gia) VALUES
( 1,1,2,3800000), ( 2,5,6,4800000), ( 3,2,4,2800000), ( 4,4,2,3200000),
( 5,3,1,1950000), ( 6,2,4,2800000), ( 7,4,2,3200000), ( 8,3,1,1950000),
( 9,11,6,4900000),(10,6,2,1500000), (11,8,2,2600000), (12,9,3,3200000),
(13,1,2,3800000), (14,5,4,4800000), (15,10,5,4500000),(16,7,2,4200000);

-- nguoi_phan_hoi_id 7 = Daiki
INSERT INTO danh_gia (khach_hang_id, set_id, don_id, so_sao, noi_dung, loai, phan_hoi, nguoi_phan_hoi_id, ngay_phan_hoi, da_xu_ly, ngay_danh_gia) VALUES
( 1, 4,   7,5,'Nguyên liệu tươi, bếp trưởng giải thích từng món rất tận tình.','Đánh giá','Cảm ơn quý khách đã chia sẻ, Sabi luôn mong được đón tiếp quý khách lần tới.',7,'2026-10-02 10:00',0,'2026-10-02 09:00'),
( 2,11,   9,5,'Otoro tan ngay khi vào miệng. Không gian yên tĩnh, sẽ quay lại.','Đánh giá',NULL,NULL,NULL,0,'2026-10-01 21:30'),
( 3, 6,  10,4,'Món ngon nhưng phục vụ hơi chậm ở lượt thứ hai.','Đánh giá',NULL,NULL,NULL,0,'2026-09-30 14:00'),
( 9, 8,  11,5,'Rượu sake ghép món rất khéo. Xứng đáng với giá niêm yết.','Đánh giá','Cảm ơn quý khách đã chia sẻ, Sabi luôn mong được đón tiếp quý khách lần tới.',7,'2026-09-29 11:00',0,'2026-09-29 08:00'),
(10, 9,  12,3,'Phòng bên cạnh hơi ồn, ảnh hưởng đến trải nghiệm quầy bar.','Khiếu nại',NULL,NULL,NULL,0,'2026-09-28 22:00'),
(12, 5,  14,2,'Đặt bàn 19:00 nhưng phải chờ hơn 20 phút mới được vào chỗ.','Khiếu nại',NULL,NULL,NULL,0,'2026-09-27 23:00'),
( 3, 6,NULL,4,'Set trưa gọn nhẹ, hợp giờ nghỉ. Mong có thêm lựa chọn tráng miệng.','Đánh giá','Cảm ơn quý khách đã chia sẻ, Sabi luôn mong được đón tiếp quý khách lần tới.',7,'2026-09-27 09:00',0,'2026-09-26 13:00'),
(11, 1,  13,5,'Sinh nhật của mình được nhà hàng chuẩn bị bất ngờ rất chu đáo.','Đánh giá',NULL,NULL,NULL,0,'2026-09-25 22:30'),
( 7, 3,   5,1,'Có dị ứng hải sản có vỏ đã ghi chú trước nhưng vẫn có tôm trong món khai vị.','Khiếu nại',NULL,NULL,NULL,0,'2026-10-02 15:00'),
( 5,10,  15,5,'Wagyu A5 nướng than hoàn hảo, Chef Daiki rất chuyên nghiệp.','Đánh giá','Cảm ơn quý khách đã chia sẻ, Sabi luôn mong được đón tiếp quý khách lần tới.',7,'2026-09-28 10:00',0,'2026-09-27 22:00');

-- ============================== 8. VIEW ==============================
CREATE OR REPLACE VIEW v_set_danh_gia AS
SELECT s.id, s.ma_set, s.ten_set, s.gia,
       ROUND(COALESCE(AVG(d.so_sao),0),1) AS diem_tb, COUNT(d.id) AS so_danh_gia
FROM set_omakase s LEFT JOIN danh_gia d ON d.set_id = s.id AND d.hien_thi = 1
GROUP BY s.id, s.ma_set, s.ten_set, s.gia;

CREATE OR REPLACE VIEW v_doanh_thu_ngay AS
SELECT ngay_dung AS ngay, COUNT(*) AS so_don, SUM(so_khach) AS so_khach, SUM(tong_tien) AS doanh_thu
FROM don_dat_ban WHERE trang_thai = 'Hoàn tất'
GROUP BY ngay_dung;
