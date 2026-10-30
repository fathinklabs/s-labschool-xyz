# 📋 Roadmap & Status Pengembangan (TODOS) - s.labschool.xyz

Dokumen ini mencatat rencana kerja, backlog fitur prioritas, status rilis, dan spesifikasi arsitektur teknis platform **s.labschool.xyz** (Labschool Enterprise URL Shortener, Dynamic QR Code, and Interactive Pages Platform &bull; SMP Labschool Jakarta).

---

## 📌 Matriks Status Fitur & Modul

| Kategori | Fitur / Item | Prioritas | Status | Target Modul / File |
| :--- | :--- | :---: | :---: | :--- |
| **Keamanan & Kredensial** | Isolasi `.env`, Anti-CSRF, XSS Sanitasi, Nginx Hardening | 🛡️ Kritis | `Completed` ✅ | `.env`, `config/`, `s.labschool.xyz.conf` |
| **Pages Platform** | Bitly-Style Flow (`Create Page` &rarr; `Template` &rarr; `Builder`) | 🔥 Tinggi | `Completed` ✅ | `dashboard.php`, `page_builder.php`, `bio.php` |
| **Drawer Navigasi** | Left Sidebar Drawer Dinamis + Mini Icon-Rail (76px) | ⚡ Sedang | `Completed` ✅ | `dashboard.php` |
| **User & Organisasi** | Scope Kolaborasi User, Grup (Max 10), Activity Log, Bulk Upload | 🔥 Tinggi | `Completed` ✅ | `settings.php`, `admin.php` |
| **Settings UI/UX** | Sembunyikan (Hide) Fitur & Tab yang Disabled di Settings User | ⚡ Sedang | `Planned` ⏳ | `settings.php` |
| **Autentikasi & Email** | Request Reset Password (Forgot Password) via Email XOAUTH2 | 🔥 Tinggi | `Planned` ⏳ | `forgot_password.php`, `reset_password.php`, `config/mail.php` |
| **Admin Panel** | Global System Configuration (Site, Auth, SMTP, Rate Limits) | 🔥 Tinggi | `Planned` ⏳ | `admin.php`, `api/admin/config.php` |
| **Autentikasi SSO** | Login OAuth2 Google Workspace Labschool (`@labschool.xyz`) | 🔥 Tinggi | `Planned` ⏳ | `config/google_auth.php`, `login.php` |
| **Infrastruktur** | Redis Session Management | 🔥 Tinggi | `Planned` ⏳ | `config/session.php`, `config/redis.php` |
| **Admin Panel** | Metrics Usage (Storage, Data Egress, User, DB Stats) | ⚡ Sedang | `Planned` ⏳ | `admin.php`, `api/admin/metrics.php` |
| **UI/UX Mobile** | Mobile Bottom-Sheet Detail & Card View Tables | ⚡ Sedang | `In Progress` 🚧 | `dashboard.php`, `settings.php` |
| **QR Code Engine** | Tambahan QR Style (Logo Preset, Frame Badge, Matrix Eye) | ⚡ Sedang | `Planned` ⏳ | `api/qr.php`, `dashboard.php` |
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

### 2. 📄 Arsitektur Pages Bergaya Bitly & Page Builder
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

### 1. 🔑 Request Reset Password (Forgot Password) via Email XOAUTH2
Menyediakan alur pemulihan kata sandi mandiri yang aman bagi pengguna yang lupa password melalui pengiriman tautan reset ke email resmi pengguna.

- [ ] **Alur Permintaan Reset (`forgot_password.php`)**:
  - Form input alamat email terdaftar (`@labschool.xyz` atau `@elabschool.sch.id`).
  - Anti-bruteforce / Rate limiting per IP dan per email (maksimal 3 permintaan per jam).
  - Pengecekan akun aktif di basis data.
- [ ] **Tabel & Token Keamanan (`password_resets`)**:
  - Skema tabel: `email`, `token` (64-byte cryptographic hash), `created_at`, `expires_at` (berlaku 15-30 menit), `is_used`.
  - Token hangus otomatis setelah sekali digunakan atau kedaluwarsa.
- [ ] **Integrasi Email Server XOAUTH2 Google Cloud Console**:
  - Akun pengirim resmi: **`admin@labschool.xyz`**.
  - Autentikasi modern menggunakan **XOAUTH2** (bukan password aplikasi SMTP statis yang rentan) melalui Google Cloud Console OAuth 2.0 Client credentials dari Google Workspace for Education.
  - Penanganan token refresh otomatis (*refresh_token*) untuk mendapatkan *access_token* saat pengiriman email berlangsung (`config/mail.php`).
- [ ] **Desain Template Email Resmi Labschool**:
  - Header berlogo resmi SMP Labschool Jakarta.
  - Informasi waktu permintaan, alamat IP pemohon, dan tombol CTA *"Atur Ulang Kata Sandi"*.
  - Catatan keamanan: *"Abaikan email ini jika Anda tidak meminta reset kata sandi"*.
- [ ] **Halaman Eksekusi Reset (`reset_password.php?token=...`)**:
  - Validasi ketat token sebelum menampilkan form password baru.
  - Indikator kekuatan kata sandi (*Password Strength Meter*).
  - Re-hash password dengan `PASSWORD_BCRYPT` (cost 12) dan terminasi sesi lama secara global.

---

### 2. 🧹 Sembunyikan (Hide) Fitur & Tab yang Disabled di Settings User
Menghilangkan elemen navigasi yang tidak aktif agar antarmuka pengguna bersih, ringkas, dan bebas gangguan.

- [ ] **Pembersihan Navigasi Sidebar `settings.php`**:
  - Menghilangkan sepenuhnya tombol tab yang berstatus *disabled* dari sidebar pengguna:
    - `tab=sso` (SSO Enterprise)
    - `tab=mobile-deep-links` (Mobile Deep Linking)
    - `tab=webhooks` (Webhooks & Event Listeners)
    - `tab=billing` (Billing & Kuota Langganan)
- [ ] **Fallback Proteksi URL Query**:
  - Jika pengguna mengakses langsung via URL `settings.php?tab=sso` atau `billing`, lakukan redirect halus (*graceful redirect*) ke `settings.php?tab=account-details` dengan notifikasi informatif.
- [ ] **Tampilan Menu Settings Bersih**:
  - Tab aktif yang tersisa hanya:
    1. `account-details` (Profil & Akun)
    2. `users` (Kolaborator Organisasi Pengguna)
    3. `groups` (Pengelolaan Grup Tautan, Max 10)
    4. `bulk-upload` (Riwayat & Pemendek Massal)
    5. `activity-log` (Log Aktivitas Pengguna)
    6. `integrations` (Integrasi & API Key)

---

### 3. ⚙️ Admin Panel: Global Configuration Module
Menghadirkan panel konfigurasi terpusat di `admin.php` bagi Super Admin untuk mengontrol pengaturan sistem tanpa perlu mengubah file konfigurasi server secara manual.

- [ ] **Tabel Basis Data Konfigurasi (`global_settings`)**:
  - Skema tabel: `setting_key` (VARCHAR PK), `setting_value` (LONGTEXT), `category` (VARCHAR), `updated_at`, `updated_by`.
- [ ] **Pengaturan Aplikasi & Domain**:
  - Nama Platform / Brand (Default: `s.labschool.xyz`).
  - Domain pemendek utama & alternatif yang diizinkan.
  - Fallback Redirect URL jika tautan pendek tidak ditemukan (Default ke landing page atau portal resmi sekolah).
- [ ] **Kontrol Pendaftaran Akun (Registration Policy)**:
  - Toggle buka/tutup pendaftaran publik (`allow_public_registration`).
  - Whitelist domain email yang diizinkan mendaftar (`labschool.xyz`, `elabschool.sch.id`).
  - Kuota tautan default untuk akun baru.
- [ ] **Pengaturan Email Server & XOAUTH2**:
  - Pengaturan akun `admin@labschool.xyz`.
  - Input Client ID, Client Secret, Refresh Token Google Cloud Console.
  - Tombol *"Test Kirim Email"* untuk memverifikasi autentikasi XOAUTH2 langsung dari admin panel.
- [ ] **Keamanan & Rate Limiting**:
  - Maksimal klik tautan per menit per IP (Anti-DDoS / Scraping).
  - Rate limit pemanggilan REST API publik.
  - Batas waktu sesi idle pengguna (*Session Timeout*).
- [ ] **Mode Pemeliharaan (Maintenance Mode)**:
  - Toggle *Maintenance Mode* platform dengan pengecualian IP administrator.
  - Pesan banner khusus yang tampil saat pemeliharaan sistem.

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

- [ ] **Handler Konfigurasi `php-redis`**:
  - `session.save_handler = redis`
  - `session.save_path = "tcp://127.0.0.1:6379?prefix=labs_sess:&timeout=2.0"`
- [ ] **TTL & Keamanan**:
  - Session TTL dinamis: 7 hari untuk *Remember Me*, 24 jam untuk sesi standar.
  - Sesi otomatis dibersihkan saat `logout.php`.
- [ ] **Fail-Safe Mechanism**:
  - Fallback otomatis ke basis data/file jika daemon Redis mengalami *downtime*.

---

### 6. 📊 Metrics Usage pada Admin Panel (Sprint 2)
Visibilitas kapasitas dan beban sumber daya platform di `admin.php`.

- [ ] **Storage Usage**:
  - Ukuran direktori upload aset (`/assets/img/`, avatar kustom, logo QR).
  - Indikator sisa kapasitas partisi disk server (`df -h`).
- [ ] **Data Usage & Bandwidth**:
  - Estimasi volume transfer data keluar (Egress Bandwidth) bulanan.
  - Grafik hit redirect link per hari/minggu/bulan.
- [ ] **User Activity Metrics**:
  - Rasio pengguna aktif (DAU / WAU / MAU).
  - Distribusi domain (`@labschool.xyz` vs `@elabschool.sch.id`).
- [ ] **Database Analytics (MariaDB)**:
  - Ukuran tabel individu: `short_urls`, `click_logs`, `microsites`, `activity_logs`.
  - Overhead index & ukuran buffer pool.

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

- [ ] **Logo Tengah (Center Logo)**:
  - Preset logo resmi: SMP Labschool Jakarta, SMA Labschool Jakarta, KB-TK Labschool, BPS Labschool UNJ.
  - Fitur unggah logo kustom pengguna dengan *safe white margin*.
- [ ] **Badge & Frame CTA**:
  - Bingkai badge: *"PINDAI SAYA"*, *"DAFTAR DI SINI"*, *"MATERI KELAS"*.
- [ ] **Gaya Titik & Sudut QR**:
  - Matriks titik: *Square*, *Rounded Dots*, *Smooth*.
  - Mata sudut (*Corner Eyes*): *Square*, *Circle Ring*.
- [ ] **Format Ekspor**:
  - PNG Ultra HD (resolusi tinggi untuk cetak spanduk).
  - SVG Vektor murni.

---

## 📅 Rencana Tahapan Rilis (Milestones)

```
[Release v1.2] ✅ Hardening Keamanan, Bitly-Style Pages, Mini-Rail Left Drawer, User Groups
[Sprint 1]     ───> Reset Password via Email XOAUTH2 + Hide Disabled Tabs Settings
[Sprint 2]     ───> Admin Global Configuration Module + Metrics Usage (Storage, Data, DB)
[Sprint 3]     ───> Login OAuth2 Google Workspace Labschool + Redis Session Management
[Sprint 4]     ───> Mobile View Refinement (Bottom-Sheet & Cards) + Advance Page Builder Phase 2
[Sprint 5]     ───> Tambahan QR Style (Logo Presets, Frame Badges, Vector Export)
```

---
*Dokumen ini diperbarui secara berkala sesuai perkembangan kode dan kebutuhan operasional s.labschool.xyz.*
