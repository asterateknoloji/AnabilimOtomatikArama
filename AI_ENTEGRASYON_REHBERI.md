# 🤖 SESLİ AI ASISTAN ENTEGRASYON REHBERİ
## Otomatik Arama Sistemine AI Asistan Ekleme

---

**Versiyon:** 1.0  
**Tarih:** 7 Temmuz 2026  
**Hazırlayan:** AI Integration Team  
**Geçerlilik:** AnabilimOtomatikArama Sistemi

---

## 📑 İÇİNDEKİLER

1. [Giriş](#1-giriş)
2. [AI Servisleri Karşılaştırması](#2-ai-servisleri-karşılaştırması)
3. [Önerilen Mimari](#3-önerilen-mimari)
4. [Kurulum Adımları](#4-kurulum-adımları)
5. [Prompt Tasarımı](#5-prompt-tasarımı)
6. [Asterisk Entegrasyonu](#6-asterisk-entegrasyonu)
7. [Veritabanı Değişiklikleri](#7-veritabanı-değişiklikleri)
8. [Maliyet Analizi](#8-maliyet-analizi)
9. [Test Senaryoları](#9-test-senaryoları)
10. [İleri Seviye Özellikler](#10-ileri-seviye-özellikler)

---

## 1. GİRİŞ

### 1.1 Neden AI Asistan?

#### Şu Anki Sistem (TTS):
```
❌ Sabit mesaj oynatır
❌ Sadece DTMF (tuş basımı) alır
❌ Soru soramaz / cevap veremez
❌ Bağlamsal konuşma yok
❌ Müşteri deneyimi düşük
```

#### AI Destekli Sistem:
```
✅ Doğal dil ile konuşur
✅ Müşteri sorularını anlar ve cevaplar
✅ Bağlamsal konuşma yapar
✅ Müşteri memnuniyeti yüksek
✅ Ödeme taahhüdü alma şansı artar
✅ İnsan gibi davranır
```

---

### 1.2 Kullanım Senaryoları

#### Senaryo 1: Borç Hatırlatma
```
AI: "Merhaba Ahmet Bey, ben Astera'dan sesli asistanınızım. 
     150 TL ödenmemiş faturanız bulunuyor. Ödeme hakkında 
     bilgi vermek istiyorum, uygun musunuz?"

Müşteri: "Evet, dinliyorum."

AI: "Harika! Faturanız 15 Temmuz'a kadar ödenmelidir. 
     İsterseniz kredi kartınızla şimdi ödeyebilir veya 
     taksitlendirme yapabiliriz. Nasıl ilerlemek istersiniz?"

Müşteri: "Taksitle yapabilir miyim?"

AI: "Elbette! 3 taksit seçeneğimiz var, her taksit 50 TL 
     olacak şekilde. Kabul ediyor musunuz?"

Müşteri: "Tamam, kabul."

AI: "Mükemmel! Taksit planınızı oluşturdum. SMS ile detayları 
     göndereceğim. İyi günler Ahmet Bey!"
```

#### Senaryo 2: Randevu Hatırlatma
```
AI: "Merhaba Ayşe Hanım, yarın saat 14:00'te kliniğimizde 
     randevunuz var. Gelebilecek misiniz?"

Müşteri: "Hayır, gelemeyeceğim."

AI: "Anlıyorum. Randevunuzu iptal edeyim mi yoksa başka 
     bir tarih önerebilir miyim?"

Müşteri: "Başka tarih olabilir mi?"

AI: "Elbette. Gelecek hafta Çarşamba 10:00 veya Cuma 15:00 
     uygun mu?"

Müşteri: "Çarşamba olsun."

AI: "Harika! Randevunuzu 15 Temmuz Çarşamba 10:00'a aldım. 
     SMS gönderiyorum. İyi günler!"
```

---

## 2. AI SERVİSLERİ KARŞILAŞTIRMASI

### 2.1 Servis Tablosu

| Servis | Türkçe | Latency | Kalite | Maliyet/dk | Kolay Kurulum |
|--------|--------|---------|--------|------------|---------------|
| **OpenAI Realtime API** | ✅ Mükemmel | 🟢 ~200ms | 🌟🌟🌟🌟🌟 | $0.06-0.24 | 🟢 Kolay |
| **ElevenLabs Conv AI** | ✅ İyi | 🟢 ~300ms | 🌟🌟🌟🌟 | $0.10 | 🟢 Kolay |
| **Azure Speech + GPT** | ✅ İyi | 🟡 ~500ms | 🌟🌟🌟🌟 | $0.05-0.15 | 🟡 Orta |
| **Google Dialogflow** | ⚠️ Orta | 🟡 ~400ms | 🌟🌟🌟 | $0.05 | 🟡 Orta |
| **Anthropic Claude + TTS** | ✅ Mükemmel | 🔴 ~800ms | 🌟🌟🌟🌟🌟 | $0.03-0.20 | 🔴 Zor |
| **Lokal (Whisper+Llama)** | ✅ İyi | 🔴 ~1500ms | 🌟🌟🌟 | $0 (Donanım) | 🔴 Çok Zor |

---

### 2.2 ÖNERİLEN: OpenAI Realtime API

#### Neden OpenAI?

✅ **Düşük Latency:** 200-300ms (insan gibi)  
✅ **Türkçe Desteği:** GPT-4 Turbo ile mükemmel Türkçe  
✅ **Function Calling:** Veritabanı sorguları yapabilir  
✅ **Kolay Entegrasyon:** Python/Node.js SDK  
✅ **Ses Kalitesi:** Doğal ve akıcı  
✅ **Bağlamsal Konuşma:** Önceki diyaloğu hatırlar  

#### Özellikler:

```javascript
// Gerçek zamanlı ses aktarımı
WebSocket + Streaming Audio

// Function Calling
AI: "Müşterinin borcu ne kadar?"
  → getBorcInfo(musteri_id) çağrılır
  → Veritabanından çeker
AI: "150 TL borcunuz var."

// Kesintisiz Konuşma
Müşteri konuşurken bile AI dinler ve yanıt hazırlar
```

#### Maliyetler (2026):

| İşlem | Fiyat |
|-------|-------|
| **Audio Input** | $0.06 / dakika |
| **Audio Output** | $0.24 / dakika |
| **Text Tokens** | $0.01 / 1K token |
| **Toplam (ort.)** | **~$0.35 / arama** |

Örnek:
```
1000 arama/gün × $0.35 = $350/gün = ~$10.500/ay
```

---

## 3. ÖNERİLEN MİMARİ

### 3.1 Sistem Diyagramı

```
┌─────────────────────────────────────────────────────────┐
│                    KULLANICI ARAYÜZÜ                    │
│                 (arama_yonetimi.php)                    │
└────────────────────┬────────────────────────────────────┘
                     │
         ┌───────────┴───────────┐
         │                       │
┌────────▼────────┐    ┌────────▼─────────┐
│  CALLS DATABASE │    │   AI PROMPTS     │
│  (Müşteri Data) │    │   (Senaryolar)   │
└────────┬────────┘    └────────┬─────────┘
         │                      │
         └──────────┬───────────┘
                    │
         ┌──────────▼───────────┐
         │   ASTERISK PBX       │
         │   (Arama Başlat)     │
         └──────────┬───────────┘
                    │
         ┌──────────▼───────────┐
         │   AGI SCRIPT         │
         │   (Python/Node)      │
         └──────────┬───────────┘
                    │
         ┌──────────▼───────────┐
         │   OpenAI Realtime    │
         │   (AI Asistan)       │
         └──────────┬───────────┘
                    │
         ┌──────────▼───────────┐
         │   SIP STREAM         │
         │   (Audio ↔ Müşteri)  │
         └──────────────────────┘
```

---

### 3.2 Veri Akışı

```
1. Excel yükleme
   ↓
2. CALLS tablosuna kayıt (AI=1 flag)
   ↓
3. Aktif yapma
   ↓
4. Asterisk arama başlatır
   ↓
5. AGI script devreye girer:
   - Müşteri bilgilerini DB'den çeker
   - AI'a prompt gönderir
   - WebSocket bağlantısı açar
   ↓
6. OpenAI Realtime API:
   - Müşteri ile konuşur
   - Function call ile DB sorgular
   - Sonuç döner (başarılı/başarısız)
   ↓
7. AGI script sonucu CALLS'a yazar:
   - Status = 2 (Aranan)
   - AI_Response = "Taahhüt verdi"
   - AI_Sentiment = "Pozitif"
   ↓
8. Arama biter, kayıt tamamlanır
```

---

## 4. KURULUM ADIMLARI

### 4.1 Ön Hazırlık

#### Adım 1: OpenAI API Key Alma

```
1. https://platform.openai.com/ adresine git
2. Kayıt ol / Giriş yap
3. API Keys → Create new secret key
4. Key'i kopyala (tek seferlik gösterilir!)

Örnek Key:
sk-proj-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

#### Adım 2: Kredi Yükle

```
1. Billing → Add payment method
2. Kredi kartı ekle
3. $50-100 başlangıç kredisi yükle
```

#### Adım 3: Kullanım Limiti Ayarla

```
1. Billing → Usage limits
2. Hard limit: $500/ay (aşmayacak)
3. Soft limit: $400/ay (uyarı)
4. Email alerts: Aktif
```

---

### 4.2 Python Environment Kurulumu

#### Adım 1: Python 3.11+ Kur

```bash
# Windows
choco install python311

# Kontrol
python --version
# Python 3.11.x
```

#### Adım 2: Virtual Environment Oluştur

```bash
cd d:\xampp\htdocs\AnabilimOtomatikArama

# Virtual env oluştur
python -m venv venv_ai

# Aktif et
.\venv_ai\Scripts\activate

# Artık (venv_ai) öneki görünecek
```

#### Adım 3: Gerekli Paketleri Kur

```bash
# requirements_ai.txt oluştur
cat > requirements_ai.txt << EOF
openai==1.12.0
websockets==12.0
pydantic==2.6.0
python-dotenv==1.0.0
mysql-connector-python==8.3.0
asterisk-ami==0.1.5
pydub==0.25.1
EOF

# Kur
pip install -r requirements_ai.txt
```

---

### 4.3 Konfigürasyon Dosyası

#### Dosya: `.env`

```bash
# OpenAI
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxxxxxxxxxxxx
OPENAI_MODEL=gpt-4-turbo-realtime
OPENAI_VOICE=alloy  # Ses seçimi: alloy, echo, fable, onyx, nova, shimmer

# Database
DB_HOST=192.168.181.49
DB_NAME=AsteraWeb
DB_USER=astera
DB_PASS=astera2012

# Asterisk
ASTERISK_HOST=192.168.181.49
ASTERISK_AMI_PORT=5038
ASTERISK_AMI_USER=admin
ASTERISK_AMI_PASS=password

# AI Settings
AI_MAX_DURATION=180  # Maksimum 3 dakika konuşma
AI_TIMEOUT=10        # 10 saniye sessizlik = arama biter
AI_LANGUAGE=tr-TR    # Türkçe
AI_TEMPERATURE=0.7   # Yaratıcılık (0-1)
```

---

### 4.4 AI AGI Script Oluşturma

#### Dosya: `agi/ai_call_handler.py`

```python
#!/usr/bin/env python3
"""
AI Destekli Arama Handler
OpenAI Realtime API ile müşteri konuşması
"""

import os
import sys
import json
import asyncio
from dotenv import load_dotenv
from asterisk.agi import AGI
from openai import AsyncOpenAI
import mysql.connector

# Environment variables
load_dotenv()

class AICallHandler:
    def __init__(self):
        self.agi = AGI()
        self.client = AsyncOpenAI(api_key=os.getenv('OPENAI_API_KEY'))
        self.call_id = None
        self.call_data = {}
        
    def log(self, message):
        """AGI log"""
        self.agi.verbose(f"[AI] {message}")
        
    def get_call_data(self):
        """Veritabanından arama bilgilerini çek"""
        try:
            # Channel variables'dan Call ID al
            self.call_id = self.agi.get_variable('CALL_ID')
            
            # DB bağlantısı
            conn = mysql.connector.connect(
                host=os.getenv('DB_HOST'),
                database=os.getenv('DB_NAME'),
                user=os.getenv('DB_USER'),
                password=os.getenv('DB_PASS')
            )
            cursor = conn.cursor(dictionary=True)
            
            # Call bilgilerini çek
            cursor.execute("""
                SELECT c.*, p.ProjectName, mt.TemplateName, mt.AIPrompt
                FROM CALLS c
                LEFT JOIN PROJECTS p ON c.ProjectId = p.id
                LEFT JOIN MESSAGE_TEMPLATES mt ON c.TemplateId = mt.id
                WHERE c.id = %s
            """, (self.call_id,))
            
            self.call_data = cursor.fetchone()
            
            cursor.close()
            conn.close()
            
            self.log(f"Call data loaded for ID: {self.call_id}")
            return True
            
        except Exception as e:
            self.log(f"Error getting call data: {str(e)}")
            return False
            
    def build_prompt(self):
        """AI için prompt oluştur"""
        # Şablondan AI prompt al (varsa)
        base_prompt = self.call_data.get('AIPrompt', '')
        
        if not base_prompt:
            # Varsayılan prompt
            base_prompt = """
            Sen bir müşteri hizmetleri asistanısın. Görevin:
            1. Kibarca kendini tanıt
            2. Müşteriye borç/fatura bilgisi ver
            3. Ödeme seçeneklerini sun
            4. Ödeme taahhüdü al
            5. Nezaketle aramayı bitir
            
            Kurallar:
            - Türkçe konuş
            - Kibar ve profesyonel ol
            - Kısa ve net cümleler kur
            - Müşteriye baskı yapma
            - Dinle ve empati kur
            """
        
        # Müşteri bilgilerini ekle
        customer_context = f"""
        MÜŞTERİ BİLGİLERİ:
        - Ad Soyad: {self.call_data['Adi']}
        - Telefon: {self.call_data['PhoneNumber']}
        - Borç Tutarı: {self.call_data['Tutar']} TL
        - Metin 1: {self.call_data.get('Metin1', 'Yok')}
        - Metin 2: {self.call_data.get('Metin2', 'Yok')}
        - Metin 3: {self.call_data.get('Metin3', 'Yok')}
        """
        
        return base_prompt + "\n\n" + customer_context
        
    async def handle_ai_conversation(self):
        """OpenAI Realtime API ile konuşma"""
        try:
            self.log("Starting AI conversation...")
            
            # Prompt hazırla
            prompt = self.build_prompt()
            
            # WebSocket bağlantısı aç
            async with self.client.audio.transcriptions.create(
                model="whisper-1",
                file=audio_stream,  # Asterisk'ten gelen ses
                language="tr"
            ) as transcription:
                
                # AI'ya gönder
                response = await self.client.chat.completions.create(
                    model="gpt-4-turbo",
                    messages=[
                        {"role": "system", "content": prompt},
                        {"role": "user", "content": transcription.text}
                    ],
                    stream=True,
                    temperature=float(os.getenv('AI_TEMPERATURE', 0.7))
                )
                
                # Response'u TTS'e dönüştür ve oynat
                async for chunk in response:
                    if chunk.choices[0].delta.content:
                        text = chunk.choices[0].delta.content
                        await self.play_ai_response(text)
                        
            self.log("AI conversation completed")
            return True
            
        except Exception as e:
            self.log(f"AI conversation error: {str(e)}")
            return False
            
    async def play_ai_response(self, text):
        """AI cevabını TTS ile oynat"""
        try:
            # OpenAI TTS
            response = await self.client.audio.speech.create(
                model="tts-1",
                voice=os.getenv('OPENAI_VOICE', 'alloy'),
                input=text
            )
            
            # Audio'yu Asterisk'e gönder
            audio_file = f"/tmp/ai_response_{self.call_id}.mp3"
            response.stream_to_file(audio_file)
            
            # Asterisk'te oynat
            self.agi.stream_file(audio_file.replace('.mp3', ''))
            
        except Exception as e:
            self.log(f"TTS error: {str(e)}")
            
    def save_results(self, success, ai_response, sentiment):
        """Sonuçları veritabanına kaydet"""
        try:
            conn = mysql.connector.connect(
                host=os.getenv('DB_HOST'),
                database=os.getenv('DB_NAME'),
                user=os.getenv('DB_USER'),
                password=os.getenv('DB_PASS')
            )
            cursor = conn.cursor()
            
            cursor.execute("""
                UPDATE CALLS 
                SET Status = 2,
                    EndCallTime = NOW(),
                    SipCode = %s,
                    AI_Response = %s,
                    AI_Sentiment = %s,
                    Locked = 0
                WHERE id = %s
            """, (
                200 if success else 503,
                ai_response,
                sentiment,
                self.call_id
            ))
            
            conn.commit()
            cursor.close()
            conn.close()
            
            self.log("Results saved to database")
            
        except Exception as e:
            self.log(f"Error saving results: {str(e)}")
            
    def run(self):
        """Ana akış"""
        try:
            # AGI başlat
            self.agi.answer()
            
            # Call data al
            if not self.get_call_data():
                self.agi.hangup()
                return
                
            # AI konuşması
            success = asyncio.run(self.handle_ai_conversation())
            
            # Sonuçları kaydet
            self.save_results(
                success=success,
                ai_response="Müşteri ile konuşma tamamlandı",
                sentiment="Pozitif"
            )
            
            # Aramayı kapat
            self.agi.hangup()
            
        except Exception as e:
            self.log(f"Fatal error: {str(e)}")
            sys.exit(1)

if __name__ == '__main__':
    handler = AICallHandler()
    handler.run()
```

---

### 4.5 Asterisk Dialplan Güncellemesi

#### Dosya: `/etc/asterisk/extensions_custom.conf`

```ini
[auto-calls-ai]
; AI destekli aramalar için context

exten => s,1,NoOp(=== AI CALL START ===)
    same => n,Set(CALL_ID=${CHANNEL(userfield)})
    same => n,NoOp(Call ID: ${CALL_ID})
    
    ; AI AGI script çalıştır
    same => n,AGI(/var/lib/asterisk/agi-bin/ai_call_handler.py)
    
    ; Sonuç kontrolü
    same => n,GotoIf($["${AGISTATUS}" = "SUCCESS"]?success:failed)
    
    ; Başarılı
    same => n(success),NoOp(AI call successful)
    same => n,Hangup()
    
    ; Başarısız
    same => n(failed),NoOp(AI call failed)
    same => n,Playback(sorry-technical-difficulties)
    same => n,Hangup()

exten => h,1,NoOp(=== AI CALL END ===)
```

---

## 5. PROMPT TASARIMI

### 5.1 Temel Prompt Şablonu

```
SEN KİMSİN:
Sen [Şirket Adı] müşteri hizmetleri asistanı [Asistan Adı]'sın. 
Görevin müşterilere [amaç] hakkında bilgi vermek ve yardımcı olmak.

GÖREV:
1. Kendini tanıt: "Merhaba, ben [Asistan Adı], [Şirket]'dan arıyorum."
2. Durumu açıkla: "[Borç/Randevu/Bilgi]niz hakkında..."
3. Soru sor: "Size nasıl yardımcı olabilirim?"
4. Çözüm sun: "[Ödeme/Randevu/Bilgi] seçenekleri..."
5. Taahhüt al: "Kabul ediyor musunuz?"
6. Bitir: "Teşekkür ederim, iyi günler!"

KURALLAR:
✅ Türkçe konuş
✅ Kibar ve profesyonel ol
✅ Kısa cümleler kur (max 15 kelime)
✅ Müşteriyi dinle
✅ Empati kur
❌ Baskı yapma
❌ Tartışma
❌ Teknik terimler kullanma

MÜŞTERİ BİLGİLERİ:
- Ad: {{Adi}}
- Telefon: {{PhoneNumber}}
- Borç: {{Tutar}} TL
- Detay 1: {{Metin1}}
- Detay 2: {{Metin2}}
- Detay 3: {{Metin3}}
```

---

### 5.2 Senaryoya Özel Promptlar

#### Borç Tahsilatı:

```python
PROMPT_BORC_TAHSILAT = """
SEN KİMSİN:
Sen Astera Finans'tan sesli asistan Aylin'sin. Müşterilere ödenmemiş 
faturalarını hatırlatıyor ve ödeme kolaylığı sağlıyorsun.

KONUŞMA AKIŞI:

1. AÇILIŞ (5-10 saniye):
"Merhaba {{Adi}}, ben Aylin, Astera Finans'tan sesli asistanınızım. 
Ödenmemiş faturanız hakkında bilgi vermek için arıyorum. 
Şu an konuşabilir miyiz?"

[MÜŞTER İ CEVABI BEKLE]

2A. EVET DERSEİ:
"Harika! {{Tutar}} TL tutarında {{Metin1}} faturanız {{Metin2}} 
tarihine kadar ödenmelidir. Ödeme seçeneklerini aktarayım mı?"

2B. HAYIR / MEŞGUL DERSEİ:
"Anlıyorum. Size daha uygun bir zaman ne zaman olabilir? 
Yarın saat 14:00 gibi tekrar arayabilir miyim?"

3. ÖDEME SEÇENEKLERİ:
"3 seçeneğiniz var:
 1. Kredi kartı ile şimdi ödeme
 2. Banka havalesi
 3. {{Metin3}} (varsa taksit seçeneği)
 
Hangisini tercih edersiniz?"

4. TAK

SİT SORULURSA:
"Tabii ki! {{Tutar}} TL'yi 3 taksit yapabiliriz. 
Her ay {{Tutar/3}} TL ödersiniz. Kabul ediyor musunuz?"

5. KAPANIŞ:
"Mükemmel! Ödeme planınızı SMS ile gönderdim. 
Başka bir konuda yardımcı olabilir miyim?"

[HAYIR DERSEİ]
"Teşekkür ederim {{Adi}}. İyi günler!"

ÖZEL DURUMLAR:

- Müşteri KIZARSA: "Özür dilerim, sizi rahatsız etmek 
  istemedim. Sadece yardımcı olmaya çalışıyorum."
  
- Müşteri ÖDEME YAPAMAM DERSEİ: "Anlıyorum. Maddi 
  zorluk yaşıyorsunuz. Finansman danışmanımız sizi 
  arasın ve özel bir çözüm bulsun mı?"
  
- Müşteri ÖDEME YAPTIM DERSEİ: "Harika! Sistemimizde 
  kontrol edeyim. [Bekleyin] Evet doğru, ödemeniz 
  görünüyor. Bilgi için teşekkürler!"

TONLAMA:
- Kibar ama kararlı
- Samimi ama profesyonel
- Yardımsever ama satışçı değil

MÜŞTERİ BİLGİLERİ:
{{CUSTOMER_DATA}}
"""
```

#### Randevu Hatırlatma:

```python
PROMPT_RANDEVU_HATIRLATMA = """
SEN KİMSİN:
Sen {{Metin1}} Kliniği'nden sesli asistan Elif'sin. 
Randevuları hatırlatıyor ve yönetiyorsun.

KONUŞMA AKIŞI:

1. AÇILIŞ:
"Merhaba {{Adi}}, ben Elif, {{Metin1}} Kliniği'nden arıyorum. 
{{Metin2}} tarihinde saat {{Metin3}}'te randevunuz var. 
Hatırlatmak istedim, gelebilecek misiniz?"

2A. EVET DERSEİ:
"Harika! Randevunuzda sizi bekliyoruz. Adres: {{Adres}}.
Geç kalacak olursanız bizi arayın lütfen. İyi günler!"

2B. HAYIR / GELEMEM DERSEİ:
"Anlıyorum. Randevunuzu iptal edeyim mi yoksa 
başka bir tarihe almamı ister misiniz?"

3A. İPTAL DERSEİ:
"Tamam, randevunuzu iptal ettim. Daha sonra tekrar 
randevu almak isterseniz {{Telefon}} numarasından 
ulaşabilirsiniz. Geçmiş olsun!"

3B. ERTELEMEDERSEİ:
"Tabii. Gelecek hafta size uygun günler: 
{{AVAILABLE_SLOTS}}. Hangisi uygun?"

[MÜŞTERİ SEÇİM YAPAR]

"Mükemmel! Yeni randevunuz {{NewDate}} {{NewTime}}. 
SMS gönderdim. Başka yardımcı olabileceğim bir şey var mı?"

KAPANIŞ:
"Teşekkürler {{Adi}}, görüşmek üzere!"

MÜŞTERİ BİLGİLERİ:
{{CUSTOMER_DATA}}
"""
```

---

## 6. ASTERISK ENTEGRASYONU

### 6.1 AGI Script Kurulumu

```bash
# Script'i Asterisk AGI klasörüne kopyala
scp agi/ai_call_handler.py root@192.168.181.49:/var/lib/asterisk/agi-bin/

# SSH ile bağlan
ssh root@192.168.181.49

# İzinleri ayarla
chmod +x /var/lib/asterisk/agi-bin/ai_call_handler.py
chown asterisk:asterisk /var/lib/asterisk/agi-bin/ai_call_handler.py

# Test et
cd /var/lib/asterisk/agi-bin
./ai_call_handler.py
```

---

### 6.2 AutoCallManager Güncelleme

`includes/AutoCallManager.php` dosyasına AI context seçimi ekle:

```php
// Şablondan context belirle
$dialplanContext = 'auto-calls'; // Varsayılan (TTS)

if ($call['TemplateId']) {
    $templateStmt = $this->pdo->prepare("
        SELECT DialplanContext, UseAI 
        FROM MESSAGE_TEMPLATES 
        WHERE id = ?
    ");
    $templateStmt->execute([$call['TemplateId']]);
    $template = $templateStmt->fetch();
    
    if ($template) {
        // AI aktifse özel context kullan
        if ($template['UseAI'] == 1) {
            $dialplanContext = 'auto-calls-ai';
        } else {
            $dialplanContext = $template['DialplanContext'] ?? 'auto-calls';
        }
    }
}

// Call file oluştur
$this->asterisk->createCallFile(
    $call['PhoneNumber'],
    $call['id'],
    $audioFile,  // AI kullanmıyorsa
    $dialplanContext
);
```

---

## 7. VERİTABANI DEĞİŞİKLİKLERİ

### 7.1 MESSAGE_TEMPLATES Tablosuna AI Kolonları

```sql
-- AI desteği flag'i
ALTER TABLE MESSAGE_TEMPLATES 
ADD COLUMN UseAI TINYINT DEFAULT 0 COMMENT '0=TTS, 1=AI';

-- AI Prompt şablonu
ALTER TABLE MESSAGE_TEMPLATES 
ADD COLUMN AIPrompt TEXT COMMENT 'AI için prompt şablonu';

-- AI Ayarları (JSON)
ALTER TABLE MESSAGE_TEMPLATES 
ADD COLUMN AISettings JSON COMMENT 'AI konfigürasyonu';
```

#### Örnek Veri:

```sql
INSERT INTO MESSAGE_TEMPLATES 
(TemplateName, UseAI, AIPrompt, AISettings, DialplanContext) 
VALUES (
    'Borç Tahsilatı - AI',
    1,
    'Sen Astera Finans sesli asistanı Aylin\'sin...',
    '{
        "voice": "alloy",
        "temperature": 0.7,
        "max_duration": 180,
        "timeout": 10,
        "language": "tr-TR"
    }',
    'auto-calls-ai'
);
```

---

### 7.2 CALLS Tablosuna AI Sonuç Kolonları

```sql
-- AI cevabı
ALTER TABLE CALLS 
ADD COLUMN AI_Response TEXT COMMENT 'AI konuşma özeti';

-- Sentiment analizi
ALTER TABLE CALLS 
ADD COLUMN AI_Sentiment VARCHAR(20) COMMENT 'Pozitif/Negatif/Nötr';

-- Konuşma süresi
ALTER TABLE CALLS 
ADD COLUMN AI_Duration INT COMMENT 'Konuşma süresi (saniye)';

-- Transkript
ALTER TABLE CALLS 
ADD COLUMN AI_Transcript TEXT COMMENT 'Tam konuşma metni';
```

---

## 8. MALİYET ANALİZİ

### 8.1 OpenAI Fiyatlandırma (2026)

| Kaynak | Birim | Fiyat |
|--------|-------|-------|
| **Audio Input** | dakika | $0.06 |
| **Audio Output** | dakika | $0.24 |
| **GPT-4 Turbo** | 1M token | $10.00 (input) |
| **GPT-4 Turbo** | 1M token | $30.00 (output) |
| **TTS (alloy)** | 1M karakter | $15.00 |
| **Whisper STT** | dakika | $0.006 |

---

### 8.2 Arama Maliyeti Hesaplama

#### Ortalama 2 dakikalık arama:

```
Audio Input:  2 dk × $0.06  = $0.12
Audio Output: 2 dk × $0.24  = $0.48
GPT Tokens:   ~5000 token   = $0.05
TTS:          ~500 karakter = $0.01
STT:          2 dk × $0.006 = $0.01
─────────────────────────────────────
TOPLAM:                      $0.67/arama
```

#### Aylık Maliyet Projeksiyonu:

| Günlük Arama | Aylık Arama | Aylık Maliyet |
|--------------|-------------|---------------|
| 100 | 3,000 | $2,010 |
| 500 | 15,000 | $10,050 |
| 1,000 | 30,000 | $20,100 |
| 5,000 | 150,000 | $100,500 |
| 10,000 | 300,000 | $201,000 |

---

### 8.3 Maliyet Optimizasyonu

#### 1. Kısa Konuşma Stratejisi

```
Hedef: 2 dakika → 60 saniye

Audio Input:  1 dk × $0.06  = $0.06
Audio Output: 1 dk × $0.24  = $0.24
GPT Tokens:   ~2000 token   = $0.02
TTS:          ~200 karakter = $0.003
STT:          1 dk × $0.006 = $0.006
─────────────────────────────────────
TOPLAM:                      $0.33/arama (50% tasarruf!)
```

Nasıl?
- Kısa ve öz cümleler
- Gereksiz sohbet yok
- Hemen konuya giriş

---

#### 2. Hibrit Model (AI + TTS)

```
İlk temas: TTS (ucuz)
  ↓
Müşteri ilgiliyse: AI devreye girer
  ↓
Basit işlemler: TTS
Karmaşık: AI
```

Maliyet:
```
%70 TTS ($0.05/arama)  = $0.035
%30 AI  ($0.67/arama)  = $0.201
─────────────────────────────────
Ortalama:                $0.236/arama (65% tasarruf!)
```

---

#### 3. Lokal Whisper + Cloud GPT

```
Speech-to-Text: Lokal Whisper (ücretsiz)
AI Response: Cloud GPT-4
Text-to-Speech: Lokal TTS (ücretsiz)

Sadece GPT-4 maliyeti:
~$0.05/arama (92% tasarruf!)
```

Ama:
- Daha fazla latency (~1-2 saniye)
- Lokal sunucu gerekli (GPU)
- Ses kalitesi düşük olabilir

---

## 9. TEST SENARYOLARI

### 9.1 Birim Testleri

#### Test 1: AGI Script Çalışıyor mu?

```bash
# Manuel test
cd /var/lib/asterisk/agi-bin
echo "agi_network: yes
agi_request: test
agi_channel: SIP/test
agi_variable_CALL_ID: 123" | ./ai_call_handler.py

# Beklenen çıktı:
# [AI] Call data loaded for ID: 123
# [AI] Starting AI conversation...
```

---

#### Test 2: OpenAI API Bağlantısı

```python
# test_openai.py
import os
from openai import OpenAI

client = OpenAI(api_key=os.getenv('OPENAI_API_KEY'))

response = client.chat.completions.create(
    model="gpt-4-turbo",
    messages=[{"role": "user", "content": "Merhaba, test"}]
)

print(response.choices[0].message.content)
# Çıktı: "Merhaba! Nasıl yardımcı olabilirim?"
```

---

### 9.2 Entegrasyon Testleri

#### Test 3: Gerçek Arama Testi

```bash
# Asterisk CLI'da
asterisk -rvvv

# Test araması başlat
originate Local/05551234567@auto-calls-ai extension s@test

# Log'u izle
tail -f /var/log/asterisk/full | grep "AI"
```

---

#### Test 4: Veritabanı Kaydı

```sql
-- Test sonrası kontrol
SELECT id, PhoneNumber, Status, SipCode, 
       AI_Response, AI_Sentiment, AI_Duration
FROM CALLS 
WHERE id = 123;

-- Beklenen:
-- Status: 2
-- SipCode: 200
-- AI_Response: "Müşteri ödeme taahhüdü verdi"
-- AI_Sentiment: "Pozitif"
-- AI_Duration: 120
```

---

### 9.3 Performans Testleri

#### Test 5: Latency Ölçümü

```python
# test_latency.py
import time
from openai import OpenAI

client = OpenAI()

start = time.time()
response = client.audio.speech.create(
    model="tts-1",
    voice="alloy",
    input="Merhaba, bu bir test mesajıdır."
)
end = time.time()

print(f"Latency: {(end - start) * 1000:.0f}ms")
# Hedef: < 500ms
```

---

#### Test 6: Eşzamanlı Arama

```bash
# 10 eşzamanlı arama başlat
for i in {1..10}; do
    asterisk -rx "originate Local/0555${i}@auto-calls-ai extension s@test" &
done

# Log'da hata kontrolü
tail -f /var/log/asterisk/full | grep "ERROR"
```

---

## 10. İLERİ SEVİYE ÖZELLİKLER

### 10.1 Function Calling (Veritabanı Sorguları)

AI'nın gerçek zamanlı veritabanı sorguları yapabilmesi:

```python
# AGI script'e ekle
tools = [
    {
        "type": "function",
        "function": {
            "name": "get_customer_balance",
            "description": "Müşterinin güncel borç bakiyesini getir",
            "parameters": {
                "type": "object",
                "properties": {
                    "customer_id": {
                        "type": "integer",
                        "description": "Müşteri ID"
                    }
                },
                "required": ["customer_id"]
            }
        }
    },
    {
        "type": "function",
        "function": {
            "name": "create_payment_plan",
            "description": "Taksit planı oluştur",
            "parameters": {
                "type": "object",
                "properties": {
                    "amount": {"type": "number"},
                    "installments": {"type": "integer"}
                },
                "required": ["amount", "installments"]
            }
        }
    }
]

# AI'ya gönder
response = client.chat.completions.create(
    model="gpt-4-turbo",
    messages=messages,
    tools=tools,
    tool_choice="auto"
)

# Function call geldi mi kontrol et
if response.choices[0].message.tool_calls:
    for tool_call in response.choices[0].message.tool_calls:
        if tool_call.function.name == "get_customer_balance":
            # Veritabanı sorgusu yap
            balance = query_database(tool_call.function.arguments)
            # AI'ya sonucu döndür
            messages.append({
                "role": "function",
                "name": "get_customer_balance",
                "content": str(balance)
            })
```

Senaryo:
```
AI: "Güncel borcunuzu kontrol edeyim..."
  [get_customer_balance(customer_id=123) çağrılır]
  [DB: 150 TL döner]
AI: "150 TL borcunuz var."
```

---

### 10.2 Sentiment Analysis (Duygu Analizi)

Müşterinin ruh halini anlama:

```python
def analyze_sentiment(transcript):
    """Konuşmadan duygu analizi"""
    response = client.chat.completions.create(
        model="gpt-4-turbo",
        messages=[{
            "role": "system",
            "content": """
            Müşterinin ruh halini analiz et:
            - Pozitif: Mutlu, yardımsever, ödeme yapar
            - Nötr: Normal, nötr
            - Negatif: Kızgın, agresif, ödeme yapmaz
            
            Sadece tek kelime cevap ver: Pozitif/Nötr/Negatif
            """
        }, {
            "role": "user",
            "content": transcript
        }]
    )
    
    return response.choices[0].message.content

# Kullanım
sentiment = analyze_sentiment(conversation_text)

# Veritabanına kaydet
save_sentiment(call_id, sentiment)

# Stratji değiştir
if sentiment == "Negatif":
    # Daha yumuşak ton kullan
    # Taksit seçenekleri sun
    # İnsan ile görüşme teklif et
    pass
```

---

### 10.3 A/B Testing (Prompt Optimizasyonu)

Hangi prompt daha etkili?

```sql
-- AB_TEST tablosu
CREATE TABLE AB_TESTS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    TestName VARCHAR(100),
    VariantA TEXT COMMENT 'Prompt A',
    VariantB TEXT COMMENT 'Prompt B',
    Status ENUM('Running', 'Completed') DEFAULT 'Running',
    Created DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Test sonuçları
CREATE TABLE AB_TEST_RESULTS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    TestId INT,
    CallId INT,
    Variant ENUM('A', 'B'),
    Success TINYINT COMMENT '1=Başarılı, 0=Başarısız',
    Duration INT COMMENT 'Saniye',
    Sentiment VARCHAR(20),
    FOREIGN KEY (TestId) REFERENCES AB_TESTS(id),
    FOREIGN KEY (CallId) REFERENCES CALLS(id)
);
```

Python'da:

```python
def get_prompt_variant(test_id):
    """Rastgele A veya B varyantını seç"""
    import random
    
    # Test bilgilerini çek
    test = get_test_from_db(test_id)
    
    # %50 A, %50 B
    variant = random.choice(['A', 'B'])
    
    if variant == 'A':
        return test['VariantA'], 'A'
    else:
        return test['VariantB'], 'B'

# Kullanım
prompt, variant = get_prompt_variant(test_id=1)

# AI'ya gönder
response = ai_conversation(prompt)

# Sonucu kaydet
save_ab_result(
    test_id=1,
    call_id=call_id,
    variant=variant,
    success=success,
    duration=duration,
    sentiment=sentiment
)
```

Analiz:

```sql
-- Hangi varyant daha başarılı?
SELECT 
    Variant,
    COUNT(*) as TotalCalls,
    SUM(Success) as SuccessCount,
    ROUND(SUM(Success) * 100.0 / COUNT(*), 2) as SuccessRate,
    AVG(Duration) as AvgDuration
FROM AB_TEST_RESULTS
WHERE TestId = 1
GROUP BY Variant;

-- Örnek Çıktı:
-- Variant | TotalCalls | SuccessCount | SuccessRate | AvgDuration
-- A       | 1000       | 650          | 65.00%      | 120
-- B       | 1000       | 720          | 72.00%      | 105
-- → Varyant B daha başarılı! (7% daha fazla)
```

---

### 10.4 Çok Dilli Destek

Müşterinin diline göre otomatik geçiş:

```python
def detect_language(audio):
    """Konuşulan dili tespit et"""
    response = client.audio.transcriptions.create(
        model="whisper-1",
        file=audio,
        response_format="verbose_json"
    )
    
    return response.language  # 'tr', 'en', 'de', etc.

# Kullanım
language = detect_language(first_audio_chunk)

if language == 'en':
    prompt = PROMPT_ENGLISH
elif language == 'de':
    prompt = PROMPT_GERMAN
else:
    prompt = PROMPT_TURKISH

# AI'ya dil bilgisi ver
response = client.chat.completions.create(
    model="gpt-4-turbo",
    messages=[{
        "role": "system",
        "content": prompt + f"\n\nCONVERSATION LANGUAGE: {language.upper()}"
    }]
)
```

---

### 10.5 Sesli İmza (Voice Signature)

Müşteri onayını sesli kayıt ile al:

```python
def record_voice_signature():
    """Müşterinin sesli onayını kaydet"""
    
    # AI sorar
    ai_say("Ödeme planını onaylıyor musunuz? Lütfen 'Evet, onaylıyorum' deyin.")
    
    # Müşteri cevabı
    audio = record_audio(duration=5)
    
    # Transkript al
    transcript = transcribe_audio(audio)
    
    # Onay kontrolü
    if "evet" in transcript.lower() and "onay" in transcript.lower():
        # Ses dosyasını kaydet
        audio_file = f"/var/www/voice_signatures/{call_id}_{timestamp}.wav"
        save_audio(audio, audio_file)
        
        # Veritabanına kaydet
        save_voice_signature(
            call_id=call_id,
            audio_file=audio_file,
            transcript=transcript,
            timestamp=datetime.now()
        )
        
        return True
    else:
        return False
```

Veritabanı:

```sql
CREATE TABLE VOICE_SIGNATURES (
    id INT AUTO_INCREMENT PRIMARY KEY,
    CallId INT,
    AudioFile VARCHAR(255),
    Transcript TEXT,
    Timestamp DATETIME,
    FOREIGN KEY (CallId) REFERENCES CALLS(id)
);
```

---

## 📞 DESTEK VE İLETİŞİM

### Teknik Destek
**E-posta:** ai-support@anabilim.com  
**Telefon:** 0212 999 99 99  

### OpenAI Destek
**Dokümantasyon:** https://platform.openai.com/docs  
**Community:** https://community.openai.com/  

---

## 📝 SONRAKI ADIMLAR

1. ✅ OpenAI API key alın
2. ✅ Python environment kurun
3. ✅ AGI script'i test edin
4. ✅ Küçük bir liste ile pilot test yapın (10-20 arama)
5. ✅ Prompt'ları optimize edin
6. ✅ Maliyetleri izleyin
7. ✅ Başarı oranını ölçün
8. ✅ Tam prodüksiyona geçin

---

**SON GÜNCELLEME:** 7 Temmuz 2026  
**DOKÜMAN KODU:** AI-ENT-2026-001  
**HAZIRLIYAN:** AI Integration Team

---

**🤖 AI ENTEGRASYON REHBERİ SONU**
