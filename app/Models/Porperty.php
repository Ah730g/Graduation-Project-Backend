<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Porperty extends Model
{
    public function post() : HasOne{
        return $this->hasOne(post::class);
    }
}
