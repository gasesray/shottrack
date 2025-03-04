<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\tournaments;

class Teams extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'name',
        'team_acronym',
        'head_coach_name',
        'school_president',
        'sports_director',
        'years_playing_in_bucal',
        'address',
        'logo',
        'tournament_id',
    ];

    /**
     * Define the relationship with the Tournament model.
     * Each team belongs to one tournament.
     */
    public function tournament()
    {
        return $this->belongsTo( tournaments::class);
    }
    public function players()
    {
        return $this->hasMany(Players::class, 'team_id'); 
    }

    public function scores()
    {
        return $this->hasMany(Score::class);
    }

    public function teamStats()
    {
        return $this->hasMany(TeamStat::class, 'team_id'); 
    }

    public function metrics()
    {
        return $this->hasMany(TeamMetric::class);
    }
}
