<?php
// Tiga baris ini adalah "Senter" untuk melihat error asli
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
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
   FILTER DATA RESOURCE
   Mengambil input pencarian dari query string.
   ========================================================= */
$filter_jenis = $_GET['jenis'] ?? 'semua';
$filter_status = $_GET['status'] ?? 'semua';
$cari = trim($_GET['cari'] ?? '');
$filter_tag = trim($_GET['tag'] ?? '');

$jenis_standar = ['video', 'artikel', 'dokumentasi', 'github'];
$status_labels = [
    'belum_dibaca' => 'Belum Dibaca/Belum selesai',
    'sedang_dipelajari' => 'Sedang Dipelajari',
    'selesai' => 'Selesai',
    'arsip' => 'Arsip',
];

/* =========================================================
   QUERY RESOURCE
   Menggunakan prepared statement agar aman dari SQL Injection.
   ========================================================= */
$sql = "SELECT * FROM resources WHERE user_id = ?";
$params = [$_SESSION['user_id']];
$types = "i";

// Filter favorit
if (isset($_GET['favorit']) && $_GET['favorit'] == 1) {
    $sql .= " AND is_favorite = 1";
}
// Jangan tampilkan resource arsip di halaman utama (kecuali sedang filter arsip)
if (!isset($_GET['status']) || $_GET['status'] !== 'arsip') {
    $sql .= " AND status != 'arsip'";
}

// Filter jenis dan status
if ($filter_jenis !== '' && $filter_jenis !== 'semua') {
    if ($filter_jenis === 'lainnya') {
        $sql .= " AND jenis NOT IN ('video', 'artikel', 'dokumentasi', 'github')";
    } else {
        $sql .= " AND jenis = ?";
        $params[] = $filter_jenis;
        $types .= "s";
    }
}

if ($filter_status !== '' && $filter_status !== 'semua') {
    $sql .= " AND status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($cari !== '') {
    $sql .= " AND (judul LIKE ? OR deskripsi LIKE ?)";
    $params[] = "%{$cari}%";
    $params[] = "%{$cari}%";
    $types .= "ss";
}

if ($filter_tag !== '') {
    $sql .= " AND id IN (
        SELECT rt.resource_id
        FROM resource_tag rt
        JOIN tags t ON rt.tag_id = t.id
        WHERE t.nama_tag = ?
    )";
    $params[] = $filter_tag;
    $types .= "s";
}

$sql .= " ORDER BY is_favorite DESC, created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$resources = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$active_menu = 'my_resources';
if (isset($_GET['favorit']) && $_GET['favorit'] == 1) {
    $active_menu = 'favorit';
} elseif (isset($_GET['status']) && $_GET['status'] == 'arsip') {
    $active_menu = 'arsip';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Resources - Personal Resource Hub</title>
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
    <a href="index.php" class="nav-link <?= $active_menu == 'my_resources' ? 'active' : '' ?>">📚 My Resources</a>
    <a href="index.php?favorit=1" class="nav-link <?= $active_menu == 'favorit' ? 'active' : '' ?>">⭐ Favorit</a>
    <a href="index.php?status=arsip" class="nav-link <?= $active_menu == 'arsip' ? 'active' : '' ?>">📦 Arsip</a>
    <a href="manage_tags.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_tags.php' ? 'active' : '' ?>">🏷️ Kelola Tag</a>
    <a href="activity.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'activity.php' ? 'active' : '' ?>">🕐 Aktivitas</a>
</nav>
    <div class="user-section">
        <a href="tambah.php" class="btn-sidebar-tambah <?= basename($_SERVER['PHP_SELF']) == 'tambah.php' ? 'active' : '' ?>">
            + Tambah Resource
        </a>
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
             KONTEN UTAMA: DAFTAR RESOURCE
             Berisi filter, pencarian, dan kartu resource.
             ===================================================== -->
        <main class="main-content">
            <h1 class="content-title">📚 Koleksi Sumber Dayaku</h1>

            <?php if ($filter_tag !== ''): ?>
                <!-- ===== Info filter tag aktif ===== -->
                <div class="filter-tag-notice">
                    Menampilkan resource dengan tag:
                    <strong class="filter-tag-name">#<?= htmlspecialchars($filter_tag, ENT_QUOTES, 'UTF-8') ?></strong>
                    <a href="index.php" class="filter-tag-close" title="Hapus filter tag">✖</a>
                </div>
            <?php endif; ?>

            <!-- ===== Form filter & pencarian ===== -->
            <form method="GET" class="filter-form">
                <input
                    type="text"
                    name="cari"
                    placeholder="Cari judul atau deskripsi..."
                    value="<?= htmlspecialchars($cari, ENT_QUOTES, 'UTF-8') ?>"
                >

                <select name="jenis">
                    <option value="semua">Semua Jenis</option>
                    <option value="video" <?= $filter_jenis === 'video' ? 'selected' : '' ?>>Video</option>
                    <option value="artikel" <?= $filter_jenis === 'artikel' ? 'selected' : '' ?>>Artikel</option>
                    <option value="dokumentasi" <?= $filter_jenis === 'dokumentasi' ? 'selected' : '' ?>>Dokumentasi</option>
                    <option value="github" <?= $filter_jenis === 'github' ? 'selected' : '' ?>>GitHub</option>
                    <option value="lainnya" <?= $filter_jenis === 'lainnya' ? 'selected' : '' ?>>Lainnya</option>
                </select>

                <select name="status">
                    <option value="semua">Semua Status</option>
                    <?php foreach ($status_labels as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $filter_status === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" title="Terapkan filter resource yang ingin dipilih/dicari">Filter</button>
            </form>

            <!-- ===== Daftar kartu resource ===== -->
            <?php if (count($resources) > 0): ?>
                <section class="resource-list" aria-label="Daftar resource">
                    <?php foreach ($resources as $r): ?>
                        <?php
                        $resource_id = (int)$r['id'];
                        $parsed_url = parse_url($r['url']);
                        $domain = $parsed_url['host'] ?? '';
                        $detail_tags = ambilDetailTagResource($conn, $resource_id);
                        $status_key = $r['status'] ?? '';
                        $status_text = $status_labels[$status_key] ?? str_replace('_', ' ', $status_key);
                        ?>

                        <article class="resource-card <?= (int)$r['is_favorite'] === 1 ? 'kartu-favorit' : '' ?>">
                            <div class="resource-card-header">
                                <h3 class="resource-card-title">
                                    <?php if ($domain !== ''): ?>
                                        <img
                                            src="https://www.google.com/s2/favicons?domain=<?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') ?>&amp;sz=32"
                                            alt=""
                                            class="resource-favicon"
                                        >
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($r['judul'], ENT_QUOTES, 'UTF-8') ?></span>
                                </h3>

                                <a
                                    href="pin.php?id=<?= $resource_id ?>&amp;token=<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                                    class="btn-pin"
                                    data-tooltip="<?= (int)$r['is_favorite'] === 1 ? 'Lepaskan Pin' : 'Pin ke Atas' ?>"
                                    aria-label="<?= (int)$r['is_favorite'] === 1 ? 'Lepaskan pin resource' : 'Pin resource ke atas' ?>"
                                ><?= (int)$r['is_favorite'] === 1 ? '⭐' : '☆' ?></a>
                            </div>

                            <p class="resource-description"><?= nl2br(htmlspecialchars($r['deskripsi'], ENT_QUOTES, 'UTF-8')) ?></p>

                            <p class="resource-meta">
                                <strong>Jenis:</strong> <?= htmlspecialchars($r['jenis'], ENT_QUOTES, 'UTF-8') ?> |
                                <strong>Status:</strong> <?= htmlspecialchars($status_text, ENT_QUOTES, 'UTF-8') ?> |
                                <strong>Rating:</strong> <?= $r['rating'] ? (int)$r['rating'] . '/10' : '-' ?>
                            </p>

                            <?php if (count($detail_tags) > 0): ?>
                                <div class="tag-list">
                                    <?php foreach ($detail_tags as $dt): ?>
                                        <a href="index.php?tag=<?= urlencode($dt['nama_tag']) ?>" class="tag-badge">
                                            #<?= htmlspecialchars($dt['nama_tag'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="resource-actions">
                                <a href="<?= htmlspecialchars($r['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn-buka">🔗 Buka Resource</a>
                                <button type="button" class="btn-copy" data-copy-url="<?= htmlspecialchars($r['url'], ENT_QUOTES, 'UTF-8') ?>" onclick="copyToClipboard(this.dataset.copyUrl, this)">📋 Salin Link</button>
                            </div>

                            <div class="action-buttons">
                                <a href="detail.php?id=<?= $r['id'] ?>" class="btn-detail">Lihat Detail</a>
                                <a href="edit.php?id=<?= $r['id'] ?>" class="btn-edit" title="Edit resource">Edit</a>
                                <button type="button" 
                                        class="btn-hapus" 
                                        onclick="confirmDeleteResource(<?= $r['id'] ?>, '<?= htmlspecialchars($r['judul'], ENT_QUOTES) ?>')">
                                   Hapus
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php else: ?>
                <p class="empty-state">Belum ada resource digital yang disimpan. Yuk tambah baru!</p>
            <?php endif; ?>
        </main>
    </div>

    <script>
    const csrfToken = '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>';
    </script>
    <?php include 'assets/modal.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>