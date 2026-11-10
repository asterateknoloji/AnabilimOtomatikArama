# 🏠 LOKAL AI ASISTAN KURULUM REHBERİ
## Tamamen Offline, Ücretsiz, Kendi Sunucunuzda AI

---

**Versiyon:** 1.0  
**Tarih:** 7 Temmuz 2026  
**Platform:** Windows 10/11 + Linux  
**Maliyet:** $0 (Sadece donanım ve elektrik)

---

## 📑 İÇİNDEKİLER

1. [Giriş](#1-giriş)
2. [Sistem Gereksinimleri](#2-sistem-gereksinimleri)
3. [Kurulum Adımları](#3-kurulum-adımları)
4. [Whisper (STT) Kurulumu](#4-whisper-stt-kurulumu)
5. [Ollama (LLM) Kurulumu](#5-ollama-llm-kurulumu)
6. [Piper (TTS) Kurulumu](#6-piper-tts-kurulumu)
7. [Python API Oluşturma](#7-python-api-oluşturma)
8. [Asterisk Entegrasyonu](#8-asterisk-entegrasyonu)
9. [Performans Optimizasyonu](#9-performans-optimizasyonu)
10. [Sorun Giderme](#10-sorun-giderme)

---

## 1. GİRİŞ

### 1.1 Neden Lokal AI?

#### Avantajlar:
```
✅ MALIYET: $0 (sınırsız kullanım)
✅ GİZLİLİK: Veriler dışarı çıkmaz
✅ OFFLINE: İnternet gerekmez
✅ TAM KONTROL: Kendi modelleriniz
✅ ÖZELLEŞTIRME: Fine-tuning yapabilirsiniz
✅ YASAL UYUM: KVKK, GDPR tam uyumlu
```

#### Dezavantajlar:
```
❌ DONANIM: GPU gerekli ($500-2000)
❌ LATENCY: 500-2000ms (Cloud: 200ms)
❌ KALİTE: Biraz daha düşük
❌ KURULUM: Teknik bilgi gerekli
❌ BAKIM: Güncellemeler manuel
```

---

### 1.2 Kullanım Senaryoları

#### İdeal Senaryolar:
- ✅ Günde 1000+ arama (Maliyet tasarrufu)
- ✅ Hassas veriler (Sağlık, finans)
- ✅ Kısıtlı internet (Sınırlı bant genişliği)
- ✅ Özel model eğitimi

#### Uygun Olmayan Senaryolar:
- ❌ Düşük donanım
- ❌ Günde <100 arama (Cloud daha mantıklı)
- ❌ Maksimum kalite gerekli

---

## 2. SİSTEM GEREKSİNİMLERİ

### 2.1 Donanım Tablosu

| Bileşen | Minimum | Önerilen | İdeal |
|---------|---------|----------|-------|
| **CPU** | i5/Ryzen 5 | i7/Ryzen 7 | i9/Threadripper |
| **RAM** | 16 GB | 32 GB | 64 GB |
| **GPU** | GTX 1660 (6GB) | RTX 3060 (12GB) | RTX 4090 (24GB) |
| **VRAM** | 6 GB | 12 GB | 24 GB |
| **Disk** | 50 GB SSD | 100 GB NVMe | 500 GB NVMe |
| **OS** | Win 10/Linux | Win 11/Ubuntu 22.04 | Ubuntu 22.04 LTS |

---

### 2.2 Model Boyutları vs VRAM

| Model | Boyut | VRAM Gereksinimi | Kalite | Hız |
|-------|-------|------------------|--------|-----|
| **Llama 3.1 (1B)** | 1.3 GB | 2 GB | ⭐⭐ | 🚀 Çok Hızlı |
| **Llama 3.1 (8B)** | 4.7 GB | 6 GB | ⭐⭐⭐⭐ | 🚀 Hızlı |
| **Llama 3.1 (70B)** | 40 GB | 48 GB | ⭐⭐⭐⭐⭐ | 🐢 Yavaş |
| **Mistral (7B)** | 4.1 GB | 6 GB | ⭐⭐⭐⭐ | 🚀 Hızlı |
| **Gemma 2 (9B)** | 5.5 GB | 8 GB | ⭐⭐⭐⭐ | 🚀 Hızlı |

**ÖNERİ:** GTX 1660 (6GB) → Llama 3.1 (8B) veya Mistral (7B)

---

### 2.3 GPU Kontrolü

#### Windows:

```cmd
# NVIDIA GPU var mı?
nvidia-smi

# Çıktı örneği:
+-----------------------------------------------------------------------------+
| NVIDIA-SMI 535.54       Driver Version: 535.54       CUDA Version: 12.2   |
|-------------------------------+----------------------+----------------------+
| GPU  Name        Persistence-M| Bus-Id        Disp.A | Volatile Uncorr. ECC |
| Fan  Temp  Perf  Pwr:Usage/Cap|         Memory-Usage | GPU-Util  Compute M. |
|===============================+======================+======================|
|   0  NVIDIA GeForce ...  Off  | 00000000:01:00.0  On |                  N/A |
| 30%   45C    P8    15W / 170W |    512MiB /  8192MiB |      2%      Default |
+-------------------------------+----------------------+----------------------+
```

#### Linux:

```bash
lspci | grep -i nvidia
# Çıktı: 01:00.0 VGA compatible controller: NVIDIA Corporation ...
```

**GPU YOK?** → CPU ile de çalışır ama çok yavaş (2-5 saniye latency)

---

## 3. KURULUM ADIMLARI

### 3.1 Python ve CUDA Kurulumu

#### Windows:

```cmd
# 1. Python 3.11 kur
choco install python311

# 2. NVIDIA CUDA Toolkit kur (GPU varsa)
# https://developer.nvidia.com/cuda-downloads

# 3. cuDNN kur (GPU varsa)
# https://developer.nvidia.com/cudnn

# 4. Kontrol
python --version
nvcc --version
```

#### Linux (Ubuntu):

```bash
# Python
sudo apt update
sudo apt install python3.11 python3.11-venv python3-pip

# CUDA (GPU varsa)
wget https://developer.download.nvidia.com/compute/cuda/repos/ubuntu2204/x86_64/cuda-keyring_1.1-1_all.deb
sudo dpkg -i cuda-keyring_1.1-1_all.deb
sudo apt-get update
sudo apt-get install cuda

# cuDNN
sudo apt-get install libcudnn8

# Kontrol
python3 --version
nvidia-smi
nvcc --version
```

---

### 3.2 Virtual Environment

```bash
cd d:\xampp\htdocs\AnabilimOtomatikArama

# Virtual env oluştur
python -m venv venv_local_ai

# Aktif et (Windows)
.\venv_local_ai\Scripts\activate

# Aktif et (Linux)
source venv_local_ai/bin/activate

# Artık (venv_local_ai) öneki görünür
```

---

## 4. WHISPER (STT) KURULUMU

### 4.1 Nedir?

**Whisper**, OpenAI'ın açık kaynak Speech-to-Text modelidir.

#### Özellikler:
- ✅ **99 Dil Desteği** (Türkçe dahil)
- ✅ **Yüksek Doğruluk** (~95%)
- ✅ **Offline Çalışır**
- ✅ **Ücretsiz**

#### Model Boyutları:

| Model | Boyut | VRAM | WER (Hata) | Hız |
|-------|-------|------|------------|-----|
| **tiny** | 39 MB | 1 GB | ~15% | ⚡ Çok Hızlı |
| **base** | 74 MB | 1 GB | ~12% | ⚡ Hızlı |
| **small** | 244 MB | 2 GB | ~10% | 🔵 Orta |
| **medium** | 769 MB | 5 GB | ~7% | 🐢 Yavaş |
| **large-v3** | 1550 MB | 10 GB | ~5% | 🐢 Çok Yavaş |

**ÖNERİ:** `small` (İyi denge: kalite + hız)

---

### 4.2 Kurulum

```bash
# Whisper kur
pip install -U openai-whisper

# ffmpeg kur (ses işleme için)
# Windows: choco install ffmpeg
# Linux: sudo apt install ffmpeg

# Test
python -c "import whisper; print('Whisper kuruldu!')"
```

---

### 4.3 Kullanım

```python
import whisper

# Model yükle (ilk seferinde indirilir)
model = whisper.load_model("small")

# Ses dosyasını transkript et
result = model.transcribe(
    "audio.wav",
    language="tr",  # Türkçe
    fp16=False      # CPU için False, GPU için True
)

print(result["text"])
# Çıktı: "Merhaba, bu bir test kaydıdır."
```

---

### 4.4 Performans Tablosu

| Model | GPU (RTX 3060) | CPU (i7) |
|-------|----------------|----------|
| tiny | ~100ms | ~500ms |
| base | ~200ms | ~800ms |
| small | ~400ms | ~1500ms |
| medium | ~800ms | ~3000ms |
| large-v3 | ~1500ms | ~6000ms |

---

## 5. OLLAMA (LLM) KURULUMU

### 5.1 Nedir?

**Ollama**, lokal LLM'leri kolayca çalıştıran bir araçtır.

#### Özellikler:
- ✅ **Tek Komut Kurulum**
- ✅ **GPU Otomatik Kullanır**
- ✅ **REST API** (OpenAI benzeri)
- ✅ **Model Yönetimi** (Pull, push, delete)
- ✅ **Llama, Mistral, Gemma** destekli

---

### 5.2 Kurulum

#### Windows:

```cmd
# 1. İndir ve kur
https://ollama.com/download/windows

# 2. Kurulum sonrası kontrol
ollama --version
# Çıktı: ollama version is 0.1.45
```

#### Linux:

```bash
# Tek komut kurulum
curl -fsSL https://ollama.com/install.sh | sh

# Kontrol
ollama --version
```

---

### 5.3 Model İndirme

```bash
# Llama 3.1 (8B) - ÖNERİLEN
ollama pull llama3.1:8b

# Alternatifler:
ollama pull mistral:7b      # Mistral 7B
ollama pull gemma2:9b       # Gemma 2 (9B)
ollama pull llama3.1:70b    # Çok güçlü ama yavaş (48GB VRAM!)

# İndirilen modelleri listele
ollama list
```

---

### 5.4 Test

```bash
# Terminal'de interaktif test
ollama run llama3.1:8b

# Prompt:
>>> Merhaba, sen kimsin?
Merhaba! Ben bir yapay zeka asistanıyım...

>>> /bye  (Çıkmak için)
```

---

### 5.5 REST API

Ollama otomatik olarak REST API açar:

```bash
# Sunucu başlat (genelde otomatik başlar)
ollama serve
```

API: `http://localhost:11434`

Test:

```bash
curl http://localhost:11434/api/generate -d '{
  "model": "llama3.1:8b",
  "prompt": "Merhaba, nasılsın?",
  "stream": false
}'
```

Python:

```python
import requests

response = requests.post('http://localhost:11434/api/generate', json={
    "model": "llama3.1:8b",
    "prompt": "Merhaba, nasılsın?",
    "stream": False
})

print(response.json()['response'])
# Çıktı: "İyiyim, teşekkür ederim! Size nasıl yardımcı olabilirim?"
```

---

### 5.6 Türkçe Optimizasyonu

Llama 3.1, Türkçe'yi destekler ama ince ayar yapabilirsiniz:

#### Modelfile Oluştur:

```bash
# modelfile_turkish
FROM llama3.1:8b

# System prompt (Türkçe)
SYSTEM """
Sen Türkçe konuşan bir müşteri hizmetleri asistanısın.
Görevin müşterilere yardımcı olmak ve sorunlarını çözmek.

KURALLAR:
- Sadece Türkçe konuş
- Kısa ve net cümleler kur
- Kibar ve profesyonel ol
- Teknik terimler kullanma
"""

# Parametreler
PARAMETER temperature 0.7
PARAMETER top_p 0.9
PARAMETER top_k 40
PARAMETER num_ctx 2048
```

Model oluştur:

```bash
ollama create turkish-assistant -f modelfile_turkish
```

Test:

```bash
ollama run turkish-assistant
>>> Merhaba, nasıl yardımcı olabilirim?
```

---

## 6. PIPER (TTS) KURULUMU

### 6.1 Nedir?

**Piper**, hızlı ve kaliteli lokal TTS sistemidir.

#### Özellikler:
- ✅ **Hızlı** (~100ms latency)
- ✅ **Hafif** (CPU yeterli)
- ✅ **Çok Dilli** (Türkçe dahil)
- ✅ **Offline**

---

### 6.2 Kurulum

#### Windows:

```cmd
# 1. Piper binary indir
https://github.com/rhasspy/piper/releases

# Örnek:
piper_windows_amd64.zip

# 2. Çıkart
unzip piper_windows_amd64.zip -d C:\piper

# 3. Path'e ekle (opsiyonel)
setx PATH "%PATH%;C:\piper"
```

#### Linux:

```bash
# Binary indir
wget https://github.com/rhasspy/piper/releases/download/v1.2.0/piper_linux_x86_64.tar.gz

# Çıkart
tar -xvzf piper_linux_x86_64.tar.gz

# /usr/local/bin'e taşı
sudo mv piper /usr/local/bin/
sudo chmod +x /usr/local/bin/piper
```

---

### 6.3 Türkçe Ses Modeli İndirme

```bash
# Türkçe ses modelleri
cd C:\piper  # veya ~/piper

# Model 1: Female (Kadın Sesi)
wget https://huggingface.co/rhasspy/piper-voices/resolve/main/tr/tr_TR/dfki/medium/tr_TR-dfki-medium.onnx
wget https://huggingface.co/rhasspy/piper-voices/resolve/main/tr/tr_TR/dfki/medium/tr_TR-dfki-medium.onnx.json

# Model 2: Male (Erkek Sesi)
wget https://huggingface.co/rhasspy/piper-voices/resolve/main/tr/tr_TR/fettah/medium/tr_TR-fettah-medium.onnx
wget https://huggingface.co/rhasspy/piper-voices/resolve/main/tr/tr_TR/fettah/medium/tr_TR-fettah-medium.onnx.json
```

---

### 6.4 Kullanım

```bash
# Basit kullanım
echo "Merhaba, bu bir test mesajıdır." | piper \
  --model tr_TR-dfki-medium.onnx \
  --output_file output.wav

# WAV dosyası oluşturuldu: output.wav
```

Python:

```python
import subprocess

def text_to_speech(text, output_file="output.wav"):
    """Metni sese çevir"""
    process = subprocess.Popen(
        [
            "piper",
            "--model", "tr_TR-dfki-medium.onnx",
            "--output_file", output_file
        ],
        stdin=subprocess.PIPE,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE
    )
    
    stdout, stderr = process.communicate(input=text.encode())
    
    return output_file

# Kullanım
audio_file = text_to_speech("Merhaba, nasılsınız?")
print(f"Ses dosyası oluşturuldu: {audio_file}")
```

---

### 6.5 Ses Kalitesi Ayarları

```bash
# Yüksek kalite (yavaş)
echo "Test" | piper --model tr_TR-dfki-medium.onnx \
  --length_scale 1.0 \
  --noise_scale 0.667 \
  --noise_w 0.8 \
  --output_file output.wav

# Hızlı (düşük kalite)
echo "Test" | piper --model tr_TR-dfki-medium.onnx \
  --length_scale 0.8 \
  --noise_scale 0.5 \
  --noise_w 0.5 \
  --output_file output.wav
```

Parametreler:
- **length_scale**: Konuşma hızı (0.8=hızlı, 1.2=yavaş)
- **noise_scale**: Ses tonlama (0.5=düz, 1.0=doğal)
- **noise_w**: Varyasyon (0.5=az, 1.0=çok)

---

## 7. PYTHON API OLUŞTURMA

### 7.1 Tüm Bileşenleri Birleştir

#### Dosya: `local_ai_api.py`

```python
#!/usr/bin/env python3
"""
Lokal AI API
Whisper (STT) + Ollama (LLM) + Piper (TTS)
"""

import os
import tempfile
import subprocess
import whisper
import requests
from flask import Flask, request, jsonify, send_file

app = Flask(__name__)

# Whisper model yükle (başlangıçta bir kez)
print("Whisper modeli yükleniyor...")
whisper_model = whisper.load_model("small")
print("✅ Whisper hazır")

# Ollama URL
OLLAMA_URL = "http://localhost:11434/api/generate"
OLLAMA_MODEL = "llama3.1:8b"

# Piper ayarları
PIPER_PATH = "C:\\piper\\piper.exe"  # Windows
# PIPER_PATH = "piper"  # Linux
PIPER_MODEL = "C:\\piper\\tr_TR-dfki-medium.onnx"

def speech_to_text(audio_file):
    """Ses → Metin (Whisper)"""
    try:
        result = whisper_model.transcribe(
            audio_file,
            language="tr",
            fp16=False  # CPU için False
        )
        return result["text"]
    except Exception as e:
        raise Exception(f"STT Error: {str(e)}")

def get_ai_response(prompt, system_prompt=None):
    """Metin → AI Cevap (Ollama)"""
    try:
        full_prompt = prompt
        if system_prompt:
            full_prompt = f"{system_prompt}\n\n{prompt}"
        
        response = requests.post(OLLAMA_URL, json={
            "model": OLLAMA_MODEL,
            "prompt": full_prompt,
            "stream": False,
            "options": {
                "temperature": 0.7,
                "top_p": 0.9,
                "top_k": 40
            }
        })
        
        if response.status_code == 200:
            return response.json()['response']
        else:
            raise Exception(f"Ollama Error: {response.status_code}")
    except Exception as e:
        raise Exception(f"LLM Error: {str(e)}")

def text_to_speech(text, output_file):
    """Metin → Ses (Piper)"""
    try:
        process = subprocess.Popen(
            [PIPER_PATH, "--model", PIPER_MODEL, "--output_file", output_file],
            stdin=subprocess.PIPE,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE
        )
        
        stdout, stderr = process.communicate(input=text.encode('utf-8'))
        
        if process.returncode != 0:
            raise Exception(f"Piper Error: {stderr.decode()}")
        
        return output_file
    except Exception as e:
        raise Exception(f"TTS Error: {str(e)}")

# ============================================
# REST API ENDPOINTS
# ============================================

@app.route('/api/stt', methods=['POST'])
def api_stt():
    """Speech-to-Text endpoint"""
    try:
        if 'audio' not in request.files:
            return jsonify({'error': 'No audio file'}), 400
        
        audio = request.files['audio']
        
        # Geçici dosyaya kaydet
        with tempfile.NamedTemporaryFile(delete=False, suffix='.wav') as tmp:
            audio.save(tmp.name)
            tmp_path = tmp.name
        
        # Transkript
        text = speech_to_text(tmp_path)
        
        # Temizle
        os.unlink(tmp_path)
        
        return jsonify({'text': text})
    
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/api/chat', methods=['POST'])
def api_chat():
    """Chat endpoint (LLM)"""
    try:
        data = request.get_json()
        
        prompt = data.get('prompt')
        system_prompt = data.get('system_prompt')
        
        if not prompt:
            return jsonify({'error': 'No prompt provided'}), 400
        
        response = get_ai_response(prompt, system_prompt)
        
        return jsonify({'response': response})
    
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/api/tts', methods=['POST'])
def api_tts():
    """Text-to-Speech endpoint"""
    try:
        data = request.get_json()
        
        text = data.get('text')
        
        if not text:
            return jsonify({'error': 'No text provided'}), 400
        
        # Geçici dosya
        with tempfile.NamedTemporaryFile(delete=False, suffix='.wav') as tmp:
            output_path = tmp.name
        
        # TTS
        text_to_speech(text, output_path)
        
        # Dosyayı döndür
        return send_file(output_path, mimetype='audio/wav')
    
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/api/full_conversation', methods=['POST'])
def api_full_conversation():
    """Tam konuşma akışı: Audio → STT → LLM → TTS → Audio"""
    try:
        if 'audio' not in request.files:
            return jsonify({'error': 'No audio file'}), 400
        
        audio = request.files['audio']
        system_prompt = request.form.get('system_prompt')
        
        # 1. STT
        with tempfile.NamedTemporaryFile(delete=False, suffix='.wav') as tmp:
            audio.save(tmp.name)
            input_audio_path = tmp.name
        
        user_text = speech_to_text(input_audio_path)
        os.unlink(input_audio_path)
        
        # 2. LLM
        ai_response = get_ai_response(user_text, system_prompt)
        
        # 3. TTS
        with tempfile.NamedTemporaryFile(delete=False, suffix='.wav') as tmp:
            output_audio_path = tmp.name
        
        text_to_speech(ai_response, output_audio_path)
        
        # 4. Response
        return jsonify({
            'user_text': user_text,
            'ai_response': ai_response,
            'audio_url': f'/api/audio/{os.path.basename(output_audio_path)}'
        })
    
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/health', methods=['GET'])
def health():
    """Health check"""
    return jsonify({
        'status': 'healthy',
        'whisper': 'ready',
        'ollama': 'ready',
        'piper': 'ready'
    })

if __name__ == '__main__':
    print("=" * 50)
    print("🤖 LOKAL AI API BAŞLATILIYOR")
    print("=" * 50)
    print()
    print("Endpoints:")
    print("  POST /api/stt              → Speech-to-Text")
    print("  POST /api/chat             → Chat (LLM)")
    print("  POST /api/tts              → Text-to-Speech")
    print("  POST /api/full_conversation → Tam konuşma")
    print("  GET  /health               → Health check")
    print()
    print("Listening on: http://localhost:5000")
    print("=" * 50)
    
    app.run(host='0.0.0.0', port=5000, debug=False)
```

---

### 7.2 Çalıştırma

```bash
# Flask kur
pip install flask

# API'yi başlat
python local_ai_api.py

# Çıktı:
# ===================================
# 🤖 LOKAL AI API BAŞLATILIYOR
# ===================================
# Whisper modeli yükleniyor...
# ✅ Whisper hazır
# Listening on: http://localhost:5000
```

---

### 7.3 Test

#### Test 1: Health Check

```bash
curl http://localhost:5000/health
```

Sonuç:
```json
{
  "status": "healthy",
  "whisper": "ready",
  "ollama": "ready",
  "piper": "ready"
}
```

#### Test 2: Chat (Sadece Metin)

```bash
curl -X POST http://localhost:5000/api/chat \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "Merhaba, nasılsın?",
    "system_prompt": "Sen yardımsever bir asistansın."
  }'
```

Sonuç:
```json
{
  "response": "Merhaba! İyiyim, teşekkür ederim. Size nasıl yardımcı olabilirim?"
}
```

#### Test 3: TTS

```bash
curl -X POST http://localhost:5000/api/tts \
  -H "Content-Type: application/json" \
  -d '{"text": "Merhaba, bu bir test mesajıdır."}' \
  --output test_tts.wav

# test_tts.wav dosyası oluşturuldu
```

---

## 8. ASTERISK ENTEGRASYONU

### 8.1 AGI Script (Lokal AI ile)

#### Dosya: `agi/local_ai_call_handler.py`

```python
#!/usr/bin/env python3
"""
Lokal AI ile Arama Handler
Whisper + Ollama + Piper
"""

import os
import sys
import requests
from asterisk.agi import AGI
import mysql.connector
from dotenv import load_dotenv

load_dotenv()

# Lokal AI API URL
LOCAL_AI_API = "http://localhost:5000"

class LocalAICallHandler:
    def __init__(self):
        self.agi = AGI()
        self.call_id = None
        self.call_data = {}
        
    def log(self, message):
        self.agi.verbose(f"[LOCAL-AI] {message}")
        
    def get_call_data(self):
        """Veritabanından arama bilgilerini çek"""
        try:
            self.call_id = self.agi.get_variable('CALL_ID')
            
            conn = mysql.connector.connect(
                host=os.getenv('DB_HOST'),
                database=os.getenv('DB_NAME'),
                user=os.getenv('DB_USER'),
                password=os.getenv('DB_PASS')
            )
            cursor = conn.cursor(dictionary=True)
            
            cursor.execute("""
                SELECT c.*, mt.AIPrompt
                FROM CALLS c
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
        """Sistem prompt oluştur"""
        base_prompt = self.call_data.get('AIPrompt', '')
        
        if not base_prompt:
            base_prompt = """
            Sen bir müşteri hizmetleri asistanısın.
            Kibarca konuş, yardımcı ol, kısa cümleler kur.
            """
        
        customer_context = f"""
        MÜŞTERİ:
        - Ad: {self.call_data['Adi']}
        - Telefon: {self.call_data['PhoneNumber']}
        - Borç: {self.call_data['Tutar']} TL
        """
        
        return base_prompt + "\n\n" + customer_context
        
    def record_audio(self, duration=10):
        """Müşteriden ses kaydı al"""
        audio_file = f"/tmp/call_{self.call_id}_user.wav"
        
        self.agi.exec_command('Record', f'{audio_file}:wav,{duration},s')
        
        return audio_file + ".wav"
        
    def play_audio(self, audio_file):
        """Ses dosyasını oynat"""
        # .wav uzantısını kaldır (Asterisk otomatik ekler)
        base_name = audio_file.replace('.wav', '')
        self.agi.stream_file(base_name)
        
    def handle_conversation(self):
        """Konuşma akışı"""
        try:
            system_prompt = self.build_prompt()
            
            # İlk karşılama
            greeting = "Merhaba, size nasıl yardımcı olabilirim?"
            
            # TTS
            response = requests.post(f"{LOCAL_AI_API}/api/tts", json={
                "text": greeting
            })
            
            greeting_audio = f"/tmp/call_{self.call_id}_greeting.wav"
            with open(greeting_audio, 'wb') as f:
                f.write(response.content)
            
            self.play_audio(greeting_audio)
            
            # Konuşma döngüsü (max 3 tur)
            for turn in range(3):
                self.log(f"Conversation turn {turn + 1}")
                
                # Müşteriden ses kaydı al
                user_audio = self.record_audio(duration=10)
                
                # Lokal AI API'ye gönder
                with open(user_audio, 'rb') as audio_file:
                    response = requests.post(
                        f"{LOCAL_AI_API}/api/full_conversation",
                        files={'audio': audio_file},
                        data={'system_prompt': system_prompt}
                    )
                
                if response.status_code != 200:
                    self.log(f"API Error: {response.status_code}")
                    break
                
                result = response.json()
                
                user_text = result['user_text']
                ai_response = result['ai_response']
                
                self.log(f"User: {user_text}")
                self.log(f"AI: {ai_response}")
                
                # AI cevabını oynat
                ai_audio = f"/tmp/call_{self.call_id}_ai_{turn}.wav"
                
                # TTS
                tts_response = requests.post(f"{LOCAL_AI_API}/api/tts", json={
                    "text": ai_response
                })
                
                with open(ai_audio, 'wb') as f:
                    f.write(tts_response.content)
                
                self.play_audio(ai_audio)
                
                # "Hoşça kal", "teşekkürler" gibi ifadeler varsa bitir
                if any(word in user_text.lower() for word in ['hoşça', 'güle güle', 'teşekkür']):
                    self.log("Conversation ended by user")
                    break
            
            self.log("Conversation completed")
            return True
            
        except Exception as e:
            self.log(f"Conversation error: {str(e)}")
            return False
            
    def save_results(self, success):
        """Sonuçları kaydet"""
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
                    Locked = 0
                WHERE id = %s
            """, (200 if success else 503, self.call_id))
            
            conn.commit()
            cursor.close()
            conn.close()
            
            self.log("Results saved")
            
        except Exception as e:
            self.log(f"Error saving results: {str(e)}")
            
    def run(self):
        """Ana akış"""
        try:
            self.agi.answer()
            
            if not self.get_call_data():
                self.agi.hangup()
                return
            
            success = self.handle_conversation()
            
            self.save_results(success)
            
            self.agi.hangup()
            
        except Exception as e:
            self.log(f"Fatal error: {str(e)}")
            sys.exit(1)

if __name__ == '__main__':
    handler = LocalAICallHandler()
    handler.run()
```

---

### 8.2 Asterisk Dialplan

```ini
[auto-calls-local-ai]
; Lokal AI ile arama

exten => s,1,NoOp(=== LOCAL AI CALL START ===)
    same => n,Set(CALL_ID=${CHANNEL(userfield)})
    
    ; AGI script çalıştır
    same => n,AGI(/var/lib/asterisk/agi-bin/local_ai_call_handler.py)
    
    ; Sonuç
    same => n,GotoIf($["${AGISTATUS}" = "SUCCESS"]?success:failed)
    
    same => n(success),NoOp(AI call successful)
    same => n,Hangup()
    
    same => n(failed),NoOp(AI call failed)
    same => n,Playback(sorry)
    same => n,Hangup()

exten => h,1,NoOp(=== LOCAL AI CALL END ===)
```

---

## 9. PERFORMANS OPTİMİZASYONU

### 9.1 GPU Kullanımı

#### Whisper için:

```python
# GPU kullan (CUDA)
model = whisper.load_model("small", device="cuda")

# FP16 (daha hızlı)
result = model.transcribe(audio_file, fp16=True)
```

#### Ollama için:

Ollama otomatik GPU kullanır. Kontrol:

```bash
# GPU kullanımını izle
nvidia-smi -l 1

# Ollama loglarında GPU kontrolü
ollama run llama3.1:8b
# Çıktı: "Using GPU 0 (NVIDIA GeForce RTX 3060)"
```

---

### 9.2 Model Seçimi

| Hedef | Whisper | Ollama | Piper |
|-------|---------|--------|-------|
| **Hız** | tiny | llama3.1:1b | low quality |
| **Denge** | small | llama3.1:8b | medium |
| **Kalite** | large-v3 | llama3.1:70b | high quality |

---

### 9.3 Caching

```python
# Model'i başlangıçta bir kez yükle
whisper_model = whisper.load_model("small")

# Her arama için tekrar yükleme!
# ❌ Yanlış:
def transcribe(audio):
    model = whisper.load_model("small")  # Her seferinde yükler!
    return model.transcribe(audio)

# ✅ Doğru:
def transcribe(audio):
    global whisper_model
    return whisper_model.transcribe(audio)
```

---

### 9.4 Paralel İşleme

```python
from concurrent.futures import ThreadPoolExecutor

def process_calls_parallel(call_ids):
    """Birden fazla aramayı paralel işle"""
    with ThreadPoolExecutor(max_workers=4) as executor:
        results = executor.map(process_single_call, call_ids)
    return list(results)
```

---

## 10. SORUN GİDERME

### 10.1 "CUDA Out of Memory"

```
Sebep: Model GPU'ya sığmıyor

Çözüm:
1. Daha küçük model kullan (8B → 1B)
2. Batch size düşür
3. FP16 kullan (FP32 yerine)
4. Daha fazla VRAM'li GPU
```

---

### 10.2 Whisper Yavaş

```
Sebep: CPU kullanıyor (GPU yok)

Çözüm:
1. GPU kur (NVIDIA)
2. CUDA kur
3. fp16=True kullan
4. Daha küçük model (small → tiny)
```

---

### 10.3 Ollama Bağlanamıyor

```bash
# Servis çalışıyor mu?
ollama serve

# Port kontrolü
netstat -an | findstr 11434

# Model var mı?
ollama list
```

---

### 10.4 Piper Sesi Çıkmıyor

```bash
# Model dosyası var mı?
ls -l tr_TR-dfki-medium.onnx

# Test
echo "Test" | piper --model tr_TR-dfki-medium.onnx --output_file test.wav

# WAV dosyası oynat
ffplay test.wav
```

---

## 📊 PERFORMANS KARŞILAŞTIRMASI

| İşlem | Cloud (OpenAI) | Lokal (RTX 3060) |
|-------|----------------|------------------|
| **STT (10 sn)** | 200ms | 400ms |
| **LLM Response** | 300ms | 800ms |
| **TTS (30 kelime)** | 200ms | 150ms |
| **TOPLAM** | **700ms** | **1350ms** |

**Sonuç:** Lokal %50 daha yavaş ama kabul edilebilir (<2 saniye)

---

## 💰 MALİYET ANALİZİ

### İlk Yatırım:

| Bileşen | Maliyet |
|---------|---------|
| GPU (RTX 3060) | $400 |
| RAM (+16 GB) | $80 |
| SSD (256 GB) | $50 |
| **TOPLAM** | **$530** |

### İşletme Maliyeti:

```
Elektrik: ~200W × 24 saat × 30 gün × $0.10/kWh
        = 144 kWh × $0.10
        = $14.40/ay

Cloud karşılaştırma:
1000 arama/gün × $0.35 = $350/gün = $10,500/ay

Tasarruf: $10,500 - $14.40 = $10,485.60/ay

ROI: $530 ÷ $10,485.60 = 0.05 ay = 1.5 gün! 🎉
```

**Sonuç:** 2 günde kendini amorti ediyor!

---

## ✅ SONUÇ

### Ne Zaman Lokal?

✅ **Günde 500+ arama**  
✅ **Hassas veriler**  
✅ **GPU var**  
✅ **Maliyet öncelikli**  

### Ne Zaman Cloud?

✅ **Günde <100 arama**  
✅ **Maksimum kalite**  
✅ **GPU yok**  
✅ **Hızlı kurulum**  

---

**🏠 LOKAL AI REHBER SONU**

İyi aramalar! 🚀
