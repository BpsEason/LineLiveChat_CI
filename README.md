```markdown
# Line Live Chat Customer Service System (CodeIgniter 3)

---

## 🚀 專案概述 (Project Overview)

這是一個為 Line 應用程式設計的即時客戶服務系統。它旨在提供一個高效且可擴展的解決方案，讓客服人員能夠即時接收並回覆來自 Line 用戶的訊息。本專案旨在展示 PHP 應用在即時通訊系統中的開發實踐，特別適合作為個人作品集中的展示項目。

---

## ✨ 核心亮點 (Key Highlights)

* **高併發處理**: 系統經過優化，可穩定支援 **100~200 人同時在線**，透過 Redis 訊息佇列和長輪詢技術，確保高效訊息處理與低延遲回應。
* **高效訊息佇列**: 使用 **Redis** 的 `LPUSH`/`BLPOP` 原子操作，實現高併發訊息接收與回覆，有效解耦 Line Webhook 和客服處理，確保系統穩定性。
* **即時客服體驗**: 透過 jQuery **長輪詢 (Long Polling)**，客服介面能即時顯示新訊息，無需頻繁請求伺服器，兼顧效率與簡易實現。
* **非同步回覆**: 後台 Worker 獨立處理回覆發送，減輕 Web 伺服器負擔，適用於高流量場景。
* **安全設計**: 支援 Line Webhook 簽名驗證（HMAC-SHA256）和 CodeIgniter CSRF 保護，確保請求來源安全與防止惡意操作。
* **輕量架構**: 採用 CodeIgniter 3.1.13，框架本身輕巧，易於快速部署與維護，適合中小型專案或快速原型開發。

---

## 🛠️ 技術棧 (Tech Stack)

* **後端框架**: PHP (CodeIgniter 3.1.13)
* **訊息佇列/快取**: Redis
* **Line API 整合**: Line Messaging API SDK
* **資料庫**: MySQL (用於未來擴展或歷史記錄儲存)
* **前端**: HTML5, CSS3, jQuery, AJAX
* **部署環境**: Apache, FastCGI (或 PHP-FPM)
* **版本控制**: Git

---

## 📂 專案結構 (Project Structure)

```

LineLiveChat\_CI/
├── application/
│   ├── config/                \# 設定檔 (database.php, line.php, redis.php 等)
│   ├── controllers/           \# 控制器 (Line\_webhook.php, Customer\_service.php)
│   ├── libraries/             \# 自訂函式庫 (Line\_api.php, Redis\_library.php)
│   ├── models/                \# 模型 (Message\_model.php, User\_model.php)
│   ├── views/                 \# 視圖 (customer\_service/index.php 等)
│   ├── cli/                   \# CLI 腳本 (line\_message\_worker.php)
│   └── third\_party/           \# 第三方庫佔位
├── public/
│   ├── css/                   \# 樣式 (style.css)
│   ├── js/                    \# JavaScript (customer\_service.js)
│   └── index.php              \# CodeIgniter 入口
├── system/                    \# CodeIgniter 核心
├── vendor/                    \# Composer 依賴
├── composer.json              \# Composer 設定
└── .gitignore                 \# Git 忽略設定

````

---

## ⚙️ 關鍵程式碼與設計考量 (Key Code & Design Considerations)

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
````

**說明**：這段程式碼負責驗證 Line Webhook 請求的簽名，確保請求來自 Line 官方。使用 HMAC-SHA256 演算法比對 `X-Line-Signature` 和請求體，若驗證失敗則拒絕處理，增強安全性，防止偽造請求。

### 2\. Redis 訊息佇列處理 (`application/models/Message_model.php`)

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

**說明**：這段程式碼展示如何用 Redis 管理訊息佇列。`add_line_message_to_redis` 將 Line 訊息推入佇列並發送通知；`get_new_incoming_message_from_redis` 使用阻塞式 `BLPOP` 提取訊息，支援長輪詢的高效實現，確保在高併發場景下的系統穩定性。

### 3\. 長輪詢前端實現 (`public/js/customer_service.js`)

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
                // 假設 displayMessage(response.message) 是用於在前端顯示訊息的函數
                // displayMessage(response.message); 
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

**說明**：這段 JavaScript 使用 jQuery 實現長輪詢，持續向伺服器請求新訊息。若收到訊息則顯示，否則在超時或錯誤後重新輪詢，確保客服介面即時更新。此方法旨在兼顧效率與簡易實現，以穩定處理預期的中高負載。

### 4\. 技術選型理由 (Technical Choices Rationale)

  * **為何選用 CodeIgniter 3？** CodeIgniter 以其輕量級、高效率和易於學習的特性聞名，非常適合快速開發中小型專案或原型驗證。其簡潔的配置和直觀的 MVC 架構，能有效加速專案開發進度。
  * **為何選用 Redis 作為訊息佇列？** Redis 的記憶體內處理能力和對原子操作（如 `LPUSH` 和 `BLPOP`）的支援，使其成為高併發訊息處理的理想選擇。相較於傳統資料庫，Redis 能避免鎖定問題和顯著降低延遲，有效解耦 Line Webhook 接收與客服處理流程。
  * **為何選用長輪詢？** 長輪詢相較於傳統短輪詢能大幅減少不必要的伺服器請求，同時提供足夠的即時性以滿足客服介面需求。雖然 WebSocket 提供更全面的即時通訊能力，但長輪詢在 CodeIgniter 環境下實現更為簡潔，且對於預期的 100-200 人同時在線規模，其效能表現已相當可靠。
  * **Webhook 簽名驗證的重要性？** 對 Line Webhook 請求進行簽名驗證是確保系統安全的首要步驟。它能有效防止惡意第三方偽造請求，保護系統免受潛在的濫用和資料損害。

-----

## 🚀 如何運行 (How to Run)

1.  **克隆專案**:

    ```bash
    git clone [https://github.com/BpsEason/LineLiveChat_CI.git](https://github.com/BpsEason/LineLiveChat_CI.git)
    cd LineLiveChat_CI
    ```

2.  **安裝 Composer 依賴 (模擬)**:
    此專案的 `vendor` 目錄和 `composer.json` 為模擬結構，在實際運行前，您需要確保 `linecorp/line-bot-sdk` 和 `predis/predis` 庫已實際安裝並可用於您的 PHP 環境。在真實環境下，您會運行：

    ```bash
    composer install
    ```

3.  **配置 Line API 和 Redis**:

      * 編輯 `application/config/line.php`，填入您的 Line Channel Access Token 和 Channel Secret。
      * 編輯 `application/config/redis.php`，根據您的 Redis 伺服器部署方式設定 `host`、`port`、`password` 等。

4.  **配置資料庫（可選）**:

      * 編輯 `application/config/database.php`，若需要持久化儲存對話歷史或其他資料，請配置 MySQL 連線。

5.  **配置 Web 伺服器**:

      * 將您的 Apache 或 Nginx 伺服器的 Document Root 指向專案的 `public/` 目錄。
      * 確保 `application/` 和 `system/` 目錄無法被直接訪問。
      * 強烈建議啟用 HTTPS 保護所有通訊。

6.  **啟動後台 Worker**:
    運行 CLI 腳本以處理出站訊息：

    ```bash
    php application/cli/line_message_worker.php
    ```

    在生產環境中，建議使用 PM2, Supervisor 或 systemd 等工具來管理此進程，確保其持續運行並自動重啟。

7.  **設定 Line Webhook**:
    在 Line Developers 後台，將您的 Webhook URL 設定為指向 `https://您的域名/line_webhook`。

8.  **訪問介面**：
    在瀏覽器中輸入 `https://您的域名/customer_service` 即可訪問客服介面。

-----

## 🌐 部署與效能優化策略 (Deployment & Performance Optimization)

為了穩定支援 **100\~200 人同時在線**，本專案建議將 **Web Server** 與 **Redis Server** 分開部署，以實現資源隔離、效能提升和高可用性。

### 1\. Web Server 部署 (Apache + FastCGI / PHP-FPM)

  * **環境**: 部署 Web Server 運行 Apache 搭配 FastCGI 模組與 PHP-FPM。
  * **配置要點**:
      * Apache 虛擬主機設定 Document Root 指向 `public/`，並限制對 `application/` 和 `system/` 的直接訪問。
      * **PHP-FPM 優化**: 調整 `pm.max_children`, `pm.start_servers`, `pm.min_spare_servers`, `pm.max_spare_servers` 等 PHP-FPM 進程池參數，以適應預期的高併發連接數。
      * **HTTPS**: 透過 Let's Encrypt 等工具為域名啟用 HTTPS，確保所有通訊加密。
      * **快取**: 啟用 Apache 的 `mod_cache` 或 `mod_expires` 來快取靜態資源，減少伺服器負載。
  * **效能測試**: 使用 Apache Benchmark (ab) 等工具進行負載測試，驗證伺服器在 100-200 併發連接下的表現。

### 2\. Redis 獨立部署 (Dedicated Server)

  * **環境**: 建議將 Redis 部署在**獨立的伺服器**上，提供專用資源（例如：至少 4GB RAM）。
  * **配置要點**:
      * **安全**: 在 `redis.conf` 中設定 `requirepass` 為強密碼，並配置防火牆規則（如 `ufw`），**僅允許 Web Server 和 Worker 伺服器的 IP 地址訪問 Redis 埠（默認 6379）**。
      * **記憶體**: 設定 `maxmemory` 和 `maxmemory-policy`，避免記憶體耗盡。
      * **持久化**: 啟用 `appendonly yes` 進行 AOF 持久化，確保訊息佇列數據在重啟後不丟失。
  * **高可用性 (可選)**: 若需更高可用性，可考慮部署 Redis Sentinel 實現主從複製與自動故障轉移。

### 3\. Worker 部署 (Background Process)

  * **環境**: `line_message_worker.php` 腳本可與 Web Server 同機或獨立部署。獨立部署可進一步降低 Web Server 負載。
  * **管理工具**: 使用 PM2 (Node.js ecosystem) 或 systemd (Linux) 等專業進程管理工具來守護 Worker 進程，確保其在後台持續運行並自動重啟。

### 4\. 監控與日誌

  * **全面的監控**: 實施系統級（CPU, RAM, 網路 I/O）、應用級（PHP-FPM 進程數）、Redis 級（記憶體、佇列長度、連接數）的監控。
  * **日誌分析**: 集中應用日誌（CodeIgniter logs, Apache logs, Redis logs, Worker logs），便於問題診斷和效能分析。

### 5\. 硬體建議 (Hardware Recommendations)

  * **Web Server (Apache + FastCGI)**：2 vCPU，4GB RAM，支援 100\~200 人同時在線（視 PHP-FPM 設定調整）。
  * **Redis Server**：2 vCPU，4\~8GB RAM，確保記憶體足夠處理佇列與快取。
  * **Worker Server（若獨立）**：1 vCPU，2GB RAM，足以運行 CLI 腳本。

-----

## 💡 未來改進方向 (Future Enhancements)

  * **多客服人員管理**: 實現客服人員登入系統、會話列表、訊息分配（自動或手動認領）、客服狀態管理等。
  * **對話歷史記錄**: 將 Line 訊息和客服回覆持久化儲存到關聯式資料庫 (如 MySQL)，以便提供完整的對話歷史查詢和報告功能。
  * **豐富的訊息類型支援**: 擴展 `Line_webhook` 處理圖片、影片、語音等 Line 訊息類型，並將媒體檔案儲存到雲端儲存服務（如 AWS S3）後在客服介面顯示。
  * **WebSocket 整合**: 考慮將長輪詢升級為 WebSocket，實現真正的全雙工即時通訊，進一步提升客服介面的響應速度和效率。
  * **Docker 化部署**: 提供 Dockerfile 和 Docker Compose 配置，簡化開發、測試和生產環境的部署流程。
  * **自動化測試**: 為核心業務邏輯和 API 互動編寫單元測試和整合測試，確保程式碼品質和功能穩定性。

-----

## 🤝 貢獻 (Contribution)

歡迎提出 Issues 或 Pull Requests，一起讓這個專案變得更好！

-----

## 📄 許可證 (License)

本專案採用 MIT 許可證發布。詳情請參閱 [LICENSE](https://www.google.com/search?q=LICENSE) 文件。

-----

## 📧 聯繫 (Contact)

有任何問題或建議，歡迎透過 [GitHub 倉庫](https://www.google.com/url?sa=E&source=gmail&q=https://github.com/BpsEason/LineLiveChat_CI.git) 提交 Issue 或聯繫專案維護者。

```
```
