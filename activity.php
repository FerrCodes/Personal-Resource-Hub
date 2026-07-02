<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/fungsi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ===== PROSES HAPUS SEMUA LOG =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Validasi keamanan gagal.');
    }
    $stmt = $conn->prepare("DELETE FROM user_logs WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    header('Location: activity.php?pesan=log_dihapus');
    exit;
}

// ===== HAPUS LOG LEBIH DARI 7 HARI =====
$stmt = $conn->prepare("DELETE FROM user_logs WHERE user_id = ? AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->close();

// ===== AMBIL 50 LOG TERBARU =====
$stmt = $conn->prepare("
    SELECT action, details, created_at 
    FROM user_logs 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Tampilkan pesan sukses
if (isset($_GET['pesan']) && $_GET['pesan'] === 'log_dihapus') {
    $success = '✅ Semua log aktivitas berhasil dihapus!';
}

// Map action ke label dan icon
$action_map = [
    'login' => ['icon' => '🔐', 'label' => 'Login'],
    'logout' => ['icon' => '🚪', 'label' => 'Logout'],
    'tambah_resource' => ['icon' => '➕', 'label' => 'Menambahkan resource'],
    'edit_resource' => ['icon' => '✏️', 'label' => 'Mengedit resource'],
    'hapus_resource' => ['icon' => '🗑️', 'label' => 'Menghapus resource'],
    'ubah_password' => ['icon' => '🔑', 'label' => 'Mengubah password'],
    'pin_resource' => ['icon' => '⭐', 'label' => 'Pin resource'],
    'tambah_tag' => ['icon' => '🏷️', 'label' => 'Menambahkan tag'],
    'hapus_tag' => ['icon' => '🗑️', 'label' => 'Menghapus tag'],
    'edit_tag' => ['icon' => '✏️', 'label' => 'Edit tag'], // <-- TAMBAHKAN INI
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivitas - Personal Resource Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <!-- Floating toggle -->
        <button class="floating-toggle" onclick="toggleSidebar()" title="Buka sidebar">☰</button>

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <button class="toggle-btn" onclick="toggleSidebar()" data-tooltip="Sembunyikan sidebar">❮</button>
            <h2 class="sidebar-title">Resource Hub</h2>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link">📊 Dashboard</a>
                <a href="index.php" class="nav-link">📚 My Resources</a>
                <a href="index.php?favorit=1" class="nav-link">⭐ Favorit</a>
                <a href="index.php?status=arsip" class="nav-link">📦 Arsip</a>
                <a href="manage_tags.php" class="nav-link">🏷️ Kelola Tag</a>
                <a href="activity.php" class="nav-link active">🕐 Aktivitas</a>
            </nav>
            <div class="user-section">
                <a href="tambah.php" class="btn-sidebar-tambah">+ Tambah Resource</a>
                <div class="user-dropdown" id="userDropdown">
                    <div class="user-trigger" onclick="toggleUserDropdown(event)">
                        <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?></div>
                        <span class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="user-chevron">▾</span>
                    </div>
                    <div class="dropdown-menu">
                        <a href="settings.php" class="dropdown-item">⚙️ Settings</a>
                        <div class="dropdown-divider"></div>
                        <button id="darkModeToggle" class="dropdown-item" type="button" style="width:100%; text-align:left;">
                            <span id="darkModeLabel">🌙 Switch Theme</span>
                        </button>
                        <div class="dropdown-divider"></div>
                        <?php if ($_SESSION['user_id'] == 3): ?>
                        <a href="manage_users.php" class="dropdown-item">👥 Kelola User</a>
                        <?php endif; ?>
                        <a href="logout.php" class="dropdown-item danger">🚪 Logout</a>
                    </div>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <h1 class="content-title">🕐 Aktivitas Terbaru</h1>
            <p class="page-description">Riwayat aktivitas terakhir kamu di Resource Hub</p>

            <?php if (isset($success)): ?>
                <div id="notif-success" style="background:#d4edda; color:#155724; padding:12px 16px; border-radius:8px; margin-bottom:15px;">
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <?php if (count($logs) > 0): ?>
                <section class="form-container" style="max-width:800px;">
                    <ul style="list-style:none; padding:0; margin:0;">
                    <?php foreach ($logs as $log): 
                        $time_diff = time() - strtotime($log['created_at']);
                        if ($time_diff < 60) {
                            $time_text = 'baru saja';
                        } elseif ($time_diff < 3600) {
                            $time_text = floor($time_diff / 60) . ' menit yang lalu';
                        } elseif ($time_diff < 86400) {
                            $time_text = floor($time_diff / 3600) . ' jam yang lalu';
                        } elseif ($time_diff < 604800) {
                            $time_text = floor($time_diff / 86400) . ' hari yang lalu';
                        } else {
                            $time_text = date('d M Y H:i', strtotime($log['created_at']));
                        }

                        $action_info = $action_map[$log['action']] ?? ['icon' => '❓', 'label' => $log['action']];
                    ?>
                        <li class="activity-item">
                            <div class="activity-left">
                                <span class="activity-icon"><?= $action_info['icon'] ?></span>
                                <span class="activity-action"><?= htmlspecialchars($action_info['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($log['details']): ?>
                                    <span class="activity-details">— <?= htmlspecialchars($log['details'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="activity-time"><?= $time_text ?></span>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                </section>
            <?php else: ?>
                <p style="color:var(--text-muted); text-align:center; margin-top:40px;">Belum ada aktivitas tercatat.</p>
            <?php endif; ?>
            <!-- Tombol Hapus Semua Log (di atas daftar) -->
            <form id="clearLogsForm" method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="clear_logs" value="1">
                <button type="button" onclick="confirmClearAllLogs()" 
                        style="background:var(--text-danger); color:#fff; padding:8px 16px; border-radius:var(--radius-md); border:none; cursor:pointer; font-weight:600;">
                    🗑️ Hapus Semua Log
                </button>
            </form>
        </main>
    </div>

    <script>
        // Auto-hide notifikasi sukses
        document.addEventListener('DOMContentLoaded', function() {
            var notif = document.getElementById('notif-success');
            if (notif) {
                setTimeout(function() {
                    notif.style.transition = 'opacity 0.5s';
                    notif.style.opacity = '0';
                    setTimeout(function() {
                        notif.style.display = 'none';
                    }, 500);
                }, 3000);
            }
        });
    </script>

    
<script>const csrfToken = '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>';</script>
<?php include 'assets/modal.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>