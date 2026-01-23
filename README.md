# YouTube Shorts Collector

Production-ready система автоматического поиска топовых YouTube Shorts с ежедневным сбором данных, админ-панелью и планировщиком задач.

## 🚀 Возможности

- Автоматический поиск топовых YouTube Shorts
- Работа по расписанию (cron)
- Сохранение данных в MySQL
- Извлечение первого кадра видео
- Админ-панель управления
- Готовность к работе на VPS с SSL

## 📋 Требования

- PHP 8.x
- MySQL / MariaDB
- YouTube Data API v3 ключ
- yt-dlp
- ffmpeg
- Apache / nginx
- SSL сертификат (Let's Encrypt)

## 📦 Установка

### 1. Клонирование и настройка

```bash
cd /ssd/www/top
git clone <repository-url> .
```

### 2. Установка зависимостей системы

```bash
# yt-dlp
sudo pip3 install yt-dlp
# или
sudo curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp
sudo chmod a+rx /usr/local/bin/yt-dlp

# ffmpeg
sudo apt update
sudo apt install -y ffmpeg
```

### 3. Настройка базы данных

```bash
mysql -u root -p < sql/schema.sql
```

Создайте пользователя БД:
```sql
CREATE USER 'shorts_user'@'localhost' IDENTIFIED BY 'qweasd333123';
GRANT ALL PRIVILEGES ON shorts_collector.* TO 'shorts_user'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Настройка конфигурации

Отредактируйте `config/config.php`:
- Настройте параметры БД
- Установите пути к yt-dlp и ffmpeg
- Настройте BASE_URL

### 5. Настройка Apache

Создайте виртуальный хост `/etc/apache2/sites-available/top.1tlt.ru.conf`:

```apache
<VirtualHost *:80>
    ServerName top.1tlt.ru
    DocumentRoot /ssd/www/top
    
    <Directory /ssd/www/top>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/top_error.log
    CustomLog ${APACHE_LOG_DIR}/top_access.log combined
</VirtualHost>
```

Активируйте сайт:
```bash
sudo a2ensite top.1tlt.ru.conf
sudo systemctl reload apache2
```

### 6. SSL сертификат

```bash
sudo certbot --apache -d top.1tlt.ru
```

### 7. Настройка cron

Добавьте в crontab:
```bash
crontab -e
```

Добавьте строку:
```
0 3 * * * php /ssd/www/top/api/cron.php >> /ssd/www/top/storage/cron.log 2>&1
```

### 8. Права доступа

```bash
chmod -R 755 /ssd/www/top
chmod -R 777 /ssd/www/top/storage
chown -R www-data:www-data /ssd/www/top
```

## 🔐 Первый вход

1. Откройте `https://top.1tlt.ru/admin/login.php`
2. Логин: `admin`
3. Пароль: `password` (сразу измените в `config/config.php`!)

## ⚙️ Настройка

1. Войдите в админ-панель
2. Перейдите в "Настройки"
3. Укажите YouTube Data API v3 ключ
4. Проверьте пути к yt-dlp и ffmpeg
5. Создайте подборки в разделе "Подборки"
6. Настройте расписания в разделе "Расписания"

## 📁 Структура проекта

```
shorts-collector/
 ├ api/              # API endpoints
 ├ admin/            # Админ-панель
 ├ core/             # Основные классы
 ├ storage/          # Хранилище (кадры, логи)
 ├ config/           # Конфигурация
 └ sql/              # SQL схемы
```

## 🔄 Обновление на VPS

```bash
cd /ssd/www/top
git pull origin main
# Проверьте изменения в config/config.php
# При необходимости обновите БД:
mysql -u shorts_user -p shorts_collector < sql/schema.sql
```

## 📝 Логи

- Cron: `storage/cron.log`
- Ошибки PHP: `storage/error.log`
- Логи выполнения: таблица `execution_logs` в БД

## 🛠 API Endpoints

- `POST /api/collect.php` - Ручной запуск сбора для подборки
- `POST /api/process.php` - Обработка всех активных подборок
- `GET /api/cron.php` - Запуск через cron

## 📊 База данных

- `collections` - Подборки
- `schedules` - Расписания
- `shorts` - Найденные видео
- `settings` - Настройки системы
- `execution_logs` - Логи выполнения

## 🔒 Безопасность

- Измените пароль админа в `config/config.php`
- Используйте сильные пароли для БД
- Настройте firewall
- Регулярно обновляйте систему

## 📞 Поддержка

При возникновении проблем проверьте:
1. Логи в `storage/`
2. Логи Apache
3. Логи cron
4. Таблицу `execution_logs` в БД
