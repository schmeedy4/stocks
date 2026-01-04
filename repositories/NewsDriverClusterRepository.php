<?php

declare(strict_types=1);

class NewsDriverClusterRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get_connection();
    }

    /**
     * Sync cluster_keys from news_article.drivers JSON into news_driver_cluster registry.
     * Extracts unique cluster_keys from all news articles and inserts missing ones.
     * Uses the first available title from drivers for each cluster_key.
     */
    public function sync_from_news(): int
    {
        // Extract all unique cluster_keys with their titles from news_article.drivers JSON
        // Using JSON_TABLE to extract cluster_key and title from each driver
        // We use MIN(title) to get the first title for each cluster_key
        $sql = '
            INSERT IGNORE INTO news_driver_cluster (cluster_key, title, is_active)
            SELECT 
                cluster_key COLLATE utf8mb4_unicode_ci,
                COALESCE(
                    NULLIF(MIN(driver_title), ""),
                    cluster_key
                ) COLLATE utf8mb4_unicode_ci as title,
                1 as is_active
            FROM (
                SELECT DISTINCT
                    jt.cluster_key,
                    jt.driver_title
                FROM news_article
                CROSS JOIN JSON_TABLE(
                    news_article.drivers,
                    "$[*]" COLUMNS (
                        cluster_key VARCHAR(64) PATH "$.cluster_key",
                        driver_title VARCHAR(120) PATH "$.title"
                    )
                ) AS jt
                WHERE jt.cluster_key IS NOT NULL
                  AND jt.cluster_key != ""
                  AND JSON_VALID(news_article.drivers) = 1
            ) AS extracted
            GROUP BY cluster_key
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * List all clusters with usage counts (how many times each cluster_key appears in news articles).
     * @return array Array of arrays with keys: id, cluster_key, title, description, is_active, created_at, usage_count
     */
    public function list_with_counts(): array
    {
        $sql = '
            SELECT 
                ndc.id,
                ndc.cluster_key,
                ndc.title,
                ndc.description,
                ndc.is_active,
                ndc.created_at,
                COALESCE(`usage`.usage_count, 0) as usage_count
            FROM news_driver_cluster ndc
            LEFT JOIN (
                SELECT 
                    jt.cluster_key COLLATE utf8mb4_unicode_ci as cluster_key,
                    COUNT(*) as usage_count
                FROM news_article
                CROSS JOIN JSON_TABLE(
                    news_article.drivers,
                    "$[*]" COLUMNS (
                        cluster_key VARCHAR(64) PATH "$.cluster_key"
                    )
                ) AS jt
                WHERE jt.cluster_key IS NOT NULL
                  AND jt.cluster_key != ""
                  AND JSON_VALID(news_article.drivers) = 1
                GROUP BY jt.cluster_key COLLATE utf8mb4_unicode_ci
            ) AS `usage` ON ndc.cluster_key = `usage`.cluster_key
            ORDER BY ndc.cluster_key ASC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[] = [
                'id' => (int) $row['id'],
                'cluster_key' => $row['cluster_key'],
                'title' => $row['title'],
                'description' => $row['description'],
                'is_active' => (bool) $row['is_active'],
                'created_at' => $row['created_at'],
                'usage_count' => (int) $row['usage_count'],
            ];
        }

        return $results;
    }

    /**
     * Upsert cluster_keys from a drivers array into news_driver_cluster registry.
     * Extracts cluster_keys from drivers and inserts missing ones.
     * @param array $drivers Array of driver objects, each with optional cluster_key and title
     */
    public function upsert_from_drivers(array $drivers): void
    {
        if (empty($drivers)) {
            return;
        }

        $cluster_keys_to_insert = [];

        foreach ($drivers as $driver) {
            if (!is_array($driver)) {
                continue;
            }

            // Extract and validate cluster_key
            $cluster_key = isset($driver['cluster_key']) ? trim((string) $driver['cluster_key']) : '';
            
            // Skip if empty or invalid
            if ($cluster_key === '') {
                continue;
            }

            // Validate snake_case pattern (letters, numbers, underscores)
            if (!preg_match('/^[a-z0-9_]+$/', strtolower($cluster_key))) {
                continue;
            }

            // Normalize to lowercase for consistency
            $cluster_key = strtolower($cluster_key);

            // Extract title from driver if available, otherwise use cluster_key
            $title = isset($driver['title']) && trim((string) $driver['title']) !== '' 
                ? trim((string) $driver['title']) 
                : $cluster_key;

            // Limit title length to 120 characters (schema constraint)
            if (strlen($title) > 120) {
                $title = substr($title, 0, 120);
            }

            // Store for batch insert (avoid duplicates in same batch)
            if (!isset($cluster_keys_to_insert[$cluster_key])) {
                $cluster_keys_to_insert[$cluster_key] = $title;
            }
        }

        if (empty($cluster_keys_to_insert)) {
            return;
        }

        // Batch insert using INSERT IGNORE (idempotent)
        $sql = '
            INSERT IGNORE INTO news_driver_cluster (cluster_key, title, is_active)
            VALUES (:cluster_key, :title, 1)
        ';

        $stmt = $this->db->prepare($sql);

        foreach ($cluster_keys_to_insert as $cluster_key => $title) {
            $stmt->execute([
                'cluster_key' => $cluster_key,
                'title' => $title,
            ]);
        }
    }
}

