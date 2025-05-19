<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    protected $fillable = [
        "user_id",
        "store_id",
        "name",
        "value",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
