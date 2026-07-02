<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/fungsi.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Validasi keamanan gagal.');
    }

    $login = trim(strip_tags($_POST['login'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($login === '' || $password === '') {
        $error = 'Email/Username dan password wajib diisi.';
    } else {
        // Query dengan kolom is_verified dan is_allowed
        $stmt = $conn->prepare("SELECT id, username, password, is_verified, is_allowed FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            if ((int)$user['is_verified'] === 0) {
                $error = 'Akun belum diverifikasi. Silakan cek email.';
            } elseif ((int)$user['is_allowed'] === 0) {
                header('Location: pending.php');
                exit;
            } else {
                // Login berhasil
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                logActivity($conn, $_SESSION['user_id'], 'login', 'Login berhasil');
                header('Location: index.php');
                exit;
            }
        } else {
            $error = 'Email/Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Resource Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
    <div class="app-container">
<main class="main-content" style="display:flex; justify-content:center; align-items:center; min-height:100vh; margin-left:0; gap:40px; flex-wrap:wrap; padding:20px;">
    <!-- Kolom Form -->
<section class="form-container" style="max-width:400px; width:100%; flex:1 1 300px;">
    <h1 class="content-title" style="text-align:center;">🔐 Login</h1>
    <?php if ($error !== ''): ?>
        <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-group">
            <label for="login">Email atau Username</label>
            <input type="text" id="login" name="login" required autofocus placeholder="contoh@email.com atau username">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <div class="password-wrapper">
                <input type="password" id="password" name="password" required>
                <button type="button" class="toggle-password" title="Tampilkan/Sembunyikan password">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
        <div class="form-actions" style="justify-content:center;">
            <button type="submit">Masuk</button>
        </div>
        <p style="text-align:center; margin-top:10px;">
            <a href="lupa_password.php" style="color:var(--text-muted); font-size:0.9rem;">Lupa password?</a>
        </p>
        <p style="text-align:center; margin-top:15px;">
            Belum punya akun? <a href="register.php" style="color:var(--accent);">Buat sekarang</a>
        </p>
    </form>
    <!-- TOMBOL DARK MODE -->
    <div style="text-align:center; margin-top:20px;">
        <button id="darkModeToggle" type="button" title="Sesuaikan kenyamanan"
                style="width:50%; max-width:300px; background:transparent; border:2px solid var(--border-color); border-radius:var(--radius-md); padding:7px 16px; cursor:pointer; color:var(--text-primary); transition:all 0.2s ease;">
            <span id="darkModeLabel">Switch Dark</span>
        </button>
    </div>
</section>
    <!-- Kolom Sambutan -->
    <div class="welcome-section" style="flex:1 1 300px; max-width:360px; text-align:center; padding:20px; color:var(--text-primary);">
        <h2 style="font-size:2rem; margin-bottom:15px;">👋 Selamat Datang!</h2>
        <p style="font-size:1.1rem; line-height:1.6;">- Lanjutkan progress Resource Hub mu.</p>
    </div>
</main>
    <script src="assets/js/main.js"></script>
</body>
</html>