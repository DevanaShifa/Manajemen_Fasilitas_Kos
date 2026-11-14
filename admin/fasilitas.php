<?php
include "../includes/koneksi.php";
include "../includes/cek_akses.php";
wajib_login('admin', '../auth/login.php');

$jenis_boleh = array('kamar', 'umum');
$kondisi_boleh = array('baik', 'rusak', 'perbaikan');
$status_boleh = array('diterima', 'diproses', 'selesai');

// Tambah fasilitas baru
if (isset($_POST['tambah_fasilitas'])) {
    $nama = trim($_POST['nama_fasilitas']);
    $jenis = $_POST['jenis'];
    $kondisi = $_POST['kondisi'];
    $ket = trim($_POST['keterangan']);
    if ($nama != "" && in_array($jenis, $jenis_boleh) && in_array($kondisi, $kondisi_boleh)) {
        $tambah = mysqli_prepare($koneksi, "INSERT INTO fasilities (nama_fasilitas, jenis, kondisi, keterangan) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($tambah, "ssss", $nama, $jenis, $kondisi, $ket);
        mysqli_stmt_execute($tambah);
    }
    header("Location: fasilitas.php?pesan=fasilitas_ok");
    exit;
}

// Ubah kondisi fasilitas
if (isset($_POST['ubah_kondisi'])) {
    $id = (int) $_POST['id'];
    $kondisi = $_POST['kondisi'];
    if (in_array($kondisi, $kondisi_boleh)) {
        $ubah = mysqli_prepare($koneksi, "UPDATE fasilities SET kondisi = ? WHERE id = ?");
        mysqli_stmt_bind_param($ubah, "si", $kondisi, $id);
        mysqli_stmt_execute($ubah);
    }
    header("Location: fasilitas.php?pesan=fasilitas_ok");
    exit;
}

// Ubah status keluhan
if (isset($_POST['ubah_status'])) {
    $id = (int) $_POST['id'];
    $status = $_POST['status'];
    if (in_array($status, $status_boleh)) {
        $ubah = mysqli_prepare($koneksi, "UPDATE complains SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($ubah, "si", $status, $id);
        mysqli_stmt_execute($ubah);
    }
    header("Location: fasilitas.php?pesan=keluhan_ok#keluhan");
    exit;
}

$daftar_fasilitas = mysqli_query($koneksi, "SELECT * FROM fasilities ORDER BY jenis, nama_fasilitas");

$daftar_keluhan = mysqli_query($koneksi, "SELECT complains.id, complains.judul, complains.deskripsi, complains.status, complains.created_at,
                                                 tenants.nama_lengkap, fasilities.nama_fasilitas
                                          FROM complains
                                          JOIN tenants ON complains.tenant_id = tenants.id
                                          LEFT JOIN fasilities ON complains.fasilitas_id = fasilities.id
                                          ORDER BY complains.created_at DESC");

$judul_halaman = "Fasilitas & Keluhan";
include "../includes/header.php";
$gaya_form_kecil = "display:flex; gap:6px; align-items:center;";
?>

<h1>Fasilitas &amp; Keluhan</h1>
<p class="keterangan">Kelola fasilitas kos dan tanggapi keluhan penghuni di satu tempat.</p>

<?php if (isset($_GET['pesan'])) { ?>
    <div class="pesan pesan-sukses">Perubahan disimpan.</div>
<?php } ?>

<div class="panel">
    <h2>Fasilitas</h2>
    <?php if (mysqli_num_rows($daftar_fasilitas) == 0) { ?>
        <p class="kosong">Belum ada fasilitas. Tambahkan lewat formulir di bawah.</p>
    <?php } else { ?>
        <div class="bungkus-tabel">
            <table>
                <tr><th>Fasilitas</th><th>Jenis</th><th>Keterangan</th><th>Kondisi</th></tr>
                <?php while ($f = mysqli_fetch_assoc($daftar_fasilitas)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($f['nama_fasilitas']); ?></td>
                        <td><?php echo ucfirst($f['jenis']); ?></td>
                        <td><?php echo htmlspecialchars($f['keterangan'] ?? ''); ?></td>
                        <td>
                            <form method="POST" style="<?php echo $gaya_form_kecil; ?>">
                                <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                                <select name="kondisi" style="margin:0; width:auto;">
                                    <?php foreach ($kondisi_boleh as $k) { ?>
                                        <option value="<?php echo $k; ?>" <?php echo ($f['kondisi'] == $k) ? 'selected' : ''; ?>><?php echo ucfirst($k); ?></option>
                                    <?php } ?>
                                </select>
                                <button type="submit" name="ubah_kondisi" class="tombol-utama" style="width:auto; margin:0; padding:8px 14px;">Simpan</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>

    <h2 style="margin-top:22px;">Tambah fasilitas</h2>
    <form method="POST">
        <label for="nama_fasilitas">Nama fasilitas</label>
        <input type="text" id="nama_fasilitas" name="nama_fasilitas" placeholder="Contoh: AC Kamar 3, Dapur bersama" required>

        <label for="jenis">Jenis</label>
        <select id="jenis" name="jenis">
            <option value="kamar">Kamar</option>
            <option value="umum">Umum</option>
        </select>

        <label for="kondisi">Kondisi</label>
        <select id="kondisi" name="kondisi">
            <option value="baik">Baik</option>
            <option value="rusak">Rusak</option>
            <option value="perbaikan">Perbaikan</option>
        </select>

        <label for="keterangan">Keterangan (boleh kosong)</label>
        <input type="text" id="keterangan" name="keterangan">

        <button type="submit" name="tambah_fasilitas" class="tombol-utama">Tambah</button>
    </form>
</div>

<div class="panel" id="keluhan">
    <h2>Keluhan penghuni</h2>
    <?php if (mysqli_num_rows($daftar_keluhan) == 0) { ?>
        <p class="kosong">Belum ada keluhan yang masuk.</p>
    <?php } else { ?>
        <input type="text" class="cari-tabel" data-tabel="tabelKeluhan" placeholder="Cari keluhan, fasilitas, atau nama penghuni...">
        <div class="bungkus-tabel">
            <table id="tabelKeluhan">
                <tr><th>Penghuni</th><th>Keluhan</th><th>Fasilitas</th><th>Isi</th><th>Tanggal</th><th>Status</th></tr>
                <?php while ($k = mysqli_fetch_assoc($daftar_keluhan)) { ?>
                    <tr class="baris-data">
                        <td><?php echo htmlspecialchars($k['nama_lengkap']); ?></td>
                        <td><?php echo htmlspecialchars($k['judul']); ?></td>
                        <td><?php echo htmlspecialchars($k['nama_fasilitas'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($k['deskripsi']); ?></td>
                        <td><?php echo date("d-m-Y", strtotime($k['created_at'])); ?></td>
                        <td>
                            <form method="POST" style="<?php echo $gaya_form_kecil; ?>">
                                <input type="hidden" name="id" value="<?php echo $k['id']; ?>">
                                <select name="status" style="margin:0; width:auto;">
                                    <?php foreach ($status_boleh as $s) { ?>
                                        <option value="<?php echo $s; ?>" <?php echo ($k['status'] == $s) ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                                    <?php } ?>
                                </select>
                                <button type="submit" name="ubah_status" class="tombol-utama" style="width:auto; margin:0; padding:8px 14px;">Simpan</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>
</div>

<?php include "../includes/footer.php"; ?>