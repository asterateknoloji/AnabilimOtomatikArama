═══════════════════════════════════════════════════════════
  ÖRNEK IMPORT DOSYASI KULLANIMI
═══════════════════════════════════════════════════════════

📁 DOSYA: ornek_import.csv

Bu dosya örnek bir import dosyasıdır. Kendi Excel dosyanızı 
oluşturmak için bu formatı kullanın.

═══════════════════════════════════════════════════════════
  EXCEL FORMATI
═══════════════════════════════════════════════════════════

Sütunlar (A-F):
┌─────────────────────────────────────────────────────────┐
│ A: Telefon  (Zorunlu)                                   │
│ B: Ad Soyad (Zorunlu)                                   │
│ C: Tutar    (Zorunlu)                                   │
│ D: Metin1   (Opsiyonel)                                 │
│ E: Metin2   (Opsiyonel)                                 │
│ F: Metin3   (Opsiyonel)                                 │
└─────────────────────────────────────────────────────────┘

═══════════════════════════════════════════════════════════
  ÖRNEK SATIRLAR
═══════════════════════════════════════════════════════════

Telefon         Ad Soyad          Tutar    Metin1
─────────────────────────────────────────────────────────
05301234567     Ahmet Yılmaz      1250.50  Ocak ayı ödemesi
05321234568     Ayşe Demir        2450.75  Şubat ayı ödemesi
05331234569     Mehmet Kaya       875.00   Mart ayı taksiti

═══════════════════════════════════════════════════════════
  CSV'Yİ EXCEL'E ÇEVİRME
═══════════════════════════════════════════════════════════

1. ornek_import.csv dosyasına sağ tıklayın
2. "Birlikte Aç" > "Excel" seçin
3. Dosya > Farklı Kaydet > Excel Çalışma Kitabı (.xlsx)
4. Kaydedin

VEYA

1. Excel'i açın
2. Veri > Metin/CSV'den
3. ornek_import.csv dosyasını seçin
4. Virgül (,) ayırıcı olarak seçin
5. Yükle

═══════════════════════════════════════════════════════════
  KENDİ EXCEL DOSYANIZI OLUŞTURMA
═══════════════════════════════════════════════════════════

1. Excel'de yeni bir dosya açın

2. İlk satıra başlıkları yazın:
   A1: Telefon
   B1: Ad Soyad
   C1: Tutar
   D1: Metin1
   E1: Metin2
   F1: Metin3

3. 2. satırdan itibaren müşteri bilgilerini girin

4. Kaydedin (.xlsx veya .xls formatında)

═══════════════════════════════════════════════════════════
  TELEFON FORMATI
═══════════════════════════════════════════════════════════

✓ Doğru formatlar:
  - 05301234567
  - 5301234567
  - +905301234567
  - 0 (530) 123 45 67

✗ Yanlış formatlar:
  - 530 123 45 67 (başta 0 eksik)
  - (530) 123-4567 (eksik rakam)

Sistem otomatik olarak düzeltir!

═══════════════════════════════════════════════════════════
  TUTAR FORMATI
═══════════════════════════════════════════════════════════

✓ Doğru formatlar:
  - 1250.50
  - 1250,50
  - 1.250,50
  - 1,250.50

Sistem sayı olarak algılar ve Türkçe okur:
"bin ikiyüz elli Türk Lirası elli kuruş"

═══════════════════════════════════════════════════════════
  IMPORT İŞLEMİ
═══════════════════════════════════════════════════════════

1. Web paneline girin:
   http://localhost:8009/OtomatikArama/import.php

2. Proje seçin (veya yeni oluşturun)

3. Mesaj şablonu seçin (opsiyonel)

4. Zamanlama ayarlayın (opsiyonel)

5. Excel dosyanızı seçin

6. "Excel'i İçe Aktar" tıklayın

═══════════════════════════════════════════════════════════
  İPUÇLARI
═══════════════════════════════════════════════════════════

💡 Başlık satırını atlayabilirsiniz
   Sistem otomatik algılar

💡 Boş metin alanları sorun değil
   Zorunlu değiller

💡 Büyük dosyalar için
   Max 10.000 satır tavsiye edilir

💡 Dosya adı önemli değil
   Sistem otomatik yeniden adlandırır:
   2026-01-27_OriginalIsim.xlsx

═══════════════════════════════════════════════════════════
  SORU VE SORUNLAR
═══════════════════════════════════════════════════════════

❓ "Dosya yüklenemiyor"
   → Maksimum 10MB, .xlsx veya .xls olmalı

❓ "Telefon numarası geçersiz"
   → 11 haneli olmalı (05XXXXXXXXX)

❓ "Tutar okunamıyor"
   → Sadece rakam ve . , kullanın

❓ "Türkçe karakterler bozuk"
   → Excel'de UTF-8 olarak kaydedin

═══════════════════════════════════════════════════════════
