Laporan Dokumentasi Kode HTML - Portofolio Web
Berikut adalah penjelasan dan bedah kode per snippet untuk file index.html pada proyek web portofolio ini. Laporan ini bisa kamu copy-paste langsung ke dalam file README1.md.

1. Bagian Konfigurasi Dokumen & Header ()
```
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portofolio - Maida Aqillah</title>
    <link rel="stylesheet" href="style.css">
</head>
```
Penjelasan Snippet:

<!DOCTYPE html> dan <html lang="id">: Mendefinisikan bahwa dokumen ini menggunakan standar HTML5 dengan bahasa utama Bahasa Indonesia.

<meta charset="UTF-8">: Mengatur encoding karakter agar teks, simbol, atau emoji dapat terbaca dengan benar di berbagai browser.

<meta name="viewport" content="width=device-width, initial-scale=1.0">: Mengatur agar tata letak halaman bersifat responsif dan menyesuaikan ukuran layar perangkat pengguna (baik mobile maupun desktop).

<title>: Menentukan judul tab yang tampil pada jendela browser.

<link rel="stylesheet" href="style.css">: Menghubungkan file HTML dengan lembar gaya eksternal (stylesheet) untuk mengatur visual dan tata letak.

2. Navigasi Utama ( & )
```
    <header>
        <nav>
            <div class="logo">Mai.</div>
            <ul>
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#skills">Skills</a></li>
                <li><a href="#tools">Toolset</a></li>
                <li><a href="#education">Ed & Exp</a></li>
                <li><a href="#portfolio">Portfolio</a></li>
                <li><a href="#gallery">Gallery</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="assets/CV_Maida.pdf" class="btn-download" download>Download CV</a></li>
            </ul>
        </nav>
    </header>
```
Penjelasan Snippet:

<header> & <nav>: Elemen semantik untuk membungkus area menu navigasi utama situs.

<div class="logo">Mai.</div>: Menampilkan teks identitas atau merek singkat di bagian kiri navigasi.

<ul> & <li>: Daftar menu navigasi yang menggunakan tautan internal (anchor links seperti #home, #about, dll.) untuk mempermudah pengguna berpindah antar bagian halaman secara mulus (smooth scroll).

Tombol Download CV: Menggunakan atribut download pada elemen <a> agar pengunjung dapat mengunduh dokumen CV secara langsung.

3. Bagian Utama: Beranda / Home ()
```
        <section id="home">
            <div class="hero-content">
                <div class="hero-image">
                    <img src="../images/profile.png" alt="Foto Profil Maida Aqillah" style="width: 200px; height: 200px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                </div>
                <h1>HALO, SAYA MAIDA AQILLAH PUTRI NURANDANI</h1>
                <h3>Mahasiswa Informatika | Web Developer</h3>
                <p>Memiliki ketertarikan di bidang Teknologi, Seni, dan Sosial serta selalu antusias mengaplikasikan hal baru ke dunia nyata.</p>
                <div class="cta-buttons">
                    <a href="#portfolio" class="btn-primary">Lihat Portofolio</a>
                    <a href="#contact" class="btn-secondary">Hubungi Saya</a>
                </div>
            </div>
        </section>
```

Penjelasan Snippet:

<section id="home">: Bagian awal atau hero section yang menyambut pengunjung.

.hero-image: Memuat foto profil pribadi yang diatur berbentuk lingkaran (border-radius: 50%) dengan tambahan efek bayangan tipis (box-shadow).

<h1> & <h3>: Menampilkan nama lengkap serta identitas profesional secara jelas sebagai perkenalan utama.

.cta-buttons: Berisi tombol panggilan aksi (Call to Action) yang mengarahkan pengguna secara cepat menuju bagian portofolio atau kontak.

4. Bagian Tentang Saya ()
```
        <section id="about">
            <h2>Tentang Saya</h2>
            <p>Saya adalah mahasiswa program studi Teknik Informatika di Institut Teknologi Sepuluh Nopember (ITS). Saya merupakan pribadi yang suka belajar hal baru, mudah beradaptasi dengan lingkungan, dan memiliki tekad untuk mengaplikasikan ilmu saya ke dunia nyata. Dengan kemampuan manajemen waktu dan berpikir kritis, saya siap memberikan kontribusi maksimal dalam setiap proyek yang saya kerjakan.</p>
        </section>
```
Penjelasan Snippet:

<section id="about">: Bagian deskripsi diri atau latar belakang akademis.

Berisi narasi singkat mengenai status studi di Teknik Informatika ITS, sifat adaptif, serta komitmen dalam menjalankan setiap tugas maupun proyek.

5. Bagian Skillset & Toolset ( & )
```
        <section id="skills">
            <h2>Skillset</h2>
            <ul>
                <li>C & C++</li>
                <li>Python</li>
                <li>Bash Scripting</li>
                <li>HTML & CSS</li>
                <li>Manajemen Waktu & Pemecahan Masalah</li>
            </ul>
        </section>

        <section id="tools">
            <h2>Toolset</h2>
            <div class="tools-grid">
                <div class="tool-card"><span>Visual Studio Code</span></div>
                <div class="tool-card"><span>Git & GitHub</span></div>
                <div class="tool-card"><span>MySQL</span></div>
                <div class="tool-card"><span>Ubuntu / Linux</span></div>
                <div class="tool-card"><span>VMware</span></div>
                <div class="tool-card"><span>Microsoft Office</span></div>
            </div>
        </section>
```
Penjelasan Snippet:

skills: Menggunakan daftar berbasis poin (<ul> dan <li>) untuk merinci penguasaan bahasa pemrograman dan kemampuan lunak (soft skills).

tools: Menggunakan tata letak grid (.tools-grid) dan kartu individual (.tool-card) untuk menampilkan perangkat lunak serta sistem operasi penunjang pengembangan teknologi yang dikuasai.

6. Bagian Pendidikan & Pengalaman ()
```
        <section id="education">
            <h2>Pendidikan & Pengalaman</h2>
            <div class="education-list">
                <article>
                    <h3>S1 Teknik Informatika - Institut Teknologi Sepuluh Nopember</h3>
                    <p>Agustus 2025 - Sekarang</p>
                </article>
                <article>
                    <h3>D4 Teknik Informatika - Universitas Airlangga</h3>
                    <p>Agustus 2024 - September 2025</p>
                </article>
            </div>
            <div class="experience-list">
                <article>
                    <h3>Staff of Consumption Division - Petrolida 2026</h3>
                    <p>Mei 2026 | Berkoordinasi dengan vendor dan memastikan kelancaran distribusi konsumsi acara.</p>
                </article>
                <article>
                    <h3>Anggota Departemen Pengabdian Masyarakat - HIMTI Universitas Airlangga</h3>
                    <p>Februari 2025 - September 2025 | Terlibat dalam berbagai kepanitiaan seperti HIMTI Cares, HIMTI Berbagi, dan MAKRAB VIESA.</p>
                </article>
            </div>
        </section>
```
Penjelasan Snippet:

Menggunakan tag semantik <article> untuk memisahkan setiap entri riwayat pendidikan dan pengalaman organisasi secara terstruktur.

Menampilkan rekam jejak akademis serta keaktifan dalam kepanitiaan kampus secara kronologis.

7. Bagian Portofolio & Galeri ( & )
```
        <section id="portfolio">
            <h2>Portofolio Project</h2>
            <div class="portfolio-grid">
                <article class="project-card">
                    <h3>belum ada yang selesai hehehe, soon yah :></h3>
                    <h1>Coming Soon!</h1>
                </article>
            </div>
        </section>

        <section id="gallery">
            <h2>Dokumentasi & Galeri</h2>
            <div class="gallery-grid">
                
            </div>
        </section>
```
Penjelasan Snippet:

portfolio: Menyediakan wadah kartu proyek dengan status informasi "Coming Soon" sebagai penanda bahwa proyek mendatang akan segera diunggah.

gallery: Menyediakan kerangka bagian galeri dokumentasi yang fleksibel untuk pengembangan atau penambahan konten foto di masa mendatang.

8. Bagian Kontak & Footer ( & )
```
        <section id="contact">
            <h2>Hubungi Saya</h2>
            <p>Email: <a href="mailto:aqillahmaida30@gmail.com">aqillahmaida30@gmail.com</a></p>
            <p>Telepon: <a href="https://wa.me/6287840033459" target="_blank">call me!</a></p>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 Maida Aqillah Putri Nurandani</p>
        <div class="social-links">
            <a href="https://www.linkedin.com/in/maidaaqillah" target="_blank">LinkedIn</a>
        </div>
    </footer>
</body>
</html>
```
Penjelasan Snippet:

contact: Menyediakan tautan langsung interaktif menuju alamat surel (mailto) dan aplikasi perpesanan WhatsApp (wa.me) agar mudah dihubungi.

<footer>: Menampilkan hak cipta kepemilikan situs beserta tautan menuju profil profesional seperti LinkedIn.
