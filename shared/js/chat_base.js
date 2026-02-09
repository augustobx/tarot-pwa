// Shared Chat Base Functions for All Modules
// Common functionality used across Tarot, Astrology, and Numerology modules

// Global chat endpoint (set by each module)
window.CHAT_API_ENDPOINT = window.CHAT_API_ENDPOINT || 'api/chat.php';
window.MODULE_NAME = window.MODULE_NAME || 'tarot';

// Append message to chat container
function appendMessage(sender, text, container, skipScroll = false) {
    const msgDiv = document.createElement('div');
    msgDiv.className = `message ${sender}`;

    const bubble = document.createElement('div');
    bubble.className = 'bubble';

    // Handle HTML content (for buttons, etc.)
    if (text.includes('<button')) {
        bubble.innerHTML = text;
    } else {
        // Convert markdown-style bold to HTML
        const formattedText = text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
        bubble.innerHTML = formattedText;
    }

    msgDiv.appendChild(bubble);
    container.appendChild(msgDiv);

    if (!skipScroll) {
        container.scrollTop = container.scrollHeight;
    }
}

// Show typing indicator
function showTypingIndicator(container) {
    const typing = document.createElement('div');
    typing.className = 'message ai typing-indicator';
    typing.id = 'typing-indicator';
    typing.innerHTML = '<div class="bubble">Consultando los astros<span class="dots"></span></div>';
    container.appendChild(typing);
    container.scrollTop = container.scrollHeight;
}

// Remove typing indicator
function removeTypingIndicator() {
    const typing = document.getElementById('typing-indicator');
    if (typing) typing.remove();
}

// Send message to API
async function sendMessage(message, imagePath = null) {
    const container = document.getElementById('chat');

    if (!message && !imagePath) return;

    // Append user message
    if (message) {
        appendMessage('user', message, container);
    }

    showTypingIndicator(container);

    try {
        const payload = { message: message || '' };
        if (imagePath) payload.image = imagePath;

        const response = await fetch(window.CHAT_API_ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        removeTypingIndicator();

        if (data.error) {
            appendMessage('ai', `Error: ${data.error}`, container);
            return;
        }

        // Handle tarot cards if present
        if (data.cards && typeof renderTarotCards === 'function') {
            renderTarotCards(container, data.cards, data.response);
        } else {
            appendMessage('ai', data.response, container);
        }

        // Update question counter if available
        if (data.remaining_questions !== undefined) {
            updateQuestionCounter(data.remaining_questions);
        }

    } catch (error) {
        removeTypingIndicator();
        appendMessage('ai', `Error de conexión: ${error.message}`, container);
    }
}

// Update question counter display
function updateQuestionCounter(count) {
    const el = document.getElementById('questions-left');
    if (el) el.textContent = count;
}

// Open payment modal (if exists)
window.openPaymentModal = function () {
    const modal = document.getElementById('payment-modal');
    if (modal) modal.style.display = 'block';
};

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { appendMessage, sendMessage, showTypingIndicator, removeTypingIndicator };
}
