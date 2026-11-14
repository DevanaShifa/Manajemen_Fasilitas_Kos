<?php
// Bagian atas halaman (judul web, menu, tombol keluar).
// Dipakai halaman di folder admin/ dan user/, jadi alamat di bawah memakai ../
//
// Sebelum memanggil file ini, isi dulu:  $judul_halaman = "Nama Halaman";

// Nama file halaman yang sedang dibuka, untuk menandai menu yang sedang aktif
$file_sekarang = basename($_SERVER['PHP_SELF']);

// Daftar menu. Satu menu = array(tulisan menu, nama file tujuan).
// Mau menambah menu baru? Tambah satu baris array di bagian yang sesuai.
if ($_SESSION['peran'] == 'admin') {
    $tulisan_peran = "Admin";
    $daftar_menu = array(
        array("Dashboard", "dashboard.php")
    );
} else {
    $tulisan_peran = "Penghuni";
    $daftar_menu = array(
        array("Dashboard", "dashboard.php")
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
<body>
    <header class="bar-atas">
        <div class="bar-kiri">
            <span class="merek">Kos Ndut</span>
            <nav class="menu">
                <?php foreach ($daftar_menu as $menu) { ?>
                    <a href="<?php echo $menu[1]; ?>" class="<?php echo ($file_sekarang == $menu[1]) ? 'aktif' : ''; ?>">
                        <?php echo $menu[0]; ?>
                    </a>
                <?php } ?>
            </nav>
        </div>
        <div class="bar-kanan">
            <span class="nama-pengguna"><?php echo htmlspecialchars($_SESSION['nama']); ?> (<?php echo $tulisan_peran; ?>)</span>
            <a href="../auth/logout.php" class="tombol-keluar">Keluar</a>
        </div>
    </header>

    <main class="isi">