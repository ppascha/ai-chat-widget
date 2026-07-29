/*
 * Purpose: Browser-side widget runtime for chat interactions and product card rendering.
 * Highlights:
 * - Sends user turns to the REST chat endpoint.
 * - Persists conversation ID in localStorage for multi-turn continuity.
 */

document.addEventListener(
    'DOMContentLoaded',
    function(){

        const chat = document.getElementById('aicw-chat');
        const toggle = document.getElementById('aicw-toggle');
        const close = document.getElementById('aicw-close');

        const send = document.getElementById('aicw-send');
        const input = document.getElementById('aicw-message');
        const messages = document.getElementById('aicw-messages');

        // Guard against partial template renders; avoid runtime errors when widget markup is absent.
        if (!chat || !toggle || !close || !send || !input || !messages) {
            return;
        }

        // Keep fallback endpoint for environments where localized config is unavailable.
        const fallbackUrl = '/wp-json/ai-chatbot/v1/message';
        const restUrl =
            window.AICW_Config && window.AICW_Config.restUrl
                ? window.AICW_Config.restUrl
                : fallbackUrl;

        // Browser-owned conversation key lets backend restore prior history storage for the same user tab/profile.
        const storageKey = 'aicw_conversation_id';
        let conversationId = window.localStorage.getItem(storageKey);

        const sentHistory = [];
        let historyCursor = -1;

        if (!conversationId) {
            // Prefer Web Crypto UUID when available; fallback remains deterministic enough for local demos.
            conversationId = (window.crypto && window.crypto.randomUUID)
                ? window.crypto.randomUUID()
                : 'aicw-' + String(Date.now()) + '-' + String(Math.random()).slice(2);

            window.localStorage.setItem(storageKey, conversationId);
        }


        toggle.addEventListener(
            'click',
            function(){

                chat.classList.remove('closed');

                // Focus input on open so user can type immediately without an extra click.
                input.focus();

            }
        );


        close.addEventListener(
            'click',
            function(){

                chat.classList.add('closed');

            }
        );


        const appendBotMessage = function(text) {
            const botMessage = document.createElement('div');
            botMessage.className = 'aicw-bot-message';
            botMessage.textContent = text;
            messages.appendChild(botMessage);
            messages.scrollTop = messages.scrollHeight;
        };

        const appendProductIframe = function(product) {
            const wrapper = document.createElement('div');
            wrapper.className = 'aicw-product-card';

            const productUrl = typeof product.productUrl === 'string'
                ? product.productUrl
                : '';

            const title = document.createElement(productUrl ? 'a' : 'div');
            title.className = 'aicw-product-title';
            title.textContent = product.title;

            // Use canonical product URL when available so chat and storefront share one destination route.
            if (productUrl) {
                title.href = productUrl;
                title.target = '_top';
                title.rel = 'noopener noreferrer';
            }

            const summary = document.createElement('div');
            summary.className = 'aicw-product-summary';
            summary.textContent = product.summary;

            const iframe = document.createElement('iframe');
            iframe.className = 'aicw-product-iframe';
            iframe.title = product.title;
            iframe.loading = 'lazy';
            iframe.src = product.iframeUrl;

            const action = document.createElement('a');
            action.className = 'aicw-product-link';
            action.textContent = 'Open product page';
            action.href = productUrl || product.iframeUrl;
            action.target = '_top';
            action.rel = 'noopener noreferrer';

            wrapper.appendChild(title);
            wrapper.appendChild(summary);
            wrapper.appendChild(iframe);
            wrapper.appendChild(action);
            messages.appendChild(wrapper);
            messages.scrollTop = messages.scrollHeight;
        };

        const sendMessage = function(){

            const text = input.value.trim();

            // Skip no-op turns and keep UI/REST traffic clean.
            if(text === ''){
                return;
            }

            const userMessage = document.createElement('div');
            userMessage.className = 'aicw-user-message';
            userMessage.textContent = text;
            messages.appendChild(userMessage);

            // Store sent messages so ArrowUp can recall recent prompts for quick resubmission.
            sentHistory.push(text);
            historyCursor = sentHistory.length;

            input.value='';
            messages.scrollTop = messages.scrollHeight;

            const headers = {
                'Content-Type': 'application/json'
            };

            // Pass nonce when available so future authenticated routes can be locked down without JS changes.
            if (window.AICW_Config && window.AICW_Config.nonce) {
                headers['X-WP-Nonce'] = window.AICW_Config.nonce;
            }

            // Single POST endpoint drives chat-loop orchestration; product routes are separate support APIs.
            fetch(restUrl, {
                method: 'POST',
                headers: headers,
                body: JSON.stringify({
                    message: text,
                    conversation_id: conversationId
                })
            })
            .then(async response => {
                // Parse JSON defensively so malformed/non-JSON server responses still produce useful errors.
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    // Prefer server-provided message to expose real backend failures (quota, tool, transport, etc).
                    const errorMessage = data && data.message
                        ? data.message
                        : 'Could not get response from server.';
                    throw new Error(errorMessage);
                }

                return data;
            })
            .then(data => {
                // Server can return different payload kinds; default to text for forward compatibility.
                const kind = data && typeof data.kind === 'string'
                    ? data.kind
                    : 'text';

                const textFromResponse =
                    data && typeof data.message === 'string'
                        ? data.message
                        : data && data.data && typeof data.data.message === 'string'
                            ? data.data.message
                            : 'No response received from AI.';

                appendBotMessage(textFromResponse);

                // Product payloads are rendered as iframe cards below the assistant text reply.
                if (kind === 'products' && Array.isArray(data.products)) {
                    data.products.forEach(appendProductIframe);
                }
            })
            .catch(error => {
                appendBotMessage('Error: ' + (error.message || 'Could not get response.'));
            });
        };

        send.addEventListener('click', sendMessage);

        input.addEventListener('keydown', function(event) {
            if (event.key === 'ArrowUp') {
                // Recall previous user message into input so Enter can resend/edit quickly.
                if (sentHistory.length === 0) {
                    return;
                }

                event.preventDefault();

                historyCursor = Math.max(0, historyCursor - 1);
                input.value = sentHistory[historyCursor] || '';
                return;
            }

            if (event.key === 'ArrowDown') {
                // Navigate forward in history and clear input when cursor passes the newest item.
                if (sentHistory.length === 0) {
                    return;
                }

                event.preventDefault();

                historyCursor = Math.min(sentHistory.length, historyCursor + 1);
                input.value = historyCursor < sentHistory.length
                    ? (sentHistory[historyCursor] || '')
                    : '';
                return;
            }

            if (event.key === 'Enter') {
                event.preventDefault();
                sendMessage();
            }
        });

        // If widget starts visible, focus immediately so first keystroke lands in the input.
        if (!chat.classList.contains('closed')) {
            input.focus();
        }


    }
);