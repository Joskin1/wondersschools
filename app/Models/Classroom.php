<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    protected $fillable = ['name', 'staff_id'];

    public function teacher()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
