<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseSessionController extends Controller
{
    public function index(Request $request, Course $course): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function show(CourseSession $session): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function update(Request $request, CourseSession $session): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function cancel(Request $request, CourseSession $session): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
