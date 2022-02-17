<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'questionText' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['mcq'])
        ];
    }
}
