@echo off
chcp 65001 >nul
echo ═══════════════════════════════════════════════════════════════
echo     🧹 GEÇİCİ FIX SCRİPTLERİNİ TEMİZLE
echo ═══════════════════════════════════════════════════════════════
echo.
echo Bu script, artık kullanılmayan geçici düzeltme scriptlerini siler.
echo.
echo Silinecek dosyalar:
echo   - fix_users_management_encoding.php
echo   - fix_user_names_encoding.php
echo   - fix_user_names_direct.php
echo   - fix_users_final.php
echo   - check_users_table.php
echo   - fix_database_encoding.php
echo.
echo NOT: Bu dosyalar artık kullanılmıyor, güvenle silinebilir.
echo.
pause
echo.

cd /d "d:\xampp\htdocs\OtomatikArama"

del /q fix_users_management_encoding.php 2>nul
if %errorlevel% equ 0 (echo ✅ fix_users_management_encoding.php silindi) else (echo ⚠️ Dosya bulunamadı)

del /q fix_user_names_encoding.php 2>nul
if %errorlevel% equ 0 (echo ✅ fix_user_names_encoding.php silindi) else (echo ⚠️ Dosya bulunamadı)

del /q fix_user_names_direct.php 2>nul
if %errorlevel% equ 0 (echo ✅ fix_user_names_direct.php silindi) else (echo ⚠️ Dosya bulunamadı)

del /q fix_users_final.php 2>nul
if %errorlevel% equ 0 (echo ✅ fix_users_final.php silindi) else (echo ⚠️ Dosya bulunamadı)

del /q check_users_table.php 2>nul
if %errorlevel% equ 0 (echo ✅ check_users_table.php silindi) else (echo ⚠️ Dosya bulunamadı)

del /q fix_database_encoding.php 2>nul
if %errorlevel% equ 0 (echo ✅ fix_database_encoding.php silindi) else (echo ⚠️ Dosya bulunamadı)

echo.
echo ═══════════════════════════════════════════════════════════════
echo ✅ TEMİZLİK TAMAMLANDI!
echo ═══════════════════════════════════════════════════════════════
echo.
pause
