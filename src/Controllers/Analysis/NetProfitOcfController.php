<?php

namespace ArsoftModules\Keuangan\Controllers\Analysis;

use ArsoftModules\Keuangan\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NetProfitOcfController extends Controller
{
    /**
     * @param string $startDate date_format: Y-m
     * @param string $endDate date_format: Y-m
     * @param string $type opt : 'month', 'year'
     */
    public function data(
        string $startDate,
        string $endDate,
        string $type = 'month'
    )
    {
        $startDate = Carbon::createFromFormat('Y-m', $startDate);
        $endDate = Carbon::createFromFormat('Y-m', $endDate);

        $data = [
            'period' => [],
            'ocf' => [],
            'net_profit' => []
        ];

        while ($startDate <= $endDate) {
            $ocf = DB::table('dk_jurnal_detail')
                ->join('dk_jurnal', 'jr_id', 'jrdt_jurnal')
                ->whereIn('jrdt_cashflow', function ($query) {
                    $query->select('ac_id')->from('dk_akun_cashflow')->where('ac_type', 'OCF');
                });

            $netProfit = DB::table('dk_akun')
                ->leftJoin('dk_akun_saldo', 'dk_akun_saldo.as_akun', 'dk_akun.ak_id')
                ->where(function ($q) {
                    $q->where(DB::raw('substring(ak_nomor, 1, 1)'), '4')
                        ->orWhere(DB::raw('substring(ak_nomor, 1, 1)'), '8');
                })
                ->where('ak_isactive', '1');

            $expenses = DB::table('dk_akun')
                ->leftJoin('dk_akun_saldo', 'dk_akun_saldo.as_akun', 'dk_akun.ak_id')
                ->where(function ($q) {
                    $q->where(DB::raw('substring(ak_nomor, 1, 1)'), '5')
                        ->orWhere(DB::raw('substring(ak_nomor, 1, 1)'), '6')
                        ->orWhere(DB::raw('substring(ak_nomor, 1, 1)'), '7')
                        ->orWhere(DB::raw('substring(ak_nomor, 1, 1)'), '9');
                })
                ->where('ak_isactive', '1');

            if ($type === 'month') {
                $ocf = $ocf->where(function ($q) use ($startDate) {
                    $q->whereYear('jr_tanggal_trans', $startDate)
                    ->whereMonth('jr_tanggal_trans', $startDate);
                });
                $netProfit = $netProfit->where(function ($q) use ($startDate) {
                    $q->whereYear('as_periode', $startDate)
                    ->whereMonth('as_periode', $startDate);
                });
                $expenses = $expenses->where(function ($q) use ($startDate) {
                    $q->whereYear('as_periode', $startDate)
                    ->whereMonth('as_periode', $startDate);
                });

            } elseif ($type === 'year') {
                $ocf = $ocf->where(function ($q) use ($startDate) {
                    $q->whereYear('jr_tanggal_trans', $startDate);
                });
                $netProfit = $netProfit->where(function ($q) use ($startDate) {
                    $q->whereYear('as_periode', $startDate);
                });
                $expenses = $expenses->where(function ($q) use ($startDate) {
                    $q->whereYear('as_periode', $startDate);
                });
            }
            
            $ocf = $ocf->select(
                    DB::raw("coalesce(sum(if(jrdt_dk = 'K', (jrdt_value * -1), jrdt_value)), 0) as value")
                )
                ->first();

            $netProfit = $netProfit->select(
                    DB::raw('coalesce(sum(as_saldo_akhir - as_saldo_awal), 0) as saldo_akhir')
                )
                ->first();
            
            $expenses = $expenses->select(
                    DB::raw('coalesce(sum(as_saldo_akhir - as_saldo_awal), 0) as saldo_akhir')
                )
                ->first();

            array_push($data['ocf'], $ocf->value / 1000);
            array_push($data['net_profit'], (($netProfit->saldo_akhir - $expenses->saldo_akhir) / 1000));
        
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
            'ocf' => $data['ocf'],
            'net_profit' => $data['net_profit']
        ];
    }
}