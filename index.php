<?php
session_start();
require_once 'chat-handler.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant - Wee Technology Solutions</title>
    <!-- Include Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Import Roboto Font from Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
    <!-- CSS styles will be here -->
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Add your CSS styles here */
        #chat-popup {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 400px;
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 5px;
            overflow: hidden;
            z-index: 9999;
        }
        .minimized #chatMessages {
            display: none;
        }
        /* Add more styles as needed */
    </style>
</head>
<body>
    <!-- Chat Popup Container -->
    <div id="chat-popup" class="chat-popup">
        <div class="chat-header">
            <h3>AI Assistant</h3>
            <div class="chat-controls">
                <button id="minimizeChat" class="control-btn"><i class="fas fa-minus"></i></button>
                <button id="closeChat" class="control-btn"><i class="fas fa-times"></i></button>
            </div>
        </div>
        <div class="chat-messages" id="chatMessages">
            <?php foreach ($_SESSION['chat_history'] ?? [] as $chat): ?>
                <div class="message user-message"><?php echo htmlspecialchars($chat['user']); ?></div>
                <div class="message assistant-message"><?php echo nl2br(htmlspecialchars($chat['assistant'])); ?></div>
            <?php endforeach; ?>
        </div>
        <div class="typing-indicator" id="typingIndicator">AI is thinking...</div>
        <form class="chat-input" id="chatForm">
            <input type="text" id="messageInput" name="message" placeholder="Type your message here..." required>
            <button type="submit">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>

    <!-- Chat Trigger Button -->
    <button id="chatTrigger" class="chat-trigger">
        <i class="fas fa-comment"></i>
    </button>

    <!-- JavaScript will be here -->
    <script src="script.js"></script>
</body>
</html>