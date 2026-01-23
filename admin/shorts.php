<?php
require_once __DIR__ . '/common.php';
checkAuth();

$db = getDB();

// Pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filters
$collectionId = $_GET['collection_id'] ?? null;
$search = $_GET['search'] ?? '';

$where = ['1=1'];
$params = [];

if ($collectionId) {
    $where[] = 's.collection_id = ?';
    $params[] = $collectionId;
}

if ($search) {
    $where[] = '(s.title LIKE ? OR s.youtube_id LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereStr = implode(' AND ', $where);

// Get total count
$total = $db->fetchOne(
    "SELECT COUNT(*) as cnt FROM shorts s WHERE {$whereStr}",
    $params
)['cnt'];

// Get shorts
$shorts = $db->fetchAll(
    "SELECT s.*, c.name as collection_name 
     FROM shorts s 
     LEFT JOIN collections c ON s.collection_id = c.id 
     WHERE {$whereStr}
     ORDER BY s.created_at DESC 
     LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
);

// Get collections for filter
$collections = $db->fetchAll("SELECT id, name FROM collections ORDER BY name");

$totalPages = ceil($total / $perPage);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Найденные Shorts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <h1 class="mb-4">Найденные Shorts</h1>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Подборка</label>
                        <select name="collection_id" class="form-select">
                            <option value="">Все подборки</option>
                            <?php foreach ($collections as $col): ?>
                                <option value="<?= $col['id'] ?>" <?= $collectionId == $col['id'] ? 'selected' : '' ?>>
                                    <?= h($col['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Поиск</label>
                        <input type="text" name="search" class="form-control" 
                               value="<?= h($search) ?>" placeholder="Название или ID">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">Применить</button>
                            <a href="shorts.php" class="btn btn-secondary">Сбросить</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span>Всего: <?= number_format($total) ?> видео</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Кадр</th>
                                <th>Название</th>
                                <th>Просмотры</th>
                                <th>Просмотры/час</th>
                                <th>Подборка</th>
                                <th>Опубликовано</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shorts as $short): ?>
                            <tr>
                                <td>
                                    <a href="https://youtube.com/shorts/<?= h($short['youtube_id']) ?>" 
                                       target="_blank" class="text-decoration-none">
                                        <?= h($short['youtube_id']) ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($short['frame_path']): ?>
                                        <img src="../storage/<?= h($short['frame_path']) ?>" 
                                             alt="Frame" style="max-width: 100px; height: auto;">
                                    <?php else: ?>
                                        <span class="text-muted">Нет</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= h(mb_substr($short['title'], 0, 60)) ?><?= mb_strlen($short['title']) > 60 ? '...' : '' ?></td>
                                <td><?= formatNumber($short['views']) ?></td>
                                <td><strong><?= formatNumber($short['views_per_hour']) ?></strong></td>
                                <td><?= h($short['collection_name'] ?? '-') ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($short['published_at'])) ?></td>
                                <td>
                                    <a href="https://youtube.com/shorts/<?= h($short['youtube_id']) ?>" 
                                       target="_blank" class="btn btn-sm btn-primary">
                                        <i class="bi bi-play-circle"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&collection_id=<?= $collectionId ?>&search=<?= urlencode($search) ?>">Предыдущая</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&collection_id=<?= $collectionId ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&collection_id=<?= $collectionId ?>&search=<?= urlencode($search) ?>">Следующая</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
