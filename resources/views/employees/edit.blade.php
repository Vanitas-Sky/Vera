<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('employees.index') }}" class="text-vera-gray hover:text-vera-dark transition">
                &larr; Volver
            </a>
            <h2 class="font-semibold text-xl text-vera-dark leading-tight">
                Editar Empleado: {{ $employee->full_name }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 p-6">
                <form action="{{ route('employees.update', $employee->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nombre Completo -->
                        <div class="md:col-span-2">
                            <label for="full_name" class="block text-sm font-bold text-slate-700 mb-1">Nombre Completo</label>
                            <input type="text" name="full_name" id="full_name" value="{{ old('full_name', $employee->full_name) }}" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-vera-green focus:ring focus:ring-emerald-200 focus:ring-opacity-50" required>
                            @error('full_name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <!-- RFC -->
                        <div>
                            <label for="rfc" class="block text-sm font-bold text-slate-700 mb-1">RFC</label>
                            <input type="text" name="rfc" id="rfc" value="{{ old('rfc', $employee->rfc) }}" maxlength="13" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-vera-green focus:ring focus:ring-emerald-200 uppercase font-mono" required>
                            @error('rfc') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <!-- CURP -->
                        <div>
                            <label for="curp" class="block text-sm font-bold text-slate-700 mb-1">CURP</label>
                            <input type="text" name="curp" id="curp" value="{{ old('curp', $employee->curp) }}" maxlength="18" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-vera-green focus:ring focus:ring-emerald-200 uppercase font-mono" required>
                            @error('curp') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <!-- NSS -->
                        <div>
                            <label for="nss" class="block text-sm font-bold text-slate-700 mb-1">Número de Seguridad Social (NSS)</label>
                            <input type="text" name="nss" id="nss" value="{{ old('nss', $employee->nss) }}" maxlength="11" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-vera-green focus:ring focus:ring-emerald-200 font-mono" required>
                            @error('nss') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <!-- Salario Base -->
                        <div>
                            <label for="base_salary" class="block text-sm font-bold text-slate-700 mb-1">Salario Base Bruto (Mensual)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500 font-bold">$</span>
                                <input type="number" step="0.01" name="base_salary" id="base_salary" value="{{ old('base_salary', $employee->base_salary) }}" class="w-full pl-8 border-slate-300 rounded-lg shadow-sm focus:border-vera-green focus:ring focus:ring-emerald-200" required>
                            </div>
                            @error('base_salary') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <!-- Estado del Empleado (Activo/Inactivo) -->
                        <div class="md:col-span-2 bg-slate-50 p-4 rounded-lg border border-slate-200 mt-2">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" class="rounded border-slate-300 text-vera-green shadow-sm focus:border-emerald-300 focus:ring focus:ring-emerald-200 focus:ring-opacity-50 w-5 h-5" {{ $employee->is_active ? 'checked' : '' }}>
                                <span class="ml-3 text-sm font-bold text-slate-700">Empleado Activo</span>
                            </label>
                            <p class="text-xs text-slate-500 mt-1 ml-8">Si desmarcas esta casilla, el empleado quedará como inactivo y no se le generarán más recibos en los cálculos de nómina, pero su historial se mantendrá intacto.</p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-6">
                        <a href="{{ route('employees.index') }}" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-700 font-bold hover:bg-slate-50 transition shadow-sm">Cancelar</a>
                        <button type="submit" class="px-4 py-2 bg-vera-dark text-white rounded-lg font-bold hover:bg-slate-800 transition shadow-sm">Guardar Cambios</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>