<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

try {
    // Import kayıtlarını çek
    $stmt = $pdo->query("
        SELECT 
            ih.id,
            ih.ImportDate,
            p.ProjectName,
            ih.OriginalFileName,
            ih.RecordCount,
            ih.RetryCount,
            ih.RetryIntervalMinutes,
            ih.MaxConcurrentCalls,
            mt.TemplateName,
            ih.Status
        FROM " . table('IMPORT_HISTORY') . " ih
        LEFT JOIN " . table('PROJECTS') . " p ON ih.ProjectId = p.id
        LEFT JOIN " . table('MESSAGE_TEMPLATES') . " mt ON ih.TemplateId = mt.id
        ORDER BY ih.ImportDate DESC
    ");
    
    $imports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Spreadsheet oluştur
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Import Kayıtları');
    
    // Başlık satırı
    $headers = [
        'A1' => 'ID',
        'B1' => 'Tarih/Saat',
        'C1' => 'Proje',
        'D1' => 'Dosya Adı',
        'E1' => 'Kayıt Sayısı',
        'F1' => 'Tekrar Sayısı',
        'G1' => 'Aralık (Dakika)',
        'H1' => 'Eşzamanlı',
        'I1' => 'Şablon',
        'J1' => 'Durum'
    ];
    
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    
    // Başlık stili
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '667eea']
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
    
    $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
    
    // Sütun genişlikleri
    $sheet->getColumnDimension('A')->setWidth(8);
    $sheet->getColumnDimension('B')->setWidth(18);
    $sheet->getColumnDimension('C')->setWidth(20);
    $sheet->getColumnDimension('D')->setWidth(40);
    $sheet->getColumnDimension('E')->setWidth(14);
    $sheet->getColumnDimension('F')->setWidth(14);
    $sheet->getColumnDimension('G')->setWidth(16);
    $sheet->getColumnDimension('H')->setWidth(12);
    $sheet->getColumnDimension('I')->setWidth(25);
    $sheet->getColumnDimension('J')->setWidth(15);
    
    // Veriyi yaz
    $row = 2;
    foreach ($imports as $import) {
        $sheet->setCellValue('A' . $row, $import['id']);
        $sheet->setCellValue('B' . $row, date('d.m.Y H:i', strtotime($import['ImportDate'])));
        $sheet->setCellValue('C' . $row, $import['ProjectName'] ?? '-');
        $sheet->setCellValue('D' . $row, $import['OriginalFileName']);
        $sheet->setCellValue('E' . $row, $import['RecordCount']);
        $sheet->setCellValue('F' . $row, $import['RetryCount'] ?? 3);
        $sheet->setCellValue('G' . $row, $import['RetryIntervalMinutes'] ?? 120);
        $sheet->setCellValue('H' . $row, $import['MaxConcurrentCalls'] ?? 10);
        $sheet->setCellValue('I' . $row, $import['TemplateName'] ?? 'Varsayılan');
        
        // Durum
        $status = '';
        switch ($import['Status']) {
            case 'pending': $status = 'Beklemede'; break;
            case 'running': $status = 'Çalışıyor'; break;
            case 'completed': $status = 'Tamamlandı'; break;
            case 'failed': $status = 'Başarısız'; break;
            default: $status = '-';
        }
        $sheet->setCellValue('J' . $row, $status);
        
        // Satır stili
        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
        
        // Zebra stripes
        if ($row % 2 == 0) {
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8F9FA']
                ]
            ]);
        }
        
        $row++;
    }
    
    // Dosyayı oluştur
    $fileName = 'import_kayitlari_' . date('Y-m-d_His') . '.xlsx';
    
    // Headers
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    echo "Export hatası: " . $e->getMessage();
    exit;
}
