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
            // resolveName() mengembalikan string nama class, bukan object
            $jobClass = $event->job->resolveName();
            $jobName  = class_basename($jobClass);

            // Record success metrics
            PrometheusMetricsService::recordQueueJob(
                $jobName,
                'succeeded',
                0
            );

            // Log success
            Log::info('Queue job succeeded', [
                'job' => $jobName,
                'job_id' => $event->job->getJobId(),
                'attempts' => $event->job->attempts(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in QueueJobSucceededListener', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
