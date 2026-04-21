<?php

namespace App\Providers;

use App\Models\AssignmentSuggestion;
use App\Models\CourseSession;
use App\Models\LessonPlan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 路由参数名 -> 模型 的显式绑定，方便 routes/api.php 中使用语义化名称
        Route::model('session', CourseSession::class);
        Route::model('lesson_plan', LessonPlan::class);
        Route::model('suggestion', AssignmentSuggestion::class);
    }
}
