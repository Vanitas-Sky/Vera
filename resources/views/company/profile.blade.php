<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-vera-dark leading-tight">
            {{ __('Perfil Fiscal de la Empresa') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            @if (session('success'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-lg text-sm font-medium">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-8">
                <div class="mb-6 border-b border-slate-100 pb-4">
                    <h3 class="text-lg font-bold text-vera-dark">Configuración SAT</h3>
                    <p class="text-sm text-slate-500">Define tu información legal. Vera usará tu régimen para determinar qué puedes deducir.</p>
                </div>

                <form action="{{ route('company.update') }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- RFC -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">RFC</label>
                            <input type="text" name="rfc" value="{{ old('rfc', $company->rfc) }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-vera-green focus:ring-vera-green uppercase font-mono" maxlength="13" required>
                            @error('rfc') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <!-- Código Postal -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Código Postal Fiscal</label>
                            <input type="text" name="postal_code" value="{{ old('postal_code', $company->postal_code) }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-vera-green focus:ring-vera-green" maxlength="5" required>
                            @error('postal_code') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <!-- Razón Social -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Razón Social (Sin SA de CV)</label>
                            <input type="text" name="legal_name" value="{{ old('legal_name', $company->legal_name) }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-vera-green focus:ring-vera-green uppercase" required>
                        </div>

                        <!-- Régimen Fiscal -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Régimen Fiscal (SAT)</label>
                            <select name="tax_regime_code" class="w-full rounded-md border-slate-300 shadow-sm focus:border-vera-green focus:ring-vera-green bg-slate-50">
                                <option value="">Selecciona tu régimen oficial...</option>
                                @foreach($regimenesSAT as $codigo => $descripcion)
                                    <option value="{{ $codigo }}" {{ old('tax_regime_code', $company->tax_regime_code) == $codigo ? 'selected' : '' }}>
                                        {{ $codigo }} - {{ $descripcion }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tax_regime_code') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end pt-4 border-t border-slate-100">
                        <button type="submit" class="bg-vera-dark text-white px-6 py-2 rounded-lg hover:bg-slate-800 transition font-bold shadow-sm">
                            Guardar Configuración Fiscal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>