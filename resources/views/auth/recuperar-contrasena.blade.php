<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Vera ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100 flex items-center justify-center min-h-screen px-4">

    <div class="w-full max-w-md bg-slate-800 rounded-2xl shadow-2xl border border-slate-700/60 p-8">
        
        <!-- Encabezado -->
        <div class="text-center mb-6">
            <h1 class="text-2xl font-black tracking-tight text-white">Vera ERP</h1>
            <p class="text-xs text-slate-400 mt-1 uppercase tracking-widest font-semibold">
                {{ $step === 1 ? 'Paso 1: Solicitar Código' : 'Paso 2: Restablecer Contraseña' }}
            </p>
        </div>

        <!-- Alertas de estado o errores -->
        @if (session('status'))
            <div class="mb-5 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- ========================================== -->
        <!-- PASO 1: FORMULARIO PARA SOLICITAR CÓDIGO   -->
        <!-- ========================================== -->
        @if ($step === 1)
            <form method="POST" action="{{ route('password.recovery.send') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Correo institucional</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        placeholder="tu-correo@empresa.com"
                        class="w-full px-4 py-3 rounded-xl bg-slate-900/70 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                </div>

                <button type="submit" 
                    class="w-full py-3 px-4 rounded-xl bg-emerald-500 hover:bg-emerald-600 font-bold text-white shadow-lg shadow-emerald-500/20 transition-all text-sm">
                    Enviar Código de Recuperación
                </button>
            </form>

        <!-- ========================================== -->
        <!-- PASO 2: RECIBIR TOKEN Y NUEVA CONTRASEÑA  -->
        <!-- ========================================== -->
        @else
            <div class="mb-4 p-3 rounded-lg bg-slate-900/80 border border-slate-700/80 text-xs text-slate-300 flex justify-between items-center">
                <span>Enviado a: <strong class="text-white">{{ $email }}</strong></span>
                <a href="{{ route('password.recovery.restart') }}" class="text-emerald-400 hover:underline text-xs">Cambiar</a>
            </div>

            <form method="POST" action="{{ route('password.recovery.update') }}" class="space-y-4">
                @csrf

                <!-- Correo automático oculto -->
                <input type="hidden" name="email" value="{{ $email }}">

                <div>
                    <label for="token" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Código / Token recibido</label>
                    <input id="token" type="text" name="token" required autofocus
                        placeholder="Pega aquí el código que llegó a tu correo"
                        class="w-full px-4 py-3 rounded-xl bg-slate-900/70 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm font-mono">
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Nueva Contraseña</label>
                    <input id="password" type="password" name="password" required
                        placeholder="Mínimo 6 caracteres"
                        class="w-full px-4 py-3 rounded-xl bg-slate-900/70 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Confirmar Contraseña</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required
                        placeholder="Repite la nueva contraseña"
                        class="w-full px-4 py-3 rounded-xl bg-slate-900/70 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                </div>

                <button type="submit" 
                    class="w-full py-3 px-4 rounded-xl bg-emerald-500 hover:bg-emerald-600 font-bold text-white shadow-lg shadow-emerald-500/20 transition-all text-sm">
                    Restablecer y Actualizar Clave
                </button>
            </form>
        @endif

        <div class="text-center mt-6">
            <a href="/login" class="text-xs text-slate-400 hover:text-white transition">← Regresar al inicio de sesión</a>
        </div>
    </div>

</body>
</html>