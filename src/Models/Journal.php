<?php

namespace ArsoftModules\Keuangan\Models;

use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Journal extends Model
{
    protected $table = 'dk_jurnal';
    protected $primaryKey = 'jr_id';

    public $fillable = [
        'jr_type',
        'jr_comp',
        'jr_ref',
        'jr_nota_ref',
        'jr_tanggal_trans',
        'jr_keterangan',
        'jr_memorial',
        'jr_isproses'
    ];
    
    use Compoships;

    public function details()
    {
        return $this->hasMany('ArsoftModules\Keuangan\Models\JournalDetail', 'jrdt_jurnal', 'jr_id');
    }


    /**
     * load total debit from jurnal detail ( where jurnal-id )
     */
    public function scopeLoadDebitTotal($q)
    {
        $q->withCount(['details as debit_total' => function ($q) {
            $q->where('jrdt_dk', 'D')
                ->select(
                    DB::raw('COALESCE(SUM(jrdt_value), 0)')
                )
                ->groupBy('jrdt_jurnal');
        }]);
    }
    /**
     * load total credit from jurnal detail ( where jurnal-id )
     */
    public function scopeLoadCreditTotal($q)
    {
        $q->withCount(['details as credit_total' => function ($q) {
            $q->where('jrdt_dk', 'K')
                ->select(
                    DB::raw('COALESCE(SUM(jrdt_value), 0)')
                )
                ->groupBy('jrdt_jurnal');
        }]);
    }
}
