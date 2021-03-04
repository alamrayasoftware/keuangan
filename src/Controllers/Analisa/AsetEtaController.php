<?php

namespace ArsoftModules\Keuangan\Controllers\Analisa;

use ArsoftModules\Keuangan\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
            'periode'    => [],
            'aset'       => [],
            'ekuitas'    => []
        ];

        while ($startDate <= $endDate) {
            $aset = DB::table('dk_akun')
                ->leftJoin('dk_akun_saldo', 'dk_akun_saldo.as_akun', 'dk_akun.ak_id');

            $ekuitas = DB::table('dk_akun')
                ->where('ak_comp', $position)
                ->leftJoin('dk_akun_saldo', 'dk_akun_saldo.as_akun', 'dk_akun.ak_id');

            if ($type === 'month') {
                $aset = $aset->where(function ($q) use ($startDate) {
                    $q->whereYear('as_periode', $startDate)
                    ->whereMonth('as_periode', $startDate);
                });
                $ekuitas = $ekuitas->where(function ($q) use ($startDate) {
                    $q->whereYear('as_periode', $startDate)
                    ->whereMonth('as_periode', $startDate);
                });
            } elseif ($type === 'year') {
                $aset = $aset->where(function ($q) use ($startDate) {
                    $q->whereYear('as_periode', $startDate);
                });
                $ekuitas = $ekuitas->where(function ($q) use ($startDate) {
                    $q->whereYear('as_periode', $startDate);
                });
            }
            
            $aset = $aset->where('ak_kelompok', 16)
                ->where('ak_isactive', '1')
                ->select(
                    DB::raw('coalesce(sum(as_saldo_awal), 0) as saldo_awal'),
                    DB::raw('coalesce(sum(as_mut_kas_debet + as_trans_kas_debet + as_trans_memorial_debet), 0) as penambahan'),
                    DB::raw('coalesce(sum(as_mut_kas_kredit + as_trans_kas_kredit + as_trans_memorial_kredit), 0) as pengurangan'),
                    DB::raw('coalesce(sum(as_saldo_akhir), 0) as saldo_akhir')
                )
                ->first();

            $ekuitas = $ekuitas->where(DB::raw('substring(ak_nomor, 1, 1)'), '3')
                ->where('ak_isactive', '1')
                ->select(
                    DB::raw('coalesce(sum(as_saldo_awal), 0) as saldo_awal'),
                    DB::raw('coalesce(sum(as_mut_kas_kredit + as_trans_kas_kredit + as_trans_memorial_kredit), 0) as penambahan'),
                    DB::raw('coalesce(sum(as_mut_kas_debet + as_trans_kas_debet + as_trans_memorial_debet), 0) as pengurangan'),
                    DB::raw('coalesce(sum(as_saldo_akhir), 0) as saldo_akhir')
                )
                ->first();

            array_push($data['aset'], $aset->saldo_akhir / 1000);
            array_push($data['ekuitas'], ($ekuitas->saldo_akhir / 1000));
        
            if ($type === 'month') {
                array_push($data['periode'], $startDate->format('M y'));
                // increment time
                $startDate = $startDate->addMonth();
            } elseif ($type === 'year') {
                array_push($data['periode'], $startDate->format('Y'));
                // increment time
                $startDate = $startDate->addYear();
            }
        }

        return $data;
    }
}