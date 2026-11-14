<?php
// File ini tidak punya tampilan. Tugasnya memeriksa data login lalu mengarahkan pengguna.
include "../includes/koneksi.php";

// Kalau file ini dibuka langsung lewat alamat web (bukan dari formulir), kembalikan ke login
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: login.php");
    exit;
}

// Ambil isi formulir login
$email = trim($_POST['email']);
$kata_sandi = $_POST['kata_sandi'];
$peran_dipilih = $_POST['peran_dipilih'];

// Cari akun yang emailnya sama. Tanda ? akan diisi nilai asli di baris berikutnya,
// supaya yang diketik pengguna tidak bisa dipakai untuk merusak database.
$perintah = mysqli_prepare($koneksi, "SELECT * FROM users WHERE email = ?");
mysqli_stmt_bind_param($perintah, "s", $email);
mysqli_stmt_execute($perintah);
$hasil = mysqli_stmt_get_result($perintah);
$data_pengguna = mysqli_fetch_assoc($hasil);

// Gagal kalau email tidak ketemu ATAU password tidak cocok.
// Pesannya sengaja dibuat sama supaya orang tidak bisa menebak email mana yang terdaftar.
if (!$data_pengguna || !password_verify($kata_sandi, $data_pengguna['password'])) {
    header("Location: login.php?pesan=salah");
    exit;
}

// Akun benar, tapi tombol peran yang dipilih tidak sesuai
if ($data_pengguna['role'] != $peran_dipilih) {
    header("Location: login.php?pesan=peran_tidak_cocok");
    exit;
}

// Login berhasil. Ganti nomor session supaya lebih aman.
session_regenerate_id(true);

// Catat data penting di session, nanti dipakai di semua halaman
$id_pengguna = $data_pengguna['id'];
$_SESSION['id_pengguna'] = $id_pengguna;
$_SESSION['nama'] = $data_pengguna['nama'];
$_SESSION['peran'] = $data_pengguna['role'];

// Kalau penghuni, catat juga nomor data dirinya (dibutuhkan untuk tagihan dan keluhan)
if ($data_pengguna['role'] == 'penghuni') {
    $cari_penghuni = mysqli_prepare($koneksi, "SELECT id FROM tenants WHERE user_id = ?");
    mysqli_stmt_bind_param($cari_penghuni, "i", $id_pengguna);
    mysqli_stmt_execute($cari_penghuni);
    $data_penghuni = mysqli_fetch_assoc(mysqli_stmt_get_result($cari_penghuni));
    $_SESSION['id_penghuni'] = $data_penghuni ? $data_penghuni['id'] : 0;
}

// Arahkan ke dashboard sesuai peran
if ($data_pengguna['role'] == 'admin') {
    header("Location: ../admin/dashboard.php");
} else {
    header("Location: ../user/dashboard.php");
}
exit;
?>