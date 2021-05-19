<?php

namespace ArsoftModules\Keuangan\Helpers;

use ArsoftModules\Keuangan\Models\BalanceAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BalanceHelper {
    /**
     * @param array $journalDetails list journal-data
     * @param string $date transaction date, format: Y-m-d
     * @param string $journalType journal type, exp: MK/TK/TM ( Mutasi Kas, Transaksi Kas, Transaksi Memorial )
     */
    public function balanceUsingJournal($journalDetails, $date, $journalType)
    {
        $date = Carbon::parse($date)->startOfMonth();

        foreach ($journalDetails as $key => $detail) {
            $balanceAccount = BalanceAccount::where('as_akun', $detail['jrdt_akun'])
                ->whereDate('as_periode', $date)
                ->with('financeAccount')
                ->first();
            
            if ($balanceAccount) {
                $debit = $credit = $calc = 0;

                if ($detail['jrdt_dk'] === 'D') {
                    $debit = $calc = $detail['jrdt_value'];
                } else {
                    $credit = $calc = $detail['jrdt_value'];
                }

                if ($balanceAccount->financeAccount->ak_posisi !== $detail['jrdt_dk']) {
                    $calc *= -1;
                }

                switch ($journalType) {
                    case 'MK':
                        $balanceAccount->as_mut_kas_debet = $balanceAccount->as_mut_kas_debet + $debit;
                        $balanceAccount->as_mut_kas_kredit = $balanceAccount->as_mut_kas_kredit + $credit;
                        $balanceAccount->as_saldo_akhir = $balanceAccount->as_saldo_akhir + $calc;
                        break;

                    case 'TK':
                        $balanceAccount->as_trans_kas_debet = $balanceAccount->as_trans_kas_debet + $debit;
                        $balanceAccount->as_trans_kas_kredit = $balanceAccount->as_trans_kas_kredit + $credit;
                        $balanceAccount->as_saldo_akhir = $balanceAccount->as_saldo_akhir + $calc;
                        break;

                    case 'TM':
                        $balanceAccount->as_trans_memorial_debet = $balanceAccount->as_trans_memorial_debet + $debit;
                        $balanceAccount->as_trans_memorial_kredit = $balanceAccount->as_trans_memorial_kredit + $credit;
                        $balanceAccount->as_saldo_akhir = $balanceAccount->as_saldo_akhir + $calc;
                        break;
                    
                    default :
                        break;
                }
                $balanceAccount->save();

                $this->updateFutureBalanceAccount($detail['jrdt_akun'], $date, $calc);
            }
        }

        return [
            'status' => 'success'
        ];
    }

    /**
     * @param array $journalDetails list journal-data
     * @param string $date transaction date, format: Y-m-d
     * @param string $journalType journal type, exp: MK/TK/TM ( Mutasi Kas, Transaksi Kas, Transaksi Memorial )
     */
    public function decrease(
        array $journalDetails, 
        string $date, 
        string $journalType
    )
    {
        $date = Carbon::parse($date)->startOfMonth();

        foreach ($journalDetails as $key => $detail) {
            $balanceAccount = BalanceAccount::where('as_akun', $detail['jrdt_akun'])
                ->whereDate('as_periode', $date)
                ->with('financeAccount')
                ->first();

            if ($balanceAccount) {
                $debit = $credit = $calc = 0;

                if ($detail['jrdt_dk'] === 'D') {
                    $debit = $calc = $detail['jrdt_value'];
                } else {
                    $credit = $calc = $detail['jrdt_value'];
                }

                if ($balanceAccount->financeAccount->ak_posisi !== $detail['jrdt_dk']) {
                    $calc *= -1;
                }

                switch ($journalType) {
                    case 'MK':
                        $balanceAccount->as_mut_kas_debet = $balanceAccount->as_mut_kas_debet - $debit;
                        $balanceAccount->as_mut_kas_kredit = $balanceAccount->as_mut_kas_kredit - $credit;
                        $balanceAccount->as_saldo_akhir = $balanceAccount->as_saldo_akhir + $calc;
                        break;

                    case 'TK':
                        $balanceAccount->as_trans_kas_debet = $balanceAccount->as_trans_kas_debet - $debit;
                        $balanceAccount->as_trans_kas_kredit = $balanceAccount->as_trans_kas_kredit - $credit;
                        $balanceAccount->as_saldo_akhir = $balanceAccount->as_saldo_akhir + $calc;
                        break;

                    case 'TM':
                        $balanceAccount->as_trans_memorial_debet = $balanceAccount->as_trans_memorial_debet - $debit;
                        $balanceAccount->as_trans_memorial_kredit = $balanceAccount->as_trans_memorial_kredit - $credit;
                        $balanceAccount->as_saldo_akhir = $balanceAccount->as_saldo_akhir + $calc;
                        break;

                    default:
                        break;
                }
                $balanceAccount->save();

                $this->updateFutureBalanceAccount($detail['jrdt_akun'], $date, $calc);
            }
        }

        return [
            'status' => 'success'
        ];
    }

    private function updateFutureBalanceAccount(
        $accountId, 
        $date,
        int $value
    )
    {
        BalanceAccount::where('as_akun', $accountId)
            ->whereDate('as_periode', '>', $date)
            ->update([
                'as_saldo_awal' => DB::raw('as_saldo_awal + ' . $value),
                'as_saldo_akhir' => DB::raw('as_saldo_akhir + ' . $value),
            ]);
    }
}