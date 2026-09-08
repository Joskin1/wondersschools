<?php

namespace Database\Factories;

use App\Models\ReferenceMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReferenceMaterialFactory extends Factory
{
    protected $model = ReferenceMaterial::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true) . ' Textbook',
        ];
    }
}
