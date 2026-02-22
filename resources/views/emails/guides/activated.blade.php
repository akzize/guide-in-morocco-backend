<x-mail::message>
# Account Activated

Hello {{ $user->first_name }},

Great news! Your guide account for Guide in Morocco has been successfully activated by our administrators.
You can now log in and start managing your profile, tours, and bookings.

<x-mail::button :url="config('app.url') . '/login'">
Log In to Your Account
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
