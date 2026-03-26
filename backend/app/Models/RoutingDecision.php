<?php

namespace App\Models;

use App\Enums\DecisionSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutingDecision extends Model
{
    protected $fillable = [
        'task_id', 'source', 'category', 'priority', 'sentiment', 'confidence', 'rationale',
        'suggested_actions', 'team_id', 'model', 'latency_ms', 'decided_by',
    ];

    protected function casts(): array
    {
        return [
            'source' => DecisionSource::class,
            'confidence' => 'float',
            'suggested_actions' => 'array',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
