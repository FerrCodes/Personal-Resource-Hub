<?php
// Tidak perlu session, karena ini halaman publik
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panduan Aktivasi Akun - Resource Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .guide-container {
            max-width: 720px;
            margin: 40px auto;
            padding: 20px;
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
        }
        .guide-container h1 {
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: var(--text-primary);
        }
        .guide-container .subtitle {
            color: var(--text-muted);
            margin-bottom: 25px;
            font-size: 1rem;
        }
        .guide-container .step {
            margin-bottom: 25px;
            padding: 18px 20px;
            background: var(--bg-soft);
            border-radius: var(--radius-md);
            border-left: 4px solid var(--accent);
        }
        .guide-container .step h3 {
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: var(--text-primary);
        }
        .guide-container .step p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            margin: 0;
        }
        .guide-container .step .icon {
            margin-right: 8px;
        }
        .guide-container .screenshot-box {
            margin: 25px 0;
            padding: 15px;
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-md);
            text-align: center;
            background: var(--bg-soft);
        }
        .guide-container .screenshot-box img {
            max-width: 100%;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-card);
        }
        .guide-container .screenshot-box .placeholder {
            color: var(--text-muted);
            font-size: 0.9rem;
            padding: 40px 20px;
            display: block;
        }
        .guide-container .btn-back {
            display: inline-block;
            padding: 10px 24px;
            background: var(--primary);
            color: #fff;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
            margin-top: 10px;
        }
        .guide-container .btn-back:hover {
            background: var(--accent);
        }
        .dark-mode .screenshot-box {
            border-color: var(--border-color);
        }
        @media (max-width: 600px) {
            .guide-container {
                margin: 20px 10px;
                padding: 16px;
            }
            .guide-container h1 {
                font-size: 1.4rem;
            }
            .guide-container .step {
                padding: 14px 16px;
            }
        }
    </style>
</head>
<body>
    <div class="guide-container">
        <h1>Panduan Aktivasi Akun</h1>
        <p class="subtitle">
            Setelah mendaftar, akun Anda harus diaktifkan oleh admin untuk mencegah penyalahgunaan.
        </p>

        <!-- STEP 1 -->
        <div class="step">
            <h3><span class="icon">1️⃣</span> Registrasi Akun</h3>
            <p>Buat akun melalui halaman <strong>Register</strong>. Isi username, email, dan password. Anda akan menerima email verifikasi untuk konfirmasi alamat email.</p>
        </div>

        <!-- STEP 2 -->
        <div class="step">
            <h3><span class="icon">2️⃣</span> Login & Halaman Pending</h3>
            <p>Setelah verifikasi email, coba login. Karena akun belum diaktifkan oleh admin, Anda akan diarahkan ke halaman <strong>"Akun Menunggu Aktivasi"</strong> seperti gambar di bawah.</p>
        </div>

        <!-- SCREENSHOT -->
        <div class="screenshot-box">
            <img src="assets/img/pending.png" 
                 alt="Halaman Akun Menunggu Aktivasi"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <span class="placeholder" style="display:none;">
                📸 <em>Letakkan screenshot halaman pending.png di sini</em><br>
                <small style="color:var(--text-muted);">Path: assets/images/pending_screenshot.png</small>
            </span>
        </div>

        <!-- STEP 3 -->
        <div class="step">
            <h3><span class="icon">3️⃣</span> Tunggu Aktivasi Admin</h3>
            <p>Admin akan memverifikasi akun Anda (biasanya dalam waktu maksimal 24 jam). Jika sudah diaktifkan, Anda bisa login seperti biasa.</p>
        </div>

        <!-- STEP 4 -->
        <div class="step">
            <h3><span class="icon">4️⃣</span> Hubungi Admin Jika Terlambat</h3>
            <p>Jika sudah lebih dari 24 jam dan akun Anda belum aktif, kunjungi halaman <em>pending</em>, ada sidebar kiri atas.</p>
        </div>

        <!-- TOMBOL KEMBALI -->
        <div style="text-align:center; margin-top:25px;">
            <a href="pending.php" class="btn-back">
            ← Lihat Halaman Pending
            </a>
            <a href="login.php" style="display:inline-block; margin-left:10px; color:var(--text-muted); text-decoration:none; font-weight:500;">Kembali ke halaman Login</a>
        </div>

        <p style="text-align:center; margin-top:20px; font-size:0.8rem; color:var(--text-muted);">
            <i class="fas fa-shield-alt"></i> Keamanan adalah prioritas utama kami.
        </p>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>