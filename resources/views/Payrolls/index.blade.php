<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-white leading-tight">
                {{ __('Historial de Nóminas') }}
            </h2>

            <!-- Botón de Generación (Envuelto en Formulario POST) -->
            <form action="{{ route('payrolls.generate') }}" method="POST" class="form-confirm"
                data-title="¿Generar la nómina del mes?"
                data-text="Se calculará la nómina para todos los empleados activos y este proceso no se puede deshacer."
                data-confirm="Sí, calcular nómina">
                @csrf
                <button type="submit" class="bg-vera-green hover:bg-emerald-400 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Calcular Nómina del Mes
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Alertas de Éxito o Error -->
            @if (session('success'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('success') }}
            </div>
            @endif
            @if (session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('error') }}
            </div>
            @endif

            <!-- Filtro de Nómina -->
            <form method="GET" action="{{ route('payrolls.index') }}" id="searchForm" class="mb-6 bg-white p-5 rounded-lg shadow-sm border border-slate-200 flex flex-col sm:flex-row gap-4 items-end">

                <div class="w-full sm:w-64">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Filtrar por Mes Fiscal</label>
                    <input type="month" name="period" id="periodInput" value="{{ request('period') }}"
                        class="w-full border-slate-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm transition cursor-pointer">
                </div>

                <!-- Botón Limpiar -->
                <div class="flex gap-2 w-full sm:w-auto">
                    @if(request('period'))
                    <a href="{{ route('payrolls.index') }}" class="px-4 py-2.5 bg-white border border-slate-300 text-red-600 font-bold rounded-md hover:bg-red-50 transition shadow-sm text-sm flex items-center">
                        X Limpiar
                    </a>
                    @endif
                </div>
            </form>

            <!-- Motor Javascript de Auto-Filtro -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const periodInput = document.getElementById('periodInput');

                    periodInput.addEventListener('change', function() {
                        document.getElementById('searchForm').submit();
                    });
                });
            </script>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-vera-gray uppercase tracking-wider">Periodo</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-vera-gray uppercase tracking-wider">Empleados</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-vera-gray uppercase tracking-wider">Total Bruto</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-vera-gray uppercase tracking-wider">Retención ISR</th>
                                <!-- NUEVA COLUMNA: Retención IMSS -->
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-vera-gray uppercase tracking-wider">Retención IMSS</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-vera-gray uppercase tracking-wider">Total a Pagar (Neto)</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-vera-gray uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @forelse ($periods as $period)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-vera-dark">
                                    {{ $period->period_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-vera-gray font-bold">
                                    {{ $period->details->count() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-slate-500">
                                    ${{ number_format($period->total_gross, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-500 font-medium">
                                    - ${{ number_format($period->total_isr_retention, 2) }}
                                </td>
                                <!-- NUEVO DATO: Valor del IMSS -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-500 font-medium">
                                    - ${{ number_format($period->total_imss_employee, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-vera-green">
                                    ${{ number_format($period->total_net, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('payrolls.show', $period->id) }}" class="inline-flex items-center gap-2 rounded-lg border border-vera-green/20 bg-vera-green/10 px-3 py-2 text-vera-green transition hover:bg-vera-green hover:text-white">
                                        <svg class="h-2 w-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Ver detalles
                                    </a>
                                    <!-- Botón Envío Masivo -->
                                    <form action="{{ route('payrolls.send_emails', $period->id) }}" method="POST" class="inline-block form-confirm"
                                        data-title="¿Enviar recibos por correo?"
                                        data-text="Se enviará el PDF a todos los empleados que tengan un correo registrado."
                                        data-confirm="Sí, enviar masivamente">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-blue-500/20 bg-blue-500/10 px-3 py-2 text-blue-600 transition hover:bg-blue-600 hover:text-white">
                                            <svg class="h-2 w-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                            Enviar Recibos
                                        </button>
                                    </form>
                                    <!-- Botón Eliminar / Recalcular -->
                                    <form action="{{ route('payrolls.destroy', $period->id) }}" method="POST" class="inline-block ml-2 form-confirm"
                                        data-title="¿Eliminar el cálculo de este mes?"
                                        data-text="Esto borrará los recibos de todos los empleados y te permitirá volver a calcular la nómina desde cero."
                                        data-confirm="Sí, eliminar nómina">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-red-500/20 bg-red-500/10 px-3 py-2 text-red-600 transition hover:bg-red-600 hover:text-white">
                                            <svg class="h-2 w-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-vera-gray">
                                    No has calculado ninguna nómina aún. Da clic en el botón superior para comenzar.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginación -->
            <div class="mt-6">
                {{ $periods->links() }}
            </div>

        </div>
    </div>
</x-app-layout>