<?php
//nyambung ke db + nyalain session
include "../includes/koneksi.php";
//ini isinya fungsi buat "satpam"
include "../includes/cek_akses.php";
//cuma admin yg boleh masuk, penghuni/org yg blm login dilempar ke hal login
wajib_login('admin', '../auth/login.php');

//daftar pilihan yg diizinin. dipake buat dropdown sekaligus buat ngecek kiriman dari form
$jenis_boleh = array('kamar', 'umum');
$kondisi_boleh = array('baik', 'rusak', 'perbaikan');
$status_boleh = array('diterima', 'diproses', 'selesai');

//BARU: wadah buat pesan error, klo masih kosong berarti blm ada masalah
$pesan_error = "";

//BARU: mode buat edit: ambil data fasilitas yg mau diubah
// pas tombol "Edit" diklik, alamatnya jadi fasilitas.php?edit=3
$fasilitas_edit = null;
if (isset($_GET['edit'])) {
    $id_edit = (int) $_GET['edit']; //int dipake biar yg masuk pasti angka, bkn tulisan aneh dari alamat web
    $ambil = mysqli_prepare($koneksi, "SELECT * FROM fasilities WHERE id = ?");
    mysqli_stmt_bind_param($ambil, "i", $id_edit);
    mysqli_stmt_execute($ambil);
    $fasilitas_edit = mysqli_fetch_assoc(mysqli_stmt_get_result($ambil));
}


// 1. Tambah fasilitas baru (CREATE)
if (isset($_POST['tambah_fasilitas'])) { //cuma jalan klo tombol "Tambah" diklik
    $nama = trim($_POST['nama_fasilitas']);
    $jenis = $_POST['jenis'];
    $kondisi = $_POST['kondisi'];
    $ket = trim($_POST['keterangan']);
    //in_array = cek jenis & kondisi yg dikirim memang salah satu dari yg diizinin
    if ($nama != "" && in_array($jenis, $jenis_boleh) && in_array($kondisi, $kondisi_boleh)) {
        $tambah = mysqli_prepare($koneksi, "INSERT INTO fasilities (nama_fasilitas, jenis, kondisi, keterangan) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($tambah, "ssss", $nama, $jenis, $kondisi, $ket);
        mysqli_stmt_execute($tambah);
    }
    header("Location: fasilitas.php?pesan=fasilitas_ok");
    exit;
}

// 2. BARU: Simpan perubahan fasilitas (UPDATE)
// jalan klo tombol "Simpan perubahan" (tombol ini cuma muncul di mode edit) diklik
if (isset($_POST['simpan_perubahan'])) {
    //id dateng dari input tersembunyi di form, biar PHP tau fasilitas MANA yg diubah
    $id = (int) $_POST['id'];
    $nama = trim($_POST['nama_fasilitas']);
    $jenis = $_POST['jenis'];
    $kondisi = $_POST['kondisi'];
    $ket = trim($_POST['keterangan']);

    //isi form disimpen lagi ke $fasilitas_edit
    //jdi klo ada error di bawah, form tetep mode edit dan ketikan user ga ilang
    $fasilitas_edit = array('id' => $id, 'nama_fasilitas' => $nama, 'jenis' => $jenis,
                            'kondisi' => $kondisi, 'keterangan' => $ket);

    if ($nama == "") {
        $pesan_error = "Nama fasilitas wajib diisi.";
    } elseif (!in_array($jenis, $jenis_boleh) || !in_array($kondisi, $kondisi_boleh)) {
        $pesan_error = "Jenis atau kondisi tidak valid.";
    } else {
        //WHERE id = ? penting banget. Tanpa ini, SEMUA fasilitas ikut berubah.
        $ubah = mysqli_prepare($koneksi, "UPDATE fasilities SET nama_fasilitas = ?, jenis = ?, kondisi = ?, keterangan = ? WHERE id = ?");
        mysqli_stmt_bind_param($ubah, "ssssi", $nama, $jenis, $kondisi, $ket, $id);
        mysqli_stmt_execute($ubah);
        header("Location: fasilitas.php?pesan=fasilitas_ok");
        exit;
    }
}

// 3. BARU: Hapus fasilitas (DELETE)
if (isset($_POST['hapus_fasilitas'])) {
    $id = (int) $_POST['id'];

    //try/catch itu jaring pengaman. klo ternyata database tetep nolak,
    //yg muncul pesan sopan, bukan layar error PHP
    try {
        //keluhan lama yg nunjuk ke fasilitas ini dilepas dulu (fasilitas_id jadi NULL).
        //jdi keluhannya TETEP ADA di riwayat, cuma ga terhubung ke fasilitas ini lagi.
        //tanpa langkah ini, database bisa nolak hapus karena keluhan masih nunjuk ke fasilitas ini
        $lepas = mysqli_prepare($koneksi, "UPDATE complains SET fasilitas_id = NULL WHERE fasilitas_id = ?");
        mysqli_stmt_bind_param($lepas, "i", $id);
        mysqli_stmt_execute($lepas);

        $hapus = mysqli_prepare($koneksi, "DELETE FROM fasilities WHERE id = ?");
        mysqli_stmt_bind_param($hapus, "i", $id);
        mysqli_stmt_execute($hapus);

        header("Location: fasilitas.php?pesan=fasilitas_hapus");
        exit;
    } catch (mysqli_sql_exception $e) {
        $pesan_error = "Fasilitas ini masih dipakai data lain, jadi tidak bisa dihapus.";
    }
}

// 4. Ubah kondisi fasilitas (lewat dropdown kecil di tiap baris tabel)
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

// 5. Ubah status keluhan
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

// 6. Ambil data buat ditampilkan (READ)
$daftar_fasilitas = mysqli_query($koneksi, "SELECT * FROM fasilities ORDER BY jenis, nama_fasilitas");

//LEFT JOIN fasilities: keluhan yg ga terkait fasilitas apa pun tetep ikut tampil
$daftar_keluhan = mysqli_query($koneksi, "SELECT complains.id, complains.judul, complains.deskripsi, complains.status, complains.created_at,
                                                 tenants.nama_lengkap, fasilities.nama_fasilitas
                                          FROM complains
                                          JOIN tenants ON complains.tenant_id = tenants.id
                                          LEFT JOIN fasilities ON complains.fasilitas_id = fasilities.id
                                          ORDER BY complains.created_at DESC");

//BARU: penanda "form lagi mode edit atau mode tambah?"
//!empty(...) = true klo $fasilitas_edit ada isinya. Dipake berkali-kali di form bawah.
$sedang_edit = !empty($fasilitas_edit);

$judul_halaman = "Fasilitas & Keluhan";
include "../includes/header.php";
$gaya_form_kecil = "display:flex; gap:6px; align-items:center;";
?>

<h1>Fasilitas &amp; Keluhan</h1>
<p class="keterangan">Kelola fasilitas kos dan tanggapi keluhan penghuni di satu tempat.</p>

<!-- BARU: pesannya dibedain. klo ?pesan=fasilitas_hapus tulisannya "Fasilitas dihapus.", selain itu "Perubahan disimpan." -->
<?php if (isset($_GET['pesan'])) { ?>
    <div class="pesan pesan-sukses"><?php echo ($_GET['pesan'] == 'fasilitas_hapus') ? 'Fasilitas dihapus.' : 'Perubahan disimpan.'; ?></div>
<?php } ?>
<!-- BARU: kotak error cuma muncul klo $pesan_error ada isinya -->
<?php if ($pesan_error != "") { ?>
    <div class="pesan pesan-error"><?php echo $pesan_error; ?></div>
<?php } ?>

<div class="panel">
    <h2>Fasilitas</h2>
    <?php if (mysqli_num_rows($daftar_fasilitas) == 0) { ?>
        <p class="kosong">Belum ada fasilitas. Tambahkan lewat formulir di bawah.</p>
    <?php } else { ?>
        <div class="bungkus-tabel">
            <table>
                <!-- BARU: kolom Aksi ditambah di paling belakang -->
                <tr><th>Fasilitas</th><th>Jenis</th><th>Keterangan</th><th>Kondisi</th><th>Aksi</th></tr>
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
                        <!-- BARU: kolom aksi (Edit dan Hapus) -->
                        <td>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <!-- edit itu cuma tautan biasa ke fasilitas.php?edit=ID. #form-fasilitas bikin halaman langsung turun ke formulir -->
                                <a href="fasilitas.php?edit=<?php echo $f['id']; ?>#form-fasilitas">Edit</a>

                                <!-- hapus dibikin tombol form (POST), bukan tautan biasa. Alasannya tautan bisa
                                     kepencet / kebuka otomatis sama browser, jadi data bisa kehapus tanpa sengaja. -->
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                                    <!-- data-konfirmasi dibaca script.js (bagian no. 8): muncul kotak "Yakin?"
                                         dan klo diklik Cancel, form-nya batal dikirim -->
                                    <button type="submit" name="hapus_fasilitas" class="tombol-utama"
                                            data-konfirmasi="Yakin hapus fasilitas <?php echo htmlspecialchars($f['nama_fasilitas']); ?>? Keluhan lama tetap ada, tapi tidak lagi terhubung ke fasilitas ini."
                                            style="width:auto; margin:0; padding:6px 12px; background:var(--warna-bahaya);">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>

    <!-- BARU: satu formulir dipake buat TAMBAH dan EDIT.
         Bedanya cuma: klo $sedang_edit = true, kolomnya keisi data lama dan tombolnya jadi "Simpan perubahan".
         Jadi ga perlu bikin 2 formulir yg isinya hampir sama. -->
    <h2 id="form-fasilitas" style="margin-top:22px;"><?php echo $sedang_edit ? "Edit fasilitas" : "Tambah fasilitas"; ?></h2>
    <form method="POST">
        <!-- input tersembunyi buat bawa id fasilitas, biar PHP tau fasilitas mana yg diubah. Cuma ada di mode edit. -->
        <?php if ($sedang_edit) { ?>
            <input type="hidden" name="id" value="<?php echo $fasilitas_edit['id']; ?>">
        <?php } ?>

        <label for="nama_fasilitas">Nama fasilitas</label>
        <input type="text" id="nama_fasilitas" name="nama_fasilitas" placeholder="Contoh: AC Kamar 3, Dapur bersama"
               value="<?php echo $sedang_edit ? htmlspecialchars($fasilitas_edit['nama_fasilitas']) : ''; ?>" required>

        <label for="jenis">Jenis</label>
        <select id="jenis" name="jenis">
            <!-- "selected" dipasang di pilihan yg sesuai sama data lama, biar dropdown-nya keisi pas edit -->
            <option value="kamar" <?php echo ($sedang_edit && $fasilitas_edit['jenis'] == 'kamar') ? 'selected' : ''; ?>>Kamar</option>
            <option value="umum" <?php echo ($sedang_edit && $fasilitas_edit['jenis'] == 'umum') ? 'selected' : ''; ?>>Umum</option>
        </select>

        <label for="kondisi">Kondisi</label>
        <select id="kondisi" name="kondisi">
            <option value="baik" <?php echo ($sedang_edit && $fasilitas_edit['kondisi'] == 'baik') ? 'selected' : ''; ?>>Baik</option>
            <option value="rusak" <?php echo ($sedang_edit && $fasilitas_edit['kondisi'] == 'rusak') ? 'selected' : ''; ?>>Rusak</option>
            <option value="perbaikan" <?php echo ($sedang_edit && $fasilitas_edit['kondisi'] == 'perbaikan') ? 'selected' : ''; ?>>Perbaikan</option>
        </select>

        <label for="keterangan">Keterangan (boleh kosong)</label>
        <input type="text" id="keterangan" name="keterangan"
               value="<?php echo $sedang_edit ? htmlspecialchars($fasilitas_edit['keterangan'] ?? '') : ''; ?>">

        <!-- nama tombol ganti sesuai mode. Nama inilah yg dicek isset() di atas buat nentuin
             bagian PHP mana yg jalan: tambah_fasilitas (bagian 1) atau simpan_perubahan (bagian 2) -->
        <button type="submit" name="<?php echo $sedang_edit ? 'simpan_perubahan' : 'tambah_fasilitas'; ?>" class="tombol-utama">
            <?php echo $sedang_edit ? "Simpan perubahan" : "Tambah"; ?>
        </button>
        <?php if ($sedang_edit) { ?>
            <p class="tautan-bawah"><a href="fasilitas.php">Batal edit</a></p>
        <?php } ?>
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