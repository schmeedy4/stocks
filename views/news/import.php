<?php

$page_title = 'Import News';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Import News JSON</h1>
</div>

<?php if (!empty($errors)): ?>
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        <ul class="list-disc list-inside">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars(is_array($error) ? implode(', ', $error) : $error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="?action=news_import_post">
        <div class="mb-4">
            <label for="json" class="block text-sm font-medium text-gray-700 mb-2">
                Paste JSON Payload
            </label>
            <textarea 
                id="json" 
                name="json" 
                rows="20"
                class="w-full px-3 py-2 border border-gray-300 rounded-md font-mono text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder='{
  "source": "example",
  "url": "https://example.com/article",
  "title": "Article Title",
  "captured_at": "2024-01-01 12:00:00",
  "sentiment": "bullish",
  "confidence": 85,
  "read_grade": 4,
  "drivers": [],
  "key_dates": [],
  "tags": [],
  "recap": "Article summary..."
}'
            ><?= htmlspecialchars($old_input) ?></textarea>
            <?php if (isset($errors['json'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['json']) ?></p>
            <?php endif; ?>
        </div>

        <div class="flex gap-2">
            <button 
                type="submit" 
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                Validate & Save
            </button>
            <a 
                href="?action=news" 
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
            >
                Cancel
            </a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

