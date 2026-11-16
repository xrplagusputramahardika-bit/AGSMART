-- ========================================================
-- Database Dump: db_tokokita (Enhanced & Modernized for AGSmart POS)
-- Generated: 2026-11-16
-- Compatible with MySQL 5.7+ / 8.0+ / MariaDB 10.4+
-- ========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_tokokita`
--
CREATE DATABASE IF NOT EXISTS `db_tokokita` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_tokokita`;

-- --------------------------------------------------------
-- 1. Table structure for `tbl_pengaturan` (Store Profile & Receipt Settings)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_pengaturan` (
  `id` int(11) NOT NULL DEFAULT 1,
  `nama_toko` varchar(100) NOT NULL DEFAULT 'AGSMART STORE',
  `slogan` varchar(150) DEFAULT 'Pusat Belanja Hemat & Terpercaya',
  `alamat` text DEFAULT 'Jl. Merdeka Raya No. 45, Sentra Niaga',
  `telepon` varchar(50) DEFAULT '0812-9876-5432',
  `email` varchar(100) DEFAULT 'kontak@agsmart.id',
  `pesan_struk` text DEFAULT 'Terima kasih atas kunjungan Anda!\nBarang yang sudah dibeli tidak dapat ditukar kecuali ada perjanjian.',
  `footer_struk` text DEFAULT 'Simpan struk ini sebagai bukti pembayaran yang sah.',
  `pajak_persen` decimal(5,2) DEFAULT 0.00,
  `lebar_struk` varchar(10) DEFAULT '58mm',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tbl_pengaturan` (`id`, `nama_toko`, `slogan`, `alamat`, `telepon`, `email`, `pesan_struk`, `footer_struk`, `pajak_persen`, `lebar_struk`) 
VALUES (1, 'AGSMART MART', 'Supermarket & Toko Modern', 'Jl. Merdeka Raya No. 45, Jawa Timur', '0812-8888-9999', 'admin@agsmart.com', 'Terima kasih telah berbelanja di AGSMART MART!', 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.', 0.00, '58mm')
ON DUPLICATE KEY UPDATE `nama_toko` = VALUES(`nama_toko`);

-- --------------------------------------------------------
-- 2. Table structure for `tbl_user` (Users / Cashiers)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_user` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','kasir') DEFAULT 'kasir',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tbl_user` (`id_user`, `username`, `password`, `nama_lengkap`, `role`) VALUES
(1, 'admin', '$2y$10$wE9o42Q6i4p1bA9yv23oQOaG5737Z6l9Qv3oO68w6wM86yM86yM86', 'Administrator', 'admin'),
(2, 'kasir1', '$2y$10$wE9o42Q6i4p1bA9yv23oQOaG5737Z6l9Qv3oO68w6wM86yM86yM86', 'Siti Rahma (Kasir 1)', 'kasir')
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`);

-- --------------------------------------------------------
-- 3. Table structure for `tbl_pelanggan` (With Member Card / RFID Tap Support)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_pelanggan` (
  `id_pelanggan` int(11) NOT NULL AUTO_INCREMENT,
  `nama_pelanggan` varchar(100) DEFAULT NULL,
  `kota` varchar(50) DEFAULT NULL,
  `telepon` varchar(25) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `no_kartu` varchar(50) DEFAULT NULL UNIQUE,
  `tipe_member` enum('Regular','Silver','Gold','Platinum') DEFAULT 'Silver',
  `diskon_member` decimal(5,2) DEFAULT 0.00,
  `poin` int(11) DEFAULT 0,
  `saldo` decimal(12,2) DEFAULT 0.00,
  `status_kartu` enum('aktif','nonaktif','blokir') DEFAULT 'aktif',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pelanggan`),
  KEY `no_kartu` (`no_kartu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tbl_pelanggan` (`id_pelanggan`, `nama_pelanggan`, `kota`, `telepon`, `alamat`, `no_kartu`, `tipe_member`, `diskon_member`, `poin`, `saldo`, `status_kartu`) VALUES
(1, 'Andi Saputra', 'Jakarta', '08123456701', 'Jl. Sudirman No. 12', 'MBR-88012301', 'Platinum', 10.00, 350, 250000.00, 'aktif'),
(2, 'Budi Santoso', 'Surabaya', '08123456702', 'Jl. Basuki Rahmat No. 44', 'MBR-88012302', 'Gold', 5.00, 180, 120000.00, 'aktif'),
(3, 'Citra Lestari', 'Bandung', '08123456703', 'Jl. Dago Asri No. 8', 'MBR-88012303', 'Silver', 2.00, 95, 50000.00, 'aktif'),
(4, 'Dewi Anggraini', 'Yogyakarta', '08123456704', 'Jl. Malioboro No. 19', 'MBR-88012304', 'Gold', 5.00, 220, 150000.00, 'aktif'),
(5, 'Eko Prasetyo', 'Semarang', '08123456705', 'Jl. Pandanaran No. 77', 'MBR-88012305', 'Silver', 2.00, 120, 35000.00, 'aktif'),
(6, 'Fajar Nugroho', 'Malang', '08123456706', 'Jl. Ijen Boulevard No. 15', 'MBR-88012306', 'Platinum', 10.00, 410, 300000.00, 'aktif'),
(7, 'Gita Permata', 'Medan', '08123456707', 'Jl. Gatot Subroto No. 32', 'MBR-88012307', 'Regular', 0.00, 50, 0.00, 'aktif'),
(8, 'Hendra Wijaya', 'Makassar', '08123456708', 'Jl. Somba Opu No. 90', 'MBR-88012308', 'Gold', 5.00, 190, 80000.00, 'aktif'),
(9, 'Indah Kusuma', 'Denpasar', '08123456709', 'Jl. Sunset Road No. 101', 'MBR-88012309', 'Regular', 0.00, 40, 25000.00, 'aktif'),
(10, 'Joko Susilo', 'Solo', '08123456710', 'Jl. Slamet Riyadi No. 5', 'MBR-88012310', 'Silver', 2.00, 110, 50000.00, 'aktif')
ON DUPLICATE KEY UPDATE 
  `nama_pelanggan` = VALUES(`nama_pelanggan`), 
  `kota` = VALUES(`kota`),
  `no_kartu` = VALUES(`no_kartu`),
  `tipe_member` = VALUES(`tipe_member`),
  `diskon_member` = VALUES(`diskon_member`),
  `poin` = VALUES(`poin`),
  `saldo` = VALUES(`saldo`);

-- --------------------------------------------------------
-- 4. Table structure for `tbl_kategori` (Product Categories)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_kategori` (
  `id_kategori` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  `ikon` varchar(50) DEFAULT 'bi-tag',
  PRIMARY KEY (`id_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tbl_kategori` (`id_kategori`, `nama_kategori`, `ikon`) VALUES
(1, 'Sembako & Kebutuhan Pokok', 'bi-basket2-fill'),
(2, 'Makanan & Snack', 'bi-egg-fried'),
(3, 'Minuman Segar', 'bi-cup-straw'),
(4, 'Bumbu Dapur & Masak', 'bi-fire'),
(5, 'Perawatan Tubuh & Mandi', 'bi-heart-pulse-fill'),
(6, 'Kebersihan Rumah Tangga', 'bi-shield-check'),
(7, 'Alat Tulis Kantor (ATK)', 'bi-pencil-fill')
ON DUPLICATE KEY UPDATE `nama_kategori` = VALUES(`nama_kategori`);

-- --------------------------------------------------------
-- 5. Table structure for `tbl_produk` (Products / Items)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_produk` (
  `id_produk` int(11) NOT NULL AUTO_INCREMENT,
  `kode_produk` varchar(50) NOT NULL UNIQUE,
  `nama_produk` varchar(150) NOT NULL,
  `id_kategori` int(11) DEFAULT NULL,
  `satuan` varchar(30) DEFAULT 'pcs',
  `harga_beli` decimal(12,2) NOT NULL DEFAULT 0.00,
  `harga_jual` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stok` int(11) NOT NULL DEFAULT 0,
  `stok_minimal` int(11) NOT NULL DEFAULT 5,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_produk`),
  KEY `id_kategori` (`id_kategori`),
  CONSTRAINT `fk_produk_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `tbl_kategori` (`id_kategori`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tbl_produk` (`id_produk`, `kode_produk`, `nama_produk`, `id_kategori`, `satuan`, `harga_beli`, `harga_jual`, `stok`, `stok_minimal`) VALUES
(1, 'BRG-001', 'Beras Ramos Super 5kg', 1, 'sak', 62000.00, 70000.00, 45, 10),
(2, 'BRG-002', 'Minyak Goreng Sania 2 Liter', 1, 'pouch', 33000.00, 38000.00, 60, 15),
(3, 'BRG-003', 'Gula Pasir Gulaku Premium 1kg', 1, 'kg', 15000.00, 18000.00, 80, 20),
(4, 'BRG-004', 'Tepung Terigu Segitiga Biru 1kg', 1, 'kg', 10500.00, 13000.00, 50, 15),
(5, 'BRG-005', 'Telur Ayam Negeri 1kg', 1, 'kg', 24000.00, 28000.00, 40, 10),
(6, 'BRG-006', 'Indomie Goreng Spesial 85g', 2, 'bungkus', 2600.00, 3200.00, 150, 30),
(7, 'BRG-007', 'Indomie Kuah Ayam Bawang 69g', 2, 'bungkus', 2500.00, 3000.00, 120, 25),
(8, 'BRG-008', 'Chitato Sapi Panggang 68g', 2, 'bungkus', 9500.00, 12000.00, 40, 10),
(9, 'BRG-009', 'Oreo Vanilla Sandwich 133g', 2, 'bungkus', 8500.00, 10500.00, 55, 10),
(10, 'BRG-010', 'SilverQueen Milk Chocolate 62g', 2, 'pcs', 13000.00, 16500.00, 35, 10),
(11, 'BRG-011', 'Aqua Air Mineral 600ml', 3, 'botol', 2800.00, 4000.00, 95, 24),
(12, 'BRG-012', 'Pocari Sweat Botol 500ml', 3, 'botol', 6500.00, 8500.00, 50, 15),
(13, 'BRG-013', 'Teh Botol Sosro Kotak 250ml', 3, 'kotak', 3200.00, 4500.00, 70, 20),
(14, 'BRG-014', 'Susu UHT Ultra Milk Coklat 250ml', 3, 'kotak', 5500.00, 7000.00, 65, 15),
(15, 'BRG-015', 'Kopi Nescafe Classic Jar 100g', 3, 'jar', 28000.00, 35000.00, 25, 5),
(16, 'BRG-016', 'Kecap Manis Bango Pouch 520ml', 4, 'pouch', 20000.00, 24000.00, 45, 10),
(17, 'BRG-017', 'Saus Sambal ABC Botol 335ml', 4, 'botol', 14000.00, 17500.00, 30, 8),
(18, 'BRG-018', 'Royco Kaldu Ayam Sachet 230g', 4, 'pouch', 9000.00, 11500.00, 50, 10),
(19, 'BRG-019', 'Sabun Mandi Lifebuoy Total 10 85g', 5, 'batang', 3500.00, 4800.00, 80, 20),
(20, 'BRG-020', 'Shampoo Pantene Anti Ketombe 160ml', 5, 'botol', 21000.00, 26000.00, 30, 5),
(21, 'BRG-021', 'Pasta Gigi Pepsodent Fresh Cool 190g', 5, 'tube', 13500.00, 17000.00, 45, 10),
(22, 'BRG-022', 'Deterjen Rinso Molto Bubuk 770g', 6, 'bungkus', 17500.00, 22000.00, 40, 10),
(23, 'BRG-023', 'Sunlight Pencuci Piring Jeruk Nipis 750ml', 6, 'pouch', 14500.00, 18500.00, 55, 12),
(24, 'BRG-024', 'Super Pell Pembersih Lantai 770ml', 6, 'pouch', 12000.00, 15000.00, 35, 8),
(25, 'BRG-025', 'Buku Tulis Sidu 38 Lembar Pack 10', 7, 'pack', 28000.00, 36000.00, 25, 5),
(26, 'BRG-026', 'Bolpoint Standard AE7 Hitam Box 12', 7, 'kotak', 22000.00, 28000.00, 30, 5)
ON DUPLICATE KEY UPDATE `nama_produk` = VALUES(`nama_produk`), `harga_jual` = VALUES(`harga_jual`);

-- --------------------------------------------------------
-- 6. Table structure for `tbl_transaksi` (Transaction Header - Enhanced)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_transaksi` (
  `id_transaksi` int(11) NOT NULL AUTO_INCREMENT,
  `no_faktur` varchar(50) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `waktu` time DEFAULT '10:00:00',
  `id_pelanggan` int(11) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `diskon` decimal(12,2) DEFAULT 0.00,
  `pajak` decimal(12,2) DEFAULT 0.00,
  `total_belanja` decimal(12,2) DEFAULT NULL,
  `bayar` decimal(12,2) DEFAULT 0.00,
  `kembalian` decimal(12,2) DEFAULT 0.00,
  `metode_pembayaran` varchar(30) DEFAULT 'Tunai',
  `kasir` varchar(50) DEFAULT 'Siti Rahma (Kasir 1)',
  `catatan` varchar(255) DEFAULT NULL,
  `poin_didapat` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_transaksi`),
  KEY `id_pelanggan` (`id_pelanggan`),
  KEY `no_faktur` (`no_faktur`),
  CONSTRAINT `tbl_transaksi_ibfk_1` FOREIGN KEY (`id_pelanggan`) REFERENCES `tbl_pelanggan` (`id_pelanggan`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert Original 20 Transactions with matching invoice and payment details
INSERT INTO `tbl_transaksi` (`id_transaksi`, `no_faktur`, `tanggal`, `waktu`, `id_pelanggan`, `subtotal`, `diskon`, `pajak`, `total_belanja`, `bayar`, `kembalian`, `metode_pembayaran`, `kasir`, `poin_didapat`) VALUES
(1, 'TRX-20261101-0001', '2026-11-01', '08:30:00', 1, 150000.00, 0.00, 0.00, 150000.00, 150000.00, 0.00, 'Tunai', 'Siti Rahma', 15),
(2, 'TRX-20261101-0002', '2026-11-01', '10:15:00', 2, 275000.00, 0.00, 0.00, 275000.00, 300000.00, 25000.00, 'QRIS', 'Siti Rahma', 27),
(3, 'TRX-20261102-0003', '2026-11-02', '11:00:00', 3, 95000.00, 0.00, 0.00, 95000.00, 100000.00, 5000.00, 'Tunai', 'Siti Rahma', 9),
(4, 'TRX-20261102-0004', '2026-11-02', '14:20:00', 1, 320000.00, 0.00, 0.00, 320000.00, 350000.00, 30000.00, 'Transfer Bank', 'Siti Rahma', 32),
(5, 'TRX-20261103-0005', '2026-11-03', '09:45:00', 4, 450000.00, 0.00, 0.00, 450000.00, 450000.00, 0.00, 'Tunai', 'Siti Rahma', 45),
(6, 'TRX-20261103-0006', '2026-11-03', '15:10:00', 5, 125000.00, 0.00, 0.00, 125000.00, 150000.00, 25000.00, 'QRIS', 'Siti Rahma', 12),
(7, 'TRX-20261104-0007', '2026-11-04', '11:35:00', 6, 610000.00, 0.00, 0.00, 610000.00, 650000.00, 40000.00, 'Debit', 'Siti Rahma', 61),
(8, 'TRX-20261104-0008', '2026-11-04', '16:00:00', 2, 180000.00, 0.00, 0.00, 180000.00, 200000.00, 20000.00, 'Tunai', 'Siti Rahma', 18),
(9, 'TRX-20261105-0009', '2026-11-05', '10:05:00', 7, 230000.00, 0.00, 0.00, 230000.00, 250000.00, 20000.00, 'QRIS', 'Siti Rahma', 23),
(10, 'TRX-20261105-0010', '2026-11-05', '17:40:00', 8, 540000.00, 0.00, 0.00, 540000.00, 540000.00, 0.00, 'Transfer Bank', 'Siti Rahma', 54),
(11, 'TRX-20261106-0011', '2026-11-06', '08:50:00', 9, 85000.00, 0.00, 0.00, 85000.00, 100000.00, 15000.00, 'Tunai', 'Siti Rahma', 8),
(12, 'TRX-20261106-0012', '2026-11-06', '13:25:00', 10, 390000.00, 0.00, 0.00, 390000.00, 400000.00, 10000.00, 'Debit', 'Siti Rahma', 39),
(13, 'TRX-20261107-0013', '2026-11-07', '10:15:00', 3, 210000.00, 0.00, 0.00, 210000.00, 210000.00, 0.00, 'QRIS', 'Siti Rahma', 21),
(14, 'TRX-20261107-0014', '2026-11-07', '14:50:00', 4, 175000.00, 0.00, 0.00, 175000.00, 200000.00, 25000.00, 'Tunai', 'Siti Rahma', 17),
(15, 'TRX-20261108-0015', '2026-11-08', '11:10:00', 5, 490000.00, 0.00, 0.00, 490000.00, 500000.00, 10000.00, 'Debit', 'Siti Rahma', 49),
(16, 'TRX-20261108-0016', '2026-11-08', '15:30:00', 1, 110000.00, 0.00, 0.00, 110000.00, 110000.00, 0.00, 'Tunai', 'Siti Rahma', 11),
(17, 'TRX-20261109-0017', '2026-11-09', '09:20:00', 6, 330000.00, 0.00, 0.00, 330000.00, 350000.00, 20000.00, 'QRIS', 'Siti Rahma', 33),
(18, 'TRX-20261109-0018', '2026-11-09', '16:45:00', 7, 295000.00, 0.00, 0.00, 295000.00, 300000.00, 5000.00, 'Tunai', 'Siti Rahma', 29),
(19, 'TRX-20261110-0019', '2026-11-10', '10:40:00', 8, 415000.00, 0.00, 0.00, 415000.00, 420000.00, 5000.00, 'Transfer Bank', 'Siti Rahma', 41),
(20, 'TRX-20261110-0020', '2026-11-10', '18:00:00', 10, 260000.00, 0.00, 0.00, 260000.00, 300000.00, 40000.00, 'Tunai', 'Siti Rahma', 26)
ON DUPLICATE KEY UPDATE `no_faktur` = VALUES(`no_faktur`), `total_belanja` = VALUES(`total_belanja`);

-- --------------------------------------------------------
-- 7. Table structure for `tbl_detail_transaksi` (Transaction Items)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tbl_detail_transaksi` (
  `id_detail` int(11) NOT NULL AUTO_INCREMENT,
  `id_transaksi` int(11) NOT NULL,
  `id_produk` int(11) DEFAULT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `harga_satuan` decimal(12,2) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id_detail`),
  KEY `id_transaksi` (`id_transaksi`),
  KEY `id_produk` (`id_produk`),
  CONSTRAINT `fk_detail_transaksi` FOREIGN KEY (`id_transaksi`) REFERENCES `tbl_transaksi` (`id_transaksi`) ON DELETE CASCADE,
  CONSTRAINT `fk_detail_produk` FOREIGN KEY (`id_produk`) REFERENCES `tbl_produk` (`id_produk`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tbl_detail_transaksi` (`id_transaksi`, `id_produk`, `nama_produk`, `harga_satuan`, `qty`, `subtotal`) VALUES
(1, 1, 'Beras Ramos Super 5kg', 70000.00, 1, 70000.00),
(1, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 2, 76000.00),
(1, 11, 'Aqua Air Mineral 600ml', 4000.00, 1, 4000.00),

(2, 1, 'Beras Ramos Super 5kg', 70000.00, 2, 140000.00),
(2, 3, 'Gula Pasir Gulaku Premium 1kg', 18000.00, 3, 54000.00),
(2, 5, 'Telur Ayam Negeri 1kg', 28000.00, 2, 56000.00),
(2, 25, 'Buku Tulis Sidu 38 Lembar Pack 10', 25000.00, 1, 25000.00),

(3, 8, 'Chitato Sapi Panggang 68g', 12000.00, 3, 36000.00),
(3, 10, 'SilverQueen Milk Chocolate 62g', 16500.00, 2, 33000.00),
(3, 12, 'Pocari Sweat Botol 500ml', 8500.00, 2, 17000.00),
(3, 9, 'Oreo Vanilla Sandwich 133g', 9000.00, 1, 9000.00),

(4, 1, 'Beras Ramos Super 5kg', 70000.00, 2, 140000.00),
(4, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 3, 114000.00),
(4, 20, 'Shampoo Pantene Anti Ketombe 160ml', 26000.00, 1, 26000.00),
(4, 21, 'Pasta Gigi Pepsodent Fresh Cool 190g', 20000.00, 2, 40000.00),

(5, 1, 'Beras Ramos Super 5kg', 70000.00, 4, 280000.00),
(5, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 3, 114000.00),
(5, 5, 'Telur Ayam Negeri 1kg', 28000.00, 2, 56000.00),

(6, 16, 'Kecap Manis Bango Pouch 520ml', 24000.00, 2, 48000.00),
(6, 17, 'Saus Sambal ABC Botol 335ml', 17500.00, 2, 35000.00),
(6, 22, 'Deterjen Rinso Molto Bubuk 770g', 21000.00, 2, 42000.00),

(7, 1, 'Beras Ramos Super 5kg', 70000.00, 5, 350000.00),
(7, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 4, 152000.00),
(7, 3, 'Gula Pasir Gulaku Premium 1kg', 18000.00, 3, 54000.00),
(7, 5, 'Telur Ayam Negeri 1kg', 27000.00, 2, 54000.00),

(8, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 3, 114000.00),
(8, 22, 'Deterjen Rinso Molto Bubuk 770g', 22000.00, 3, 66000.00),

(9, 15, 'Kopi Nescafe Classic Jar 100g', 35000.00, 4, 140000.00),
(9, 3, 'Gula Pasir Gulaku Premium 1kg', 18000.00, 5, 90000.00),

(10, 1, 'Beras Ramos Super 5kg', 70000.00, 6, 420000.00),
(10, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 2, 76000.00),
(10, 23, 'Sunlight Pencuci Piring Jeruk Nipis 750ml', 22000.00, 2, 44000.00),

(11, 6, 'Indomie Goreng Spesial 85g', 3200.00, 10, 32000.00),
(11, 7, 'Indomie Kuah Ayam Bawang 69g', 3000.00, 10, 30000.00),
(11, 11, 'Aqua Air Mineral 600ml', 4000.00, 5, 20000.00),
(11, 19, 'Sabun Mandi Lifebuoy Total 10 85g', 3000.00, 1, 3000.00),

(12, 1, 'Beras Ramos Super 5kg', 70000.00, 4, 280000.00),
(12, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 2, 76000.00),
(12, 21, 'Pasta Gigi Pepsodent Fresh Cool 190g', 17000.00, 2, 34000.00),

(13, 1, 'Beras Ramos Super 5kg', 70000.00, 3, 210000.00),

(14, 20, 'Shampoo Pantene Anti Ketombe 160ml', 26000.00, 3, 78000.00),
(14, 21, 'Pasta Gigi Pepsodent Fresh Cool 190g', 17000.00, 3, 51000.00),
(14, 23, 'Sunlight Pencuci Piring Jeruk Nipis 750ml', 23000.00, 2, 46000.00),

(15, 1, 'Beras Ramos Super 5kg', 70000.00, 5, 350000.00),
(15, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 2, 76000.00),
(15, 4, 'Tepung Terigu Segitiga Biru 1kg', 13000.00, 4, 52000.00),
(15, 18, 'Royco Kaldu Ayam Sachet 230g', 12000.00, 1, 12000.00),

(16, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 2, 76000.00),
(16, 21, 'Pasta Gigi Pepsodent Fresh Cool 190g', 17000.00, 2, 34000.00),

(17, 1, 'Beras Ramos Super 5kg', 70000.00, 3, 210000.00),
(17, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 2, 76000.00),
(17, 22, 'Deterjen Rinso Molto Bubuk 770g', 22000.00, 2, 44000.00),

(18, 1, 'Beras Ramos Super 5kg', 70000.00, 3, 210000.00),
(18, 5, 'Telur Ayam Negeri 1kg', 28000.00, 2, 56000.00),
(18, 13, 'Teh Botol Sosro Kotak 250ml', 4500.00, 4, 18000.00),
(18, 14, 'Susu UHT Ultra Milk Coklat 250ml', 11000.00, 1, 11000.00),

(19, 1, 'Beras Ramos Super 5kg', 70000.00, 4, 280000.00),
(19, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 3, 114000.00),
(19, 11, 'Aqua Air Mineral 600ml', 4000.00, 5, 20000.00),
(19, 19, 'Sabun Mandi Lifebuoy Total 10 85g', 1000.00, 1, 1000.00),

(20, 1, 'Beras Ramos Super 5kg', 70000.00, 2, 140000.00),
(20, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 2, 76000.00),
(20, 24, 'Super Pell Pembersih Lantai 770ml', 15000.00, 2, 30000.00),
(20, 26, 'Bolpoint Standard AE7 Hitam Box 12', 14000.00, 1, 14000.00);

-- --------------------------------------------------------
-- 8. Structure for View: `v_laporan_transaksi` (Original View preserved)
-- --------------------------------------------------------
DROP VIEW IF EXISTS `v_laporan_transaksi`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_laporan_transaksi` AS 
SELECT 
  `t`.`id_transaksi` AS `id_transaksi`,
  `t`.`no_faktur` AS `no_faktur`,
  `t`.`tanggal` AS `tanggal`,
  `t`.`waktu` AS `waktu`,
  `p`.`nama_pelanggan` AS `nama_pelanggan`,
  `p`.`kota` AS `kota`,
  `p`.`no_kartu` AS `no_kartu`,
  `p`.`tipe_member` AS `tipe_member`,
  `t`.`metode_pembayaran` AS `metode_pembayaran`,
  `t`.`total_belanja` AS `total_belanja`
FROM (`tbl_transaksi` `t` JOIN `tbl_pelanggan` `p` ON(`t`.`id_pelanggan` = `p`.`id_pelanggan`));

-- --------------------------------------------------------
-- 9. Structure for View: `v_total_belanja_pelanggan` (Original View preserved)
-- --------------------------------------------------------
DROP VIEW IF EXISTS `v_total_belanja_pelanggan`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_total_belanja_pelanggan` AS 
SELECT 
  `p`.`id_pelanggan` AS `id_pelanggan`,
  `p`.`nama_pelanggan` AS `nama_pelanggan`,
  `p`.`kota` AS `kota`,
  `p`.`telepon` AS `telepon`,
  `p`.`no_kartu` AS `no_kartu`,
  `p`.`tipe_member` AS `tipe_member`,
  `p`.`poin` AS `poin`,
  `p`.`saldo` AS `saldo`,
  COUNT(`t`.`id_transaksi`) AS `jumlah_transaksi`,
  COALESCE(SUM(`t`.`total_belanja`),0) AS `total_keseluruhan_belanja`
FROM (`tbl_pelanggan` `p` LEFT JOIN `tbl_transaksi` `t` ON(`p`.`id_pelanggan` = `t`.`id_pelanggan`))
GROUP BY `p`.`id_pelanggan`, `p`.`nama_pelanggan`, `p`.`kota`, `p`.`telepon`, `p`.`no_kartu`, `p`.`tipe_member`, `p`.`poin`, `p`.`saldo`;

-- --------------------------------------------------------
-- 10. Structure for View: `v_produk_terlaris`
-- --------------------------------------------------------
DROP VIEW IF EXISTS `v_produk_terlaris`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_produk_terlaris` AS 
SELECT 
  `p`.`id_produk`,
  `p`.`kode_produk`,
  `p`.`nama_produk`,
  `k`.`nama_kategori`,
  `p`.`harga_jual`,
  `p`.`stok`,
  COALESCE(SUM(`d`.`qty`), 0) AS `total_terjual`,
  COALESCE(SUM(`d`.`subtotal`), 0) AS `total_pendapatan`
FROM `tbl_produk` `p`
LEFT JOIN `tbl_kategori` `k` ON `p`.`id_kategori` = `k`.`id_kategori`
LEFT JOIN `tbl_detail_transaksi` `d` ON `p`.`id_produk` = `d`.`id_produk`
GROUP BY `p`.`id_produk`, `p`.`kode_produk`, `p`.`nama_produk`, `k`.`nama_kategori`, `p`.`harga_jual`, `p`.`stok`
ORDER BY `total_terjual` DESC;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
