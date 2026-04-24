<?php

namespace App\Imports;

use App\Models\Course;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

/**
 * 学生名单批量导入：接受 student_no / name / class_name 三列，
 * upsert 到 students 表并自动关联到目标课程。
 *
 * 表头支持两种写法：
 *  - 英文 header：student_no / name / class_name
 *  - 中文 header：学号 / 姓名 / 班级（通过 preprocess 映射为英文键）
 */
class StudentsImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, SkipsEmptyRows
{
    use Importable;

    /** @var list<array{row:int,errors:array<string,array<int,string>>|string}> */
    public array $failureLog = [];

    public int $attached = 0;

    public int $created = 0;

    public function __construct(private readonly Course $course) {}

    public function collection(\Illuminate\Support\Collection $rows): void
    {
        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                $normalized = $this->normalize($row->all());
                if ($normalized === null) {
                    continue;
                }

                $student = Student::updateOrCreate(
                    ['student_no' => $normalized['student_no']],
                    [
                        'name' => $normalized['name'],
                        'class_name' => $normalized['class_name'],
                    ],
                );

                if ($student->wasRecentlyCreated) {
                    $this->created++;
                }

                $this->course->students()->syncWithoutDetaching([
                    $student->id => ['enrolled_at' => Carbon::now()],
                ]);
                $this->attached++;
            }
        });
    }

    public function rules(): array
    {
        return [
            '*.student_no' => ['nullable', 'string', 'max:30'],
            '*.name' => ['nullable', 'string', 'max:100'],
            '*.class_name' => ['nullable', 'string', 'max:100'],
            '*.学号' => ['nullable', 'string', 'max:30'],
            '*.姓名' => ['nullable', 'string', 'max:100'],
            '*.班级' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function onError(\Throwable $e): void
    {
        $this->failureLog[] = ['row' => 0, 'errors' => $e->getMessage()];
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->failureLog[] = [
                'row' => $failure->row(),
                'errors' => [$failure->attribute() => $failure->errors()],
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{student_no:string, name:string, class_name:string}|null
     */
    private function normalize(array $row): ?array
    {
        $studentNo = (string) ($row['student_no'] ?? $row['学号'] ?? '');
        $name = (string) ($row['name'] ?? $row['姓名'] ?? '');
        $className = (string) ($row['class_name'] ?? $row['班级'] ?? '');

        $studentNo = trim($studentNo);
        $name = trim($name);
        $className = trim($className);

        if ($studentNo === '' || $name === '' || $className === '') {
            return null;
        }

        return [
            'student_no' => $studentNo,
            'name' => $name,
            'class_name' => $className,
        ];
    }
}
