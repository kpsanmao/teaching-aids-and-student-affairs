<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels（广播频道授权）
|--------------------------------------------------------------------------
| 对应文档第 7/14 节"Reverb 实时通知 / AI 流式推送"，所有广播事件通过
| 这里的私有频道授权后，再由前端 Laravel Echo 订阅。
*/

// 框架默认通知频道：用户通知
Broadcast::channel('App.Models.User.{id}', fn (User $user, int $id) => $user->id === $id);

// 用户维度的预警/系统通知（前端 users.{id} 订阅）
Broadcast::channel('users.{id}', fn (User $user, int $id) => $user->id === $id);

// 课程维度：教案分析完成、AI 作业建议流式、报告生成等
Broadcast::channel('courses.{courseId}', function (User $user, int $courseId) {
    $course = Course::find($courseId);
    if (! $course) {
        return false;
    }

    return $user->isAdmin() || $course->user_id === $user->id;
});
