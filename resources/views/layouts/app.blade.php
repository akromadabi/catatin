<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $appName = \App\Models\Setting::get('app_name', 'Catat-in');
            $appDesc = \App\Models\Setting::get('app_description', 'Aplikasi pencatatan keuangan UMKM terpintar.');
            $appIcon = \App\Models\Setting::get('app_icon') ? asset('storage/' . \App\Models\Setting::get('app_icon')) : asset('favicon.png');
            $appPhoto = \App\Models\Setting::get('app_photo') ? asset('storage/' . \App\Models\Setting::get('app_photo')) : asset('favicon.png');
        @endphp
        <title>{{ $appName }}</title>
        <meta name="description" content="{{ $appDesc }}">
        <meta property="og:title" content="{{ $appName }}">
        <meta property="og:description" content="{{ $appDesc }}">
        <meta property="og:image" content="{{ $appPhoto }}">
        <meta property="og:type" content="website">
        <link rel="icon" type="image/png" href="{{ $appIcon }}">
        <link rel="apple-touch-icon" href="{{ $appIcon }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts (Using CDN instead of Vite because no Node.js environment) -->
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
