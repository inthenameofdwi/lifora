-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 21, 2026 at 07:39 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_lifora`
--

-- --------------------------------------------------------

--
-- Table structure for table `antrean`
--

CREATE TABLE `antrean` (
  `id` int NOT NULL,
  `no_antrean` varchar(10) NOT NULL,
  `nama_pasien` varchar(100) NOT NULL,
  `no_rm` varchar(20) NOT NULL,
  `poli_tujuan` varchar(50) NOT NULL,
  `dokter` varchar(100) NOT NULL,
  `status_layanan` varchar(20) DEFAULT 'Menunggu',
  `waktu_daftar` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `antrean`
--

INSERT INTO `antrean` (`id`, `no_antrean`, `nama_pasien`, `no_rm`, `poli_tujuan`, `dokter`, `status_layanan`, `waktu_daftar`) VALUES
(1, 'A-042', 'Bambang Hartono', 'RM-2024-008912', 'Poli Umum', 'dr. Siti Rahmawati', 'Dilayani', '2026-10-17 06:17:07'),
(2, 'G-018', 'Laras Sekar Putri', 'RM-2023-010478', 'Poli Gigi', 'drg. Hendra Pratama', 'Dilayani', '2026-10-17 06:17:07'),
(3, 'A-043', 'Ny. Nurhayati', 'RM-2022-004319', 'Poli Umum', 'dr. Siti Rahmawati', 'Menunggu', '2026-10-17 06:17:07'),
(4, 'K-021', 'An. Dimas Arfian', 'RM-2024-009115', 'Poli KIA', 'Bdn. Ratna Anjani', 'Menunggu', '2026-10-17 06:17:07'),
(5, 'L-009', 'Kakek Sastro W.', 'RM-2021-001209', 'Poli Lansia', 'dr. Rahmat Hidayat', 'Selesai', '2026-10-17 06:17:07');

-- --------------------------------------------------------

--
-- Table structure for table `dokter`
--

CREATE TABLE `dokter` (
  `id` int NOT NULL,
  `nama_dokter` varchar(150) NOT NULL,
  `gelar_spesialis` varchar(100) DEFAULT '',
  `nip_sip` varchar(50) NOT NULL,
  `status_satusehat` varchar(50) DEFAULT 'Terkoneksi SatuSehat',
  `poli` varchar(50) NOT NULL,
  `hari_praktik` varchar(100) DEFAULT '',
  `jam_praktik` varchar(50) DEFAULT '',
  `kuota_harian` int DEFAULT '30',
  `kuota_terisi` int DEFAULT '0',
  `status_tugas` enum('Aktif Bertugas','Sedang Cuti','Non-Aktif') DEFAULT 'Aktif Bertugas',
  `keterangan_status` varchar(100) DEFAULT '',
  `foto_avatar` varchar(255) DEFAULT '',
  `username` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dokter`
--

INSERT INTO `dokter` (`id`, `nama_dokter`, `gelar_spesialis`, `nip_sip`, `status_satusehat`, `poli`, `hari_praktik`, `jam_praktik`, `kuota_harian`, `kuota_terisi`, `status_tugas`, `keterangan_status`, `foto_avatar`, `username`) VALUES
(1, 'dr. Siti Rahmawati, Sp.PD', 'Dokter Spesialis Penyakit Dalam', '19850412 201101 2 003', 'Terkoneksi SatuSehat', 'Poli Umum', 'Senin - Jumat', '08.00 - 14.00 WIB', 40, 32, 'Non-Aktif', '', 'Siti+Rahmawati', 'siti'),
(2, 'drg. Hendra Pratama', 'Dokter Gigi Umum', '19890214 201402 1 002', 'Terkoneksi SatuSehat', 'Poli Gigi & Mulut', 'Senin, Rabu, Kamis', '08.30 - 13.00 WIB', 20, 18, 'Aktif Bertugas', '', 'Hendra+Pratama', NULL),
(3, 'dr. Rahmat Hidayat', 'Dokter Pelayanan Geriatri', '19780521 200501 1 004', 'Terkoneksi SatuSehat', 'Poli Lansia / Geriatri', 'Selasa, Kamis, Sabtu', '08.00 - 12.00 WIB', 25, 15, 'Aktif Bertugas', '', 'Rahmat+Hidayat', NULL),
(4, 'Bdn. Ratna Anjani, S.Tr.Keb', 'Bidan Koordinator KIA', '19920819 201803 2 007', 'Terkoneksi SatuSehat', 'Poli KIA & Imunisasi', 'Senin - Jumat', '08.00 - 14.30 WIB', 30, 0, 'Sedang Cuti', 'Pengganti: Bdn. Dewi (s/d 27 Okt)', 'Ratna+Anjani', NULL),
(6, 'dr.Nisa.Sp.DP', 'Dokter Jaga Gawat Darurat', '19910405 201602 1 008', 'Terkoneksi SatuSehat', 'Poli Umum', 'Senin', '08-13.00', 7, 0, 'Aktif Bertugas', '', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` int NOT NULL,
  `judul` varchar(100) NOT NULL,
  `deskripsi` text NOT NULL,
  `waktu` varchar(20) NOT NULL,
  `tipe_log` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id`, `judul`, `deskripsi`, `waktu`, `tipe_log`) VALUES
(1, 'Update SOAP & Terapi', 'dr. Siti Rahmawati memperbarui diagnosis ICD-10 (J06.9) untuk pasien RM-2024-008912.', '09:41 WIB', 'soap'),
(2, 'Registrasi Pasien Baru BPJS', 'Admin Loket 2 (Budi Santoso) mendaftarkan NIK 32732454**** atas nama Dewi Sartika ke Poli Gigi.', '09:37 WIB', 'registrasi'),
(3, 'Resep Elektronik Selesai', 'Apoteker apt. Faisal Reza menyerahkan obat & menutup berkas RM-2021-001209.', '09:29 WIB', 'resep'),
(4, 'Klaim BPJS Terverifikasi', 'Sistem e-Puskesmas mengesahkan bridging SEP untuk 14 berkas rawat jalan batch #04.', '09:14 WIB', 'klaim');

-- --------------------------------------------------------

--
-- Table structure for table `pasien`
--

CREATE TABLE `pasien` (
  `id` int NOT NULL,
  `no_rm` varchar(30) NOT NULL,
  `nik_identitas` varchar(20) NOT NULL,
  `jenis_identitas` varchar(50) DEFAULT 'E-KTP Terverifikasi',
  `nama_lengkap` varchar(150) NOT NULL,
  `inisial` varchar(5) DEFAULT '',
  `tempat_lahir` varchar(100) DEFAULT '',
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') NOT NULL,
  `nama_ibu_kandung` varchar(150) DEFAULT '',
  `no_hp` varchar(20) DEFAULT '',
  `alamat_ktp` text,
  `rt` varchar(10) DEFAULT '',
  `rw` varchar(10) DEFAULT '',
  `kode_pos` varchar(10) DEFAULT '',
  `jenis_penjamin` varchar(50) NOT NULL DEFAULT 'BPJS Kesehatan (PBI)',
  `no_kartu_bpjs` varchar(30) DEFAULT '',
  `faskes_asal` varchar(100) DEFAULT 'Puskesmas Melati Sejahtera',
  `nama_penanggung_jawab` varchar(150) DEFAULT '',
  `hubungan_penanggung_jawab` varchar(50) DEFAULT '',
  `no_telp_darurat` varchar(20) DEFAULT '',
  `gol_darah` varchar(10) DEFAULT '-',
  `catatan_khusus` varchar(100) DEFAULT '',
  `usia` int DEFAULT '0',
  `kategori_usia` varchar(20) DEFAULT 'Dewasa',
  `jenis_jaminan` varchar(50) DEFAULT 'BPJS Kesehatan',
  `nomor_kartu` varchar(50) DEFAULT '-',
  `domisili_wilayah` text,
  `kunjungan_terakhir` date DEFAULT NULL,
  `poli_terakhir` varchar(50) DEFAULT 'Poli Umum'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pasien`
--

INSERT INTO `pasien` (`id`, `no_rm`, `nik_identitas`, `jenis_identitas`, `nama_lengkap`, `inisial`, `tempat_lahir`, `tanggal_lahir`, `jenis_kelamin`, `nama_ibu_kandung`, `no_hp`, `alamat_ktp`, `rt`, `rw`, `kode_pos`, `jenis_penjamin`, `no_kartu_bpjs`, `faskes_asal`, `nama_penanggung_jawab`, `hubungan_penanggung_jawab`, `no_telp_darurat`, `gol_darah`, `catatan_khusus`, `usia`, `kategori_usia`, `jenis_jaminan`, `nomor_kartu`, `domisili_wilayah`, `kunjungan_terakhir`, `poli_terakhir`) VALUES
(1, 'RM-2024-00192', '3201121508820003', 'E-KTP Terverifikasi', 'Budi Santoso', 'BS', '', NULL, 'Laki-laki', '', '', NULL, '', '', '', 'BPJS Kesehatan (PBI)', '', 'Puskesmas Melati Sejahtera', '', '', '', 'O+', '', 42, 'Dewasa', 'BPJS Kesehatan', '0001827491021', 'RT 03 / RW 04<br><span class=\"text-slate-400\">Desa Melati Asri</span>', '2024-10-22', 'Poli Umum'),
(2, 'RM-2024-00193', '3201125203910001', 'E-KTP Terverifikasi', 'Siti Aminah', 'SA', '', NULL, 'Perempuan', '', '', NULL, '', '', '', 'BPJS Kesehatan (PBI)', '', 'Puskesmas Melati Sejahtera', '', '', '', 'B+', '', 33, 'Dewasa', 'BPJS Kesehatan', '0002918471924', 'RT 01 / RW 02<br><span class=\"text-slate-400\">Desa Sukamaju</span>', '2024-10-23', 'Poli Gigi'),
(3, 'RM-2024-00194', '3201120401560002', 'E-KTP Terverifikasi', 'Djoko Haryanto', 'DH', '', NULL, 'Laki-laki', '', '', NULL, '', '', '', 'BPJS Kesehatan (PBI)', '', 'Puskesmas Melati Sejahtera', '', '', '', '-', 'Alergi: Penisilin', 68, 'Lansia', 'Umum / Mandiri', 'Bayar Langsung', 'RW 07<br><span class=\"text-slate-400\">Kelurahan Cibubur Kulon</span>', '2024-10-24', 'Poli Lansia'),
(4, 'RM-2024-00195', '3201126811180004', 'KIA / Kartu Anak', 'Anisa Putri Zahra', 'AP', '', NULL, 'Perempuan', '', '', NULL, '', '', '', 'BPJS Kesehatan (PBI)', '', 'Puskesmas Melati Sejahtera', '', '', '', '-', 'Anak dr. Ny. Rina', 6, 'Anak', 'BPJS Kesehatan', '0005481729012', 'RT 04 / RW 01<br><span class=\"text-slate-400\">Desa Melati Asri</span>', '2024-10-18', 'Poli KIA / Imunisasi'),
(5, 'RM-2024-00196', '3201121104880005', 'E-KTP Terverifikasi', 'Rian Pratama', 'RP', '', NULL, 'Laki-laki', '', '', NULL, '', '', '', 'BPJS Kesehatan (PBI)', '', 'Puskesmas Melati Sejahtera', '', '', '', 'A+', '', 36, 'Dewasa', 'Asuransi Lain', 'Prudential / 991204', 'Kompleks Griya Asri C-12<br><span class=\"text-slate-400\">Kecamatan Cileungsi</span>', '2024-10-15', 'Poli Umum'),
(6, 'RM-2024-00197', '3201124507520001', 'E-KTP Terverifikasi', 'Sri Mulyati', 'SM', '', NULL, 'Perempuan', '', '', NULL, '', '', '', 'BPJS Kesehatan (PBI)', '', 'Puskesmas Melati Sejahtera', '', '', '', '-', 'Prolanis Hipertensi', 72, 'Lansia', 'BPJS Kesehatan', '0001726354189', 'RT 02 / RW 03<br><span class=\"text-slate-400\">Desa Karang Mekar</span>', '2024-10-24', 'Poli Lansia'),
(7, 'RM-2024-00198', '3201121908950007', 'E-KTP Terverifikasi', 'Fajar Wahyudi', 'FW', '', NULL, 'Laki-laki', '', '', NULL, '', '', '', 'BPJS Kesehatan (PBI)', '', 'Puskesmas Melati Sejahtera', '', '', '', 'AB-', '', 29, 'Dewasa', 'BPJS Kesehatan', '0003891029481', 'RT 05 / RW 06<br><span class=\"text-slate-400\">Desa Melati Asri</span>', '2024-10-19', 'Poli Umum'),
(8, 'RM-2026-000008', '3245667898765432', 'E-KTP Terverifikasi', 'Dian Nuraeni', 'DN', 'Bandung', '2008-01-14', 'Perempuan', 'Ani', '081398765737', 'JL anggrek no 12', '02', '14', '13495', 'BPJS Kesehatan (PBI)', '0009876543245', 'Puskesmas Melati Sejahtera', 'Heli', 'Heli', '081654727828', '-', '', 18, 'Dewasa', 'BPJS Kesehatan', '0009876543245', 'RT 02 / RW 14<br><span class=\"text-slate-400\">JL anggrek no 12</span>', '2026-10-19', 'Poli Umum');

-- --------------------------------------------------------

--
-- Table structure for table `rekam_medis`
--

CREATE TABLE `rekam_medis` (
  `id` int NOT NULL,
  `no_registrasi` varchar(30) NOT NULL,
  `waktu_kunjungan` datetime NOT NULL,
  `no_rm` varchar(30) NOT NULL,
  `nama_pasien` varchar(120) NOT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `jenis_kelamin` char(1) NOT NULL DEFAULT 'L',
  `usia` int NOT NULL DEFAULT '0',
  `jaminan` varchar(40) NOT NULL DEFAULT 'Umum / Mandiri',
  `poli` varchar(60) NOT NULL,
  `dokter` varchar(100) NOT NULL,
  `sip_dokter` varchar(40) DEFAULT NULL,
  `kode_icd` varchar(12) NOT NULL,
  `nama_diagnosis` varchar(150) NOT NULL,
  `keterangan_klinis` varchar(255) DEFAULT NULL,
  `status_berkas` varchar(40) NOT NULL DEFAULT 'Menunggu Validasi Farmasi',
  `ket_status` varchar(120) DEFAULT NULL,
  `satusehat` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `rekam_medis`
--

INSERT INTO `rekam_medis` (`id`, `no_registrasi`, `waktu_kunjungan`, `no_rm`, `nama_pasien`, `nik`, `jenis_kelamin`, `usia`, `jaminan`, `poli`, `dokter`, `sip_dokter`, `kode_icd`, `nama_diagnosis`, `keterangan_klinis`, `status_berkas`, `ket_status`, `satusehat`, `created_at`) VALUES
(1, 'REG-20261021-0089', '2026-10-21 09:15:00', 'RM-2021-09412', 'Bambang Sugianto', '3201121503660002', 'L', 58, 'BPJS PBI', 'Poli Umum', 'dr. Siti Rahmawati', '19850412-2011', 'I10', 'Hipertensi Primer', 'Tekanan Darah: 150/95 mmHg,', 'Lengkap & Terkunci', 'TTE Sah • dr. Siti', 1, '2026-10-21 07:39:52'),
(2, 'REG-20261021-0092', '2026-10-21 09:22:00', 'RM-2023-11029', 'Dewi Lestari', '3201124508000004', 'P', 24, 'BPJS Non-PBI', 'Poli Umum', 'dr. Siti Rahmawati', '19850412-2011', 'J06.9', 'ISPA Akut', 'Batuk berdahak 4 hari, demam', 'Menunggu Validasi Farmasi', 'Resep E-Prescribing dikirim', 1, '2026-10-21 07:39:52'),
(3, 'REG-20261021-0078', '2026-10-21 08:45:00', 'RM-2022-04188', 'An. Rayhan Pratama', '-', 'L', 3, 'Umum / Mandiri', 'Poli KIA & Imunisasi', 'dr. Budi Santoso, Sp.A', '19780918-2006', 'A09', 'Gastroenteritis Akut', 'Diare 3x sehari cair tanpa lendir', 'Selesai Ditandatangani', 'Resume Terbit 09:05 WIB', 1, '2026-10-21 07:39:52'),
(4, 'REG-20261021-0062', '2026-10-21 08:10:00', 'RM-2019-00341', 'Siti Aminah', '3201125201610001', 'P', 63, 'BPJS PBI', 'Poli Lansia / Geriatri', 'dr. Nur Hasanah', '19890214-2016', 'E11.9', 'Diabetes Melitus Tipe 2', 'GDS: 215 mg/dL, edukasi pola makan', 'Lengkap & Terkunci', 'TTE Sah • dr. Nur H.', 1, '2026-10-21 07:39:52'),
(5, 'REG-20261021-0051', '2026-10-21 07:55:00', 'RM-2024-00120', 'Wahyu Hidayat', '3201121004930005', 'L', 31, 'BPJS Non-PBI', 'Poli Gigi & Mulut', 'drg. Andini Permata', '19910520-2018', 'K02.1', 'Karies Dentin', 'Nyeri ngilu gigi geraham bawah', 'Selesai Ditandatangani', 'TTE Sah • drg. Andini', 1, '2026-10-21 07:39:52');

-- --------------------------------------------------------

--
-- Table structure for table `rekam_medis_log`
--

CREATE TABLE `rekam_medis_log` (
  `id` int NOT NULL,
  `rekam_id` int NOT NULL,
  `aksi` varchar(30) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `oleh` varchar(100) DEFAULT NULL,
  `waktu` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `role` enum('admin','dokter') DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`) VALUES
(1, 'admin_rekammedis', 'Puskesmas2025#', 'Admin Rekam Medis', 'admin'),
(2, 'dr_siti', 'Puskesmas2025#', 'dr. Siti Rahmawati', 'dokter');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `antrean`
--
ALTER TABLE `antrean`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dokter`
--
ALTER TABLE `dokter`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pasien`
--
ALTER TABLE `pasien`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_rm` (`no_rm`);

--
-- Indexes for table `rekam_medis`
--
ALTER TABLE `rekam_medis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_registrasi` (`no_registrasi`);

--
-- Indexes for table `rekam_medis_log`
--
ALTER TABLE `rekam_medis_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rekam_id` (`rekam_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `antrean`
--
ALTER TABLE `antrean`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `dokter`
--
ALTER TABLE `dokter`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pasien`
--
ALTER TABLE `pasien`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `rekam_medis`
--
ALTER TABLE `rekam_medis`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `rekam_medis_log`
--
ALTER TABLE `rekam_medis_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
