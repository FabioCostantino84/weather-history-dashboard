<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>{{ config('app.name', 'Weather Dashboard') }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  @vite(['resources/js/app.js', 'resources/css/app.css'])
</head>
<body>
  <div class="container py-4">
    @yield('content')
  </div>

  @if(session('error'))
    <div class="alert alert-danger mt-3">{{ session('error') }}</div>
  @endif
  @if(session('status'))
    <div class="alert alert-success mt-3">{{ session('status') }}</div>
  @endif
</body>
</html>
