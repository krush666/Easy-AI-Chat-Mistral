document.addEventListener('DOMContentLoaded', function() {
    var chatbotContainer = document.getElementById('easy-ai-chat');
    if (chatbotContainer) {
        // Replace the following line with the actual script to embed the AI chatbot
        chatbotContainer.innerHTML = '<script src="' + easyAIChatSettings.plugin_url + '/assets/js/aiscript.html"><\/script>';
    }

    // Get the API key from settings
    var apiKey = easyAIChatSettings.api_key;

    // Apply button color from settings
    var sendButton = document.getElementById('sendButton');
    if (sendButton) {
        sendButton.style.backgroundColor = easyAIChatSettings.button_color;
    }

    // Function to send message
    async function sendMessage() {
        const userText = userInput.value.trim();
        if (!userText) return;

        // Add user message to chat
        addMessage(userText, true);
        userInput.value = '';

        // Add loading message
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'message ai-message loading';
        loadingDiv.textContent = 'Thinking...';
        chatBox.appendChild(loadingDiv);

        try {
            // Prepare headers
            const headers = {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${apiKey}`
            };

            const response = await fetch('/wp-json/mistral-chat/v1/query', {
                method: 'POST',
                headers: headers,
                body: JSON.stringify({
                    message: userText
                })
            });

            const data = await response.json();
            
            // Remove loading message
            chatBox.removeChild(loadingDiv);

            // Add AI response to chat
            if (data.candidates && data.candidates[0].content.parts[0].text) {
                addMessage(data.candidates[0].content.parts[0].text, false);
            } else {
                addMessage('Sorry, I could not generate a response.', false);
            }
        } catch (error) {
            console.error('Error:', error);
            chatBox.removeChild(loadingDiv);
            addMessage('An error occurred while processing your request.', false);
        }
    }

    // Event listeners
    sendButton.addEventListener('click', sendMessage);
    userInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
});
