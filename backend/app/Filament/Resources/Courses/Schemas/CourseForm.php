<?php

namespace App\Filament\Resources\Courses\Schemas;

use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('基础信息')
                ->columns(2)
                ->components([
                    Select::make('user_id')
                        ->label('授课教师')
                        ->relationship('teacher', 'name', fn ($query) => $query->where('role', User::ROLE_TEACHER))
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('name')
                        ->label('课程名称')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('credit')
                        ->label('学分')
                        ->numeric()
                        ->step(0.5)
                        ->minValue(0)
                        ->maxValue(10)
                        ->required(),
                    Select::make('course_type')
                        ->label('课程类型')
                        ->options([
                            'theory' => '理论课',
                            'practice' => '实践课',
                            'mixed' => '理论+实践',
                        ])
                        ->default('theory')
                        ->required(),
                ]),

            Section::make('学期安排')
                ->columns(3)
                ->components([
                    TextInput::make('semester')
                        ->label('学期')
                        ->required()
                        ->placeholder('2026-春')
                        ->maxLength(32),
                    DatePicker::make('semester_start')
                        ->label('学期开始')
                        ->required()
                        ->native(false),
                    DatePicker::make('semester_end')
                        ->label('学期结束')
                        ->required()
                        ->native(false)
                        ->after('semester_start'),
                    CheckboxList::make('weekly_days')
                        ->label('每周授课日')
                        ->options([
                            1 => '星期一',
                            2 => '星期二',
                            3 => '星期三',
                            4 => '星期四',
                            5 => '星期五',
                            6 => '星期六',
                            7 => '星期日',
                        ])
                        ->columns(4)
                        ->columnSpanFull()
                        ->required(),
                ]),

            Section::make('教学参数')
                ->columns(3)
                ->components([
                    TextInput::make('assignment_count')
                        ->label('作业预计数量')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(50)
                        ->default(4),
                    TextInput::make('max_absence')
                        ->label('最大缺勤次数')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(50)
                        ->default(6),
                    TextInput::make('remind_before')
                        ->label('提醒提前天数')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(30)
                        ->default(1),
                ]),
        ]);
    }
}
