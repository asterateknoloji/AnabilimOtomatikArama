<?php
/**
 * CONFIG.PHP - GERİYE UYUMLU WRAPPER
 * 
 * Bu dosya artık config.secure.php'yi yükler.
 * Tüm yeni projeler config.secure.php kullanmalıdır.
 * Bu wrapper sadece eski kodların çalışması için.
 */

// Yeni güvenli config'i yükle
require_once __DIR__ . '/config.secure.php';

// Geriye uyumluluk için değişkenleri kopyala (zaten config.secure.php'de tanımlı)
// $dbHost, $dbName, $dbUser, $dbPass ve $pdo zaten mevcut

// CDR kayıtları için asteriskcdrdb bağlantısı (opsiyonel)
try {
    $pdoCdr = new PDO(
        "mysql:host={$dbHost};dbname=ANABILIMNEW;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ]
    );
} catch (PDOException $e) {
    // CDR bağlantısı opsiyonel - hata verme
    $pdoCdr = null;
}

// Asterisk veritabanı için ayrı bağlantı (queue_log tablosu için - opsiyonel)
try {
    $pdoAsterisk = new PDO(
        "mysql:host={$dbHost};dbname=asterisk;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ]
    );
} catch (PDOException $e) {
    // Asterisk DB bağlantısı opsiyonel - hata verme
    $pdoAsterisk = null;
}

// NOT: Tüm diğer ayarlar (AMI, SSH, FFMPEG, CALLER_ID, TRUNK_NAME) 
// artık config.secure.php'de tanımlı ve .env dosyasından okunuyor.
// Geriye uyumluluk için bu dosya sadece ek PDO bağlantıları oluşturur.


