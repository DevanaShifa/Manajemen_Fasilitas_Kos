<?php
include "../includes/koneksi.php";
include "../includes/cek_akses.php";
wajib_login('penghuni', '../auth/login.php');

$id_penghuni = isset($_SESSION['id_penghuni']) ? $_SESSION['id_penghuni'] : 0;
$pesan_error = "";

if (isset($_POST['upload'])) {
    $id = (int) $_POST['id'];

    // Pastikan tagihan ini milik penghuni yang login dan memang masih perlu dibayar
    $cek = mysqli_prepare($koneksi, "SELECT payments.id FROM payments
                                     JOIN rentals ON payments.rental_id = rentals.id
                                     WHERE payments.id = ? AND rentals.tenant_id = ? AND payments.status IN ('belum_bayar', 'ditolak')");
    mysqli_stmt_bind_param($cek, "ii", $id, $id_penghuni);
    mysqli_stmt_execute($cek);
    $milik_saya = mysqli_num_rows(mysqli_stmt_get_result($cek)) > 0;

    $berkas = isset($_FILES['bukti']) ? $_FILES['bukti'] : null;
    $boleh = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf');

    if (!$milik_saya) {
        $pesan_error = "Tagihan tidak ditemukan.";
    } elseif (!$berkas || $berkas['error'] != 0) {
        $pesan_error = "Pilih file bukti bayar dulu.";
    } elseif ($berkas['size'] > 2 * 1024 * 1024) {
        $pesan_error = "Ukuran file maksimal 2 MB.";
    } else {
        $ekstensi = strtolower(pathinfo($berkas['name'], PATHINFO_EXTENSION));
        $jenis_asli = mime_content_type($berkas['tmp_name']);

        if (!isset($boleh[$ekstensi]) || $boleh[$ekstensi] != $jenis_asli) {
            $pesan_error = "File harus berupa JPG, PNG, atau PDF.";
        } else {
            // Nama file diacak supaya tidak bisa ditebak dan tidak bentrok
            $nama_file = "bukti_" . $id . "_" . bin2hex(random_bytes(6)) . "." . $ekstensi;
            move_uploaded_file($berkas['tmp_name'], __DIR__ . "/../uploads/" . $nama_file);

            $simpan = mysqli_prepare($koneksi, "UPDATE payments SET bukti = ?, tanggal_bayar = CURDATE(), status = 'menunggu', catatan_admin = NULL WHERE id = ?");
            mysqli_stmt_bind_param($simpan, "si", $nama_file, $id);
            mysqli_stmt_execute($simpan);
            header("Location: pembayaran.php?pesan=terkirim");
            exit;
        }
    }
}

$cari = mysqli_prepare($koneksi, "SELECT payments.id, payments.bulan, payments.jumlah, payments.jatuh_tempo, payments.tanggal_bayar,
                                         payments.status, payments.catatan_admin,
                                         (payments.jatuh_tempo < CURDATE() AND payments.status = 'belum_bayar') AS telat
                                  FROM payments
                                  JOIN rentals ON payments.rental_id = rentals.id
                                  WHERE rentals.tenant_id = ?
                                  ORDER BY payments.jatuh_tempo DESC");
mysqli_stmt_bind_param($cari, "i", $id_penghuni);
mysqli_stmt_execute($cari);
$daftar = mysqli_stmt_get_result($cari);

$judul_halaman = "Pembayaran";
include "../includes/header.php";
?>

<h1>Pembayaran</h1>
<p class="keterangan">Lihat tagihan kamu, lalu upload bukti transfer (JPG, PNG, atau PDF, maksimal 2 MB).</p>

<?php if (isset($_GET['pesan']) && $_GET['pesan'] == "terkirim") { ?>
    <div class="pesan pesan-sukses">Bukti bayar terkirim. Tunggu admin mengecek.</div>
<?php } ?>
<?php if ($pesan_error != "") { ?>
    <div class="pesan pesan-error"><?php echo $pesan_error; ?></div>
<?php } ?>

<div class="panel">
    <h2>Tagihan saya</h2>
    <?php if (mysqli_num_rows($daftar) == 0) { ?>
        <p class="kosong">Belum ada tagihan.</p>
    <?php } else { ?>
        <div class="bungkus-tabel">
            <table>
                <tr><th>Bulan</th><th>Jumlah</th><th>Jatuh tempo</th><th>Status</th><th>Bukti bayar</th></tr>
                <?php while ($p = mysqli_fetch_assoc($daftar)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['bulan']); ?></td>
                        <td>Rp <?php echo number_format($p['jumlah'], 0, ',', '.'); ?></td>
                        <td><?php echo $p['jatuh_tempo'] ? date("d-m-Y", strtotime($p['jatuh_tempo'])) : '-'; ?></td>
                        <td>
                            <?php $kelas = ($p['telat'] == 1) ? 'telat' : $p['status']; ?>
                            <span class="lencana lencana-<?php echo $kelas; ?>"><?php echo ucfirst(str_replace('_', ' ', $kelas)); ?></span>
                            <?php if ($p['status'] == 'ditolak' && $p['catatan_admin'] != "") { ?>
                                <br><small><?php echo htmlspecialchars($p['catatan_admin']); ?></small>
                            <?php } ?>
                        </td>
                        <td>
                            <?php if ($p['status'] == 'belum_bayar' || $p['status'] == 'ditolak') { ?>
                                <form method="POST" enctype="multipart/form-data" style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                    <input type="file" name="bukti" accept=".jpg,.jpeg,.png,.pdf" required>
                                    <button type="submit" name="upload" class="tombol-utama" style="width:auto; margin:0; padding:7px 14px;">Kirim bukti</button>
                                </form>
                            <?php } elseif ($p['status'] == 'menunggu') { ?>
                                <small>Terkirim <?php echo date("d-m-Y", strtotime($p['tanggal_bayar'])); ?>, menunggu dicek admin</small>
                            <?php } else { ?>
                                <small>Lunas</small>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>
</div>

<?php include "../includes/footer.php"; ?>