<?php

declare(strict_types=1);

class NewsArticleService
{
    private NewsArticleRepository $news_repo;

    public function __construct()
    {
        $this->news_repo = new NewsArticleRepository();
    }

    /**
     * @return array{items: array, total: int, page: int, limit: int}
     */
    public function search(
        ?string $ticker = null,
        ?string $sentiment = null,
        ?int $min_confidence = null,
        ?int $min_read_grade = null,
        string $sort = 'captured_desc',
        int $page = 1,
        int $limit = 25
    ): array {
        $result = $this->news_repo->search(
            $ticker,
            $sentiment,
            $min_confidence,
            $min_read_grade,
            $sort,
            $page,
            $limit
        );

        return [
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function get(int $id): NewsArticle
    {
        $article = $this->news_repo->find_by_id($id);
        if ($article === null) {
            throw new NotFoundException('News article not found');
        }
        return $article;
    }

    public function import(array $json_data): int
    {
        $errors = $this->validate_import($json_data);
        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        // Normalize date formats (ISO 8601 to MySQL DATETIME)
        $captured_at = $this->normalize_datetime($json_data['captured_at']);
        $published_at = null;
        if (isset($json_data['published_at']) && $json_data['published_at'] !== '') {
            $published_at = $this->normalize_datetime($json_data['published_at']);
        }

        // Normalize tickers: uppercase, trim, dedupe (save all tickers)
        $tickers = $this->normalize_tickers($json_data['tickers'] ?? []);

        // Normalize URLs: extract plain URL from markdown format [text](url) if present
        $url = $this->normalize_url($json_data['url'] ?? '');
        $author_url = null;
        if (isset($json_data['author_url']) && $json_data['author_url'] !== '') {
            $author_url = $this->normalize_url($json_data['author_url']);
        }

        // Normalize the data with defaults for missing analysis fields (for raw article imports)
        $data = [
            'source' => $json_data['source'],
            'url' => $url,
            'title' => $json_data['title'],
            'captured_at' => $captured_at,
            'published_at' => $published_at,
            'sentiment' => $json_data['sentiment'] ?? 'neutral',
            'confidence' => isset($json_data['confidence']) ? (int) $json_data['confidence'] : 0,
            'read_grade' => isset($json_data['read_grade']) ? (int) $json_data['read_grade'] : 1,
            'tickers' => $tickers,
            'drivers' => $json_data['drivers'] ?? [],
            'key_dates' => $json_data['key_dates'] ?? [],
            'tags' => $json_data['tags'] ?? [],
            'recap' => $json_data['recap'] ?? ($json_data['snippet'] ?? ''),
            'raw_json' => $json_data,
        ];

        // Optional fields
        if (isset($json_data['author_name']) && $json_data['author_name'] !== '') {
            $data['author_name'] = $json_data['author_name'];
        }
        if ($author_url !== null) {
            $data['author_url'] = $author_url;
        }
        if (isset($json_data['author_followers'])) {
            $data['author_followers'] = (int) $json_data['author_followers'];
        }

        return $this->news_repo->create_or_update($data);
    }

    /**
     * Convert ISO 8601 date string to MySQL DATETIME format (YYYY-MM-DD HH:MM:SS)
     */
    private function normalize_datetime(string $date_string): string
    {
        try {
            $dt = new \DateTime($date_string);
            return $dt->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // If parsing fails, try to extract basic format
            // Remove timezone and milliseconds if present
            $cleaned = preg_replace('/[TZ].*$/', '', $date_string);
            $cleaned = preg_replace('/\.\d+/', '', $cleaned);
            // Try to parse again
            try {
                $dt = new \DateTime($cleaned);
                return $dt->format('Y-m-d H:i:s');
            } catch (\Exception $e2) {
                // Last resort: return as-is if it looks like MySQL format already
                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $cleaned)) {
                    return $cleaned;
                }
                // If all parsing fails, throw validation exception with proper format
                throw new ValidationException('Invalid date format: ' . $date_string, ['date' => 'Invalid date format: ' . $date_string]);
            }
        }
    }

    /**
     * Normalize tickers: uppercase, trim, remove duplicates
     * @param array $tickers Raw ticker array from JSON
     * @return array Normalized ticker array (all tickers, no limit)
     */
    private function normalize_tickers(array $tickers): array
    {
        if (empty($tickers)) {
            return [];
        }

        $normalized = [];
        $seen = [];

        foreach ($tickers as $ticker) {
            if (!is_string($ticker)) {
                continue;
            }

            $ticker = strtoupper(trim($ticker));
            
            // Skip empty strings
            if ($ticker === '') {
                continue;
            }

            // Skip duplicates
            if (isset($seen[$ticker])) {
                continue;
            }

            $seen[$ticker] = true;
            $normalized[] = $ticker;
        }

        return $normalized;
    }

    /**
     * Normalize URL: extract plain URL from markdown format [text](url) if present
     * @param string $url Raw URL that might be in markdown format
     * @return string Plain URL
     */
    private function normalize_url(string $url): string
    {
        // If URL is in markdown format [text](url), extract the URL part
        if (preg_match('/\[.*?\]\((.*?)\)/', $url, $matches)) {
            return $matches[1];
        }
        
        // Return as-is if not markdown format
        return trim($url);
    }

    private function validate_import(array $data): array
    {
        $errors = [];

        // Required fields (core article data)
        $required = ['source', 'url', 'title', 'captured_at'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }

        // Validate sentiment enum
        if (isset($data['sentiment'])) {
            $valid_sentiments = ['bullish', 'bearish', 'neutral', 'mixed'];
            if (!in_array($data['sentiment'], $valid_sentiments, true)) {
                $errors['sentiment'] = 'Sentiment must be one of: ' . implode(', ', $valid_sentiments);
            }
        }

        // Validate confidence (0-100)
        if (isset($data['confidence'])) {
            $confidence = (int) $data['confidence'];
            if ($confidence < 0 || $confidence > 100) {
                $errors['confidence'] = 'Confidence must be between 0 and 100';
            }
        }

        // Validate read_grade (1-5)
        if (isset($data['read_grade'])) {
            $read_grade = (int) $data['read_grade'];
            if ($read_grade < 1 || $read_grade > 5) {
                $errors['read_grade'] = 'Read grade must be between 1 and 5';
            }
        }

        // Validate arrays
        if (isset($data['drivers']) && !is_array($data['drivers'])) {
            $errors['drivers'] = 'Drivers must be an array';
        }
        if (isset($data['key_dates']) && !is_array($data['key_dates'])) {
            $errors['key_dates'] = 'Key dates must be an array';
        }
        if (isset($data['tags']) && !is_array($data['tags'])) {
            $errors['tags'] = 'Tags must be an array';
        }

        return $errors;
    }
}

