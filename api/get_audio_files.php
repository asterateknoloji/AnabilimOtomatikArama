<?php
/**
 * Otomatik klasöründeki ses dosyalarını listele
 */

require_once '../includes/check_auth.php';

// Şablon görüntüleme yetkisi gerekli
requirePermission('template.view');

header('Content-Type: application/json');

// Asterisk sunucusundan ses dosyalarını al
$audioFiles = [
    ['value' => 'otomatik/transfer', 'label' => '🔊 Transfer Mesajı (Lütfen bekleyiniz, operatöre aktarılıyorsunuz)'],
    ['value' => 'otomatik/goodbye', 'label' => '🔊 Veda Mesajı (İyi günler dileriz)'],
    ['value' => 'otomatik/invalid', 'label' => '🔊 Geçersiz Tuş (Geçersiz tuşlama yaptınız)'],
    ['value' => 'otomatik/queue-thankyou', 'label' => '🔊 Teşekkür (Bizi aradığınız için teşekkür ederiz)'],
    ['value' => 'otomatik/agent-connecting', 'label' => '🔊 Bağlanıyor (Bağlanıyorsunuz, lütfen bekleyiniz)'],
    ['value' => '', 'label' => '🔇 Ses Yok (Sessiz)'],
];

echo json_encode($audioFiles, JSON_UNESCAPED_UNICODE);
