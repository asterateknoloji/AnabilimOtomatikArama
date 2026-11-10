<?php
/**
 * Veritabanı Bağlantı Testi
 */

require_once __DIR__ . '/includes/EnvLoader.php';

echo "=== VERİTABANI BAĞLANTI TESTİ ===\n\n";

// .env yükle
$envLoader = new EnvLoader(__DIR__ . '/.env');
$envLoader->load();

$dbHost = EnvLoader::get('DB_HOST');
$dbName = EnvLoader::get('DB_NAME');
$dbUser = EnvLoader::get('DB_USER');
$dbPass = EnvLoader::get('DB_PASS');

echo "Host: {$dbHost}\n";
echo "Database: {$dbName}\n";
echo "User: {$dbUser}\n\n";

echo "1️⃣ BAĞLANTI TESTİ...\n";

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    echo "   ✅ Bağlantı başarılı!\n\n";
    
    echo "2️⃣ TABLO KONTROLÜ...\n";
    $tables = [
        'CALLS' => 'Arama kayıtları',
        'IMPORT_HISTORY' => 'İmport geçmişi',
        'MESSAGE_TEMPLATES' => 'Mesaj şablonları',
        'PROJECTS' => 'Projeler',
        'users' => 'Kullanıcılar',
        'roles' => 'Roller',
        'user_roles' => 'Kullanıcı rolleri',
        'role_permissions' => 'İzinler',
        'ZAMANLAR' => 'Çalışma saatleri'
    ];
    
    foreach ($tables as $table => $description) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Kayıt sayısını al
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$dbName}.{$table}");
            $count = $stmt->fetch()['count'];
            echo "   ✅ {$table} ({$description}) - {$count} kayıt\n";
        } else {
            echo "   ❌ {$table} ({$description}) - BULUNAMADI!\n";
        }
    }
    
    echo "\n3️⃣ KULLANICI KONTROLÜ...\n";
    $stmt = $pdo->query("SELECT username, email FROM {$dbName}.users WHERE is_active = 1");
    $users = $stmt->fetchAll();
    echo "   Aktif kullanıcı sayısı: " . count($users) . "\n";
    foreach ($users as $user) {
        echo "   - {$user['username']} ({$user['email']})\n";
    }
    
    echo "\n4️⃣ PROJE KONTROLÜ...\n";
    $stmt = $pdo->query("SELECT ProjectName, CallerID, Trunk FROM {$dbName}.PROJECTS WHERE Aktif = 1");
    $projects = $stmt->fetchAll();
    echo "   Aktif proje sayısı: " . count($projects) . "\n";
    foreach ($projects as $project) {
        echo "   - {$project['ProjectName']} (CallerID: {$project['CallerID']}, Trunk: {$project['Trunk']})\n";
    }
    
} catch (PDOException $e) {
    echo "   ❌ Bağlantı hatası!\n";
    echo "   Hata: " . $e->getMessage() . "\n\n";
    
    echo "🔧 SORUN GİDERME:\n";
    echo "   1. MySQL portu açık mı?\n";
    echo "      Test-NetConnection -ComputerName {$dbHost} -Port 3306\n\n";
    echo "   2. Kullanıcı/şifre doğru mu?\n";
    echo "      .env dosyasını kontrol edin\n\n";
    echo "   3. Uzaktan erişim izni var mı?\n";
    echo "      MySQL'de: GRANT ALL ON {$dbName}.* TO '{$dbUser}'@'%';\n";
}

echo "\n=== TEST TAMAMLANDI ===\n";
