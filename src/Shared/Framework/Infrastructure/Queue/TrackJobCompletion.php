<?php

declare(strict_types=1);

namespace Src\Shared\Framework\Infrastructure\Queue;

use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Redis;

class TrackJobCompletion
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
    public function handle(JobProcessed $event): void
    {

        $queueName = $event->job->getQueue();
        $today = date('Y-m-d');
        $connection = Redis::connection();
        $connection->incr("queue_jobs_completed:{$queueName}:today:{$today}");
    }
}
