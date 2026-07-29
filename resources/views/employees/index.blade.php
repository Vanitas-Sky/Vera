<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-vera-dark leading-tight">
                {{ __('Directorio de Empleados') }}
            </h2>
            <a href="{{ route('employees.create') }}" class="bg-vera-green hover:bg-emerald-400 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition text-sm">
                + Nuevo Empleado
            </a>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if (session('success'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-vera-gray uppercase tracking-wider">Nombre Completo</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-vera-gray uppercase tracking-wider">RFC / CURP</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-vera-gray uppercase tracking-wider">NSS</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-vera-gray uppercase tracking-wider">Salario Base (Mensual)</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-vera-gray uppercase tracking-wider">Estado</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-vera-gray uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @forelse ($employees as $employee)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-vera-dark">
                                        {{ $employee->full_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-vera-gray">
                                        <div class="font-medium">{{ $employee->rfc }}</div>
                                        <div class="text-xs text-slate-400">{{ $employee->curp }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-vera-gray">
                                        {{ $employee->nss ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-vera-green">
                                        ${{ number_format($employee->base_salary, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($employee->is_active)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-100 text-emerald-800">Activo</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Baja</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="#" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-sm text-vera-gray">
                                        No hay empleados registrados en tu empresa.
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