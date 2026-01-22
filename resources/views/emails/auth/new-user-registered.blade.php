<x-mail::message>
# Nuevo usuario registrado

Se registró un nuevo usuario en GetSales con los siguientes datos:

-   **Nombre:** {{ $name }}
-   **Email:** {{ $email }}
-   **Organización:** {{ $organizationName }}
-   **Fecha de Registro:** {{ $registrationDate }}

<br>
{{ config('app.name') }}
</x-mail::message>
