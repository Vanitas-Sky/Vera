<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

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
</body>

</html>