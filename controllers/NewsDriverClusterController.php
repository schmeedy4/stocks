<?php

declare(strict_types=1);

class NewsDriverClusterController
{
    private NewsDriverClusterRepository $cluster_repo;

    public function __construct()
    {
        require_once __DIR__ . '/../infrastructure/auth.php';
        require_auth();

        $this->cluster_repo = new NewsDriverClusterRepository();
    }

    public function list(): void
    {
        $user_id = current_user_id();
        if ($user_id === null) {
            header('Location: ?action=login');
            exit;
        }

        // Handle sync POST request
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync'])) {
            $inserted_count = $this->cluster_repo->sync_from_news();
            $_SESSION['cluster_sync_result'] = [
                'success' => true,
                'inserted_count' => $inserted_count,
            ];
            header('Location: ?action=news_driver_clusters');
            exit;
        }

        // Get clusters with usage counts
        $clusters = $this->cluster_repo->list_with_counts();

        // Get sync result message if available
        $sync_result = $_SESSION['cluster_sync_result'] ?? null;
        unset($_SESSION['cluster_sync_result']);

        require __DIR__ . '/../views/news_driver_clusters/list.php';
    }
}

