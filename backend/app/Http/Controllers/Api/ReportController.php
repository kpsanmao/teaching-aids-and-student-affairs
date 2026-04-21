<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function generate(Request $request, Course $course): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function show(string $taskId): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function download(string $taskId): BinaryFileResponse|JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
