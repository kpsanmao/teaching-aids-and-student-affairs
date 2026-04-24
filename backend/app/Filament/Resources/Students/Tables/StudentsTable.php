<?php

namespace App\Filament\Resources\Students\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student_no')
                    ->label('学号')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('姓名')
                    ->searchable(),
                TextColumn::make('class_name')
                    ->label('班级')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                TextColumn::make('courses_count')
                    ->label('所选课程')
                    ->counts('courses')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('created_at')
                    ->label('录入时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('class_name')
                    ->label('班级')
                    ->options(function () {
                        return \App\Models\Student::query()
                            ->select('class_name')
                            ->distinct()
                            ->orderBy('class_name')
                            ->pluck('class_name', 'class_name')
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
            ->defaultSort('student_no');
    }
}
