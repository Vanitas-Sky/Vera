<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-vera-dark leading-tight">
                {{ __('Control de OpEx (Gastos Fijos)') }}
            </h2>
            <a href="{{ route('opex.create') }}" class="bg-vera-dark text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-800 transition inline-block">
                + Nuevo Contrato
            </a>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Centro de Notificaciones / Alertas -->
            @if(count($alerts) > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 mb-6">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <h3 class="text-sm font-bold text-vera-dark uppercase tracking-wider">Alertas de Vencimiento</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach($alerts as $alert)
                    <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            @if($alert['type'] === 'danger')
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            @elseif($alert['type'] === 'warning')
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            @else
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            @endif
                            <p class="text-sm font-medium text-slate-700">{{ $alert['message'] }}</p>
                        </div>
                        <span class="text-xs font-bold text-slate-400 uppercase">{{ $alert['action'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Resumen Financiero -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-6 md:col-span-1">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Proyección Costo Fijo Mensual</p>
                    <p class="text-4xl font-black text-vera-dark mt-2">${{ number_format($totalMonthlyOpex, 2) }}</p>
                    <p class="text-xs text-slate-500 mt-2">Esta es tu carga operativa base antes de sueldos o impuestos.</p>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-6 md:col-span-2">
                    <!-- Aquí irá a futuro la gráfica dinámica del flujo de caja (Módulo 1) -->
                    <div class="h-full flex items-center justify-center border-2 border-dashed border-slate-200 rounded-lg">
                        <p class="text-sm text-slate-400">Espacio reservado para gráfico de proyección OpEx</p>
                    </div>
                </div>
            </div>

            <!-- Tabla de Contratos Activos -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                    <h3 class="text-sm font-bold text-vera-dark uppercase tracking-wider">Pólizas y Servicios Indexados</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Proveedor / Descripción</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Categoría</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Día de Pago</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Monto Mensual</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Vigencia Contrato</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @forelse($expenses as $expense)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <p class="font-bold text-vera-dark">{{ $expense->provider_name }}</p>
                                    <p class="text-xs text-slate-500">{{ $expense->description }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded-md text-xs font-semibold uppercase">{{ $expense->category }}</span>
                                </td>
                                <td class="px-6 py-4 text-slate-700 font-medium">
                                    Día {{ $expense->due_day }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-vera-dark">
                                    ${{ number_format($expense->monthly_amount, 2) }}
                                </td>
                                <td class="px-6 py-4 text-right text-xs text-slate-500">
                                    {{ $expense->contract_start_date ? $expense->contract_start_date->format('d/m/Y') : 'N/A' }} <br>
                                    al <br>
                                    <span class="font-semibold {{ $expense->contract_end_date && $expense->contract_end_date->isPast() ? 'text-red-500' : 'text-slate-700' }}">
                                        {{ $expense->contract_end_date ? $expense->contract_end_date->format('d/m/Y') : 'Indefinido' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-400">
                                    No tienes contratos ni gastos fijos registrados.
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