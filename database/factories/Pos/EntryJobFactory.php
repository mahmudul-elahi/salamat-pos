<?php

namespace Database\Factories\Pos;

use App\Models\Pos\EntryJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EntryJob>
 */
class EntryJobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'heading' => $this->faker->jobTitle,
            'slug' => $this->faker->slug,
            'image' => null,
            'vacancy' => $this->faker->numberBetween(1, 5),
            'job_type' => $this->faker->randomElement(['Full Time', 'Part Time', 'Contract']),
            'salary' => $this->faker->numberBetween(20000, 50000),
            'deadline' => $this->faker->dateTimeBetween('now', '+2 months'),
            'qualification' => $this->faker->paragraphs(3, true),
            'benefits' => $this->faker->paragraphs(2, true),
            'meta_title' => $this->faker->sentence,
            'meta_description' => $this->faker->text,
            'keywords' => $this->faker->words(5, true),
            'status' => 'active',
        ];
    }
}
