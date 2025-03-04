<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Models\PlayerStat;
use App\Models\Teams;
use App\Models\Schedule;

class TeamStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'minutes',
        'points',
        'rebounds',
        'assists',
        'steals',
        'blocks',
        'personal_fouls',
        'turnovers',
        'offensive_rebounds',
        'defensive_rebounds',
        'offensive_rebound_percentage',
        'defensive_rebound_percentage',
        'two_pt_fg_attempt',
        'three_pt_fg_attempt',
        'two_pt_fg_made',
        'three_pt_fg_made',
        'two_pt_percentage',
        'three_pt_percentage',
        'free_throw_attempt',
        'free_throw_made',
        'free_throw_percentage',
        'free_throw_attempt_rate',
        'effective_field_goal_percentage',
        'turnover_ratio',
        'schedule_id',
        'team_id'
    ];

    public function team()
    {
        return $this->belongsTo(Teams::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}