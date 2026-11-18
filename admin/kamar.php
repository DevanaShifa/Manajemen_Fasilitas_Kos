<?php
//nyambung ke db + nyalain session
include "../includes/koneksi.php";
//ini isinya fungsi buat "satpam"
include "../includes/cek_akses.php";
//cuma admin yang boleh masuk, penghuni/org yg blm login di lempar ke hal login
wajib_login('admin', '../auth/login.php');

// status yg noleh dipilih sama admin
//"terisi" sengaja ga ada karena status terisi tuh otomatis diatur waktu penghuni ditempatkan di menu penghuni
$status_boleh = array('kosong', 'perbaikan');
//buat pesan error, kalo masih kosong berarti blm ada masalah
$pesan_error = "";


//fungsi pengecek data, kaya nanya ke db datanya ada gasi, ntar hasilnya true or false
//dipakai 2x di bawah (cek nomor kembar & cek riwayat sewa), biar ga nulis kode yg sama berulang-ulang
//tanda tanya (?) di query itu tempat nilai asli diisi belakangan lewat bind_param,
// biar apa pun yang diketik user ga bisa dipakai buat ngerusak database (SQL injection).
function ada_data($koneksi, $sql, $tipe, ...$nilai) //"...$nilai" (titik tiga) artinya fungsi ini boleh dikasih nilai berapa pun jumlahnya.
{
    $perintah = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($perintah, $tipe, ...$nilai);
    mysqli_stmt_execute($perintah);
    return mysqli_num_rows(mysqli_stmt_get_result($perintah)) > 0;
}

// FOTO: fungsi buat ambil nama file foto sebuah kamar dari db ("" kalo blm ada fotonya)
// dipakai pas edit (buat tau foto lamanya) dan pas hapus (buat tau file mana yg dibuang)
function foto_kamar($koneksi, $id)
{
    $perintah = mysqli_prepare($koneksi, "SELECT foto FROM rooms WHERE id = ?");
    mysqli_stmt_bind_param($perintah, "i", $id);
    mysqli_stmt_execute($perintah);
    $baris = mysqli_fetch_assoc(mysqli_stmt_get_result($perintah));
    return ($baris && $baris['foto'] != null) ? $baris['foto'] : "";
}

// FOTO: fungsi buat ngehapus file foto dari folder uploads
// basename() = ambil nama filenya aja, jaga-jaga biar ga bisa nunjuk ke folder lain
// is_file() = cek filenya beneran ada, biar ga error kalo ternyata udh kehapus
function hapus_file_foto($nama)
{
    if ($nama != "") {
        $lokasi = __DIR__ . "/../uploads/" . basename($nama);
        if (is_file($lokasi)) {
            unlink($lokasi);
        }
    }
}

// FOTO: fungsi buat ngurus upload foto. hasilnya ada 3 kemungkinan:
//   "" (kosong)       = admin ga milih foto, boleh (foto bkn wajib)
//   false             = ada yg salah (kegedean / bkn jpg-png), alasannya diisi ke $pesan
//   "kamar_xxx.jpg"   = foto berhasil disimpen, ini nama filenya
// tanda & di &$pesan artinya $pesan bisa diisi dari dalam fungsi, dan isinya kebawa keluar
function proses_foto($berkas, &$pesan)
{
    // ga ada file yg dipilih = lewati aja
    if (!$berkas || $berkas['error'] == UPLOAD_ERR_NO_FILE) {
        return "";
    }
    // kegedean menurut pengaturan server (php.ini)
    if ($berkas['error'] == UPLOAD_ERR_INI_SIZE || $berkas['error'] == UPLOAD_ERR_FORM_SIZE) {
        $pesan = "Ukuran foto maksimal 2 MB.";
        return false;
    }
    if ($berkas['error'] != 0) {
        $pesan = "Upload foto gagal, coba lagi.";
        return false;
    }
    // batas 2 MB (2 x 1024 x 1024 byte)
    if ($berkas['size'] > 2 * 1024 * 1024) {
        $pesan = "Ukuran foto maksimal 2 MB.";
        return false;
    }

    // cek 2 lapis: ekstensi (.jpg/.png) DAN isi file aslinya (mime_content_type)
    // kalo cuma cek ekstensi, org bisa ganti nama file apa pun jadi .jpg
    $boleh = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png');
    $ekstensi = strtolower(pathinfo($berkas['name'], PATHINFO_EXTENSION));
    $jenis_asli = mime_content_type($berkas['tmp_name']);
    if (!isset($boleh[$ekstensi]) || $boleh[$ekstensi] != $jenis_asli) {
        $pesan = "Foto harus berupa JPG atau PNG.";
        return false;
    }

    // nama file diacak biar ga bentrok sama foto lain dan ga bisa ditebak orang
    $nama_file = "kamar_" . bin2hex(random_bytes(6)) . "." . $ekstensi;
    move_uploaded_file($berkas['tmp_name'], __DIR__ . "/../uploads/" . $nama_file);
    return $nama_file;
}

//mode buat edit: ambil data kamar yg mau diubah
// trs pas tombol "Edit" diklik, alamatnya jadi kamar.php?edit=3.
$kamar_edit = null;
if (isset($_GET['edit'])) {
    $id_edit = (int) $_GET['edit']; //int dipake biar yang masuk pasti angka, bkn tulisan aneh dari hal web
    $ambil = mysqli_prepare($koneksi, "SELECT * FROM rooms WHERE id = ?");
    mysqli_stmt_bind_param($ambil, "i", $id_edit);
    mysqli_stmt_execute($ambil);
    $kamar_edit = mysqli_fetch_assoc(mysqli_stmt_get_result($ambil));
}






// 1. Tambah kamar baru (CREATE)
if (isset($_POST['tambah_kamar'])) { //cuma jalan kalo tombol "Tambah" di klik
    //trim() buat buang spasi di pinggir
    $no = trim($_POST['no_kamar']);
    $tipe = trim($_POST['tipe']);
    $harga = (int) $_POST['harga']; //int() biar harga pasti angka
    $ukuran = trim($_POST['ukuran']);
    $deskripsi = trim($_POST['deskripsi']);

    if ($no == "" || $tipe == "" || $harga <= 0) {
        $pesan_error = "Nomor kamar, tipe, dan harga wajib diisi.";
    } else {
        //no kamar ga bole kembar, jdi di cek dulu sblm di simpan
        $cek = mysqli_prepare($koneksi, "SELECT id FROM rooms WHERE no_kamar = ?");
        mysqli_stmt_bind_param($cek, "s", $no);
        mysqli_stmt_execute($cek);
        if (mysqli_num_rows(mysqli_stmt_get_result($cek)) > 0) {
            $pesan_error = "Nomor kamar itu sudah ada.";
        } else {
            // FOTO: foto baru diurus SETELAH semua cek di atas lolos
            // jdi klo datanya ditolak, ga ada file foto yatim yg nyangkut di folder uploads
            // file yg dikirim lewat form ada di $_FILES (bkn di $_POST)
            $foto_baru = proses_foto(isset($_FILES['foto']) ? $_FILES['foto'] : null, $pesan_error);

            // FOTO: !== false (tanda seru + dua sama dengan) = bener2 false, bkn cuma kosong
            // klo false, $pesan_error udh diisi sama proses_foto(), jdi berhenti di sini
            if ($foto_baru !== false) {
                // FOTO: klo ga ada foto, simpen NULL (kosong beneran) ke db
                $foto_simpan = ($foto_baru == "") ? null : $foto_baru;

                // "ssisss" = tipe tiap tanda tanya:
                // s= string(teks), i= int(angka)
                $tambah = mysqli_prepare($koneksi, "INSERT INTO rooms (no_kamar, tipe, harga, ukuran, deskripsi, foto) VALUES (?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($tambah, "ssisss", $no, $tipe, $harga, $ukuran, $deskripsi, $foto_simpan);
                mysqli_stmt_execute($tambah);

                //trs pindah halaman after simpan(post/redirect/get)
                // biar klo halaman di re-fresh, form nya ga kekirim ulang dan kamar ga kesiman double
                header("Location: kamar.php?pesan=ok");
                exit;
            }
        }
    }
}

// 2. Simpan Perubahan Kamar (UPDATE)
// jalan klo tombol "simpan perubahan" (tombol ini cuma muncul di mode edit) di klik 
if (isset($_POST['simpan_perubahan'])) {
    // id dateng dari input tersembunyi di formulir, biar PHP tau kamar MANA yang diubah
    $id = (int) $_POST['id'];
    $no = trim($_POST['no_kamar']);
    $tipe = trim($_POST['tipe']);
    $harga = (int) $_POST['harga'];
    $ukuran = trim($_POST['ukuran']);
    $deskripsi = trim($_POST['deskripsi']);

    // FOTO: catat foto lamanya dulu, dipake buat nampilin di form dan buat dibuang klo diganti
    $foto_lama = foto_kamar($koneksi, $id);

    // isi form disimpen lagi ke $kamar_edit
    //jdi klo ada error di bawah, form tetap mode edit dan ketikan user ga ilang
    $kamar_edit = array('id' => $id, 'no_kamar' => $no, 'tipe' => $tipe,
                        'harga' => $harga, 'ukuran' => $ukuran, 'deskripsi' => $deskripsi,
                        'foto' => $foto_lama);

    if ($no == "" || $tipe == "" || $harga <= 0) {
        $pesan_error = "Nomor kamar, tipe, dan harga wajib diisi.";
    } elseif (ada_data($koneksi, "SELECT id FROM rooms WHERE no_kamar = ? AND id != ?", "si", $no, $id)) {
        //"id != ?" artinya: nomor ini boleh sama dengan nomor kamar ini sendiri
        //(misal cuma ganti harga, nomor ga diubah), tapi ga boleh sama dengan kamar LAIN.
        $pesan_error = "Nomor kamar itu sudah dipakai kamar lain.";
    } else {
        // FOTO: cek apakah admin milih foto baru
        $foto_baru = proses_foto(isset($_FILES['foto']) ? $_FILES['foto'] : null, $pesan_error);

        if ($foto_baru !== false) {
            if ($foto_baru != "") {
                // FOTO: ada foto baru, kolom foto ikut diubah, terus file foto lamanya dibuang dari folder uploads
                // WHERE id = ? penting banget. Tanpa ini, SEMUA kamar ikut berubah.
                $ubah = mysqli_prepare($koneksi, "UPDATE rooms SET no_kamar = ?, tipe = ?, harga = ?, ukuran = ?, deskripsi = ?, foto = ? WHERE id = ?");
                mysqli_stmt_bind_param($ubah, "ssisssi", $no, $tipe, $harga, $ukuran, $deskripsi, $foto_baru, $id);
                mysqli_stmt_execute($ubah);
                hapus_file_foto($foto_lama);
            } else {
                // FOTO: ga ada foto baru, kolom foto SENGAJA ga disentuh, jdi foto lama tetep aman
                $ubah = mysqli_prepare($koneksi, "UPDATE rooms SET no_kamar = ?, tipe = ?, harga = ?, ukuran = ?, deskripsi = ? WHERE id = ?");
                mysqli_stmt_bind_param($ubah, "ssissi", $no, $tipe, $harga, $ukuran, $deskripsi, $id);
                mysqli_stmt_execute($ubah);
            }
            header("Location: kamar.php?pesan=ok");
            exit;
        }
    }
}

//3. Hapus Kamar (DELETE)
if (isset($_POST['hapus_kamar'])) {
    $id = (int) $_POST['id'];

    // Kamar yang pernah/lagi disewa ga boleh dihapus. Di database, tabel rentals
    // nyambung ke tabel rooms. kalau kamarnya ilang, riwayat sewa dan tagihannya
    // jadi rusak (nunjuk ke kamar yang udah ga ada).
    if (ada_data($koneksi, "SELECT id FROM rentals WHERE room_id = ?", "i", $id)) {
        $pesan_error = "Kamar ini punya riwayat sewa, jadi tidak bisa dihapus.";
    } else {
        // try/catch itu jaring pengaman. kalau ternyata database tetap nolak
        // (misal masih dipakai tabel lain), yang muncul pesan sopan, bukan layar error PHP.
        try {
            // FOTO: catat nama fotonya SEBELUM barisnya dihapus
            $foto_dibuang = foto_kamar($koneksi, $id);

            $hapus = mysqli_prepare($koneksi, "DELETE FROM rooms WHERE id = ?");
            mysqli_stmt_bind_param($hapus, "i", $id);
            mysqli_stmt_execute($hapus);

            // FOTO: baru buang file fotonya setelah kamar berhasil dihapus
            // biar folder uploads ga numpuk foto yg udh ga kepake
            hapus_file_foto($foto_dibuang);

            header("Location: kamar.php?pesan=terhapus");
            exit;
        } catch (mysqli_sql_exception $e) {
            $pesan_error = "Kamar ini masih dipakai data lain, jadi tidak bisa dihapus.";
        }
    }
}





// 4. Ubah status kamar (kamar yang sedang dihuni tidak bisa diubah dari sini)
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

// 5. Ambil daftar kamar buat ditampilkan (READ)
// LEFT JOIN artinya: kamar yang kosong (ga punya penghuni) tetap ikut tampil.
// Kalau pakai JOIN biasa, kamar kosong malah hilang dari daftar.
// Bagian "rentals.status = 'aktif'" bikin yang diambil cuma penghuni yang lagi nempatin sekarang.
$daftar_kamar = mysqli_query($koneksi, "SELECT rooms.*, tenants.nama_lengkap
                                        FROM rooms
                                        LEFT JOIN rentals ON rentals.room_id = rooms.id AND rentals.status = 'aktif'
                                        LEFT JOIN tenants ON rentals.tenant_id = tenants.id
                                        ORDER BY rooms.no_kamar");

$sedang_edit = !empty($kamar_edit);


$judul_halaman = "Kelola Kamar";
include "../includes/header.php";
?>

<h1>Kelola Kamar</h1>
<p class="keterangan">Daftar kamar kos. Penghuni ditempatkan ke kamar lewat menu Penghuni.</p>

<!-- pesannya dibedain: kalo ?pesan=terhapus tulisannya "Kamar dihapus.", selain itu "Perubahan disimpan." -->
<?php if (isset($_GET['pesan'])) { ?>
    <div class="pesan pesan-sukses"><?php echo ($_GET['pesan'] == 'terhapus') ? 'Kamar dihapus.' : 'Perubahan disimpan.'; ?></div>
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
                <!-- FOTO: kolom Foto ditambah di paling depan. Kolom Aksi juga ditambah, soalnya isi kolom Aksi (Edit/Hapus) udh ada di bawah -->
                <tr><th>Foto</th><th>No.</th><th>Tipe</th><th>Ukuran</th><th>Harga</th><th>Status</th><th>Aksi</th></tr>
                <?php while ($k = mysqli_fetch_assoc($daftar_kamar)) { ?>
                    <tr>
                        <!-- FOTO: kalo kamar punya foto, tampil gambar kecil. Kalo ga, tampil tanda "-"
                             object-fit:cover = gambar dipotong rapi, ga gepeng -->
                        <td>
                            <?php if (!empty($k['foto'])) { ?>
                                <img src="../uploads/<?php echo htmlspecialchars($k['foto']); ?>" alt="Foto kamar"
                                     style="width:64px; height:48px; object-fit:cover; border-radius:6px;">
                            <?php } else { ?>
                                -
                            <?php } ?>
                        </td>
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
                         <td>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <!-- edit itu cuma tautan biasa ke kamar.php?edit=ID. #form-kamar bikin halaman langsung turun ke formulir -->
                                <a href="kamar.php?edit=<?php echo $k['id']; ?>#form-kamar">Edit</a>

                                <!-- tombol Hapus cuma muncul buat kamar yang ga lagi dihuni -->
                                <?php if ($k['nama_lengkap'] == null) { ?>
                                    <!-- hapus dibikin tombol form (POST), bukan tautan biasa. Alasannya tautan bisa
                                         kepencet / kebuka otomatis sama browser, jadi data bisa kehapus tanpa sengaja. -->
                                    <form method="POST">
                                        <input type="hidden" name="id" value="<?php echo $k['id']; ?>">
                                        <!-- data-konfirmasi dibaca script.js (bagian no. 8): muncul kotak "Yakin?"
                                             dan kalau diklik Cancel, form-nya batal dikirim -->
                                        <button type="submit" name="hapus_kamar" class="tombol-utama"
                                                data-konfirmasi="Yakin hapus kamar <?php echo htmlspecialchars($k['no_kamar']); ?>?"
                                                style="width:auto; margin:0; padding:6px 12px; background:var(--warna-bahaya);">Hapus</button>
                                    </form>
                                <?php } ?>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>
</div>

<div class="panel" id="form-kamar">
    <h2><?php echo $sedang_edit ? "Edit kamar" : "Tambah kamar"; ?></h2>
    <!-- FOTO: enctype="multipart/form-data" WAJIB klo form mau ngirim file
         tanpa ini, file fotonya ga ikut kekirim ke PHP, yg kekirim cuma teks -->
    <form method="POST" enctype="multipart/form-data">
        <!-- Input tersembunyi buat bawa id kamar, biar PHP tau kamar mana yang diubah. Cuma ada di mode edit. -->
        <?php if ($sedang_edit) { ?>
            <input type="hidden" name="id" value="<?php echo $kamar_edit['id']; ?>">
        <?php } ?>

        <label for="no_kamar">Nomor kamar</label>
        <input type="text" id="no_kamar" name="no_kamar" maxlength="10" placeholder="Contoh: A1"
               value="<?php echo $sedang_edit ? htmlspecialchars($kamar_edit['no_kamar']) : ''; ?>" required>

        <label for="tipe">Tipe</label>
        <input type="text" id="tipe" name="tipe" placeholder="Contoh: Standar, AC, Kamar mandi dalam"
               value="<?php echo $sedang_edit ? htmlspecialchars($kamar_edit['tipe']) : ''; ?>" required>

        <label for="harga">Harga per bulan (Rp)</label>
        <input type="number" id="harga" name="harga" min="1"
               value="<?php echo $sedang_edit ? (int) $kamar_edit['harga'] : ''; ?>" required>

        <label for="ukuran">Ukuran (boleh kosong)</label>
        <input type="text" id="ukuran" name="ukuran" placeholder="Contoh: 3x4 m"
               value="<?php echo $sedang_edit ? htmlspecialchars($kamar_edit['ukuran'] ?? '') : ''; ?>">

        <!-- Textarea beda sendiri: isinya ditaruh DI ANTARA tag, bukan di atribut value -->
        <label for="deskripsi">Deskripsi (boleh kosong)</label>
        <textarea id="deskripsi" name="deskripsi" rows="3"><?php echo $sedang_edit ? htmlspecialchars($kamar_edit['deskripsi'] ?? '') : ''; ?></textarea>

        <!-- FOTO: kolom upload model kotak. Seluruh kotak itu sebenernya <label> buat input file,
        jadi klik di mana aja dalam kotak = buka kotak pilih file.
        accept=".jpg..." cuma bantuan di kotak pilih file, pengecekan yg beneran ada di PHP (proses_foto) -->
        <?php $ada_foto = ($sedang_edit && !empty($kamar_edit['foto'])); ?>
        <label for="foto">Foto kamar (boleh kosong)</label>
        <label for="foto" class="zona-foto <?php echo $ada_foto ? 'ada-foto' : ''; ?>" id="zonaFoto">
            <input type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png">

            <!-- pratinjau: kalo lagi edit dan udh ada foto, langsung keisi foto yg sekarang.
                 JavaScript yg ngatur gambar ini berubah pas user milih foto baru (script.js bagian 9) -->
            <img class="foto-pratinjau" id="fotoPratinjau" alt="Pratinjau foto"
                 <?php if ($ada_foto) { ?>src="../uploads/<?php echo htmlspecialchars($kamar_edit['foto']); ?>"<?php } ?>>

            <span class="zona-ikon">🖼️</span>
            <span class="teks-kosong"><strong>Klik untuk pilih foto</strong> atau tarik ke sini</span>
            <span class="teks-ganti"><strong>Klik atau tarik foto baru</strong> untuk mengganti</span>
            <small>JPG atau PNG, maksimal 2 MB</small>
            <span class="nama-file" id="namaFile"></span>
        </label>
        <small class="pesan-foto" id="pesanFoto"></small>

        <!-- Nama tombol ganti sesuai mode. Nama inilah yang dicek isset() di atas buat nentuin
             bagian PHP mana yang jalan: tambah_kamar (bagian 1) atau simpan_perubahan (bagian 2) -->
        <button type="submit" name="<?php echo $sedang_edit ? 'simpan_perubahan' : 'tambah_kamar'; ?>" class="tombol-utama">
            <?php echo $sedang_edit ? "Simpan perubahan" : "Tambah"; ?>
        </button>
        <?php if ($sedang_edit) { ?>
            <p class="tautan-bawah"><a href="kamar.php">Batal edit</a></p>
        <?php } ?>
    </form>
</div>

<?php include "../includes/footer.php"; ?>