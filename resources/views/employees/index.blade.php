<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-white leading-tight">
                {{ __('Directorio de Empleados') }}
            </h2>

            <a href="{{ route('employees.create') }}" class="bg-vera-green hover:bg-emerald-400 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m7-7H4"></path>
                </svg>
                Registrar Nuevo Empleado
            </a>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Alertas -->
            @if (session('success'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('success') }}
            </div>
            @endif

            <!-- Filtros y Buscador -->
            <form method="GET" action="{{ route('employees.index') }}" class="mb-6 bg-white p-5 rounded-lg shadow-sm border border-slate-200 grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Buscar (Nombre, RFC, CURP, NSS, Puesto, Correo)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Escribe para buscar por nombre, puesto, correo..."
                            class="w-full pl-9 border-slate-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Estatus Operativo</label>
                    <select name="status" onchange="this.form.submit()" class="w-full border-slate-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm transition">
                        <option value="activos" @selected(request('status', 'activos') == 'activos')>Empleados Activos</option>
                        <option value="inactivos" @selected(request('status') == 'inactivos')>Empleados Inactivos (Bajas)</option>
                        <option value="todos" @selected(request('status') == 'todos')>Todos los Registros</option>
                    </select>
                </div>
            </form>

            <!-- Tabla Principal -->
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
                        <tbody class="bg-white divide-y divide-slate-200">
                            @forelse ($employees as $employee)
                            <tr class="hover:bg-slate-50/80 transition">
                                <!-- Empleado / Puesto -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-slate-900">{{ $employee->full_name }}</div>
                                    <div class="text-xs text-slate-500">{{ $employee->position ?? 'Sin puesto asignado' }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">{{ $employee->email }}</div>
                                </td>

                                <!-- Identificadores -->
                                <td class="px-6 py-4 whitespace-nowrap font-mono text-xs text-slate-600">
                                    <div><span class="text-slate-400">RFC:</span> {{ $employee->rfc }}</div>
                                    <div><span class="text-slate-400">NSS:</span> {{ $employee->nss }}</div>
                                </td>

                                <!-- Salario Bruto -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-slate-800">
                                    ${{ number_format($employee->base_salary, 2) }}
                                </td>

                                <!-- Estado -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($employee->is_active)
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-100 text-emerald-700">Activo</span>
                                    @else
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-slate-100 text-slate-500">Inactivo</span>
                                    @endif
                                </td>

                                <!-- Acciones (Alineadas y Proporcionales) -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        
                                        <!-- Botón Deducciones (Compacto) -->
                                        <a href="{{ route('employees.deductions.index', $employee->id) }}" 
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 border border-indigo-200/60 text-indigo-700 hover:bg-indigo-600 hover:text-white rounded-md text-xs font-bold transition shadow-sm"
                                           title="Gestionar créditos, Infonavit o deducciones fijas">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Deducciones
                                        </a>

                                        <!-- Botón Editar -->
                                        <a href="{{ route('employees.edit', $employee->id) }}" 
                                           class="px-3 py-1.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 rounded-md text-xs font-bold transition shadow-sm">
                                            Editar
                                        </a>

                                        <!-- Botón Dar de Baja / Eliminar -->
                                        @if($employee->is_active)
                                        <form action="{{ route('employees.destroy', $employee->id) }}" method="POST" class="inline-block form-confirm"
                                            data-title="¿Dar de baja al empleado?"
                                            data-text="Pasará a estatus inactivo y ya no se incluirá en el cálculo de nómina."
                                            data-confirm="Sí, dar de baja">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1.5 bg-red-50 border border-red-200 text-red-600 hover:bg-red-600 hover:text-white rounded-md text-xs font-bold transition shadow-sm">
                                                Baja
                                            </button>
                                        </form>
                                        @endif

                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">
                                    No se encontraron empleados registrados con los criterios seleccionados.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginación -->
            <div class="mt-6">
                {{ $employees->links() }}
            </div>

        </div>
    </div>
</x-app-layout>