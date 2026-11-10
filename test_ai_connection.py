#!/usr/bin/env python3
"""
OpenAI API Bağlantı Testi
Hızlı kontrol: API key çalışıyor mu?
"""

import os
import sys
from dotenv import load_dotenv

# .env dosyasını yükle
load_dotenv()

# API key kontrolü
api_key = os.getenv('OPENAI_API_KEY')

print("=" * 50)
print("OpenAI API Bağlantı Testi")
print("=" * 50)
print()

if not api_key or api_key == "BURAYA_API_KEY_YAZIN":
    print("❌ HATA: .env dosyasında OPENAI_API_KEY bulunamadı!")
    print()
    print("Çözüm:")
    print("1. .env dosyasını açın")
    print("2. OPENAI_API_KEY=sk-proj-xxxxx şeklinde key ekleyin")
    print("3. Bu scripti tekrar çalıştırın")
    sys.exit(1)

print(f"✅ API Key bulundu: {api_key[:20]}...")
print()

# OpenAI modülü kontrolü
try:
    from openai import OpenAI
    print("✅ OpenAI paketi yüklü")
except ImportError:
    print("❌ OpenAI paketi yüklü değil!")
    print("Çözüm: pip install openai")
    sys.exit(1)

# API bağlantısı test et
print()
print("OpenAI API'ye bağlanılıyor...")
print()

try:
    client = OpenAI(api_key=api_key)
    
    # Basit bir test isteği
    response = client.chat.completions.create(
        model="gpt-4-turbo",
        messages=[
            {"role": "user", "content": "Merhaba, bu bir test. Sadece 'Test başarılı!' yaz."}
        ],
        max_tokens=50
    )
    
    print("✅ OpenAI API bağlantısı BAŞARILI!")
    print()
    print("AI Cevabı:")
    print("-" * 50)
    print(response.choices[0].message.content)
    print("-" * 50)
    print()
    print("=" * 50)
    print("HER ŞEY HAZIR! AI asistan kullanıma hazır.")
    print("=" * 50)
    print()
    print("Sonraki adımlar:")
    print("1. AGI script'i oluşturun: agi/ai_call_handler.py")
    print("2. Asterisk dialplan güncelleyin")
    print("3. Test araması yapın")
    print()
    print("Detaylı bilgi: AI_ENTEGRASYON_REHBERI.md")
    
except Exception as e:
    print("❌ HATA: OpenAI API'ye bağlanılamadı!")
    print()
    print(f"Hata mesajı: {str(e)}")
    print()
    print("Olası sebepler:")
    print("- API key yanlış")
    print("- İnternet bağlantısı yok")
    print("- OpenAI servisi çalışmıyor")
    print("- Kredi yetersiz (https://platform.openai.com/account/billing)")
    sys.exit(1)
