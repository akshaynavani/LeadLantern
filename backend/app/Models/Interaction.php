<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Interaction extends Model
{
    use HasFactory;

    public const CHANNELS = ['email', 'call', 'chat', 'form', 'meeting', 'note'];

    protected $fillable = ['contact_id', 'user_id', 'channel', 'direction', 'subject', 'body', 'occurred_at'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): HasOne
    {
        return $this->hasOne(Task::class);
    }
}
