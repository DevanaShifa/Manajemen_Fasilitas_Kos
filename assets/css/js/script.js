// =====================================================
// script.js - efek interaktif untuk semua halaman
// =====================================================

// ---------- 1. Bayangan di menu atas saat halaman digulir ----------
var barAtas = document.getElementById("barAtas");
if (barAtas) {
    window.addEventListener("scroll", function () {
        // Kalau sudah turun lebih dari 4 piksel, tambahkan kelas "bergulir" (memunculkan bayangan)
        barAtas.classList.toggle("bergulir", window.scrollY > 4);
    });
}

// ---------- 2. Tombol menu untuk tampilan HP ----------
var tombolMenu = document.getElementById("tombolMenu");
var lipatan = document.getElementById("lipatan");
if (tombolMenu && lipatan) {
    tombolMenu.addEventListener("click", function () {
        // Buka kalau tertutup, tutup kalau terbuka
        var sedangTerbuka = lipatan.classList.toggle("terbuka");
        tombolMenu.textContent = sedangTerbuka ? "✕" : "☰";
    });
}

// ---------- 3. Angka ringkasan berhitung naik dari 0 ----------
// Cara pakai di HTML: <div data-angka="12">12</div>
var kurangiGerakan = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
var daftarAngka = document.querySelectorAll("[data-angka]");
daftarAngka.forEach(function (elemen) {
    var angkaTujuan = parseInt(elemen.getAttribute("data-angka"));

    // Kalau angkanya 0, atau pengguna mematikan animasi di perangkatnya, langsung tampilkan
    if (angkaTujuan === 0 || kurangiGerakan) {
        elemen.textContent = angkaTujuan;
        return;
    }

    var langkahSekarang = 0;
    var jumlahLangkah = 25;
    elemen.textContent = 0;

    // Setiap 30 milidetik, naikkan angkanya sedikit
    var pewaktu = setInterval(function () {
        langkahSekarang++;
        elemen.textContent = Math.round(angkaTujuan * langkahSekarang / jumlahLangkah);
        if (langkahSekarang >= jumlahLangkah) {
            clearInterval(pewaktu);
        }
    }, 30);
});

// ---------- 4. Kotak cari untuk tabel ----------
// Cara pakai di HTML:
//   <input type="text" class="cari-tabel" data-tabel="idTabel">
//   <table id="idTabel"> ... <tr class="baris-data"> ...
var daftarKotakCari = document.querySelectorAll(".cari-tabel");
daftarKotakCari.forEach(function (kotakCari) {
    kotakCari.addEventListener("input", function () {
        var kataKunci = kotakCari.value.toLowerCase();
        var tabel = document.getElementById(kotakCari.getAttribute("data-tabel"));
        var semuaBaris = tabel.querySelectorAll("tr.baris-data");

        // Tampilkan baris yang mengandung kata kunci, sembunyikan yang tidak
        semuaBaris.forEach(function (baris) {
            var cocok = baris.textContent.toLowerCase().indexOf(kataKunci) > -1;
            baris.style.display = cocok ? "" : "none";
        });
    });
});

// ---------- 5. Kotak pesan: tombol tutup dan hilang sendiri ----------
var daftarPesan = document.querySelectorAll(".pesan");
daftarPesan.forEach(function (pesan) {
    function sembunyikanPesan() {
        pesan.classList.add("hilang");
        setTimeout(function () { pesan.remove(); }, 300);
    }

    // Tambahkan tombol x di kotak pesan
    var tombolTutup = document.createElement("button");
    tombolTutup.type = "button";
    tombolTutup.className = "tutup-pesan";
    tombolTutup.setAttribute("aria-label", "Tutup pesan");
    tombolTutup.innerHTML = "&times;";
    tombolTutup.addEventListener("click", sembunyikanPesan);
    pesan.appendChild(tombolTutup);

    // Pesan sukses hilang sendiri setelah 5 detik
    if (pesan.classList.contains("pesan-sukses")) {
        setTimeout(sembunyikanPesan, 5000);
    }
});

// ---------- 6. Tombol "Lihat" untuk menampilkan password ----------
var daftarKolomSandi = document.querySelectorAll('input[type="password"]');
daftarKolomSandi.forEach(function (kolomSandi) {
    // Bungkus kolom password dengan kotak supaya tombolnya bisa ditaruh di dalamnya
    var pembungkus = document.createElement("div");
    pembungkus.className = "bungkus-sandi";
    kolomSandi.parentNode.insertBefore(pembungkus, kolomSandi);
    pembungkus.appendChild(kolomSandi);

    var tombolLihat = document.createElement("button");
    tombolLihat.type = "button";
    tombolLihat.className = "lihat-sandi";
    tombolLihat.textContent = "Lihat";
    tombolLihat.addEventListener("click", function () {
        var sedangTersembunyi = (kolomSandi.type === "password");
        kolomSandi.type = sedangTersembunyi ? "text" : "password";
        tombolLihat.textContent = sedangTersembunyi ? "Sembunyi" : "Lihat";
    });
    pembungkus.appendChild(tombolLihat);
});

// ---------- 7. Tombol kirim berubah jadi "Memproses..." ----------
var daftarFormulir = document.querySelectorAll("form");
daftarFormulir.forEach(function (formulir) {
    formulir.addEventListener("submit", function () {
        var tombolKirim = formulir.querySelector(".tombol-utama");
        if (tombolKirim) {
            tombolKirim.setAttribute("data-teks-awal", tombolKirim.textContent);
            tombolKirim.textContent = "Memproses...";
            tombolKirim.classList.add("memuat");
        }
    });
});

// Kalau pengguna menekan tombol Kembali di browser, kembalikan tulisan tombolnya
window.addEventListener("pageshow", function () {
    var daftarTombolMemuat = document.querySelectorAll(".tombol-utama.memuat");
    daftarTombolMemuat.forEach(function (tombol) {
        tombol.textContent = tombol.getAttribute("data-teks-awal");
        tombol.classList.remove("memuat");
    });
});

// ---------- 8. Konfirmasi sebelum hapus (untuk nanti) ----------
// Cara pakai di HTML: <a href="hapus.php?id=3" data-konfirmasi="Yakin hapus kamar ini?">Hapus</a>
document.addEventListener("click", function (kejadian) {
    var elemen = kejadian.target.closest("[data-konfirmasi]");
    if (elemen && !confirm(elemen.getAttribute("data-konfirmasi"))) {
        kejadian.preventDefault();
    }
});