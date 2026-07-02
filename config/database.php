<?php
// config/database.php

session_start();
date_default_timezone_set('Asia/Jakarta'); // Set zona waktu ke Jakarta, Indonesia
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Memanggil variabel rahasia dari file env.php
$env_path = __DIR__ . '/../env.php';

if (!file_exists($env_path)) {
    die("Error Sistem: File env.php tidak ditemukan di luar folder config.");
}

$env = require $env_path;

// ** [UPDATE] Menggunakan Try-Catch untuk mencegah HTTP ERROR 500 jika koneksi salah
try {
    // PHP 8+ akan melempar Exception ke blok 'catch' di bawah jika data env salah
    $conn = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'], $env['DB_NAME']);
    $conn->set_charset("utf8mb4");
    $conn->query("SET time_zone = '+07:00'");
    
} catch (mysqli_sql_exception $e) {
    // Layar akan memunculkan teks ini dengan rapi, website tidak akan crash (Error 500)
    die("<div style='font-family:sans-serif; padding:20px; text-align:center; color:#dc3545; background:#ffe4e6; border-radius:10px; margin:50px auto; max-width:600px;'>
            <h2>🚨 Koneksi Database Gagal</h2>
            <p>Pastikan isi file <b>env.php</b> di hosting sudah menggunakan Username dan Password dari InfinityFree, bukan menggunakan localhost/root.</p>
         </div>");
}
?>