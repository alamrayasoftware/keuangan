<?php

namespace ArsoftModules\Keuangan\Controllers\Reports;

use ArsoftModules\Keuangan\Controllers\Controller;
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
        string $type = 'general'
    )
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        $data = '';
        $debitTotal = 0;
        $creditTotal = 0;
        
        return [
            'status' => 'success',
            'periods' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'type' => $type,
            'data' => $data,
            'total_debit' => $debitTotal,
            'total_credit' => $creditTotal
        ];
        
    }
}
