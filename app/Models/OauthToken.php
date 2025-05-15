<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OauthToken extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
    protected $fillable = [
        'merchant',
        'access_token',
        'expires_in',
        'refresh_token',
        'user_id',
    ];

    public function hasExpired()
    {
        return now()->timestamp > $this->expires_in;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
