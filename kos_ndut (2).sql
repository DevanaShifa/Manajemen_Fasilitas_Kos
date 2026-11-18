-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 18 Nov 2026 pada 21.22
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kos_ndut`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `isi` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `complains`
--

CREATE TABLE `complains` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `judul` varchar(150) NOT NULL,
  `deskripsi` text NOT NULL,
  `fasilitas_id` int(11) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('diterima','diproses','selesai') NOT NULL DEFAULT 'diterima',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `fasilities`
--

CREATE TABLE `fasilities` (
  `id` int(11) NOT NULL,
  `nama_fasilitas` varchar(100) NOT NULL,
  `jenis` enum('kamar','umum') NOT NULL,
  `kondisi` enum('baik','rusak','perbaikan') NOT NULL DEFAULT 'baik',
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `fasilities`
--

INSERT INTO `fasilities` (`id`, `nama_fasilitas`, `jenis`, `kondisi`, `keterangan`) VALUES
(1, 'Dapur', 'umum', 'baik', 'Dapur bersama, silahkan dipakai dan dirawat bersama');

-- --------------------------------------------------------

--
-- Struktur dari tabel `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `rental_id` int(11) NOT NULL,
  `bulan` varchar(20) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `jatuh_tempo` date DEFAULT NULL,
  `catatan_admin` text DEFAULT NULL,
  `tanggal_bayar` date DEFAULT NULL,
  `bukti` varchar(255) DEFAULT NULL,
  `status` enum('belum_bayar','menunggu','lunas','telat','ditolak') NOT NULL DEFAULT 'belum_bayar'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `payments`
--

INSERT INTO `payments` (`id`, `rental_id`, `bulan`, `jumlah`, `jatuh_tempo`, `catatan_admin`, `tanggal_bayar`, `bukti`, `status`) VALUES
(1, 1, 'november 2026', 800000, '2026-11-21', NULL, '2026-11-18', 'bukti_1_2ade363b2100.jpg', 'lunas');

-- --------------------------------------------------------

--
-- Struktur dari tabel `rentals`
--

CREATE TABLE `rentals` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `tanggal_masuk` date NOT NULL,
  `tanggal_keluar` date DEFAULT NULL,
  `status` enum('aktif','selesai') NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `rentals`
--

INSERT INTO `rentals` (`id`, `tenant_id`, `room_id`, `tanggal_masuk`, `tanggal_keluar`, `status`) VALUES
(1, 1, 2, '2026-11-18', NULL, 'aktif');

-- --------------------------------------------------------

--
-- Struktur dari tabel `rental_request`
--

CREATE TABLE `rental_request` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `room_id` int(11) NOT NULL,
  `tanggal_survei` date DEFAULT NULL,
  `pesan` text DEFAULT NULL,
  `status` enum('baru','dihubungi','selesai') NOT NULL DEFAULT 'baru'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `no_kamar` varchar(10) NOT NULL,
  `tipe` varchar(50) NOT NULL,
  `harga` int(11) NOT NULL,
  `ukuran` varchar(50) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `foto` varchar(500) DEFAULT NULL,
  `status` enum('kosong','terisi','perbaikan') NOT NULL DEFAULT 'kosong'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `rooms`
--

INSERT INTO `rooms` (`id`, `no_kamar`, `tipe`, `harga`, `ukuran`, `deskripsi`, `foto`, `status`) VALUES
(2, 'A1', 'AC, Kamar mandi dalam', 800000, '3x4 m', '', NULL, 'terisi');

-- --------------------------------------------------------

--
-- Struktur dari tabel `room_fasilities`
--

CREATE TABLE `room_fasilities` (
  `room_id` int(11) NOT NULL,
  `facility_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tenants`
--

CREATE TABLE `tenants` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `pekerjaan` varchar(100) DEFAULT NULL,
  `alamat_asal` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tenants`
--

INSERT INTO `tenants` (`id`, `user_id`, `nama_lengkap`, `no_hp`, `pekerjaan`, `alamat_asal`) VALUES
(1, 1, 'hihu', '08123456789121', NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `role` enum('admin','penghuni') NOT NULL DEFAULT 'penghuni'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`) VALUES
(1, 'hihu', 'hihu@gmail.com', '$2y$10$gkSX7gRc3Im6MeSSYfBDJeurRgV.M10nz16l3KrVFzTxGH9E.unKa', 'penghuni'),
(2, 'Administrator', 'adminkos@gmail.com', '$2y$10$kNec2slpRf7/8ahfI79qOOetJxIKLvgNB.pURpYRZ6jFU2Au8SSEq', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `complains`
--
ALTER TABLE `complains`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `fasilities`
--
ALTER TABLE `fasilities`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `rentals`
--
ALTER TABLE `rentals`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `rental_request`
--
ALTER TABLE `rental_request`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `room_fasilities`
--
ALTER TABLE `room_fasilities`
  ADD PRIMARY KEY (`room_id`,`facility_id`);

--
-- Indeks untuk tabel `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `complains`
--
ALTER TABLE `complains`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `fasilities`
--
ALTER TABLE `fasilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `rentals`
--
ALTER TABLE `rentals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `rental_request`
--
ALTER TABLE `rental_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
