<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataFeed extends Model
{
    use HasFactory;

    /**
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * @var string
     */
    protected $table = 'datafeeds';

    /**
     * Retrieves specific data types from the db
     *
     * @return mixed
     */
    public function getDataFeed(int $dataType, string $field = 'label', ?int $limit = null)
    {
        $query = $this->where('data_type', $dataType)
            ->where(function ($q) use ($field) {
                if ($field == 'label') {
                    $q->whereNotNull('label');
                }
            })->pluck($field)
            ->toArray();

        if ($limit !== null) {
            return array_slice($query, 0, $limit);
        }

        return $query;
    }

    /**
     * Counts a set of data based on the datatype
     *
     * @param  int|null  $limit
     * @return mixed
     */
    public function sumDataSet(int $dataType, ?int $dataset = null)
    {
        $query = $this->where('data_type', $dataType)
            ->where(function ($q) use ($dataset) {
                if ($dataset !== null) {
                    $q->where('dataset_name', $dataset);
                }
            })
            ->sum('data');

        return $query;
    }
}
