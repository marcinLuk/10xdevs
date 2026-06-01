<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-white min-h-screen flex items-center justify-center font-sans text-gray-900 antialiased">
        <div class="w-full max-w-md mx-auto px-6 py-12">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
                <div class="flex justify-center mb-6">
                    <x-application-logo class="w-20 h-20" />
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-2">Welcome to GardenLog</h1>
                <p class="text-gray-500 mb-8">Track your garden tasks and ask AI about your growing history.</p>

                <div class="flex flex-col gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-wider hover:bg-gray-700 transition ease-in-out duration-150">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-wider hover:bg-gray-700 transition ease-in-out duration-150">
                            Register
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-sm text-gray-700 uppercase tracking-wider hover:bg-gray-50 transition ease-in-out duration-150">
                            Log in
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </body>
</html>
