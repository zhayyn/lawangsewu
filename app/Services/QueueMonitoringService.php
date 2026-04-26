<?php

namespace App\Services;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Queue Monitoring Service
 * 
 * Monitors background job queue:
 * - Job success/failure tracking
 * - Retry logic implementation
 * - Queue health monitoring
 * - Failed job alerting
 */
class QueueMonitoringService
{
    /**
     * Monitor queue depth and health
     */
    public static function getQueueHealth()
    {
        return [
            'pending_jobs' => self::getPendingJobCount(),
            'failed_jobs' => self::getFailedJobCount(),
            'queue_driver' => config('queue.default'),
            'worker_status' => self::checkWorkerStatus(),
            'average_processing_time' => self::getAverageProcessingTime(),
        ];
    }

    /**
     * Get count of pending jobs
     */
    private static function getPendingJobCount()
    {
        try {
            return DB::table('jobs')->count();
        } catch (\Exception $e) {
            Log::error('Failed to get pending job count', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get count of failed jobs
     */
    private static function getFailedJobCount()
    {
        try {
            return DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            Log::error('Failed to get failed job count', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Check if queue workers are running
     */
    private static function checkWorkerStatus()
    {
        try {
            // For database driver, check if any jobs are being processed
            $activeJobs = DB::table('jobs')
                ->where('attempts', '>', 0)
                ->count();

            return [
                'is_active' => $activeJobs > 0 || self::getPendingJobCount() === 0,
                'active_jobs' => $activeJobs,
            ];
        } catch (\Exception $e) {
            return ['is_active' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get average job processing time
     */
    private static function getAverageProcessingTime()
    {
        try {
            $processed = DB::table('failed_jobs')
                ->selectRaw('AVG(UNIX_TIMESTAMP(failed_at) - UNIX_TIMESTAMP(created_at)) as avg_time')
                ->first();

            return $processed ? round($processed->avg_time, 2) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Track job execution
     */
    public static function trackJobExecution($jobName, $duration, $success = true, $data = [])
    {
        Log::info('Job execution tracked', [
            'job_name' => $jobName,
            'duration_ms' => $duration,
            'success' => $success,
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ]);

        // Store metrics for monitoring
        cache()->increment('job_execution:' . $jobName, 1, 3600);
    }

    /**
     * Handle job failure with retry logic
     */
    public static function handleJobFailure($job, $exception, $maxRetries = 3)
    {
        $attempts = $job->attempts();

        Log::error('Job failed', [
            'job_id' => $job->getJobId(),
            'job_name' => $job->getName(),
            'attempt' => $attempts,
            'max_retries' => $maxRetries,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        if ($attempts < $maxRetries) {
            // Retry with exponential backoff
            $delay = 2 ** ($attempts - 1); // 1s, 2s, 4s, 8s...
            Log::info('Job retrying', [
                'job_id' => $job->getJobId(),
                'delay_seconds' => $delay,
            ]);
            
            return $job->release($delay);
        }

        // Max retries exceeded - move to failed jobs table
        $job->fail($exception);
        
        // Alert on critical failures
        self::alertCriticalJobFailure($job, $exception);
    }

    /**
     * Alert when critical job fails
     */
    private static function alertCriticalJobFailure($job, $exception)
    {
        $criticalJobs = [
            'SendWaCarakaOutboundMessage',
            'FetchWaRuntimeContactMetadata',
            'SyncSippCases',
        ];

        $jobName = class_basename($job->getName());

        if (in_array($jobName, $criticalJobs)) {
            Log::critical('Critical job failed after retries', [
                'job_name' => $jobName,
                'error' => $exception->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Send alert notification (Slack, email, etc)
            // Notification::route('slack', config('services.slack.alert_webhook'))
            //     ->notify(new CriticalJobFailedNotification($jobName, $exception));
        }
    }

    /**
     * Get job statistics for dashboard
     */
    public static function getJobStatistics($days = 7)
    {
        try {
            $failedJobs = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subDays($days))
                ->selectRaw('DATE(failed_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->get();

            return [
                'failed_jobs_by_date' => $failedJobs,
                'total_failed' => $failedJobs->sum('count'),
                'success_rate' => self::calculateSuccessRate(),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Calculate overall job success rate
     */
    private static function calculateSuccessRate()
    {
        try {
            $total = DB::table('jobs')->count() + DB::table('failed_jobs')->count();
            if ($total === 0) return 100;

            $failed = DB::table('failed_jobs')->count();
            return round((($total - $failed) / $total) * 100, 2);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Clean up old failed jobs
     */
    public static function cleanupOldFailedJobs($days = 30)
    {
        try {
            $deleted = DB::table('failed_jobs')
                ->where('failed_at', '<', now()->subDays($days))
                ->delete();

            Log::info('Cleaned up old failed jobs', [
                'deleted_count' => $deleted,
                'days_old' => $days,
            ]);

            return $deleted;
        } catch (\Exception $e) {
            Log::error('Failed to cleanup old jobs', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Monitor specific job type
     */
    public static function monitorJobType($jobClass)
    {
        try {
            $pending = DB::table('jobs')
                ->where('payload', 'LIKE', '%' . class_basename($jobClass) . '%')
                ->count();

            $failed = DB::table('failed_jobs')
                ->where('payload', 'LIKE', '%' . class_basename($jobClass) . '%')
                ->count();

            return [
                'job_class' => $jobClass,
                'pending_count' => $pending,
                'failed_count' => $failed,
                'status' => $pending > 0 ? 'processing' : ($failed > 0 ? 'has_failures' : 'idle'),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
