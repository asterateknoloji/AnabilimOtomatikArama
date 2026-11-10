# 🎯 Proje Bazlı Arama Yönetimi

## 📋 Genel Bakış

Artık her proje için ayrı ayrı arama yönetimi yapabilirsiniz! Her projenin aramaları bağımsız olarak kontrol edilir.

## 🚀 Özellikler

### 1. **Proje Bazlı Arama Başlatma**
Her proje için istediğiniz kadar aramayı başlatabilirsiniz:
- ▶️ **1 Adet**: Tek arama (test için ideal)
- ▶️ **5 Adet**: Küçük batch
- ▶️ **10 Adet**: Orta batch
- ▶️ **20 Adet**: Büyük batch
- ▶️ **50 Adet**: Çok büyük batch
- ▶️ **100 Adet**: Yüksek kapasiteli batch
- ⚡ **TÜM ARAMALARI BAŞLAT**: Tüm bekleyen aramalar

### 2. **Aramayı Durdurma**
Proje bazlı devam eden aramaları tek tuşla durdurun:
- ⏹️ Tüm devam eden aramalar sonlandırılır
- Status otomatik "Tamamlandı" olur
- Bekleyen aramalar etkilenmez

### 3. **Gerçek Zamanlı İstatistikler**
Her proje için anlık durum:
- 🟡 **Bekleyen**: Aranmayı bekleyen kayıtlar
- 🔵 **Aranıyor**: Şu anda devam eden aramalar
- 🟢 **Tamamlandı**: Başarıyla tamamlanan aramalar
- 🔴 **Başarısız**: Başarısız olan aramalar
- 📊 **Toplam**: Tüm kayıtlar

### 4. **Import Geçmişi**
Her projenin son import'larını görün:
- Tarih ve saat
- Dosya adı
- Kayıt sayısı
- Durum

### 5. **Otomatik Yenileme**
Sayfa her 30 saniyede bir otomatik yenilenir, güncel durumu gösterir.

## 📖 Kullanım Adımları

### Adım 1: Proje Oluşturun

```
http://localhost:8009/OtomatikArama/projeler.php
```

1. **"➕ Yeni Proje Oluştur"** bölümünü doldurun
2. Proje Adı: Örn: "Ocak 2026 Tahsilat"
3. Açıklama: Proje hakkında kısa bilgi
4. Varsayılan Şablon: Mesaj şablonu seçin
5. **"💾 Proje Oluştur"** butonuna tıklayın

### Adım 2: Veri İçe Aktarın

```
http://localhost:8009/OtomatikArama/import.php
```

1. **Proje Seçin**: Açılan menüden projenizi seçin
2. **Şablon Seçin**: Mesaj şablonunu seçin
3. **Excel Dosyası Yükleyin**: Müşteri verilerinizi yükleyin
4. **"📤 Veri Yükle ve Başlat"** butonuna tıklayın

### Adım 3: Proje Aramalarını Yönetin

```
http://localhost:8009/OtomatikArama/proje_aramalar.php
```

#### Arama Başlatma

Her proje kartında:

1. **Küçük Test** (1-5 arama):
   - İlk defa kullanıyorsanız **"▶️ 1 Adet"** ile başlayın
   - Sistem çalışıyor mu test edin
   - Her şey yolundaysa **"▶️ 5 Adet"** ile devam edin

2. **Normal Kullanım** (10-50 arama):
   - Sistem stabil çalışıyorsa **"▶️ 10 Adet"** veya **"▶️ 20 Adet"**
   - Yüksek kapasitede **"▶️ 50 Adet"** veya **"▶️ 100 Adet"**

3. **Toplu Başlatma**:
   - Tüm bekleyenleri başlatmak için **"⚡ TÜM ARAMALARI BAŞLAT"**
   - ⚠️ Dikkat: Bu seçenek onay ister

#### Aramayı Durdurma

Devam eden aramalar varsa:

1. **"⏹️ TÜM ARAMALARI DURDUR"** butonu görünür
2. Butona tıklayın
3. Onay verin
4. Tüm devam eden aramalar sonlanır

## 📊 İstatistikleri Anlama

### Proje Kartı Başlığı

```
Proje Adı: "Ocak 2026 Tahsilat"
Toplam Import: 3
```

### İstatistik Kutuları

```
🟡 Bekleyen: 245        → Aranmayı bekliyor
🔵 Aranıyor: 10         → Şu anda arıyor
🟢 Tamamlandı: 1.234    → Başarıyla tamamlandı
🔴 Başarısız: 45        → Meşgul/cevapsız/hata
📊 Toplam: 1.534        → Tüm kayıtlar
```

### Import Geçmişi Tablosu

```
Tarih: 27.01.2026 22:52
Dosya: 2026-01-27_test_import1.csv
Kayıt: 2
Durum: 🟢 completed
```

## 🎯 Örnek Senaryolar

### Senaryo 1: İlk Kullanım

```
1. Proje oluşturun: "Test Projesi"
2. 5 kayıt içe aktarın
3. Proje Aramaları sayfasına gidin
4. "▶️ 1 Adet" ile test edin
5. Telefon çaldı mı kontrol edin
6. Başarılıysa "▶️ 5 Adet" ile devam edin
```

### Senaryo 2: Büyük Kampanya

```
1. Proje: "Şubat 2026 Tahsilat Kampanyası"
2. 5.000 kayıt içe aktarın
3. İlk gün: "▶️ 100 Adet" ile başlayın
4. Sorun yoksa: "▶️ 100 Adet" daha başlatın
5. Günlük 500-1000 arama yapın
6. İstatistikleri takip edin
```

### Senaryo 3: Çoklu Proje Yönetimi

```
Proje 1: "Tahsilat" → 1.000 kayıt
  - Sabah: 100 arama başlat
  - Öğlen: 100 arama daha
  - Akşam: Kalan 800'ü başlat

Proje 2: "Hatırlatma" → 500 kayıt
  - Öğleden sonra: 50 arama başlat
  - Sorun yoksa: 200 arama daha

Proje 3: "Anket" → 200 kayıt
  - Akşam: Tümünü başlat
```

## ⚡ Hız Optimizasyonu

### Düşük Hız (Güvenli)
- Her seferinde 10-20 arama başlatın
- 5-10 dakika bekleyin
- Tekrar 10-20 arama başlatın
- **Avantaj**: Sistem stabil kalır
- **Dezavantaj**: Yavaş ilerler

### Orta Hız (Önerilen)
- Her seferinde 50-100 arama başlatın
- 2-3 dakika bekleyin
- Tekrar 50-100 arama başlatın
- **Avantaj**: Dengeli hız
- **Dezavantaj**: -

### Yüksek Hız (Riskli)
- Tümünü birden başlatın
- **Avantaj**: Çok hızlı
- **Dezavantaj**: Sistem yavaşlayabilir, hatalar artabilir

## 🛠️ Sorun Giderme

### Sorun 1: "0 arama başlatıldı"

**Sebep**: Bekleyen arama yok

**Çözüm**: 
- Import geçmişine bakın
- Veri İçe Aktar sayfasından yeni veri yükleyin

### Sorun 2: Aramalar "Aranıyor" durumunda takılı

**Sebep**: AGI scriptleri çalışmıyor

**Çözüm**: 
- "⏹️ TÜM ARAMALARI DURDUR" butonuna tıklayın
- Kayıtlar "Tamamlandı" olacak
- Yeni arama başlatabilirsiniz

### Sorun 3: Çok fazla başarısız arama

**Sebep**: 
- Telefon numaraları yanlış
- Asterisk bağlantı problemi
- Müşteriler cevaplamıyor

**Çözüm**:
- Raporlar sayfasından detaylı analiz yapın
- Başarısız aramaları Excel'e aktarın
- Numaraları kontrol edin

## 📱 Mobil Uyumlu

Proje Aramaları sayfası mobil cihazlarda da çalışır:
- Tablet'ten kontrol edebilirsiniz
- Telefon'dan istatistik görebilirsiniz
- Her cihazdan yönetebilirsiniz

## 🔄 Otomatik Yenileme

Sayfa her 30 saniyede bir otomatik yenilenir:
- ✅ Güncel istatistikler
- ✅ Devam eden aramalar
- ✅ Yeni tamamlananlar

Manuel yenilemek için **F5** tuşuna basın.

## 📞 İpuçları

1. **Test Edin**: Her zaman küçük batch'lerle başlayın
2. **İstatistikleri Takip Edin**: Başarı oranını gözlemleyin
3. **Durdurma Butonu**: Sorun olursa hemen durdurun
4. **Çoklu Proje**: Aynı anda birden fazla projeyi yönetin
5. **Zamanlama**: Yoğun saatlerde arama yapın

## 🎉 Başarı Kriterleri

```
✅ %80+ Başarı Oranı: Mükemmel!
✅ %60-80 Başarı Oranı: İyi
⚠️ %40-60 Başarı Oranı: Orta (Numaraları kontrol edin)
❌ <%40 Başarı Oranı: Sorun var (Teknik destek)
```

---

**Başarılı aramalar! 🚀**
