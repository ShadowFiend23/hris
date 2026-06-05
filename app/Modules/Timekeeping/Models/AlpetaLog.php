<?php

namespace App\Modules\Timekeeping\Models;

use Illuminate\Database\Eloquent\Model;

class AlpetaLog extends Model
{
    protected $connection = 'alpeta';

    public $timestamps = false;

    protected $primaryKey = 'index_key';

    /**
     * Set the dynamic table name for a given month.
     *
     * @param  int|string  $yearMonth  e.g. 202603
     */
    public function setTableForMonth(int|string $yearMonth): static
    {
        $this->table = 'auth_logs_'.$yearMonth;

        return $this;
    }

    /**
     * Return a query builder scoped to a specific month's log table.
     *
     * @param  int|string  $yearMonth  e.g. 202603
     */
    public static function forMonth(int|string $yearMonth): \Illuminate\Database\Eloquent\Builder
    {
        return (new static)->setTableForMonth($yearMonth)->newQuery();
    }
}
