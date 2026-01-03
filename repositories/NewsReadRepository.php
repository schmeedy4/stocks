<?php

declare(strict_types=1);

class NewsReadRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::get_connection();
    }

    /**
     * Check if a user has read a news article.
     */
    public function is_read(int $user_id, int $news_article_id): bool
    {
        $stmt = $this->db->prepare('
            SELECT 1
            FROM news_read
            WHERE user_id = :user_id AND news_article_id = :news_article_id
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'news_article_id' => $news_article_id,
        ]);
        
        return $stmt->fetch() !== false;
    }

    /**
     * Get all read article IDs for a user.
     * Returns array of news_article_id => true for fast lookup.
     */
    public function get_read_article_ids(int $user_id): array
    {
        $stmt = $this->db->prepare('
            SELECT news_article_id
            FROM news_read
            WHERE user_id = :user_id
        ');
        $stmt->execute(['user_id' => $user_id]);
        
        $read_ids = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $read_ids[(int) $row['news_article_id']] = true;
        }
        
        return $read_ids;
    }

    /**
     * Mark an article as read for a user.
     */
    public function mark_as_read(int $user_id, int $news_article_id): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO news_read (user_id, news_article_id, read_at)
            VALUES (:user_id, :news_article_id, NOW())
            ON DUPLICATE KEY UPDATE read_at = NOW()
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'news_article_id' => $news_article_id,
        ]);
    }

    /**
     * Mark an article as unread for a user.
     */
    public function mark_as_unread(int $user_id, int $news_article_id): void
    {
        $stmt = $this->db->prepare('
            DELETE FROM news_read
            WHERE user_id = :user_id AND news_article_id = :news_article_id
        ');
        $stmt->execute([
            'user_id' => $user_id,
            'news_article_id' => $news_article_id,
        ]);
    }

    /**
     * Toggle read status for an article.
     * Returns true if marked as read, false if marked as unread.
     */
    public function toggle_read(int $user_id, int $news_article_id): bool
    {
        if ($this->is_read($user_id, $news_article_id)) {
            $this->mark_as_unread($user_id, $news_article_id);
            return false;
        } else {
            $this->mark_as_read($user_id, $news_article_id);
            return true;
        }
    }
}

