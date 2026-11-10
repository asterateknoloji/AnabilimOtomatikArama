# 🎹 Şablon Tuş Davranışları Sistemi

## 📋 Genel Bakış

Bu sistem, her mesaj şablonu için özel tuş davranışları tanımlamanızı sağlar. Artık her şablon için:
- **1 tuşu**: Anonsu tekrar dinletme veya farklı bir numaraya transfer
- **2 tuşu**: Operatör kuyruğuna veya farklı bir dahiliye transfer
- **Yanlış tuşlama**: Nasıl davranacağını belirleme
- **Timeout**: Tuş basılmazsa ne olacağını belirleme

## 🚀 Kurulum

### 1. Veritabanını Güncelleme

```cmd
cd d:\xampp\htdocs\OtomatikArama
setup_template_actions.bat
```

Bu script:
- ✅ MESSAGE_TEMPLATES tablosuna yeni alanlar ekler
- ✅ Mevcut şablonları varsayılan değerlerle günceller
- ✅ Veritabanı yapısını test eder

### 2. Şablon Sayfasını Açın

Tarayıcınızda:
```
http://localhost:8009/OtomatikArama/sablonlar.php
```

## 📝 Yeni Şablon Oluşturma

### Adım 1: Temel Bilgiler

1. **Şablon Adı**: Örn: "Tahsilat Mesajı 2026"
2. **Açıklama**: Şablon hakkında kısa açıklama
3. **Şablon İçeriği**: 
   - Sabit metin ekleyin
   - Alan ekleyin (Müşteri Adı, Tutar, vb.)
   - Sıralamasını değiştirin

### Adım 2: Tuş Davranışları

#### 🔢 1 Tuşu Fonksiyonu

**Seçenek 1: Anonsu Tekrar Dinlet** (Varsayılan)
```
✅ Müşteri 1'e bastığında anons baştan çalar
```

**Seçenek 2: Transfer Yap**
```
📞 Transfer Numarası: 133
✅ Müşteri 1'e bastığında 133 dahili aranır
```

#### 🔢 2 Tuşu Transfer Numarası

```
Kuyruk Adı: TAHSILAT
veya
Dahili: 200
```

#### ❌ Yanlış Tuşlama

```
Seçenek 1: 1 Tuşu Fonksiyonunu Çalıştır (Varsayılan)
Seçenek 2: Hattı Kapat
```

#### ⏱️ Timeout (Tuş Basılmazsa)

```
Seçenek 1: 1 Tuşu Fonksiyonu + Hattı Kapat (Varsayılan)
Seçenek 2: Sadece Hattı Kapat
```

#### ⏲️ Tuş Bekleme Süresi

```
3-30 saniye arası (Varsayılan: 5)
```

### Adım 3: Kaydet

**💾 Şablonu Kaydet** butonuna tıklayın.

Sistem otomatik olarak:
1. ✅ Şablonu veritabanına kaydeder
2. ✅ Asterisk için dialplan oluşturur
3. ✅ SSH ile Asterisk sunucusuna gönderir
4. ✅ Asterisk dialplan'ı reload eder

## 📋 Örnek Senaryolar

### Senaryo 1: Standart Tahsilat Mesajı

```
1 Tuşu: Tekrar Dinlet
2 Tuşu: TAHSILAT kuyruğu
Yanlış Tuşlama: 1 tuşu fonksiyonu
Timeout: 1 tuşu + hattı kapat
Bekleme: 5 saniye
```

**Davranış:**
- Müşteri 1'e basarsa → Anons tekrar çalar
- Müşteri 2'ye basarsa → TAHSILAT kuyruğuna gider
- Müşteri 5'e basarsa → Anons tekrar çalar
- Hiç tuş basmazsa → Anons tekrar çalar, sonra kapanır

### Senaryo 2: Hızlı Satış Transfer

```
1 Tuşu: Transfer (133)
2 Tuşu: Transfer (200)
Yanlış Tuşlama: Hattı kapat
Timeout: Hattı kapat
Bekleme: 3 saniye
```

**Davranış:**
- Müşteri 1'e basarsa → 133 dahili aranır
- Müşteri 2'ye basarsa → 200 dahili aranır
- Müşteri 5'e basarsa → Hattı kapatır
- Hiç tuş basmazsa → Hattı kapatır

### Senaryo 3: Müşteri Hizmetleri

```
1 Tuşu: Transfer (Müşteri Hizmetleri - 150)
2 Tuşu: Transfer (Teknik Destek - 151)
Yanlış Tuşlama: 1 tuşu fonksiyonu
Timeout: 1 tuşu + hattı kapat
Bekleme: 7 saniye
```

## 🔍 Test Etme

### 1. Şablon Oluşturun

```
http://localhost:8009/OtomatikArama/sablonlar.php
```

### 2. Veri Import Edin

```
http://localhost:8009/OtomatikArama/import.php
```

- Şablonu seçin
- Excel dosyasını yükleyin

### 3. Aramayı Başlatın

```
http://localhost:8009/OtomatikArama/otomatikarama.php
```

- "Aramaları Başlat" butonuna tıklayın

### 4. Test Edin

- Telefonu açın
- Anonsu dinleyin
- Tuşlara basın (1, 2, veya yanlış tuş)
- Veya hiç tuşlamayın (timeout)

## 🛠️ Sorun Giderme

### Dialplan Oluşmadı

```cmd
# Manuel olarak oluşturun:
1. sablonlar.php'de şablonu açın
2. Herhangi bir değişiklik yapın (Timeout süresini 6 yapıp 5'e geri alın)
3. "Şablonu Kaydet" butonuna tıklayın
```

### Asterisk Reload Olmadı

```cmd
# SSH ile bağlanın:
D:\xampp\htdocs\OtomatikArama\tools\plink.exe -ssh -P 45555 root@192.168.181.16

# Dialplan'ı kontrol edin:
asterisk -rx "dialplan show auto-calls-template-1"

# Manuel reload:
asterisk -rx "dialplan reload"
```

### Context Bulunamadı

```sql
-- Şablon context'lerini kontrol edin:
SELECT id, TemplateName, DialplanContext 
FROM ANABILIM.MESSAGE_TEMPLATES;

-- Context'i düzeltin:
UPDATE ANABILIM.MESSAGE_TEMPLATES 
SET DialplanContext = CONCAT('auto-calls-template-', id)
WHERE DialplanContext IS NULL;
```

## 📁 Oluşturulan Dosyalar

Her şablon için:

```
Asterisk Sunucusu:
/etc/asterisk/extensions_template_1.conf
/etc/asterisk/extensions_template_2.conf
...

Windows (Geçici):
d:\xampp\htdocs\OtomatikArama\asterisk\extensions_template_1.conf
d:\xampp\htdocs\OtomatikArama\asterisk\extensions_template_2.conf
...
```

## 🎯 Önemli Notlar

1. **Her şablon kaydettiğinizde** Asterisk dialplan otomatik oluşturulur ve reload edilir
2. **Import sırasında** seçilen şablonun context'i kullanılır
3. **Eski aramalar** hala varsayılan `auto-calls` context'ini kullanır
4. **Yeni aramalar** şablonun özel context'ini kullanır (örn: `auto-calls-template-2`)

## 📞 Destek

Sorun yaşarsanız log dosyalarını kontrol edin:

```bash
# Asterisk log:
tail -f /var/log/asterisk/full | grep auto-calls

# AGI log:
cat /tmp/agi_dtmf.log
```

---

**İyi çalışmalar! 🚀**
