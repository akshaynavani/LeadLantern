<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id', 'interaction_id', 'title', 'description', 'channel', 'status', 'category',
        'priority', 'team_id', 'assignee_id', 'created_by', 'due_at', 'routed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => Priority::class,
            'due_at' => 'datetime',
            'routed_at' => 'datetime',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function interaction(): BelongsTo
    {
        return $this->belongsTo(Interaction::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function routingDecisions(): HasMany
    {
        return $this->hasMany(RoutingDecision::class)->latest('id');
    }

    public function latestDecision(): HasOne
    {
        return $this->hasOne(RoutingDecision::class)->latestOfMany();
    }

    /**
     * Agents only see tasks for their own team or assigned to them.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasPermission('tasks.view_all')) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('assignee_id', $user->id);
            if ($user->team_id) {
                $q->orWhere('team_id', $user->team_id);
            }
        });
    }
}
