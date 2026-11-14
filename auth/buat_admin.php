<?php
// INI CUMA FILE SEMENTARA, buka sekali di browser terus bikin akun admin yang pertama
// terus HAPUS file ini, soalnya kalo dibiarin, orang lain bisa ikut membuat admin.
include "../includes/koneksi.php";

// ini kaya data admin pertama. terus ganti passwordnya kalo udah login
$nama = "Administrator";
$email = "admin@kos.com";
$kata_sandi_asli = "admin123";


$cek = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($cek, "s", $email);
mysqli_stmt_execute($cek);
$hasil_cek = mysqli_stmt_get_result($cek);

if (mysqli_num_rows($hasil_cek) > 0) {
    echo "Admin sudah ada. Hapus file buat_admin.php sekarang.";
    exit;
}

// password diacak terus simpen di akun admin
$kata_sandi_hash = password_hash($kata_sandi_asli, PASSWORD_DEFAULT);
$perintah = mysqli_prepare($koneksi, "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'admin')");
mysqli_stmt_bind_param($perintah, "sss", $nama, $email, $kata_sandi_hash);
mysqli_stmt_execute($perintah);

echo "Admin berhasil dibuat.<br>Email: " . $email . "<br>Password: " . $kata_sandi_asli;
echo "<br><br>Sekarang hapus file buat_admin.php.";
?>