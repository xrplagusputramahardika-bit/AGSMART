-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 18, 2026 at 04:22 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_tokokita`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `CariPelanggan` (IN `p_id` INT)   BEGIN
    SELECT * 
    FROM tbl_pelanggan 
    WHERE id_pelanggan = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `HitungPelanggan` (OUT `jumlah` INT)   BEGIN
    SELECT COUNT(*)
    INTO jumlah
    FROM tbl_pelanggan;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `LaporanTransaksi` (IN `p_id` INT)   BEGIN
    SELECT 
        t.id_transaksi,
        t.tanggal,
        p.nama_pelanggan,
        p.kota,
        t.total_belanja
    FROM tbl_transaksi t
    JOIN tbl_pelanggan p ON t.id_pelanggan = p.id_pelanggan
    WHERE t.id_pelanggan = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `TambahPelanggan` (IN `p_nama` VARCHAR(100), IN `p_kota` VARCHAR(50))   BEGIN
    INSERT INTO tbl_pelanggan (nama_pelanggan, kota)
    VALUES (p_nama, p_kota);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `TampilPelanggan` ()   BEGIN
    SELECT * FROM tbl_pelanggan;
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `HitungDiskon` (`harga` DECIMAL(12,2), `diskon` DECIMAL(5,2)) RETURNS DECIMAL(12,2) DETERMINISTIC BEGIN
    RETURN harga * (diskon / 100);
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `HitungPajak` (`harga` DECIMAL(12,2)) RETURNS DECIMAL(12,2) DETERMINISTIC BEGIN
    RETURN harga * 0.10;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `HitungTotal` (`harga` DECIMAL(12,2), `jumlah` INT) RETURNS DECIMAL(12,2) DETERMINISTIC BEGIN
    RETURN harga * jumlah;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `KategoriBelanja` (`total` DECIMAL(12,2)) RETURNS VARCHAR(50) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC BEGIN
    DECLARE kategori VARCHAR(50);
    
    IF total > 500000 THEN
        SET kategori = 'VIP / Belanja Besar';
    ELSEIF total > 200000 THEN
        SET kategori = 'Reguler / Menengah';
    ELSE
        SET kategori = 'Ekonomis / Kecil';
    END IF;
    
    RETURN kategori;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `KonversiNilai` (`nilai` INT) RETURNS VARCHAR(5) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC BEGIN
    DECLARE grade VARCHAR(5);
    
    IF nilai >= 85 THEN
        SET grade = 'A';
    ELSEIF nilai >= 75 THEN
        SET grade = 'B';
    ELSEIF nilai >= 60 THEN
        SET grade = 'C';
    ELSEIF nilai >= 50 THEN
        SET grade = 'D';
    ELSE
        SET grade = 'E';
    END IF;
    
    RETURN grade;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `log_pelanggan_baru`
--

CREATE TABLE `log_pelanggan_baru` (
  `id_log` int(11) NOT NULL,
  `id_pelanggan` int(11) DEFAULT NULL,
  `nama_pelanggan` varchar(100) DEFAULT NULL,
  `kota` varchar(50) DEFAULT NULL,
  `waktu_input` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_pelanggan_baru`
--

INSERT INTO `log_pelanggan_baru` (`id_log`, `id_pelanggan`, `nama_pelanggan`, `kota`, `waktu_input`) VALUES
(2, 12, 'Eko Santoso', 'Sukun', '2026-11-16 11:08:47');

-- --------------------------------------------------------

--
-- Table structure for table `log_pelanggan_update`
--

CREATE TABLE `log_pelanggan_update` (
  `id_log` int(11) NOT NULL,
  `id_pelanggan` int(11) DEFAULT NULL,
  `nama_lama` varchar(100) DEFAULT NULL,
  `nama_baru` varchar(100) DEFAULT NULL,
  `kota_lama` varchar(50) DEFAULT NULL,
  `kota_baru` varchar(50) DEFAULT NULL,
  `waktu_update` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_pelanggan_update`
--

INSERT INTO `log_pelanggan_update` (`id_log`, `id_pelanggan`, `nama_lama`, `nama_baru`, `kota_lama`, `kota_baru`, `waktu_update`) VALUES
(1, 1, 'Andi Saputra', 'Andi Saputra', 'Jakarta', 'Yogyakarta', '2026-11-16 09:14:25'),
(2, 1, 'Andi Saputra', 'Andi Saputra', 'Yogyakarta', 'Jakarta', '2026-11-16 09:46:54'),
(3, 2, 'Budi Santoso', 'Budi Santoso', 'Surabaya', 'Surabaya', '2026-11-16 09:46:54'),
(4, 3, 'Citra Lestari', 'Citra Lestari', 'Bandung', 'Bandung', '2026-11-16 09:46:54'),
(5, 4, 'Dewi Anggraini', 'Dewi Anggraini', 'Yogyakarta', 'Yogyakarta', '2026-11-16 09:46:54'),
(6, 5, 'Eko Prasetyo', 'Eko Prasetyo', 'Semarang', 'Semarang', '2026-11-16 09:46:54'),
(7, 6, 'Fajar Nugroho', 'Fajar Nugroho', 'Malang', 'Malang', '2026-11-16 09:46:54'),
(8, 7, 'Gita Permata', 'Gita Permata', 'Medan', 'Medan', '2026-11-16 09:46:54'),
(9, 8, 'Hendra Wijaya', 'Hendra Wijaya', 'Makassar', 'Makassar', '2026-11-16 09:46:54'),
(10, 9, 'Indah Kusuma', 'Indah Kusuma', 'Denpasar', 'Denpasar', '2026-11-16 09:46:54'),
(11, 10, 'Joko Susilo', 'Joko Susilo', 'Solo', 'Solo', '2026-11-16 09:46:54'),
(12, 12, 'Eko Santoso', 'Eko Santoso', 'Sukun', 'Sukun', '2026-11-16 11:09:20'),
(13, 12, 'Eko Santoso', 'Eko Santoso', 'Sukun', 'Sukun', '2026-11-16 11:13:22'),
(14, 12, 'Eko Santoso', 'Eko Santoso', 'Sukun', 'Sukun', '2026-11-16 11:17:54'),
(15, 12, 'Eko Santoso', 'Eko Santoso', 'Sukun', 'Sukun', '2026-11-16 11:22:42'),
(16, 12, 'Eko Santoso', 'Eko Santoso', 'Sukun', 'Sukun', '2026-11-16 11:27:15');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_detail_transaksi`
--

CREATE TABLE `tbl_detail_transaksi` (
  `id_detail` int(11) NOT NULL,
  `id_transaksi` int(11) NOT NULL,
  `id_produk` int(11) DEFAULT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `harga_satuan` decimal(12,2) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_detail_transaksi`
--

INSERT INTO `tbl_detail_transaksi` (`id_detail`, `id_transaksi`, `id_produk`, `nama_produk`, `harga_satuan`, `qty`, `subtotal`) VALUES
(1, 1, 1, 'Beras Ramos Super 5kg', 70000.00, 1, 70000.00),
(2, 1, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 2, 76000.00),
(3, 1, 11, 'Aqua Air Mineral 600ml', 4000.00, 1, 4000.00),
(4, 2, 1, 'Beras Ramos Super 5kg', 70000.00, 2, 140000.00),
(5, 2, 3, 'Gula Pasir Gulaku Premium 1kg', 18000.00, 3, 54000.00),
(6, 2, 5, 'Telur Ayam Negeri 1kg', 28000.00, 2, 56000.00),
(7, 2, 25, 'Buku Tulis Sidu 38 Lembar Pack 10', 25000.00, 1, 25000.00),
(8, 3, 8, 'Chitato Sapi Panggang 68g', 12000.00, 3, 36000.00),
(9, 3, 10, 'SilverQueen Milk Chocolate 62g', 16500.00, 2, 33000.00),
(10, 3, 12, 'Pocari Sweat Botol 500ml', 8500.00, 2, 17000.00),
(11, 3, 9, 'Oreo Vanilla Sandwich 133g', 9000.00, 1, 9000.00),
(12, 4, 1, 'Beras Ramos Super 5kg', 70000.00, 2, 140000.00),
(13, 4, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 3, 114000.00),
(14, 4, 20, 'Shampoo Pantene Anti Ketombe 160ml', 26000.00, 1, 26000.00),
(15, 4, 21, 'Pasta Gigi Pepsodent Fresh Cool 190g', 20000.00, 2, 40000.00),
(16, 5, 1, 'Beras Ramos Super 5kg', 70000.00, 4, 280000.00),
(17, 5, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 3, 114000.00),
(18, 5, 5, 'Telur Ayam Negeri 1kg', 28000.00, 2, 56000.00),
(19, 6, 16, 'Kecap Manis Bango Pouch 520ml', 24000.00, 2, 48000.00),
(20, 6, 17, 'Saus Sambal ABC Botol 335ml', 17500.00, 2, 35000.00),
(21, 6, 22, 'Deterjen Rinso Molto Bubuk 770g', 21000.00, 2, 42000.00),
(22, 7, 1, 'Beras Ramos Super 5kg', 70000.00, 5, 350000.00),
(23, 7, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 4, 152000.00),
(24, 7, 3, 'Gula Pasir Gulaku Premium 1kg', 18000.00, 3, 54000.00),
(25, 7, 5, 'Telur Ayam Negeri 1kg', 27000.00, 2, 54000.00),
(26, 8, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 3, 114000.00),
(27, 8, 22, 'Deterjen Rinso Molto Bubuk 770g', 22000.00, 3, 66000.00),
(28, 9, 15, 'Kopi Nescafe Classic Jar 100g', 35000.00, 4, 140000.00),
(29, 9, 3, 'Gula Pasir Gulaku Premium 1kg', 18000.00, 5, 90000.00),
(30, 10, 1, 'Beras Ramos Super 5kg', 70000.00, 6, 420000.00),
(31, 10, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 2, 76000.00),
(32, 10, 23, 'Sunlight Pencuci Piring Jeruk Nipis 750ml', 22000.00, 2, 44000.00),
(33, 11, 6, 'Indomie Goreng Spesial 85g', 3200.00, 10, 32000.00),
(34, 11, 7, 'Indomie Kuah Ayam Bawang 69g', 3000.00, 10, 30000.00),
(35, 11, 11, 'Aqua Air Mineral 600ml', 4000.00, 5, 20000.00),
(36, 11, 19, 'Sabun Mandi Lifebuoy Total 10 85g', 3000.00, 1, 3000.00),
(37, 12, 1, 'Beras Ramos Super 5kg', 70000.00, 4, 280000.00),
(38, 12, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 2, 76000.00),
(39, 12, 21, 'Pasta Gigi Pepsodent Fresh Cool 190g', 17000.00, 2, 34000.00),
(40, 13, 1, 'Beras Ramos Super 5kg', 70000.00, 3, 210000.00),
(41, 14, 20, 'Shampoo Pantene Anti Ketombe 160ml', 26000.00, 3, 78000.00),
(42, 14, 21, 'Pasta Gigi Pepsodent Fresh Cool 190g', 17000.00, 3, 51000.00),
(43, 14, 23, 'Sunlight Pencuci Piring Jeruk Nipis 750ml', 23000.00, 2, 46000.00),
(44, 15, 1, 'Beras Ramos Super 5kg', 70000.00, 5, 350000.00),
(45, 15, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 2, 76000.00),
(46, 15, 4, 'Tepung Terigu Segitiga Biru 1kg', 13000.00, 4, 52000.00),
(47, 15, 18, 'Royco Kaldu Ayam Sachet 230g', 12000.00, 1, 12000.00),
(48, 16, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 2, 76000.00),
(49, 16, 21, 'Pasta Gigi Pepsodent Fresh Cool 190g', 17000.00, 2, 34000.00),
(50, 17, 1, 'Beras Ramos Super 5kg', 70000.00, 3, 210000.00),
(51, 17, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 2, 76000.00),
(52, 17, 22, 'Deterjen Rinso Molto Bubuk 770g', 22000.00, 2, 44000.00),
(53, 18, 1, 'Beras Ramos Super 5kg', 70000.00, 3, 210000.00),
(54, 18, 5, 'Telur Ayam Negeri 1kg', 28000.00, 2, 56000.00),
(55, 18, 13, 'Teh Botol Sosro Kotak 250ml', 4500.00, 4, 18000.00),
(56, 18, 14, 'Susu UHT Ultra Milk Coklat 250ml', 11000.00, 1, 11000.00),
(57, 19, 1, 'Beras Ramos Super 5kg', 70000.00, 4, 280000.00),
(58, 19, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 3, 114000.00),
(59, 19, 11, 'Aqua Air Mineral 600ml', 4000.00, 5, 20000.00),
(60, 19, 19, 'Sabun Mandi Lifebuoy Total 10 85g', 1000.00, 1, 1000.00),
(61, 20, 1, 'Beras Ramos Super 5kg', 70000.00, 2, 140000.00),
(62, 20, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 2, 76000.00),
(63, 20, 24, 'Super Pell Pembersih Lantai 770ml', 15000.00, 2, 30000.00),
(64, 20, 26, 'Bolpoint Standard AE7 Hitam Box 12', 14000.00, 1, 14000.00),
(65, 22, 11, 'Aqua Air Mineral 600ml', 4000.00, 3, 12000.00),
(66, 22, 3, 'Gula Pasir Gulaku Premium 1kg', 18000.00, 1, 18000.00),
(67, 22, 1, 'Beras Ramos Super 5kg', 70000.00, 1, 70000.00),
(68, 22, 25, 'Buku Tulis Sidu 38 Lembar Pack 10', 36000.00, 1, 36000.00),
(69, 23, 25, 'Buku Tulis Sidu 38 Lembar Pack 10', 36000.00, 1, 36000.00),
(70, 23, 3, 'Gula Pasir Gulaku Premium 1kg', 18000.00, 1, 18000.00),
(71, 24, 3, 'Gula Pasir Gulaku Premium 1kg', 18000.00, 1, 18000.00),
(72, 25, 3, 'Gula Pasir Gulaku Premium 1kg', 18000.00, 1, 18000.00),
(73, 26, 3, 'Gula Pasir Gulaku Premium 1kg', 18000.00, 1, 18000.00),
(74, 27, 6, 'Indomie Goreng Spesial 85g', 3200.00, 1, 3200.00),
(75, 28, 26, 'Bolpoint Standard AE7 Hitam Box 12', 28000.00, 1, 28000.00),
(76, 29, 25, 'Buku Tulis Sidu 38 Lembar Pack 10', 36000.00, 1, 36000.00),
(77, 30, 26, 'Bolpoint Standard AE7 Hitam Box 12', 28000.00, 1, 28000.00),
(78, 30, 25, 'Buku Tulis Sidu 38 Lembar Pack 10', 36000.00, 1, 36000.00),
(79, 30, 6, 'Indomie Goreng Spesial 85g', 3200.00, 2, 6400.00),
(80, 30, 1, 'Beras Ramos Super 5kg', 70000.00, 3, 210000.00),
(81, 30, 3, 'Gula Pasir Gulaku Premium 1kg', 18000.00, 1, 18000.00),
(82, 31, 11, 'Aqua Air Mineral 600ml', 4000.00, 1, 4000.00),
(83, 31, 1, 'Beras Ramos Super 5kg', 70000.00, 2, 140000.00),
(84, 31, 26, 'Bolpoint Standard AE7 Hitam Box 12', 28000.00, 1, 28000.00),
(85, 31, 25, 'Buku Tulis Sidu 38 Lembar Pack 10', 36000.00, 1, 36000.00),
(86, 31, 3, 'Gula Pasir Gulaku Premium 1kg', 18000.00, 1, 18000.00),
(87, 31, 22, 'Deterjen Rinso Molto Bubuk 770g', 22000.00, 1, 22000.00),
(88, 31, 16, 'Kecap Manis Bango Pouch 520ml', 24000.00, 1, 24000.00),
(89, 31, 15, 'Kopi Nescafe Classic Jar 100g', 35000.00, 1, 35000.00),
(90, 31, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 1, 38000.00),
(91, 32, 26, 'Bolpoint Standard AE7 Hitam Box 12', 28000.00, 1, 28000.00),
(92, 32, 1, 'Beras Ramos Super 5kg', 70000.00, 1, 70000.00),
(93, 33, 26, 'Bolpoint Standard AE7 Hitam Box 12', 28000.00, 5, 140000.00),
(94, 33, 1, 'Beras Ramos Super 5kg', 70000.00, 1, 70000.00),
(95, 33, 11, 'Aqua Air Mineral 600ml', 4000.00, 1, 4000.00),
(96, 33, 25, 'Buku Tulis Sidu 38 Lembar Pack 10', 36000.00, 1, 36000.00),
(97, 33, 6, 'Indomie Goreng Spesial 85g', 3200.00, 1, 3200.00),
(98, 33, 3, 'Gula Pasir Gulaku Premium 1kg', 18000.00, 1, 18000.00),
(99, 33, 22, 'Deterjen Rinso Molto Bubuk 770g', 22000.00, 1, 22000.00),
(100, 33, 16, 'Kecap Manis Bango Pouch 520ml', 24000.00, 1, 24000.00),
(101, 33, 15, 'Kopi Nescafe Classic Jar 100g', 35000.00, 1, 35000.00),
(102, 34, 26, 'Bolpoint Standard AE7 Hitam Box 12', 28000.00, 1, 28000.00),
(103, 34, 1, 'Beras Ramos Super 5kg', 70000.00, 2, 140000.00),
(104, 34, 25, 'Buku Tulis Sidu 38 Lembar Pack 10', 36000.00, 1, 36000.00),
(105, 34, 6, 'Indomie Goreng Spesial 85g', 3200.00, 1, 3200.00),
(106, 34, 3, 'Gula Pasir Gulaku Premium 1kg', 18000.00, 1, 18000.00),
(107, 34, 16, 'Kecap Manis Bango Pouch 520ml', 24000.00, 1, 24000.00),
(108, 34, 15, 'Kopi Nescafe Classic Jar 100g', 35000.00, 1, 35000.00),
(109, 34, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 1, 38000.00),
(110, 35, 1, 'Beras Ramos Super 5kg', 70000.00, 4, 280000.00),
(111, 35, 26, 'Bolpoint Standard AE7 Hitam Box 12', 28000.00, 2, 56000.00),
(112, 35, 25, 'Buku Tulis Sidu 38 Lembar Pack 10', 36000.00, 4, 144000.00),
(113, 35, 15, 'Kopi Nescafe Classic Jar 100g', 35000.00, 1, 35000.00),
(114, 35, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 1, 38000.00),
(115, 35, 20, 'Shampoo Pantene Anti Ketombe 160ml', 26000.00, 1, 26000.00),
(116, 36, 1, 'Beras Ramos Super 5kg', 70000.00, 3, 210000.00),
(117, 36, 26, 'Bolpoint Standard AE7 Hitam Box 12', 28000.00, 3, 84000.00),
(118, 36, 25, 'Buku Tulis Sidu 38 Lembar Pack 10', 36000.00, 2, 72000.00),
(119, 36, 15, 'Kopi Nescafe Classic Jar 100g', 35000.00, 1, 35000.00),
(120, 36, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 1, 38000.00),
(121, 36, 10, 'SilverQueen Milk Chocolate 62g', 16500.00, 1, 16500.00),
(122, 36, 11, 'Aqua Air Mineral 600ml', 4000.00, 1, 4000.00),
(123, 36, 8, 'Chitato Sapi Panggang 68g', 12000.00, 1, 12000.00),
(124, 37, 28, '8998866632287', 5000.00, 3, 15000.00),
(125, 37, 1, 'Beras Ramos Super 5kg', 70000.00, 23, 1610000.00),
(126, 37, 27, 'AQUVIVA', 4500.00, 2, 9000.00),
(127, 37, 11, 'Aqua Air Mineral 600ml', 4000.00, 1, 4000.00),
(128, 37, 26, 'Bolpoint Standard AE7 Hitam Box 12', 28000.00, 1, 28000.00),
(129, 37, 25, 'Buku Tulis Sidu 38 Lembar Pack 10', 36000.00, 2, 72000.00),
(130, 37, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 1, 38000.00),
(131, 37, 15, 'Kopi Nescafe Classic Jar 100g', 35000.00, 1, 35000.00),
(132, 37, 5, 'Telur Ayam Negeri 1kg', 28000.00, 1, 28000.00),
(133, 37, 8, 'Chitato Sapi Panggang 68g', 12000.00, 1, 12000.00),
(134, 37, 6, 'Indomie Goreng Spesial 85g', 3200.00, 1, 3200.00),
(135, 37, 7, 'Indomie Kuah Ayam Bawang 69g', 3000.00, 1, 3000.00),
(136, 37, 16, 'Kecap Manis Bango Pouch 520ml', 24000.00, 1, 24000.00),
(137, 37, 17, 'Saus Sambal ABC Botol 335ml', 17500.00, 7, 122500.00),
(138, 37, 19, 'Sabun Mandi Lifebuoy Total 10 85g', 4800.00, 1, 4800.00),
(139, 37, 23, 'Sunlight Pencuci Piring Jeruk Nipis 750ml', 18500.00, 1, 18500.00),
(140, 37, 10, 'SilverQueen Milk Chocolate 62g', 16500.00, 2, 33000.00),
(141, 37, 24, 'Super Pell Pembersih Lantai 770ml', 15000.00, 4, 60000.00),
(142, 38, 11, 'Aqua Air Mineral 600ml', 4000.00, 2, 8000.00),
(143, 38, 1, 'Beras Ramos Super 5kg', 70000.00, 21, 1470000.00),
(144, 38, 25, 'Buku Tulis Sidu 38 Lembar Pack 10', 36000.00, 5, 180000.00),
(145, 38, 22, 'Deterjen Rinso Molto Bubuk 770g', 22000.00, 5, 110000.00),
(146, 38, 16, 'Kecap Manis Bango Pouch 520ml', 24000.00, 5, 120000.00),
(147, 38, 2, 'Minyak Goreng Sania 2 Liter', 38000.00, 5, 190000.00),
(148, 38, 5, 'Telur Ayam Negeri 1kg', 28000.00, 5, 140000.00),
(149, 38, 15, 'Kopi Nescafe Classic Jar 100g', 35000.00, 2, 70000.00),
(150, 38, 18, 'Royco Kaldu Ayam Sachet 230g', 11500.00, 1, 11500.00),
(151, 38, 12, 'Pocari Sweat Botol 500ml', 8500.00, 1, 8500.00),
(152, 38, 19, 'Sabun Mandi Lifebuoy Total 10 85g', 4800.00, 1, 4800.00),
(153, 38, 17, 'Saus Sambal ABC Botol 335ml', 17500.00, 1, 17500.00),
(154, 38, 20, 'Shampoo Pantene Anti Ketombe 160ml', 26000.00, 1, 26000.00),
(155, 38, 10, 'SilverQueen Milk Chocolate 62g', 16500.00, 1, 16500.00),
(156, 38, 23, 'Sunlight Pencuci Piring Jeruk Nipis 750ml', 18500.00, 1, 18500.00),
(157, 38, 24, 'Super Pell Pembersih Lantai 770ml', 15000.00, 1, 15000.00),
(158, 38, 14, 'Susu UHT Ultra Milk Coklat 250ml', 7000.00, 1, 7000.00),
(159, 38, 13, 'Teh Botol Sosro Kotak 250ml', 4500.00, 1, 4500.00),
(160, 38, 4, 'Tepung Terigu Segitiga Biru 1kg', 13000.00, 1, 13000.00),
(161, 38, 27, 'AQUVIVA', 4500.00, 1, 4500.00),
(162, 38, 28, '8998866632287', 5000.00, 1, 5000.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_kategori`
--

CREATE TABLE `tbl_kategori` (
  `id_kategori` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `ikon` varchar(50) DEFAULT 'bi-tag'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_kategori`
--

INSERT INTO `tbl_kategori` (`id_kategori`, `nama_kategori`, `ikon`) VALUES
(1, 'Sembako & Kebutuhan Pokok', 'bi-basket2-fill'),
(2, 'Makanan & Snack', 'bi-egg-fried'),
(3, 'Minuman Segar', 'bi-cup-straw'),
(4, 'Bumbu Dapur & Masak', 'bi-fire'),
(5, 'Perawatan Tubuh & Mandi', 'bi-heart-pulse-fill'),
(6, 'Kebersihan Rumah Tangga', 'bi-shield-check'),
(7, 'Alat Tulis Kantor (ATK)', 'bi-pencil-fill');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_pelanggan`
--

CREATE TABLE `tbl_pelanggan` (
  `id_pelanggan` int(11) NOT NULL,
  `nama_pelanggan` varchar(100) DEFAULT NULL,
  `kota` varchar(50) DEFAULT NULL,
  `telepon` varchar(25) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `no_kartu` varchar(50) DEFAULT NULL,
  `tipe_member` enum('Regular','Silver','Gold','Platinum') DEFAULT 'Silver',
  `diskon_member` decimal(5,2) DEFAULT 0.00,
  `poin` int(11) DEFAULT 0,
  `saldo` decimal(12,2) DEFAULT 0.00,
  `status_kartu` enum('aktif','nonaktif','blokir') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_pelanggan`
--

INSERT INTO `tbl_pelanggan` (`id_pelanggan`, `nama_pelanggan`, `kota`, `telepon`, `alamat`, `no_kartu`, `tipe_member`, `diskon_member`, `poin`, `saldo`, `status_kartu`) VALUES
(1, 'Andi Saputra', 'Jakarta', NULL, NULL, 'MBR-88012301', 'Platinum', 10.00, 350, 250000.00, 'aktif'),
(2, 'Budi Santoso', 'Surabaya', NULL, NULL, 'MBR-88012302', 'Gold', 5.00, 180, 120000.00, 'aktif'),
(3, 'Citra Lestari', 'Bandung', NULL, NULL, 'MBR-88012303', 'Silver', 2.00, 95, 50000.00, 'aktif'),
(4, 'Dewi Anggraini', 'Yogyakarta', NULL, NULL, 'MBR-88012304', 'Gold', 5.00, 220, 150000.00, 'aktif'),
(5, 'Eko Prasetyo', 'Semarang', NULL, NULL, 'MBR-88012305', 'Silver', 2.00, 120, 35000.00, 'aktif'),
(6, 'Fajar Nugroho', 'Malang', NULL, NULL, 'MBR-88012306', 'Platinum', 10.00, 410, 300000.00, 'aktif'),
(7, 'Gita Permata', 'Medan', NULL, NULL, 'MBR-88012307', 'Regular', 0.00, 50, 0.00, 'aktif'),
(8, 'Hendra Wijaya', 'Makassar', NULL, NULL, 'MBR-88012308', 'Gold', 5.00, 190, 80000.00, 'aktif'),
(9, 'Indah Kusuma', 'Denpasar', NULL, NULL, 'MBR-88012309', 'Regular', 0.00, 40, 25000.00, 'aktif'),
(10, 'Joko Susilo', 'Solo', NULL, NULL, 'MBR-88012310', 'Silver', 2.00, 110, 50000.00, 'aktif'),
(11, 'Siti Rahma', 'Surabaya', NULL, NULL, NULL, 'Silver', 0.00, 0, 0.00, 'aktif'),
(12, 'Eko Santoso', 'Sukun', '0812-8888-9999', 'Jl. Merdeka Raya No. 45, Jawa Timur', '0008493873', 'Platinum', 10.00, 130, 200000.00, 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_pengaturan`
--

CREATE TABLE `tbl_pengaturan` (
  `id` int(11) NOT NULL DEFAULT 1,
  `nama_toko` varchar(100) NOT NULL DEFAULT 'AGSMART STORE',
  `slogan` varchar(150) DEFAULT 'Pusat Belanja Hemat & Terpercaya',
  `alamat` text DEFAULT 'Jl. Merdeka Raya No. 45, Sentra Niaga',
  `telepon` varchar(50) DEFAULT '0812-9876-5432',
  `email` varchar(100) DEFAULT 'kontak@agsmart.id',
  `pesan_struk` text DEFAULT 'Terima kasih atas kunjungan Anda!\nBarang yang sudah dibeli tidak dapat ditukar kecuali ada perjanjian.',
  `footer_struk` text DEFAULT 'Simpan struk ini sebagai bukti pembayaran yang sah.',
  `pajak_persen` decimal(5,2) DEFAULT 0.00,
  `lebar_struk` varchar(10) DEFAULT '58mm'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_pengaturan`
--

INSERT INTO `tbl_pengaturan` (`id`, `nama_toko`, `slogan`, `alamat`, `telepon`, `email`, `pesan_struk`, `footer_struk`, `pajak_persen`, `lebar_struk`) VALUES
(1, 'AGSMART', 'Supermarket  dan Toko Modern', 'Jl. Merdeka Raya No. 45, Jawa Timur', '0812-8888-9999', 'admin@agsmart.com', 'Terima kasih telah berbelanja di AGSMART MART!', 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.', 0.00, '58mm');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_produk`
--

CREATE TABLE `tbl_produk` (
  `id_produk` int(11) NOT NULL,
  `kode_produk` varchar(50) NOT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `id_kategori` int(11) DEFAULT NULL,
  `satuan` varchar(30) DEFAULT 'pcs',
  `harga_beli` decimal(12,2) NOT NULL DEFAULT 0.00,
  `harga_jual` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stok` int(11) NOT NULL DEFAULT 0,
  `stok_minimal` int(11) NOT NULL DEFAULT 5,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_produk`
--

INSERT INTO `tbl_produk` (`id_produk`, `kode_produk`, `nama_produk`, `id_kategori`, `satuan`, `harga_beli`, `harga_jual`, `stok`, `stok_minimal`, `gambar`, `created_at`, `updated_at`) VALUES
(1, 'BRG-001', 'Beras Ramos Super 5kg', 1, 'sak', 62000.00, 70000.00, 0, 10, NULL, '2026-11-16 09:46:54', '2026-11-16 06:17:33'),
(2, 'BRG-002', 'Minyak Goreng Sania 2 Liter', 1, 'pouch', 33000.00, 38000.00, 50, 15, NULL, '2026-11-16 09:46:54', '2026-11-16 06:17:33'),
(3, 'BRG-003', 'Gula Pasir Gulaku Premium 1kg', 1, 'kg', 15000.00, 18000.00, 71, 20, NULL, '2026-11-16 09:46:54', '2026-11-16 11:13:22'),
(4, 'BRG-004', 'Tepung Terigu Segitiga Biru 1kg', 1, 'kg', 10500.00, 13000.00, 49, 15, NULL, '2026-11-16 09:46:54', '2026-11-16 06:17:33'),
(5, 'BRG-005', 'Telur Ayam Negeri 1kg', 1, 'kg', 24000.00, 28000.00, 34, 10, NULL, '2026-11-16 09:46:54', '2026-11-16 06:17:33'),
(6, 'BRG-006', 'Indomie Goreng Spesial 85g', 2, 'bungkus', 2600.00, 3200.00, 144, 30, NULL, '2026-11-16 09:46:54', '2026-11-16 06:15:43'),
(7, 'BRG-007', 'Indomie Kuah Ayam Bawang 69g', 2, 'bungkus', 2500.00, 3000.00, 119, 25, NULL, '2026-11-16 09:46:54', '2026-11-16 06:15:43'),
(8, 'BRG-008', 'Chitato Sapi Panggang 68g', 2, 'bungkus', 9500.00, 12000.00, 38, 10, NULL, '2026-11-16 09:46:54', '2026-11-16 06:15:43'),
(9, 'BRG-009', 'Oreo Vanilla Sandwich 133g', 2, 'bungkus', 8500.00, 10500.00, 55, 10, NULL, '2026-11-16 09:46:54', '2026-11-16 09:46:54'),
(10, 'BRG-010', 'SilverQueen Milk Chocolate 62g', 2, 'pcs', 13000.00, 16500.00, 31, 10, NULL, '2026-11-16 09:46:54', '2026-11-16 06:17:33'),
(11, 'BRG-011', 'Aqua Air Mineral 600ml', 3, 'botol', 2800.00, 4000.00, 86, 24, NULL, '2026-11-16 09:46:54', '2026-11-16 06:17:33'),
(12, 'BRG-012', 'Pocari Sweat Botol 500ml', 3, 'botol', 6500.00, 8500.00, 49, 15, NULL, '2026-11-16 09:46:54', '2026-11-16 06:17:33'),
(13, 'BRG-013', 'Teh Botol Sosro Kotak 250ml', 3, 'kotak', 3200.00, 4500.00, 69, 20, NULL, '2026-11-16 09:46:54', '2026-11-16 06:17:33'),
(14, 'BRG-014', 'Susu UHT Ultra Milk Coklat 250ml', 3, 'kotak', 5500.00, 7000.00, 64, 15, NULL, '2026-11-16 09:46:54', '2026-11-16 06:17:33'),
(15, 'BRG-015', 'Kopi Nescafe Classic Jar 100g', 3, 'jar', 28000.00, 35000.00, 17, 5, NULL, '2026-11-16 09:46:54', '2026-11-16 06:17:33'),
(16, 'BRG-016', 'Kecap Manis Bango Pouch 520ml', 4, 'pouch', 20000.00, 24000.00, 36, 10, NULL, '2026-11-16 09:46:54', '2026-11-16 06:17:33'),
(17, 'BRG-017', 'Saus Sambal ABC Botol 335ml', 4, 'botol', 14000.00, 17500.00, 22, 8, NULL, '2026-11-16 09:46:54', '2026-11-16 06:17:33'),
(18, 'BRG-018', 'Royco Kaldu Ayam Sachet 230g', 4, 'pouch', 9000.00, 11500.00, 49, 10, NULL, '2026-11-16 09:46:54', '2026-11-16 06:17:33'),
(19, 'BRG-019', 'Sabun Mandi Lifebuoy Total 10 85g', 5, 'batang', 3500.00, 4800.00, 78, 20, NULL, '2026-11-16 09:46:54', '2026-11-16 06:17:33'),
(20, 'BRG-020', 'Shampoo Pantene Anti Ketombe 160ml', 5, 'botol', 21000.00, 26000.00, 28, 5, NULL, '2026-11-16 09:46:54', '2026-11-16 06:17:33'),
(21, 'BRG-021', 'Pasta Gigi Pepsodent Fresh Cool 190g', 5, 'tube', 13500.00, 17000.00, 45, 10, NULL, '2026-11-16 09:46:54', '2026-11-16 09:46:54'),
(22, 'BRG-022', 'Deterjen Rinso Molto Bubuk 770g', 6, 'bungkus', 17500.00, 22000.00, 33, 10, NULL, '2026-11-16 09:46:54', '2026-11-16 06:17:33'),
(23, 'BRG-023', 'Sunlight Pencuci Piring Jeruk Nipis 750ml', 6, 'pouch', 14500.00, 18500.00, 53, 12, NULL, '2026-11-16 09:46:54', '2026-11-16 06:17:33'),
(24, 'BRG-024', 'Super Pell Pembersih Lantai 770ml', 6, 'pouch', 12000.00, 15000.00, 30, 8, NULL, '2026-11-16 09:46:54', '2026-11-16 06:17:33'),
(25, 'BRG-025', 'Buku Tulis Sidu 38 Lembar Pack 10', 7, 'pack', 28000.00, 36000.00, 5, 5, NULL, '2026-11-16 09:46:54', '2026-11-16 06:17:33'),
(26, 'BRG-026', 'Bolpoint Standard AE7 Hitam Box 12', 7, 'kotak', 22000.00, 28000.00, 14, 5, NULL, '2026-11-16 09:46:54', '2026-11-16 06:15:43'),
(27, '(90)MD122882003700438', 'AQUVIVA', 3, 'pcs', 4000.00, 4500.00, 97, 5, NULL, '2026-11-16 06:10:39', '2026-11-16 06:17:33'),
(28, '8998866632287', '8998866632287', 3, 'pcs', 4500.00, 5000.00, 96, 5, NULL, '2026-11-16 06:13:02', '2026-11-16 06:17:33'),
(29, '9786231808707', 'BUKU BENGKEL', 7, 'pcs', 400000.00, 500000.00, 10, 5, NULL, '2026-11-16 06:23:29', '2026-11-16 06:23:56');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_transaksi`
--

CREATE TABLE `tbl_transaksi` (
  `id_transaksi` int(11) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `id_pelanggan` int(11) DEFAULT NULL,
  `total_belanja` decimal(12,2) DEFAULT NULL,
  `no_faktur` varchar(50) DEFAULT NULL,
  `waktu` time DEFAULT '10:00:00',
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `diskon` decimal(12,2) DEFAULT 0.00,
  `pajak` decimal(12,2) DEFAULT 0.00,
  `bayar` decimal(12,2) DEFAULT 0.00,
  `kembalian` decimal(12,2) DEFAULT 0.00,
  `metode_pembayaran` varchar(30) DEFAULT 'Tunai',
  `kasir` varchar(50) DEFAULT 'Kasir 1',
  `catatan` varchar(255) DEFAULT NULL,
  `poin_didapat` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_transaksi`
--

INSERT INTO `tbl_transaksi` (`id_transaksi`, `tanggal`, `id_pelanggan`, `total_belanja`, `no_faktur`, `waktu`, `subtotal`, `diskon`, `pajak`, `bayar`, `kembalian`, `metode_pembayaran`, `kasir`, `catatan`, `poin_didapat`) VALUES
(1, '2026-11-01', 1, 150000.00, 'TRX-20261101-0001', '10:00:00', 150000.00, 0.00, 0.00, 150000.00, 0.00, 'Tunai', 'Kasir 1', NULL, 0),
(2, '2026-11-01', 2, 275000.00, 'TRX-20261101-0002', '10:00:00', 275000.00, 0.00, 0.00, 275000.00, 0.00, 'Tunai', 'Kasir 1', NULL, 0),
(3, '2026-11-02', 3, 95000.00, 'TRX-20261102-0003', '10:00:00', 95000.00, 0.00, 0.00, 95000.00, 0.00, 'QRIS', 'Kasir 1', NULL, 0),
(4, '2026-11-02', 1, 320000.00, 'TRX-20261102-0004', '10:00:00', 320000.00, 0.00, 0.00, 320000.00, 0.00, 'Transfer Bank', 'Kasir 1', NULL, 0),
(5, '2026-11-03', 4, 450000.00, 'TRX-20261103-0005', '10:00:00', 450000.00, 0.00, 0.00, 450000.00, 0.00, 'Tunai', 'Kasir 1', NULL, 0),
(6, '2026-11-03', 5, 125000.00, 'TRX-20261103-0006', '10:00:00', 125000.00, 0.00, 0.00, 125000.00, 0.00, 'QRIS', 'Kasir 1', NULL, 0),
(7, '2026-11-04', 6, 610000.00, 'TRX-20261104-0007', '10:00:00', 610000.00, 0.00, 0.00, 610000.00, 0.00, 'Tunai', 'Kasir 1', NULL, 0),
(8, '2026-11-04', 2, 180000.00, 'TRX-20261104-0008', '10:00:00', 180000.00, 0.00, 0.00, 180000.00, 0.00, 'Transfer Bank', 'Kasir 1', NULL, 0),
(9, '2026-11-05', 7, 230000.00, 'TRX-20261105-0009', '10:00:00', 230000.00, 0.00, 0.00, 230000.00, 0.00, 'QRIS', 'Kasir 1', NULL, 0),
(10, '2026-11-05', 8, 540000.00, 'TRX-20261105-0010', '10:00:00', 540000.00, 0.00, 0.00, 540000.00, 0.00, 'Tunai', 'Kasir 1', NULL, 0),
(11, '2026-11-06', 9, 85000.00, 'TRX-20261106-0011', '10:00:00', 85000.00, 0.00, 0.00, 85000.00, 0.00, 'Tunai', 'Kasir 1', NULL, 0),
(12, '2026-11-06', 10, 390000.00, 'TRX-20261106-0012', '10:00:00', 390000.00, 0.00, 0.00, 390000.00, 0.00, 'QRIS', 'Kasir 1', NULL, 0),
(13, '2026-11-07', 3, 210000.00, 'TRX-20261107-0013', '10:00:00', 210000.00, 0.00, 0.00, 210000.00, 0.00, 'Tunai', 'Kasir 1', NULL, 0),
(14, '2026-11-07', 4, 175000.00, 'TRX-20261107-0014', '10:00:00', 175000.00, 0.00, 0.00, 175000.00, 0.00, 'Tunai', 'Kasir 1', NULL, 0),
(15, '2026-11-08', 5, 490000.00, 'TRX-20261108-0015', '10:00:00', 490000.00, 0.00, 0.00, 490000.00, 0.00, 'QRIS', 'Kasir 1', NULL, 0),
(16, '2026-11-08', 1, 110000.00, 'TRX-20261108-0016', '10:00:00', 110000.00, 0.00, 0.00, 110000.00, 0.00, 'Transfer Bank', 'Kasir 1', NULL, 0),
(17, '2026-11-09', 6, 330000.00, 'TRX-20261109-0017', '10:00:00', 330000.00, 0.00, 0.00, 330000.00, 0.00, 'Tunai', 'Kasir 1', NULL, 0),
(18, '2026-11-09', 7, 295000.00, 'TRX-20261109-0018', '10:00:00', 295000.00, 0.00, 0.00, 295000.00, 0.00, 'QRIS', 'Kasir 1', NULL, 0),
(19, '2026-11-10', 8, 415000.00, 'TRX-20261110-0019', '10:00:00', 415000.00, 0.00, 0.00, 415000.00, 0.00, 'Tunai', 'Kasir 1', NULL, 0),
(20, '2026-11-10', 10, 260000.00, 'TRX-20261110-0020', '10:00:00', 260000.00, 0.00, 0.00, 260000.00, 0.00, 'Transfer Bank', 'Kasir 1', NULL, 0),
(21, '2026-11-15', 1, 150000.00, 'TRX-20261115-0021', '10:00:00', 150000.00, 0.00, 0.00, 150000.00, 0.00, 'QRIS', 'Kasir 1', NULL, 0),
(22, '2026-11-16', NULL, 150960.00, 'TRX-20261116-0001', '03:49:55', 136000.00, 0.00, 14960.00, 200000.00, 49040.00, 'Tunai', 'Kasir 1', '', 0),
(23, '2026-11-16', NULL, 54000.00, 'TRX-20261116-0002', '03:54:16', 54000.00, 0.00, 0.00, 100000.00, 46000.00, 'Tunai', 'Kasir 1', '', 0),
(24, '2026-11-16', NULL, 18000.00, 'TRX-20261116-0003', '03:58:13', 18000.00, 0.00, 0.00, 20000.00, 2000.00, 'Tunai', 'Kasir 1', '', 0),
(25, '2026-11-16', NULL, 18000.00, 'TRX-20261116-0004', '04:06:28', 18000.00, 0.00, 0.00, 20000.00, 2000.00, 'Tunai', 'Kasir 1', '', 0),
(26, '2026-11-16', NULL, 18000.00, 'TRX-20261116-0005', '04:11:05', 18000.00, 0.00, 0.00, 20000.00, 2000.00, 'Tunai', 'Kasir 1', '', 0),
(27, '2026-11-16', NULL, 3200.00, 'TRX-20261116-0006', '04:38:47', 3200.00, 0.00, 0.00, 3200.00, 0.00, 'Tunai', 'Kasir 1', '', 0),
(28, '2026-11-16', NULL, 28000.00, 'TRX-20261116-0007', '04:41:03', 28000.00, 0.00, 0.00, 50000.00, 22000.00, 'Tunai', 'Kasir 1', '', 0),
(29, '2026-11-16', NULL, 36000.00, 'TRX-20261116-0008', '04:53:26', 36000.00, 0.00, 0.00, 50000.00, 14000.00, 'Tunai', 'Kasir 1', '', 0),
(30, '2026-11-16', NULL, 298400.00, 'TRX-20261116-0009', '04:59:43', 298400.00, 0.00, 0.00, 300000.00, 1600.00, 'Tunai', 'Kasir 1', '', 0),
(31, '2026-11-16', NULL, 345000.00, 'TRX-20261116-0010', '05:02:08', 345000.00, 0.00, 0.00, 345000.00, 0.00, 'Tunai', 'Kasir 1', '', 0),
(32, '2026-11-16', 12, 88200.00, 'TRX-20261116-0011', '05:09:20', 98000.00, 9800.00, 0.00, 88200.00, 0.00, 'Tunai', 'Kasir 1', '', 8),
(33, '2026-11-16', NULL, 352200.00, 'TRX-20261116-0012', '05:12:40', 352200.00, 0.00, 0.00, 352200.00, 0.00, 'QRIS', 'Kasir 1', '', 0),
(34, '2026-11-16', 12, 289980.00, 'TRX-20261116-0013', '05:13:22', 322200.00, 32220.00, 0.00, 300000.00, 10020.00, 'Tunai', 'Kasir 1', '', 28),
(35, '2026-11-16', 12, 521100.00, 'TRX-20261116-0014', '05:22:42', 579000.00, 57900.00, 0.00, 550000.00, 28900.00, 'Tunai', 'Kasir 1', '', 52),
(36, '2026-11-16', 12, 424350.00, 'TRX-20261116-0015', '05:27:15', 471500.00, 47150.00, 0.00, 450000.00, 25650.00, 'Tunai', 'Kasir 1', '', 42),
(37, '2026-11-16', NULL, 2120000.00, 'TRX-20261116-0016', '07:15:43', 2120000.00, 0.00, 0.00, 2150000.00, 30000.00, 'Tunai', 'Kasir 1', '', 0),
(38, '2026-11-16', NULL, 2440300.00, 'TRX-20261116-0017', '07:17:33', 2440300.00, 0.00, 0.00, 2440300.00, 0.00, 'Tunai', 'Kasir 1', '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_user`
--

CREATE TABLE `tbl_user` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','kasir') DEFAULT 'kasir',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_user`
--

INSERT INTO `tbl_user` (`id_user`, `username`, `password`, `nama_lengkap`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$wE9o42Q6i4p1bA9yv23oQOaG5737Z6l9Qv3oO68w6wM86yM86yM86', 'Administrator', 'admin', '2026-11-16 09:20:54'),
(2, 'kasir1', '$2y$10$wE9o42Q6i4p1bA9yv23oQOaG5737Z6l9Qv3oO68w6wM86yM86yM86', 'Siti Rahma (Kasir 1)', 'kasir', '2026-11-16 09:20:54');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_laporan_transaksi`
-- (See below for the actual view)
--
CREATE TABLE `v_laporan_transaksi` (
`id_transaksi` int(11)
,`no_faktur` varchar(50)
,`tanggal` date
,`waktu` time
,`nama_pelanggan` varchar(100)
,`kota` varchar(50)
,`no_kartu` varchar(50)
,`tipe_member` enum('Regular','Silver','Gold','Platinum')
,`metode_pembayaran` varchar(30)
,`total_belanja` decimal(12,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_produk_terlaris`
-- (See below for the actual view)
--
CREATE TABLE `v_produk_terlaris` (
`id_produk` int(11)
,`kode_produk` varchar(50)
,`nama_produk` varchar(150)
,`nama_kategori` varchar(100)
,`harga_jual` decimal(12,2)
,`stok` int(11)
,`total_terjual` decimal(32,0)
,`total_pendapatan` decimal(34,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_total_belanja_pelanggan`
-- (See below for the actual view)
--
CREATE TABLE `v_total_belanja_pelanggan` (
`id_pelanggan` int(11)
,`nama_pelanggan` varchar(100)
,`kota` varchar(50)
,`telepon` varchar(25)
,`no_kartu` varchar(50)
,`tipe_member` enum('Regular','Silver','Gold','Platinum')
,`poin` int(11)
,`saldo` decimal(12,2)
,`jumlah_transaksi` bigint(21)
,`total_keseluruhan_belanja` decimal(34,2)
);

-- --------------------------------------------------------

--
-- Structure for view `v_laporan_transaksi`
--
DROP TABLE IF EXISTS `v_laporan_transaksi`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_laporan_transaksi`  AS SELECT `t`.`id_transaksi` AS `id_transaksi`, `t`.`no_faktur` AS `no_faktur`, `t`.`tanggal` AS `tanggal`, `t`.`waktu` AS `waktu`, `p`.`nama_pelanggan` AS `nama_pelanggan`, `p`.`kota` AS `kota`, `p`.`no_kartu` AS `no_kartu`, `p`.`tipe_member` AS `tipe_member`, `t`.`metode_pembayaran` AS `metode_pembayaran`, `t`.`total_belanja` AS `total_belanja` FROM (`tbl_transaksi` `t` join `tbl_pelanggan` `p` on(`t`.`id_pelanggan` = `p`.`id_pelanggan`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_produk_terlaris`
--
DROP TABLE IF EXISTS `v_produk_terlaris`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_produk_terlaris`  AS SELECT `p`.`id_produk` AS `id_produk`, `p`.`kode_produk` AS `kode_produk`, `p`.`nama_produk` AS `nama_produk`, `k`.`nama_kategori` AS `nama_kategori`, `p`.`harga_jual` AS `harga_jual`, `p`.`stok` AS `stok`, coalesce(sum(`d`.`qty`),0) AS `total_terjual`, coalesce(sum(`d`.`subtotal`),0) AS `total_pendapatan` FROM ((`tbl_produk` `p` left join `tbl_kategori` `k` on(`p`.`id_kategori` = `k`.`id_kategori`)) left join `tbl_detail_transaksi` `d` on(`p`.`id_produk` = `d`.`id_produk`)) GROUP BY `p`.`id_produk`, `p`.`kode_produk`, `p`.`nama_produk`, `k`.`nama_kategori`, `p`.`harga_jual`, `p`.`stok` ORDER BY coalesce(sum(`d`.`qty`),0) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_total_belanja_pelanggan`
--
DROP TABLE IF EXISTS `v_total_belanja_pelanggan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_total_belanja_pelanggan`  AS SELECT `p`.`id_pelanggan` AS `id_pelanggan`, `p`.`nama_pelanggan` AS `nama_pelanggan`, `p`.`kota` AS `kota`, `p`.`telepon` AS `telepon`, `p`.`no_kartu` AS `no_kartu`, `p`.`tipe_member` AS `tipe_member`, `p`.`poin` AS `poin`, `p`.`saldo` AS `saldo`, count(`t`.`id_transaksi`) AS `jumlah_transaksi`, coalesce(sum(`t`.`total_belanja`),0) AS `total_keseluruhan_belanja` FROM (`tbl_pelanggan` `p` left join `tbl_transaksi` `t` on(`p`.`id_pelanggan` = `t`.`id_pelanggan`)) GROUP BY `p`.`id_pelanggan`, `p`.`nama_pelanggan`, `p`.`kota`, `p`.`telepon`, `p`.`no_kartu`, `p`.`tipe_member`, `p`.`poin`, `p`.`saldo` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `log_pelanggan_baru`
--
ALTER TABLE `log_pelanggan_baru`
  ADD PRIMARY KEY (`id_log`);

--
-- Indexes for table `log_pelanggan_update`
--
ALTER TABLE `log_pelanggan_update`
  ADD PRIMARY KEY (`id_log`);

--
-- Indexes for table `tbl_detail_transaksi`
--
ALTER TABLE `tbl_detail_transaksi`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_transaksi` (`id_transaksi`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indexes for table `tbl_kategori`
--
ALTER TABLE `tbl_kategori`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indexes for table `tbl_pelanggan`
--
ALTER TABLE `tbl_pelanggan`
  ADD PRIMARY KEY (`id_pelanggan`),
  ADD UNIQUE KEY `no_kartu` (`no_kartu`);

--
-- Indexes for table `tbl_pengaturan`
--
ALTER TABLE `tbl_pengaturan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_produk`
--
ALTER TABLE `tbl_produk`
  ADD PRIMARY KEY (`id_produk`),
  ADD UNIQUE KEY `kode_produk` (`kode_produk`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- Indexes for table `tbl_transaksi`
--
ALTER TABLE `tbl_transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_pelanggan` (`id_pelanggan`);

--
-- Indexes for table `tbl_user`
--
ALTER TABLE `tbl_user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `log_pelanggan_baru`
--
ALTER TABLE `log_pelanggan_baru`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `log_pelanggan_update`
--
ALTER TABLE `log_pelanggan_update`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tbl_detail_transaksi`
--
ALTER TABLE `tbl_detail_transaksi`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT for table `tbl_kategori`
--
ALTER TABLE `tbl_kategori`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tbl_pelanggan`
--
ALTER TABLE `tbl_pelanggan`
  MODIFY `id_pelanggan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tbl_produk`
--
ALTER TABLE `tbl_produk`
  MODIFY `id_produk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `tbl_transaksi`
--
ALTER TABLE `tbl_transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `tbl_user`
--
ALTER TABLE `tbl_user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_detail_transaksi`
--
ALTER TABLE `tbl_detail_transaksi`
  ADD CONSTRAINT `fk_detail_produk` FOREIGN KEY (`id_produk`) REFERENCES `tbl_produk` (`id_produk`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_detail_transaksi` FOREIGN KEY (`id_transaksi`) REFERENCES `tbl_transaksi` (`id_transaksi`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_produk`
--
ALTER TABLE `tbl_produk`
  ADD CONSTRAINT `fk_produk_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `tbl_kategori` (`id_kategori`) ON DELETE SET NULL;

--
-- Constraints for table `tbl_transaksi`
--
ALTER TABLE `tbl_transaksi`
  ADD CONSTRAINT `tbl_transaksi_ibfk_1` FOREIGN KEY (`id_pelanggan`) REFERENCES `tbl_pelanggan` (`id_pelanggan`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
