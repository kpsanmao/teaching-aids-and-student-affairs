<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 为演示教师 (teacher@lncu.cn) 生成 4 门示例课程并从每个班级抽人选课。
 * 便于截图时展示课程列表 / 学生绑定关系。
 */
class DemoCourseSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('email', 'teacher@lncu.cn')->first();
        if (! $teacher || Course::count() > 0) {
            return;
        }

        $semester = [
            'semester' => '2026-春',
            'semester_start' => '2026-02-23',
            'semester_end' => '2026-06-28',
        ];

        $template = [
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

        $courses = [
            [
                'name' => '数据结构与算法',
                'credit' => 3.0,
                'course_type' => 'theory',
                'weekly_days' => [1, 3],
                'periods_per_day' => [['start' => '08:00', 'end' => '09:40']],
                'classes' => ['计算机科学 2301', '软件工程 2301'],
            ],
            [
                'name' => '计算机网络',
                'credit' => 2.5,
                'course_type' => 'mixed',
                'weekly_days' => [2, 4],
                'periods_per_day' => [['start' => '10:00', 'end' => '11:40']],
                'classes' => ['计算机科学 2301'],
            ],
            [
                'name' => '数据库系统',
                'credit' => 3.0,
                'course_type' => 'mixed',
                'weekly_days' => [2, 5],
                'periods_per_day' => [['start' => '14:00', 'end' => '15:40']],
                'classes' => ['软件工程 2301', '数据科学 2301'],
            ],
            [
                'name' => '人工智能导论',
                'credit' => 2.0,
                'course_type' => 'theory',
                'weekly_days' => [3],
                'periods_per_day' => [['start' => '16:00', 'end' => '17:40']],
                'classes' => ['数据科学 2301'],
            ],
        ];

        foreach ($courses as $row) {
            $course = Course::create(array_merge($template, $semester, [
                'user_id' => $teacher->id,
                'name' => $row['name'],
                'credit' => $row['credit'],
                'course_type' => $row['course_type'],
                'weekly_days' => $row['weekly_days'],
                'periods_per_day' => $row['periods_per_day'],
            ]));

            $studentIds = Student::whereIn('class_name', $row['classes'])->pluck('id');
            $sync = [];
            foreach ($studentIds as $sid) {
                $sync[$sid] = ['enrolled_at' => now()];
            }
            $course->students()->attach($sync);
        }
    }
}
