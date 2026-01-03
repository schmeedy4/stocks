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

    case 'document_download':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?action=dividends');
            exit;
        }
        $controller = new DividendController();
        $controller->download_document($id);
        break;

    case 'document_delete':
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
        $controller->delete_document($id);
        break;

    case 'trade_document_download':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?action=trades');
            exit;
        }
        $controller = new TradeController();
        $controller->download_document($id);
        break;

    case 'trade_document_delete':
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
        $controller->delete_document($id);
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

    case 'holdings':
        $controller = new HoldingsController();
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

    case 'price_update_5days':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=prices');
            exit;
        }
        $controller = new PriceController();
        $controller->update_last_5_days_post();
        break;

    case 'news':
        $controller = new NewsController();
        $controller->list();
        break;

    case 'news_import':
        $controller = new NewsController();
        $controller->import();
        break;

    case 'news_import_post':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=news_import');
            exit;
        }
        $controller = new NewsController();
        $controller->import_post();
        break;

    case 'news_get_json':
        $controller = new NewsController();
        $controller->get_json();
        break;

    case 'news_toggle_read':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        $controller = new NewsController();
        $controller->toggle_read_post();
        break;

    case 'watchlist':
        $controller = new WatchlistController();
        $controller->list();
        break;

    case 'watchlist_add':
        $controller = new WatchlistController();
        $controller->add_post();
        break;

    case 'watchlist_remove':
        $controller = new WatchlistController();
        $controller->remove_post();
        break;

    case 'watchlist_new':
        $controller = new WatchlistController();
        $controller->new();
        break;

    case 'watchlist_create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=watchlist');
            exit;
        }
        $controller = new WatchlistController();
        $controller->create_post();
        break;

    case 'watchlist_edit':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?action=watchlist');
            exit;
        }
        $controller = new WatchlistController();
        $controller->edit($id);
        break;

    case 'watchlist_update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=watchlist');
            exit;
        }
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?action=watchlist');
            exit;
        }
        $controller = new WatchlistController();
        $controller->update_post($id);
        break;

    case 'watchlist_delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=watchlist');
            exit;
        }
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?action=watchlist');
            exit;
        }
        $controller = new WatchlistController();
        $controller->delete_post($id);
        break;

    case 'watchlist_search_instruments':
        $controller = new WatchlistController();
        $controller->search_instruments_json();
        break;

    case 'watchlist_add_instrument':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=watchlist');
            exit;
        }
        $controller = new WatchlistController();
        $controller->add_instrument_post();
        break;

    case 'watchlist_remove_instrument':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?action=watchlist');
            exit;
        }
        $controller = new WatchlistController();
        $controller->remove_instrument_post();
        break;

    default:
        header('Location: ?action=login');
        exit;
}

