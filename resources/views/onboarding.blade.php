<x-app-layout>
    <div class="min-h-screen bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <h2 class="mt-6 text-3xl font-extrabold text-vera-dark">
                Configura tu Empresa
            </h2>
            <p class="mt-2 text-sm text-vera-gray">
                Para comenzar a auditar tus facturas, necesitamos los datos fiscales de tu PyME.
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow-sm sm:rounded-lg sm:px-10 border border-slate-200">
                <form method="POST" action="{{ route('onboarding.store') }}" class="space-y-6">
                    @csrf

                    <!-- RFC -->
                    <div>
                        <label for="rfc" class="block text-sm font-medium text-vera-dark">RFC</label>
                        <div class="mt-1">
                            <input id="rfc" name="rfc" type="text" required minlength="12" maxlength="13" placeholder="Ej. EKU9003173C9" class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-vera-green focus:border-vera-green uppercase">
                        </div>
                        <p class="mt-1 text-xs text-slate-400">12 caracteres para Persona Moral, 13 para Persona Física.</p>
                        @error('rfc') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <!-- Razón Social -->
                    <div>
                        <label for="legal_name" class="block text-sm font-medium text-vera-dark">Razón Social</label>
                        <div class="mt-1">
                            <input id="legal_name" name="legal_name" type="text" required placeholder="Ej. Vera Tech" class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-vera-green focus:border-vera-green">
                        </div>
                        <p class="mt-1 text-xs text-slate-400">Escríbelo exactamente como está en tu constancia, sin el régimen societario (ej. S.A. de C.V.) si usas CFDI 4.0.</p>
                        @error('legal_name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <!-- Código Postal -->
                    <div>
                        <label for="postal_code" class="block text-sm font-medium text-vera-dark">Código Postal (Fiscal)</label>
                        <div class="mt-1">
                            <input id="postal_code" name="postal_code" type="text" required maxlength="5" pattern="\d{5}" placeholder="Ej. 29200" title="Debe ser un código postal válido de 5 dígitos." class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-md shadow-sm placeholder-slate-400 focus:outline-none focus:ring-vera-green focus:border-vera-green">
                        </div>
                        <p class="mt-1 text-xs text-slate-400">Código postal de 5 dígitos registrado ante el SAT.</p>
                        @error('postal_code') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <!-- Régimen Fiscal -->
                    <div>
                        <label for="tax_regime_code" class="block text-sm font-medium text-vera-dark">Régimen Fiscal Principal</label>
                        <div class="mt-1">
                            <select id="tax_regime_code" name="tax_regime_code" required class="block w-full pl-3 pr-10 py-2 text-base border-slate-300 focus:outline-none focus:ring-vera-green focus:border-vera-green sm:text-sm rounded-md">
                                <option value="" disabled selected>Selecciona tu régimen</option>
                                @foreach($regimes as $regime)
                                <option value="{{ $regime->code }}">{{ $regime->code }} - {{ $regime->description }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('tax_regime_code') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <!-- Botón -->
                    <div>
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-bold text-white bg-vera-green hover:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-vera-green transition">
                            Finalizar Configuración
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>