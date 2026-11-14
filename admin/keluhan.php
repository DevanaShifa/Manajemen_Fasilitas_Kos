<?php
include "../includes/koneksi.php";
include "../includes/cek_akses.php";
wajib_login('admin', '../auth/login.php');

// Kalau admin mengubah status keluhan
if (isset($_POST['ubah_status'])) {
    $id = (int) $_POST['id'];
    $status = $_POST['status'];
    $boleh = array('diterima', 'diproses', 'selesai');
    if (in_array($status, $boleh)) {
        $ubah = mysqli_prepare($koneksi, "UPDATE complains SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($ubah, "si", $status, $id);
        mysqli_stmt_execute($ubah);
    }
    header("Location: keluhan.php?pesan=diubah");
    exit;
}

$daftar = mysqli_query($koneksi, "SELECT complains.id, complains.judul, complains.deskripsi, complains.status, complains.created_at, tenants.nama_lengkap
                                  FROM complains
                                  JOIN tenants ON complains.tenant_id = tenants.id
                                  ORDER BY complains.created_at DESC");

$judul_halaman = "Kelola Keluhan";
include "../includes/header.php";
?>

<h1>Kelola Keluhan</h1>
<p class="keterangan">Semua keluhan dari penghuni. Ubah statusnya setelah ditangani.</p>

<?php if (isset($_GET['pesan']) && $_GET['pesan'] == "diubah") { ?>
    <div class="pesan pesan-sukses">Status keluhan diperbarui.</div>
<?php } ?>

<div class="panel">
    <?php if (mysqli_num_rows($daftar) == 0) { ?>
        <p class="kosong">Belum ada keluhan yang masuk.</p>
    <?php } else { ?>
        <input type="text" class="cari-tabel" data-tabel="tabelKeluhan" placeholder="Cari keluhan atau nama penghuni...">
        <div class="bungkus-tabel">
            <table id="tabelKeluhan">
                <tr><th>Penghuni</th><th>Keluhan</th><th>Isi</th><th>Tanggal</th><th>Status</th></tr>
                <?php while ($k = mysqli_fetch_assoc($daftar)) { ?>
                    <tr class="baris-data">
                        <td><?php echo htmlspecialchars($k['nama_lengkap']); ?></td>
                        <td><?php echo htmlspecialchars($k['judul']); ?></td>
                        <td><?php echo htmlspecialchars($k['deskripsi']); ?></td>
                        <td><?php echo date("d-m-Y", strtotime($k['created_at'])); ?></td>
                        <td>
                            <form method="POST" style="display:flex; gap:6px; align-items:center;">
                                <input type="hidden" name="id" value="<?php echo $k['id']; ?>">
                                <select name="status" style="margin:0; width:auto;">
                                    <?php foreach (array('diterima', 'diproses', 'selesai') as $s) { ?>
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