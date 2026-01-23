# Исправление ошибки Git "dubious ownership"

## Проблема
```
fatal: detected dubious ownership in repository at '/ssd/www/top'
```

Эта ошибка возникает, когда Git обнаруживает, что репозиторий принадлежит другому пользователю, чем тот, который выполняет команду.

## Решения

### Вариант 1: Добавить директорию в safe.directory (рекомендуется)

```bash
sudo git config --global --add safe.directory /ssd/www/top
```

После этого можно выполнять git команды:
```bash
cd /ssd/www/top
git pull origin main
```

### Вариант 2: Изменить владельца репозитория

Если вы хотите, чтобы репозиторий принадлежал root:

```bash
sudo chown -R root:root /ssd/www/top/.git
```

Но файлы должны принадлежать www-data:
```bash
sudo chown -R www-data:www-data /ssd/www/top
sudo chown -R root:root /ssd/www/top/.git
```

### Вариант 3: Выполнять команды от правильного пользователя

Если репозиторий принадлежит www-data:

```bash
sudo -u www-data git -C /ssd/www/top pull origin main
```

## Рекомендуемая настройка

Для production сервера лучше использовать комбинацию:

```bash
# 1. Добавить в safe.directory
sudo git config --global --add safe.directory /ssd/www/top

# 2. Установить правильные права
sudo chown -R www-data:www-data /ssd/www/top
sudo chown -R root:root /ssd/www/top/.git
sudo chmod -R 755 /ssd/www/top
sudo chmod -R 777 /ssd/www/top/storage

# 3. Теперь можно обновлять от root
cd /ssd/www/top
git pull origin main
```

## Проверка

После настройки проверьте:

```bash
cd /ssd/www/top
git status
git pull origin main
```

Если ошибок нет - всё настроено правильно!
