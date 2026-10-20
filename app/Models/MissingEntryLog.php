<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissingEntryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'depot_id',
        'date',
        'sent_at',
        'recipients',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }
}
