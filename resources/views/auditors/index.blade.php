<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Gestión de Auditores Externos') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Alertas -->
            @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('success') }}
            </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

                <!-- Formulario de Registro de Auditor -->
                <div class="lg:col-span-1 bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                    <div class="border-b border-slate-100 pb-3 mb-4">
                        <h3 class="font-bold text-vera-dark text-base">Registrar Nuevo Auditor</h3>
                        <p class="text-xs text-slate-500 mt-1">
                            El usuario tendrá acceso exclusivo de solo lectura a nóminas, CFDI, OpEx y al Consultor Vera AI.
                        </p>
                    </div>

                    <form action="{{ route('auditors.store') }}" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Nombre Completo o Despacho</label>
                            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Ej. Lic. Roberto Garza"
                                class="w-full text-sm rounded-md border-slate-300 shadow-sm focus:border-vera-green focus:ring-vera-green">
                            @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Correo Electrónico</label>
                            <input type="email" name="email" value="{{ old('email') }}" required placeholder="auditor@despacho.com"
                                class="w-full text-sm rounded-md border-slate-300 shadow-sm focus:border-vera-green focus:ring-vera-green">
                            @error('email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Contraseña de Acceso</label>
                            <input type="password" name="password" required placeholder="••••••••"
                                class="w-full text-sm rounded-md border-slate-300 shadow-sm focus:border-vera-green focus:ring-vera-green">
                            <p class="text-[11px] text-slate-400 mt-1">Mínimo 8 caracteres. Podrá cambiarla desde su perfil.</p>
                            @error('password') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="w-full bg-vera-green text-white font-bold py-2.5 px-4 rounded-lg hover:bg-emerald-700 transition shadow-sm text-sm">
                            + Dar de Alta Auditor
                        </button>
                    </form>
                </div>

                <!-- Lista de Auditores Activos -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                        <h3 class="font-bold text-vera-dark text-sm uppercase tracking-wider">Auditores con Acceso a {{ $company->legal_name }}</h3>
                        <span class="text-xs text-slate-500 font-medium">{{ $auditors->count() }} usuario(s)</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Nombre</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Correo</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase">Permisos</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                @forelse($auditors as $auditor)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 font-bold text-vera-dark">
                                        {{ $auditor->name }}
                                    </td>
                                    <td class="px-6 py-4 font-mono text-slate-600 text-xs">
                                        {{ $auditor->email }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            Solo Lectura
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <form action="{{ route('auditors.destroy', $auditor->id) }}" method="POST" class="inline-block form-confirm"
                                            data-title="¿Revocar acceso al auditor?"
                                            data-text="El usuario ya no podrá ingresar a consultar la información fiscal de tu empresa."
                                            data-confirm="Sí, revocar">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 font-bold text-xs uppercase tracking-wider bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-md transition">
                                                Revocar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-sm text-slate-400">
                                        No hay auditores externos asignados a tu empresa actualmente.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>