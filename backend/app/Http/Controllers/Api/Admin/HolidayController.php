<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HolidayBatchRequest;
use App\Http\Resources\HolidayResource;
use App\Models\Holiday;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class HolidayController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('admin');

        $year = $request->integer('year') ?: null;

        $holidays = Holiday::query()
            ->when($year, fn ($q) => $q->where('year', $year))
            ->orderBy('date')
            ->get();

        return HolidayResource::collection($holidays);
    }

    public function batch(HolidayBatchRequest $request): JsonResponse
    {
        $payload = $request->validated('holidays');

        $upserted = DB::transaction(function () use ($payload): int {
            $count = 0;
            foreach ($payload as $row) {
                $date = Carbon::parse($row['date']);
                Holiday::updateOrCreate(
                    ['date' => $date->format('Y-m-d')],
                    [
                        'name' => $row['name'],
                        'type' => $row['type'],
                        'year' => $date->year,
                    ],
                );
                $count++;
            }

            return $count;
        });

        return response()->json([
            'success' => true,
            'data' => ['upserted' => $upserted],
            'message' => "已写入/更新 {$upserted} 条节假日记录。",
        ], 201);
    }

    public function destroy(Holiday $holiday): JsonResponse
    {
        Gate::authorize('admin');

        $holiday->delete();

        return response()->json([
            'success' => true,
            'message' => '已删除。',
        ]);
    }
}
