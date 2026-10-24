<?php

namespace App\Imports;

use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ContactoImport implements ToModel, WithHeadingRow
{
    /**
     * @return Model|null
     */
    public function model(array $row) {}
}
