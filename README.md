<div align="center">

# 📚 Personal Resource Hub

**Aplikasi web manajemen koleksi sumber daya digital** — simpan, kelola, dan organisir link dari YouTube, Spotify, Instagram, artikel, dan lainnya dalam satu tempat.

<img src="https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=flat&logo=php&logoColor=white" alt="PHP Version" />
<img src="https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=flat&logo=mysql&logoColor=white" alt="MySQL" />
<img src="https://img.shields.io/badge/build-passing-brightgreen?style=flat" alt="Build Status" />
<img src="https://img.shields.io/badge/version-1.0.0-blue?style=flat" alt="Version" />
<img src="https://img.shields.io/badge/license-MIT-yellow?style=flat" alt="License" />

**[🔗 Live Demo](https://personalhub-feri.freepage.cc)**

</div>

---

Dibangun dengan **PHP Native** dan **MySQL**, proyek ini adalah portofolio full-stack pertama yang saya buat dari nol hingga deployment.

---

## 📑 Daftar Isi

- [🎯 Kenapa Proyek Ini Dibuat?](#-kenapa-proyek-ini-dibuat)
- [✨ Fitur Utama](#-fitur-utama)
- [🧰 Teknologi](#-teknologi)
- [🗃️ Struktur Database](#️-struktur-database)
- [🚀 Cara Install di Local](#-cara-install-di-local)
- [📸 Screenshot](#-screenshot)
- [📅 Timeline](#-timeline)
- [🧠 Apa yang Saya Pelajari](#-apa-yang-saya-pelajari)
- [👨‍💻 Pengembang](#-pengembang)
- [📄 Lisensi](#-lisensi)

---

## 🎯 Kenapa Proyek Ini Dibuat?

Saya sering menyimpan link dari berbagai platform (YouTube, Spotify, Instagram, artikel) tapi bookmark browser berantakan. Saya butuh alat untuk mengelola semua link itu di satu tempat — jadi saya buat sendiri.

> 💡 Proyek ini bukan hanya untuk portofolio, tapi saya gunakan **setiap hari** untuk mengorganisir resource digital saya.

---

## ✨ Fitur Utama

### 🔐 Autentikasi

- Register & Login (dengan CSRF Protection)
- Verifikasi Email via SMTP (Gmail)
- Lupa Password & Reset Password
- Whitelist User (hanya admin yang bisa mengizinkan login)
- Halaman Pending untuk user yang menunggu aktivasi

### 📂 Manajemen Resource

- CRUD Resource (Tambah, Lihat, Edit, Hapus)
- Tagging (Many-to-Many) — Tambah, Edit, Hapus Tag dari Resource
- Rating (1–10)
- Fitur Favorit / Pin
- Filter & Pencarian (berdasarkan jenis, status, tag, dan kata kunci)

### 📊 Dashboard & Statistik

- Kartu Statistik (Total, Belum Dibaca, Sedang Dipelajari, Selesai, Arsip)
- 5 Resource Terbaru
- Statistik Akun (Total Resource, Favorit, Selesai, Total Tag)
- Log Aktivitas (riwayat aksi user)

### 📥 Ekspor Data

- Ekspor semua resource ke CSV

### 🌙 Tampilan

- Dark / Light Mode (tersimpan di `localStorage`)
- Responsif Mobile (sidebar collapse, tombol full width di HP)
- Tooltip Info untuk penjelasan fitur

### 🛡️ Keamanan

- CSRF Token di setiap form
- Prepared Statement (anti SQL Injection)
- Password di-hash dengan Bcrypt
- Whitelist User (hanya user terverifikasi yang bisa login)
- Proteksi file sensitif via `.htaccess`
- Validasi URL (deteksi link mencurigakan)

### 👥 Manajemen User (Admin Only)

- Hanya admin (Feri) yang bisa mengakses
- Izinkan / Blokir user lain
- User yang diblokir akan diarahkan ke halaman pending

---

## 🧰 Teknologi

| Lapisan | Teknologi |
|---|---|
| 🖥️ **Backend** | PHP 8 (Native) |
| 🗄️ **Database** | MySQL (MariaDB) |
| 🎨 **Frontend** | HTML5, CSS3, JavaScript (Vanilla) |
| 📧 **Email** | PHPMailer + Gmail SMTP |
| ☁️ **Hosting** | InfinityFree |
| 🔧 **Version Control** | Git & GitHub |

---

## 🗃️ Struktur Database

| Tabel | Deskripsi |
|---|---|
| `users` | Data user (username, email, password, verifikasi, reset token, whitelist) |
| `resources` | Data resource (judul, URL, deskripsi, jenis, status, rating, dll) |
| `tags` | Daftar tag |
| `resource_tag` | Relasi many-to-many antara resource dan tag |
| `user_logs` | Log aktivitas user (login, logout, tambah, edit, hapus, pin, dll) |

---

## 🚀 Cara Install di Local

### 1️⃣ Clone Repository

```bash
git clone https://github.com/FerrCodes/Personal-Resource-Hub.git
```

### 2️⃣ Import Database

- Buka phpMyAdmin
- Buat database baru (misal: `resource_hub`)
- Import file `resource_hub_fixed.sql`

### 3️⃣ Konfigurasi Database

- Copy `env.example.php` menjadi `env.php`
- Sesuaikan kredensial database:

```php
return [
    'DB_HOST' => 'localhost',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'DB_NAME' => 'resource_hub',
];
```

### 4️⃣ Konfigurasi Email (Opsional)

- Edit `config/mail.php`
- Isi dengan kredensial SMTP (Gmail App Password atau Mailtrap)

### 5️⃣ Jalankan di Browser

```
http://localhost/personal-resource-hub/login.php
```

---

## 📸 Screenshot

<div align="center">

| Dashboard | Detail Resource |
|:---:|:---:|
| `docs/screenshots/dashboard.png` | `docs/screenshots/detail.png` |

| Settings | Aktivitas |
|:---:|:---:|
| `docs/screenshots/settings.png` | `docs/screenshots/activity.png` |

</div>

---

## 📅 Timeline

<div align="center">

| Milestone | Tanggal |
|:---|:---:|
| 🏁 Mulai | 13 Juni 2026 |
| ✅ Selesai | 2 Juli 2026 |
| ⏳ Durasi | ± 3 minggu |

</div>

---

## 🧠 Apa yang Saya Pelajari

- **Backend:** PHP Native, MySQL, relasi database, query kompleks
- **Keamanan:** CSRF, Prepared Statement, Password Hashing, `.htaccess`
- **Frontend:** Responsive Design, Dark/Light Mode, CSS Variables
- **Email:** SMTP, PHPMailer, Verifikasi & Reset Password
- **Hosting:** Deployment, Database Import, Proteksi File Sensitif
- **Debugging:** Error handling, log aktivitas, troubleshooting hosting

---

## 👨‍💻 Pengembang

<div align="center">

**Feri**

🐙 GitHub · 💼 LinkedIn *(jika sudah ada, update linknya)* · 📸 Instagram

</div>

---

## 📄 Lisensi

Didistribusikan di bawah lisensi **MIT**. Bebas digunakan, dimodifikasi, dan didistribusikan dengan mencantumkan kredit ke penulis asli.

---

<div align="center">

### 🙏 Terima Kasih

Terima kasih sudah melihat proyek ini!
Jika ada saran atau masukan, silakan buat *issue* atau *pull request*. 😊

⭐ **Jangan lupa kasih star kalau proyek ini bermanfaat!**

</div>
