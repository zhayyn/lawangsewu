<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use App\Services\QueueMonitoringService;
use App\Services\PrometheusMetricsService;
use App\Services\ErrorTrackingService;
use Illuminate\Support\Facades\Log;

/**
 * Queue Job Failure Listener
 * 
 * Handles job failures and implements retry logic.
 */
class QueueJobFailedListener
{
    public function handle(JobFailed $event)
    {
        try {
            $jobClass = class_basename($event->job->resolveName());

            // Record failure metrics
            PrometheusMetricsService::recordQueueJob(
                $jobClass,
                'failed',
                0
            );

            // Handle failure with retry logic
            QueueMonitoringService::handleJobFailure(
                $event->job,
                $event->exception
            );

            // Track error
            ErrorTrackingService::captureException($event->exception, [
                'job' => $jobClass,
                'attempts' => $event->job->attempts(),
            ]);

            // Log failure
            Log::error('Queue job failed', [
                'job' => $jobClass,
                'job_id' => $event->job->getJobId(),
                'attempts' => $event->job->attempts(),
                'error' => $event->exception->getMessage(),
            ]);

            // Alert critical job failures
            if ($this->isCriticalJob($jobClass)) {
                QueueMonitoringService::alertCriticalJobFailure($jobClass, $event->exception);
            }
        } catch (\Exception $e) {
            Log::error('Error in QueueJobFailedListener', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function isCriticalJob($jobClass)
    {
        $criticalJobs = [
            'SendWaCarakaOutboundMessage',
            'FetchWaRuntimeContactMetadata',
            'SyncSippCases',
        ];

        return in_array($jobClass, $criticalJobs);
    }
}
