<?php

namespace Tests\Feature\Courses;

use App\Models\Course;
use App\Models\Holiday;
use App\Models\User;
use App\Services\ScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createCourseWithSessions(?User $teacher = null): Course
    {
        $teacher ??= User::factory()->teacher()->create();

        $course = Course::factory()->for($teacher, 'teacher')->create([
            'semester_start' => '2026-03-02',
            'semester_end' => '2026-03-29',
            'weekly_days' => [1, 3],
            'periods_per_day' => [
                ['start' => '08:00', 'end' => '09:40'],
                ['start' => '10:00', 'end' => '11:40'],
            ],
            'assignment_count' => 0,
        ]);
        app(ScheduleService::class)->generate($course);

        return $course;
    }

    public function test_index_lists_sessions_of_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = $this->createCourseWithSessions($teacher);

        $this->actingAs($teacher, 'sanctum')
            ->getJson("/api/courses/{$course->id}/sessions")
            ->assertOk()
            ->assertJsonCount(16, 'data')
            ->assertJsonPath('data.0.seq', 1);
    }

    public function test_index_supports_date_range_filter(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = $this->createCourseWithSessions($teacher);

        $this->actingAs($teacher, 'sanctum')
            ->getJson("/api/courses/{$course->id}/sessions?from=2026-03-02&to=2026-03-08")
            ->assertOk()
            ->assertJsonCount(4, 'data'); // 03-02 周一 + 03-04 周三，各 2 节
    }

    public function test_index_rejects_other_teacher(): void
    {
        $owner = User::factory()->teacher()->create();
        $intruder = User::factory()->teacher()->create();
        $course = $this->createCourseWithSessions($owner);

        $this->actingAs($intruder, 'sanctum')
            ->getJson("/api/courses/{$course->id}/sessions")
            ->assertStatus(403);
    }

    public function test_update_shifts_session_to_new_date(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = $this->createCourseWithSessions($teacher);
        $session = $course->sessions()->whereDate('session_date', '2026-03-02')->first();

        $this->actingAs($teacher, 'sanctum')
            ->putJson("/api/sessions/{$session->id}", ['session_date' => '2026-03-10'])
            ->assertOk()
            ->assertJsonPath('data.session_date', '2026-03-10');

        $this->assertSame(2, $course->sessions()->whereDate('session_date', '2026-03-10')->count());
        $this->assertSame(0, $course->sessions()->whereDate('session_date', '2026-03-02')->count());
    }

    public function test_update_rejects_target_on_holiday(): void
    {
        Holiday::factory()->create([
            'date' => '2026-03-10',
            'name' => '测试假日',
            'type' => Holiday::TYPE_HOLIDAY,
            'year' => 2026,
        ]);

        $teacher = User::factory()->teacher()->create();
        $course = $this->createCourseWithSessions($teacher);
        $session = $course->sessions()->first();

        $this->actingAs($teacher, 'sanctum')
            ->putJson("/api/sessions/{$session->id}", ['session_date' => '2026-03-10'])
            ->assertStatus(500); // DomainException 未被捕获时 500
    }

    public function test_cancel_marks_session_status(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = $this->createCourseWithSessions($teacher);
        $session = $course->sessions()->first();

        $this->actingAs($teacher, 'sanctum')
            ->patchJson("/api/sessions/{$session->id}/cancel", ['remark' => '临时外出'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertStringContainsString('临时外出', $session->fresh()->remark);
    }

    public function test_cancel_rejects_already_completed_session(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = $this->createCourseWithSessions($teacher);
        $session = $course->sessions()->first();
        $session->update(['status' => 'completed']);

        $this->actingAs($teacher, 'sanctum')
            ->patchJson("/api/sessions/{$session->id}/cancel")
            ->assertStatus(422);
    }
}
