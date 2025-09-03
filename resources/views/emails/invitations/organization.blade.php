<x-mail::message>
# You have been invited to join {{ $invitation->organization->name }}

Hello,

You have been invited by **{{ $invitation->inviter->name }}** to join the **{{ $invitation->organization->name }}** organization as a **{{ $invitation->role->name }}**.

To accept this invitation, please click the button below:

<x-mail::button :url="$acceptUrl">
Accept Invitation
</x-mail::button>

If you were not expecting this invitation, you can ignore this email.

This invitation will expire in {{ $invitation->expires_at->diffForHumans() }}.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>