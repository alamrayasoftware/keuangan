<?php

namespace ArsoftModules\Keuangan\Models;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Model;

class JournalDetail extends Model
{
    protected $table = 'dk_jurnal_detail';
    protected $primaryKey = ['jrdt_jurnal', 'jrdt_nomor'];
    public $incrementing = false;

    public $fillable = [
        'jrdt_jurnal',
        'jrdt_nomor',
        'jrdt_akun',
        'jrdt_value',
        'jrdt_dk',
        'jrdt_cashflow',
        'jrdt_keterangan',
    ];

    use Compoships;

    public function journal()
    {
        return $this->belongsTo('ArsoftModules\Keuangan\Models\Journal', 'jrdt_jurnal', 'jr_id');
    }

    public function financeAccount()
    {
        return $this->belongsTo('ArsoftModules\Keuangan\Models\FinanceAccount', 'jrdt_akun', 'ak_id');
    }
}
