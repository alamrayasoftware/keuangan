<?php

namespace ArsoftModules\Keuangan\Models;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'dk_transaksi';
    protected $primaryKey = 'tr_id';

    use Compoships;

    public function details()
    {
        return $this->hasMany('ArsoftModules\Keuangan\Models\TransactionDetail', 'trdt_transaksi', 'tr_id');
    }


    /**
     * filter transaction type
     * @param string $type
     */
    public function scopeFilterType($q, $type)
    {
        $q->where('tr_type', $type);
    }
    /**
     * filter month
     * @param int $month
     */
    public function scopeFilterMonth($q, $month)
    {
        $q->whereMonth('tr_tanggal_trans', $month);
    }
    /**
     * filter year
     * @param int $year
     */
    public function scopeFilterYear($q, $year)
    {
        $q->whereYear('tr_tanggal_trans', $year);
    }
}
