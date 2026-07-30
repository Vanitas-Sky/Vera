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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Nombre</th>
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
                                        <form action="{{ route('employees.destroy', $employee->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de dar de baja a {{ $employee->full_name }}? Ya no aparecerá en las futuras nóminas.');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 font-bold uppercase text-xs">Dar de baja</button>
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

        </div>
    </div>
</x-app-layout>