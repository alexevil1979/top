<?php
require_once __DIR__ . '/common.php';
checkAuth();

$db = getDB();

// Statistics
$totalShorts = $db->fetchOne("SELECT COUNT(*) as cnt FROM shorts")['cnt'];
$totalCollections = $db->fetchOne("SELECT COUNT(*) as cnt FROM collections WHERE enabled = 1")['cnt'];
$activeSchedules = $db->fetchOne("SELECT COUNT(*) as cnt FROM schedules WHERE active = 1")['cnt'];
$recentShorts = $db->fetchAll(
    "SELECT s.*, c.name as collection_name 
     FROM shorts s 
     LEFT JOIN collections c ON s.collection_id = c.id 
     ORDER BY s.created_at DESC 
     LIMIT 10"
);
$recentLogs = $db->fetchAll(
    "SELECT el.*, c.name as collection_name 
     FROM execution_logs el 
     LEFT JOIN collections c ON el.collection_id = c.id 
     ORDER BY el.created_at DESC 
     LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - Главная</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <h1 class="mb-4">Панель управления</h1>
        
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Всего Shorts</h5>
                        <h2><?= number_format($totalShorts) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Активных подборок</h5>
                        <h2><?= $totalCollections ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Расписаний</h5>
                        <h2><?= $activeSchedules ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Последний запуск</h5>
                        <h6><?= $recentLogs[0]['created_at'] ?? 'Нет данных' ?></h6>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Shorts -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Последние найденные Shorts</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Название</th>
                                        <th>Просмотры/час</th>
                                        <th>Подборка</th>
                                        <th>Дата</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentShorts as $short): ?>
                                    <tr>
                                        <td><a href="https://youtube.com/shorts/<?= h($short['youtube_id']) ?>" target="_blank"><?= h($short['youtube_id']) ?></a></td>
                                        <td><?= h(mb_substr($short['title'], 0, 50)) ?>...</td>
                                        <td><?= formatNumber($short['views_per_hour']) ?></td>
                                        <td><?= h($short['collection_name'] ?? '-') ?></td>
                                        <td><?= date('d.m.Y H:i', strtotime($short['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Logs -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Последние запуски</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentLogs as $log): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted"><?= date('d.m H:i', strtotime($log['created_at'])) ?></small>
                                    <span class="badge bg-<?= $log['status'] === 'success' ? 'success' : ($log['status'] === 'error' ? 'danger' : 'warning') ?>">
                                        <?= h($log['status']) ?>
                                    </span>
                                </div>
                                <div class="mt-1">
                                    <small><?= h($log['collection_name'] ?? '-') ?></small>
                                    <br>
                                    <small class="text-muted"><?= h(mb_substr($log['message'], 0, 50)) ?>...</small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
