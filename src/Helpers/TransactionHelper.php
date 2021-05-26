<?php

namespace ArsoftModules\Keuangan\Helpers;

use ArsoftModules\Keuangan\Models\FinancePeriods;
use ArsoftModules\Keuangan\Models\Transaction;
use ArsoftModules\Keuangan\Models\TransactionDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionHelper {

    /**
     * @param string $date transaction date, format: Y-m-d
     * @param string $position position-id
     * @param string $note transaction note
     * @param array-string $cashAccounts list of used cash accounts
     * @param array-int $nominals list of transcation value each account
     * @param array-string $detailTypes list of type each account ( opt: D/K )
     * @param array-string $detailNotes list of notes
     * @param array-string $cashflows list of cashflows status
     */
    public function storeCash(
        string $date,
        string $position,
        string $note,
        array $cashAccounts,
        array $nominals,
        array $detailTypes,
        array $detailNotes,
        array $cashflows
    )
    {
        $date = Carbon::parse($date);

        $periodsCheck = FinancePeriods::filterYear($date->year)
            ->filterMonth($date->month)
            ->where('pk_comp', $position)
            ->exists();

        if (!$periodsCheck) {
            return [
                'status' => 'error',
                'message' => 'Finance period ( ' . $date->year . '/' . $date->month . ' ) not found !',
            ];
        }

        $transactionType = 'TK';
        $transactionNumber = $this->generateNumber($date, $transactionType);

        $transaction = new Transaction();
        $transaction->tr_type = $transactionType;
        $transaction->tr_comp = $position;
        $transaction->tr_nomor = $transactionNumber;
        $transaction->tr_tanggal_trans = $date;
        $transaction->tr_keterangan = $note;
        $transaction->save();

        $transactionDetails = [];
        $journalDetails = [];

        for ($i=0; $i < count($cashAccounts); $i++) { 
            array_push($transactionDetails, [
                'trdt_transaksi' => $transaction->tr_id,
                'trdt_nomor' => ($i + 1),
                'trdt_akun' => $cashAccounts[$i],
                'trdt_value' => $nominals[$i],
                'trdt_dk' => $detailTypes[$i],
                'trdt_keterangan' => $detailNotes[$i],
                'trdt_cashflow' => $cashflows[$i],
            ]);
            array_push($journalDetails, 
            [
                'jrdt_nomor' => 1,
                'jrdt_akun' => $cashAccounts[$i],
                'jrdt_value' => $nominals[$i],
                'jrdt_dk' => $detailTypes[$i],
                'jrdt_keterangan' => $detailNotes[$i],
                'jrdt_cashflow' => $cashflows[$i],
            ]);
        }

        TransactionDetail::insert($transactionDetails);

        $journal = JournalHelper::store(
            $journalDetails, 
            $date->toDateString(),
            $transactionNumber,
            $note,
            $transactionType,
            $position,
            'Y'
        );

        if ($journal['status'] !== 'success') {
            return [
                'status' => 'error',
                'message' => 'Journal record error !',
            ];
        }

        return [
            'status' => 'success',
        ];
    }

    /**
     * @param string $position position-id
     * @param int $year year
     */
    public function showAllCash(
        string $position,
        int $year = null,
        int $month = null
    )
    {
        $allData = Transaction::where('tr_comp', $position)
            ->with(['details' => function ($q) {
                $q->with('financeAccount');
            }])
            ->filterType('TK');
        
        ($year) ? $allData = $allData->filterYear($year) : null;
        ($month) ? $allData = $allData->filterMonth($month) : null;

        $allData = $allData->get();

        return [
            'status' => 'success',
            'data' => $allData,
        ];
    }

    public function storeNonCash()
    {
    }

    public function storeCashMutation()
    {
    }

    /**
     * @param string $date transaction date, format: Y-m-d
     * @param string $transactionType transaction type, opt: TK/TM/MK
     */
    private function generateNumber(
        Carbon $date,
        string $transactionType
    )
    {
        $lastNumber = Transaction::filterYear($date->year)
            ->filterMonth($date->month)
            ->filterType($transactionType)
            ->select(DB::raw('SUBSTRING(tr_nomor, 15) AS number'))
            ->latest()
            ->first();
        
        $number = ($lastNumber) ? ($lastNumber->number + 1) : 1;
        $generatedNumber = $transactionType . '-' . $date->format('Y') . '/' . $date->format('m') . '/' . $date->format('d') . '/' . $number;
        return $generatedNumber;
    }
}
