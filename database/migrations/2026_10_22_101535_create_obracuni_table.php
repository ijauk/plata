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
        Schema::create('obracuni', function (Blueprint $table) {
            $table->id();
            $table->date('period')->unique();
            $table->decimal('sati_redovan', 6, 2)->default(0);
            $table->decimal('sati_blagdan', 6, 2)->default(0);
            $table->decimal('sati_dopust', 6, 2)->default(0);
            $table->decimal('sati_godisnji', 6, 2)->default(0);
            $table->decimal('sati_bolovanje', 6, 2)->default(0);
            $table->decimal('ucinak', 5, 2)->default(20);
            $table->decimal('ostalo_bruto', 10, 2)->default(0);
            $table->decimal('ostalo_sati', 6, 2)->default(0);
            $table->decimal('prethodni_bruto', 10, 2)->nullable();
            $table->decimal('prethodni_sati', 6, 2)->nullable();
            $table->decimal('stvarni_bruto', 10, 2)->nullable();
            $table->decimal('stvarna_isplata', 10, 2)->nullable();
            $table->text('napomena')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obracuni');
    }
};
