<?php

namespace ArsoftModules\Keuangan\Controllers\Analysis;

use ArsoftModules\Keuangan\Controllers\Controller;
use ArsoftModules\Keuangan\Models\FinanceAccount;
use Illuminate\Support\Carbon;

class AsetEtaController extends Controller
{
    /**
     * @param string $position
     * @param string $startDate date_format: Y-m
     * @param string $endDate date_format: Y-m
     * @param string $type opt : 'month', 'year'
     */
    public function data(
        string $position,
        string $startDate,
        string $endDate,
        string $type = 'month'
    )
    {
        $startDate = Carbon::createFromFormat('Y-m', $startDate);
        $endDate = Carbon::createFromFormat('Y-m', $endDate);

        $data = [
            'period' => [],
            'assets' => [],
            'equities' => []
        ];

        while ($startDate <= $endDate) {
            $year = $startDate->format('Y');
            $month = $startDate->format('m');

            $assets = FinanceAccount::active();
            $equities = FinanceAccount::active();

            $assets = $assets->whereHas('balanceAccounts', function ($q) use ($type, $year, $month) {
                    $q->filterYear($year);
                    ($type === 'month') 
                        ? $q->filterMonth($month)
                        : '';
                })
                ->groupId(16)
                ->loadClosingBalanceTotal($type, $year, $month)
                ->get();
            $assetsClosingBalanceTotal = $assets->sum('closing_balance_total');

            $equities = $equities->whereHas('balanceAccounts', function ($q) use ($type, $year, $month) {
                    $q->filterYear($year);
                    ($type === 'month')
                        ? $q->filterMonth($month)
                        : '';
                })
                ->position($position)
                ->substrNomor(3)
                ->loadClosingBalanceTotal($type, $year, $month)
                ->get();
            $equitiesClosingBalanceTotal = $equities->sum('closing_balance_total');

            array_push($data['assets'], ($assetsClosingBalanceTotal / 1000));
            array_push($data['equities'], ($equitiesClosingBalanceTotal / 1000));
        
            if ($type === 'month') {
                array_push($data['period'], $startDate->format('M y'));
                // increment time
                $startDate = $startDate->addMonth();
            } elseif ($type === 'year') {
                array_push($data['period'], $startDate->format('Y'));
                // increment time
                $startDate = $startDate->addYear();
            }
        }
        
        return [
            'status' => 'success',
            'periods' => $data['period'],
            'assets' => $data['assets'],
            'equities' => $data['equities']
        ];
    }
}