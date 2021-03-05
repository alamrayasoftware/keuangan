<?php

namespace ArsoftModules\Keuangan;

use ArsoftModules\Keuangan\Controllers\Analysis\AsetEtaController;
use ArsoftModules\Keuangan\Controllers\Analysis\CashflowController;
use ArsoftModules\Keuangan\Controllers\Analysis\CommonSizeController;
use stdClass;

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

        $tempData = new stdClass();
        $tempData->periods = $report['periods'];
        $tempData->assets = $report['assets'];
        $tempData->equities = $report['equities'];

        $this->data = $tempData;

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

        $tempData = new stdClass();
        $tempData->periods = $report['periods'];
        $tempData->notes = $report['notes'];
        $tempData->cashflow = $report['cashflow'];

        $this->data = $tempData;

        return $this;
    }

    /**
     * @param string $position position id
     * @param string $startDate date_format: Y-m
     * @param string $endDate date_format: Y-m
     * @param string $type opt : 'month', 'year'
     */
    public function reportCommonSize(
        string $position,
        string $startDate,
        string $endDate,
        string $type = 'month'
    )
    {
        $commonSize = new CommonSizeController();

        $report = $commonSize->data(
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

        $tempData = new stdClass();
        $tempData->periods = $report['periods'];
        $tempData->accounts = $report['accounts'];
        $tempData->balance_sheet = $report['balance_sheet'];
        $tempData->profit_and_loss = $report['profit_and_loss'];
        
        $this->data = $tempData;

        return $this;
    }
}