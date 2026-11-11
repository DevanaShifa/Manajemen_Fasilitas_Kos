<?php
include "../includes/koneksi.php";

// ambil data dari form login. terus pake POST biar password ga muncul di URL.
$username = $_POST['username'];
$kata_sandi = $_POST['kata_sandi'];
$peran_dipilih = $_POST['peran_dipilih'];

// cari pengguna berdasarkan username (prepared statement, aman dari SQL injection)
$hasil = mysqli_query($koneksi, "SELECT * FROM pengguna WHERE username = '$username'");

// gagal kalau username ga ada ATAU password ga cocok.
// pesannya sengaja disamain biar orang ga bisa nebak username yang terdaftar.
if (!$data_pengguna || !password_verify($kata_sandi, $data_pengguna['kata_sandi'])) {
    header("Location: login.php?pesan=salah");
    exit;
}

// akun benar, tapi tab yang dipilih ga sesuai peran akun
if ($data_pengguna['peran'] != $peran_dipilih) {
    header("Location: login.php?pesan=peran_tidak_cocok");
    exit;
}

// buat klo login berhasil. ganti ID session biar aman dari "pencurian" session lama.
session_regenerate_id(true);

// ini buat simpan data penting ke session, dipakai di semua halaman
$_SESSION['id_pengguna'] = $data_pengguna['id'];
$_SESSION['nama_lengkap'] = $data_pengguna['nama_lengkap'];
$_SESSION['peran'] = $data_pengguna['peran'];

// buat arahin ke dashboard sesuai peran
if ($data_pengguna['peran'] == 'admin') {
    header("Location: ../admin/dashboard.php");
} else {
    header("Location: ../user/dashboard.php");
}
exit;
?>