<?php

namespace ArsoftModules\Keuangan\Helpers;

use ArsoftModules\Keuangan\Models\FinanceAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProfitBalance {
    /**
     * @param string $date periode, format: Y-m-d
     * @param string $type type, opt: 'month', 'year'
     */
    public static function data(
        string $date,
        string $type
    )
    {
        $date = Carbon::parse($date);

        $data = FinanceAccount::where(DB::raw('SUBSTRING(ak_nomor, 1, 1)'), '>', 3)
            ->whereHas('balanceAccounts', function ($q) use ($type, $date) {
                $q->filterYear($date->year);
                ($type === 'month') 
                    ? $q->filterMonth($date->month)
                    : '';
            })
            ->select(
                'ak_id',
                'ak_nomor',
                'ak_kelompok',
                'ak_nama',
                'ak_posisi'
            )
            ->loadClosingBalanceTotal($type, $date->year, $date->month)
            ->orderBy('ak_nomor', 'asc')
            ->get();

        $debitTotal = $data->where('ak_posisi', 'D')->sum('closing_balance_total') * -1;
        $creditTotal = $data->where('ak_posisi', 'K')->sum('closing_balance_total');
        $finalTotal = $debitTotal + $creditTotal;
        
        return $finalTotal;
    }
}
