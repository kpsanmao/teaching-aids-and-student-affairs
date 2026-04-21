<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function show(Course $course): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function update(Request $request, Course $course): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function destroy(Course $course): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function regenerateSchedule(Request $request, Course $course): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function students(Course $course): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function importStudents(Request $request, Course $course): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function removeStudent(Course $course, Student $student): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
