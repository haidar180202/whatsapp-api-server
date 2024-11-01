<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chatroom extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'max_members'];

    /**
     * Relasi dengan pengguna (many-to-many).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chatroom_user')
                    ->withTimestamps();
    }

    /**
     * Relasi dengan pesan (one-to-many).
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
