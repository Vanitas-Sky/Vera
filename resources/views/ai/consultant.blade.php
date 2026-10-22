<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl leading-tight flex items-center gap-2">
                <!-- Ícono del Relámpago en Verde Menta -->
                <svg class="w-6 h-6 text-vera-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>

                <!-- Texto con Gradiente Animado (Máscara de Texto) -->
                <span class="animate-text-shimmer bg-[linear-gradient(110deg,#ffffff,45%,#10B981,55%,#ffffff)] bg-[length:250%_100%] bg-clip-text text-transparent">
                    {{ __('Consultor Vera AI') }}
                </span>
            </h2>
            <span class="px-3 py-1 bg-indigo-100 text-indigo-800 text-xs font-bold rounded-full">Claude Haiku 4.5 Engine</span>
        </div>
    </x-slot>

    <!-- Librería para renderizar el Markdown de Claude -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <div class="py-8 bg-slate-50 min-h-[calc(100vh-64px)]">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow-sm sm:rounded-lg border border-slate-200 flex flex-col h-[75vh]">

                <!-- Área de Mensajes (Chat Box) -->
                <div id="chatbox" class="flex-1 overflow-y-auto p-6 space-y-6 bg-slate-50/50">

                    <!-- Mensaje Inicial de Vera (Corregido con la imagen) -->
                    <div class="flex gap-4">
                        <img src="{{ asset('img/noche.png') }}" alt="Vera AI" class="flex-shrink-0 w-8 h-8 rounded-full object-cover shadow-md border border-indigo-200" style="image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges;">
                        <div class="bg-white border border-slate-200 rounded-2xl rounded-tl-none px-5 py-3.5 max-w-[85%] shadow-sm text-sm text-slate-700">
                            <p>Hola. Soy <strong>Vera AI</strong>, tu consultor financiero y legal. Tengo el contexto de la empresa <strong>{{ $company->legal_name }}</strong>.</p>
                            <p class="mt-2">¿En qué te puedo asesorar hoy sobre nóminas, impuestos, IMSS o finanzas corporativas?</p>
                        </div>
                    </div>

                </div>

                <!-- Indicador de Carga (Oculto por defecto) -->
                <div id="loading" class="hidden px-14 pb-2">
                    <span class="flex items-center gap-1">
                        <span class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce"></span>
                        <span class="w-2 h-2 bg-indigo-500 rounded-full animate-bounce" style="animation-delay: 0.1s"></span>
                        <span class="w-2 h-2 bg-indigo-600 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
                        <span class="text-xs text-slate-400 font-medium ml-2">Vera está analizando...</span>
                    </span>
                </div>
                <!-- Input Box -->
                <!-- Chips de Sugerencia Rápida (Novedades de la Versión) -->
                <div class="px-4 pt-3 pb-1 bg-white border-t border-slate-100 flex flex-wrap gap-2">
                    <button type="button" class="quick-chip text-xs bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 px-3 py-1.5 rounded-full font-medium transition flex items-center gap-1.5">
                        <span>⚖️ Auditar deducibilidad de facturas según mi giro</span>
                    </button>
                    <button type="button" class="quick-chip text-xs bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200 px-3 py-1.5 rounded-full font-medium transition flex items-center gap-1.5">
                        <span>📊 ¿Cómo va el balance de ingresos vs egresos?</span>
                    </button>
                    <button type="button" class="quick-chip text-xs bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 px-3 py-1.5 rounded-full font-medium transition flex items-center gap-1.5">
                        <span>👥 Resumen integral de nómina, IMSS y retenciones</span>
                    </button>
                    <button id="clear-chat-btn" type="button" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-3 py-1.5 rounded-lg border border-slate-300 transition flex items-center gap-1.5 shadow-sm">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span>Nueva sesión / Limpiar memoria</span>
                    </button>
                </div>

                <!-- Input Box -->
                <div class="p-4 bg-white">
                    <form id="ai-form" class="flex gap-3">
                        <input type="text" id="question" autocomplete="off" placeholder="Escribe tu consulta o selecciona una sugerencia arriba..."
                            class="flex-1 rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 text-sm py-3 px-4" required>

                        <button type="submit" id="send-btn" class="bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 px-6 rounded-xl shadow-md transition flex items-center gap-2">
                            <span>Enviar</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- Motor JavaScript de la Interfaz -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('ai-form');
            const questionInput = document.getElementById('question');
            const chatbox = document.getElementById('chatbox');
            const loading = document.getElementById('loading');
            const sendBtn = document.getElementById('send-btn');
            const clearChatBtn = document.getElementById('clear-chat-btn');

            marked.setOptions({ breaks: true });

            const STORAGE_KEY_HISTORY = 'vera_ai_chat_history';
            const STORAGE_KEY_HTML = 'vera_ai_chat_html';
            const STORAGE_KEY_TIME = 'vera_ai_chat_last_time';
            const INACTIVITY_LIMIT_MINUTES = 25; // Expira tras 25 min sin uso

            let chatHistory = [];

            // 1. Verificar caducidad y restaurar si la sesión sigue viva
            function initSession() {
                const lastTime = localStorage.getItem(STORAGE_KEY_TIME);
                const savedHistory = localStorage.getItem(STORAGE_KEY_HISTORY);
                const savedHTML = localStorage.getItem(STORAGE_KEY_HTML);

                if (lastTime && savedHistory && savedHTML) {
                    const minutesElapsed = (Date.now() - parseInt(lastTime, 10)) / (1000 * 60);

                    if (minutesElapsed < INACTIVITY_LIMIT_MINUTES) {
                        // Sesión válida: restauramos los mensajes y la memoria
                        chatHistory = JSON.parse(savedHistory);
                        chatbox.innerHTML = savedHTML;
                        scrollToBottom();
                        updateActivityTime();
                        return;
                    }
                }
                // Si expiró o no existe, limpiamos
                resetSessionStorage();
            }

            function updateActivityTime() {
                localStorage.setItem(STORAGE_KEY_TIME, Date.now().toString());
            }

            function persistCurrentState() {
                localStorage.setItem(STORAGE_KEY_HISTORY, JSON.stringify(chatHistory));
                localStorage.setItem(STORAGE_KEY_HTML, chatbox.innerHTML);
                updateActivityTime();
            }

            function resetSessionStorage() {
                localStorage.removeItem(STORAGE_KEY_HISTORY);
                localStorage.removeItem(STORAGE_KEY_HTML);
                localStorage.removeItem(STORAGE_KEY_TIME);
                chatHistory = [];
            }

            // Inicializamos al cargar la página
            initSession();

            // 2. Botón manual de limpiar chat / nueva sesión
            if (clearChatBtn) {
                clearChatBtn.addEventListener('click', function() {
                    resetSessionStorage();
                    chatbox.innerHTML = `
                        <div class="flex gap-4">
                            <img src="{{ asset('img/noche.png') }}" alt="Vera AI" class="flex-shrink-0 w-8 h-8 rounded-full object-cover shadow-md border border-indigo-200">
                            <div class="bg-white border border-slate-200 rounded-2xl rounded-tl-none px-5 py-3.5 max-w-[85%] shadow-sm text-sm text-slate-700">
                                <p>Sesión reiniciada. He limpiado la memoria de la conversación anterior.</p>
                                <p class="mt-2">¿Sobre qué factura, nómina, colaborador o gasto fijo deseas consultar?</p>
                            </div>
                        </div>
                    `;
                });
            }

            // 3. Listener para chips de sugerencias rápidas
            document.querySelectorAll('.quick-chip').forEach(button => {
                button.addEventListener('click', function() {
                    const chipText = this.innerText.replace(/^[^\w\s¿?]+/, '').trim();
                    questionInput.value = chipText;
                    form.dispatchEvent(new Event('submit'));
                });
            });

            // 4. Envío del formulario
            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                const question = questionInput.value.trim();
                if (!question) return;

                appendMessage('user', question);
                questionInput.value = '';

                questionInput.disabled = true;
                sendBtn.disabled = true;
                loading.classList.remove('hidden');
                scrollToBottom();

                try {
                    const response = await fetch('{{ route("ai.ask") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            question: question,
                            history: chatHistory
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        appendMessage('ai', data.answer);
                        chatHistory.push({ role: 'user', content: question });
                        chatHistory.push({ role: 'assistant', content: data.answer });
                        persistCurrentState(); // Guardamos tanto el HTML como la memoria
                    } else {
                        appendMessage('error', 'Ocurrió un error: ' + data.error);
                    }

                } catch (error) {
                    appendMessage('error', 'Fallo de conexión o respuesta inesperada del servidor.');
                } finally {
                    loading.classList.add('hidden');
                    questionInput.disabled = false;
                    sendBtn.disabled = false;
                    questionInput.focus();
                    scrollToBottom();
                }
            });

            function appendMessage(sender, text) {
                const div = document.createElement('div');
                div.className = 'flex gap-4 ' + (sender === 'user' ? 'flex-row-reverse' : '');

                let innerHTML = '';
                if (sender === 'user') {
                    innerHTML = `
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-white font-bold text-xs shadow-md">TU</div>
                        <div class="bg-slate-800 text-white border border-slate-700 rounded-2xl rounded-tr-none px-5 py-3.5 max-w-[85%] shadow-sm text-sm">
                            ${escapeHTML(text)}
                        </div>
                    `;
                } else if (sender === 'ai') {
                    innerHTML = `
                        <img src="{{ asset('img/noche.png') }}" alt="Vera AI" class="flex-shrink-0 w-8 h-8 rounded-full object-cover shadow-md border border-indigo-200">
                        <div class="bg-white border border-slate-200 rounded-2xl rounded-tl-none px-5 py-3.5 max-w-[85%] shadow-sm text-sm text-slate-700 overflow-x-auto" style="line-height: 1.6;">
                            ${marked.parse(text)}
                        </div>
                    `;
                } else {
                    innerHTML = `
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-red-600 flex items-center justify-center text-white font-bold text-xs shadow-md">!</div>
                        <div class="bg-red-50 text-red-700 border border-red-200 rounded-2xl rounded-tl-none px-5 py-3.5 max-w-[85%] shadow-sm text-sm font-semibold">
                            ${escapeHTML(text)}
                        </div>
                    `;
                }

                div.innerHTML = innerHTML;
                chatbox.appendChild(div);
            }

            function escapeHTML(str) {
                return str.replace(/[&<>'"]/g, tag => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
                }[tag]));
            }

            function scrollToBottom() {
                chatbox.scrollTop = chatbox.scrollHeight;
            }
        });
    </script>
</x-app-layout>