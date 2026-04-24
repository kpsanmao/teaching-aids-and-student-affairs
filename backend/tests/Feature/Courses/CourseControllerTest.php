<?php

namespace Tests\Feature\Courses;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseControllerTest extends TestCase
{
    use RefreshDatabase;

    private function coursePayload(array $override = []): array
    {
        return array_merge([
            'name' => '数据结构与算法',
            'credit' => 3.0,
            'course_type' => 'theory',
            'semester' => '2026-春',
            'semester_start' => '2026-03-02',
            'semester_end' => '2026-03-29',
            'weekly_days' => [1, 3],
            'periods_per_day' => [
                ['start' => '08:00', 'end' => '09:40'],
                ['start' => '10:00', 'end' => '11:40'],
            ],
            'assignment_count' => 2,
            'max_absence' => 6,
            'remind_before' => 1,
            'grade_formula' => ['attendance' => 0.1, 'assignment' => 0.3, 'final' => 0.6],
            'alert_thresholds' => ['absence_count' => 3, 'assignment_score' => 60],
        ], $override);
    }

    public function test_store_creates_course_and_generates_sessions(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/courses', $this->coursePayload());

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', '数据结构与算法')
            ->assertJsonPath('data.teacher.id', $teacher->id);

        $course = Course::first();
        // 4 周 × 2 天（周一周三） × 2 节 = 16 节
        $this->assertSame(16, $course->sessions()->count());
        // 2 次作业 × remind_before=1 → 应有 2 条 reminder
        $this->assertSame(2, $course->sessions()->where('assignment_reminder', true)->count());
    }

    public function test_index_returns_only_own_courses_for_teacher(): void
    {
        $teacherA = User::factory()->teacher()->create();
        $teacherB = User::factory()->teacher()->create();
        Course::factory()->for($teacherA, 'teacher')->count(2)->create();
        Course::factory()->for($teacherB, 'teacher')->count(3)->create();

        $response = $this->actingAs($teacherA, 'sanctum')
            ->getJson('/api/courses');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_index_returns_all_courses_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        Course::factory()->for($teacher, 'teacher')->count(4)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/courses');

        $response->assertOk()->assertJsonCount(4, 'data');
    }

    public function test_show_rejects_other_teachers_course(): void
    {
        $owner = User::factory()->teacher()->create();
        $intruder = User::factory()->teacher()->create();
        $course = Course::factory()->for($owner, 'teacher')->create();

        $this->actingAs($intruder, 'sanctum')
            ->getJson("/api/courses/{$course->id}")
            ->assertStatus(403);
    }

    public function test_update_regenerates_schedule_when_time_fields_change(): void
    {
        $teacher = User::factory()->teacher()->create();

        $store = $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/courses', $this->coursePayload())
            ->assertStatus(201)
            ->json();
        $courseId = $store['data']['id'];

        // 把周三去掉，只保留周一；课次数从 16 → 8
        $this->actingAs($teacher, 'sanctum')
            ->putJson("/api/courses/{$courseId}", ['weekly_days' => [1]])
            ->assertOk()
            ->assertJsonPath('message', '课程已更新，课次已重排。');

        $this->assertSame(8, Course::find($courseId)->sessions()->count());
    }

    public function test_update_skips_regenerate_when_only_name_changes(): void
    {
        $teacher = User::factory()->teacher()->create();
        $store = $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/courses', $this->coursePayload())->json();
        $courseId = $store['data']['id'];
        $originalCount = Course::find($courseId)->sessions()->count();

        $this->actingAs($teacher, 'sanctum')
            ->putJson("/api/courses/{$courseId}", ['name' => '数据结构（改）'])
            ->assertOk()
            ->assertJsonPath('data.name', '数据结构（改）')
            ->assertJsonPath('message', '课程已更新。');

        $this->assertSame($originalCount, Course::find($courseId)->sessions()->count());
    }

    public function test_regenerate_schedule_endpoint_returns_counts(): void
    {
        $teacher = User::factory()->teacher()->create();
        $store = $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/courses', $this->coursePayload())->json();
        $courseId = $store['data']['id'];

        $this->actingAs($teacher, 'sanctum')
            ->postJson("/api/courses/{$courseId}/regenerate-schedule")
            ->assertOk()
            ->assertJsonPath('data.total', 16)
            ->assertJsonPath('data.scheduled', 16);
    }

    public function test_destroy_soft_deletes_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->for($teacher, 'teacher')->create();

        $this->actingAs($teacher, 'sanctum')
            ->deleteJson("/api/courses/{$course->id}")
            ->assertOk();

        $this->assertSoftDeleted('courses', ['id' => $course->id]);
    }

    public function test_store_validation_rejects_bad_payload(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/courses', ['name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'credit', 'semester_start']);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/courses')->assertStatus(401);
        $this->postJson('/api/courses', $this->coursePayload())->assertStatus(401);
    }
}
