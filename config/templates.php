<?php
/**
 * Master Template Presets Collection: s.labschool.xyz
 * Kategori Standar Bitly & Enterprise:
 * 1. Link-in-bio
 * 2. Product and services
 * 3. Digital business card
 * 4. Promotions
 */

$templatePresets = [
    // =============================================================
    // KATEGORI 1: LINK-IN-BIO (5 TEMPLATES)
    // =============================================================
    'link-in-bio' => [
        'name'           => 'Official Labs Portal',
        'category'       => 'Link-in-bio',
        'template_style' => 'genially-showcase',
        'theme'          => 'labs-navy',
        'title'          => 'SMP Labschool Jakarta',
        'bio'            => 'Pusat Tautan Informasi, Akademik & Layanan Komunikasi Resmi SMP Labschool Jakarta.',
        'countdown_title'=> '',
        'countdown_date' => '',
        'blocks'         => [
            [
                'block_type' => 'link',
                'title'      => 'Portal Resmi SMP Labschool Jakarta',
                'subtitle'   => 'smplabschooljakarta.sch.id',
                'target_url' => 'https://smplabschooljakarta.sch.id',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Sistem Pembelajaran Digital (LMS Labs)',
                'subtitle'   => 'study.labschool.xyz',
                'target_url' => 'https://study.labschool.xyz',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Penerimaan Siswa Baru (PSB Online)',
                'subtitle'   => 'psb.labschool.xyz',
                'target_url' => 'https://psb.labschool.xyz',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Kanal YouTube Resmi Labschool',
                'subtitle'   => 'Dokumentasi prestasi & kegiatan civitas',
                'target_url' => 'https://youtube.com/@smplabschooljakarta',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Kanal WhatsApp Resmi Sekolah',
                'subtitle'   => 'Pusat siaran berita resmi dan pengumuman',
                'target_url' => 'https://wa.me/6281200000000',
                'animation'  => 'none'
            ]
        ]
    ],

    'link-in-bio-creator' => [
        'name'           => 'Student Creator & Podcast Media',
        'category'       => 'Link-in-bio',
        'template_style' => 'genially-showcase',
        'theme'          => 'genially-cosmic',
        'title'          => 'Labs Pods & Media Kreasi Siswa',
        'bio'            => 'Kanal resmi podcast suara siswa, buletin literasi, dan konten multimedia karya pelajar Labschool.',
        'countdown_title'=> '',
        'countdown_date' => '',
        'blocks'         => [
            [
                'block_type' => 'link',
                'title'      => 'Dengarkan Podcast Terbaru di Spotify',
                'subtitle'   => 'Episode 12: Eksplorasi Sains & Teknologi AI',
                'target_url' => 'https://open.spotify.com',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'youtube',
                'title'      => 'Video Liputan Pentas Seni & Kreativitas',
                'subtitle'   => '',
                'target_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'pdf',
                'title'      => 'Unduh Buletin Literasi Digital (Edisi 4)',
                'subtitle'   => 'Buletin-Labs-Siswa-Vol4.pdf',
                'target_url' => 'https://s.labschool.xyz/buletin.pdf',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Instagram Resmi Komunitas Kreator',
                'subtitle'   => '@labschool.creators',
                'target_url' => 'https://instagram.com',
                'animation'  => 'none'
            ]
        ]
    ],

    'link-in-bio-club' => [
        'name'           => 'OSIS & Ekstrakurikuler Hub',
        'category'       => 'Link-in-bio',
        'template_style' => 'genially-roadmap',
        'theme'          => 'emerald',
        'title'          => 'OSIS & Ekstrakurikuler SMP Labschool',
        'bio'            => 'Gerbang utama kegiatan kesiswaan, jadwal latihan mingguan, dan rekrutmen anggota kepengurusan.',
        'countdown_title'=> '',
        'countdown_date' => '',
        'blocks'         => [
            [
                'block_type' => 'text',
                'title'      => 'Visi & Misi Kepengurusan OSIS',
                'subtitle'   => "Mewujudkan siswa Labschool yang berkarakter unggul, inovatif, peduli lingkungan, dan berwawasan global.",
                'target_url' => '',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Formulir Pendaftaran Ekstrakurikuler',
                'subtitle'   => 'Pilih Robotik, Basket, Pramuka, Paduan Suara, dll.',
                'target_url' => 'https://s.labschool.xyz/daftar-ekskul',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'pdf',
                'title'      => 'Unduh Jadwal Latihan Ekskul (PDF)',
                'subtitle'   => 'Jadwal-Ekskul-Semester-Ganjil.pdf',
                'target_url' => 'https://s.labschool.xyz/jadwal-ekskul.pdf',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'faq',
                'title'      => 'Pertanyaan Umum Pendaftaran Ekskul',
                'subtitle'   => 'Setiap siswa wajib memilih minimal 1 ekskul pilihan di samping ekskul wajib Pramuka.',
                'target_url' => '',
                'animation'  => 'none'
            ]
        ]
    ],

    'link-in-bio-alumni' => [
        'name'           => 'Alumni & Community Network',
        'category'       => 'Link-in-bio',
        'template_style' => 'genially-cards',
        'theme'          => 'labs-gold',
        'title'          => 'Ikatan Alumni Labschool (IKA Labs)',
        'bio'            => 'Mempererat tali silaturahmi, jejaring karier, dan kontribusi beasiswa pendidikan lintas angkatan.',
        'countdown_title'=> '',
        'countdown_date' => '',
        'blocks'         => [
            [
                'block_type' => 'link',
                'title'      => 'Direktori & Database Alumni',
                'subtitle'   => 'Perbarui profil dan tempat pengabdian Anda',
                'target_url' => 'https://s.labschool.xyz/alumni-db',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Program Donasi Beasiswa Siswa Labschool',
                'subtitle'   => 'Dukungan pendidikan untuk adik kelas berprestasi',
                'target_url' => 'https://s.labschool.xyz/beasiswa-alumni',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Grup Profesional LinkedIn Alumni',
                'subtitle'   => 'Jejaring kerja & lowongan magang sesama alumni',
                'target_url' => 'https://linkedin.com',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'faq',
                'title'      => 'Info Temu Kangen & Reuni Akbar 2026',
                'subtitle'   => 'Reuni akbar direncanakan berlangsung di akhir tahun. Registrasi presale dibuka mulai bulan depan.',
                'target_url' => '',
                'animation'  => 'none'
            ]
        ]
    ],

    'link-in-bio-web-design' => [
        'name'           => 'Modern Web Design Studio',
        'category'       => 'Link-in-bio',
        'template_style' => 'genially-showcase',
        'theme'          => 'web-design-studio',
        'thumbnail'      => '/assets/img/templates/web-design-template.jpg',
        'title'          => 'Labs Digital Studio & Web Agency',
        'bio'            => 'Crafting clean, responsive websites, creative school landing pages, and interactive digital experiences with Catamaran & Fredoka UI.',
        'countdown_title'=> '',
        'countdown_date' => '',
        'blocks'         => [
            [
                'block_type' => 'link',
                'title'      => 'Lihat Portofolio Web & Interactive Demos',
                'subtitle'   => 'Koleksi web apps, dynamic landing pages & UI/UX design showcase',
                'target_url' => 'https://s.labschool.xyz/portfolio-web',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'text',
                'title'      => 'Layanan Desain & Pengembangan Web',
                'subtitle'   => "Desain responsif mobile-first, integrasi CMS modern, optimasi kecepatan SEO, dan animasi mikro interaktif untuk ekosistem sekolah.",
                'target_url' => '',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Konsultasikan Proyek Website Sekolah',
                'subtitle'   => 'Diskusi kebutuhan antarmuka, arsitektur sistem & estimasi pengerjaan',
                'target_url' => 'https://wa.me/6281200000000',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'pdf',
                'title'      => 'Unduh Proposal & Standar Spesifikasi Web (PDF)',
                'subtitle'   => 'Panduan-Desain-Web-Labschool-2026.pdf',
                'target_url' => 'https://s.labschool.xyz/panduan-web.pdf',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'faq',
                'title'      => 'Proses Kerja & Timeline Studio Web',
                'subtitle'   => 'Mulai dari riset civitas, wireframing Figma, coding frontend responsif, hingga deployment performa tinggi di server sekolah.',
                'target_url' => '',
                'animation'  => 'none'
            ]
        ]
    ],

    // =============================================================
    // KATEGORI 2: PRODUCT AND SERVICES (5 TEMPLATES)
    // =============================================================
    'product-services-agency' => [
        'name'           => 'Awesome UI/UX & Creative Agency',
        'category'       => 'Product and services',
        'template_style' => 'genially-showcase',
        'theme'          => 'creative-agency',
        'thumbnail'      => '/assets/img/templates/laptop-landing-page.jpg',
        'title'          => 'NextGen UI/UX & Digital Creative Lab',
        'bio'            => 'Awesome and stunning design solutions for educational platforms, interactive portfolios, and digital branding with Nexa & Roboto.',
        'countdown_title'=> '',
        'countdown_date' => '',
        'blocks'         => [
            [
                'block_type' => 'text',
                'title'      => 'Solusi Desain Antarmuka & Produk Digital',
                'subtitle'   => "Desain sistem modern dengan tipografi Nexa & Roboto, tata letak dinamis, mockup laptop resolusi tinggi, dan pengalaman pengguna tingkat lanjut.",
                'target_url' => '',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Jelajahi Mockup Laptop & Tampilan Aplikasi',
                'subtitle'   => 'Pratinjau antarmuka interaktif kelas dunia dan prototipe langsung',
                'target_url' => 'https://s.labschool.xyz/mockup-agency',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'pdf',
                'title'      => 'Unduh Portofolio Karya & Pricelist Layanan (PDF)',
                'subtitle'   => 'Katalog-Layanan-Desain-Creative-2026.pdf',
                'target_url' => 'https://s.labschool.xyz/katalog-layanan.pdf',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Jadwalkan Sesi Discovery Call Gratis (30 Menit)',
                'subtitle'   => 'Konsultasi gratis mengenai strategi visual dan produk digital Anda',
                'target_url' => 'https://s.labschool.xyz/jadwal-call',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'faq',
                'title'      => 'Kemitraan, Garansi & Lisensi Aset Desain',
                'subtitle'   => 'Semua karya dilengkapi hak komersial penuh, berkas master sumber (Figma/AI/EPS), panduan token warna, dan garansi revisi terstruktur.',
                'target_url' => '',
                'animation'  => 'none'
            ]
        ]
    ],

    'product-services-koperasi' => [
        'name'           => 'Koperasi & Official Merchandise',
        'category'       => 'Product and services',
        'template_style' => 'genially-cards',
        'theme'          => 'slate',
        'title'          => 'Koperasi Siswa & Toko Resmi Labschool',
        'bio'            => 'Penyedia resmi seragam sekolah, batik Labschool, buku kurikulum, alat tulis, dan atribut lengkap.',
        'countdown_title'=> '',
        'countdown_date' => '',
        'blocks'         => [
            [
                'block_type' => 'pdf',
                'title'      => 'Katalog Seragam Resmi & Panduan Ukuran',
                'subtitle'   => 'Brosur-Ukuran-Seragam-Labs.pdf',
                'target_url' => 'https://s.labschool.xyz/katalog-seragam.pdf',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Paket Buku Pelajaran Kurikulum Merdeka',
                'subtitle'   => 'Buku teks wajib & pengayaan SMP Kelas 7, 8, 9',
                'target_url' => 'https://s.labschool.xyz/buku-pelajaran',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Merchandise & Jaket Hoodie Angkatan',
                'subtitle'   => 'Kaos eksklusif, pin logam, topi, dan jaket almamater',
                'target_url' => 'https://s.labschool.xyz/merch-labs',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Pemesanan Cepat via WhatsApp Koperasi',
                'subtitle'   => 'Layanan antar atau ambil di gerai koperasi sekolah',
                'target_url' => 'https://wa.me/6281200000000',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'faq',
                'title'      => 'Syarat Penukaran Ukuran & Jam Buka Gerai',
                'subtitle'   => 'Buka Senin - Jumat pukul 07.30 - 15.00 WIB. Penukaran ukuran seragam maksimal 7 hari kerja setelah pembelian.',
                'target_url' => '',
                'animation'  => 'none'
            ]
        ]
    ],

    'product-services-course' => [
        'name'           => 'Bimbel & Program Persiapan Ujian',
        'category'       => 'Product and services',
        'template_style' => 'genially-roadmap',
        'theme'          => 'emerald',
        'title'          => 'Labs Academic Tutoring & Prep Center',
        'bio'            => 'Bimbingan intensif olimpiade sains, persiapan asesmen akademik nasional, dan kursus pengayaan siswa.',
        'countdown_title'=> '',
        'countdown_date' => '',
        'blocks'         => [
            [
                'block_type' => 'text',
                'title'      => 'Program Unggulan Semester Ini',
                'subtitle'   => "1. Pembinaan Olimpiade OSN (Matematika & IPA)\n2. Kursus Coding & Algoritma Robotik\n3. Klinik Matematika & Pengayaan Bahasa Inggris.",
                'target_url' => '',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'pdf',
                'title'      => 'Unduh Jadwal & Silabus Bimbingan (PDF)',
                'subtitle'   => 'Silabus-Bimbel-Labs-2026.pdf',
                'target_url' => 'https://s.labschool.xyz/silabus-bimbel.pdf',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Formulir Registrasi Siswa Bimbel',
                'subtitle'   => 'Pilih jadwal sore atau akhir pekan',
                'target_url' => 'https://s.labschool.xyz/daftar-bimbel',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'faq',
                'title'      => 'Metode Pembelajaran & Rasio Pengajar',
                'subtitle'   => 'Setiap kelas dibatasi maksimal 15 siswa untuk memastikan pemahaman personal dan evaluasi berkala.',
                'target_url' => '',
                'animation'  => 'none'
            ]
        ]
    ],

    'product-services-canteen' => [
        'name'           => 'Kantin Sehat & Catering Sekolah',
        'category'       => 'Product and services',
        'template_style' => 'genially-cards',
        'theme'          => 'labs-gold',
        'title'          => 'Labs Healthy Canteen & Catering',
        'bio'            => 'Pilihan hidangan bergizi seimbang, higienis, halal, dan disiapkan khusus untuk mendukung stamina belajar civitas.',
        'countdown_title'=> '',
        'countdown_date' => '',
        'blocks'         => [
            [
                'block_type' => 'text',
                'title'      => 'Menu Makanan Sehat Harian Minggu Ini',
                'subtitle'   => "Senin: Nasi Timbel Ayam Bakar & Sayur Asem\nSelasa: Spaghetti Bolognese Sayur & Buah Segar\nRabu: Bento Ikan Salmon Teriyaki & Sup Jamur.",
                'target_url' => '',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Paket Berlangganan Makan Siang Bulanan',
                'subtitle'   => 'Hemat 20% & makanan diantar tepat waktu istirahat',
                'target_url' => 'https://s.labschool.xyz/langganan-catering',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Pemesanan Cepat WhatsApp Kantin',
                'subtitle'   => 'Chat admin untuk pesanan khusus guru & rapat',
                'target_url' => 'https://wa.me/6281200000000',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'faq',
                'title'      => 'Standar Kebersihan & Sertifikasi Higienis',
                'subtitle'   => 'Seluruh pengolah makanan melewati uji higienitas berkala dan menggunakan bahan-bahan organik segar tanpa MSG berlebih.',
                'target_url' => '',
                'animation'  => 'none'
            ]
        ]
    ],

    'product-services-facilities' => [
        'name'           => 'Penyewaan Fasilitas & Lab Hall',
        'category'       => 'Product and services',
        'template_style' => 'genially-roadmap',
        'theme'          => 'labs-navy',
        'title'          => 'Fasilitas & Ruang Serbaguna Labschool',
        'bio'            => 'Layanan peminjaman Auditorium, Laboratorium Komputer CBT, Lapangan Olahraga, dan Ruang Pertemuan.',
        'countdown_title'=> '',
        'countdown_date' => '',
        'blocks'         => [
            [
                'block_type' => 'text',
                'title'      => 'Daftar Sarana yang Dapat Digunakan',
                'subtitle'   => "1. Hall Pertemuan Utama (Kapasitas 800 orang)\n2. Laboratorium CBT (120 unit PC berkecepatan tinggi)\n3. Gelanggang Olahraga & Lapangan Basket Indoor.",
                'target_url' => '',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'pdf',
                'title'      => 'Unduh Prosedur & Formulir Permohonan (PDF)',
                'subtitle'   => 'SOP-Peminjaman-Fasilitas.pdf',
                'target_url' => 'https://s.labschool.xyz/sop-fasilitas.pdf',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Cek Kalender Ketersediaan Jadwal',
                'subtitle'   => 'Pastikan tanggal acara Anda belum terisi',
                'target_url' => 'https://s.labschool.xyz/kalender-gedung',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'faq',
                'title'      => 'Tata Tertib & Ketentuan Penggunaan',
                'subtitle'   => 'Pengajuan permohonan minimal diajukan 14 hari sebelum hari pelaksanaan acara. Dilengkapi dukungan teknis audio visual.',
                'target_url' => '',
                'animation'  => 'none'
            ]
        ]
    ],

    // =============================================================
    // KATEGORI 3: DIGITAL BUSINESS CARD (5 TEMPLATES)
    // =============================================================
    'digital-card-designer' => [
        'name'           => 'Hand-Drawn Graphic Designer & Artist',
        'category'       => 'Digital business card',
        'template_style' => 'genially-cards',
        'theme'          => 'artist-doodle',
        'thumbnail'      => '/assets/img/templates/graphic-designer-landing-page.jpg',
        'title'          => 'Creative Graphic Designer & Illustrator',
        'bio'            => 'Hand-drawn illustrations, playful character design, and creative educational media with Poppins & Gasoek One fonts.',
        'countdown_title'=> '',
        'countdown_date' => '',
        'blocks'         => [
            [
                'block_type' => 'vcard',
                'title'      => 'Simpan Kontak Desainer (vCard)',
                'subtitle'   => 'Satu klik untuk simpan nomor WA, surel & portofolio',
                'target_url' => '/vcard.php',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Portofolio Desain di Behance & Dribbble',
                'subtitle'   => 'Koleksi ilustrasi vektor, maskot, & sampul buku',
                'target_url' => 'https://behance.net',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'pdf',
                'title'      => 'Unduh CV & Katalog Ilustrasi (PDF)',
                'subtitle'   => 'Portfolio-Ilustrator-Kreatif-2026.pdf',
                'target_url' => 'https://s.labschool.xyz/cv-designer.pdf',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Chat WhatsApp untuk Kolaborasi Seni',
                'subtitle'   => 'Diskusikan proyek ilustrasi komik & maskot',
                'target_url' => 'https://wa.me/6281200000000',
                'animation'  => 'none'
            ]
        ]
    ],

    'digital-card' => [
        'name'           => 'Pendidik & Guru Mata Pelajaran',
        'category'       => 'Digital business card',
        'template_style' => 'genially-showcase',
        'theme'          => 'labs-navy',
        'title'          => 'Profil Pendidik & Tenaga Pengajar',
        'bio'            => 'Guru Matematika & Fasilitator Teknologi Pembelajaran • SMP Labschool Jakarta.',
        'countdown_title'=> '',
        'countdown_date' => '',
        'blocks'         => [
            [
                'block_type' => 'vcard',
                'title'      => 'Simpan Kontak Resmi Guru ke HP (vCard)',
                'subtitle'   => 'Simpan nomor kantor, surel resmi, & jam kerja',
                'target_url' => '/vcard.php',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Chat WhatsApp Konsultasi Siswa & Wali Murid',
                'subtitle'   => 'Tersedia pada hari kerja pukul 13.00 - 15.00 WIB',
                'target_url' => 'https://wa.me/6281200000000',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'text',
                'title'      => 'Jadwal Mengajar & Jam Bimbingan Belajar',
                'subtitle'   => "Kelas 7A - 7C: Senin & Rabu\nKelas 8A - 8B: Selasa & Kamis\nKlinik Konsultasi: Jumat Pagi.",
                'target_url' => '',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Bahan Ajar & Bank Soal Matematika',
                'subtitle'   => 'Akses rangkuman rumus, video materi, & latihan',
                'target_url' => 'https://s.labschool.xyz/materi-matematika',
                'animation'  => 'none'
            ]
        ]
    ],

    'digital-card-principal' => [
        'name'           => 'Kepala Sekolah & Manajemen Pimpinan',
        'category'       => 'Digital business card',
        'template_style' => 'genially-cards',
        'theme'          => 'labs-navy',
        'title'          => 'Pimpinan & Manajemen Sekolah',
        'bio'            => 'Kepala SMP Labschool Jakarta • Pembina Mutu dan Inovasi Pendidikan Berkelanjutan.',
        'countdown_title'=> '',
        'countdown_date' => '',
        'blocks'         => [
            [
                'block_type' => 'vcard',
                'title'      => 'Simpan Kontak Sekretariat Pimpinan (vCard)',
                'subtitle'   => 'Buku telepon resmi pimpinan & humas sekolah',
                'target_url' => '/vcard.php',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'text',
                'title'      => 'Pesan Sambutan Kepala Sekolah',
                'subtitle'   => "Selamat datang di ekosistem pendidikan Labschool. Bersama kita wujudkan generasi cerdas, mandiri, dan berkarakter luhur.",
                'target_url' => '',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'pdf',
                'title'      => 'Unduh Buku Profil Sekolah & Capaian (PDF)',
                'subtitle'   => 'Profil-SMP-Labschool-2026.pdf',
                'target_url' => 'https://s.labschool.xyz/profil-sekolah.pdf',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Permohonan Audiensi & Jadwal Pertemuan',
                'subtitle'   => 'Dikoordinasikan melalui sekretaris pimpinan',
                'target_url' => 'https://s.labschool.xyz/audiensi-pimpinan',
                'animation'  => 'none'
            ]
        ]
    ],

    'digital-card-counselor' => [
        'name'           => 'Konselor BK & Psikolog Sekolah',
        'category'       => 'Digital business card',
        'template_style' => 'genially-showcase',
        'theme'          => 'emerald',
        'title'          => 'Layanan Bimbingan & Konseling (BK)',
        'bio'            => 'Konselor Ramah Anak, Pemantauan Minat Bakat, dan Pendampingan Psikologis Siswa.',
        'countdown_title'=> '',
        'countdown_date' => '',
        'blocks'         => [
            [
                'block_type' => 'vcard',
                'title'      => 'Simpan Kontak Ruang Konseling BK (vCard)',
                'subtitle'   => 'Akses cepat nomor darurat & jadwal temu konseling',
                'target_url' => '/vcard.php',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Booking Sesi Konseling Privat (Rahasia)',
                'subtitle'   => 'Konseling daring atau tatap muka di ruang BK',
                'target_url' => 'https://s.labschool.xyz/konseling-privat',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Kuesioner Minat, Bakat, & Gaya Belajar Siswa',
                'subtitle'   => 'Membantu siswa menemukan potensi diri terbaik',
                'target_url' => 'https://s.labschool.xyz/angket-minat-bakat',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'faq',
                'title'      => 'Etika & Jaminan Kerahasiaan Konseling',
                'subtitle'   => 'Semua cerita dan sesi bimbingan bersifat rahasia dan dilindungi kode etik profesi konseling.',
                'target_url' => '',
                'animation'  => 'none'
            ]
        ]
    ],

    'digital-card-doctor' => [
        'name'           => 'Unit Kesehatan Sekolah (Dokter & UKS)',
        'category'       => 'Digital business card',
        'template_style' => 'genially-cards',
        'theme'          => 'slate',
        'title'          => 'Petugas Medis & Ruang UKS Labschool',
        'bio'            => 'Layanan pertolongan pertama, pemantauan kesehatan siswa, dan edukasi sanitasi lingkungan sekolah.',
        'countdown_title'=> '',
        'countdown_date' => '',
        'blocks'         => [
            [
                'block_type' => 'vcard',
                'title'      => 'Simpan Kontak Cepat Ruang UKS (vCard)',
                'subtitle'   => 'Nomor darurat medis di lingkungan sekolah',
                'target_url' => '/vcard.php',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Hotline Panggilan Darurat Medis Sekolah',
                'subtitle'   => 'Petugas siaga saat jam belajar & kegiatan lapangan',
                'target_url' => 'https://wa.me/6281200000000',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'text',
                'title'      => 'SOP Siswa Sakit & Rujukan RS Mitra',
                'subtitle'   => "Siswa yang sakit di sekolah akan dirawat di UKS. Jika memerlukan rujukan, sekolah segera menghubungi orang tua dan RS mitra terdekat.",
                'target_url' => '',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Formulir Riwayat Alergi & Obat Rutin Siswa',
                'subtitle'   => 'Data penting untuk arsip ruang UKS',
                'target_url' => 'https://s.labschool.xyz/data-alergi-siswa',
                'animation'  => 'none'
            ]
        ]
    ],

    // =============================================================
    // KATEGORI 4: PROMOTIONS (4 TEMPLATES)
    // =============================================================
    'promotions-open-house' => [
        'name'           => 'Labs Open House & Edufair 2026',
        'category'       => 'Promotions',
        'template_style' => 'genially-roadmap',
        'theme'          => 'genially-cosmic',
        'title'          => 'Labs Open House & Edufair 2026',
        'bio'            => 'Saksikan pameran riset sains siswa, bazaar kuliner, seminar pendidikan masa depan, dan konsultasi PPDB.',
        'countdown_title'=> 'Menuju Labs Open House 2026',
        'countdown_date' => date('Y-m-d\TH:i', strtotime('+14 days 08:00')),
        'blocks'         => [
            [
                'block_type' => 'text',
                'title'      => 'Pengumuman Jadwal & Lokasi Acara',
                'subtitle'   => "Sabtu & Minggu, 14-15 November 2026\nPukul 08.00 - 15.30 WIB di Hall Utama SMP Labschool Jakarta.",
                'target_url' => '',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'youtube',
                'title'      => 'Teaser Video Edufair 2026',
                'subtitle'   => '',
                'target_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Formulir Registrasi Pengunjung (Gratis)',
                'subtitle'   => 'Dapatkan tiket masuk dan goodie bag di pintu masuk',
                'target_url' => 'https://s.labschool.xyz/daftar-edufair',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'pdf',
                'title'      => 'Unduh E-Brosur & Rundown Acara (PDF)',
                'subtitle'   => 'Brosur-Edufair-Labs-2026.pdf',
                'target_url' => 'https://s.labschool.xyz/brosur.pdf',
                'animation'  => 'none'
            ]
        ]
    ],

    'promotions-early-bird' => [
        'name'           => 'Promo Early Bird PSB 2027/2028',
        'category'       => 'Promotions',
        'template_style' => 'genially-showcase',
        'theme'          => 'labs-gold',
        'title'          => 'Periode Khusus Early Bird PSB',
        'bio'            => 'Potongan biaya formulir pendaftaran 25% dan bonus materi simulasi CBT untuk 100 calon siswa pertama.',
        'countdown_title'=> 'Batas Waktu Periode Promo Early Bird',
        'countdown_date' => date('Y-m-d\TH:i', strtotime('+7 days 23:59')),
        'blocks'         => [
            [
                'block_type' => 'link',
                'title'      => 'Klaim Diskon Formulir Pendaftaran Sekarang',
                'subtitle'   => 'Gunakan kode voucher: LABSEARLY2027',
                'target_url' => 'https://psb.labschool.xyz',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'text',
                'title'      => 'Syarat & Ketentuan Kuota Promo',
                'subtitle'   => "Promo hanya berlaku untuk pendaftaran jalur reguler dan tes kemampuan akademik selama kuota 100 siswa masih tersedia.",
                'target_url' => '',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'pdf',
                'title'      => 'Unduh Panduan Pendaftaran Siswa Baru (PDF)',
                'subtitle'   => 'Panduan-Pendaftaran-PSB-Labs.pdf',
                'target_url' => 'https://s.labschool.xyz/panduan-psb.pdf',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'faq',
                'title'      => 'Jadwal Tes Seleksi & Verifikasi Berkas',
                'subtitle'   => 'Tes seleksi CBT dilaksanakan pada hari Sabtu pekan ketiga setelah pendaftaran online diverifikasi.',
                'target_url' => '',
                'animation'  => 'none'
            ]
        ]
    ],

    'promotions-bazaar' => [
        'name'           => 'Pentas Seni & Labs Bazaar Festival',
        'category'       => 'Promotions',
        'template_style' => 'genially-cards',
        'theme'          => 'genially-cosmic',
        'title'          => 'Labs Music Fest & Creative Bazaar',
        'bio'            => 'Festival musik tahunan siswa Labschool menghadirkan panggung pentas seni, booth UMKM kuliner, dan guest star nasional.',
        'countdown_title'=> 'Hitung Mundur Gerbang Festival Dibuka',
        'countdown_date' => date('Y-m-d\TH:i', strtotime('+21 days 10:00')),
        'blocks'         => [
            [
                'block_type' => 'link',
                'title'      => 'Beli Tiket Presale Konser Musik',
                'subtitle'   => 'Tiket Presale 1 hemat 30% via online payment',
                'target_url' => 'https://s.labschool.xyz/tiket-pensi',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'text',
                'title'      => 'Deretan Bintang Tamu (Lineup Performers)',
                'subtitle'   => "Penampilan spesial band indie terkemuka, tarian tradisional kolosal siswa, dan orchestra sekolah.",
                'target_url' => '',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Pendaftaran Tenant / Stand Makanan Bazaar',
                'subtitle'   => 'Tersedia 30 booth kuliner & produk kreatif',
                'target_url' => 'https://s.labschool.xyz/daftar-tenant',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'youtube',
                'title'      => 'Aftermovie Dokumentasi Pensi Tahun Lalu',
                'subtitle'   => '',
                'target_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'animation'  => 'none'
            ]
        ]
    ],

    'promotions-scholarship' => [
        'name'           => 'Program Beasiswa Bakat & Prestasi',
        'category'       => 'Promotions',
        'template_style' => 'genially-roadmap',
        'theme'          => 'emerald',
        'title'          => 'Beasiswa Prestasi Bintang Labschool',
        'bio'            => 'Beasiswa pembebasan biaya SPP bagi siswa berprestasi di tingkat kota, provinsi, dan nasional di bidang sains, seni, & olahraga.',
        'countdown_title'=> 'Penutupan Pengumpulan Berkas Beasiswa',
        'countdown_date' => date('Y-m-d\TH:i', strtotime('+30 days 17:00')),
        'blocks'         => [
            [
                'block_type' => 'text',
                'title'      => 'Kategori Jalur Beasiswa yang Tersedia',
                'subtitle'   => "1. Beasiswa Prestasi Sains & Riset (Juara OSN/KIR)\n2. Beasiswa Atlet Olahraga Berprestasi\n3. Beasiswa Seni Budaya & Musisi Muda.",
                'target_url' => '',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'pdf',
                'title'      => 'Unduh Syarat & Formulir Pengajuan (PDF)',
                'subtitle'   => 'Formulir-Beasiswa-Labs-2026.pdf',
                'target_url' => 'https://s.labschool.xyz/form-beasiswa.pdf',
                'animation'  => 'pulse'
            ],
            [
                'block_type' => 'link',
                'title'      => 'Kirim Berkas Pendaftaran Beasiswa Online',
                'subtitle'   => 'Unggah bukti piagam penghargaan dan portofolio',
                'target_url' => 'https://s.labschool.xyz/upload-beasiswa',
                'animation'  => 'none'
            ],
            [
                'block_type' => 'faq',
                'title'      => 'Tahapan Seleksi Wawancara & Pengumuman',
                'subtitle'   => 'Seleksi berkas berlangsung 7 hari dilanjutkan sesi wawancara bakat bersama komite penilai beasiswa.',
                'target_url' => '',
                'animation'  => 'none'
            ]
        ]
    ],

    // =============================================================
    // KANVAS KOSONG (BLANK PAGE)
    // =============================================================
    'blank' => [
        'name'           => 'Start from scratch (Kanvas Kosong)',
        'category'       => 'All templates',
        'template_style' => 'genially-showcase',
        'theme'          => 'labs-navy',
        'title'          => '',
        'bio'            => '',
        'countdown_title'=> '',
        'countdown_date' => '',
        'blocks'         => [
            [
                'block_type' => 'link',
                'title'      => 'Tautan Pertama Anda',
                'subtitle'   => 'Klik ikon edit di samping untuk mengubah teks & tautan',
                'target_url' => 'https://labschool.sch.id',
                'animation'  => 'none'
            ]
        ]
    ]
];
