<?php
/**
 * Mevcut kullanıcıları yeni sisteme migrate et
 * - Şifreleri hash'le
 * - Superadmin kullanıcısına superadmin rolü ata
 */

require_once 'includes/ConfigManager.php';
require_once 'includes/Encryption.php';

echo "=== KULLANICI MIGRATION ===\n\n";

try {
    $pdo = ConfigManager::createPDO();
    $encryption = new Encryption();
    
    // Tüm kullanıcıları çek
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll();
    
    echo "Toplam " . count($users) . " kullanıcı bulundu.\n\n";
    
    $updated = 0;
    $skipped = 0;
    
    foreach ($users as $user) {
        $username = $user['username'];
        $currentPassword = $user['password'];
        
        echo "İşleniyor: $username... ";
        
        // Eğer şifre zaten hash'lenmişse atla
        if (strlen($currentPassword) > 40) {
            echo "⏭️  Atlandı (zaten hash'li)\n";
            $skipped++;
            continue;
        }
        
        // Özel durumlar
        if ($username === 'superadmin' && $currentPassword === 'Astera2020') {
            // Superadmin için yeni hash oluştur
            $newHash = $encryption->hashPassword('Astera2020');
            
            $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->execute([$newHash, $user['id']]);
            
            echo "✅ Güncellendi (superadmin)\n";
            $updated++;
            
            // Superadmin rolü ata
            $checkRoleStmt = $pdo->prepare("SELECT id FROM USER_ROLES WHERE user_id = ? AND role_id = 1");
            $checkRoleStmt->execute([$user['id']]);
            
            if (!$checkRoleStmt->fetch()) {
                $insertRoleStmt = $pdo->prepare("INSERT INTO USER_ROLES (user_id, role_id, assigned_at) VALUES (?, 1, NOW())");
                $insertRoleStmt->execute([$user['id']]);
                echo "   → Superadmin rolü atandı\n";
            }
            
            continue;
        }
        
        // Diğer kullanıcılar için mevcut şifreyi hash'le
        if (!empty($currentPassword)) {
            $newHash = $encryption->hashPassword($currentPassword);
            
            $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->execute([$newHash, $user['id']]);
            
            echo "✅ Güncellendi\n";
            $updated++;
        } else {
            echo "⚠️  Atlandı (şifre boş)\n";
            $skipped++;
        }
    }
    
    echo "\n=== ÖZET ===\n";
    echo "✅ Güncellenen: $updated\n";
    echo "⏭️  Atlanan: $skipped\n";
    echo "📊 Toplam: " . count($users) . "\n\n";
    
    echo "✅ Migration tamamlandı!\n";
    
} catch (Exception $e) {
    echo "❌ Kritik Hata: " . $e->getMessage() . "\n";
    exit(1);
}
