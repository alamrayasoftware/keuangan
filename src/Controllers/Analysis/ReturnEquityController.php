<?php

namespace ArsoftModules\Keuangan\Controllers\Analysis;

use ArsoftModules\Keuangan\Controllers\Controller;
use ArsoftModules\Keuangan\Models\FinanceAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReturnEquityController extends Controller
{
    /**
     * @param string $year date_format: Y
     */
    public function data(
        string $year
    )
    {
        $date = Carbon::createFromFormat('Y', $year)->endOfYear();

        if ($date->year == Carbon::now()->year) {
            $date = Carbon::now()->endOfMonth();
        }

        $total = [
            'expenses' => 0,
            'income' => 0,
            'equity' => 0,
            'assets' => 0
        ];

        $expenses = [
            'other_expenses' => 0,
            'admin_expenses' => 0,
            'hpp' => 0
        ];

        $assets = [
            'current_assets' => 0,
            'fixed_assets' => 0
        ];

        $currentAssets = [
            'cash' => 0,
            'receivable' => 0,
            'stock' => 0
        ];

        $fixedAssets = [
            'tangible' => 0,
            'intangible' => 0
        ];

        $otherExpenses = FinanceAccount::whereIn('ak_kelompok', function($q) {
                $q->select('hd_id')->from('dk_hierarki_dua')
                    ->where('hd_level_1', 8)
                    ->orWhere('hd_level_1', 9);
            })
            ->whereHas('balanceAccounts', function ($q) use ($date) {
                $q->filterYear($date->year)
                    ->filterMonth($date->month);
            })
            ->select(
                'ak_id',
                'ak_posisi',
            )
            ->loadClosingBalanceTotal('month', $date->year, $date->month)
            ->get();

        $tempDebitTotal = $otherExpenses->where('ak_posisi', 'D')->sum('closing_balance_total');
        $tempCreditTotal = $otherExpenses->where('ak_posisi', 'K')->sum('closing_balance_total') * -1;
        $tempVal = ($tempDebitTotal + $tempCreditTotal);

        $expenses['other_expenses'] = $tempVal;
        $total['expenses'] += (float) $tempVal;

        $adminExpenses = FinanceAccount::whereIn('ak_kelompok', function($q) {
                $q->select('hd_id')->from('dk_hierarki_dua')
                    ->where('hd_level_1', 6)
                    ->orWhere('hd_level_1', 7);
            })
            ->whereHas('balanceAccounts', function ($q) use ($date) {
                $q->filterYear($date->year)
                    ->filterMonth($date->month);
            })
            ->select(
                'ak_id',
                'ak_posisi',
            )
            ->loadClosingBalanceTotal('month', $date->year, $date->month)
            ->get();

        $tempDebitTotal = $adminExpenses->where('ak_posisi', 'D')->sum('closing_balance_total');
        $tempCreditTotal = $adminExpenses->where('ak_posisi', 'K')->sum('closing_balance_total') * -1;
        $tempVal = ($tempDebitTotal + $tempCreditTotal);

        $expenses['admin_expenses'] = $tempVal;
        $total['expenses'] += (float) $tempVal;

        $hpp = FinanceAccount::whereIn('ak_kelompok', function($q) {
                $q->select('hd_id')->from('dk_hierarki_dua')
                    ->where('hd_level_1', 5);
            })
            ->whereHas('balanceAccounts', function ($q) use ($date) {
                $q->filterYear($date->year)
                    ->filterMonth($date->month);
            })
            ->select(
                'ak_id',
                'ak_posisi',
            )
            ->loadClosingBalanceTotal('month', $date->year, $date->month)
            ->get();

        $tempDebitTotal = $hpp->where('ak_posisi', 'D')->sum('closing_balance_total');
        $tempCreditTotal = $hpp->where('ak_posisi', 'K')->sum('closing_balance_total') * -1;
        $tempVal = ($tempDebitTotal + $tempCreditTotal);

        $expenses['hpp'] = $tempVal;
        $total['expenses'] += (float) $tempVal;

        $income = FinanceAccount::whereIn('ak_kelompok', function($q) {
                $q->select('hd_id')->from('dk_hierarki_dua')
                    ->where('hd_level_1', 4);
            })
            ->whereHas('balanceAccounts', function ($q) use ($date) {
                $q->filterYear($date->year)
                    ->filterMonth($date->month);
            })
            ->select(
                'ak_id',
                'ak_posisi',
            )
            ->loadClosingBalanceTotal('month', $date->year, $date->month)
            ->get();

        $tempDebitTotal = $income->where('ak_posisi', 'D')->sum('closing_balance_total') * -1;
        $tempCreditTotal = $income->where('ak_posisi', 'K')->sum('closing_balance_total');
        $tempVal = ($tempDebitTotal + $tempCreditTotal);

        $total['income'] += (float) $tempVal;

        $equity = FinanceAccount::whereIn('ak_kelompok', function($q) {
                $q->select('hd_id')->from('dk_hierarki_dua')
                    ->where('hd_level_1', 3);
            })
            ->whereHas('balanceAccounts', function ($q) use ($date) {
                $q->filterYear($date->year)
                    ->filterMonth($date->month);
            })
            ->select(
                'ak_id',
                'ak_posisi',
            )
            ->loadClosingBalanceTotal('month', $date->year, $date->month)
            ->get();

        $tempDebitTotal = $equity->where('ak_posisi', 'D')->sum('closing_balance_total') * -1;
        $tempCreditTotal = $equity->where('ak_posisi', 'K')->sum('closing_balance_total');
        $tempVal = ($tempDebitTotal + $tempCreditTotal);

        $total['equity'] += (float) $tempVal;

        $cash = FinanceAccount::whereIn('ak_kelompok', function($q) {
                $q->select('hd_id')->from('dk_hierarki_dua')
                    ->where('hd_id', 13);
            })
            ->whereHas('balanceAccounts', function ($q) use ($date) {
                $q->filterYear($date->year)
                    ->filterMonth($date->month);
            })
            ->select(
                'ak_id',
                'ak_posisi',
            )
            ->loadClosingBalanceTotal('month', $date->year, $date->month)
            ->get();

        $tempDebitTotal = $cash->where('ak_posisi', 'D')->sum('closing_balance_total');
        $tempCreditTotal = $cash->where('ak_posisi', 'K')->sum('closing_balance_total') * -1;
        $tempVal = ($tempDebitTotal + $tempCreditTotal);

        $currentAssets['cash'] = $tempVal;
        $assets['current_assets'] += (float) $tempVal;

        $receivable = FinanceAccount::whereIn('ak_kelompok', function($q) {
                $q->select('hd_id')->from('dk_hierarki_dua')
                    ->where('hd_id', 14);
            })
            ->whereHas('balanceAccounts', function ($q) use ($date) {
                $q->filterYear($date->year)
                    ->filterMonth($date->month);
            })
            ->select(
                'ak_id',
                'ak_posisi',
            )
            ->loadClosingBalanceTotal('month', $date->year, $date->month)
            ->get();

        $tempDebitTotal = $receivable->where('ak_posisi', 'D')->sum('closing_balance_total');
        $tempCreditTotal = $receivable->where('ak_posisi', 'K')->sum('closing_balance_total') * -1;
        $tempVal = ($tempDebitTotal + $tempCreditTotal);

        $currentAssets['receivable'] = $tempVal;
        $assets['current_assets'] += (float) $tempVal;

        $stock = FinanceAccount::whereIn('ak_kelompok', function($q) {
                $q->select('hd_id')->from('dk_hierarki_dua')
                    ->where('hd_id', 15);
            })
            ->whereHas('balanceAccounts', function ($q) use ($date) {
                $q->filterYear($date->year)
                    ->filterMonth($date->month);
            })
            ->select(
                'ak_id',
                'ak_posisi',
            )
            ->loadClosingBalanceTotal('month', $date->year, $date->month)
            ->get();

        $tempDebitTotal = $stock->where('ak_posisi', 'D')->sum('closing_balance_total');
        $tempCreditTotal = $stock->where('ak_posisi', 'K')->sum('closing_balance_total') * -1;
        $tempVal = ($tempDebitTotal + $tempCreditTotal);

        $currentAssets['stock'] = $tempVal;
        $assets['current_assets'] += (float) $tempVal;

        $tangible = FinanceAccount::whereIn('ak_kelompok', function($q) {
                $q->select('hd_id')->from('dk_hierarki_dua')
                    ->where('hd_subclass', 11);
            })
            ->whereHas('balanceAccounts', function ($q) use ($date) {
                $q->filterYear($date->year)
                    ->filterMonth($date->month);
            })
            ->select(
                'ak_id',
                'ak_posisi',
            )
            ->loadClosingBalanceTotal('month', $date->year, $date->month)
            ->get();

        $tempVal = $tangible->sum('closing_balance_total');

        $currentAssets['tangible'] = $tempVal;
        $assets['fixed_assets'] += (float) $tempVal;

        $intangible = FinanceAccount::whereIn('ak_kelompok', function($q) {
                $q->select('hd_id')->from('dk_hierarki_dua')
                    ->where('hd_id', 10);
            })
            ->whereHas('balanceAccounts', function ($q) use ($date) {
                $q->filterYear($date->year)
                    ->filterMonth($date->month);
            })
            ->select(
                'ak_id',
                'ak_posisi',
            )
            ->loadClosingBalanceTotal('month', $date->year, $date->month)
            ->get();

        $tempDebitTotal = $intangible->where('ak_posisi', 'D')->sum('closing_balance_total');
        $tempCreditTotal = $intangible->where('ak_posisi', 'K')->sum('closing_balance_total') * -1;
        $tempVal = ($tempDebitTotal + $tempCreditTotal);

        $currentAssets['intangible'] = $tempVal;
        $assets['fixed_assets'] += (float) $tempVal;

        $total['assets'] = ($assets['fixed_assets'] + $assets['current_assets']);

        return [
            'status' => 'success',
            'periods' => [
                'date' => $date->format('Y'),
            ],
            'total' => $total,
            'expenses' => $expenses,
            'assets' => $assets,
            'current_assets' => $currentAssets,
            'fixed_assets' => $fixedAssets
        ];
    }
}