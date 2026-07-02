<?php
// Tidak perlu session, karena user belum login
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Menunggu Aktivasi - Resource Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- ===== TOMBOL SIDEBAR MOBILE ===== -->
        <button class="floating-toggle" onclick="toggleSidebar()" title="Buka sidebar">☰</button>

        <!-- ===== SIDEBAR SEDERHANA ===== -->
        <aside class="sidebar">
            <button class="toggle-btn" onclick="toggleSidebar()" data-tooltip="Sembunyikan sidebar">❮</button>
            <h2 class="sidebar-title">Resource Hub</h2>
            <p style="font-size:0.9rem; color:var(--text-muted);">Kontak Pemilik Website</p>
            <nav class="sidebar-nav">
                <a href="mailto:ferdiantoferi1303@gmail.com?subject=Hubungi%20saya%20via%20Email&body=Halo%20Feri%2C%20saya%20ingin%20bertanya%20tentang..." class="nav-link">
                    <i class="fas fa-envelope"></i> Email
                </a>
                <a href="https://www.instagram.com/imnotferrriii/" target="_blank" rel="noopener noreferrer" class="nav-link">
                    <i class="fab fa-instagram"></i> Instagram
                </a>
            </nav>
        </aside>

        <!-- ===== MAIN CONTENT ===== -->
        <main class="main-content" style="display:flex; justify-content:center; align-items:center; min-height:100vh; margin-left:0; padding:20px;">
            <div class="pending-card" style="max-width:500px; width:100%; padding:40px 30px; background:var(--bg-card); border-radius:var(--radius-lg); box-shadow:var(--shadow-card); text-align:center;">
                <div class="icon" style="font-size:4rem; margin-bottom:15px;">⏳</div>
                <h1 style="font-size:1.6rem; margin-bottom:10px; color:var(--text-primary);">Akun Menunggu Aktivasi</h1>
                <p style="color:var(--text-secondary); line-height:1.6; margin-bottom:15px;">
                    Akun Anda telah berhasil dibuat, namun <strong>belum diaktifkan</strong> oleh Admin
                </p>
                <div style="background:var(--bg-soft); padding:12px 16px; border-radius:var(--radius-md); margin:15px 0; font-size:0.9rem; color:var(--text-muted); border-left:4px solid var(--accent);">
                    ❗ <strong>Kenapa harus menunggu?</strong><br>
                    Website ini memang bertujuan Publik. Tapi melihat kembali Projek ini menggunakan Link,
                    maka untuk mencegah <strong>Menghindari Penyalahgunaan (misalnya: menyimpan link berbahaya, Phishing, Virus, Malware dan link negatif lainnya).</strong>
                </div>
                <p style="font-size:0.9rem; color:var(--text-muted);">
                    Silakan tunggu konfirmasi dari pemilik website. Jika sudah lebih dari 24 jam, <strong>klik sidebar kiri atas</strong>
                </p>
                <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:center; margin-top:20px;">
                    <a href="login.php" style="padding:10px 24px; border-radius:var(--radius-md); text-decoration:none; font-weight:600; background:var(--primary); color:#fff; transition:all 0.2s;">← Kembali ke halaman Login</a>
                </div>
                <p style="text-align:center; margin-top:10px;">
                    <a href="panduan_aktivasi.php" style="font-size:0.85rem;">Ada kendala Login? Cek panduan di sini</a>
                </p>
            </div>
        </main>
    </div>

    <!-- ===== JAVASCRIPT ===== -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const app = document.querySelector('.app-container');
            if (app) {
                const savedState = localStorage.getItem('sidebarState');
                if (savedState === 'collapsed') {
                    app.classList.add('sidebar-collapsed');
                }
            }
        });
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>