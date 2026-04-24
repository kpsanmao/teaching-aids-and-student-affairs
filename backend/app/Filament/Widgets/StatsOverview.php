<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use App\Models\Holiday;
use App\Models\Student;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $teacherCount = User::where('role', User::ROLE_TEACHER)->count();
        $studentCount = Student::count();
        $courseCount = Course::count();
        $holidayCount = Holiday::whereYear('date', now()->year)->count();

        return [
            Stat::make('授课教师', $teacherCount)
                ->description('活跃教师账号')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->chart([3, 4, 5, 5, 6, $teacherCount, $teacherCount]),

            Stat::make('在册学生', $studentCount)
                ->description('全部班级合计')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary')
                ->chart([20, 40, 65, 80, 85, 88, $studentCount]),

            Stat::make('开课数量', $courseCount)
                ->description('本学期开设')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('warning')
                ->chart([0, 1, 2, 3, 3, 4, $courseCount]),

            Stat::make(now()->year.' 年节假日', $holidayCount)
                ->description('法定 + 调休')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
        ];
    }
}
