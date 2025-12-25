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

    case 'dividends':
        require_once __DIR__ . '/../infrastructure/auth.php';
        require_auth();
        require __DIR__ . '/../views/dividends/list.php';
        break;

    default:
        header('Location: ?action=login');
        exit;
}

