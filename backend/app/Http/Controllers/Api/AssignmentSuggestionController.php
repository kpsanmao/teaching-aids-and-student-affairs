<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSuggestion;
use App\Models\CourseSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssignmentSuggestionController extends Controller
{
    public function index(CourseSession $session): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function stream(Request $request, CourseSession $session): StreamedResponse|JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function adopt(Request $request, AssignmentSuggestion $suggestion): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function reject(Request $request, AssignmentSuggestion $suggestion): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
