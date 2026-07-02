<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/fungsi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil data user (untuk tanggal bergabung)
$stmt = $conn->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Ambil statistik resource
$total_resource = 0;
$total_favorit = 0;
$total_selesai = 0;
$total_tag = 0;

// Total resource
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM resources WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$total_resource = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total favorit
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM resources WHERE user_id = ? AND is_favorite = 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$total_favorit = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total selesai
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM resources WHERE user_id = ? AND status = 'selesai'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$total_selesai = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total tag unik (tag yang dipakai oleh resource milik user)
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT rt.tag_id) AS total 
    FROM resource_tag rt 
    JOIN resources r ON rt.resource_id = r.id 
    WHERE r.user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$total_tag = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$error = '';
$success = '';
$error_delete = '';

// ===== PROSES UBAH PASSWORD =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Validasi keamanan gagal.');
    }

    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!password_verify($old, $user['password'])) {
        $error = 'Password lama salah.';
    } elseif (strlen($new) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } elseif ($new !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $hashed, $_SESSION['user_id']);
        if ($update->execute()) {
            $success = 'Password berhasil diubah!';
            logActivity($conn, $_SESSION['user_id'], 'ubah_password', 'Mengubah password pengguna');
        } else {
            $error = 'Gagal mengubah password. Coba lagi.';
        }
        $update->close();
    }
} // <-- TUTUP BLOK UBAH PASSWORD

// ===== PROSES HAPUS AKUN =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Validasi keamanan gagal.');
    }

    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!password_verify($password, $user['password'])) {
        $error_delete = 'Password salah.';
    } else {
        $conn->begin_transaction();
        try {
            $user_id = $_SESSION['user_id'];

            $stmt = $conn->prepare("DELETE FROM resources WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("
                DELETE t FROM tags t 
                LEFT JOIN resource_tag rt ON t.id = rt.tag_id 
                LEFT JOIN resources r ON rt.resource_id = r.id 
                WHERE t.id NOT IN (
                    SELECT tag_id FROM resource_tag 
                    WHERE resource_id IN (SELECT id FROM resources WHERE user_id != ?)
                )
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM user_logs WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            session_unset();
            session_destroy();
            header('Location: login.php?pesan=akun_dihapus');
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error_delete = 'Gagal menghapus akun: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Personal Resource Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Font Awesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Floating toggle -->
        <button class="floating-toggle" onclick="toggleSidebar()" title="Buka sidebar">☰</button>

        <!-- SIDEBAR (copy dari index.php) -->
        <aside class="sidebar">
            <button class="toggle-btn" onclick="toggleSidebar()" data-tooltip="Sembunyikan sidebar">❮</button>
            <h2 class="sidebar-title">Resource Hub</h2>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a>
                <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">📚 My Resources</a>
                <a href="index.php?favorit=1" class="nav-link">⭐ Favorit</a>
                <a href="index.php?status=arsip" class="nav-link">📦 Arsip</a>
                <a href="manage_tags.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_tags.php' ? 'active' : '' ?>">🏷️ Kelola Tag</a>
                <a href="activity.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'activity.php' ? 'active' : '' ?>">🕐 Aktivitas</a>
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

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <h1 class="content-title">⚙️ Pengaturan</h1>
            <p class="page-description">Kelola password, tema, dan fitur lainnya.</p>

            <?php if ($error !== ''): ?>
                <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if ($success !== ''): ?>
                <div style="background:#d4edda; color:#155724; padding:12px 16px; border-radius:8px; margin-bottom:15px;">
                    <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <div style="display:grid; grid-template-columns:1fr; gap:25px; max-width:700px;">

                <!-- 1. Ubah Password -->
                <section class="form-container" style="margin-top:0;">
                    <h2 style="margin-bottom:15px; font-size:1.3rem;">🔑 Ubah Password</h2>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="change_password" value="1">
                        <div class="form-group">
                            <label>Password Lama</label>
                            <div class="password-wrapper">
                                <input type="password" name="old_password" required>
                                <button type="button" class="toggle-password" title="Tampilkan/Sembunyikan password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Password Baru (min 6 karakter)</label>
                            <div class="password-wrapper">
                                <input type="password" name="new_password" required>
                                <button type="button" class="toggle-password" title="Tampilkan/Sembunyikan password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password Baru</label>
                            <div class="password-wrapper">
                                <input type="password" name="confirm_password" required>
                                <button type="button" class="toggle-password" title="Tampilkan/Sembunyikan password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-actions" style="margin-top:10px;">
                            <button type="submit">Update Password</button>
                        </div>
                    </form>
                </section>

                <!-- 2. Ekspor Data -->
                <section class="form-container" style="margin-top:0;">
                    <h2 style="margin-bottom:15px; font-size:1.3rem;">📥 Ekspor Data</h2>
                    <p style="color:var(--text-muted); margin-bottom:15px;">Download semua resource dalam format CSV.</p>
                    <a href="export.php" class="btn-primary" style="display:inline-block; text-decoration:none;">📥 Unduh CSV</a>
                </section>

                <!-- 3. Statistik Akun -->
                <section class="form-container" style="margin-top:0;">
                    <h2 style="margin-bottom:15px; font-size:1.3rem;">📊 Statistik Akun</h2>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                        <div style="background:var(--bg-soft); padding:15px; border-radius:var(--radius-md); text-align:center;">
                            <div style="font-size:2rem; font-weight:700; color:var(--text-link);"><?= $total_resource ?></div>
                            <div style="font-size:0.85rem; color:var(--text-muted);">Total Resource</div>
                        </div>
                        <div style="background:var(--bg-soft); padding:15px; border-radius:var(--radius-md); text-align:center;">
                            <div style="font-size:2rem; font-weight:700; color:var(--warning);"><?= $total_favorit ?></div>
                            <div style="font-size:0.85rem; color:var(--text-muted);">⭐ Favorit</div>
                        </div>
                        <div style="background:var(--bg-soft); padding:15px; border-radius:var(--radius-md); text-align:center;">
                            <div style="font-size:2rem; font-weight:700; color:var(--success);"><?= $total_selesai ?></div>
                            <div style="font-size:0.85rem; color:var(--text-muted);">✅ Selesai</div>
                        </div>
                        <div style="background:var(--bg-soft); padding:15px; border-radius:var(--radius-md); text-align:center;">
                            <div style="font-size:2rem; font-weight:700; color:var(--accent);"><?= $total_tag ?></div>
                            <div style="font-size:0.85rem; color:var(--text-muted);">🏷️ Total Tag</div>
                        </div>
                    </div>
                    <div style="margin-top:15px; padding:12px 16px; background:var(--bg-soft); border-radius:var(--radius-md); text-align:center; font-size:0.9rem; color:var(--text-muted);">
                        Bergabung sejak: <strong><?= date('d F Y', strtotime($user_data['created_at'])) ?></strong>
                    </div>
                </section>

                
        <!-- 4. Tentang Aplikasi (README) -->
        <section class="form-container" style="margin-top:0;" id="tentang-aplikasi">
            <h2 style="margin-bottom:15px; font-size:1.3rem;">📖 Tentang Projek</h2>
            <div style="color:var(--text-secondary); line-height:1.8; font-size:0.95rem;">
                <p><strong>Personal Resource Hub</strong> adalah website manajemen koleksi sumber daya digital menggunakan link dari berbagai platform. Projek ini dibangun dengan <strong>PHP Native</strong> dan <strong>MySQL</strong> sebagai portofolio pertama penulis yang mengimplementasikan fitur autentikasi lengkap, CRUD data, pengiriman email menggunakan SMTP, serta berbagai fitur pendukung.</p>
                    
                <hr style="border-color:var(--border-color); margin:15px 0;">
                    
                <h3 style="font-size:1rem; color:var(--text-primary);">✨ Fitur Utama</h3>
                <ul style="padding-left:20px; margin:8px 0;">
                    <li>🔐 Autentikasi (Register, Login, Verifikasi Email, Lupa Password)</li>
                    <li>📚 CRUD Resource dengan Tag & Rating</li>
                    <li>⭐ Fitur Favorit / Pin</li>
                    <li>📊 Dashboard Statistik</li>
                    <li>📥 Ekspor Resource ke CSV</li>
                    <li>🌙 Dark / Light Mode</li>
                    <li>📧 Kirim Email (Verifikasi & Reset Password) via SMTP</li>
                    <li>📌 Log Aktivitas (riwayat aksi user)</li>
                    <li>🏷️ Kelola Tag (tambah, edit, hapus tag permanen)</li>
                    <li>📊 Statistik Akun (total resource, favorit, selesai, tag)</li>
                    <li>⚠️ Hapus Akun (Danger Zone)</li>
                    <li>🛡️ Validasi URL (deteksi link mencurigakan, blacklist domain)</li>
                    <li>👥 Manajemen User (whitelist akses untuk keamanan portofolio)</li>
                </ul>
                    
                <hr style="border-color:var(--border-color); margin:15px 0;">
                    
                <h3 style="font-size:1rem; color:var(--text-primary);">🔒 Keamanan</h3>
                <ul style="padding-left:20px; margin:8px 0;">
                    <li>🔑 Password di-hash dengan Bcrypt</li>
                    <li>🛡️ CSRF Protection di setiap form</li>
                    <li>🧹 Prepared Statement (anti SQL Injection)</li>
                    <li>🔐 Whitelist User (hanya user terverifikasi yang bisa login dan telah diberi akses Login)</li>
                    <li>🚫 Validasi URL (blacklist domain berbahaya & shortlink)</li>
                    <li>📁 Proteksi file sensitif via .htaccess</li>
                </ul>
                    
                <hr style="border-color:var(--border-color); margin:15px 0;">
                    
                <p style="font-size:0.85rem; color:var(--text-muted);">
                    <strong>Teknologi:</strong> PHP 8, MySQL, PHPMailer, HTML5, CSS3, JavaScript (Vanilla) <br>
                    <strong>Versi:</strong> 2.0.0 &nbsp;|&nbsp; <strong>Lisensi:</strong> MIT
                </p>
                <p style="margin-top:15px; font-size:0.9rem; color:var(--text-muted);">
                    <strong>📅 Dibuat:</strong> Juni 2026 &nbsp;|&nbsp; 
                    <strong>👨‍💻 Pengembang:</strong> Feri
                </p>
            </div>
        </section>
                    
                <!-- 5. Hubungi Saya -->
                <section class="form-container" style="margin-top:0;" id="kontak">
                    <h2 style="margin-bottom:15px; font-size:1.3rem;">📬 Hubungi Saya</h2>
                    <p style="color:var(--text-muted); margin-bottom:15px;">
                        Jika menemukan bug atau kendala, hubungi saya melalui:
                    </p>
                    <div style="display:flex; flex-wrap:wrap; gap:15px;">
                        <!-- Email -->
                        <a href="mailto:ferdiantoferi1303@gmail.com?subject=Hubungi%20saya%20via%20Email&body=Halo%20Feri%2C%20saya%20ingin%20bertanya%20tentang..." 
                           style="display:inline-flex; align-items:center; gap:10px; padding:12px 20px; 
                                  background:var(--bg-soft); border-radius:var(--radius-md); text-decoration:none; 
                                  color:var(--text-primary); transition:all 0.2s ease; border:1px solid var(--border-color);">
                            <i class="fas fa-envelope" style="font-size:1.3rem; color:#D44638;"></i>
                            <span>ferdiantoferi1303@gmail.com</span>
                        </a>
                    
                        <!-- Instagram -->
                        <a href="https://www.instagram.com/imnotferrriii/" target="_blank" rel="noopener noreferrer" 
                           style="display:inline-flex; align-items:center; gap:10px; padding:12px 20px; 
                                  background:var(--bg-soft); border-radius:var(--radius-md); text-decoration:none; 
                                  color:var(--text-primary); transition:all 0.2s ease; border:1px solid var(--border-color);">
                            <i class="fab fa-instagram" style="font-size:1.3rem; color:#E4405F;"></i>
                            <span>@imnotferrriii</span>
                        </a>
                    </div>
                    <p style="margin-top:12px; font-size:0.85rem; color:var(--text-muted);">
                        Terima kasih sudah menggunakan Website ini! 🙏
                    </p>
                </section>

        <!-- 6. Danger Zone - Hapus Akun -->
        <section style="border:2px solid var(--text-danger); border-radius:var(--radius-lg); padding:25px; margin-top:25px; background:var(--bg-danger-soft);">
            <h3 style="color:var(--text-danger); margin-bottom:10px; font-size:1.2rem;">⚠️ Danger Zone</h3>
            <p style="color:var(--text-muted); margin-bottom:15px;">
                Hapus akun secara permanen. Semua data (resource, tag, log aktivitas) akan hilang dan tidak dapat dikembalikan.
            </p>

            <?php if (isset($error_delete) && $error_delete !== ''): ?>
                <div class="error"><?= htmlspecialchars($error_delete, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            
            <!-- ===== FORM HAPUS AKUN (TANPA onsubmit) ===== -->
            <div>
                <div class="form-group" style="max-width:400px;">
                    <label for="delete-password">Masukkan password untuk konfirmasi:</label>
                    <div class="password-wrapper">
                        <input type="password" id="delete-password" name="password" required placeholder="Password Anda">
                        <button type="button" class="toggle-password" title="Tampilkan/Sembunyikan password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            
                <div class="form-group" style="max-width:400px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" id="confirm-delete" required>
                        <span style="font-weight:400; color:var(--text-secondary);">Saya yakin ingin menghapus akun ini secara permanen.</span>
                    </label>
                </div>
            
                <!-- Form tersembunyi (DI LUAR form utama) -->
                <form id="deleteAccountForm" method="POST" style="display:none;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="delete_account" value="1">
                    <input type="hidden" name="password" id="delete-password-hidden">
                </form>
            
                <button type="button" onclick="confirmDeleteAccount()" 
                        style="background:var(--text-danger); color:#fff; padding:12px 24px; border-radius:var(--radius-md); cursor:pointer; font-weight:700; border:none;">
                    🗑️ Hapus Akun Permanen
                </button>
            </div>
        </section>

        </div>
    </div>
</main>



<?php include 'assets/modal.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>