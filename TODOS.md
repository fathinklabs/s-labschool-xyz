# 📋 Roadmap & Status Pengembangan (TODOS) - s.labschool.xyz

Dokumen ini mencatat rencana kerja, backlog fitur prioritas, status rilis, dan spesifikasi arsitektur teknis platform **s.labschool.xyz** (Labschool Enterprise URL Shortener, Dynamic QR Code, and Interactive Pages Platform &bull; SMP Labschool Jakarta).

---

## 📌 Matriks Status Fitur & Modul

| Kategori | Fitur / Item | Prioritas | Status | Target Modul / File |
| :--- | :--- | :---: | :---: | :--- |
| **Keamanan & Kredensial** | Isolasi `.env`, Anti-CSRF, XSS Sanitasi, Nginx Hardening | 🛡️ Kritis | `Completed` ✅ | `.env`, `config/`, `s.labschool.xyz.conf` |
| **Pages Platform** | Bitly-Style Flow (`Create Page` &rarr; `Template` &rarr; `Builder`) | 🔥 Tinggi | `Completed` ✅ | `dashboard.php`, `page_builder.php`, `bio.php` |
| **Pages Platform** | Fully Migrate dari `bio_editor.php` ke `page_builder.php` (API, Aliases & Deprecation) | 🔥 Tinggi | `Completed` ✅ | `page_builder.php`, `api/save_page.php`, `bio_editor.php` |
| **Pages Platform** | Master Template Collection (20 Templates across 4 Bitly Categories + Microsite Assets) | 🔥 Tinggi | Completed ✅ | config/templates.php, dashboard.php, page_builder.php |
| **Drawer Navigasi** | Left Sidebar Drawer Dinamis + Mini Icon-Rail (76px) | ⚡ Sedang | `Completed` ✅ | `dashboard.php` |
| **User & Organisasi** | Scope Kolaborasi User, Grup (Max 10), Activity Log, Bulk Upload | 🔥 Tinggi | `Completed` ✅ | `settings.php`, `admin.php` |
| **Settings UI/UX** | Sembunyikan (Hide) Fitur & Tab yang Disabled di Settings User | ⚡ Sedang | `Completed` ✅ | `settings.php` |
| **Autentikasi & Email** | Request Reset Password (Forgot Password) via Email XOAUTH2 | 🔥 Tinggi | `Completed` ✅ | `forgot_password.php`, `reset_password.php`, `config/mail.php` |
| **Admin Panel** | Global System Configuration (Site, Auth, Rate Limits) | 🔥 Tinggi | `Completed` ✅ | `admin.php`, `global_settings` |
| **Autentikasi SSO** | Login OAuth2 Google Workspace Labschool (`@labschool.xyz`) | 🔥 Tinggi | `Planned` ⏳ | `config/google_auth.php`, `login.php` |
| **Infrastruktur** | Redis Session Management | 🔥 Tinggi | `Completed` ✅ | `config/auth.php`, Redis daemon |
| **Admin Panel** | Metrics Usage (Storage, Data Egress, User, DB Stats) | ⚡ Sedang | `Completed` ✅ | `admin.php` |
| **UI/UX Mobile** | Mobile Bottom-Sheet Detail & Card View Tables | ⚡ Sedang | `In Progress` 🚧 | `dashboard.php`, `settings.php` |
| **QR Code Engine** | Tambahan QR Style (Logo Preset, Frame Badge, Vector SVG) | ⚡ Sedang | `Completed` ✅ | `api/qr.php`, `dashboard.php` |
| **Pages Phase 2** | Custom Mesh Gradient BG, Audio Player Card, Color Picker | ⚡ Sedang | `Planned` ⏳ | `page_builder.php`, `bio.php` |

---

## ✅ Fitur yang Telah Selesai (Completed Milestones)

### 1. 🛡️ Keamanan & Perlindungan Data Produksi
- [x] **Pemisahan Kredensial Basis Data**: Password basis data MariaDB telah dipindahkan dari berkas kode ke `/var/www/s.labschool.xyz/.env` dengan izin berkas ketat (`chmod 600`, kepemilikan `nginx:nginx`).
- [x] **Nginx Security Rules**: Pemblokiran akses publik HTTP/HTTPS ke seluruh folder `/config/` (`return 404`) serta ekstensi sensitif (`.env`, `.sql`, `.log`, `.md`, `.bak`).
- [x] **Security Headers Lengkap**: `Strict-Transport-Security: max-age=31536000`, `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Permissions-Policy`.
- [x] **Sistem Proteksi Anti-CSRF**: Validasi token `csrf_token()` dan `verify_csrf_token()` pada seluruh endpoint POST internal.
- [x] **Pertahanan Stored XSS**: Sanitasi protokol URL (`sanitize_safe_url`) memblokir skema berbahaya (`javascript:`, `data:`, `vbscript:`).
- [x] **Atribut Cookie Sesi**: Konfigurasi otomatis `secure`, `HttpOnly`, dan `SameSite=Lax`.

### 2. 📄 Arsitektur Pages Bergaya Bitly & Master Template Collection
- [x] **Master Template Collection (20 Template Lengkap)**:
  - Terpusat di config/templates.php dengan inisialisasi blok, layout, tema warna, dan countdown.
  - **3 Template Microsite dari Folder (G:...\microsite-templates)**:
    * product-services-agency (Awesome UI/UX & Creative Agency)
    * digital-card-designer (Hand-Drawn Graphic Designer & Artist)
    * link-in-bio-web-design (Modern Web Design Studio)
  - **4 Variasi Template Baru per Kategori Standar Bitly**:
    * **Link-in-bio (5)**: Official Labs Portal, Student Creator & Podcast, OSIS & Ekskul Hub, Alumni Network, Web Design Studio.
    * **Product and services (5)**: Creative Agency, Koperasi & Merchandise, Bimbel & Ujian, Kantin Sehat, Penyewaan Fasilitas.
    * **Digital business card (5)**: Graphic Designer, Pendidik & Guru, Kepala Sekolah & Manajemen, Konselor BK, Dokter & UKS.
    * **Promotions (4)**: Open House & Edufair 2026, Promo Early Bird PSB 2027/2028, Pentas Seni & Bazaar, Beasiswa Bakat & Prestasi.
    * **Kanvas Kosong (1)**: Start from scratch.
  - **Render Dinamis Modal Template**: Filter kategori tabs instan (case-insensitive) dan hero image cards / gradient cards.
- [x] **Standarisasi Istilah 'Pages'**: Mengubah istilah ambigu `Microsite / Bio Link` menjadi `Pages` di seluruh dashboard, pengaturan, dan navigasi (di mana *Link-in-bio* kini merupakan salah satu kategori template).
- [x] **Flow Pembuatan Halaman Bergaya Bitly**:
  - `Pages` &rarr; `Create Page` &rarr; `Select Template Modal` &rarr; `Page Builder`.
- [x] **Koleksi 6 Preset Template Awal**:
  1. *Official Link in Bio*: Portal resmi sekolah, LMS, PSB online, YouTube Labschool.
  2. *School Event & Announcement*: Countdown hitung mundur acara, video teaser YouTube, registrasi online, unduh brosur PDF.
  3. *Digital Business Card*: Kartu nama guru/staf, one-click unduh kontak vCard (.vcf) ke HP, chat WhatsApp.
  4. *Portfolio & Showcase*: Dokumentasi proyek sains siswa, video presentasi, unduh paper riset PDF.
  5. *Alur & Roadmap Pendaftaran*: Alur bertahap 4 langkah pendaftaran PSB dengan nomor urut & FAQ akordion.
  6. *Start from Scratch*: Kanvas kosong siap rancang.
- [x] **Engine Baru `page_builder.php`**: Menggantikan `bio_editor.php` dengan inisialisasi preset otomatis, pemilihan layout Genially (*Showcase*, *Roadmap*, *Cards Grid*), pemilih tema (*Labs Navy*, *Cosmic Neon*, *Labs Gold*, *Emerald*, *Clean Slate*), dan countdown editor.
- [x] **Backward-Compatibility Redirect**: `bio_editor.php` dipertahankan sebagai HTTP 302 redirect stub meneruskan parameter query (`?id=...`, `?template=...`).
- [x] **Drawer Blok Komponen Slide-over (`#drawerAddBlock`)**: Pilihan elemen interaktif (Tautan, YouTube, Pengumuman, vCard, PDF, FAQ) dalam drawer responsif dengan efek sorotan animasi (*ring pulse*).
- [x] **Dukungan Render Publik di `bio.php`**: Render visual untuk berkas PDF, akordion FAQ, dan tombol vCard.

### 3. 📱 Dynamic Left Sidebar Drawer & Mini Icon-Rail (76px)
- [x] **Drawer Responsif**:
  - Di layar seluler (`< 768px`), sidebar berubah menjadi off-canvas drawer dengan latar belakang *backdrop blur overlay* (`#sidebarBackdrop`).
  - Menutup otomatis saat memilih menu tab atau menekan tombol <kbd>Esc</kbd>.
- [x] **Pita Baris Ikon (Mini-Rail) pada Desktop (`>= 768px`)**:
  - Saat di-collapse (`.desktop-collapsed`), sidebar menyusut secara elegan ke lebar 76px (`4.75rem`) tanpa hilang sepenuhnya.
  - Tetap menampilkan logo Labschool, tombol `+` ringkas, dan seluruh deretan ikon menu yang terpusat rapi (*centered*).
  - Dilengkapi *native tooltip* pada setiap ikon menu saat di-hover.
  - Tombol toggle di header atas (`#sidebarToggleIcon`) beradaptasi dinamis (`menu` &harr; `menu_open`).
  - Status collapse tersimpan di `localStorage` peramban.

### 4. 👥 Manajemen User, Organisasi, dan Super Admin
- [x] **Pemisahan Hak Akses User vs Admin**:
  - Halaman `admin.php` terisolasi ketat untuk super admin mengelola seluruh data platform.
  - Halaman `settings.php?tab=users` difokuskan untuk mengundang kolaborator dalam lingkup organisasi user.
- [x] **Grup Pembatas Scope (`settings.php?tab=groups`)**:
  - Pengguna dapat membuat grup untuk membatasi hak akses tautan/pages (maksimal 10 grup per akun).
- [x] **Tab Baru & Penonaktifan Tab Obsolet**:
  - Tab baru `bulk-upload` (dengan riwayat logs) dan tab `activity-log` di `settings.php`.

---

## 🚀 Rencana Fitur Baru & Backlog Prioritas

### 📄 Migrasi Penuh (Full Migration) dari `bio_editor.php` ke `page_builder.php`
Menuntaskan transisi arsitektural menyeluruh dari arsitektur editor legasi (`bio_editor.php` / `bio_edit.php`) ke engine modern `page_builder.php` hingga tidak ada lagi dependensi lawas.

- [x] **Penerapan Engine Baru**: Implementasi `page_builder.php` lengkap dengan drawer blok komponen dan inisialisasi preset template.
- [x] **Redirect Transisi**: `bio_editor.php` meneruskan query string (`?id=...`, `?template=...`) via HTTP 301 Permanent Redirect dengan header `Deprecation: true` dan `Link: </page_builder.php>; rel="successor-version"`.
- [x] **Migrasi Endpoint API Penyimpanan**:
  - Endpoint modern `api/save_page.php` diaktifkan dan menjadi target utama penyimpanan `page_builder.php`.
  - `api/save_microsite.php` dipertahankan sebagai proxy backward-compatibility dengan header `Deprecation: true`.
- [x] **Pembersihan & Standardisasi Nomenklatur Kode**:
  - Istilah 'Pages' distandarisasi di seluruh dashboard, drawer, modal, dan antarmuka.
- [x] **Siklus Depresiasi & Pensiun Berkas Usang**:
  - Upgrade redirect `bio_editor.php` ke HTTP 301 (Permanent Redirect).
  - Response headers `Deprecation: true` dan rel="successor-version" aktif.

---

### 1. 🔑 Request Reset Password (Forgot Password) via Email XOAUTH2
Menyediakan alur pemulihan kata sandi mandiri yang aman bagi pengguna yang lupa password melalui pengiriman tautan reset ke email resmi pengguna.

- [x] **Alur Permintaan Reset (`forgot_password.php`)**:
  - Form input alamat email terdaftar (`@labschool.xyz` atau `@elabschool.sch.id`).
  - Anti-bruteforce / Rate limiting per IP dan per email (maksimal 5 permintaan per jam).
  - Pengecekan akun aktif di basis data.
- [x] **Tabel & Token Keamanan (`password_resets`)**:
  - Skema tabel: `email`, `token` (64-byte cryptographic hash), `created_at`, `expires_at` (berlaku 30 menit), `is_used`.
  - Token hangus otomatis setelah sekali digunakan atau kedaluwarsa.
- [x] **Integrasi Email Server XOAUTH2 Google Cloud Console**:
  - Akun pengirim resmi: **`admin@labschool.xyz`**.
  - Autentikasi modern menggunakan **XOAUTH2** Service Account Domain-Wide Delegation (DwD) resmi dengan scope `https://mail.google.com/` dari Google Workspace for Education.
  - Pengiriman ultra-cepat (~300ms) menggunakan Gmail REST API RFC 2822 base64url encode, bebas blokir port firewall SMTP (`config/mail.php`).
- [x] **Desain Template Email Resmi Labschool**:
  - Layout responsif HTML resmi SMP Labschool Jakarta (Navy `#0F172A`, Blue `#2563EB`, logo sekolah, security footer).
  - Informasi waktu permintaan, alamat IP pemohon, dan tombol CTA *"Atur Ulang Kata Sandi"*.
  - Template sambutan resmi untuk konfirmasi pendaftaran civitas baru.
- [x] **Halaman Eksekusi Reset (`reset_password.php?token=...`)**:
  - Validasi ketat token sebelum menampilkan form password baru.
  - Indikator kekuatan kata sandi (*Password Strength Meter*) interaktif 4 parameter.
  - Re-hash password dengan `PASSWORD_BCRYPT` (cost 12) dan invalidasi token.
- [x] **Konfirmasi Registrasi Civitas Baru**:
  - Pendaftaran mandiri via `register.php` otomatis mengirimkan email konfirmasi selamat datang resmi berdesain Labschool.
- [x] **Admin Panel XOAUTH2 Verification**:
  - Indikator status koneksi XOAUTH2 dan widget uji coba kirim email verifikasi di `admin.php?tab=config`.

---

### 2. 🧹 Sembunyikan (Hide) Fitur & Tab yang Disabled di Settings User
Menghilangkan elemen navigasi yang tidak aktif agar antarmuka pengguna bersih, ringkas, dan bebas gangguan.

- [x] **Pembersihan Navigasi Sidebar `settings.php`**:
  - Menghilangkan sepenuhnya tombol tab yang berstatus *disabled* dari sidebar pengguna (`sso`, `mobile-deep-links`, `webhooks`, `billing`).
- [x] **Fallback Proteksi URL Query**:
  - Jika pengguna mengakses langsung via URL `settings.php?tab=sso` atau `billing`, otomatis redirect halus (*graceful redirect*) ke `settings.php?tab=account-details`.
- [x] **Tampilan Menu Settings Bersih**:
  - Tab aktif yang tersisa hanya: `account-details`, `users`, `groups`, `bulk-upload`, `activity-log`, `integrations`, `profile`, `api`.

---

### 3. ⚙️ Admin Panel: Global Configuration Module
Menghadirkan panel konfigurasi terpusat di `admin.php` bagi Super Admin untuk mengontrol pengaturan sistem tanpa perlu mengubah file konfigurasi server secara manual.

- [x] **Tabel Basis Data Konfigurasi (`global_settings`)**:
  - Skema tabel: `setting_key` (VARCHAR PK), `setting_value` (LONGTEXT), `category` (VARCHAR), `updated_at`, `updated_by`.
- [x] **Pengaturan Aplikasi & Domain**:
  - Nama Platform / Brand (Default: `s.labschool.xyz`), Judul Platform.
  - Domain pemendek utama (`s.labschool.xyz`).
  - Fallback Redirect URL jika tautan pendek tidak ditemukan (`https://labschool.xyz`).
- [x] **Kontrol Pendaftaran Akun (Registration Policy)**:
  - Toggle buka/tutup pendaftaran mandiri (`allow_public_registration`).
  - Whitelist domain email yang diizinkan mendaftar (`labschool.xyz, elabschool.sch.id`).
- [ ] **Pengaturan Email Server & XOAUTH2 (Khusus Google Workspace - Excluded)**:
  - Pengaturan akun `admin@labschool.xyz`.
  - Input Client ID, Client Secret, Refresh Token Google Cloud Console.
- [x] **Keamanan & Rate Limiting**:
  - Maksimal request per menit per IP (Anti-DDoS / Scraping).
  - Batas waktu sesi idle pengguna (*Session Timeout* hari).
- [x] **Mode Pemeliharaan (Maintenance Mode)**:
  - Toggle *Maintenance Mode* platform dengan pesan banner khusus.

---

### 4. 🔐 Login OAuth2 Google Workspace Labschool (Sprint 1)
Mengintegrasikan Single Sign-On (SSO) Google Identity Platform khusus akun sekolah.

- [ ] **Google Cloud Console Credentials**:
  - OAuth 2.0 Client ID & Secret untuk `s.labschool.xyz`.
  - Authorized Redirect URI: `https://s.labschool.xyz/auth/google/callback.php`.
- [ ] **Restriksi Ketat Domain Akun**:
  - Pembatasan domain hosted (`hd` parameter dan verifikasi server-side):
    - `@labschool.xyz`
    - `@elabschool.sch.id`
  - Penolakan otomatis (HTTP 403) jika login dengan email publik (`@gmail.com`).
- [ ] **Sinkronisasi Profil & Auto-Provisioning**:
  - Auto-create user jika baru pertama kali login: nama lengkap, email resmi, avatar Google HD.
  - Auto-assign API Key unik (`labs_...`).
- [ ] **UI Tombol Login**:
  - Tombol *"Masuk dengan Google Workspace Labschool"* di `login.php` dan `register.php`.

---

### 5. ⚡ Redis Session Management (Sprint 1)
Menggantikan sesi berbasis berkas PHP (`/var/lib/php/sessions`) dengan cache in-memory **Redis**.

- [x] **Handler Konfigurasi `php-redis`**:
  - `session.save_handler = redis`
  - `session.save_path = "tcp://127.0.0.1:6379?prefix=labs_sess:&timeout=2.0"`
- [x] **TTL & Keamanan**:
  - Session TTL dinamis: 7 hari dengan cookie secure, HttpOnly, SameSite=Lax.
  - Sesi otomatis dibersihkan saat `logout.php`.
- [x] **Fail-Safe Mechanism**:
  - Fallback otomatis dan transparan ke file jika daemon Redis mengalami kendala koneksi.

---

### 6. 📊 Metrics Usage pada Admin Panel (Sprint 2)
Visibilitas kapasitas dan beban sumber daya platform di `admin.php`.

- [x] **Storage Usage**:
  - Ukuran direktori upload aset (`/assets/img/`, avatar kustom, logo QR).
  - Indikator kapasitas dan sisa ruang kosong partisi disk server beserta bar persentase visual.
- [x] **Data Usage & Bandwidth**:
  - Ringkasan hit klik: Hari Ini, 7 Hari Terakhir, 30 Hari Terakhir, Total Seumur Hidup.
- [x] **User Activity Metrics**:
  - Distribusi domain civitas (`@labschool.xyz` vs `@elabschool.sch.id`).
  - Breakdown peran akun (Superadmin, Guru, Staff, Siswa).
  - Distribusi perangkat pengunjung (Mobile, Desktop, Tablet).
- [x] **Database Analytics (MariaDB)**:
  - Ukuran tabel individu via `information_schema.TABLES` (`short_urls`, `click_logs`, `microsites`, `users`, `activity_logs`, dll).
  - Total ukuran database gabungan index dan data.

---

### 7. 🎨 Advance Page Builder Phase 2 (Sprint 3)
Fitur tingkat lanjut untuk `page_builder.php` dan `bio.php`.

- [ ] **Background Kustom Tingkat Lanjut**:
  - Animated CSS Gradient Mesh.
  - Pola geometris / pattern (dots grid, diagonal lines).
  - Unggah gambar background dengan slider blur dan dark overlay.
- [ ] **Color Palette Selector**:
  - Color picker kustom untuk teks, tombol, dan border.
  - Preset warna resmi Labschool (Royal Blue `#1E40AF`, Amber `#F59E0B`, Emerald `#10B981`).
- [ ] **Widget Tambahan**:
  - Audio Player Card (Spotify embed / Mars Labschool).
  - Google Drive Folder Embed / PDF Viewer interaktif.

---

### 8. 📱 UI/UX Mobile Refinements (Sprint 3)
- [ ] **Mobile Bottom-Sheet untuk Detail Tautan**:
  - Mengubah sliding drawer kanan menjadi bottom-sheet drawer pada layar seluler (`< 768px`) dengan drag handle.
- [ ] **Card View Tables di Mobile**:
  - Mengonversi tabel horizontal lebar di `dashboard.php` dan `settings.php` menjadi tampilan kartu vertikal pada smartphone.

---

### 9. 🖼️ Tambahan QR Style (Sprint 4)
Kustomisasi kode QR terlengkap di `api/qr.php` dan modal QR di `dashboard.php`.

- [x] **Logo Tengah (Center Logo)**:
  - Preset logo resmi: SMP Labschool Jakarta dengan safe white margin.
  - Opsi Tanpa Logo (polos) via selector tombol di dashboard drawer.
- [x] **Badge & Frame CTA**:
  - Bingkai badge: Tanpa Frame, Badge Bawah ("PINDAI SAYA"), Badge Atas (Header Banner), Card Border.
- [ ] **Gaya Titik & Sudut QR**:
  - Matriks titik: *Square*, *Rounded Dots*, *Smooth*.
  - Mata sudut (*Corner Eyes*): *Square*, *Circle Ring*.
- [x] **Format Ekspor**:
  - PNG High-Resolution untuk cetak spanduk/poster.
  - SVG Vektor murni dengan tombol unduh langsung.

---

## 📅 Rencana Tahapan Rilis (Milestones)

```
[Release v1.2] ✅ Hardening Keamanan, Bitly-Style Pages, Mini-Rail Left Drawer, User Groups
[Sprint 1]     ───> Fully Migrate ke page_builder.php + Reset Password via Email XOAUTH2 + Hide Disabled Tabs Settings
[Sprint 2]     ───> Admin Global Configuration Module + Metrics Usage (Storage, Data, DB)
[Sprint 3]     ───> Login OAuth2 Google Workspace Labschool + Redis Session Management
[Sprint 4]     ───> Mobile View Refinement (Bottom-Sheet & Cards) + Advance Page Builder Phase 2
[Sprint 5]     ───> Tambahan QR Style (Logo Presets, Frame Badges, Vector Export)
```

---
*Dokumen ini diperbarui secara berkala sesuai perkembangan kode dan kebutuhan operasional s.labschool.xyz.*
