<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\DividendExportService;

final class ExportController
{
    public function __construct(
        private DividendExportService $exportService,
        private AuthService $authService,
    ) {
    }

    public function exportDohDiv(int $year): string
    {
        $userId = $this->authService->requireUserId();
        return $this->exportService->exportForYear($userId, $year);
    }
}
