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
                    $db->insert('schedules', [
                        'collection_id' => (int)$_POST['collection_id'],
                        'run_time' => $_POST['run_time'],
                        'limit_per_run' => (int)$_POST['limit_per_run'],
                        'active' => isset($_POST['active']) ? 1 : 0
                    ]);
                    $message = 'Расписание создано';
                } catch (Exception $e) {
                    $error = 'Ошибка: ' . $e->getMessage();
                }
                break;
                
            case 'update':
                try {
                    $db->update('schedules', [
                        'collection_id' => (int)$_POST['collection_id'],
                        'run_time' => $_POST['run_time'],
                        'limit_per_run' => (int)$_POST['limit_per_run'],
                        'active' => isset($_POST['active']) ? 1 : 0
                    ], 'id = ?', [$_POST['id']]);
                    $message = 'Расписание обновлено';
                } catch (Exception $e) {
                    $error = 'Ошибка: ' . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    $db->delete('schedules', 'id = ?', [$_POST['id']]);
                    $message = 'Расписание удалено';
                } catch (Exception $e) {
                    $error = 'Ошибка: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get schedules
$schedules = $db->fetchAll(
    "SELECT s.*, c.name as collection_name 
     FROM schedules s 
     JOIN collections c ON s.collection_id = c.id 
     ORDER BY s.run_time ASC"
);

// Get collections for dropdown
$collections = $db->fetchAll("SELECT id, name FROM collections WHERE enabled = 1 ORDER BY name");

// Get schedule for edit
$editSchedule = null;
if (isset($_GET['edit'])) {
    $editSchedule = $db->fetchOne("SELECT * FROM schedules WHERE id = ?", [$_GET['edit']]);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление расписаниями</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Управление расписаниями</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="bi bi-plus-circle"></i> Создать расписание
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
                            <th>Подборка</th>
                            <th>Время запуска</th>
                            <th>Лимит</th>
                            <th>Последний запуск</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?= $schedule['id'] ?></td>
                            <td><?= h($schedule['collection_name']) ?></td>
                            <td><?= date('H:i', strtotime($schedule['run_time'])) ?></td>
                            <td><?= $schedule['limit_per_run'] ?></td>
                            <td><?= $schedule['last_run'] ? date('d.m.Y H:i', strtotime($schedule['last_run'])) : 'Никогда' ?></td>
                            <td>
                                <span class="badge bg-<?= $schedule['active'] ? 'success' : 'secondary' ?>">
                                    <?= $schedule['active'] ? 'Активно' : 'Неактивно' ?>
                                </span>
                            </td>
                            <td>
                                <a href="?edit=<?= $schedule['id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить расписание?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $schedule['id'] ?>">
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
                        <h5 class="modal-title"><?= $editSchedule ? 'Редактировать' : 'Создать' ?> расписание</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <?php if ($editSchedule): ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= $editSchedule['id'] ?>">
                        <?php else: ?>
                            <input type="hidden" name="action" value="create">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Подборка</label>
                            <select name="collection_id" class="form-select" required>
                                <option value="">Выберите подборку</option>
                                <?php foreach ($collections as $col): ?>
                                    <option value="<?= $col['id'] ?>" 
                                            <?= ($editSchedule && $editSchedule['collection_id'] == $col['id']) ? 'selected' : '' ?>>
                                        <?= h($col['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Время запуска</label>
                            <input type="time" name="run_time" class="form-control" 
                                   value="<?= $editSchedule ? date('H:i', strtotime($editSchedule['run_time'])) : '03:00' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Лимит видео за запуск</label>
                            <input type="number" name="limit_per_run" class="form-control" 
                                   value="<?= $editSchedule ? $editSchedule['limit_per_run'] : 10 ?>" required min="1">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="active" class="form-check-input" id="active" 
                                       <?= ($editSchedule && $editSchedule['active']) || !$editSchedule ? 'checked' : '' ?>>
                                <label class="form-check-label" for="active">Активно</label>
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
    
    <?php if ($editSchedule): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(document.getElementById('createModal')).show();
        });
    </script>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
