<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Players;
use App\Models\Schedule;



class PlayByPlay extends Model
{
    use HasFactory;
    protected $fillable = [
        'player_id',
        'schedule_id',
        'quarter',
        'game_time',
        'type_of_stat',
        'result',
        'team_A_score',
        'team_B_score',
    ];

    public function player()
    {
        return $this->belongsTo(Players::class, 'player_id');
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }
}
