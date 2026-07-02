<?php
require_once __DIR__ . '/config/database.php';

$token = $_GET['token'] ?? '';

if ($token === '') {
    die('Token tidak ditemukan.');
}

// Cari user dengan token tersebut
$stmt = $conn->prepare("SELECT id FROM users WHERE verification_token = ? AND is_verified = 0");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user) {
    // Update is_verified = 1 dan hapus token
    $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
    $update->bind_param("i", $user['id']);
    $update->execute();
    $update->close();

    echo "<div style='font-family:sans-serif; padding:20px; text-align:center;'>
            <h2>✅ Verifikasi Berhasil!</h2>
            <p>Akun kamu sudah aktif. Silakan <a href='login.php'>Login</a>.</p>
          </div>";
} else {
    echo "<div style='font-family:sans-serif; padding:20px; text-align:center; color:#dc3545;'>
            <h2>❌ Verifikasi Gagal</h2>
            <p>Token tidak valid atau akun sudah diverifikasi.</p>
          </div>";
}
?>