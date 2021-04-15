<?php

namespace ArsoftModules\Keuangan\Controllers\Analysis;

use ArsoftModules\Keuangan\Controllers\Controller;
use DateInterval;
use DatePeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LiquidityRatioController extends Controller
{
    /**
     * @param string $position position id
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

        $listPeriod = [];
        if ($type === 'month') {
            if ($startDate->year !== $endDate->year) {
                return [
                    'status' => 'error',
                    'message' => 'Tidak diperkenankan untuk tahun yang berbeda !'
                ];
            }

            $dateInterval = new DateInterval('P1M');
            $period = new DatePeriod($startDate, $dateInterval, $endDate->addMonth());
            foreach ($period as $key => $date) {
                array_push($listPeriod, $date->startOfMonth());
            }
        } elseif ($type === 'year') {
            $dateInterval = new DateInterval('P1Y');
            $period = new DatePeriod($startDate, $dateInterval, $endDate);
            foreach ($period as $key => $date) {
                array_push($listPeriod, $date->startOfYear());
            }
        }

        $periodDate = [];
        $data = [
            'assets' => [],
            'liability' => [],
            'inventory' => [],
            'cash' => []
        ];

        foreach ($listPeriod as $key => $val) {
            // get assets values
            $totalAssets = DB::table('dk_akun_saldo')
                ->whereIn('as_akun', function ($q) use ($position) {
                    $q->select('ak_id')->from('dk_akun')
                    ->where('ak_comp', $position)
                    ->whereIn('ak_kelompok', function ($q) {
                        $q->select('hd_id')->from('dk_hierarki_dua')
                        ->whereIn('hd_level_1', function ($q) {
                            $q->select('hs_id')->from('dk_hierarki_satu')
                            ->where('hs_id', 1);
                        });
                    });
                })
                ->join('dk_akun', 'ak_id', 'as_akun')
                ->join('dk_hierarki_satu', 'hs_id', DB::raw('MID(ak_nomor, 1, 1)'))
                ->where(function ($q) use ($type, $val) {
                    $q->whereYear('as_periode', $val);
                    ($type === 'month')
                        ? $q->whereMonth('as_periode', $val)
                        : $q;
                })
                ->select(
                    'as_id', 
                    'ak_id', 
                    'hs_id', 
                    'ak_nama', 
                    'as_saldo_akhir'
                )
                ->sum('as_saldo_akhir');
            
            // get liability values
            $totalLiability = DB::table('dk_akun_saldo')
                ->whereIn('as_akun', function ($q) use ($position) {
                    $q->select('ak_id')->from('dk_akun')
                    ->where('ak_comp', $position)
                    ->whereIn('ak_kelompok', function ($q) {
                        $q->select('hd_id')->from('dk_hierarki_dua')
                        ->whereIn('hd_level_1', function ($q) {
                            $q->select('hs_id')->from('dk_hierarki_satu')
                            ->where('hs_id', 2);
                        });
                    });
                })
                ->join('dk_akun', 'ak_id', 'as_akun')
                ->join('dk_hierarki_satu', 'hs_id', DB::raw('MID(ak_nomor, 1, 1)'))
                ->where(function ($q) use ($type, $val) {
                    $q->whereYear('as_periode', $val);
                    ($type === 'month')
                        ? $q->whereMonth('as_periode', $val)
                        : $q;
                })
                ->select(
                    'as_id', 
                    'ak_id', 
                    'hs_id', 
                    'ak_nama', 
                    'as_saldo_akhir'
                )
                ->sum('as_saldo_akhir');
            
            // get inventory values
            $totalInventory = DB::table('dk_akun_saldo')
                ->whereIn('as_akun', function ($q) use ($position) {
                    $q->select('ak_id')->from('dk_akun')
                    ->where('ak_comp', $position)
                    ->whereIn('ak_kelompok', function ($r) {
                        $r->select('hd_id')->from('dk_hierarki_dua')
                        ->where('hd_id', 15);
                    });
                })
                ->join('dk_akun', 'ak_id', 'as_akun')
                ->join('dk_hierarki_satu', 'hs_id', DB::raw('MID(ak_nomor, 1, 1)'))
                ->where(function ($q) use ($type, $val) {
                    $q->whereYear('as_periode', $val);
                    ($type === 'month')
                        ? $q->whereMonth('as_periode', $val)
                        : $q;
                })
                ->select(
                    'as_id', 
                    'ak_id', 
                    'hs_id', 
                    'ak_nama', 
                    'as_saldo_akhir'
                )
                ->sum('as_saldo_akhir');
            
            // get cash values
            $totalCash = DB::table('dk_akun_saldo')
                ->whereIn('as_akun', function ($q) use ($position) {
                    $q->select('ak_id')->from('dk_akun')
                    ->where('ak_comp', $position)
                    ->where('ak_setara_kas', '1')
                    ->whereIn('ak_kelompok', function ($q) {
                        $q->select('hd_id')->from('dk_hierarki_dua')
                        ->where('hd_id', 13);
                    });
                })
                ->join('dk_akun', 'ak_id', 'as_akun')
                ->join('dk_hierarki_satu', 'hs_id', DB::raw('MID(ak_nomor, 1, 1)'))
                ->where(function ($q) use ($type, $val) {
                    $q->whereYear('as_periode', $val);
                    ($type === 'month')
                        ? $q->whereMonth('as_periode', $val)
                        : $q;
                })
                ->select(
                    'as_id', 
                    'ak_id', 
                    'hs_id', 
                    'ak_nama', 
                    'as_saldo_akhir'
                )
                ->sum('as_saldo_akhir');

            array_push($data['assets'], $totalAssets);
            array_push($data['liability'], $totalLiability);
            array_push($data['inventory'], $totalInventory);
            array_push($data['cash'], $totalCash);

            $tempPeriodDate = ($type === 'month') ? $val->format('M Y') : $val->format('Y');
            array_push($periodDate, $tempPeriodDate);
        }

        return [
            'status' => 'success',
            'periods' => $periodDate,
            'data' => $data,
        ];
    }
}