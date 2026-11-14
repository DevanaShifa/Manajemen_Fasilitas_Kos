<?php
include "../includes/koneksi.php";
include "../includes/cek_akses.php";
wajib_login('penghuni', '../auth/login.php');

$id_penghuni = isset($_SESSION['id_penghuni']) ? $_SESSION['id_penghuni'] : 0;
$pesan_error = "";

// Kalau tombol Kirim ditekan
if (isset($_POST['kirim'])) {
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);

    if ($judul == "" || $deskripsi == "") {
        $pesan_error = "Judul dan isi keluhan wajib diisi.";
    } elseif ($id_penghuni == 0) {
        $pesan_error = "Kamu belum tercatat sebagai penghuni. Hubungi admin.";
    } else {
        $simpan = mysqli_prepare($koneksi, "INSERT INTO complains (tenant_id, judul, deskripsi) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($simpan, "iss", $id_penghuni, $judul, $deskripsi);
        mysqli_stmt_execute($simpan);
        header("Location: keluhan.php?pesan=terkirim");
        exit;
    }
}

// Riwayat keluhan milik penghuni ini
$cari = mysqli_prepare($koneksi, "SELECT judul, deskripsi, status, created_at FROM complains WHERE tenant_id = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($cari, "i", $id_penghuni);
mysqli_stmt_execute($cari);
$daftar = mysqli_stmt_get_result($cari);

$judul_halaman = "Keluhan";
include "../includes/header.php";
?>

<h1>Keluhan</h1>
<p class="keterangan">Kirim keluhan soal kamar atau fasilitas, lalu pantau statusnya di sini.</p>

<?php if (isset($_GET['pesan']) && $_GET['pesan'] == "terkirim") { ?>
    <div class="pesan pesan-sukses">Keluhan berhasil dikirim.</div>
<?php } ?>
<?php if ($pesan_error != "") { ?>
    <div class="pesan pesan-error"><?php echo $pesan_error; ?></div>
<?php } ?>

<div class="panel">
    <h2>Kirim keluhan baru</h2>
    <form method="POST">
        <label for="judul">Judul</label>
        <input type="text" id="judul" name="judul" required>

        <label for="deskripsi">Ceritakan masalahnya</label>
        <textarea id="deskripsi" name="deskripsi" rows="4" required></textarea>

        <button type="submit" name="kirim" class="tombol-utama">Kirim</button>
    </form>
</div>

<div class="panel">
    <h2>Riwayat keluhan</h2>
    <?php if (mysqli_num_rows($daftar) == 0) { ?>
        <p class="kosong">Belum ada keluhan yang kamu kirim.</p>
    <?php } else { ?>
        <div class="bungkus-tabel">
            <table>
                <tr><th>Keluhan</th><th>Isi</th><th>Tanggal</th><th>Status</th></tr>
                <?php while ($k = mysqli_fetch_assoc($daftar)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($k['judul']); ?></td>
                        <td><?php echo htmlspecialchars($k['deskripsi']); ?></td>
                        <td><?php echo date("d-m-Y", strtotime($k['created_at'])); ?></td>
                        <td><span class="lencana lencana-<?php echo $k['status']; ?>"><?php echo ucfirst($k['status']); ?></span></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>
</div>

<?php include "../includes/footer.php"; ?>