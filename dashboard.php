<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/fungsi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Tambahkan kode ini untuk mencegah browser menyimpan cache halaman
header("Cache-Control: no-cache, no-store, must-revalidate"); // Standar HTTP 1.1
header("Pragma: no-cache"); // Standar HTTP 1.0
header("Expires: 0"); // Proxies

/* =========================================================
   DASHBOARD DATA
   Mengambil jumlah resource berdasarkan status.
   ========================================================= */
$statistik = ambilStatistikStatus($conn, $_SESSION['user_id']);

// Ambil statistik per jenis
$statistik_jenis = ambilStatistikJenis($conn, $_SESSION['user_id']);

// Data untuk grafik status (tanpa total)
$status_labels = ['Belum Dibaca', 'Sedang Dipelajari', 'Selesai', 'Arsip'];
$status_data = [
    (int)$statistik['belum_dibaca'],
    (int)$statistik['sedang_dipelajari'],
    (int)$statistik['selesai'],
    (int)$statistik['arsip']
];

// Data untuk grafik jenis
$jenis_labels = array_keys($statistik_jenis);
$jenis_data = array_values($statistik_jenis);

// Warna untuk grafik
$warna_status = ['#e94560', '#f1c40f', '#2ecc71', '#6c757d'];
$warna_jenis = ['#0f3460', '#e94560', '#2ecc71', '#f1c40f', '#6c757d', '#3498db'];

// Ambil 5 resource terbaru milik user yang login
$stmt = $conn->prepare("SELECT id, judul, jenis, status, created_at FROM resources WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$recent_resources = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Personal Resource Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <!-- =====================================================
             TOMBOL SIDEBAR MOBILE / COLLAPSED
             Muncul ketika sidebar disembunyikan.
             ===================================================== -->
        <button class="floating-toggle" onclick="toggleSidebar()" title="Buka sidebar" aria-label="Buka sidebar">☰</button>
<aside class="sidebar">
        <button class="toggle-btn" onclick="toggleSidebar()" data-tooltip="Sembunyikan sidebar" aria-label="Sembunyikan sidebar">❮</button>

    <h2 class="sidebar-title">Resource Hub</h2>

    <nav class="sidebar-nav" aria-label="Navigasi utama">
         <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a>
        <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">📚 My Resources</a>
        <a href="index.php?favorit=1" class="nav-link <?= isset($_GET['favorit']) && $_GET['favorit'] == 1 ? 'active' : '' ?>">⭐ Favorit</a>
        <a href="index.php?status=arsip" class="nav-link <?= isset($_GET['status']) && $_GET['status'] == 'arsip' ? 'active' : '' ?>">📦 Arsip</a>
        <a href="manage_tags.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_tags.php' ? 'active' : '' ?>">🏷️ Kelola Tag</a>
        <a href="activity.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'activity.php' ? 'active' : '' ?>">🕐 Aktivitas</a>
    </nav>
    <!-- ===== WRAPPER untuk tombol + dropdown ===== -->
    <div class="user-section">
        <!-- Tombol Tambah Resource -->
        <a href="tambah.php" class="btn-sidebar-tambah <?= basename($_SERVER['PHP_SELF']) == 'tambah.php' ? 'active' : '' ?>">
            + Tambah Resource
        </a>

        <!-- Dropdown User (muncul menutupi tombol) -->
        <div class="user-dropdown" id="userDropdown">
            <div class="user-trigger" onclick="toggleUserDropdown(event)">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                </div>
                <span class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
                <span class="user-chevron">▾</span>
            </div>

            <div class="dropdown-menu">
                <a href="" class="dropdown-item">👤 Profile (Belum tersedia)</a>
                <a href="settings.php" class="dropdown-item" title="Fitur lainnya">⚙️ Settings</a>
                <div class="dropdown-divider"></div>
                <button id="darkModeToggle" class="dropdown-item" type="button" title="Sesuaikan kenyamanan" style="width:100%; text-align:left;">
                    <span id="darkModeLabel">🌙 Switch Theme</span>
                </button>
                <div class="dropdown-divider"></div>
                <?php if ($_SESSION['user_id'] == 3): ?>
                <a href="manage_users.php" class="dropdown-item">👥 Kelola User</a>
                <?php endif; ?>
                <a href="settings.php#tentang-aplikasi" class="dropdown-item">❗ Info Lebih Lanjut</a>
                <a href="logout.php" class="dropdown-item danger" title="Keluar dari akun">🚪 Logout</a>
            </div>
        </div>
    </div>
</aside>

        <!-- =====================================================
             KONTEN UTAMA: DASHBOARD
             Berisi ringkasan statistik resource.
             ===================================================== -->
        <main class="main-content">
            <h1 class="content-title">📊 Learning Insights Dashboard</h1>
            <p class="page-description">Kumpulkan, kelola, dan lacak progress resource digital favoritmu.</p>

            <!-- ===== Grid kartu statistik ===== -->
            <section class="dashboard-grid" aria-label="Statistik resource">
                <article class="big-stat-card total">
                    <div class="stat-info">
                        <h3>Total Semua Koleksi</h3>
                        <p><?= (int)$statistik['total'] ?></p>
                    </div>
                    <div class="stat-icon" aria-hidden="true">📚</div>
                </article>

                <article class="big-stat-card belum-dibaca">
                    <div class="stat-info">
                        <h3>Belum Dibaca/Belum selesai</h3>
                        <p><?= (int)$statistik['belum_dibaca'] ?></p>
                    </div>
                    <div class="stat-icon" aria-hidden="true">💤</div>
                </article>

                <article class="big-stat-card sedang-dipelajari">
                    <div class="stat-info">
                        <h3>Sedang Dipelajari</h3>
                        <p><?= (int)$statistik['sedang_dipelajari'] ?></p>
                    </div>
                    <div class="stat-icon" aria-hidden="true">📖</div>
                </article>

                <article class="big-stat-card arsip">
                    <div class="stat-info">
                        <h3>Diarsipkan</h3>
                        <p><?= (int)$statistik['arsip'] ?></p>
                    </div>
                <div class="stat-icon" aria-hidden="true">📦</div>
                </article>

                <article class="big-stat-card selesai">
                    <div class="stat-info">
                        <h3>Sudah Selesai</h3>
                        <p><?= (int)$statistik['selesai'] ?></p>
                    </div>
                    <div class="stat-icon" aria-hidden="true">✅</div>
                </article>
            </section>

        <!-- ===== DAFTAR RESOURCE TERBARU ===== -->
        <section class="recent-section">
            <h2>📌 Resource Terbaru</h2>
            <?php if (count($recent_resources) > 0): ?>
                <ul class="recent-list">
                    <?php foreach ($recent_resources as $rr): ?>
                        <li class="recent-item">
                            <span class="title">
                                <a href="detail.php?id=<?= (int)$rr['id'] ?>">
                                    <?= htmlspecialchars($rr['judul'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </span>
                            <span class="meta">
                                <?= htmlspecialchars($rr['jenis'], ENT_QUOTES, 'UTF-8') ?> · 
                                <?= date('d M Y', strtotime($rr['created_at'])) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="empty-state">Belum ada resource. <a href="tambah.php" style="color:var(--accent);">Tambahkan sekarang!</a></p>
            <?php endif; ?>
        </section>
            
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
