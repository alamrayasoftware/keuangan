<?php

namespace ArsoftModules\Keuangan\Models;

use Illuminate\Database\Eloquent\Model;

class FinancePeriods extends Model
{
    protected $table = 'dk_periode_keuangan';
    protected $primaryKey = 'pk_id';

    /**
     * filter month
     * @param int $month
     */
    public function scopeFilterMonth($q, $month)
    {
        $q->whereMonth('pk_periode', $month);
    }
    /**
     * filter year
     * @param int $year
     */
    public function scopeFilterYear($q, $year)
    {
        $q->whereYear('pk_periode', $year);
    }
    /**
     * filter status is-active
     */
    public function scopeActive($q)
    {
        $q->where('pk_status', 1);
    }
    /**
     * filter status non-active
     */
    public function scopeNonActive($q)
    {
        $q->where('pk_status', 0);
    }
}
