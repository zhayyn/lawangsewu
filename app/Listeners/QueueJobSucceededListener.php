<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobProcessed;
use App\Services\PrometheusMetricsService;
use Illuminate\Support\Facades\Log;

/**
 * Queue Job Success Listener
 * 
 * Tracks successful job completions.
 */
class QueueJobSucceededListener
{
    public function handle(JobProcessed $event)
    {
        try {
            $jobClass = get_class($event->job->resolveName());
            $duration = $event->job->getReleaseTimestamp() - $event->job->getFailedAtTimestamp();

            // Record success metrics
            PrometheusMetricsService::recordQueueJob(
                class_basename($jobClass),
                'succeeded',
                $duration ?? 0
            );

            // Log success
            Log::info('Queue job succeeded', [
                'job' => class_basename($jobClass),
                'job_id' => $event->job->getJobId(),
                'attempts' => $event->job->attempts(),
                'duration_ms' => $duration,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in QueueJobSucceededListener', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
