<?php

//INI BUAT PAGE DASHBOARD ADMIN (halaman pertama yg diliat admin abis login)

//nyambungin ke databse + nyalain session
include "../includes/koneksi.php";
//buat ambil si satpamnya (fungsi wajib_login)
include "../includes/cek_akses.php";

// Satpam: hanya admin yang boleh masuk. Penghuni atau orang yang belum login ditolak.
// klo ditolak, orangnya dilempar ke halaman login (auth/login.php)
wajib_login('admin', '../auth/login.php');

// fungsi kecil untuk menghitung jumlah data. Dipakai berulang di bawah.
//fungsi ini tugasnya buat nanya ke db ada brp baris data yg cocok, trs ngasih angkanya
// trs dibikin fungsi karena biar ga nulis 4 baris yg sama 6 kali (jadi cukup sekali aja)
// Pertanyaan SQL di sini tidak memakai ketikan pengguna, jadi aman dijalankan langsung.
// makanya disini tu ga pake tanda tanya "?" kaya di page login, tanda tanya perlu klo 
// ada isian dari orang yg diketik (disini semua pertanyaan udh ditulis sendiri sama kita)
function hitung_jumlah($koneksi, $perintah_sql)
{   
    //mysqli_query = buat nanya ke db (hasilnya msi mentah blm bisa lgsng di baca)
    $hasil = mysqli_query($koneksi, $perintah_sql);
    //mysqli_fetch_assoc = ambil 1 baris trs hasilnya jadi "daftar berlabel"
    //misal kaya total = 5
    $baris = mysqli_fetch_assoc($hasil);
    //ini jawabannya (ngasih balik ke yg manggil fungsi, ambil yg berlabel total)
    return $baris['total'];
}

// Hitung angka-angka untuk ringkasan
// COUNT(*) = hitung jumlah barisnya. "AS total" = kasih label 'total'ke hasil hitungannya
// WHERE status = 'kosong' (cuma hitung baris yg kolom statusnya kosong)
$jumlah_kamar_kosong    = hitung_jumlah($koneksi, "SELECT COUNT(*) AS total FROM rooms WHERE status = 'kosong'");
$jumlah_kamar_terisi    = hitung_jumlah($koneksi, "SELECT COUNT(*) AS total FROM rooms WHERE status = 'terisi'");
$jumlah_penghuni_aktif  = hitung_jumlah($koneksi, "SELECT COUNT(*) AS total FROM rentals WHERE status = 'aktif'");
// yg masih diterima tapi blm diproses admin
$jumlah_keluhan_baru    = hitung_jumlah($koneksi, "SELECT COUNT(*) AS total FROM complains WHERE status = 'diterima'");
$jumlah_fasilitas_rusak = hitung_jumlah($koneksi, "SELECT COUNT(*) AS total FROM fasilities WHERE kondisi != 'baik'");
//menunggu = penghuni udh up tpi admin blm cek
$jumlah_bukti_menunggu  = hitung_jumlah($koneksi, "SELECT COUNT(*) AS total FROM payments WHERE status = 'menunggu'");


// Ambil 5 keluhan terbaru beserta nama penghuni yang melapor
// tabel complains cuma nyimpen NOMOR penghuni (tenant_id), bkn namanya.
// jadi gabungin dua tabel pake JOIN (tabel complains + tabel tenants)
// trs disambungin lewat no yg sama (complains.tenant_id = tenant.id)
// IBARAT : di daftar keluhan ada "penghuni no 3", terus kita liat di daftar penghuni, no 3 itu namanya siapa
// ORDER BY created_at DESC = berarti urutin dari yg paling baru (DESC = menurun)
// LIMIT 5 = ambil 5 baris pertama aja
$keluhan_terbaru = mysqli_query($koneksi, "SELECT complains.judul, complains.status, complains.created_at, tenants.nama_lengkap
                                        FROM complains
                                        JOIN tenants ON complains.tenant_id = tenants.id
                                        ORDER BY complains.created_at DESC
                                        LIMIT 5");


// Siapkan header halaman
$judul_halaman = "Dashboard Admin"; //ini dibaca sama header php buat jadi tulisan di tab browser
//dari sini variabel $tanggal_hari_ini muncul, yg dipake di banner bawah
include "../includes/header.php"; // menu bar atas

?>

<!-- Banner sambutan -->
<div class="sambutan">
    <div>
         <!-- $_SESSION['nama'] = nama admin yang tadi disimpan pas login (di proses_login.php).
             htmlspecialchars = bikin aman: kalau ada yang nyelipin tanda-tanda kode jahat di nama, ga akan dijalanin -->
        <h1>Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?></h1>
        <p><?php echo $tanggal_hari_ini; ?>. Kondisi kos saat ini.</p>
    </div>
    <div class="sambutan-ikon">🏠</div>
</div>


<?php
// DAFTAR KARTU. Daripada nulis HTML 6 kali, kita catat dulu isi tiap kartu di sebuah daftar (array),
// terus nanti dicetak pakai perulangan. Jadi kalau mau nambah kartu, cukup nambah 1 baris di sini.
// Satu kartu = array(ikon, label, angka, halaman tujuan, apakah merah kalau angkanya > 0)
// Nomor urutnya mulai dari 0: [0]=ikon, [1]=label, [2]=angka, [3]=halaman tujuan, [4]=bisa merah atau ga
$daftar_kartu = array(
    array("🚪", "Kamar kosong",            $jumlah_kamar_kosong,    "kamar.php",             false),
    array("🛏️", "Kamar terisi",            $jumlah_kamar_terisi,    "kamar.php",             false),
    array("👥", "Penghuni aktif",          $jumlah_penghuni_aktif,  "penghuni.php",          false),
     // true di ujung = kartu ini berubah merah kalau angkanya lebih dari 0 (artinya ada yang perlu dikerjakan admin)
    array("📣", "Keluhan belum ditangani", $jumlah_keluhan_baru,    "fasilitas.php#keluhan", true),
    array("🔧", "Fasilitas bermasalah",    $jumlah_fasilitas_rusak, "fasilitas.php",         true),
    array("🧾", "Bukti bayar perlu dicek", $jumlah_bukti_menunggu,  "pembayaran.php",        true)
);
?>
<div class="ringkasan">
    <!-- foreach = "untuk setiap kartu di daftar, lakukan ini". Tiap putaran nyetak 1 kartu.
         Di dalam putaran, $kartu isinya 1 kartu (ikon, label, angka, tujuan, merah-atau-ga) -->
    <?php foreach ($daftar_kartu as $kartu) { ?>
     <!-- Seluruh kartu dibikin tautan (<a>), jadi diklik langsung pindah ke halaman tujuannya ($kartu[3]).
             Bagian class: kalau kartu BISA merah ($kartu[4] true) DAN angkanya lebih dari 0, tambahin class
             'perlu-perhatian' (CSS-nya yang bikin warna merah). Kalau ga, class-nya kosong.
             Bentuk "kondisi ? kalau_benar : kalau_salah" namanya ternary, singkatan dari if-else. -->
        <a href="<?php echo $kartu[3]; ?>" class="ringkasan-item <?php echo ($kartu[4] && $kartu[2] > 0) ? 'perlu-perhatian' : ''; ?>">
            <div class="ikon"><?php echo $kartu[0]; ?></div>
            <div>
                <!-- data-angka = "titipan" angka buat JavaScript. script.js (bagian 3) baca angka ini,
                     terus bikin tulisannya berhitung naik dari 0 sampai angka itu (efek animasi) -->
                <div class="ringkasan-angka" data-angka="<?php echo $kartu[2]; ?>"><?php echo $kartu[2]; ?></div>
                <div class="ringkasan-label"><?php echo $kartu[1]; ?></div>
            </div>
        </a>
    <?php } ?>
</div>

<!-- Daftar keluhan terbaru, ada kotak cari -->
<div class="panel">
    <h2>Keluhan terbaru <a href="keluhan.php" style="font-size:13px; font-weight:400;">Lihat semua &rarr;</a></h2>

    <!-- mysqli_num_rows = "ada berapa baris hasilnya?". Kalau 0 (ga ada keluhan sama sekali),
         tampilin tulisan "Belum ada keluhan". Kalau ada, tampilin tabelnya. -->
    <?php if (mysqli_num_rows($keluhan_terbaru) == 0) { ?>
        <p class="kosong">Belum ada keluhan yang masuk.</p>
    <?php } else { ?>
        <!-- Ketik di sini, tabel di bawah otomatis tersaring (diatur script.js).
             data-tabel="tabelKeluhan" = "kotak cari ini buat nyaring tabel yang id-nya tabelKeluhan".
             Penyaringannya dikerjain JavaScript di browser, jadi ga ngubah isi database. -->
        <input type="text" class="cari-tabel" data-tabel="tabelKeluhan" placeholder="Cari keluhan atau nama penghuni...">

        <div class="bungkus-tabel">
            <table id="tabelKeluhan">
                <tr>
                    <th>Penghuni</th>
                    <th>Keluhan</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                </tr>
               <!-- class baris-data membuat baris ini ikut tersaring dan menyala saat disentuh.
                     while = "ulangi terus selama masih ada baris keluhan". 1 putaran = 1 baris tabel.
                     $keluhan isinya 1 keluhan (judul, status, tanggal, nama penghuni) -->
                <?php while ($keluhan = mysqli_fetch_assoc($keluhan_terbaru)) { ?>
                    <tr class="baris-data">
                        <td><?php echo htmlspecialchars($keluhan['nama_lengkap']); ?></td>
                        <td><?php echo htmlspecialchars($keluhan['judul']); ?></td>
                        <!-- Tanggal di database bentuknya 2026-11-25 12:30:00. strtotime = ubah jadi "tanggal beneran",
                             date("d-m-Y") = tampilin dengan format 25-11-2026 -->
                        <td><?php echo date("d-m-Y", strtotime($keluhan['created_at'])); ?></td>
                        <!-- Lencana (label berwarna) buat status. Nama class-nya ikut statusnya,
                             misal "lencana-diterima" atau "lencana-selesai", dan tiap status punya warna sendiri di CSS.
                             ucfirst = huruf pertama jadi kapital (diterima jadi Diterima) -->
                        <td><span class="lencana lencana-<?php echo $keluhan['status']; ?>"><?php echo ucfirst($keluhan['status']); ?></span></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>
</div>

<?php include "../includes/footer.php"; ?>