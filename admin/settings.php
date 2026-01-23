<?php
require_once __DIR__ . '/common.php';
checkAuth();

$db = getDB();
$message = '';
$error = '';

// Get all settings
$settings = $db->fetchAll("SELECT `key`, value FROM settings");
$settingsMap = [];
foreach ($settings as $setting) {
    $settingsMap[$setting['key']] = $setting['value'];
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST as $key => $value) {
            if ($key !== 'submit') {
                // Check if setting exists
                $existing = $db->fetchOne("SELECT `key` FROM settings WHERE `key` = ?", [$key]);
                if ($existing) {
                    $db->update('settings', ['value' => $value], '`key` = ?', [$key]);
                } else {
                    $db->insert('settings', ['key' => $key, 'value' => $value]);
                }
            }
        }
        $message = 'Настройки сохранены';
        
        // Reload settings
        $settings = $db->fetchAll("SELECT `key`, value FROM settings");
        $settingsMap = [];
        foreach ($settings as $setting) {
            $settingsMap[$setting['key']] = $setting['value'];
        }
    } catch (Exception $e) {
        $error = 'Ошибка: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки системы</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <h1 class="mb-4">Настройки системы</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= h($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?= h($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-4">
                        <h5>YouTube API</h5>
                        <div class="mb-3">
                            <label class="form-label">API Key</label>
                            <input type="text" name="youtube_api_key" class="form-control" 
                                   value="<?= h($settingsMap['youtube_api_key'] ?? '') ?>" 
                                   placeholder="Введите YouTube Data API v3 ключ"
                                   required>
                            <small class="form-text text-muted">
                                Получить ключ: <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>
                            </small>
                            <?php if (!empty($settingsMap['youtube_api_key'])): ?>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="testApiKey()">
                                        <i class="bi bi-play-circle"></i> Проверить ключ
                                    </button>
                                    <span id="apiKeyTestResult" class="ms-2"></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Системные пути</h5>
                        <div class="mb-3">
                            <label class="form-label">Путь к yt-dlp</label>
                            <input type="text" name="yt_dlp_path" class="form-control" 
                                   value="<?= h($settingsMap['yt_dlp_path'] ?? '/usr/local/bin/yt-dlp') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Путь к ffmpeg</label>
                            <input type="text" name="ffmpeg_path" class="form-control" 
                                   value="<?= h($settingsMap['ffmpeg_path'] ?? '/usr/bin/ffmpeg') ?>">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Параметры поиска</h5>
                        <div class="mb-3">
                            <label class="form-label">Максимальная длительность Shorts (секунды)</label>
                            <input type="number" name="shorts_max_duration" class="form-control" 
                                   value="<?= h($settingsMap['shorts_max_duration'] ?? '60') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Максимальный возраст видео (часы)</label>
                            <input type="number" name="shorts_max_age_hours" class="form-control" 
                                   value="<?= h($settingsMap['shorts_max_age_hours'] ?? '48') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Лимит по умолчанию за запуск</label>
                            <input type="number" name="default_limit_per_run" class="form-control" 
                                   value="<?= h($settingsMap['default_limit_per_run'] ?? '10') ?>">
                        </div>
                    </div>
                    
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Сохранить настройки
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Информация о системе</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li><strong>PHP версия:</strong> <?= PHP_VERSION ?></li>
                    <li><strong>Путь к проекту:</strong> <?= BASE_PATH ?></li>
                    <li><strong>Путь к хранилищу:</strong> <?= FRAMES_PATH ?></li>
                    <li><strong>Временная зона:</strong> <?= TIMEZONE ?></li>
                    <li><strong>Лимит памяти:</strong> <?= MEMORY_LIMIT ?></li>
                    <li><strong>Макс. время выполнения:</strong> <?= MAX_EXECUTION_TIME ?> сек</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testApiKey() {
            const apiKey = document.querySelector('input[name="youtube_api_key"]').value;
            const resultSpan = document.getElementById('apiKeyTestResult');
            
            if (!apiKey) {
                resultSpan.innerHTML = '<span class="text-danger">Введите ключ</span>';
                return;
            }
            
            resultSpan.innerHTML = '<span class="text-info">Проверка...</span>';
            
            // Test API key with a simple request
            const testUrl = 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id=dQw4w9WgXcQ&key=' + apiKey;
            
            fetch(testUrl)
                .then(response => {
                    if (response.ok) {
                        resultSpan.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Ключ работает</span>';
                    } else {
                        resultSpan.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> Ошибка: ' + response.status + '</span>';
                    }
                })
                .catch(error => {
                    resultSpan.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> Ошибка проверки</span>';
                });
        }
    </script>
</body>
</html>
