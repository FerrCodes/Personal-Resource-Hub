<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/fungsi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';
$edit_tag_id = null;
$edit_tag_name = '';

// Ambil semua tag
$all_tags = ambilSemuaTag($conn);

/* =========================================================
   HANDLER: HAPUS TAG PERMANEN
   ========================================================= */
if (isset($_GET['hapus']) && isset($_GET['token'])) {
    $tag_id = (int)$_GET['hapus'];
    $token = $_GET['token'] ?? '';

    if ($tag_id > 0 && hash_equals($_SESSION['csrf_token'], $token)) {
        $cek = $conn->prepare("SELECT COUNT(*) FROM resource_tag WHERE tag_id = ?");
        $cek->bind_param("i", $tag_id);
        $cek->execute();
        $cek->bind_result($count);
        $cek->fetch();
        $cek->close();

        if ($count > 0) {
            $error = "Tag masih digunakan oleh $count resource. Hapus relasi terlebih dahulu.";
        } else {
            $stmt = $conn->prepare("DELETE FROM tags WHERE id = ?");
            $stmt->bind_param("i", $tag_id);
            if ($stmt->execute()) {
                $success = "✅ Tag berhasil dihapus permanen!";
                logActivity($conn, $_SESSION['user_id'], 'hapus_tag', "Menghapus tag ID $tag_id");
                $all_tags = ambilSemuaTag($conn);
            } else {
                $error = "Gagal menghapus tag.";
            }
            $stmt->close();
        }
    } else {
        $error = "Token tidak valid.";
    }
}

/* =========================================================
   HANDLER: EDIT TAG
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_tag'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Validasi keamanan gagal.');
    }

    $tag_id = (int)$_POST['tag_id'];
    $nama_baru = trim(strip_tags($_POST['nama_tag'] ?? ''));

    if ($nama_baru === '') {
        $error = 'Nama tag tidak boleh kosong.';
    } else {
        // Cek apakah nama tag sudah ada (kecuali dirinya sendiri)
        $stmt = $conn->prepare("SELECT id FROM tags WHERE nama_tag = ? AND id != ?");
        $stmt->bind_param("si", $nama_baru, $tag_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error = "Tag '$nama_baru' sudah ada.";
        } else {
            $stmt = $conn->prepare("UPDATE tags SET nama_tag = ? WHERE id = ?");
            $stmt->bind_param("si", $nama_baru, $tag_id);
            if ($stmt->execute()) {
                $success = "✅ Tag berhasil diubah menjadi '$nama_baru'!";
                logActivity($conn, $_SESSION['user_id'], 'edit_tag', "Mengubah tag ID $tag_id menjadi '$nama_baru'");
                // Redirect agar modal tidak muncul lagi
                $all_tags = ambilSemuaTag($conn);
                header("Location: manage_tags.php?pesan=tag_diedit");
                exit;
            } else {
                $error = "Gagal mengubah tag.";
            }
            $stmt->close();
        }
        $stmt->close();
    }
}
        // Tampilkan pesan sukses dari redirect
    if (isset($_GET['pesan']) && $_GET['pesan'] === 'tag_diedit') {
        $success = '✅ Tag berhasil diubah!';
        // Reset edit_tag_id agar modal tidak muncul
        $edit_tag_id = 0;
    }


// Jika ada request edit via GET, ambil data tag untuk modal
if (isset($_GET['edit']) && isset($_GET['token'])) {
    $edit_tag_id = (int)$_GET['edit'];
    $token = $_GET['token'] ?? '';
    if ($edit_tag_id > 0 && hash_equals($_SESSION['csrf_token'], $token)) {
        $stmt = $conn->prepare("SELECT nama_tag FROM tags WHERE id = ?");
        $stmt->bind_param("i", $edit_tag_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $edit_tag_name = $row['nama_tag'];
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Tag - Personal Resource Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
    <div class="app-container">
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
                <a href="manage_tags.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_tags.php' ? 'active' : '' ?>">🏷️ Kelola Tag</a>
                <a href="activity.php" class="nav-link">🕐 Aktivitas</a>
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
                        <a href="settings.php#tentang-aplikasi" class="dropdown-item">❗ Info Lebih Lanjut</a>
                        <a href="logout.php" class="dropdown-item danger" title="Keluar dari akun">🚪 Logout</a>
                    </div>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <h1 class="content-title">🏷️ Kelola Tag</h1>
            <p class="page-description">Lihat semua tag yang tersedia. Edit nama tag atau hapus tag yang tidak terpakai.</p>

            <?php if ($error !== ''): ?>
                <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if ($success !== ''): ?>
                <div id="notif-success" style="background:#d4edda; color:#155724; padding:12px 16px; border-radius:8px; margin-bottom:15px;">
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <section class="form-container" style="max-width:700px;">
                <?php if (count($all_tags) > 0): ?>
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="border-bottom:2px solid var(--border-color);">
                                <th style="text-align:left; padding:10px 0;">#</th>
                                <th style="text-align:left; padding:10px 0;">Nama Tag</th>
                                <th style="text-align:right; padding:10px 0;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_tags as $index => $tag): ?>
                                <?php 
                                    $cek = $conn->prepare("SELECT COUNT(*) FROM resource_tag WHERE tag_id = ?");
                                    $cek->bind_param("i", $tag['id']);
                                    $cek->execute();
                                    $cek->bind_result($used_count);
                                    $cek->fetch();
                                    $cek->close();
                                ?>
                                <tr style="border-bottom:1px solid var(--border-color);">
                                    <td style="padding:10px 0;"><?= $index + 1 ?></td>
                                    <td style="padding:10px 0;">
                                        #<?= htmlspecialchars($tag['nama_tag'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php if ($used_count > 0): ?>
                                            <span style="font-size:0.7rem; color:var(--text-muted); background:var(--bg-soft); padding:2px 8px; border-radius:10px;">
                                                dipakai <?= $used_count ?> resource
                                            </span>
                                        <?php else: ?>
                                            <span style="font-size:0.7rem; color:#2ecc71; background:#d4edda; padding:2px 8px; border-radius:10px;">
                                                tidak terpakai
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:10px 0; text-align:right;">
                                        <!-- Tombol Edit -->
                                        <a href="manage_tags.php?edit=<?= $tag['id'] ?>&token=<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>" 
                                           style="color:var(--text-link); text-decoration:none; margin-right:12px;" 
                                           title="Edit tag ini">✏️</a>

                                        <!-- Tombol Hapus (hanya jika tidak terpakai) -->
                                        <?php if ($used_count == 0): ?>
                                        <a href="javascript:void(0)"
                                           onclick="confirmDeleteTagPermanen(<?= $tag['id'] ?>, '<?= htmlspecialchars($tag['nama_tag'], ENT_QUOTES) ?>')"
                                           style="color:var(--text-danger); text-decoration:none; font-weight:bold;">
                                           Hapus
                                        </a>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted); font-size:0.8rem;">🔒 Terpakai</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color:var(--text-muted);">Belum ada tag yang dibuat.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- ===== MODAL EDIT TAG ===== -->
    <?php if ($edit_tag_id > 0): ?>
    <div style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; display:flex; align-items:center; justify-content:center; padding:20px;" onclick="if(event.target===this) window.location='manage_tags.php'">
        <div style="background:var(--bg-card); border-radius:var(--radius-lg); padding:30px; max-width:400px; width:100%; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <h2 style="margin-bottom:15px; font-size:1.3rem;">✏️ Edit Tag</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="edit_tag" value="1">
                <input type="hidden" name="tag_id" value="<?= $edit_tag_id ?>">
                <div class="form-group">
                    <label for="edit-nama-tag">Nama Tag</label>
                    <input type="text" id="edit-nama-tag" name="nama_tag" value="<?= htmlspecialchars($edit_tag_name, ENT_QUOTES, 'UTF-8') ?>" required autofocus>
                </div>
                <div class="form-actions" style="margin-top:20px; gap:10px;">
                    <button type="submit" style="background:var(--primary); color:#fff; padding:10px 24px; border-radius:var(--radius-md); cursor:pointer; border:none; font-weight:600;">
                        Simpan
                    </button>
                    <a href="manage_tags.php" style="padding:10px 24px; border-radius:var(--radius-md); background:var(--bg-soft); color:var(--text-primary); text-decoration:none; font-weight:600;">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Auto-hide notifikasi sukses setelah 3 detik
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