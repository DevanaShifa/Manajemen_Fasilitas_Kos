<?php
// nyalain session dulu, biar bisa dihapus
session_start();

// kosongin semua isi login (nama, peran, id user, dll)
session_unset();
// ngehancurin atau matiin ingatan loginnya sekalian biar bener2 logout
session_destroy();

//balik ke halaman login sambil bawa pesan "sudah keluar"
header("Location: login.php?pesan=logout");
exit;
?>