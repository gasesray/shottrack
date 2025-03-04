<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Schedule;
use App\Models\Teams;

class Score extends Model
{
    use HasFactory;

    protected $fillable = ['schedule_id', 'team_id', 'quarter', 'score'];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function team()
    {
        return $this->belongsTo(Teams::class);
    }
}
