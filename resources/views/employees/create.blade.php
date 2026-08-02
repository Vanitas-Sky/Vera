<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('employees.index') }}" class="text-slate-400 hover:text-slate-600 transition font-bold">&larr; Volver</a>
            <h2 class="font-semibold text-xl text-vera-dark leading-tight">
                {{ __('Registrar Nuevo Empleado') }}
            </h2>
        </div>
    </x-slot>

    <!-- Animación CSS para errores (Shake Effect) -->
    <style>
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-4px); }
            40%, 80% { transform: translateX(4px); }
        }
        .animate-shake {
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }
    </style>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-8">
                
                <form action="{{ route('employees.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <!-- Datos Personales y Fiscales -->
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 border-b pb-2">Información de Identidad</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Nombre Completo</label>
                                <input type="text" name="full_name" value="{{ old('full_name') }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. Juan Pérez Gómez" required>
                                <p class="text-[11px] text-slate-400 mt-1">Tal como aparece en su Constancia de Situación Fiscal.</p>
                                @error('full_name') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">RFC</label>
                                <input type="text" name="rfc" value="{{ old('rfc') }}" class="w-full rounded-md border-slate-300 shadow-sm uppercase focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. CACX7605101P8" required>
                                <p class="text-[11px] text-slate-400 mt-1">12 o 13 caracteres, sin espacios ni guiones.</p>
                                @error('rfc') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">CURP</label>
                                <input type="text" name="curp" value="{{ old('curp') }}" class="w-full rounded-md border-slate-300 shadow-sm uppercase focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. GOVG750311HMCTRM00" required>
                                <p class="text-[11px] text-slate-400 mt-1">18 caracteres alfanuméricos.</p>
                                @error('curp') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Datos Laborales y de Contacto -->
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 border-b pb-2">Datos Laborales y Bancarios</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Correo Electrónico</label>
                                <input type="email" name="email" value="{{ old('email') }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. empleado@email.com">
                                <p class="text-[11px] text-slate-400 mt-1">Se usará para enviar automáticamente sus recibos de nómina.</p>
                                @error('email') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Puesto / Cargo</label>
                                <input type="text" name="position" value="{{ old('position') }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. Desarrollador Fullstack">
                                <p class="text-[11px] text-slate-400 mt-1">Para identificación en organigrama y tabuladores.</p>
                                @error('position') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Código Postal</label>
                                <input type="text" name="cp" value="{{ old('cp') }}" maxlength="5" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. 06000">
                                <p class="text-[11px] text-slate-400 mt-1">5 dígitos para registro de domicilio del empleado.</p>
                                @error('cp') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">NSS</label>
                                <input type="text" name="nss" value="{{ old('nss') }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. 12345678903" required>
                                <p class="text-[11px] text-slate-400 mt-1">Número de Seguridad Social (11 dígitos exactos).</p>
                                @error('nss') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">CLABE Interbancaria</label>
                                <input type="text" name="clabe" value="{{ old('clabe') }}" maxlength="18" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. 012180001234567890">
                                <p class="text-[11px] text-slate-400 mt-1">18 dígitos exactos para transferencia SPEI.</p>
                                @error('clabe') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Salario Base Mensual</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-slate-500">$</span>
                                    <input type="number" step="0.01" name="base_salary" value="{{ old('base_salary') }}" class="w-full pl-8 rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. 12500.50" required>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1">Salario bruto antes de retenciones de ISR e IMSS.</p>
                                @error('base_salary') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Fecha de Ingreso</label>
                                <input type="date" name="hire_date" value="{{ old('hire_date') }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                                <p class="text-[11px] text-slate-400 mt-1">Vital para cálculo de antigüedad, aguinaldo y liquidaciones.</p>
                                @error('hire_date') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="{{ route('employees.index') }}" class="px-4 py-2 bg-white border border-slate-300 rounded-md font-bold text-slate-700 hover:bg-slate-50 transition">
                            Cancelar
                        </a>
                        <button type="submit" class="px-6 py-2 bg-slate-900 text-white rounded-md font-bold hover:bg-slate-800 transition">
                            Guardar Empleado
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>