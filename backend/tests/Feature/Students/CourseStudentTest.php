<?php

namespace Tests\Feature\Students;

use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class CourseStudentTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_lists_course_students(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->for($teacher, 'teacher')->create();
        $students = Student::factory()->count(3)->create();
        $course->students()->sync($students->pluck('id')->mapWithKeys(
            fn ($id) => [$id => ['enrolled_at' => Carbon::now()]]
        )->toArray());

        $response = $this->actingAs($teacher, 'sanctum')
            ->getJson("/api/courses/{$course->id}/students");

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_students_rejects_other_teacher(): void
    {
        $teacherA = User::factory()->teacher()->create();
        $teacherB = User::factory()->teacher()->create();
        $course = Course::factory()->for($teacherA, 'teacher')->create();

        $response = $this->actingAs($teacherB, 'sanctum')
            ->getJson("/api/courses/{$course->id}/students");

        $response->assertForbidden();
    }

    public function test_import_students_via_excel_fake(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->for($teacher, 'teacher')->create();

        Excel::fake();

        $file = UploadedFile::fake()->create('students.xlsx', 10);

        $response = $this->actingAs($teacher, 'sanctum')
            ->postJson("/api/courses/{$course->id}/students/import", [
                'file' => $file,
            ]);

        $response->assertOk()->assertJsonPath('success', true);

        Excel::assertImported('students.xlsx');
    }

    public function test_import_rejects_non_excel_file(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->for($teacher, 'teacher')->create();

        $file = UploadedFile::fake()->create('bad.pdf', 10, 'application/pdf');

        $response = $this->actingAs($teacher, 'sanctum')
            ->postJson("/api/courses/{$course->id}/students/import", [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_import_rejected_when_not_owner(): void
    {
        $teacherA = User::factory()->teacher()->create();
        $teacherB = User::factory()->teacher()->create();
        $course = Course::factory()->for($teacherA, 'teacher')->create();

        $file = UploadedFile::fake()->create('students.xlsx', 5);

        $response = $this->actingAs($teacherB, 'sanctum')
            ->postJson("/api/courses/{$course->id}/students/import", [
                'file' => $file,
            ]);

        $response->assertForbidden();
    }

    public function test_remove_student_detaches_from_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->for($teacher, 'teacher')->create();
        $student = Student::factory()->create();
        $course->students()->attach($student->id, ['enrolled_at' => Carbon::now()]);

        $response = $this->actingAs($teacher, 'sanctum')
            ->deleteJson("/api/courses/{$course->id}/students/{$student->id}");

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame(0, $course->students()->count());
        // 学生档案仍然保留
        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_remove_student_rejects_other_teacher(): void
    {
        $teacherA = User::factory()->teacher()->create();
        $teacherB = User::factory()->teacher()->create();
        $course = Course::factory()->for($teacherA, 'teacher')->create();
        $student = Student::factory()->create();
        $course->students()->attach($student->id, ['enrolled_at' => Carbon::now()]);

        $response = $this->actingAs($teacherB, 'sanctum')
            ->deleteJson("/api/courses/{$course->id}/students/{$student->id}");

        $response->assertForbidden();
    }
}
