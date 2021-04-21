<?php

namespace ArsoftModules\Keuangan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FinanceAccount extends Model
{
    protected $table = 'dk_akun';
    protected $primaryKey = 'ak_id';
    public $incrementing = false;

    public function balanceAccounts()
    {
        return $this->hasMany('ArsoftModules\Keuangan\Models\BalanceAccount', 'as_akun', 'ak_id');
    }

    public function journalDetails()
    {
        return $this->hasMany('ArsoftModules\Keuangan\Models\JournalDetail', 'jrdt_akun', 'ak_id');
    }


    /**
     * load total initial-balance ( saldo-awal )
     */
    public function scopeLoadInitialBalanceTotal($q, $type, $year, $month = null)
    {
        $q->withCount(['balanceAccounts as initial_balance_total' => function ($q) use ($type, $year, $month) {
            $q->filterYear($year);
            ($type === 'month')
                ? $q->filterMonth($month)
                : '';
            $q->select(
                DB::raw('COALESCE(SUM(as_saldo_awal), 0)')
            )
            ->groupBy('as_akun');
        }]);
    }
    /**
     * load total closing-balance ( saldo-akhir )
     */
    public function scopeLoadClosingBalanceTotal($q, $type, $year, $month = null)
    {
        $q->withCount(['balanceAccounts as closing_balance_total' => function ($q) use ($type, $year, $month) {
            $q->filterYear($year);
            ($type === 'month')
                ? $q->filterMonth($month)
                : '';
            $q->select(
                DB::raw('COALESCE(SUM(as_saldo_akhir), 0)')
            );
        }]);
    }
    /**
     * load total closing-balance ( saldo-akhir )
     */
    public function scopeLoadClosingWithInitialBalanceTotal($q, $type, $year, $month = null)
    {
        $q->withCount(['balanceAccounts as closing_with_initial_balance_total' => function ($q) use ($type, $year, $month) {
            $q->filterYear($year);
            ($type === 'month')
                ? $q->filterMonth($month)
                : '';
            $q->select(
                DB::raw('COALESCE((as_saldo_akhir - as_saldo_awal), 0)')
            );
        }]);
    }
    /**
     * load total increment ( penambahan )
     */
    public function scopeLoadIncrementTotal($q, $type, $year, $month = null)
    {
        $q->withCount(['balanceAccounts as increment_total' => function ($q) use ($type, $year, $month) {
            $q->filterYear($year);
            ($type === 'month')
                ? $q->filterMonth($month)
                : '';
            $q->select(
                DB::raw('COALESCE(SUM(as_mut_kas_kredit + as_trans_kas_kredit + as_trans_memorial_kredit), 0)')
            );
        }]);
    }
    /**
     * load total decrement ( pengurangan )
     */
    public function scopeLoadDecrementTotal($q, $type, $year, $month = null)
    {
        $q->withCount(['balanceAccounts as decrement_total' => function ($q) use ($type, $year, $month) {
            $q->filterYear($year);
            ($type === 'month')
                ? $q->filterMonth($month)
                : '';
            $q->select(
                DB::raw('COALESCE(SUM(as_mut_kas_debet + as_trans_kas_debet + as_trans_memorial_debet), 0)')
            );
        }]);
    }
    /**
     * filter where active
     */
    public function scopeActive($q)
    {
        $q->where('ak_isactive', '1');
    }
    /**
     * filter where non-active
     */
    public function scopeNonActive($q)
    {
        $q->where('ak_isactive', '0');
    }
    /**
     * filter where group-id
     */
    public function scopeGroupId($q, $groupId)
    {
        $q->where('ak_kelompok', $groupId);
    }
    /**
     * filter where ak_nomor
     */
    public function scopeSubstrNomor($q, $nomor)
    {
        $q->where(DB::raw('SUBSTRING(ak_nomor, 1, 1)'), $nomor);
    }
    /**
     * filter where comp ( position )
     */
    public function scopePosition($q, $positionId)
    {
        $q->where('ak_comp', $positionId);
    }
}
