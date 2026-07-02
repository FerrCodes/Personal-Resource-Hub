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
   AMBIL RESOURCE YANG AKAN DIEDIT
   ID wajib berupa angka.
   ========================================================= */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

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

/* =========================================================
   DATA FORM
   Tag tersedia, tag terpilih, dan pengecekan jenis custom.
   ========================================================= */
$all_tags = ambilSemuaTag($conn);
$selected_tags = ambilTagResource($conn, $id);

$jenis_standar = ['video', 'artikel', 'dokumentasi', 'github'];
$jenis_sekarang = $resource['jenis'];
$is_jenis_custom = !in_array($jenis_sekarang, $jenis_standar, true);
$jenis_custom_value = $is_jenis_custom ? $jenis_sekarang : '';

$pesan = '';

/* =========================================================
   HANDLER: HAPUS TAG (via link ×)
   ========================================================= */
if (isset($_GET['hapus_tag']) && isset($_GET['token'])) {
    $hapus_tag_id = (int)$_GET['hapus_tag'];
    $token = $_GET['token'] ?? '';

    if ($id > 0 && $hapus_tag_id > 0 && hash_equals($_SESSION['csrf_token'], $token)) {
        $stmt = $conn->prepare("DELETE FROM resource_tag WHERE resource_id = ? AND tag_id = ?");
        $stmt->bind_param("ii", $id, $hapus_tag_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                // Berhasil hapus
                header("Location: edit.php?id=$id&pesan=tag_dihapus");
                exit;
            } else {
                // Tidak ada baris yang terhapus (mungkin relasi sudah tidak ada)
                $pesan = 'Tag tidak ditemukan atau sudah dihapus.';
            }
        } else {
            $pesan = 'Gagal menghapus tag: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $pesan = 'Token tidak valid.';
    }
}

/* =========================================================
   HANDLER: TAMBAH TAG BARU (via tombol Tambah Tag)
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_tag'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Validasi keamanan gagal.');
    }

    $tag_baru = trim(strip_tags($_POST['tag_baru'] ?? ''));
    
    if ($tag_baru !== '') {
        // Simpan tag baru (fungsi sudah ada di fungsi.php)
        simpanTagBaruDariInput($conn, $id, $tag_baru);
        
        header("Location: edit.php?id=$id&pesan=tag_tambah");
        exit;
    } else {
        $pesan = 'Masukkan nama tag terlebih dahulu.';
    }
}

/* =========================================================
   PROSES UPDATE RESOURCE
   Form memakai token CSRF dan prepared statement.
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Validasi keamanan gagal. Silakan muat ulang halaman.');
    }

    $judul = trim(strip_tags($_POST['judul'] ?? ''));
    $deskripsi = trim(strip_tags($_POST['deskripsi'] ?? ''));
    $catatan = trim(strip_tags($_POST['catatan_pribadi'] ?? ''));
    $jenis = trim(strip_tags($_POST['jenis'] ?? ''));
    $status = $_POST['status'] ?? 'belum_dibaca';
    $rating = $_POST['rating'] !== '' ? (int)$_POST['rating'] : null;
    $tag_ids = $_POST['tag_ids'] ?? [];

    $url = filter_var($_POST['url'] ?? '', FILTER_SANITIZE_URL);

    if ($judul === '') {
        $pesan = 'Judul wajib diisi.';
    } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
        $pesan = 'Format URL tidak valid.';
    } elseif ($jenis === '') {
        $pesan = 'Jenis resource wajib diisi.';
    } elseif ($rating !== null && ($rating < 1 || $rating > 10)) {
        $pesan = 'Rating harus berada di antara 1 sampai 10.';
    } else {
            $stmt = $conn->prepare("UPDATE resources SET judul = ?, url = ?, deskripsi = ?, jenis = ?, status = ?, rating = ?, catatan_pribadi = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sssssisii", $judul, $url, $deskripsi, $jenis, $status, $rating, $catatan, $id, $_SESSION['user_id']);

        if ($stmt->execute()) {
            $stmt->close();

            // Update relasi tag dari checkbox.
            simpanTagDanRelasi($conn, $id, $tag_ids);

            // Simpan tag baru, pisahkan dengan koma.
            simpanTagBaruDariInput($conn, $id, $_POST['tag_baru'] ?? '');

            // ===== TAMBAHKAN INI =====
            logActivity($conn, $_SESSION['user_id'], 'edit_resource', "Mengupdate resource ID $id - Judul: $judul");
            // =========================

            header('Location: index.php?pesan=diupdate');
            exit;
        }

        $pesan = 'Gagal mengupdate: ' . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resource - Personal Resource Hub</title>
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
             KONTEN UTAMA: FORM EDIT RESOURCE
             Semua tampilan form diatur melalui class CSS.
             ===================================================== -->
        <main class="main-content">
            <h1 class="content-title">✏️ Edit Resource</h1>

            <section class="form-container" aria-label="Form edit resource">

                <?php if (isset($_GET['pesan'])): ?>
                <?php if ($_GET['pesan'] === 'tag_dihapus'): ?>
                    <div id="notif-delete" style="background:#ffe4e6; color:#dc3545; padding:12px 16px; border-radius:8px; margin-bottom:15px;">
                        ✅ Tag berhasil dihapus dari resource.
                    </div>
                <?php elseif ($_GET['pesan'] === 'tag_tambah'): ?>
                    <div id="notif-success" style="background:#d4edda; color:#155724; padding:12px 16px; border-radius:8px; margin-bottom:15px;">
                        ✅ Tag baru berhasil ditambahkan!
                    </div>
                        <?php endif; ?>
                    <?php endif; ?>

                <?php if ($pesan !== ''): ?>
                    <div class="error"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="POST" onsubmit="return prepareJenis();">
                    <!-- ===== Token keamanan CSRF ===== -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <!-- ===== Informasi utama resource ===== -->
                    <div class="form-group">
                        <label for="judul">Judul:</label>
                        <input type="text" id="judul" name="judul" value="<?= htmlspecialchars($resource['judul'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="url">URL:</label>
                        <input type="url" id="url" name="url" value="<?= htmlspecialchars($resource['url'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="deskripsi">Deskripsi:</label>
                        <textarea id="deskripsi" name="deskripsi" rows="3"><?= htmlspecialchars($resource['deskripsi'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <!-- ===== Kategori / jenis resource ===== -->
                    <div class="form-group">
                        <label for="jenis_select">Jenis:</label>
                        <select id="jenis_select" onchange="toggleJenisLainnya()">
                            <option value="video" <?= $jenis_sekarang === 'video' ? 'selected' : '' ?>>Video</option>
                            <option value="musik" <?= $jenis_sekarang === 'musik' ? 'selected' : '' ?>>Musik</option>
                            <option value="artikel" <?= $jenis_sekarang === 'artikel' ? 'selected' : '' ?>>Artikel</option>
                            <option value="dokumentasi" <?= $jenis_sekarang === 'dokumentasi' ? 'selected' : '' ?>>Dokumentasi</option>
                            <option value="github" <?= $jenis_sekarang === 'github' ? 'selected' : '' ?>>GitHub</option>
                            <option value="lainnya" <?= $is_jenis_custom ? 'selected' : '' ?>>Lainnya (isi sendiri)</option>
                        </select>

                        <div id="jenis_lainnya_container" class="custom-field <?= $is_jenis_custom ? 'is-visible' : '' ?>">
                            <input type="text" id="jenis_lainnya" placeholder="Masukkan jenis, contoh: Podcast atau E-book" value="<?= htmlspecialchars($jenis_custom_value, ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <input type="hidden" name="jenis" id="jenis_hidden">
                    </div>

                    <!-- ===== Progress belajar ===== -->
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select id="status" name="status">
                            <option value="belum_dibaca" <?= $resource['status'] === 'belum_dibaca' ? 'selected' : '' ?>>Belum Dibaca/Belum selesai</option>
                            <option value="sedang_dipelajari" <?= $resource['status'] === 'sedang_dipelajari' ? 'selected' : '' ?>>Sedang Dipelajari</option>
                            <option value="selesai" <?= $resource['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                            <option value="arsip" <?= $resource['status'] === 'arsip' ? 'selected' : '' ?>>Arsip</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="rating">Rating (1-10, opsional):</label>
                        <input type="number" id="rating" name="rating" min="1" max="10" value="<?= htmlspecialchars((string)$resource['rating'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="form-group">
                        <label for="catatan_pribadi">Catatan Pribadi:</label>
                        <textarea id="catatan_pribadi" name="catatan_pribadi" rows="3"><?= htmlspecialchars($resource['catatan_pribadi'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <!-- ===== Tag resource ===== -->
                    <div class="form-group">
                    <span class="form-label" style="display:flex; align-items:center; gap:8px;">
                        🏷️ Tag
                        <span class="tag-info-icon" data-tooltip="Jika melihat Tag yang sudah ada dihalaman ini, 
                        itu artinya sudah tersimpan di Database, jadi akan muncul saat ingin mengedit atau menambah Tag di halaman ini.
                        Kalau ingin mengelola tag, di sidebar, Kelola Tag." tabindex="0">
                            ⓘ
                        </span>
                    </span>
                        <div class="checkbox-group">
                            <?php foreach ($all_tags as $tag): ?>
                                <?php $tag_id = (int)$tag['id']; ?>
                                <label class="checkbox-item" style="display:inline-flex; align-items:center; gap:6px;">
                                    <input type="checkbox" name="tag_ids[]" value="<?= $tag_id ?>" <?= in_array($tag_id, $selected_tags, true) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($tag['nama_tag'], ENT_QUOTES, 'UTF-8') ?>

                                    <!-- Tombol Hapus Tag (hanya untuk tag yang sedang dipilih) -->
                                    <?php if (in_array($tag_id, $selected_tags, true)): ?>
                                    <a href="javascript:void(0)" 
                                       style="color:var(--text-danger); text-decoration:none; font-size:0.8rem; font-weight:bold;" 
                                       onclick="confirmDeleteTagFromResource(<?= $id ?>, <?= $tag_id ?>, '<?= htmlspecialchars($tag['nama_tag'], ENT_QUOTES) ?>')"
                                       title="Hapus tag ini">×</a>
                                    <?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                                    
                    <!-- ===== Tambah Tag Baru ===== -->
                    <div class="form-group">
                        <label for="tag_baru">Atau tambah tag baru:</label>
                        <div style="display:flex; gap:10px; flex-wrap:wrap;">
                            <input type="text" id="tag_baru" name="tag_baru" placeholder="Contoh: musik" style="flex:1; min-width:150px;">
                            <button type="submit" name="tambah_tag" value="1" class="btn-primary" style="background:var(--accent); padding:10px 20px;">
                                ➕ Tambah Tag
                            </button>
                        </div>
                        <small style="color:var(--text-muted); font-size:0.8rem;">Pisahkan beberapa tag dengan koma (contoh: musik, novel, dokumentasi).
                            *Jika ingin rapi bisa menambahkan Tag satu per satu dan simpan perubahan dengan tombol disebelah isi form.</small>
                    </div>

                    <!-- ===== Tombol aksi form ===== -->
                    <div class="form-actions">
                        <button type="submit">Simpan Perubahan</button>
                        <a href="index.php" class="btn-cancel">Batal</a>
                    </div>
                </form>
            </section>
        </main>
    </div>



    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Notifikasi sukses tambah tag hilang otomatis
        var notifSuccess = document.getElementById('notif-success');
        if (notifSuccess) {
            setTimeout(function() {
                notifSuccess.style.transition = 'opacity 0.5s';
                notifSuccess.style.opacity = '0';
                setTimeout(function() {
                    notifSuccess.style.display = 'none';
                }, 500);
            }, 3000);
        }

        // Notifikasi hapus tag hilang otomatis (3 detik)
        var notifDelete = document.getElementById('notif-delete');
        if (notifDelete) {
            setTimeout(function() {
                notifDelete.style.transition = 'opacity 0.5s';
                notifDelete.style.opacity = '0';
                setTimeout(function() {
                    notifDelete.style.display = 'none';
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