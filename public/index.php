<?php

declare(strict_types=1);

use App\Controllers\DividendController;
use App\Controllers\ExportController;
use App\Exports\DohDivCsvExporter;
use App\Infrastructure\DatabaseConnector;
use App\Repositories\AppSettingsRepository;
use App\Repositories\DividendPayerRepository;
use App\Repositories\DividendRepository;
use App\Repositories\TradeLotAllocationRepository;
use App\Repositories\TradeLotRepository;
use App\Repositories\TradeRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\DividendExportService;
use App\Services\DividendService;
use App\Services\TradeService;

require __DIR__ . '/../bootstrap.php';
$config = require __DIR__ . '/../config/config.php';

$pdo = DatabaseConnector::connect($config['db']);

$userRepository = new UserRepository($pdo);
$authService = new AuthService($userRepository);

$dividendRepository = new DividendRepository($pdo);
$payerRepository = new DividendPayerRepository($pdo);
$settingsRepository = new AppSettingsRepository($pdo);
$dividendService = new DividendService($dividendRepository);
$exporter = new DohDivCsvExporter();
$dividendExportService = new DividendExportService($dividendRepository, $payerRepository, $settingsRepository, $exporter);
$dividendController = new DividendController($dividendService, $payerRepository, $authService);
$exportController = new ExportController($dividendExportService, $authService);

$tradeRepository = new TradeRepository($pdo);
$lotRepository = new TradeLotRepository($pdo);
$allocationRepository = new TradeLotAllocationRepository($pdo);
$tradeService = new TradeService($tradeRepository, $lotRepository, $allocationRepository);

$action = $_GET['action'] ?? '';

try {
    if ($action === 'add_dividend') {
        $id = $dividendController->addFromRequest($_POST);
        echo json_encode(['dividend_id' => $id], JSON_THROW_ON_ERROR);
        return;
    }

    if ($action === 'export_doh_div') {
        $year = (int) ($_GET['year'] ?? (new DateTimeImmutable())->format('Y'));
        $csv = $exportController->exportDohDiv($year);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="doh-div-' . $year . '.csv"');
        echo $csv;
        return;
    }

    if ($action === 'add_buy') {
        $userId = $authService->requireUserId();
        $tradeService->createBuyTrade(
            $userId,
            (int) $_POST['instrument_id'],
            new DateTimeImmutable((string) $_POST['trade_date']),
            (float) $_POST['quantity'],
            (float) $_POST['price_per_unit'],
            (string) $_POST['trade_currency'],
            (float) $_POST['fx_rate_to_eur'],
            isset($_POST['fee_eur']) ? (float) $_POST['fee_eur'] : 0.0,
            isset($_POST['broker_account_id']) ? (int) $_POST['broker_account_id'] : null,
            isset($_POST['fee_amount']) ? (float) $_POST['fee_amount'] : null,
            $_POST['fee_currency'] ?? null,
            $_POST['notes'] ?? null,
            $userId,
        );
        echo json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR);
        return;
    }

    if ($action === 'add_sell') {
        $userId = $authService->requireUserId();
        $tradeService->createSellTrade(
            $userId,
            (int) $_POST['instrument_id'],
            new DateTimeImmutable((string) $_POST['trade_date']),
            (float) $_POST['quantity'],
            (float) $_POST['price_per_unit'],
            (string) $_POST['trade_currency'],
            (float) $_POST['fx_rate_to_eur'],
            isset($_POST['fee_eur']) ? (float) $_POST['fee_eur'] : 0.0,
            isset($_POST['broker_account_id']) ? (int) $_POST['broker_account_id'] : null,
            isset($_POST['fee_amount']) ? (float) $_POST['fee_amount'] : null,
            $_POST['fee_currency'] ?? null,
            $_POST['notes'] ?? null,
            $userId,
        );
        echo json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR);
        return;
    }

    http_response_code(404);
    echo json_encode(['error' => 'Unsupported action'], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR);
}
