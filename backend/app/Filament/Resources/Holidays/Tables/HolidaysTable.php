<?php

namespace App\Filament\Resources\Holidays\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HolidaysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('日期')
                    ->date('Y-m-d')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('名称')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('类型')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'holiday' => '法定假日',
                        'workday' => '调休补班',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'holiday' => 'success',
                        'workday' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('year')
                    ->label('年度')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('year')
                    ->label('年度')
                    ->options(function () {
                        return \App\Models\Holiday::query()
                            ->select('year')
                            ->distinct()
                            ->orderByDesc('year')
                            ->pluck('year', 'year')
                            ->all();
                    }),
                SelectFilter::make('type')
                    ->label('类型')
                    ->options([
                        'holiday' => '法定假日',
                        'workday' => '调休补班',
                    ]),
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
            ->defaultSort('date', 'desc');
    }
}
