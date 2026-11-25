<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Munaasab Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @php($canAccessAdmin = auth()->user()?->can('access-admin') === true)
    <div
        id="app"
        data-login-url="{{ route('login.store', [], false) }}"
        data-logout-url="{{ route('logout', [], false) }}"
        data-csrf-token="{{ csrf_token() }}"
        data-login-error="{{ $errors->first() }}"
        data-old-email="{{ old('email') }}"
        data-can-access-admin="{{ $canAccessAdmin ? '1' : '0' }}"
        data-admin-name="{{ $canAccessAdmin ? auth()->user()->name : '' }}"
    ></div>
</body>
</html>
