<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['chatroom_id', 'user_id', 'content', 'attachment'];

    /**
     * Relasi dengan pengguna (many-to-one).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi dengan chatroom (many-to-one).
     */
    public function chatroom(): BelongsTo
    {
        return $this->belongsTo(Chatroom::class);
    }
}
