<?php

declare(strict_types=1);

class NewsArticleRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get_connection();
    }

    /**
     * Search news articles with filters and pagination
     * @return array{items: array, total: int}
     */
    public function search(
        ?string $ticker = null,
        ?string $sentiment = null,
        ?int $min_confidence = null,
        ?int $min_read_grade = null,
        ?array $holdings_tickers = null,
        ?array $watchlist_tickers = null,
        string $sort = 'captured_desc',
        int $page = 1,
        int $limit = 25,
        ?int $user_id = null,
        string $read_status = 'all'
    ): array {
        $offset = ($page - 1) * $limit;

        // Build JOIN for read status filter if needed
        $read_join = '';
        $read_where = '';
        if ($user_id !== null && $read_status !== 'all') {
            if ($read_status === 'read') {
                $read_join = ' INNER JOIN news_read nr ON news_article.id = nr.news_article_id AND nr.user_id = :read_user_id';
            } elseif ($read_status === 'unread') {
                $read_join = ' LEFT JOIN news_read nr ON news_article.id = nr.news_article_id AND nr.user_id = :read_user_id';
                $read_where = ' AND nr.news_article_id IS NULL';
            }
        }

        $sql = 'SELECT 
            news_article.id, news_article.source, news_article.url, news_article.url_hash, news_article.title, 
            news_article.published_at, news_article.captured_at, news_article.created_at,
            news_article.author_name, news_article.author_url, news_article.author_followers,
            news_article.sentiment, news_article.confidence, news_article.read_grade,
            news_article.tickers, news_article.drivers, news_article.key_dates, news_article.tags, news_article.recap, news_article.raw_json
        FROM news_article' . $read_join . '
        WHERE 1=1' . $read_where;

        $params = [];
        
        if ($user_id !== null && $read_status !== 'all') {
            $params['read_user_id'] = $user_id;
        }

        // Filter by ticker (search in tickers JSON column)
        if ($ticker !== null && $ticker !== '') {
            $sql .= ' AND JSON_SEARCH(news_article.tickers, "one", :ticker, NULL, "$[*]") IS NOT NULL';
            $params['ticker'] = strtoupper(trim($ticker));
        }

        // Filter by holdings tickers (show only news for tickers in user's holdings)
        if ($holdings_tickers !== null && !empty($holdings_tickers)) {
            // Normalize holdings tickers to uppercase
            $normalized_holdings = array_map(function($t) { return strtoupper(trim($t)); }, $holdings_tickers);
            $normalized_holdings = array_filter($normalized_holdings, function($t) { return $t !== ''; });
            
            if (!empty($normalized_holdings)) {
                // Use JSON_OVERLAPS (MySQL 8.0.17+) to check if news tickers overlap with holdings tickers
                // JSON_OVERLAPS returns true if two JSON documents have any matching values
                $holdings_json = json_encode(array_values($normalized_holdings));
                $sql .= ' AND JSON_OVERLAPS(news_article.tickers, :holdings_tickers_json)';
                $params['holdings_tickers_json'] = $holdings_json;
            }
        }

        // Filter by watchlist tickers (show only news for tickers in user's watchlists)
        if ($watchlist_tickers !== null && !empty($watchlist_tickers)) {
            // Normalize watchlist tickers to uppercase
            $normalized_watchlist = array_map(function($t) { return strtoupper(trim($t)); }, $watchlist_tickers);
            $normalized_watchlist = array_filter($normalized_watchlist, function($t) { return $t !== ''; });
            
            if (!empty($normalized_watchlist)) {
                // Use JSON_OVERLAPS to check if news tickers overlap with watchlist tickers
                $watchlist_json = json_encode(array_values($normalized_watchlist));
                $sql .= ' AND JSON_OVERLAPS(news_article.tickers, :watchlist_tickers_json)';
                $params['watchlist_tickers_json'] = $watchlist_json;
            }
        }

        // Filter by sentiment
        if ($sentiment !== null && $sentiment !== '' && $sentiment !== 'all') {
            $sql .= ' AND news_article.sentiment = :sentiment';
            $params['sentiment'] = $sentiment;
        }

        // Filter by min confidence
        if ($min_confidence !== null) {
            $sql .= ' AND news_article.confidence >= :min_confidence';
            $params['min_confidence'] = $min_confidence;
        }

        // Filter by min read grade
        if ($min_read_grade !== null) {
            $sql .= ' AND news_article.read_grade >= :min_read_grade';
            $params['min_read_grade'] = $min_read_grade;
        }

        // Sort
        switch ($sort) {
            case 'published_desc':
                $sql .= ' ORDER BY news_article.published_at DESC, news_article.captured_at DESC';
                break;
            case 'confidence_desc':
                $sql .= ' ORDER BY news_article.confidence DESC, news_article.captured_at DESC';
                break;
            case 'read_grade_desc':
                $sql .= ' ORDER BY news_article.read_grade DESC, news_article.confidence DESC, news_article.captured_at DESC';
                break;
            case 'captured_desc':
            default:
                $sql .= ' ORDER BY news_article.captured_at DESC';
                break;
        }

        // Pagination
        $sql .= ' LIMIT :limit OFFSET :offset';
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            if ($key === 'limit' || $key === 'offset') {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } elseif ($key === 'holdings_tickers_json' || $key === 'watchlist_tickers_json') {
                // JSON parameter needs to be bound as string
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':' . $key, $value);
            }
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $items[] = new NewsArticle(
                (int) $row['id'],
                $row['source'],
                $row['url'],
                $row['title'],
                $row['published_at'],
                $row['captured_at'],
                $row['created_at'],
                $row['author_name'],
                $row['author_url'],
                $row['author_followers'] ? (int) $row['author_followers'] : null,
                $row['sentiment'],
                (int) $row['confidence'],
                (int) $row['read_grade'],
                json_decode($row['tickers'], true) ?? [],
                json_decode($row['drivers'], true) ?? [],
                json_decode($row['key_dates'], true) ?? [],
                json_decode($row['tags'], true) ?? [],
                $row['recap'],
                json_decode($row['raw_json'], true) ?? []
            );
        }

        // Get total count
        $count_read_join = '';
        $count_read_where = '';
        if ($user_id !== null && $read_status !== 'all') {
            if ($read_status === 'read') {
                $count_read_join = ' INNER JOIN news_read nr ON news_article.id = nr.news_article_id AND nr.user_id = :read_user_id';
            } elseif ($read_status === 'unread') {
                $count_read_join = ' LEFT JOIN news_read nr ON news_article.id = nr.news_article_id AND nr.user_id = :read_user_id';
                $count_read_where = ' AND nr.news_article_id IS NULL';
            }
        }
        
        $count_sql = 'SELECT COUNT(*) as total FROM news_article' . $count_read_join . ' WHERE 1=1' . $count_read_where;
        $count_params = [];
        
        if ($user_id !== null && $read_status !== 'all') {
            $count_params['read_user_id'] = $user_id;
        }
        
        if ($ticker !== null && $ticker !== '') {
            $count_sql .= ' AND JSON_SEARCH(news_article.tickers, "one", :ticker, NULL, "$[*]") IS NOT NULL';
            $count_params['ticker'] = strtoupper(trim($ticker));
        }
        if ($sentiment !== null && $sentiment !== '' && $sentiment !== 'all') {
            $count_sql .= ' AND news_article.sentiment = :sentiment';
            $count_params['sentiment'] = $sentiment;
        }
        if ($min_confidence !== null) {
            $count_sql .= ' AND news_article.confidence >= :min_confidence';
            $count_params['min_confidence'] = $min_confidence;
        }
        if ($min_read_grade !== null) {
            $count_sql .= ' AND news_article.read_grade >= :min_read_grade';
            $count_params['min_read_grade'] = $min_read_grade;
        }
        if ($holdings_tickers !== null && !empty($holdings_tickers)) {
            $normalized_holdings = array_map(function($t) { return strtoupper(trim($t)); }, $holdings_tickers);
            $normalized_holdings = array_filter($normalized_holdings, function($t) { return $t !== ''; });
            if (!empty($normalized_holdings)) {
                $holdings_json = json_encode(array_values($normalized_holdings));
                $count_sql .= ' AND JSON_OVERLAPS(news_article.tickers, :holdings_tickers_json)';
                $count_params['holdings_tickers_json'] = $holdings_json;
            }
        }
        if ($watchlist_tickers !== null && !empty($watchlist_tickers)) {
            $normalized_watchlist = array_map(function($t) { return strtoupper(trim($t)); }, $watchlist_tickers);
            $normalized_watchlist = array_filter($normalized_watchlist, function($t) { return $t !== ''; });
            if (!empty($normalized_watchlist)) {
                $watchlist_json = json_encode(array_values($normalized_watchlist));
                $count_sql .= ' AND JSON_OVERLAPS(news_article.tickers, :watchlist_tickers_json)';
                $count_params['watchlist_tickers_json'] = $watchlist_json;
            }
        }

        $count_stmt = $this->db->prepare($count_sql);
        foreach ($count_params as $key => $value) {
            if ($key === 'holdings_tickers_json' || $key === 'watchlist_tickers_json') {
                // JSON parameter needs to be bound as string
                $count_stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
            } else {
                $count_stmt->bindValue(':' . $key, $value);
            }
        }
        $count_stmt->execute();
        $count_row = $count_stmt->fetch(PDO::FETCH_ASSOC);
        $total = (int) ($count_row['total'] ?? 0);

        return ['items' => $items, 'total' => $total];
    }

    public function find_by_id(int $id): ?NewsArticle
    {
        $stmt = $this->db->prepare('
            SELECT 
                id, source, url, url_hash, title, 
                published_at, captured_at, created_at,
                author_name, author_url, author_followers,
                sentiment, confidence, read_grade,
                tickers, drivers, key_dates, tags, recap, raw_json
            FROM news_article
            WHERE id = :id
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new NewsArticle(
            (int) $row['id'],
            $row['source'],
            $row['url'],
            $row['title'],
            $row['published_at'],
            $row['captured_at'],
            $row['created_at'],
            $row['author_name'],
            $row['author_url'],
            $row['author_followers'] ? (int) $row['author_followers'] : null,
            $row['sentiment'],
            (int) $row['confidence'],
            (int) $row['read_grade'],
            json_decode($row['tickers'], true) ?? [],
            json_decode($row['drivers'], true) ?? [],
            json_decode($row['key_dates'], true) ?? [],
            json_decode($row['tags'], true) ?? [],
            $row['recap'],
            json_decode($row['raw_json'], true) ?? []
        );
    }

    public function create_or_update(array $data): int
    {
        // Calculate URL hash in PHP to avoid parameter reuse issue
        $url_hash = hex2bin(hash('sha256', $data['url']));

        $stmt = $this->db->prepare('
            INSERT INTO news_article (
                source, url, url_hash, title,
                published_at, captured_at,
                author_name, author_url, author_followers,
                sentiment, confidence, read_grade,
                tickers, drivers, key_dates, tags, recap, raw_json
            ) VALUES (
                :source, :url, :url_hash, :title,
                :published_at, :captured_at,
                :author_name, :author_url, :author_followers,
                :sentiment, :confidence, :read_grade,
                :tickers, :drivers, :key_dates, :tags, :recap, :raw_json
            )
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                published_at = VALUES(published_at),
                captured_at = VALUES(captured_at),
                author_name = VALUES(author_name),
                author_url = VALUES(author_url),
                author_followers = VALUES(author_followers),
                sentiment = VALUES(sentiment),
                confidence = VALUES(confidence),
                read_grade = VALUES(read_grade),
                tickers = VALUES(tickers),
                drivers = VALUES(drivers),
                key_dates = VALUES(key_dates),
                tags = VALUES(tags),
                recap = VALUES(recap),
                raw_json = VALUES(raw_json)
        ');

        $stmt->execute([
            'source' => $data['source'],
            'url' => $data['url'],
            'url_hash' => $url_hash,
            'title' => $data['title'],
            'published_at' => $data['published_at'] ?? null,
            'captured_at' => $data['captured_at'],
            'author_name' => $data['author_name'] ?? null,
            'author_url' => $data['author_url'] ?? null,
            'author_followers' => $data['author_followers'] ?? null,
            'sentiment' => $data['sentiment'],
            'confidence' => $data['confidence'],
            'read_grade' => $data['read_grade'],
            'tickers' => json_encode($data['tickers'] ?? []),
            'drivers' => json_encode($data['drivers']),
            'key_dates' => json_encode($data['key_dates']),
            'tags' => json_encode($data['tags']),
            'recap' => $data['recap'],
            'raw_json' => json_encode($data['raw_json']),
        ]);

        // If it was an update, we need to find the ID
        if ($stmt->rowCount() === 0) {
            // It was an update, find the ID by url_hash
            $find_stmt = $this->db->prepare('
                SELECT id FROM news_article 
                WHERE source = :source AND url_hash = :url_hash
            ');
            $find_stmt->execute([
                'source' => $data['source'],
                'url_hash' => $url_hash,
            ]);
            $row = $find_stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (int) $row['id'] : 0;
        }

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get sentiment counts for a ticker from news articles in the last N days.
     * Returns array with counts: ['bullish' => int, 'bearish' => int, 'neutral' => int, 'mixed' => int]
     */
    public function get_sentiment_counts_days(string $ticker, int $days): array
    {
        $ticker_upper = strtoupper(trim($ticker));
        
        // Calculate date N days ago
        $days_ago = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $stmt = $this->db->prepare('
            SELECT sentiment, COUNT(*) as count
            FROM news_article
            WHERE JSON_SEARCH(tickers, "one", :ticker, NULL, "$[*]") IS NOT NULL
            AND published_at >= :days_ago
            AND published_at IS NOT NULL
            GROUP BY sentiment
        ');
        $stmt->execute([
            'ticker' => $ticker_upper,
            'days_ago' => $days_ago,
        ]);
        
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Initialize counts
        $counts = [
            'bullish' => 0,
            'bearish' => 0,
            'neutral' => 0,
            'mixed' => 0,
        ];
        
        // Fill in actual counts
        foreach ($rows as $row) {
            $sentiment = $row['sentiment'];
            if (isset($counts[$sentiment])) {
                $counts[$sentiment] = (int) $row['count'];
            }
        }
        
        return $counts;
    }

    /**
     * Get sentiment counts for a ticker from news articles in the last month (30 days).
     * Returns array with counts: ['bullish' => int, 'bearish' => int, 'neutral' => int, 'mixed' => int]
     */
    public function get_sentiment_counts_last_month(string $ticker): array
    {
        return $this->get_sentiment_counts_days($ticker, 30);
    }
}

