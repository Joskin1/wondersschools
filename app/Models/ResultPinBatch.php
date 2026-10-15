<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResultPinBatch extends Model
{
    protected $table = 'result_pin_batches';

    protected $fillable = [
        'batch_code',
        'session_id',
        'term_id',
        'quantity',
    ];

    protected $casts = [
        'session_id' => 'integer',
        'term_id' => 'integer',
        'quantity' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'term_id');
    }

    public function pins(): HasMany
    {
        return $this->hasMany(ResultPin::class, 'batch_id');
    }
}
