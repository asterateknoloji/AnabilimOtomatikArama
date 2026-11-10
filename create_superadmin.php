<?php
/**
 * İlk Superadmin Kullanıcısı Oluştur
 */

require_once 'includes/ConfigManager.php';
require_once 'includes/Encryption.php';

echo "=== SUPERADMIN KULLANICISI OLUŞTUR ===\n\n";

try {
    $pdo = ConfigManager::createPDO();
    $encryption = new Encryption();
    
    // Superadmin kullanıcısı var mı kontrol et
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = 'superadmin'");
    $checkStmt->execute();
    
    $existingUser = $checkStmt->fetch();
    
    if ($existingUser) {
        echo "⚠️  'superadmin' kullanıcısı zaten mevcut! (ID: {$existingUser['id']})\n";
        
        // Şifreyi güncelle
        $newHash = $encryption->hashPassword('Astera2020');
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'superadmin'");
        $updateStmt->execute([$newHash]);
        
        echo "✅ Şifre güncellendi!\n\n";
        
        $userId = $existingUser['id'];
        
        // Superadmin rolü var mı kontrol et
        $checkRoleStmt = $pdo->prepare("SELECT id FROM USER_ROLES WHERE user_id = ? AND role_id = 1");
        $checkRoleStmt->execute([$userId]);
        
        if (!$checkRoleStmt->fetch()) {
            $insertRoleStmt = $pdo->prepare("
                INSERT INTO USER_ROLES (user_id, role_id, assigned_at) 
                VALUES (?, 1, NOW())
            ");
            $insertRoleStmt->execute([$userId]);
            echo "✅ Superadmin rolü atandı!\n\n";
        } else {
            echo "ℹ️  Superadmin rolü zaten mevcut.\n\n";
        }
        
    } else {
        // Yeni kullanıcı oluştur
        $newHash = $encryption->hashPassword('Astera2020');
        
        $insertStmt = $pdo->prepare("
            INSERT INTO users (username, password, isadmin, adi, soyadi, isview) 
            VALUES ('superadmin', ?, 1, 'Sistem', 'Yöneticisi', 1)
        ");
        $insertStmt->execute([$newHash]);
        
        $userId = $pdo->lastInsertId();
        
        echo "✅ Kullanıcı oluşturuldu! (ID: $userId)\n\n";
        
        // Superadmin rolü ata
        $insertRoleStmt = $pdo->prepare("
            INSERT INTO USER_ROLES (user_id, role_id, assigned_at) 
            VALUES (?, 1, NOW())
        ");
        $insertRoleStmt->execute([$userId]);
        
        echo "✅ Superadmin rolü atandı!\n\n";
    }
    
    echo "=== GİRİŞ BİLGİLERİ ===\n";
    echo "Kullanıcı Adı: superadmin\n";
    echo "Şifre: Astera2020\n\n";
    
    echo "✅ İşlem tamamlandı!\n";
    
} catch (Exception $e) {
    echo "❌ Kritik Hata: " . $e->getMessage() . "\n";
    exit(1);
}
