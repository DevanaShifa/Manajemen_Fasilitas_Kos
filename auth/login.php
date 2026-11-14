<?php
// sambungkan ke database dan nyalakan session
include "../includes/koneksi.php";

// kalo udah login, ga perlu liat halaman login lagi
// terus langsung di arahkan ke dashboard sesuai perannya.
if (isset($_SESSION['peran'])) {
    if ($_SESSION['peran'] == 'admin') {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../user/dashboard.php");
    }
    exit;
}

// buat ambil kode pesan dari alamat web (contoh: login.php?pesan=salah).
// klo gaada berarti isinya kosong.
$pesan = isset($_GET['pesan']) ? $_GET['pesan'] : "";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kos Ndut</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="halaman-auth">
    <div class="kartu-auth">
        <h1>Kos Ndut</h1>
        <p class="subjudul">Masuk untuk melanjutkan</p>

        <!-- Pesan yang tampil tergantung kode di alamat web -->
        <?php if ($pesan == "salah") { ?>
            <div class="pesan pesan-error">Email atau password salah.</div>
        <?php } elseif ($pesan == "peran_tidak_cocok") { ?>
            <div class="pesan pesan-error">Akun ini bukan akun untuk peran yang dipilih.</div>
        <?php } elseif ($pesan == "harus_login") { ?>
            <div class="pesan pesan-info">Silakan login dulu.</div>
        <?php } elseif ($pesan == "tidak_berhak") { ?>
            <div class="pesan pesan-error">Kamu tidak punya akses ke halaman itu.</div>
        <?php } elseif ($pesan == "logout") { ?>
            <div class="pesan pesan-sukses">Kamu sudah keluar.</div>
        <?php } elseif ($pesan == "daftar_sukses") { ?>
            <div class="pesan pesan-sukses">Pendaftaran berhasil, silakan login.</div>
        <?php } ?>

        <!-- Tombol pilihan peran -->
        <div class="tab-peran">
            <button type="button" id="tombolPenghuni" class="aktif" onclick="pilihPeran('penghuni')">Penghuni</button>
            <button type="button" id="tombolAdmin" onclick="pilihPeran('admin')">Admin</button>
        </div>

        <!-- Isi formulir dikirim ke proses_login.php (satu folder, jadi tanpa ../) -->
        <form action="proses_login.php" method="POST">
            <input type="hidden" name="peran_dipilih" id="peranDipilih" value="penghuni">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <label for="kata_sandi">Password</label>
            <input type="password" id="kata_sandi" name="kata_sandi" required>

            <button type="submit" class="tombol-utama">Masuk</button>
        </form>

        <p class="tautan-bawah" id="tautanDaftar">Belum punya akun? <a href="register.php">Daftar</a></p>
    </div>

    <script>
        // jalan pas tombol Penghuni / Admin diklik
        function pilihPeran(peran) {
            // simpan pilihan ke kolom tersembunyi biar ikut terkirim
            document.getElementById("peranDipilih").value = peran;
            // tandai tombol yang sedang dipilih
            document.getElementById("tombolPenghuni").classList.toggle("aktif", peran == "penghuni");
            document.getElementById("tombolAdmin").classList.toggle("aktif", peran == "admin");
            // admin ga bisa daftar sendiri, jadi tulisan daftar disembunyikan
            document.getElementById("tautanDaftar").style.display = (peran == "admin") ? "none" : "block";
        }
    </script>
</body>
</html>