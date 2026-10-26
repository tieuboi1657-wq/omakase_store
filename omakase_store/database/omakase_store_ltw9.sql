-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: omakase_store_ltw9
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `omakase_store_ltw9`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `omakase_store_ltw9` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `omakase_store_ltw9`;

--
-- Table structure for table `ban`
--

DROP TABLE IF EXISTS `ban`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ban` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_ban` varchar(10) NOT NULL,
  `ten_ban` varchar(50) NOT NULL,
  `khu_vuc` enum('Quầy bar chính','Phòng riêng','Bàn ngoài trời','Lounge','VIP') NOT NULL,
  `suc_chua` tinyint(3) unsigned NOT NULL,
  `trang_thai` enum('Trống','Đang dùng','Bảo trì') NOT NULL DEFAULT 'Trống',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_ban` (`ma_ban`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ban`
--

LOCK TABLES `ban` WRITE;
/*!40000 ALTER TABLE `ban` DISABLE KEYS */;
INSERT INTO `ban` VALUES (1,'B01','Quầy bar 01','Quầy bar chính',4,'Trống'),(2,'B02','Quầy bar 02','Quầy bar chính',4,'Trống'),(3,'B03','Quầy bar 03','Quầy bar chính',2,'Trống'),(4,'B04','Bàn 04 (Riêng tư)','Quầy bar chính',2,'Trống'),(5,'B05','Lounge 05','Lounge',4,'Trống'),(6,'B06','Phòng Tokyo Room','Phòng riêng',8,'Trống'),(7,'B07','Bàn ngoài trời 07','Bàn ngoài trời',4,'Trống'),(8,'B08','Phòng VIP Kyoto','VIP',6,'Trống');
/*!40000 ALTER TABLE `ban` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chi_tiet_don`
--

DROP TABLE IF EXISTS `chi_tiet_don`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chi_tiet_don` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `don_id` int(11) NOT NULL,
  `set_id` int(11) NOT NULL,
  `so_luong` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `don_gia` decimal(12,0) NOT NULL,
  `thanh_tien` decimal(14,0) GENERATED ALWAYS AS (`so_luong` * `don_gia`) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ctd` (`don_id`,`set_id`),
  KEY `set_id` (`set_id`),
  CONSTRAINT `chi_tiet_don_ibfk_1` FOREIGN KEY (`don_id`) REFERENCES `don_dat_ban` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chi_tiet_don_ibfk_2` FOREIGN KEY (`set_id`) REFERENCES `set_omakase` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chi_tiet_don`
--

LOCK TABLES `chi_tiet_don` WRITE;
/*!40000 ALTER TABLE `chi_tiet_don` DISABLE KEYS */;
INSERT INTO `chi_tiet_don` VALUES (1,1,1,2,3800000,7600000),(2,2,5,6,4800000,28800000),(3,3,2,4,2800000,11200000),(4,4,4,2,3200000,6400000),(5,5,3,1,1950000,1950000),(6,6,2,4,2800000,11200000),(7,7,4,2,3200000,6400000),(8,8,3,1,1950000,1950000),(9,9,11,6,4900000,29400000),(10,10,6,2,1500000,3000000),(11,11,8,2,2600000,5200000),(12,12,9,3,3200000,9600000),(13,13,1,2,3800000,7600000),(14,14,5,4,4800000,19200000),(15,15,10,5,4500000,22500000),(16,16,7,2,4200000,8400000),(17,17,1,2,3800000,7600000),(18,18,1,2,480000,960000),(19,19,1,2,320000,640000);
/*!40000 ALTER TABLE `chi_tiet_don` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Triggers for table `chi_tiet_don`
--

DROP TRIGGER IF EXISTS `trg_ctd_ai`;
CREATE TRIGGER `trg_ctd_ai` AFTER INSERT ON `chi_tiet_don` FOR EACH ROW
  UPDATE don_dat_ban d
  SET d.tam_tinh  = (SELECT COALESCE(SUM(thanh_tien), 0) FROM chi_tiet_don WHERE don_id = NEW.don_id),
      d.tong_tien = GREATEST(0, d.tam_tinh - d.giam_gia)
  WHERE d.id = NEW.don_id;

DROP TRIGGER IF EXISTS `trg_ctd_au`;
CREATE TRIGGER `trg_ctd_au` AFTER UPDATE ON `chi_tiet_don` FOR EACH ROW
  UPDATE don_dat_ban d
  SET d.tam_tinh  = (SELECT COALESCE(SUM(thanh_tien), 0) FROM chi_tiet_don WHERE don_id = NEW.don_id),
      d.tong_tien = GREATEST(0, d.tam_tinh - d.giam_gia)
  WHERE d.id = NEW.don_id;

DROP TRIGGER IF EXISTS `trg_ctd_ad`;
CREATE TRIGGER `trg_ctd_ad` AFTER DELETE ON `chi_tiet_don` FOR EACH ROW
  UPDATE don_dat_ban d
  SET d.tam_tinh  = (SELECT COALESCE(SUM(thanh_tien), 0) FROM chi_tiet_don WHERE don_id = OLD.don_id),
      d.tong_tien = GREATEST(0, d.tam_tinh - d.giam_gia)
  WHERE d.id = OLD.don_id;

--
-- Table structure for table `danh_gia`
--

DROP TABLE IF EXISTS `danh_gia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `danh_gia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `khach_hang_id` int(11) NOT NULL,
  `set_id` int(11) NOT NULL,
  `don_id` int(11) DEFAULT NULL,
  `so_sao` tinyint(3) unsigned NOT NULL CHECK (`so_sao` between 1 and 5),
  `noi_dung` text DEFAULT NULL,
  `loai` enum('Đánh giá','Khiếu nại') NOT NULL DEFAULT 'Đánh giá',
  `phan_hoi` text DEFAULT NULL,
  `nguoi_phan_hoi_id` int(11) DEFAULT NULL,
  `ngay_phan_hoi` datetime DEFAULT NULL,
  `da_xu_ly` tinyint(1) NOT NULL DEFAULT 0,
  `hien_thi` tinyint(1) NOT NULL DEFAULT 1,
  `ngay_danh_gia` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `khach_hang_id` (`khach_hang_id`),
  KEY `set_id` (`set_id`),
  KEY `don_id` (`don_id`),
  KEY `nguoi_phan_hoi_id` (`nguoi_phan_hoi_id`),
  CONSTRAINT `danh_gia_ibfk_1` FOREIGN KEY (`khach_hang_id`) REFERENCES `khach_hang` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `danh_gia_ibfk_2` FOREIGN KEY (`set_id`) REFERENCES `set_omakase` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `danh_gia_ibfk_3` FOREIGN KEY (`don_id`) REFERENCES `don_dat_ban` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `danh_gia_ibfk_4` FOREIGN KEY (`nguoi_phan_hoi_id`) REFERENCES `nhan_vien` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `danh_gia`
--

LOCK TABLES `danh_gia` WRITE;
/*!40000 ALTER TABLE `danh_gia` DISABLE KEYS */;
INSERT INTO `danh_gia` VALUES (1,1,4,7,5,'Nguyên liệu tươi, bếp trưởng giải thích từng món rất tận tình.','Đánh giá','Cảm ơn quý khách đã chia sẻ, Sabi luôn mong được đón tiếp quý khách lần tới.',7,'2026-10-02 10:00:00',0,1,'2026-10-02 09:00:00'),(2,2,11,9,5,'Otoro tan ngay khi vào miệng. Không gian yên tĩnh, sẽ quay lại.','Đánh giá',NULL,NULL,NULL,0,1,'2026-10-01 21:30:00'),(3,3,6,10,4,'Món ngon nhưng phục vụ hơi chậm ở lượt thứ hai.','Đánh giá',NULL,NULL,NULL,0,1,'2026-09-30 14:00:00'),(4,9,8,11,5,'Rượu sake ghép món rất khéo. Xứng đáng với giá niêm yết.','Đánh giá','Cảm ơn quý khách đã chia sẻ, Sabi luôn mong được đón tiếp quý khách lần tới.',7,'2026-09-29 11:00:00',0,1,'2026-09-29 08:00:00'),(5,10,9,12,3,'Phòng bên cạnh hơi ồn, ảnh hưởng đến trải nghiệm quầy bar.','Khiếu nại',NULL,NULL,NULL,0,1,'2026-09-28 22:00:00'),(6,12,5,14,2,'Đặt bàn 19:00 nhưng phải chờ hơn 20 phút mới được vào chỗ.','Khiếu nại',NULL,NULL,NULL,0,1,'2026-09-27 23:00:00'),(7,3,6,NULL,4,'Set trưa gọn nhẹ, hợp giờ nghỉ. Mong có thêm lựa chọn tráng miệng.','Đánh giá','Cảm ơn quý khách đã chia sẻ, Sabi luôn mong được đón tiếp quý khách lần tới.',7,'2026-09-27 09:00:00',0,1,'2026-09-26 13:00:00'),(8,11,1,13,5,'Sinh nhật của mình được nhà hàng chuẩn bị bất ngờ rất chu đáo.','Đánh giá',NULL,NULL,NULL,0,1,'2026-09-25 22:30:00'),(9,7,3,5,1,'Có dị ứng hải sản có vỏ đã ghi chú trước nhưng vẫn có tôm trong món khai vị.','Khiếu nại',NULL,NULL,NULL,0,1,'2026-10-02 15:00:00'),(10,5,10,15,5,'Wagyu A5 nướng than hoàn hảo, Chef Daiki rất chuyên nghiệp.','Đánh giá','Cảm ơn quý khách đã chia sẻ, Sabi luôn mong được đón tiếp quý khách lần tới.',7,'2026-09-28 10:00:00',0,1,'2026-09-27 22:00:00');
/*!40000 ALTER TABLE `danh_gia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `don_dat_ban`
--

DROP TABLE IF EXISTS `don_dat_ban`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `don_dat_ban` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_don` varchar(20) NOT NULL,
  `khach_hang_id` int(11) DEFAULT NULL,
  `ban_id` int(11) DEFAULT NULL,
  `nhan_vien_tao_id` int(11) DEFAULT NULL,
  `ten_lien_he` varchar(100) NOT NULL,
  `sdt_lien_he` varchar(15) NOT NULL,
  `email_lien_he` varchar(120) DEFAULT NULL,
  `ngay_dung` date NOT NULL,
  `gio_dung` time NOT NULL,
  `so_khach` tinyint(3) unsigned NOT NULL CHECK (`so_khach` between 1 and 12),
  `tam_tinh` decimal(14,0) NOT NULL DEFAULT 0,
  `giam_gia` decimal(14,0) NOT NULL DEFAULT 0,
  `tong_tien` decimal(14,0) NOT NULL DEFAULT 0,
  `tien_coc` decimal(14,0) NOT NULL DEFAULT 0,
  `phuong_thuc_tt` enum('Thẻ','Chuyển khoản','Tiền mặt','Ví điện tử') NOT NULL DEFAULT 'Thẻ',
  `trang_thai_thanh_toan` enum('Chưa thanh toán','Đã cọc','Đã thanh toán','Đã hoàn cọc','Miễn cọc') NOT NULL DEFAULT 'Chưa thanh toán',
  `trang_thai` enum('Chờ xác nhận','Đã xác nhận','Hoàn tất','Đã hủy') NOT NULL DEFAULT 'Chờ xác nhận',
  `ghi_chu` text DEFAULT NULL,
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_don` (`ma_don`),
  KEY `idx_don_ngay` (`ngay_dung`,`gio_dung`),
  KEY `idx_don_tt` (`trang_thai`),
  KEY `khach_hang_id` (`khach_hang_id`),
  KEY `ban_id` (`ban_id`),
  KEY `nhan_vien_tao_id` (`nhan_vien_tao_id`),
  CONSTRAINT `don_dat_ban_ibfk_1` FOREIGN KEY (`khach_hang_id`) REFERENCES `khach_hang` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `don_dat_ban_ibfk_2` FOREIGN KEY (`ban_id`) REFERENCES `ban` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `don_dat_ban_ibfk_3` FOREIGN KEY (`nhan_vien_tao_id`) REFERENCES `nhan_vien` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `don_dat_ban`
--

LOCK TABLES `don_dat_ban` WRITE;
/*!40000 ALTER TABLE `don_dat_ban` DISABLE KEYS */;
INSERT INTO `don_dat_ban` VALUES (1,'KW-8813',1,4,NULL,'Trần Đình Khang','0342345678','khang.tran@sabi-patron.com','2026-10-03','18:00:00',2,7600000,0,7600000,2500000,'Thẻ','Đã cọc','Đã xác nhận','Dị ứng: không dùng tôm/hải sản có vỏ.','2026-10-05 15:05:54','2026-10-05 15:05:54'),(2,'WG-8812',4,6,NULL,'Lê Hoàng Kiêm','0908765430','kiem.le@gmail.com','2026-10-03','20:00:00',6,28800000,0,28800000,0,'Chuyển khoản','Chưa thanh toán','Chờ xác nhận','Yêu cầu cọc 30%.','2026-10-05 15:05:54','2026-10-05 15:05:54'),(3,'KS-8811',5,8,NULL,'Nguyễn Chí Kiên','0981112220','kien.nguyen@corp.com','2026-10-04','12:00:00',4,11200000,0,11200000,0,'Thẻ','Miễn cọc','Đã xác nhận','Khách VIP - miễn cọc.','2026-10-05 15:05:54','2026-10-05 15:05:54'),(4,'SK-8810',6,7,NULL,'Phạm Gia Bảo','0912223330','bao.pham@gmail.com','2026-10-04','18:00:00',2,6400000,0,6400000,0,'Chuyển khoản','Đã hoàn cọc','Đã hủy',NULL,'2026-10-05 15:05:54','2026-10-05 15:05:54'),(5,'NG-8809',7,1,NULL,'Vũ Thùy Linh','0975558880','linh.vu@gmail.com','2026-10-02','13:00:00',1,1950000,0,1950000,0,'Tiền mặt','Đã thanh toán','Hoàn tất',NULL,'2026-10-05 15:05:54','2026-10-05 15:05:54'),(6,'KS-8820',1,6,NULL,'Trần Đình Khang','0342345678','khang.tran@sabi-patron.com','2026-10-12','19:00:00',4,11200000,0,11200000,0,'Thẻ','Chưa thanh toán','Chờ xác nhận','Phòng riêng.','2026-10-05 15:05:54','2026-10-05 15:05:54'),(7,'SK-8790',1,7,NULL,'Trần Đình Khang','0342345678','khang.tran@sabi-patron.com','2026-09-28','12:00:00',2,6400000,0,6400000,0,'Thẻ','Đã thanh toán','Hoàn tất',NULL,'2026-10-05 15:05:54','2026-10-05 15:05:54'),(8,'NG-8701',1,1,NULL,'Trần Đình Khang','0342345678','khang.tran@sabi-patron.com','2026-09-02','13:00:00',1,1950000,0,1950000,0,'Thẻ','Đã hoàn cọc','Đã hủy',NULL,'2026-10-05 15:05:54','2026-10-05 15:05:54'),(9,'SB-1001',2,6,NULL,'Nguyễn Đăng Quang','0987654321','thuongkhach@omakase.vn','2026-09-21','20:00:00',6,29400000,0,29400000,0,'Thẻ','Đã thanh toán','Hoàn tất','Sommelier phụ trách: Kenji Sato.','2026-10-05 15:05:54','2026-10-05 15:05:54'),(10,'SB-1002',3,2,NULL,'Hoàng Mỹ Linh','0912345678','linh.hoang@gmail.com','2026-09-22','12:00:00',2,3000000,0,3000000,0,'Ví điện tử','Đã thanh toán','Hoàn tất',NULL,'2026-10-05 15:05:54','2026-10-05 15:05:54'),(11,'SB-1003',9,3,NULL,'Đặng Minh Châu','0933445566','chau.dang@gmail.com','2026-09-23','19:00:00',2,5200000,0,5200000,0,'Chuyển khoản','Đã thanh toán','Hoàn tất',NULL,'2026-10-05 15:05:54','2026-10-05 15:05:54'),(12,'SB-1004',10,5,NULL,'Bùi Quốc Huy','0944556677','huy.bui@gmail.com','2026-09-24','18:00:00',3,9600000,0,9600000,0,'Tiền mặt','Đã thanh toán','Hoàn tất',NULL,'2026-10-05 15:05:54','2026-10-05 15:05:54'),(13,'SB-1005',11,2,NULL,'Ngô Thanh Hà','0955667788','ha.ngo@gmail.com','2026-09-25','19:00:00',2,7600000,0,7600000,0,'Thẻ','Đã thanh toán','Hoàn tất','Sinh nhật - chuẩn bị bánh.','2026-10-05 15:05:54','2026-10-05 15:05:54'),(14,'SB-1006',12,1,NULL,'Trương Anh Dũng','0966778899','dung.truong@corp.com','2026-09-26','20:00:00',4,19200000,0,19200000,0,'Thẻ','Đã thanh toán','Hoàn tất',NULL,'2026-10-05 15:05:54','2026-10-05 15:05:54'),(15,'SB-1007',5,8,NULL,'Nguyễn Chí Kiên','0981112220','kien.nguyen@corp.com','2026-09-27','19:00:00',5,22500000,0,22500000,0,'Chuyển khoản','Đã thanh toán','Hoàn tất',NULL,'2026-10-05 15:05:54','2026-10-05 15:05:54'),(16,'SB-1008',2,4,NULL,'Nguyễn Đăng Quang','0987654321','thuongkhach@omakase.vn','2026-09-30','18:00:00',2,8400000,0,8400000,0,'Thẻ','Đã thanh toán','Hoàn tất',NULL,'2026-10-05 15:05:54','2026-10-05 15:05:54'),(17,'SB-5171',13,NULL,NULL,'Mai Hoàng Vy','0398335349','vymai0806@gmail.com','2026-10-22','18:00:00',2,7600000,0,7600000,0,'Thẻ','Chưa thanh toán','Chờ xác nhận','','2026-10-22 20:14:28','2026-10-22 20:14:28'),(18,'BK261022442',NULL,NULL,NULL,'Nguy?n Van Test','0987654321','test@example.com','2026-10-25','19:00:00',2,960000,0,960000,0,'Tiền mặt','Chưa thanh toán','Chờ xác nhận','Loại dịch vụ: Sushi Omakase - Ghi chú: Test booking via API','2026-10-22 21:16:35','2026-10-22 21:16:35'),(19,'DON261022232',NULL,NULL,NULL,'Trần Văn Test','0912345678','Trần Văn Test@example.com','2026-10-22','21:19:26',2,640000,0,640000,0,'Tiền mặt','Chưa thanh toán','Đã xác nhận','Giao hàng / Đặt bàn. Địa chỉ: 123 Lê Lợi, Q1','2026-10-22 21:19:26','2026-10-22 21:19:26');
/*!40000 ALTER TABLE `don_dat_ban` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gio_hang`
--

DROP TABLE IF EXISTS `gio_hang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gio_hang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `khach_hang_id` int(11) NOT NULL,
  `set_id` int(11) NOT NULL,
  `so_luong` tinyint(3) unsigned NOT NULL DEFAULT 1 CHECK (`so_luong` between 1 and 12),
  `ngay_them` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gio` (`khach_hang_id`,`set_id`),
  KEY `set_id` (`set_id`),
  CONSTRAINT `gio_hang_ibfk_1` FOREIGN KEY (`khach_hang_id`) REFERENCES `khach_hang` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `gio_hang_ibfk_2` FOREIGN KEY (`set_id`) REFERENCES `set_omakase` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gio_hang`
--

LOCK TABLES `gio_hang` WRITE;
/*!40000 ALTER TABLE `gio_hang` DISABLE KEYS */;
INSERT INTO `gio_hang` VALUES (1,1,1,2,'2026-10-05 15:05:53'),(2,3,6,1,'2026-10-05 15:05:53');
/*!40000 ALTER TABLE `gio_hang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hinh_anh_set`
--

DROP TABLE IF EXISTS `hinh_anh_set`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hinh_anh_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `set_id` int(11) NOT NULL,
  `duong_dan` varchar(255) NOT NULL,
  `thu_tu` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_ha_set` (`set_id`,`thu_tu`),
  CONSTRAINT `hinh_anh_set_ibfk_1` FOREIGN KEY (`set_id`) REFERENCES `set_omakase` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hinh_anh_set`
--

LOCK TABLES `hinh_anh_set` WRITE;
/*!40000 ALTER TABLE `hinh_anh_set` DISABLE KEYS */;
INSERT INTO `hinh_anh_set` VALUES (1,1,'https://images.unsplash.com/photo-1611143669185-af224c5e3252?q=80&w=1200&auto=format&fit=crop',1),(2,1,'https://images.unsplash.com/photo-1553621042-f6e147245754?q=80&w=1200&auto=format&fit=crop',2),(3,1,'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?q=80&w=1200&auto=format&fit=crop',3),(4,1,'https://images.unsplash.com/photo-1544025162-811114757e79?q=80&w=1200&auto=format&fit=crop',4);
/*!40000 ALTER TABLE `hinh_anh_set` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `khach_hang`
--

DROP TABLE IF EXISTS `khach_hang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `khach_hang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_kh` varchar(10) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `so_dien_thoai` varchar(15) NOT NULL,
  `mat_khau` varchar(255) NOT NULL,
  `dia_chi` varchar(255) DEFAULT NULL,
  `ngay_sinh` date DEFAULT NULL,
  `hang_thanh_vien` enum('Thành viên','Vàng','VIP') NOT NULL DEFAULT 'Thành viên',
  `diem_tich_luy` int(10) unsigned NOT NULL DEFAULT 0,
  `ghi_chu_khau_vi` text DEFAULT NULL,
  `trang_thai` enum('Hoạt động','Chờ xác thực','Đã khóa') NOT NULL DEFAULT 'Hoạt động',
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_kh` (`ma_kh`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `so_dien_thoai` (`so_dien_thoai`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `khach_hang`
--

LOCK TABLES `khach_hang` WRITE;
/*!40000 ALTER TABLE `khach_hang` DISABLE KEYS */;
INSERT INTO `khach_hang` VALUES (1,'KH101','Trần Đình Khang','khang.tran@sabi-patron.com','0342345678','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'VIP',1850,'Không dùng Wasabi sống, dị ứng nhẹ hải sản có vỏ. Rượu ưa thích: Junmai Daiginjo lạnh 10°C.','Hoạt động','2025-12-07 00:00:00','2026-10-05 15:05:53'),(2,'KH102','Nguyễn Đăng Quang','thuongkhach@omakase.vn','0987654321','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'VIP',1230,'Ưu tiên rượu vang đỏ nhẹ, không thêm mực nướng than. Sommelier: Kenji Sato.','Hoạt động','2025-12-28 00:00:00','2026-10-05 15:05:53'),(3,'KH103','Hoàng Mỹ Linh','linh.hoang@gmail.com','0912345678','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'Vàng',459,NULL,'Hoạt động','2026-01-18 00:00:00','2026-10-05 15:05:53'),(4,'KH104','Lê Hoàng Kiêm','kiem.le@gmail.com','0908765430','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'Vàng',520,NULL,'Hoạt động','2026-02-08 00:00:00','2026-10-05 15:05:53'),(5,'KH105','Nguyễn Chí Kiên','kien.nguyen@corp.com','0981112220','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'VIP',990,NULL,'Hoạt động','2026-03-01 00:00:00','2026-10-05 15:05:53'),(6,'KH106','Phạm Gia Bảo','bao.pham@gmail.com','0912223330','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'Thành viên',120,NULL,'Hoạt động','2026-03-22 00:00:00','2026-10-05 15:05:53'),(7,'KH107','Vũ Thùy Linh','linh.vu@gmail.com','0975558880','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'Thành viên',80,NULL,'Hoạt động','2026-04-12 00:00:00','2026-10-05 15:05:53'),(8,'KH108','Lê Tuấn Anh','tuananh.le@corp.com','0901234567','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'Thành viên',0,NULL,'Chờ xác thực','2026-05-03 00:00:00','2026-10-05 15:05:53'),(9,'KH109','Đặng Minh Châu','chau.dang@gmail.com','0933445566','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'Vàng',310,NULL,'Hoạt động','2026-05-24 00:00:00','2026-10-05 15:05:53'),(10,'KH110','Bùi Quốc Huy','huy.bui@gmail.com','0944556677','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'Thành viên',150,NULL,'Hoạt động','2026-06-14 00:00:00','2026-10-05 15:05:53'),(11,'KH111','Ngô Thanh Hà','ha.ngo@gmail.com','0955667788','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'Vàng',280,NULL,'Hoạt động','2026-07-05 00:00:00','2026-10-05 15:05:53'),(12,'KH112','Trương Anh Dũng','dung.truong@corp.com','0966778899','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'Thành viên',60,NULL,'Hoạt động','2026-07-26 00:00:00','2026-10-05 15:05:53'),(13,'KH915','Mai Hoàng Vy','vymai0806@gmail.com','0398335349','$2y$10$iOLVhjF29vdoRN/05rB/aOGUfrxOMdqv017qR7bvaLK/NcB0JxRla','Quận 1, Thành phố Hồ Chí Minh',NULL,'Thành viên',60,NULL,'Hoạt động','2026-10-22 20:10:57','2026-10-22 20:14:28');
/*!40000 ALTER TABLE `khach_hang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loai_set`
--

DROP TABLE IF EXISTS `loai_set`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loai_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ten_loai` varchar(100) NOT NULL,
  `mo_ta` varchar(255) DEFAULT NULL,
  `thu_tu` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ten_loai` (`ten_loai`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loai_set`
--

LOCK TABLES `loai_set` WRITE;
/*!40000 ALTER TABLE `loai_set` DISABLE KEYS */;
INSERT INTO `loai_set` VALUES (1,'Omakase Trưa (Lunch)',NULL,1),(2,'Omakase Tối Cao Cấp',NULL,2),(3,'Kaiseki 12 Món Truyền Thống',NULL,3),(4,'Set Theo Mùa Đặc Biệt',NULL,4),(5,'Set Bò Wagyu A5 & Otoro',NULL,5);
/*!40000 ALTER TABLE `loai_set` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mon_trong_set`
--

DROP TABLE IF EXISTS `mon_trong_set`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mon_trong_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `set_id` int(11) NOT NULL,
  `nhom_mon` varchar(50) NOT NULL,
  `noi_dung` varchar(500) NOT NULL,
  `thu_tu` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_mon_set` (`set_id`,`thu_tu`),
  CONSTRAINT `mon_trong_set_ibfk_1` FOREIGN KEY (`set_id`) REFERENCES `set_omakase` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mon_trong_set`
--

LOCK TABLES `mon_trong_set` WRITE;
/*!40000 ALTER TABLE `mon_trong_set` DISABLE KEYS */;
INSERT INTO `mon_trong_set` VALUES (1,1,'KHAI VỊ','Gan ngỗng Ankimo ủ rượu vang ngọt, Súp nấm Matsutake thượng hạng ấm nồng.',1),(2,1,'SASHIMI','Bào ngư Ezo hầm rượu sake 14 ngày & sốt gan, Cá Cam Buri hun khói rơm mùa đông.',2),(3,1,'NIGIRI 10','Otoro ủ lạnh 14 ngày dát vàng, Nhum biển Murasaki Uni Hokkaido, Sò điệp Hotate áp chảo muối tuyết, Lươn biển Anago sốt tsume cổ truyền...',3),(4,1,'TRÁNG MIỆNG','Trứng cuộn Tamagoyaki vị mật ong rừng, Kem Wagashi trà xanh Uji và dưa lưới Shizuoka.',4);
/*!40000 ALTER TABLE `mon_trong_set` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nhan_vien`
--

DROP TABLE IF EXISTS `nhan_vien`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nhan_vien` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_nv` varchar(10) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `so_dien_thoai` varchar(15) NOT NULL,
  `bo_phan` enum('Quản lý','Phục vụ','Đầu bếp','Tạp vụ') NOT NULL,
  `chuc_danh` varchar(100) NOT NULL,
  `chuyen_mon` varchar(150) DEFAULT NULL,
  `ngay_vao_lam` date DEFAULT NULL,
  `trang_thai` enum('Hoạt động','Tạm nghỉ','Đã nghỉ') NOT NULL DEFAULT 'Hoạt động',
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_nv` (`ma_nv`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nhan_vien`
--

LOCK TABLES `nhan_vien` WRITE;
/*!40000 ALTER TABLE `nhan_vien` DISABLE KEYS */;
INSERT INTO `nhan_vien` VALUES (1,'PV1','Nguyễn Thu Hà','ha.nguyen@sabiomakase.vn','0901000001','Phục vụ','Trưởng ca phục vụ',NULL,'2023-03-01','Hoạt động','2026-10-05 15:05:53'),(2,'PV2','Trần Bảo Ngọc','ngoc.tran@sabiomakase.vn','0901000002','Phục vụ','Phục vụ bàn',NULL,'2023-06-15','Hoạt động','2026-10-05 15:05:53'),(3,'PV3','Lê Minh Anh','anh.le@sabiomakase.vn','0901000003','Phục vụ','Phục vụ bàn',NULL,'2024-01-10','Hoạt động','2026-10-05 15:05:53'),(4,'PV4','Phạm Quỳnh Như','nhu.pham@sabiomakase.vn','0901000004','Phục vụ','Phục vụ bàn',NULL,'2024-04-02','Hoạt động','2026-10-05 15:05:53'),(5,'PV5','Võ Đức Long','long.vo@sabiomakase.vn','0901000005','Phục vụ','Phục vụ bàn',NULL,'2024-08-20','Hoạt động','2026-10-05 15:05:53'),(6,'PV6','Đỗ Khánh Vy','vy.do@sabiomakase.vn','0901000006','Phục vụ','Phục vụ bàn',NULL,'2025-02-01','Hoạt động','2026-10-05 15:05:53'),(7,'DB1','Daiki Takahashi','daiki.master@sabiomakase.vn','0902000001','Đầu bếp','Bếp trưởng','Master Edomae (Ginza, Tokyo)','2018-01-01','Hoạt động','2026-10-05 15:05:53'),(8,'DB2','Kenji Sato','sake.sato@sabiomakase.vn','0902000002','Đầu bếp','Bếp phó (Kaiseki)','Kaiseki Specialist (Kyoto) · Sake Sommelier','2019-03-12','Hoạt động','2026-10-05 15:05:53'),(9,'DB3','Hiroshi Mori','hiroshi.mori@sabiomakase.vn','0902000003','Đầu bếp','Bếp phó (Sashimi)','Sashimi & Sake Sommelier','2020-05-05','Hoạt động','2026-10-05 15:05:53'),(10,'DB4','Nguyễn Văn Tài','tai.nguyen@sabiomakase.vn','0902000004','Đầu bếp','Đầu bếp sushi',NULL,'2021-07-01','Hoạt động','2026-10-05 15:05:53'),(11,'DB5','Trần Quốc Việt','viet.tran@sabiomakase.vn','0902000005','Đầu bếp','Đầu bếp sushi',NULL,'2022-02-14','Hoạt động','2026-10-05 15:05:53'),(12,'DB6','Lê Hữu Phước','phuoc.le@sabiomakase.vn','0902000006','Đầu bếp','Đầu bếp nướng',NULL,'2022-09-01','Hoạt động','2026-10-05 15:05:53'),(13,'TV1','Nguyễn Thị Lan',NULL,'0903000001','Tạp vụ','Vệ sinh & kho',NULL,'2022-01-05','Hoạt động','2026-10-05 15:05:53'),(14,'TV2','Trần Văn Hòa',NULL,'0903000002','Tạp vụ','Vệ sinh & kho',NULL,'2023-08-08','Hoạt động','2026-10-05 15:05:53'),(15,'TV3','Lê Thị Mai',NULL,'0903000003','Tạp vụ','Vệ sinh & kho',NULL,'2024-06-18','Hoạt động','2026-10-05 15:05:53');
/*!40000 ALTER TABLE `nhan_vien` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phuong_thuc_luu`
--

DROP TABLE IF EXISTS `phuong_thuc_luu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phuong_thuc_luu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `khach_hang_id` int(11) NOT NULL,
  `loai` enum('Thẻ','Chuyển khoản','Ví điện tử') NOT NULL,
  `mo_ta` varchar(100) NOT NULL,
  `so_cuoi` varchar(4) DEFAULT NULL,
  `han_su_dung` varchar(5) DEFAULT NULL,
  `mac_dinh` tinyint(1) NOT NULL DEFAULT 0,
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `khach_hang_id` (`khach_hang_id`),
  CONSTRAINT `phuong_thuc_luu_ibfk_1` FOREIGN KEY (`khach_hang_id`) REFERENCES `khach_hang` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phuong_thuc_luu`
--

LOCK TABLES `phuong_thuc_luu` WRITE;
/*!40000 ALTER TABLE `phuong_thuc_luu` DISABLE KEYS */;
INSERT INTO `phuong_thuc_luu` VALUES (1,1,'Thẻ','Visa / Mastercard','8829','11/27',1,'2026-10-05 15:05:53'),(2,1,'Chuyển khoản','QR Napas 247 - Vietcombank',NULL,NULL,0,'2026-10-05 15:05:53'),(3,1,'Ví điện tử','Apple Pay / Ví Sabi',NULL,NULL,0,'2026-10-05 15:05:53');
/*!40000 ALTER TABLE `phuong_thuc_luu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `set_omakase`
--

DROP TABLE IF EXISTS `set_omakase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `set_omakase` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_set` varchar(20) NOT NULL,
  `ten_set` varchar(150) NOT NULL,
  `ten_tieng_nhat` varchar(100) DEFAULT NULL,
  `loai_set_id` int(11) NOT NULL,
  `bep_truong_id` int(11) DEFAULT NULL,
  `so_mon` tinyint(3) unsigned NOT NULL,
  `thoi_luong_phut` smallint(5) unsigned NOT NULL,
  `gia` decimal(12,0) NOT NULL CHECK (`gia` > 0),
  `nhan` varchar(50) DEFAULT NULL,
  `mo_ta_ngan` varchar(255) DEFAULT NULL,
  `mo_ta_chi_tiet` text DEFAULT NULL,
  `hinh_anh` varchar(255) DEFAULT NULL,
  `noi_bat` tinyint(1) NOT NULL DEFAULT 0,
  `trang_thai` enum('Đang bán','Tạm ngừng','Ngừng bán') NOT NULL DEFAULT 'Đang bán',
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_set` (`ma_set`),
  KEY `idx_set_gia` (`gia`),
  KEY `loai_set_id` (`loai_set_id`),
  KEY `bep_truong_id` (`bep_truong_id`),
  CONSTRAINT `set_omakase_ibfk_1` FOREIGN KEY (`loai_set_id`) REFERENCES `loai_set` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `set_omakase_ibfk_2` FOREIGN KEY (`bep_truong_id`) REFERENCES `nhan_vien` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `set_omakase`
--

LOCK TABLES `set_omakase` WRITE;
/*!40000 ALTER TABLE `set_omakase` DISABLE KEYS */;
INSERT INTO `set_omakase` VALUES (1,'KIWAMI','Set Kiwami Grand Omakase','極上 おまかせ',2,7,18,120,3800000,'CHEF\'S CHOICE','Trải nghiệm đỉnh cao với Otoro ủ lạnh 14 ngày, Bào ngư Ezo hầm rượu Sake.','Mỗi buổi sáng lúc 4:30 AM tại chợ cá Toyosu (Tokyo), các Nakagai làm việc trực tiếp cùng bếp trưởng Daiki Takahashi để chọn ngừ vây xanh hon-maguro loại 1, nhum biển Nemuro và cá cam Buri. Hải sản xử lý theo phương pháp Ikejime và vận chuyển hỏa tốc đến bếp Sabi trước 15:00 mỗi ngày. Gạo Koshihikari Niigata ủ giấm đỏ Akazu 3 năm; Wasabi củ tươi Shizuoka.','https://images.unsplash.com/photo-1579871494447-9811cf80d66c?q=80&w=600&auto=format&fit=crop',1,'Đang bán','2026-10-05 15:05:53','2026-10-05 15:05:53'),(2,'MIYABI','Set Miyabi Kaiseki','雅 懐石',3,8,14,105,2800000,'SIGNATURE','Sự hài hòa thi vị giữa các món nướng than Binchotan thơm nồng và Sashimi.',NULL,'https://images.unsplash.com/photo-1553621042-f6e147245754?q=80&w=600&auto=format&fit=crop',1,'Đang bán','2026-10-05 15:05:53','2026-10-05 15:05:53'),(3,'TSUKI','Set Tsuki Nigiri','月 握り',2,7,12,90,1950000,'TRADITIONAL','Khám phá nghệ thuật cơm giấm đỏ Akasu bí truyền đi kèm 10 tuyệt phẩm Nigiri.',NULL,'https://images.unsplash.com/photo-1611143669185-af224c5e3252?q=80&w=600&auto=format&fit=crop',0,'Đang bán','2026-10-05 15:05:53','2026-10-05 15:05:53'),(4,'SAKURA','Set Sakura Seasonal','桜 季節限定',4,9,16,120,3200000,'LIMITED SEASONAL','Hương vị đầu mùa với Mực đom đóm Toyama, Cá thu Sawara xông rơm.',NULL,'https://images.unsplash.com/photo-1617196034183-421b4917c92d?q=80&w=600&auto=format&fit=crop',1,'Đang bán','2026-10-05 15:05:53','2026-10-05 15:05:53'),(5,'WAGYU','Set Wagyu & Otoro','和牛 & 大トロ',5,7,20,135,4800000,'PRESTIGE','Sự thăng hoa giữa Bò Wagyu A5 tỉnh Miyazaki và Bụng cá ngừ vây xanh.',NULL,'images/wagyu_otoro.jpg',1,'Đang bán','2026-10-05 15:05:53','2026-10-05 15:05:53'),(6,'TENMANGU','Set Tenmangu Lunch','天満宮 昼食',1,9,10,60,1500000,'LUNCH EXCLUSIVE','Phiên trải nghiệm giờ trưa với Chawanmushi trứng nhím biển, 8 món Nigiri.',NULL,'https://images.unsplash.com/photo-1558985250-27a406d64cb3?q=80&w=600&auto=format&fit=crop',0,'Đang bán','2026-10-05 15:05:53','2026-10-05 15:05:53'),(7,'UNI','Set Uni & Caviar Special','雲丹 & キャビア',2,7,18,120,4200000,'PREMIUM','Nhum biển Hokkaido và trứng cá tầm muối thượng hạng.',NULL,NULL,0,'Đang bán','2026-10-05 15:05:53','2026-10-05 15:05:53'),(8,'AKAMI','Set Akami Edomae','赤身 江戸前',2,7,14,100,2600000,NULL,'Các phần thịt đỏ cá ngừ ủ tương theo lối Edomae cổ điển.',NULL,NULL,0,'Đang bán','2026-10-05 15:05:53','2026-10-05 15:05:53'),(9,'MATSU','Set Sashimi Matsu','松 刺身',2,9,12,90,3200000,NULL,'Tuyển tập sashimi cắt tay từ hải sản Toyosu trong ngày.',NULL,NULL,0,'Đang bán','2026-10-05 15:05:53','2026-10-05 15:05:53'),(10,'AKI','Set Kaiseki Mùa Thu','秋 懐石',4,8,16,120,4500000,'SEASONAL','Kaiseki mùa thu với nấm Matsutake và hạt dẻ Kyoto.',NULL,NULL,0,'Đang bán','2026-10-05 15:05:53','2026-10-05 15:05:53'),(11,'SAKE','Set Omakase Sake Pairing','酒 ペアリング',2,8,18,150,4900000,'PAIRING','Omakase kết hợp 6 loại Sake do sommelier tuyển chọn.',NULL,NULL,0,'Đang bán','2026-10-05 15:05:53','2026-10-05 15:05:53');
/*!40000 ALTER TABLE `set_omakase` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tai_khoan_quan_tri`
--

DROP TABLE IF EXISTS `tai_khoan_quan_tri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tai_khoan_quan_tri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nhan_vien_id` int(11) NOT NULL,
  `ten_dang_nhap` varchar(50) NOT NULL,
  `mat_khau` varchar(255) NOT NULL,
  `quyen` enum('admin','quan_ly','nhan_vien') NOT NULL DEFAULT 'nhan_vien',
  `lan_dang_nhap_cuoi` datetime DEFAULT NULL,
  `trang_thai` tinyint(1) NOT NULL DEFAULT 1,
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nhan_vien_id` (`nhan_vien_id`),
  UNIQUE KEY `ten_dang_nhap` (`ten_dang_nhap`),
  CONSTRAINT `tai_khoan_quan_tri_ibfk_1` FOREIGN KEY (`nhan_vien_id`) REFERENCES `nhan_vien` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tai_khoan_quan_tri`
--

LOCK TABLES `tai_khoan_quan_tri` WRITE;
/*!40000 ALTER TABLE `tai_khoan_quan_tri` DISABLE KEYS */;
INSERT INTO `tai_khoan_quan_tri` VALUES (1,7,'admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin',NULL,1,'2026-10-05 15:05:53'),(2,8,'kenji','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','quan_ly',NULL,1,'2026-10-05 15:05:53'),(3,1,'thuha','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','nhan_vien',NULL,1,'2026-10-05 15:05:53');
/*!40000 ALTER TABLE `tai_khoan_quan_tri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `v_doanh_thu_ngay`
--

DROP TABLE IF EXISTS `v_doanh_thu_ngay`;
/*!50001 DROP VIEW IF EXISTS `v_doanh_thu_ngay`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_doanh_thu_ngay` AS SELECT
 1 AS `ngay`,
  1 AS `so_don`,
  1 AS `so_khach`,
  1 AS `doanh_thu` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_set_danh_gia`
--

DROP TABLE IF EXISTS `v_set_danh_gia`;
/*!50001 DROP VIEW IF EXISTS `v_set_danh_gia`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_set_danh_gia` AS SELECT
 1 AS `id`,
  1 AS `ma_set`,
  1 AS `ten_set`,
  1 AS `gia`,
  1 AS `diem_tb`,
  1 AS `so_danh_gia` */;
SET character_set_client = @saved_cs_client;

--
-- Dumping routines for database 'omakase_store_ltw9'
--

--
-- Current Database: `omakase_store_ltw9`
--

USE `omakase_store_ltw9`;

--
-- Final view structure for view `v_doanh_thu_ngay`
--

/*!50001 DROP VIEW IF EXISTS `v_doanh_thu_ngay`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_doanh_thu_ngay` AS select `don_dat_ban`.`ngay_dung` AS `ngay`,count(0) AS `so_don`,sum(`don_dat_ban`.`so_khach`) AS `so_khach`,sum(`don_dat_ban`.`tong_tien`) AS `doanh_thu` from `don_dat_ban` where `don_dat_ban`.`trang_thai` = 'Hoàn tất' group by `don_dat_ban`.`ngay_dung` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_set_danh_gia`
--

/*!50001 DROP VIEW IF EXISTS `v_set_danh_gia`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_set_danh_gia` AS select `s`.`id` AS `id`,`s`.`ma_set` AS `ma_set`,`s`.`ten_set` AS `ten_set`,`s`.`gia` AS `gia`,round(coalesce(avg(`d`.`so_sao`),0),1) AS `diem_tb`,count(`d`.`id`) AS `so_danh_gia` from (`set_omakase` `s` left join `danh_gia` `d` on(`d`.`set_id` = `s`.`id` and `d`.`hien_thi` = 1)) group by `s`.`id`,`s`.`ma_set`,`s`.`ten_set`,`s`.`gia` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-22 21:51:50
