# 📞 ANABİLİM OTOMATİK ARAMA SİSTEMİ

**Yapay Zeka Destekli Otomatik Arama ve Müşteri Yönetim Sistemi**

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net)
[![Python Version](https://img.shields.io/badge/Python-3.8%2B-green.svg)](https://python.org)
[![Asterisk](https://img.shields.io/badge/Asterisk-18%2B-red.svg)](https://asterisk.org)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

---

## 🎯 ÖZELLİKLER

### ✨ Temel Özellikler
- ✅ **Excel'den Toplu Arama** - XLS/XLSX/CSV desteği
- ✅ **Otomatik TTS Mesaj** - Windows TTS ile Türkçe sesli mesaj
- ✅ **Dinamik Mesaj Şablonları** - Özelleştirilebilir sesli mesajlar
- ✅ **Proje Bazlı Yönetim** - Multiple proje desteği
- ✅ **Tekrar Arama Sistemi** - Başarısız aramalar için otomatik retry
- ✅ **DTMF Tuş Algılama** - Müşteri tuş basımı kaydı
- ✅ **Arama Raporlama** - Detaylı istatistikler ve Excel export

### 🤖 AI Özellikleri (Yeni!)
- ✅ **Sesli AI Asistan** - Müşteri ile doğal konuşma
- ✅ **Cloud AI (OpenAI)** - GPT-4 Turbo + Whisper + TTS
- ✅ **Lokal AI (Offline)** - Ollama + Llama 3.1 + Piper TTS
- ✅ **Sentiment Analizi** - Müşteri duygu durumu tespiti
- ✅ **Konuşma Transkripti** - Tüm konuşma kayıtları
- ✅ **Bağlamsal Diyalog** - Önceki konuşmayı hatırlar

### 🔧 Teknik Özellikler
- ✅ **Asterisk PBX Entegrasyonu** - AGI script ile tam kontrol
- ✅ **MySQL Veritabanı** - Tüm veriler güvenle saklanır
- ✅ **Web Arayüzü** - Kullanıcı dostu yönetim paneli
- ✅ **Çalışma Saatleri Kontrolü** - Otomatik zaman yönetimi
- ✅ **Hız Limiti** - Eşzamanlı arama sayısı kontrolü
- ✅ **CDR Entegrasyonu** - Asterisk CDR ile tam senkronizasyon

---

## 📋 GEREKSİNİMLER

### Sunucu Gereksinimleri
- **OS:** Windows 10/11 veya Linux (Ubuntu 20.04+)
- **Web Server:** Apache 2.4+ veya Nginx
- **PHP:** 7.4+ (8.0+ önerilen)
- **MySQL:** 5.7+ veya MariaDB 10.3+
- **Python:** 3.8+ (AI özellikleri için)

### Asterisk
- **Asterisk:** 18+ (20 önerilen)
- **Dahili modüller:** AGI, AMI, MixMonitor
- **SIP Trunk:** Dış arama için

### AI Özellikleri İçin (Opsiyonel)
- **GPU:** NVIDIA GTX 1660+ (Lokal AI için)
- **RAM:** 16 GB+ (Lokal AI için)
- **OpenAI API Key** (Cloud AI için)
- **Ollama** (Lokal AI için)

---

## 🚀 KURULUM

### 1. Depoyu Klonlayın

```bash
git clone https://github.com/your-username/AnabilimOtomatikArama.git
cd AnabilimOtomatikArama
```

### 2. Veritabanını Kurun

```bash
mysql -u root -p < database/schema.sql
```

### 3. Konfigürasyon

```bash
# .env dosyası oluştur
cp .env.example .env

# .env dosyasını düzenle
nano .env
```

Gerekli bilgileri girin:
- Veritabanı bilgileri
- Asterisk SSH/AMI bilgileri
- AI API keys (isteğe bağlı)

### 4. Bağımlılıkları Kurun

#### PHP Bağımlılıkları:
```bash
composer install
```

#### Python Bağımlılıkları (AI için):
```bash
pip install -r requirements.txt
```

### 5. Asterisk Entegrasyonu

```bash
# AGI script'i kopyala
sudo cp agi/ai_handler.py /var/lib/asterisk/agi-bin/
sudo chmod +x /var/lib/asterisk/agi-bin/ai_handler.py
sudo chown asterisk:asterisk /var/lib/asterisk/agi-bin/ai_handler.py

# Dialplan ekle
sudo nano /etc/asterisk/extensions_custom.conf
# (ASTERISK_AI_ENTEGRASYON.md'deki dialplan'ı ekle)

# Reload
sudo asterisk -rx "dialplan reload"
```

### 6. Testi Çalıştırın

```bash
# Temel test
php artisan test

# AI test
python test_ai_connection.py

# Asterisk test
python test_asterisk_ai.py
```

---

## 📖 KULLANIM

### Excel Yükleme ve Arama Başlatma

1. **Web Arayüzüne Giriş:** `http://localhost:8009/AnabilimOtomatikArama/`
2. **Arama Yönetimi** sayfasına git
3. **Excel Yükle** butonuna tıkla
4. Dosyanı seç (XLS/XLSX/CSV)
5. Proje ve şablon seç
6. **Yükle** butonuna bas
7. **Aktif** butonuna basarak aramaları başlat

### AI Asistan Kullanımı

1. **Mesaj Şablonları** sayfasına git
2. Yeni şablon oluştur veya mevcut şablonu düzenle
3. **"AI Kullan"** seçeneğini aktif et
4. AI Prompt'u yaz (örnek promptlar rehberde)
5. Şablonu kaydet
6. Import yaparken bu şablonu seç
7. Aramalar otomatik olarak AI ile yapılacak

---

## 📚 DÖKÜMANTASYON

### Kullanım Kılavuzları

| Dosya | Açıklama | Sayfa |
|-------|----------|-------|
| [KULLANIM_KILAVUZU_ARAMA_HIZI.pdf.md](KULLANIM_KILAVUZU_ARAMA_HIZI.pdf.md) | Arama hızı ve ayarlama | 50+ |
| [AI_ENTEGRASYON_REHBERI.md](AI_ENTEGRASYON_REHBERI.md) | Cloud AI (OpenAI) kurulum | 120+ |
| [LOKAL_AI_REHBERI.md](LOKAL_AI_REHBERI.md) | Lokal AI (Offline) kurulum | 150+ |
| [ASTERISK_AI_ENTEGRASYON.md](ASTERISK_AI_ENTEGRASYON.md) | Asterisk PBX entegrasyon | 70+ |

### Hızlı Başlangıç Scriptleri

```bash
# Temel kurulum
setup.bat                    # Veritabanı ve temel kurulum

# AI kurulumları
setup_ai.bat                 # Cloud AI (OpenAI)
setup_local_ai.bat           # Lokal AI (Ollama)

# Cron job kurulumu
setup_cron.bat               # Her 1 dakika
setup_cron_5min.bat          # Her 5 dakika

# Test scriptleri
test_ai_connection.py        # Cloud AI test
test_local_ai.py             # Lokal AI test
test_asterisk_ai.py          # Asterisk AI test
```

---

## 🏗️ MİMARİ

```
┌─────────────────────────────────────────────────────┐
│              WEB INTERFACE (PHP)                    │
│         arama_yonetimi.php, login.php               │
└────────────────────┬────────────────────────────────┘
                     │
         ┌───────────┴───────────┐
         │                       │
┌────────▼────────┐    ┌────────▼─────────┐
│  MySQL Database │    │   PHP Backend    │
│  (CALLS, etc.)  │    │   (AutoCall      │
│                 │    │    Manager)      │
└────────┬────────┘    └────────┬─────────┘
         │                      │
         └──────────┬───────────┘
                    │
         ┌──────────▼───────────┐
         │   ASTERISK PBX       │
         │   Call Files         │
         └──────────┬───────────┘
                    │
         ┌──────────▼───────────┐
         │   AGI SCRIPT         │
         │   (Python)           │
         └──────────┬───────────┘
                    │
         ┌──────────▼───────────┐
         │   AI API             │
         │   OpenAI / Ollama    │
         └──────────┬───────────┘
                    │
         ┌──────────▼───────────┐
         │   CUSTOMER           │
         │   (Phone Call)       │
         └──────────────────────┘
```

---

## 🎨 EKRAN GÖRÜNTÜLERİ

### Ana Dashboard
![Dashboard](docs/screenshots/dashboard.png)

### Arama Yönetimi
![Call Management](docs/screenshots/call-management.png)

### Raporlar
![Reports](docs/screenshots/reports.png)

---

## 🔧 YÖNETİM PANELİ

### Menü Yapısı
- 🏠 **Dashboard** - Genel istatistikler
- 📁 **Projeler** - Proje yönetimi
- 📄 **Mesaj Şablonları** - TTS/AI şablon yönetimi
- 📞 **Arama Yönetimi** - Excel yükleme, arama başlatma
- 📊 **Raporlar** - İstatistikler, Excel export
- 👥 **Kullanıcılar** - Kullanıcı ve rol yönetimi
- ⏰ **Zamanlar** - Çalışma saatleri ayarı
- 🎨 **Ayarlar** - Sistem ayarları

---

## 💰 MALİYET ANALİZİ

### Cloud AI (OpenAI)
```
Arama başı: ~$0.35
1000 arama/gün: $350/gün = $10,500/ay
```

### Lokal AI (Offline)
```
İlk yatırım: $500 (GPU)
Arama başı: ~$0.001 (sadece elektrik)
1000 arama/gün: ~$15/ay (elektrik)

ROI: 1.5 gün! 🎉
```

---

## 🤝 KATKI SAĞLAMA

Katkılarınızı bekliyoruz! Lütfen:

1. Fork yapın
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Commit yapın (`git commit -m 'Add amazing feature'`)
4. Push edin (`git push origin feature/amazing-feature`)
5. Pull Request açın

---

## 📝 LİSANS

Bu proje MIT lisansı altında lisanslanmıştır. Detaylar için [LICENSE](LICENSE) dosyasına bakın.

---

## ⚠️ YASAL UYARI

Bu sistem otomatik arama yapar. Kullanırken:

- ✅ **KVKK** uyumlu kullanın
- ✅ **TİCİS** izinlerini kontrol edin
- ✅ Çalışma saatleri dışında (22:00-08:00) aramayın
- ✅ İzinsiz kişileri aramayın

**Sorumluluk:** Kullanıcı sistemin hukuka uygun kullanımından sorumludur.

---

## 🆘 DESTEK

### İletişim
- **Email:** support@anabilim.com
- **Telefon:** 0212 999 99 99
- **Website:** https://anabilim.com

### Dokümantasyon
- **GitHub:** https://github.com/your-username/AnabilimOtomatikArama
- **Wiki:** https://github.com/your-username/AnabilimOtomatikArama/wiki

### Sorun Bildirimi
GitHub Issues: https://github.com/your-username/AnabilimOtomatikArama/issues

---

## 📊 PROJE İSTATİSTİKLERİ

- **Başlangıç:** 2024
- **Toplam Kod:** 50,000+ satır
- **Diller:** PHP, Python, JavaScript, SQL
- **Test Coverage:** %80+
- **Production Ready:** ✅

---

## 🎖️ TEŞEKKÜRLER

- [Asterisk](https://asterisk.org) - Harika PBX sistemi
- [OpenAI](https://openai.com) - GPT-4 ve Whisper
- [Ollama](https://ollama.com) - Lokal LLM altyapısı
- [Piper](https://github.com/rhasspy/piper) - TTS sistemi

---

## 🗺️ ROADMAP

### v2.1 (Q1 2027)
- [ ] Çoklu dil desteği (İngilizce, Almanca)
- [ ] WhatsApp entegrasyonu
- [ ] SMS kampanya özelliği
- [ ] API endpoint'leri

### v2.2 (Q2 2027)
- [ ] Mobil uygulama (Android/iOS)
- [ ] Video arama desteği
- [ ] Gelişmiş AI analytics
- [ ] CRM entegrasyonları

### v3.0 (Q3 2027)
- [ ] Multi-tenant mimari
- [ ] SaaS modeli
- [ ] Marketplace (şablon satışı)
- [ ] White-label çözüm

---

## 🌟 YILDIZ VERİN!

Bu projeyi beğendiyseniz ⭐ vermeyi unutmayın!

---

**Made with ❤️ in Turkey**

© 2024-2026 Anabilim. All rights reserved.
