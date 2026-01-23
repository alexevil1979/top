<?php
/**
 * Process All Collections API
 * POST /api/process.php
 * Body: { "limit_per_collection": 10 }
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/DB.php';
require_once __DIR__ . '/../core/ShortsFinder.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$limit = (int)($input['limit_per_collection'] ?? DEFAULT_LIMIT_PER_RUN);

try {
    set_time_limit(MAX_EXECUTION_TIME);
    ini_set('memory_limit', MEMORY_LIMIT);
    
    $finder = new ShortsFinder();
    $results = $finder->processAllCollections($limit);
    
    echo json_encode([
        'status' => 'success',
        'results' => $results
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
