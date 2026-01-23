# Инструкция по публикации на Git и обновлению на VPS

## Публикация на Git

### Репозиторий уже настроен

Репозиторий: **https://github.com/alexevil1979/top.git**

Код уже опубликован в этот репозиторий.

### Для обновления кода

```bash
cd /path/to/topsearch
git add .
git commit -m "Описание изменений"
git push origin main
```

## Обновление на VPS

### Первоначальная установка

```bash
# На VPS сервере
cd /ssd/www
git clone https://github.com/alexevil1979/top.git top
cd top

# Следуйте инструкциям из DEPLOY.md
```

### Обновление существующей установки

```bash
# На VPS сервере
cd /ssd/www/top

# Сохраните изменения в config/config.php (если были локальные правки)
cp config/config.php config/config.php.backup

# Получите обновления
git pull origin main

# Восстановите локальные изменения (если нужно)
# cp config/config.php.backup config/config.php

# Проверьте изменения в SQL схеме
# При необходимости обновите БД:
mysql -u shorts_user -pqweasd333123 shorts_collector < sql/schema.sql

# Установите правильные права
sudo chown -R www-data:www-data /ssd/www/top
sudo chmod -R 755 /ssd/www/top
sudo chmod -R 777 /ssd/www/top/storage

# Перезапустите Apache (если нужно)
sudo systemctl restart apache2
```

## Быстрая команда для обновления

Создайте скрипт `/ssd/www/top/update.sh`:

```bash
#!/bin/bash
cd /ssd/www/top
git pull https://github.com/alexevil1979/top.git main
mysql -u shorts_user -pqweasd333123 shorts_collector < sql/schema.sql 2>/dev/null
sudo chown -R www-data:www-data /ssd/www/top
sudo chmod -R 755 /ssd/www/top
sudo chmod -R 777 /ssd/www/top/storage
echo "Update completed!"
```

Сделайте его исполняемым:

```bash
chmod +x /ssd/www/top/update.sh
```

Использование:

```bash
sudo /ssd/www/top/update.sh
```
