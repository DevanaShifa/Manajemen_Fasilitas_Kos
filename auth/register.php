<?php
// ambil file koneksi.php supaya web bisa "nyambung" ke database.
include "../includes/koneksi.php";

// kotak kosong untuk taro pesan error, kalau kotaknya tetap kosong sampai akhir, berarti ga ada masalah
$pesan_error = "";

// kode di dalam kurung kurawal ini cuma jalan kalau tombol Daftar sudah diklik
if (isset($_POST['daftar'])) {

    // trim() = buang spasi yang tidak sengaja ketik di depan atau belakang
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $username = trim($_POST['username']);

    // pw sengaja tidak di-trim, karena spasi bisa jadi bagian dari password
    $kata_sandi = $_POST['kata_sandi'];
    $ulangi_kata_sandi = $_POST['ulangi_kata_sandi'];

    // ngecek 1: password terlalu pendek?
    // strlen() = hitung jumlah huruf, kalau kurang dari 6, ditolak
    if (strlen($kata_sandi) < 6) {
        $pesan_error = "Password minimal 6 karakter.";

    // ngecek 2: password dan ulangi password sama atau engga
    // ini buat mastiin pengguna ga salah ketik
    } elseif ($kata_sandi != $ulangi_kata_sandi) {
        $pesan_error = "Konfirmasi password tidak sama.";

    // kalau dua cek di atas lolos, lanjut ke sini.
    } else {

        // ngecek 3: username ini udah pernah dipakai orang lain atau ga?
        // Tanda ? itu kayak kolom kosong di formulir yang nakal dicek sebentar lagi
        // dibuat begini biar data yang diketik orang ga bisa dipakai untuk ngerusak dbnya
        $cek = mysqli_prepare($koneksi, "SELECT id FROM pengguna WHERE username = ?");

        // isi kolom kosong (?) tadi dengan username, Huruf "s" artinya isinya tipe teks
        mysqli_stmt_bind_param($cek, "s", $username);

        // kirim pertanyaannya ke db, trus ambil jawabannya
        mysqli_stmt_execute($cek);
        $hasil_cek = mysqli_stmt_get_result($cek);

        // itung ada berapa baris jawaban, Kalau ada 1 atau lebih, berarti usn nya dah ada yang pake
        if (mysqli_num_rows($hasil_cek) > 0) {
            $pesan_error = "Username sudah dipakai.";

        // kalo username masih kosong, simpan akun barunya
        } else {

            // password diacak dulu sebelum disimpan, jadi bentuknya ga bisa dibaca
            // tujuannya: kalau database bocor, password asli pengguna tetap aman
            $kata_sandi_hash = password_hash($kata_sandi, PASSWORD_DEFAULT);

            // perintah ini untuk nyimpan data baru ke tabel pengguna
            // tiga tanda ? di sini adalah kolom kosong untuk nama, username, dan password
            // kata 'user' ditulis langsung di sini (bukan dari formulir),
            // supaya ga ada orang yang bisa mendaftar sebagai admin
            // dengan cara mengutak-atik formulir
            $perintah = mysqli_prepare($koneksi, "INSERT INTO pengguna (nama_lengkap, username, kata_sandi, peran) VALUES (?, ?, ?, 'user')");

            // isi tiga kolom kosong tadi. "sss" artinya ketiganya berupa teks.
            mysqli_stmt_bind_param($perintah, "sss", $nama_lengkap, $username, $kata_sandi_hash);

            // jalankan perintahnya, maka data baru tersimpan di database.
            mysqli_stmt_execute($perintah);

            // pindahkan pengguna ke halaman login sambil bawa kabar "daftar berhasil".
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
    <title>Daftar</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="kartu-auth">
        <h2>Buat Akun</h2>
        <p class="subjudul">Daftar sebagai penghuni</p>

        <!-- Kotak pesan error ini hanya muncul kalau $pesan_error tidak kosong -->
        <?php if ($pesan_error != "") { ?>
            <div class="pesan pesan-error"><?php echo $pesan_error; ?></div>
        <?php } ?>

        <!-- method="POST" artinya data dikirim diam-diam, tidak kelihatan di alamat web -->
        <form method="POST">
            <label for="nama_lengkap">Nama lengkap</label>
            <input type="text" id="nama_lengkap" name="nama_lengkap" required>

            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>

            <label for="kata_sandi">Password</label>
            <input type="password" id="kata_sandi" name="kata_sandi" required>

            <label for="ulangi_kata_sandi">Ulangi password</label>
            <input type="password" id="ulangi_kata_sandi" name="ulangi_kata_sandi" required>

            <button type="submit" name="daftar" class="tombol-utama">Daftar</button>
        </form>

        <p class="tautan-bawah">Sudah punya akun? <a href="login.php">Login</a></p>
    </div>
</body>
</html>