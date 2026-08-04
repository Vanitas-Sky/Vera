<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('employees.show', $employee->id) }}" class="text-slate-500 hover:text-vera-green transition font-medium">
                &larr; Volver al Perfil
            </a>
            <h2 class="font-semibold text-xl text-vera-dark leading-tight">
                Deducciones Personalizadas
            </h2>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Alertas de Éxito o Error -->
            @if (session('success'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-lg text-sm font-medium flex items-center gap-2 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('success') }}
            </div>
            @endif

            <!-- Cabecera del Empleado -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 mb-6 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold text-vera-dark">{{ $employee->full_name }}</h3>
                    <p class="text-sm text-slate-500 mt-1">RFC: {{ $employee->rfc }} | Salario Base Mensual: <span class="font-bold text-slate-700">${{ number_format($employee->base_salary, 2) }}</span></p>
                </div>
                <div class="text-right hidden md:block">
                    <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-bold uppercase tracking-wider">Gestión de Pasivos</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- COLUMNA IZQUIERDA: Formulario de Nueva Deducción -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 sticky top-6">
                        <h4 class="font-bold text-vera-dark border-b border-slate-100 pb-3 mb-4">Agregar Deducción</h4>
                        
                        <form action="{{ route('employees.deductions.store', $employee->id) }}" method="POST">
                            @csrf
                            
                            <!-- Concepto -->
                            <div class="mb-4">
                                <label for="description" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Concepto / Descripción</label>
                                <input type="text" name="description" id="description" required placeholder="Ej. Crédito Infonavit, Préstamo..."
                                    class="w-full border-slate-300 rounded-md focus:ring-vera-green focus:border-vera-green text-sm shadow-sm" value="{{ old('description') }}">
                                @error('description') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <!-- Clave SAT -->
                            <div class="mb-4">
                                <label for="sat_key" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Clave SAT (3 dígitos)</label>
                                <select name="sat_key" id="sat_key" required class="w-full border-slate-300 rounded-md focus:ring-vera-green focus:border-vera-green text-sm shadow-sm">
                                    <option value="">Selecciona una clave...</option>
                                    <option value="004" @selected(old('sat_key') == '004')>004 - Préstamo empresa</option>
                                    <option value="007" @selected(old('sat_key') == '007')>007 - Pensión alimenticia</option>
                                    <option value="009" @selected(old('sat_key') == '009')>009 - Préstamo Infonavit</option>
                                    <option value="010" @selected(old('sat_key') == '010')>010 - Pago por crédito de vivienda</option>
                                    <option value="019" @selected(old('sat_key') == '019')>019 - Cuota sindical</option>
                                    <option value="000" @selected(old('sat_key') == '000')>000 - Otra deducción</option>
                                </select>
                                @error('sat_key') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <!-- Tipo de Cálculo -->
                            <div class="mb-4">
                                <label for="amount_type" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Método de Cálculo</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <label class="cursor-pointer">
                                        <input type="radio" name="amount_type" value="fixed" class="peer sr-only" @checked(old('amount_type', 'fixed') == 'fixed')>
                                        <div class="text-center px-2 py-2 border border-slate-200 rounded-md text-xs font-bold text-slate-500 peer-checked:bg-indigo-50 peer-checked:border-indigo-500 peer-checked:text-indigo-700 transition">
                                            $ Fijo
                                        </div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="amount_type" value="percentage" class="peer sr-only" @checked(old('amount_type') == 'percentage')>
                                        <div class="text-center px-2 py-2 border border-slate-200 rounded-md text-xs font-bold text-slate-500 peer-checked:bg-indigo-50 peer-checked:border-indigo-500 peer-checked:text-indigo-700 transition">
                                            % Neto
                                        </div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="amount_type" value="vsm" class="peer sr-only" @checked(old('amount_type') == 'vsm')>
                                        <div class="text-center px-2 py-2 border border-slate-200 rounded-md text-xs font-bold text-slate-500 peer-checked:bg-indigo-50 peer-checked:border-indigo-500 peer-checked:text-indigo-700 transition" title="Veces Salario Mínimo (Infonavit)">
                                            VSM
                                        </div>
                                    </label>
                                </div>
                                @error('amount_type') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <!-- Monto/Valor -->
                            <div class="mb-6">
                                <label for="amount" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Valor Numérico</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-400 sm:text-sm">#</span>
                                    </div>
                                    <input type="number" step="0.0001" min="0.01" name="amount" id="amount" required placeholder="Ej. 500.00 o 25.5"
                                        class="pl-7 w-full border-slate-300 rounded-md focus:ring-vera-green focus:border-vera-green text-sm shadow-sm font-mono" value="{{ old('amount') }}">
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1 leading-tight">Si es Fijo pon pesos. Si es % Neto pon del 1 al 100. Si es VSM pon el factor de descuento.</p>
                                @error('amount') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <button type="submit" class="w-full bg-vera-dark hover:bg-slate-800 text-white font-bold py-2.5 px-4 rounded-lg shadow transition text-sm">
                                + Guardar Deducción
                            </button>
                        </form>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: Tabla de Historial -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 bg-slate-50">
                            <h4 class="font-bold text-vera-dark">Historial de Retenciones Asignadas</h4>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-white">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Concepto</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Regla / Valor</th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">Estado</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-100">
                                    @forelse($deductions as $deduction)
                                    <tr class="{{ !$deduction->is_active ? 'bg-slate-50 opacity-75' : 'hover:bg-slate-50 transition' }}">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-bold text-vera-dark">{{ $deduction->description }}</div>
                                            <div class="text-xs text-slate-500 font-mono mt-0.5">SAT: {{ $deduction->sat_key }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($deduction->amount_type == 'fixed')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-700">
                                                    Monto Fijo: <span class="text-red-500">-${{ number_format($deduction->amount, 2) }}</span>
                                                </span>
                                            @elseif($deduction->amount_type == 'percentage')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-indigo-50 text-indigo-700">
                                                    {{ rtrim(rtrim(number_format($deduction->amount, 4), '0'), '.') }}% del Salario Neto
                                                </span>
                                            @elseif($deduction->amount_type == 'vsm')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-amber-50 text-amber-700">
                                                    Factor VSM: {{ $deduction->amount }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-center whitespace-nowrap">
                                            @if($deduction->is_active)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Cobrando
                                                </span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-200 text-slate-600">
                                                    Detenida
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right whitespace-nowrap text-sm font-medium">
                                            @if($deduction->is_active)
                                            <form action="{{ route('employees.deductions.destroy', [$employee->id, $deduction->id]) }}" method="POST" class="inline-block form-confirm"
                                                data-title="¿Detener esta deducción?"
                                                data-text="Ya no se le cobrará este monto en la próxima nómina. El historial pasado no se afectará."
                                                data-confirm="Sí, detener cobro">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-xs uppercase tracking-wider transition bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-md">
                                                    Detener
                                                </button>
                                            </form>
                                            @else
                                            <span class="text-xs text-slate-400 italic">Inactiva</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-sm text-slate-500">
                                            <svg class="mx-auto h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Este empleado no tiene deducciones personalizadas asignadas.
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
    </div>
</x-app-layout>