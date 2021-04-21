<?php

namespace ArsoftModules\Keuangan\Controllers\Reports;

use ArsoftModules\Keuangan\Controllers\Controller;
use ArsoftModules\Keuangan\Models\HierarchyOne;
use Illuminate\Support\Carbon;

class IncomeStatementController extends Controller {
    /**
     * @param string $date date_format: Y-m-d
     * @param string $type option: 'month', 'year'
     */
    public function data(
        string $date,
        string $type
    ) {
        $date = Carbon::parse($date);

        $datas = HierarchyOne::where('hs_id', '>', 3)
            ->with(['hierarchySubClass' => function ($q) use ($type, $date) {
                $q->select(
                    'hs_id',
                    'hs_nama',
                    'hs_level_1'
                )
                ->with(['hierarchyTwo' => function ($q) use ($type, $date) {
                    $q->select(
                        'hd_id',
                        'hd_nama',
                        'hd_subclass',
                        'hd_nomor'
                    )
                    ->with(['financeAccount' => function ($q) use ($type, $date) {
                        $q->whereHas('balanceAccounts', function ($q) use ($type, $date) {
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
                        ->loadClosingWithInitialBalanceTotal(
                            $type,
                            $date->year,
                            $date->month
                        );
                    }]);
                }])
                ->orderBy('hs_flag');
            }])
            ->select(
                'hs_id',
                'hs_nama'
            )
            ->get();

        foreach ($datas as $i => $data) {
            foreach ($data->hierarchySubClass as $j => $hierarchySubClass) {
                foreach ($hierarchySubClass->hierarchyTwo as $k => $hierarchyTwo) {
                    foreach ($hierarchyTwo->financeAccount as $l => $financeAccount) {
                        if ($financeAccount->ak_posisi === 'D') {
                            $financeAccount->closing_with_initial_balance_total *= -1;
                        }
                    }
                }
            }
        }

        return [
            'status' => 'success',
            'periods' => [
                'date' => ($type === 'month') ? $date->format('M Y') : $date->format('Y'),
            ],
            'type' => $type,
            'data' => $datas
        ];
    }
}