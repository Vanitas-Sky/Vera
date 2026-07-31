<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-vera-dark leading-tight">
            {{ __('Bóveda de Facturas (CFDI)') }}
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
            @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('error') }}
            </div>
            @endif

            <!-- Formulario de Subida (Modificado para aceptar ZIP y XML) -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-6">
                <form action="{{ route('invoices.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="flex items-center justify-center w-full">
                        <label for="xml_file" class="relative flex flex-col items-center justify-center w-full h-40 border-2 border-slate-300 border-dashed rounded-lg cursor-pointer bg-slate-50 hover:bg-slate-100 transition overflow-hidden">

                            <div class="flex flex-col items-center justify-center pt-5 pb-6 pointer-events-none">
                                <svg class="w-8 h-8 mb-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <p class="mb-2 text-sm text-slate-500"><span class="font-semibold text-vera-green">Haz clic para subir</span> o arrastra tu archivo aquí</p>
                                <p class="text-xs text-slate-400">Sube un archivo XML individual o un paquete <b>.ZIP</b> con múltiples facturas</p>
                            </div>

                            <!-- Cambiamos accept para permitir .zip -->
                            <input id="xml_file" name="xml_file" type="file" accept=".xml,.zip" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required onchange="this.form.submit()" title="Arrastra tu archivo aquí" />

                        </label>
                    </div>
                    @error('xml_file')
                    <p class="mt-2 text-xs text-red-500 text-center">{{ $message }}</p>
                    @enderror
                </form>
            </div>

            <!-- Buscador y Filtros Inteligentes (Live Search) -->
            <form method="GET" action="{{ route('invoices.index') }}" id="searchForm" class="mb-6 bg-white p-5 rounded-lg shadow-sm border border-slate-200 flex flex-col sm:flex-row gap-4 items-end">

                <!-- Buscador Profundo -->
                <div class="flex-1 w-full">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Buscar (Nombre, RFC, UUID, Concepto)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" name="search" id="searchInput" value="{{ request('search') }}" placeholder="Escribe para filtrar automáticamente..."
                            class="w-full pl-10 border-slate-300 rounded-md focus:ring-vera-green focus:border-vera-green text-sm shadow-sm transition">
                    </div>
                </div>

                <!-- Filtro de Tipo -->
                <div class="w-full sm:w-40">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tipo</label>
                    <select name="type" id="typeSelect" class="w-full border-slate-300 rounded-md focus:ring-vera-green focus:border-vera-green text-sm shadow-sm cursor-pointer">
                        <option value="todas" {{ request('type') == 'todas' ? 'selected' : '' }}>Todos</option>
                        <option value="I" {{ request('type') == 'I' ? 'selected' : '' }}>Ingresos</option>
                        <option value="E" {{ request('type') == 'E' ? 'selected' : '' }}>Egresos</option>
                    </select>
                </div>

                <!-- Filtro de Estatus -->
                <div class="w-full sm:w-40">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Estatus SAT</label>
                    <select name="status" id="statusSelect" class="w-full border-slate-300 rounded-md focus:ring-vera-green focus:border-vera-green text-sm shadow-sm cursor-pointer">
                        <option value="activas" {{ request('status') == 'activas' ? 'selected' : '' }}>Válidas</option>
                        <option value="canceladas" {{ request('status') == 'canceladas' ? 'selected' : '' }}>Canceladas</option>
                        <option value="todas" {{ request('status') == 'todas' ? 'selected' : '' }}>Histórico</option>
                    </select>
                </div>

                <!-- Botón Limpiar -->
                <div class="flex gap-2 w-full sm:w-auto">
                    @if(request('search') || request('status', 'activas') != 'activas' || request('type', 'todas') != 'todas')
                    <a href="{{ route('invoices.index') }}" class="px-4 py-2.5 bg-white border border-slate-300 text-red-600 font-bold rounded-md hover:bg-red-50 transition shadow-sm text-sm flex items-center">
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
                    const statusSelect = document.getElementById('statusSelect');
                    let timeout = null;

                    // Función que envía el formulario
                    function submitForm() {
                        form.submit();
                    }

                    // Escuchar escritura en el buscador con retraso de 600ms (Debounce)
                    searchInput.addEventListener('input', function() {
                        clearTimeout(timeout);
                        timeout = setTimeout(submitForm, 600);
                    });

                    // Escuchar cambios inmediatos en los selects
                    typeSelect.addEventListener('change', submitForm);
                    statusSelect.addEventListener('change', submitForm);
                });
            </script>

            <!-- Tabla de Resultados -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-vera-gray uppercase tracking-wider">Fecha / Tipo</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-vera-gray uppercase tracking-wider">Emisor</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-vera-gray uppercase tracking-wider">Receptor</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-vera-gray uppercase tracking-wider">Concepto Principal</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-vera-gray uppercase tracking-wider">Estado</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-vera-gray uppercase tracking-wider">Total (IVA inc.)</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-vera-gray uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @forelse ($invoices as $invoice)
                            <tr class="{{ $invoice->is_canceled ? 'opacity-60 bg-slate-50' : 'hover:bg-slate-50 transition' }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-vera-gray">
                                    <div class="font-medium text-vera-dark">{{ $invoice->issue_date->format('d/m/Y') }}</div>
                                    <div>
                                        @if($invoice->type === 'I')
                                        <span class="px-2 inline-flex text-[10px] leading-4 font-semibold rounded-full bg-emerald-100 text-emerald-800">Ingreso</span>
                                        @elseif($invoice->type === 'E')
                                        <span class="px-2 inline-flex text-[10px] leading-4 font-semibold rounded-full bg-red-100 text-red-800">Egreso</span>
                                        @else
                                        <span class="px-2 inline-flex text-[10px] leading-4 font-semibold rounded-full bg-slate-100 text-slate-800">{{ $invoice->type }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-slate-600" title="{{ $invoice->issuer_name }}">
                                    {{ $invoice->issuer_rfc }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-slate-600" title="{{ $invoice->receiver_name }}">
                                    {{ $invoice->receiver_rfc }}
                                </td>

                                <!-- Nueva Columna: Concepto Principal -->
                                <td class="px-6 py-4 text-sm text-slate-600 max-w-[200px]">
                                    @php
                                    // Extraemos los items de forma segura
                                    $items = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
                                    $primerConcepto = $items[0]['descripcion'] ?? 'Sin descripción';
                                    $totalConceptos = is_array($items) ? count($items) : 0;
                                    @endphp
                                    <div class="truncate font-medium text-vera-dark" title="{{ $primerConcepto }}">
                                        {{ \Illuminate\Support\Str::limit($primerConcepto, 35) }}
                                    </div>
                                    @if($totalConceptos > 1)
                                    <div class="text-[10px] text-slate-400 font-bold mt-0.5">
                                        +{{ $totalConceptos - 1 }} concepto(s) extra
                                    </div>
                                    @endif
                                </td>

                                <!-- Nueva Columna: Estado -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($invoice->is_canceled)
                                    <span class="px-2.5 py-1 inline-flex text-[10px] font-black rounded-full bg-red-100 text-red-800 border border-red-200 shadow-sm">CANCELADA</span>
                                    @else
                                    <span class="px-2.5 py-1 inline-flex text-[10px] font-bold rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200">Vigente</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-black {{ $invoice->is_canceled ? 'text-slate-400 line-through' : 'text-vera-dark' }}">
                                    ${{ number_format($invoice->total, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('invoices.show', $invoice->id) }}" class="text-vera-green hover:text-emerald-700 font-semibold">Ver detalles &rarr;</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-vera-gray">
                                    Aún no has subido ninguna factura. Arrastra tu primer XML en la zona superior.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginación Inteligente -->
            <div class="mt-6">
                {{ $invoices->links() }}
            </div>

        </div>
    </div>
</x-app-layout>