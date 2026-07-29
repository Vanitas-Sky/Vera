<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('employees.index') }}" class="text-vera-gray hover:text-vera-dark transition">
                &larr; Volver
            </a>
            <h2 class="font-semibold text-xl text-vera-dark leading-tight">
                {{ __('Registrar Nuevo Empleado') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200">
                <div class="p-6 bg-white border-b border-slate-200">
                    
                    <form method="POST" action="{{ route('employees.store') }}" class="space-y-6">
                        @csrf

                        <!-- Nombre Completo -->
                        <div>
                            <label for="full_name" class="block text-sm font-medium text-vera-dark">Nombre Completo</label>
                            <input id="full_name" name="full_name" type="text" value="{{ old('full_name') }}" required placeholder="Ej. Juan Pérez Gómez" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:ring-vera-green focus:border-vera-green sm:text-sm">
                            <p class="mt-1 text-xs text-slate-400">Tal como aparece en su Constancia de Situación Fiscal (sin abreviaturas).</p>
                            @error('full_name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- RFC -->
                            <div>
                                <label for="rfc" class="block text-sm font-medium text-vera-dark">RFC</label>
                                <input id="rfc" name="rfc" type="text" value="{{ old('rfc') }}" required minlength="13" maxlength="13" pattern="[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}" title="Debe ser un RFC válido de Persona Física (13 caracteres)." placeholder="Ej. PEGJ880326XXX" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:ring-vera-green focus:border-vera-green sm:text-sm uppercase">
                                <p class="mt-1 text-xs text-slate-400">13 caracteres (4 letras, 6 números, 3 alfanuméricos).</p>
                                @error('rfc') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <!-- CURP -->
                            <div>
                                <label for="curp" class="block text-sm font-medium text-vera-dark">CURP</label>
                                <input id="curp" name="curp" type="text" value="{{ old('curp') }}" required minlength="18" maxlength="18" pattern="[A-Z]{4}\d{6}[A-Z]{6}\d{2}" title="Debe ser una CURP válida de 18 caracteres." placeholder="Ej. PEGJ880326HMCMNX01" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:ring-vera-green focus:border-vera-green sm:text-sm uppercase">
                                <p class="mt-1 text-xs text-slate-400">18 caracteres (letras y números sin espacios).</p>
                                @error('curp') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- NSS -->
                            <div>
                                <label for="nss" class="block text-sm font-medium text-vera-dark">NSS (Número de Seguridad Social)</label>
                                <input id="nss" name="nss" type="text" value="{{ old('nss') }}" required minlength="11" maxlength="11" pattern="\d{11}" title="Debe contener exactamente 11 números, sin espacios ni guiones." placeholder="Ej. 34123456789" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:ring-vera-green focus:border-vera-green sm:text-sm">
                                <p class="mt-1 text-xs text-slate-400">Ingresa 11 dígitos continuos, sin espacios ni guiones.</p>
                                @error('nss') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <!-- Salario Base -->
                            <div>
                                <label for="base_salary" class="block text-sm font-medium text-vera-dark">Salario Base Mensual (MXN)</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-500 sm:text-sm">$</span>
                                    </div>
                                    <input id="base_salary" name="base_salary" type="number" step="0.01" min="0" value="{{ old('base_salary') }}" required placeholder="0.00" class="block w-full pl-7 border-slate-300 rounded-md focus:ring-vera-green focus:border-vera-green sm:text-sm">
                                </div>
                                <p class="mt-1 text-xs text-slate-400">Salario bruto antes de retenciones de ISR e IMSS.</p>
                                @error('base_salary') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit" class="bg-vera-green hover:bg-emerald-400 text-white font-bold py-2 px-6 rounded-lg shadow-sm transition">
                                Guardar Empleado
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>