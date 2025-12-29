<?php

declare(strict_types=1);

class NewsArticle
{
    public function __construct(
        public readonly int $id,
        public readonly string $source,
        public readonly string $url,
        public readonly string $title,
        public readonly ?string $published_at,
        public readonly string $captured_at,
        public readonly string $created_at,
        public readonly ?string $author_name,
        public readonly ?string $author_url,
        public readonly ?int $author_followers,
        public readonly string $sentiment,
        public readonly int $confidence,
        public readonly int $read_grade,
        public readonly array $tickers,
        public readonly array $drivers,
        public readonly array $key_dates,
        public readonly array $tags,
        public readonly string $recap,
        public readonly array $raw_json
    ) {
    }
}

