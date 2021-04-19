<?php

namespace ArsoftModules\Keuangan\Controllers\Reports;

use ArsoftModules\Keuangan\Controllers\Controller;
use ArsoftModules\Keuangan\Models\HierarchyOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BalanceSheetController extends Controller {
    /**
     * @param string $position position-id
     * @param string $date date_format: Y-m-d
     * @param string $type option: 'general', 'cash', 'memorial'
     */
    public function data(
        string $date,
        string $type
    ) {
        $date = Carbon::parse($date);
        $year = $date->format('Y');
        $month = $date->format('m');

        $data = HierarchyOne::where('hs_id', '<=', '3')
            ->with(['hierarchySubClass' => function ($q) use ($type, $year, $month) {
                $q->select(
                    'hs_id',
                    'hs_nama',
                    'hs_level_1'
                )
                ->orderBy('hs_flag')
                ->with(['hierarchyTwo' => function ($q) use ($type, $year, $month) {
                    $q->select(
                        'hd_id',
                        'hd_nama',
                        'hd_subclass',
                        'hd_nomor'
                    )
                    ->with(['financeAccount' => function ($q) use ($type, $year, $month) {
                        $q->whereHas('balanceAccounts', function ($q) use ($type, $year, $month) {
                            $q->filterYear($year);
                            ($type === 'month')
                                ? $q->filterMonth($month)
                                : '';
                        })
                        ->select(
                            'ak_id',
                            'ak_nomor',
                            'ak_kelompok',
                            'ak_nama',
                            'ak_posisi'
                        )
                        ->loadClosingBalanceTotal(
                            $type,
                            $year,
                            $month
                        );
                    }]);
                }]);
            }])
            ->select(
                'hs_id',
                'hs_nama'
            )
            ->get();

        $profitBalance = 0;

        return [
            'status' => 'success',
            'periods' => [
                'date' => ($type === 'month') ? $date->format('M Y') : $date->format('Y'),
            ],
            'type' => $type,
            'data' => $data,
            'profit_balance' => $profitBalance,
        ];
        
    }
}