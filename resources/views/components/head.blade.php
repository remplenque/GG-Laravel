@props(['titulo' => null])

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="Go Gym: registra tus entrenamientos, sigue tu racha y arma rutinas con un coach de IA.">
<meta name="theme-color" content="#0a101d">
<meta name="color-scheme" content="dark">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Go Gym">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

<title>{{ $titulo ? $titulo.' · '.config('app.name') : config('app.name') }}</title>

<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('icono-192.png') }}">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|space-grotesk:500,600,700&display=swap" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
