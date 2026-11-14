<?php
// Nyalakan session dulu, supaya bisa dihapus
session_start();

// Hapus semua data login (nama, peran, dan lainnya)
session_unset();
session_destroy();

// Kembali ke halaman login sambil membawa pesan "sudah keluar"
header("Location: login.php?pesan=logout");
exit;
?>