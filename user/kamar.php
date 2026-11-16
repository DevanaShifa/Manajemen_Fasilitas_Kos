<?php
include "../includes/koneksi.php";
include "../includes/cek_akses.php";
wajib_login('penghuni', '../auth/login.php');

$id_penghuni = isset($_SESSION['id_penghuni']) ? $_SESSION['id_penghuni'] : 0;

$cari = mysqli_prepare($koneksi, "SELECT rooms.no_kamar, rooms.tipe, rooms.harga, rooms.ukuran, rooms.deskripsi, rooms.foto, rentals.tanggal_masuk
                                  FROM rentals
                                  JOIN rooms ON rentals.room_id = rooms.id
                                  WHERE rentals.tenant_id = ? AND rentals.status = 'aktif'
                                  LIMIT 1");
mysqli_stmt_bind_param($cari, "i", $id_penghuni);
mysqli_stmt_execute($cari);
$kamar = mysqli_fetch_assoc(mysqli_stmt_get_result($cari));

$judul_halaman = "Kamar Saya";
include "../includes/header.php";
?>

<h1>Kamar Saya</h1>
<p class="keterangan">Detail kamar yang kamu tempati.</p>

<div class="panel">
    <?php if ($kamar) { ?>
        <h2>Kamar <?php echo htmlspecialchars($kamar['no_kamar']); ?></h2>
        <?php if (!empty($kamar['foto'])) { ?>
            <img src="<?php echo htmlspecialchars($kamar['foto']); ?>" alt="Foto kamar" style="max-width:100%; border-radius:8px; margin-bottom:12px;">
        <?php } ?>
        <div class="bungkus-tabel">
            <table>
                <tr><th>Tipe</th><td><?php echo htmlspecialchars($kamar['tipe']); ?></td></tr>
                <tr><th>Ukuran</th><td><?php echo htmlspecialchars($kamar['ukuran'] ?? '-'); ?></td></tr>
                <tr><th>Harga per bulan</th><td>Rp <?php echo number_format($kamar['harga'], 0, ',', '.'); ?></td></tr>
                <tr><th>Masuk sejak</th><td><?php echo date("d-m-Y", strtotime($kamar['tanggal_masuk'])); ?></td></tr>
                <tr><th>Keterangan</th><td><?php echo htmlspecialchars($kamar['deskripsi'] ?? '-'); ?></td></tr>
            </table>
        </div>
        <p style="margin:14px 0 0;"><a href="fasilitas.php">Ada yang rusak di kamar? Kirim keluhan &rarr;</a></p>
    <?php } else { ?>
        <p class="kosong">Kamarmu belum dicatat oleh admin. Hubungi admin untuk dicatatkan.</p>
    <?php } ?>
</div>

<?php include "../includes/footer.php"; ?>