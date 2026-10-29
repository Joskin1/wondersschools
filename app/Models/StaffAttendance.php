<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    use HasFactory;

    protected $table = 'staff_attendances';

    protected $fillable = [
        'session_id',
        'term_id',
        'user_id',
        'date',
        'status',
        'arrival_time',
        'departure_time',
        'remark',
        'recorded_by_device_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(DesktopDevice::class, 'recorded_by_device_id');
    }
}
