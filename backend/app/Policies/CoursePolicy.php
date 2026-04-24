<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * 管理员无条件通过所有权限检查。
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Course $course): bool
    {
        return $course->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->role === User::ROLE_TEACHER;
    }

    public function update(User $user, Course $course): bool
    {
        return $course->user_id === $user->id;
    }

    public function delete(User $user, Course $course): bool
    {
        return $course->user_id === $user->id;
    }

    public function restore(User $user, Course $course): bool
    {
        return $course->user_id === $user->id;
    }

    public function forceDelete(User $user, Course $course): bool
    {
        return $course->user_id === $user->id;
    }
}
