<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vera | Inteligencia financiera y claridad fiscal</title>

    <!-- Tailwind CSS (A través de Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased text-vera-gray bg-slate-50 font-sans">

    <!-- Navbar -->
    <nav class="bg-vera-dark border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo y Símbolo (Diamante) -->
                <div class="flex-shrink-0 flex items-center gap-3">
                    <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Faceta superior izquierda (Gris claro) -->
                        <path d="M2 9L7 3H12V9H2Z" fill="#CBD5E1" />
                        <!-- Faceta superior derecha (Gris Pizarra / sombra suave) -->
                        <path d="M22 9L17 3H12V9H22Z" fill="#64748B" />
                        <!-- Triángulo central superior (Verde Menta) -->
                        <path d="M7 3L17 3L12 9L7 3Z" fill="#10B981" />
                        <!-- Faceta inferior izquierda (Blanco para contraste máximo) -->
                        <path d="M2 9L12 22V9H2Z" fill="#F8FAFC" />
                        <!-- Faceta inferior derecha (Verde Menta principal) -->
                        <path d="M22 9L12 22V9H22Z" fill="#10B981" />
                    </svg>

                    <span class="text-white font-bold text-2xl tracking-tight">Vera</span>
                </div>

                <!-- Links (Desktop) -->
                <div class="hidden md:flex space-x-8">
                    <a href="#solucion" class="text-slate-300 hover:text-white transition font-medium">La Solución</a>
                    <a href="#como-funciona" class="text-slate-300 hover:text-white transition font-medium">Cómo Funciona</a>
                </div>

                <!-- Botones Auth -->
                <div class="flex items-center space-x-4">
                    @if (Route::has('login'))
                    @auth
                    <a href="{{ url('/dashboard') }}" class="text-white font-semibold hover:text-vera-green transition">Ir al Dashboard</a>
                    @else
                    <a href="{{ route('login') }}" class="text-slate-300 hover:text-white font-medium transition">Iniciar Sesión</a>
                    @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="bg-vera-green hover:bg-emerald-400 text-white font-bold py-2 px-5 rounded-lg transition shadow-lg shadow-emerald-500/30">
                        Prueba Gratis
                    </a>
                    @endif
                    @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative bg-vera-dark overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-24 lg:pt-32 lg:pb-40 text-center">
            <h1 class="text-4xl tracking-tight font-extrabold text-white sm:text-5xl md:text-6xl">
                <span class="block">Inteligencia financiera y</span>
                <span class="block text-vera-green">claridad fiscal.</span>
            </h1>
            <p class="mt-6 max-w-2xl mx-auto text-lg text-slate-300 sm:text-xl">
                El primer ERP-Lite para PyMEs que no solo registra tus transacciones, sino que las audita. Conecta tu flujo de caja con tus impuestos de forma inteligente y segura.
            </p>
            <div class="mt-10 flex justify-center gap-4">
                <a href="{{ route('register') ?? '#' }}" class="bg-vera-green hover:bg-emerald-400 text-white font-bold py-3 px-8 rounded-lg text-lg transition shadow-lg shadow-emerald-500/40">
                    Comienza a optimizar
                </a>
                <a href="#solucion" class="bg-transparent border-2 border-slate-600 text-white hover:border-slate-400 font-bold py-3 px-8 rounded-lg text-lg transition">
                    Conoce más
                </a>
            </div>
        </div>
        <div class="absolute bottom-0 inset-x-0 h-16 bg-gradient-to-t from-slate-50 to-transparent"></div>
    </div>

    <!-- Problema / Solución Section -->
    <section id="solucion" class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-extrabold text-vera-dark">Termina con la ceguera financiera</h2>
            <p class="mt-4 max-w-3xl mx-auto text-lg text-vera-gray">Las PyMEs operan con canales desconectados: el banco dice una cosa, el SAT otra, y tu flujo operativo otra. Vera lo centraliza todo.</p>

            <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-10">
                <!-- Feature 1 -->
                <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition">
                    <div class="w-14 h-14 bg-emerald-100 rounded-lg flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-vera-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-vera-dark mb-3">Auditoría Preventiva</h3>
                    <p class="text-vera-gray">Nuestro semáforo de deducibilidad evalúa cada gasto comprobando su estricta indispensabilidad según tu régimen fiscal.</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition">
                    <div class="w-14 h-14 bg-emerald-100 rounded-lg flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-vera-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-vera-dark mb-3">Seguridad Absoluta</h3>
                    <p class="text-vera-gray">No requerimos tus contraseñas del SAT ni credenciales bancarias. El control total de la ingesta de archivos (ZIP, XML) es tuyo.</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition">
                    <div class="w-14 h-14 bg-emerald-100 rounded-lg flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-vera-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-vera-dark mb-3">Agente Estratégico</h3>
                    <p class="text-vera-gray">Nuestra tecnología analiza tus métricas y redacta planes ejecutivos en español claro con consejos legales para reducir tus impuestos.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-vera-dark border-t border-slate-800 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center md:flex md:justify-between md:text-left">
            <div>
                <span class="text-white font-bold text-xl tracking-tight">Vera</span>
                <p class="mt-2 text-sm text-slate-400">Inteligencia financiera y claridad fiscal.</p>
            </div>
            <div class="mt-8 md:mt-0">
                <p class="text-sm text-slate-400">&copy; {{ date('Y') }} Vera Tech S.A. de C.V. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

</body>

</html>