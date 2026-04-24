<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentCoursesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('最新课程')
            ->query(Course::query()->latest()->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('课程')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('教师')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('course_type')
                    ->label('类型')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'theory' => '理论',
                        'practice' => '实践',
                        'mixed' => '理论+实践',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'theory' => 'success',
                        'practice' => 'warning',
                        'mixed' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('credit')
                    ->label('学分'),
                Tables\Columns\TextColumn::make('students_count')
                    ->label('学生数')
                    ->counts('students')
                    ->badge(),
                Tables\Columns\TextColumn::make('semester')
                    ->label('学期'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->since(),
            ]);
    }
}
