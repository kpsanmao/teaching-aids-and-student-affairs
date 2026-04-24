<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * 2025 年节假日基于「国务院办公厅 国办发明电〔2024〕8 号」。
 * 2026 年数据为示例占位，生产环境请用 `php artisan holidays:sync` 从官方 API 同步。
 */
class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->holidays() as $row) {
            $date = Carbon::parse($row['date']);
            Holiday::updateOrCreate(
                ['date' => $date->format('Y-m-d')],
                [
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'year' => $date->year,
                ],
            );
        }
    }

    /**
     * @return list<array{date: string, name: string, type: 'holiday'|'workday'}>
     */
    private function holidays(): array
    {
        return [
            // ---- 2025 ----
            ['date' => '2025-01-01', 'name' => '元旦', 'type' => 'holiday'],
            ['date' => '2025-01-26', 'name' => '春节调休补班', 'type' => 'workday'],
            ['date' => '2025-01-28', 'name' => '春节', 'type' => 'holiday'],
            ['date' => '2025-01-29', 'name' => '春节', 'type' => 'holiday'],
            ['date' => '2025-01-30', 'name' => '春节', 'type' => 'holiday'],
            ['date' => '2025-01-31', 'name' => '春节', 'type' => 'holiday'],
            ['date' => '2025-02-01', 'name' => '春节', 'type' => 'holiday'],
            ['date' => '2025-02-02', 'name' => '春节', 'type' => 'holiday'],
            ['date' => '2025-02-03', 'name' => '春节', 'type' => 'holiday'],
            ['date' => '2025-02-04', 'name' => '春节', 'type' => 'holiday'],
            ['date' => '2025-02-08', 'name' => '春节调休补班', 'type' => 'workday'],
            ['date' => '2025-04-04', 'name' => '清明节', 'type' => 'holiday'],
            ['date' => '2025-04-05', 'name' => '清明节', 'type' => 'holiday'],
            ['date' => '2025-04-06', 'name' => '清明节', 'type' => 'holiday'],
            ['date' => '2025-04-27', 'name' => '劳动节调休补班', 'type' => 'workday'],
            ['date' => '2025-05-01', 'name' => '劳动节', 'type' => 'holiday'],
            ['date' => '2025-05-02', 'name' => '劳动节', 'type' => 'holiday'],
            ['date' => '2025-05-03', 'name' => '劳动节', 'type' => 'holiday'],
            ['date' => '2025-05-04', 'name' => '劳动节', 'type' => 'holiday'],
            ['date' => '2025-05-05', 'name' => '劳动节', 'type' => 'holiday'],
            ['date' => '2025-05-31', 'name' => '端午节', 'type' => 'holiday'],
            ['date' => '2025-06-01', 'name' => '端午节', 'type' => 'holiday'],
            ['date' => '2025-06-02', 'name' => '端午节', 'type' => 'holiday'],
            ['date' => '2025-09-28', 'name' => '国庆节调休补班', 'type' => 'workday'],
            ['date' => '2025-10-01', 'name' => '国庆节', 'type' => 'holiday'],
            ['date' => '2025-10-02', 'name' => '国庆节', 'type' => 'holiday'],
            ['date' => '2025-10-03', 'name' => '国庆节', 'type' => 'holiday'],
            ['date' => '2025-10-04', 'name' => '国庆节', 'type' => 'holiday'],
            ['date' => '2025-10-05', 'name' => '国庆节/中秋节', 'type' => 'holiday'],
            ['date' => '2025-10-06', 'name' => '国庆节', 'type' => 'holiday'],
            ['date' => '2025-10-07', 'name' => '国庆节', 'type' => 'holiday'],
            ['date' => '2025-10-08', 'name' => '国庆节', 'type' => 'holiday'],
            ['date' => '2025-10-11', 'name' => '国庆节调休补班', 'type' => 'workday'],

            // ---- 2026（示例占位，以 holidays:sync 为准）----
            ['date' => '2026-01-01', 'name' => '元旦', 'type' => 'holiday'],
            ['date' => '2026-01-02', 'name' => '元旦', 'type' => 'holiday'],
            ['date' => '2026-01-03', 'name' => '元旦', 'type' => 'holiday'],
            ['date' => '2026-05-01', 'name' => '劳动节', 'type' => 'holiday'],
            ['date' => '2026-05-02', 'name' => '劳动节', 'type' => 'holiday'],
            ['date' => '2026-05-03', 'name' => '劳动节', 'type' => 'holiday'],
            ['date' => '2026-10-01', 'name' => '国庆节', 'type' => 'holiday'],
            ['date' => '2026-10-02', 'name' => '国庆节', 'type' => 'holiday'],
            ['date' => '2026-10-03', 'name' => '国庆节', 'type' => 'holiday'],
            ['date' => '2026-10-04', 'name' => '国庆节', 'type' => 'holiday'],
            ['date' => '2026-10-05', 'name' => '国庆节', 'type' => 'holiday'],
            ['date' => '2026-10-06', 'name' => '国庆节', 'type' => 'holiday'],
            ['date' => '2026-10-07', 'name' => '国庆节', 'type' => 'holiday'],
        ];
    }
}
