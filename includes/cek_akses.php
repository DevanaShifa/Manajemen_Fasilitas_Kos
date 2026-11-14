<?php
// ini kaya satpam buat halaman yg cuma boleh dibuka orang tertentu
// cara pakainya, tulis di paling atas halaman:
//     wajib_login('admin', '../auth/login.php');

// buat mastiin session udah nyala
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// $peran_yang_diizinkan = 'admin' atau 'penghuni'
// $alamat_login         = letak login.php dilihat dari halaman yang memanggil
function wajib_login($peran_yang_diizinkan, $alamat_login)
{
    // kalo belum login sama sekali? suruh login dulu.
    if (!isset($_SESSION['id_pengguna'])) {
        header("Location: " . $alamat_login . "?pesan=harus_login");
        exit;
    }

    // kalo udah login tapi perannya ga cocok (misal penghuni buka halaman admin)? Tolak.
    if ($_SESSION['peran'] != $peran_yang_diizinkan) {
        header("Location: " . $alamat_login . "?pesan=tidak_berhak");
        exit;
    }
}
?>