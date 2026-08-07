<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'Vera') }}</title>

    <!-- Favicon del Diamante de Vera -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @if (isset($header))
        <header class="bg-white dark:bg-gray-800 shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
        @endif

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Script Maestro para Confirmaciones Globales -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Interceptamos todos los formularios que tengan la clase 'form-confirm'
            const confirmForms = document.querySelectorAll('.form-confirm');

            confirmForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault(); // Detenemos el envío del formulario instantáneo

                    // Leemos los mensajes personalizados desde el HTML, o usamos unos por defecto
                    const title = this.dataset.title || '¿Estás seguro?';
                    const text = this.dataset.text || 'Esta acción no se puede deshacer.';
                    const confirmText = this.dataset.confirm || 'Sí, continuar';

                    Swal.fire({
                        title: title,
                        text: text,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#0f172a', // Color slate-900 (Tailwind)
                        cancelButtonColor: '#ef4444', // Color red-500 (Tailwind)
                        confirmButtonText: confirmText,
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true, // Pone el botón de cancelar a la izquierda (mejor UX)
                        customClass: {
                            popup: 'rounded-lg border border-slate-200 shadow-xl',
                            title: 'text-slate-800 text-xl font-bold',
                            htmlContainer: 'text-slate-500'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Si el usuario confirma, enviamos el formulario físicamente
                            this.submit();
                        }
                    });
                });
            });
        });
    </script>

    <a href="{{ route('ai.consultant') }}"
        class="fixed bottom-5 right-5 z-50 inline-flex items-center gap-2 rounded-full bg-emerald-500 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-900/20 transition hover:bg-emerald-600"
        aria-label="Abrir Consultor Vera AI"
        title="Consultor Vera AI">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.5a2.75 2.75 0 0 1 4.5 0h.5a2.5 2.5 0 0 1 2.5 2.5v.5a2.75 2.75 0 0 1 0 4.5v.5a2.5 2.5 0 0 1-2.5 2.5h-.5a2.75 2.75 0 0 1-4.5 0h-.5A2.5 2.5 0 0 1 6.75 13v-.5a2.75 2.75 0 0 1 0-4.5v-.5A2.5 2.5 0 0 1 9.25 5h.5zm2.25 8.25a1.25 1.25 0 1 0 0 2.5 1.25 1.25 0 0 0 0-2.5zm-4.25-4.5a1.25 1.25 0 1 0 0 2.5 1.25 1.25 0 0 0 0-2.5zm8.5 0a1.25 1.25 0 1 0 0 2.5 1.25 1.25 0 0 0 0-2.5z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13v5m-2.5-2.5h5"></path>
        </svg>
        <span class="hidden sm:inline">Vera AI</span>
    </a>
</body>

</html>