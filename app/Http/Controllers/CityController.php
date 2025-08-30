<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Services\OpenMeteoService;
use App\Http\Requests\CitySearchRequest;

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

    public function stats(\App\Models\City $city)
    {
        // TODO: qui mostreremo le statistiche meteo per $city
        return view('city.stats', ['city' => $city]);
    }
}
