<?php

namespace App\Filament\Resources\Holidays\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HolidayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->label('日期')
                    ->required()
                    ->native(false)
                    ->unique(ignoreRecord: true)
                    ->displayFormat('Y-m-d'),
                TextInput::make('name')
                    ->label('名称')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('如：春节、调休补班'),
                Select::make('type')
                    ->label('类型')
                    ->options([
                        'holiday' => '法定假日',
                        'workday' => '调休补班',
                    ])
                    ->default('holiday')
                    ->required(),
                TextInput::make('year')
                    ->label('年度')
                    ->numeric()
                    ->required()
                    ->default(now()->year)
                    ->minValue(2020)
                    ->maxValue(2100),
            ]);
    }
}
