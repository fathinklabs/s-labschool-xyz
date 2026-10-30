# 📋 Roadmap & Status Pengembangan (TODOS) - s.labschool.xyz

Dokumen ini mencatat rencana kerja, status rilis fitur, dan arsitektur teknis platform **s.labschool.xyz** (Labschool Enterprise URL Shortener, Dynamic QR Code, and Interactive Pages Platform &bull; SMP Labschool Jakarta).

---

## 📌 Matriks Status Fitur & Modul

| Kategori | Fitur / Item | Prioritas | Status | Target Modul / File |
| :--- | :--- | :---: | :---: | :--- |
| **Keamanan & Kredensial** | Isolasi `.env`, Anti-CSRF, XSS Sanitasi, Nginx Hardening | 🛡️ Kritis | `Completed` ✅ | `.env`, `config/`, `s.labschool.xyz.conf` |
| **Pages Platform** | Bitly-Style Flow (`Create Page` &rarr; `Template` &rarr; `Builder`) | 🔥 Tinggi | `Completed` ✅ | `dashboard.php`, `page_builder.php`, `bio.php` |
| **Drawer Navigasi** | Left Sidebar Drawer Dinamis + Mini Icon-Rail (76px) | ⚡ Sedang | `Completed` ✅ | `dashboard.php` |
| **User & Organisasi** | Scope Kolaborasi User, Grup (Max 10), Activity Log, Bulk Upload | 🔥 Tinggi | `Completed` ✅ | `settings.php`, `admin.php` |
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
  - Menonaktifkan tab yang belum diimplementasikan (`sso`, `mobile-deep-links`, `webhooks`, `billing`).

---

## 🚀 Rencana Fitur Mendatang (Upcoming Backlog)

### 1. 🔐 Login OAuth2 Google Workspace Labschool (Sprint 1)
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

### 2. ⚡ Redis Session Management (Sprint 1)
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

### 3. 📊 Metrics Usage pada Admin Panel (Sprint 2)
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

### 4. 🎨 Advance Page Builder Phase 2 (Sprint 3)
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

### 5. 📱 UI/UX Mobile Refinements (Sprint 3)
- [ ] **Mobile Bottom-Sheet untuk Detail Tautan**:
  - Mengubah sliding drawer kanan menjadi bottom-sheet drawer pada layar seluler (`< 768px`) dengan drag handle.
- [ ] **Card View Tables di Mobile**:
  - Mengonversi tabel horizontal lebar di `dashboard.php` dan `settings.php` menjadi tampilan kartu vertikal pada smartphone.

---

### 6. 🖼️ Tambahan QR Style (Sprint 4)
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
[Sprint 1]     ───> Login OAuth2 Google Workspace Labschool + Redis Session Management
[Sprint 2]     ───> Metrics Usage pada Admin Panel (Storage, Data, DB Analytics)
[Sprint 3]     ───> Mobile View Refinement (Bottom-Sheet & Cards) + Advance Page Builder Phase 2
[Sprint 4]     ───> Tambahan QR Style (Logo Presets, Frame Badges, Vector Export)
```

---
*Dokumen ini diperbarui secara berkala sesuai perkembangan kode dan kebutuhan operasional s.labschool.xyz.*
