<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Teams;

class tournaments extends Model
{
    use HasFactory;
    public function teams()
    {
        return $this->hasMany(Teams::class);
    }
}
