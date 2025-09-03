<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LevelPlan extends Model
{
    protected $fillable = [
        'name',
        'price',
        'level_number',
        'description',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'level_number' => 'integer',
        'sort_order' => 'integer'
    ];

    /**
     * Scope to get only active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by level number
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('level_number', 'asc');
    }

    /**
     * Get plan by level number
     */
    public static function getByLevel($levelNumber)
    {
        return self::where('level_number', $levelNumber)->active()->first();
    }

    /**
     * Get all active plans ordered by level
     */
    public static function getAllActive()
    {
        return self::active()->ordered()->get();
    }
}
