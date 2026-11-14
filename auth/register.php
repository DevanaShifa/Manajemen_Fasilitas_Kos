<?php
include "../includes/koneksi.php";

// Tempat menaruh pesan error. Kosong artinya belum ada masalah.
$pesan_error = "";

// Isi formulir disimpan di sini supaya tidak hilang kalau ada error
$nama = "";
$email = "";
$no_hp = "";

// Bagian ini jalan hanya kalau tombol Daftar diklik
if (isset($_POST['daftar'])) {

    // Ambil isi formulir, spasi di pinggir dibuang
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $no_hp = trim($_POST['no_hp']);
    $kata_sandi = $_POST['kata_sandi'];
    $ulangi_kata_sandi = $_POST['ulangi_kata_sandi'];

    // Penulisan email benar?
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan_error = "Penulisan email belum benar.";

    // Password kependekan?
    } elseif (strlen($kata_sandi) < 6) {
        $pesan_error = "Password minimal 6 karakter.";

    // Dua password beda?
    } elseif ($kata_sandi != $ulangi_kata_sandi) {
        $pesan_error = "Password dan ulangi password tidak sama.";

    } else {
        // Tanya database: email ini sudah dipakai belum?
        $cek = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($cek, "s", $email);
        mysqli_stmt_execute($cek);
        $hasil_cek = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($hasil_cek) > 0) {
            $pesan_error = "Email sudah terdaftar.";

        } else {
            // Acak password sebelum disimpan, supaya aman kalau database bocor
            $kata_sandi_hash = password_hash($kata_sandi, PASSWORD_DEFAULT);

            // Langkah 1: simpan akun login. Perannya langsung 'penghuni',
            // jadi tidak ada yang bisa mendaftar sebagai admin sendiri.
            $simpan_akun = mysqli_prepare($koneksi, "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'penghuni')");
            mysqli_stmt_bind_param($simpan_akun, "sss", $nama, $email, $kata_sandi_hash);
            mysqli_stmt_execute($simpan_akun);

            // Ambil nomor akun yang barusan dibuat
            $id_akun_baru = mysqli_insert_id($koneksi);

            // Langkah 2: simpan data diri penghuni, disambungkan ke akun tadi
            $simpan_penghuni = mysqli_prepare($koneksi, "INSERT INTO tenants (user_id, nama_lengkap, no_hp) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($simpan_penghuni, "iss", $id_akun_baru, $nama, $no_hp);
            mysqli_stmt_execute($simpan_penghuni);

            // Selesai. Pindah ke halaman login dan berhenti.
            header("Location: login.php?pesan=daftar_sukses");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Kos Ndut</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="halaman-auth">
    <div class="kartu-auth">
        <h1>Buat Akun</h1>
        <p class="subjudul">Daftar sebagai penghuni</p>

        <!-- Kotak error hanya muncul kalau ada pesannya -->
        <?php if ($pesan_error != "") { ?>
            <div class="pesan pesan-error"><?php echo $pesan_error; ?></div>
        <?php } ?>

        <!-- method="POST" artinya data dikirim tanpa muncul di alamat web -->
        <form method="POST">
            <label for="nama">Nama lengkap</label>
            <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($nama); ?>" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>

            <label for="no_hp">No. HP</label>
            <input type="text" id="no_hp" name="no_hp" value="<?php echo htmlspecialchars($no_hp); ?>" required>

            <label for="kata_sandi">Password</label>
            <input type="password" id="kata_sandi" name="kata_sandi" required>

            <label for="ulangi_kata_sandi">Ulangi password</label>
            <input type="password" id="ulangi_kata_sandi" name="ulangi_kata_sandi" required>

            <button type="submit" name="daftar" class="tombol-utama">Daftar</button>
        </form>

        <p class="tautan-bawah">Sudah punya akun? <a href="login.php">Masuk</a></p>
    </div>
</body>
</html>