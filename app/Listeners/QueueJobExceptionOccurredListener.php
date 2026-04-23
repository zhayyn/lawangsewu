<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobExceptionOccurred;
use App\Services\DistributedTracingService;
use Illuminate\Support\Facades\Log;

/**
 * Queue Job Exception Listener
 * 
 * Handles exceptions during job execution.
 */
class QueueJobExceptionOccurredListener
{
    public function handle(JobExceptionOccurred $event)
    {
        try {
            $jobClass = class_basename($event->job->resolveName());

            // Log exception
            Log::error('Queue job exception occurred', [
                'job' => $jobClass,
                'job_id' => $event->job->getJobId(),
                'attempts' => $event->job->attempts(),
                'error' => $event->exception->getMessage(),
                'file' => $event->exception->getFile(),
                'line' => $event->exception->getLine(),
            ]);

            // Track with distributed tracing
            DistributedTracingService::logError(
                $event->exception,
                "Queue Job Exception: {$jobClass}"
            );

            // Alert on exception
            if ($this->shouldAlertException($event->exception)) {
                Log::critical('Queue job exception alert', [
                    'job' => $jobClass,
                    'error' => $event->exception->getMessage(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in QueueJobExceptionOccurredListener', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function shouldAlertException($exception)
    {
        // Alert on specific exception types
        return in_array(get_class($exception), [
            'Exception',
            'RuntimeException',
        ]);
    }
}
