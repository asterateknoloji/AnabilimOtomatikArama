<?php
/**
 * Superadmin Şifresini Sıfırla
 */

require_once __DIR__ . '/config.php';

echo "=== SUPERADMIN ŞİFRE SIFIRLAMA ===\n\n";

try {
    // Mevcut superadmin kullanıcısını kontrol et
    $stmt = $pdo->prepare("SELECT id, username, email FROM " . table('users') . " WHERE username = ?");
    $stmt->execute(['superadmin']);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "❌ superadmin kullanıcısı bulunamadı!\n";
        echo "Kullanıcı oluşturulsun mu? (y/n): ";
        exit(1);
    }
    
    echo "Kullanıcı bulundu:\n";
    echo "  ID: {$user['id']}\n";
    echo "  Kullanıcı Adı: {$user['username']}\n";
    echo "  Email: {$user['email']}\n\n";
    
    // Yeni şifre
    $newPassword = 'Astera2021';
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    echo "Yeni şifre: {$newPassword}\n";
    echo "Yeni hash: " . substr($newHash, 0, 50) . "...\n\n";
    
    // Şifreyi güncelle
    $updateStmt = $pdo->prepare("
        UPDATE " . table('users') . "
        SET password = ?,
            is_active = 1
        WHERE username = ?
    ");
    
    $updateStmt->execute([$newHash, 'superadmin']);
    
    echo "✅ Şifre başarıyla güncellendi!\n\n";
    
    echo "===========================================\n";
    echo "YENİ GİRİŞ BİLGİLERİ:\n";
    echo "===========================================\n";
    echo "Kullanıcı Adı: superadmin\n";
    echo "Şifre: Astera2021\n";
    echo "===========================================\n\n";
    
    // Test et
    echo "Şifre doğrulama testi:\n";
    $verified = password_verify($newPassword, $newHash);
    echo "  " . ($verified ? "✅" : "❌") . " Şifre hash'i doğru\n\n";
    
    echo "Artık giriş yapabilirsiniz!\n";
    
} catch (Exception $e) {
    echo "❌ HATA: " . $e->getMessage() . "\n";
    exit(1);
}
