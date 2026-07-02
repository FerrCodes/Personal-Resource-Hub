<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/fungsi.php';

// Debug: lihat apakah parameter GET ada
if (isset($_GET['pesan'])) {
    error_log('Pesan GET: ' . $_GET['pesan']);
}

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
   DATA AWAL FORM
   Mengambil semua tag untuk pilihan checkbox.
   ========================================================= */
$all_tags = ambilSemuaTag($conn);
$pesan = '';

/* =========================================================
   AMBIL DATA FORM DARI SESSION (jika ada)
   ========================================================= */
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']); // hapus setelah digunakan

// Ambil pesan error dari session
$pesan = '';
if (isset($_SESSION['form_error'])) {
    $pesan = $_SESSION['form_error'];
    unset($_SESSION['form_error']);
}

/* =========================================================
   HANDLER: TAMBAH TAG BARU (via tombol +)
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_tag'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Validasi keamanan gagal.');
    }

    $tag_baru = trim(strip_tags($_POST['tag_baru'] ?? ''));

    // Simpan data form ke session
    $_SESSION['form_data'] = [
        'judul' => $_POST['judul'] ?? '',
        'url' => $_POST['url'] ?? '',
        'deskripsi' => $_POST['deskripsi'] ?? '',
        'jenis' => $_POST['jenis'] ?? '',
        'status' => $_POST['status'] ?? 'belum_dibaca',
        'rating' => $_POST['rating'] ?? '',
        'catatan_pribadi' => $_POST['catatan_pribadi'] ?? '',
    ];

    if ($tag_baru === '') {
        $_SESSION['form_error'] = 'Masukkan nama tag terlebih dahulu.';
        header("Location: tambah.php");
        exit;
    }

    // ===== Perbaikan: Pecah koma dan buat banyak tag =====
    $daftar_tag = array_map('trim', explode(',', $tag_baru));
    $total_tag = 0;
    $error_tag = false;

    foreach ($daftar_tag as $nama) {
        $nama = trim(strip_tags($nama));
        if ($nama === '') continue;
        $tag_id = ambilAtauBuatTag($conn, $nama);
        if ($tag_id > 0) {
            $total_tag++;
        } else {
            $error_tag = true;
        }
    }

    if ($total_tag > 0) {
        // Sukses membuat minimal satu tag
        header("Location: tambah.php?pesan=tag_tambah");
        exit;
    } else {
        // Gagal semua
        $_SESSION['form_error'] = 'Gagal menambahkan tag. Periksa nama tag.';
        header("Location: tambah.php");
        exit;
    }
}

/* =========================================================
   PROSES SIMPAN RESOURCE BARU
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
            $stmt = $conn->prepare("INSERT INTO resources (judul, url, deskripsi, jenis, status, rating, catatan_pribadi, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssisi", $judul, $url, $deskripsi, $jenis, $status, $rating, $catatan, $_SESSION['user_id']);

        if ($stmt->execute()) {
            $resource_id = $stmt->insert_id;
            $stmt->close();

            // Simpan tag lama yang dipilih dari checkbox.
            simpanTagDanRelasi($conn, $resource_id, $tag_ids);

            // Simpan tag baru, pisahkan dengan koma.
            simpanTagBaruDariInput($conn, $resource_id, $_POST['tag_baru'] ?? '');

            // ===== TAMBAHKAN INI =====
            logActivity($conn, $_SESSION['user_id'], 'tambah_resource', "Menambahkan resource ID $resource_id - Judul: $judul");
            // =========================

            header('Location: index.php?pesan=tersimpan');
            exit;
        }

        $pesan = 'Gagal menyimpan data.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Resource - Personal Resource Hub</title>
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
             KONTEN UTAMA: FORM TAMBAH RESOURCE
             Semua tampilan form diatur melalui class CSS.
             ===================================================== -->
        <main class="main-content">
            <h1 class="content-title">➕ Tambah Resource Baru</h1>

    <section class="form-container" aria-label="Form tambah resource">

        <!-- ===== NOTIFIKASI SUKSES TAMBAH TAG ===== -->
        <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'tag_tambah'): ?>
            <div id="notif-success" style="background:#d4edda; color:#155724; padding:12px 16px; border-radius:8px; margin-bottom:15px;">
                ✅ Tag baru berhasil ditambahkan! Silakan centang di daftar tag.
            </div>
        <?php endif; ?>

        <!-- ===== NOTIFIKASI ERROR ===== -->
        <?php if ($pesan !== ''): ?>
            <div class="error"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

                <form method="POST" onsubmit="return prepareJenis();">
                    <!-- ===== Token keamanan CSRF ===== -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <!-- ===== Informasi utama resource ===== -->
                    <div class="form-group">
                        <label for="judul">Judul:</label>
                        <input type="text" id="judul" name="judul" required value="<?= htmlspecialchars($form_data['judul'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="form-group">
                        <label for="url">URL:</label>
                        <input type="url" id="url" name="url" required value="<?= htmlspecialchars($form_data['url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="form-group">
                        <label for="deskripsi">Deskripsi:</label>
                        <textarea id="deskripsi" name="deskripsi" rows="3"><?= htmlspecialchars($form_data['deskripsi'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <!-- ===== Kategori / jenis resource ===== -->
                    <div class="form-group">
                        <label for="jenis_select">Jenis:</label>
                        <select id="jenis_select" onchange="toggleJenisLainnya()">
                            <option value="video">Video</option>
                            <option value="musik">Musik</option>
                            <option value="artikel">Artikel</option>
                            <option value="dokumentasi">Dokumentasi</option>
                            <option value="github">GitHub</option>
                            <option value="lainnya">Lainnya (isi sendiri)</option>
                        </select>

                        <div id="jenis_lainnya_container" class="custom-field">
                            <input type="text" id="jenis_lainnya" placeholder="Masukkan jenis, contoh: Podcast atau E-book" value="<?= htmlspecialchars($form_data['jenis'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <input type="hidden" name="jenis" id="jenis_hidden">
                    </div>

                    <!-- ===== Progress belajar ===== -->
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select id="status" name="status">
                            <option value="belum_dibaca">Belum Dibaca</option>
                            <option value="sedang_dipelajari">Sedang Dipelajari</option>
                            <option value="selesai">Selesai</option>
                            <option value="arsip">Arsip</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="rating">Rating (1-10, opsional):</label>
                        <input type="number" id="rating" name="rating" min="1" max="10" value="<?= htmlspecialchars($form_data['rating'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="form-group">
                        <label for="catatan_pribadi">Catatan Pribadi:</label>
                        <textarea id="catatan_pribadi" name="catatan_pribadi" rows="3"><?= htmlspecialchars($form_data['catatan_pribadi'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
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
                        <div class="checkbox-group">
                            <?php foreach ($all_tags as $tag): ?>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="tag_ids[]" value="<?= (int)$tag['id'] ?>">
                                    <?= htmlspecialchars($tag['nama_tag'], ENT_QUOTES, 'UTF-8') ?>
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
                    </div>

                    <!-- ===== Tombol aksi form ===== -->
                    <div class="form-actions">
                        <button type="submit">Simpan</button>
                        <a href="index.php" class="btn-cancel">Batal</a>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <script>
    // Notifikasi sukses hilang otomatis setelah 3 detik
    document.addEventListener('DOMContentLoaded', function() {
        var notif = document.getElementById('notif-success');
        if (notif) {
            setTimeout(function() {
                notif.style.transition = 'opacity 0.5s';
                notif.style.opacity = '0';
                setTimeout(function() {
                    notif.style.display = 'none';
                }, 500);
            }, 3000); // 3 detik
        }
    });
</script>

    <script src="assets/js/main.js"></script>
</body>
</html>