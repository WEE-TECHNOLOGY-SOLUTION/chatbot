document.addEventListener('DOMContentLoaded', function () {
    const chatPopup = document.getElementById('chat-popup');
    const chatTrigger = document.getElementById('chatTrigger');
    const minimizeBtn = document.getElementById('minimizeChat');
    const closeBtn = document.getElementById('closeChat');
    const chatForm = document.getElementById('chatForm');
    const chatMessages = document.getElementById('chatMessages');
    const header = document.querySelector('.chat-header');

    // Chat popup toggle
    chatTrigger.addEventListener('click', () => {
        chatPopup.style.display = chatPopup.style.display === 'none' ? 'flex' : 'none';
        if (chatPopup.style.display === 'flex') {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    });

    // Minimize chat
    minimizeBtn.addEventListener('click', () => {
        chatPopup.classList.toggle('minimized');
    });

    // Close chat
    closeBtn.addEventListener('click', () => {
        chatPopup.style.display = 'none';
    });

    // Handle form submission
    chatForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const input = document.getElementById('messageInput');
        const message = input.value.trim();
        if (!message) return;

        // Add user message
        addMessage(message, 'user-message');
        input.value = '';

        // Show typing indicator
        document.getElementById('typingIndicator').style.display = 'block';

        try {
            const response = await fetch('chat-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ message })
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            addMessage(data.response, 'assistant-message');
        } catch (error) {
            console.error('Error:', error);
            addMessage('Sorry, something went wrong. Please try again.', 'assistant-message');
        }

        // Hide typing indicator
        document.getElementById('typingIndicator').style.display = 'none';
    });

    // Add message to chat
    function addMessage(message, className) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message ' + className;
        messageDiv.textContent = message;
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Make chat draggable
    let isDragging = false;
    let currentX;
    let currentY;
    let initialX;
    let initialY;
    let xOffset = 0;
    let yOffset = 0;

    header.addEventListener('mousedown', dragStart);
    document.addEventListener('mousemove', drag);
    document.addEventListener('mouseup', dragEnd);

    function dragStart(e) {
        initialX = e.clientX - xOffset;
        initialY = e.clientY - yOffset;

        if (e.target === header) {
            isDragging = true;
        }
    }

    function drag(e) {
        if (isDragging) {
            e.preventDefault();
            currentX = e.clientX - initialX;
            currentY = e.clientY - initialY;

            xOffset = currentX;
            yOffset = currentY;

            setTranslate(currentX, currentY, chatPopup);
        }
    }

    function setTranslate(xPos, yPos, el) {
        el.style.transform = `translate3d(${xPos}px, ${yPos}px, 0)`;
    }

    function dragEnd(e) {
        initialX = currentX;
        initialY = currentY;
        isDragging = false;
    }

    // Part 1: Auto-Refresh Chat
    function autoRefreshChat() {
        setInterval(async () => {
            try {
                const response = await fetch('chat-handler.php', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                // Check if the response is valid JSON
                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (error) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Invalid JSON response from server');
                }

                const chatMessages = document.getElementById('chatMessages');

                // Clear existing messages
                chatMessages.innerHTML = '';

                // Add updated messages
                if (data.chat_history && Array.isArray(data.chat_history)) {
                    data.chat_history.forEach(chat => {
                        addMessage(chat.user, 'user-message');
                        addMessage(chat.assistant, 'assistant-message');
                    });
                } else {
                    console.error('Invalid chat history format:', data);
                }
            } catch (error) {
                console.error('Error refreshing chat:', error);
            }
        }, 5000); // Refresh every 5 seconds
    }

    // Part 2: Automatically Delete Chat History
    function autoDeleteChatHistory() {
        let inactivityTimer;

        // Reset the timer on user interaction
        document.addEventListener('mousedown', resetTimer);
        document.addEventListener('keydown', resetTimer);

        function resetTimer() {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(deleteHistory, 10 * 60 * 1000); // 10 minutes
        }

        // Function to delete chat history
        async function deleteHistory() {
            try {
                const response = await fetch('chat-handler.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                // Clear the chat messages on the client side
                const chatMessages = document.getElementById('chatMessages');
                chatMessages.innerHTML = '';
            } catch (error) {
                console.error('Error deleting chat history:', error);
            }
        }

        // Start the timer
        resetTimer();
    }

    // Initialize auto-refresh and auto-delete
    autoRefreshChat();
    autoDeleteChatHistory();
});