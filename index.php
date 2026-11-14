<?php
// Pintu masuk web. Siapa pun yang membuka folder kosan/ akan dibelokkan ke sini.
include "includes/koneksi.php";

// Sudah login? Arahkan ke dashboard sesuai perannya.
if (isset($_SESSION['peran'])) {
    if ($_SESSION['peran'] == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit;
}

// Belum login? Arahkan ke halaman login.
header("Location: auth/login.php");
exit;
?>