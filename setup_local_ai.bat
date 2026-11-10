@echo off
REM ================================================
REM LOKAL AI KURULUM - OTOMATIK
REM ================================================

echo.
echo ============================================
echo   LOKAL AI KURULUM SIHIRBAZI
echo ============================================
echo.

echo [KONTROL 1/5] GPU kontrolu...
nvidia-smi >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] NVIDIA GPU bulundu
    set HAS_GPU=1
) else (
    echo [UYARI] GPU bulunamadi - CPU kullanilacak yavaş olabilir!
    set HAS_GPU=0
    pause
)

echo [KONTROL 2/5] Python kontrolu...
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [HATA] Python bulunamadi!
    echo Lutfen Python 3.11+ kurun: https://www.python.org/downloads/
    pause
    exit /b 1
)
echo [OK] Python bulundu

echo [KONTROL 3/5] Ollama kontrolu...
ollama --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [HATA] Ollama bulunamadi!
    echo.
    echo Lutfen Ollama kurun:
    echo 1. https://ollama.com/download/windows adresine git
    echo 2. Indir ve kur
    echo 3. Bu scripti tekrar calistir
    pause
    exit /b 1
)
echo [OK] Ollama bulundu

echo.
echo [KURULUM 4/5] Python paketleri kuruluyor...
echo.

REM Virtual environment
if not exist "venv_local_ai" (
    python -m venv venv_local_ai
)

call venv_local_ai\Scripts\activate.bat

REM Paketler
pip install -q --upgrade pip
pip install -q openai-whisper flask requests python-dotenv mysql-connector-python

echo [OK] Python paketleri kuruldu

echo.
echo [MODEL 5/5] AI modelleri indiriliyor...
echo (Bu islem biraz zaman alabilir - kahve molasi!)
echo.

REM Whisper (otomatik indirilir)
echo Whisper: ilk kullanimda otomatik indirilecek

REM Ollama modeli
echo Ollama Llama 3.1 (8B) indiriliyor...
ollama pull llama3.1:8b

echo.
echo ============================================
echo   KURULUM TAMAMLANDI!
echo ============================================
echo.
echo SONRAKI ADIMLAR:
echo.
echo 1. Piper TTS kur (opsiyonel):
echo    https://github.com/rhasspy/piper/releases
echo.
echo 2. Lokal AI API'yi baslat:
echo    python local_ai_api.py
echo.
echo 3. Test et:
echo    curl http://localhost:5000/health
echo.
echo 4. Detayli rehber:
echo    LOKAL_AI_REHBERI.md
echo.
echo GPU Durumu: %HAS_GPU%
if "%HAS_GPU%"=="0" (
    echo [UYARI] GPU yok - yavas olabilir!
)
echo.
pause
