<?php

namespace ArsoftModules\Keuangan\Models;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    protected $table = 'dk_transaksi_detail';
    protected $primaryKey = ['trdt_transaksi', 'trdt_nomor'];
    public $incrementing = false;

    protected $fillable = [
        'trdt_transaksi',
        'trdt_nomor',
        'trdt_akun',
        'trdt_value',
        'trdt_dk',
        'trdt_cashflow',
        'trdt_keterangan',
    ];

    use Compoships;

    public function transaction()
    {
        return $this->belongsTo('ArsoftModules\Keuangan\Models\Transaction', 'trdt_transaksi', 'tr_id');
    }

    public function financeAccount()
    {
        return $this->belongsTo('ArsoftModules\Keuangan\Models\FinanceAccount', 'trdt_akun', 'ak_id');
    }
}
