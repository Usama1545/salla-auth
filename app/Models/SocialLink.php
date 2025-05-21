<?php

namespace App\Models;

use App\Helpers\TokenEncryption;
use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    protected $fillable = [
        "user_id",
        "store_id",
        "name",
        "value",
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'value' => 'encrypted'
    ];

    /**
     * Get the user that owns the social link.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the value attribute.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getValueAttribute($value)
    {
        // If this is the Facebook access token, decrypt it
        if ($this->name === 'access_token_conversion_facebook' && $value !== null) {
            return TokenEncryption::encrypt_decrypt($value, true); // true for decrypt
        }
        
        return $value;
    }

    /**
     * Set the value attribute.
     *
     * @param  string|null  $value
     * @return void
     */
    public function setValueAttribute($value)
    {
        // If this is the Facebook access token, encrypt it before storage
        if ($this->name === 'access_token_conversion_facebook' && $value !== null) {
            $this->attributes['value'] = TokenEncryption::encrypt_decrypt($value, false); // false for encrypt
        } else {
            $this->attributes['value'] = $value;
        }
    }

    /**
     * Scope a query to exclude Facebook access token.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExcludeFacebookToken($query)
    {
        return $query->where('name', '!=', 'access_token_conversion_facebook');
    }
}
