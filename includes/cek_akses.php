<?php
// File ini berisi "satpam" untuk halaman yang hanya boleh dibuka orang tertentu.
// Cara pakai, tulis di paling atas halaman:
//     wajib_login('admin', '../auth/login.php');

// Pastikan session sudah menyala
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// $peran_yang_diizinkan = 'admin' atau 'penghuni'
// $alamat_login         = letak login.php dilihat dari halaman yang memanggil
function wajib_login($peran_yang_diizinkan, $alamat_login)
{
    // Belum login sama sekali? Suruh login dulu.
    if (!isset($_SESSION['id_pengguna'])) {
        header("Location: " . $alamat_login . "?pesan=harus_login");
        exit;
    }

    // Sudah login tapi perannya tidak cocok (misal penghuni buka halaman admin)? Tolak.
    if ($_SESSION['peran'] != $peran_yang_diizinkan) {
        header("Location: " . $alamat_login . "?pesan=tidak_berhak");
        exit;
    }
}
?>