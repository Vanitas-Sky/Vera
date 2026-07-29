<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('opex.index') }}" class="text-vera-gray hover:text-vera-dark transition">
                &larr; Volver
            </a>
            <h2 class="font-semibold text-xl text-vera-dark leading-tight">
                {{ __('Registrar Gasto Fijo (OpEx)') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-8">
                
                <form action="{{ route('opex.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <!-- Bloque 1: Datos Generales -->
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2 mb-4">Datos del Servicio</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Nombre del Proveedor</label>
                            <input type="text" name="provider_name" value="{{ old('provider_name') }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-vera-green focus:ring-vera-green" placeholder="Ej. Telmex, AWS, GNP Seguros" required>
                            @error('provider_name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Categoría</label>
                            <select name="category" class="w-full rounded-md border-slate-300 shadow-sm focus:border-vera-green focus:ring-vera-green bg-slate-50" required>
                                <option value="">Selecciona...</option>
                                <option value="renta" {{ old('category') == 'renta' ? 'selected' : '' }}>Renta Inmobiliaria</option>
                                <option value="seguro" {{ old('category') == 'seguro' ? 'selected' : '' }}>Póliza de Seguro</option>
                                <option value="servicio" {{ old('category') == 'servicio' ? 'selected' : '' }}>Servicios (Luz, Agua, Internet)</option>
                                <option value="licencia" {{ old('category') == 'licencia' ? 'selected' : '' }}>Licencias de Software</option>
                                <option value="otro" {{ old('category') == 'otro' ? 'selected' : '' }}>Otro</option>
                            </select>
                            @error('category') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Descripción corta</label>
                            <input type="text" name="description" value="{{ old('description') }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-vera-green focus:ring-vera-green" placeholder="Ej. Renta de oficina sucursal centro">
                            @error('description') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Bloque 2: Datos de Cobro y Vigencia -->
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2 mb-4 mt-8">Cobro y Vigencia</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Monto Mensual (Antes de IVA)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-slate-500 sm:text-sm">$</span>
                                </div>
                                <input type="number" step="0.01" name="monthly_amount" value="{{ old('monthly_amount') }}" class="w-full pl-7 rounded-md border-slate-300 shadow-sm focus:border-vera-green focus:ring-vera-green font-bold text-vera-dark" placeholder="0.00" required>
                            </div>
                            @error('monthly_amount') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Día exacto de pago</label>
                            <input type="number" name="due_day" min="1" max="31" value="{{ old('due_day') }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-vera-green focus:ring-vera-green" placeholder="Del 1 al 31" required>
                            @error('due_day') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Fecha de inicio de contrato</label>
                            <input type="date" name="contract_start_date" value="{{ old('contract_start_date') }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-vera-green focus:ring-vera-green text-slate-600">
                            @error('contract_start_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Fecha de fin (Expiración)</label>
                            <input type="date" name="contract_end_date" value="{{ old('contract_end_date') }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-vera-green focus:ring-vera-green text-slate-600">
                            <p class="text-[10px] text-slate-400 mt-1">Déjalo en blanco si el servicio no tiene fecha de término.</p>
                            @error('contract_end_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end pt-4 border-t border-slate-100">
                        <button type="submit" class="bg-vera-green text-white px-6 py-2 rounded-lg hover:bg-emerald-700 transition font-bold shadow-sm">
                            Guardar Contrato
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>