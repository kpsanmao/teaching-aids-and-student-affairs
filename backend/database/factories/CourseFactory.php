<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->teacher(),
            'name' => fake()->randomElement([
                '数据结构与算法',
                '计算机网络',
                '操作系统原理',
                '软件工程',
                '数据库系统',
            ]),
            'credit' => fake()->randomFloat(1, 1, 4),
            'course_type' => fake()->randomElement(['theory', 'practice', 'mixed']),
            'semester' => '2026-春',
            'semester_start' => '2026-02-24',
            'semester_end' => '2026-06-28',
            'weekly_days' => [1, 3],
            'periods_per_day' => [
                ['start' => '08:00', 'end' => '09:40'],
            ],
            'assignment_count' => 4,
            'max_absence' => 6,
            'remind_before' => 1,
            'grade_formula' => [
                'attendance' => 0.1,
                'assignment' => 0.3,
                'midterm' => 0.2,
                'final' => 0.4,
            ],
            'alert_thresholds' => [
                'absence_count' => 3,
                'assignment_score' => 60,
                'grade_decline' => 10,
            ],
        ];
    }
}
