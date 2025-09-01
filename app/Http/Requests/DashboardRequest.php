<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardRequest extends FormRequest
{
    /**
     * L'utente è sempre autorizzato a fare la richiesta.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regole di validazione per il campo 'name'.
     *
     * Caratteri ammessi:
     * - lettere Unicode (incluse accentate)
     * - spazi
     * - apostrofi (' e ’)
     * - trattini (-)
     * - punto (.)
     *
     * Inoltre:
     * - niente punteggiatura doppia/consecutiva
     * - non può iniziare/finire con punteggiatura
     *
     * NOTA IMPORTANTE (evita loop):
     * - al primo caricamento della dashboard (GET / senza parametri) 'name' deve poter essere assente -> nullable
     * - quando l'utente invia davvero il form, lo renderemo "obbligatorio" in withValidator()
     */
    public function rules(): array
    {
        return [
            'name' => [
                'bail', // Al primo requisito fallito si ferma (messaggio di errore più chiaro).
                'nullable',   // <-- cambiato da 'required' a 'nullable' per evitare redirect loop al primo load
                'string',
                'min:2',
                'max:100',
                // Evita inizio/fine con punteggiatura e ripetizioni di punteggiatura
                'regex:/^(?![\'’.\-])(?!.*[\'’.\-]{2})[\p{L}\p{M}\s\'’\.-]+(?<![\'’.\-])$/u',
            ],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to'   => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * Messaggi di errore personalizzati.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Inserisci il nome della città.', // resta valido: lo useremo in withValidator()
            'name.min'      => 'Il nome della città deve avere almeno :min caratteri.',
            'name.max'      => 'Il nome della città non può superare :max caratteri.',
            'name.regex'    => "Usa solo lettere, spazi, apostrofi (') , trattini (-) e punto (.). "
                               . "Niente punteggiatura doppia o all'inizio/fine.",

            // Testare se davvero l'utente ha la possibilità di inserire una data in formato sbagliato.
            'from.date_format' => 'La data iniziale deve essere nel formato YYYY-MM-DD.',
            'to.date_format'   => 'La data finale deve essere nel formato YYYY-MM-DD.',                   
        ];
    }

    /**
     * Nomi "umani" degli attributi (opzionale ma utile nei messaggi).
     */
    public function attributes(): array
    {
        return [
            'name' => 'città',
        ];
    }

    /**
     * Normalizza l'input prima di validare:
     * - trim
     * - spazi multipli -> singolo spazio
     * - apostrofo tipografico ’ -> '
     *
     * In più:
     * - se la stringa è vuota, la trasformo in null (compatibile con 'nullable').
     */
    protected function prepareForValidation(): void
    {
        $name = (string) $this->input('name', '');

        $name = trim($name);
        $name = preg_replace('/\s+/u', ' ', $name);   // spazi multipli -> singolo
        $name = str_replace(['’'], ["'"], $name);     // apostrofo tipografico -> semplice

        if ($name === '') {
            $name = null; // importante per far passare 'nullable' al primo load
        }

        $this->merge(['name' => $name]);
    }

    /**
     * Dopo le regole base, aggiungiamo controlli extra.
     *
     * - Evitiamo il loop di redirect:
     *   se la pagina è stata aperta senza parametri (prima visita), non vogliamo l'errore 'required'.
     *   Se invece l'utente ha davvero inviato il form (la query ha 'name'), allora 'name' NON può essere null.
     *
     * - Controllo coerenza date: se entrambe presenti, 'from' <= 'to'.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            // Il form è stato davvero inviato solo se c'è "submitted" nella query
            $formSubmitted = $this->query->has('submitted');
    
            // Il nome città già normalizzato in prepareForValidation()
            $cityName = $this->input('name');
    
            // Richiediamo il nome SOLO se il form è stato inviato
            if ($formSubmitted && is_null($cityName)) {
                // uso il tuo messaggio "name.required"
                $v->errors()->add('name', $this->messages()['name.required']);
            }
    
            // Controllo coerenza date: se entrambe presenti, from <= to
            $startDate = $this->input('from');
            $endDate   = $this->input('to');
    
            if ($startDate && $endDate && $startDate > $endDate) {
                $v->errors()->add(
                    'from',
                    'La data di inizio non può essere successiva alla data di fine.'
                );
            }
        });
    }
}
