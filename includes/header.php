<?php
// Bagian atas halaman (judul web, menu, tombol keluar).
// Dipakai halaman di folder admin/ dan user/, jadi alamat di bawah memakai ../
// Sebelum memanggil file ini, isi dulu:  $judul_halaman = "Nama Halaman";

// Pakai jam Indonesia (WIB)
date_default_timezone_set("Asia/Jakarta");

// Tanggal hari ini dalam bahasa Indonesia, dipakai di banner dashboard
$nama_hari = array("Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu");
$nama_bulan = array(1 => "Januari", "Februari", "Maret", "April", "Mei", "Juni",
                    "Juli", "Agustus", "September", "Oktober", "November", "Desember");
//Huruf w, j, dan n itu parameter format bawaan fungsi date() di PHP.
// w mengambil indeks hari mulai dari 0 untuk Minggu, 
// j mengambil tanggal tanpa awalan nol, 
// dan n mengambil nomor bulan 1 sampai 12 untuk dicocokkan dengan array nama hari dan bulan bahasa Indonesia
$tanggal_hari_ini = $nama_hari[date("w")] . ", " . date("j") . " " . $nama_bulan[date("n")] . " " . date("Y");

// Nama file halaman yang sedang dibuka, untuk menandai menu yang sedang aktif
$file_sekarang = basename($_SERVER['PHP_SELF']);

// nentuin menu apa aja yang muncul based on siapa yang lagi login
if ($_SESSION['peran'] == 'admin') {
    //klo yg login itu admin, tampilin menu lengkah pengelolaan kos
    $tulisan_peran = "Admin";
    $daftar_menu = array(
        array("Dashboard", "dashboard.php"),
        array("Kamar", "kamar.php"),
        array("Penghuni", "penghuni.php"),
        array("Pembayaran", "pembayaran.php"),
        array("Fasilitas & Keluhan", "fasilitas.php")
    );
} else {
    //klo yg login penghuni, tampilin menu khusus penghuni aja
    $tulisan_peran = "Penghuni";
    $daftar_menu = array(
        array("Dashboard", "dashboard.php"),
        array("Kamar Saya", "kamar.php"),
        array("Pembayaran", "pembayaran.php"),
        array("Fasilitas & Keluhan", "fasilitas.php")
    );
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Judul di tab browser diambil dari variabel $judul_halaman, lalu diamankan pakai htmlspecialchars biar nggak disusupi kode aneh -->
    <title><?php echo htmlspecialchars($judul_halaman); ?> - Kos Ndut</title>

    <!--ngehubungin file CSS buat ngatur warna, font, sama tata letak-->
        <link rel="stylesheet" href="../assets/css/style.css">
</head>
<!-- Kalau halaman minta pakai gaya banner khusus ($pakai_hero), tambahkan class "halaman-hero" di body -->
<body<?php echo !empty($pakai_hero) ? ' class="halaman-hero"' : ''; ?>>
    <header class="bar-atas" id="barAtas">
        <!-- Nama brand/aplikasi di pojok kiri atas -->
        <span class="merek">Kos Ndut</span>

        <!-- Tombol garis tiga (hamburger), buat buka menu pas di HP -->
        <button type="button" class="tombol-menu" id="tombolMenu" aria-label="Buka menu">&#9776;</button>

        <!-- Bagian yang terlipat di HP -->
        <div class="lipatan" id="lipatan">
            <nav class="menu">
                <!-- Mengulang daftar menu satu per satu lalu menjadikannya tombol link -->
                <?php foreach ($daftar_menu as $menu) { ?>
                <!-- Jika nama file menu sama dengan file yang sedang dibuka, kasih class 'aktif' -->
                    <a href="<?php echo $menu[1]; ?>" class="<?php echo ($file_sekarang == $menu[1]) ? 'aktif' : ''; ?>">
                        <?php echo $menu[0]; ?>
                    </a>
                <?php } ?>
            </nav>
            <div class="bar-kanan">
                <!-- Menampilkan nama orang yang sedang login beserta perannya (Admin / Penghuni) -->
                <span class="nama-pengguna"><?php echo htmlspecialchars($_SESSION['nama']); ?> (<?php echo $tulisan_peran; ?>)</span>
                <!-- Tombol keluar untuk mengakhiri sesi login -->
                <a href="../auth/logout.php" class="tombol-keluar">Keluar</a>
            </div>
        </div>
    </header>

    <main class="isi">