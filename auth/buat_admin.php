<?php
// INI CUMA FILE SEMENTARA, buka sekali di browser terus bikin akun admin yang pertama
// terus HAPUS file ini, soalnya kalo dibiarin, orang lain bisa ikut membuat admin.
// ini file buat bikin akun bos kos pertama aja
include "../includes/koneksi.php";

// ini kaya data admin pertama. terus ganti passwordnya kalo udah login
$nama = "Administrator";
$email = "adminkos@gmail.com";
$kata_sandi_asli = "admin123";

//buat ngecek db nya , email ini udah dipake buat bikin admin belum
//Ibaratnya: nulis surat perintah buat satpam, tapi bagian nama emailnya dikosongin dulu pakai tanda tanya (?).
$cek = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ?");
//bind_param itu singkatan dari bind parameter = "nempel-in isi ke tanda tanya (?)"
// S = string 
// Ibaratnya: Kamu ngasih secarik kertas berisi tulisan adminkos@gmail.com ke satpam untuk ditempel ke bagian tanda tanya (?) tadi.
mysqli_stmt_bind_param($cek, "s", $email);
// Perintah pencariannya baru benar-benar dijalankan ke database di baris ini.
mysqli_stmt_execute($cek);
// Ibaratnya: Satpam balik lagi bawa hasil pencariannya ke kamu.
$hasil_cek = mysqli_stmt_get_result($cek);

//Hitung hasil cek tadi: ketemu berapa orang yang pakai email ini?
//Kalau lebih dari 0 (> 0), berarti adminnya SUDAH ADA.
if (mysqli_num_rows($hasil_cek) > 0) {
    echo "Admin sudah ada. Hapus bfile buat_admin.php sekarang.";
    exit; // Stop di sini! Nggak usah lanjut nyimpen biar nggak dobel.
}

// Kalau belum ada, password asli ("admin123") diacak dulu jadi kode rumit/acak.
// Tujuannya biar orang yang ngintip database nggak langsung baca password aslinya.
$kata_sandi_hash = password_hash($kata_sandi_asli, PASSWORD_DEFAULT);
//Siapin formulir simpan data baru ke tabel users, tapi 3 isiannya dikosongin dulu pakai tanda tanya (?)
//(nama, email, password). Sedangkan role langsung diisi 'admin'.
$perintah = mysqli_prepare($koneksi, "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'admin')");
//Tempelin 3 isi tadi ke 3 tanda tanya (?) berurutan.
//"sss" artinya ketiganya berupa teks (string-string-string).
mysqli_stmt_bind_param($perintah, "sss", $nama, $email, $kata_sandi_hash);
//baru beneran nyimpen datanya ke db
mysqli_stmt_execute($perintah);

echo "Admin berhasil dibuat.<br>Email: " . $email . "<br>Password: " . $kata_sandi_asli;
echo "<br><br>Sekarang hapus file buat_admin.php.";
?>