<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseSession>
 */
class CourseSessionFactory extends Factory
{
    protected $model = CourseSession::class;

    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-30 days', '+30 days');

        return [
            'course_id' => Course::factory(),
            'seq' => fake()->unique()->numberBetween(1, 9999),
            'session_date' => $date,
            'weekday' => ((int) $date->format('N')),
            'period' => 1,
            'status' => 'scheduled',
            'assignment_reminder' => false,
        ];
    }

    public function completed(): self
    {
        return $this->state(fn () => ['status' => 'completed']);
    }

    public function cancelled(): self
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }
}
