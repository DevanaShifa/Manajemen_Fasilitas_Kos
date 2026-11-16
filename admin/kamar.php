<?php
include "../includes/koneksi.php";
include "../includes/cek_akses.php";
wajib_login('admin', '../auth/login.php');

$status_boleh = array('kosong', 'perbaikan');
$pesan_error = "";

// Tambah kamar baru
if (isset($_POST['tambah_kamar'])) {
    $no = trim($_POST['no_kamar']);
    $tipe = trim($_POST['tipe']);
    $harga = (int) $_POST['harga'];
    $ukuran = trim($_POST['ukuran']);
    $deskripsi = trim($_POST['deskripsi']);

    if ($no == "" || $tipe == "" || $harga <= 0) {
        $pesan_error = "Nomor kamar, tipe, dan harga wajib diisi.";
    } else {
        $cek = mysqli_prepare($koneksi, "SELECT id FROM rooms WHERE no_kamar = ?");
        mysqli_stmt_bind_param($cek, "s", $no);
        mysqli_stmt_execute($cek);
        if (mysqli_num_rows(mysqli_stmt_get_result($cek)) > 0) {
            $pesan_error = "Nomor kamar itu sudah ada.";
        } else {
            $tambah = mysqli_prepare($koneksi, "INSERT INTO rooms (no_kamar, tipe, harga, ukuran, deskripsi) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($tambah, "ssiss", $no, $tipe, $harga, $ukuran, $deskripsi);
            mysqli_stmt_execute($tambah);
            header("Location: kamar.php?pesan=ok");
            exit;
        }
    }
}

// Ubah status kamar (kamar yang sedang dihuni tidak bisa diubah dari sini)
if (isset($_POST['ubah_status'])) {
    $id = (int) $_POST['id'];
    $status = $_POST['status'];
    if (in_array($status, $status_boleh)) {
        $ubah = mysqli_prepare($koneksi, "UPDATE rooms SET status = ? WHERE id = ? AND status != 'terisi'");
        mysqli_stmt_bind_param($ubah, "si", $status, $id);
        mysqli_stmt_execute($ubah);
    }
    header("Location: kamar.php?pesan=ok");
    exit;
}

$daftar_kamar = mysqli_query($koneksi, "SELECT rooms.*, tenants.nama_lengkap
                                        FROM rooms
                                        LEFT JOIN rentals ON rentals.room_id = rooms.id AND rentals.status = 'aktif'
                                        LEFT JOIN tenants ON rentals.tenant_id = tenants.id
                                        ORDER BY rooms.no_kamar");

$judul_halaman = "Kelola Kamar";
include "../includes/header.php";
?>

<h1>Kelola Kamar</h1>
<p class="keterangan">Daftar kamar kos. Penghuni ditempatkan ke kamar lewat menu Penghuni.</p>

<?php if (isset($_GET['pesan'])) { ?>
    <div class="pesan pesan-sukses">Perubahan disimpan.</div>
<?php } ?>
<?php if ($pesan_error != "") { ?>
    <div class="pesan pesan-error"><?php echo $pesan_error; ?></div>
<?php } ?>

<div class="panel">
    <h2>Daftar kamar</h2>
    <?php if (mysqli_num_rows($daftar_kamar) == 0) { ?>
        <p class="kosong">Belum ada kamar. Tambahkan lewat formulir di bawah.</p>
    <?php } else { ?>
        <div class="bungkus-tabel">
            <table>
                <tr><th>No.</th><th>Tipe</th><th>Ukuran</th><th>Harga</th><th>Status</th></tr>
                <?php while ($k = mysqli_fetch_assoc($daftar_kamar)) { ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($k['no_kamar']); ?></strong></td>
                        <td><?php echo htmlspecialchars($k['tipe']); ?></td>
                        <td><?php echo htmlspecialchars($k['ukuran'] ?? '-'); ?></td>
                        <td>Rp <?php echo number_format($k['harga'], 0, ',', '.'); ?></td>
                        <td>
                            <?php if ($k['nama_lengkap'] != null) { ?>
                                <span class="lencana lencana-terisi">Terisi</span>
                                <small><?php echo htmlspecialchars($k['nama_lengkap']); ?></small>
                            <?php } else { ?>
                                <form method="POST" style="display:flex; gap:6px; align-items:center;">
                                    <input type="hidden" name="id" value="<?php echo $k['id']; ?>">
                                    <select name="status" style="margin:0; width:auto;">
                                        <?php foreach ($status_boleh as $s) { ?>
                                            <option value="<?php echo $s; ?>" <?php echo ($k['status'] == $s) ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                                        <?php } ?>
                                    </select>
                                    <button type="submit" name="ubah_status" class="tombol-utama" style="width:auto; margin:0; padding:8px 14px;">Simpan</button>
                                </form>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>
</div>

<div class="panel">
    <h2>Tambah kamar</h2>
    <form method="POST">
        <label for="no_kamar">Nomor kamar</label>
        <input type="text" id="no_kamar" name="no_kamar" maxlength="10" placeholder="Contoh: A1" required>

        <label for="tipe">Tipe</label>
        <input type="text" id="tipe" name="tipe" placeholder="Contoh: Standar, AC, Kamar mandi dalam" required>

        <label for="harga">Harga per bulan (Rp)</label>
        <input type="number" id="harga" name="harga" min="1" required>

        <label for="ukuran">Ukuran (boleh kosong)</label>
        <input type="text" id="ukuran" name="ukuran" placeholder="Contoh: 3x4 m">

        <label for="deskripsi">Deskripsi (boleh kosong)</label>
        <textarea id="deskripsi" name="deskripsi" rows="3"></textarea>

        <button type="submit" name="tambah_kamar" class="tombol-utama">Tambah</button>
    </form>
</div>

<?php include "../includes/footer.php"; ?>