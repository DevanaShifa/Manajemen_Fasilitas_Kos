<?php
$hostname = "localhost";
$username = "root";
$password = "";
$database = "kos_ndut";

$koneksi = mysqli_connect($hostname, $username, $password, $database);

//klo koneksinya gagal nyambung, berhenti programnya dan tampilin alasan gagalnya
if (!$koneksi) {
    die("Koneksi Gagal: " . mysqli_connect_error());
}

// nyalakan session di sini, karena semua file memanggil koneksi.php
// jadi status login tu bisa dikenali di berbagai halaman
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>