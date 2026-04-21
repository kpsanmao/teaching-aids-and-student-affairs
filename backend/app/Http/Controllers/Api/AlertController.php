<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function markRead(Request $request, Alert $alert): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function forCourse(Course $course): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
