<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Teams;
use App\Models\tournaments;
use Spatie\Permission\Commands\UpgradeForTeams;


class Schedule extends Model
{
    use HasFactory;

    // The attributes that are mass assignable
    protected $fillable = [
        'date',
        'time',
        'venue',
        'team1_id',
        'team1_color',
        'team2_color',
        'team2_id',
        'category',
        'tournament_id',
        'remaining_game_time',
        'time_elapsed',
        'total_elapsed_time'
    ];

    // Define the relationship with the Tournament model
    public function tournament()
    {
        return $this->belongsTo(tournaments::class);
    }

    // Define the relationship with the first Team (team 1)
    public function team1()
    {
        return $this->belongsTo(Teams::class, 'team1_id');
    }

    // Define the relationship with the second Team (team2)
    public function team2()
    {
        return $this->belongsTo(Teams::class, 'team2_id');
    }
    
    public function scores()
    {
        return $this->hasMany(Score::class);
    }
    
    public function playerStats()
    {
        return $this->hasMany(PlayerStat::class);
    }

    public function teamMetrics()
    {
        return $this->hasMany(TeamMetric::class);
    }
}
