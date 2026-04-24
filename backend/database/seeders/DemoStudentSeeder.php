<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;

class DemoStudentSeeder extends Seeder
{
    /**
     * 生成 3 个示例班级，各 30 人，共 90 名学生（学号 20260001 起）。
     * 便于截图时展示列表分页和班级筛选效果。
     */
    public function run(): void
    {
        if (Student::count() > 0) {
            return;
        }

        $classes = [
            '计算机科学 2301' => 30,
            '软件工程 2301' => 30,
            '数据科学 2301' => 30,
        ];

        $seq = 1;
        foreach ($classes as $className => $count) {
            $rows = [];
            for ($i = 1; $i <= $count; $i++) {
                $rows[] = [
                    'student_no' => '2026'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                    'name' => $this->randomChineseName(),
                    'class_name' => $className,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $seq++;
            }
            Student::insert($rows);
        }
    }

    private function randomChineseName(): string
    {
        $surnames = ['王', '李', '张', '刘', '陈', '杨', '赵', '黄', '周', '吴', '徐', '孙', '胡', '朱', '高', '林', '何', '郭', '马', '罗'];
        $given = [
            '浩然', '子轩', '宇航', '思远', '睿哲', '俊熙', '博文', '子涵', '俊杰', '若曦',
            '梓涵', '语嫣', '诗涵', '欣怡', '依诺', '奕辰', '沐辰', '雨桐', '梓萱', '可欣',
            '家豪', '嘉怡', '雅静', '晓琳', '文博', '佳怡', '思媛', '宇飞', '一鸣', '梓菲',
        ];

        return $surnames[array_rand($surnames)].$given[array_rand($given)];
    }
}
