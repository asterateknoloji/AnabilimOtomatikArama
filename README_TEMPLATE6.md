# ✅ TEMPLATE 6 (FERHAT DAVET) - KURULUM TAMAM!

## 🎯 DURUM

✅ Dialplan dosyası oluşturuldu: `asterisk/dialplan/extensions_template_6.conf`  
✅ Dialplan kodu **PANOYA KOPYALANDI**  
✅ Giriş sayfası açıldı: http://localhost:8009/otomatikarama/login.php

---

## 📋 SON ADIM (2 DAKİKA)

### Dialplan'ı Asterisk'e Yükle:

1. **Santral web paneline gir:**
   - URL: http://192.168.181.16 (veya santral IP'niz)
   - Login yapın (admin/astera2012 veya mevcut şifreniz)

2. **Config Edit'e git:**
   - Menü: **Admin** → **Config Edit**
   - Dosya: **extensions_custom.conf**

3. **Dialplan'ı yapıştır:**
   - Dosyanın **EN SONUNA** gidin
   - **CTRL+V** yapın (dialplan zaten panoda!)
   - **Save** tıklayın
   - **Apply Config** / **Reload** tıklayın

---

## 🚀 KULLANMAYA BAŞLAYIN

### Giriş:
```
http://localhost:8009/otomatikarama/login.php
```

### Kullanım:
1. Login yapın
2. **Arama Yönetimi** sayfasına gidin
3. **Template 6 (Ferhat Davet)** seçin
4. Excel yükleyin
5. Aramaları başlatın

---

## ✅ BAŞARI KONTROLÜ

Asterisk Console'da şunu göreceksiniz:
```
=== Template 6: Ferhat Davet Araması Başladı ===
Call ID: ...
Context: auto-calls-template-6
```

**"Invalid extension" hatası OLMAYACAK!** ✅

---

## 📁 OLUŞTURULAN DOSYALAR

- ✅ `/asterisk/dialplan/extensions_template_6.conf` - Template 6 dialplan
- ✅ `/install_template6_ami.php` - Otomatik kurulum scripti
- ✅ `/README_TEMPLATE6.md` - Bu dosya

---

## 🔍 SORUN GİDERME

### Context bulunamıyor?

Asterisk CLI'de kontrol edin:
```bash
asterisk -rx "dialplan show auto-calls-template-6"
```

Eğer bulunamazsa:
- extensions_custom.conf'a doğru yapıştırdığınızdan emin olun
- `dialplan reload` komutunu çalıştırın

### Aramalar hala hataya düşüyor?

1. Asterisk loglarını kontrol edin
2. Context adının doğru olduğundan emin olun: `auto-calls-template-6`
3. AGI scriptlerinin yolunu kontrol edin

---

## ✅ ÖZET

| Bileşen | Durum |
|---------|-------|
| Dialplan Dosyası | ✅ Oluşturuldu |
| Kopyala/Yapıştır | ✅ Panoda hazır |
| Web Sayfası | ✅ Açıldı |
| Manuel İşlem | ⏳ Bekleniyor (2 dk) |

---

**Kurulum Tarihi:** <?php echo date('d.m.Y H:i'); ?>  
**Sistem:** OtomatikArama (Port 8009)  
**Durum:** ⏳ Manuel adım bekleniyor

Manuel adımı tamamlayınca sistem **%100 hazır** olacak! 🎉
