<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Services\OpenMeteoService;
use App\Http\Requests\CitySearchRequest;
use App\Http\Requests\StatsRequest;
use App\Models\WeatherRecord;
use Carbon\Carbon;

class CityController extends Controller
{
    /**
     * Riceve dall'utente il nome della città attraverso il form, e lo cerca tramite l'API OpenMeteo.
     * Salva/aggiorna il record nel DB.
     */
    
    public function store(CitySearchRequest $request, OpenMeteoService $geo)
    {
        // 1) Validazione input
        // Con la FormRequest l'input è già validato/pulito: uso validated()
        $data = $request->validated();
        // Siamo sicuri che sia stato digitato un 'name'.
        // Che sia una stringa e non un numero ad esempio. 
        // Che sia composto da almeno 2 caratteri.
    
        // 2) Chiamiamo OpenMeteoService
        try {
            $match = $geo->searchCity($data['name']);
        } catch (\Throwable $e) {
            // Throwable lo usiamo per catturare qualsiasi tipo di errore (HTTP, di rete/timeout/DNS o bug).
            // Se l'API fallisce, restituiamo un errore all'utente e torniamo alla pagina precedente.
            return back()->with('error', 'Servizio non disponibile, riprova più tardi.');
        }
    
        // 3) Se non troviamo nessuna città, facciamo un messaggio all'utente
        if (!$match || $match['latitude'] === null || $match['longitude'] === null) {
            return back()->with('error', 'Città non trovata.');
        }
    
        // 4) Salvataggio nel DB (se esiste già la aggiorna altrimenti la crea) si evitano duplicati nel DB.
        $city = City::updateOrCreate(
            ['name' => $match['name'], 'country' => $match['country']], 
            ['latitude' => $match['latitude'], 'longitude' => $match['longitude']]
        );
    
        // 5) Facciamo un redirect alla pagina con le statistiche relative alla città
        return redirect()->route('cities.stats', $city);
    }
    
    public function stats(City $city, OpenMeteoService $meteo, StatsRequest $request)
    {
        // 1) Prendiamo le date validate dalla StatsRequest.
        // Se l’utente ha lasciato vuoto, arrivano come null.
        $fromInput = $request->validated()['from'] ?? null;
        $toInput   = $request->validated()['to']   ?? null;
    
        // 2) Se mancano le date, usiamo il default = ultimi 7 giorni.
        if (!$fromInput || !$toInput) {
            $fromInput = Carbon::now()->subDays(7)->toDateString(); // es. "2025-08-22"
            $toInput   = Carbon::now()->toDateString();             // es. "2025-08-29"
        }
    
        // 3) Normalizziamo le date.
        // startOfDay = inizio giornata 00:00
        // endOfDay   = fine giornata 23:59:59
        $from = Carbon::parse($fromInput)->startOfDay();
        $to   = Carbon::parse($toInput)->endOfDay();
    
        // 4) Chiamo l’API di OpenMeteo per scaricare i dati orari
        // e li salvo nel DB (inserisce o aggiorna se già esistono).
        try {
            $records = $meteo->fetchHourlyTemps(
                $city->latitude,
                $city->longitude,
                $from->toDateString(),
                $to->toDateString()
            );
    
            $meteo->saveHourlyTemps($city, $records);
        } catch (\Throwable $e) {
            // Se qualcosa va storto (es. internet non disponibile, API down),
            // mando l’utente indietro con un messaggio di errore.
            return back()->with('error', 'Servizio meteo non disponibile, riprova più tardi.');
        }
    
        // 5) Creo una query per rileggere i dati dal DB nell’intervallo scelto.
        $baseQuery = WeatherRecord::where('city_id', $city->id)
            ->whereBetween('recorded_at', [$from, $to]);
    
        // 6) Calcolo statistiche semplici:
        // - temperatura media
        // - minima
        // - massima
        $stats = [
            'avg' => round((float) $baseQuery->clone()->avg('temperature'), 1),
            'min' => (clone $baseQuery)->min('temperature'),
            'max' => (clone $baseQuery)->max('temperature'),
        ];
    
        // 7) Ottengo le righe singole ordinate per data/ora
        // (ci serviranno per tabella o grafico).
        $rows = (clone $baseQuery)
            ->orderBy('recorded_at', 'asc')
            ->get(['recorded_at', 'temperature']);
    
        // 8) Ritorno la view con tutti i dati:
        // - la città
        // - le date (da rimettere nei campi del form)
        // - le statistiche
        // - le righe di dettaglio
        return view('city.stats', [
            'city'      => $city,
            'fromInput' => $from->toDateString(),
            'toInput'   => $to->toDateString(),
            'stats'     => $stats,
            'rows'      => $rows,
        ]);
    }
}
