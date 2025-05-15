<?php

namespace App\Models;

use App\Helpers\TokenEncryption;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OauthToken extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
    protected $fillable = [
        'access_token',
        'refresh_token',
        'expires_in',
        'expires_at',
        'merchant',
        'user_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the decrypted access token
     *
     * @return string
     */
    public function getDecryptedAccessTokenAttribute()
    {
        return TokenEncryption::encrypt_decrypt($this->access_token, true);
    }

    /**
     * Get the decrypted refresh token
     *
     * @return string
     */
    public function getDecryptedRefreshTokenAttribute()
    {
        return TokenEncryption::encrypt_decrypt($this->refresh_token, true);
    }

    public function hasExpired()
    {
        // If we have expires_at, use that
        if ($this->expires_at) {
            return now()->isAfter($this->expires_at);
        }

        // Fall back to expires_in for backward compatibility
        if ($this->expires_in) {
            return now()->timestamp > $this->created_at->timestamp + $this->expires_in;
        }

        // If we have neither, assume expired
        return true;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
