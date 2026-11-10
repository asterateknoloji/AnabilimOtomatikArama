#!/bin/bash
################################################################################
# ASTERISK CUSTOM KLASÖR TEMİZLEME SCRİPTİ
# 
# Açıklama: /var/lib/asterisk/sounds/custom/ klasöründeki call_* ile başlayan
#           24 saatten eski dosyaları siler.
#
# Kullanım: ./cleanup_custom_files.sh
# Cron: 0 0 * * * /usr/local/bin/cleanup_custom_files.sh >> /var/log/asterisk/cleanup_custom.log 2>&1
################################################################################

# Renkli çıktı için
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Log başlığı
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  ASTERISK CUSTOM KLASÖR TEMİZLEME${NC}"
echo -e "${BLUE}  Tarih: $(date '+%Y-%m-%d %H:%M:%S')${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo ""

# Temizlenecek klasör
CUSTOM_DIR="/var/lib/asterisk/sounds/custom"

# Klasör kontrolü
if [ ! -d "$CUSTOM_DIR" ]; then
    echo -e "${RED}❌ HATA: $CUSTOM_DIR klasörü bulunamadı!${NC}"
    exit 1
fi

echo -e "${YELLOW}📁 Klasör: $CUSTOM_DIR${NC}"
echo ""

# İşlem öncesi durum
echo -e "${BLUE}📊 İŞLEM ÖNCESİ DURUM:${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

TOTAL_FILES=$(find "$CUSTOM_DIR" -name "call_*" -type f 2>/dev/null | wc -l)
OLD_FILES=$(find "$CUSTOM_DIR" -name "call_*" -type f -mtime +0 2>/dev/null | wc -l)
TOTAL_SIZE=$(du -sh "$CUSTOM_DIR" 2>/dev/null | awk '{print $1}')

echo "📦 Toplam call_* dosyası: $TOTAL_FILES"
echo "🗑️  24 saatten eski dosya: $OLD_FILES"
echo "💾 Klasör boyutu: $TOTAL_SIZE"
echo ""

# Silinecek dosya yoksa çık
if [ "$OLD_FILES" -eq 0 ]; then
    echo -e "${GREEN}✅ Silinecek dosya yok. Tüm dosyalar 24 saatten yeni.${NC}"
    echo ""
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    exit 0
fi

# Silinecek dosyaları listele
echo -e "${YELLOW}📋 SİLİNECEK DOSYALAR (son 10):${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
find "$CUSTOM_DIR" -name "call_*" -type f -mtime +0 -ls 2>/dev/null | tail -10
echo ""

# Silinecek dosyaların toplam boyutu
OLD_SIZE=$(find "$CUSTOM_DIR" -name "call_*" -type f -mtime +0 -exec du -ch {} + 2>/dev/null | grep total$ | awk '{print $1}')
echo -e "${YELLOW}💾 Silinecek toplam boyut: $OLD_SIZE${NC}"
echo ""

# Silme işlemi
echo -e "${RED}🗑️  SİLME İŞLEMİ BAŞLIYOR...${NC}"
echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# 24 saatten eski call_* dosyalarını sil
DELETED_COUNT=0
while IFS= read -r -d '' file; do
    if rm -f "$file" 2>/dev/null; then
        DELETED_COUNT=$((DELETED_COUNT + 1))
        echo "  ✓ Silindi: $(basename "$file")"
    else
        echo -e "  ${RED}✗ Silinemedi: $(basename "$file")${NC}"
    fi
done < <(find "$CUSTOM_DIR" -name "call_*" -type f -mtime +0 -print0 2>/dev/null)

echo ""
echo -e "${GREEN}✅ Silme işlemi tamamlandı: $DELETED_COUNT dosya silindi${NC}"
echo ""

# İşlem sonrası durum
echo -e "${BLUE}📊 İŞLEM SONRASI DURUM:${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

REMAINING_FILES=$(find "$CUSTOM_DIR" -name "call_*" -type f 2>/dev/null | wc -l)
CURRENT_SIZE=$(du -sh "$CUSTOM_DIR" 2>/dev/null | awk '{print $1}')

echo "📦 Kalan call_* dosyası: $REMAINING_FILES"
echo "💾 Mevcut klasör boyutu: $CURRENT_SIZE"
echo "🧹 Temizlenen alan: $OLD_SIZE"
echo ""

# Özet
echo -e "${GREEN}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✅ TEMİZLEME TAMAMLANDI${NC}"
echo -e "${GREEN}  📊 $DELETED_COUNT dosya silindi ($OLD_SIZE)${NC}"
echo -e "${GREEN}  📁 $REMAINING_FILES dosya kaldı${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════════${NC}"
echo ""

exit 0
