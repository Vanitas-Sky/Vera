<nav x-data="{ open: false }" class="bg-vera-dark border-b border-slate-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <!-- Logo en blanco para contrastar con el fondo oscuro -->
                        <x-application-logo class="block h-9 w-auto fill-current text-white" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Inicio') }}
                    </x-nav-link>

                    <x-nav-link :href="route('company.profile')" :active="request()->routeIs('company.profile')">
                        {{ __('Perfil Fiscal') }}
                    </x-nav-link>

                    <x-nav-link :href="route('employees.index')" :active="request()->routeIs('employees.*')">
                        {{ __('Empleados') }}
                    </x-nav-link>

                    <x-nav-link :href="route('payrolls.index')" :active="request()->routeIs('payrolls.*')">
                        {{ __('Nóminas') }}
                    </x-nav-link>

                    <x-nav-link :href="route('opex.index')" :active="request()->routeIs('expenses.*')">
                        {{ __('Rentas y servicios') }}
                    </x-nav-link>

                    <x-nav-link :href="route('invoices.index')" :active="request()->routeIs('invoices.*')">
                        {{ __('Facturas') }}
                    </x-nav-link>

                    <x-nav-link :href="route('billing.create')" :active="request()->routeIs('billing.create')">
                        {{ __('Crear Factura') }}
                    </x-nav-link>

                    <x-nav-link :href="route('conciliations.index')" :active="request()->routeIs('conciliations.*')">
                        {{ __('Conciliación Bancaria') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown (VISTA ESCRITORIO) -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <!-- Botón sin fondo blanco, texto claro -->
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-slate-300 bg-vera-dark hover:text-white focus:outline-none transition ease-in-out duration-150">
                            
                            <!-- AQUI VA EL AVATAR Y NOMBRE (ESCRITORIO) -->
                            <div class="flex items-center gap-2">
                                @if(Auth::user()->profile_photo_path)
                                    <img class="h-8 w-8 rounded-full object-cover border border-slate-600 shadow-sm" src="{{ Storage::url(Auth::user()->profile_photo_path) }}" alt="{{ Auth::user()->name }}" />
                                @else
                                    <!-- Fallback Verde Menta si no hay foto -->
                                    <div class="h-8 w-8 rounded-full bg-vera-accent flex items-center justify-center text-white text-sm font-bold shadow-sm">
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    </div>
                                @endif
                                <div class="font-semibold text-white">{{ Auth::user()->name }}</div>
                            </div>

                            <div class="ms-1 text-slate-400">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Mi Perfil') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Cerrar Sesión') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger (Menú Móvil) -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none focus:bg-slate-800 focus:text-white transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu (VISTA MÓVIL) -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-vera-dark">
        <div class="pt-2 pb-3 space-y-1 border-t border-slate-700">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('conciliations.index')" :active="request()->routeIs('conciliations.*')">
                {{ __('Conciliación Bancaria') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-slate-700">
            
            <!-- AQUI VA EL AVATAR Y NOMBRE (MÓVIL) -->
            <div class="px-4 flex items-center gap-3">
                @if(Auth::user()->profile_photo_path)
                    <img class="h-10 w-10 rounded-full object-cover border border-slate-600" src="{{ Storage::url(Auth::user()->profile_photo_path) }}" alt="{{ Auth::user()->name }}" />
                @else
                    <div class="h-10 w-10 rounded-full bg-vera-accent flex items-center justify-center text-white text-lg font-bold shadow-sm">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                @endif
                <div>
                    <div class="font-semibold text-base text-white">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-slate-400">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Mi Perfil') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Cerrar Sesión') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>