<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\WeatherRecord;
use App\Services\OpenMeteoService;
use App\Http\Requests\DashboardRequest; // FormRequest unica per città + date
use Carbon\Carbon;

class CityController extends Controller
{
    /**
     * Pagina unica: riceve dall'utente il nome della città + (opzionale) range di date.
     * 1) Valida/pulisce l'input (FormRequest)
     * 2) Se mancano le date nel form di default mostro ultimi 7 giorni.
     * 3) Cerca la città tramite l'API Open-Meteo (geocoding)
     * 4) Se non troviamo nessuna città, messaggio all'utente.
     * 5) Salva/aggiorna la città nel DB (evita duplicati)
     * 6) Scarica le temperature orarie nell’intervallo e le salva (upsert)
     * 7) Creo una query base per rileggere i dati dal DB nell’intervallo scelto.
     * 8) Calcoliamo le statistiche in base al from e to.
     * 9) Medie GIORNALIERE (richiesta dall’assignment)
     * 10) Ritorno la view unica (form + risultati)
     */
    public function dashboard(DashboardRequest $request, OpenMeteoService $meteo)
    {
        // 1) Input già validato/pulito dalla FormRequest
        $data      = $request->validated();
        $nameInput = $data['name'] ?? null; // es. "Roma"
        $fromInput = $data['from'] ?? null; // es. "2025-08-01"
        $toInput   = $data['to']   ?? null; // es. "2025-08-07"

        // Prima apertura pagina: nessuna ricerca -> mostro solo il form
        if (!$nameInput) {
            return view('city.dashboard', [
                'nameInput' => '',
                'fromInput' => null,
                'toInput'   => null,
                'city'      => null,
                'stats'     => [],
                'dailyRows' => collect(),
            ]);
        }

        // 2) Date: se mancano, uso il default = ultimi 7 giorni.
        // startOfDay = inizio giornata 00:00; endOfDay = fine giornata 23:59:59
        $from = $fromInput ? Carbon::parse($fromInput)->startOfDay()
                           : Carbon::now()->subDays(7)->startOfDay();
        $to   = $toInput   ? Carbon::parse($toInput)->endOfDay()
                           : Carbon::now()->endOfDay();

        // Se invertite, le scambio (difesa da input errato)
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        // 3) Chiamiamo OpenMeteoService per cercare la città (geocoding)
        //    e poi scarichiamo/salviamo le temperature orarie.
        try {
            $match = $meteo->searchCity($nameInput);
        } catch (\Throwable $e) {
            // Throwable lo usiamo per catturare qualsiasi tipo di errore (HTTP, di rete/timeout/DNS o bug).
            // Se l'API fallisce, restituiamo un errore all'utente e torniamo alla pagina precedente.
            return back()->with('error', 'Servizio non disponibile, riprova più tardi.')->withInput();
        }

        // 4) Se non troviamo nessuna città, messaggio all'utente
        if (!$match || $match['latitude'] === null || $match['longitude'] === null) {
            return back()->with('error', 'Città non trovata.')->withInput();
        }

        // 5) Salvataggio nel DB (se esiste già la aggiorna altrimenti la crea) si evitano duplicati nel DB.
        $city = City::updateOrCreate(
            ['name' => $match['name'], 'country' => $match['country']],
            ['latitude' => $match['latitude'], 'longitude' => $match['longitude']]
        );

        // 6) Scarico i dati orari e li salvo (upsert)
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
            return back()->with('error', 'Servizio meteo non disponibile, riprova più tardi.')->withInput();
        }

        // 7) Creo una query base per rileggere i dati dal DB nell’intervallo scelto.
        $baseQuery = WeatherRecord::where('city_id', $city->id)
            ->whereBetween('recorded_at', [$from, $to]);

        // 8) Calcolo statistiche semplici:
        // - temperatura media
        // - minima
        // - massima
        $stats = [
            'avg' => round((float) (clone $baseQuery)->avg('temperature'), 1),
            'min' => (clone $baseQuery)->min('temperature'),
            'max' => (clone $baseQuery)->max('temperature'),
        ];

        // 9) Medie GIORNALIERE (richiesta dall’assignment)
        $dailyRows = (clone $baseQuery)
            ->selectRaw('DATE(recorded_at) as day, AVG(temperature) as avg_temp')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // 10) Ritorno la view unica (form + risultati)
        return view('city.dashboard', [
            'nameInput' => $nameInput,
            'fromInput' => $from->toDateString(),
            'toInput'   => $to->toDateString(),
            'city'      => $city,
            'stats'     => $stats,
            'dailyRows' => $dailyRows,
        ]);
    }
}
