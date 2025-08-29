{{-- resources/views/city/stats.blade.php --}}
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Statistiche città</title>
</head>
<body>
    <h1>Statistiche per {{ $city->name }} {{ $city->country ? '(' . $city->country . ')' : '' }}</h1>

    <p>Qui mostreremo le medie, minimi, massimi e la tabella dei dati meteo.</p>
</body>
</html>
