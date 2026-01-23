<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">Shorts Collector</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="index.php"><i class="bi bi-house"></i> Главная</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="collections.php"><i class="bi bi-folder"></i> Подборки</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="schedules.php"><i class="bi bi-clock"></i> Расписания</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="shorts.php"><i class="bi bi-play-circle"></i> Shorts</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> Настройки</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="navbar-text me-3"><?= h($_SESSION['admin_username']) ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="?logout=1">Выход</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php
if (isset($_GET['logout'])) {
    logout();
}
?>
