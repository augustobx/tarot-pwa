document.addEventListener('DOMContentLoaded', () => {
    const messageInput = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-btn');
    const chatWindow = document.getElementById('chat-window');
    const uploadBtn = document.getElementById('upload-btn');
    const imageInput = document.getElementById('image-input');
    const previewContainer = document.getElementById('image-preview-container');
    const imagePreview = document.getElementById('image-preview');
    const removeImageBtn = document.getElementById('remove-image');
    const addBalanceBtn = document.getElementById('add-balance-btn');

    let currentImage = null; // Base64 string

    // Auto-resize textarea
    messageInput.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
        if (this.value === '') this.style.height = 'auto';
    });

    // Handle Image Selection
    uploadBtn.addEventListener('click', () => imageInput.click());

    imageInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (evt) {
                currentImage = evt.target.result;
                imagePreview.src = currentImage;
                previewContainer.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    });

    removeImageBtn.addEventListener('click', () => {
        currentImage = null;
        imageInput.value = '';
        previewContainer.classList.add('hidden');
    });

    // Send Message
    async function sendMessage() {
        const text = messageInput.value.trim();
        if (!text && !currentImage) return;

        // Add User Message to UI
        appendMessage('user', text, currentImage);

        // Clear Input
        messageInput.value = '';
        messageInput.style.height = 'auto';
        const imageToSend = currentImage; // Keep ref
        currentImage = null;
        imageInput.value = '';
        previewContainer.classList.add('hidden');

        // Show loading indicator
        const loadingId = appendLoading();

        try {
            const response = await fetch('api/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text, image: imageToSend })
            });
            const data = await response.json();

            removeMessage(loadingId);

            // Check if tarot cards were drawn
            if (data.cards && Array.isArray(data.cards)) {
                renderTarotSpread(data.cards, data.response);
            } else if (data.response) {
                appendMessage('ai', data.response);
            } else if (data.error) {
                appendMessage('system', 'Error: ' + data.error);
            }
        } catch (error) {
            console.error('Fetch error:', error);
            removeMessage(loadingId);
            appendMessage('system', 'Error de conexión con el oráculo. Detalles: ' + error.message);
        }
    }

    sendBtn.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    function appendMessage(sender, text, image = null) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `message ${sender}`;

        let content = '';
        if (image) {
            content += `<img src="${image}" style="max-width: 100%; border-radius: 10px; margin-bottom: 5px; display: block;">`;
        }

        // Simple Markdown bold support
        let formattedText = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        formattedText = formattedText.replace(/\n/g, '<br>');

        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        bubble.innerHTML = content;

        // Agregar span para el texto con typewriter effect si es AI
        const textSpan = document.createElement('span');
        textSpan.className = sender === 'ai' ? 'typewriter-text' : '';
        bubble.appendChild(textSpan);

        msgDiv.appendChild(bubble);

        // Botón compartir solo para mensajes AI (solo ícono)
        if (sender === 'ai') {
            const shareBtn = document.createElement('button');
            shareBtn.className = 'share-btn';
            shareBtn.innerHTML = '<i class="fa-solid fa-share-nodes"></i>';
            shareBtn.title = 'Compartir lectura'; // Tooltip
            shareBtn.onclick = () => shareReading(text);
            msgDiv.appendChild(shareBtn);
        }

        chatWindow.appendChild(msgDiv);
        chatWindow.scrollTop = chatWindow.scrollHeight;

        // Efecto typewriter para mensajes AI
        if (sender === 'ai') {
            typewriterEffect(textSpan, formattedText);
        } else {
            textSpan.innerHTML = formattedText;
        }
    }

    // Efecto de escritura mágica
    function typewriterEffect(element, text) {
        element.innerHTML = ''; // Limpiar
        const isHTML = /<[^>]+>/.test(text);

        // Si tiene HTML, separar en partes
        const parts = isHTML ? text.split(/(<[^>]+>)/) : [text];
        let currentPart = 0;
        let currentChar = 0;

        // Agregar cursor parpadeante
        const cursor = document.createElement('span');
        cursor.className = 'typing-cursor';
        cursor.textContent = '|';
        element.appendChild(cursor);

        function type() {
            if (currentPart >= parts.length) {
                cursor.remove(); // Quitar cursor al terminar
                return;
            }

            const part = parts[currentPart];

            // Si es una etiqueta HTML, agregar completa
            if (part.startsWith('<')) {
                const temp = document.createElement('div');
                temp.innerHTML = element.innerHTML.replace(cursor.outerHTML, '') + part;
                element.innerHTML = temp.innerHTML;
                element.appendChild(cursor);
                currentPart++;
                currentChar = 0;
                setTimeout(type, 10);
                return;
            }

            // Escribir carácter por carácter
            if (currentChar < part.length) {
                const temp = document.createElement('div');
                temp.innerHTML = element.innerHTML.replace(cursor.outerHTML, '') + part[currentChar];
                element.innerHTML = temp.innerHTML;
                element.appendChild(cursor);
                currentChar++;

                // Velocidad variable para más realismo
                const speed = part[currentChar - 1] === ' ' ? 30 : (Math.random() * 40 + 20);
                setTimeout(type, speed);
            } else {
                currentPart++;
                currentChar = 0;
                setTimeout(type, 10);
            }
        }

        type();
    }

    // Función para compartir lectura
    window.shareReading = function (aiResponse) {
        // Obtener la última pregunta del usuario
        const userMessages = chatWindow.querySelectorAll('.message.user');
        const lastUserMessage = userMessages[userMessages.length - 1];
        const userQuestion = lastUserMessage ? lastUserMessage.textContent.trim() : 'Mi consulta';

        // Limpiar HTML del response
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = aiResponse;
        const cleanResponse = tempDiv.textContent || tempDiv.innerText;

        // Determinar tipo de lectura por palabras clave
        let tipo = 'general';
        const lowerQ = userQuestion.toLowerCase();
        if (lowerQ.includes('amor') || lowerQ.includes('relacion') || lowerQ.includes('pareja')) {
            tipo = 'amor';
        } else if (lowerQ.includes('dinero') || lowerQ.includes('econom') || lowerQ.includes('financ')) {
            tipo = 'dinero';
        } else if (lowerQ.includes('trabajo') || lowerQ.includes('empleo') || lowerQ.includes('carrer')) {
            tipo = 'trabajo';
        }

        // URL de la imagen generada
        const imageUrl = `api/generate_share_image.php?q=${encodeURIComponent(userQuestion)}&r=${encodeURIComponent(cleanResponse.substring(0, 300))}&t=${tipo}`;

        // Abrir modal de compartir
        openShareModal(imageUrl, userQuestion, cleanResponse);
    };


    function appendLoading() {
        const id = 'loading-' + Date.now();
        const msgDiv = document.createElement('div');
        msgDiv.className = `message ai`;
        msgDiv.id = id;
        msgDiv.innerHTML = `<div class="bubble"><i class="fa-solid fa-spinner fa-spin"></i> Consultando a los astros...</div>`;
        chatWindow.appendChild(msgDiv);
        chatWindow.scrollTop = chatWindow.scrollHeight;
        return id;
    }

    function removeMessage(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    // Questions Counter Update
    async function updateQuestionsCounter() {
        try {
            const res = await fetch('api/get_questions.php');
            const data = await res.json();

            const counter = document.getElementById('questions-counter');
            if (!data.is_guest && data.preguntas_restantes !== null) {
                counter.innerHTML = `
                    <i class="fa-solid fa-circle-question"></i> 
                    <strong>${data.preguntas_restantes}</strong> restantes 
                    <small style="opacity: 0.7;">(${data.preguntas_realizadas} realizadas)</small>
                `;
                counter.style.color = data.preguntas_restantes > 5 ? '#4ade80' : '#fbbf24';
                if (data.preguntas_restantes === 0) {
                    counter.style.color = '#f87171';
                }
            } else {
                counter.innerHTML = `
                    <i class="fa-solid fa-user"></i> 
                    Invitado - <small>Regístrate para consultas</small>
                `;
                counter.style.color = '#a855f7';
            }
        } catch (err) {
            console.error('Error updating counter:', err);
            document.getElementById('questions-counter').innerHTML =
                '<i class="fa-solid fa-exclamation-triangle"></i> Error';
        }
    }

    // Update counter on load
    updateQuestionsCounter();

    // Override sendMessage to update counter after response
    const originalSendMessage = sendMessage;
    sendMessage = async function () {
        await originalSendMessage.call(this);
        setTimeout(updateQuestionsCounter, 1000);
    };

    // ===== PREMIUM PAYMENT MODAL =====

    let packs = []; // Will be loaded dynamically
    let selectedPack = null;

    // Inject modal HTML
    const modalHTML = `
        <div id="payment-modal" class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>🌙 Comprar Preguntas</h2>
                    <p>Elige el pack que mejor se adapte a ti</p>
                </div>
                <div class="pack-grid" id="pack-grid">
                    <div style="text-align: center; padding: 20px; color: #bca0d9;">
                        <i class="fa-solid fa-spinner fa-spin"></i> Cargando packs...
                    </div>
                </div>
                <div class="modal-actions">
                    <button class="modal-btn secondary" onclick="closePaymentModal()">Cancelar</button>
                    <button class="modal-btn primary" id="continue-payment-btn" onclick="processPurchase()">Continuar a Pago</button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Load packs from API
    async function loadPacks() {
        try {
            const res = await fetch('api/get_packs.php');
            const data = await res.json();
            if (data.packs) {
                packs = data.packs;
                return true;
            }
            return false;
        } catch (err) {
            console.error('Error loading packs:', err);
            return false;
        }
    }

    window.openPaymentModal = async function () {
        const modal = document.getElementById('payment-modal');
        const grid = document.getElementById('pack-grid');

        modal.classList.add('active');

        // Load packs if not already loaded
        if (packs.length === 0) {
            const loaded = await loadPacks();
            if (!loaded) {
                grid.innerHTML = '<div style="text-align: center; padding: 20px; color: #f87171;">Error al cargar packs. Intenta de nuevo.</div>';
                return;
            }
        }

        // Render packs
        grid.innerHTML = packs.map((pack, idx) => `
            <div class="pack-card ${pack.tag ? 'best-value' : ''}" onclick="selectPack(${idx})">
                <div class="pack-info">
                    <div>
                        <div class="pack-quantity">${pack.emoji} ${pack.cantidad}</div>
                        <div class="pack-label">
                            ${pack.cantidad === 1 ? 'pregunta' : 'preguntas'}
                        </div>
                    </div>
                    <div class="pack-price">
                        <div class="amount">$${pack.precio}</div>
                        <div class="unit">$${Math.round(pack.precio / pack.cantidad)} c/u</div>
                    </div>
                </div>
                ${pack.discount > 0 ? `<div class="pack-savings">Ahorrás ${pack.discount}%</div>` : ''}
            </div>
        `).join('');

        selectedPack = null;
    };

    window.closePaymentModal = function () {
        const modal = document.getElementById('payment-modal');
        modal.classList.remove('active');
        selectedPack = null;

        // Remove selected class from all cards
        document.querySelectorAll('.pack-card').forEach(card => {
            card.classList.remove('selected');
        });
    };

    window.selectPack = function (index) {
        selectedPack = packs[index];
        document.querySelectorAll('.pack-card').forEach((card, i) => {
            card.classList.toggle('selected', i === index);
        });
    };

    window.processPurchase = function () {
        if (!selectedPack) {
            alert('Por favor selecciona un pack');
            return;
        }

        const btn = document.getElementById('continue-payment-btn');
        btn.disabled = true;
        btn.textContent = 'Procesando...';

        fetch('api/payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pack: selectedPack.cantidad })
        })
            .then(r => r.json())
            .then(d => {
                if (d.init_point) {
                    window.location.href = d.init_point; // Redirect to MercadoPago
                } else {
                    alert(d.error || 'Error al iniciar pago');
                    btn.disabled = false;
                    btn.textContent = 'Continuar a Pago';
                }
            })
            .catch(err => {
                alert('Error de conexión: ' + err.message);
                btn.disabled = false;
                btn.textContent = 'Continuar a Pago';
            });
    };

    // Pack Selection Button
    const addQuestionsBtn = document.getElementById('add-questions-btn');
    if (addQuestionsBtn) {
        addQuestionsBtn.addEventListener('click', openPaymentModal);
    }

    // Close modal when clicking outside
    document.addEventListener('click', (e) => {
        const modal = document.getElementById('payment-modal');
        if (e.target === modal) {
            closePaymentModal();
        }
    });

    // ===== SETTINGS MODAL =====

    // Inject settings modal HTML
    const settingsHTML = `
        <div id="settings-modal" class="settings-modal">
            <div class="settings-content">
                <div class="settings-header">
                    <h2>⚙️ Configuración</h2>
                    <p>Personaliza tu experiencia con el oráculo</p>
                </div>
                
                <div class="settings-option">
                    <label>Tipo de Respuestas</label>
                    <div class="response-toggle">
                        <button class="toggle-btn" data-pref="corta">
                            <i class="fa-solid fa-bolt"></i>
                            <span>Cortas y Directas</span>
                        </button>
                        <button class="toggle-btn" data-pref="larga">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            <span>Largas y Místicas</span>
                        </button>
                    </div>
                </div>
                
                <div class="settings-info">
                    <strong>💡 Tip:</strong> Las respuestas cortas van directo al punto. 
                    Las largas incluyen más contexto, simbolismo y detalles espirituales.
                </div>
                
                <div class="settings-actions">
                    <button class="settings-btn secondary" onclick="closeSettingsModal()">Cancelar</button>
                    <button class="settings-btn primary" id="save-settings-btn">Guardar Cambios</button>
                </div>
                
                <div class="settings-footer" style="margin-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 15px;">
                    <button id="logout-btn" class="settings-btn danger" style="width: 100%; background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);">
                        <i class="fa-solid fa-sign-out-alt"></i> Cerrar Sesión
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', settingsHTML);

    let currentPreference = 'larga'; // Default

    window.openSettingsModal = async function () {
        const modal = document.getElementById('settings-modal');

        // Obtener preferencia actual del servidor
        try {
            const res = await fetch('api/get_questions.php');
            const data = await res.json();
            currentPreference = data.preferencia_respuesta || 'larga';

            // Show/Hide logout based on auth status
            const logoutBtn = document.getElementById('logout-btn');
            if (data.is_guest) {
                logoutBtn.style.display = 'none';
            } else {
                logoutBtn.style.display = 'block';
            }
        } catch (err) {
            console.error('Error loading preferences:', err);
        }

        // Marcar botón activo
        document.querySelectorAll('.toggle-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.pref === currentPreference);
        });

        modal.classList.add('active');
    };

    // Logout Logic
    document.getElementById('logout-btn').addEventListener('click', async () => {
        if (!confirm('¿Estás seguro de que quieres cerrar sesión?')) return;

        try {
            await fetch('api/logout.php');
            window.location.reload();
        } catch (err) {
            console.error('Error logging out:', err);
            window.location.reload();
        }
    });

    window.closeSettingsModal = function () {
        document.getElementById('settings-modal').classList.remove('active');
    };

    // Toggle preference selection
    document.addEventListener('click', (e) => {
        if (e.target.closest('.toggle-btn')) {
            const btn = e.target.closest('.toggle-btn');
            currentPreference = btn.dataset.pref;

            document.querySelectorAll('.toggle-btn').forEach(b => {
                b.classList.remove('active');
            });
            btn.classList.add('active');
        }
    });

    // Save settings
    document.getElementById('save-settings-btn').addEventListener('click', async () => {
        const btn = document.getElementById('save-settings-btn');
        btn.disabled = true;
        btn.textContent = 'Guardando...';

        try {
            const res = await fetch('api/update_preferences.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ preferencia: currentPreference })
            });

            const data = await res.json();

            if (data.success) {
                closeSettingsModal();
                // Mostrar mensaje temporal
                const msg = document.createElement('div');
                msg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #4ade80; color: #000; padding: 15px 20px; border-radius: 10px; z-index: 9999; font-weight: 600;';
                msg.textContent = '✓ Configuración guardada';
                document.body.appendChild(msg);
                setTimeout(() => msg.remove(), 2000);
            } else {
                alert('Error al guardar: ' + (data.error || 'Inténtalo de nuevo'));
            }
        } catch (err) {
            alert('Error de conexión: ' + err.message);
        }

        btn.disabled = false;
        btn.textContent = 'Guardar Cambios';
    });

    // Settings button
    const settingsBtn = document.getElementById('settings-btn');
    if (settingsBtn) {
        settingsBtn.addEventListener('click', openSettingsModal);
    }

    // Close settings modal when clicking outside
    document.addEventListener('click', (e) => {
        const modal = document.getElementById('settings-modal');
        if (e.target === modal) {
            closeSettingsModal();
        }
    });

    // ===== SHARE MODAL =====

    const shareModalHTML = `
        <div id="share-modal" class="share-modal">
            <div class="share-content">
                <h2>📸 Compartir Lectura</h2>
                <p>Comparte tu consulta con amigos y familiares</p>
                <img id="share-preview-img" class="share-preview" src="" alt="Lectura">
                <div class="share-actions">
                    <button class="share-action-btn download" id="download-share-btn">
                        <i class="fa-solid fa-download"></i> Descargar
                    </button>
                    <button class="share-action-btn whatsapp" id="whatsapp-share-btn">
                        <i class="fa-brands fa-whatsapp"></i> WhatsApp
                    </button>
                    <button class="share-action-btn instagram" id="instagram-share-btn">
                        <i class="fa-brands fa-instagram"></i> Instagram
                    </button>
                    <button class="share-action-btn close" onclick="closeShareModal()">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', shareModalHTML);

    let currentShareUrl = '';

    window.openShareModal = function (imageUrl, question, response) {
        currentShareUrl = imageUrl;
        const modal = document.getElementById('share-modal');
        const img = document.getElementById('share-preview-img');
        img.src = imageUrl;
        modal.classList.add('active');
    };

    window.closeShareModal = function () {
        document.getElementById('share-modal').classList.remove('active');
    };

    // Download button
    document.getElementById('download-share-btn').addEventListener('click', () => {
        const link = document.createElement('a');
        link.href = currentShareUrl;
        link.download = 'lectura-oraculo.png';
        link.click();
    });

    // WhatsApp share
    document.getElementById('whatsapp-share-btn').addEventListener('click', () => {
        const text = encodeURIComponent('Mira mi lectura del Oráculo Místico ✨ - Consulta tu destino en OraculoMistico.com');
        window.open(`https://wa.me/?text=${text}`, '_blank');
        alert('💡 Descarga la imagen y compártela manualmente en WhatsApp');
    });

    // Instagram share
    document.getElementById('instagram-share-btn').addEventListener('click', () => {
        alert('💡 Descarga la imagen y compártela en Instagram Stories o Feed');
        const link = document.createElement('a');
        link.href = currentShareUrl;
        link.download = 'lectura-oraculo.png';
        link.click();
    });

    // Close share modal when clicking outside
    document.addEventListener('click', (e) => {
        const modal = document.getElementById('share-modal');
        if (e.target === modal) {
            closeShareModal();
        }
    });

    // ===== MOON PHASE WIDGET =====
    initMoonPhase();
});

// Moon Phase & Sign Calculator
function initMoonPhase() {
    // Container in index.php
    const moonContainer = document.getElementById('moon-container');
    if (!moonContainer) return;

    // Create widget
    const widget = document.createElement('div');
    widget.id = 'moon-widget';
    widget.className = 'moon-widget';
    widget.title = 'Fase lunar y signo actual';

    // Calculate current moon phase and sign
    const moonData = getMoonPhase();

    // Moon Sign approximation based on Sun sign + days
    const sign = getApproxMoonSign(moonData.age);

    widget.innerHTML = `
        <div class="moon-icon">${moonData.emoji}</div>
        <div class="moon-info">
            <div class="moon-phase-name">${moonData.name}</div>
            <div class="moon-sign-badge">
                <i class="fa-solid fa-star-and-crescent"></i> ${sign.symbol} ${sign.name}
            </div>
        </div>
    `;

    moonContainer.appendChild(widget);
}

function getApproxMoonSign(moonAgeDays) {
    const today = new Date();
    // 1. Get Sun Sign index roughly
    // Simple lookup table for Sun start dates (approx 20th of each month)
    const day = today.getDate();
    const month = today.getMonth(); // 0-11

    // Standard Zodiac: Aries (0) starts ~March 21
    // March is month 2.
    // Let's index 0 = Aries.

    const signs = [
        { name: 'Aries', symbol: '♈' }, { name: 'Tauro', symbol: '♉' }, { name: 'Géminis', symbol: '♊' },
        { name: 'Cáncer', symbol: '♋' }, { name: 'Leo', symbol: '♌' }, { name: 'Virgo', symbol: '♍' },
        { name: 'Libra', symbol: '♎' }, { name: 'Escorpio', symbol: '♏' }, { name: 'Sagitario', symbol: '♐' },
        { name: 'Capricornio', symbol: '♑' }, { name: 'Acuario', symbol: '♒' }, { name: 'Piscis', symbol: '♓' }
    ];

    // Determine Sun Sign Index
    let sunSignIndex = 0;
    // Simplified Sun Sign:
    // Mar 21-Apr 19: Aries (0)
    // Apr 20-May 20: Tauro (1) ...
    const dates = [21, 20, 21, 20, 21, 21, 23, 23, 23, 23, 22, 22]; // Start days from Mar to Feb? No.
    // Let's do a simple if/else for month
    if ((month == 2 && day >= 21) || (month == 3 && day <= 19)) sunSignIndex = 0; // Aries
    else if ((month == 3 && day >= 20) || (month == 4 && day <= 20)) sunSignIndex = 1; // Taurus
    else if ((month == 4 && day >= 21) || (month == 5 && day <= 20)) sunSignIndex = 2; // Gemini
    else if ((month == 5 && day >= 21) || (month == 6 && day <= 22)) sunSignIndex = 3; // Cancer
    else if ((month == 6 && day >= 23) || (month == 7 && day <= 22)) sunSignIndex = 4; // Leo
    else if ((month == 7 && day >= 23) || (month == 8 && day <= 22)) sunSignIndex = 5; // Virgo
    else if ((month == 8 && day >= 23) || (month == 9 && day <= 22)) sunSignIndex = 6; // Libra
    else if ((month == 9 && day >= 23) || (month == 10 && day <= 21)) sunSignIndex = 7; // Scorpio
    else if ((month == 10 && day >= 22) || (month == 11 && day <= 21)) sunSignIndex = 8; // Sagittarius
    else if ((month == 11 && day >= 22) || (month == 0 && day <= 19)) sunSignIndex = 9; // Capricorn
    else if ((month == 0 && day >= 20) || (month == 1 && day <= 18)) sunSignIndex = 10; // Aquarius
    else sunSignIndex = 11; // Pisces

    // 2. Calculate Moon offset
    // Moon moves approx 1 sign every 2.5 days.
    // New moon (age 0) is same as Sun sign.
    const signsToAdd = Math.floor(moonAgeDays / 2.3); // 2.3 is slightly better avg

    const moonSignIndex = (sunSignIndex + signsToAdd) % 12;

    return signs[moonSignIndex];
}

function getMoonPhase() {
    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth() + 1;
    const day = today.getDate();

    // Lunar cycle calculation (simplified)
    const julianDay = 367 * year - Math.floor((7 * (year + Math.floor((month + 9) / 12))) / 4) +
        Math.floor((275 * month) / 9) + day + 1721013.5;

    const daysSinceNew = julianDay - 2451549.5; // Days since known new moon
    const newMoons = daysSinceNew / 29.53;
    const phase = (newMoons - Math.floor(newMoons)) * 29.53; // Age in days
    const illumination = Math.round((1 - Math.cos(2 * Math.PI * phase / 29.53)) * 50);

    // Determine phase name and emoji
    let name, emoji;
    if (phase < 1.84566) {
        name = 'Luna Nueva';
        emoji = '🌑';
    } else if (phase < 5.53699) {
        name = 'Creciente';
        emoji = '🌒';
    } else if (phase < 9.22831) {
        name = 'Cuarto Creciente';
        emoji = '🌓';
    } else if (phase < 12.91963) {
        name = 'Gibosa Creciente';
        emoji = '🌔';
    } else if (phase < 16.61096) {
        name = 'Luna Llena';
        emoji = '🌕';
    } else if (phase < 20.30228) {
        name = 'Gibosa Menguante';
        emoji = '🌖';
    } else if (phase < 23.99361) {
        name = 'Cuarto Menguante';
        emoji = '🌗';
    } else if (phase < 27.68493) {
        name = 'Menguante';
        emoji = '🌘';
    } else {
        name = 'Luna Nueva';
        emoji = '🌑';
    }

    return { name, emoji, illumination, age: phase };
}

// ==========================================
//  TAROT CARD RENDERING & ANIMATION
// ================================// ===== TAROT CARD RENDERING =====
function renderTarotSpread(cards, interpretation) {
    const container = document.getElementById('chat-window');
    if (!container) {
        console.error('Chat window container not found');
        return;
    }
    const msgDiv = document.createElement('div');
    msgDiv.className = 'message ai';

    // Header
    const header = document.createElement('div');
    header.className = 'tarot-reading-header';
    header.textContent = 'Tu Tirada de Tarot';
    msgDiv.appendChild(header);

    // Card container
    const spreadContainer = document.createElement('div');
    spreadContainer.className = 'tarot-spread-container';

    cards.forEach((card, index) => {
        const cardDiv = document.createElement('div');
        cardDiv.className = 'tarot-card';
        cardDiv.dataset.index = index;

        // Back of card
        const cardBack = document.createElement('div');
        cardBack.className = 'tarot-card-face tarot-card-back';

        // Front of card
        const cardFront = document.createElement('div');
        cardFront.className = 'tarot-card-face tarot-card-front';

        // Use real card image if available, otherwise placeholder
        if (card.image_path && card.image_path !== '') {
            const cardImage = document.createElement('img');
            cardImage.src = card.image_path;
            cardImage.alt = card.name;
            cardImage.style.cssText = 'max-width: 100%; max-height: 70%; object-fit: contain; border-radius: 8px; margin-bottom: 10px;';
            cardImage.onerror = function () {
                this.replaceWith(createPlaceholder()); // Fallback if image fails to load
            };
            cardFront.appendChild(cardImage);
        } else {
            cardFront.appendChild(createPlaceholder());
        }

        function createPlaceholder() {
            const placeholder = document.createElement('div');
            placeholder.className = 'tarot-card-placeholder';
            placeholder.textContent = '🌟';
            return placeholder;
        }

        const cardName = document.createElement('div');
        cardName.className = 'tarot-card-name';
        cardName.textContent = card.name;
        if (card.reversed) {
            cardName.textContent += ' (Invertida)';
        }
        cardFront.appendChild(cardName);

        // Append faces directly to card (no cardInner wrapper)
        cardDiv.appendChild(cardBack);
        cardDiv.appendChild(cardFront);

        // Position label
        const posLabel = document.createElement('div');
        posLabel.className = 'tarot-position-label';
        posLabel.textContent = card.position;

        const cardWrapper = document.createElement('div');
        cardWrapper.appendChild(cardDiv);
        cardWrapper.appendChild(posLabel);

        spreadContainer.appendChild(cardWrapper);
    });

    msgDiv.appendChild(spreadContainer);
    container.appendChild(msgDiv);
    container.scrollTop = container.scrollHeight;

    // Trigger flip animations immediately after DOM insertion
    setTimeout(() => {
        console.log('⭐ FLIP ANIMATION TRIGGERED - spreadContainer:', spreadContainer);
        cards.forEach((card, index) => {
            setTimeout(() => {
                const cardEl = spreadContainer.querySelector(`.tarot-card[data-index="${index}"]`);
                console.log(`🎴 Card ${index} (${card.name}):`, cardEl);
                if (cardEl) {
                    console.log(`  - Classes BEFORE:`, cardEl.className);
                    cardEl.classList.add('flipped');
                    console.log(`  - Classes AFTER:`, cardEl.className);
                    console.log(`  - Computed transform:`, window.getComputedStyle(cardEl).transform);
                    if (card.reversed) {
                        setTimeout(() => {
                            cardEl.classList.add('reversed');
                            console.log(`  - Added 'reversed' class to card ${index}`);
                        }, 800);
                    }
                } else {
                    console.error(`❌ Card element NOT FOUND for index ${index}`);
                }
            }, index * 1000); // 1 second between each flip
        });
    }, 100); // Start almost immediately

    // Show interpretation after all cards have flipped
    setTimeout(() => {
        if (interpretation) {
            // Create interpretation message directly without using appendMessage
            const interpDiv = document.createElement('div');
            interpDiv.className = 'message ai';
            const bubble = document.createElement('div');
            bubble.className = 'bubble';
            bubble.textContent = interpretation;
            interpDiv.appendChild(bubble);
            container.appendChild(interpDiv);
        }
        container.scrollTop = container.scrollHeight;
    }, cards.length * 1200 + 1500);
}


