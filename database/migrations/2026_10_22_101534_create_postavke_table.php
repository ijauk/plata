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
        Schema::create('postavke', function (Blueprint $table) {
            $table->id();
            $table->date('vrijedi_od')->unique();
            $table->decimal('osnovica', 10, 4);
            $table->decimal('koeficijent', 6, 3);
            $table->decimal('minuli_rad', 5, 2);
            $table->decimal('fond_sati', 6, 2);
            $table->decimal('stalni_dodatak', 10, 2);
            $table->decimal('topli_obrok', 10, 2);
            $table->decimal('stopa_mio1', 5, 2);
            $table->decimal('stopa_mio2', 5, 2);
            $table->decimal('osobni_odbitak', 10, 2);
            $table->decimal('faktor_odbitka', 5, 2);
            $table->decimal('stopa_poreza', 5, 2);
            $table->decimal('sindikat_spiv', 5, 3);
            $table->decimal('spiv_hto', 5, 3);
            $table->decimal('bolovanje_postotak', 5, 2);
            $table->string('napomena')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postavke');
    }
};
