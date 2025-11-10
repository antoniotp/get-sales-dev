<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $formLink->publicFormTemplate->title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="container mx-auto p-4 sm:p-6 lg:p-8">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-8" x-data="form()">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ $formLink->publicFormTemplate->title }}</h1>
            @if($formLink->publicFormTemplate->description)
                <p class="text-gray-600 mb-6">{{ $formLink->publicFormTemplate->description }}</p>
            @endif

            <form method="POST" action="{{ route('public-forms.store', $formLink->uuid) }}" x-on:submit.prevent="submitForm" x-ref="formElement">
                @csrf

                {{-- Honeypot field for bot protection --}}
                <div class="hidden" aria-hidden="true">
                    <label for="honeypot_field">Do not fill this field</label>
                    <input type="text" name="honeypot_field" id="honeypot_field" tabindex="-1" autocomplete="off">
                </div>

                <h2 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-4">Datos del Tutor</h2>

                {{-- Base Fields for Contact --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label for="first_name" class="block text-sm font-medium text-gray-700">Nombre</label>
                        <input type="text" name="first_name" id="first_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div class="mb-4">
                        <label for="last_name" class="block text-sm font-medium text-gray-700">Apellido</label>
                        <input type="text" name="last_name" id="last_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                    <input type="email" name="email" id="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div class="mb-4">
                    <label for="phone_number" class="block text-sm font-medium text-gray-700">Teléfono para WhatsApp</label>
                    <input type="tel" name="phone_number" id="phone_number" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div class="mb-4">
                    <label for="country_code" class="block text-sm font-medium text-gray-700">País</label>
                    <select name="country_code" id="country_code" x-model="selectedCountry" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Seleccione un país</option>
                        <option value="ES">España</option>
                        <option value="AR">Argentina</option>
                        <option value="MX">México</option>
                    </select>
                </div>

                {{-- Dynamic Language and Timezone based on Country --}}
                <div x-show="selectedCountry === 'ES'" x-transition>
                    <div class="mb-4">
                        <label for="language_code" class="block text-sm font-medium text-gray-700">Idioma</label>
                        <select name="language_code" id="language_code" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="es">Español</option>
                            <option value="ca">Catalán</option>
                            <option value="eu">Euskera</option>
                            <option value="gl">Gallego</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="timezone" class="block text-sm font-medium text-gray-700">Zona Horaria</label>
                        <select name="timezone" id="timezone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="Europe/Madrid">Europa/Madrid</option>
                            <option value="Atlantic/Canary">Atlántico/Canarias</option>
                        </select>
                    </div>
                </div>

                <hr class="my-6">

                <h2 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-4">Datos del Paciente y Otros</h2>

                {{-- Dynamic Custom Fields --}}
                @foreach($formLink->publicFormTemplate->custom_fields_schema as $field)
                    <div class="mb-4">
                        <label for="custom_{{ $field['name'] }}" class="block text-sm font-medium text-gray-700">
                            {{ $field['label'] }}
                            @if(in_array('required', $field['validation'] ?? []))
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                        @if($field['type'] === 'textarea')
                            <textarea name="custom_fields[{{ $field['name'] }}]" id="custom_{{ $field['name'] }}"
                                      placeholder="{{ $field['placeholder'] ?? '' }}"
                                      @if(in_array('required', $field['validation'] ?? [])) required @endif
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        @elseif($field['type'] === 'select')
                            <select name="custom_fields[{{ $field['name'] }}]" id="custom_{{ $field['name'] }}"
                                    @if(in_array('required', $field['validation'] ?? [])) required @endif
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Seleccione una opción</option>
                                @foreach($field['options'] ?? [] as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        @elseif($field['type'] === 'checkbox')
                            <input type="checkbox" name="custom_fields[{{ $field['name'] }}]" id="custom_{{ $field['name'] }}"
                                   value="1"
                                   class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @else
                            <input type="{{ $field['type'] }}" name="custom_fields[{{ $field['name'] }}]" id="custom_{{ $field['name'] }}"
                                   placeholder="{{ $field['placeholder'] ?? '' }}"
                                   @if(in_array('required', $field['validation'] ?? [])) required @endif
                                   @if(isset($field['step'])) step="{{ $field['step'] }}" @endif
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @endif
                    </div>
                @endforeach

                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

                <div class="mt-6">
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Enviar Registro
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function form() {
            return {
                selectedCountry: '',
                submitForm() {
                    const form = this.$refs.formElement;
                    grecaptcha.ready(() => {
                        grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'submit'}).then((token) => {
                            document.getElementById('g-recaptcha-response').value = token;
                            form.submit();
                        });
                    });
                }
            }
        }
    </script>
</body>
</html>
