<x-mail::message>
# Welcome to Guide in Morocco!

Hello {{ $user->first_name }},

Thank you for registering with Guide in Morocco. Your account has been created successfully and is now active! 
You can now log in to explore tours, book guides, and manage your trips.

<x-mail::button :url="config('app.url') . '/login'">
Log In to Your Account
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
