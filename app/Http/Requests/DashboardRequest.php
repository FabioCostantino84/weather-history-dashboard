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
     */
    public function rules(): array
    {
        return [
            'name' => [
                'bail', // Al primo requisito fallito si ferma (messaggio di errore più chiaro).
                'required',
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
            'name.required' => 'Inserisci il nome della città.',
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
     */
    protected function prepareForValidation(): void
    {
        $name = (string) $this->input('name', '');

        $name = trim($name);
        $name = preg_replace('/\s+/u', ' ', $name);   // spazi multipli -> singolo
        $name = str_replace(['’'], ["'"], $name);     // apostrofo tipografico -> semplice

        $this->merge(['name' => $name]);
    }

    /**
     * Dopo le regole base, aggiungiamo controlli extra.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $startDate = $this->input('from');
            $endDate   = $this->input('to');
    
            // Se entrambi sono presenti e la data di inizio è maggiore della fine
            if ($startDate && $endDate && $startDate > $endDate) {
                $validator->errors()->add(
                    'from',
                    'La data di inizio non può essere successiva alla data di fine.'
                );
            }
        });
    }

}
