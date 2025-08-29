<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('weather_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')                  // La colonna "city_id" è vincolata al "id" della tabella "cities"
                  ->constrained('cities')     
                  ->onDelete('cascade');                  // se cancello una città, cancello tutti i suoi dati
            $table->dateTime('recorded_at');              // Data e ora della misurazione (esempio: "2025-08-28 12:00.00")
            $table->decimal('temperature', 5, 2);
            $table->timestamps();                         // created_at e updated_at

            $table->unique((['city_id', 'recorded_at'])); // impedisce duplicati per città e ora
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_records');
    }
};
