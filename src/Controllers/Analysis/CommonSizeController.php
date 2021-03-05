<?php

namespace ArsoftModules\Keuangan\Controllers\Analysis;

use ArsoftModules\Keuangan\Controllers\Controller;
use DateInterval;
use DatePeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CommonSizeController extends Controller
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

        $akun_kas = DB::table('dk_akun')
            ->whereIn('ak_kelompok', function($r) {
                $r->select('hd_id')
                    ->from('dk_hierarki_dua')
                    ->where('hd_id', '=', 13);
            })
            ->where('ak_comp', $position)
            ->where('ak_setara_kas', '=', '1')
            ->select('ak_id', 'ak_nama')
            ->get();

        $akun_piutang = DB::table('dk_akun')
            ->whereIn('ak_kelompok', function ($r) {
                $r->select('hd_id')
                    ->from('dk_hierarki_dua')
                    ->where('hd_id', '=', 14);
            })
            ->where('ak_comp', $position)
            ->select('ak_id', 'ak_nama')
            ->get();

        $akun_inventory = DB::table('dk_akun')
            ->whereIn('ak_kelompok', function ($r) {
                $r->select('hd_id')
                    ->from('dk_hierarki_dua')
                    ->where('hd_id', '=', 15);
            })
            ->where('ak_comp', $position)
            ->select('ak_id', 'ak_nama')
            ->get();

        $akun_pendapatan = DB::table('dk_akun')
            ->whereIn('ak_kelompok', function ($r) {
                $r->select('hd_id')
                    ->from('dk_hierarki_dua')
                    ->whereIn('hd_level_1', function ($s) {
                        $s->select('hs_id')->from('dk_hierarki_satu')->where('hs_id', '=', 4);
                    });
            })
            ->where('ak_comp', $position)
            ->select('ak_id', 'ak_nama')
            ->get();


        $akun_hpp = DB::table('dk_akun')
            ->whereIn('ak_kelompok', function ($r) {
                $r->select('hd_id')
                    ->from('dk_hierarki_dua')
                    ->whereIn('hd_level_1', function ($s) {
                        $s->select('hs_id')->from('dk_hierarki_satu')->where('hs_id', '=', 5);
                    });
            })
            ->where('ak_comp', $position)
            ->select('ak_id', 'ak_nama')
            ->get();


        $akun_beban = DB::table('dk_akun')
            ->whereIn('ak_kelompok', function ($r) {
                $r->select('hd_id')
                    ->from('dk_hierarki_dua')
                    ->whereIn('hd_level_1', function ($s) {
                        $s->select('hs_id')->from('dk_hierarki_satu')->where('hs_id', '=', 6)->orWhere('hs_id', '=', 7);
                    });
            })
            ->where('ak_comp', $position)
            ->select('ak_id', 'ak_nama')
            ->get();

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
        $neraca = [
            'kas' => [],
            'piutang' => [],
            'inventory' => [],
            'total_aset' => []
        ];

        $labarugi = [
            'pendapatan' => [],
            'tot_pdpt' => [],
            'hpp' => [],
            'beban' => []
        ];

        foreach ($listPeriod as $key => $val) {
            $total_aset = 0;
            $total = 0;

            $kas = DB::table('dk_akun_saldo')
                ->whereIn('as_akun', function ($q) use ($position) {
                    $q->select('ak_id')
                        ->from('dk_akun')
                        ->where('ak_comp', $position)
                        ->where('ak_setara_kas', '=', '1')
                        ->whereIn('ak_kelompok', function ($r) {
                            $r->select('hd_id')->from('dk_hierarki_dua')->where('hd_id', '=', 13);
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
                    DB::raw('SUM(as_saldo_akhir) as as_saldo_akhir')
                )
                ->groupBy('ak_id')
                ->get();

            foreach ($kas as $key => $value) {
                $total_aset += (int)$value->as_saldo_akhir;
            }

            $piutang = DB::table('dk_akun_saldo')
                ->whereIn('as_akun', function ($q) use ($position) {
                    $q->select('ak_id')
                        ->from('dk_akun')
                        ->where('ak_comp', $position)
                        ->whereIn('ak_kelompok', function ($r) {
                            $r->select('hd_id')->from('dk_hierarki_dua')->where('hd_id', '=', 14);
                        });
                })
                ->join('dk_akun', 'ak_id', 'as_akun')
                ->join('dk_hierarki_satu', 'hs_id', DB::raw('MID(ak_nomor, 1, 1)'))
                ->where(function ($q) use ($type, $val) {
                    $q->whereYear('as_periode', $val);
                    ($type === 'month')
                        ? $q->whereMonth('as_periode', $val)
                        : '';
                })
                ->select(
                    'as_id', 
                    'ak_id', 
                    'hs_id', 
                    'ak_nama', 
                    DB::raw('SUM(as_saldo_akhir) as as_saldo_akhir')
                )
                ->groupBy('ak_id')
                ->get();

            foreach ($piutang as $key => $value) {
                $total_aset += (int)$value->as_saldo_akhir;
            }

            $inventory = DB::table('dk_akun_saldo')
                ->whereIn('as_akun', function ($q) use ($position) {
                    $q->select('ak_id')
                        ->from('dk_akun')
                        ->where('ak_comp', $position)
                        ->whereIn('ak_kelompok', function ($r) {
                            $r->select('hd_id')->from('dk_hierarki_dua')->where('hd_id', '=', 15);
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
                    DB::raw('SUM(as_saldo_akhir) as as_saldo_akhir')
                )
                ->groupBy('ak_id')
                ->get();

            foreach ($inventory as $key => $value) {
                $total_aset += (int)$value->as_saldo_akhir;
            }

            $pendapatan = DB::table('dk_akun_saldo')
                ->whereIn('as_akun', function ($q) use ($position) {
                    $q->select('ak_id')
                        ->from('dk_akun')
                        ->where('ak_comp', $position)
                        ->whereIn(DB::raw('MID(ak_nomor, 1, 1)'), function ($r) {
                            $r->select('hs_id')->from('dk_hierarki_satu')->where('hs_id', '=', 4);
                        });
                })
                ->join('dk_akun', 'ak_id', 'as_akun')
                ->join('dk_hierarki_satu', 'hs_id', DB::raw('MID(ak_nomor, 1, 1)'))
                ->where(function ($q) use ($type, $val) {
                    $q->whereYear('as_periode', $val);
                    ($type === 'month')
                        ? $q->whereMonth('as_periode', $val)
                        : '';
                })
                ->select(
                    'as_id', 
                    'ak_id', 
                    'hs_id', 
                    'ak_nama', 
                    DB::raw('SUM(as_saldo_akhir) as as_saldo_akhir')
                )
                ->groupBy('ak_id')
                ->get();

            foreach ($pendapatan as $key => $value) {
                $total += (int)$value->as_saldo_akhir;
            }

            $dataHpp = DB::table('dk_akun_saldo')
                ->whereIn('as_akun', function ($q) use ($position) {
                    $q->select('ak_id')
                        ->from('dk_akun')
                        ->where('ak_comp', $position)
                        ->whereIn(DB::raw('MID(ak_nomor, 1, 1)'), function ($r) {
                            $r->select('hs_id')->from('dk_hierarki_satu')->where('hs_id', '=', 5);
                        });
                })
                ->join('dk_akun', 'ak_id', 'as_akun')
                ->join('dk_hierarki_satu', 'hs_id', DB::raw('MID(ak_nomor, 1, 1)'))
                ->where(function ($q) use ($type, $val) {
                    $q->whereYear('as_periode', $val);
                    ($type === 'month')
                        ? $q->whereMonth('as_periode', $val)
                        : '';
                })
                ->select(
                    'as_id', 
                    'ak_id', 
                    'hs_id', 
                    'ak_nama', 
                    DB::raw('SUM(as_saldo_akhir) as as_saldo_akhir')
                )
                ->groupBy('ak_id')
                ->get();

            $dataBeban = DB::table('dk_akun_saldo')
                ->whereIn('as_akun', function ($q) use ($position) {
                    $q->select('ak_id')
                        ->from('dk_akun')
                        ->where('ak_comp', $position)
                        ->whereIn(DB::raw('MID(ak_nomor, 1, 1)'), function ($r) {
                            $r->select('hs_id')->from('dk_hierarki_satu')->where('hs_id', '=', 6)->orWhere('hs_id', '=', 7);
                        });
                })
                ->join('dk_akun', 'ak_id', 'as_akun')
                ->join('dk_hierarki_satu', 'hs_id', DB::raw('MID(ak_nomor, 1, 1)'))
                ->where(function ($q) use ($type, $val) {
                    $q->whereYear('as_periode', $val);
                    ($type === 'month')
                        ? $q->whereMonth('as_periode', $val)
                        : '';
                })
                ->select(
                    'as_id', 
                    'ak_id', 
                    'hs_id', 
                    'ak_nama', 
                    DB::raw('SUM(as_saldo_akhir) as as_saldo_akhir')
                )
                ->groupBy('ak_id')
                ->get();

            array_push($neraca['kas'], $kas);
            array_push($neraca['piutang'], $piutang);
            array_push($neraca['inventory'], $inventory);
            array_push($neraca['total_aset'], $total_aset);

            array_push($labarugi['pendapatan'], $pendapatan);
            array_push($labarugi['tot_pdpt'], $total);
            array_push($labarugi['hpp'], $dataHpp);
            array_push($labarugi['beban'], $dataBeban);

            $tempPeriodDate = ($type === 'month') ? $val->format('M Y') : $val->format('Y');
            array_push($periodDate, $tempPeriodDate);
        }

        $akun = [
            'kas' => $akun_kas,
            'piutang' => $akun_piutang,
            'inventory' => $akun_inventory,
            'pendapatan' => $akun_pendapatan,
            'hpp' => $akun_hpp,
            'beban' => $akun_beban
        ];

        return [
            'status' => 'success',
            'balance_sheet' => $neraca,
            'profit_and_loss' => $labarugi,
            'accounts' => $akun,
            'periods'=> $periodDate
        ];
    }
}