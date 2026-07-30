<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('conciliations.index') }}" class="text-vera-gray hover:text-vera-dark transition">
                &larr; Cancelar Importación
            </a>
            <h2 class="font-semibold text-xl text-vera-dark leading-tight">
                Mapeo de Columnas del Banco
            </h2>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-8">
                
                <div class="mb-6 pb-6 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-vera-dark">Hemos leído tu archivo exitosamente</h3>
                    <p class="text-sm text-slate-500 mt-1">El sistema detectó <b>{{ count($headers) }} columnas</b> en tu archivo. Para importar correctamente el dinero, indícanos qué columna corresponde a cada dato.</p>
                </div>

                <form action="{{ route('conciliations.import') }}" method="POST">
                    @csrf
                    
                    <!-- Pasamos la ruta del archivo temporal oculto -->
                    <input type="hidden" name="path" value="{{ $path }}">

                    <div class="space-y-6">
                        <!-- Fecha -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                            <label class="md:col-span-1 text-sm font-bold text-slate-700">1. Fecha de Operación:</label>
                            <div class="md:col-span-2">
                                <select name="col_date" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 text-sm" required>
                                    <option value="">Selecciona la columna...</option>
                                    @foreach($headers as $index => $header)
                                        <option value="{{ $index }}">{{ $header }} (Columna {{ $index + 1 }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Concepto -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                            <label class="md:col-span-1 text-sm font-bold text-slate-700">2. Concepto / Descripción:</label>
                            <div class="md:col-span-2">
                                <select name="col_description" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 text-sm" required>
                                    <option value="">Selecciona la columna...</option>
                                    @foreach($headers as $index => $header)
                                        <option value="{{ $index }}">{{ $header }} (Columna {{ $index + 1 }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Cargos / Retiros -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center bg-red-50 p-4 rounded-lg border border-red-100">
                            <label class="md:col-span-1 text-sm font-bold text-red-700">3. Salidas (Retiros):</label>
                            <div class="md:col-span-2">
                                <select name="col_withdrawal" class="w-full border-red-300 rounded-lg shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 text-sm" required>
                                    <option value="">Selecciona la columna...</option>
                                    @foreach($headers as $index => $header)
                                        <option value="{{ $index }}">{{ $header }} (Columna {{ $index + 1 }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Abonos / Depósitos -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center bg-emerald-50 p-4 rounded-lg border border-emerald-100">
                            <label class="md:col-span-1 text-sm font-bold text-emerald-700">4. Entradas (Depósitos):</label>
                            <div class="md:col-span-2">
                                <select name="col_deposit" class="w-full border-emerald-300 rounded-lg shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-200 text-sm" required>
                                    <option value="">Selecciona la columna...</option>
                                    @foreach($headers as $index => $header)
                                        <option value="{{ $index }}">{{ $header }} (Columna {{ $index + 1 }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition shadow-sm">
                            Importar Transacciones y Conciliar
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</x-app-layout>