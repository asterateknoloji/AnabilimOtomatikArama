<?php
/**
 * IP Debug - Hangi IP'nin algılandığını gösterir
 */

function getRealIpAddress()
{
    // Proxy başlıklarını kontrol et
    $headers = array(
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    );
    
    foreach ($headers as $header) {
        if (isset($_SERVER[$header]) && !empty($_SERVER[$header])) {
            // Birden fazla IP varsa (virgülle ayrılmış), ilkini al
            $ip = $_SERVER[$header];
            if (strpos($ip, ',') !== false) {
                $ips = explode(',', $ip);
                $ip = trim($ips[0]);
            }
            
            // IP adresini validate et
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
            // Private IP'leri de kabul et (local network için)
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return 'unknown';
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP Debug</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #252526;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
        }
        h1 {
            color: #4ec9b0;
            border-bottom: 2px solid #4ec9b0;
            padding-bottom: 10px;
        }
        .result {
            background: #1e1e1e;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #4ec9b0;
        }
        .result.primary {
            border-left-color: #4ec9b0;
            font-size: 24px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #3e3e42;
        }
        th {
            background: #1e1e1e;
            color: #4ec9b0;
            font-weight: bold;
        }
        tr:hover {
            background: #2d2d30;
        }
        .label {
            color: #9cdcfe;
        }
        .value {
            color: #ce9178;
        }
        .null {
            color: #808080;
            font-style: italic;
        }
        .success {
            color: #4ec9b0;
        }
        .warning {
            color: #dcdcaa;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 IP Adresi Debug</h1>
        
        <div class="result primary">
            <span class="label">Algılanan IP:</span> 
            <span class="success"><?php echo getRealIpAddress(); ?></span>
        </div>
        
        <h2>📋 Tüm HTTP Başlıkları:</h2>
        <table>
            <thead>
                <tr>
                    <th>Başlık</th>
                    <th>Değer</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $headers = array(
                    'HTTP_X_FORWARDED_FOR' => 'Proxy/Load Balancer IP',
                    'HTTP_X_REAL_IP' => 'Gerçek IP (Nginx)',
                    'HTTP_CLIENT_IP' => 'İstemci IP',
                    'HTTP_X_FORWARDED' => 'Proxy Forwarded',
                    'HTTP_FORWARDED_FOR' => 'Forwarded For',
                    'HTTP_FORWARDED' => 'Forwarded',
                    'REMOTE_ADDR' => 'Direkt Bağlantı IP'
                );
                
                foreach ($headers as $header => $desc) {
                    $value = $_SERVER[$header] ?? null;
                    $status = $value ? '✅' : '❌';
                    echo "<tr>";
                    echo "<td><span class='label'>{$header}</span><br><small>{$desc}</small></td>";
                    if ($value) {
                        echo "<td><span class='value'>{$value}</span></td>";
                        echo "<td><span class='success'>{$status} Mevcut</span></td>";
                    } else {
                        echo "<td><span class='null'>null</span></td>";
                        echo "<td><span class='null'>{$status} Yok</span></td>";
                    }
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
        
        <h2>🌐 Diğer Bilgiler:</h2>
        <table>
            <tbody>
                <tr>
                    <td><span class="label">User Agent:</span></td>
                    <td><span class="value"><?php echo $_SERVER['HTTP_USER_AGENT'] ?? 'Bilinmiyor'; ?></span></td>
                </tr>
                <tr>
                    <td><span class="label">Request Method:</span></td>
                    <td><span class="value"><?php echo $_SERVER['REQUEST_METHOD'] ?? 'Bilinmiyor'; ?></span></td>
                </tr>
                <tr>
                    <td><span class="label">Server Software:</span></td>
                    <td><span class="value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor'; ?></span></td>
                </tr>
                <tr>
                    <td><span class="label">Server IP:</span></td>
                    <td><span class="value"><?php echo $_SERVER['SERVER_ADDR'] ?? 'Bilinmiyor'; ?></span></td>
                </tr>
                <tr>
                    <td><span class="label">Server Port:</span></td>
                    <td><span class="value"><?php echo $_SERVER['SERVER_PORT'] ?? 'Bilinmiyor'; ?></span></td>
                </tr>
            </tbody>
        </table>
        
        <div class="result">
            <strong>📝 Not:</strong><br>
            - Eğer <span class="warning">127.0.0.1</span> görünüyorsa, proxy/reverse proxy kullanıyorsunuz.<br>
            - Gerçek IP için <span class="success">HTTP_X_FORWARDED_FOR</span> veya <span class="success">HTTP_X_REAL_IP</span> başlığı kullanılır.<br>
            - Local network IP'si: <span class="warning">192.168.x.x</span> formatında olmalı.
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="otomatikarama.php" style="color: #4ec9b0; text-decoration: none;">← Ana Sayfaya Dön</a>
        </div>
    </div>
</body>
</html>
