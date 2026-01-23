# Исправление ошибки подключения к БД

## Проблема
```
DB Connection Error: SQLSTATE[HY000] [2002] No such file or directory
```

Эта ошибка означает, что PHP не может найти MySQL сокет или подключиться к MySQL серверу.

## Решения

### 1. Проверьте, запущен ли MySQL

```bash
sudo systemctl status mysql
# или
sudo systemctl status mariadb
```

Если не запущен:
```bash
sudo systemctl start mysql
sudo systemctl enable mysql
```

### 2. Найдите путь к MySQL сокету

```bash
mysql_config --socket
# или
sudo find /var -name "mysql.sock" 2>/dev/null
# или
sudo find /run -name "mysqld.sock" 2>/dev/null
```

### 3. Исправьте config.php

Откройте `/ssd/www/top/config/config.php` и измените:

```php
// Вариант 1: Используйте 127.0.0.1 (принудительно TCP)
define('DB_HOST', '127.0.0.1');

// Вариант 2: Если нашли сокет, оставьте localhost
// Код автоматически найдет сокет
define('DB_HOST', 'localhost');
```

### 4. Проверьте, что БД создана

```bash
mysql -u root -p -e "SHOW DATABASES;"
```

Если базы `shorts_collector` нет:
```bash
cd /ssd/www/top
mysql -u root -p < sql/schema.sql
```

### 5. Проверьте пользователя БД

```bash
mysql -u root -p -e "SELECT User, Host FROM mysql.user WHERE User='shorts_user';"
```

Если пользователя нет:
```sql
CREATE USER 'shorts_user'@'localhost' IDENTIFIED BY 'qweasd333123';
GRANT ALL PRIVILEGES ON shorts_collector.* TO 'shorts_user'@'localhost';
FLUSH PRIVILEGES;
```

### 6. Проверьте подключение вручную

```bash
mysql -u shorts_user -pqweasd333123 shorts_collector -e "SHOW TABLES;"
```

### 7. Используйте диагностический скрипт

Откройте в браузере: `https://top.1tlt.ru/admin/db_check.php`

Этот скрипт покажет:
- Найденные сокеты
- Попытку подключения
- Детальную информацию об ошибке

### 8. Проверьте права на сокет

```bash
ls -la /var/run/mysqld/mysqld.sock
# или
ls -la /var/lib/mysql/mysql.sock
```

Права должны быть доступны для пользователя www-data или все (666).

### 9. Альтернативное решение: используйте TCP

В `config/config.php`:
```php
define('DB_HOST', '127.0.0.1'); // Принудительно TCP вместо сокета
```

И убедитесь, что MySQL слушает на 127.0.0.1:
```bash
sudo netstat -tlnp | grep mysql
# или
sudo ss -tlnp | grep mysql
```

Должно быть:
```
127.0.0.1:3306
```

Если нет, проверьте `/etc/mysql/mysql.conf.d/mysqld.cnf`:
```ini
bind-address = 127.0.0.1
```

После изменений:
```bash
sudo systemctl restart mysql
```

## Быстрая проверка

Выполните все команды по порядку:

```bash
# 1. Проверка MySQL
sudo systemctl status mysql

# 2. Создание БД (если нужно)
cd /ssd/www/top
mysql -u root -p < sql/schema.sql

# 3. Создание пользователя (если нужно)
mysql -u root -p << EOF
CREATE USER IF NOT EXISTS 'shorts_user'@'localhost' IDENTIFIED BY 'qweasd333123';
GRANT ALL PRIVILEGES ON shorts_collector.* TO 'shorts_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# 4. Проверка подключения
mysql -u shorts_user -pqweasd333123 shorts_collector -e "SHOW TABLES;"

# 5. Изменение config.php
nano /ssd/www/top/config/config.php
# Измените DB_HOST на '127.0.0.1'

# 6. Проверка в браузере
# Откройте https://top.1tlt.ru/admin/db_check.php
```

## После исправления

1. Обновите код на сервере:
```bash
cd /ssd/www/top
git pull origin main
```

2. Проверьте админ-панель:
```
https://top.1tlt.ru/admin/index.php
```

3. Удалите диагностические файлы (опционально):
```bash
rm /ssd/www/top/admin/db_check.php
rm /ssd/www/top/admin/debug.php
rm /ssd/www/top/admin/test_login.php
```
