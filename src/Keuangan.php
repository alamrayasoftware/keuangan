<?php

namespace ArsoftModules\Keuangan;

use ArsoftModules\Keuangan\Controllers\Analisa\AsetEtaController;
use ArsoftModules\Keuangan\Controllers\Analisa\CashflowController;
use Exception;

class Keuangan {
    private $status = 'success', $data, $errorMessage;

    public function getStatus()
    {
        return $this->status;
    }
    public function getData()
    {
        return $this->data;
    }
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

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
            $this->status = 'error';
            $this->errorMessage = $report['message'];
            return $this;
        }

        $this->data = $report['data'];

        return $this;
    }

    /**
     * @param string $position position id
     * @param string $startDate date_format: Y-m
     * @param string $endDate date_format: Y-m
     * @param string $type opt : 'month', 'year'
     */
    public function reportCashflow(
        string $position,
        string $startDate,
        string $endDate,
        string $type = 'month'
    )
    {
        $cashflow = new CashflowController();

        $report = $cashflow->data(
            $position,
            $startDate,
            $endDate,
            $type
        );

        if ($report['status'] !== 'success') {
            $this->status = 'error';
            $this->errorMessage = $report['message'];
            return $this;
        }

        $tempData = [
            'period' => $report['period'],
            'note' => $report['keterangan'],
            'report' => $report['data']
        ];
        $this->data = $tempData;

        return $this;
    }
}