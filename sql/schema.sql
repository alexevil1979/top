-- YouTube Shorts Collector Database Schema
-- Production Ready

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `shorts_collector` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `shorts_collector`;

-- Таблица: подборки (collections)
CREATE TABLE IF NOT EXISTS `collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `keywords` text NOT NULL COMMENT 'Ключевые слова через запятую',
  `min_views_per_hour` int(11) DEFAULT 1000 COMMENT 'Минимальный порог просмотров в час',
  `enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица: расписания (schedules)
CREATE TABLE IF NOT EXISTS `schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collection_id` int(11) NOT NULL,
  `run_time` time NOT NULL COMMENT 'Время запуска (HH:MM:SS)',
  `limit_per_run` int(11) DEFAULT 10 COMMENT 'Лимит видео за запуск',
  `active` tinyint(1) DEFAULT 1,
  `last_run` timestamp NULL DEFAULT NULL,
  `next_run` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `collection_id` (`collection_id`),
  KEY `active` (`active`),
  KEY `next_run` (`next_run`),
  CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица: shorts (найденные видео)
CREATE TABLE IF NOT EXISTS `shorts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `youtube_id` varchar(20) NOT NULL,
  `title` varchar(500) NOT NULL,
  `description` text,
  `views` bigint(20) DEFAULT 0,
  `published_at` datetime NOT NULL,
  `views_per_hour` decimal(15,2) DEFAULT 0.00,
  `frame_path` varchar(500) DEFAULT NULL COMMENT 'Путь к первому кадру',
  `collection_id` int(11) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'Длительность в секундах',
  `channel_id` varchar(100) DEFAULT NULL,
  `channel_title` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `youtube_id` (`youtube_id`),
  KEY `collection_id` (`collection_id`),
  KEY `views_per_hour` (`views_per_hour`),
  KEY `published_at` (`published_at`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `shorts_ibfk_1` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица: настройки (settings)
CREATE TABLE IF NOT EXISTS `settings` (
  `key` varchar(100) NOT NULL,
  `value` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица: логи выполнения (execution_logs)
CREATE TABLE IF NOT EXISTS `execution_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) DEFAULT NULL,
  `collection_id` int(11) DEFAULT NULL,
  `status` enum('success','error','warning') DEFAULT 'success',
  `message` text,
  `shorts_found` int(11) DEFAULT 0,
  `execution_time` decimal(10,2) DEFAULT 0.00 COMMENT 'Время выполнения в секундах',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `schedule_id` (`schedule_id`),
  KEY `collection_id` (`collection_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `execution_logs_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE SET NULL,
  CONSTRAINT `execution_logs_ibfk_2` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставка начальных данных
INSERT INTO `settings` (`key`, `value`) VALUES
('youtube_api_key', ''),
('yt_dlp_path', '/usr/local/bin/yt-dlp'),
('ffmpeg_path', '/usr/bin/ffmpeg'),
('default_limit_per_run', '10'),
('shorts_max_duration', '60'),
('shorts_max_age_hours', '48');

-- Пример подборки
INSERT INTO `collections` (`name`, `keywords`, `min_views_per_hour`, `enabled`) VALUES
('Популярные Shorts', 'funny, viral, trending, shorts', 1000, 1);
