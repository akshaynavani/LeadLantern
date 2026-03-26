<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    public const TRIAGE_SLUG = 'triage';

    protected $fillable = ['name', 'slug', 'description'];

    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function routingRules(): HasMany
    {
        return $this->hasMany(RoutingRule::class);
    }

    public static function triage(): self
    {
        return static::firstOrCreate(
            ['slug' => self::TRIAGE_SLUG],
            ['name' => 'Triage Review', 'description' => 'Low-confidence or unmatched tasks awaiting manual routing.'],
        );
    }
}
