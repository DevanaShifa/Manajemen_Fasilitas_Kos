<?php
include "../includes/koneksi.php";
include "../includes/cek_akses.php";

// Satpam: hanya penghuni yang boleh masuk
wajib_login('penghuni', '../auth/login.php');

// Nomor data diri penghuni, dicatat saat login. Dipakai untuk mencari datanya.
$id_penghuni = isset($_SESSION['id_penghuni']) ? $_SESSION['id_penghuni'] : 0;

// 1. Cari kamar yang sedang ditempati (sewa yang statusnya aktif)
$cari_kamar = mysqli_prepare($koneksi, "SELECT rooms.no_kamar, rooms.tipe, rooms.harga, rentals.tanggal_masuk
                                        FROM rentals
                                        JOIN rooms ON rentals.room_id = rooms.id
                                        WHERE rentals.tenant_id = ? AND rentals.status = 'aktif'
                                        LIMIT 1");
mysqli_stmt_bind_param($cari_kamar, "i", $id_penghuni);
mysqli_stmt_execute($cari_kamar);
$data_kamar = mysqli_fetch_assoc(mysqli_stmt_get_result($cari_kamar));

// 2. Cari tagihan yang belum beres: belum dibayar, atau bukti bayarnya ditolak.
// Kolom "telat" bernilai 1 kalau tanggal jatuh tempo sudah lewat dan belum dibayar.
$cari_tagihan = mysqli_prepare($koneksi, "SELECT payments.bulan, payments.jumlah, payments.jatuh_tempo, payments.status, payments.catatan_admin,
                                                (payments.jatuh_tempo < CURDATE() AND payments.status = 'belum_bayar') AS telat
                                        FROM payments
                                        JOIN rentals ON payments.rental_id = rentals.id
                                        WHERE rentals.tenant_id = ? AND payments.status IN ('belum_bayar', 'ditolak')
                                        ORDER BY payments.jatuh_tempo");
mysqli_stmt_bind_param($cari_tagihan, "i", $id_penghuni);
mysqli_stmt_execute($cari_tagihan);
$daftar_tagihan = mysqli_stmt_get_result($cari_tagihan);

// 3. Cari 5 keluhan terakhir milik penghuni ini
$cari_keluhan = mysqli_prepare($koneksi, "SELECT judul, status, created_at
                                        FROM complains
                                        WHERE tenant_id = ?
                                        ORDER BY created_at DESC
                                        LIMIT 5");
mysqli_stmt_bind_param($cari_keluhan, "i", $id_penghuni);
mysqli_stmt_execute($cari_keluhan);
$daftar_keluhan = mysqli_stmt_get_result($cari_keluhan);

// Siapkan header halaman
$judul_halaman = "Dashboard Penghuni";
$pakai_hero = true;                     // menu atas transparan di atas foto
include "../includes/header.php";
include "../includes/header.php";
?>

<!-- Banner sambutan. Kalau ada tagihan yang belum beres, muncul penanda kuning -->
<!-- Foto kos di awal halaman. Gulir ke bawah untuk melihat isi dashboard -->
<div class="hero">
    <div class="hero-isi">
        <h1>Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?></h1>
        <p><?php echo $tanggal_hari_ini; ?></p>
        <?php if (mysqli_num_rows($daftar_tagihan) > 0) { ?>
            <span class="chip-peringatan">Ada <?php echo mysqli_num_rows($daftar_tagihan); ?> tagihan yang perlu dibayar</span>
        <?php } ?>
        <a href="#kamar-saya" class="hero-tombol">Lihat kamar saya</a>
    </div>
</div>

<!-- Kamar yang ditempati -->
<div class="panel" id="kamar-saya">
    <h2>Kamar saya</h2>
    <?php if ($data_kamar) { ?>
        <p style="margin:0;">
            Kamar <strong><?php echo htmlspecialchars($data_kamar['no_kamar']); ?></strong>
            (<?php echo htmlspecialchars($data_kamar['tipe']); ?>),
            Rp <?php echo number_format($data_kamar['harga'], 0, ',', '.'); ?> per bulan.
            Masuk sejak <?php echo date("d-m-Y", strtotime($data_kamar['tanggal_masuk'])); ?>.
        </p>
    <?php } else { ?>
        <p class="kosong">Kamu belum tercatat menempati kamar. Hubungi admin kalau ini keliru.</p>
    <?php } ?>
</div>

<!-- Tagihan yang perlu dibayar -->
<div class="panel">
    <h2>Tagihan yang perlu dibayar</h2>
    <?php if (mysqli_num_rows($daftar_tagihan) == 0) { ?>
        <p class="kosong">Tidak ada tagihan yang menunggu pembayaran.</p>
    <?php } else { ?>
        <div class="bungkus-tabel">
            <table>
                <tr>
                    <th>Bulan</th>
                    <th>Jumlah</th>
                    <th>Jatuh tempo</th>
                    <th>Status</th>
                </tr>
                <?php while ($tagihan = mysqli_fetch_assoc($daftar_tagihan)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($tagihan['bulan']); ?></td>
                        <td>Rp <?php echo number_format($tagihan['jumlah'], 0, ',', '.'); ?></td>
                        <td><?php echo date("d-m-Y", strtotime($tagihan['jatuh_tempo'])); ?></td>
                        <td>
                            <?php if ($tagihan['telat'] == 1) { ?>
                                <span class="lencana lencana-telat">Telat</span>
                            <?php } elseif ($tagihan['status'] == 'ditolak') { ?>
                                <span class="lencana lencana-ditolak">Bukti ditolak</span>
                                <?php if ($tagihan['catatan_admin'] != "") { ?>
                                    <br><small><?php echo htmlspecialchars($tagihan['catatan_admin']); ?></small>
                                <?php } ?>
                            <?php } else { ?>
                                <span class="lencana lencana-belum_bayar">Belum dibayar</span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>
</div>

<!-- Keluhan yang pernah dikirim -->
<div class="panel">
    <h2>Keluhan saya <a href="keluhan.php" style="font-size:13px; font-weight:400;">Kirim / lihat semua &rarr;</a></h2>
    <?php if (mysqli_num_rows($daftar_keluhan) == 0) { ?>
        <p class="kosong">Belum ada keluhan yang kamu kirim.</p>
    <?php } else { ?>
        <div class="bungkus-tabel">
            <table>
                <tr>
                    <th>Keluhan</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                </tr>
                <?php while ($keluhan = mysqli_fetch_assoc($daftar_keluhan)) { ?>
                    <tr>
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