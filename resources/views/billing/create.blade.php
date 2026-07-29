<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-vera-dark leading-tight">
            {{ __('Emisión de Factura (Simulador PAC)') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0 text-blue-600 font-bold text-lg">ℹ️ MODO SANDBOX</div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-800 font-medium">Estás en un entorno seguro de pruebas. Los CFDI generados no tienen validez fiscal ante el SAT de producción.</p>
                    </div>
                </div>
            </div>

            <!-- Bloque de Errores de Validación -->
            @if ($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm">
                <div class="flex items-center mb-2">
                    <div class="flex-shrink-0 text-red-600 font-bold text-lg">⚠️ Error en el Formulario</div>
                    <div class="ml-3">
                        <p class="text-sm text-red-800 font-bold">Por favor corrige los siguientes datos para poder timbrar la factura:</p>
                    </div>
                </div>
                <ul class="list-disc list-inside text-sm text-red-700 ml-8 mt-2 font-medium">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('billing.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Bloque 1: Datos del Cliente (Receptor) -->
                <div class="bg-white shadow-sm sm:rounded-lg border border-slate-200 p-6">
                    <h3 class="text-base font-bold text-vera-dark border-b border-slate-100 pb-2 mb-4">1. Datos del Receptor (Cliente)</h3>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="md:col-span-1">
                            <label class="block text-sm font-bold text-slate-700 mb-1">RFC</label>
                            <input type="text" name="receptor_rfc" value="{{ old('receptor_rfc') }}" class="w-full rounded-md border-slate-300 focus:border-vera-green focus:ring-vera-green uppercase font-mono" placeholder="Ej. XAXX010101000" maxlength="13" required>
                            @error('receptor_rfc') <span class="text-xs text-red-500 font-bold mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Razón Social</label>
                            <input type="text" name="receptor_nombre" class="w-full rounded-md border-slate-300 focus:border-vera-green focus:ring-vera-green uppercase" placeholder="Tal cual aparece en su Constancia de Situación Fiscal" required>
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-sm font-bold text-slate-700 mb-1">C.P. Fiscal</label>
                            <input type="text" name="receptor_cp" class="w-full rounded-md border-slate-300 focus:border-vera-green focus:ring-vera-green" maxlength="5" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Régimen Fiscal</label>
                            <select name="receptor_regimen" class="w-full rounded-md border-slate-300 focus:border-vera-green focus:ring-vera-green bg-slate-50" required>
                                <option value="">Selecciona...</option>
                                @foreach($regimenes as $codigo => $desc)
                                <option value="{{ $codigo }}">{{ $desc }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Uso de CFDI</label>
                            <select name="uso_cfdi" class="w-full rounded-md border-slate-300 focus:border-vera-green focus:ring-vera-green bg-slate-50" required>
                                <option value="">Selecciona...</option>
                                @foreach($usosCfdi as $codigo => $desc)
                                <option value="{{ $codigo }}">{{ $desc }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Bloque 2: Datos del Comprobante -->
                <div class="bg-white shadow-sm sm:rounded-lg border border-slate-200 p-6">
                    <h3 class="text-base font-bold text-vera-dark border-b border-slate-100 pb-2 mb-4">2. Configuración de Pago</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Método de Pago</label>
                            <select name="metodo_pago" class="w-full rounded-md border-slate-300 focus:border-vera-green focus:ring-vera-green" required>
                                <option value="">Selecciona...</option>
                                @foreach($metodosPago as $codigo => $desc)
                                <option value="{{ $codigo }}">{{ $desc }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Forma de Pago</label>
                            <select name="forma_pago" class="w-full rounded-md border-slate-300 focus:border-vera-green focus:ring-vera-green" required>
                                <option value="">Selecciona...</option>
                                @foreach($formasPago as $codigo => $desc)
                                <option value="{{ $codigo }}">{{ $desc }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Moneda</label>
                            <select name="moneda" class="w-full rounded-md border-slate-300 focus:border-vera-green focus:ring-vera-green" required>
                                <option value="MXN" selected>MXN - Peso Mexicano</option>
                                <option value="USD">USD - Dólar Estadounidense</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Bloque 3: Conceptos (MVP) -->
                <div class="bg-white shadow-sm sm:rounded-lg border border-slate-200 p-6">
                    <h3 class="text-base font-bold text-vera-dark border-b border-slate-100 pb-2 mb-4">3. Concepto a Facturar</h3>

                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Clave SAT</label>
                            <input type="text" name="clave_prod_serv" value="{{ old('clave_prod_serv') }}" class="w-full text-sm rounded-md border-slate-300" placeholder="Ej. 80111600" required>
                            @error('clave_prod_serv') <span class="text-xs text-red-500 font-bold mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Cantidad</label>
                            <input type="number" step="1" name="cantidad" class="w-full text-sm rounded-md border-slate-300" value="1" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Descripción</label>
                            <input type="text" name="descripcion" class="w-full text-sm rounded-md border-slate-300" placeholder="Descripción del servicio/producto" required>
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Precio Unitario</label>
                            <input type="number" step="0.01" name="valor_unitario" class="w-full text-sm rounded-md border-slate-300" placeholder="0.00" required>
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">IVA (16%)</label>
                            <select name="aplica_iva" class="w-full text-sm rounded-md border-slate-300 bg-slate-50">
                                <option value="1">Sí (Traslado 16%)</option>
                                <option value="0">No objeto / Exento</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="flex justify-end gap-4 mt-8">
                    <a href="{{ route('dashboard') }}" class="px-6 py-2 border border-slate-300 text-slate-700 rounded-lg font-bold hover:bg-slate-50 transition">
                        Cancelar
                    </a>
                    <button type="submit" class="px-6 py-2 bg-vera-green text-white rounded-lg font-bold hover:bg-emerald-700 shadow-sm transition">
                        Simular Timbrado PAC
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>