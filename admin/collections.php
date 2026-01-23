<?php
require_once __DIR__ . '/common.php';
checkAuth();

$db = getDB();
$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                try {
                    $db->insert('collections', [
                        'name' => $_POST['name'],
                        'keywords' => $_POST['keywords'],
                        'min_views_per_hour' => (int)$_POST['min_views_per_hour'],
                        'enabled' => isset($_POST['enabled']) ? 1 : 0
                    ]);
                    $message = 'Подборка создана';
                } catch (Exception $e) {
                    $error = 'Ошибка: ' . $e->getMessage();
                }
                break;
                
            case 'update':
                try {
                    $db->update('collections', [
                        'name' => $_POST['name'],
                        'keywords' => $_POST['keywords'],
                        'min_views_per_hour' => (int)$_POST['min_views_per_hour'],
                        'enabled' => isset($_POST['enabled']) ? 1 : 0
                    ], 'id = ?', [$_POST['id']]);
                    $message = 'Подборка обновлена';
                } catch (Exception $e) {
                    $error = 'Ошибка: ' . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    $db->delete('collections', 'id = ?', [$_POST['id']]);
                    $message = 'Подборка удалена';
                } catch (Exception $e) {
                    $error = 'Ошибка: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get collections
$collections = $db->fetchAll("SELECT * FROM collections ORDER BY id DESC");

// Get collection for edit
$editCollection = null;
if (isset($_GET['edit'])) {
    $editCollection = $db->fetchOne("SELECT * FROM collections WHERE id = ?", [$_GET['edit']]);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление подборками</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Управление подборками</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="bi bi-plus-circle"></i> Создать подборку
            </button>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= h($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?= h($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Ключевые слова</th>
                            <th>Мин. просмотров/час</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($collections as $col): ?>
                        <tr>
                            <td><?= $col['id'] ?></td>
                            <td><?= h($col['name']) ?></td>
                            <td><?= h($col['keywords']) ?></td>
                            <td><?= number_format($col['min_views_per_hour']) ?></td>
                            <td>
                                <span class="badge bg-<?= $col['enabled'] ? 'success' : 'secondary' ?>">
                                    <?= $col['enabled'] ? 'Включена' : 'Выключена' ?>
                                </span>
                            </td>
                            <td>
                                <a href="?edit=<?= $col['id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить подборку?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $col['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Create/Edit Modal -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= $editCollection ? 'Редактировать' : 'Создать' ?> подборку</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <?php if ($editCollection): ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= $editCollection['id'] ?>">
                        <?php else: ?>
                            <input type="hidden" name="action" value="create">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Название</label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?= $editCollection ? h($editCollection['name']) : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ключевые слова (через запятую)</label>
                            <input type="text" name="keywords" class="form-control" 
                                   value="<?= $editCollection ? h($editCollection['keywords']) : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Минимальный просмотров в час</label>
                            <input type="number" name="min_views_per_hour" class="form-control" 
                                   value="<?= $editCollection ? $editCollection['min_views_per_hour'] : 1000 ?>" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="enabled" class="form-check-input" id="enabled" 
                                       <?= ($editCollection && $editCollection['enabled']) || !$editCollection ? 'checked' : '' ?>>
                                <label class="form-check-label" for="enabled">Включена</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php if ($editCollection): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(document.getElementById('createModal')).show();
        });
    </script>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
