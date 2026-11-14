<?php
// nyalain session dulu, biar bisa dihapus
session_start();

// hapus semua data login (nama, peran, dan lainnya)
session_unset();
session_destroy();

//balik ke halaman login sambil bawa pesan "sudah keluar"
header("Location: login.php?pesan=logout");
exit;
?>