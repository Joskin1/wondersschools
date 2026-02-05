<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermMigrationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_term_id',
        'to_term_id',
        'from_session_id',
        'to_session_id',
        'user_id',
        'students_promoted',
        'students_graduated',
    ];

    protected $casts = [
        'students_promoted' => 'integer',
        'students_graduated' => 'integer',
    ];

    public function fromTerm()
    {
        return $this->belongsTo(Term::class, 'from_term_id');
    }

    public function toTerm()
    {
        return $this->belongsTo(Term::class, 'to_term_id');
    }

    public function fromSession()
    {
        return $this->belongsTo(AcademicSession::class, 'from_session_id');
    }

    public function toSession()
    {
        return $this->belongsTo(AcademicSession::class, 'to_session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
