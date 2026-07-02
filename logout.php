<?php
session_start();
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    // Simpan log sebelum session dihancurkan
    require_once __DIR__ . '/fungsi.php';
    logActivity($conn, $user_id, 'logout', 'Logout berhasil');
}
 // ===== TAMBAHKAN INI =====
 logActivity($conn, $_SESSION['user_id'], 'logout', 'Logout berhasil');
 // =========================
session_unset();
session_destroy();
header('Location: login.php');
exit;