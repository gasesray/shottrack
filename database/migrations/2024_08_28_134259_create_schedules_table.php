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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('time');
            $table->string('venue');
            $table->timestamps();
            $table->foreignId('tournament_id')->constrained('tournaments');
            $table->foreignId('team1_id')->constrained('teams');
            $table->foreignId('team2_id')->constrained('teams');
            $table->string('category')->nullable(); 

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
