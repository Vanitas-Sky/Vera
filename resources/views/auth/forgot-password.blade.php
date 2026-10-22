<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Vera ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100 flex items-center justify-center min-h-screen px-4">

    <div class="w-full max-w-md bg-slate-800 rounded-2xl shadow-xl border border-slate-700 p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-white">Vera ERP</h1>
            <p class="text-sm text-slate-400 mt-1">Recuperación de Acceso</p>
        </div>

        <!-- Mensaje de éxito si ya se envió -->
        @if (session('status'))
            <div class="mb-5 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm">
                {{ $errors->first('email') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Ingresa tu correo registrado</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    placeholder="correo@empresa.com"
                    class="w-full px-4 py-3 rounded-xl bg-slate-900/60 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
            </div>

            <button type="submit" 
                class="w-full py-3 px-4 rounded-xl bg-emerald-500 hover:bg-emerald-600 font-semibold text-white shadow-lg shadow-emerald-500/20 transition text-sm">
                Enviar Enlace de Recuperación
            </button>
        </form>

        <div class="text-center mt-6">
            <a href="/login" class="text-xs text-slate-400 hover:text-white transition">Regresar al Login</a>
        </div>
    </div>

</body>
</html>