<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Catat-in') }} - Login</title>
        <meta name="theme-color" content="#ffffff">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <!-- Using SF Pro / Inter font stack -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Tailwind CSS -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: { sans: ['Inter', 'sans-serif'] },
                        colors: {
                            brand: {
                                50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6',
                                600: '#2563eb', 700: '#1d4ed8', 900: '#1e3a8a',
                            }
                        },
                        boxShadow: {
                            'card': '0 4px 20px -2px rgba(0, 0, 0, 0.05)',
                        }
                    }
                }
            }
        </script>
        <style>
            body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
        </style>
    </head>
    <body class="antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-8">
            <div class="mb-8 text-center">
                <div class="w-16 h-16 bg-brand-600 rounded-2xl mx-auto flex items-center justify-center shadow-lg shadow-brand-500/30 mb-4 text-white text-2xl font-bold">
                    C
                </div>
                <h1 class="text-2xl font-bold text-slate-900">Masuk ke Catat-in</h1>
                <p class="text-sm text-slate-500 mt-2">Kelola keuangan Anda lebih mudah.</p>
            </div>

            <div class="w-full max-w-[400px] bg-white border border-slate-100 shadow-card rounded-3xl p-6 sm:p-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
