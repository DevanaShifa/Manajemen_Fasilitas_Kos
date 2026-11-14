<?php
include "../includes/koneksi.php";
include "../includes/cek_akses.php";
wajib_login('penghuni', '../auth/login.php');

$id_penghuni = isset($_SESSION['id_penghuni']) ? $_SESSION['id_penghuni'] : 0;
$pesan_error = "";

if (isset($_POST['kirim'])) {
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);
    $fasilitas_id = ($_POST['fasilitas_id'] != "") ? (int) $_POST['fasilitas_id'] : null;

    if ($judul == "" || $deskripsi == "") {
        $pesan_error = "Judul dan isi keluhan wajib diisi.";
    } elseif ($id_penghuni == 0) {
        $pesan_error = "Kamu belum tercatat sebagai penghuni. Hubungi admin.";
    } else {
        $simpan = mysqli_prepare($koneksi, "INSERT INTO complains (tenant_id, judul, deskripsi, fasilitas_id) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($simpan, "issi", $id_penghuni, $judul, $deskripsi, $fasilitas_id);
        mysqli_stmt_execute($simpan);
        header("Location: fasilitas.php?pesan=terkirim");
        exit;
    }
}

// Daftar fasilitas (dipakai untuk tabel dan pilihan di formulir)
$daftar_fasilitas = mysqli_fetch_all(
    mysqli_query($koneksi, "SELECT id, nama_fasilitas, jenis, kondisi, keterangan FROM fasilities ORDER BY jenis, nama_fasilitas"),
    MYSQLI_ASSOC
);

// Riwayat keluhan penghuni ini
$cari = mysqli_prepare($koneksi, "SELECT complains.judul, complains.deskripsi, complains.status, complains.created_at, fasilities.nama_fasilitas
                                  FROM complains
                                  LEFT JOIN fasilities ON complains.fasilitas_id = fasilities.id
                                  WHERE complains.tenant_id = ?
                                  ORDER BY complains.created_at DESC");
mysqli_stmt_bind_param($cari, "i", $id_penghuni);
mysqli_stmt_execute($cari);
$daftar = mysqli_stmt_get_result($cari);

$judul_halaman = "Fasilitas & Keluhan";
include "../includes/header.php";
?>

<h1>Fasilitas &amp; Keluhan</h1>
<p class="keterangan">Lihat kondisi fasilitas kos, kirim keluhan kalau ada yang bermasalah, dan pantau statusnya.</p>

<?php if (isset($_GET['pesan']) && $_GET['pesan'] == "terkirim") { ?>
    <div class="pesan pesan-sukses">Keluhan berhasil dikirim.</div>
<?php } ?>
<?php if ($pesan_error != "") { ?>
    <div class="pesan pesan-error"><?php echo $pesan_error; ?></div>
<?php } ?>

<div class="panel">
    <h2>Kondisi fasilitas</h2>
    <?php if (count($daftar_fasilitas) == 0) { ?>
        <p class="kosong">Belum ada fasilitas yang dicatat admin.</p>
    <?php } else { ?>
        <div class="bungkus-tabel">
            <table>
                <tr><th>Fasilitas</th><th>Jenis</th><th>Kondisi</th><th>Keterangan</th></tr>
                <?php foreach ($daftar_fasilitas as $f) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($f['nama_fasilitas']); ?></td>
                        <td><?php echo ucfirst($f['jenis']); ?></td>
                        <td><span class="lencana lencana-<?php echo $f['kondisi']; ?>"><?php echo ucfirst($f['kondisi']); ?></span></td>
                        <td><?php echo htmlspecialchars($f['keterangan'] ?? ''); ?></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>
</div>

<div class="panel">
    <h2>Kirim keluhan baru</h2>
    <form method="POST">
        <label for="fasilitas_id">Fasilitas yang bermasalah (boleh dikosongkan)</label>
        <select id="fasilitas_id" name="fasilitas_id">
            <option value="">- Tidak terkait fasilitas tertentu -</option>
            <?php foreach ($daftar_fasilitas as $f) { ?>
                <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['nama_fasilitas']); ?></option>
            <?php } ?>
        </select>

        <label for="judul">Judul</label>
        <input type="text" id="judul" name="judul" required>

        <label for="deskripsi">Ceritakan masalahnya</label>
        <textarea id="deskripsi" name="deskripsi" rows="4" required></textarea>

        <button type="submit" name="kirim" class="tombol-utama">Kirim</button>
    </form>
</div>

<div class="panel" id="riwayat">
    <h2>Riwayat keluhan saya</h2>
    <?php if (mysqli_num_rows($daftar) == 0) { ?>
        <p class="kosong">Belum ada keluhan yang kamu kirim.</p>
    <?php } else { ?>
        <div class="bungkus-tabel">
            <table>
                <tr><th>Keluhan</th><th>Fasilitas</th><th>Isi</th><th>Tanggal</th><th>Status</th></tr>
                <?php while ($k = mysqli_fetch_assoc($daftar)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($k['judul']); ?></td>
                        <td><?php echo htmlspecialchars($k['nama_fasilitas'] ?? '-'); ?></td>
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