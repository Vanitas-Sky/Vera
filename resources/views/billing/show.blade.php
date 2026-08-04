<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Comprobante Fiscal Generado') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-8 text-center">

                <!-- Ícono de Éxito -->
                <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4 shadow-inner">
                    <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <h3 class="text-2xl font-black text-vera-dark mb-1">¡Factura Timbrada con Éxito!</h3>
                <p class="text-sm text-slate-500 font-mono mb-6">Folio Fiscal (UUID): {{ $invoice->uuid }}</p>

                <!-- Tarjeta de Resumen -->
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-6 mb-8 text-left grid grid-cols-1 md:grid-cols-2 gap-4 shadow-sm">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Receptor del Comprobante</p>
                        <p class="font-bold text-slate-700 text-lg leading-tight">{{ $invoice->receiver_name }}</p>
                        <p class="text-xs text-slate-500 font-mono mt-1">RFC: {{ $invoice->receiver_rfc }}</p>
                    </div>
                    <div class="md:text-right flex flex-col justify-center">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Monto Total Facturado</p>
                        <p class="text-3xl font-black text-vera-dark">${{ number_format($invoice->total, 2) }} <span class="text-sm text-slate-500 font-normal">MXN</span></p>
                    </div>
                </div>

                <!-- Botón Unificado de Descarga -->
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <!-- Botón Principal: Descarga Paquete ZIP (PDF + XML) -->
                    <a href="{{ route('billing.zip', $invoice->id) }}" class="flex items-center justify-center gap-2 px-8 py-3 bg-vera-green text-white font-bold rounded-lg hover:bg-emerald-700 transition shadow-sm w-full sm:w-auto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Descargar Factura (PDF y XML)
                    </a>

                    <!-- Botón Secundario: Solo XML -->
                    <a href="{{ route('billing.xml', $invoice->id) }}" class="flex items-center justify-center gap-2 px-6 py-3 bg-slate-800 text-white font-bold rounded-lg hover:bg-slate-700 transition shadow-sm w-full sm:w-auto text-xs">
                        Solo XML
                    </a>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-100">
                    <a href="{{ route('dashboard') }}" class="text-sm font-bold text-slate-500 hover:text-vera-dark transition flex items-center justify-center gap-2">
                        &larr; Volver al Dashboard Principal
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>