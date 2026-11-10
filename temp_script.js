
        function saveProjectSelection(importId, projectId) {
            document.getElementById('project_id_' + importId).value = projectId;
            
            // AJAX ile sessizce kaydet
            fetch('save_project.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'import_id=' + importId + '&project_id=' + projectId
            });
        }
        
        function saveTemplateSelection(importId, templateId) {
            // Hidden inputlarÄ± gÃ¼ncelle
            const restartInput = document.getElementById('template_id_restart_' + importId);
            const hiddenInput = document.getElementById('template_id_hidden_' + importId);
            
            if (restartInput) restartInput.value = templateId;
            if (hiddenInput) hiddenInput.value = templateId;
            
            // AJAX ile sessizce kaydet
            fetch('save_template.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'import_id=' + importId + '&template_id=' + templateId
            });
        }

        function startImport(importId) {
            const projectId = document.getElementById('project_id_' + importId).value;
            const retryCount = document.getElementById('retry_count_' + importId).value;
            const retryInterval = document.getElementById('retry_interval_' + importId).value;
            const maxConcurrent = document.getElementById('max_concurrent_' + importId).value;
            
            if (!projectId) {
                alert('âŒ LÃ¼tfen Ã¶nce proje seÃ§in!');
                return false;
            }
            
            if (!retryCount || retryCount < 1 || retryCount > 10) {
                alert('âŒ Tekrar sayÄ±sÄ± 1-10 arasÄ±nda olmalÄ±!');
                return false;
            }
            
            if (!retryInterval || retryInterval < 5 || retryInterval > 1440) {
                alert('âŒ AralÄ±k 5-1440 dakika arasÄ±nda olmalÄ±!');
                return false;
            }
            
            if (!maxConcurrent || maxConcurrent < 1 || maxConcurrent > 100) {
                alert('âŒ EÅŸzamanlÄ± arama sayÄ±sÄ± 1-100 arasÄ±nda olmalÄ±!');
                return false;
            }
            
            // Hidden inputlarÄ± gÃ¼ncelle
            document.getElementById('retry_count_hidden_' + importId).value = retryCount;
            document.getElementById('retry_interval_hidden_' + importId).value = retryInterval;
            document.getElementById('max_concurrent_hidden_' + importId).value = maxConcurrent;
            
            // Onay sor
            const pending = document.querySelector('#retry_count_' + importId).closest('tr').querySelector('.stat-pending');
            const count = pending ? pending.textContent.match(/\d+/)[0] : '?';
            
            return confirm(`${count} kayÄ±t iÃ§in arama baÅŸlatÄ±lacak.\n\nTekrar: ${retryCount} kez\nAralÄ±k: ${retryInterval} dakika\nEÅŸzamanlÄ±: Max ${maxConcurrent} arama\n\nDevam edilsin mi?`);
        }
        
        function confirmDelete(fileName, totalCount) {
            const message = `âš ï¸ UYARI!\n\n` +
                          `Dosya: ${fileName}\n` +
                          `Toplam KayÄ±t: ${totalCount}\n\n` +
                          `Bu dosyayÄ± ve ilgili TÃœM arama kayÄ±tlarÄ±nÄ± silmek istediÄŸinizden emin misiniz?\n\n` +
                          `â— Bu iÅŸlem GERÄ° ALINAMAZ!`;
            
            return confirm(message);
        }
        
        function restartImport(importId, totalCount) {
            // Proje ID'sini bul (birden fazla form olabilir)
            let projectIdInput = document.getElementById('project_id_' + importId);
            if (!projectIdInput) {
                projectIdInput = document.getElementById('project_id_restart_' + importId);
            }
            
            const projectId = projectIdInput ? projectIdInput.value : '';
            
            // Sadece gÃ¶rÃ¼nÃ¼r proje seÃ§im dropdown'u varsa kontrol et
            const visibleProjectSelect = document.querySelector(`#project_select_${importId}`);
            if (visibleProjectSelect && !projectId) {
                alert('âŒ LÃ¼tfen Ã¶nce proje seÃ§in!');
                return false;
            }
            
            // Hidden inputlarÄ± bul
            let retryCountInput = document.getElementById('retry_count_' + importId) || 
                                 document.getElementById('retry_count_hidden_' + importId) ||
                                 document.getElementById('retry_count_restart_' + importId);
            
            let retryIntervalInput = document.getElementById('retry_interval_' + importId) ||
                                    document.getElementById('retry_interval_hidden_' + importId) ||
                                    document.getElementById('retry_interval_restart_' + importId);
            
            let maxConcurrentInput = document.getElementById('max_concurrent_' + importId) ||
                                    document.getElementById('max_concurrent_hidden_' + importId) ||
                                    document.getElementById('max_concurrent_restart_' + importId);
            
            const retryCount = retryCountInput ? retryCountInput.value : 3;
            const retryInterval = retryIntervalInput ? retryIntervalInput.value : 60;
            const maxConcurrent = maxConcurrentInput ? maxConcurrentInput.value : 10;
            
            // DetaylÄ± onay sor
            const message = `ğŸ”„ YENÄ°DEN BAÅLATMA\n\n` +
                          `Toplam ${totalCount} kayÄ±t iÃ§in arama yapÄ±lacak.\n\n` +
                          `Tekrar: ${retryCount} kez\n` +
                          `AralÄ±k: ${retryInterval} dakika\n` +
                          `EÅŸzamanlÄ±: Max ${maxConcurrent} arama\n\n` +
                          `Devam edilsin mi?`;
            
            return confirm(message);
        }

        // Import fonksiyonu
        function importData(input) {
            if (!input.files || input.files.length === 0) {
                return;
            }
            
            const file = input.files[0];
            const validExtensions = ['.xls', '.xlsx'];
            const fileExtension = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();
            
            if (!validExtensions.includes(fileExtension)) {
                alert('âŒ Sadece Excel dosyalarÄ± (.xls, .xlsx) yÃ¼klenebilir!');
                input.value = '';
                return;
            }
            
            // Modal'Ä± aÃ§ ve dosya bilgisini gÃ¶ster
            document.getElementById('selectedFileName').textContent = file.name;
            document.getElementById('importModal').style.display = 'flex';
            
            // VarsayÄ±lan tarih-saat deÄŸerini ayarla (1 saat sonra)
            const now = new Date();
            now.setHours(now.getHours() + 1);
            now.setMinutes(0); // DakikayÄ± yuvarla
            now.setSeconds(0);
            
            // datetime-local formatÄ±na Ã§evir (YYYY-MM-DDTHH:MM)
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const defaultDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
            
            document.getElementById('import_scheduled_time').value = defaultDateTime;
            
            // Global'e kaydet
            window.selectedImportFile = file;
        }
        
        // Modal kapat
        function closeImportModal() {
            document.getElementById('importModal').style.display = 'none';
            document.getElementById('importFile').value = '';
            window.selectedImportFile = null;
        }
        
        // ZamanlanmÄ±ÅŸ saati temizle
        function clearScheduledTime() {
            document.getElementById('import_scheduled_time').value = '';
            alert('âœ… Arama zamanÄ± temizlendi!\n\nImport hemen baÅŸlayacak.');
        }
        
        // Import iÅŸlemini baÅŸlat
        function submitImport() {
            const projectId = document.getElementById('import_project_id').value;
            const templateId = document.getElementById('import_template_id').value;
            const scheduledTime = document.getElementById('import_scheduled_time').value;
            
            if (!projectId) {
                alert('âŒ LÃ¼tfen proje seÃ§in!');
                return;
            }
            
            if (!window.selectedImportFile) {
                alert('âŒ Dosya seÃ§ilmedi!');
                return;
            }
            
            // FormData oluÅŸtur
            const formData = new FormData();
            formData.append('excel_file', window.selectedImportFile);
            formData.append('project_id', projectId);
            formData.append('template_id', templateId);
            formData.append('scheduled_time', scheduledTime);
            formData.append('quick_import', '1');
            
            // Loading gÃ¶ster
            const btn = document.getElementById('submitImportBtn');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> YÃ¼kleniyor...';
            btn.disabled = true;
            
            // Import et
            fetch('quick_import.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                
                if (data.success) {
                    alert(`âœ… ${data.message}\n\n${data.recordCount} kayÄ±t eklendi!`);
                    closeImportModal();
                    location.reload();
                } else {
                    let errorMsg = 'âŒ HATA: ' + data.message;
                    if (data.file) {
                        errorMsg += '\n\nDosya: ' + data.file + ':' + data.line;
                    }
                    if (data.trace) {
                        console.error('Stack Trace:', data.trace);
                        errorMsg += '\n\nDetaylar console\'da (F12)';
                    }
                    alert(errorMsg);
                }
            })
            .catch(error => {
                console.error('Import hatasÄ±:', error);
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                alert('âŒ Import sÄ±rasÄ±nda hata oluÅŸtu!');
            });
        }
        
        // Export fonksiyonu
        function exportData() {
            if (!confirm('TÃ¼m import kayÄ±tlarÄ±nÄ± Excel olarak indirmek istiyor musunuz?')) {
                return;
            }
            
            // Loading gÃ¶ster
            const btn = event.target.closest('.btn');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ä°ndiriliyor...';
            btn.disabled = true;
            
            // Export API'ye istek at
            fetch('export_imports.php')
                .then(response => {
                    if (!response.ok) throw new Error('Export baÅŸarÄ±sÄ±z');
                    return response.blob();
                })
                .then(blob => {
                    // DosyayÄ± indir
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'import_kayitlari_' + new Date().toISOString().split('T')[0] + '.xlsx';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    // Butonu eski haline getir
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                    
                    alert('âœ… Export baÅŸarÄ±lÄ±!');
                })
                .catch(error => {
                    console.error('Export hatasÄ±:', error);
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                    alert('âŒ Export sÄ±rasÄ±nda hata oluÅŸtu!');
                });
        }
        
        // Global deÄŸiÅŸkenler
        let currentReportImportId = null;
        let currentReportFileName = null;
        
        // Rapor modalÄ±nÄ± aÃ§
        function showReport(importId, fileName) {
            console.log('showReport called:', importId, fileName);
            try {
                currentReportImportId = importId;
                currentReportFileName = fileName;
                
                document.getElementById('reportFileName').textContent = fileName;
                const modal = document.getElementById('reportModal');
                console.log('Report Modal element:', modal);
                if (modal) {
                    modal.style.cssText = 'display: block !important; position: fixed !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; background: rgba(0,0,0,0.7) !important; z-index: 999999 !important; overflow-y: auto !important; padding: 20px !important;';
                    document.body.style.overflow = 'hidden';
                    console.log('Report Modal displayed');
                } else {
                    console.error('reportModal element not found!');
                    alert('âŒ Rapor modal elementi bulunamadÄ±!');
                    return;
                }
            
            // Rapor verilerini yÃ¼kle
            fetch('get_report.php?import_id=' + importId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Ã–zet istatistikleri gÃ¶ster
                        const summary = data.summary;
                        document.getElementById('reportSummary').innerHTML = `
                            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 10px; color: white; text-align: center;">
                                <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">${summary.total}</div>
                                <div style="font-size: 14px; opacity: 0.9;">Toplam KayÄ±t</div>
                            </div>
                            <div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 20px; border-radius: 10px; color: white; text-align: center;">
                                <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">${summary.answered}</div>
                                <div style="font-size: 14px; opacity: 0.9;">CevaplandÄ±</div>
                            </div>
                            <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 20px; border-radius: 10px; color: white; text-align: center;">
                                <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">${summary.busy}</div>
                                <div style="font-size: 14px; opacity: 0.9;">MeÅŸgul/CevapsÄ±z</div>
                            </div>
                            <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 20px; border-radius: 10px; color: white; text-align: center;">
                                <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">${summary.calling}</div>
                                <div style="font-size: 14px; opacity: 0.9;">AranÄ±yor</div>
                            </div>
                            <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); padding: 20px; border-radius: 10px; color: white; text-align: center;">
                                <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">${summary.pending}</div>
                                <div style="font-size: 14px; opacity: 0.9;">Aranacak</div>
                            </div>
                        `;
                        
                        // DetaylÄ± tabloyu doldur
                        let tableHTML = '';
                        data.details.forEach((row, index) => {
                            const bgColor = index % 2 === 0 ? '#f8f9fa' : 'white';
                            const statusColor = row.SipCode == 200 ? '#28a745' : (row.Status == 1 ? '#ffc107' : '#dc3545');
                            const statusText = row.SipCode == 200 ? 'âœ… BaÅŸarÄ±lÄ±' : (row.Status == 1 ? 'â³ Bekliyor' : 'âŒ BaÅŸarÄ±sÄ±z');
                            
                            tableHTML += `
                                <tr style="background: ${bgColor}; border-bottom: 1px solid #e0e0e0;">
                                    <td style="padding: 12px; font-size: 13px;">${row.PhoneNumber}</td>
                                    <td style="padding: 12px; font-size: 13px;">${row.Adi || '-'}</td>
                                    <td style="padding: 12px; text-align: center; font-size: 13px;">${row.CallCount} / ${row.TryCallCount}</td>
                                    <td style="padding: 12px; text-align: center; font-size: 13px;">
                                        <span style="background: ${statusColor}; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                            ${statusText}
                                        </span>
                                    </td>
                                    <td style="padding: 12px; font-size: 12px; color: #666;">${row.Durum || '-'}</td>
                                    <td style="padding: 12px; font-size: 13px;">${row.LastCallTime || '-'}</td>
                                    <td style="padding: 12px; font-size: 13px;">${row.tus1 || row.tus2 ? (row.tus1 || '') + ' ' + (row.tus2 || '') : '-'}</td>
                                </tr>
                            `;
                        });
                        
                        document.getElementById('reportTableBody').innerHTML = tableHTML;
                    } else {
                        alert('âŒ Rapor yÃ¼klenemedi: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Rapor yÃ¼kleme hatasÄ±:', error);
                    alert('âŒ Rapor yÃ¼klenirken hata oluÅŸtu!');
                });
        }
        
        // Rapor modalÄ±nÄ± kapat
        function closeReportModal() {
            document.getElementById('reportModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Ã‡oÄŸaltma modal'Ä±nÄ± aÃ§
        function showDuplicateModal(importId, fileName) {
            console.log('showDuplicateModal called:', importId, fileName);
            try {
                document.getElementById('source_import_id').value = importId;
                const modal = document.getElementById('duplicateModal');
                console.log('Modal element:', modal);
                if (modal) {
                    modal.style.cssText = 'display: flex !important; position: fixed !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; background: rgba(0,0,0,0.7) !important; z-index: 999999 !important; align-items: center !important; justify-content: center !important;';
                    document.body.style.overflow = 'hidden';
                    console.log('Modal displayed');
                } else {
                    console.error('duplicateModal element not found!');
                    alert('âŒ Modal elementi bulunamadÄ±!');
                }
            } catch (e) {
                console.error('showDuplicateModal error:', e);
                alert('âŒ Hata: ' + e.message);
            }
        }
        
        // Ã‡oÄŸaltma modal'Ä±nÄ± kapat
        function closeDuplicateModal() {
            const modal = document.getElementById('duplicateModal');
            if (modal) {
                modal.style.cssText = 'display: none !important;';
            }
            document.body.style.overflow = 'auto';
        }
        
        // Raporu Excel'e aktar
        function exportReport() {
            if (!currentReportImportId) {
                alert('âŒ Rapor yÃ¼klenmedi!');
                return;
            }
            
            const btn = document.getElementById('exportReportBtn');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ä°ndiriliyor...';
            btn.disabled = true;
            
            // Export URL
            const url = 'export_report.php?import_id=' + currentReportImportId;
            
            // DosyayÄ± indir
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(text || 'Export baÅŸarÄ±sÄ±z');
                        });
                    }
                    return response.blob();
                })
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'rapor_' + currentReportFileName.replace(/[^a-zA-Z0-9]/g, '_') + '_' + new Date().toISOString().split('T')[0] + '.csv';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                })
                .catch(error => {
                    console.error('Export hatasÄ±:', error);
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                    
                    // Hata mesajÄ±nÄ± gÃ¶ster
                    const errorMsg = error.message;
                    if (errorMsg.includes('<h3>')) {
                        // HTML hata mesajÄ± - yeni pencerede gÃ¶ster
                        const win = window.open('', '_blank');
                        win.document.write(errorMsg);
                    } else {
                        alert('âŒ Export hatasÄ±:\n\n' + errorMsg);
                    }
                });
        }

        // EÄŸer Ã§alÄ±ÅŸan arama varsa otomatik yenile (sadece GET isteklerinde)
        <?php 
        $hasRunning = false;
        $isPostRequest = $_SERVER['REQUEST_METHOD'] === 'POST';
        
        if (!$isPostRequest) {
            foreach ($imports as $import) {
                $stats = $importManager->getImportCallStats($import['id']);
                if ($stats['calling'] > 0) {
                    $hasRunning = true;
                    break;
                }
            }
        }
        
        if ($hasRunning): 
        ?>
        setTimeout(function() {
            location.reload();
        }, 5000);
        <?php endif; ?>
    
