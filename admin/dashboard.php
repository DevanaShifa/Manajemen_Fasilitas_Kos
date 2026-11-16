<?php
include "../includes/koneksi.php";
include "../includes/cek_akses.php";

// Satpam: hanya admin yang boleh masuk. Penghuni atau orang yang belum login ditolak.
wajib_login('admin', '../auth/login.php');

// Fungsi kecil untuk menghitung jumlah data. Dipakai berulang di bawah.
// Pertanyaan SQL di sini tidak memakai ketikan pengguna, jadi aman dijalankan langsung.
function hitung_jumlah($koneksi, $perintah_sql)
{
    $hasil = mysqli_query($koneksi, $perintah_sql);
    $baris = mysqli_fetch_assoc($hasil);
    return $baris['total'];
}

// Hitung angka-angka untuk ringkasan
$jumlah_kamar_kosong    = hitung_jumlah($koneksi, "SELECT COUNT(*) AS total FROM rooms WHERE status = 'kosong'");
$jumlah_kamar_terisi    = hitung_jumlah($koneksi, "SELECT COUNT(*) AS total FROM rooms WHERE status = 'terisi'");
$jumlah_penghuni_aktif  = hitung_jumlah($koneksi, "SELECT COUNT(*) AS total FROM rentals WHERE status = 'aktif'");
$jumlah_keluhan_baru    = hitung_jumlah($koneksi, "SELECT COUNT(*) AS total FROM complains WHERE status = 'diterima'");
$jumlah_fasilitas_rusak = hitung_jumlah($koneksi, "SELECT COUNT(*) AS total FROM fasilities WHERE kondisi != 'baik'");
$jumlah_bukti_menunggu  = hitung_jumlah($koneksi, "SELECT COUNT(*) AS total FROM payments WHERE status = 'menunggu'");

// Ambil 5 keluhan terbaru beserta nama penghuni yang melapor
$keluhan_terbaru = mysqli_query($koneksi, "SELECT complains.judul, complains.status, complains.created_at, tenants.nama_lengkap
                                        FROM complains
                                        JOIN tenants ON complains.tenant_id = tenants.id
                                        ORDER BY complains.created_at DESC
                                        LIMIT 5");

// Siapkan header halaman
$judul_halaman = "Dashboard Admin";
include "../includes/header.php";
?>

<!-- Banner sambutan -->
<div class="sambutan">
    <div>
        <h1>Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?></h1>
        <p><?php echo $tanggal_hari_ini; ?>. Kondisi kos saat ini.</p>
    </div>
    <div class="sambutan-ikon">🏠</div>
</div>

<?php
// Satu kartu = array(ikon, label, angka, halaman tujuan, apakah merah kalau angkanya > 0)
$daftar_kartu = array(
    array("🚪", "Kamar kosong",            $jumlah_kamar_kosong,    "kamar.php",             false),
    array("🛏️", "Kamar terisi",            $jumlah_kamar_terisi,    "kamar.php",             false),
    array("👥", "Penghuni aktif",          $jumlah_penghuni_aktif,  "penghuni.php",          false),
    array("📣", "Keluhan belum ditangani", $jumlah_keluhan_baru,    "fasilitas.php#keluhan", true),
    array("🔧", "Fasilitas bermasalah",    $jumlah_fasilitas_rusak, "fasilitas.php",         true),
    array("🧾", "Bukti bayar perlu dicek", $jumlah_bukti_menunggu,  "pembayaran.php",        true)
);
?>
<div class="ringkasan">
    <?php foreach ($daftar_kartu as $kartu) { ?>
        <a href="<?php echo $kartu[3]; ?>" class="ringkasan-item <?php echo ($kartu[4] && $kartu[2] > 0) ? 'perlu-perhatian' : ''; ?>">
            <div class="ikon"><?php echo $kartu[0]; ?></div>
            <div>
                <div class="ringkasan-angka" data-angka="<?php echo $kartu[2]; ?>"><?php echo $kartu[2]; ?></div>
                <div class="ringkasan-label"><?php echo $kartu[1]; ?></div>
            </div>
        </a>
    <?php } ?>
</div>

<!-- Daftar keluhan terbaru, ada kotak cari -->
<div class="panel">
    <h2>Keluhan terbaru <a href="keluhan.php" style="font-size:13px; font-weight:400;">Lihat semua &rarr;</a></h2>

    <?php if (mysqli_num_rows($keluhan_terbaru) == 0) { ?>
        <p class="kosong">Belum ada keluhan yang masuk.</p>
    <?php } else { ?>
        <!-- Ketik di sini, tabel di bawah otomatis tersaring (diatur script.js) -->
        <input type="text" class="cari-tabel" data-tabel="tabelKeluhan" placeholder="Cari keluhan atau nama penghuni...">

        <div class="bungkus-tabel">
            <table id="tabelKeluhan">
                <tr>
                    <th>Penghuni</th>
                    <th>Keluhan</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                </tr>
                <!-- class baris-data membuat baris ini ikut tersaring dan menyala saat disentuh -->
                <?php while ($keluhan = mysqli_fetch_assoc($keluhan_terbaru)) { ?>
                    <tr class="baris-data">
                        <td><?php echo htmlspecialchars($keluhan['nama_lengkap']); ?></td>
                        <td><?php echo htmlspecialchars($keluhan['judul']); ?></td>
                        <td><?php echo date("d-m-Y", strtotime($keluhan['created_at'])); ?></td>
                        <td><span class="lencana lencana-<?php echo $keluhan['status']; ?>"><?php echo ucfirst($keluhan['status']); ?></span></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>
</div>

<?php include "../includes/footer.php"; ?>