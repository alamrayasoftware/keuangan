<?php

namespace ArsoftModules\Keuangan\Controllers\Reports;

use ArsoftModules\Keuangan\Controllers\Controller;
use ArsoftModules\Keuangan\Models\HierarchyOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LedgerReportController extends Controller {
    /**
     * @param string $position position-id
     * @param int $groupId group-id
     * @param string $date date_format: Y-m-d
     * @param string $type option: 'month', 'year'
     */
    public function data(
        string $position,
        int $groupId,
        string $date
    ) {
        $date = Carbon::parse($date);

        $datas = HierarchyOne::where('hs_id', $groupId)
            ->select(
                'hs_id',
                'hs_nama'
            )
            ->with(['hierarchyTwo' => function ($q) use ($position, $date) {
                $q->select(
                        'hd_id', 
                        'hd_level_1', 
                        'hd_nama'
                    )
                    ->with(['financeAccount' => function ($q) use ($position, $date) {
                        $q->position($position)
                            ->whereHas('balanceAccounts', function ($q) use ($date) {
                                $q->filterYear($date->year)
                                    ->filterMonth($date->month);
                            })
                            ->select(
                                'ak_id',
                                'ak_nomor',
                                'ak_nama',
                                'ak_posisi',
                                'ak_kelompok',
                                DB::raw('0 as debit_total'),
                                DB::raw('0 as credit_total'),
                                DB::raw('0 as closing_balance_total')
                            )
                            ->loadInitialBalanceTotal('month', $date->year, $date->month)
                            ->with(['journalDetails' => function ($q) use ($date) {
                                $q->select(
                                    'jrdt_jurnal',
                                    'jrdt_value',
                                    'jrdt_dk',
                                    'jrdt_akun'
                                )
                                ->whereHas('Journal', function ($q) use ($date) {
                                    $q->whereDate('jr_tanggal_trans', '>=', $date->copy()->startOfMonth())
                                        ->whereDate('jr_tanggal_trans', '<', $date->copy()->addMonth()->startOfMonth());
                                })
                                ->leftJoin('dk_jurnal', 'jr_id', 'jrdt_jurnal')
                                ->with(['Journal' => function ($q) use ($date) {
                                    $q->select(
                                            'jr_id',
                                            'jr_nota_ref',
                                            'jr_keterangan',
                                            'jr_tanggal_trans',
                                            DB::raw('0 as balance')
                                        );
                                }])
                                ->orderBy('jr_tanggal_trans', 'asc');
                            }]);
                    }]);
            }])
            ->first();

        return [
            'status' => 'success',
            'periods' => [
                'date' => $date->format('M Y'),
            ],
            'data' => $datas
        ];
    }
}