$(document).ready(function() {
    // csrfName and csrfHash are passed from the PHP view
    // Example: <script>var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>'; var csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';</script>

    // Function to continuously poll for new messages
    function pollMessages() {
        $.ajax({
            url: '<?php echo site_url("customer_service/poll_for_messages"); ?>',
            type: 'GET',
            dataType: 'json',
            cache: false,
            timeout: 30000, // 30 seconds timeout for long polling request
            success: function(response) {
                if (response.status === 'success') {
                    console.log('Received new message:', response.message);
                    displayMessage(response.message);
                } else if (response.status === 'no_new_messages') {
                    console.log('No new messages within timeout, re-polling...');
                }
                // Always re-poll after a response or timeout
                pollMessages();
            },
            error: function(xhr, status, error) {
                console.error('Long Polling Error:', status, error);
                // Retry after a short delay on error
                setTimeout(pollMessages, 5000);
            }
        });
    }

    // Function to display messages in the UI
    function displayMessage(message) {
        var messageClass = (message.direction === 'in') ? 'incoming' : 'outgoing';
        var timestamp = new Date(message.timestamp * 1000).toLocaleString();

        var messageHtml = `<li class="${messageClass}">
                                <div class="message-header">
                                    <strong>${message.user_id}</strong>
                                    <span class="timestamp">${timestamp}</span>
                                </div>
                                <div class="message-body">
                                    ${message.type === 'text' ? message.content : `[${message.type} 訊息]: ${message.content}`}
                                </div>
                            </li>`;
        $('#message_list').prepend(messageHtml); // Add new messages to the top
    }

    // Start polling when the document is ready
    pollMessages();

    // Handle reply form submission
    $('#reply_form').submit(function(e) {
        e.preventDefault(); // Prevent default form submission

        var userId = $('#target_user_id').val();
        var replyText = $('#reply_text').val();

        if (!userId || !replyText) {
            alert('請填寫完整的用戶ID和回覆內容。');
            return;
        }

        // Prepare POST data including CSRF token
        var postData = {};
        postData['user_id'] = userId;
        postData['reply_text'] = replyText;
        postData[csrfName] = csrfHash; // Add CSRF token to the request

        $.ajax({
            url: '<?php echo site_url("customer_service/send_reply"); ?>',
            type: 'POST',
            data: postData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert('回覆已發送。');
                    $('#reply_text').val(''); // Clear the reply textarea
                } else {
                    alert('發送回覆失敗: ' + response.message);
                }
                // Update CSRF token if returned by the server (important for subsequent requests)
                if (response.csrf_token_name && response.csrf_hash) {
                    csrfName = response.csrf_token_name;
                    csrfHash = response.csrf_hash;
                }
            },
            error: function(xhr, status, error) {
                console.error('Error sending reply:', status, error);
                alert('發送回覆時發生錯誤，請稍後再試。');
            }
        });
    });
});
