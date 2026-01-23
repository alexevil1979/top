<?php
/**
 * Cron Entry Point
 * Run: 0 3 * * * php /var/www/shorts/api/cron.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/DB.php';
require_once __DIR__ . '/../core/Scheduler.php';

// Set execution limits
set_time_limit(MAX_EXECUTION_TIME);
ini_set('memory_limit', MEMORY_LIMIT);

try {
    $scheduler = new Scheduler();
    $results = $scheduler->runDueSchedules();
    
    $logMessage = date('Y-m-d H:i:s') . " - Cron executed. Schedules run: " . count($results) . "\n";
    error_log($logMessage, 3, BASE_PATH . '/storage/cron.log');
    
    echo json_encode([
        'status' => 'success',
        'schedules_run' => count($results),
        'results' => $results
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    $logMessage = date('Y-m-d H:i:s') . " - Cron error: " . $e->getMessage() . "\n";
    error_log($logMessage, 3, BASE_PATH . '/storage/cron.log');
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
