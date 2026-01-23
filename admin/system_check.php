<?php
/**
 * System Check Script
 * Проверяет готовность системы к работе
 */

require_once __DIR__ . '/common.php';
checkAuth();

$db = getDB();
$checks = [];
$allPassed = true;

// Check 1: Database connection
$checks['database'] = [
    'name' => 'Подключение к базе данных',
    'status' => 'success',
    'message' => 'OK'
];

if (!$db) {
    $checks['database']['status'] = 'error';
    $checks['database']['message'] = 'Не удалось подключиться к БД';
    $allPassed = false;
} else {
    try {
        $db->query("SELECT 1");
    } catch (Exception $e) {
        $checks['database']['status'] = 'error';
        $checks['database']['message'] = 'Ошибка: ' . $e->getMessage();
        $allPassed = false;
    }
}

// Check 2: YouTube API Key
$checks['youtube_api'] = [
    'name' => 'YouTube API ключ',
    'status' => 'warning',
    'message' => 'Не настроен'
];

if ($db) {
    try {
        $apiKey = $db->fetchOne("SELECT value FROM settings WHERE `key` = 'youtube_api_key'");
        if ($apiKey && !empty($apiKey['value'])) {
            $checks['youtube_api']['status'] = 'success';
            $checks['youtube_api']['message'] = 'Настроен (' . substr($apiKey['value'], 0, 10) . '...)';
            
            // Test API key
            try {
                require_once __DIR__ . '/../core/YouTube.php';
                $youtube = new YouTube();
                $youtube->setApiKey($apiKey['value']);
                
                // Simple test request
                $testUrl = 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id=dQw4w9WgXcQ&key=' . $apiKey['value'];
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $testUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 5
                ]);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    $checks['youtube_api']['message'] .= ' - Работает';
                } else {
                    $checks['youtube_api']['status'] = 'error';
                    $checks['youtube_api']['message'] .= ' - Ошибка HTTP ' . $httpCode;
                    $allPassed = false;
                }
            } catch (Exception $e) {
                $checks['youtube_api']['status'] = 'warning';
                $checks['youtube_api']['message'] .= ' - Не удалось проверить';
            }
        } else {
            $allPassed = false;
        }
    } catch (Exception $e) {
        $checks['youtube_api']['status'] = 'error';
        $checks['youtube_api']['message'] = 'Ошибка проверки: ' . $e->getMessage();
        $allPassed = false;
    }
}

// Check 3: yt-dlp
$checks['yt_dlp'] = [
    'name' => 'yt-dlp',
    'status' => 'warning',
    'message' => 'Не найден'
];

$ytDlpPath = '/usr/local/bin/yt-dlp';
if ($db) {
    try {
        $setting = $db->fetchOne("SELECT value FROM settings WHERE `key` = 'yt_dlp_path'");
        if ($setting && !empty($setting['value'])) {
            $ytDlpPath = $setting['value'];
        }
    } catch (Exception $e) {
        // Use default
    }
}

if (file_exists($ytDlpPath) && is_executable($ytDlpPath)) {
    $checks['yt_dlp']['status'] = 'success';
    $checks['yt_dlp']['message'] = 'Найден: ' . $ytDlpPath;
    
    // Test version
    $output = [];
    exec(escapeshellarg($ytDlpPath) . ' --version 2>&1', $output, $returnVar);
    if ($returnVar === 0 && !empty($output)) {
        $checks['yt_dlp']['message'] .= ' (версия: ' . trim($output[0]) . ')';
    }
} else {
    $checks['yt_dlp']['status'] = 'error';
    $checks['yt_dlp']['message'] = 'Не найден по пути: ' . $ytDlpPath;
    $allPassed = false;
}

// Check 4: ffmpeg
$checks['ffmpeg'] = [
    'name' => 'ffmpeg',
    'status' => 'warning',
    'message' => 'Не найден'
];

$ffmpegPath = '/usr/bin/ffmpeg';
if ($db) {
    try {
        $setting = $db->fetchOne("SELECT value FROM settings WHERE `key` = 'ffmpeg_path'");
        if ($setting && !empty($setting['value'])) {
            $ffmpegPath = $setting['value'];
        }
    } catch (Exception $e) {
        // Use default
    }
}

if (file_exists($ffmpegPath) && is_executable($ffmpegPath)) {
    $checks['ffmpeg']['status'] = 'success';
    $checks['ffmpeg']['message'] = 'Найден: ' . $ffmpegPath;
    
    // Test version
    $output = [];
    exec(escapeshellarg($ffmpegPath) . ' -version 2>&1 | head -1', $output, $returnVar);
    if ($returnVar === 0 && !empty($output)) {
        $checks['ffmpeg']['message'] .= ' (версия: ' . trim($output[0]) . ')';
    }
} else {
    $checks['ffmpeg']['status'] = 'error';
    $checks['ffmpeg']['message'] = 'Не найден по пути: ' . $ffmpegPath;
    $allPassed = false;
}

// Check 5: Storage directories
$checks['storage'] = [
    'name' => 'Директории хранилища',
    'status' => 'success',
    'message' => 'OK'
];

$storagePath = __DIR__ . '/../storage';
$framesPath = $storagePath . '/frames';

if (!is_dir($storagePath)) {
    $checks['storage']['status'] = 'error';
    $checks['storage']['message'] = 'Директория storage не существует';
    $allPassed = false;
} elseif (!is_writable($storagePath)) {
    $checks['storage']['status'] = 'error';
    $checks['storage']['message'] = 'Директория storage не доступна для записи';
    $allPassed = false;
} else {
    if (!is_dir($framesPath)) {
        @mkdir($framesPath, 0777, true);
    }
    
    if (!is_writable($framesPath)) {
        $checks['storage']['status'] = 'error';
        $checks['storage']['message'] = 'Директория frames не доступна для записи';
        $allPassed = false;
    } else {
        $checks['storage']['message'] = 'OK (storage: ' . $storagePath . ', frames: ' . $framesPath . ')';
    }
}

// Check 6: Collections
$checks['collections'] = [
    'name' => 'Подборки',
    'status' => 'warning',
    'message' => 'Нет активных подборок'
];

if ($db) {
    try {
        $collections = $db->fetchAll("SELECT COUNT(*) as cnt FROM collections WHERE enabled = 1");
        $count = $collections[0]['cnt'] ?? 0;
        if ($count > 0) {
            $checks['collections']['status'] = 'success';
            $checks['collections']['message'] = 'Активных подборок: ' . $count;
        } else {
            $allPassed = false;
        }
    } catch (Exception $e) {
        $checks['collections']['status'] = 'error';
        $checks['collections']['message'] = 'Ошибка: ' . $e->getMessage();
        $allPassed = false;
    }
}

// Check 7: Schedules
$checks['schedules'] = [
    'name' => 'Расписания',
    'status' => 'warning',
    'message' => 'Нет активных расписаний'
];

if ($db) {
    try {
        $schedules = $db->fetchAll("SELECT COUNT(*) as cnt FROM schedules WHERE active = 1");
        $count = $schedules[0]['cnt'] ?? 0;
        if ($count > 0) {
            $checks['schedules']['status'] = 'success';
            $checks['schedules']['message'] = 'Активных расписаний: ' . $count;
        } else {
            $allPassed = false;
        }
    } catch (Exception $e) {
        $checks['schedules']['status'] = 'error';
        $checks['schedules']['message'] = 'Ошибка: ' . $e->getMessage();
        $allPassed = false;
    }
}

// Check 8: Cron setup
$checks['cron'] = [
    'name' => 'Настройка Cron',
    'status' => 'info',
    'message' => 'Проверьте вручную: crontab -l'
];

$cronFile = __DIR__ . '/../api/cron.php';
if (file_exists($cronFile) && is_readable($cronFile)) {
    $checks['cron']['message'] = 'Файл cron.php существует и доступен';
} else {
    $checks['cron']['status'] = 'error';
    $checks['cron']['message'] = 'Файл cron.php не найден';
    $allPassed = false;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проверка системы</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Проверка системы</h1>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Назад
            </a>
        </div>
        
        <?php if ($allPassed): ?>
            <div class="alert alert-success">
                <h5><i class="bi bi-check-circle"></i> Все проверки пройдены!</h5>
                <p>Система готова к работе.</p>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <h5><i class="bi bi-exclamation-triangle"></i> Обнаружены проблемы</h5>
                <p>Исправьте отмеченные проблемы перед использованием системы.</p>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Проверка</th>
                            <th>Статус</th>
                            <th>Сообщение</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($checks as $check): ?>
                        <tr>
                            <td><?= h($check['name']) ?></td>
                            <td>
                                <?php if ($check['status'] === 'success'): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> OK
                                    </span>
                                <?php elseif ($check['status'] === 'error'): ?>
                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle"></i> Ошибка
                                    </span>
                                <?php elseif ($check['status'] === 'warning'): ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-exclamation-triangle"></i> Предупреждение
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-info">
                                        <i class="bi bi-info-circle"></i> Инфо
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= h($check['message']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Рекомендации</h5>
            </div>
            <div class="card-body">
                <ul>
                    <li>Убедитесь, что YouTube API ключ настроен и работает</li>
                    <li>Проверьте, что yt-dlp и ffmpeg установлены и доступны</li>
                    <li>Создайте хотя бы одну активную подборку</li>
                    <li>Настройте расписание для автоматического сбора</li>
                    <li>Проверьте настройку cron: <code>crontab -l</code></li>
                    <li>Убедитесь, что директории storage доступны для записи</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
