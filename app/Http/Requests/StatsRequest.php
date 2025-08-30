<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatsRequest extends FormRequest
{
    /**
     * Tutti gli utenti sono autorizzati a fare questa richiesta.
     * Qui potresti inserire controlli di autorizzazione (es. solo utenti loggati),
     * ma per il nostro progetto lasciamo sempre true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regole di validazione per i campi 'from' e 'to'.
     * - Entrambi possono essere nulli (nullable), in quel caso useremo i valori di default.
     * - Devono essere date valide.
     * - 'from' deve essere una data prima o uguale a 'to'.
     * - 'to' deve essere una data dopo o uguale a 'from'.
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date', 'before_or_equal:to'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }

    /**
     * Messaggi di errore personalizzati.
     * Sono più chiari per l'utente finale rispetto ai messaggi generici di Laravel.
     */
    public function messages(): array
    {
        return [
            'from.date' => 'La data di inizio non è valida.',
            'to.date'   => 'La data di fine non è valida.',
            'from.before_or_equal' => 'La data di inizio deve essere prima o uguale alla data di fine.',
            'to.after_or_equal'    => 'La data di fine deve essere dopo o uguale alla data di inizio.',
        ];
    }

    /**
     * Pre-elaborazione dei dati prima della validazione.
     * Qui possiamo "normalizzare" gli input:
     * - Se l'utente non scrive nulla, li forziamo a null.
     * - Potresti anche fare trim/spazi, ma non serve per le date.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'from' => $this->input('from') ?: null,
            'to'   => $this->input('to')   ?: null,
        ]);
    }
}