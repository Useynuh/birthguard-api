<?php

namespace App\Models;
use App\Models\Child;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vaccination extends Model
{
    use HasFactory;

    protected $table = 'vaccinations';

    protected $primaryKey = 'vaccination_id';

    protected $fillable = [
        'child_id',
        'vaccine_name',
        'date',
        'time',
        'is_administered',
    ];

    protected $casts = [
        'date' => 'date',
        'is_administered' => 'boolean',
    ];

    /**
     * A vaccination belongs to a child.
     */
    public function child(): BelongsTo
    {
        return $this->belongsTo(
            Child::class,
            'child_id',
            'id'
        );
    }
}