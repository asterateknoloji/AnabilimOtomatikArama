#!/usr/bin/env python3
"""
Asterisk AI Entegrasyon Test Script
Tüm bileşenleri kontrol eder
"""

import os
import sys
import subprocess

print("=" * 60)
print("ASTERISK AI ENTEGRASYON TEST")
print("=" * 60)
print()

# Test 1: AGI Script Varlığı
print("[1/6] AGI Script kontrolü...")
agi_path = "/var/lib/asterisk/agi-bin/ai_handler.py"

if os.path.exists(agi_path):
    print(f"  ✅ AGI script bulundu: {agi_path}")
    
    # İzinler
    stat_info = os.stat(agi_path)
    mode = oct(stat_info.st_mode)[-3:]
    
    if mode >= '755':
        print(f"  ✅ İzinler uygun: {mode}")
    else:
        print(f"  ⚠️  İzinler yetersiz: {mode}")
        print(f"  Çözüm: chmod +x {agi_path}")
else:
    print(f"  ❌ AGI script bulunamadı!")
    print(f"  Beklenen konum: {agi_path}")
    print()

# Test 2: Asterisk Bağlantısı
print("\n[2/6] Asterisk bağlantısı...")
try:
    result = subprocess.run(
        ['asterisk', '-rx', 'core show version'],
        capture_output=True,
        text=True,
        timeout=5
    )
    
    if result.returncode == 0:
        version = result.stdout.strip()
        print(f"  ✅ Asterisk çalışıyor")
        print(f"  ℹ️  {version}")
    else:
        raise Exception("Asterisk yanıt vermiyor")
        
except FileNotFoundError:
    print("  ❌ Asterisk bulunamadı!")
    print("  Asterisk kurulu mu kontrol edin")
except Exception as e:
    print(f"  ❌ Hata: {str(e)}")

# Test 3: Dialplan Kontrolü
print("\n[3/6] Dialplan kontrolü...")
try:
    result = subprocess.run(
        ['asterisk', '-rx', 'dialplan show auto-calls-ai'],
        capture_output=True,
        text=True,
        timeout=5
    )
    
    if 'auto-calls-ai' in result.stdout:
        print("  ✅ auto-calls-ai context bulundu")
        
        # AGI çağrısı var mı?
        if 'AGI' in result.stdout and 'ai_handler' in result.stdout:
            print("  ✅ AGI çağrısı dialplan'da mevcut")
        else:
            print("  ⚠️  AGI çağrısı dialplan'da yok")
            print("  Dialplan'ı kontrol edin")
    else:
        print("  ❌ auto-calls-ai context bulunamadı!")
        print("  extensions_custom.conf dosyasını kontrol edin")
        
except Exception as e:
    print(f"  ❌ Hata: {str(e)}")

# Test 4: Python Modülleri
print("\n[4/6] Python modülleri...")
required_modules = [
    'asterisk.agi',
    'mysql.connector',
    'requests',
    'dotenv'
]

all_ok = True
for module in required_modules:
    try:
        __import__(module)
        print(f"  ✅ {module}")
    except ImportError:
        print(f"  ❌ {module} yüklü değil!")
        all_ok = False

if not all_ok:
    print()
    print("  Çözüm: pip3 install pyst2 mysql-connector-python requests python-dotenv")

# Test 5: Veritabanı Bağlantısı
print("\n[5/6] Veritabanı bağlantısı...")
try:
    import mysql.connector
    from dotenv import load_dotenv
    
    # .env yükle
    load_dotenv()
    
    conn = mysql.connector.connect(
        host=os.getenv('DB_HOST', 'localhost'),
        database=os.getenv('DB_NAME', 'AsteraWeb'),
        user=os.getenv('DB_USER', 'astera'),
        password=os.getenv('DB_PASS', 'astera2012')
    )
    
    print("  ✅ Veritabanı bağlantısı başarılı")
    
    # AI kolonları var mı?
    cursor = conn.cursor()
    cursor.execute("SHOW COLUMNS FROM CALLS LIKE 'AI_%'")
    ai_columns = cursor.fetchall()
    
    if len(ai_columns) >= 4:
        print(f"  ✅ AI kolonları mevcut ({len(ai_columns)} kolon)")
    else:
        print(f"  ⚠️  AI kolonları eksik ({len(ai_columns)}/4)")
        print("  Çözüm: ALTER TABLE CALLS ADD COLUMN AI_Response TEXT...")
    
    cursor.close()
    conn.close()
    
except Exception as e:
    print(f"  ❌ Veritabanı hatası: {str(e)}")
    print("  .env dosyasını kontrol edin")

# Test 6: AI API
print("\n[6/6] AI API kontrolü...")
ai_type = os.getenv('AI_TYPE', 'openai')

if ai_type == 'openai':
    print("  ℹ️  AI Type: OpenAI (Cloud)")
    
    api_key = os.getenv('OPENAI_API_KEY')
    
    if api_key and api_key != 'BURAYA_API_KEY_YAZIN':
        print(f"  ✅ API Key bulundu: {api_key[:20]}...")
    else:
        print("  ❌ OpenAI API Key bulunamadı!")
        print("  .env dosyasına OPENAI_API_KEY ekleyin")
else:
    print("  ℹ️  AI Type: Lokal")
    
    import requests
    
    try:
        url = os.getenv('LOCAL_AI_URL', 'http://localhost:5000')
        response = requests.get(f"{url}/health", timeout=5)
        
        if response.status_code == 200:
            print(f"  ✅ Lokal AI API çalışıyor: {url}")
        else:
            raise Exception(f"HTTP {response.status_code}")
            
    except Exception as e:
        print(f"  ❌ Lokal AI API bağlanamıyor: {str(e)}")
        print("  Servis çalışıyor mu? systemctl status local-ai-api")

# Özet
print()
print("=" * 60)
print("TEST ÖZET")
print("=" * 60)
print()
print("Hazır mı?")
print("  AGI Script:    ✅" if os.path.exists(agi_path) else "  AGI Script:    ❌")
print("  Asterisk:      ✅" if 'result' in locals() and result.returncode == 0 else "  Asterisk:      ❌")
print("  Dialplan:      ✅" if 'auto-calls-ai' in (result.stdout if 'result' in locals() else '') else "  Dialplan:      ⚠️")
print("  Python Mods:   ✅" if all_ok else "  Python Mods:   ❌")
print("  Database:      ✅" if 'conn' in locals() else "  Database:      ❌")
print()

if all([
    os.path.exists(agi_path),
    'result' in locals() and result.returncode == 0,
    'auto-calls-ai' in (result.stdout if 'result' in locals() else ''),
    all_ok,
    'conn' in locals()
]):
    print("=" * 60)
    print("HER ŞEY HAZIR! 🎉")
    print("=" * 60)
    print()
    print("Test araması yapmak için:")
    print("1. Veritabanına test kaydı ekleyin")
    print("2. Call file oluşturun veya PHP'den arama başlatın")
    print("3. Log izleyin: tail -f /var/log/asterisk/full")
    print()
else:
    print("=" * 60)
    print("KURULUM TAMAMLANMADI!")
    print("=" * 60)
    print()
    print("Lütfen yukarıdaki hataları düzeltin.")
    print("Detaylı rehber: ASTERISK_AI_ENTEGRASYON.md")
    print()
