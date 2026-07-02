<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/fungsi.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil ID resource
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Ambil data resource (hanya milik user yang login)
$stmt = $conn->prepare("SELECT * FROM resources WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$resource = $result->fetch_assoc();
$stmt->close();

if (!$resource) {
    header('Location: index.php');
    exit;
}

// Ambil tag resource
$tags = ambilDetailTagResource($conn, $id);

// Format tanggal
$created_at = date('d F Y H:i', strtotime($resource['created_at']));

// Status label
$status_labels = [
    'belum_dibaca' => 'Belum Dibaca/Belum selesai',
    'sedang_dipelajari' => 'Sedang Dipelajari',
    'selesai' => 'Selesai',
    'arsip' => 'Arsip',
];
$status_text = $status_labels[$resource['status']] ?? $resource['status'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Resource - Personal Resource Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <button class="floating-toggle" onclick="toggleSidebar()" title="Buka sidebar" aria-label="Buka sidebar">☰</button>
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

        <!-- ===== KONTEN UTAMA ===== -->
        <main class="main-content">
            <div class="detail-container">
                <h1 class="detail-title"><?= htmlspecialchars($resource['judul'], ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="detail-meta">
                    Ditambahkan pada <?= $created_at ?>
                    <?php if ((int)$resource['is_favorite'] === 1): ?>
                        <span style="color:var(--warning); margin-left:10px;">⭐ Favorit</span>
                    <?php endif; ?>
                </div>

                <div class="detail-item">
                    <span class="detail-label">🔗 URL</span>
                    <span class="detail-value"><a href="<?= htmlspecialchars($resource['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($resource['url'], ENT_QUOTES, 'UTF-8') ?></a></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">🌐 Sumber Link</span>
                    <span class="detail-value" style="display:flex; align-items:center; gap:8px;">
                        <?php 
                            $domain = parse_url($resource['url'], PHP_URL_HOST);
                            $domain = str_replace('www.', '', $domain);
                        ?>
                        <img src="https://www.google.com/s2/favicons?domain=<?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') ?>" 
                             alt="Favicon" 
                             style="width:20px; height:20px; border-radius:4px;">
                        <span><?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">📝 Deskripsi</span>
                    <span class="detail-value"><?= nl2br(htmlspecialchars($resource['deskripsi'], ENT_QUOTES, 'UTF-8')) ?: '-' ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">📂 Jenis</span>
                    <span class="detail-value"><?= htmlspecialchars($resource['jenis'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">📊 Status</span>
                    <span class="detail-value"><?= htmlspecialchars($status_text, ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">⭐ Rating</span>
                    <span class="detail-value"><?= $resource['rating'] ? (int)$resource['rating'] . '/10' : 'Belum diberi rating' ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">📝 Catatan Pribadi</span>
                    <span class="detail-value"><?= nl2br(htmlspecialchars($resource['catatan_pribadi'], ENT_QUOTES, 'UTF-8')) ?: '-' ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">🏷️ Tag</span>
                    <span class="detail-value">
                        <?php if (count($tags) > 0): ?>
                            <div class="detail-tags">
                                <?php foreach ($tags as $tag): ?>
                                    <span class="tag-badge">#<?= htmlspecialchars($tag['nama_tag'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </span>
                </div>

                <div class="detail-actions">
                    <a href="index.php" class="btn-back">← Kembali ke Daftar</a>
                    <a href="edit.php?id=<?= $id ?>" class="btn-edit-detail">✏️ Edit Resource</a>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>