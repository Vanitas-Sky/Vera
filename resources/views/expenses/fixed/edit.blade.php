<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('opex.index') }}" class="text-white hover:text-vera-green transition font-bold">
                &larr; Volver
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">
                {{ __('Editar Contrato / Gasto Fijo') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-8">
                
                <form action="{{ route('opex.update', $expense->id) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Proveedor y Categoría -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Nombre del Proveedor</label>
                            <input type="text" name="provider_name" value="{{ old('provider_name', $expense->provider_name) }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                            @error('provider_name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Categoría</label>
                            <select name="category" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                                <option value="Renta" {{ old('category', $expense->category) == 'Renta' ? 'selected' : '' }}>Renta (Inmuebles)</option>
                                <option value="Seguro" {{ old('category', $expense->category) == 'Seguro' ? 'selected' : '' }}>Seguros / Fianzas</option>
                                <option value="Servicio" {{ old('category', $expense->category) == 'Servicio' ? 'selected' : '' }}>Servicios (Internet, Luz, Agua)</option>
                                <option value="Software" {{ old('category', $expense->category) == 'Software' ? 'selected' : '' }}>Licencias de Software</option>
                                <option value="Otro" {{ old('category', $expense->category) == 'Otro' ? 'selected' : '' }}>Otro</option>
                            </select>
                            @error('category') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Concepto / Descripción Breve</label>
                        <input type="text" name="description" value="{{ old('description', $expense->description) }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                        @error('description') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <!-- Finanzas: Monto y Día de Pago -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-4 rounded-lg border border-slate-100">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Monto Mensual (Sin IVA)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-slate-500">$</span>
                                <input type="number" step="0.01" name="monthly_amount" value="{{ old('monthly_amount', $expense->monthly_amount) }}" class="w-full pl-8 rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                            </div>
                            @error('monthly_amount') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Día de vencimiento (1 - 31)</label>
                            <input type="number" name="due_day" min="1" max="31" value="{{ old('due_day', $expense->due_day) }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                            @error('due_day') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Vigencia -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Inicio del Contrato (Opcional)</label>
                            <input type="date" name="contract_start_date" value="{{ old('contract_start_date', $expense->contract_start_date ? \Carbon\Carbon::parse($expense->contract_start_date)->format('Y-m-d') : '') }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Fin del Contrato (Opcional)</label>
                            <input type="date" name="contract_end_date" value="{{ old('contract_end_date', $expense->contract_end_date ? \Carbon\Carbon::parse($expense->contract_end_date)->format('Y-m-d') : '') }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="{{ route('opex.index') }}" class="px-4 py-2 bg-white border border-slate-300 rounded-md font-bold text-slate-700 hover:bg-slate-50 transition">
                            Cancelar
                        </a>
                        <button type="submit" class="px-6 py-2 bg-slate-800 text-white rounded-md font-bold hover:bg-slate-700 transition">
                            Guardar Cambios
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>