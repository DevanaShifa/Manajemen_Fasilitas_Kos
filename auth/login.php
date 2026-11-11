<?php
// "../" artinya naik satu folder (dari auth/ ke Manajemen_Fasilitas_Kos/), lalu masuk ke includes/
include "../includes/koneksi.php";

// kalau udah login, ga perlu lihat halaman login lagi
// terus langsung arahin dashboard sesuai peran yang dipilih
if (isset($_SESSION['peran'])) {
    if ($_SESSION['peran'] == 'admin') {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../user/dashboard.php");
    }
    exit;
}

// ambil kode pesan dari URL (misal login.php?pesan=salah).
// klo gaada, isinya kosong.
$pesan = isset($_GET['pesan']) ? $_GET['pesan'] : "";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="kartu-auth">
        <h2>Selamat Datang</h2>
        <p class="subjudul">Silakan login untuk melanjutkan</p>

        <?php if ($pesan == "salah") { ?>
            <div class="pesan pesan-error">Username atau password salah.</div>
        <?php } elseif ($pesan == "peran_tidak_cocok") { ?>
            <div class="pesan pesan-error">Akun ini bukan akun untuk peran yang dipilih.</div>
        <?php } elseif ($pesan == "harus_login") { ?>
            <div class="pesan pesan-info">Kamu harus login dulu.</div>
        <?php } elseif ($pesan == "tidak_berhak") { ?>
            <div class="pesan pesan-error">Kamu tidak punya akses ke halaman itu.</div>
        <?php } elseif ($pesan == "logout") { ?>
            <div class="pesan pesan-sukses">Kamu berhasil logout.</div>
        <?php } elseif ($pesan == "daftar_sukses") { ?>
            <div class="pesan pesan-sukses">Registrasi berhasil, silakan login.</div>
        <?php } ?>

        <div class="tab-peran">
            <button type="button" id="tombolUser" class="aktif" onclick="pilihPeran('user')">Penghuni</button>
            <button type="button" id="tombolAdmin" onclick="pilihPeran('admin')">Admin</button>
        </div>

        <!-- action="proses_login.php" tanpa "../" karena filenya satu folder -->
        <form action="proses_login.php" method="POST">
            <input type="hidden" name="peran_dipilih" id="peranDipilih" value="user">

            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Masukkan username" required>

            <label for="kata_sandi">Password</label>
            <input type="password" id="kata_sandi" name="kata_sandi" placeholder="Masukkan password" required>

            <button type="submit" class="tombol-utama">Masuk</button>
        </form>

        <p class="tautan-bawah" id="tautanDaftar">Belum punya akun? <a href="register.php">Daftar</a></p>
    </div>

    <script>
        // Ganti tab Penghuni/Admin. Nilainya disimpan di input tersembunyi
        // supaya ikut terkirim ke proses_login.php.
        function pilihPeran(peran) {
            document.getElementById("peranDipilih").value = peran;
            document.getElementById("tombolUser").classList.toggle("aktif", peran == "user");
            document.getElementById("tombolAdmin").classList.toggle("aktif", peran == "admin");
            // Admin tidak bisa daftar sendiri, jadi tautan daftar disembunyikan
            document.getElementById("tautanDaftar").style.display = (peran == "admin") ? "none" : "block";
        }
    </script>
</body>
</html>