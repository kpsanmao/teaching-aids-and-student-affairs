<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'student_no' => fake()->unique()->numerify('2024##########'),
            'name' => fake()->name(),
            'class_name' => fake()->randomElement([
                '计算机科学 2301',
                '计算机科学 2302',
                '软件工程 2301',
                '数据科学 2301',
            ]),
        ];
    }
}
