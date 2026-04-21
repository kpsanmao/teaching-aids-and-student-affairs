<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LessonPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonPlanController extends Controller
{
    public function index(Course $course): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function store(Request $request, Course $course): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function show(LessonPlan $lessonPlan): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function destroy(LessonPlan $lessonPlan): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function reanalyze(Request $request, LessonPlan $lessonPlan): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function sections(LessonPlan $lessonPlan): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function confirmSections(Request $request, LessonPlan $lessonPlan): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
