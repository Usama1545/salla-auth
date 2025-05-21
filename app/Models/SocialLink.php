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
    protected $hidden = [
        'value' => function () {
            return $this->name === 'access_token_conversion_facebook';
        }
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
     * @param  string  $value
     * @return string
     */
    public function getValueAttribute($value)
    {
        // If this is the Facebook access token, it's stored encrypted
        if ($this->name === 'access_token_conversion_facebook') {
            return TokenEncryption::encrypt_decrypt($value, true);
        }

        return $value;
    }

    /**
     * Set the value attribute.
     *
     * @param  string  $value
     * @return void
     */
    public function setValueAttribute($value)
    {
        // If this is the Facebook access token, encrypt it before storage
        if ($this->name === 'access_token_conversion_facebook' && !empty($value)) {
            $this->attributes['value'] = TokenEncryption::encrypt_decrypt($value, false);
        } else {
            $this->attributes['value'] = $value;
        }
    }
}
