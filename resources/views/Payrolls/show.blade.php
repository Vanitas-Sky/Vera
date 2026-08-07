<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('payrolls.index') }}" class="text-white hover:text-vera-green transition font-medium">
                &larr; Volver
            </a>
            <h2 class="font-semibold text-xl text-vera-dark leading-tight">
                {{ $period->period_name }} <span class="text-sm font-normal text-white ml-2">(Radiografía de Cálculos Consolidados)</span>
            </h2>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Resumen Global de la Nómina -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <p class="text-xs text-slate-500 uppercase font-bold tracking-wider mb-1">Periodo Fiscal</p>
                    <p class="text-lg font-bold text-vera-dark">Del {{ \Carbon\Carbon::parse($period->start_date)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($period->end_date)->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase font-bold tracking-wider mb-1">Nómina Bruta</p>
                    <p class="text-lg font-bold text-slate-700">${{ number_format($period->total_gross, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase font-bold tracking-wider mb-1">Total Retenciones</p>
                    <p class="text-lg font-bold text-red-500">-${{ number_format($period->total_isr_retention + $period->total_imss_employee + $period->details->sum('total_custom_deductions'), 2) }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-slate-500 uppercase font-bold tracking-wider mb-1">Dispersión Neta</p>
                    <p class="text-2xl font-black text-vera-green">${{ number_format($period->total_net, 2) }}</p>
                </div>
            </div>

            <!-- BUSCADOR DE EMPLEADOS DENTRO DE LA NÓMINA -->
            <form method="GET" action="{{ route('payrolls.show', $period->id) }}" id="searchForm" class="bg-white p-4 rounded-lg shadow-sm border border-slate-200 flex flex-col sm:flex-row gap-4 items-center">
                <div class="flex-1 w-full relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" name="search" id="searchInput" value="{{ request('search') }}" placeholder="Buscar por Nombre, Apellidos o RFC del empleado..."
                        class="w-full pl-10 border-slate-300 rounded-md focus:ring-vera-green focus:border-vera-green text-sm shadow-sm transition">
                </div>

                @if(request('search'))
                <a href="{{ route('payrolls.show', $period->id) }}" class="px-4 py-2 bg-white border border-slate-300 text-slate-500 font-bold rounded-md hover:bg-slate-50 hover:text-red-500 transition shadow-sm text-sm">
                    Limpiar Búsqueda
                </a>
                @endif
            </form>

            <!-- Script de Auto-Búsqueda -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const searchInput = document.getElementById('searchInput');
                    const form = document.getElementById('searchForm');
                    let timeout = null;

                    searchInput.addEventListener('input', function() {
                        clearTimeout(timeout);
                        // Espera 600ms después de que el usuario deje de teclear para buscar
                        timeout = setTimeout(() => {
                            form.submit();
                        }, 600);
                    });
                });
            </script>

            <!-- MENSAJE SI NO HAY RESULTADOS -->
            @if($period->details->isEmpty())
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider">No se encontraron empleados</h3>
                <p class="text-xs text-slate-400 mt-1">No hay recibos que coincidan con la búsqueda "{{ request('search') }}".</p>
            </div>
            @endif

            <!-- Desglose por Empleado -->
            @foreach($period->details as $detail)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-6 mb-6">

                <!-- Encabezado del Recibo -->
                <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg font-bold text-vera-dark">{{ $detail->employee->full_name }}</h3>
                            <!-- ETIQUETA DE PERIODICIDAD -->
                            <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-md {{ $detail->periodicity_label == 'Mensual' ? 'bg-slate-100 text-slate-600' : 'bg-indigo-50 text-indigo-700' }}">
                                Pago {{ $detail->periodicity_label }}
                            </span>
                        </div>
                        <p class="text-sm text-vera-gray mt-1">RFC: {{ $detail->employee->rfc }} | NSS: {{ $detail->employee->nss ?? 'No registrado' }}</p>

                        <!-- Botón Descargar PDF Recibo -->
                        <div class="mt-3">
                            <a target="_blank" href="{{ route('payrolls.receipt.pdf', $detail->id) }}" class="inline-flex items-center gap-2 text-xs font-bold text-vera-green hover:text-emerald-700 transition uppercase tracking-wider">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Descargar PDF Recibo
                            </a>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-slate-500">Consolidado Mensual a Pagar</p>
                        <p class="text-2xl font-black text-vera-green">${{ number_format($detail->net_salary, 2) }}</p>

                        <!-- NUEVO: Mostrar el pago por periodo exacto -->
                        @if($detail->multiplier > 1)
                        <div class="mt-1 bg-emerald-50 border border-emerald-100 rounded px-3 py-1.5 inline-block">
                            <p class="text-[11px] text-emerald-700 font-medium">
                                Depositado por {{ $detail->periodicity_label == 'Quincenal' ? 'quincena' : 'semana' }}:
                                <span class="font-black">${{ number_format($detail->net_per_period, 2) }}</span>
                            </p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Grid de Memorias de Cálculo -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <!-- Memoria de Cálculo ISR -->
                    <div class="bg-slate-50 rounded-lg p-5 border border-slate-200 relative">
                        <!-- Insignia de Consolidación -->
                        @if($detail->multiplier > 1)
                        <div class="absolute -top-3 -right-3 bg-indigo-500 text-white text-[9px] font-black uppercase px-2 py-1 rounded shadow border border-indigo-600 transform rotate-2">
                            Memoria base x {{ round($detail->multiplier, 2) }}
                        </div>
                        @endif

                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 border-b border-slate-200 pb-2 flex justify-between">
                            <span>Memoria ISR (Tarifa {{ $detail->periodicity_label }})</span>
                            <span class="text-red-500" title="Retención total del mes">- ${{ number_format($detail->isr_retention, 2) }}</span>
                        </h4>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm text-center">
                            <div class="p-2 bg-white rounded border border-slate-100 shadow-sm" title="Salario base de 1 periodo">
                                <p class="text-[10px] text-slate-400 mb-1">Base Gravable (1 periodo)</p>
                                <p class="font-bold text-vera-dark">${{ number_format($detail->isr_breakdown['base'], 2) }}</p>
                            </div>
                            <div class="p-2 bg-white rounded border border-slate-100 shadow-sm">
                                <p class="text-[10px] text-slate-400 mb-1">(-) Límite Inferior</p>
                                <p class="font-medium text-red-500">${{ number_format($detail->isr_breakdown['lower_limit'], 2) }}</p>
                            </div>
                            <div class="p-2 bg-white rounded border border-slate-100 shadow-sm">
                                <p class="text-[10px] text-slate-400 mb-1">(=) Excedente</p>
                                <p class="font-medium text-slate-700">${{ number_format($detail->isr_breakdown['surplus'], 2) }}</p>
                            </div>
                            <div class="p-2 bg-white rounded border border-slate-100 shadow-sm">
                                <p class="text-[10px] text-slate-400 mb-1">(x) Tasa ({{ $detail->isr_breakdown['rate'] }}%)</p>
                                <p class="font-medium text-slate-700">${{ number_format($detail->isr_breakdown['marginal_tax'], 2) }}</p>
                            </div>
                            <div class="p-2 bg-white rounded border border-slate-100 shadow-sm">
                                <p class="text-[10px] text-slate-400 mb-1">(+) Cuota Fija</p>
                                <p class="font-medium text-slate-700">${{ number_format($detail->isr_breakdown['fixed_fee'], 2) }}</p>
                            </div>

                            @if($detail->isr_breakdown['is_minimum_wage'])
                            <div class="p-2 bg-emerald-50 text-emerald-800 rounded border border-emerald-200 shadow-sm col-span-1">
                                <p class="text-[10px] font-bold mb-1 uppercase">Exento</p>
                                <p class="font-bold text-base">$0.00</p>
                                <p class="text-[9px] leading-tight mt-1 opacity-75">Salario Mínimo</p>
                            </div>
                            @else
                            <div class="p-2 bg-vera-dark text-white rounded border border-slate-800 shadow-sm col-span-1">
                                <p class="text-[10px] text-slate-400 mb-1">ISR Base</p>
                                <p class="font-bold">${{ number_format($detail->isr_breakdown['total_isr'], 2) }}</p>
                            </div>
                            @endif
                        </div>
                        @if($detail->multiplier > 1 && !$detail->isr_breakdown['is_minimum_wage'])
                        <div class="mt-3 text-right">
                            <p class="text-[11px] text-slate-500 font-bold">Consolidado: <span class="text-slate-700">${{ number_format($detail->isr_breakdown['total_isr'], 2) }} x {{ round($detail->multiplier, 2) }} periodos =</span> <span class="text-red-500 border-b border-red-200">-${{ number_format($detail->isr_retention, 2) }}</span></p>
                        </div>
                        @endif
                    </div>

                    <!-- Memoria de Cálculo IMSS -->
                    <div class="bg-slate-50 rounded-lg p-5 border border-slate-200 relative">
                        @if($detail->multiplier > 1)
                        <div class="absolute -top-3 -right-3 bg-indigo-500 text-white text-[9px] font-black uppercase px-2 py-1 rounded shadow border border-indigo-600 transform rotate-2">
                            Memoria base x {{ round($detail->multiplier, 2) }}
                        </div>
                        @endif

                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 border-b border-slate-200 pb-2 flex justify-between">
                            <span>Retención IMSS (Cuota Obrera)</span>
                            <span class="text-red-500">- ${{ number_format($detail->imss_employee, 2) }}</span>
                        </h4>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm text-center">
                            @if($detail->isr_breakdown['is_minimum_wage'])
                            <div class="col-span-full flex flex-col items-center justify-center p-4 bg-emerald-50 text-emerald-800 rounded border border-emerald-200 shadow-sm h-full">
                                <svg class="w-8 h-8 mb-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                                <p class="text-sm font-bold uppercase mb-1">Exento (Art. 36 LSS)</p>
                                <p class="text-[10px] opacity-80 text-center max-w-[250px]">El empleado percibe el Salario Mínimo. La cuota obrera la absorbe el Patrón.</p>
                                <p class="font-black text-xl mt-2">$0.00</p>
                            </div>
                            @else
                            @php
                            $sbcDiario = ($detail->gross_salary / $detail->multiplier / ($detail->periodicity_label == 'Quincenal' ? 15 : ($detail->periodicity_label == 'Semanal' ? 7 : 30.4))) * 1.0493;
                            $diasCotizados = $detail->periodicity_label == 'Quincenal' ? 15 : ($detail->periodicity_label == 'Semanal' ? 7 : 30.4);
                            $cuotaBasePeriodo = ($sbcDiario * 0.02375) * $diasCotizados;
                            $cuotaExcedentePeriodo = max(0, ($detail->imss_employee / $detail->multiplier) - $cuotaBasePeriodo);
                            @endphp
                            <div class="p-2 bg-white rounded border border-slate-100 shadow-sm flex flex-col justify-center">
                                <p class="text-[10px] text-slate-400 mb-1">SBC Diario Est.</p>
                                <p class="font-bold text-vera-dark">${{ number_format($sbcDiario, 2) }}</p>
                            </div>
                            <div class="p-2 bg-white rounded border border-slate-100 shadow-sm flex flex-col justify-center">
                                <p class="text-[10px] text-slate-400 mb-1">(x) {{ $diasCotizados }} Días Cotizados</p>
                                <p class="font-medium text-slate-700">Tasa 2.375%</p>
                            </div>
                            <div class="p-2 bg-white rounded border border-slate-100 shadow-sm flex flex-col justify-center">
                                <p class="text-[10px] text-slate-400 mb-1">(=) Cuota Base</p>
                                <p class="font-medium text-slate-700">${{ number_format($cuotaBasePeriodo, 2) }}</p>
                            </div>
                            <div class="p-2 bg-white rounded border border-slate-100 shadow-sm flex flex-col justify-center">
                                <p class="text-[10px] text-slate-400 mb-1">(+) Exc. > 3 UMAs</p>
                                <p class="font-medium text-slate-700">${{ number_format($cuotaExcedentePeriodo, 2) }}</p>
                            </div>
                            <div class="p-2 bg-white rounded border border-slate-100 shadow-sm flex items-center justify-center px-1">
                                <p class="text-[9px] text-slate-400 leading-tight">Incluye Enf. Mat, Invalidez, Cesantía.</p>
                            </div>
                            <div class="p-2 bg-vera-dark text-white rounded border border-slate-800 shadow-sm col-span-1 flex flex-col justify-center">
                                <p class="text-[10px] text-slate-400 mb-1">IMSS Base</p>
                                <p class="font-bold text-lg">${{ number_format($detail->imss_employee / $detail->multiplier, 2) }}</p>
                            </div>
                            @endif
                        </div>
                        @if($detail->multiplier > 1 && !$detail->isr_breakdown['is_minimum_wage'])
                        <div class="mt-3 text-right">
                            <p class="text-[11px] text-slate-500 font-bold">Consolidado: <span class="text-slate-700">${{ number_format($detail->imss_employee / $detail->multiplier, 2) }} x {{ round($detail->multiplier, 2) }} periodos =</span> <span class="text-red-500 border-b border-red-200">-${{ number_format($detail->imss_employee, 2) }}</span></p>
                        </div>
                        @endif
                    </div>

                </div>

                <!-- TARJETA DE DEDUCCIONES PERSONALIZADAS -->
                @if($detail->total_custom_deductions > 0 && !empty($detail->custom_deductions_breakdown))
                <div class="mt-6 bg-slate-50 rounded-lg p-5 border border-slate-200 relative">
                    <!-- Insignia Deducciones -->
                    <div class="absolute -top-3 -left-3 bg-slate-800 text-white text-[9px] font-black uppercase px-2 py-1 rounded shadow border border-slate-900">
                        Sumatoria Total Consolidada
                    </div>
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 border-b border-slate-200 pb-2 flex justify-between">
                        <span>Deducciones Especiales Consolidadas del Mes</span>
                        <span class="text-red-500">-${{ number_format($detail->total_custom_deductions, 2) }}</span>
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($detail->custom_deductions_breakdown as $custom)
                        <div class="bg-white p-3 rounded border border-slate-100 shadow-sm flex justify-between items-center">
                            <div>
                                <p class="text-xs font-bold text-vera-dark">{{ $custom['description'] }}</p>
                                <p class="text-[10px] text-slate-400 font-mono">Clave SAT: {{ $custom['sat_key'] }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-red-500">-${{ number_format($custom['amount'], 2) }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>
            @endforeach

        </div>
    </div>
</x-app-layout>