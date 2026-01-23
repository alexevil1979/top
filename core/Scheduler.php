<?php
/**
 * Task Scheduler
 * Manages scheduled collection runs
 */

class Scheduler {
    private $db;
    
    public function __construct() {
        $this->db = DB::getInstance();
    }
    
    /**
     * Get schedules that should run now
     */
    public function getDueSchedules() {
        $now = date('H:i:s');
        $today = date('Y-m-d');
        
        $schedules = $this->db->fetchAll(
            "SELECT s.*, c.name as collection_name 
             FROM schedules s
             JOIN collections c ON s.collection_id = c.id
             WHERE s.active = 1 
             AND c.enabled = 1
             AND s.run_time <= ?
             AND (s.last_run IS NULL OR DATE(s.last_run) < ? OR (DATE(s.last_run) = ? AND TIME(s.last_run) < s.run_time))
             ORDER BY s.run_time ASC",
            [$now, $today, $today]
        );
        
        return $schedules;
    }
    
    /**
     * Execute a schedule
     */
    public function executeSchedule($scheduleId) {
        $schedule = $this->db->fetchOne(
            "SELECT * FROM schedules WHERE id = ? AND active = 1",
            [$scheduleId]
        );
        
        if (!$schedule) {
            throw new Exception("Schedule not found or inactive");
        }
        
        $startTime = microtime(true);
        $finder = new ShortsFinder();
        
        try {
            $result = $finder->findShortsForCollection(
                $schedule['collection_id'],
                $schedule['limit_per_run']
            );
            
            $executionTime = round(microtime(true) - $startTime, 2);
            
            // Update schedule
            $this->db->update('schedules', [
                'last_run' => date('Y-m-d H:i:s'),
                'next_run' => $this->calculateNextRun($schedule['run_time'])
            ], 'id = ?', [$scheduleId]);
            
            // Log success
            $this->db->insert('execution_logs', [
                'schedule_id' => $scheduleId,
                'collection_id' => $schedule['collection_id'],
                'status' => 'success',
                'message' => "Found {$result['found']} shorts, saved {$result['saved']}",
                'shorts_found' => $result['saved'],
                'execution_time' => $executionTime
            ]);
            
            return [
                'success' => true,
                'result' => $result,
                'execution_time' => $executionTime
            ];
            
        } catch (Exception $e) {
            $executionTime = round(microtime(true) - $startTime, 2);
            
            // Log error
            $this->db->insert('execution_logs', [
                'schedule_id' => $scheduleId,
                'collection_id' => $schedule['collection_id'],
                'status' => 'error',
                'message' => $e->getMessage(),
                'shorts_found' => 0,
                'execution_time' => $executionTime
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'execution_time' => $executionTime
            ];
        }
    }
    
    /**
     * Calculate next run time
     */
    private function calculateNextRun($runTime) {
        $now = new DateTime();
        $run = new DateTime($now->format('Y-m-d') . ' ' . $runTime);
        
        // If run time already passed today, schedule for tomorrow
        if ($run <= $now) {
            $run->modify('+1 day');
        }
        
        return $run->format('Y-m-d H:i:s');
    }
    
    /**
     * Run all due schedules
     */
    public function runDueSchedules() {
        $schedules = $this->getDueSchedules();
        $results = [];
        
        foreach ($schedules as $schedule) {
            $results[] = $this->executeSchedule($schedule['id']);
        }
        
        return $results;
    }
}
