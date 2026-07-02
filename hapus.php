<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/fungsi.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ===== AMBIL ID & TOKEN DARI URL =====
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = $_GET['token'] ?? '';

// ===== VALIDASI TOKEN CSRF =====
if ($id <= 0 || !hash_equals($_SESSION['csrf_token'], $token)) {
    die('Akses ditolak: validasi token keamanan gagal.');
}

// ===== AMBIL JUDUL SEBELUM HAPUS (untuk log) =====
$stmt = $conn->prepare("SELECT judul FROM resources WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$judul = $row['judul'] ?? 'ID ' . $id;
$stmt->close();

// ===== HAPUS RESOURCE =====
$stmt = $conn->prepare("DELETE FROM resources WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();
$stmt->close();

// ===== LOG AKTIVITAS =====
logActivity($conn, $_SESSION['user_id'], 'hapus_resource', "Menghapus resource: $judul");

// ===== REDIRECT =====
header('Location: index.php?pesan=dihapus');
exit;
?>