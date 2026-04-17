<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Lawangsewu') }}</title>
        <link rel="icon" type="image/gif" href="/logo-pa.gif">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        <script>
            // Escape iframe: jika halaman Inertia (LawangsewuLayout) termuat di dalam iframe,
            // paksa navigasi ke top window agar sidebar tidak nesting/dobel.
            if (window.self !== window.top) {
                window.top.location.replace(window.location.href);
            }
        </script>
        @inertia
    </body>
</html>
