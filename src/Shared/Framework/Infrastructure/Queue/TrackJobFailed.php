<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Infrastructure\Queue;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Redis;

class TrackJobFailed
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(JobFailed $event): void
    {

        $queueName = $event->job->getQueue();
        $today = date('Y-m-d');
        $connection = Redis::connection();
        $connection->incr("queue_jobs_failed:{$queueName}:today:{$today}");
    }
}
