<?php
// ini kaya satpam buat halaman yg cuma boleh dibuka orang tertentu
// cara pakainya, tulis di paling atas halaman:
//     wajib_login('admin', '../auth/login.php');

// buat mastiin session udah nyala, klo belum nyalain dlu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// fungsi ini tugasnya buat meriksa tiket masuk orang yang mau buka halaman
// $peran_yang_diizinkan = siapa yg boleh masuk ('admin' atau 'penghuni')
// $alamat_login         = arah jalan menuju halaman login kalau ditolak.
function wajib_login($peran_yang_diizinkan, $alamat_login)
{
    // kalo belum login sama sekali? suruh login dulu.
    if (!isset($_SESSION['id_pengguna'])) {
        header("Location: " . $alamat_login . "?pesan=harus_login");
        exit; //buat stop kode di bawahnya biar halaman rahasia nggak ikut kebuka
    }

    // kalo udah login tapi perannya ga cocok (misal penghuni buka halaman admin)? Tolak.
    if ($_SESSION['peran'] != $peran_yang_diizinkan) {
        header("Location: " . $alamat_login . "?pesan=tidak_berhak");
        exit; //stop kode dibawahnya juga
    }
}
?>