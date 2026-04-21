<?php

use App\Http\Controllers\Api\Admin\HolidayController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AssignmentSuggestionController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseSessionController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\LessonPlanController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (学情管理系统)
|--------------------------------------------------------------------------
| 所有业务接口挂在此文件。对应文档第 10 节。
| 目前全部 Controller 方法为占位（返回 501 Not Implemented）。
*/

// ============ 10.1 认证接口（不需要登录） ============
Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');
});

// ============ 需要登录的接口 ============
Route::middleware('auth:sanctum')->group(function (): void {

    // ---- 10.1 认证（需登录部分） ----
    Route::prefix('auth')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('auth.me');
    });

    Route::get('/user', fn (Request $request) => $request->user())
        ->name('user.current');

    // ---- 10.2 课程接口 ----
    Route::apiResource('courses', CourseController::class);
    Route::prefix('courses/{course}')->group(function (): void {
        Route::post('regenerate-schedule', [CourseController::class, 'regenerateSchedule'])
            ->name('courses.regenerate-schedule');

        Route::get('students', [CourseController::class, 'students'])
            ->name('courses.students.index');
        Route::post('students/import', [CourseController::class, 'importStudents'])
            ->name('courses.students.import');
        Route::delete('students/{student}', [CourseController::class, 'removeStudent'])
            ->name('courses.students.remove');
    });

    // ---- 10.3 课次接口 ----
    Route::get('courses/{course}/sessions', [CourseSessionController::class, 'index'])
        ->name('courses.sessions.index');
    Route::put('sessions/{session}', [CourseSessionController::class, 'update'])
        ->name('sessions.update');
    Route::patch('sessions/{session}/cancel', [CourseSessionController::class, 'cancel'])
        ->name('sessions.cancel');

    // ---- 10.4 教案接口 ----
    Route::get('courses/{course}/lesson-plans', [LessonPlanController::class, 'index'])
        ->name('courses.lesson-plans.index');
    Route::post('courses/{course}/lesson-plans', [LessonPlanController::class, 'store'])
        ->name('courses.lesson-plans.store');
    Route::prefix('lesson-plans/{lesson_plan}')->group(function (): void {
        Route::get('/', [LessonPlanController::class, 'show'])->name('lesson-plans.show');
        Route::delete('/', [LessonPlanController::class, 'destroy'])->name('lesson-plans.destroy');
        Route::post('reanalyze', [LessonPlanController::class, 'reanalyze'])
            ->name('lesson-plans.reanalyze');
        Route::get('sections', [LessonPlanController::class, 'sections'])
            ->name('lesson-plans.sections.index');
        Route::put('sections/confirm', [LessonPlanController::class, 'confirmSections'])
            ->name('lesson-plans.sections.confirm');
    });

    // ---- 10.5 作业与 AI 建议接口 ----
    Route::get('courses/{course}/assignments', [AssignmentController::class, 'index'])
        ->name('courses.assignments.index');
    Route::post('courses/{course}/assignments', [AssignmentController::class, 'store'])
        ->name('courses.assignments.store');
    Route::prefix('assignments/{assignment}')->group(function (): void {
        Route::get('/', [AssignmentController::class, 'show'])->name('assignments.show');
        Route::put('/', [AssignmentController::class, 'update'])->name('assignments.update');
        Route::delete('/', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

        Route::get('scores', [GradeController::class, 'scores'])->name('assignments.scores.index');
        Route::post('scores', [GradeController::class, 'batchScores'])->name('assignments.scores.batch');
        Route::post('scores/import', [GradeController::class, 'importScores'])
            ->name('assignments.scores.import');
    });

    Route::get('sessions/{session}/assignment-suggestions', [AssignmentSuggestionController::class, 'index'])
        ->name('sessions.assignment-suggestions.index');
    Route::get('sessions/{session}/assignment-suggestions/stream', [AssignmentSuggestionController::class, 'stream'])
        ->name('sessions.assignment-suggestions.stream');
    Route::post('assignment-suggestions/{suggestion}/adopt', [AssignmentSuggestionController::class, 'adopt'])
        ->name('assignment-suggestions.adopt');
    Route::post('assignment-suggestions/{suggestion}/reject', [AssignmentSuggestionController::class, 'reject'])
        ->name('assignment-suggestions.reject');

    // ---- 10.6 考勤接口 ----
    Route::get('sessions/{session}/attendance', [AttendanceController::class, 'index'])
        ->name('sessions.attendance.index');
    Route::post('sessions/{session}/attendance', [AttendanceController::class, 'batch'])
        ->name('sessions.attendance.batch');
    Route::get('courses/{course}/attendance/summary', [AttendanceController::class, 'summary'])
        ->name('courses.attendance.summary');
    Route::get('courses/{course}/attendance/export', [AttendanceController::class, 'export'])
        ->name('courses.attendance.export');

    // ---- 10.7 成绩接口 ----
    Route::get('courses/{course}/grades/summary', [GradeController::class, 'summary'])
        ->name('courses.grades.summary');
    Route::get('courses/{course}/grades/formula-preview', [GradeController::class, 'formulaPreview'])
        ->name('courses.grades.formula-preview');
    Route::get('courses/{course}/grades/export', [GradeController::class, 'export'])
        ->name('courses.grades.export');

    // ---- 10.8 分析统计接口 ----
    Route::prefix('courses/{course}/analytics')->group(function (): void {
        Route::get('overview', [AnalyticsController::class, 'overview'])
            ->name('courses.analytics.overview');
        Route::get('attendance', [AnalyticsController::class, 'attendance'])
            ->name('courses.analytics.attendance');
        Route::get('grade-distribution', [AnalyticsController::class, 'gradeDistribution'])
            ->name('courses.analytics.grade-distribution');
        Route::get('assignment-trend', [AnalyticsController::class, 'assignmentTrend'])
            ->name('courses.analytics.assignment-trend');
        Route::post('ai-interpret', [AnalyticsController::class, 'aiInterpret'])
            ->name('courses.analytics.ai-interpret');
    });

    Route::get('students/{student}/analytics/radar', [AnalyticsController::class, 'studentRadar'])
        ->name('students.analytics.radar');

    Route::post('courses/{course}/reports', [ReportController::class, 'generate'])
        ->name('courses.reports.generate');
    Route::get('reports/{taskId}', [ReportController::class, 'show'])
        ->name('reports.show');
    Route::get('reports/{taskId}/download', [ReportController::class, 'download'])
        ->name('reports.download');

    // ---- 10.9 预警与通知接口 ----
    Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::patch('alerts/{alert}/read', [AlertController::class, 'markRead'])->name('alerts.read');
    Route::get('courses/{course}/alerts', [AlertController::class, 'forCourse'])
        ->name('courses.alerts.index');

    // ---- 学生独立 CRUD（供档案等场景使用） ----
    Route::apiResource('students', StudentController::class);

    // ---- 10.10 节假日管理接口（管理员） ----
    // 管理员鉴权交由后续 Policy/Gate 处理；此处先仅限已登录。
    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('holidays', [HolidayController::class, 'index'])->name('holidays.index');
        Route::post('holidays/batch', [HolidayController::class, 'batch'])->name('holidays.batch');
        Route::delete('holidays/{holiday}', [HolidayController::class, 'destroy'])
            ->name('holidays.destroy');
    });
});
