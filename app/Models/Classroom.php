<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'staff_id'];

    public function teacher()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }
}
