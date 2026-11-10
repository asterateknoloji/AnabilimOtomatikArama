# 📋 Arama Yönetimi - Kullanım Kılavuzu

## 🎯 Genel Bakış

Her import dosyası için:
- ✅ Proje atama
- ✅ Tekrar sayısı belirleme  
- ✅ START/STOP ile tek tek kontrol
- ✅ Canlı durum takibi

## 📖 Kullanım Adımları

### 1️⃣ Sayfayı Açın
```
http://localhost:8009/OtomatikArama/arama_yonetimi.php
```

### 2️⃣ Her Dosya İçin Ayarları Yapın

#### Tablo Sütunları:
| Sütun | Açıklama |
|-------|----------|
| **Saat** | Import tarihi ve saati |
| **Dosya Adı** | Orijinal dosya ismi ve kayıt sayısı |
| **Proje** | Dropdown ile proje seçimi |
| **Tekrar** | Kaç arama başlatılacak |
| **İstatistikler** | Bekleyen, Aranıyor, Tamamlandı, Başarısız |
| **İşlem** | START/STOP butonları |

### 3️⃣ Proje Atama

```
1. "Proje" sütunundaki dropdown'ı açın
2. İstediğiniz projeyi seçin
3. Otomatik kaydedilir ✅
```

**Örnek:**
```
Dosya: 2026-01-27_test_import.csv
Proje: [10.Kas ▼]
       └─ 10.Kas
       └─ ABC
       └─ XYZ
       └─ KLM
```

### 4️⃣ Tekrar Sayısı Belirleme

```
1. "Tekrar" sütunundaki input'a sayı girin
2. Minimum: 1
3. Maximum: Bekleyen arama sayısı
```

**Örnek:**
```
Bekleyen: 4 arama
Tekrar: [10] → Otomatik 4'e düşer
Tekrar: [2]  → 2 arama başlatır
```

### 5️⃣ START Butonu

```
▶️ START butonuna tıklayın
```

**Kontroller:**
- ❌ Proje seçilmemişse → Uyarı verir
- ❌ Tekrar sayısı geçersizse → Uyarı verir
- ✅ Tüm kontroller OK → Aramalar başlar

**Başlatma Sonrası:**
```
Durum: 🔄 Çalışıyor
Buton: ⏹️ STOP (görünür)
Satır: Mavi renkli (çalışan göstergesi)
```

### 6️⃣ STOP Butonu

```
Arama çalışırken:
⏹️ STOP butonuna tıklayın
```

**Durdurma Sonrası:**
```
Çalışan aramalar tamamlandı olarak işaretlenir
Bekleyen aramalar durur
```

## 📊 Durum Göstergeleri

### İstatistik Rozetleri:
| Rozet | Anlamı |
|-------|--------|
| 🟡 X | X adet bekleyen arama |
| 🔵 X | X adet şu an aranıyor |
| 🟢 X | X adet tamamlandı |
| 🔴 X | X adet başarısız |

### İşlem Durumları:
| Durum | Görünüm |
|-------|---------|
| **Bekliyor** | ▶️ START butonu |
| **Çalışıyor** | ⏹️ STOP butonu + 🔄 Çalışıyor rozeti |
| **Tamamlandı** | ✅ Tamamlandı rozeti (işlem yok) |

## 🎯 Kullanım Senaryoları

### Senaryo 1: 2 Dosyayı Başlatma
```
1. Dosya 1:
   Proje: ABC
   Tekrar: 10
   ▶️ START

2. Dosya 2:
   Proje: XYZ
   Tekrar: 20
   ▶️ START

Sonuç: İki dosya bağımsız çalışır
```

### Senaryo 2: 4 Dosyayı Aynı Anda
```
Dosya 1 → Proje: ABC → Tekrar: 5  → ▶️ START
Dosya 2 → Proje: ABC → Tekrar: 10 → ▶️ START
Dosya 3 → Proje: XYZ → Tekrar: 15 → ▶️ START
Dosya 4 → Proje: KLM → Tekrar: 20 → ▶️ START

Toplam: 50 arama aynı anda çalışır
```

### Senaryo 3: Çalışanı Durdurma
```
Dosya 1 → 🔄 Çalışıyor → ⏹️ STOP
Dosya 2 → 🔄 Çalışıyor → ⏹️ STOP

Dosya 3 → ▶️ Çalışmıyor → Dokunma

Sonuç: Sadece 1 ve 2 durur, 3 etkilenmez
```

### Senaryo 4: Farklı Tekrar Sayıları
```
10 dosya var, hepsine farklı sayı:

Dosya 1 → 1 arama
Dosya 2 → 5 arama
Dosya 3 → 10 arama
Dosya 4 → TÜM (50 arama)
...

Her biri START ile ayrı ayrı başlatılır
```

## ⚙️ Otomatik İşlemler

### Proje Seçimi
```
Dropdown değiştirince → Otomatik kaydedilir (AJAX)
```

### Sayfa Yenileme
```
Çalışan arama varsa → 5 saniyede bir otomatik yenilenir
```

### Limit Kontrolü
```
Tekrar sayısı > Bekleyen → Otomatik max'a düşürülür
```

## 🎨 Görsel İpuçları

### Satır Renkleri:
- **Beyaz/Gri**: Normal durum (bekliyor veya tamamlandı)
- **Mavi**: Çalışıyor (🔄 animasyonlu)

### Hover Efekti:
- Fare üzerine gelince → Satır sağa kayar
- Tıklanabilir alanlar → İmleç değişir

## 💡 İpuçları

### ✅ İyi Pratikler:

1. **Test Et**: İlk kullanımda 1-2 arama ile test edin
2. **Proje Seç**: START'tan önce mutlaka proje atayın
3. **Küçük Başla**: İlk deneme için küçük sayılarla başlayın
4. **Durum İzle**: İstatistiklere bakarak ilerleme takibi yapın
5. **STOP Kullan**: Hata gördüğünüzde hemen durdurun

### ❌ Kaçınılması Gerekenler:

1. ❌ Proje seçmeden START'a basmayın
2. ❌ Çok büyük sayılarla ilk denemede başlamayın
3. ❌ Sayfayı kapatmayın (aramalar devam eder ama kontrol edemezsiniz)
4. ❌ Aynı anda çok fazla dosya başlatmayın (sunucu yükü)

## 🔧 Sorun Giderme

### Proje seçilmiyor
```
Çözüm: Sayfayı yenileyin (F5)
```

### START çalışmıyor
```
Kontrol:
1. Proje seçili mi?
2. Tekrar sayısı geçerli mi?
3. Bekleyen arama var mı?
```

### STOP çalışmıyor
```
Kontrol:
1. Gerçekten çalışıyor mu? (Mavi satır olmalı)
2. Sayfayı yenileyin ve tekrar deneyin
```

### Sayfa yenilenmiyor
```
Çözüm: Manuel F5 yapın
```

## 📈 Örnek Ekran Görünümü

```
┌─────────────────────────────────────────────────────────────────────┐
│ 📋 Arama Yönetimi                                                    │
│ Her dosya için proje atayın, tekrar sayısını belirleyin             │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│ Saat        Dosya Adı              Proje   Tekrar  İstatistik İşlem│
│ ────────────────────────────────────────────────────────────────────│
│ 27.01.2026  2026-01-27_test.csv    [10.Kas▼] [10]  🟡2 🔵2   ⏹️STOP│
│ 22:52       📊 4 kayıt                              🔄 Çalışıyor     │
│                                                     50% tamamlandı   │
│ ────────────────────────────────────────────────────────────────────│
│ 27.01.2026  2026-01-27_ornek.csv   [ABC▼]   [5]   🟡4          ▶️START│
│ 22:50       📊 4 kayıt                                              │
│                                                     0% tamamlandı    │
│ ────────────────────────────────────────────────────────────────────│
│ 27.01.2026  2026-01-27_ornek1.csv  -        -     🟢4 ✅ Tamamlandı│
│ 22:39       📊 4 kayıt                              100% tamamlandı │
└─────────────────────────────────────────────────────────────────────┘
```

## 🚀 Hızlı Başlangıç

```
1. http://localhost:8009/OtomatikArama/arama_yonetimi.php
2. Her satır için:
   - Proje seç
   - Tekrar sayısı gir
   - START bas
3. İzle ve kontrol et
```

## ⚡ Önemli Notlar

1. **Bağımsız Kontrol**: Her dosya diğerlerinden bağımsız çalışır
2. **Eşzamanlı**: Birden fazla dosya aynı anda çalışabilir
3. **Canlı Takip**: Durum güncellemeleri otomatik
4. **Güvenli Durdurma**: STOP ile anında durdurabilirsiniz
5. **Proje Bazlı**: Her dosya farklı projeye atanabilir

---

**Başarılar! 🎉**
