# LineLiveChat_CI

這是一個基於 **CodeIgniter 3.1.13** 打造的 Line 客服系統，結合 **Line Messaging API** 和 **Redis**，讓客服人員可以即時處理 Line 用戶的訊息。系統使用**長輪詢**技術實現客服介面的即時訊息更新，並透過後台 Worker 非同步發送回覆。專案經過優化，可穩定支援 **100~200 人同時在線**，特別透過在不同 Windows Server 上共用 Redis 解決效能瓶頸，適合中小型企業的客服需求。

## 系統亮點
- **跨 Windows Server 共用 Redis**：透過在多台 Windows Server 上部署並共用單一 Redis 實例，實現訊息佇列和快取的高效共享，顯著提升高併發場景下的效能，解決單伺服器記憶體和處理瓶頸，穩定支援 **100~200 人同時在線**。
- **高併發處理**：結合 Redis 佇列和長輪詢技術，確保高效訊息處理與低延遲回應，特別適合分散式環境下的高流量需求。
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

## 架構流程圖
以下是系統的架構流程圖，使用 Mermaid 語法繪製，展示各組件（包括跨 Windows Server 共用 Redis）的交互流程：

```mermaid
graph TD
    A[Line 用戶] -->|發送訊息| B[Line Messaging API]
    B -->|Webhook 請求| C[Windows Server 1: Apache + FastCGI]
    C --> D[Line_webhook 控制器]
    D -->|驗證簽名| E[共用 Redis 伺服器: line_incoming_messages]
    E -->|發佈通知| F[Windows Server 2: 客服介面 - 長輪詢]
    F -->|顯示訊息| G[客服人員]
    G -->|輸入回覆| H[Customer_service 控制器]
    H -->|存入回覆| E[共用 Redis 伺服器: customer_outgoing_messages]
    E -->|阻塞提取| I[Windows Server 3: line_message_worker.php]
    I -->|發送回覆| B
    E -->|快取用戶資料| J[共用 Redis 快取]
    D -->|記錄日誌| K[應用日誌]
```

**說明**：
- **Line 用戶** 透過 Line 發送訊息至 **Line Messaging API**。
- **Webhook** 將訊息轉發至 **Windows Server 1** 上的 **Line_webhook 控制器**。
- 控制器驗證簽名後，將訊息存入 **共用 Redis 伺服器** 的 `line_incoming_messages` 佇列。
- **Windows Server 2** 的 **客服介面** 透過長輪詢從共用 Redis 提取訊息並顯示。
- 客服人員輸入回覆，經 **Customer_service 控制器** 存入共用 Redis 的 `customer_outgoing_messages` 佇列。
- **Windows Server 3** 的 **後台 Worker** 從共用 Redis 提取回覆並透過 Line API 發送。
- 共用 Redis 同時用於快取用戶資料，減少 API 請求，跨伺服器共享確保高效能。

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
- **Redis (Predis)**：跨 Windows Server 共用 Redis 實例，用於訊息佇列 (`line_incoming_messages`, `customer_outgoing_messages`) 和快取。
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
   - 在獨立 Windows Server 或共用伺服器上安裝並啟動 Redis（預設 `127.0.0.1:6379`，或按部署方式另行設定）。

3. **設定 Line API**：
   - 編輯 `application/config/line.php`，填入 Line Channel Access Token 和 Secret：
     ```php
     $config['line_channel_access_token'] = '您的_TOKEN';
     $config['line_channel_secret'] = '您的_SECRET';
     ```

4. **設定 Redis**：
   - 編輯 `application/config/redis.php`，指向共用 Redis 伺服器：
     ```php
     $config['redis_host'] = 'redis-server-ip'; // 例：192.168.1.100
     $config['redis_port'] = 6379;
     $config['redis_password'] = '您的密碼';
     ```

5. **設定資料庫（可選）**：
   - 編輯 `application/config/database.php`，若需要可設定 MySQL 連線（目前為占位）。

6. **設定 Apache Web 伺服器（Windows Server）**：
   - 在 Windows Server 上安裝 Apache 和 PHP，確保 Document Root 設為 `public/`。
   - 確保 `application/` 和 `system/` 目錄無法直接訪問。
   - 強烈建議啟用 HTTPS 保護通訊（使用 SSL 證書）。

7. **啟動 Worker**：
   - 在另一台 Windows Server 上運行 CLI 腳本處理回覆：
     ```bash
     php application/cli/line_message_worker.php
     ```
   - 建議使用 Windows 排程器或第三方工具（如 NSSM）管理 Worker，確保穩定運行。

8. **設定 Line Webhook**：
   - 在 Line Developers 後台設定 Webhook URL，例如：`https://your-domain.com/line_webhook`。

9. **訪問介面**：
   - 瀏覽器輸入 `https://your-domain.com/customer_service` 開啟客服介面。

## 部署與效能優化
為了穩定支援 **100~200 人同時在線**，本專案在多台 Windows Server 上部署，並透過共用 Redis 實例解決效能瓶頸。以下是具體部署方式：

### 1. Apache + FastCGI 部署（Windows Server）
- **環境**：在 Windows Server 上部署 Apache 搭配 FastCGI 模組和 PHP-FPM，支援高併發。
- **配置**：
  - 安裝 Apache 和 PHP（透過 XAMPP 或手動安裝）。
  - 設定 Apache 虛擬主機，指向 `public/` 目錄，範例配置（`httpd-vhosts.conf`）：
    ```apache
    <VirtualHost *:443>
        ServerName your-domain.com
        DocumentRoot "C:/path/to/LineLiveChat_CI/public"

        <Directory "C:/path/to/LineLiveChat_CI/public">
            Options -Indexes +FollowSymLinks
            AllowOverride All
            Require all granted
        </Directory>

        <FilesMatch \.php$>
            SetHandler "proxy:fcgi://127.0.0.1:9000"
        </FilesMatch>

        <Directory "C:/path/to/LineLiveChat_CI/(application|system|vendor)">
            Require all denied
        </Directory>

        SSLEngine on
        SSLCertificateFile "C:/path/to/your-cert.pem"
        SSLCertificateKeyFile "C:/path/to/your-key.pem"
    </VirtualHost>
    ```
  - 啟用 HTTPS，使用 Let’s Encrypt 或自簽證書。
  - 調整 PHP-FPM 設定（`php-fpm.conf`），支援高併發：
    ```ini
    pm = dynamic
    pm.max_children = 50
    pm.start_servers = 10
    pm.min_spare_servers = 5
    pm.max_spare_servers = 20
    pm.max_requests = 500
    ```
- **效能優化**：
  - 啟用 Apache 的 `mod_expires` 快取靜態資源：
    ```apache
    <IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType text/css "access plus 1 month"
        ExpiresByType application/javascript "access plus 1 month"
    </IfModule>
    ```
  - 使用壓力測試工具（如 JMeter）測試負載：
    ```bash
    jmeter -n -t testplan.jmx -l results.jtl
    ```

### 2. 共用 Redis 部署（跨 Windows Server）
- **環境**：在獨立 Windows Server 上部署 Redis，供多台伺服器（Web Server、Worker）共用，建議配置至少 4GB 記憶體。
- **配置**：
  - 在 Windows Server 上安裝 Redis（使用官方 Windows 版本或 WSL）：
    - 下載 Redis for Windows 或使用 Microsoft 提供的 Redis 移植版。
    - 啟動 Redis 服務：
      ```cmd
      redis-server.exe redis.conf
      ```
  - 修改 Redis 配置文件（`redis.conf`）：
    ```conf
    bind 0.0.0.0 # 允許外部連線
    requirepass 您的強密碼 # 設定密碼
    maxmemory 2gb # 限制記憶體使用
    maxmemory-policy allkeys-lru # 記憶體滿時移除不常用鍵
    appendonly yes # 啟用 AOF 持久化
    appendfsync everysec
    ```
  - 設定 Windows 防火牆，僅允許指定伺服器連線至 Redis 埠（6379）：
    ```powershell
    New-NetFirewallRule -Name "Allow-Redis" -DisplayName "Allow Redis" -Direction Inbound -Protocol TCP -LocalPort 6379 -Action Allow -RemoteAddress <web-server-ip>,<worker-server-ip>
    ```
  - 更新所有伺服器的 `application/config/redis.php`，指向共用 Redis：
    ```php
    $config['redis_host'] = 'redis-server-ip'; // 例：192.168.1.100
    $config['redis_password'] = '您的密碼';
    ```
- **效能優化**：
  - **共用 Redis 的優勢**：多台 Windows Server 共用單一 Redis 實例，集中管理訊息佇列和快取，減少記憶體碎片化，降低單伺服器負載，提升 100~200 人同時在線的處理能力。
  - 監控 Redis 效能：
    ```cmd
    redis-cli INFO MEMORY
    redis-cli LLEN line_incoming_messages
    ```
  - 若佇列過長，可增加 Worker 數量或調整 Redis maxmemory。
- **高可用性**：部署 Redis Sentinel 或 Cluster，實現主從複製與故障轉移：
  ```cmd
  redis-server.exe sentinel.conf --sentinel
  ```

### 3. Worker 部署（Windows Server）
- **環境**：在獨立 Windows Server 上運行 Worker，降低 Web Server 負載。
- **配置**：
  - 使用 NSSM（Non-Sucking Service Manager）將 Worker 設為 Windows 服務：
    ```cmd
    nssm install LineWorker "C:\path\to\php.exe" "C:\path\to\LineLiveChat_CI\application\cli\line_message_worker.php"
    nssm start LineWorker
    ```
  - 檢查 Worker 日誌：
    ```cmd
    type application\logs\log-*.php
    ```
- **連線共用 Redis**：確保 Worker 的 `application/config/redis.php` 指向共用 Redis 伺服器。

### 4. 效能與監控
- **Web Server**：使用 JMeter 或 Apache Benchmark 測試負載：
  ```bash
  ab -n 1000 -c 200 https://your-domain.com/customer_service
  ```
- **Redis**：監控共用 Redis 的連線數與佇列長度：
  ```cmd
  redis-cli MONITOR
  redis-cli INFO CLIENTS
  ```
- **日誌**：集中日誌到 Splunk 或 Windows 事件檢視器，檢查 Webhook 錯誤與 Worker 狀態。
- **快取**：用共用 Redis 快取 Line API 回應：
  ```php
  $this->redis->setex('line_user_profile_' . $user_id, 3600, json_encode($profile));
  ```

### 5. 硬體建議
- **Web Server (Apache + FastCGI)**：2 vCPU，4GB RAM，支援 100~200 人同時在線。
- **Redis Server**：2 vCPU，4~8GB RAM，確保記憶體足夠處理跨伺服器佇列與快取。
- **Worker Server**：1 vCPU，2GB RAM，足以運行 CLI 腳本。

## 使用方式
- **Line Webhook**：接收用戶訊息，存到共用 Redis 的 `line_incoming_messages` 佇列。
- **客服介面**：透過長輪詢從共用 Redis 取出訊息並顯示，客服回覆存入 `customer_outgoing_messages` 佇列。
- **後台 Worker**：從共用 Redis 取出回覆，透過 Line Push API 發送。

## 關鍵程式碼（含詳細註解）
### 1. Line Webhook 簽名驗證 (`application/controllers/Line_webhook.php`)
```php
/**
 * 處理 Line Webhook 的主要方法，負責驗證請求簽名並處理事件
 */
public function index(): void {
    // 取得 HTTP 頭中提取的 X-Line-Signature 用於驗證
    $signature = $this->input->get_request_header('X-Line-Signature');
    // 取得原始請求體用於簽名計算
    $http_body = file_get_contents('php://input');
    // 從配置文件中取得 Line Channel Secret
    $channel_secret = $this->config->item('line_channel_secret');

    // 驗證簽名，若失敗則記錄錯誤並拒絕請求
    if (!$this->line_api->validate_signature($http_body, $signature, $channel_secret)) {
        log_message('error', 'Line Webhook: Signature validation failed. Signature: ' . $signature);
        http_response_code(400);
        echo 'Signature validation failed';
        exit();
    }
    // 解碼 JSON 請求體，確保事件格式正確
    $decoded_body = json_decode($http_body, true);
    if (!isset($decoded_body['events']) || !is_array($decoded_body['events'])) {
        log_message('warning', 'Line Webhook: Invalid event format received.');
        http_response_code(400);
        echo 'Invalid event format';
        exit();
    }

    // 處理每個事件（訊息、追蹤、取消追蹤等）
    $events = $decoded_body['events'];
    foreach ($events as &$event) {
        switch ($event['type']) {
            case 'message':
                $this->handleMessageEvent($event); // 處理訊息事件
                break;
            case 'follow':
                $this->handleFollowEvent($event); // 處理追蹤事件
                break;
            case 'unfollow':
                $this->handleUnfollowEvent($event); // 處理取消追蹤事件
                break;
            default:
                log_message('info', 'Line Webhook: Unhandled event type: ' . $event['type']);
                break;
        }
    }
    echo "OK"; // 回應 Line API 表示處理成功
}
```
**說明**：此程式碼驗證 Line Webhook 請求簽名，確保請求安全。訊息處理後存入共用 Redis，支援跨伺服器高效存取。

### 2. Redis 訊息佇列處理 (`application/models/Message_model.php`)
```php
/**
 * 將來自 Line 的訊息加入共用 Redis 佇列，並發送即時通知
 * @param string $user_id 用戶 ID
 * @param string $message_type 訊息類型（text, sticker, etc.）
 * @param string $message_content 訊息內容
 */
public function add_line_message_to_redis($user_id, $message_type, $message_content): void {
    // 構建訊息資料結構
    $message_data = [
        'direction' => 'in', // 標記為接收訊息
        'user_id' => $user_id,
        'type' => $message_type,
        'content' => $message_content,
        'timestamp' => time()
    ];
    // 使用 RPUSH 將訊息加入共用 Redis 佇列尾部
    $this->redis->rpush(self::LINE_IN_QUEUE, json_encode($message_data));
    // 發佈通知，觸發其他伺服器的客服介面更新
    $this->redis->publish(self::NEW_MESSAGE_CHANNEL, 'new_line_message');
    // 記錄日誌，方便除錯
    log_message('info', 'Line message added to Redis queue for user: ' . $user_id);
}

/**
 * 從共用 Redis 佇列中提取新訊息，用於長輪詢
 * @param int $timeout 阻塞等待時間（秒）
 * @return array|null 訊息資料或 null（若超時）
 */
public function get_new_incoming_message_from_redis($timeout = 25): ?array {
    // 使用 BLPOP 阻塞式提取共用 Redis 佇列頭部訊息
    $result = $this->redis->blpop([self::LINE_IN_QUEUE], $timeout);
    if ($result && isset($result[1])) {
        // 記錄成功提取的日誌
        log_message('info', 'New incoming message retrieved from Redis.');
        // 解碼 JSON 訊息並返回
        return json_decode($result[1], true);
    }
    // 若超時無訊息，返回 null
    return null;
}
```
**說明**：此程式碼利用共用 Redis 管理訊息佇列，跨 Windows Server 共享資料，確保高效能和高併發處理。

### 3. 長輪詢實現 (`public/js/customer_service.js`)
```javascript
/**
 * 持續輪詢伺服器以獲取新訊息
 */
function pollMessages() {
    $.ajax({
        url: '<?php echo site_url("customer_service/poll_for_messages"); ?>', // 輪詢的 API 端點
        type: 'GET',
        dataType: 'json',
        cache: false, // 禁用快取，確保每次請求新數據
        timeout: 30000, // 設定 30 秒超時，適合長輪詢
        success: function(response) {
            if (response.status === 'success') {
                // 若有新訊息，顯示在控制台並調用顯示函數
                console.log('Received new message:', response.message);
                displayMessage(response.message);
            } else if (response.status === 'no_new_messages') {
                // 若無新訊息，記錄並繼續輪詢
                console.log('No new messages within timeout, re-polling...');
            }
            // 不論是否有新訊息，繼續下一次輪詢
            pollMessages();
        },
        error: function(xhr, status, error) {
            // 處理錯誤，記錄並在 5 秒後重試
            console.error('Long Polling Error:', status, error);
            setTimeout(pollMessages, 5000);
        }
    });
}

/**
 * 將新訊息顯示在客服介面
 * @param {Object} message 訊息資料
 */
function displayMessage(message) {
    // 根據訊息方向選擇樣式類（接收或發送）
    var messageClass = (message.direction === 'in') ? 'incoming' : 'outgoing';
    // 格式化時間戳
    var timestamp = new Date(message.timestamp * 1000).toLocaleString();
    // 構建訊息 HTML，支援不同訊息類型
    var messageHtml = `<li class="${messageClass}">
                            <div class="message-header">
                                <strong>${message.user_id}</strong>
                                <span class="timestamp">${timestamp}</span>
                            </div>
                            <div class="message-body">
                                ${message.type === 'text' ? message.content : `[${message.type} 訊息]: ${message.content}`}
                            </div>
                        </li>`;
    // 將新訊息插入訊息列表頂部
    $('#message_list').prepend(messageHtml);
}
```
**說明**：此 JavaScript 程式碼實現長輪詢，從共用 Redis 獲取訊息並顯示，支援跨伺服器環境下的即時更新。

## 技術細節與設計考量
- **跨 Windows Server 共用 Redis**：  
  透過單一 Redis 實例，Web Server、客服介面和 Worker 在不同 Windows Server 上共享訊息佇列和快取資料，解決單伺服器記憶體和 CPU 瓶頸，提升 100~200 人同時在線的效能。Redis 的高性能和原子操作確保資料一致性。
- **為何用 CodeIgniter**？  
  CodeIgniter 輕量、簡單，適合快速開發中小型專案，特別在 Windows Server 環境下部署便捷。
- **為何用 Redis 做佇列**？  
  Redis 的記憶體內處理和 `LPUSH`/`BLPOP` 原子操作，支援跨伺服器的高併發訊息處理，相比 MySQL 避免鎖定問題。
- **長輪詢的選擇**：  
  長輪詢實現簡單，與共用 Redis 結合，確保跨伺服器的即時訊息更新，適合 Windows Server 環境。
- **Webhook 簽名驗證**：  
  使用 HMAC-SHA256 和 `hash_equals()` 確保請求安全，保護跨伺服器通訊。
- **Reply API vs Push API**：  
  Push API 用於客服回覆，支援跨伺服器非同步處理，靈活性更高。

## 未來改進
- **支援多客服**：實現訊息分配和會話管理，存對話歷史至資料庫。
- **處理更多訊息類型**：擴展處理圖片、影片，儲存至雲端（如 AWS S3）。
- **升級即時技術**：用 WebSocket 取代長輪詢，提升效率。
- **監控系統**：整合 Windows Server 監控工具（如 Zabbix），監控 Redis 和 Worker 狀態。

## 貢獻
有任何建議或問題，歡迎提交 Issue 或 Pull Request 至 [GitHub 倉庫](https://github.com/BpsEason/LineLiveChat_CI.git)。

## 授權
本專案採用 MIT License，詳見 [LICENSE](https://github.com/BpsEason/LineLiveChat_CI/blob/master/LICENSE) 文件。
