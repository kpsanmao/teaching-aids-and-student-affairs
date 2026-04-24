<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\StoreCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Http\Requests\Student\ImportStudentsRequest;
use App\Http\Resources\CourseResource;
use App\Http\Resources\StudentResource;
use App\Imports\StudentsImport;
use App\Models\Course;
use App\Models\Student;
use App\Services\ScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class CourseController extends Controller
{
    public function __construct(private readonly ScheduleService $schedule) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $courses = Course::query()
            ->with('teacher')
            ->withCount(['sessions', 'students'])
            ->when(
                ! $user->isAdmin(),
                fn ($q) => $q->where('user_id', $user->id),
            )
            ->when($request->filled('semester'), fn ($q) => $q->where('semester', $request->string('semester')))
            ->orderByDesc('id')
            ->get();

        return CourseResource::collection($courses);
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $course = DB::transaction(function () use ($data) {
            $course = Course::create($data);
            $this->schedule->generate($course);

            return $course;
        });

        $course->load('teacher')->loadCount(['sessions', 'students']);

        return response()->json([
            'success' => true,
            'data' => CourseResource::make($course),
            'message' => '课程创建成功，已自动生成课次。',
        ], 201);
    }

    public function show(Course $course): JsonResponse
    {
        Gate::authorize('view', $course);

        $course->load('teacher')->loadCount(['sessions', 'students']);

        return response()->json([
            'success' => true,
            'data' => CourseResource::make($course),
            'message' => 'OK',
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
        $data = $request->validated();

        $scheduleKeys = ['semester_start', 'semester_end', 'weekly_days', 'periods_per_day'];
        $scheduleChanged = collect($scheduleKeys)
            ->contains(fn ($k) => array_key_exists($k, $data) && $data[$k] != $course->{$k});

        $reminderChanged = array_key_exists('assignment_count', $data) || array_key_exists('remind_before', $data);

        DB::transaction(function () use ($course, $data, $scheduleChanged, $reminderChanged): void {
            $course->update($data);

            if ($scheduleChanged) {
                $this->schedule->regenerate($course);
            } elseif ($reminderChanged) {
                $this->schedule->markAssignmentReminder($course);
            }
        });

        $course->refresh()->load('teacher')->loadCount(['sessions', 'students']);

        return response()->json([
            'success' => true,
            'data' => CourseResource::make($course),
            'message' => $scheduleChanged ? '课程已更新，课次已重排。' : '课程已更新。',
        ]);
    }

    public function destroy(Course $course): JsonResponse
    {
        Gate::authorize('delete', $course);
        $course->delete();

        return response()->json([
            'success' => true,
            'message' => '课程已删除。',
        ]);
    }

    public function regenerateSchedule(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('update', $course);

        $sessions = $this->schedule->regenerate($course);

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $sessions->count(),
                'scheduled' => $sessions->where('status', 'scheduled')->count(),
                'completed' => $sessions->where('status', 'completed')->count(),
            ],
            'message' => "已完成课次重排，共 {$sessions->count()} 节。",
        ]);
    }

    public function students(Request $request, Course $course): AnonymousResourceCollection
    {
        Gate::authorize('view', $course);

        $students = $course->students()
            ->when($request->filled('class_name'), fn ($q) => $q->where('class_name', $request->string('class_name')))
            ->when($request->filled('keyword'), function ($q) use ($request): void {
                $kw = (string) $request->string('keyword');
                $q->where(function ($inner) use ($kw): void {
                    $inner->where('students.name', 'like', "%{$kw}%")
                        ->orWhere('students.student_no', 'like', "%{$kw}%");
                });
            })
            ->orderBy('students.student_no')
            ->paginate($request->integer('per_page', 50));

        return StudentResource::collection($students);
    }

    public function importStudents(ImportStudentsRequest $request, Course $course): JsonResponse
    {
        $import = new StudentsImport($course);
        Excel::import($import, $request->file('file'));

        $course->loadCount('students');

        return response()->json([
            'success' => true,
            'data' => [
                'attached' => $import->attached,
                'created' => $import->created,
                'total_students' => $course->students_count,
                'failures' => $import->failureLog,
            ],
            'message' => "导入成功：新建 {$import->created} 人，加入课程 {$import->attached} 人。",
        ]);
    }

    public function removeStudent(Course $course, Student $student): JsonResponse
    {
        Gate::authorize('update', $course);

        $course->students()->detach($student->id);

        return response()->json([
            'success' => true,
            'message' => "学生 {$student->name} 已从课程中移除。",
        ]);
    }
}
