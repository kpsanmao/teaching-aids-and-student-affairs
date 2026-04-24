<?php

namespace Tests\Feature\Students;

use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_students_with_pagination(): void
    {
        $teacher = User::factory()->teacher()->create();
        Student::factory()->count(3)->create();

        $response = $this->actingAs($teacher, 'sanctum')
            ->getJson('/api/students');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'student_no', 'name', 'class_name']], 'meta']);
        $this->assertSame(3, count($response->json('data')));
    }

    public function test_index_supports_keyword_search(): void
    {
        $teacher = User::factory()->teacher()->create();
        Student::factory()->create(['name' => '张三', 'student_no' => '2024001']);
        Student::factory()->create(['name' => '李四', 'student_no' => '2024002']);

        $response = $this->actingAs($teacher, 'sanctum')
            ->getJson('/api/students?keyword=张三');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame('张三', $data[0]['name']);
    }

    public function test_store_creates_student(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/students', [
                'student_no' => '2024999',
                'name' => '王五',
                'class_name' => '软件工程 2301',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.student_no', '2024999');

        $this->assertDatabaseHas('students', ['student_no' => '2024999', 'name' => '王五']);
    }

    public function test_store_rejects_duplicate_student_no(): void
    {
        $teacher = User::factory()->teacher()->create();
        Student::factory()->create(['student_no' => '2024888']);

        $response = $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/students', [
                'student_no' => '2024888',
                'name' => '重名',
                'class_name' => '重班',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('student_no');
    }

    public function test_update_modifies_student(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = Student::factory()->create(['name' => '旧名']);

        $response = $this->actingAs($teacher, 'sanctum')
            ->putJson("/api/students/{$student->id}", ['name' => '新名']);

        $response->assertOk()
            ->assertJsonPath('data.name', '新名');
    }

    public function test_destroy_removes_student(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = Student::factory()->create();

        $response = $this->actingAs($teacher, 'sanctum')
            ->deleteJson("/api/students/{$student->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/students')->assertUnauthorized();
    }
}
