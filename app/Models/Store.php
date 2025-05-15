<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'salla_id',
        'owner_id',
        'user_id',
        'owner_name',
        'username',
        'name',
        'avatar',
        'store_location',
        'plan',
        'status',
        'salla_created_at',
    ];

    protected $casts = [
        'salla_created_at' => 'datetime',
    ];

    /**
     * Get the user that owns the store.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
