<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AppSettings;
use PDO;

final class AppSettingsRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByUserId(int $userId): ?AppSettings
    {
        $stmt = $this->pdo->prepare('SELECT * FROM app_settings WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return new AppSettings(
            userId: (int) $row['user_id'],
            taxPayerId: $row['tax_payer_id'],
            taxPayerType: $row['tax_payer_type'],
            documentWorkflowId: $row['document_workflow_id'],
            baseCurrency: $row['base_currency'],
        );
    }
}
