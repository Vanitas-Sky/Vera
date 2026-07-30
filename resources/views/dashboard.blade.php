<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-semibold text-xl text-vera-dark leading-tight">
                {{ __('Semáforo y Panel Fiscal') }}
            </h2>

            <!-- Formulario de Filtro de Tiempo -->
            <form action="{{ route('dashboard') }}" method="GET" class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-sm">
                <label for="period" class="text-xs font-bold text-slate-400 uppercase tracking-wider">Mes Fiscal:</label>
                <input type="month" id="period" name="period" value="{{ $selectedPeriod }}"
                    class="border-none bg-transparent focus:ring-0 text-sm font-bold text-vera-dark cursor-pointer py-1"
                    onchange="this.form.submit()">
            </form>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Alerta del Semáforo Fiscal -->
            @if($semaforo === 'rojo')
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0 text-red-500 font-bold text-lg">🔴 ALERTA ROJA</div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700 font-medium">{{ $mensajeSemaforo }}</p>
                    </div>
                </div>
            </div>
            @elseif($semaforo === 'amarillo')
            <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-lg shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0 text-amber-600 font-bold text-lg">🟡 ATENCIÓN</div>
                    <div class="ml-3">
                        <p class="text-sm text-amber-800 font-medium">{{ $mensajeSemaforo }}</p>
                    </div>
                </div>
            </div>
            @else
            <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-lg shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0 text-emerald-600 font-bold text-lg">🟢 SALUDABLE</div>
                    <div class="ml-3">
                        <p class="text-sm text-emerald-800 font-medium">{{ $mensajeSemaforo }}</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Botones de Acción Rápida -->
            <div class="flex flex-col sm:flex-row gap-4 mb-6">
                <a href="{{ route('billing.create') }}" class="flex-1 bg-slate-800 text-white text-center py-3 rounded-lg font-bold hover:bg-slate-700 transition shadow-sm">
                    + Emitir Nueva Factura
                </a>
                <a href="{{ route('invoices.index') }}" class="flex-1 bg-white border border-slate-300 text-slate-700 text-center py-3 rounded-lg font-bold hover:bg-slate-50 transition shadow-sm">
                    Subir XML (Ingresos y Egresos)
                </a>
                <!-- Botón IA (Estático temporalmente) -->
                <button type="button" onclick="alert('La integración con el Consultor Inteligente Vera AI estará disponible en la próxima actualización.')" class="flex-1 bg-vera-green text-white text-center py-3 rounded-lg font-bold hover:bg-emerald-700 transition shadow-sm flex items-center justify-center gap-2 cursor-pointer opacity-90">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Consultor Vera AI <span class="text-[10px] bg-white text-vera-green px-1.5 py-0.5 rounded-full uppercase ml-1">Pronto</span>
                </button>
            </div>

            <!-- Tarjetas de Métricas Principales -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Ingresos -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-6 flex flex-col h-full">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ingresos Totales (Ventas)</p>
                    <p class="text-3xl font-black text-vera-dark mt-2">${{ number_format($totalIncome, 2) }}</p>
                    <p class="text-xs text-slate-500 mt-1">IVA Trasladado: ${{ number_format($totalIncomeIva, 2) }}</p>
                </div>

                <!-- Gastos (Egresos) -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-6 flex flex-col justify-between h-full">
                    <div>
                        <div class="flex justify-between items-start">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Gastos Facturados</p>
                            @if($missingInvoicesAmount > 0)
                            <span class="px-2 py-1 bg-amber-100 text-amber-800 text-[10px] font-bold rounded-full uppercase shadow-sm">Faltan Facturas</span>
                            @else
                            <span class="px-2 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded-full uppercase shadow-sm">OpEx Cubierto</span>
                            @endif
                        </div>
                        <p class="text-3xl font-black text-red-500 mt-2">${{ number_format($totalExpense, 2) }}</p>
                        <p class="text-xs text-slate-500 mt-1">IVA Acreditable Pagado: ${{ number_format($totalExpenseIva, 2) }}</p>
                    </div>

                    <!-- Barra de progreso OpEx vs Facturado -->
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <div class="flex justify-between text-[11px] mb-1">
                            <span class="text-slate-500 font-medium">Contratos Fijos (OpEx):</span>
                            <span class="font-bold text-slate-700">${{ number_format($projectedOpex, 2) }}</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 mb-1 overflow-hidden">
                            @php
                            $percentage = $projectedOpex > 0 ? min(100, ($subtotalExpense / $projectedOpex) * 100) : 100;
                            @endphp
                            <div class="bg-blue-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
                        </div>
                        @if($missingInvoicesAmount > 0)
                        <p class="text-[10px] text-amber-600 font-semibold leading-tight mt-2">
                            ⚠️ Tienes un hueco de ${{ number_format($missingInvoicesAmount, 2) }} sin facturar. Asegúrate de pedirle el XML a tus proveedores.
                        </p>
                        @else
                        <p class="text-[10px] text-emerald-600 font-semibold leading-tight mt-2">
                            ✓ Has facturado el equivalente o más a tus costos operativos base.
                        </p>
                        @endif
                    </div>
                </div>

                <!-- Widget de Alerta Bancaria (Conciliación) -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border flex flex-col justify-between h-full {{ $discrepancy > 0 ? 'border-red-300' : 'border-slate-200' }} p-6 relative">

                    @if($discrepancy > 0)
                    <!-- Insignia de alerta en la esquina -->
                    <span class="absolute top-4 right-4 flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                    </span>
                    @endif

                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-5 h-5 {{ $discrepancy > 0 ? 'text-red-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path>
                            </svg>
                            <p class="text-xs font-bold uppercase tracking-wider {{ $discrepancy > 0 ? 'text-red-600' : 'text-slate-500' }}">
                                Discrepancia Bancaria
                            </p>
                        </div>
                        <p class="text-3xl font-black {{ $discrepancy > 0 ? 'text-red-600' : 'text-vera-dark' }}">
                            ${{ number_format(max(0, $discrepancy), 2) }}
                        </p>
                        <div class="mt-3 text-xs flex flex-col gap-1">
                            <p class="text-slate-500 flex justify-between">
                                <span>Retiros Bancarios:</span>
                                <span class="font-bold">${{ number_format($bankWithdrawals, 2) }}</span>
                            </p>
                            <p class="text-slate-500 flex justify-between">
                                <span>Facturas SAT (Egresos):</span>
                                <span class="font-bold">${{ number_format($totalExpense, 2) }}</span>
                            </p>
                        </div>
                    </div>

                    @if($discrepancy > 0)
                    <a href="{{ route('conciliations.index') }}" class="mt-4 block w-full text-center py-2 bg-red-50 text-red-600 border border-red-200 rounded-md text-xs font-bold hover:bg-red-100 transition">
                        Resolver Discrepancia &rarr;
                    </a>
                    @else
                    <a href="{{ route('conciliations.index') }}" class="mt-4 block w-full text-center py-2 bg-slate-50 text-slate-600 border border-slate-200 rounded-md text-xs font-bold hover:bg-slate-100 transition">
                        Ver Conciliación
                    </a>
                    @endif
                </div>

                <!-- ================= SEGUNDA FILA DEL GRID ================= -->

                <!-- Nómina -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-6 flex flex-col h-full">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Costo Operativo Nómina</p>
                    <p class="text-3xl font-black text-amber-600 mt-2">${{ number_format($totalPayrollGross, 2) }}</p>
                    <p class="text-xs text-slate-500 mt-1">ISR Retenido a Enterar: ${{ number_format($totalIsrRetained, 2) }}</p>
                </div>

                <!-- Centro de Notificaciones -->
                <div class="md:col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 flex flex-col h-full">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <h3 class="text-sm font-bold text-vera-dark uppercase tracking-wider">Centro de Notificaciones</h3>
                        </div>
                        @if(count($alerts) > 0 && $alerts[0]['type'] !== 'success')
                        <span class="px-2.5 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold uppercase tracking-wider">{{ count($alerts) }} Alertas</span>
                        @endif
                    </div>

                    <div class="divide-y divide-slate-100 flex-grow overflow-y-auto" style="max-height: 250px;">
                        @foreach($alerts as $alert)
                        <div class="p-4 flex gap-4 hover:bg-slate-50 transition items-start">

                            <div class="flex-shrink-0 mt-0.5">
                                @if($alert['type'] === 'danger')
                                <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $alert['icon'] }}"></path>
                                    </svg>
                                </div>
                                @elseif($alert['type'] === 'warning')
                                <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-amber-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $alert['icon'] }}"></path>
                                    </svg>
                                </div>
                                @else
                                <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $alert['icon'] }}"></path>
                                    </svg>
                                </div>
                                @endif
                            </div>

                            <div>
                                <h4 class="text-sm font-bold text-slate-800">{{ $alert['title'] }}</h4>
                                <p class="text-xs text-slate-500 mt-1">{{ $alert['message'] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>

            <!-- Panel de Cierre Fiscal (IVA y Utilidad) -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-vera-dark mb-4">Proyección de Cierre Fiscal</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                        <p class="text-xs text-slate-500 uppercase font-bold">Balance de IVA (A pagar al SAT)</p>
                        <p class="text-2xl font-bold text-vera-dark mt-1">
                            ${{ number_format($netIva, 2) }}
                        </p>
                        <p class="text-xs text-slate-400 mt-1">Resultado de restar el IVA de tus compras al IVA cobrado en tus ventas.</p>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                        <p class="text-xs text-slate-500 uppercase font-bold">Utilidad Neta Preliminar</p>
                        <p class="text-2xl font-bold {{ $netProfit >= 0 ? 'text-vera-green' : 'text-red-500' }} mt-1">
                            ${{ number_format($netProfit, 2) }}
                        </p>
                        <p class="text-xs text-slate-400 mt-1">Ingresos menos Gastos y Costos de Nómina (Sin impuestos).</p>
                    </div>
                </div>
            </div>

            <!-- Últimos Movimientos (Historial Resumido) -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 mt-8">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-vera-dark">Movimientos Recientes</h3>
                    <a href="{{ route('invoices.index') }}" class="text-sm text-vera-green font-bold hover:underline">Ver bóveda completa &rarr;</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left whitespace-nowrap">
                        <thead class="text-xs text-slate-500 bg-slate-50 uppercase border-b border-slate-200">
                            <tr>
                                <th scope="col" class="px-6 py-4 font-bold">Fecha</th>
                                <th scope="col" class="px-6 py-4 font-bold">Folio Fiscal (UUID)</th>
                                <th scope="col" class="px-6 py-4 font-bold">Cliente / Proveedor</th>
                                <th scope="col" class="px-6 py-4 font-bold">Tipo</th>
                                <th scope="col" class="px-6 py-4 text-right font-bold">Total</th>
                                <th scope="col" class="px-6 py-4 text-center font-bold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse(isset($invoices) ? $invoices->take(5) : [] as $inv)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-mono text-slate-600">
                                    {{ $inv->issue_date->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-400" title="{{ $inv->uuid }}">
                                    {{ substr($inv->uuid, 0, 8) }}...
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-700">
                                    {{ $inv->type == 'I' ? $inv->receiver_name : $inv->issuer_name }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($inv->type == 'I')
                                    <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">Ingreso</span>
                                    @elseif($inv->type == 'E')
                                    <span class="bg-red-100 text-red-800 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">Egreso</span>
                                    @else
                                    <span class="bg-slate-100 text-slate-800 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">{{ $inv->type }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right font-black text-vera-dark">
                                    ${{ number_format($inv->total, 2) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center items-center gap-3">
                                        <a href="{{ route('invoices.show', $inv->id) }}" class="text-slate-400 hover:text-vera-dark font-bold text-xs uppercase transition">Ver</a>
                                        <span class="text-slate-200">|</span>
                                        @if($inv->type == 'I')
                                        <a href="{{ route('billing.zip', $inv->id) }}" class="text-vera-green hover:text-emerald-700 font-bold text-xs uppercase transition">ZIP</a>
                                        @else
                                        <span class="text-slate-300 font-bold text-xs uppercase cursor-not-allowed" title="Solo XML disponible">XML</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                    No tienes facturas registradas. Utiliza los botones superiores para empezar.
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