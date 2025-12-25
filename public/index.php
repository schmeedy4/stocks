<?php

declare(strict_types=1);

use App\Controllers\AuthController;
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

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$config = require __DIR__ . '/../config/config.php';

$pdo = DatabaseConnector::connect($config['db']);

$userRepository = new UserRepository($pdo);
$authService = new AuthService($userRepository);
$authController = new AuthController($authService);

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

$action = $_GET['action'] ?? 'dividends';

/** @return array<int, array{type:string,message:string}> */
function collectFlashes(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);

    return $messages;
}

function addFlash(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

try {
    if ($action !== 'login') {
        require_auth();
    }

    if ($action === 'login') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $authController->loginPost((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''));
                addFlash('success', 'Logged in.');
                header('Location: /?action=dividends');
                return;
            } catch (Throwable $e) {
                addFlash('error', $e->getMessage());
                header('Location: /?action=login');
                return;
            }
        }

        $flashes = collectFlashes();
        $authController->showLogin($flashes);
        return;
    }

    if ($action === 'logout') {
        $authController->logout();
        addFlash('success', 'Logged out.');
        header('Location: /?action=login');
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_dividend') {
        try {
            $dividendController->addFromRequest($_POST);
            addFlash('success', 'Dividend saved.');
        } catch (Throwable $e) {
            addFlash('error', $e->getMessage());
        }

        $yearForRedirect = isset($_POST['year']) ? (int) $_POST['year'] : (int) ($_GET['year'] ?? (new DateTimeImmutable())->format('Y'));
        header('Location: /?action=dividends&year=' . $yearForRedirect);
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

    if ($action === 'dividends' || $action === '') {
        $year = (int) ($_GET['year'] ?? (new DateTimeImmutable())->format('Y'));
        $dividends = $dividendController->listForYear($year);
        $payers = [];
        foreach ($dividendController->listActivePayers() as $payer) {
            $payers[$payer->id] = $payer;
        }
        $flashes = collectFlashes();

        require __DIR__ . '/../views/dividends.php';
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
