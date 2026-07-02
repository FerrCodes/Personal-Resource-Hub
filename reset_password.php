<?php
require_once __DIR__ . '/config/database.php';

// Jika sudah login, arahkan ke index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if ($token === '') {
    die('Token tidak ditemukan.');
}

// Cek validitas token
$stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die('Token tidak valid atau sudah kadaluarsa. Silakan <a href="lupa_password.php">minta ulang</a>.');
}

// Proses reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Validasi keamanan gagal.');
    }

    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $password_confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $update->bind_param("si", $hashed, $user['id']);
        if ($update->execute()) {
            $success = 'Password berhasil direset! Silakan <a href="login.php">login</a>.';
        } else {
            $error = 'Gagal mereset password. Coba lagi.';
        }
        $update->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Resource Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <main class="main-content" style="display:flex; justify-content:center; align-items:center; min-height:100vh; margin-left:0; padding:20px;">
            <section class="form-container" style="max-width:400px; width:100%;">
                <h1 class="content-title" style="text-align:center;">🔐 Reset Password</h1>

                <?php if ($error !== ''): ?>
                    <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($success !== ''): ?>
                    <div style="background:#d4edda; color:#155724; padding:12px 16px; border-radius:8px; margin-bottom:15px;">
                        <?= $success ?>
                    </div>
                <?php endif; ?>

                <?php if (!$success): ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-group">
                        <label for="password">Password Baru (min 6 karakter)</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="password_confirm">Konfirmasi Password Baru</label>
                        <input type="password" id="password_confirm" name="password_confirm" required>
                    </div>
                    <div class="form-actions" style="justify-content:center;">
                        <button type="submit">Reset Password</button>
                    </div>
                </form>
                <?php endif; ?>

                <p style="text-align:center; margin-top:15px;">
                    <a href="login.php" style="color:var(--accent);">Kembali ke Login</a>
                </p>
            </section>
        </main>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>