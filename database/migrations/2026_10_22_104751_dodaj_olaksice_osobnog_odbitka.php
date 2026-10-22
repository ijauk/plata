<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faktor osobnog odbitka više se ne upisuje ručno, nego se računa iz broja djece,
 * uzdržavanih članova i osoba s invaliditetom (svi postojeći zapisi imali su faktor 1).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('postavke', function (Blueprint $table) {
            $table->unsignedTinyInteger('broj_djece')->default(0)->after('osobni_odbitak');
            $table->decimal('udio_djeca', 5, 2)->default(100)->after('broj_djece');
            $table->unsignedTinyInteger('broj_uzdrzavanih')->default(0)->after('udio_djeca');
            $table->unsignedTinyInteger('broj_invalidnosti')->default(0)->after('broj_uzdrzavanih');
            $table->dropColumn('faktor_odbitka');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('postavke', function (Blueprint $table) {
            $table->decimal('faktor_odbitka', 5, 2)->default(1)->after('osobni_odbitak');
            $table->dropColumn(['broj_djece', 'udio_djeca', 'broj_uzdrzavanih', 'broj_invalidnosti']);
        });
    }
};
