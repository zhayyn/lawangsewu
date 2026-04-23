<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;

/**
 * Queue Job Retrying Listener
 * 
 * Tracks job retries.
 */
class QueueJobRetryingListener
{
    public function handle($event)
    {
        try {
            if (!method_exists($event, 'job')) {
                return;
            }

            $jobClass = class_basename($event->job->resolveName());

            // Record retry
            Log::info('Queue job retrying', [
                'job' => $jobClass,
                'job_id' => $event->job->getJobId(),
                'attempts' => $event->job->attempts(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in QueueJobRetryingListener', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
