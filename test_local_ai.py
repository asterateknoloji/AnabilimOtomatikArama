#!/usr/bin/env python3
"""
Lokal AI Hızlı Test
Whisper + Ollama + Piper bağlantı kontrolü
"""

import os
import sys

print("=" * 60)
print("LOKAL AI BAĞLANTI TESTİ")
print("=" * 60)
print()

# Test 1: Whisper
print("[1/3] Whisper (STT) testi...")
try:
    import whisper
    print("  ✅ Whisper paketi yüklü")
    
    # Model yükleme testi (sadece kontrol, yükleme yapma)
    print("  ℹ️  İlk kullanımda model otomatik indirilecek")
    print()
except ImportError:
    print("  ❌ Whisper yüklü değil!")
    print("  Çözüm: pip install openai-whisper")
    sys.exit(1)

# Test 2: Ollama
print("[2/3] Ollama (LLM) testi...")
try:
    import requests
    
    # Ollama API kontrolü
    response = requests.get('http://localhost:11434/api/tags', timeout=5)
    
    if response.status_code == 200:
        models = response.json().get('models', [])
        print(f"  ✅ Ollama çalışıyor")
        print(f"  ℹ️  İndirili modeller: {len(models)}")
        
        for model in models:
            print(f"     - {model['name']}")
        
        # Llama 3.1 var mı?
        llama_found = any('llama3.1' in m['name'] for m in models)
        
        if not llama_found:
            print()
            print("  ⚠️  Llama 3.1 bulunamadı!")
            print("  Çözüm: ollama pull llama3.1:8b")
        
        print()
    else:
        raise Exception("Ollama API yanıt vermiyor")
        
except Exception as e:
    print(f"  ❌ Ollama bağlantı hatası: {str(e)}")
    print()
    print("  Çözümler:")
    print("  1. Ollama kurulu mu? → ollama --version")
    print("  2. Servis çalışıyor mu? → ollama serve")
    print("  3. Model var mı? → ollama list")
    sys.exit(1)

# Test 3: Piper
print("[3/3] Piper (TTS) testi...")
import subprocess

try:
    # Piper binary kontrolü
    result = subprocess.run(['piper', '--version'], 
                          capture_output=True, 
                          text=True, 
                          timeout=5)
    
    if result.returncode == 0:
        print("  ✅ Piper bulundu")
        
        # Model kontrolü
        if os.path.exists('tr_TR-dfki-medium.onnx'):
            print("  ✅ Türkçe TTS modeli mevcut")
        else:
            print("  ⚠️  Türkçe TTS modeli bulunamadı!")
            print("  Çözüm: Türkçe model indirin")
            print("  wget https://huggingface.co/rhasspy/piper-voices/...")
        
        print()
    else:
        raise Exception("Piper çalışmıyor")
        
except FileNotFoundError:
    print("  ⚠️  Piper bulunamadı (opsiyonel)")
    print("  İndirmek için: https://github.com/rhasspy/piper/releases")
    print()
except Exception as e:
    print(f"  ⚠️  Piper hatası: {str(e)}")
    print()

# Özet
print("=" * 60)
print("TEST ÖZET")
print("=" * 60)
print()
print("Durum:")
print("  Whisper (STT): ✅ Hazır")
print("  Ollama (LLM):  ✅ Hazır" if 'models' in locals() else "  Ollama (LLM):  ❌ Hata")
print("  Piper (TTS):   ✅ Hazır" if 'result' in locals() else "  Piper (TTS):   ⚠️  Yok")
print()

if 'models' in locals() and len(models) > 0:
    print("=" * 60)
    print("HER ŞEY HAZIR! 🎉")
    print("=" * 60)
    print()
    print("Sonraki adımlar:")
    print("1. Lokal AI API'yi başlat:")
    print("   python local_ai_api.py")
    print()
    print("2. Test et:")
    print("   curl http://localhost:5000/health")
    print()
    print("3. Detaylı rehber:")
    print("   LOKAL_AI_REHBERI.md")
    print()
else:
    print("=" * 60)
    print("KURULUM TAMAMLANMADI!")
    print("=" * 60)
    print()
    print("Lütfen yukarıdaki hataları düzeltin.")
    sys.exit(1)
