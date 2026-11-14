<?php
// FILE SEMENTARA. Buka sekali di browser untuk membuat akun admin pertama,
// lalu HAPUS file ini. Kalau dibiarkan, orang lain bisa ikut membuat admin.
include "../includes/koneksi.php";

// Data admin pertama. Ganti password-nya setelah login nanti.
$nama = "Administrator";
$email = "admin@kos.com";
$kata_sandi_asli = "admin123";

// Cek dulu: jangan buat dua kali
$cek = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($cek, "s", $email);
mysqli_stmt_execute($cek);
$hasil_cek = mysqli_stmt_get_result($cek);

if (mysqli_num_rows($hasil_cek) > 0) {
    echo "Admin sudah ada. Hapus file buat_admin.php sekarang.";
    exit;
}

// Acak password lalu simpan akun admin
$kata_sandi_hash = password_hash($kata_sandi_asli, PASSWORD_DEFAULT);
$perintah = mysqli_prepare($koneksi, "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'admin')");
mysqli_stmt_bind_param($perintah, "sss", $nama, $email, $kata_sandi_hash);
mysqli_stmt_execute($perintah);

echo "Admin berhasil dibuat.<br>Email: " . $email . "<br>Password: " . $kata_sandi_asli;
echo "<br><br>Sekarang hapus file buat_admin.php.";
?>