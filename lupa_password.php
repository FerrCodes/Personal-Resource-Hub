<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/fungsi.php'; // <-- pastikan ini ada

// Jika sudah login, arahkan ke index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Validasi keamanan gagal.');
    }

    $email = trim(strip_tags($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        // Cek apakah email terdaftar
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            // Buat token unik
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Simpan token dan expired ke database
            $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $update->bind_param("ssi", $token, $expires, $user['id']);
            $update->execute();
            $update->close();

            // Buat link reset (gunakan base_url agar fleksibel untuk hosting)
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            $base_url = $protocol . $host . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
            $reset_link = $base_url . "reset_password.php?token=" . $token;

            // Siapkan body email
            $email_body = "
                <h2>Reset Password</h2>
                <p>Kami menerima permintaan untuk mereset password akun <strong>Resource Hub</strong> kamu.</p>
                <p>Klik link di bawah ini untuk membuat password baru:</p>
                <p><a href='$reset_link' style='background:#e94560; color:#fff; padding:10px 20px; text-decoration:none; border-radius:5px;'>Reset Password</a></p>
                <p>Atau copy link ini ke browser: <br> <code>$reset_link</code></p>
                <p>Link ini berlaku 1 jam.</p>
                <p>Jika kamu tidak meminta reset password, abaikan email ini.</p>
            ";

            $email_sent = kirimEmail($email, $user['username'], 'Reset Password - Resource Hub', $email_body);

            if ($email_sent) {
                $success = '✅ Link reset password telah dikirim ke email kamu. Cek inbox atau spam!';
            } else {
                // Fallback jika email gagal
                $success = "⚠️ Gagal mengirim email. Gunakan link ini untuk reset: <br> <a href='$reset_link'>$reset_link</a>";
            }
        } else {
            // Tetap tampilkan pesan sukses agar tidak terjadi email enumeration
            $success = "Jika email terdaftar, link reset akan dikirim.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Resource Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <main class="main-content" style="display:flex; justify-content:center; align-items:center; min-height:100vh; margin-left:0; padding:20px;">
            <section class="form-container" style="max-width:400px; width:100%;">
                <h1 class="content-title" style="text-align:center;">🔑 Lupa Password</h1>
                <p style="text-align:center; color:var(--text-muted); margin-bottom:20px;">Masukkan email kamu, kami akan kirim link reset.</p>

                <?php if ($error !== ''): ?>
                    <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($success !== ''): ?>
                    <div style="background:#d4edda; color:#155724; padding:12px 16px; border-radius:8px; margin-bottom:15px; word-break:break-all;">
                        <?= $success ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-group">
                        <label for="email">Alamat Email</label>
                        <input type="email" id="email" name="email" required autofocus placeholder="contoh@email.com">
                    </div>
                    <div class="form-actions" style="justify-content:center;">
                        <button type="submit">Kirim Link Reset</button>
                    </div>
                    <p style="text-align:center; margin-top:15px;">
                        <a href="login.php" style="color:var(--accent);">Kembali ke Login</a>
                    </p>
                </form>
            </section>
        </main>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>