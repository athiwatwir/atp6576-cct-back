<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'ชำระเงิน' }} | {{ config('payment.merchant_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/payment.js'])
    @stack('head')
</head>

<body class="min-h-screen bg-gray-50 font-google-sans text-gray-800 antialiased">
    <main class="mx-auto w-full max-w-lg px-4 py-6 sm:py-10">
        @yield('content')
    </main>
    @stack('scripts')
</body>

</html>
