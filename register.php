<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/fungsi.php';

// Jika sudah login, arahkan ke index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Validasi keamanan gagal.');
    }

    $username = trim(strip_tags($_POST['username'] ?? ''));
    $email = trim(strip_tags($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validasi dasar
    if (strlen($username) < 3) {
        $error = 'Username minimal 3 karakter.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $password_confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Validasi email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } else {
            $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $checkEmail->bind_param("s", $email);
            $checkEmail->execute();
            $checkEmail->store_result();
            if ($checkEmail->num_rows > 0) {
                $error = 'Email sudah terdaftar.';
            }
            $checkEmail->close();
        }

        // Jika tidak ada error, lanjutkan ke cek username dan insert
        if (empty($error)) {
            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check->bind_param("s", $username);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = 'Username sudah terdaftar.';
            } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $token = bin2hex(random_bytes(32)); // Token unik
                    $insert = $conn->prepare("INSERT INTO users (username, email, password, verification_token) VALUES (?, ?, ?, ?)");
                    $insert->bind_param("ssss", $username, $email, $hashed, $token);
            if ($insert->execute()) {
                $user_id = $insert->insert_id;

                // Buat link verifikasi
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'];
                $base_url = $protocol . $host . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
                $verification_link = $base_url . "verify.php?token=" . $token;

                $email_body = "
                    <p>Halo <strong>$username</strong>,</p>
                    <p>Terima kasih telah mendaftar di <strong>Personal Resource Hub</strong>.</p>
                    <p>Untuk mengaktifkan akun, silakan klik tombol di bawah ini:</p>
                    <p style='text-align:center;'>
                        <a href='$verification_link' style='background:#e94560; color:#fff; padding:12px 24px; text-decoration:none; border-radius:6px; display:inline-block;'>
                            Verifikasi Akun
                        </a>
                    </p>
                    <p>Atau copy link ini ke browser: <br> <code>$verification_link</code></p>
                    <p>Link ini berlaku 1 jam.</p>
                    <p>Salam,<br><strong>Personal Resource Hub</strong></p>
                ";

                $email_sent = kirimEmail($email, $username, 'Verifikasi Akun - Resource Hub', $email_body);

            if ($email_sent) {
                $success = '✅ Pendaftaran berhasil! <br> 
                            <strong>Akun Anda sedang menunggu aktivasi oleh admin.</strong><br>
                            Silakan tunggu konfirmasi via email.';
            } else {
                // fallback
                $success = "✅ Pendaftaran berhasil! <br> 
                            <strong>⚠️ Email gagal terkirim.</strong><br>
                            Akun Anda sedang menunggu aktivasi oleh admin.";
            }
            } else {
                $error = 'Gagal membuat akun. Coba lagi.';
            }
                $insert->close();
            }
            $check->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Akun - Resource Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <main class="main-content" style="display:flex; justify-content:center; align-items:center; min-height:100vh; margin-left:0; gap:40px; flex-wrap:wrap; padding:20px;">
            <!-- Kolom Form -->
            <section class="form-container" style="max-width:400px; width:100%; flex:1 1 300px;">
                <h1 class="content-title" style="text-align:center;">📝 Buat Akun</h1>
                <?php if ($error !== ''): ?>
                    <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($success !== ''): ?>
                    <div style="background:#d4edda; color:#155724; padding:12px 16px; border-radius:8px; margin-bottom:15px;">
                        <?= $success ?>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="email">Alamat Email</label>
                        <input type="email" id="email" name="email" required placeholder="contoh@email.com">
                    </div>
                    <div class="form-group">
                        <label for="password">Password (min 6 karakter)</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" required>
                            <button type="button" class="toggle-password" title="Tampilkan/Sembunyikan password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password_confirm">Konfirmasi Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password_confirm" name="password_confirm" required>
                            <button type="button" class="toggle-password" title="Tampilkan/Sembunyikan password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-actions" style="justify-content:center;">
                        <button type="submit">Buat Akun</button>
                    </div>
                    <p style="text-align:center; margin-top:15px;">
                        Sudah punya akun? <a href="login.php" style="color:var(--accent);">Login di sini</a>
                    </p>
                </form>
                    <!-- TOMBOL DARK MODE -->
                <div style="text-align:center; margin-top:20px;">
                <button id="darkModeToggle" type="button" title="Sesuaikan kenyamanan"
                        style="width:50%; max-width:300px; background:transparent; border:2px solid var(--border-color); border-radius:var(--radius-md); padding:7px 16px; cursor:pointer; color:var(--text-primary); transition:all 0.2s ease;">
                    <span id="darkModeLabel">Switch Light</span>
                </button>   
            </section>

            <!-- Kolom Sambutan -->
            <div class="welcome-section" style="flex:1 1 300px; max-width:360px; text-align:center; padding:20px; color:var(--text-primary);">
                <h2 style="font-size:2rem; margin-bottom:15px;">🚀 Mulai Sekarang!</h2>
                <p style="font-size:1.1rem; line-height:1.6;">Buat akun, kelola dan atur semua resource favoritmu yang telah kamu simpan.
                    Lihat semua progress, beri rating, dokumentasi dan fitur lainnya.</p>
            </div>
        </main>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>