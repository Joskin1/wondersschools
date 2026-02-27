<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Grading Scale
    |--------------------------------------------------------------------------
    |
    | Config-driven grading boundaries. Sorted descending by 'min' so the
    | first match wins during grade resolution.  Adjust these values per
    | tenant or school policy without touching code.
    |
    */

    'scale' => [
        ['min' => 70, 'max' => 100, 'grade' => 'A', 'remark' => 'Excellent'],
        ['min' => 60, 'max' => 69,  'grade' => 'B', 'remark' => 'Very Good'],
        ['min' => 50, 'max' => 59,  'grade' => 'C', 'remark' => 'Good'],
        ['min' => 45, 'max' => 49,  'grade' => 'D', 'remark' => 'Fair'],
        ['min' => 40, 'max' => 44,  'grade' => 'E', 'remark' => 'Pass'],
        ['min' => 0,  'max' => 39,  'grade' => 'F', 'remark' => 'Fail'],
    ],

];
