<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
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

            @if (session('error'))
            <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-600 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('error') }}
            </div>
            @endif

            {{-- Banner Informativo para el Auditor Externo --}}
            @if(Auth::user()->isAuditor())
            <div class="mb-6 bg-amber-50 border border-amber-200 text-amber-800 p-4 rounded-xl flex items-center gap-3 shadow-sm">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-xs">
                    <span class="font-bold">Modo Auditoría:</span> Estás consultando la configuración fiscal y régimen tributario de la empresa. La modificación de estos parámetros está reservada exclusivamente para el Administrador PyME.
                </div>
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

                    @php
                        $isAuditor = Auth::user()->isAuditor();
                        $inputClasses = $isAuditor 
                            ? 'w-full rounded-md border-slate-200 bg-slate-100 text-slate-500 shadow-sm cursor-not-allowed' 
                            : 'w-full rounded-md border-slate-300 shadow-sm focus:border-vera-green focus:ring-vera-green';
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- RFC -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">RFC</label>
                            <input type="text" name="rfc" value="{{ old('rfc', $company->rfc) }}" 
                                class="{{ $inputClasses }} uppercase font-mono" maxlength="13" 
                                @if($isAuditor) disabled @else required @endif>
                            @error('rfc') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <!-- Código Postal -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Código Postal Fiscal</label>
                            <input type="text" name="postal_code" value="{{ old('postal_code', $company->postal_code) }}" 
                                class="{{ $inputClasses }}" maxlength="5" 
                                @if($isAuditor) disabled @else required @endif>
                            @error('postal_code') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <!-- Razón Social -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Razón Social (Sin SA de CV)</label>
                            <input type="text" name="legal_name" value="{{ old('legal_name', $company->legal_name) }}" 
                                class="{{ $inputClasses }} uppercase" 
                                @if($isAuditor) disabled @else required @endif>
                        </div>

                        <!-- Régimen Fiscal -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Régimen Fiscal (SAT)</label>
                            <select name="tax_regime_code" class="{{ $inputClasses }} {{ $isAuditor ? '' : 'bg-slate-50' }}" 
                                @if($isAuditor) disabled @endif>
                                <option value="">Selecciona tu régimen oficial...</option>
                                @foreach($regimenesSAT as $codigo => $descripcion)
                                <option value="{{ $codigo }}" {{ old('tax_regime_code', $company->tax_regime_code) == $codigo ? 'selected' : '' }}>
                                    {{ $codigo }} - {{ $descripcion }}
                                </option>
                                @endforeach
                            </select>
                            @error('tax_regime_code') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <!-- Giro Específico / Actividad Económica -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Giro o Actividad Económica principal</label>
                            <select name="industry" class="{{ $inputClasses }} text-sm" 
                                @if($isAuditor) disabled @endif>
                                <option value="">Selecciona un giro (Opcional)...</option>
                                <option value="Construcción e Inmobiliaria" @selected(old('industry', $company->industry ?? '') == 'Construcción e Inmobiliaria')>Construcción e Inmobiliaria</option>
                                <option value="Hotelería y Turismo" @selected(old('industry', $company->industry ?? '') == 'Hotelería y Turismo')>Hotelería y Turismo</option>
                                <option value="Restaurantes y Alimentos" @selected(old('industry', $company->industry ?? '') == 'Restaurantes y Alimentos')>Restaurantes y Alimentos</option>
                                <option value="Transporte y Logística" @selected(old('industry', $company->industry ?? '') == 'Transporte y Logística')>Transporte y Logística</option>
                                <option value="Comercio al por Mayor / Menor" @selected(old('industry', $company->industry ?? '') == 'Comercio al por Mayor / Menor')>Comercio al por Mayor / Menor</option>
                                <option value="Servicios Profesionales y Consultoría" @selected(old('industry', $company->industry ?? '') == 'Servicios Profesionales y Consultoría')>Servicios Profesionales y Consultoría</option>
                                <option value="Tecnologías de la Información (TI)" @selected(old('industry', $company->industry ?? '') == 'Tecnologías de la Información (TI)')>Tecnologías de la Información (TI)</option>
                                <option value="Manufactura y Producción" @selected(old('industry', $company->industry ?? '') == 'Manufactura y Producción')>Manufactura y Producción</option>
                                <option value="Agricultura y Ganadería" @selected(old('industry', $company->industry ?? '') == 'Agricultura y Ganadería')>Agricultura y Ganadería</option>
                                <option value="Otro" @selected(old('industry', $company->industry ?? '') == 'Otro')>Otro</option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">Dato informativo para la carátula del Perfil Fiscal.</p>
                            @error('industry') <span class="text-xs font-bold text-red-500 block mt-1">⚠️ {{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Botón de Guardado: Solo para Administradores / Contadores --}}
                    @if(!$isAuditor)
                    <div class="mt-8 flex justify-end pt-4 border-t border-slate-100">
                        <button type="submit" class="bg-vera-green text-white px-6 py-2 rounded-lg hover:bg-emerald-700 transition font-bold shadow-sm">
                            Guardar Configuración Fiscal
                        </button>
                    </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</x-app-layout>