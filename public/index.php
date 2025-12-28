<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../infrastructure/autoloader.php';

$action = $_GET['action'] ?? 'login';

switch ($action) {
    case 'login':
        $controller = new AuthController();
        $controller->show_login();
        break;

    case 'login_post':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=login');
            exit;
        }
        $controller = new AuthController();
        $controller->login_post();
        break;

    case 'logout':
        $controller = new AuthController();
        $controller->logout();
        break;

    case 'dashboard':
        require_once __DIR__ . '/../infrastructure/auth.php';
        require_auth();
        $controller = new DashboardController();
        $controller->index();
        break;

    case 'instruments':
        $controller = new InstrumentController();
        $controller->list();
        break;

    case 'instrument_new':
        $controller = new InstrumentController();
        $controller->new();
        break;

    case 'instrument_create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=instruments');
            exit;
        }
        $controller = new InstrumentController();
        $controller->create_post();
        break;

    case 'instrument_edit':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?action=instruments');
            exit;
        }
        $controller = new InstrumentController();
        $controller->edit($id);
        break;

    case 'instrument_update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=instruments');
            exit;
        }
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?action=instruments');
            exit;
        }
        $controller = new InstrumentController();
        $controller->update_post($id);
        break;

    case 'trades':
        $controller = new TradeController();
        $controller->list();
        break;

    case 'trade_new_buy':
        $controller = new TradeController();
        $controller->new_buy();
        break;

    case 'trade_create_buy':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=trades');
            exit;
        }
        $controller = new TradeController();
        $controller->create_buy_post();
        break;

    case 'trade_new_sell':
        $controller = new TradeController();
        $controller->new_sell();
        break;

    case 'trade_create_sell':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=trades');
            exit;
        }
        $controller = new TradeController();
        $controller->create_sell_post();
        break;

    case 'trade_view_sell':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?action=trades');
            exit;
        }
        $controller = new TradeController();
        $controller->view_sell($id);
        break;

    case 'trade_edit':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?action=trades');
            exit;
        }
        $controller = new TradeController();
        $controller->edit($id);
        break;

    case 'trade_update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=trades');
            exit;
        }
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?action=trades');
            exit;
        }
        $controller = new TradeController();
        $controller->update_post($id);
        break;

    case 'trades_sell_available':
        $controller = new TradeController();
        $controller->get_available_quantity_json();
        break;

    case 'trades_sell_instruments':
        $controller = new TradeController();
        $controller->get_sell_instruments_json();
        break;

    case 'payers':
        $controller = new DividendPayerController();
        $controller->list();
        break;

    case 'payer_new':
        $controller = new DividendPayerController();
        $controller->new();
        break;

    case 'payer_create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=payers');
            exit;
        }
        $controller = new DividendPayerController();
        $controller->create_post();
        break;

    case 'payer_edit':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?action=payers');
            exit;
        }
        $controller = new DividendPayerController();
        $controller->edit($id);
        break;

    case 'payer_update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=payers');
            exit;
        }
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?action=payers');
            exit;
        }
        $controller = new DividendPayerController();
        $controller->update_post($id);
        break;

    case 'dividends':
        $controller = new DividendController();
        $controller->list();
        break;

    case 'dividend_new':
        $controller = new DividendController();
        $controller->new();
        break;

    case 'dividend_create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=dividends');
            exit;
        }
        $controller = new DividendController();
        $controller->create_post();
        break;

    case 'dividend_edit':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?action=dividends');
            exit;
        }
        $controller = new DividendController();
        $controller->edit($id);
        break;

    case 'dividend_update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=dividends');
            exit;
        }
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?action=dividends');
            exit;
        }
        $controller = new DividendController();
        $controller->update_post($id);
        break;

    case 'dividend_void':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=dividends');
            exit;
        }
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?action=dividends');
            exit;
        }
        $controller = new DividendController();
        $controller->void_post($id);
        break;

    case 'corporate_actions':
        $controller = new CorporateActionController();
        $controller->show_split_form();
        break;

    case 'corporate_action_apply_split':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=corporate_actions');
            exit;
        }
        $controller = new CorporateActionController();
        $controller->apply_split_post();
        break;

    case 'prices':
        $controller = new PriceController();
        $controller->list();
        break;

    case 'price_update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=prices');
            exit;
        }
        $controller = new PriceController();
        $controller->update_prices_post();
        break;

    default:
        header('Location: ?action=login');
        exit;
}

