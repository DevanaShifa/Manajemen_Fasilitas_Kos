<?php
session_start();      // wajib dipanggil dulu supaya bisa menghapus session yang aktif
session_unset();      // kosongkan semua isi session
session_destroy();    // hancurkan session-nya
header("Location: login.php?pesan=logout");
exit;
?>