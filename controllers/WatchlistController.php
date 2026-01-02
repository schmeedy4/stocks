<?php

declare(strict_types=1);

class WatchlistController
{
    private WatchlistRepository $watchlist_repo;
    private InstrumentRepository $instrument_repo;

    public function __construct()
    {
        require_once __DIR__ . '/../infrastructure/auth.php';
        require_auth();

        $this->watchlist_repo = new WatchlistRepository();
        $this->instrument_repo = new InstrumentRepository();
    }

    public function list(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        // Get all watchlists for sidebar
        $all_watchlists = $this->watchlist_repo->list_by_user($user_id);

        // Determine which watchlist to show
        $watchlist_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        
        if ($watchlist_id > 0) {
            $selected_watchlist = $this->watchlist_repo->find_by_id($user_id, $watchlist_id);
            if ($selected_watchlist === null) {
                // Invalid watchlist ID, redirect to default
                header('Location: ?action=watchlist');
                exit;
            }
        } else {
            // No ID provided, use default watchlist
            $default_id = $this->watchlist_repo->watchlist_get_default_id($user_id);
            $selected_watchlist = $this->watchlist_repo->find_by_id($user_id, $default_id);
        }

        // Get instruments for the selected watchlist
        $instruments = $this->watchlist_repo->list_instruments_by_watchlist_id($user_id, $selected_watchlist->id);

        require __DIR__ . '/../views/watchlist/list.php';
    }

    public function new(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $errors = $_SESSION['form_errors'] ?? [];
        $old_input = $_SESSION['old_input'] ?? [];
        unset($_SESSION['form_errors'], $_SESSION['old_input']);

        $watchlist_data = (object) [
            'name' => $old_input['name'] ?? '',
        ];

        require __DIR__ . '/../views/watchlist/form.php';
    }

    public function create_post(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $name = trim($_POST['name'] ?? '');

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Name is required';
        } elseif (strlen($name) > 80) {
            $errors['name'] = 'Name must be 80 characters or less';
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['old_input'] = ['name' => $name];
            header('Location: ?action=watchlist_new');
            exit;
        }

        try {
            $this->watchlist_repo->create($user_id, $name);
            header('Location: ?action=watchlist');
            exit;
        } catch (PDOException $e) {
            // Check for duplicate name error
            if ($e->getCode() === '23000') {
                $errors['name'] = 'A watchlist with this name already exists';
                $_SESSION['form_errors'] = $errors;
                $_SESSION['old_input'] = ['name' => $name];
                header('Location: ?action=watchlist_new');
                exit;
            }
            throw $e;
        }
    }

    public function edit(int $id): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $watchlist = $this->watchlist_repo->find_by_id($user_id, $id);
        if ($watchlist === null) {
            header('Location: ?action=watchlist');
            exit;
        }

        $errors = $_SESSION['form_errors'] ?? [];
        $old_input = $_SESSION['old_input'] ?? [];
        unset($_SESSION['form_errors'], $_SESSION['old_input']);

        $watchlist_data = (object) [
            'id' => $watchlist->id,
            'name' => !empty($old_input) ? $old_input['name'] : $watchlist->name,
            'is_default' => $watchlist->is_default,
        ];

        require __DIR__ . '/../views/watchlist/form.php';
    }

    public function update_post(int $id): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $watchlist = $this->watchlist_repo->find_by_id($user_id, $id);
        if ($watchlist === null) {
            header('Location: ?action=watchlist');
            exit;
        }

        $name = trim($_POST['name'] ?? '');

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Name is required';
        } elseif (strlen($name) > 80) {
            $errors['name'] = 'Name must be 80 characters or less';
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['old_input'] = ['name' => $name];
            header('Location: ?action=watchlist_edit&id=' . $id);
            exit;
        }

        try {
            $this->watchlist_repo->update($user_id, $id, $name);
            header('Location: ?action=watchlist');
            exit;
        } catch (PDOException $e) {
            // Check for duplicate name error
            if ($e->getCode() === '23000') {
                $errors['name'] = 'A watchlist with this name already exists';
                $_SESSION['form_errors'] = $errors;
                $_SESSION['old_input'] = ['name' => $name];
                header('Location: ?action=watchlist_edit&id=' . $id);
                exit;
            }
            throw $e;
        }
    }

    public function delete_post(int $id): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $watchlist = $this->watchlist_repo->find_by_id($user_id, $id);
        if ($watchlist === null) {
            header('Location: ?action=watchlist');
            exit;
        }

        // Prevent deletion of default watchlist
        if ($watchlist->is_default) {
            header('Location: ?action=watchlist');
            exit;
        }

        $this->watchlist_repo->delete($user_id, $id);
        header('Location: ?action=watchlist');
        exit;
    }

    public function add_post(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $instrument_id = isset($_GET['instrument_id']) ? (int) $_GET['instrument_id'] : 0;
        if ($instrument_id <= 0) {
            header('Location: ?action=instruments');
            exit;
        }

        // Verify instrument exists
        $instrument = $this->instrument_repo->find_by_id($instrument_id);
        if ($instrument === null) {
            header('Location: ?action=instruments');
            exit;
        }

        $this->watchlist_repo->watchlist_add_to_default($user_id, $instrument_id);

        // Redirect back to referring page or instruments list
        $referer = $_SERVER['HTTP_REFERER'] ?? '?action=instruments';
        header('Location: ' . $referer);
        exit;
    }

    public function remove_post(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $instrument_id = isset($_GET['instrument_id']) ? (int) $_GET['instrument_id'] : 0;
        if ($instrument_id <= 0) {
            header('Location: ?action=instruments');
            exit;
        }

        // Verify instrument exists
        $instrument = $this->instrument_repo->find_by_id($instrument_id);
        if ($instrument === null) {
            header('Location: ?action=instruments');
            exit;
        }

        $this->watchlist_repo->watchlist_remove_from_default($user_id, $instrument_id);

        // Redirect back to referring page or instruments list
        $referer = $_SERVER['HTTP_REFERER'] ?? '?action=instruments';
        header('Location: ' . $referer);
        exit;
    }

    public function search_instruments_json(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $q = trim($_GET['q'] ?? '');
        $watchlist_id = isset($_GET['watchlist_id']) ? (int) $_GET['watchlist_id'] : 0;

        if ($watchlist_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid watchlist ID']);
            exit;
        }

        // Verify watchlist belongs to user
        $watchlist = $this->watchlist_repo->find_by_id($user_id, $watchlist_id);
        if ($watchlist === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Watchlist not found']);
            exit;
        }

        // Search instruments
        $instrument_service = new InstrumentService();
        $instruments = $instrument_service->list($q);

        // Check which instruments are already in the watchlist
        $result = [];
        foreach ($instruments as $instrument) {
            $is_in_watchlist = $this->watchlist_repo->is_instrument_in_watchlist($user_id, $watchlist_id, $instrument->id);
            $result[] = [
                'id' => $instrument->id,
                'isin' => $instrument->isin,
                'ticker' => $instrument->ticker,
                'name' => $instrument->name,
                'instrument_type' => $instrument->instrument_type,
                'is_in_watchlist' => $is_in_watchlist,
            ];
        }

        header('Content-Type: application/json');
        echo json_encode(['instruments' => $result]);
        exit;
    }

    public function add_instrument_post(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $watchlist_id = isset($_POST['watchlist_id']) ? (int) $_POST['watchlist_id'] : 0;
        $instrument_id = isset($_POST['instrument_id']) ? (int) $_POST['instrument_id'] : 0;

        if ($watchlist_id <= 0 || $instrument_id <= 0) {
            header('Location: ?action=watchlist');
            exit;
        }

        // Verify watchlist belongs to user
        $watchlist = $this->watchlist_repo->find_by_id($user_id, $watchlist_id);
        if ($watchlist === null) {
            header('Location: ?action=watchlist');
            exit;
        }

        // Verify instrument exists
        $instrument = $this->instrument_repo->find_by_id($instrument_id);
        if ($instrument === null) {
            header('Location: ?action=watchlist&id=' . $watchlist_id);
            exit;
        }

        $this->watchlist_repo->add_instrument_to_watchlist($user_id, $watchlist_id, $instrument_id);

        header('Location: ?action=watchlist&id=' . $watchlist_id);
        exit;
    }

    public function remove_instrument_post(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        $watchlist_id = isset($_POST['watchlist_id']) ? (int) $_POST['watchlist_id'] : 0;
        $instrument_id = isset($_POST['instrument_id']) ? (int) $_POST['instrument_id'] : 0;

        if ($watchlist_id <= 0 || $instrument_id <= 0) {
            header('Location: ?action=watchlist');
            exit;
        }

        // Verify watchlist belongs to user
        $watchlist = $this->watchlist_repo->find_by_id($user_id, $watchlist_id);
        if ($watchlist === null) {
            header('Location: ?action=watchlist');
            exit;
        }

        // Verify instrument exists
        $instrument = $this->instrument_repo->find_by_id($instrument_id);
        if ($instrument === null) {
            header('Location: ?action=watchlist&id=' . $watchlist_id);
            exit;
        }

        $this->watchlist_repo->remove_instrument_from_watchlist($user_id, $watchlist_id, $instrument_id);

        header('Location: ?action=watchlist&id=' . $watchlist_id);
        exit;
    }
}

