<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Vera ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-900 text-slate-100 flex items-center justify-center min-h-screen px-4">

    <div class="w-full max-w-md bg-slate-800 rounded-2xl shadow-xl border border-slate-700 p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold tracking-tight text-white">Vera ERP</h1>
            <p class="text-sm text-slate-400 mt-1">Restablece tu contraseña de acceso</p>
        </div>

        @if ($errors->any())
        <div class="mb-5 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf

            <!-- Token oculto -->
            <input type="hidden" name="token" value="{{ $token ?? request()->route('token') }}">

            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Correo Electrónico</label>
                <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}" required autofocus
                    class="w-full px-4 py-3 rounded-xl bg-slate-900/60 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm">
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Nueva Contraseña</label>
                <input id="password" type="password" name="password" required
                    placeholder="Mínimo 6 caracteres"
                    class="w-full px-4 py-3 rounded-xl bg-slate-900/60 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm">
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Confirmar Contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                    placeholder="Repite la contraseña"
                    class="w-full px-4 py-3 rounded-xl bg-slate-900/60 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm">
            </div>

            <button type="submit"
                class="w-full py-3 px-4 rounded-xl bg-emerald-500 hover:bg-emerald-600 font-semibold text-white shadow-lg shadow-emerald-500/20 transition-all text-sm mt-2">
                Actualizar Contraseña
            </button>
        </form>

        <div class="text-center mt-6">
            <a href="/login" class="text-xs text-slate-400 hover:text-white transition">Regresar al inicio de sesión</a>
        </div>
    </div>

</body>

</html>