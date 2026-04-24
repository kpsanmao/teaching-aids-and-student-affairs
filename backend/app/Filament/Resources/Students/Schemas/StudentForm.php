<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('student_no')
                    ->label('学号')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(32),
                TextInput::make('name')
                    ->label('姓名')
                    ->required()
                    ->maxLength(100),
                TextInput::make('class_name')
                    ->label('班级')
                    ->required()
                    ->maxLength(100)
                    ->datalist([
                        '计算机科学 2301',
                        '软件工程 2301',
                        '数据科学 2301',
                    ]),
            ]);
    }
}
