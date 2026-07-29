<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-vera-dark leading-tight">
            {{ __('Semáforo y Panel Fiscal') }}
        </h2>
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

            <!-- Tarjetas de Métricas Principales -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Ingresos -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-6">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ingresos Totales (Ventas)</p>
                    <p class="text-3xl font-black text-vera-dark mt-2">${{ number_format($totalIncome, 2) }}</p>
                    <p class="text-xs text-slate-500 mt-1">IVA Trasladado: ${{ number_format($totalIncomeIva, 2) }}</p>
                </div>

                <!-- Gastos (Egresos) -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-6 flex flex-col justify-between">
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
                            ⚠️ Tienes un hueco de ${{ number_format($missingInvoicesAmount, 2) }} sin facturar. Asegúrate de pedirle el XML a tus proveedores de servicios o perderás la deducción.
                        </p>
                        @else
                        <p class="text-[10px] text-emerald-600 font-semibold leading-tight mt-2">
                            ✓ Has facturado el equivalente o más a tus costos operativos base.
                        </p>
                        @endif
                    </div>
                </div>

                <!-- Nómina -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-6">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Costo Operativo Nómina</p>
                    <p class="text-3xl font-black text-amber-600 mt-2">${{ number_format($totalPayrollGross, 2) }}</p>
                    <p class="text-xs text-slate-500 mt-1">ISR Retenido a Enterar: ${{ number_format($totalIsrRetained, 2) }}</p>
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

        </div>
    </div>
</x-app-layout>