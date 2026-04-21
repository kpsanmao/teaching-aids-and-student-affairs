<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\BatchAttendanceRequest;
use App\Models\Course;
use App\Models\CourseSession;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceController extends Controller
{
    public function index(CourseSession $session): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function batch(BatchAttendanceRequest $request, CourseSession $session): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function summary(Course $course): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function export(Course $course): BinaryFileResponse|JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
