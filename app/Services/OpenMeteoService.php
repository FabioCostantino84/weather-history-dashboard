<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache; // <-- AGGIUNTO: cache Laravel (file/redis…) 
use Illuminate\Support\Facades\Log;
use App\Models\City;

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

        // Chiave cache basata sul nome normalizzato (così "Roma" e " roma " coincidono)
        $cacheKey = 'geo:' . $this->norm($name);

        // (facoltativo) Log HIT: se già in cache
        if (Cache::has($cacheKey)) {
            Log::info('[OpenMeteo] Cache HIT geocoding', ['key' => $cacheKey]);
        }

        // Ricorda per 12 ore il risultato della geocodifica
        return Cache::remember($cacheKey, now()->addHours(12), function () use ($name, $cacheKey) {
            Log::info('[OpenMeteo] Cache MISS geocoding → chiamo API', ['key' => $cacheKey, 'q' => $name]);
            // 2) Endpoint e parametri della query
            $endpoint = 'https://geocoding-api.open-meteo.com/v1/search';
            $queryParams = [
                'name'     => $name,
                'count'    => 1, // Così prendiamo solo il risultato migliore
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

            // ✔ Confronto “rigido”: l’input deve coincidere col nome trovato (case-insensitive)
            $inputNorm = $this->norm($name);
            $matchNorm = $this->norm((string)($firstMatchCity['name'] ?? ''));

            if ($inputNorm !== $matchNorm) {
                return null; // es. "rom" ≠ "roma" → rifiutiamo
            }

            // 7) Normalizza i dati in un array
            return [
                'name'      => $firstMatchCity['name'] ?? $name,
                'country'   => $firstMatchCity['country'] ?? null,
                'latitude'  => isset($firstMatchCity['latitude'])  ? (float)$firstMatchCity['latitude']  : null,
                'longitude' => isset($firstMatchCity['longitude']) ? (float)$firstMatchCity['longitude'] : null,
            ];
        });
    }

    /**
     * Recupera le temperature orarie storiche per un intervallo di date.
     * Ritorna un array normalizzato: [['time' => 'Y-m-d H:i:s', 'temperature' => float|null], ...]
     *
     * @param  float  $lat   Latitudine (-90..90)
     * @param  float  $lon   Longitudine (-180..180)
     * @param  string $from  Data inizio (YYYY-MM-DD)
     * @param  string $to    Data fine   (YYYY-MM-DD)
     * @return array
     */
    public function fetchHourlyTemps(float $lat, float $lon, string $from, string $to): array
    {
        // 1) evitiamo valori fuori range.
        $lat = max(-90, min(90, $lat));
        $lon = max(-180, min(180, $lon));
    
        // 2) Con Carbon normalizziamo le date.
        $start = Carbon::parse($from)->startOfDay();
        $end   = Carbon::parse($to)->endOfDay();
    
        // 3) se le date sono invertite nel form, le invertiamo.
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }
    
        // --- CACHE: chiave deterministica su lat,lon e intervallo ---
        // Arrotondo lat/lon a 3 decimali per una chiave più “stabile”.
        $latKey = number_format($lat, 3, '.', '');
        $lonKey = number_format($lon, 3, '.', '');
        $cacheKey = "openmeteo:hourly:{$latKey},{$lonKey}:{$start->toDateString()}-{$end->toDateString()}";
        $ttl = now()->addHours(6); // tieni i dati per 6 ore
    
        // Se in cache → ritorno subito (HIT)
        if (Cache::has($cacheKey)) {
            Log::info('[OpenMeteo] Cache HIT hourly', ['key' => $cacheKey]);
            return Cache::get($cacheKey);
        }
    
        // 4) Endpoint API storico di Open-Meteo.
        $endpoint = 'https://archive-api.open-meteo.com/v1/archive';
    
        // 5) Query 
        $queryParams = [
            'latitude'   => $lat,
            'longitude'  => $lon,
            'start_date' => $start->toDateString(),
            'end_date'   => $end->toDateString(), // Formato YYYY-MM-DD.
            'hourly'     => 'temperature_2m',     // Temperature orarie.
            'timezone'   => 'Europe/Rome',        // Europe/Rome per orari italiani.
        ];
    
        // 6) Chiamata HTTP
        $response = Http::timeout(15)
            ->retry(2, 200) // così se fallisce la chiamata riprova 2 volte
            ->get($endpoint, $queryParams);
    
        $response->throw(); // Se l'API risponde con errore (es. 404, 500), lancia un'eccezione
    
        // 7) Converte il JSON in un array PHP
        $data = $response->json();
        
        // 8) Estrae i due array paralleli: orari e temperature
        $times = $data['hourly']['time']            ?? [];
        $temps = $data['hourly']['temperature_2m']  ?? [];
    
        // 9) Validazione dei dati ricevuti
        if (count($times) === 0 || count($times) !== count($temps)) {
            // Se i dati negli array non sono allineati, ritorniamo un array vuoto.
            Log::info('[OpenMeteo] Hourly API empty/misaligned - NOT cached', ['key' => $cacheKey]);
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
    
        // 11) Salvo in cache SOLO se ho qualcosa (evito di memorizzare vuoti per ore)
        Cache::put($cacheKey, $records, $ttl);
        Log::info('[OpenMeteo] Cache MISS hourly → stored', ['key' => $cacheKey, 'count' => count($records)]);
    
        // 12) Ritorniamo i record normalizzati
        return $records;
    }
    
    /**
     * Salva nel database i record orari di temperatura di una città.
     * Se esiste già una riga con stessa città e stessa ora → fa UPDATE.
     * Altrimenti → fa INSERT.
     *
     * @param  City   $city
     * @param  array  $hourlyRecords
     * @return int
     */
    public function saveHourlyTemps(City $city, array $hourlyRecords): int
    {
        $now = Carbon::now();
        $rowsToUpsert = [];
    
        foreach ($hourlyRecords as $record) {
            if (!isset($record['time'])) {
                continue;
            }
    
            $rowsToUpsert[] = [
                'city_id'     => $city->id,
                'recorded_at' => Carbon::parse($record['time'])->format('Y-m-d H:i:s'),
                'temperature' => array_key_exists('temperature', $record) && $record['temperature'] !== null
                                    ? (float) $record['temperature']
                                    : null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }
    
        if (empty($rowsToUpsert)) {
            return 0;
        }
    
        $totalAffectedRows = 0;
        foreach (array_chunk($rowsToUpsert, 500) as $chunk) {
            $affected = DB::table('weather_records')->upsert(
                values: $chunk,
                uniqueBy: ['city_id', 'recorded_at'],
                update: ['temperature', 'updated_at']
            );
            $totalAffectedRows += $affected;
        }
    
        return $totalAffectedRows;
    }

    /**
     * Normalizza stringhe per confronto semplice:
     * - minuscolo
     * - trim spazi
     */
    private function norm(string $value): string
    {
        return strtolower(trim($value));
    }
}