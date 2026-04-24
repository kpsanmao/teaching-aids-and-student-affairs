<?php

namespace App\Filament\Resources\Courses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('课程名称')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('teacher.name')
                    ->label('教师')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('course_type')
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
                TextColumn::make('credit')
                    ->label('学分')
                    ->sortable(),
                TextColumn::make('semester')
                    ->label('学期')
                    ->searchable(),
                TextColumn::make('students_count')
                    ->label('学生数')
                    ->counts('students')
                    ->badge(),
                TextColumn::make('semester_start')
                    ->label('开始')
                    ->date('Y-m-d')
                    ->toggleable(),
                TextColumn::make('semester_end')
                    ->label('结束')
                    ->date('Y-m-d')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('course_type')
                    ->label('类型')
                    ->options([
                        'theory' => '理论',
                        'practice' => '实践',
                        'mixed' => '理论+实践',
                    ]),
                SelectFilter::make('semester')
                    ->label('学期')
                    ->options(function () {
                        return \App\Models\Course::query()
                            ->select('semester')
                            ->distinct()
                            ->orderByDesc('semester')
                            ->pluck('semester', 'semester')
                            ->all();
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
