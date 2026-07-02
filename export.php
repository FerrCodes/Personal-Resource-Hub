<?php
require_once __DIR__ . '/config/database.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil semua resource user
$stmt = $conn->prepare("SELECT judul, url, deskripsi, jenis, status, rating, catatan_pribadi, created_at FROM resources WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Set header CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="resource_export.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Judul', 'URL', 'Deskripsi', 'Jenis', 'Status', 'Rating', 'Catatan Pribadi', 'Tanggal Ditambahkan']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['judul'],
        $row['url'],
        $row['deskripsi'],
        $row['jenis'],
        $row['status'],
        $row['rating'] ?? '',
        $row['catatan_pribadi'],
        $row['created_at']
    ]);
}
fclose($output);
exit;