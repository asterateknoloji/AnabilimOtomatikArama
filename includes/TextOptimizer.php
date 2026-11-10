<?php
/**
 * Metin İyileştirme Sınıfı
 * TTS için metni daha doğal hale getirir
 */

class TextOptimizer
{
    /**
     * TTS için metni optimize et
     * - Kısaltmaları aç
     * - Sayıları Türkçe'ye çevir
     * - Noktalama iyileştir
     */
    public static function optimizeForTTS($text)
    {
        // 1. Gereksiz boşlukları temizle
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        // 2. Yaygın kısaltmaları aç
        $abbreviations = [
            'Tel:' => 'Telefon:',
            'Tel.' => 'Telefon',
            'GSM:' => 'GSM:',
            'TL' => 'Türk Lirası',
            'Kr' => 'Kuruş',
            'No:' => 'Numara:',
            'Sayın' => 'Sayın',
            'sn.' => 'saniye',
            'dk.' => 'dakika',
            'st.' => 'saat',
        ];
        
        foreach ($abbreviations as $abbr => $full) {
            $text = str_replace($abbr, $full, $text);
        }
        
        // 3. Noktalama işaretlerinden sonra küçük duraklama ekle
        // (Google TTS için cümleleri ayırır)
        $text = preg_replace('/([.!?])\s+/', '$1 ', $text);
        $text = preg_replace('/([,;:])\s*/', '$1 ', $text);
        
        // 4. Çift boşlukları tekle
        $text = preg_replace('/\s+/', ' ', $text);
        
        // 5. Cümle başlarını büyük harf yap
        $text = preg_replace_callback('/([.!?]\s+)(\w)/', function($matches) {
            return $matches[1] . mb_strtoupper($matches[2]);
        }, $text);
        
        // 6. İlk harfi büyük yap
        $text = mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
        
        return trim($text);
    }
    
    /**
     * Cümleyi daha doğal hale getir
     */
    public static function naturalizeText($text)
    {
        // Resmi ifadeleri yumuşat
        $replacements = [
            'size hatırlatma yapıyoruz' => 'size hatırlatmak istiyoruz',
            'borcunuz bulunmaktadır' => 'borcunuz var',
            'ödemenizi gerçekleştiriniz' => 'ödemenizi yapınız',
            'lütfen en kısa zamanda' => 'lütfen en kısa sürede',
        ];
        
        foreach ($replacements as $formal => $natural) {
            $text = str_ireplace($formal, $natural, $text);
        }
        
        return $text;
    }
}
