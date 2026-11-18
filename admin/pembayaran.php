<?php
include "../includes/koneksi.php";
include "../includes/cek_akses.php";
wajib_login('admin', '../auth/login.php');

$pesan_error = "";

// Buat tagihan baru untuk penghuni yang sedang menyewa
if (isset($_POST['buat_tagihan'])) {
    $rental_id = (int) $_POST['rental_id'];
    $bulan = trim($_POST['bulan']);
    $jumlah = (int) $_POST['jumlah'];
    $jatuh_tempo = $_POST['jatuh_tempo'];

    if ($rental_id == 0 || $bulan == "" || $jatuh_tempo == "") {
        $pesan_error = "Pilih penghuni, isi bulan, dan tanggal jatuh tempo.";
    } else {
        // Ambil harga kamar. Kalau jumlah dikosongkan, pakai harga kamar.
        $cari = mysqli_prepare($koneksi, "SELECT rooms.harga FROM rentals JOIN rooms ON rentals.room_id = rooms.id
                                          WHERE rentals.id = ? AND rentals.status = 'aktif'");
        mysqli_stmt_bind_param($cari, "i", $rental_id);
        mysqli_stmt_execute($cari);
        $kamar = mysqli_fetch_assoc(mysqli_stmt_get_result($cari));

        if (!$kamar) {
            $pesan_error = "Sewa itu tidak ditemukan atau sudah selesai.";
        } else {
            if ($jumlah <= 0) {
                $jumlah = $kamar['harga'];
            }
            $simpan = mysqli_prepare($koneksi, "INSERT INTO payments (rental_id, bulan, jumlah, jatuh_tempo) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($simpan, "isis", $rental_id, $bulan, $jumlah, $jatuh_tempo);
            mysqli_stmt_execute($simpan);
            header("Location: pembayaran.php?pesan=ok");
            exit;
        }
    }
}

// Terima atau tolak bukti bayar
if (isset($_POST['aksi'])) {
    $id = (int) $_POST['id'];
    if ($_POST['aksi'] == 'terima') {
        $ubah = mysqli_prepare($koneksi, "UPDATE payments SET status = 'lunas', catatan_admin = NULL WHERE id = ? AND status = 'menunggu'");
        mysqli_stmt_bind_param($ubah, "i", $id);
        mysqli_stmt_execute($ubah);
    } elseif ($_POST['aksi'] == 'tolak') {
        $catatan = trim($_POST['catatan_admin']);
        $ubah = mysqli_prepare($koneksi, "UPDATE payments SET status = 'ditolak', catatan_admin = ? WHERE id = ? AND status = 'menunggu'");
        mysqli_stmt_bind_param($ubah, "si", $catatan, $id);
        mysqli_stmt_execute($ubah);
    }
    header("Location: pembayaran.php?pesan=ok");
    exit;
}

// Pilihan sewa aktif untuk formulir tagihan
$sewa_aktif = mysqli_query($koneksi, "SELECT rentals.id, tenants.nama_lengkap, rooms.no_kamar, rooms.harga
                                      FROM rentals
                                      JOIN tenants ON rentals.tenant_id = tenants.id
                                      JOIN rooms ON rentals.room_id = rooms.id
                                      WHERE rentals.status = 'aktif'
                                      ORDER BY tenants.nama_lengkap");

// Semua pembayaran, yang menunggu dicek ditaruh paling atas
$daftar = mysqli_query($koneksi, "SELECT payments.*, tenants.nama_lengkap, rooms.no_kamar,
                                         (payments.jatuh_tempo < CURDATE() AND payments.status = 'belum_bayar') AS telat
                                  FROM payments
                                  JOIN rentals ON payments.rental_id = rentals.id
                                  JOIN tenants ON rentals.tenant_id = tenants.id
                                  JOIN rooms ON rentals.room_id = rooms.id
                                  ORDER BY (payments.status = 'menunggu') DESC, payments.jatuh_tempo DESC");

$judul_halaman = "Kelola Pembayaran";
include "../includes/header.php";
?>

<h1>Kelola Pembayaran</h1>
<p class="keterangan">Buat tagihan bulanan dan cek bukti bayar dari penghuni.</p>

<?php if (isset($_GET['pesan'])) { ?>
    <div class="pesan pesan-sukses">Perubahan disimpan.</div>
<?php } ?>
<?php if ($pesan_error != "") { ?>
    <div class="pesan pesan-error"><?php echo $pesan_error; ?></div>
<?php } ?>

<div class="panel">
    <h2>Pembayaran</h2>
    <?php if (mysqli_num_rows($daftar) == 0) { ?>
        <p class="kosong">Belum ada tagihan. Buat lewat formulir di bawah.</p>
    <?php } else { ?>
        <input type="text" class="cari-tabel" data-tabel="tabelBayar" placeholder="Cari nama, kamar, atau bulan...">
        <div class="bungkus-tabel">
            <table id="tabelBayar">
                <tr><th>Penghuni</th><th>Kamar</th><th>Bulan</th><th>Jumlah</th><th>Jatuh tempo</th><th>Status</th><th>Bukti / aksi</th></tr>
                <?php while ($p = mysqli_fetch_assoc($daftar)) { ?>
                    <tr class="baris-data">
                        <td><?php echo htmlspecialchars($p['nama_lengkap']); ?></td>
                        <td><?php echo htmlspecialchars($p['no_kamar']); ?></td>
                        <td><?php echo htmlspecialchars($p['bulan']); ?></td>
                        <td>Rp <?php echo number_format($p['jumlah'], 0, ',', '.'); ?></td>
                        <td><?php echo $p['jatuh_tempo'] ? date("d-m-Y", strtotime($p['jatuh_tempo'])) : '-'; ?></td>
                        <td>
                            <?php $kelas = ($p['telat'] == 1) ? 'telat' : $p['status']; ?>
                            <span class="lencana lencana-<?php echo $kelas; ?>"><?php echo ucfirst(str_replace('_', ' ', $kelas)); ?></span>
                        </td>
                        <td>
                            <?php if ($p['bukti']) { ?>
                                <a href="../uploads/<?php echo htmlspecialchars($p['bukti']); ?>" target="_blank">Lihat bukti</a><br>
                            <?php } ?>
                            <?php if ($p['status'] == 'menunggu') { ?>
                                <form method="POST" style="display:flex; gap:6px; align-items:center; flex-wrap:wrap; margin-top:4px;">
                                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                    <input type="text" name="catatan_admin" placeholder="Alasan (kalau ditolak)" style="margin:0; width:170px;">
                                    <button type="submit" name="aksi" value="terima" class="tombol-utama" style="width:auto; margin:0; padding:7px 12px; background:var(--warna-sukses);">Terima</button>
                                    <button type="submit" name="aksi" value="tolak" class="tombol-utama" style="width:auto; margin:0; padding:7px 12px; background:var(--warna-bahaya);">Tolak</button>
                                </form>
                            <?php } elseif ($p['status'] == 'ditolak' && $p['catatan_admin'] != "") { ?>
                                <small>Alasan: <?php echo htmlspecialchars($p['catatan_admin']); ?></small>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>
</div>

<div class="panel">
    <h2>Buat tagihan</h2>
    <?php if (mysqli_num_rows($sewa_aktif) == 0) { ?>
        <p class="kosong">Belum ada penghuni yang menempati kamar. Tempatkan penghuni dulu di menu Penghuni.</p>
    <?php } else { ?>
        <form method="POST">
            <label for="rental_id">Penghuni</label>
            <select id="rental_id" name="rental_id" required>
                <option value="">- Pilih penghuni -</option>
                <?php while ($s = mysqli_fetch_assoc($sewa_aktif)) { ?>
                    <option value="<?php echo $s['id']; ?>">
                        <?php echo htmlspecialchars($s['nama_lengkap'] . " - Kamar " . $s['no_kamar'] . " (Rp " . number_format($s['harga'], 0, ',', '.') . ")"); ?>
                    </option>
                <?php } ?>
            </select>

            <label for="bulan">Untuk bulan</label>
            <input type="text" id="bulan" name="bulan" maxlength="20" placeholder="Contoh: November 2026" required>

            <label for="jumlah">Jumlah (Rp), kosongkan untuk memakai harga kamar</label>
            <input type="number" id="jumlah" name="jumlah" min="0">

            <label for="jatuh_tempo">Jatuh tempo</label>
            <input type="date" id="jatuh_tempo" name="jatuh_tempo" required>

            <button type="submit" name="buat_tagihan" class="tombol-utama">Buat tagihan</button>
        </form>
    <?php } ?>
</div>

<?php include "../includes/footer.php"; ?>