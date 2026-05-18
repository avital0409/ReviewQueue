<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'author_email',
        'content',
        'status',
        'risk_score',
        'heuristic_flags',
        'auto_suggestion',
        'reviewer_note',
        'reviewed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'heuristic_flags' => 'array',
            'reviewed_at' => 'datetime',
            'risk_score' => 'integer',
        ];
    }
}
