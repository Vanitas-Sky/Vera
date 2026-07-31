<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-vera-dark leading-tight">
                {{ __('Plantilla de Empleados') }}
            </h2>
            <a href="{{ route('employees.create') }}" class="bg-vera-dark hover:bg-slate-800 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition text-sm">
                + Registrar Empleado
            </a>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Alertas -->
            @if (session('success'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-lg text-sm font-medium shadow-sm">
                {{ session('success') }}
            </div>
            @endif

            <!-- Buscador y Filtros Inteligentes (Live Search) -->
            <form method="GET" action="{{ route('employees.index') }}" id="searchForm" class="mb-6 bg-white p-5 rounded-lg shadow-sm border border-slate-200 flex flex-col sm:flex-row gap-4 items-end">

                <!-- Buscador Profundo -->
                <div class="flex-1 w-full">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Buscar (Nombre, RFC, CURP, NSS, Puesto, Correo)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" name="search" id="searchInput" value="{{ request('search') }}" placeholder="Escribe para buscar por nombre, puesto, correo..."
                            class="w-full pl-10 border-slate-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm transition">
                    </div>
                </div>

                <!-- Filtro de Estatus -->
                <div class="w-full sm:w-48">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Estatus Operativo</label>
                    <select name="status" id="statusSelect" class="w-full border-slate-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm cursor-pointer">
                        <option value="activos" {{ request('status') == 'activos' ? 'selected' : '' }}>Empleados Activos</option>
                        <option value="inactivos" {{ request('status') == 'inactivos' ? 'selected' : '' }}>Dados de Baja</option>
                        <option value="todos" {{ request('status') == 'todos' ? 'selected' : '' }}>Todos (Histórico)</option>
                    </select>
                </div>

                <!-- Botón Limpiar -->
                <div class="flex gap-2 w-full sm:w-auto">
                    @if(request('search') || request('status', 'activos') != 'activos')
                    <a href="{{ route('employees.index') }}" class="px-4 py-2.5 bg-white border border-slate-300 text-red-600 font-bold rounded-md hover:bg-red-50 transition shadow-sm text-sm flex items-center">
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
                    const statusSelect = document.getElementById('statusSelect');
                    let timeout = null;

                    function submitForm() {
                        form.submit();
                    }

                    searchInput.addEventListener('input', function() {
                        clearTimeout(timeout);
                        timeout = setTimeout(submitForm, 600);
                    });

                    statusSelect.addEventListener('change', submitForm);
                });
            </script>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Empleado / Puesto</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Identificadores</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Salario Bruto</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Estado</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            @forelse ($employees as $employee)
                            <tr class="{{ $employee->is_active ? 'hover:bg-slate-50 transition' : 'bg-slate-50 opacity-60' }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-vera-dark">{{ $employee->full_name }}</div>
                                    @if($employee->position)
                                        <div class="text-xs font-semibold text-slate-600">{{ $employee->position }}</div>
                                    @endif
                                    @if($employee->email)
                                        <div class="text-xs text-slate-400">{{ $employee->email }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs font-mono text-slate-600">RFC: {{ $employee->rfc }}</div>
                                    <div class="text-xs font-mono text-slate-500">NSS: {{ $employee->nss }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-black text-slate-700">
                                    ${{ number_format($employee->base_salary, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($employee->is_active)
                                    <span class="px-2.5 py-1 inline-flex text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800">Activo</span>
                                    @else
                                    <span class="px-2.5 py-1 inline-flex text-[10px] font-bold rounded-full bg-slate-200 text-slate-600">Baja</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">

                                    <div class="flex justify-end items-center gap-3">
                                        <a href="{{ route('employees.edit', $employee->id) }}" class="text-vera-green hover:text-emerald-700 font-bold uppercase text-xs">Editar</a>

                                        @if($employee->is_active)
                                        <span class="text-slate-200">|</span>
                                        <form action="{{ route('employees.destroy', $employee->id) }}" method="POST" class="inline-block form-confirm"
                                            data-title="¿Dar de baja a {{ $employee->full_name }}?"
                                            data-text="El empleado dejará de generar nómina. Su historial financiero se mantendrá intacto."
                                            data-confirm="Sí, dar de baja">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 font-bold text-xs uppercase transition">
                                                Dar de Baja
                                            </button>
                                        </form>
                                        @endif
                                    </div>

                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">
                                    No tienes empleados registrados en tu plantilla.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginación Inteligente -->
            <div class="mt-6">
                {{ $employees->links() }}
            </div>

        </div>
    </div>
</x-app-layout>