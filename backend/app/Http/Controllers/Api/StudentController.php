<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StudentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $students = Student::query()
            ->withCount('courses')
            ->when($request->filled('class_name'), fn ($q) => $q->where('class_name', $request->string('class_name')))
            ->when($request->filled('keyword'), function ($q) use ($request): void {
                $kw = (string) $request->string('keyword');
                $q->where(function ($inner) use ($kw): void {
                    $inner->where('name', 'like', "%{$kw}%")
                        ->orWhere('student_no', 'like', "%{$kw}%");
                });
            })
            ->orderBy('student_no')
            ->paginate($request->integer('per_page', 30));

        return StudentResource::collection($students);
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $student = Student::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => StudentResource::make($student),
            'message' => '学生已创建。',
        ], 201);
    }

    public function show(Student $student): JsonResponse
    {
        $student->loadCount('courses');

        return response()->json([
            'success' => true,
            'data' => StudentResource::make($student),
            'message' => 'OK',
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $student->update($request->validated());
        $student->loadCount('courses');

        return response()->json([
            'success' => true,
            'data' => StudentResource::make($student),
            'message' => '学生已更新。',
        ]);
    }

    public function destroy(Student $student): JsonResponse
    {
        $student->delete();

        return response()->json([
            'success' => true,
            'message' => '学生已删除。',
        ]);
    }
}
