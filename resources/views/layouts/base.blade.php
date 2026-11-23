<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', config('moz.nome')) · {{ config('moz.nome') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/moz.css') }}">
    @stack('head')
</head>
<body class="@yield('corpo')">
@yield('conteudo')
<script src="{{ asset('js/vendor/qrcode-generator.js') }}"></script>
@stack('libs')
<script src="{{ asset('js/moz.js') }}"></script>
@stack('scripts')
</body>
</html>
