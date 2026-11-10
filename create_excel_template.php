<?php
/**
 * Excel Şablon Oluşturucu
 * Otomatik arama sistemi için örnek Excel dosyası oluşturur
 */

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Başlık satırı
$headers = ['Telefon', 'AdSoyad', 'Tutar', 'Metin1', 'Metin2', 'Metin3'];
$sheet->fromArray($headers, null, 'A1');

// Başlık stilini ayarla
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 12
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '667EEA']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];

$sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

// Örnek veriler
$sampleData = [
    ['05551234567', 'Ahmet Yılmaz', 1500.50, 'Vade tarihi: 15 Ocak 2026', 'Erken ödeme indirimi mevcuttur', ''],
    ['05559876543', 'Ayşe Demir', 2300.00, 'Aylık taksit: 500 TL', '', ''],
    ['02129876543', 'Mehmet Kaya', 850.75, '', '', ''],
    ['05321112233', 'Fatma Öz', 1200.00, 'Son ödeme tarihi yaklaşıyor', '', ''],
    ['05445556677', 'Ali Çelik', 3500.00, 'Toplam 3 taksit', 'İlk taksit vadesi geçmiş', ''],
];

$sheet->fromArray($sampleData, null, 'A2');

// Sütun genişliklerini ayarla
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(20);
$sheet->getColumnDimension('C')->setWidth(12);
$sheet->getColumnDimension('D')->setWidth(30);
$sheet->getColumnDimension('E')->setWidth(30);
$sheet->getColumnDimension('F')->setWidth(30);

// Satır yüksekliğini ayarla
$sheet->getRowDimension(1)->setRowHeight(25);

// Tüm hücrelere kenarlık ekle
$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CCCCCC']
        ]
    ]
];

$lastRow = 2 + count($sampleData);
$sheet->getStyle("A1:F{$lastRow}")->applyFromArray($dataStyle);

// Tutar sütununu para formatında göster
$sheet->getStyle('C2:C' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00 ₺');

// Açıklama sayfası ekle
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Kullanım Kılavuzu');

$instructions = [
    ['Otomatik Arama Sistemi - Excel Şablon Kullanım Kılavuzu'],
    [''],
    ['SÜTUN AÇIKLAMALARI:'],
    [''],
    ['1. Telefon:', 'Aranacak telefon numarası (0555XXXXXXX formatında)'],
    ['2. AdSoyad:', 'Müşteri ad ve soyadı (seslendirmede kullanılır)'],
    ['3. Tutar:', 'Borç tutarı (Örn: 1500.50)'],
    ['4. Metin1:', 'Ek bilgi 1 (İsteğe bağlı)'],
    ['5. Metin2:', 'Ek bilgi 2 (İsteğe bağlı)'],
    ['6. Metin3:', 'Ek bilgi 3 (İsteğe bağlı)'],
    [''],
    ['ÖRNEK ARAMA METNİ:'],
    [''],
    ['"Merhaba Ahmet Yılmaz. Bugünkü borcunuz 1500,50 Türk Lirasıdır.'],
    ['Vade tarihi: 15 Ocak 2026. Erken ödeme indirimi mevcuttur.'],
    ['Tekrar dinlemek için 1, operatöre bağlanmak için 2 tuşuna basınız."'],
    [''],
    ['ÖNEMLİ NOTLAR:'],
    [''],
    ['✓ İlk satır başlık satırıdır, silinmemelidir'],
    ['✓ Telefon numaraları 0 ile başlamalıdır'],
    ['✓ Tutar alanı sayısal olmalıdır (virgül veya nokta ile)'],
    ['✓ Metin alanları isteğe bağlıdır, boş bırakılabilir'],
    ['✓ Maksimum önerilen satır sayısı: 1000'],
    [''],
    ['DOSYA YÜKLEME:'],
    [''],
    ['1. Bu dosyayı doldurun'],
    ['2. otomatikarama.php sayfasına gidin'],
    ['3. "Excel Dosyası Yükle" butonuna tıklayın'],
    ['4. Bu dosyayı seçin ve yükleyin'],
    ['5. "Aramaları Başlat" ile aramalar başlatılır'],
];

$sheet2->fromArray($instructions, null, 'A1');

// Açıklama sayfası stilini ayarla
$sheet2->getColumnDimension('A')->setWidth(50);
$sheet2->getColumnDimension('B')->setWidth(60);

$titleStyle = [
    'font' => [
        'bold' => true,
        'size' => 16,
        'color' => ['rgb' => '667EEA']
    ]
];
$sheet2->getStyle('A1')->applyFromArray($titleStyle);

$sectionStyle = [
    'font' => [
        'bold' => true,
        'size' => 12,
        'color' => ['rgb' => '2c3e50']
    ]
];
$sheet2->getStyle('A3')->applyFromArray($sectionStyle);
$sheet2->getStyle('A12')->applyFromArray($sectionStyle);
$sheet2->getStyle('A18')->applyFromArray($sectionStyle);
$sheet2->getStyle('A27')->applyFromArray($sectionStyle);

// İlk sayfayı aktif yap
$spreadsheet->setActiveSheetIndex(0);

// Dosyayı kaydet
$filename = __DIR__ . '/excel_sablon_otomatik_arama.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($filename);

echo "Excel şablon dosyası oluşturuldu: {$filename}\n";
echo "Dosya boyutu: " . number_format(filesize($filename) / 1024, 2) . " KB\n";

// İndirme için kullanım
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="excel_sablon_otomatik_arama.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit;
}
