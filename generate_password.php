<?php
/**
 * Güçlü Şifre Oluşturucu
 * Kullanım: php generate_password.php [uzunluk]
 */

// Komut satırından uzunluk al (varsayılan: 16)
$length = isset($argv[1]) ? (int)$argv[1] : 16;

// Minimum 8, maximum 128
$length = max(8, min(128, $length));

// Karakter setleri
$lowercase = 'abcdefghijklmnopqrstuvwxyz';
$uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
$numbers = '0123456789';
$special = '!@#$%^&*()';

$allChars = $lowercase . $uppercase . $numbers . $special;

// Şifre oluştur
$password = '';

// En az bir karakter her kategoriden
$password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
$password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
$password .= $numbers[random_int(0, strlen($numbers) - 1)];
$password .= $special[random_int(0, strlen($special) - 1)];

// Geri kalanı rastgele
for ($i = 4; $i < $length; $i++) {
    $password .= $allChars[random_int(0, strlen($allChars) - 1)];
}

// Karıştır
$password = str_shuffle($password);

// Çıktı
echo "\n";
echo "═══════════════════════════════════════\n";
echo "   Güçlü Şifre Oluşturuldu\n";
echo "═══════════════════════════════════════\n";
echo "\n";
echo "Şifre: " . $password . "\n";
echo "Uzunluk: " . strlen($password) . " karakter\n";
echo "\n";
echo "Özellikler:\n";
echo "✅ Küçük harf: " . (preg_match('/[a-z]/', $password) ? 'Evet' : 'Hayır') . "\n";
echo "✅ Büyük harf: " . (preg_match('/[A-Z]/', $password) ? 'Evet' : 'Hayır') . "\n";
echo "✅ Rakam: " . (preg_match('/[0-9]/', $password) ? 'Evet' : 'Hayır') . "\n";
echo "✅ Özel karakter: " . (preg_match('/[!@#$%^&*()]/', $password) ? 'Evet' : 'Hayır') . "\n";
echo "\n";

// Güç seviyesi hesapla
$strength = 0;
if (strlen($password) >= 12) $strength++;
if (strlen($password) >= 16) $strength++;
if (preg_match('/[a-z]/', $password)) $strength++;
if (preg_match('/[A-Z]/', $password)) $strength++;
if (preg_match('/[0-9]/', $password)) $strength++;
if (preg_match('/[!@#$%^&*()]/', $password)) $strength++;

$strengthText = '';
if ($strength <= 2) {
    $strengthText = '🔴 Zayıf';
} elseif ($strength <= 4) {
    $strengthText = '🟡 Orta';
} elseif ($strength <= 5) {
    $strengthText = '🟢 İyi';
} else {
    $strengthText = '🟣 Mükemmel';
}

echo "Güç Seviyesi: {$strengthText} ({$strength}/6)\n";
echo "\n";

// Ek şifreler üret
echo "Alternatif Şifreler:\n";
for ($i = 0; $i < 3; $i++) {
    $alt = '';
    for ($j = 0; $j < $length; $j++) {
        $alt .= $allChars[random_int(0, strlen($allChars) - 1)];
    }
    echo ($i + 1) . ". " . $alt . "\n";
}

echo "\n";
echo "═══════════════════════════════════════\n";
echo "\n";
