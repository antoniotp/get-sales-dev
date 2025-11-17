<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $formLink->publicFormTemplate->title }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/css/intlTelInput.css">
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])

    <style>
        .iti.iti--allow-dropdown {
            width: 100%;
        }
    </style>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/libphonenumber-js@1.11.4/bundle/libphonenumber-min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/js/intlTelInput.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="container mx-auto p-4 sm:p-6 lg:p-8">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-8" x-data="form()">
            <div x-show="success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline" x-text="message"></span>
            </div>
            <div x-show="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <ul class="list-disc list-inside" x-show="Object.keys(errors).length > 0">
                    <template x-for="(errorMessages, fieldName) in errors" :key="fieldName">
                        <template x-for="errorMessage in errorMessages">
                            <li x-text="errorMessage"></li>
                        </template>
                    </template>
                </ul>
                {{-- Fallback message if there are no specific field errors (e.g., network error) --}}
                <span x-show="Object.keys(errors).length === 0" x-text="message"></span>
            </div>

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
                        <input type="text" name="first_name" id="first_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-1">
                    </div>
                    <div class="mb-4">
                        <label for="last_name" class="block text-sm font-medium text-gray-700">Apellido</label>
                        <input type="text" name="last_name" id="last_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-1">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                    <input type="email" name="email" id="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-1">
                </div>

                <div class="mb-4">
                    <label for="phone_number" class="block text-sm font-medium text-gray-700">Teléfono para WhatsApp</label>
                    <input type="tel" name="phone_number" id="phone_number" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-1">
                </div>

                <div class="mb-4">
                    <label for="country_code" class="block text-sm font-medium text-gray-700">País</label>
                    <select name="country_code" id="country_code" x-model="selectedCountry" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-1">
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
                        <select name="language_code" id="language_code" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-1">
                            <option value="es">Español</option>
                            <option value="ca">Catalán</option>
                            <option value="eu">Euskera</option>
                            <option value="gl">Gallego</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="timezone" class="block text-sm font-medium text-gray-700">Zona Horaria</label>
                        <select name="timezone" id="timezone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-1">
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
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-1"></textarea>
                        @elseif($field['type'] === 'select')
                            <select name="custom_fields[{{ $field['name'] }}]" id="custom_{{ $field['name'] }}"
                                    @if(in_array('required', $field['validation'] ?? [])) required @endif
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-1">
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
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-1">
                        @endif
                    </div>
                @endforeach

                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

                <div class="mt-6">
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" :disabled="submitting">
                        <span x-show="!submitting">Enviar Registro</span>
                        <span x-show="submitting">Enviando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function form() {
            return {
                selectedCountry: '',
                submitting: false,
                success: false,
                error: false,
                message: '',
                errors: {},
                iti: null,

                init() {
                    const input = this.$refs.formElement.querySelector('#phone_number');
                    this.iti = window.intlTelInput(input, {
                        utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/js/utils.js",
                        initialCountry: "auto",
                        fixDropdown: true,
                        geoIpLookup: callback => {
                            fetch("https://ipapi.co/json")
                                .then(res => res.json())
                                .then(data => callback(data.country_code))
                                .catch(() => callback("es"));
                        },
                        nationalMode: false,
                        containerClassName: 'w-full',
                    });
                },

                submitForm() {
                    this.submitting = true;
                    this.success = false;
                    this.error = false;
                    this.message = '';
                    this.errors = {};

                    if (!this.iti.isValidNumber()) {
                        this.error = true;
                        this.errors = { phone_number: ['El número de teléfono no es válido para el país seleccionado.'] };
                        this.submitting = false;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        return;
                    }

                    this.$refs.formElement.querySelector('#phone_number').value = this.iti.getNumber();

                    grecaptcha.ready(() => {
                        grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'submit'}).then((token) => {
                            document.getElementById('g-recaptcha-response').value = token;

                            const formData = new FormData(this.$refs.formElement);

                            fetch(this.$refs.formElement.action, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'Accept': 'application/json',
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.errors) {
                                    this.error = true;
                                    this.message = data.message || 'Por favor, corrija los errores.';
                                    this.errors = data.errors;
                                } else {
                                    this.success = true;
                                    this.message = data.message;
                                    this.$refs.formElement.reset();
                                    this.iti.setNumber("");
                                }
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                            })
                            .catch(() => {
                                this.error = true;
                                this.message = 'Ocurrió un error inesperado. Por favor, intente de nuevo.';
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                            })
                            .finally(() => {
                                this.submitting = false;
                            });
                        });
                    });
                }
            }
        }
    </script>
</body>
</html>
