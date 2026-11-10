<?php
/**
 * Örnek Excel Import Dosyası Oluşturucu
 */

require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Başlık satırı
$headers = ['Telefon', 'Ad Soyad', 'Tutar', 'Metin1', 'Metin2', 'Metin3'];
$sheet->fromArray($headers, NULL, 'A1');

// Başlık stilini ayarla
$headerStyle = $sheet->getStyle('A1:F1');
$headerStyle->getFont()->setBold(true)->setSize(12);
$headerStyle->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FF4CAF50');
$headerStyle->getFont()->getColor()->setARGB('FFFFFFFF');
$headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Örnek veriler
$sampleData = [
    ['05301234567', 'Ahmet Yılmaz', '1250.50', 'Ocak ayı ödemesi', 'Son ödeme tarihi: 31 Ocak', 'Teşekkür ederiz'],
    ['05321234568', 'Ayşe Demir', '2450.75', 'Şubat ayı ödemesi', 'İndirimli fiyat geçerli', ''],
    ['05331234569', 'Mehmet Kaya', '875.00', 'Mart ayı taksiti', '', ''],
    ['05341234570', 'Fatma Özkan', '3125.25', 'Yıllık ödeme', 'Peşin ödeme avantajı', 'Hemen ödeyin'],
    ['05351234571', 'Mustafa Şahin', '1875.00', 'Kurs ücreti', 'Kayıt ücreti dahil', ''],
    ['05361234572', 'Zeynep Çelik', '950.50', 'Nisan ayı ödemesi', '', ''],
    ['05371234573', 'Ali Aydın', '1500.00', 'Mayıs ayı taksiti', 'Normal fiyat', 'Son hatırlatma'],
    ['05381234574', 'Elif Aksoy', '2250.75', 'Haziran ayı ödemesi', 'Erken ödeme indirimi', ''],
    ['05391234575', 'Hasan Yurt', '1125.00', 'Temmuz ayı taksiti', '', ''],
    ['05401234576', 'Selin Koç', '1750.50', 'Ağustos ayı ödemesi', 'Yaz indirimi', 'Fırsat kaçmaz'],
];

// Verileri ekle
$row = 2;
foreach ($sampleData as $data) {
    $sheet->fromArray($data, NULL, 'A' . $row);
    $row++;
}

// Kolon genişliklerini ayarla
$sheet->getColumnDimension('A')->setWidth(15); // Telefon
$sheet->getColumnDimension('B')->setWidth(20); // Ad Soyad
$sheet->getColumnDimension('C')->setWidth(12); // Tutar
$sheet->getColumnDimension('D')->setWidth(25); // Metin1
$sheet->getColumnDimension('E')->setWidth(25); // Metin2
$sheet->getColumnDimension('F')->setWidth(20); // Metin3

// Tutar kolonunu para formatında göster
$sheet->getStyle('C2:C' . ($row - 1))->getNumberFormat()
    ->setFormatCode('#,##0.00');

// Tüm verileri ortala
$sheet->getStyle('A2:F' . ($row - 1))
    ->getAlignment()
    ->setVertical(Alignment::VERTICAL_CENTER);

// Telefon kolonunu merkeze al
$sheet->getStyle('A2:A' . ($row - 1))
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Tutar kolonunu sağa hizala
$sheet->getStyle('C2:C' . ($row - 1))
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

// Dosyayı kaydet
$fileName = 'ornek_import_' . date('Y-m-d') . '.xlsx';
$filePath = __DIR__ . '/' . $fileName;

$writer = new Xlsx($spreadsheet);
$writer->save($filePath);

echo "✓ Örnek Excel dosyası oluşturuldu!\n";
echo "Dosya: {$filePath}\n";
echo "Kayıt sayısı: " . count($sampleData) . "\n\n";
echo "Bu dosyayı import sayfasında kullanabilirsiniz:\n";
echo "http://localhost:8009/OtomatikArama/import.php\n";
