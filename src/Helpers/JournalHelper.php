<?php

namespace ArsoftModules\Keuangan\Helpers;

use ArsoftModules\Keuangan\Models\FinanceAccount;
use ArsoftModules\Keuangan\Models\FinancePeriods;
use ArsoftModules\Keuangan\Models\Journal;
use ArsoftModules\Keuangan\Models\JournalDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class JournalHelper {
    /**
     * @param array $journalDetails list journal-data
     * @param string $date transaction date, format: Y-m-d
     * @param string $transactionNota transaction number/nota
     * @param string $note transaction note
     * @param string $position position-id
     * @param string $isMemorial option: 'Y', 'N'
     */
    public static function store(
        array $journalDetails,
        string $date,
        string $transactionNota,
        string $note,
        string $type,
        string $position,
        string $isMemorial = 'N'
    )
    {
        $date = Carbon::parse($date);
        $periodsCheck = FinancePeriods::filterYear($date->year)
            ->filterMonth($date->month)
            ->where('pk_comp', $position)
            ->exists();

        $isProcessed = 0;
        if (!$periodsCheck) {
            $isProcessed = 1;
        }

        $newJournal = new Journal([
            'jr_type' => $type,
            'jr_comp' => $position,
            'jr_ref' => 'Transaksi',
            'jr_nota_ref' => $transactionNota,
            'jr_tanggal_trans' => $date,
            'jr_keterangan' => $note,
            'jr_memorial' => $isMemorial,
            'jr_isproses' => $isProcessed
        ]);
        $newJournal->save();

        $tempJurnalDetails = [];
        foreach ($journalDetails as $key => $detail) {
            $getFinanceAccount = FinanceAccount::where('ak_id', $detail['jrdt_akun'])
                ->first();
            
            if (!$getFinanceAccount) {
                return [
                    'status' => 'error',
                    'messages' => 'Finance Account ( ' . $detail['jrdt_akun'] . ' ) not found !'
                ];
            }

            $tempCashflow = (isset($detail['jrdt_cashflow']) && $getFinanceAccount->ak_setara_kas == 1)
                ? $detail['jrdt_cashflow']
                : null;
            array_push($tempJurnalDetails, [
                'jrdt_jurnal' => $newJournal->jr_id,
                'jrdt_nomor' => $key+1,
                'jrdt_akun' => $detail['jrdt_akun'],
                'jrdt_value' => $detail['jrdt_value'],
                'jrdt_dk' => $detail['jrdt_dk'],
                'jrdt_keterangan' => $detail['jrdt_keterangan'],
                'jrdt_cashflow' => $tempCashflow,
            ]);
        }

        $newJournalDetail = new JournalDetail([
            $tempJurnalDetails
        ]);
        $newJournalDetail->save();

dd($newJournal);
        // return $finalTotal;
    }
}
