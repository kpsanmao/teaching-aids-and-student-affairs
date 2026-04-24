<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Holiday extends Model
{
    use HasFactory;

    public const TYPE_HOLIDAY = 'holiday';

    public const TYPE_WORKDAY = 'workday';

    protected $guarded = ['id'];

    /**
     * 始终以 `Y-m-d` 字符串进行 DB 存取，避免 MySQL DATE 与 SQLite TEXT
     * 在 updateOrCreate 的 WHERE 匹配时出现 `Y-m-d` vs `Y-m-d H:i:s` 的不一致。
     * 读出时再转换为 Carbon，方便业务代码使用。
     */
    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Carbon::parse($value)->startOfDay() : null,
            set: function ($value): ?string {
                if ($value === null || $value === '') {
                    return null;
                }

                return $value instanceof \DateTimeInterface
                    ? $value->format('Y-m-d')
                    : Carbon::parse((string) $value)->format('Y-m-d');
            },
        );
    }
}
