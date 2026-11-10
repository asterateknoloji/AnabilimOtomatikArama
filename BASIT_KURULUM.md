# 🚀 Basit Kurulum Rehberi

## Adım 1: Composer Yükle

```batch
cd d:\xampp\htdocs\AsteraWeb
```

### Global Composer varsa:
```batch
composer install
```

### Global Composer yoksa:
```batch
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php composer.phar install
```

## Adım 2: Klasörleri Oluştur

```batch
mkdir asterisk\audio\calls
mkdir asterisk\callfiles
mkdir logs
mkdir temp
```

## Adım 3: Test Et

```batch
php test_autocall.php
```

## Adım 4: Excel Şablonu Oluştur

```batch
php create_excel_template.php
```

## Adım 5: Web Paneline Git

```
http://localhost:8009/AsteraWeb/otomatikarama.php
```

## Sorun mu yaşıyorsunuz?

### Composer Hatası
Eğer "composer.json bulunamadı" hatası alıyorsanız:

1. Doğru dizinde olduğunuzdan emin olun:
```batch
cd d:\xampp\htdocs\AsteraWeb
dir composer.json
```

2. composer.json görünüyorsa:
```batch
php -r "echo file_exists('composer.json') ? 'OK' : 'BULUNAMADI';"
```

### PHP Hatası
Eğer PHP bulunamıyorsa:

```batch
C:\xampp\php\php.exe -v
```

PATH'e ekleyin veya tam yolu kullanın:
```batch
C:\xampp\php\php.exe composer.phar install
```

## Hızlı Başlangıç

Tüm adımları tek seferde:

```batch
cd d:\xampp\htdocs\AsteraWeb
composer install
mkdir asterisk\audio\calls 2>nul
mkdir asterisk\callfiles 2>nul
mkdir logs 2>nul
mkdir temp 2>nul
php test_autocall.php
start http://localhost:8009/AsteraWeb/otomatikarama.php
```

Herşey hazır! 🎉
