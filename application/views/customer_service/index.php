<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Line 客服系統</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="<?php echo base_url('public/css/style.css'); ?>">
</head>
<body>
    <div class="container">
        <h1>Line 客服中心</h1>

        <div class="message-area">
            <h2>最新訊息</h2>
            <ul id="message_list">
                <li><div class="message-header"><strong>系統</strong> <span class="timestamp"><?php echo date('Y-m-d H:i:s'); ?></span></div><div class="message-body">等待來自 Line 的新訊息...</div></li>
            </ul>
        </div>

        <div class="reply-area">
            <h2>發送回覆</h2>
            <?php echo form_open('', ['id' => 'reply_form']); ?>
                <label for="target_user_id">目標 Line 用戶 ID:</label>
                <input type="text" id="target_user_id" name="user_id" required placeholder="請輸入 Line 用戶 ID">

                <label for="reply_text">回覆內容:</label>
                <textarea id="reply_text" name="reply_text" rows="4" required placeholder="輸入您的回覆..."></textarea>

                <button type="submit">發送回覆</button>
            <?php echo form_close(); ?>
        </div>
    </div>

    <script>
        // Pass CSRF token to JavaScript
        var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
        var csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
    </script>
    <script src="<?php echo base_url('public/js/customer_service.js'); ?>"></script>
</body>
</html>
