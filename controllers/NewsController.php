<?php

declare(strict_types=1);

class NewsController
{
    private NewsArticleService $news_service;

    public function __construct()
    {
        require_once __DIR__ . '/../infrastructure/auth.php';
        require_auth();

        $this->news_service = new NewsArticleService();
    }

    public function list(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        // Get filter parameters from query string
        $title = isset($_GET['title']) && $_GET['title'] !== '' ? trim($_GET['title']) : null;
        $author = isset($_GET['author']) && $_GET['author'] !== '' ? trim($_GET['author']) : null;
        $ticker = isset($_GET['ticker']) && $_GET['ticker'] !== '' 
            ? strtoupper(trim($_GET['ticker'])) 
            : null;
        $sentiment = isset($_GET['sentiment']) && $_GET['sentiment'] !== '' ? $_GET['sentiment'] : null;
        $min_confidence = isset($_GET['min_confidence']) && $_GET['min_confidence'] !== '' 
            ? (int) $_GET['min_confidence'] 
            : null;
        $min_read_grade = isset($_GET['min_read_grade']) && $_GET['min_read_grade'] !== '' 
            ? (int) $_GET['min_read_grade'] 
            : null;
        $read_status = isset($_GET['read_status']) && $_GET['read_status'] !== '' ? $_GET['read_status'] : 'all';
        $show_only = isset($_GET['show_only']) && $_GET['show_only'] !== '' ? $_GET['show_only'] : 'all';
        $sort = isset($_GET['sort']) && $_GET['sort'] !== '' ? $_GET['sort'] : 'captured_desc';
        $page = isset($_GET['page']) && $_GET['page'] !== '' ? max(1, (int) $_GET['page']) : 1;
        $limit = isset($_GET['limit']) && $_GET['limit'] !== '' ? max(1, (int) $_GET['limit']) : 25;

        // Get holdings tickers (always, for highlighting in the view)
        $holdings_service = new HoldingsService();
        $holdings_result = $holdings_service->get_holdings($user_id);
        $holdings = $holdings_result['holdings'];
        
        // Extract tickers from holdings
        $holdings_tickers_list = [];
        foreach ($holdings as $holding) {
            if (isset($holding['instrument']) && $holding['instrument']->ticker !== null && $holding['instrument']->ticker !== '') {
                $holdings_tickers_list[] = strtoupper(trim($holding['instrument']->ticker));
            }
        }
        // Remove duplicates and create lookup array for fast checking
        $holdings_tickers_list = array_unique($holdings_tickers_list);
        $holdings_tickers_lookup = array_flip($holdings_tickers_list);

        // Get watchlist tickers (for highlighting in the view)
        $watchlist_repo = new WatchlistRepository();
        $watchlists = $watchlist_repo->list_by_user($user_id);
        $watchlist_tickers_list = [];
        foreach ($watchlists as $watchlist) {
            $instruments = $watchlist_repo->list_instruments_by_watchlist_id($user_id, $watchlist->id);
            foreach ($instruments as $instrument) {
                if ($instrument->ticker !== null && $instrument->ticker !== '') {
                    $ticker_upper = strtoupper(trim($instrument->ticker));
                    // Only add if not already in holdings (holdings take priority)
                    if (!isset($holdings_tickers_lookup[$ticker_upper])) {
                        $watchlist_tickers_list[] = $ticker_upper;
                    }
                }
            }
        }
        // Remove duplicates and create lookup array for fast checking
        $watchlist_tickers_list = array_unique($watchlist_tickers_list);
        $watchlist_tickers_lookup = array_flip($watchlist_tickers_list);

        // Get holdings tickers for filtering (if filter is enabled)
        $holdings_tickers = null;
        if ($show_only === 'holdings') {
            $holdings_tickers = $holdings_tickers_list;
        }

        // Get watchlist tickers for filtering (if filter is enabled)
        $watchlist_tickers = null;
        if ($show_only === 'watchlist') {
            $watchlist_repo = new WatchlistRepository();
            $watchlists = $watchlist_repo->list_by_user($user_id);
            $watchlist_tickers_list = [];
            foreach ($watchlists as $watchlist) {
                $instruments = $watchlist_repo->list_instruments_by_watchlist_id($user_id, $watchlist->id);
                foreach ($instruments as $instrument) {
                    if ($instrument->ticker !== null && $instrument->ticker !== '') {
                        $watchlist_tickers_list[] = strtoupper(trim($instrument->ticker));
                    }
                }
            }
            $watchlist_tickers_list = array_unique($watchlist_tickers_list);
            $watchlist_tickers = array_values($watchlist_tickers_list);
        }

        $result = $this->news_service->search(
            $ticker,
            $sentiment,
            $min_confidence,
            $min_read_grade,
            $holdings_tickers,
            $watchlist_tickers,
            $sort,
            $page,
            $limit,
            $user_id,
            $read_status,
            $title,
            $author
        );

        $articles = $result['items'];
        $total = $result['total'];
        $total_pages = (int) ceil($total / $limit);

        require __DIR__ . '/../views/news/list.php';
    }

    public function import(): void
    {
        $errors = $_SESSION['form_errors'] ?? [];
        $old_input = $_SESSION['old_input'] ?? '';
        unset($_SESSION['form_errors'], $_SESSION['old_input']);

        require __DIR__ . '/../views/news/import.php';
    }

    public function import_post(): void
    {
        $json_text = $_POST['json'] ?? '';

        if ($json_text === '') {
            $_SESSION['form_errors'] = ['json' => 'JSON is required'];
            $_SESSION['old_input'] = $json_text;
            header('Location: ?action=news_import');
            exit;
        }

        // Try to parse JSON
        $json_data = json_decode($json_text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $_SESSION['form_errors'] = ['json' => 'Invalid JSON: ' . json_last_error_msg()];
            $_SESSION['old_input'] = $json_text;
            header('Location: ?action=news_import');
            exit;
        }

        try {
            $id = $this->news_service->import($json_data);
            $_SESSION['success_message'] = 'News article saved successfully';
            header('Location: ?action=news&highlight=' . $id);
            exit;
        } catch (ValidationException $e) {
            $_SESSION['form_errors'] = $e->errors;
            $_SESSION['old_input'] = $json_text;
            header('Location: ?action=news_import');
            exit;
        } catch (\Exception $e) {
            $_SESSION['form_errors'] = ['json' => 'Error saving: ' . $e->getMessage()];
            $_SESSION['old_input'] = $json_text;
            header('Location: ?action=news_import');
            exit;
        }
    }

    public function get_json(): void
    {
        header('Content-Type: application/json');

        $user_id = current_user_id();
        if ($user_id === null) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid id']);
            exit;
        }

        try {
            $article = $this->news_service->get($id);
            echo json_encode([
                'id' => $article->id,
                'source' => $article->source,
                'url' => $article->url,
                'title' => $article->title,
                'published_at' => $article->published_at,
                'captured_at' => $article->captured_at,
                'author_name' => $article->author_name,
                'author_url' => $article->author_url,
                'author_followers' => $article->author_followers,
                'sentiment' => $article->sentiment,
                'confidence' => $article->confidence,
                'read_grade' => $article->read_grade,
                'tickers' => $article->tickers,
                'drivers' => $article->drivers,
                'key_dates' => $article->key_dates,
                'tags' => $article->tags,
                'recap' => $article->recap,
                'raw_json' => $article->raw_json,
            ]);
        } catch (NotFoundException $e) {
            http_response_code(404);
            echo json_encode(['error' => 'Article not found']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    public function toggle_read_post(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $article_id = isset($_POST['article_id']) ? (int) $_POST['article_id'] : 0;
        if ($article_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid article_id']);
            exit;
        }

        try {
            // Verify article exists
            $article = $this->news_service->get($article_id);
            
            $read_repo = new NewsReadRepository();
            $is_read = $read_repo->toggle_read($user_id, $article_id);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'is_read' => $is_read,
            ]);
        } catch (NotFoundException $e) {
            http_response_code(404);
            echo json_encode(['error' => 'Article not found']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    public function key_dates(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        // Get filter parameters
        $date_range = isset($_GET['range']) && $_GET['range'] !== '' ? $_GET['range'] : 'upcoming';
        $type = isset($_GET['type']) && $_GET['type'] !== '' ? $_GET['type'] : null;
        $ticker = isset($_GET['ticker']) && $_GET['ticker'] !== '' ? trim($_GET['ticker']) : null;

        // Validate date_range
        $valid_ranges = ['upcoming', 'past_30', 'next_30', 'next_90', 'all'];
        if (!in_array($date_range, $valid_ranges, true)) {
            $date_range = 'upcoming';
        }

        // Get holdings tickers (for highlighting in the view)
        $holdings_service = new HoldingsService();
        $holdings_result = $holdings_service->get_holdings($user_id);
        $holdings = $holdings_result['holdings'];
        
        // Extract tickers from holdings
        $holdings_tickers_list = [];
        foreach ($holdings as $holding) {
            if (isset($holding['instrument']) && $holding['instrument']->ticker !== null && $holding['instrument']->ticker !== '') {
                $holdings_tickers_list[] = strtoupper(trim($holding['instrument']->ticker));
            }
        }
        // Remove duplicates and create lookup array for fast checking
        $holdings_tickers_list = array_unique($holdings_tickers_list);
        $holdings_tickers_lookup = array_flip($holdings_tickers_list);

        // Get watchlist tickers (for highlighting in the view)
        $watchlist_repo = new WatchlistRepository();
        $watchlists = $watchlist_repo->list_by_user($user_id);
        $watchlist_tickers_list = [];
        foreach ($watchlists as $watchlist) {
            $instruments = $watchlist_repo->list_instruments_by_watchlist_id($user_id, $watchlist->id);
            foreach ($instruments as $instrument) {
                if ($instrument->ticker !== null && $instrument->ticker !== '') {
                    $ticker_upper = strtoupper(trim($instrument->ticker));
                    // Only add if not already in holdings (holdings take priority)
                    if (!isset($holdings_tickers_lookup[$ticker_upper])) {
                        $watchlist_tickers_list[] = $ticker_upper;
                    }
                }
            }
        }
        // Remove duplicates and create lookup array for fast checking
        $watchlist_tickers_list = array_unique($watchlist_tickers_list);
        $watchlist_tickers_lookup = array_flip($watchlist_tickers_list);

        // Get key dates
        $key_dates = $this->news_service->get_key_dates($date_range, $type, $ticker);

        // Get unique types for filter dropdown
        $unique_types = $this->news_service->get_unique_key_date_types();

        require __DIR__ . '/../views/news/key_dates.php';
    }
}

