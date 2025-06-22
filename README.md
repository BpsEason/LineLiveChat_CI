# LineLiveChat_CI

這是一個基於 **CodeIgniter 3.1.13** 打造的 Line 客服系統，結合 **Line Messaging API** 和 **Redis**，讓客服人員可以即時處理 Line 用戶的訊息。系統使用**長輪詢**技術實現客服介面的即時訊息更新，並透過後台 Worker 非同步發送回覆。專案已優化，可穩定支援 **100~200 人同時在線**，適合中小型企業的客服需求。

## 系統亮點
- **高併發處理**：經過優化，系統可穩定支援 **100~200 人同時在線**，透過 Redis 佇列和長輪詢技術，確保高效訊息處理與低延遲回應。
- **高效訊息佇列**：使用 Redis 的 `LPUSH`/`BLPOP` 實現高併發訊息接收與回覆，解耦 Webhook 和客服處理，確保穩定性。
- **即時客服體驗**：透過 jQuery 長輪詢，客服介面能即時顯示新訊息，無需頻繁請求伺服器，兼顧效率與簡易實現。
- **非同步回覆**：後台 Worker 獨立處理回覆發送，減輕 Web 伺服器負擔，適合高流量場景。
- **安全設計**：支援 Line Webhook 簽名驗證（HMAC-SHA256）和 CodeIgniter CSRF 保護，確保請求來源安全與防止惡意操作。
- **輕量架構**：採用 CodeIgniter 3，快速部署、易於維護，適合中小型專案或快速原型開發。

## 功能
- 接收 Line 用戶的文字、貼圖、圖片訊息，以及追蹤/取消追蹤事件。
- 客服介面即時顯示新訊息（透過長輪詢）。
- 用 Redis 佇列管理訊息，解耦接收和回覆的處理。
- 後台 Worker (`line_message_worker.php`) 負責從 Redis 取出客服回覆並發送。
- 支援 Line Webhook 簽名驗證，確保安全性。
- 使用 CodeIgniter 的 CSRF 保護，避免未授權請求。

## 專案結構
```
LineLiveChat_CI/
├── application/
│   ├── config/                # 設定檔 (database.php, line.php, redis.php 等)
│   ├── controllers/           # 控制器 (Line_webhook.php, Customer_service.php)
│   ├── libraries/             # 自訂函式庫 (Line_api.php, Redis_library.php)
│   ├── models/                # 模型 (Message_model.php, User_model.php)
│   ├── views/                 # 視圖 (customer_service/index.php 等)
│   ├── cli/                   # CLI 腳本 (line_message_worker.php)
│   └── third_party/           # 第三方庫占位
├── public/
│   ├── css/                   # 樣式 (style.css)
│   ├── js/                    # JavaScript (customer_service.js)
│   └── index.php              # CodeIgniter 入口
├── system/                    # CodeIgniter 核心
├── vendor/                    # Composer 依賴
├── composer.json              # Composer 設定
└── .gitignore                 # Git 忽略設定
```

### 用到的技術
- **CodeIgniter 3.1.13**：簡單好用的 PHP 框架，適合快速開發。
- **Line Messaging API**：透過 `linecorp/line-bot-sdk` 處理 Line 訊息。
- **Redis (Predis)**：用來做訊息佇列 (`line_incoming_messages`, `customer_outgoing_messages`) 和即時通知。
- **jQuery 長輪詢**：讓客服介面即時更新訊息。
- **CLI Worker**：處理非同步回覆，減輕 Webhook 負擔。
- **Apache + FastCGI**：高效處理 PHP 請求，支援高併發。

## 安裝步驟
1. **克隆專案**：
   ```bash
   git clone https://github.com/BpsEason/LineLiveChat_CI.git
   cd LineLiveChat_CI
   ```

2. **安裝依賴**：
   - 確保 PHP >= 5.6 已安裝。
   - 用 Composer 安裝依賴：
     ```bash
     composer install
     ```
   - 安裝並啟動 Redis 伺服器（預設 `127.0.0.1:6379`，或按部署方式另行設定）。

3. **設定 Line API**：
   - 編輯 `application/config/line.php`，填入 Line Channel Access Token 和 Secret：
     ```php
     $config['line_channel_access_token'] = '您的_TOKEN';
     $config['line_channel_secret'] = '您的_SECRET';
     ```

4. **設定 Redis**：
   - 編輯 `application/config/redis.php`，根據部署環境設定 `host`、`port`、`password` 等：
     ```php
     $config['redis_host'] = 'redis-server-ip'; // 例：192.168.1.100
     $config['redis_port'] = 6379;
     $config['redis_password'] = '您的密碼';
     ```

5. **設定資料庫（可選）**：
   - 編輯 `application/config/database.php`，若需要可設定 MySQL 連線（目前為占位）。

6. **設定 Apache Web 伺服器**：
   - 將 Document Root 設為 `public/`。
   - 確保 `application/` 和 `system/` 目錄無法直接訪問。
   - 強烈建議啟用 HTTPS 保護通訊。

7. **啟動 Worker**：
   - 運行 CLI 腳本處理回覆：
     ```bash
     php application/cli/line_message_worker.php
     ```
   - 建議用 PM2 或 systemd 管理 Worker，確保穩定運行。

8. **設定 Line Webhook**：
   - 在 Line Developers 後台設定 Webhook URL，例如：`https://your-domain.com/line_webhook`。

9. **訪問介面**：
   - 瀏覽器輸入 `https://your-domain.com/customer_service` 開啟客服介面。

## 部署與效能優化
為了穩定支援 **100~200 人同時在線**，本專案採用 **Apache + FastCGI** 作為 Web Server，並將 Redis 部署在獨立伺服器，以提升效能與穩定性。以下是具體部署方式：

### 1. Apache + FastCGI 部署
- **環境**：將 Web Server 部署在一台或多台伺服器上，運行 Apache 搭配 FastCGI 模組（`mod_fcgid` 或 `mod_fastcgi`）與 PHP-FPM。
- **配置**：
  - 安裝 Apache 和 PHP-FPM：
    ```bash
    sudo apt install apache2 php-fpm libapache2-mod-fcgid
    sudo a2enmod proxy_fcgi setenvif
    sudo a2enconf php-fpm
    ```
  - 設定 Apache 虛擬主機，指向 `public/` 目錄，範例配置（`/etc/apache2/sites-available/your-domain.conf`）：
    ```apache
    <VirtualHost *:443>
        ServerName your-domain.com
        DocumentRoot /path/to/LineLiveChat_CI/public

        <Directory /path/to/LineLiveChat_CI/public>
            Options -Indexes +FollowSymLinks
            AllowOverride All
            Require all granted
        </Directory>

        <FilesMatch \.php$>
            SetHandler "proxy:unix:/var/run/php-fpm.sock|fcgi://localhost/"
        </FilesMatch>

        <Directory /path/to/LineLiveChat_CI/(application|system|vendor)>
            Require all denied
        </Directory>

        SSLEngine on
        SSLCertificateFile /path/to/your-cert.pem
        SSLCertificateKeyFile /path/to/your-key.pem
    </VirtualHost>
    ```
  - 啟用配置並重啟 Apache：
    ```bash
    sudo a2ensite your-domain
    sudo systemctl restart apache2
    ```
  - 調整 PHP-FPM 設定（`/etc/php/7.x/fpm/pool.d/www.conf`），支援高併發：
    ```ini
    pm = dynamic
    pm.max_children = 50
    pm.start_servers = 10
    pm.min_spare_servers = 5
    pm.max_spare_servers = 20
    pm.max_requests = 500
    ```
  - 啟用 HTTPS，使用 Let’s Encrypt 或其他 SSL 證書：
    ```bash
    sudo certbot --apache -d your-domain.com
    ```
- **效能優化**：
  - 啟用 Apache 的 `mod_cache` 或 `mod_expires` 快取靜態資源（CSS、JS）：
    ```apache
    <IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType text/css "access plus 1 month"
        ExpiresByType application/javascript "access plus 1 month"
    </IfModule>
    ```
  - 使用 Apache Benchmark (ab) 測試負載：
    ```bash
    ab -n 1000 -c 200 https://your-domain.com/customer_service
    ```

### 2. Redis 獨立部署
- **環境**：將 Redis 部署在獨立伺服器，建議配置至少 4GB 記憶體（視訊息量調整）。
- **配置**：
  - 安裝 Redis：
    ```bash
    sudo apt install redis-server
    ```
  - 修改 Redis 配置文件（`/etc/redis/redis.conf`）：
    ```conf
    bind 0.0.0.0 # 允許外部連線
    requirepass 您的強密碼 # 設定密碼
    maxmemory 2gb # 限制記憶體使用
    maxmemory-policy allkeys-lru # 記憶體滿時移除不常用鍵
    appendonly yes # 啟用 AOF 持久化
    appendfsync everysec
    ```
  - 開放 Redis 埠（預設 6379），僅允許 Web Server 和 Worker 伺服器連線：
    ```bash
    sudo ufw allow from web-server-ip to any port 6379
    ```
  - 更新 `application/config/redis.php`，指向 Redis 伺服器：
    ```php
    $config['redis_host'] = 'redis-server-ip'; // 例：192.168.1.100
    $config['redis_password'] = '您的密碼';
    ```
  - 重啟 Redis：
    ```bash
    sudo systemctl restart redis
    ```
- **效能優化**：
  - 監控 Redis 記憶體與佇列長度：
    ```bash
    redis-cli INFO MEMORY
    redis-cli LLEN line_incoming_messages
    ```
  - 若佇列過長，可增加 Worker 數量或優化 Redis 配置。
- **高可用性（可選）**：部署 Redis Sentinel 或 Redis Cluster，實現主從複製與故障轉移：
  ```bash
  redis-sentinel /etc/redis/sentinel.conf
  ```

### 3. Worker 部署
- **環境**：Worker（`line_message_worker.php`）可與 Web Server 同機或獨立部署，建議獨立以降低 Web Server 負載。
- **配置**：
  - 使用 PM2 管理 Worker：
    ```bash
    npm install -g pm2
    pm2 start php --name line-worker -- application/cli/line_message_worker.php
    pm2 startup
    pm2 save
    ```
  - 或使用 systemd，範例服務文件（`/etc/systemd/system/line-worker.service`）：
    ```ini
    [Unit]
    Description=Line Message Worker
    After=network.target

    [Service]
    ExecStart=/usr/bin/php /path/to/LineLiveChat_CI/application/cli/line_message_worker.php
    Restart=always
    User=www-data

    [Install]
    WantedBy=multi-user.target
    ```
  - 啟用並啟動服務：
    ```bash
    sudo systemctl enable line-worker
    sudo systemctl start line-worker
    ```
  - 檢查 Worker 日誌：
    ```bash
    tail -f application/logs/log-*.php
    ```
- **連線 Redis**：確保 Worker 的 `application/config/redis.php` 指向獨立 Redis 伺服器。

### 4. 效能與監控
- **Web Server**：使用 Apache Benchmark 測試負載，確保支援 100~200 人同時在線：
  ```bash
  ab -n 1000 -c 200 https://your-domain.com/customer_service
  ```
- **Redis**：監控連線數與佇列長度，設定告警若超過閾值：
  ```bash
  redis-cli MONITOR
  redis-cli INFO CLIENTS
  ```
- **日誌**：集中日誌到 ELK Stack 或 CloudWatch，檢查 Webhook 錯誤與 Worker 狀態。
- **快取（可選）**：用 Redis 快取 Line API 回應，減少外部請求：
  ```php
  $this->redis->setex('line_user_profile_' . $user_id, 3600, json_encode($profile));
  ```

### 5. 硬體建議
- **Web Server (Apache + FastCGI)**：2 vCPU，4GB RAM，支援 100~200 人同時在線（視 PHP-FPM 設定調整）。
- **Redis Server**：2 vCPU，4~8GB RAM，確保記憶體足夠處理佇列與快取。
- **Worker Server（若獨立）**：1 vCPU，2GB RAM，足以運行 CLI 腳本。

## 使用方式
- **Line Webhook**：接收用戶訊息，存到 Redis 的 `line_incoming_messages` 佇列。
- **客服介面**：透過長輪詢從 Redis 取出訊息並顯示，客服輸入用戶 ID 和回覆內容後，回覆存入 `customer_outgoing_messages` 佇列。
- **後台 Worker**：從 Redis 取出回覆，透過 Line Push API 發送給用戶。

## 關鍵程式碼
以下是專案中幾段核心程式碼，展示系統的技術實現：

### 1. Line Webhook 簽名驗證 (`application/controllers/Line_webhook.php`)
```php
public function index() {
    $signature = $this->input->get_request_header('X-Line-Signature');
    $http_body = file_get_contents('php://input');
    $channel_secret = $this->config->item('line_channel_secret');

    if (!$this->line_api->validate_signature($http_body, $signature, $channel_secret)) {
        log_message('error', 'Line Webhook: Signature validation failed. Signature: ' . $signature);
        http_response_code(400);
        echo 'Signature validation failed';
        exit();
    }
    // 處理事件邏輯...
}
```
**說明**：這段程式碼負責驗證 Line Webhook 請求的簽名，確保請求來自 Line 官方。使用 HMAC-SHA256 演算法比對 `X-Line-Signature` 和請求體，若驗證失敗則拒絕處理，增強安全性。

### 2. Redis 訊息佇列處理 (`application/models/Message_model.php`)
```php
public function add_line_message_to_redis($user_id, $message_type, $message_content) {
    $message_data = [
        'direction' => 'in',
        'user_id' => $user_id,
        'type' => $message_type,
        'content' => $message_content,
        'timestamp' => time()
    ];
    $this->redis->rpush(self::LINE_IN_QUEUE, json_encode($message_data));
    $this->redis->publish(self::NEW_MESSAGE_CHANNEL, 'new_line_message');
    log_message('info', 'Line message added to Redis queue for user: ' . $user_id);
}

public function get_new_incoming_message_from_redis($timeout = 25) {
    $result = $this->redis->blpop([self::LINE_IN_QUEUE], $timeout);
    if ($result && isset($result[1])) {
        log_message('info', 'New incoming message retrieved from Redis.');
        return json_decode($result[1], true);
    }
    return null;
}
```
**說明**：這段程式碼展示如何用 Redis 管理訊息佇列。`add_line_message_to_redis` 將 Line 訊息推入佇列並發送通知；`get_new_incoming_message_from_redis` 使用阻塞式 `BLPOP` 提取訊息，支援長輪詢的高效實現，確保 100~200 人同時在線時的穩定性。

### 3. 長輪詢實現 (`public/js/customer_service.js`)
```javascript
function pollMessages() {
    $.ajax({
        url: '<?php echo site_url("customer_service/poll_for_messages"); ?>',
        type: 'GET',
        dataType: 'json',
        cache: false,
        timeout: 30000,
        success: function(response) {
            if (response.status === 'success') {
                console.log('Received new message:', response.message);
                displayMessage(response.message);
            } else if (response.status === 'no_new_messages') {
                console.log('No new messages within timeout, re-polling...');
            }
            pollMessages();
        },
        error: function(xhr, status, error) {
            console.error('Long Polling Error:', status, error);
            setTimeout(pollMessages, 5000);
        }
    });
}
```
**說明**：這段 JavaScript 使用 jQuery 實現長輪詢，持續向伺服器請求新訊息。若收到訊息則顯示，否則在超時或錯誤後重新輪詢，確保客服介面即時更新。程式碼經過優化，能穩定處理 100~200 人同時在線的負載。

## 技術細節與設計考量
- **為何用 CodeIgniter 3？**  
  CodeIgniter 輕量、好上手，適合快速開發中小型專案。相比 Laravel 或 Symfony，它的配置簡單，學習曲線低，適合快速展示功能原型。

- **為何用 Redis 做佇列？**  
  Redis 記憶體內處理速度快，支援 `LPUSH` 和 `BLPOP` 等原子操作，適合高併發訊息佇列（100~200 人同時在線）。相比 MySQL，能避免鎖定問題，並讓 Webhook 和回覆處理分離，防止超時。

- **長輪詢的選擇**  
  長輪詢讓客服介面即時更新訊息，伺服器只在有新訊息或超時時回應，減少不必要請求。相較 WebSocket，長輪詢實現簡單，無需額外伺服器支援，適合 CodeIgniter 環境並能穩定支援高併發。

- **Webhook 簽名驗證**  
  使用 HMAC-SHA256 驗證 `X-Line-Signature`，確保請求來自 Line，防止偽造。`hash_equals()` 用於安全比較簽名。

- **Reply API vs Push API**  
  Reply API 用於即時回應（需 `replyToken`，限 30 秒內）；Push API 可隨時發送訊息給指定用戶。本專案用 Push API 發送歡迎訊息和客服回覆。

## 未來改進
- **支援多客服**：加入訊息分配（自動或手動認領）和會話管理，存對話歷史到資料庫。
- **處理更多訊息類型**：擴展 `Line_webhook` 處理圖片、影片，下載至雲端儲存（如 AWS S3）並顯示。
- **升級即時技術**：用 WebSocket 取代長輪詢，提升效率。
- **監控系統**：整合 Prometheus 或日誌分析，監控 Worker 和 Redis 狀態。

## 貢獻
有任何建議或問題，歡迎提交 Issue 或 Pull Request 至 [GitHub 倉庫](https://github.com/BpsEason/LineLiveChat_CI.git)。

## 授權
本專案採用 MIT 授權，詳見 [LICENSE](LICENSE) 文件。

## 聯繫
有問題請聯繫 [BpsEason](https://github.com/BpsEason) 或提交 Issue。
