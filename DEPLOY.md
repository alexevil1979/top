# Инструкция по деплою на VPS

## Подготовка сервера

### 1. Установка необходимого ПО

```bash
# Обновление системы
sudo apt update && sudo apt upgrade -y

# Установка PHP и расширений
sudo apt install -y php8.1 php8.1-cli php8.1-fpm php8.1-mysql php8.1-curl php8.1-mbstring php8.1-xml

# Установка Apache
sudo apt install -y apache2 libapache2-mod-php8.1

# Установка MySQL
sudo apt install -y mysql-server

# Установка yt-dlp
sudo pip3 install yt-dlp
# или
sudo curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp
sudo chmod a+rx /usr/local/bin/yt-dlp

# Установка ffmpeg
sudo apt install -y ffmpeg

# Установка certbot для SSL
sudo apt install -y certbot python3-certbot-apache
```

### 2. Настройка базы данных

```bash
# Вход в MySQL
sudo mysql -u root -p

# Выполнить SQL скрипт
source /ssd/www/top/sql/schema.sql

# Создать пользователя БД
CREATE USER 'shorts_user'@'localhost' IDENTIFIED BY 'qweasd333123';
GRANT ALL PRIVILEGES ON shorts_collector.* TO 'shorts_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Клонирование проекта

```bash
cd /ssd/www
git clone https://github.com/alexevil1979/top.git top
cd top
```

### 4. Настройка прав доступа

```bash
sudo chown -R www-data:www-data /ssd/www/top
sudo chmod -R 755 /ssd/www/top
sudo chmod -R 777 /ssd/www/top/storage
```

### 5. Настройка Apache

Создайте файл `/etc/apache2/sites-available/top.1tlt.ru.conf`:

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
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### 6. Настройка SSL

```bash
sudo certbot --apache -d top.1tlt.ru
```

Certbot автоматически настроит SSL и перенаправление с HTTP на HTTPS.

### 7. Настройка cron

```bash
sudo crontab -e
```

Добавьте строку:

```
0 3 * * * php /ssd/www/top/api/cron.php >> /ssd/www/top/storage/cron.log 2>&1
```

### 8. Первоначальная настройка

1. Откройте `https://top.1tlt.ru/admin/login.php`
2. Войдите:
   - Логин: `admin`
   - Пароль: `password`
3. **ВАЖНО**: Сразу измените пароль в `config/config.php`:
   ```php
   define('ADMIN_PASS_HASH', password_hash('ваш_новый_пароль', PASSWORD_DEFAULT));
   ```
4. Перейдите в "Настройки" и укажите:
   - YouTube Data API v3 ключ
   - Проверьте пути к yt-dlp и ffmpeg
5. Создайте подборки в разделе "Подборки"
6. Настройте расписания в разделе "Расписания"

## Обновление на VPS

```bash
cd /ssd/www/top
git pull origin main

# Проверьте изменения в config/config.php
# При необходимости обновите БД:
mysql -u shorts_user -pqweasd333123 shorts_collector < sql/schema.sql

# Перезапустите Apache (если нужно)
sudo systemctl restart apache2
```

## Проверка работы

1. Проверьте логи cron:
   ```bash
   tail -f /ssd/www/top/storage/cron.log
   ```

2. Проверьте логи ошибок PHP:
   ```bash
   tail -f /ssd/www/top/storage/error.log
   ```

3. Проверьте логи Apache:
   ```bash
   sudo tail -f /var/log/apache2/top_error.log
   ```

4. Проверьте выполнение расписаний в админ-панели → Главная → Последние запуски

## Устранение проблем

### yt-dlp не найден
```bash
which yt-dlp
# Если не найден, установите:
sudo pip3 install yt-dlp
```

### ffmpeg не найден
```bash
which ffmpeg
# Если не найден, установите:
sudo apt install -y ffmpeg
```

### Ошибки прав доступа
```bash
sudo chown -R www-data:www-data /ssd/www/top
sudo chmod -R 755 /ssd/www/top
sudo chmod -R 777 /ssd/www/top/storage
```

### Проблемы с БД
```bash
# Проверьте подключение:
mysql -u shorts_user -pqweasd333123 shorts_collector -e "SHOW TABLES;"
```

### Cron не выполняется
```bash
# Проверьте cron:
sudo crontab -l
# Проверьте права на файл:
ls -la /ssd/www/top/api/cron.php
```
