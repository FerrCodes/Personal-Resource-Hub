<?php
require_once __DIR__ . '/config/database.php';

/* =========================================================
   FUNGSI TAG
   Kumpulan fungsi untuk mengambil, membuat, dan menghubungkan tag.
   ========================================================= */
function ambilSemuaTag(mysqli $conn): array
{
    $result = $conn->query("SELECT * FROM tags ORDER BY nama_tag ASC");

    if (!$result) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

function ambilTagResource(mysqli $conn, int $resource_id): array
{
    $stmt = $conn->prepare("SELECT tag_id FROM resource_tag WHERE resource_id = ?");
    $stmt->bind_param("i", $resource_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $tag_ids = [];

    while ($row = $result->fetch_assoc()) {
        $tag_ids[] = (int)$row['tag_id'];
    }

    $stmt->close();
    return $tag_ids;
}

function ambilDetailTagResource(mysqli $conn, int $resource_id): array
{
    $stmt = $conn->prepare("
        SELECT t.id, t.nama_tag
        FROM tags t
        JOIN resource_tag rt ON t.id = rt.tag_id
        WHERE rt.resource_id = ?
        ORDER BY t.nama_tag ASC
    ");
    $stmt->bind_param("i", $resource_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $tags = [];

    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }

    $stmt->close();
    return $tags;
}

function simpanTagDanRelasi(mysqli $conn, int $resource_id, array $tag_ids): void
{
    // Hapus relasi lama agar hasil checkbox selalu sinkron.
    $stmt = $conn->prepare("DELETE FROM resource_tag WHERE resource_id = ?");
    $stmt->bind_param("i", $resource_id);
    $stmt->execute();
    $stmt->close();

    // Hindari duplikat dari input checkbox.
    $tag_ids = array_unique(array_map('intval', $tag_ids));

    foreach ($tag_ids as $tag_id) {
        if ($tag_id <= 0) continue;
        tambahkanRelasiTag($conn, $resource_id, $tag_id);
    }
}

function ambilAtauBuatTag(mysqli $conn, string $nama_tag): int
{
    $nama_tag = trim(strip_tags($nama_tag));

    if ($nama_tag === '') {
        return 0;
    }

    $stmt = $conn->prepare("SELECT id FROM tags WHERE nama_tag = ? LIMIT 1");
    $stmt->bind_param("s", $nama_tag);
    $stmt->execute();
    $stmt->bind_result($tag_id);

    if ($stmt->fetch()) {
        $stmt->close();
        return (int)$tag_id;
    }

    $stmt->close();

    $insert = $conn->prepare("INSERT INTO tags (nama_tag) VALUES (?)");
    $insert->bind_param("s", $nama_tag);
    $insert->execute();
    $tag_id_baru = (int)$insert->insert_id;
    $insert->close();

    return $tag_id_baru;
}

function tambahkanRelasiTag(mysqli $conn, int $resource_id, int $tag_id): void
{
    if ($resource_id <= 0 || $tag_id <= 0) {
        return;
    }

    $cek = $conn->prepare("SELECT 1 FROM resource_tag WHERE resource_id = ? AND tag_id = ? LIMIT 1");
    $cek->bind_param("ii", $resource_id, $tag_id);
    $cek->execute();
    $cek->store_result();

    if ($cek->num_rows === 0) {
        $insert = $conn->prepare("INSERT INTO resource_tag (resource_id, tag_id) VALUES (?, ?)");
        $insert->bind_param("ii", $resource_id, $tag_id);
        $insert->execute();
        $insert->close();
    }

    $cek->close();
}

function simpanTagBaruDariInput(mysqli $conn, int $resource_id, string $input_tag_baru): void
{
    if (trim($input_tag_baru) === '') {
        return;
    }

    $daftar_tag = explode(',', $input_tag_baru);

    foreach ($daftar_tag as $nama_tag) {
        $tag_id = ambilAtauBuatTag($conn, $nama_tag);
        tambahkanRelasiTag($conn, $resource_id, $tag_id);
    }
}

/* =========================================================
   FUNGSI DASHBOARD
   Menghitung jumlah resource berdasarkan status.
   ========================================================= */
function ambilStatistikStatus(mysqli $conn, int $user_id): array
{
    $stats = [
        'total' => 0,
        'belum_dibaca' => 0,
        'sedang_dipelajari' => 0,
        'selesai' => 0,
        'arsip' => 0,
    ];

    $stmt = $conn->prepare("SELECT status, COUNT(*) AS jumlah FROM resources WHERE user_id = ? GROUP BY status");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        return $stats;
    }

    while ($row = $result->fetch_assoc()) {
        $status = $row['status'];
        $jumlah = (int)$row['jumlah'];

        if (array_key_exists($status, $stats)) {
            $stats[$status] = $jumlah;
        }

        $stats['total'] += $jumlah;
    }

    $stmt->close();
    return $stats;
}

/* =========================================================
   FUNGSI STATISTIK JENIS RESOURCE
   ========================================================= */
function ambilStatistikJenis(mysqli $conn, int $user_id): array
{
    $stats = [];
    $stmt = $conn->prepare("SELECT jenis, COUNT(*) AS jumlah FROM resources WHERE user_id = ? GROUP BY jenis");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $stats[$row['jenis']] = (int)$row['jumlah'];
    }

    $stmt->close();
    return $stats;
}

/**
 * Kirim email menggunakan PHPMailer
 * 
 * @param string $to_email Email penerima
 * @param string $to_name Nama penerima
 * @param string $subject Subject email
 * @param string $body Isi email (HTML)
 * @return bool true jika berhasil
 */
function kirimEmail($to_email, $to_name, $subject, $body) {
    // Load PHPMailer & PSR Log
    require_once __DIR__ . '/src/PHPMailer.php';
    require_once __DIR__ . '/src/SMTP.php';
    require_once __DIR__ . '/src/Exception.php';

    // Load konfigurasi email
    $mailConfig = require __DIR__ . '/config/mail.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Konfigurasi SMTP
        $mail->isSMTP();
        $mail->Host       = $mailConfig['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailConfig['username'];
        $mail->Password   = $mailConfig['password'];
        $mail->SMTPSecure = 'tls';
        $mail->Port       = $mailConfig['port'];

        // Pengirim & penerima
        $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
        $mail->addReplyTo('ferdiantoferi1303@gmail.com', 'Feri');
        $mail->addAddress($to_email, $to_name);

        // Konten email (HTML)
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        // Aktifkan debug untuk pengembangan
        //$mail->SMTPDebug = 2;
        //$mail->Debugoutput = 'echo';//
        

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error
        error_log("Email gagal dikirim: " . $mail->ErrorInfo);
        return false;
    }
}

/* =========================================================
   FUNGSI LOG AKTIVITAS
   ========================================================= */
function logActivity(mysqli $conn, int $user_id, string $action, ?string $details = null): bool {
    // Daftar aksi yang diizinkan
    $allowed_actions = [
        'login', 
        'logout', 
        'tambah_resource', 
        'edit_resource', 
        'hapus_resource', 
        'ubah_password', 
        'pin_resource', 
        'tambah_tag', 
        'hapus_tag',
        'edit_tag'
    ];
    
    // Jika aksi tidak dikenal, set ke 'unknown'
    if (!in_array($action, $allowed_actions)) {
        $action = 'unknown';
    }

    $stmt = $conn->prepare("INSERT INTO user_logs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $details);
    return $stmt->execute();
}
?>