<?php

namespace App\Models;

use App\Enums\Priority;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutingRule extends Model
{
    protected $fillable = ['category', 'team_id', 'default_priority', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'default_priority' => Priority::class,
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
