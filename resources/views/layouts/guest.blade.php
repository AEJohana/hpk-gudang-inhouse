<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'WMS HPK') }} - PT Hydraxle Perkasa Karoseri</title>
        <link rel="icon" type="image/webp" href="{{ asset('images/logo_hpk.webp') }}">

        <!-- Offline Local Fonts & FontAwesome -->
        <link rel="stylesheet" href="{{ asset('fonts/poppins.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-800 min-h-screen relative flex items-center justify-center p-4">
        
        <!-- Animated Background from referens -->
        <div class="login-background">
            <ul class="circles">
                <li></li><li></li><li></li><li></li><li></li>
                <li></li><li></li><li></li><li></li><li></li>
            </ul>
        </div>

        <div class="relative w-full max-w-md my-auto z-10">
            {{ $slot }}
        </div>

    </body>
</html>
