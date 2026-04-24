<?php

namespace Tests\Feature\Admin;

use App\Models\Holiday;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HolidayControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_holidays_filtered_by_year(): void
    {
        $admin = User::factory()->admin()->create();

        Holiday::factory()->create(['date' => '2025-01-01', 'name' => '元旦', 'year' => 2025]);
        Holiday::factory()->create(['date' => '2026-01-01', 'name' => '元旦', 'year' => 2026]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/holidays?year=2025');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.year', 2025);
    }

    public function test_index_rejects_non_admin_users(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher, 'sanctum')
            ->getJson('/api/admin/holidays')
            ->assertStatus(403);
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/admin/holidays')->assertStatus(401);
    }

    public function test_batch_upsert_creates_holidays(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/holidays/batch', [
                'holidays' => [
                    ['date' => '2025-05-01', 'name' => '劳动节', 'type' => 'holiday'],
                    ['date' => '2025-05-02', 'name' => '劳动节', 'type' => 'holiday'],
                    ['date' => '2025-04-27', 'name' => '调休补班', 'type' => 'workday'],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.upserted', 3);

        $this->assertDatabaseCount('holidays', 3);
        $this->assertDatabaseHas('holidays', [
            'date' => '2025-04-27',
            'type' => 'workday',
            'year' => 2025,
        ]);
    }

    public function test_batch_upsert_is_idempotent_on_same_date(): void
    {
        $admin = User::factory()->admin()->create();
        Holiday::factory()->create(['date' => '2025-05-01', 'name' => '旧名', 'type' => 'holiday', 'year' => 2025]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/holidays/batch', [
                'holidays' => [
                    ['date' => '2025-05-01', 'name' => '劳动节', 'type' => 'holiday'],
                ],
            ])->assertStatus(201);

        $this->assertDatabaseCount('holidays', 1);
        $this->assertDatabaseHas('holidays', [
            'date' => '2025-05-01',
            'name' => '劳动节',
        ]);
    }

    public function test_batch_rejects_invalid_type(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/holidays/batch', [
                'holidays' => [
                    ['date' => '2025-05-01', 'name' => '劳动节', 'type' => 'invalid'],
                ],
            ])->assertStatus(422)
            ->assertJsonValidationErrors(['holidays.0.type']);
    }

    public function test_destroy_removes_holiday(): void
    {
        $admin = User::factory()->admin()->create();
        $holiday = Holiday::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/holidays/{$holiday->id}")
            ->assertOk();

        $this->assertDatabaseMissing('holidays', ['id' => $holiday->id]);
    }

    public function test_destroy_rejects_teacher(): void
    {
        $teacher = User::factory()->teacher()->create();
        $holiday = Holiday::factory()->create();

        $this->actingAs($teacher, 'sanctum')
            ->deleteJson("/api/admin/holidays/{$holiday->id}")
            ->assertStatus(403);
    }
}
