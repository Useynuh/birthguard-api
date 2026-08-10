<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Child extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'full_name',
        'gender',
        'date_of_birth',
        'place_of_birth',
        'weight_at_birth',
        'height_at_birth',
        'registered_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'weight_at_birth' => 'decimal:2',
        'height_at_birth' => 'decimal:2',
    ];

    /**
     * The parent who owns this child.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Vaccinations belonging to this child.
     */
    public function vaccinations(): HasMany
    {
        return $this->hasMany(
            Vaccination::class,
            'child_id',
            'id'
        );
    }
}