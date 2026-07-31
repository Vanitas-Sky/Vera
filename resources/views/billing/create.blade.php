<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <h2 class="font-semibold text-xl text-vera-dark leading-tight">
                {{ __('Emitir Nueva Factura (Ingreso)') }}
            </h2>
        </div>
    </x-slot>

    <!-- Animación CSS para errores (Shake Effect) -->
    <style>
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-4px); }
            40%, 80% { transform: translateX(4px); }
        }
        .animate-shake {
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }
    </style>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-8">
                
                <form action="{{ route('billing.store') }}" method="POST" class="space-y-8">
                    @csrf

                    <!-- SECCIÓN 1: Datos del Cliente (Receptor) -->
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 border-b pb-2">1. Datos del Cliente (Receptor)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Nombre o Razón Social</label>
                                <input type="text" name="receptor_nombre" value="{{ old('receptor_nombre') }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. Juan Pérez Gómez" required>
                                <p class="text-[11px] text-slate-400 mt-1">Exactamente como aparece en su Constancia Fiscal (sin S.A. de C.V. para CFDI 4.0).</p>
                                @error('receptor_nombre') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">RFC del Receptor</label>
                                <input type="text" name="receptor_rfc" value="{{ old('receptor_rfc') }}" class="w-full rounded-md border-slate-300 shadow-sm uppercase focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. CACX7605101P8" required>
                                <p class="text-[11px] text-slate-400 mt-1">12 o 13 caracteres. Se validará criptográficamente.</p>
                                @error('receptor_rfc') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Código Postal Fiscal</label>
                                <input type="text" name="receptor_cp" value="{{ old('receptor_cp') }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. 06000" maxlength="5" required>
                                <p class="text-[11px] text-slate-400 mt-1">5 dígitos exactos. No se permite ingresar 00000.</p>
                                @error('receptor_cp') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Régimen Fiscal del Receptor</label>
                                <select name="receptor_regimen" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                                    <option value="">Selecciona un régimen...</option>
                                    @foreach($regimenes as $key => $value)
                                        <option value="{{ $key }}" {{ old('receptor_regimen') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                    @endforeach
                                </select>
                                @error('receptor_regimen') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN 2: Detalles del Comprobante -->
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 border-b pb-2">2. Detalles del Comprobante</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Uso del CFDI</label>
                                <select name="uso_cfdi" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                                    <option value="">Selecciona el uso...</option>
                                    @foreach($usosCfdi as $key => $value)
                                        <option value="{{ $key }}" {{ old('uso_cfdi') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                    @endforeach
                                </select>
                                @error('uso_cfdi') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Moneda</label>
                                <select name="moneda" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                                    <option value="MXN" {{ old('moneda') == 'MXN' ? 'selected' : '' }}>MXN - Peso Mexicano</option>
                                    <option value="USD" {{ old('moneda') == 'USD' ? 'selected' : '' }}>USD - Dólar Estadounidense</option>
                                </select>
                                @error('moneda') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Método de Pago</label>
                                <select name="metodo_pago" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                                    <option value="">Selecciona el método...</option>
                                    @foreach($metodosPago as $key => $value)
                                        <option value="{{ $key }}" {{ old('metodo_pago') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                    @endforeach
                                </select>
                                @error('metodo_pago') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Forma de Pago</label>
                                <select name="forma_pago" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                                    <option value="">Selecciona la forma...</option>
                                    @foreach($formasPago as $key => $value)
                                        <option value="{{ $key }}" {{ old('forma_pago') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                    @endforeach
                                </select>
                                @error('forma_pago') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN 3: Concepto (Partida) -->
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 border-b pb-2">3. Concepto a Facturar</h3>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            
                            <div class="md:col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Clave SAT (Prod/Serv)</label>
                                <input type="text" name="clave_prod_serv" value="{{ old('clave_prod_serv') }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. 80111600" maxlength="8" required>
                                <p class="text-[11px] text-slate-400 mt-1">8 dígitos exactos del catálogo. No se permite 00000000.</p>
                                @error('clave_prod_serv') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>

                            <div class="md:col-span-3">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Descripción del Servicio / Producto</label>
                                <input type="text" name="descripcion" value="{{ old('descripcion') }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. Servicios de consultoría financiera correspondientes al mes de Julio" required>
                                @error('descripcion') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>

                            <div class="md:col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Cantidad</label>
                                <input type="number" step="0.01" name="cantidad" value="{{ old('cantidad', 1) }}" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                                @error('cantidad') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Valor Unitario (Sin Impuestos)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-slate-500">$</span>
                                    <input type="number" step="0.01" name="valor_unitario" value="{{ old('valor_unitario') }}" class="w-full pl-8 rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Ej. 15000.00" required>
                                </div>
                                @error('valor_unitario') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>

                            <div class="md:col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Desglose de IVA</label>
                                <select name="aplica_iva" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                                    <option value="1" {{ old('aplica_iva', '1') == '1' ? 'selected' : '' }}>Sí (Tasa 16%)</option>
                                    <option value="0" {{ old('aplica_iva') == '0' ? 'selected' : '' }}>No (Exento/0%)</option>
                                </select>
                                @error('aplica_iva') <span class="text-xs font-bold text-red-500 block mt-1 animate-shake">⚠️ {{ $message }}</span> @enderror
                            </div>

                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-slate-200">
                        <button type="submit" class="px-6 py-2 bg-emerald-600 text-white rounded-md font-bold hover:bg-emerald-700 transition shadow-md flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            Timbrar Factura
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>