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
        Schema::create('play_by_plays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('schedule_id');
            $table->string('type_of_stat');
            $table->string('result'); 
            $table->string('quarter'); 
            $table->string('game_time');  
            $table->unsignedInteger('team_A_score')->default(0);
            $table->unsignedInteger('team_B_score')->default(0);

            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
            $table->foreign('schedule_id')->references('id')->on('schedules')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('play_by_plays');
    }
};
