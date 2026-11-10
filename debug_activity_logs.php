<?php
/**
 * Activity Logs Debug
 */

// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Activity Logs Debug</h1>";

echo "<h2>1. Check Auth Yükleniyor...</h2>";
try {
    require_once 'includes/check_auth.php';
    echo "✅ check_auth.php yüklendi<br>";
    echo "Kullanıcı: " . htmlspecialchars($currentUser['username']) . "<br>";
} catch (Exception $e) {
    echo "❌ HATA: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>2. requirePermission Test Ediliyor...</h2>";
try {
    requirePermission('log.view');
    echo "✅ log.view yetkisi var<br>";
} catch (Exception $e) {
    echo "❌ HATA: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>3. Veritabanı Bağlantısı Test Ediliyor...</h2>";
try {
    $testStmt = $pdo->query("SELECT 1");
    echo "✅ PDO bağlantısı çalışıyor<br>";
} catch (Exception $e) {
    echo "❌ HATA: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>4. USER_ACTIVITY_LOG Tablosu Kontrol Ediliyor...</h2>";
try {
    $checkTable = $pdo->query("SHOW TABLES LIKE 'USER_ACTIVITY_LOG'");
    if ($checkTable->rowCount() > 0) {
        echo "✅ USER_ACTIVITY_LOG tablosu mevcut<br>";
        
        // Tablo yapısını göster
        $structure = $pdo->query("DESCRIBE USER_ACTIVITY_LOG");
        echo "<pre>";
        print_r($structure->fetchAll(PDO::FETCH_ASSOC));
        echo "</pre>";
    } else {
        echo "❌ USER_ACTIVITY_LOG tablosu bulunamadı!<br>";
    }
} catch (Exception $e) {
    echo "❌ HATA: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Kayıt Sayısı Kontrol Ediliyor...</h2>";
try {
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM USER_ACTIVITY_LOG");
    $count = $countStmt->fetch();
    echo "✅ Toplam kayıt sayısı: " . $count['total'] . "<br>";
} catch (Exception $e) {
    echo "❌ HATA: " . $e->getMessage() . "<br>";
}

echo "<h2>6. Son 5 Kayıt Getiriliyor...</h2>";
try {
    $sql = "
        SELECT 
            ual.*,
            u.username,
            u.adi,
            u.soyadi
        FROM USER_ACTIVITY_LOG ual
        LEFT JOIN users u ON ual.user_id = u.id
        ORDER BY ual.created_at DESC 
        LIMIT 5
    ";
    $stmt = $pdo->query($sql);
    $logs = $stmt->fetchAll();
    echo "✅ " . count($logs) . " kayıt getirildi<br>";
    echo "<pre>";
    print_r($logs);
    echo "</pre>";
} catch (Exception $e) {
    echo "❌ HATA: " . $e->getMessage() . "<br>";
}

echo "<h2>7. Filtre Değerleri Test Ediliyor...</h2>";
try {
    $actionsStmt = $pdo->query("SELECT DISTINCT action_type FROM USER_ACTIVITY_LOG ORDER BY action_type");
    $actions = $actionsStmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ " . count($actions) . " farklı action_type bulundu<br>";
    echo "<pre>";
    print_r($actions);
    echo "</pre>";
    
    $modulesStmt = $pdo->query("SELECT DISTINCT action_module FROM USER_ACTIVITY_LOG ORDER BY action_module");
    $modules = $modulesStmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ " . count($modules) . " farklı action_module bulundu<br>";
    echo "<pre>";
    print_r($modules);
    echo "</pre>";
} catch (Exception $e) {
    echo "❌ HATA: " . $e->getMessage() . "<br>";
}

echo "<h2>✅ TÜM TESTLER TAMAMLANDI!</h2>";
echo "<a href='activity_logs.php'>Activity Logs Sayfasına Git</a>";
