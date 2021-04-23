# Keuangan

Package untuk mengelola data keuangan mulai dari jurnal, akun keuangan, hingga laporan-laporan terkait keuangan

## Konfigurasi

untuk menggunakan package ini dengan optimal, perhatikan poin-poin berikut ini :

* Keperluan tabel
  * dk_akun
  * dk_akun_cashflow
  * dk_akun_saldo
  * dk_hierarki_dua
  * dk_hierarki_satu
  * dk_hierarki_subclass
  * dk_jurnal
  * dk_jurnal_detail
  * dk_periode_keuangan

* Konfigurasi *config/database.php* pada laravel
  
  ```php
  'connections' => [
      ...
      'mysql' => [
          ...
          'strict' => false,
          ...
      ],
  ],
  ```

## Instalasi

install package menggunakan composer

```cli
composer require arsoft-modules/keuangan
```

## Penggunaan

### Laporan Jurnal

```php
use ArsoftModules\Keuangan\Keuangan;

$arsKeuangan = new Keuangan();
$reportJournal = $arsKeuangan->reportJournal(
    'MB0000001',
    '2021-04-01',
    '2021-04-30',
    'general'
);

if ($reportJournal->getStatus() !== 'success') {
    throw new Exception($reportJournal->getErrorMessage(), 1);
}
$reportJournal = $reportJournal->getData();
```

### Laporan Neraca

```php
use ArsoftModules\Keuangan\Keuangan;

$arsKeuangan = new Keuangan();
$reportBalanceSheet = $arsKeuangan->reportBalanceSheet(
    '2021-04-30',
    'month'
);

if ($reportBalanceSheet->getStatus() !== 'success') {
    throw new Exception($reportBalanceSheet->getErrorMessage(), 1);
}
$reportBalanceSheet = $reportBalanceSheet->getData();
```

### Laporan Laba Rugi

```php
use ArsoftModules\Keuangan\Keuangan;

$arsKeuangan = new Keuangan();
$reportIncomeStatement = $arsKeuangan->reportIncomeStatement(
    '2020-07-30',
    'month'
);

if ($reportIncomeStatement->getStatus() !== 'success') {
    throw new Exception($reportIncomeStatement->getErrorMessage(), 1);
}
$reportIncomeStatement = $reportIncomeStatement->getData();
```

### Laporan Buku Besar

```php
use ArsoftModules\Keuangan\Keuangan;

$arsKeuangan = new Keuangan();
$reportLedger = $arsKeuangan->reportLedger(
    'MB0000001',
    1,
    '2021-03-25'
);

if ($reportLedger->getStatus() !== 'success') {
    throw new Exception($reportLedger->getErrorMessage(), 1);
}
$reportLedger = $reportLedger->getData();
```

### Analisa Aset vs ETA

```php
use ArsoftModules\Keuangan\Keuangan;

$arsKeuangan = new Keuangan();
$reportAsetEta = $arsKeuangan->reportAsetEta(
    'MB0000001',
    '2020-01',
    '2021-03',
    'month'
);

if ($reportAsetEta->getStatus() !== 'success') {
    throw new Exception($reportAsetEta->getErrorMessage(), 1);
}
$reportAsetEta = $reportAsetEta->getData();
```

### Analisa Cashflow

```php
use ArsoftModules\Keuangan\Keuangan;

$arsKeuangan = new Keuangan();
$reportCashflow = $arsKeuangan->reportCashflow(
    'MB0000001',
    '2020-01',
    '2020-12',
    'month'
);

if ($reportCashflow->getStatus() !== 'success') {
    throw new Exception($reportCashflow->getErrorMessage(), 1);
}
$reportCashflow = $reportCashflow->getData();
```

### Analisa Common Size

```php
use ArsoftModules\Keuangan\Keuangan;

$arsKeuangan = new Keuangan();
$reportCommonSize = $arsKeuangan->reportCommonSize(
        'MB0000001',
        '2021-01',
        '2021-04',
        'month'
    );

if ($reportCommonSize->getStatus() !== 'success') {
    throw new Exception($reportCommonSize->getErrorMessage(), 1);
}
$reportCommonSize = $reportCommonSize->getData();
```

### Analisa Net Profit vs OCF

```php
use ArsoftModules\Keuangan\Keuangan;

$arsKeuangan = new Keuangan();
$reportNetProfitOcf = $arsKeuangan->reportNetProfitOcf(
    '2021-01',
    '2021-04',
    'month'
);

if ($reportNetProfitOcf->getStatus() !== 'success') {
    throw new Exception($reportNetProfitOcf->getErrorMessage(), 1);
}
$reportNetProfitOcf = $reportNetProfitOcf->getData();
```

### Analisa Liquidity Ratio

```php
use ArsoftModules\Keuangan\Keuangan;

$arsKeuangan = new Keuangan();
$reportLiquidityRatio = $arsKeuangan->reportLiquidityRatio(
    'MB0000001',
    '2021-01',
    '2021-04',
    'month'
);

if ($reportLiquidityRatio->getStatus() !== 'success') {
    throw new Exception($reportLiquidityRatio->getErrorMessage(), 1);
}
$reportLiquidityRatio = $reportLiquidityRatio->getData();
```

### Analisa Return On Equity

```php
use ArsoftModules\Keuangan\Keuangan;

$arsKeuangan = new Keuangan();
$reportReturnEquity = $arsKeuangan->reportReturnEquity(
    '2020'
);

if ($reportReturnEquity->getStatus() !== 'success') {
    throw new Exception($reportReturnEquity->getErrorMessage(), 1);
}
$reportReturnEquity = $reportReturnEquity->getData();
```
