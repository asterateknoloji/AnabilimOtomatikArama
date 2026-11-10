# 🎯 Proje Bazlı Arama Yönetimi - Hızlı Başlangıç

## ✨ Yeni Özellik!

Artık **her proje için ayrı ayrı** arama yönetimi yapabilirsiniz!

## 📍 Hızlı Erişim

```
Ana Sayfa: http://localhost:8009/OtomatikArama/otomatikarama.php
Proje Aramaları: http://localhost:8009/OtomatikArama/proje_aramalar.php
```

## 🎯 Ne Yapabilirsiniz?

### 1. Proje Seçimi
Her proje için ayrı arama yönetimi:
- ✅ "Tahsilat Kampanyası" projesi → Ayrı yönetim
- ✅ "Hatırlatma Kampanyası" projesi → Ayrı yönetim
- ✅ "Anket Kampanyası" projesi → Ayrı yönetim

### 2. Esnek Arama Başlatma
İstediğiniz kadar aramayı başlatın:
- **1 arama** (test için)
- **5, 10, 20, 50, 100 arama** (batch başlatma)
- **TÜM aramalar** (toplu başlatma)

### 3. Hızlı Durdurma
Devam eden aramaları tek tuşla durdurun:
- ⏹️ **"TÜM ARAMALARI DURDUR"** butonu
- Anında tüm aramalar sonlanır

### 4. Gerçek Zamanlı Takip
Her proje için anlık istatistikler:
- 🟡 Bekleyen
- 🔵 Aranıyor  
- 🟢 Tamamlandı
- 🔴 Başarısız

## 🚀 3 Adımda Kullanım

### Adım 1: Proje Oluştur
```
📁 Projeler → ➕ Yeni Proje Oluştur
```

### Adım 2: Veri İçe Aktar
```
📥 Veri İçe Aktar → Proje Seç → Excel Yükle
```

### Adım 3: Aramaları Başlat
```
🎯 Proje Aramaları → Proje Seç → ▶️ X Adet Başlat
```

## 📊 Örnek Kullanım

```
Proje: "Ocak 2026 Tahsilat"
Durum:
  🟡 Bekleyen: 245
  🔵 Aranıyor: 10
  🟢 Tamamlandı: 1.234
  🔴 Başarısız: 45
  📊 Toplam: 1.534

Aksiyon:
  ▶️ 50 Adet Başlat → Tıkla
  ✅ 50 arama başlatıldı!
```

## 🎨 Yeni Dosyalar

```
proje_aramalar.php                    → Ana arama yönetim sayfası
PROJE_BAZLI_ARAMA_YONETIMI.md        → Detaylı kullanım kılavuzu
README_PROJE_ARAMALARI.md            → Bu dosya (hızlı başlangıç)

Güncellenmiş:
includes/AutoCallManager.php          → startProjectCalls() + stopProjectCalls()
includes/ProjectManager.php           → getProjectImports()
projeler.php                          → Link eklendi
sablonlar.php                         → Link eklendi
import.php                            → Link eklendi
```

## 💡 İpuçları

1. **İlk Kullanım**: 1 arama ile test edin
2. **Normal Kullanım**: 10-50 arama başlatın
3. **Toplu Kullanım**: "TÜM ARAMALARI BAŞLAT" kullanın
4. **Sorun Durumu**: "TÜM ARAMALARI DURDUR" ile sonlandırın

## 📱 Navigasyon

Her sayfada üst menüde:
```
🏠 Ana Sayfa
📁 Projeler
🎯 Proje Aramaları  ← YENİ!
📥 Veri İçe Aktar
📝 Şablonlar
📊 Raporlar
```

## 🔄 Otomatik Yenileme

Proje Aramaları sayfası **30 saniyede bir** otomatik yenilenir!
- ✅ Güncel istatistikler
- ✅ Anlık durum
- ✅ Canlı takip

## 🎯 Avantajlar

### Önceki Sistem
```
❌ Tüm aramalar bir arada
❌ Proje farkı yok
❌ Toplu kontrol
❌ Karışık istatistikler
```

### Yeni Sistem
```
✅ Her proje ayrı
✅ Bağımsız kontrol
✅ Esnek başlatma (1-100 arama)
✅ Proje bazlı istatistikler
✅ Hızlı durdurma
✅ Import geçmişi
```

## 🎉 Hemen Deneyin!

```
http://localhost:8009/OtomatikArama/proje_aramalar.php
```

**Başarılar! 🚀**
