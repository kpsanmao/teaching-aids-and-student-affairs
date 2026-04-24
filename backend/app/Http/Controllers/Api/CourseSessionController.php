<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseSessionResource;
use App\Models\Course;
use App\Models\CourseSession;
use App\Services\ScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CourseSessionController extends Controller
{
    public function __construct(private readonly ScheduleService $schedule) {}

    public function index(Request $request, Course $course): AnonymousResourceCollection
    {
        Gate::authorize('view', $course);

        $sessions = $course->sessions()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('session_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('session_date', '<=', $request->date('to')))
            ->orderBy('seq')
            ->get();

        return CourseSessionResource::collection($sessions);
    }

    /**
     * 调课：将课次日期调整到 new_date（调用 ScheduleService::shift）。
     * 也支持更新 topic / remark。
     */
    public function update(Request $request, CourseSession $session): JsonResponse
    {
        Gate::authorize('update', $session->course);

        $data = $request->validate([
            'session_date' => ['sometimes', 'date'],
            'topic' => ['sometimes', 'nullable', 'string', 'max:500'],
            'remark' => ['sometimes', 'nullable', 'string'],
        ]);

        $originalDate = $session->session_date?->format('Y-m-d');

        if (! empty($data['session_date'])) {
            $newDate = \Illuminate\Support\Carbon::parse($data['session_date'])->format('Y-m-d');
            if ($newDate !== $originalDate) {
                $this->schedule->shift($session->course, $originalDate, $newDate);
                $session->refresh();
            }
        }

        $directUpdate = array_intersect_key($data, array_flip(['topic', 'remark']));
        if ($directUpdate !== []) {
            $session->update($directUpdate);
        }

        return response()->json([
            'success' => true,
            'data' => CourseSessionResource::make($session->fresh()),
            'message' => '课次已更新。',
        ]);
    }

    public function cancel(Request $request, CourseSession $session): JsonResponse
    {
        Gate::authorize('update', $session->course);

        if (in_array($session->status, ['completed', 'cancelled'], true)) {
            return response()->json([
                'success' => false,
                'message' => "课次处于 {$session->status} 状态，无法取消。",
            ], 422);
        }

        $data = $request->validate([
            'remark' => ['sometimes', 'nullable', 'string'],
        ]);

        $session->update([
            'status' => 'cancelled',
            'remark' => trim(($session->remark ?? '')."\n[取消] ".($data['remark'] ?? '教师取消')),
        ]);

        return response()->json([
            'success' => true,
            'data' => CourseSessionResource::make($session->fresh()),
            'message' => '课次已取消。',
        ]);
    }
}
