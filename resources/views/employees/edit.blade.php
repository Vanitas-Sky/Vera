<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('employees.index') }}" class="text-white hover:text-vera-green transition font-bold">&larr; Volver</a>
            <h2 class="font-semibold text-xl text-white leading-tight">
                {{ __('Editar Empleado: ') }} {{ $employee->full_name }}
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
                
                <form action="{{ route('employees.update', $employee->id) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Datos de Identidad -->
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 border-b pb-2">Información de Identidad</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Nombre Completo</label>
                                <input type="text" name="full_name" value="{{ old('full_name', $employee->full_name) }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. Juan Pérez Gómez" required>
                                <p class="text-[11px] text-slate-400 mt-1">Tal como aparece en su Constancia de Situación Fiscal.</p>
                                @error('full_name') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">RFC</label>
                                <input type="text" name="rfc" value="{{ old('rfc', $employee->rfc) }}" class="w-full rounded-md border-slate-300 shadow-sm uppercase focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. CACX7605101P8" required>
                                <p class="text-[11px] text-slate-400 mt-1">12 o 13 caracteres, sin espacios ni guiones.</p>
                                @error('rfc') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">CURP</label>
                                <input type="text" name="curp" value="{{ old('curp', $employee->curp) }}" class="w-full rounded-md border-slate-300 shadow-sm uppercase focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. GOVG750311HMCTRM00" required>
                                <p class="text-[11px] text-slate-400 mt-1">18 caracteres alfanuméricos.</p>
                                @error('curp') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Datos Laborales y Contractuales -->
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 border-b pb-2">Datos Laborales y Contractuales</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- NUEVO: Régimen SAT -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Régimen de Contratación (SAT)</label>
                                <select name="work_regime" required class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                                    <option value="02 - Sueldos y Salarios" @selected(old('work_regime', $employee->work_regime) == '02 - Sueldos y Salarios')>02 - Sueldos y Salarios</option>
                                    <option value="09 - Asimilados a Salarios" @selected(old('work_regime', $employee->work_regime) == '09 - Asimilados a Salarios')>09 - Asimilados a Salarios</option>
                                    <option value="13 - Indemnización" @selected(old('work_regime', $employee->work_regime) == '13 - Indemnización')>13 - Indemnización o Separación</option>
                                </select>
                                <p class="text-[11px] text-slate-400 mt-1">Obligatorio para el timbrado del recibo CFDI.</p>
                                @error('work_regime') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>

                            <!-- NUEVO: Periodicidad de Pago -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Periodicidad de Pago</label>
                                <select name="periodicity" required class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                                    <option value="mensual" @selected(old('periodicity', $employee->periodicity) == 'mensual')>Mensual (1 pago al mes)</option>
                                    <option value="quincenal" @selected(old('periodicity', $employee->periodicity) == 'quincenal')>Quincenal (2 pagos al mes)</option>
                                    <option value="semanal" @selected(old('periodicity', $employee->periodicity) == 'semanal')>Semanal (4 pagos al mes)</option>
                                </select>
                                <p class="text-[11px] text-slate-400 mt-1">Determina qué tarifa de ISR aplica automáticamente el motor.</p>
                                @error('periodicity') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>

                            <!-- Salario y Puesto -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Salario Base Mensual</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-slate-500">$</span>
                                    <input type="number" step="0.01" name="base_salary" value="{{ old('base_salary', $employee->base_salary) }}" class="w-full pl-8 rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. 12500.50" required>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1">Salario bruto MENSUAL. Si es semanal/quincenal, el sistema prorratea.</p>
                                @error('base_salary') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Puesto / Cargo</label>
                                <input type="text" name="position" value="{{ old('position', $employee->position) }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. Desarrollador Fullstack">
                                @error('position') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Fecha de Ingreso</label>
                                <input type="date" name="hire_date" value="{{ old('hire_date', $employee->hire_date ? \Carbon\Carbon::parse($employee->hire_date)->format('Y-m-d') : '') }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                                @error('hire_date') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Correo Electrónico</label>
                                <input type="email" name="email" value="{{ old('email', $employee->email) }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. empleado@email.com">
                                @error('email') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">CLABE Interbancaria</label>
                                <input type="text" name="clabe" value="{{ old('clabe', $employee->clabe) }}" maxlength="18" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. 012180001234567890">
                                @error('clabe') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">NSS</label>
                                <input type="text" name="nss" value="{{ old('nss', $employee->nss) }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. 12345678903" required>
                                @error('nss') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Código Postal</label>
                                <input type="text" name="cp" value="{{ old('cp', $employee->cp) }}" maxlength="5" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. 06000">
                                @error('cp') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Estatus Operativo -->
                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 flex items-center justify-between">
                        <div>
                            <span class="block font-bold text-slate-800 text-sm">Estatus del Empleado</span>
                            <span class="text-slate-500 text-xs">Si se desmarca, el empleado pasará a baja y no generará nómina.</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $employee->is_active) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="{{ route('employees.index') }}" class="px-4 py-2 bg-white border border-slate-300 rounded-md font-bold text-slate-700 hover:bg-slate-50 transition">
                            Cancelar
                        </a>
                        <button type="submit" class="px-6 py-2 bg-slate-900 text-white rounded-md font-bold hover:bg-slate-800 transition">
                            Actualizar Empleado
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>