<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-vera-dark leading-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                {{ __('Consultor Vera AI') }}
            </h2>
            <span class="px-3 py-1 bg-indigo-100 text-indigo-800 text-xs font-bold rounded-full">Claude 4 Haiku Engine</span>
        </div>
    </x-slot>

    <!-- Librería para renderizar el Markdown de Claude -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <div class="py-8 bg-slate-50 min-h-[calc(100vh-64px)]">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow-sm sm:rounded-lg border border-slate-200 flex flex-col h-[75vh]">

                <!-- Área de Mensajes (Chat Box) -->
                <div id="chatbox" class="flex-1 overflow-y-auto p-6 space-y-6 bg-slate-50/50">

                    <!-- Mensaje Inicial de Vera -->
                    <div class="flex gap-4">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-xs shadow-md">
                            V
                        </div>
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
                <div class="p-4 bg-white border-t border-slate-200">
                    <form id="ai-form" class="flex gap-3">
                        <input type="text" id="question" autocomplete="off" placeholder="Escribe tu consulta aquí..."
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

            // Configurar Marked.js para seguridad y saltos de línea
            marked.setOptions({
                breaks: true
            });

            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                const question = questionInput.value.trim();
                if (!question) return;

                // 1. Mostrar la pregunta del usuario en el chat
                appendMessage('user', question);
                questionInput.value = '';

                // 2. Bloquear input y mostrar loading
                questionInput.disabled = true;
                sendBtn.disabled = true;
                loading.classList.remove('hidden');
                scrollToBottom();

                try {
                    // 3. Hacer la llamada AJAX al controlador
                    const response = await fetch('{{ route("ai.ask") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({question: question})
                        });

                    const data = await response.json();

                    // 4. Procesar la respuesta
                    if (data.success) {
                        appendMessage('ai', data.answer);
                    } else {
                        appendMessage('error', 'Ocurrió un error: ' + data.error);
                    }

                } catch (error) {
                    appendMessage('error', 'Fallo de conexión. Revisa tu internet.');
                } finally {
                    // 5. Restaurar interfaz
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
                    // Burbuja del Usuario (Oscura)
                    innerHTML = `
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-white font-bold text-xs shadow-md">TU</div>
                        <div class="bg-slate-800 text-white border border-slate-700 rounded-2xl rounded-tr-none px-5 py-3.5 max-w-[85%] shadow-sm text-sm">
                            ${escapeHTML(text)}
                        </div>
                    `;
                } else if (sender === 'ai') {
                    // Burbuja de la IA (Clara + Parseo de Markdown)
                    // Las clases 'prose' son de Tailwind Typography, pero usamos estilos base si no está instalada
                    innerHTML = `
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-xs shadow-md">V</div>
                        <div class="bg-white border border-slate-200 rounded-2xl rounded-tl-none px-5 py-3.5 max-w-[85%] shadow-sm text-sm text-slate-700 overflow-x-auto" style="line-height: 1.6;">
                            ${marked.parse(text)}
                        </div>
                    `;
                } else {
                    // Burbuja de Error
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
                return str.replace(/[&<>'"]/g,
                    tag => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        "'": '&#39;',
                        '"': '&quot;'
                    } [tag])
                );
            }

            function scrollToBottom() {
                chatbox.scrollTop = chatbox.scrollHeight;
            }
        });
    </script>
</x-app-layout>