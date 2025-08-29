<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;


class OpenMeteoService
{
    /**
     * Cerco la città usando l'API di geocoding di Open-Meteo. 
     * Ritorna un array oppure null se non viene trovato un match. 
     * 
     * @param string $name Nome della città inserita nel form (esempio. "Roma")
     * @return array|null ['name', 'country', 'latitude', 'longitude'] o null
     */

    public function searchCity(string $name): ?array
    {
        // 1) Pulizia input, elimina gli spazi all'inizio e alla fine (esempio. " Roma ")
        $name = trim($name);
        if ($name === '') {
            return null; // non è stata digitata nessuna città da cercare.
        }

        // 2) Endpoint e parametri della query
        $endpoint = 'https://geocoding-api.open-meteo.com/v1/search';
        $queryParams = [
            'name'     => $name,
            'count'    => 1, // Così prendiamo solo il risutato migliore
            'language' => 'it',
        ];

        // 3) Chiamata HTTP
        $response = Http::timeout(10)
            ->retry(2, 200) // così se fallisce la chiamata riprova 2 volte
            ->get($endpoint, $queryParams);

        // 4) Se l'API risponde con errore (es. 404, 500), lancia un'eccezione
        $response->throw();

        // 5) Converte il JSON in un array PHP
        $responseData = $response->json();

        // 6) Prende la prima città trovata
        $firstMatchCity = $responseData['results'][0] ?? null;
        if (!$firstMatchCity) {
            return null; // null se non trova risultati
        }

        // 7) Normalizza i dati in un array
        return [
            'name'      => $firstMatchCity['name'] ?? $name,
            'country'   => $firstMatchCity['country'] ?? null,
            'latitude'  => isset($firstMatchCity['latitude'])  ? (float)$firstMatchCity['latitude']  : null,
            'longitude' => isset($firstMatchCity['longitude']) ? (float)$firstMatchCity['longitude'] : null,
        ];    
    }

    /**
    * Recupera le temperature orarie storiche per un intervallo di date.
    * Ritorna (per ora) la query costruita; nel passo successivo faremo la chiamata HTTP e la normalizzazione.
    *
    * @param  float  $lat   Latitudine (-90..90)
    * @param  float  $lon   Longitudine (-180..180)
    * @param  string $from  Data inizio (YYYY-MM-DD)
    * @param  string $to    Data fine   (YYYY-MM-DD)
    * @return array         Parametri di query pronti per l'API
    */
    public function fetchHourlyTemps(float $lat, float $lon, string $from, string $to): array
    {
        // 1) evitiamo valori fuori range.
        $lat = max(-90, min(90,$lat));
        $lon = max(-180, min(180,$lon));

        // 2) Con Carbon normalizziamo le date.
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        // 3) se le date sono invertite nel form, le invertiamo.
        if ($start->gt($end)) {
            [$start,$end] = [$end, $start];
        }

        // 4) Endpoint API storico di Open-Meteo.
        $endpoint = 'https://archive-api.open-meteo.com/v1/archive';

        // 5) Query 
        $queryParams = [
            'latitude'   => $lat,
            'longitude'  => $lon,
            'start_date' => $start->toDateString(),
            'end_date'   => $end->toDateString(), // Formato YYYY-MM-DD.
            'hourly'     => 'temperature_2m', // Temperature orarie.
            'timezone'   => 'Europe/Rome', // Europe/Rome per orari italiani.
        ];

        // 6) Chiamata HTTP
        $response = Http::timeout(15)
            ->retry(2, 200) // così se fallisce la chiamata riprova 2 volte
            ->get($endpoint, $queryParams);
        
        $response->throw(); // Se l'API risponde con errore (es. 404, 500), lancia un'eccezione

        // 7) Converte il JSON in un array PHP
        $data = $response->json();
        
        // 8) Estrae i due array paralleli: orari e temperature
        $times  = $data['hourly']['time']           ?? [];
        $temps  = $data['hourly']['temperature_2m'] ?? [];

        // 9) Validazione dei dati ricevuti
        if (count($times) === 0 || count($times) !== count($temps)) {
            // Se i dati negli array non sono allineati, ritorniamo un array vuoto.
            return [];
        }

        // 10) Creiamo lista ordinata con ora e temperature
        $records = [];
        for ($i = 0, $n = count($times); $i < $n; $i++) {
            $iso  = $times[$i];             
            $temp = $temps[$i];             

            $records[] = [
                // Mi assicuro che i dati siano coerenti per DB/UI
                'time'        => Carbon::parse($iso)->format('Y-m-d H:i:s'),
                'temperature' => is_null($temp) ? null : (float) $temp,
            ];
        }

        // 11) Ritorniamo i record normalizzati
        return $records;


    }
}
