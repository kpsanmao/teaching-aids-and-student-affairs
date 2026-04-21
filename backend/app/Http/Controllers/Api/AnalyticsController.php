<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function overview(Course $course): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function attendance(Request $request, Course $course): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function gradeDistribution(Course $course): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function assignmentTrend(Course $course): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function studentRadar(Request $request, Student $student): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function aiInterpret(Request $request, Course $course): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
