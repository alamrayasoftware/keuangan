<?php

namespace ArsoftModules\Keuangan\Controllers\Analisa;

use ArsoftModules\Keuangan\Controllers\Controller;
use DateInterval;
use DatePeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CashflowController extends Controller
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

        if ($type === 'month') {
            if ($startDate->year !== $endDate->year) {
                return [
                    'status' => 'error',
                    'message' => 'Tidak diperkenankan untuk tahun yang berbeda !'
                ];
            }

            $dateInterval = new DateInterval('P1M');
            $period = new DatePeriod($startDate, $dateInterval, $endDate->addMonth());

            $listPeriode = [];
            foreach ($period as $key => $date) {
                array_push($listPeriode, $date->startOfMonth());
            }

            $periodDate    = [];
            $keterangan   = [];
            $data         = [
                'saldo' => [],
                'ocf_in' => [],
                'icf_in' => [],
                'fcf_in' => [],
                'total_cf_in' => [],
                'ocf_out' => [],
                'icf_out' => [],
                'fcf_out' => [],
                'total_cf_out' => []
            ];

            foreach ($listPeriode as $key => $val) {
                $total_cf_in  = 0;
                $total_cf_out = 0;

                // Saldo Awal
                $saldo = DB::table('dk_akun_saldo')
                    ->whereIn('as_akun', function ($q) use ($position) {
                        $q->select('ak_id')
                            ->from('dk_akun')
                            ->where('ak_comp', $position);
                    })
                    ->join('dk_akun', 'ak_id', 'as_akun')
                    ->where('ak_setara_kas', '=', '1')
                    ->where(function ($q) use ($val) {
                        $q->whereYear('as_periode', $val)
                            ->whereMonth('as_periode', $val);
                    })
                    ->select(
                        'as_periode', 
                        DB::raw('SUM(as_saldo_awal) as saldo_awal')
                    )
                    ->groupBy('as_periode')
                    ->get();

                // OCF In
                $jr_ocf_in = DB::table('dk_jurnal')
                    ->whereIn('jr_id', function ($q) {
                        $q->select('jrdt_jurnal')->from('dk_jurnal_detail')
                            ->whereIn('jrdt_akun', function ($r) {
                                $r->select('ak_id')->from('dk_akun')->where('ak_setara_kas', '=', '1');
                            })
                            ->where('jrdt_cashflow', '=', 1)
                            ->where('jrdt_dk', '=', 'D');
                    })
                    ->where('jr_comp', $position)
                    ->where(function ($q) use ($val) {
                        $q->whereYear('jr_tanggal_trans', $val)
                            ->whereMonth('jr_tanggal_trans', $val);
                    })
                    ->get();

                $ocf_in = 0;
                foreach ($jr_ocf_in as $keys => $value) {
                    $detail = DB::table('dk_jurnal_detail')
                        ->whereIn('jrdt_akun', function ($r) {
                            $r->select('ak_id')->from('dk_akun')->where('ak_setara_kas', '=', '1');
                        })
                        ->where('jrdt_jurnal', $value->jr_id)
                        ->where('jrdt_cashflow', '=', 1)
                        ->where('jrdt_dk', '=', 'D')
                        ->get();

                    foreach ($detail as $index => $values) {
                        $ocf_in += (int)$values->jrdt_value;
                    }
                }

                // OCF Out
                $jr_ocf_out = DB::table('dk_jurnal')
                    ->whereIn('jr_id', function ($q) {
                        $q->select('jrdt_jurnal')->from('dk_jurnal_detail')
                            ->whereIn('jrdt_akun', function ($r) {
                                $r->select('ak_id')->from('dk_akun')->where('ak_setara_kas', '=', '1');
                        })
                        ->where('jrdt_cashflow', '=', 1)
                        ->where('jrdt_dk', '=', 'K');
                    })
                    ->where('jr_comp', $position)
                    ->where(function ($q) use ($val) {
                        $q->whereYear('jr_tanggal_trans', $val)
                            ->whereMonth('jr_tanggal_trans', $val);
                    })
                    ->get();

                $ocf_out = 0;
                foreach ($jr_ocf_out as $keys => $value) {
                    $detail = DB::table('dk_jurnal_detail')
                        ->where('jrdt_jurnal', $value->jr_id)
                        ->where('jrdt_cashflow', '=', 1)
                        ->where('jrdt_dk', '=', 'K')
                        ->get();

                    foreach ($detail as $index => $values) {
                        $ocf_out += (int)$values->jrdt_value;
                    }
                }

                // ICF In
                $jr_icf_in = DB::table('dk_jurnal')
                    ->whereIn('jr_id', function ($q) {
                        $q->select('jrdt_jurnal')->from('dk_jurnal_detail')
                            ->whereIn('jrdt_akun', function ($r) {
                                $r->select('ak_id')->from('dk_akun')->where('ak_setara_kas', '=', '1');
                            })
                            ->where('jrdt_cashflow', '=', 2)
                            ->where('jrdt_dk', '=', 'D');
                    })
                    ->where('jr_comp', $position)
                    ->where(function ($q) use ($val) {
                        $q->whereYear('jr_tanggal_trans', $val)
                            ->whereMonth('jr_tanggal_trans', $val);
                    })
                    ->get();

                $icf_in = 0;
                foreach ($jr_icf_in as $keys => $value) {
                    $detail = DB::table('dk_jurnal_detail')
                        ->whereIn('jrdt_akun', function ($r) {
                            $r->select('ak_id')->from('dk_akun')->where('ak_setara_kas', '=', '1');
                        })
                        ->where('jrdt_jurnal', $value->jr_id)
                        ->where('jrdt_cashflow', '=', 2)
                        ->where('jrdt_dk', '=', 'D')
                        ->get();

                    foreach ($detail as $index => $values) {
                        $icf_in += (int)$values->jrdt_value;
                    }
                }

                // ICF Out
                $jr_icf_out = DB::table('dk_jurnal')
                    ->whereIn('jr_id', function ($q) {
                        $q->select('jrdt_jurnal')->from('dk_jurnal_detail')
                            ->whereIn('jrdt_akun', function ($r) {
                                $r->select('ak_id')->from('dk_akun')->where('ak_setara_kas', '=', '1');
                            })
                            ->where('jrdt_cashflow', '=', 2)
                            ->where('jrdt_dk', '=', 'K');
                    })
                    ->where('jr_comp', $position)
                    ->where(function ($q) use ($val) {
                        $q->whereYear('jr_tanggal_trans', $val)
                            ->whereMonth('jr_tanggal_trans', $val);
                    })
                    ->get();

                $icf_out = 0;
                foreach ($jr_icf_out as $keys => $value) {
                    $detail = DB::table('dk_jurnal_detail')
                        ->where('jrdt_jurnal', $value->jr_id)
                        ->where('jrdt_cashflow', '=', 2)
                        ->where('jrdt_dk', '=', 'K')
                        ->get();

                    foreach ($detail as $index => $values) {
                        $icf_out += (int)$values->jrdt_value;
                    }
                }

                // FCF In
                $jr_fcf_in = DB::table('dk_jurnal')
                    ->whereIn('jr_id', function ($q) {
                        $q->select('jrdt_jurnal')->from('dk_jurnal_detail')
                            ->whereIn('jrdt_akun', function ($r) {
                                $r->select('ak_id')->from('dk_akun')->where('ak_setara_kas', '=', '1');
                            })
                            ->where('jrdt_cashflow', '=', 3)
                            ->where('jrdt_dk', '=', 'D');
                    })
                    ->where('jr_comp', $position)
                    ->where(function ($q) use ($val) {
                        $q->whereYear('jr_tanggal_trans', $val)
                            ->whereMonth('jr_tanggal_trans', $val);
                    })
                    ->get();

                $fcf_in = 0;
                foreach ($jr_fcf_in as $keys => $value) {
                    $detail = DB::table('dk_jurnal_detail')
                        ->whereIn('jrdt_akun', function ($r) {
                            $r->select('ak_id')->from('dk_akun')->where('ak_setara_kas', '=', '1');
                        })
                        ->where('jrdt_jurnal', $value->jr_id)
                        ->where('jrdt_cashflow', '=', 3)
                        ->where('jrdt_dk', '=', 'D')
                        ->get();

                    foreach ($detail as $index => $values) {
                        $fcf_in += (int)$values->jrdt_value;
                    }
                }

                // FCF Out
                $jr_fcf_out = DB::table('dk_jurnal')
                    ->whereIn('jr_id', function ($q) {
                        $q->select('jrdt_jurnal')->from('dk_jurnal_detail')
                            ->whereIn('jrdt_akun', function ($r) {
                                $r->select('ak_id')->from('dk_akun')->where('ak_setara_kas', '=', '1');
                            })
                            ->where('jrdt_cashflow', '=', 3)
                            ->where('jrdt_dk', '=', 'K');
                    })
                    ->where('jr_comp', $position)
                    ->where(function ($q) use ($val) {
                        $q->whereYear('jr_tanggal_trans', $val)
                            ->whereMonth('jr_tanggal_trans', $val);
                    })
                    ->get();

                $fcf_out = 0;
                foreach ($jr_fcf_out as $keys => $value) {
                    $detail = DB::table('dk_jurnal_detail')
                        ->where('jrdt_jurnal', $value->jr_id)
                        ->where('jrdt_cashflow', '=', 3)
                        ->where('jrdt_dk', '=', 'K')
                        ->get();

                    foreach ($detail as $index => $values) {
                        $fcf_out += (int)$values->jrdt_value;
                    }
                }

                // Total Cashflow
                $total_cf_in  = $ocf_in + $icf_in + $fcf_in;
                $total_cf_out = $ocf_out + $icf_out + $fcf_out;

                // Keterangan
                if ($total_cf_in >= $total_cf_out) {
                    array_push($keterangan, 'Cashflow (+)');
                } else {
                    array_push($keterangan, 'Cashflow (-)');
                }

                array_push($data['saldo'], $saldo);
                array_push($data['total_cf_out'], $total_cf_out);
                array_push($data['total_cf_in'], $total_cf_in);
                array_push($data['ocf_in'], $ocf_in);
                array_push($data['ocf_out'], $ocf_out);
                array_push($data['icf_in'], $icf_in);
                array_push($data['icf_out'], $icf_out);
                array_push($data['fcf_in'], $fcf_in);
                array_push($data['fcf_out'], $fcf_out);

                array_push($periodDate, $val->format('M Y'));
            }
        } elseif ($type === 'year') {
            return [
                'status' => 'error',
                'message' => 'Saat ini belum mendukung laporan jenis tahunan !'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Jenis laporan tidak didukung, saat ini hanya mendukung jenis bulanan !'
            ];
        }

        return [
            'status' => 'success',
            'periods' => $periodDate,
            'notes' => $keterangan,
            'cashflow' => $data
        ];
    }
}
