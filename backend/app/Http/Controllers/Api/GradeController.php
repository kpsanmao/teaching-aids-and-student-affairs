<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Grade\ImportScoresRequest;
use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GradeController extends Controller
{
    public function scores(Assignment $assignment): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function batchScores(Request $request, Assignment $assignment): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function importScores(ImportScoresRequest $request, Assignment $assignment): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function summary(Course $course): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function formulaPreview(Request $request, Course $course): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function export(Course $course): BinaryFileResponse|JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
