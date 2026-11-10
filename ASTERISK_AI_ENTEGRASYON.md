# 📞 ASTERISK AI ENTEGRASYON REHBERİ
## Tam Kurulum: Dialplan → AGI → AI → TTS → Müşteri

---

**Versiyon:** 2.0  
**Tarih:** 7 Temmuz 2026  
**Platform:** Asterisk 18+ / 20  
**AI:** OpenAI (Cloud) veya Ollama (Lokal)

---

## 📑 İÇİNDEKİLER

1. [Giriş](#1-giriş)
2. [Ön Hazırlık](#2-ön-hazırlık)
3. [Asterisk Konfigürasyonu](#3-asterisk-konfigürasyonu)
4. [AGI Script Geliştirme](#4-agi-script-geliştirme)
5. [Audio Streaming](#5-audio-streaming)
6. [Dialplan Entegrasyonu](#6-dialplan-entegrasyonu)
7. [AutoCallManager Güncellemesi](#7-autocallmanager-güncellemesi)
8. [Test ve Debug](#8-test-ve-debug)
9. [Production Deployment](#9-production-deployment)
10. [Sorun Giderme](#10-sorun-giderme)

---

## 1. GİRİŞ

### 1.1 Sistem Mimarisi

```
┌─────────────────────────────────────────────────────────┐
│                   WEB INTERFACE                         │
│              (arama_yonetimi.php)                       │
└────────────────────┬────────────────────────────────────┘
                     │
         ┌───────────┴───────────┐
         │                       │
┌────────▼────────┐    ┌────────▼─────────┐
│  MySQL DB       │    │   AI Config      │
│  (CALLS)        │    │   (Prompts)      │
└────────┬────────┘    └────────┬─────────┘
         │                      │
         └──────────┬───────────┘
                    │
         ┌──────────▼───────────┐
         │   PHP Call Manager   │
         │   (Call File Oluştur)│
         └──────────┬───────────┘
                    │
         ┌──────────▼───────────┐
         │   ASTERISK PBX       │
         │   /var/spool/asterisk/outgoing/
         └──────────┬───────────┘
                    │
         ┌──────────▼───────────┐
         │   DIALPLAN           │
         │   (extensions.conf)  │
         └──────────┬───────────┘
                    │
         ┌──────────▼───────────┐
         │   AGI SCRIPT         │
         │   (Python)           │
         └──────────┬───────────┘
                    │
         ┌──────────▼───────────┐
         │   AI API             │
         │   (OpenAI / Lokal)   │
         └──────────┬───────────┘
                    │
         ┌──────────▼───────────┐
         │   SIP/RTP STREAM     │
         │   (Audio → Müşteri)  │
         └──────────────────────┘
```

---

### 1.2 Veri Akışı

```
1. PHP → Call File Oluştur
   ↓
2. Asterisk → Müşteriyi Ara
   ↓
3. Müşteri Açtı → AGI Script Çalışır
   ↓
4. AGI → Veritabanından Bilgi Al
   ↓
5. AGI → Müşteriden Ses Kaydı Al
   ↓
6. AGI → AI API'ye Gönder (STT + LLM)
   ↓
7. AI → Cevap Döner (Metin)
   ↓
8. AGI → TTS ile Sese Çevir
   ↓
9. Asterisk → Ses Oynat (Müşteriye)
   ↓
10. AGI → Sonucu DB'ye Kaydet
   ↓
11. Asterisk → Aramayı Kapat
```

---

## 2. ÖN HAZIRLIK

### 2.1 Gereksinimler

#### Asterisk Tarafı:
```bash
# Asterisk versiyonu (18+ önerilen)
asterisk -V
# Çıktı: Asterisk 18.20.0

# Gerekli modüller:
- res_agi.so          → AGI desteği
- app_mixmonitor.so   → Kayıt
- app_playback.so     → Ses oynatma
- app_record.so       → Kayıt alma
- chan_sip.so         → SIP kanalları
```

#### Python Tarafı:
```bash
# Python 3.8+
python3 --version

# Gerekli paketler:
pip install asterisk-ami
pip install pyst2  # Asterisk AGI Python library
pip install requests
pip install python-dotenv
pip install mysql-connector-python
```

---

### 2.2 Dizin Yapısı

```
/var/lib/asterisk/
├── agi-bin/              → AGI scriptleri
│   ├── ai_handler.py
│   └── test_agi.py
├── sounds/               → Ses dosyaları
│   └── tr/              → Türkçe sesler
│       └── custom/
└── monitor/              → Kayıtlar

/etc/asterisk/
├── extensions.conf       → Dialplan
├── extensions_custom.conf
├── sip.conf             → SIP ayarları
└── manager.conf         → AMI ayarları

/tmp/
└── ai_audio/            → Geçici ses dosyaları
```

---

### 2.3 İzinler

```bash
# AGI script'leri
sudo chown asterisk:asterisk /var/lib/asterisk/agi-bin/*.py
sudo chmod +x /var/lib/asterisk/agi-bin/*.py

# Ses klasörü
sudo chown -R asterisk:asterisk /var/lib/asterisk/sounds/
sudo chmod -R 755 /var/lib/asterisk/sounds/

# Geçici klasör
sudo mkdir -p /tmp/ai_audio
sudo chown asterisk:asterisk /tmp/ai_audio
sudo chmod 777 /tmp/ai_audio
```

---

## 3. ASTERISK KONFİGÜRASYONU

### 3.1 Dialplan Temel Yapı

#### Dosya: `/etc/asterisk/extensions_custom.conf`

```ini
; ============================================
; AI DESTEKLI ARAMA CONTEXTI
; ============================================

[auto-calls-ai]
; AI asistan ile otomatik arama

exten => s,1,NoOp(=== AI CALL START ===)
    ; Call ID'yi al (PHP'den gönderilen)
    same => n,Set(CALL_ID=${CHANNEL(userfield)})
    same => n,NoOp(Call ID: ${CALL_ID})
    
    ; Müşteri bilgilerini değişkenlere al
    same => n,Set(PHONE_NUMBER=${CALLERID(num)})
    same => n,NoOp(Phone: ${PHONE_NUMBER})
    
    ; Aramayı başlat (opsiyonel beep)
    ; same => n,Playback(beep)
    
    ; Python AGI script'i çalıştır
    same => n,AGI(ai_handler.py,${CALL_ID})
    
    ; AGI sonuç kontrolü
    same => n,GotoIf($["${AGISTATUS}" = "SUCCESS"]?success:failed)
    
    ; Başarılı
    same => n(success),NoOp(AI conversation successful)
    same => n,NoOp(AI Status: ${AI_STATUS})
    same => n,Hangup()
    
    ; Başarısız
    same => n(failed),NoOp(AI conversation failed)
    same => n,Playback(sorry)
    same => n,Hangup()

; Hangup handler
exten => h,1,NoOp(=== AI CALL END ===)
    same => n,NoOp(Call Duration: ${CDR(duration)})
    same => n,Return()
```

---

### 3.2 Eski Sistem ile Karşılaştırma

#### Eski (TTS):
```ini
[auto-calls]
exten => s,1,Answer()
    same => n,Set(AUDIO_FILE=${AUDIO_FILE})
    same => n,Playback(${AUDIO_FILE})  ; Sabit ses
    same => n,Read(DTMF_RESPONSE,beep,1)
    same => n,NoOp(DTMF: ${DTMF_RESPONSE})
    same => n,Hangup()
```

#### Yeni (AI):
```ini
[auto-calls-ai]
exten => s,1,Answer()
    same => n,AGI(ai_handler.py)  ; Dinamik AI konuşması
    same => n,Hangup()
```

---

### 3.3 Manager Interface (AMI)

#### Dosya: `/etc/asterisk/manager.conf`

```ini
[general]
enabled = yes
port = 5038
bindaddr = 0.0.0.0

[admin]
secret = your_password_here
deny = 0.0.0.0/0.0.0.0
permit = 127.0.0.1/255.255.255.0
permit = 192.168.1.0/255.255.255.0
read = all
write = all
```

**Güvenlik:** Production'da IP'leri sınırlayın!

---

### 3.4 SIP Ayarları (Opsiyonel)

#### Dosya: `/etc/asterisk/sip.conf`

```ini
[general]
context = default
allowguest = no
udpbindaddr = 0.0.0.0
tcpenable = yes
tcpbindaddr = 0.0.0.0

[trunk-provider]
type = friend
host = sip.provider.com
username = your_username
secret = your_password
fromuser = your_username
context = from-trunk
```

---

### 3.5 Reload Asterisk

```bash
# Dialplan reload
asterisk -rx "dialplan reload"

# Manager reload
asterisk -rx "manager reload"

# SIP reload
asterisk -rx "sip reload"

# Veya full reload
asterisk -rx "core reload"

# Kontrol
asterisk -rx "dialplan show auto-calls-ai"
```

---

## 4. AGI SCRIPT GELİŞTİRME

### 4.1 Basit Test AGI

#### Dosya: `/var/lib/asterisk/agi-bin/test_agi.py`

```python
#!/usr/bin/env python3
"""
Test AGI Script
Basit AGI bağlantısını test eder
"""

import sys
from asterisk.agi import AGI

def main():
    # AGI başlat
    agi = AGI()
    
    # Log
    agi.verbose("=== TEST AGI START ===")
    
    # Aramayı cevapla
    agi.answer()
    
    # Ses oynat
    agi.stream_file('hello-world')
    
    # DTMF al
    agi.verbose("Waiting for DTMF...")
    result = agi.wait_for_digit(5000)  # 5 saniye bekle
    
    if result == ord('1'):
        agi.verbose("User pressed 1")
        agi.stream_file('thank-you')
    else:
        agi.verbose("No DTMF or timeout")
    
    # Bitir
    agi.verbose("=== TEST AGI END ===")
    agi.hangup()

if __name__ == '__main__':
    try:
        main()
    except Exception as e:
        print(f"AGI ERROR: {str(e)}", file=sys.stderr)
        sys.exit(1)
```

**Test:**
```bash
# İzin ver
chmod +x /var/lib/asterisk/agi-bin/test_agi.py

# Manuel test
echo "agi_network: yes
agi_request: test
agi_channel: SIP/test" | /var/lib/asterisk/agi-bin/test_agi.py

# Asterisk'ten test
asterisk -rx "originate Local/s@test-agi application Echo"
```

---

### 4.2 AI Handler AGI (Tam Versiyon)

#### Dosya: `/var/lib/asterisk/agi-bin/ai_handler.py`

```python
#!/usr/bin/env python3
"""
AI Handler AGI Script
OpenAI veya Lokal AI ile konuşma yönetimi
"""

import os
import sys
import json
import tempfile
import subprocess
import time
from asterisk.agi import AGI
import mysql.connector
import requests
from dotenv import load_dotenv

# .env yükle
load_dotenv('/var/www/html/AnabilimOtomatikArama/.env')

# ============================================
# KONFİGÜRASYON
# ============================================

# AI Type: 'openai' veya 'local'
AI_TYPE = os.getenv('AI_TYPE', 'openai')

# OpenAI Config
OPENAI_API_KEY = os.getenv('OPENAI_API_KEY')
OPENAI_MODEL = os.getenv('OPENAI_MODEL', 'gpt-4-turbo')

# Lokal AI Config
LOCAL_AI_URL = os.getenv('LOCAL_AI_URL', 'http://localhost:5000')

# Database
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'database': os.getenv('DB_NAME', 'AsteraWeb'),
    'user': os.getenv('DB_USER', 'astera'),
    'password': os.getenv('DB_PASS', 'astera2012')
}

# Audio
TEMP_AUDIO_DIR = '/tmp/ai_audio'
MAX_RECORDING_DURATION = 10  # saniye
CONVERSATION_TIMEOUT = 180   # 3 dakika max

# ============================================
# AI HANDLER CLASS
# ============================================

class AICallHandler:
    def __init__(self, call_id=None):
        self.agi = AGI()
        self.call_id = call_id or self.agi.env.get('agi_arg_1')
        self.call_data = {}
        self.conversation_history = []
        self.start_time = time.time()
        
    def log(self, message, level='INFO'):
        """Asterisk log'a yaz"""
        self.agi.verbose(f"[{level}] {message}")
        
    def get_call_data(self):
        """Veritabanından arama bilgilerini çek"""
        try:
            conn = mysql.connector.connect(**DB_CONFIG)
            cursor = conn.cursor(dictionary=True)
            
            query = """
                SELECT 
                    c.id, c.PhoneNumber, c.Adi, c.Tutar, 
                    c.Metin1, c.Metin2, c.Metin3,
                    c.ProjectId, c.TemplateId,
                    mt.AIPrompt, mt.AISettings,
                    p.ProjectName
                FROM CALLS c
                LEFT JOIN MESSAGE_TEMPLATES mt ON c.TemplateId = mt.id
                LEFT JOIN PROJECTS p ON c.ProjectId = p.id
                WHERE c.id = %s
            """
            
            cursor.execute(query, (self.call_id,))
            self.call_data = cursor.fetchone()
            
            cursor.close()
            conn.close()
            
            if not self.call_data:
                raise Exception(f"Call ID {self.call_id} not found")
            
            self.log(f"Call data loaded for {self.call_data['Adi']}")
            return True
            
        except Exception as e:
            self.log(f"Database error: {str(e)}", 'ERROR')
            return False
    
    def build_system_prompt(self):
        """AI için sistem prompt oluştur"""
        base_prompt = self.call_data.get('AIPrompt', '')
        
        if not base_prompt:
            # Varsayılan prompt
            base_prompt = """
            Sen bir müşteri hizmetleri asistanısın.
            Görevin kibarca müşterilere yardım etmek.
            
            KURALLAR:
            - Türkçe konuş
            - Kısa ve net cümleler kur (max 20 kelime)
            - Kibar ve profesyonel ol
            - Müşteriyi dinle
            - Gereksiz tekrar yapma
            """
        
        # Müşteri bilgilerini ekle
        customer_info = f"""
        MÜŞTERİ BİLGİLERİ:
        - Ad Soyad: {self.call_data['Adi']}
        - Telefon: {self.call_data['PhoneNumber']}
        - Borç/Tutar: {self.call_data['Tutar']} TL
        - Ek Bilgi 1: {self.call_data.get('Metin1', 'Yok')}
        - Ek Bilgi 2: {self.call_data.get('Metin2', 'Yok')}
        - Ek Bilgi 3: {self.call_data.get('Metin3', 'Yok')}
        - Proje: {self.call_data.get('ProjectName', 'Genel')}
        """
        
        return base_prompt + "\n\n" + customer_info
    
    def record_audio(self, duration=MAX_RECORDING_DURATION):
        """Müşteriden ses kaydı al"""
        try:
            audio_file = f"{TEMP_AUDIO_DIR}/call_{self.call_id}_{int(time.time())}"
            
            # Asterisk Record komutu
            # Syntax: Record(filename:format,silence_seconds,maxduration)
            self.agi.set_variable('REC_FILE', audio_file)
            self.agi.exec_command('Record', f'{audio_file}:wav,{duration},s')
            
            # .wav otomatik eklenir
            full_path = f"{audio_file}.wav"
            
            if os.path.exists(full_path):
                self.log(f"Audio recorded: {full_path}")
                return full_path
            else:
                raise Exception("Audio file not created")
                
        except Exception as e:
            self.log(f"Recording error: {str(e)}", 'ERROR')
            return None
    
    def transcribe_audio(self, audio_file):
        """Ses → Metin (STT)"""
        try:
            if AI_TYPE == 'openai':
                # OpenAI Whisper API
                import openai
                openai.api_key = OPENAI_API_KEY
                
                with open(audio_file, 'rb') as f:
                    response = openai.audio.transcriptions.create(
                        model="whisper-1",
                        file=f,
                        language="tr"
                    )
                
                text = response.text
                
            else:
                # Lokal AI
                with open(audio_file, 'rb') as f:
                    response = requests.post(
                        f"{LOCAL_AI_URL}/api/stt",
                        files={'audio': f},
                        timeout=30
                    )
                
                if response.status_code == 200:
                    text = response.json()['text']
                else:
                    raise Exception(f"STT API error: {response.status_code}")
            
            self.log(f"Transcribed: {text}")
            return text
            
        except Exception as e:
            self.log(f"Transcription error: {str(e)}", 'ERROR')
            return None
    
    def get_ai_response(self, user_text):
        """AI'dan cevap al (LLM)"""
        try:
            # Konuşma geçmişine ekle
            self.conversation_history.append({
                'role': 'user',
                'content': user_text
            })
            
            if AI_TYPE == 'openai':
                # OpenAI ChatGPT
                import openai
                openai.api_key = OPENAI_API_KEY
                
                messages = [
                    {'role': 'system', 'content': self.build_system_prompt()}
                ] + self.conversation_history
                
                response = openai.chat.completions.create(
                    model=OPENAI_MODEL,
                    messages=messages,
                    temperature=0.7,
                    max_tokens=150
                )
                
                ai_text = response.choices[0].message.content
                
            else:
                # Lokal AI
                response = requests.post(
                    f"{LOCAL_AI_URL}/api/chat",
                    json={
                        'prompt': user_text,
                        'system_prompt': self.build_system_prompt()
                    },
                    timeout=30
                )
                
                if response.status_code == 200:
                    ai_text = response.json()['response']
                else:
                    raise Exception(f"LLM API error: {response.status_code}")
            
            # Geçmişe ekle
            self.conversation_history.append({
                'role': 'assistant',
                'content': ai_text
            })
            
            self.log(f"AI Response: {ai_text}")
            return ai_text
            
        except Exception as e:
            self.log(f"AI response error: {str(e)}", 'ERROR')
            return None
    
    def text_to_speech(self, text):
        """Metin → Ses (TTS)"""
        try:
            audio_file = f"{TEMP_AUDIO_DIR}/tts_{self.call_id}_{int(time.time())}.wav"
            
            if AI_TYPE == 'openai':
                # OpenAI TTS
                import openai
                openai.api_key = OPENAI_API_KEY
                
                response = openai.audio.speech.create(
                    model="tts-1",
                    voice="alloy",
                    input=text
                )
                
                response.stream_to_file(audio_file)
                
            else:
                # Lokal AI (Piper)
                response = requests.post(
                    f"{LOCAL_AI_URL}/api/tts",
                    json={'text': text},
                    timeout=30
                )
                
                if response.status_code == 200:
                    with open(audio_file, 'wb') as f:
                        f.write(response.content)
                else:
                    raise Exception(f"TTS API error: {response.status_code}")
            
            self.log(f"TTS created: {audio_file}")
            return audio_file
            
        except Exception as e:
            self.log(f"TTS error: {str(e)}", 'ERROR')
            return None
    
    def play_audio(self, audio_file):
        """Ses dosyasını oynat"""
        try:
            # .wav uzantısını kaldır (Asterisk otomatik ekler)
            base_file = audio_file.replace('.wav', '')
            
            # Oynat
            self.agi.stream_file(base_file)
            
            self.log(f"Audio played: {audio_file}")
            return True
            
        except Exception as e:
            self.log(f"Playback error: {str(e)}", 'ERROR')
            return False
    
    def handle_conversation(self):
        """Ana konuşma döngüsü"""
        try:
            self.log("Starting AI conversation...")
            
            # İlk karşılama
            greeting = f"Merhaba {self.call_data['Adi']}, size nasıl yardımcı olabilirim?"
            
            greeting_audio = self.text_to_speech(greeting)
            if greeting_audio:
                self.play_audio(greeting_audio)
            
            # Konuşma döngüsü (max 5 tur)
            max_turns = 5
            
            for turn in range(max_turns):
                self.log(f"Conversation turn {turn + 1}/{max_turns}")
                
                # Timeout kontrolü
                elapsed = time.time() - self.start_time
                if elapsed > CONVERSATION_TIMEOUT:
                    self.log("Conversation timeout", 'WARNING')
                    break
                
                # Kullanıcıdan ses al
                self.log("Recording user audio...")
                user_audio = self.record_audio()
                
                if not user_audio:
                    self.log("No audio recorded", 'WARNING')
                    break
                
                # Ses → Metin
                user_text = self.transcribe_audio(user_audio)
                
                if not user_text or len(user_text) < 3:
                    self.log("Empty or invalid transcription", 'WARNING')
                    
                    # Tekrar sor
                    retry_audio = self.text_to_speech("Sizi duyamadım, tekrar edebilir misiniz?")
                    if retry_audio:
                        self.play_audio(retry_audio)
                    
                    continue
                
                # "Hoşça kal", "teşekkürler" gibi ifadeler varsa bitir
                end_phrases = ['hoşça', 'güle güle', 'teşekkür', 'tamam bitir', 'kapatabilirsin']
                if any(phrase in user_text.lower() for phrase in end_phrases):
                    self.log("User ended conversation")
                    
                    goodbye_audio = self.text_to_speech("Teşekkür ederim, iyi günler!")
                    if goodbye_audio:
                        self.play_audio(goodbye_audio)
                    
                    break
                
                # AI'dan cevap al
                ai_response = self.get_ai_response(user_text)
                
                if not ai_response:
                    self.log("No AI response", 'ERROR')
                    break
                
                # Metin → Ses
                ai_audio = self.text_to_speech(ai_response)
                
                if not ai_audio:
                    self.log("TTS failed", 'ERROR')
                    break
                
                # Ses oynat
                self.play_audio(ai_audio)
            
            self.log("Conversation completed")
            
            # Özet oluştur
            success = len(self.conversation_history) > 2
            
            return success
            
        except Exception as e:
            self.log(f"Conversation error: {str(e)}", 'ERROR')
            return False
    
    def save_results(self, success):
        """Sonuçları veritabanına kaydet"""
        try:
            conn = mysql.connector.connect(**DB_CONFIG)
            cursor = conn.cursor()
            
            # Konuşma özeti
            transcript = '\n'.join([
                f"{msg['role']}: {msg['content']}" 
                for msg in self.conversation_history
            ])
            
            # Sentiment (basit analiz)
            positive_words = ['tamam', 'evet', 'olur', 'anladım', 'teşekkür']
            negative_words = ['hayır', 'olmaz', 'istemiyorum', 'bırak']
            
            user_messages = [msg['content'].lower() for msg in self.conversation_history if msg['role'] == 'user']
            
            pos_count = sum(1 for msg in user_messages for word in positive_words if word in msg)
            neg_count = sum(1 for msg in user_messages for word in negative_words if word in msg)
            
            if pos_count > neg_count:
                sentiment = 'Pozitif'
            elif neg_count > pos_count:
                sentiment = 'Negatif'
            else:
                sentiment = 'Nötr'
            
            # Güncelle
            query = """
                UPDATE CALLS 
                SET Status = 2,
                    EndCallTime = NOW(),
                    SipCode = %s,
                    AI_Response = %s,
                    AI_Sentiment = %s,
                    AI_Transcript = %s,
                    AI_Duration = %s,
                    Locked = 0
                WHERE id = %s
            """
            
            duration = int(time.time() - self.start_time)
            
            cursor.execute(query, (
                200 if success else 503,
                f"AI konuşma tamamlandı ({len(self.conversation_history)} tur)",
                sentiment,
                transcript,
                duration,
                self.call_id
            ))
            
            conn.commit()
            cursor.close()
            conn.close()
            
            self.log("Results saved to database")
            return True
            
        except Exception as e:
            self.log(f"Save error: {str(e)}", 'ERROR')
            return False
    
    def cleanup(self):
        """Geçici dosyaları temizle"""
        try:
            # Bu call ID'ye ait tüm geçici dosyaları sil
            import glob
            
            pattern = f"{TEMP_AUDIO_DIR}/*{self.call_id}*"
            files = glob.glob(pattern)
            
            for file in files:
                try:
                    os.remove(file)
                    self.log(f"Deleted temp file: {file}")
                except:
                    pass
                    
        except Exception as e:
            self.log(f"Cleanup error: {str(e)}", 'WARNING')
    
    def run(self):
        """Ana akış"""
        try:
            # AGI başlat
            self.agi.answer()
            
            # Call data al
            if not self.get_call_data():
                self.agi.hangup()
                return False
            
            # AI konuşması
            success = self.handle_conversation()
            
            # Sonuçları kaydet
            self.save_results(success)
            
            # Temizlik
            self.cleanup()
            
            # Aramayı kapat
            self.agi.hangup()
            
            return success
            
        except Exception as e:
            self.log(f"Fatal error: {str(e)}", 'ERROR')
            
            # Hata mesajı oynat
            try:
                self.agi.stream_file('sorry')
                self.agi.hangup()
            except:
                pass
            
            return False

# ============================================
# MAIN
# ============================================

if __name__ == '__main__':
    try:
        # Call ID argüman olarak gelir
        call_id = sys.argv[1] if len(sys.argv) > 1 else None
        
        # Handler oluştur ve çalıştır
        handler = AICallHandler(call_id)
        success = handler.run()
        
        # Exit code
        sys.exit(0 if success else 1)
        
    except Exception as e:
        print(f"AGI FATAL ERROR: {str(e)}", file=sys.stderr)
        sys.exit(1)
```

---

### 4.3 AGI Kurulumu

```bash
# Scripti kopyala
scp ai_handler.py root@192.168.181.49:/var/lib/asterisk/agi-bin/

# SSH ile bağlan
ssh root@192.168.181.49

# İzinleri ayarla
cd /var/lib/asterisk/agi-bin
chmod +x ai_handler.py
chown asterisk:asterisk ai_handler.py

# Python modüllerini kur (Asterisk user olarak)
sudo -u asterisk pip3 install asterisk-agi openai requests python-dotenv mysql-connector-python

# Test
./ai_handler.py
# Hata verirse bağımlılıkları kontrol et
```

---

## 5. AUDIO STREAMING

### 5.1 Ses Kayıt Formatları

Asterisk ses kayıt komutları:

```python
# WAV (16kHz, mono, 16-bit)
self.agi.exec_command('Record', 'filename:wav,5,s')
# 5 = max duration (saniye)
# s = silence detection (sessizlik algılama)

# MP3 (daha küçük dosya)
self.agi.exec_command('Record', 'filename:mp3,10')

# GSM (Asterisk native, çok küçük)
self.agi.exec_command('Record', 'filename:gsm,10')
```

**Öneri:** WAV kullanın (AI API'leri için en uygun)

---

### 5.2 Ses Oynatma

```python
# Basit oynatma
self.agi.stream_file('/path/to/file')  # .wav uzantısız

# Interrupt edilebilir (DTMF ile)
digit = self.agi.stream_file('/path/to/file', '1234567890#*')
if digit:
    print(f"User pressed: {chr(digit)}")

# Ses seviyesi ayarlı
self.agi.set_variable('VOLUME(TX)', '2')  # +2dB
self.agi.stream_file('/path/to/file')
self.agi.set_variable('VOLUME(TX)', '0')  # Sıfırla
```

---

### 5.3 Gerçek Zamanlı Streaming (İleri Seviye)

OpenAI Realtime API için WebSocket streaming:

```python
import websocket
import json

def stream_audio_to_ai(audio_source):
    """Gerçek zamanlı ses streaming"""
    ws = websocket.create_connection('wss://api.openai.com/v1/realtime')
    
    # Auth
    ws.send(json.dumps({
        'type': 'auth',
        'api_key': OPENAI_API_KEY
    }))
    
    # Audio stream
    while True:
        chunk = audio_source.read(4096)
        if not chunk:
            break
        
        ws.send_binary(chunk)
        
        # AI response al
        response = ws.recv()
        # Process response...
    
    ws.close()
```

**Not:** Bu ileri seviye, şimdilik kaydedip gönderme yöntemi daha basit.

---

## 6. DIALPLAN ENTEGRASYONU

### 6.1 Tam Dialplan Örneği

#### Dosya: `/etc/asterisk/extensions_custom.conf`

```ini
; ============================================
; AI ASISTAN - TAM DIALPLAN
; ============================================

; Context: AI ile arama
[auto-calls-ai]

exten => s,1,NoOp(=================================)
    same => n,NoOp(AI CALL START)
    same => n,NoOp(=================================)
    
    ; Call ID al (PHP'den gelen)
    same => n,Set(CALL_ID=${CHANNEL(userfield)})
    same => n,GotoIf($["${CALL_ID}" = ""]?no_call_id:has_call_id)
    
    ; Call ID yok
    same => n(no_call_id),NoOp(ERROR: No Call ID provided)
    same => n,Playback(invalid)
    same => n,Hangup()
    
    ; Call ID var
    same => n(has_call_id),NoOp(Call ID: ${CALL_ID})
    
    ; Telefon numarasını al
    same => n,Set(PHONE=${CALLERID(num)})
    same => n,NoOp(Phone: ${PHONE})
    
    ; MixMonitor (kayıt) başlat (opsiyonel)
    ; same => n,MixMonitor(/var/lib/asterisk/monitor/ai_${CALL_ID}.wav)
    
    ; AGI Script çalıştır
    same => n,NoOp(Calling AGI: ai_handler.py)
    same => n,AGI(ai_handler.py,${CALL_ID})
    
    ; AGI sonuç kontrolü
    same => n,NoOp(AGI Status: ${AGISTATUS})
    same => n,GotoIf($["${AGISTATUS}" = "SUCCESS"]?agi_success:agi_failed)
    
    ; AGI başarılı
    same => n(agi_success),NoOp(AI conversation completed successfully)
    same => n,Goto(cleanup)
    
    ; AGI başarısız
    same => n(agi_failed),NoOp(AI conversation failed)
    same => n,Playback(sorry)
    same => n,Goto(cleanup)
    
    ; Temizlik ve kapanış
    same => n(cleanup),NoOp(Cleaning up...)
    ; same => n,StopMixMonitor()  ; Kayıt varsa durdur
    same => n,NoOp(Call Duration: ${CDR(duration)} seconds)
    same => n,Hangup()

; Hangup handler
exten => h,1,NoOp(=================================)
    same => n,NoOp(AI CALL END)
    same => n,NoOp(=================================)
    same => n,NoOp(Disposition: ${CDR(disposition)})
    same => n,NoOp(Duration: ${CDR(duration)})
    same => n,Return()

; ============================================
; ESKI TTS CONTEXT (Karşılaştırma için)
; ============================================

[auto-calls]
exten => s,1,NoOp(TTS CALL START)
    same => n,Answer()
    same => n,Wait(1)
    same => n,Set(AUDIO_FILE=${AUDIO_FILE})
    same => n,Playback(${AUDIO_FILE})
    same => n,Read(DTMF,beep,1)
    same => n,NoOp(DTMF: ${DTMF})
    same => n,Hangup()

exten => h,1,NoOp(TTS CALL END)
```

---

### 6.2 Dynamic Context Selection

PHP'den hangi context kullanılacağını belirleyin:

```php
// AutoCallManager.php içinde

if ($template['UseAI'] == 1) {
    $dialplanContext = 'auto-calls-ai';  // AI kullan
} else {
    $dialplanContext = 'auto-calls';     // TTS kullan
}

$this->asterisk->createCallFile(
    $call['PhoneNumber'],
    $call['id'],
    $audioFile,
    $dialplanContext  // Context buradan geliyor
);
```

---

### 6.3 Reload ve Test

```bash
# Dialplan reload
asterisk -rx "dialplan reload"

# Kontrol
asterisk -rx "dialplan show auto-calls-ai"

# Çıktı:
# [ Context 'auto-calls-ai' created by 'pbx_config' ]
#   's' =>            1. NoOp(AI CALL START)
#                     2. Set(CALL_ID=${CHANNEL(userfield)})
#                     ...
```

---

## 7. AUTOCALLMANAGER GÜNCELLEMESI

### 7.1 AI Context Seçimi

#### Dosya: `includes/AutoCallManager.php`

```php
// startAllImportCalls() fonksiyonunda değişiklik

// Şablon ID'sini ve context'ini bul
$templateId = null;
$dialplanContext = 'auto-calls'; // Varsayılan (TTS)

if ($call['ImportId']) {
    $importStmt = $this->pdo->prepare("
        SELECT TemplateId 
        FROM " . table('IMPORT_HISTORY') . " 
        WHERE id = ?
    ");
    $importStmt->execute([$call['ImportId']]);
    $import = $importStmt->fetch();
    $templateId = $import['TemplateId'] ?? null;
    
    // Şablondan context ve AI flag al
    if ($templateId) {
        $templateStmt = $this->pdo->prepare("
            SELECT DialplanContext, UseAI 
            FROM " . table('MESSAGE_TEMPLATES') . " 
            WHERE id = ?
        ");
        $templateStmt->execute([$templateId]);
        $template = $templateStmt->fetch();
        
        if ($template) {
            // AI aktifse özel context
            if ($template['UseAI'] == 1) {
                $dialplanContext = 'auto-calls-ai';
            } else {
                $dialplanContext = $template['DialplanContext'] ?? 'auto-calls';
            }
        }
    }
}

// Call file oluştur
$this->asterisk->createCallFile(
    $call['PhoneNumber'],
    $call['id'],
    $audioFile,
    $dialplanContext  // AI veya TTS
);
```

---

### 7.2 Veritabanı Kolonları

AI sonuçları için yeni kolonlar:

```sql
-- CALLS tablosuna AI kolonları ekle
ALTER TABLE CALLS 
ADD COLUMN AI_Response TEXT COMMENT 'AI konuşma özeti',
ADD COLUMN AI_Sentiment VARCHAR(20) COMMENT 'Pozitif/Negatif/Nötr',
ADD COLUMN AI_Transcript TEXT COMMENT 'Tam konuşma metni',
ADD COLUMN AI_Duration INT COMMENT 'Konuşma süresi (saniye)';

-- MESSAGE_TEMPLATES tablosuna AI flag ekle
ALTER TABLE MESSAGE_TEMPLATES 
ADD COLUMN UseAI TINYINT DEFAULT 0 COMMENT '0=TTS, 1=AI';
```

---

## 8. TEST VE DEBUG

### 8.1 Manuel AGI Test

```bash
# Test input oluştur
cat > /tmp/test_agi_input.txt << EOF
agi_network: yes
agi_request: agi://localhost/ai_handler.py
agi_channel: SIP/test-00000001
agi_language: tr
agi_type: SIP
agi_uniqueid: 1234567890.1
agi_callerid: 05551234567
agi_arg_1: 123
EOF

# AGI script test et
cat /tmp/test_agi_input.txt | /var/lib/asterisk/agi-bin/ai_handler.py

# Log'a bak
tail -f /var/log/asterisk/full | grep "ai_handler"
```

---

### 8.2 Asterisk CLI'dan Test

```bash
# Asterisk CLI'ya bağlan
asterisk -rvvv

# Test araması başlat
originate Local/s@auto-calls-ai application Echo

# Veya gerçek numara
originate SIP/05551234567 extension s@auto-calls-ai

# Log izle
tail -f /var/log/asterisk/full

# Aktif kanalları gör
core show channels

# AGI debug açordebug agi on
```

---

### 8.3 Python Debug

AGI script içinde debug ekleyin:

```python
import logging

# Log dosyası
logging.basicConfig(
    filename='/tmp/agi_debug.log',
    level=logging.DEBUG,
    format='%(asctime)s [%(levelname)s] %(message)s'
)

# Kullanım
logging.debug(f"Call ID: {self.call_id}")
logging.info(f"User said: {user_text}")
logging.error(f"API error: {str(e)}")
```

Sonra:
```bash
tail -f /tmp/agi_debug.log
```

---

### 8.4 End-to-End Test

```bash
# 1. Veritabanına test kaydı ekle
mysql -u astera -p AsteraWeb
```

```sql
INSERT INTO CALLS 
(PhoneNumber, Adi, Tutar, Metin1, Status, Aktif, ProjectId, TemplateId, CallTime, TryCallCount) 
VALUES 
('05551234567', 'Test Kullanıcı', 100, 'Test borç', 1, 1, 1, 1, NOW(), 3);

-- ID'yi not al (örn: 999)
SELECT LAST_INSERT_ID();
```

```bash
# 2. Call file oluştur (manuel)
cat > /tmp/test_call.call << EOF
Channel: SIP/your_trunk/05551234567
CallerID: "Astera" <02129999999>
MaxRetries: 0
WaitTime: 45
Context: auto-calls-ai
Extension: s
Priority: 1
Account: 999
Setvar: CALL_ID=999
AlwaysDelete: yes
Archive: no
EOF

# 3. Asterisk spool'a taşı
sudo mv /tmp/test_call.call /var/spool/asterisk/outgoing/

# 4. Log izle
tail -f /var/log/asterisk/full | grep -E "(ai_handler|AI CALL)"

# 5. Veritabanı sonucu kontrol et
mysql -u astera -p -e "SELECT id, Status, SipCode, AI_Response, AI_Sentiment FROM AsteraWeb.CALLS WHERE id = 999;"
```

---

## 9. PRODUCTION DEPLOYMENT

### 9.1 Checklist

```
✅ Python bağımlılıkları kuruldu
✅ AGI script izinleri ayarlandı (755, asterisk:asterisk)
✅ Dialplan reload edildi
✅ .env dosyası oluşturuldu (API keys)
✅ Temp klasörler oluşturuldu (/tmp/ai_audio)
✅ Veritabanı kolonları eklendi (AI_Response vb.)
✅ AutoCallManager güncellendi
✅ Test araması başarılı
✅ Log rotation ayarlandı
✅ Monitoring kuruldu
```

---

### 9.2 Systemd Service (Lokal AI için)

Lokal AI API'yi servis olarak çalıştırın:

#### Dosya: `/etc/systemd/system/local-ai-api.service`

```ini
[Unit]
Description=Local AI API for Asterisk
After=network.target

[Service]
Type=simple
User=asterisk
WorkingDirectory=/var/www/html/AnabilimOtomatikArama
ExecStart=/usr/bin/python3 local_ai_api.py
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Başlat:
```bash
sudo systemctl daemon-reload
sudo systemctl enable local-ai-api
sudo systemctl start local-ai-api
sudo systemctl status local-ai-api
```

---

### 9.3 Log Rotation

#### Dosya: `/etc/logrotate.d/ai_calls`

```
/tmp/agi_debug.log
/var/log/asterisk/full
{
    daily
    rotate 7
    compress
    delaycompress
    missingok
    notifempty
    create 644 asterisk asterisk
    postrotate
        /usr/sbin/asterisk -rx 'logger reload' > /dev/null 2>&1 || true
    endscript
}
```

---

### 9.4 Monitoring

```bash
# Asterisk durumu
watch -n 5 'asterisk -rx "core show channels"'

# AGI process sayısı
watch -n 2 'ps aux | grep ai_handler | wc -l'

# Disk kullanımı (temp audio)
watch -n 10 'du -sh /tmp/ai_audio'

# MySQL query
watch -n 5 'mysql -u astera -p -e "SELECT COUNT(*) as Active FROM AsteraWeb.CALLS WHERE Locked = 1;"'
```

---

## 10. SORUN GİDERME

### 10.1 "AGI Script Not Found"

```
Sebep: Script yolu yanlış veya izinler hatalı

Çözüm:
1. ls -l /var/lib/asterisk/agi-bin/ai_handler.py
2. chmod +x /var/lib/asterisk/agi-bin/ai_handler.py
3. chown asterisk:asterisk /var/lib/asterisk/agi-bin/ai_handler.py
4. Script başında shebang var mı: #!/usr/bin/env python3
```

---

### 10.2 "Python Module Not Found"

```
Sebep: Asterisk user'ı için paket kurulmamış

Çözüm:
sudo -u asterisk pip3 install openai asterisk-agi requests
```

---

### 10.3 "Database Connection Failed"

```
Sebep: .env dosyası yok veya yanlış

Çözüm:
1. /var/www/html/AnabilimOtomatikArama/.env oluştur
2. DB bilgilerini ekle:
   DB_HOST=192.168.181.49
   DB_NAME=AsteraWeb
   DB_USER=astera
   DB_PASS=astera2012
3. Dosya izinleri:
   chmod 640 .env
   chown asterisk:asterisk .env
```

---

### 10.4 "AI API Timeout"

```
Sebep: API yanıt vermiyor veya çok yavaş

Çözüm:
1. OpenAI: İnternet bağlantısı kontrol et
2. Lokal AI: Servis çalışıyor mu?
   systemctl status local-ai-api
3. Timeout süresini artır:
   requests.post(..., timeout=60)
```

---

### 10.5 "No Audio Recorded"

```
Sebep: Kayıt başarısız

Çözüm:
1. Temp klasör var mı ve yazılabilir mi?
   ls -ld /tmp/ai_audio
   chmod 777 /tmp/ai_audio
2. Disk dolu mu?
   df -h
3. Asterisk log kontrol:
   tail -f /var/log/asterisk/full | grep "Record"
```

---

### 10.6 Debug Modu

AGI script'e verbose logging ekleyin:

```python
# ai_handler.py başında
import sys
sys.stderr = open('/tmp/agi_stderr.log', 'a')

# Her adımda log
print(f"[DEBUG] Step 1: Answering call", file=sys.stderr)
print(f"[DEBUG] Step 2: Getting call data", file=sys.stderr)
# ...
```

Sonra:
```bash
tail -f /tmp/agi_stderr.log
```

---

## 📚 EK KAYNAKLAR

### Dokümantasyon:
- Asterisk AGI: https://wiki.asterisk.org/wiki/display/AST/AGI
- Python AGI Library: https://github.com/zaf/python-asterisk
- OpenAI API: https://platform.openai.com/docs/api-reference

### Örnek Komutlar:

```bash
# Asterisk dialplan reload
asterisk -rx "dialplan reload"

# AGI debug açık
asterisk -rx "agi set debug on"

# Kanal durumu
asterisk -rx "core show channels verbose"

# Log izleme
tail -f /var/log/asterisk/full | grep -E "AI|AGI"

# Test araması
asterisk -rx "channel originate Local/s@auto-calls-ai application Echo"
```

---

## ✅ ÖZET

### Kurulum Sırası:

1. **Python Kurulumu** (5 dk)
2. **AGI Script Yazımı** (30 dk)
3. **Asterisk Dialplan** (15 dk)
4. **PHP Entegrasyonu** (20 dk)
5. **Test** (30 dk)
6. **Production** (15 dk)

**Toplam:** ~2 saat

---

### Test Checklist:

```
✅ AGI script manuel test
✅ Asterisk CLI'dan test call
✅ Veritabanı kayıt kontrolü
✅ AI API bağlantı testi
✅ Audio kayıt/oynatma testi
✅ End-to-end gerçek arama
```

---

**🎉 BAŞARILAR!**

Herhangi bir sorunuz olursa, detaylı log'lar ile destek alabilirsiniz.

---

**📞 ASTERISK AI ENTEGRASYON REHBERI SONU**
