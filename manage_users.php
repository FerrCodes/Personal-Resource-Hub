<?php
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Cek apakah user adalah admin (hanya Feri yang boleh)
if ($_SESSION['user_id'] != 3) {
    die('Akses ditolak. Anda bukan orang yang berwenang untuk mengakses halaman ini.');
}

// Ambil daftar user
$users = $conn->query("SELECT id, username, email, is_allowed FROM users ORDER BY id DESC");

// Proses toggle status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_user'])) {
    $user_id = (int)$_POST['user_id'];
    $status = (int)$_POST['status'];
    $stmt = $conn->prepare("UPDATE users SET is_allowed = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $user_id);
    $stmt->execute();
    header('Location: manage_users.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Personal Resource Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <main class="main-content" style="margin-left:0; padding:40px; max-width:800px;">
            <h1 class="content-title">👥 Kelola User</h1>
            <p class="page-description">Izinkan atau blokir akses user ke aplikasi.</p>

        <div class="manage-users-wrapper">
            <table style="width:100%; border-collapse:collapse; margin-top:20px;">
                <thead>
                    <tr style="border-bottom:2px solid var(--border-color);">
                        <th style="text-align:left; padding:10px 0;">ID</th>
                        <th style="text-align:left; padding:10px 0;">Username</th>
                        <th style="text-align:left; padding:10px 0;">Email</th>
                        <th style="text-align:left; padding:10px 0;">Status</th>
                        <th style="text-align:left; padding:10px 0;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $users->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid var(--border-color);">
                        <td style="padding:10px 0;"><?= $row['id'] ?></td>
                        <td style="padding:10px 0;"><?= htmlspecialchars($row['username']) ?></td>
                        <td style="padding:10px 0;"><?= htmlspecialchars($row['email']) ?></td>
                        <td style="padding:10px 0;">
                            <?= $row['is_allowed'] ? '✅ Diizinkan' : '❌ Diblokir' ?>
                        </td>
                        <td style="padding:10px 0;">
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="status" value="<?= $row['is_allowed'] ? 0 : 1 ?>">
                                <button type="submit" name="toggle_user" 
                                        style="background:<?= $row['is_allowed'] ? 'var(--text-danger)' : 'var(--success)' ?>; 
                                               color:#fff; border:none; padding:5px 12px; border-radius:6px; cursor:pointer;">
                                    <?= $row['is_allowed'] ? '🔒 Blokir' : '🔓 Izinkan' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

            <p style="margin-top:20px;"><a href="index.php" style="color:var(--accent);">← Kembali</a></p>
        </main>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>