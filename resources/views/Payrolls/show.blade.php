<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('payrolls.index') }}" class="text-vera-gray hover:text-vera-dark transition">
                &larr; Volver
            </a>
            <h2 class="font-semibold text-xl text-vera-dark leading-tight">
                {{ $period->period_name }} <span class="text-sm font-normal text-slate-500 ml-2">(Radiografía de Cálculos)</span>
            </h2>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @foreach($period->details as $detail)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-6 mb-6">
                <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-vera-dark">{{ $detail->employee->full_name }}</h3>
                        <p class="text-sm text-vera-gray">RFC: {{ $detail->employee->rfc }} | NSS: {{ $detail->employee->nss }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-slate-500">Neto a Pagar</p>
                        <p class="text-2xl font-black text-vera-green">${{ number_format($detail->net_salary, 2) }}</p>
                    </div>
                </div>

                <!-- Memoria de Cálculo (Desglose Matemático) -->
                <div class="bg-slate-50 rounded-lg p-5 border border-slate-200">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 border-b border-slate-200 pb-2">Memoria de Cálculo ISR (Art. 96)</h4>

                    <div class="grid grid-cols-2 md:grid-cols-6 gap-4 text-sm text-center">
                        <div class="p-3 bg-white rounded border border-slate-100 shadow-sm">
                            <p class="text-xs text-slate-400 mb-1">Base Gravable</p>
                            <p class="font-bold text-vera-dark">${{ number_format($detail->isr_breakdown['base'], 2) }}</p>
                        </div>

                        <div class="p-3 bg-white rounded border border-slate-100 shadow-sm">
                            <p class="text-xs text-slate-400 mb-1">(-) Límite Inferior</p>
                            <p class="font-medium text-slate-700 text-red-500">${{ number_format($detail->isr_breakdown['lower_limit'], 2) }}</p>
                        </div>

                        <div class="p-3 bg-white rounded border border-slate-100 shadow-sm">
                            <p class="text-xs text-slate-400 mb-1">(=) Excedente</p>
                            <p class="font-medium text-slate-700">${{ number_format($detail->isr_breakdown['surplus'], 2) }}</p>
                        </div>

                        <div class="p-3 bg-white rounded border border-slate-100 shadow-sm">
                            <p class="text-xs text-slate-400 mb-1">(x) Tasa ({{ $detail->isr_breakdown['rate'] }}%)</p>
                            <p class="font-medium text-slate-700">${{ number_format($detail->isr_breakdown['marginal_tax'], 2) }}</p>
                        </div>

                        <div class="p-3 bg-white rounded border border-slate-100 shadow-sm">
                            <p class="text-xs text-slate-400 mb-1">(+) Cuota Fija</p>
                            <p class="font-medium text-slate-700">${{ number_format($detail->isr_breakdown['fixed_fee'], 2) }}</p>
                        </div>

                        <!-- Caja Final de Retención -->
                        @if($detail->isr_breakdown['is_minimum_wage'])
                        <div class="p-3 bg-emerald-50 text-emerald-800 rounded border border-emerald-200 shadow-sm col-span-1">
                            <p class="text-xs font-bold mb-1 uppercase">Exento (Art. 96)</p>
                            <p class="font-bold text-lg">$0.00</p>
                            <p class="text-[10px] leading-tight mt-1 opacity-75">Protegido por Salario Mínimo</p>
                        </div>
                        @else
                        <div class="p-3 bg-vera-dark text-white rounded border border-slate-800 shadow-sm col-span-1">
                            <p class="text-xs text-slate-400 mb-1">ISR a Retener</p>
                            <p class="font-bold">${{ number_format($detail->isr_breakdown['total_isr'], 2) }}</p>
                        </div>
                        @endif

                        <div class="p-3 bg-vera-dark text-white rounded border border-slate-800 shadow-sm">
                            <p class="text-xs text-slate-400 mb-1">ISR a Retener</p>
                            <p class="font-bold">${{ number_format($detail->isr_breakdown['total_isr'], 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach

        </div>
    </div>
</x-app-layout>