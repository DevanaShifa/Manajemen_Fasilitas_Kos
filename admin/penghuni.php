<?php
include "../includes/koneksi.php";
include "../includes/cek_akses.php";
wajib_login('admin', '../auth/login.php');

$pesan_error = "";

// Fungsi kecil: apakah query ini menghasilkan minimal satu baris?
function ada_data($koneksi, $sql, $tipe, ...$nilai)
{
    $perintah = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($perintah, $tipe, ...$nilai);
    mysqli_stmt_execute($perintah);
    return mysqli_num_rows(mysqli_stmt_get_result($perintah)) > 0;
}

// Tempatkan penghuni ke kamar
if (isset($_POST['tempatkan'])) {
    $tenant_id = (int) $_POST['tenant_id'];
    $room_id = (int) $_POST['room_id'];
    $tanggal_masuk = $_POST['tanggal_masuk'];

    if ($tenant_id == 0 || $room_id == 0 || $tanggal_masuk == "") {
        $pesan_error = "Pilih penghuni, kamar, dan tanggal masuk.";
    } elseif (!ada_data($koneksi, "SELECT id FROM rooms WHERE id = ? AND status = 'kosong'", "i", $room_id)) {
        $pesan_error = "Kamar itu sudah tidak kosong.";
    } elseif (ada_data($koneksi, "SELECT id FROM rentals WHERE tenant_id = ? AND status = 'aktif'", "i", $tenant_id)) {
        $pesan_error = "Penghuni itu sudah punya kamar.";
    } else {
        $simpan = mysqli_prepare($koneksi, "INSERT INTO rentals (tenant_id, room_id, tanggal_masuk) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($simpan, "iis", $tenant_id, $room_id, $tanggal_masuk);
        mysqli_stmt_execute($simpan);

        $tandai = mysqli_prepare($koneksi, "UPDATE rooms SET status = 'terisi' WHERE id = ?");
        mysqli_stmt_bind_param($tandai, "i", $room_id);
        mysqli_stmt_execute($tandai);

        header("Location: penghuni.php?pesan=ok");
        exit;
    }
}

// Akhiri sewa: kamar jadi kosong lagi
if (isset($_POST['akhiri'])) {
    $rental_id = (int) $_POST['rental_id'];
    $cari = mysqli_prepare($koneksi, "SELECT room_id FROM rentals WHERE id = ? AND status = 'aktif'");
    mysqli_stmt_bind_param($cari, "i", $rental_id);
    mysqli_stmt_execute($cari);
    $sewa = mysqli_fetch_assoc(mysqli_stmt_get_result($cari));

    if ($sewa) {
        $tutup = mysqli_prepare($koneksi, "UPDATE rentals SET status = 'selesai', tanggal_keluar = CURDATE() WHERE id = ?");
        mysqli_stmt_bind_param($tutup, "i", $rental_id);
        mysqli_stmt_execute($tutup);

        $kosongkan = mysqli_prepare($koneksi, "UPDATE rooms SET status = 'kosong' WHERE id = ?");
        mysqli_stmt_bind_param($kosongkan, "i", $sewa['room_id']);
        mysqli_stmt_execute($kosongkan);
    }
    header("Location: penghuni.php?pesan=ok");
    exit;
}

// Semua penghuni beserta kamar aktifnya (kalau ada)
$daftar_penghuni = mysqli_query($koneksi, "SELECT tenants.nama_lengkap, tenants.no_hp, users.email,
                                                  rooms.no_kamar, rentals.id AS rental_id, rentals.tanggal_masuk
                                           FROM tenants
                                           JOIN users ON tenants.user_id = users.id
                                           LEFT JOIN rentals ON rentals.tenant_id = tenants.id AND rentals.status = 'aktif'
                                           LEFT JOIN rooms ON rentals.room_id = rooms.id
                                           ORDER BY tenants.nama_lengkap");

// Pilihan untuk formulir: penghuni yang belum punya kamar, dan kamar yang kosong
$penghuni_belum = mysqli_query($koneksi, "SELECT id, nama_lengkap FROM tenants
                                          WHERE id NOT IN (SELECT tenant_id FROM rentals WHERE status = 'aktif')
                                          ORDER BY nama_lengkap");
$kamar_kosong = mysqli_query($koneksi, "SELECT id, no_kamar, tipe, harga FROM rooms WHERE status = 'kosong' ORDER BY no_kamar");

$judul_halaman = "Kelola Penghuni";
include "../includes/header.php";
?>

<h1>Kelola Penghuni</h1>
<p class="keterangan">Penghuni yang baru daftar perlu ditempatkan ke kamar di sini.</p>

<?php if (isset($_GET['pesan'])) { ?>
    <div class="pesan pesan-sukses">Perubahan disimpan.</div>
<?php } ?>
<?php if ($pesan_error != "") { ?>
    <div class="pesan pesan-error"><?php echo $pesan_error; ?></div>
<?php } ?>

<div class="panel">
    <h2>Tempatkan penghuni ke kamar</h2>
    <?php if (mysqli_num_rows($penghuni_belum) == 0) { ?>
        <p class="kosong">Semua penghuni yang terdaftar sudah punya kamar.</p>
    <?php } elseif (mysqli_num_rows($kamar_kosong) == 0) { ?>
        <p class="kosong">Tidak ada kamar kosong. Tambahkan kamar dulu di menu Kamar.</p>
    <?php } else { ?>
        <form method="POST">
            <label for="tenant_id">Penghuni</label>
            <select id="tenant_id" name="tenant_id" required>
                <option value="">- Pilih penghuni -</option>
                <?php while ($p = mysqli_fetch_assoc($penghuni_belum)) { ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nama_lengkap']); ?></option>
                <?php } ?>
            </select>

            <label for="room_id">Kamar</label>
            <select id="room_id" name="room_id" required>
                <option value="">- Pilih kamar -</option>
                <?php while ($k = mysqli_fetch_assoc($kamar_kosong)) { ?>
                    <option value="<?php echo $k['id']; ?>">
                        <?php echo htmlspecialchars($k['no_kamar'] . " (" . $k['tipe'] . ") - Rp " . number_format($k['harga'], 0, ',', '.')); ?>
                    </option>
                <?php } ?>
            </select>

            <label for="tanggal_masuk">Tanggal masuk</label>
            <input type="date" id="tanggal_masuk" name="tanggal_masuk" value="<?php echo date('Y-m-d'); ?>" required>

            <button type="submit" name="tempatkan" class="tombol-utama">Tempatkan</button>
        </form>
    <?php } ?>
</div>

<div class="panel">
    <h2>Daftar penghuni</h2>
    <?php if (mysqli_num_rows($daftar_penghuni) == 0) { ?>
        <p class="kosong">Belum ada penghuni yang mendaftar.</p>
    <?php } else { ?>
        <input type="text" class="cari-tabel" data-tabel="tabelPenghuni" placeholder="Cari nama, email, atau kamar...">
        <div class="bungkus-tabel">
            <table id="tabelPenghuni">
                <tr><th>Nama</th><th>Email</th><th>No. HP</th><th>Kamar</th><th>Masuk</th><th></th></tr>
                <?php while ($p = mysqli_fetch_assoc($daftar_penghuni)) { ?>
                    <tr class="baris-data">
                        <td><?php echo htmlspecialchars($p['nama_lengkap']); ?></td>
                        <td><?php echo htmlspecialchars($p['email']); ?></td>
                        <td><?php echo htmlspecialchars($p['no_hp'] ?? '-'); ?></td>
                        <td><?php echo $p['no_kamar'] ? htmlspecialchars($p['no_kamar']) : '<em>belum ada</em>'; ?></td>
                        <td><?php echo $p['tanggal_masuk'] ? date("d-m-Y", strtotime($p['tanggal_masuk'])) : '-'; ?></td>
                        <td>
                            <?php if ($p['rental_id']) { ?>
                                <form method="POST">
                                    <input type="hidden" name="rental_id" value="<?php echo $p['rental_id']; ?>">
                                    <button type="submit" name="akhiri" class="tombol-utama" data-konfirmasi="Akhiri sewa penghuni ini? Kamarnya jadi kosong."
                                            style="width:auto; margin:0; padding:6px 12px; background:var(--warna-bahaya);">Akhiri sewa</button>
                                </form>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>
</div>

<?php include "../includes/footer.php"; ?>