@echo off
REM ==================================================
REM AI ENTEGRASYONU - HIZLI KURULUM
REM ==================================================

echo.
echo ========================================
echo   AI ASISTAN KURULUM SIHIRBAZI
echo ========================================
echo.

REM Adım 1: Python Kontrolü
echo [1/5] Python kontrolü...
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [HATA] Python bulunamadi!
    echo Lutfen Python 3.11+ kurun: https://www.python.org/downloads/
    pause
    exit /b 1
)
echo [OK] Python bulundu

REM Adım 2: Virtual Environment
echo [2/5] Virtual environment olusturuluyor...
if not exist "venv_ai" (
    python -m venv venv_ai
    echo [OK] Virtual environment olusturuldu
) else (
    echo [OK] Virtual environment zaten mevcut
)

REM Adım 3: Aktivasyon
echo [3/5] Virtual environment aktif ediliyor...
call venv_ai\Scripts\activate.bat

REM Adım 4: Paketleri Kur
echo [4/5] AI paketleri kuruluyor (biraz zaman alabilir)...
if not exist "requirements_ai.txt" (
    echo openai==1.12.0> requirements_ai.txt
    echo websockets==12.0>> requirements_ai.txt
    echo python-dotenv==1.0.0>> requirements_ai.txt
    echo mysql-connector-python==8.3.0>> requirements_ai.txt
    echo pydub==0.25.1>> requirements_ai.txt
)
pip install -r requirements_ai.txt --quiet
echo [OK] Paketler kuruldu

REM Adım 5: Konfigürasyon Dosyası
echo [5/5] Konfigurasyon olusturuluyor...
if not exist ".env" (
    (
        echo # OpenAI API Key
        echo OPENAI_API_KEY=BURAYA_API_KEY_YAZIN
        echo.
        echo # Database
        echo DB_HOST=192.168.181.49
        echo DB_NAME=AsteraWeb
        echo DB_USER=astera
        echo DB_PASS=astera2012
        echo.
        echo # AI Settings
        echo AI_MAX_DURATION=180
        echo AI_TIMEOUT=10
        echo AI_LANGUAGE=tr-TR
        echo AI_TEMPERATURE=0.7
        echo OPENAI_VOICE=alloy
    ) > .env
    echo [OK] .env dosyasi olusturuldu
    echo.
    echo ========================================
    echo   DIKKAT: .env dosyasini duzenleyin!
    echo   OPENAI_API_KEY ekleyin
    echo ========================================
) else (
    echo [OK] .env dosyasi zaten mevcut
)

echo.
echo ========================================
echo   KURULUM TAMAMLANDI!
echo ========================================
echo.
echo SONRAKI ADIMLAR:
echo.
echo 1. OpenAI API Key alin:
echo    https://platform.openai.com/api-keys
echo.
echo 2. .env dosyasina API key ekleyin
echo.
echo 3. Test yapin:
echo    python test_ai_connection.py
echo.
echo 4. Detayli rehber:
echo    AI_ENTEGRASYON_REHBERI.md
echo.
pause
