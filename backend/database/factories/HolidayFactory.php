<?php

namespace Database\Factories;

use App\Models\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    public function definition(): array
    {
        $date = fake()->unique()->dateTimeBetween('2025-01-01', '2026-12-31');

        return [
            'date' => $date->format('Y-m-d'),
            'name' => fake()->randomElement(['元旦', '春节', '清明', '端午', '中秋', '国庆']),
            'type' => 'holiday',
            'year' => (int) $date->format('Y'),
        ];
    }

    public function workday(): static
    {
        return $this->state(fn () => [
            'name' => '调休补班',
            'type' => 'workday',
        ]);
    }
}
