<?php

namespace ArsoftModules\Keuangan\Controllers\Reports;

use ArsoftModules\Keuangan\Controllers\Controller;
use ArsoftModules\Keuangan\Models\Journal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class JournalController extends Controller {
    /**
     * @param string $position position-id
     * @param string $startDate date_format: Y-m-d
     * @param string $endDate date_format: Y-m-d
     * @param string $type option: 'general', 'cash', 'memorial'
     */
    public function data(
        string $position,
        string $startDate,
        string $endDate,
        string $type
    )
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        $data = '';

        $data = Journal::whereDate('jr_tanggal_trans', '>=', $startDate)
            ->whereDate('jr_tanggal_trans', '<=', $endDate)
            ->select(
                'jr_id',
                'jr_tanggal_trans',
                DB::raw('DATE_FORMAT(jr_tanggal_trans, "%d %b %Y") as formatted_trans_date'),
                'jr_nota_ref',
                'jr_keterangan'
            )
            ->with(['details' => function ($q) {
                $q->select(
                    'jrdt_keterangan',
                    'jrdt_jurnal',
                    'jrdt_akun',
                    'jrdt_value',
                    'jrdt_dk'
                )
                ->with(['financeAccount' => function ($q) {
                    $q->select(
                        'ak_id',
                        'ak_nama',
                        'ak_nomor'
                    );
                }])
                ->orderBy('jrdt_dk', 'asc');
            }])
            ->loadDebitTotal()
            ->loadCreditTotal()
            ->groupBy('jr_id')
            ->orderBy('jr_tanggal_trans', 'asc')
            ->orderBy('jr_nota_ref', 'asc')
            ->get();

        $debitTotal = $data->sum('debit_total');
        $creditTotal = $data->sum('credit_total');

        return [
            'status' => 'success',
            'periods' => [
                'start' => $startDate->format('d M Y'),
                'end' => $endDate->format('d M Y')
            ],
            'type' => $type,
            'data' => $data,
            'debit_final' => $debitTotal,
            'credit_final' => $creditTotal
        ];
        
    }
}
