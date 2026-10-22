<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Svaki korisnik ima svoje obračune i konstante. Postojeći zapisi ostaju bez vlasnika
 * dok ih `php artisan plata:admin` ne dodijeli administratoru.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
        });

        Schema::table('postavke', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->dropUnique(['vrijedi_od']);
            $table->unique(['user_id', 'vrijedi_od']);
        });

        Schema::table('obracuni', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->dropUnique(['period']);
            $table->unique(['user_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('obracuni', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'period']);
            $table->dropColumn('user_id');
            $table->unique('period');
        });

        Schema::table('postavke', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'vrijedi_od']);
            $table->dropColumn('user_id');
            $table->unique('vrijedi_od');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
