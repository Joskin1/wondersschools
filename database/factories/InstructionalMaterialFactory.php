<?php

namespace Database\Factories;

use App\Models\InstructionalMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstructionalMaterialFactory extends Factory
{
    protected $model = InstructionalMaterial::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
        ];
    }
}
