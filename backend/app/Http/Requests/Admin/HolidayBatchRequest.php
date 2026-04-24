<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class HolidayBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('admin');
    }

    /**
     * @return array<string, array<int, string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'holidays' => ['required', 'array', 'min:1'],
            'holidays.*.date' => ['required', 'date_format:Y-m-d'],
            'holidays.*.name' => ['required', 'string', 'max:100'],
            'holidays.*.type' => ['required', 'in:holiday,workday'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'holidays' => '节假日列表',
            'holidays.*.date' => '日期',
            'holidays.*.name' => '名称',
            'holidays.*.type' => '类型',
        ];
    }
}
