<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-semibold text-xl text-vera-dark leading-tight">
                {{ __('Conciliación Bancaria y Discrepancia') }}
            </h2>

            <!-- Filtro de Tiempo (Igual al Dashboard principal) -->
            <form action="{{ route('conciliations.index') }}" method="GET" class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-sm">
                <label for="period" class="text-xs font-bold text-slate-400 uppercase tracking-wider">Mes Fiscal:</label>
                <input type="month" id="period" name="period" value="{{ $period }}" 
                       class="border-none bg-transparent focus:ring-0 text-sm font-bold text-vera-dark cursor-pointer py-1"
                       onchange="this.form.submit()">
            </form>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Alertas -->
            @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('success') }}
            </div>
            @endif
            @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('error') }}
            </div>
            @endif

            <!-- Formulario de Subida -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-6">
                <form action="{{ route('conciliations.preview') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="flex items-center justify-center w-full">
                        <label for="bank_file" class="relative flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-lg cursor-pointer bg-slate-50 hover:bg-slate-100 transition overflow-hidden">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6 pointer-events-none">
                                <svg class="w-8 h-8 mb-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <p class="mb-1 text-sm text-slate-500"><span class="font-semibold text-blue-600">Haz clic para subir tu estado de cuenta</span></p>
                                <p class="text-xs text-slate-400">Formato admitido: CSV crudo desde el portal de tu banco.</p>
                            </div>
                            <input id="bank_file" name="bank_file" type="file" accept=".csv" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required onchange="this.form.submit()" />
                        </label>
                    </div>
                </form>
            </div>

            <!-- Panel de Matemáticas de Discrepancia -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <div class="bg-white p-6 rounded-lg border border-slate-200 shadow-sm">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Gastos Facturados (SAT)</p>
                    <p class="text-3xl font-black text-slate-700 mt-2">${{ number_format($satExpenses, 2) }}</p>
                    <p class="text-xs text-slate-500 mt-1">Egresos registrados en tu Bóveda XML.</p>
                </div>

                <div class="bg-white p-6 rounded-lg border border-slate-200 shadow-sm">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Retiros Bancarios Reales</p>
                    <p class="text-3xl font-black text-slate-700 mt-2">${{ number_format($bankWithdrawals, 2) }}</p>
                    <p class="text-xs text-slate-500 mt-1">Dinero que efectivamente salió de tu cuenta.</p>
                </div>

                <div class="p-6 rounded-lg border shadow-sm flex flex-col justify-center {{ $discrepancy > 0 ? 'bg-red-50 border-red-200' : 'bg-emerald-50 border-emerald-200' }}">
                    <p class="text-xs font-bold uppercase tracking-wider {{ $discrepancy > 0 ? 'text-red-500' : 'text-emerald-600' }}">Discrepancia Fiscal (Riesgo)</p>
                    <p class="text-4xl font-black mt-2 {{ $discrepancy > 0 ? 'text-red-600' : 'text-emerald-700' }}">
                        ${{ number_format($discrepancy, 2) }}
                    </p>
                    @if($discrepancy > 0)
                        <p class="text-xs text-red-700 mt-1 font-semibold">⚠️ Peligro: Tienes retiros bancarios sin una factura que los ampare.</p>
                    @else
                        <p class="text-xs text-emerald-800 mt-1 font-semibold">✓ Saludable: Tienes facturas suficientes para amparar tus salidas.</p>
                    @endif
                </div>

            </div>

            <!-- Buscador y Filtros Bancarios (Live Search) -->
            <form method="GET" action="{{ route('conciliations.index') }}" id="searchForm" class="mb-6 bg-white p-5 rounded-lg shadow-sm border border-slate-200 flex flex-col sm:flex-row gap-4 items-end">
                
                <!-- Mantener el filtro de periodo oculto para que no se pierda al buscar texto -->
                <input type="hidden" name="period" value="{{ $period }}">

                <!-- Buscador de Texto (Concepto) -->
                <div class="flex-1 w-full">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Buscar Concepto Bancario</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" name="search" id="searchInput" value="{{ request('search') }}" placeholder="Ej. Amazon, Comisión, Gasolina..." 
                            class="w-full pl-10 border-slate-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm transition">
                    </div>
                </div>

                <!-- Filtro de Tipo de Movimiento -->
                <div class="w-full sm:w-48">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tipo de Movimiento</label>
                    <select name="type" id="typeSelect" class="w-full border-slate-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm cursor-pointer">
                        <option value="todos" {{ request('type') == 'todos' ? 'selected' : '' }}>Todos (Estado de Cuenta)</option>
                        <option value="retiros" {{ request('type') == 'retiros' ? 'selected' : '' }}>Solo Retiros (Gastos)</option>
                        <option value="depositos" {{ request('type') == 'depositos' ? 'selected' : '' }}>Solo Depósitos (Ingresos)</option>
                    </select>
                </div>

                <!-- Botón Limpiar -->
                <div class="flex gap-2 w-full sm:w-auto">
                    @if(request('search') || request('type', 'todos') != 'todos')
                        <a href="{{ route('conciliations.index', ['period' => $period]) }}" class="px-4 py-2.5 bg-white border border-slate-300 text-red-600 font-bold rounded-md hover:bg-red-50 transition shadow-sm text-sm flex items-center">
                            X Limpiar
                        </a>
                    @endif
                </div>
            </form>

            <!-- Motor Javascript de Live Search (Debounce) -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const form = document.getElementById('searchForm');
                    const searchInput = document.getElementById('searchInput');
                    const typeSelect = document.getElementById('typeSelect');
                    let timeout = null;

                    function submitForm() { form.submit(); }

                    searchInput.addEventListener('input', function() {
                        clearTimeout(timeout);
                        timeout = setTimeout(submitForm, 600); 
                    });

                    typeSelect.addEventListener('change', submitForm);
                });
            </script>

            <!-- Tabla de Transacciones -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                    <h3 class="text-sm font-bold text-vera-dark uppercase tracking-wider">Movimientos Importados del Mes</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Concepto</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">Salida (Cargo)</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">Entrada (Abono)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100 text-sm">
                            @forelse($transactions as $tx)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 font-mono text-slate-600">{{ $tx->transaction_date->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 font-medium text-vera-dark">{{ $tx->description }}</td>
                                <td class="px-6 py-4 text-right font-bold text-red-500">{{ $tx->withdrawal > 0 ? '$'.number_format($tx->withdrawal, 2) : '-' }}</td>
                                <td class="px-6 py-4 text-right font-bold text-emerald-600">{{ $tx->deposit > 0 ? '$'.number_format($tx->deposit, 2) : '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-sm text-slate-400">
                                    No hay movimientos bancarios registrados para este mes. Sube tu CSV para conciliar.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginación Bancaria -->
            <div class="mt-6">
                {{ $transactions->links() }}
            </div>
            
        </div>
    </div>
</x-app-layout>