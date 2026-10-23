<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolHoliday extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'session_id',
        'term_id',
        'title',
        'start_date',
        'end_date',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'term_id');
    }

    /**
     * Scope to check if a specific date falls within any holiday.
     */
    public function scopeForDate(Builder $query, string|Carbon $date): Builder
    {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : $date;

        return $query->whereDate('start_date', '<=', $dateStr)
            ->whereDate('end_date', '>=', $dateStr);
    }

    /**
     * Determine if a given date is covered by this holiday.
     */
    public function coversDate(string|Carbon $date): bool
    {
        $d = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $d->betweenIncluded($this->start_date, $this->end_date);
    }
}
