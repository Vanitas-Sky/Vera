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

            <!-- Formulario de Subida -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-6">
                <form action="{{ route('invoices.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="flex items-center justify-center w-full">
                        <label for="xml_file" class="relative flex flex-col items-center justify-center w-full h-40 border-2 border-slate-300 border-dashed rounded-lg cursor-pointer bg-slate-50 hover:bg-slate-100 transition overflow-hidden">

                            <div class="flex flex-col items-center justify-center pt-5 pb-6 pointer-events-none">
                                <svg class="w-8 h-8 mb-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <p class="mb-2 text-sm text-slate-500"><span class="font-semibold text-vera-green">Haz clic para subir</span> o arrastra y suelta tu archivo aquí</p>
                                <p class="text-xs text-slate-400">Solo archivos XML del SAT (CFDI 3.3 o 4.0)</p>
                            </div>

                            <input id="xml_file" name="xml_file" type="file" accept=".xml" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required onchange="this.form.submit()" title="Arrastra tu factura aquí" />

                        </label>
                    </div>
                    @error('xml_file')
                    <p class="mt-2 text-xs text-red-500 text-center">{{ $message }}</p>
                    @enderror
                </form>
            </div>

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

        </div>
    </div>
</x-app-layout>