<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('invoices.index') }}" class="text-white hover:text-vera-green transition">
                &larr; Volver
            </a>
            <h2 class="font-semibold text-xl text-vera-dark leading-tight flex items-center gap-3">
                Factura: {{ $invoice->serie }} {{ $invoice->folio }}

                <!-- Badge de Cancelación -->
                @if($invoice->is_canceled)
                <span class="bg-red-100 text-red-800 text-[10px] font-black px-3 py-1 rounded-full uppercase border border-red-200 shadow-sm tracking-wider">
                    Cancelada
                </span>
                @endif

                <span class="text-xs font-mono text-slate-400 ml-auto">UUID: {{ $invoice->uuid }}</span>
            </h2>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Alertas -->
            @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-lg text-sm font-medium shadow-sm">
                {{ session('success') }}
            </div>
            @endif

            <!-- Tarjeta de Datos Generales -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-6 grid grid-cols-1 md:grid-cols-4 gap-6 relative">

                <!-- Marca de agua sutil si está cancelada -->
                @if($invoice->is_canceled)
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.03]">
                    <span class="text-8xl font-black text-red-900 transform -rotate-12">CANCELADA</span>
                </div>
                @endif

                <div class="relative z-10">
                    <p class="text-xs text-slate-400 uppercase font-bold">Emisor</p>
                    <p class="text-sm font-bold text-vera-dark">{{ $invoice->issuer_name }}</p>
                    <p class="text-xs font-mono text-slate-500">{{ $invoice->issuer_rfc }}</p>
                </div>
                <div class="relative z-10">
                    <p class="text-xs text-slate-400 uppercase font-bold">Receptor</p>
                    <p class="text-sm font-bold text-vera-dark">{{ $invoice->receiver_name }}</p>
                    <p class="text-xs font-mono text-slate-500">{{ $invoice->receiver_rfc }}</p>
                </div>
                <div class="relative z-10">
                    <p class="text-xs text-slate-400 uppercase font-bold">Datos Fiscales</p>
                    <p class="text-xs text-slate-600">Uso CFDI: <span class="font-semibold">{{ $invoice->uso_cfdi }}</span></p>
                    <p class="text-xs text-slate-600">Método de Pago: <span class="font-semibold">{{ $invoice->metodo_pago }}</span></p>
                    <p class="text-xs text-slate-600">Forma de Pago: <span class="font-semibold">{{ $invoice->forma_pago }}</span></p>
                </div>
                <div class="text-right border-l border-slate-100 pl-4 relative z-10">
                    <p class="text-xs text-slate-400 uppercase font-bold">Total Facturado</p>
                    <p class="text-2xl font-black {{ $invoice->is_canceled ? 'text-slate-400 line-through' : 'text-vera-green' }}">${{ number_format($invoice->total, 2) }} {{ $invoice->moneda }}</p>
                    <p class="text-xs text-slate-400">Emisión: {{ $invoice->issue_date->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <!-- Tabla de Conceptos -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-6 relative">
                <h3 class="text-base font-bold text-vera-dark mb-4">Conceptos y Productos</h3>
                <div class="overflow-x-auto relative z-10">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Clave SAT</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Descripción</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Estatus de Deducibilidad</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-slate-500 uppercase">Importe</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @forelse($evaluatedItems as $item)
                            <tr class="{{ $invoice->is_canceled ? 'opacity-50' : '' }}">
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $item['clave_sat'] }}</td>
                                <td class="px-4 py-3 font-medium text-vera-dark">{{ $item['descripcion'] }} <br> <span class="text-xs text-slate-400 font-normal">{{ $item['cantidad'] }} {{ $item['unidad'] }}</span></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider
                                        {{ $item['status'] === 'rojo' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $item['status'] === 'amarillo' ? 'bg-amber-100 text-amber-800' : '' }}
                                        {{ $item['status'] === 'verde' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                    ">
                                        {{ $item['status'] }}
                                    </span>
                                    <p class="text-[10px] text-slate-500 mt-1 max-w-xs leading-tight">{{ $item['reason'] }}</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-vera-dark">${{ number_format($item['importe'], 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-4 py-4 text-center text-sm text-slate-400">
                                    Esta factura no tiene conceptos registrados.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Totales al pie -->
                <div class="mt-6 border-t border-slate-100 pt-4 flex justify-between items-end relative z-10">

                    <!-- Botón de Cancelación -->
                    <div>
                        @if(!$invoice->is_canceled)
                        <form action="{{ route('invoices.cancel', $invoice->id) }}" method="POST" class="form-confirm"
                            data-title="¿Anular esta factura?"
                            data-text="Al cancelarla, sus montos ya no sumarán en tu inteligencia financiera ni en el cálculo de impuestos."
                            data-confirm="Sí, anular factura">
                            @csrf
                            <button type="submit" class="flex items-center gap-2 px-4 py-2 bg-white border border-red-200 text-red-600 font-bold rounded-lg hover:bg-red-50 hover:border-red-300 transition shadow-sm text-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Anular Factura
                            </button>
                        </form>
                        @else
                        <div class="px-4 py-2 bg-slate-100 text-slate-500 font-bold rounded-lg border border-slate-200 text-xs flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                            </svg>
                            Sin Efectos Fiscales
                        </div>
                        @endif
                    </div>

                    <div class="w-64 space-y-2 text-sm {{ $invoice->is_canceled ? 'opacity-50' : '' }}">
                        <div class="flex justify-between text-slate-600">
                            <span>Subtotal:</span>
                            <span>${{ number_format($invoice->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>IVA Trasladado:</span>
                            <span>${{ number_format($invoice->iva, 2) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-vera-dark text-base border-t border-slate-200 pt-2">
                            <span>Total:</span>
                            <span class="{{ $invoice->is_canceled ? 'line-through text-slate-400' : 'text-vera-green' }}">${{ number_format($invoice->total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>