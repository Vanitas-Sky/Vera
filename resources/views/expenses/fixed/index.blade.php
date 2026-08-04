<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-white leading-tight">
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

                <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Tarjeta 1: OpEx Actual -->
                    <div class="bg-white p-6 rounded-lg border border-slate-200 shadow-sm flex flex-col justify-center">
                        <h3 class="text-sm font-semibold text-vera-slate uppercase tracking-wider">OpEx Mensual Actual</h3>
                        <p class="text-3xl font-bold text-vera-dark mt-2">${{ number_format($totalMonthlyOpex, 2) }} <span class="text-sm font-normal text-slate-400">MXN</span></p>
                        <p class="text-xs text-vera-accent mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Costos fijos calculados
                        </p>
                    </div>

                    <!-- Tarjeta 2: Proyección (Cumple la promesa del documento) -->
                    <div class="bg-vera-dark p-6 rounded-lg border border-slate-700 shadow-sm flex flex-col justify-center relative overflow-hidden">
                        <!-- Detalle decorativo de UI -->
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/5 rounded-full blur-2xl"></div>

                        <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">Proyección Anualizada</h3>
                        <p class="text-3xl font-bold text-white mt-2">${{ number_format($annualProjection, 2) }} <span class="text-sm font-normal text-slate-400">MXN</span></p>
                        <p class="text-xs text-slate-400 mt-1">Impacto estimado en 12 meses</p>
                    </div>

                    <!-- Tarjeta 3: Contratos Activos -->
                    <div class="bg-white p-6 rounded-lg border border-slate-200 shadow-sm flex flex-col justify-center">
                        <h3 class="text-sm font-semibold text-vera-slate uppercase tracking-wider">Contratos Activos</h3>
                        <p class="text-3xl font-bold text-vera-dark mt-2">{{ $activeContractsCount }}</p>
                        <p class="text-xs text-vera-warning mt-1 flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-vera-warning"></span>
                            Revisar próximos vencimientos
                        </p>
                    </div>
                </div>
            </div>

            <!-- Buscador y Filtros (Live Search) -->
            <form method="GET" action="{{ route('opex.index') }}" id="searchForm" class="mb-6 bg-white p-5 rounded-lg shadow-sm border border-slate-200 flex flex-col sm:flex-row gap-4 items-end">

                <!-- Buscador de Texto -->
                <div class="flex-1 w-full">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Buscar Proveedor o Concepto</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" name="search" id="searchInput" value="{{ request('search') }}" placeholder="Escribe para buscar..."
                            class="w-full pl-10 border-slate-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm transition">
                    </div>
                </div>

                <!-- Filtro de Categoría -->
                <div class="w-full sm:w-40">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Categoría</label>
                    <select name="category" id="categorySelect" class="w-full border-slate-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm cursor-pointer">
                        <option value="todas" {{ request('category') == 'todas' ? 'selected' : '' }}>Todas</option>
                        <option value="Renta" {{ request('category') == 'Renta' ? 'selected' : '' }}>Renta</option>
                        <option value="Seguros" {{ request('category') == 'Seguros' ? 'selected' : '' }}>Seguros</option>
                        <option value="Servicios" {{ request('category') == 'Servicios' ? 'selected' : '' }}>Servicios</option>
                        <option value="Software" {{ request('category') == 'Software' ? 'selected' : '' }}>Software</option>
                        <option value="Otro" {{ request('category') == 'Otro' ? 'selected' : '' }}>Otro</option>
                    </select>
                </div>

                <!-- Filtro de Estatus -->
                <div class="w-full sm:w-40">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Estatus</label>
                    <select name="status" id="statusSelect" class="w-full border-slate-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm cursor-pointer">
                        <option value="activos" {{ request('status') == 'activos' ? 'selected' : '' }}>Activos</option>
                        <option value="inactivos" {{ request('status') == 'inactivos' ? 'selected' : '' }}>Dados de baja</option>
                        <option value="todos" {{ request('status') == 'todos' ? 'selected' : '' }}>Histórico (Todos)</option>
                    </select>
                </div>

                <!-- Botón Limpiar -->
                <div class="flex gap-2 w-full sm:w-auto">
                    @if(request('search') || request('status', 'activos') != 'activos' || request('category', 'todas') != 'todas')
                    <a href="{{ route('opex.index') }}" class="px-4 py-2.5 bg-white border border-slate-300 text-red-600 font-bold rounded-md hover:bg-red-50 transition shadow-sm text-sm flex items-center">
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
                    const categorySelect = document.getElementById('categorySelect');
                    const statusSelect = document.getElementById('statusSelect');
                    let timeout = null;

                    function submitForm() {
                        form.submit();
                    }

                    searchInput.addEventListener('input', function() {
                        clearTimeout(timeout);
                        timeout = setTimeout(submitForm, 600);
                    });

                    categorySelect.addEventListener('change', submitForm);
                    statusSelect.addEventListener('change', submitForm);
                });
            </script>

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
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Estatus</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Acciones</th>
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
                                <td class="px-6 py-4 text-center">
                                    @if($expense->is_active)
                                    <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">Activo</span>
                                    @else
                                    <span class="bg-red-100 text-red-800 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">Inactivo</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center items-center gap-3">
                                        <!-- Botón Editar -->
                                        <a href="{{ route('opex.edit', $expense->id) }}" class="text-blue-600 hover:text-blue-900 font-bold text-xs uppercase transition">
                                            Editar
                                        </a>

                                        <span class="text-slate-200">|</span>

                                        <!-- Botón Dar de Baja / Reactivar -->
                                        <!-- Para Dar de Baja un Contrato -->
                                        <form action="{{ route('opex.toggle', $expense->id) }}" method="POST" class="inline-block form-confirm"
                                            data-title="{{ $expense->is_active ? '¿Dar de baja el contrato?' : '¿Reactivar el contrato?' }}"
                                            data-text="{{ $expense->is_active ? 'Ya no se contabilizará en tu OpEx mensual proyectado.' : 'Volverá a aparecer en tu presupuesto.' }}"
                                            data-confirm="Sí, proceder">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="font-bold text-xs uppercase transition {{ $expense->is_active ? 'text-red-600 hover:text-red-900' : 'text-emerald-600 hover:text-emerald-900' }}">
                                                {{ $expense->is_active ? 'Dar de Baja' : 'Reactivar' }}
                                            </button>
                                        </form>
                                    </div>
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
            <!-- Paginación Inteligente -->
            <div class="mt-6">
                {{ $expenses->links() }}
            </div>

        </div>
    </div>
</x-app-layout>