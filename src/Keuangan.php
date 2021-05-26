<?php

namespace ArsoftModules\Keuangan;

use ArsoftModules\Keuangan\Controllers\Analysis\AsetEtaController;
use ArsoftModules\Keuangan\Controllers\Analysis\CashflowController;
use ArsoftModules\Keuangan\Controllers\Analysis\CommonSizeController;
use ArsoftModules\Keuangan\Controllers\Analysis\LiquidityRatioController;
use ArsoftModules\Keuangan\Controllers\Analysis\NetProfitOcfController;
use ArsoftModules\Keuangan\Controllers\Analysis\ReturnEquityController;
use ArsoftModules\Keuangan\Controllers\Reports\BalanceSheetController;
use ArsoftModules\Keuangan\Controllers\Reports\IncomeStatementController;
use ArsoftModules\Keuangan\Controllers\Reports\JournalController;
use ArsoftModules\Keuangan\Controllers\Reports\LedgerReportController;
use ArsoftModules\Keuangan\Helpers\JournalHelper;
use ArsoftModules\Keuangan\Helpers\TransactionHelper;
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

    // --------- start: transaction cash ---------
    // -------------------------------------------

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
    public function storeTransaction(
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
        $transactionHelper = new TransactionHelper();

        $transactionType = 'TK';
        $transaction = $transactionHelper->storeTransaction(
            $transactionType,
            $date,
            $position,
            $note,
            $cashAccounts,
            $nominals,
            $detailTypes,
            $detailNotes,
            $cashflows
        );

        if ($transaction['status'] !== 'success') {
            $this->status = 'error';
            $this->errorMessage = $transaction['message'];
            return $this;
        }

        $this->data = null;

        return $this;
    }

    /**
     * @param string $position position-id
     * @param string $transactionType transaction type, opt:  TK/TM/MK
     * @param int $year year
     * @param int $month month
     */
    public function showAllTransaction(
        string $position,
        string $transactionType = null,
        int $year = null,
        int $month = null
    )
    {
        $transactionHelper = new TransactionHelper();

        $transaction = $transactionHelper->showAllTransaction(
            $position,
            $transactionType,
            $year,
            $month
        );

        if ($transaction['status'] !== 'success') {
            $this->status = 'error';
            $this->errorMessage = $transaction['message'];
            return $this;
        }

        $this->data = $transaction['data'];

        return $this;
    }

    /**
     * @param string $transaction transaction-id
     */
    public function deleteTransaction(
        string $transactionId
    )
    {
        $transactionHelper = new TransactionHelper();

        $transaction = $transactionHelper->deleteTransaction(
            $transactionId
        );

        if ($transaction['status'] !== 'success') {
            $this->status = 'error';
            $this->errorMessage = $transaction['message'];
            return $this;
        }

        $this->data = null;

        return $this;
    }
    // --------- start: journal ---------
    // ----------------------------------
    
    /**
     * @param array $journalDetails list journal-data
     * @param string $date transaction date, format: Y-m-d
     * @param string $transactionNota transaction number/nota
     * @param string $note transaction note
     * @param string $type journal type, exp: MK/TK/TM ( Mutasi Kas, Transaksi Kas, Transaksi Memorial )
     * @param string $position position-id
     * @param string $isMemorial option: 'Y', 'N'
     */
    public function storeJournal(
        array $journalDetails,
        string $date,
        string $transactionNota,
        string $note,
        string $type,
        string $position,
        string $isMemorial = 'N'
    ) {
        $journalHelper = new JournalHelper();

        $journal = $journalHelper->store(
            $journalDetails,
            $date,
            $transactionNota,
            $note,
            $type,
            $position,
            $isMemorial
        );

        if ($journal['status'] !== 'success') {
            $this->status = 'error';
            $this->errorMessage = $journal['message'];
            return $this;
        }

        $tempData = new stdClass();
        $tempData->transaction_nota = $transactionNota;
        $tempData->date = $date;

        $this->data = $tempData;

        return $this;
    }

    /**
     * @param string $journalId journal-id
     */
    public function destroyJournal(string $journalId)
    {
        $journalHelper = new JournalHelper();

        $destroyJournal = $journalHelper->destroy($journalId);

        if ($destroyJournal['status'] !== 'success') {
            $this->status = 'error';
            $this->errorMessage = $destroyJournal['message'];
            return $this;
        }

        $this->data = null;

        return $this;
    }

    // --------- start: analysis ---------
    // -----------------------------------
    
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
    ) {
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
    ) {
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
    ) {
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

    /**
     * @param string $startDate date_format: Y-m
     * @param string $endDate date_format: Y-m
     * @param string $type opt : 'month', 'year'
     */
    public function reportNetProfitOcf(
        string $startDate,
        string $endDate,
        string $type = 'month'
    ) {
        $netProfitOcf = new NetProfitOcfController();

        $report = $netProfitOcf->data(
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
        $tempData->ocf = $report['ocf'];
        $tempData->net_profit = $report['net_profit'];

        $this->data = $tempData;

        return $this;
    }

    /**
     * @param string $position position id
     * @param string $startDate date_format: Y-m
     * @param string $endDate date_format: Y-m
     * @param string $type opt : 'month', 'year'
     */
    public function reportLiquidityRatio(
        string $position,
        string $startDate,
        string $endDate,
        string $type = 'month'
    ) {
        $liquidityRatio = new LiquidityRatioController();

        $report = $liquidityRatio->data(
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
        $tempData->liquidity_ratio = $report['data'];

        $this->data = $tempData;

        return $this;
    }

    /**
     * @param string $year date_format: Y
     */
    public function reportReturnEquity(
        string $year
    ) {
        $returnEquity = new ReturnEquityController();

        $report = $returnEquity->data(
            $year
        );

        if ($report['status'] !== 'success') {
            $this->status = 'error';
            $this->errorMessage = $report['message'];
            return $this;
        }

        $tempData = new stdClass();
        $tempData->periods = $report['periods'];
        $tempData->total = $report['total'];
        $tempData->expenses = $report['expenses'];
        $tempData->assets = $report['assets'];
        $tempData->current_assets = $report['current_assets'];
        $tempData->fixed_assets = $report['fixed_assets'];

        $this->data = $tempData;

        return $this;
    }

    // --------- start: report ---------
    // ---------------------------------

    /**
     * @param string $position position id
     * @param string $startDate date_format: Y-m-d
     * @param string $endDate date_format: Y-m-d
     * @param string $type opt : 'general', 'cash', 'memorial'
     */
    public function reportJournal(
        string $position,
        string $startDate,
        string $endDate,
        string $type = 'general'
    ) {
        $journal = new JournalController();

        $report = $journal->data(
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
        $tempData->type = $report['type'];
        $tempData->journals = $report['data'];
        $tempData->debit_final = $report['debit_final'];
        $tempData->credit_final = $report['credit_final'];

        $this->data = $tempData;

        return $this;
    }

    /**
     * @param string $date date_format: Y-m-d
     * @param string $type opt : 'month', 'year'
     */
    public function reportBalanceSheet(
        string $date,
        string $type = 'month'
    ) {
        $balanceSheets = new BalanceSheetController();

        $report = $balanceSheets->data(
            $date,
            $type
        );

        if ($report['status'] !== 'success') {
            $this->status = 'error';
            $this->errorMessage = $report['message'];
            return $this;
        }

        $tempData = new stdClass();
        $tempData->periods = $report['periods'];
        $tempData->type = $report['type'];
        $tempData->balance_sheet = $report['data'];
        $tempData->profit_balance = $report['profit_balance'];

        $this->data = $tempData;

        return $this;
    }

    /**
     * @param string $date date_format: Y-m-d
     * @param string $type opt : 'month', 'year'
     */
    public function reportIncomeStatement(
        string $date,
        string $type = 'month'
    ) {
        $incomeStatement = new IncomeStatementController();

        $report = $incomeStatement->data(
            $date,
            $type
        );

        if ($report['status'] !== 'success') {
            $this->status = 'error';
            $this->errorMessage = $report['message'];
            return $this;
        }

        $tempData = new stdClass();
        $tempData->periods = $report['periods'];
        $tempData->type = $report['type'];
        $tempData->income_statement = $report['data'];

        $this->data = $tempData;

        return $this;
    }

    /**
     * @param string $position position-id
     * @param int $groupId group-id
     * @param string $date date_format: Y-m-d
     */
    public function reportLedger(
        string $position,
        int $groupId,
        string $date
    ) {
        $ledgerReport = new LedgerReportController();

        $report = $ledgerReport->data(
            $position, 
            $groupId,
            $date
        );

        if ($report['status'] !== 'success') {
            $this->status = 'error';
            $this->errorMessage = $report['message'];
            return $this;
        }

        $tempData = new stdClass();
        $tempData->periods = $report['periods'];
        $tempData->ledger_report = $report['data'];

        $this->data = $tempData;

        return $this;
    }
}