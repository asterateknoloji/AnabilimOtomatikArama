<?php
/**
 * AJAX Endpoint - Import Listesi
 * Sadece import listesi HTML'ini döndürür
 */

require_once 'includes/check_auth.php';
requirePermission('import.view');

require_once 'config.php';
require_once 'includes/SchemaManager.php';
require_once 'includes/ImportManager.php';

ensureApplicationSchema($pdo);
$importManager = new ImportManager($pdo);

try {
    // Import listesini al
    $imports = $importManager->getImportHistory();
    
    if (empty($imports)): ?>
    <div class="empty-state">
        <div class="empty-icon">📁</div>
        <h3>Henüz içe aktarım yok</h3>
        <p><a href="import.php" style="color: #667eea;">Veri İçe Aktar</a> sayfasından başlayın</p>
    </div>
    <?php else: ?>
    <table class="import-table">
        <thead>
            <tr>
                <th style="width: 9%;">📅 Saat</th>
                <th style="width: 22%;">📄 Dosya Adı</th>
                <th style="width: 16%;">📁 Proje / 📄 Şablon</th>
                <th style="width: 5%;" title="Ulaşılamazsa kaç kez daha aranacak">🔄 Tekrar</th>
                <th style="width: 7%;" title="Arama partileri arasında kaç saniye beklenecek">⏱️ Aralık</th>
                <th style="width: 6%;" title="Her partide kaç kişi aranacak">⚡ Hız</th>
                <th style="width: 6%;" title="Aramayı başlat veya durdur">🎛️ Kontrol</th>
                <th style="width: 16%;">📊 İstatistikler</th>
                <th style="width: 17%;">⚙️ İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // ON/OFF konumu IMPORT_HISTORY.IsActive alanında kalıcıdır.
            ?>
            <?php foreach ($imports as $import): ?>
                <?php 
                try {
                    $stats = $importManager->getImportCallStats($import['id']);
                } catch (Exception $e) {
                    error_log("getImportCallStats error: " . $e->getMessage());
                    $stats = ['pending' => 0, 'calling' => 0, 'retry_waiting' => 0, 'completed' => 0, 'failed' => 0];
                }
                $isRunning = $stats['calling'] > 0;
                $hasActive = $stats['pending'] > 0 || $stats['calling'] > 0;
                $isActive = !empty($import['IsActive']);
                ?>
                <tr class="<?= $isRunning ? 'running' : '' ?>" data-import-id="<?= $import['id'] ?>">
                    <!-- Saat -->
                    <td style="text-align: center;">
                        <div style="font-size: 12px; font-weight: 600;">
                            <?= date('d.m.Y', strtotime($import['ImportDate'])) ?><br>
                            <?= date('H:i', strtotime($import['ImportDate'])) ?>
                        </div>
                    </td>

                    <!-- Dosya Adı -->
                    <td>
                        <?php
                        $fileName = $import['OriginalFileName'];
                        $displayName = mb_strlen($fileName) > 40 
                            ? mb_substr($fileName, 0, 40) . '...' 
                            : $fileName;
                        ?>
                        <div class="filename" title="<?= htmlspecialchars($fileName) ?>">
                            <?= htmlspecialchars($displayName) ?>
                        </div>
                        <div class="import-meta">
                            📊 <?= $import['RecordCount'] ?> kayıt
                        </div>
                    </td>

                    <!-- Proje Seçimi -->
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <!-- Proje (Sadece Görüntü) -->
                            <select class="form-control" disabled style="font-size: 12px; padding: 4px; background: #f5f5f5; cursor: not-allowed;">
                                <option>📁 <?= htmlspecialchars($import['ProjectName'] ?? 'Proje Yok') ?></option>
                            </select>
                            
                            <!-- Şablon (Sadece Görüntü) -->
                            <select class="form-control" disabled style="font-size: 12px; padding: 4px; background: #f5f5f5; cursor: not-allowed;">
                                <option>📄 <?= htmlspecialchars($import['TemplateName'] ?? 'Şablon Yok') ?></option>
                            </select>
                        </div>
                        
                        <!-- Hidden inputs for form submission -->
                        <input type="hidden" id="project_id_<?= $import['id'] ?>" value="<?= $import['ProjectId'] ?>">
                        <input type="hidden" id="template_id_<?= $import['id'] ?>" value="<?= $import['TemplateId'] ?>">
                    </td>

                    <!-- Tekrar Sayısı -->
                    <td style="text-align: center;">
                        <input type="number" 
                               class="form-control input-number" 
                               id="retry_count_<?= $import['id'] ?>"
                               value="<?= $import['RetryCount'] ?? 3 ?>"
                               min="1" 
                               max="10"
                               title="Ulaşılamazsa kaç kez daha aranacak">
                    </td>

                    <!-- Tekrar Aralığı -->
                    <td style="text-align: center;">
                        <input type="number" 
                               class="form-control input-number" 
                               id="retry_interval_<?= $import['id'] ?>"
                               value="<?= $import['RetryIntervalMinutes'] ?? 30 ?>"
                               min="1" 
                               max="1440"
                               title="Arama partileri arasında kaç saniye beklenecek">
                    </td>

                    <!-- Parti büyüklüğü (Hız) + Kaydet -->
                    <td style="text-align: center;">
                        <div style="display: flex; flex-direction: column; gap: 4px; align-items: center;">
                            <input type="number" 
                                   class="form-control input-number" 
                                   id="max_concurrent_<?= $import['id'] ?>"
                                   value="<?= $import['MaxConcurrentCalls'] ?? 10 ?>"
                                   min="1" 
                                   max="100"
                                   title="Her partide kaç kişi aranacak">
                            <?php if (hasPermission('import.control')): ?>
                            <button type="button" class="btn-save-settings" onclick="saveImportSettings(<?= $import['id'] ?>)" 
                                    title="Tekrar, Aralık ve Hız değerlerini kaydet"
                                    style="font-size: 10px; padding: 4px 8px; background: #17a2b8; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                💾 Kaydet
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>

                    <!-- Tek ON/OFF anahtarı -->
                    <td style="text-align: center;">
                        <?php if (hasPermission('import.toggle_active') || hasPermission('import.control')): ?>
                        <button type="button"
                                id="import_switch_<?= $import['id'] ?>"
                                data-active="<?= $isActive ? 1 : 0 ?>"
                                onclick="toggleImportSwitch(<?= $import['id'] ?>, this.dataset.active === '1')"
                                style="position:relative;width:96px;height:38px;padding:0;border:0;border-radius:20px;cursor:pointer;transition:.25s;background:<?= $isActive ? '#28a745' : '#dc3545' ?>;">
                            <span style="position:absolute;top:3px;left:<?= $isActive ? '51px' : '3px' ?>;width:42px;height:32px;line-height:32px;border-radius:17px;background:#fff;color:<?= $isActive ? '#28a745' : '#dc3545' ?>;font-size:11px;font-weight:800;transition:.25s;box-shadow:0 2px 5px rgba(0,0,0,.25);">
                                <?= $isActive ? 'ON' : 'OFF' ?>
                            </span>
                        </button>
                        <?php endif; ?>
                    </td>

                    <!-- İstatistikler -->
                    <td>
                        <?php 
                        // Değerleri hesapla
                        $aradi = isset($stats['completed']) ? (int)$stats['completed'] : 0;
                        $araniyor = isset($stats['calling']) ? (int)$stats['calling'] : 0;
                        $aranacak = isset($stats['pending']) ? (int)$stats['pending'] : 0;
                        ?>
                        
                        <!-- Aradı-Aranacak SATIR -->
                        <div style="background:#f8f9fa;padding:5px 10px;border-radius:5px;margin-bottom:8px;font-size:11px;font-weight:bold;text-align:center;border-left:3px solid #667eea;">
                            <span style="color:#28a745;">✓ Aradı: <?= $aradi ?></span> | 
                            <span style="color:#ffc107;">⏳ Aranacak: <?= $aranacak ?></span>
                        </div>
                        
                        <div class="stats-mini" id="stats_<?= $import['id'] ?>">
                            <?php if ($stats['pending'] > 0): ?>
                            <span class="stat-badge stat-pending">🟡 <?= $stats['pending'] ?></span>
                            <?php endif; ?>
                            <?php if ($stats['calling'] > 0): ?>
                            <span class="stat-badge stat-calling">🔵 <?= $stats['calling'] ?></span>
                            <?php endif; ?>
                            <?php if ($stats['retry_waiting'] > 0): ?>
                            <span class="stat-badge stat-retry">🟠 <?= $stats['retry_waiting'] ?></span>
                            <?php endif; ?>
                            <?php if ($stats['completed'] > 0): ?>
                            <span class="stat-badge stat-completed">🟢 <?= $stats['completed'] ?></span>
                            <?php endif; ?>
                            <?php if ($stats['failed'] > 0): ?>
                            <span class="stat-badge stat-failed">🔴 <?= $stats['failed'] ?></span>
                            <?php endif; ?>
                        </div>
                    </td>

                    <!-- İşlem Butonları (SABİT - ASLA DEĞİŞMEZ) -->
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <?php if (hasPermission('import.view')): ?>
                            <button class="btn btn-report" data-import-id="<?= $import['id'] ?>" data-file-name="<?= htmlspecialchars($import['OriginalFileName']) ?>" style="background: #17a2b8; color: white; width: 100%;">
                                📊 RAPOR
                            </button>
                            <a href="export_filter.php?import_id=<?= $import['id'] ?>&filter=all" 
                               target="_blank"
                               class="btn-export-link"
                               style="background: #28a745 !important; color: white !important; width: 100% !important; padding: 10px 20px !important; border-radius: 8px !important; font-size: 14px !important; font-weight: 600 !important; text-decoration: none !important; display: block !important; text-align: center !important; position: relative !important; z-index: 10 !important; cursor: pointer !important;">
                                📥 EXPORT
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('import.create')): ?>
                            <button class="btn btn-duplicate" data-import-id="<?= $import['id'] ?>" data-file-name="<?= htmlspecialchars($import['OriginalFileName']) ?>" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; width: 100%;">
                                📋 ÇOĞALT
                            </button>
                            <?php endif; ?>
                            <?php if (hasPermission('import.delete')): ?>
                            <form method="post" class="form-delete-import" data-file-name="<?= htmlspecialchars($import['OriginalFileName'], ENT_QUOTES, 'UTF-8') ?>" data-total="<?= (int)($stats['total'] ?? 0) ?>" style="margin: 0; width: 100%;">
                                <input type="hidden" name="import_id" value="<?= $import['id'] ?>">
                                <input type="hidden" name="delete_import" value="1">
                                <button type="button" class="btn btn-delete btn-delete-import" style="width: 100%;">
                                    🗑️ SİL
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="refresh-info">
        🔄 Son güncellenme: <?= date('H:i:s') ?>
    </div>
    <?php endif;
    
} catch (Exception $e) {
    echo '<div class="error">Hata: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
