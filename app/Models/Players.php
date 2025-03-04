<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Teams;
use App\Models\tournaments;



class Players extends Model
{
    use HasFactory;

    protected $fillable = ['category', 'team_id', 'first_name', 'last_name', 'number', 'years_playing_in_bucal', 'position', 'date_of_birth', 'age', 'height', 'weight'];

    public function team()
    {
        return $this->belongsTo(Teams::class);
    }

    /**
     * Get the tournament through the team.
     */
    public function tournament()
    {
        return $this->hasOneThrough(Tournaments::class, Teams::class, 'id', 'id', 'team_id', 'tournament_id');
    }

    protected static function booted()
    {
        static::saving(function ($player) {
            if ($player->date_of_birth) {
                $dateOfBirth = Carbon::createFromFormat('Y-m-d', $player->date_of_birth);
                $player->age = $dateOfBirth->age;
            }
        });
    }
}
