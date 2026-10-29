# 🚀 s.labschool.xyz — Enterprise URL Shortener, Dynamic QR Code & Microsite Platform

[![Labschool Official](https://img.shields.io/badge/Labschool-Ecosystem-1E40AF?style=for-the-badge&logo=school)](https://s.labschool.xyz)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MariaDB](https://img.shields.io/badge/MariaDB-10.5%2B-003545?style=for-the-badge&logo=mariadb&logoColor=white)](https://mariadb.org)
[![Nginx](https://img.shields.io/badge/Nginx-Reverse_Proxy-009639?style=for-the-badge&logo=nginx&logoColor=white)](https://nginx.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)

**`s.labschool.xyz`** adalah platform pemendek tautan mandiri (*enterprise self-hosted URL shortener*), pembuat kode QR dinamis berbingkai profesional ala **Bitly**, dan perancang halaman tautan interaktif (*link in bio / microsite*) modern ala **Genially** yang dirancang khusus untuk ekosistem **Labschool** (SMP, SMA, KB-TK, dan PSB Labschool).

---

## 📑 Daftar Isi

- [Fitur Utama](#-fitur-utama)
  - [1. Enterprise URL Shortener & Deep Linking](#1-enterprise-url-shortener--deep-linking)
  - [2. Bitly-Style Dynamic QR Code Generator](#2-bitly-style-dynamic-qr-code-generator)
  - [3. Genially-Style Interactive Microsite Builder](#3-genially-style-interactive-microsite-builder)
  - [4. Real-time Analytics & Geolocation](#4-real-time-analytics--geolocation)
  - [5. User & Role Management](#5-user--role-management)
  - [6. Developer REST API](#6-developer-rest-api)
- [Arsitektur & Struktur Direktori](#-arsitektur--struktur-direktori)
- [Prasyarat Sistem](#-prasyarat-sistem)
- [Instalasi & Konfigurasi](#-instalasi--konfigurasi)
  - [1. Clone Repository](#1-clone-repository)
  - [2. Setup Database MariaDB](#2-setup-database-mariadb)
  - [3. Konfigurasi Environment & Database](#3-konfigurasi-environment--database)
  - [4. Konfigurasi Web Server Nginx](#4-konfigurasi-web-server-nginx)
- [Dokumentasi REST API](#-dokumentasi-rest-api)
  - [Autentikasi](#autentikasi)
  - [Endpoint 1: Create Short Link](#endpoint-1-create-short-link)
  - [Endpoint 2: Bulk Shorten Links](#endpoint-2-bulk-shorten-links)
  - [Endpoint 3: Regenerate API Key](#endpoint-3-regenerate-api-key)
- [Keamanan & Proteksi Sistem](#-keamanan--proteksi-sistem)
- [Operasional & Pemeliharaan Server](#-operasional--pemeliharaan-server)
- [Lisensi & Kontribusi](#-lisensi--kontribusi)

---

## ✨ Fitur Utama

### 1. Enterprise URL Shortener & Deep Linking
- **Custom Vanity Slug**: Bebas menentukan tautan bermerek (misal: `s.labschool.xyz/psb-2027`, `s.labschool.xyz/info-ujian`).
- **Auto-generated Slug**: Kode unik otomatis berbasis algoritma Base62 yang ringkas dan aman.
- **Smart Deep Linking**: Otomatis mendeteksi dan membuka aplikasi mobile native secara langsung tanpa tertahan di in-app browser:
  - YouTube App (`vnd.youtube:`)
  - Instagram App (`instagram://user?username=...`)
  - WhatsApp Direct Chat (`whatsapp://send?phone=...`)
  - Google Drive & Zoom Meeting protocol
- **Password Protection**: Proteksi akses tautan dengan kata sandi/PIN khusus sebelum pengguna diarahkan.
- **Link Expiration**: Pengaturan tanggal dan jam kedaluwarsa tautan secara otomatis.
- **Instant Status Toggle**: Nonaktifkan atau aktifkan kembali tautan seketika dengan switch on/off.
- **Drawer Editor**: Edit tautan dan target tujuan langsung melalui antarmuka side-drawer tanpa berpindah halaman.

### 2. Bitly-Style Dynamic QR Code Generator
- **Frame & Template Kustom**:
  - *Standard* (Clean QR code tanpa bingkai)
  - *Scan Me* (Badge teks instruksi di atas atau bawah)
  - *Badge Minimalist* (Aksen warna Labschool)
  - *Card & Rounded Modern*
- **Kustomisasi Visual & Warna**:
  - Palet warna resmi Labschool: **Royal Blue (`#1E40AF`)**, **Navy (`#1E3A8A`)**, **Amber Gold (`#F59E0B`)**, Emerald, Crimson, dan Custom Hex.
  - Opsi penanaman Logo Labschool di titik tengah kode QR tanpa mengorbankan koreksi eror (*High Error Correction Level H*).
- **Ekspor Berkualitas Tinggi**: Download dalam format PNG resolusi tinggi dan SVG untuk kebutuhan percetakan brosur/spanduk sekolah.

### 3. Genially-Style Interactive Microsite Builder
- **Desain Interaktif & Modern**: Alternatif Linktree yang lebih kaya fitur untuk profil guru, kepanitiaan, atau media center sekolah:
  - **Pilihan Tema**: *Cosmic School*, *Academic Modern*, *Clean Minimal*, *Vibrant Gradient*, dan *Dark Luxury*.
  - **Interactive Components**:
    - Header Banner & Avatar foto profil berbingkai transparan.
    - **Live Event Countdown Timer** (hitung mundur kegiatan seperti Open House atau Ujian).
    - Social Media Dock (Instagram, YouTube, TikTok, WhatsApp, Web Labschool, Email).
    - Link Action Buttons dengan efek hover gelombang mikro & ripple.
    - **Kartu Kontak Digital (vCard)**: Download kontak `.vcf` langsung ke buku telepon smartphone pengunjung via `/vcard.php?id=...`.
- **Live Preview & WYSIWYG Editor**: Pengaturan tata letak dan pratinjau tampilan perangkat ponsel secara *real-time*.

### 4. Real-time Analytics & Geolocation
- **Total & Unique Clicks**: Pelacakan akurat rasio klik unik vs klik berulang.
- **Geolokasi Pengunjung**: Deteksi negara, provinsi, dan kota asal pengunjung.
- **Perangkat & Platform**: Statistik proporsi pengguna Ponsel (Android/iOS), Desktop (Windows/macOS), dan Tablet.
- **Sumber Rujukan (Referrer)**: Memantau trafik langsung dari WhatsApp, Instagram, Google, Facebook, atau Direct URL.
- **Ekspor Data**: Download log klik dalam format file `.csv`.

### 5. User & Role Management
- **Role-Based Access Control**:
  - `admin`: Manajemen seluruh pengguna, audit tautan global, reset kredensial, dan rekap sistem.
  - `user`: Pengelolaan tautan, kode QR, dan microsite milik masing-masing akun.
- **Audit & Keamanan Akun**:
  - Password hashing standar tinggi (`PASSWORD_BCRYPT` / `Argon2ID`).
  - Brute-force & login lockout protection.
  - Pengaturan API Key individual untuk integrasi bot atau sistem eksternal.

### 6. Developer REST API
- Menyediakan endpoint RESTful JSON untuk otomasi pembuatan tautan cepat melalui skrip backend, Google Apps Script, atau CLI.

---

## 🏛️ Arsitektur & Struktur Direktori

```text
s-labschool-xyz/
├── api/                              # Endpoint REST API & Operasi Backend
│   ├── admin/
│   │   └── users.php                 # Manajemen pengguna oleh admin
│   ├── bulk_shorten.php              # Pemendekan tautan massal via CSV/JSON
│   ├── delete_link.php               # Hapus tautan
│   ├── delete_microsite.php          # Hapus microsite
│   ├── export_csv.php                # Ekspor analitik ke CSV
│   ├── qr.php                        # Generator backend kode QR dinamis
│   ├── save_microsite.php            # Simpan data & tata letak microsite
│   ├── shorten.php                   # Endpoint utama pembuatan tautan pendek
│   ├── toggle_status.php             # Toggle aktif/nonaktif tautan
│   ├── update_link.php               # Perbarui parameter tautan
│   └── user/
│       └── api_key.php               # Generate ulang API key pengguna
├── assets/                           # Asset Statis (Gambar, CSS, JS)
│   ├── img/
│   │   └── logo-smp-labschool.png    # Brand aset resmi Labschool
│   └── js/
│       └── qrcode.min.js             # Engine rendering QR sisi klien
├── config/                           # Konfigurasi Inti
│   ├── auth.php                      # Sistem sesi & middleware otentikasi
│   ├── database.php                  # Koneksi PDO MariaDB
│   └── helpers.php                   # Fungsi utilitas & Base62 encoder
├── admin.php                         # Dashboard khusus Administrator
├── analytics.php                     # Halaman detail grafik & log analitik
├── bio.php                           # Tampilan publik Microsite (Link in Bio)
├── bio_editor.php                    # Panel perancang WYSIWYG Microsite
├── dashboard.php                     # Dashboard utama pengguna (Bitly layout)
├── index.php                         # Landing page publik & quick shortener
├── login.php                         # Halaman autentikasi masuk
├── logout.php                        # Terminasi sesi akun
├── redirect.php                      # Handler pengalihan cepat & pencatat klik
├── register.php                      # Pendaftaran akun baru
├── s.labschool.xyz.conf              # Konfigurasi virtual host Nginx produksi
├── schema.sql                        # Struktur DDL basis data MariaDB
├── vcard.php                         # Generator file kontak virtual (vCard 3.0)
└── README.md                         # Dokumentasi lengkap sistem
```

---

## ⚙️ Prasyarat Sistem

| Komponen | Versi Rekomendasi | Keterangan |
| :--- | :--- | :--- |
| **Sistem Operasi** | Rocky Linux 9 / RHEL 9 / Ubuntu 22.04+ | Teruji di lingkungan produksi enterprise |
| **Web Server** | Nginx 1.20+ | Dilengkapi modul `ngx_http_rewrite_module` |
| **PHP Runtime** | PHP 8.2 atau 8.3 | Ekstensi: `pdo_mysql`, `curl`, `gd`, `mbstring`, `json` |
| **Basis Data** | MariaDB 10.5+ / MySQL 8.0+ | Engine InnoDB, Charset `utf8mb4` |
| **SSL Certificate** | Let's Encrypt / Cloudflare Origin CA | Wajib HTTPS untuk deep linking & geolocation |

---

## 🛠️ Instalasi & Konfigurasi

### 1. Clone Repository

```bash
# Clone repository ke direktori web server
git clone git@github.com:fathinklabs/s-labschool-xyz.git /var/www/s.labschool.xyz
cd /var/www/s.labschool.xyz
```

### 2. Setup Database MariaDB

Buat database dan pengguna database baru, kemudian impor struktur tabel:

```sql
CREATE DATABASE s_labschool_xyz CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 's_labschool_user'@'localhost' IDENTIFIED BY 'PASSWORD_RAHASIA_ANDA';
GRANT ALL PRIVILEGES ON s_labschool_xyz.* TO 's_labschool_user'@'localhost';
FLUSH PRIVILEGES;
```

Impor skema SQL:

```bash
mysql -u s_labschool_user -p s_labschool_xyz < schema.sql
```

> **Akun Default Admin Awal**:
> - Email: `admin@labschool.sch.id`
> - Password: Diatur melalui skrip inisialisasi atau di-hash dengan `password_hash('PasswordBaru', PASSWORD_BCRYPT)`.

### 3. Konfigurasi Environment & Database

Konfigurasi database dapat diatur via variabel lingkungan sistem (environment variable) atau langsung di file `config/database.php`:

```php
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_port = getenv('DB_PORT') ?: '3306';
$db_name = getenv('DB_NAME') ?: 's_labschool_xyz';
$db_user = getenv('DB_USER') ?: 's_labschool_user';
$db_pass = getenv('DB_PASS') ?: 'PASSWORD_RAHASIA_ANDA';
```

Atur izin berkas (ownership) agar dapat dibaca oleh worker Nginx/PHP-FPM:

```bash
chown -R antigravity:nginx /var/www/s.labschool.xyz
chmod -R 755 /var/www/s.labschool.xyz
```

### 4. Konfigurasi Web Server Nginx

Salin file `s.labschool.xyz.conf` ke folder konfigurasi Nginx:

```bash
sudo cp s.labschool.xyz.conf /etc/nginx/conf.d/s.labschool.xyz.conf
```

Pastikan blok pengalihan (*URL rewriting*) aktif agar short slug langsung ditangani oleh `redirect.php`:

```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name s.labschool.xyz;
    root /var/www/s.labschool.xyz;
    index index.php index.html;

    # SSL Certificates
    ssl_certificate /etc/pki/tls/certs/s.labschool.xyz.crt;
    ssl_certificate_key /etc/pki/tls/private/s.labschool.xyz.key;

    # Handle short link redirect
    location / {
        try_files $uri $uri/ /redirect.php?slug=$uri&$args;
    }

    # Handle PHP Scripts
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php-fpm/www.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

Uji dan reload konfigurasi Nginx:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

---

## 📡 Dokumentasi REST API

### Autentikasi
Gunakan **API Key** akun Anda yang tertera di menu pengaturan akun pada Dashboard. Masukkan token tersebut pada header HTTP:

```http
Authorization: Bearer <API_KEY_ANDA>
Content-Type: application/json
```

---

### Endpoint 1: Create Short Link

Membuat satu tautan pendek baru beserta opsi custom slug, proteksi password, dan tanggal kedaluwarsa.

- **Method**: `POST`
- **URL**: `https://s.labschool.xyz/api/shorten.php`
- **Header**:
  ```http
  Authorization: Bearer labs_3b14b10ac95fec88ae7f756a57c920ef...
  Content-Type: application/json
  ```
- **Request Body (JSON)**:
  ```json
  {
    "original_url": "https://smplabschooljakarta.sch.id/pengumuman-kelulusan-2026",
    "custom_slug": "kelulusan-2026",
    "title": "Pengumuman Kelulusan 2026",
    "password": "",
    "expires_at": "2026-12-31 23:59:59"
  }
  ```
- **Contoh cURL**:
  ```bash
  curl -X POST https://s.labschool.xyz/api/shorten.php \
    -H "Authorization: Bearer labs_3b14b10ac95fec88ae7f756a57c920ef688e2a02dcf79fc9" \
    -H "Content-Type: application/json" \
    -d '{
      "original_url": "https://smplabschooljakarta.sch.id/pengumuman",
      "custom_slug": "info-baru",
      "title": "Info Terkini Labschool"
    }'
  ```
- **Response Success (`201 Created`)**:
  ```json
  {
    "status": "success",
    "message": "Tautan pendek berhasil dibuat",
    "data": {
      "short_url": "https://s.labschool.xyz/info-baru",
      "slug": "info-baru",
      "original_url": "https://smplabschooljakarta.sch.id/pengumuman",
      "qr_code_url": "https://s.labschool.xyz/api/qr.php?url=https%3A%2F%2Fs.labschool.xyz%2Finfo-baru"
    }
  }
  ```

---

### Endpoint 2: Bulk Shorten Links

Membuat banyak tautan pendek sekaligus dalam satu request.

- **Method**: `POST`
- **URL**: `https://s.labschool.xyz/api/bulk_shorten.php`
- **Request Body (JSON)**:
  ```json
  {
    "links": [
      { "url": "https://labschool.sch.id/agenda", "custom_slug": "agenda" },
      { "url": "https://psb.labschool.sch.id", "custom_slug": "psb" }
    ]
  }
  ```

---

### Endpoint 3: Regenerate API Key

Membuat ulang API Key akun jika token sebelumnya terindikasi kompromi.

- **Method**: `POST`
- **URL**: `https://s.labschool.xyz/api/user/api_key.php`

---

## 🔒 Keamanan & Proteksi Sistem

1. **Prepared Statements**: Seluruh kueri basis data menggunakan PDO parameterized queries untuk memutus risiko SQL Injection.
2. **XSS Sanitization**: Setiap data input pengguna disaring menggunakan `htmlspecialchars()` dengan flag `ENT_QUOTES | ENT_SUBSTITUTE`.
3. **Deep Link Whitelist**: Validasi skema URL tujuan untuk mencegah eksploitasi protokol berbahaya seperti `javascript:`, `data:`, atau `file:`.
4. **Session Hardening**: Sesi login dilengkapi cookie flag `HttpOnly`, `SameSite=Lax`, dan `Secure`.

---

## 📊 Operasional & Pemeliharaan Server

Untuk melakukan sinkronisasi pembaruan kode dari GitHub ke server produksi:

```bash
# Masuk ke server produksi srv-lmslabs-smpjkt
ssh antigravity@10.255.0.188

# Tarik perubahan terbaru dari GitHub
cd /var/www/s.labschool.xyz
git pull origin main

# Reload service PHP-FPM untuk membersihkan opcache
sudo systemctl reload php-fpm
```

---

## 👥 Lisensi & Hak Cipta

Platform ini dikembangkan dan dikelola oleh **Labschool IT Infrastructure Team & Antigravity Autonomous Systems** untuk seluruh civitas akademika Labschool.

© 2026 **Yayasan Pembina Universitas Negeri Jakarta (UNJ) — Labschool**. Seluruh hak cipta dilindungi undang-undang.
