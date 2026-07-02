<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/fungsi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = $_GET['token'] ?? '';

if ($id <= 0 || !hash_equals($_SESSION['csrf_token'], $token)) {
    die('Akses ditolak: validasi token keamanan gagal.');
}

// Ambil data resource (judul dan status favorit)
$stmt = $conn->prepare("SELECT judul, is_favorite FROM resources WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if ($row) {
    $judul = $row['judul'];
    $status_baru = (int)$row['is_favorite'] === 1 ? 0 : 1;

    $update = $conn->prepare("UPDATE resources SET is_favorite = ? WHERE id = ? AND user_id = ?");
    $update->bind_param("iii", $status_baru, $id, $_SESSION['user_id']);
    $update->execute();
    $update->close();

    // ===== LOG AKTIVITAS DENGAN NAMA RESOURCE =====
    $detail = $status_baru == 1 
        ? "Menambahkan pin pada resource: $judul" 
        : "Melepas pin dari resource: $judul";
    logActivity($conn, $_SESSION['user_id'], 'pin_resource', $detail);
}

header('Location: index.php');
exit;
?>