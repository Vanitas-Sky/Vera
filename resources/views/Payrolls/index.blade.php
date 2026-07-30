<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-vera-dark leading-tight">
                {{ __('Historial de Nóminas') }}
            </h2>

            <!-- Botón de Generación (Envuelto en Formulario POST) -->
            <form action="{{ route('payrolls.generate') }}" method="POST" onsubmit="return confirm('¿Estás seguro de calcular la nómina para todos los empleados activos?');">
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-vera-gray uppercase tracking-wider">Periodo</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-vera-gray uppercase tracking-wider">Empleados</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-vera-gray uppercase tracking-wider">Total Bruto</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-vera-gray uppercase tracking-wider">Retención ISR</th>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-vera-green">
                                    ${{ number_format($period->total_net, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('payrolls.show', $period->id) }}" class="inline-flex items-center gap-2 rounded-lg border border-vera-green/20 bg-vera-green/10 px-3 py-2 text-vera-green transition hover:bg-vera-green hover:text-white">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Ver detalles
                                    </a>
                                    <!-- Botón Eliminar / Recalcular -->
                                    <form action="{{ route('payrolls.destroy', $period->id) }}" method="POST" class="inline-block ml-2" onsubmit="return confirm('¿Estás seguro de eliminar esta nómina? Esto borrará los recibos de todos los empleados y te permitirá volver a calcular el mes.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-red-500/20 bg-red-500/10 px-3 py-2 text-red-600 transition hover:bg-red-600 hover:text-white">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-vera-gray">
                                    No has calculado ninguna nómina aún. Da clic en el botón superior para comenzar.
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