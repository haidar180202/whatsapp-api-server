<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    /**
     * Fields that can be mass-assigned.
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
    ];

    /**
     * Fields that should be hidden in arrays.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Define the many-to-many relationship with chatrooms.
     */
    public function chatrooms(): BelongsToMany
    {
        return $this->belongsToMany(Chatroom::class, 'chatroom_user')
                    ->withTimestamps(); // Stores join timestamps
    }

    /**
     * Define the one-to-many relationship with messages.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
