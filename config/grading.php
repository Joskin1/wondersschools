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
        ['min' => 70, 'max' => 100, 'grade' => 'A', 'remark' => 'Excellent', 'principal_comment' => 'An excellent and outstanding performance. Keep it up!'],
        ['min' => 60, 'max' => 69,  'grade' => 'B', 'remark' => 'Very Good', 'principal_comment' => 'A very good performance. Maintain this positive standard and aim higher.'],
        ['min' => 50, 'max' => 59,  'grade' => 'C', 'remark' => 'Good',      'principal_comment' => 'A good effort, but there is still room for further improvement.'],
        ['min' => 45, 'max' => 49,  'grade' => 'D', 'remark' => 'Fair',      'principal_comment' => 'A fair performance. More commitment and focus are required next term.'],
        ['min' => 40, 'max' => 44,  'grade' => 'E', 'remark' => 'Pass',      'principal_comment' => 'A weak pass. Must work much harder to achieve better results.'],
        ['min' => 0,  'max' => 39,  'grade' => 'F', 'remark' => 'Fail',      'principal_comment' => 'A poor result. Needs urgent academic support, seriousness, and dedication.'],
    ],

];
