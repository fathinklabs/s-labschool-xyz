# 📋 Roadmap & Daftar Tugas Pengembangan (TODOS) - s.labschool.xyz

Dokumen ini mencatat rencana kerja, backlog fitur prioritas, dan arsitektur teknis yang akan diimplementasikan pada platform **s.labschool.xyz** (Labschool Enterprise URL Shortener, Dynamic QR Code & Microsite Platform).

---

## 📌 Status Ringkasan Roadmap

| Kategori | Fitur / Item | Prioritas | Status | Target Modul / File |
| :--- | :--- | :---: | :---: | :--- |
| **Autentikasi** | Login OAuth2 Google Workspace Labschool | 🔥 Tinggi | `Planned` | `config/google_auth.php`, `login.php` |
| **Infrastruktur** | Redis Session Management | 🔥 Tinggi | `Planned` | `config/session.php`, `config/redis.php` |
| **Admin Panel** | Metrics Usage (Storage, Data, User, DB) | ⚡ Sedang | `Planned` | `admin.php`, `api/admin/metrics.php` |
| **Microsite** | Advance Page Builder (Themes, BG, Palettes, Cards) | ⚡ Sedang | `Planned` | `bio_editor.php`, `bio.php` |
| **UI/UX** | Fix Mobile View yang Masih Terbatas | 🔥 Tinggi | `Planned` | `dashboard.php`, `settings.php`, `admin.php` |
| **QR Code** | Tambahan QR Style (Logo, Badge, Border, Eye) | ⚡ Sedang | `Planned` | `api/qr.php`, `dashboard.php` |

---

## 🚀 Rincian Backlog & Spesifikasi Fitur

### 1. 🔐 Login OAuth2 Google Workspace Labschool
Mengintegrasikan autentikasi Single Sign-On (SSO) menggunakan Google Identity Platform khusus untuk akun Google Workspace Labschool.

- [ ] **Google Cloud Console Setup**:
  - Konfigurasi OAuth 2.0 Client ID & Secret untuk domain `s.labschool.xyz`.
  - Authorized Redirect URI: `https://s.labschool.xyz/auth/google/callback.php`.
- [ ] **Restriksi Ketat Domain Akun**:
  - Validasi domain hosted Google Workspace (`hd` parameter dan verifikasi server-side):
    - `@labschool.xyz`
    - `@elabschool.sch.id`
  - Penolakan otomatis (HTTP 403 / Error Notice) jika pengguna mencoba login dengan akun publik (`@gmail.com`) atau domain institusi di luar Labschool.
- [ ] **Sinkronisasi Profil Otomatis**:
  - Auto-create user jika baru pertama kali login dengan data resmi dari Google:
    - Nama Lengkap (`name` / `given_name` + `family_name`)
    - Email resmi
    - Foto Profil (`picture` HD disimpan / di-link ke `avatar_url`)
  - Auto-assign API Key unik (`labs_...`).
  - Penentuan role awal cerdas: guru/staf atau siswa berdasarkan pola email / OU.
- [ ] **UI Tombol Login**:
  - Penambahan tombol resmi *"Masuk dengan Google Workspace Labschool"* dengan ikon Google berwarna dan styling brand Labschool di `login.php` dan `register.php`.

---

### 2. ⚡ Redis Session Management
Menggantikan penyimpanan sesi berbasis file PHP tradisional (`/var/lib/php/sessions`) dengan in-memory cache **Redis** untuk performa tinggi, atomisitas, dan skalabilitas horizontal.

- [ ] **Instalasi & Konfigurasi Ekstensi `php-redis`**:
  - Konfigurasi `php.ini` atau handler runtime:
    ```ini
    session.save_handler = redis
    session.save_path = "tcp://127.0.0.1:6379?prefix=labs_sess:&timeout=2.0"
    ```
- [ ] **Konfigurasi Keamanan & TTL**:
  - Session TTL dinamis: 7 hari untuk Remember Me, 24 jam untuk sesi standar.
  - Sesi otomatis terhapus saat pengguna memilih `logout.php`.
- [ ] **Manajemen Sesi Terdistribusi**:
  - Kemampuan mencabut (revoke) sesi dari jarak jauh jika akun dicurigai disusupi (Remote Logout).
  - Monitoring koneksi Redis dan graceful fallback jika Redis tidak merespons (fail-safe ke database atau file).

---

### 3. 📊 Metrics Usage pada Admin Panel
Menyediakan visibilitas mendalam bagi Super Admin mengenai kapasitas dan utilisasi sumber daya platform di `admin.php`.

- [ ] **Storage Usage (Penggunaan Penyimpanan)**:
  - Total ukuran direktori upload aset (`/assets/img/`, custom avatars, logo QR, favicon microsite).
  - Rata-rata ukuran file per pengguna.
  - Indikator sisa kapasitas partisi disk server (`df -h` metrik via PHP runtime).
- [ ] **Data Usage & Bandwidth**:
  - Volume trafik / data transfer keluar masuk (Egress Bandwidth) bulanan.
  - Total hit / request redirect link per hari/minggu/bulan.
  - Estimasi penghematan beban data melalui caching headers.
- [ ] **User Usage (Aktivitas & Utilisasi Pengguna)**:
  - Jumlah total pengguna terdaftar vs pengguna aktif (DAU / WAU / MAU).
  - Distribusi pengguna berdasarkan domain (`@labschool.xyz` vs `@elabschool.sch.id`).
  - Top 10 kreator tautan & microsite paling produktif.
- [ ] **Database Usage (Statistik Basis Data MariaDB)**:
  - Ukuran tabel individu: `short_urls`, `click_logs`, `microsites`, `activity_logs`, `bulk_upload_logs`.
  - Total overhead index dan ukuran buffer pool.
  - Rekomendasi otomatis optimasi tabel / partisi jika `click_logs` melebihi 1.000.000 baris.

---

### 4. 🎨 Advance Page Builder (Microsite / Link in Bio)
Meningkatkan kemampuan pembuat halaman interaktif di `bio_editor.php` dan tampilan publik di `bio.php` agar setara dengan standar modern (Genially & Linktree Enterprise).

- [ ] **Koleksi Tema Baru (Themes Collection)**:
  - `genially-showcase`: Tampilan portofolio interaktif dengan animasi fade-in dan card modern.
  - `notion-clean`: Tampilan minimalis monokrom bersih dengan tipografi rapi ala Notion.
  - `glassmorphism-aurora`: Efek kaca transparan blur (*backdrop-filter*) dengan pencahayaan gradien warna-warni dinamis.
  - `labs-cyber`: Tema gelap aksen neon biru-amber untuk kegiatan IT, robotik, dan kompetisi siswa.
  - `academic-classic`: Tema elegan resmi dengan ornamen emas dan navy untuk profil institusi / guru.
- [ ] **Page Background Kustom**:
  - Dukungan warna solid kustom (`hex / rgb`).
  - Animated CSS Gradient Mesh (bergerak halus di latar belakang).
  - Pola geometris / patterns (dots grid, diagonal lines, isometric shapes).
  - Upload gambar latar belakang dengan pengatur opacity, blur, dan dark overlay.
- [ ] **Color Palette Selector**:
  - Color picker modern untuk warna teks, tombol, border, dan aksen.
  - Presets warna resmi identitas Labschool (Labs Blue `#1E40AF`, Amber `#F59E0B`, Sky `#0EA5E9`, Emerald `#10B981`).
- [ ] **Tambahan Tipe Button / Card / Konten**:
  - **Tipe Button Standar**: Solid, Outline, Soft Glow, 3D Shadow, Pill Rounded.
  - **Interactive Media Card**:
    - *YouTube Video Embed* (pemutaran video langsung di dalam microsite).
    - *Spotify / Apple Music Embed* (podcast sekolah atau mars Labschool).
    - *Google Drive / PDF Viewer* (pratinjau dokumen brosur / materi pelajaran).
  - **Contact & Social Cards**:
    - Tombol WhatsApp Interaktif (dengan template pesan otomatis).
    - Tombol Unduh Kartu Nama Digital (vCard / `.vcf`).
    - Media sosial terpadu (Instagram, TikTok, YouTube, LinkedIn, X).
  - **Event & Announcement Cards**:
    - Widget hitung mundur kegiatan (*Countdown Timer*).
    - Banner pengumuman penting (*Highlighted Notification Bar*).

---

### 5. 📱 Fix Mobile View yang Masih Terbatas
Menyempurnakan antarmuka pengguna agar 100% responsif, nyaman, dan intuitif saat diakses dari perangkat ponsel (smartphone iOS & Android).

- [ ] **Drawer Detail Tautan (Mobile Bottom-Sheet)**:
  - Mengubah sliding drawer desktop di `dashboard.php` menjadi *bottom-sheet drawer* pada layar seluler (`< 768px`) dengan drag-handle dan scroll internal yang mulus.
- [ ] **Bottom Navigation Bar untuk Seluler**:
  - Menyediakan navigasi bawah tetap (*fixed bottom bar*) untuk menu cepat: *Tautan*, *Microsite*, *Buat (+)*, *Grup*, *Profil/Pengaturan*.
- [ ] **Transformasi Tabel Responsif (Card View on Mobile)**:
  - Mengonversi tampilan tabel horizontal yang memicu scrollbar samping di `dashboard.php`, `settings.php?tab=activity-log`, dan `settings.php?tab=bulk-upload` menjadi kartu vertikal yang rapi di layar kecil.
- [ ] **Perbaikan Form & Modal di Layar Ponsel**:
  - Penyesuaian padding, ukuran font input, dan tombol aksi di modal pembuat QR, modal pembuat grup, dan modal invite user agar tidak terpotong atau tertutup keyboard virtual.
- [ ] **Mobile Editor untuk Microsite**:
  - Memisahkan panel preview dan panel kontrol pada `bio_editor.php` dengan mekanisme tab *Edit* vs *Pratinjau Live* pada layar smartphone.

---

### 6. 🖼️ Tambahan QR Style pada Pembuatan QR Code (Logo/Badge/Border)
Menghadirkan kustomisasi kode QR terlengkap di `api/qr.php` dan antarmuka modal QR di `dashboard.php`.

- [ ] **Kustomisasi Logo Tengah (Center Logo)**:
  - Preset logo resmi:
    - Logo SMP Labschool Jakarta
    - Logo SMA Labschool Jakarta
    - Logo KB-TK Labschool
    - Logo BPS Labschool UNJ
  - Fitur upload logo kustom pengguna dengan kompresi otomatis dan *safe-margin white boundary* agar keterbacaan scanner tetap 100%.
- [ ] **Koleksi Badge & Frame Call-to-Action (CTA)**:
  - Bingkai badge bawah: *"PINDAI SAYA"*, *"DAFTAR DI SINI"*, *"MATERI KELAS"*, *"INFO KEGIATAN"*.
  - Bingkai badge atas: *"LABSCHOOL OFFICIAL"*, *"SCAN ME"*.
  - Desain kartu lengkap (*QR Card Stand*) dengan header judul acara dan border tepi melengkung.
- [ ] **Gaya Titik & Sudut QR (Matrix & Eye Styles)**:
  - Gaya titik matriks: *Square (Standar)*, *Rounded Dots*, *Extra-rounded Smooth*, *Hexagon*.
  - Gaya mata sudut (*Corner Eyes*): *Square*, *Circle Ring*, *Leaf Shape*.
  - Pemilihan warna gradien untuk pola QR (Gradien Biru Labschool ke Biru Langit).
- [ ] **Format Ekspor Multi-Resolusi**:
  - Format PNG Ultra HD (Resolusi tinggi hingga 2000x2000 px, 300 DPI untuk cetak banner/brosur).
  - Format SVG Vektor murni (resolusi tak terbatas untuk desainer grafis).
  - Opsi unduh langsung PDF lembar siap cetak ukuran A4 / A5.

---

## 📅 Rencana Tahapan Rilis (Milestones)

```
[Sprint 1] ───> Login OAuth2 Google Labschool + Redis Session Management
[Sprint 2] ───> Mobile View Optimization + Bottom Navigation & Card Layouts
[Sprint 3] ───> Tambahan QR Style (Logo Presets, Frame Badges, Eye Customization)
[Sprint 4] ───> Advance Page Builder (Themes, Background Video/Mesh, Media Cards)
[Sprint 5] ───> Metrics Usage (Storage, Bandwidth, Database Analytics on Admin Panel)
```

---
*Dokumen ini diperbarui secara berkala sesuai perkembangan kebutuhan pengembang dan pengguna s.labschool.xyz.*
