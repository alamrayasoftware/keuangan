<?php

namespace ArsoftModules\Keuangan;

use ArsoftModules\Keuangan\Controllers\Analisa\AsetEtaController;
use Exception;

class Keuangan {
    private $result;

    /**
     * @param string $position
     * @param string $startDate date_format: Y-m
     * @param string $endDate date_format: Y-m
     * @param string $type opt : 'month', 'year'
     */
    public function reportAsetEta(
        string $position,
        string $startDate,
        string $endDate,
        string $type = 'month'
    )
    {
        $asetEta = new AsetEtaController();

        $report = $asetEta->data(
            $position,
            $startDate,
            $endDate,
            $type
        );

        if ($report['status'] !== 'success') {
            return $this->errorData($report['message']);
        }

        $this->result = $report['data'];

        return $this->successData();
    }

    private function successData()
    {
        $tempData = [
            'status' => 'success',
            'data' => $this->result
        ];
        return $tempData;
    }

    private function errorData($message)
    {
        $tempData = [
            'status' => 'error',
            'message' => $message
        ];
        return $tempData;
    }
}