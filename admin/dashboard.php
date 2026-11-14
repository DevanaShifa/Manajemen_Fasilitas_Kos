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

<h1>Dashboard</h1>
<p class="keterangan">Gambaran kondisi kos saat ini.</p>

<!-- Ringkasan angka. Yang butuh perhatian admin diberi warna merah kalau lebih dari 0. -->
<div class="ringkasan">
    <div class="ringkasan-item">
        <div class="ringkasan-angka"><?php echo $jumlah_kamar_kosong; ?></div>
        <div class="ringkasan-label">Kamar kosong</div>
    </div>
    <div class="ringkasan-item">
        <div class="ringkasan-angka"><?php echo $jumlah_kamar_terisi; ?></div>
        <div class="ringkasan-label">Kamar terisi</div>
    </div>
    <div class="ringkasan-item">
        <div class="ringkasan-angka"><?php echo $jumlah_penghuni_aktif; ?></div>
        <div class="ringkasan-label">Penghuni aktif</div>
    </div>
    <div class="ringkasan-item <?php echo ($jumlah_keluhan_baru > 0) ? 'perlu-perhatian' : ''; ?>">
        <div class="ringkasan-angka"><?php echo $jumlah_keluhan_baru; ?></div>
        <div class="ringkasan-label">Keluhan belum ditangani</div>
    </div>
    <div class="ringkasan-item <?php echo ($jumlah_fasilitas_rusak > 0) ? 'perlu-perhatian' : ''; ?>">
        <div class="ringkasan-angka"><?php echo $jumlah_fasilitas_rusak; ?></div>
        <div class="ringkasan-label">Fasilitas bermasalah</div>
    </div>
    <div class="ringkasan-item <?php echo ($jumlah_bukti_menunggu > 0) ? 'perlu-perhatian' : ''; ?>">
        <div class="ringkasan-angka"><?php echo $jumlah_bukti_menunggu; ?></div>
        <div class="ringkasan-label">Bukti bayar perlu dicek</div>
    </div>
</div>

<!-- Daftar keluhan terbaru -->
<div class="panel">
    <h2>Keluhan terbaru</h2>

    <?php if (mysqli_num_rows($keluhan_terbaru) == 0) { ?>
        <p class="kosong">Belum ada keluhan yang masuk.</p>
    <?php } else { ?>
        <div class="bungkus-tabel">
            <table>
                <tr>
                    <th>Penghuni</th>
                    <th>Keluhan</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                </tr>
                <!-- Ulangi satu baris tabel untuk setiap keluhan -->
                <?php while ($keluhan = mysqli_fetch_assoc($keluhan_terbaru)) { ?>
                    <tr>
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