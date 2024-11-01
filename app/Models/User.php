<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory;

    /**
     * Relasi dengan chatroom (many-to-many).
     */
    public function chatrooms(): BelongsToMany
    {
        return $this->belongsToMany(Chatroom::class, 'chatroom_user')
                    ->withTimestamps(); // Menyimpan waktu saat bergabung
    }

    /**
     * Relasi dengan pesan (one-to-many).
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
