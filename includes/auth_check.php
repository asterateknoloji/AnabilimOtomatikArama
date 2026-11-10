<?php
/**
 * Yetkilendirme Kontrol Dosyası
 * Tüm rapor dosyalarında kullanılacak ortak yetkilendirme kontrolü
 */

// Session kontrolü
if (!isset($_SESSION['user'])) {
    header('Location: ../../index.php');
    exit;
}

// Kullanıcı bilgilerini al - İki farklı session yapısını destekle
// Yeni yapı: $_SESSION['user_id'], $_SESSION['isadmin']
// Eski yapı: $_SESSION['user']['id'], $_SESSION['user']['isadmin']
if (is_array($_SESSION['user'])) {
    // Eski yapı (array)
    $logged_user_id = $_SESSION['user']['id'] ?? 0;
    $is_admin = $_SESSION['user']['isadmin'] ?? 0;
} else {
    // Yeni yapı (ayrı değişkenler)
    $logged_user_id = $_SESSION['user_id'] ?? 0;
    $is_admin = $_SESSION['isadmin'] ?? 0;
}

/**
 * Kullanıcının yetkili olduğu dahilileri getir
 * Kuyruk seçilmişse o kuyruktaki tüm agent'ları dahil eder
 * @return array Yetkili dahililer listesi (boş array = tüm dahililere erişim var veya admin)
 */
function getAuthorizedExtensions($pdo, $user_id, $is_admin = 0) {
    global $pdoAsterisk;
    
    // Admin kullanıcılar veya user_id yoksa tüm dahililere erişebilir
    if ($is_admin == 1 || empty($user_id)) {
        return []; // Boş array = filtreleme yok
    }
    
    try {
        // Kullanıcının tanımlı dahili/kuyruk listesini çek
        $stmt = $pdo->prepare("SELECT dahili FROM users_viewdahili WHERE kullaniciid = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        $tanimli_liste = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tanimli_liste)) {
            return [];
        }
        
        $yetkili_dahililer = [];
        
        // Her bir tanımlı değeri kontrol et
        foreach ($tanimli_liste as $item) {
            // Kuyruk mu kontrol et (asterisk.queues_config tablosunda var mı?)
            $kuyruk_stmt = $pdoAsterisk->prepare("SELECT extension FROM asterisk.queues_config WHERE extension = :ext LIMIT 1");
            $kuyruk_stmt->execute([':ext' => $item]);
            $is_queue = $kuyruk_stmt->fetch();
            
            if ($is_queue) {
                // KUYRUK: Bu kuyruktaki agent'ları çek (queue_log'dan son 30 gün)
                $agent_stmt = $pdoAsterisk->prepare("
                    SELECT DISTINCT
                        CASE 
                            WHEN agent LIKE 'SIP/%' THEN SUBSTRING_INDEX(agent, '/', -1)
                            WHEN agent LIKE 'PJSIP/%' THEN SUBSTRING_INDEX(agent, '/', -1)
                            WHEN agent LIKE 'Local/%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(agent, '/', -1), '@', 1)
                            ELSE agent
                        END AS agent_ext
                    FROM asterisk.queue_log
                    WHERE queuename = :queue
                      AND event IN ('ADDMEMBER', 'REMOVEMEMBER', 'COMPLETEAGENT', 'COMPLETECALLER', 'CONNECT')
                      AND time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                      AND agent IS NOT NULL
                      AND agent != 'NONE'
                      AND agent != ''
                ");
                $agent_stmt->execute([':queue' => $item]);
                $queue_agents = $agent_stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Kuyruk agent'larını listeye ekle
                $yetkili_dahililer = array_merge($yetkili_dahililer, $queue_agents);
            } else {
                // DAHİLİ: Direkt listeye ekle
                $yetkili_dahililer[] = $item;
            }
        }
        
        // Benzersiz dahililer listesi döndür
        return array_unique(array_filter($yetkili_dahililer));
        
    } catch (PDOException $e) {
        // Hata durumunda güvenli taraf: hiçbir şey gösterme
        error_log("Dahili yetki kontrolü hatası: " . $e->getMessage());
        return ['__NO_ACCESS__']; // Hiçbir sonuç dönmeyecek şekilde
    }
}

/**
 * SQL sorgusuna dahili filtreleme ekler
 * @param string $agent_column Agent/dahili sütunu (örn: 'agent', 'u.extension')
 * @return string SQL WHERE koşulu
 */
function getExtensionFilterSQL($agent_column = 'agent') {
    global $logged_user_id, $is_admin, $pdo;
    
    // Admin ise filtreleme yapma
    if ($is_admin == 1) {
        return "";
    }
    
    $authorized_extensions = getAuthorizedExtensions($pdo, $logged_user_id, $is_admin);
    
    // Hiç yetki yoksa
    if (empty($authorized_extensions)) {
        // Boş liste: ya admin ya da hiç kısıtlama tanımlanmamış
        // Ancak eğer users_viewdahili tablosunda kayıt varsa ama boşsa, erişim yok
        try {
            $check_stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM users_viewdahili WHERE kullaniciid = :user_id");
            $check_stmt->execute([':user_id' => $logged_user_id]);
            $has_restrictions = $check_stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;
            
            if ($has_restrictions) {
                // Kayıt var ama dahili yok = hiçbir şey göremez
                return " AND $agent_column = '__NO_ACCESS__'";
            }
            // Kayıt yoksa = kısıtlama tanımlanmamış, her şeyi görebilir
            return "";
        } catch (PDOException $e) {
            return " AND $agent_column = '__NO_ACCESS__'";
        }
    }
    
    // Yetkili dahilileri IN koşulu olarak ekle
    $placeholders = array_map(function($i) { return ":ext_$i"; }, array_keys($authorized_extensions));
    return " AND $agent_column IN (" . implode(',', $placeholders) . ")";
}

/**
 * SQL parametrelerine dahili değerlerini ekler
 * @param array $params Mevcut parametreler
 * @return array Güncellenmiş parametreler
 */
function addExtensionFilterParams($params = []) {
    global $logged_user_id, $is_admin, $pdo;
    
    // Admin ise parametre ekleme
    if ($is_admin == 1) {
        return $params;
    }
    
    $authorized_extensions = getAuthorizedExtensions($pdo, $logged_user_id, $is_admin);
    
    if (!empty($authorized_extensions)) {
        foreach ($authorized_extensions as $index => $extension) {
            $params[":ext_$index"] = $extension;
        }
    }
    
    return $params;
}

/**
 * Kullanıcının belirli bir dahiliyi görme yetkisi var mı?
 * @param string $extension Kontrol edilecek dahili
 * @return bool
 */
function canViewExtension($extension) {
    global $logged_user_id, $is_admin, $pdo;
    
    // Admin her şeyi görebilir
    if ($is_admin == 1) {
        return true;
    }
    
    $authorized_extensions = getAuthorizedExtensions($pdo, $logged_user_id, $is_admin);
    
    // Boş liste kontrolü
    if (empty($authorized_extensions)) {
        try {
            $check_stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM users_viewdahili WHERE kullaniciid = :user_id");
            $check_stmt->execute([':user_id' => $logged_user_id]);
            $has_restrictions = $check_stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;
            
            // Kısıtlama tanımlı değilse her şeyi görebilir
            return !$has_restrictions;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    return in_array($extension, $authorized_extensions);
}

/**
 * Kullanıcının yetkili olduğu kuyrukları getir (CALLCENTER raporları için)
 * @return array Yetkili kuyruk listesi (boş array = tüm kuyruklara erişim var veya admin)
 */
function getAuthorizedQueues($pdo, $user_id, $is_admin = 0) {
    global $pdoAsterisk;
    
    // Admin kullanıcılar veya user_id yoksa tüm kuyruklara erişebilir
    if ($is_admin == 1 || empty($user_id)) {
        return []; // Boş array = filtreleme yok
    }
    
    try {
        // Kullanıcının tanımlı dahili/kuyruk listesini çek
        $stmt = $pdo->prepare("SELECT dahili FROM users_viewdahili WHERE kullaniciid = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        $tanimli_liste = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tanimli_liste)) {
            return [];
        }
        
        $yetkili_kuyruklar = [];
        
        // Her bir tanımlı değeri kontrol et
        foreach ($tanimli_liste as $item) {
            // Kuyruk mu kontrol et (asterisk.queues_config tablosunda var mı?)
            $kuyruk_stmt = $pdoAsterisk->prepare("SELECT extension FROM asterisk.queues_config WHERE extension = :ext LIMIT 1");
            $kuyruk_stmt->execute([':ext' => $item]);
            $is_queue = $kuyruk_stmt->fetch();
            
            if ($is_queue) {
                // KUYRUK: Listeye ekle
                $yetkili_kuyruklar[] = $item;
            }
        }
        
        // Benzersiz kuyruklar listesi döndür
        return array_unique(array_filter($yetkili_kuyruklar));
        
    } catch (PDOException $e) {
        // Hata durumunda güvenli taraf: hiçbir şey gösterme
        error_log("Kuyruk yetki kontrolü hatası: " . $e->getMessage());
        return ['__NO_ACCESS__']; // Hiçbir sonuç dönmeyecek şekilde
    }
}
