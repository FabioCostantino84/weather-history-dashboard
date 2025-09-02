<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>{{ config('app.name', 'WEATHER HISTORY By Innovhead') }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  {{-- Favicon --}}
  <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=1">
  <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}?v=1">

  {{-- Preload dello sfondo per ridurre il “flash” --}}
  <link rel="preload" as="image" href="{{ asset('images/bg-seasons.webp') }}">

  {{-- Carica PRIMA il CSS (via Vite), POI il JS --}}
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-main">
  {{-- Precarica anche via <img> invisibile (ok tenerlo) --}}
  <img
    src="{{ asset('images/bg-seasons.webp') }}"
    alt=""
    fetchpriority="high"
    decoding="async"
    width="1" height="1"
    style="position:absolute; width:1px; height:1px; opacity:0; pointer-events:none;"
  />

  <div class="container py-4 page-content">
    @yield('content')

    @if(session('error'))
      <div class="alert alert-danger mt-3">{{ session('error') }}</div>
    @endif
    @if(session('status'))
      <div class="alert alert-success mt-3">{{ session('status') }}</div>
    @endif
  </div>
</body>
</html>