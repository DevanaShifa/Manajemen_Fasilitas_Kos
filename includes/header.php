<?php
// Bagian atas halaman (judul web, menu, tombol keluar).
// Dipakai halaman di folder admin/ dan user/, jadi alamat di bawah memakai ../
//
// Sebelum memanggil file ini, isi dulu:  $judul_halaman = "Nama Halaman";

// Pakai jam Indonesia (WIB)
date_default_timezone_set("Asia/Jakarta");

// Tanggal hari ini dalam bahasa Indonesia, dipakai di banner dashboard
$nama_hari = array("Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu");
$nama_bulan = array(1 => "Januari", "Februari", "Maret", "April", "Mei", "Juni",
                    "Juli", "Agustus", "September", "Oktober", "November", "Desember");
$tanggal_hari_ini = $nama_hari[date("w")] . ", " . date("j") . " " . $nama_bulan[date("n")] . " " . date("Y");

// Nama file halaman yang sedang dibuka, untuk menandai menu yang sedang aktif
$file_sekarang = basename($_SERVER['PHP_SELF']);

// Daftar menu. Satu menu = array(tulisan menu, nama file tujuan).
// Mau menambah menu baru? Tambah satu baris array di bagian yang sesuai.
if ($_SESSION['peran'] == 'admin') {
    $tulisan_peran = "Admin";
        $daftar_menu = array(
        array("Dashboard", "dashboard.php"),
        array("Fasilitas & Keluhan", "fasilitas.php")
    );
} else {
    $tulisan_peran = "Penghuni";
    $daftar_menu = array(
        array("Dashboard", "dashboard.php"),
        array("Fasilitas & Keluhan", "fasilitas.php")
    );
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($judul_halaman); ?> - Kos Ndut</title>
        <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body<?php echo !empty($pakai_hero) ? ' class="halaman-hero"' : ''; ?>>
    <header class="bar-atas" id="barAtas">
        <span class="merek">Kos Ndut</span>

        <!-- Tombol hamburger, hanya muncul di HP -->
        <button type="button" class="tombol-menu" id="tombolMenu" aria-label="Buka menu">&#9776;</button>

        <!-- Bagian yang terlipat di HP -->
        <div class="lipatan" id="lipatan">
            <nav class="menu">
                <?php foreach ($daftar_menu as $menu) { ?>
                    <a href="<?php echo $menu[1]; ?>" class="<?php echo ($file_sekarang == $menu[1]) ? 'aktif' : ''; ?>">
                        <?php echo $menu[0]; ?>
                    </a>
                <?php } ?>
            </nav>
            <div class="bar-kanan">
                <span class="nama-pengguna"><?php echo htmlspecialchars($_SESSION['nama']); ?> (<?php echo $tulisan_peran; ?>)</span>
                <a href="../auth/logout.php" class="tombol-keluar">Keluar</a>
            </div>
        </div>
    </header>

    <main class="isi">