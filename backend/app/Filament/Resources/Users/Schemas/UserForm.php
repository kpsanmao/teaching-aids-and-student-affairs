<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('姓名')
                    ->required()
                    ->maxLength(100),
                TextInput::make('email')
                    ->label('邮箱')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(191),
                Select::make('role')
                    ->label('角色')
                    ->options([
                        'teacher' => '教师',
                        'admin' => '管理员',
                    ])
                    ->default('teacher')
                    ->required(),
                TextInput::make('password')
                    ->label('密码')
                    ->password()
                    ->revealable()
                    ->dehydrateStateUsing(fn ($state) => $state ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(6)
                    ->helperText('编辑时留空表示不修改密码'),
                TextInput::make('avatar')
                    ->label('头像 URL')
                    ->url()
                    ->maxLength(255),
            ]);
    }
}
