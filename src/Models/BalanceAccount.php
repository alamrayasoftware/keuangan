<?php

namespace ArsoftModules\Keuangan\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceAccount extends Model
{
    protected $table = 'dk_akun_saldo';
    protected $primaryKey = 'as_id';

    public function financeAccount()
    {
        return $this->belongsTo('ArsoftModules\Keuangan\Models\FinanceAccount', 'as_akun', 'ak_id');
    }


    /**
     * filter month
     * @param int $month
     */
    public function scopeFilterMonth($q, $month)
    {
        $q->whereMonth('as_periode', $month);
    }
    /**
     * filter year
     * @param int $year
     */
    public function scopeFilterYear($q, $year)
    {
        $q->whereYear('as_periode', $year);
    }
}
