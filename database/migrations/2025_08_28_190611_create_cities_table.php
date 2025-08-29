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
        Schema::create('cities', function (Blueprint $table){
            $table->id();                          // creo colonna "id" con "Primary key" auto increment
            $table->string('name');                
            $table->string('country')->nullable(); // codice paese opzionale
            $table->decimal('latitude', 8, 5);     // 8 cifre totali e 5 decimali
            $table->decimal('longitude', 8, 5);
            $table->timestamps();                  // created_at e updated_at

            $table->index(['name', 'country']);    // creo un indice per velocizzare le ricerche
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
