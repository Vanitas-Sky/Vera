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
    <nav class="bg-vera-dark border-b border-slate-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo y Símbolo (Diamante) -->
                <div class="flex-shrink-0 flex items-center gap-3">
                    <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2 9L7 3H12V9H2Z" fill="#CBD5E1" />
                        <path d="M22 9L17 3H12V9H22Z" fill="#64748B" />
                        <path d="M7 3L17 3L12 9L7 3Z" fill="#10B981" />
                        <path d="M2 9L12 22V9H2Z" fill="#F8FAFC" />
                        <path d="M22 9L12 22V9H22Z" fill="#10B981" />
                    </svg>
                    <span class="text-white font-bold text-2xl tracking-tight">Vera</span>
                </div>

                <!-- Links (Desktop) -->
                <div class="hidden md:flex space-x-8">
                    <a href="#solucion" class="text-slate-300 hover:text-white transition font-medium">Módulos</a>
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-24 lg:pt-28 lg:pb-36 text-center">
            
            <!-- Badge de Novedad -->
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-800 border border-slate-700 text-emerald-400 text-xs font-bold uppercase tracking-wider mb-8">
                <span class="flex h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                ERP-Lite Fiscal y Financiero 2026
            </div>

            <h1 class="text-4xl tracking-tight font-extrabold text-white sm:text-5xl md:text-6xl">
                <span class="block">Inteligencia financiera y</span>
                <span class="block text-vera-green">claridad fiscal para tu PyME.</span>
            </h1>
            <p class="mt-6 max-w-2xl mx-auto text-lg text-slate-300 sm:text-xl leading-relaxed">
                El ERP-Lite que audita tus transacciones en tiempo real: desde la **conciliación bancaria** y la **detección de discrepancia fiscal**, hasta el **cálculo unificado de nómina** (ISR e IMSS).
            </p>
            <div class="mt-10 flex flex-col sm:flex-row justify-center gap-4">
                @if (Route::has('register'))
                <a href="{{ route('register') }}" class="bg-vera-green hover:bg-emerald-400 text-white font-bold py-3 px-8 rounded-lg text-lg transition shadow-lg shadow-emerald-500/40">
                    Comenzar Gratis
                </a>
                @endif
                <a href="#solucion" class="bg-transparent border-2 border-slate-600 text-white hover:border-slate-400 font-bold py-3 px-8 rounded-lg text-lg transition">
                    Explorar Módulos
                </a>
            </div>
        </div>
        <div class="absolute bottom-0 inset-x-0 h-16 bg-gradient-to-t from-slate-50 to-transparent"></div>
    </div>

    <!-- Módulos Principales -->
    <section id="solucion" class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-extrabold text-vera-dark">Control total sobre tus finanzas y el SAT</h2>
            <p class="mt-4 max-w-3xl mx-auto text-lg text-vera-gray">Elimina la ceguera contable con herramientas diseñadas para operar sin riesgo fiscal.</p>

            <div class="mt-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                
                <!-- Card 1: Conciliación y Discrepancia -->
                <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200 hover:shadow-md transition text-left flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 bg-red-50 rounded-lg flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-vera-dark mb-2">Conciliación & Riesgo Fiscal</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">Sube el CSV crudo de tu banco y compáralo contra la Bóveda XML. Detecta salidas sin factura antes de que el SAT te audite.</p>
                    </div>
                </div>

                <!-- Card 2: Motor de Nómina Unificado -->
                <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200 hover:shadow-md transition text-left flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 bg-emerald-50 rounded-lg flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-vera-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-vera-dark mb-2">Nómina Inteligente 2026</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">Soporta pagos semanales, quincenales y mensuales. Calcula retenciones ISR (Art. 96), cuota obrera IMSS e Infonavit con timbrado CFDI 4.0 y envío masivo.</p>
                    </div>
                </div>

                <!-- Card 3: Auditoría de Gastos -->
                <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200 hover:shadow-md transition text-left flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-vera-dark mb-2">Semáforo de Deducibilidad</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">Evalúa automáticamente si cada factura recibida es estrictamente indispensable según el giro comercial registrado en tu Perfil Fiscal.</p>
                    </div>
                </div>

                <!-- Card 4: Seguridad y Privacidad -->
                <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200 hover:shadow-md transition text-left flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 bg-amber-50 rounded-lg flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-vera-dark mb-2">Seguridad Absoluta</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">Sin contraseñas del SAT ni CIEC guardadas. Conservas el control total mediante ingesta de archivos (ZIP, XML, CSV).</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Cómo Funciona Section -->
    <section id="como-funciona" class="py-20 bg-white border-y border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-extrabold text-vera-dark">Cómo opera Vera en 3 pasos</h2>
                <p class="mt-4 text-lg text-slate-500">Sin configuraciones complejas ni instalaciones pesadas.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 text-center">
                <!-- Paso 1 -->
                <div class="relative">
                    <div class="w-12 h-12 bg-slate-900 text-white rounded-full flex items-center justify-center text-xl font-bold mx-auto mb-6 shadow-md">1</div>
                    <h3 class="text-lg font-bold text-vera-dark mb-2">Carga tu Información</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Sube tus archivos XML de egresos/ingresos y el estado de cuenta bancario en CSV crudo directamente desde tu portal financiero.</p>
                </div>

                <!-- Paso 2 -->
                <div class="relative">
                    <div class="w-12 h-12 bg-vera-green text-white rounded-full flex items-center justify-center text-xl font-bold mx-auto mb-6 shadow-md">2</div>
                    <h3 class="text-lg font-bold text-vera-dark mb-2">Procesamiento Automatizado</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Vera audita la deducibilidad de tus gastos, cruza los movimientos bancarios para hallar discrepancias e imparte la nómina mensual.</p>
                </div>

                <!-- Paso 3 -->
                <div class="relative">
                    <div class="w-12 h-12 bg-slate-900 text-white rounded-full flex items-center justify-center text-xl font-bold mx-auto mb-6 shadow-md">3</div>
                    <h3 class="text-lg font-bold text-vera-dark mb-2">Decisiones Estratégicas</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Recibe recomendaciones financieras directas, emite o descarga tus recibos PDF y mantén tu empresa lista para cualquier auditoría.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Banner CTA Final -->
    <div class="bg-vera-dark py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-extrabold text-white sm:text-4xl">
                ¿Listo para eliminar la discrepancia fiscal de tu empresa?
            </h2>
            <p class="mt-4 text-lg text-slate-300 max-w-2xl mx-auto">
                Registra tu empresa en menos de 2 minutos y toma el control de tus finanzas.
            </p>
            <div class="mt-8">
                @if (Route::has('register'))
                <a href="{{ route('register') }}" class="inline-block bg-vera-green hover:bg-emerald-400 text-white font-bold py-3.5 px-8 rounded-lg text-lg transition shadow-lg shadow-emerald-500/40">
                    Comenzar Ahora
                </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-slate-950 border-t border-slate-800 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center md:flex md:justify-between md:text-left items-center">
            <div>
                <span class="text-white font-bold text-xl tracking-tight">Vera</span>
                <p class="mt-1 text-xs text-slate-400">Inteligencia financiera y claridad fiscal para PyMEs.</p>
            </div>
            <div class="mt-6 md:mt-0">
                <p class="text-xs text-slate-500">&copy; {{ date('Y') }} Vera Tech S.A. de C.V. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

</body>

</html>