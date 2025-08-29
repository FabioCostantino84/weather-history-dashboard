<?php

namespace App\Services;

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

        // 7) Normalizza i dati in un array chiaro e coerente
        return [
            'name'      => $firstMatchCity['name'] ?? $name,
            'country'   => $firstMatchCity['country'] ?? null,
            'latitude'  => isset($firstMatchCity['latitude'])  ? (float)$firstMatchCity['latitude']  : null,
            'longitude' => isset($firstMatchCity['longitude']) ? (float)$firstMatchCity['longitude'] : null,
        ];    
    }
}