<?php

namespace App\Imports;

use App\Models\AssignmentScore;
use Maatwebsite\Excel\Concerns\ToModel;

class AssignmentScoresImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new AssignmentScore([
            //
        ]);
    }
}
