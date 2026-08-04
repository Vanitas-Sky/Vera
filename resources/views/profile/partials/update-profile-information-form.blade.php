<section>
    <header>
        <h2 class="text-lg font-medium text-vera-dark">
            {{ __('Información del Perfil') }}
        </h2>

        <p class="mt-1 text-sm text-vera-slate">
            {{ __("Actualiza la información del perfil de tu cuenta y tu dirección de correo electrónico.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <!-- 1. Sección de Foto de Perfil -->
        <div x-data="{ photoName: null, photoPreview: null }" class="col-span-6 sm:col-span-4">
            <!-- Input de archivo oculto -->
            <input type="file" id="photo" name="photo" class="hidden"
                   x-ref="photo"
                   x-on:change="
                        photoName = $refs.photo.files[0].name;
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            photoPreview = e.target.result;
                        };
                        reader.readAsDataURL($refs.photo.files[0]);
                   " />

            <x-input-label for="photo" value="{{ __('Foto de Perfil') }}" class="text-vera-dark font-bold mb-2" />

            <div class="flex items-center gap-6 mt-2">
                <!-- Avatar Actual -->
                <div x-show="! photoPreview" class="relative">
                    @if($user->profile_photo_path)
                        <img src="{{ Storage::url($user->profile_photo_path) }}" alt="{{ $user->name }}" class="h-20 w-20 rounded-full object-cover border border-slate-200 shadow-sm">
                    @else
                        <div class="h-20 w-20 rounded-full bg-vera-dark flex items-center justify-center text-white text-2xl font-bold shadow-sm">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                    @endif
                </div>

                <!-- Preview del Nuevo Avatar -->
                <div x-show="photoPreview" style="display: none;" class="relative">
                    <span class="block h-20 w-20 rounded-full bg-cover bg-no-repeat bg-center border border-vera-accent shadow-md"
                          x-bind:style="'background-image: url(\'' + photoPreview + '\');'">
                    </span>
                </div>

                <button type="button" class="px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-vera-slate uppercase tracking-widest shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-vera-accent transition ease-in-out duration-150" x-on:click.prevent="$refs.photo.click()">
                    {{ __('Seleccionar Nueva Foto') }}
                </button>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('photo')" />
        </div>

        <!-- 2. Nombre y Correo (RESTAUADOS Y OBLIGATORIOS) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <x-input-label for="name" :value="__('Nombre Completo')" class="text-vera-dark font-bold" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full border-slate-300 focus:border-vera-accent focus:ring-vera-accent rounded-md shadow-sm" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Correo Electrónico')" class="text-vera-dark font-bold" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full border-slate-300 focus:border-vera-accent focus:ring-vera-accent rounded-md shadow-sm" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div>
                        <p class="text-sm mt-2 text-gray-800">
                            {{ __('Your email address is unverified.') }}

                            <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-vera-accent">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 font-medium text-sm text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- 3. Puesto y Teléfono (LOS NUEVOS) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <x-input-label for="job_title" :value="__('Puesto o Cargo')" class="text-vera-dark font-bold" />
                <x-text-input id="job_title" name="job_title" type="text" class="mt-1 block w-full border-slate-300 focus:border-vera-accent focus:ring-vera-accent rounded-md shadow-sm" :value="old('job_title', $user->job_title)" placeholder="Ej. Contador General" />
                <x-input-error class="mt-2" :messages="$errors->get('job_title')" />
            </div>

            <div>
                <x-input-label for="phone" :value="__('Teléfono de Contacto')" class="text-vera-dark font-bold" />
                <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full border-slate-300 focus:border-vera-accent focus:ring-vera-accent rounded-md shadow-sm" :value="old('phone', $user->phone)" placeholder="Ej. 55 1234 5678" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>
        </div>

        <!-- 4. Botón de Guardar -->
        <div class="flex items-center gap-4">
            <button type="submit" class="bg-vera-green hover:bg-emerald-700 text-white font-semibold py-2 px-5 rounded-lg shadow-md transition duration-150 ease-in-out">
                {{ __('Guardar Cambios') }}
            </button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm font-semibold text-vera-accent"
                >{{ __('Guardado correctamente.') }}</p>
            @endif
        </div>
    </form>
</section>